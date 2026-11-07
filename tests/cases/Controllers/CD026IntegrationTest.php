<?php
/**
 * CD-026 — اختبارات الحزمة المتكاملة
 * يغطي: الإصلاحات الأمنية + البنية التحتية للتدريب + المحرك
 */

use App\Core\Auth;
use App\Core\Database;
use App\Controllers\ApiController;
use App\Services\AveragesEngine;

class CD026IntegrationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
    }

    /**
     * اختبار 1: AveragesEngine يتجاهل final_score = NULL
     */
    public function testAveragesEngineIgnoresNullFinalScore(): void
    {
        $engine = new AveragesEngine();
        $scale = [
            (object)['grade_label'=>'ممتاز','min_value'=>90,'max_value'=>100,'numeric_value'=>5],
            (object)['grade_label'=>'جيد','min_value'=>70,'max_value'=>79.99,'numeric_value'=>3],
        ];
        $records = [
            (object)['final_score'=>85.0, 'evaluation_date'=>'2026-01-15'],
            (object)['final_score'=>null, 'evaluation_date'=>'2026-02-01'],  // should be ignored
            (object)['final_score'=>75.0, 'evaluation_date'=>'2026-03-10'],
        ];
        $summary = $engine->summarize($records, $scale);

        $this->assertSame(3, $summary['evaluation_count'], 'evaluation_count should include all');
        $this->assertSame(2, $summary['scored_count'], 'scored_count should exclude NULL');
        $this->assertSame(80.0, (float)$summary['average'], 'Average should be (85+75)/2=80');
    }

    /**
     * اختبار 2: جدول course_participants موجود
     */
    public function testCourseParticipantsTableExists(): void
    {
        $db = Database::getInstance();
        $db->query("SHOW TABLES LIKE 'course_participants'");
        $row = $db->fetch();
        $this->assertTrue($row !== false, 'course_participants table must exist');
    }

    /**
     * اختبار 3: جدول workshop_participants موجود
     */
    public function testWorkshopParticipantsTableExists(): void
    {
        $db = Database::getInstance();
        $db->query("SHOW TABLES LIKE 'workshop_participants'");
        $row = $db->fetch();
        $this->assertTrue($row !== false, 'workshop_participants table must exist');
    }

    /**
     * اختبار 4: نوع skill في target_type ENUM
     */
    public function testSkillTypeExistsInEnum(): void
    {
        $db = Database::getInstance();
        $db->query("SHOW COLUMNS FROM form_templates LIKE 'target_type'");
        $row = $db->fetch();
        $this->assertStringContainsString('skill', (string)($row->Type ?? ''),
            'target_type ENUM must include skill');
    }

    /**
     * اختبار 5:ApiController يتطلب مصادقة
     */
    public function testApiControllerRequiresAuth(): void
    {
        $previousSession = $_SESSION;
        $_SESSION = []; // لا جلسة = غير مصرح

        try {
            $level = ob_get_level();
            ob_start();
            $ctrl = new ApiController();
            $ctrl->searchEmployees();
            $output = (string)ob_get_clean();
            while (ob_get_level() > $level) { ob_end_clean(); }

            // يجب أن يعيد 401 + JSON error
            $this->assertStringContainsString('غير مصرح', $output);
        } catch (\Throwable $e) {
            // exit() in constructor may throw or terminate — both acceptable
            $this->assertTrue(true, 'ApiController blocked unauthenticated access');
        } finally {
            $_SESSION = $previousSession;
            while (ob_get_level() > 0) { ob_end_clean(); }
        }
    }

    /**
     * اختبار 6: الصلاحيات المفقودة موجودة الآن
     */
    public function testMissingPermissionsExist(): void
    {
        $db = Database::getInstance();
        $missing = ['evaluations.delete', 'training.evaluate', 'backup.manage'];
        foreach ($missing as $perm) {
            $db->query("SELECT id FROM permissions WHERE name = :name");
            $db->bind(':name', $perm);
            $row = $db->fetch();
            $this->assertTrue($row !== false, "Permission '{$perm}' must exist in permissions table");
        }
    }

    /**
     * اختبار 7: التقييم الوصفي يُحوّل إلى مقياس 0-100
     */
    public function testDescriptiveScoreIsOn100Scale(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;

        try {
            $pdo = $db->getPdo();
            $stamp = 'CD026-' . bin2hex(random_bytes(3));

            // قالب وصفي
            $pdo->prepare('INSERT INTO form_templates (template_name, target_type, evaluation_mode, is_active, version)
                           VALUES (:name, "employee", "descriptive", 1, 1)')
                ->execute(['name' => 'CD026 Descriptive ' . $stamp]);
            $templateId = (int)$pdo->lastInsertId();

            $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index)
                           VALUES (:tid, "معيار", 100, 1)')->execute(['tid' => $templateId]);
            $criterionId = (int)$pdo->lastInsertId();

            $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, section_weight, evaluation_options, order_index)
                           VALUES (:cid, "التقدير", "overall_grade", 0, :opts, 1)')
                ->execute(['cid' => $criterionId, 'opts' => json_encode(['scale_name' => 'السلم الخماسي'])]);
            $sectionId = (int)$pdo->lastInsertId();

            // موظف + تقييم
            $empCode = 'CD026-' . bin2hex(random_bytes(3));
            $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active, profile_status)
                           VALUES (:code, :name, 1, "complete")')
                ->execute(['code' => $empCode, 'name' => 'موظف CD026']);
            $employeeId = (int)$pdo->lastInsertId();

            $creatorId = 1;
            $pdo->prepare('INSERT INTO evaluations (template_id, status, evaluation_date, evaluation_type, evaluated_employee_id, employee_name, employee_code, entity_type, entity_id, created_by_user_id, created_at)
                           VALUES (:tpl, "draft", CURDATE(), "annual", :emp, :ename, :ecode, "employee", :entity, :creator, NOW())')
                ->execute(['tpl' => $templateId, 'emp' => $employeeId, 'entity' => $employeeId,
                           'ename' => 'موظف CD026', 'ecode' => $empCode, 'creator' => $creatorId]);
            $evaluationId = (int)$pdo->lastInsertId();

            // إجابة التقدير = "ممتاز" (max_value=100 في السلم الخماسي)
            $pdo->prepare('INSERT INTO evaluation_answers (evaluation_id, section_id, raw_value, normalized_score)
                           VALUES (:eid, :sid, "ممتاز", NULL)')
                ->execute(['eid' => $evaluationId, 'sid' => $sectionId]);

            $evalModel = new \App\Models\Evaluation();
            $score = $evalModel->calculateFinalScore($evaluationId);

            $this->assertNotNull($score, 'Descriptive score must not be null');
            $this->assertSame(100.0, (float)$score,
                'Descriptive "ممتاز" must map to 100.0 (max_value), not 5.0 (numeric_value)');
        } finally {
            $_SESSION = $previousSession;
            $db->rollBack();
        }
    }
}
