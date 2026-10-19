<?php
/**
 * اختبارات محرك التقارير الديناميكي (app/Services/ReportEngine.php) — CD-008
 *
 * تغطي: whitelist الأبعاد/المقاييس/الفلاتر، prepared statements،
 * مطابقة نتائج المحرك لاستعلام SQL مباشر، والزمن (< 2 ثانية).
 */

use App\Services\ReportEngine;

class ReportEngineTest extends TestCase
{
    private function testPdo(): \PDO
    {
        return new \PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    // ======================================================================
    // Views (CD-008)
    // ======================================================================

    public function testViewsExist(): void
    {
        $this->skipIfDbUnavailable();
        $pdo = $this->testPdo();
        foreach (['vw_evaluation_facts', 'vw_employee_scores', 'vw_criteria_averages'] as $v) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.views WHERE table_schema = :db AND table_name = :v');
            $stmt->execute([':db' => DB_NAME, ':v' => $v]);
            $this->assertSame(1, (int)$stmt->fetchColumn(), "العرض $v غير موجود في قاعدة البيانات.");
        }
    }

    public function testFactsViewHasRows(): void
    {
        $this->skipIfDbUnavailable();
        $pdo = $this->testPdo();
        $this->assertGreaterThan(0, (int)$pdo->query('SELECT COUNT(*) FROM vw_evaluation_facts')->fetchColumn());
    }

    // ======================================================================
    // whitelist الأبعاد والفلاتر (§2.12 — سالب إثبات)
    // ======================================================================

    public function testInvalidDimensionIsIgnored(): void
    {
        $this->skipIfDbUnavailable();
        $engine = new ReportEngine();
        $res = $engine->build([], ['branch_name', 'evil_dim; DROP TABLE users--'], ['avg_score']);
        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('DROP', $res['meta']['sql'], 'اختراق whitelist الأبعاد: DROP ظهر في SQL.');
        $this->assertStringContainsString('branch_name', $res['meta']['sql']);
        $this->assertStringNotContainsString('evil_dim', $res['meta']['sql']);
    }

    public function testInvalidMeasureIsIgnored(): void
    {
        $this->skipIfDbUnavailable();
        $engine = new ReportEngine();
        $res = $engine->build([], ['branch_name'], ['avg_score', 'evil_measure']);
        $this->assertTrue($res['success']);
        $this->assertStringContainsString('avg_score', $res['meta']['sql']);
        $this->assertStringNotContainsString('evil_measure', $res['meta']['sql']);
    }

    public function testInvalidFilterIsIgnored(): void
    {
        $this->skipIfDbUnavailable();
        $engine = new ReportEngine();
        $res = $engine->build(['evil_filter' => '1; DELETE FROM users--', 'branch_id' => '1'], ['branch_name'], ['avg_score']);
        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('DELETE', $res['meta']['sql']);
        $this->assertArrayHasKey(':branch_id', $res['meta']['params']);
        $this->assertSame('1', $res['meta']['params'][':branch_id']);
    }

    public function testSqlInjectionAttemptIsParameterized(): void
    {
        $this->skipIfDbUnavailable();
        $engine = new ReportEngine();
        $payload = "1' OR '1'='1";
        $res = $engine->build(['branch_id' => $payload], ['branch_name'], ['avg_score']);
        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString("OR '1'='1", $res['meta']['sql'], 'قيمة الفلتر دخلت SQL حرفيًا بدل prepared statement.');
        $this->assertSame($payload, $res['meta']['params'][':branch_id']);
    }

    public function testEmptyDimensionsYieldsCountOnly(): void
    {
        $this->skipIfDbUnavailable();
        $engine = new ReportEngine();
        $res = $engine->build([], [], []);
        $this->assertTrue($res['success']);
        $this->assertStringContainsString('count_rows', $res['meta']['sql']);
    }

    // ======================================================================
    // مطابقة المحرك لاستعلام SQL مباشر (مقارنة PHP مقابل SQL)
    // ======================================================================

    public function testEngineMatchesDirectSqlForBranchAvg(): void
    {
        $this->skipIfDbUnavailable();
        $engine = new ReportEngine();
        $res = $engine->build([], ['branch_name'], ['avg_score', 'count_evaluations']);
        $this->assertTrue($res['success']);

        $pdo = $this->testPdo();
        $direct = $pdo->query(
            'SELECT v.branch_name AS branch_name, ROUND(AVG(v.score), 4) AS avg_score, COUNT(DISTINCT v.evaluation_id) AS count_evaluations
             FROM vw_evaluation_facts v GROUP BY v.branch_name ORDER BY branch_name'
        )->fetchAll(\PDO::FETCH_ASSOC);

        $engineRows = array_map(function ($r) {
            return (array)$r;
        }, $res['rows']);
        usort($engineRows, function ($a, $b) {
            return strcmp((string)$a['branch_name'], (string)$b['branch_name']);
        });
        $directSorted = $direct;
        usort($directSorted, function ($a, $b) {
            return strcmp((string)$a['branch_name'], (string)$b['branch_name']);
        });

        $this->assertSame(count($directSorted), count($engineRows), 'عدد صفوف المحرك يختلف عن SQL المباشر.');
        foreach ($directSorted as $i => $row) {
            $this->assertEquals($row['branch_name'], $engineRows[$i]['branch_name']);
            $this->assertEquals((float)$row['avg_score'], (float)$engineRows[$i]['avg_score'], 'متوسط الفرع غير مطابق.');
            $this->assertEquals((int)$row['count_evaluations'], (int)$engineRows[$i]['count_evaluations']);
        }
    }

    public function testEngineMatchesDirectSqlForYearFilter(): void
    {
        $this->skipIfDbUnavailable();
        $pdo = $this->testPdo();
        $year = (int)$pdo->query('SELECT eval_year FROM vw_evaluation_facts ORDER BY eval_year DESC LIMIT 1')->fetchColumn();
        if ($year === 0) {
            $this->markSkipped('لا توجد سنوات في البيانات.');
        }

        $engine = new ReportEngine();
        $res = $engine->build(['year' => (string)$year], ['month'], ['avg_score']);
        $this->assertTrue($res['success']);

        $stmt = $pdo->prepare(
            'SELECT v.eval_month AS month, ROUND(AVG(v.score), 4) AS avg_score
             FROM vw_evaluation_facts v WHERE v.eval_year = :y GROUP BY v.eval_month ORDER BY v.eval_month'
        );
        $stmt->execute([':y' => $year]);
        $direct = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertSame(count($direct), count($res['rows']), 'عدد صفوف فلتر السنة يختلف.');
        foreach ($direct as $i => $row) {
            $this->assertEquals((int)$row['month'], (int)$res['rows'][$i]->month);
            $this->assertEquals((float)$row['avg_score'], (float)$res['rows'][$i]->avg_score);
        }
    }

    // ======================================================================
    // الأداء (< 2 ثانية لكل استعلام — قياس microtime)
    // ======================================================================

    public function testPerformanceUnderTwoSeconds(): void
    {
        $this->skipIfDbUnavailable();
        $engine = new ReportEngine();
        $scenarios = [
            ['filters' => [], 'dimensions' => ['branch_name'], 'measures' => ['avg_score', 'count_evaluations']],
            ['filters' => [], 'dimensions' => ['department_name', 'year'], 'measures' => ['avg_score', 'min_score', 'max_score', 'count_employees']],
            ['filters' => [], 'dimensions' => ['criterion_name'], 'measures' => ['avg_score', 'count_evaluations']],
            ['filters' => [], 'dimensions' => ['employee_name', 'month'], 'measures' => ['avg_score']],
        ];
        foreach ($scenarios as $i => $s) {
            $t0 = microtime(true);
            $res = $engine->build($s['filters'], $s['dimensions'], $s['measures']);
            $elapsed = microtime(true) - $t0;
            $this->assertTrue($res['success'], 'السيناريو ' . ($i + 1) . ' فشل.');
            $this->assertLessThanOrEqual(2.0, $elapsed, sprintf('السيناريو %d استغرق %.3fs (الحد 2s).', $i + 1, $elapsed));
        }
    }
}
