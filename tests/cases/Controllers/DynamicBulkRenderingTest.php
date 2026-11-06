<?php
/**
 * CD-024 — اختبارات الرسم الديناميكي في التقييم الجماعي
 *
 * يثبت أن استقبال البيانات (Payload) يتم بشكل صحيح لجميع أنواع الحقول:
 *   - percentage: normalized_score = القيمة المدخلة
 *   - textarea: raw_value = النص، normalized_score = NULL
 *   - overall_grade: raw_value = التسمية، final_score = القيمة الرقمية من السلم
 *   - schema API: تُعيد options + grades لأقسام overall_grade
 */

use App\Core\Auth;
use App\Core\Database;
use App\Controllers\BulkEvaluationController;

class DynamicBulkRenderingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
    }

    private function loginEvaluator(): int
    {
        $db = Database::getInstance();
        $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        $userId = (int)$db->fetchColumn();
        $this->assertTrue($userId > 0, 'مطلوب مستخدم أساسي.');

        $_SESSION['user_id']          = $userId;
        $_SESSION['user_roles']       = ['Manager'];
        $_SESSION['user_permissions'] = ['evaluations.create'];
        $_SESSION['user_object']      = (object)[
            'id' => $userId, 'username' => 'cd024-test', 'full_name' => 'مختبر CD-024', 'employee_id' => 0,
        ];
        return $userId;
    }

    /**
     * إنشاء قالب رقمي + موظف + استدعاء saveSingle.
     * $results: النتائج المراد إرسالها للقسم.
     * يعيد evaluationId.
     */
    private function saveSingleWithResults(array $templateSections, array $results, string $mode = 'numeric'): int
    {
        $pdo = Database::getInstance()->getPdo();
        $stamp = 'CD024-' . bin2hex(random_bytes(3));

        // قالب
        $pdo->prepare('INSERT INTO form_templates (template_name, target_type, evaluation_mode, is_active, version)
                       VALUES (:name, "employee", :mode, 1, 1)')
            ->execute(['name' => 'قالب CD024 ' . $stamp, 'mode' => $mode]);
        $templateId = (int)$pdo->lastInsertId();

        $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index)
                       VALUES (:tid, "معيار CD024", 100, 1)')
            ->execute(['tid' => $templateId]);
        $criterionId = (int)$pdo->lastInsertId();

        $sectionIds = [];
        foreach ($templateSections as $sec) {
            $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, section_weight, min_value, max_value, evaluation_options, order_index)
                           VALUES (:cid, :name, :type, :weight, :min, :max, :opts, :ord)')
                ->execute([
                    'cid' => $criterionId,
                    'name' => $sec['name'],
                    'type' => $sec['type'],
                    'weight' => $sec['weight'] ?? 0,
                    'min' => $sec['min'] ?? null,
                    'max' => $sec['max'] ?? null,
                    'opts' => $sec['opts'] ?? null,
                    'ord' => $sec['ord'] ?? 1,
                ]);
            $sectionIds[$sec['key']] = (int)$pdo->lastInsertId();
        }

        // موظف
        $empCode = 'CD024-' . bin2hex(random_bytes(3));
        $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active, profile_status)
                       VALUES (:code, :name, 1, "complete")')
            ->execute(['code' => $empCode, 'name' => 'موظف CD024']);
        $employeeId = (int)$pdo->lastInsertId();

        // بناء $_POST بنتائج محددة لكل sectionId
        $creatorId = $this->fetchAnyUserId();
        $csrf = Auth::generateCsrfToken();
        $_POST = [
            '_token' => $csrf,
            'employee_id' => $employeeId,
            'template_id' => $templateId,
            'evaluation_date' => date('Y-m-d'),
            'evaluator_name' => 'مقيّم CD024',
            'evaluator_title' => 'مدير',
            'results' => [],
        ];
        foreach ($results as $key => $data) {
            $sid = $sectionIds[$key];
            $_POST['results'][$sid] = $data;
        }

        $level = ob_get_level();
        try {
            ob_start();
            (new BulkEvaluationController())->saveSingle();
            $raw = (string)ob_get_clean();
        } finally {
            while (ob_get_level() > $level) { ob_end_clean(); }
        }

        $resp = json_decode($raw, true);
        $this->assertTrue(is_array($resp), 'استجابة saveSingle ليست JSON: ' . substr($raw, 0, 200));
        $this->assertTrue(!empty($resp['success']), 'متوقع success=true. استجابة: ' . $raw);
        return (int)$resp['evaluation_id'];
    }

    private function fetchAnyUserId(): int
    {
        $db = Database::getInstance();
        $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        return (int)$db->fetchColumn();
    }

    // ====================================================================

    public function testPercentageSectionStoresNormalizedScore(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;

        try {
            $this->loginEvaluator();
            $evaluationId = $this->saveSingleWithResults(
                [['key' => 'pct', 'name' => 'نسبة', 'type' => 'percentage', 'weight' => 100]],
                ['pct' => ['score' => 75]]
            );

            $pdo = $db->getPdo();
            $stmt = $pdo->prepare('SELECT raw_value, normalized_score FROM evaluation_answers WHERE evaluation_id = :eid');
            $stmt->execute(['eid' => $evaluationId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->assertSame('75', (string)$row['raw_value']);
            $this->assertSame(75.0, (float)$row['normalized_score']);
        } finally {
            $_SESSION = $previousSession;
            $_POST = [];
            $db->rollBack();
        }
    }

    public function testTextareaSectionStoresTextWithNullScore(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;

        try {
            $this->loginEvaluator();
            $evaluationId = $this->saveSingleWithResults(
                [['key' => 'txt', 'name' => 'ملاحظات', 'type' => 'textarea', 'weight' => 0, 'opts' => json_encode(['max_chars' => 500])]],
                ['txt' => ['text' => 'أداء ممتاز مع التزام']]
            );

            $pdo = $db->getPdo();
            $stmt = $pdo->prepare('SELECT raw_value, normalized_score FROM evaluation_answers WHERE evaluation_id = :eid');
            $stmt->execute(['eid' => $evaluationId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->assertSame('أداء ممتاز مع التزام', (string)$row['raw_value']);
            $this->assertTrue($row['normalized_score'] === null, 'normalized_score يجب أن يكون NULL للقسم النصي.');
        } finally {
            $_SESSION = $previousSession;
            $_POST = [];
            $db->rollBack();
        }
    }

    public function testOverallGradeSectionComputesFinalScoreFromScale(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;

        try {
            $this->loginEvaluator();
            $evaluationId = $this->saveSingleWithResults(
                [
                    ['key' => 'txt', 'name' => 'ملاحظات', 'type' => 'textarea', 'weight' => 0],
                    ['key' => 'og', 'name' => 'التقدير العام', 'type' => 'overall_grade', 'weight' => 0, 'opts' => json_encode(['scale_name' => 'السلم الخماسي'])],
                ],
                [
                    'txt' => ['text' => 'وصف الأداء'],
                    'og'  => ['grade_label' => 'ممتاز'],
                ],
                'descriptive'
            );

            $pdo = $db->getPdo();

            // تحقق من إجابة overall_grade
            $stmt = $pdo->prepare("SELECT raw_value, normalized_score FROM evaluation_answers WHERE evaluation_id = :eid AND raw_value = 'ممتاز'");
            $stmt->execute(['eid' => $evaluationId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertSame('ممتاز', (string)$row['raw_value']);
            $this->assertTrue($row['normalized_score'] === null, 'normalized_score يجب أن يكون NULL للتقدير العام.');

            // تحقق من final_score = 5.0 (قيمة ممتاز في السلم الخماسي)
            $stmt2 = $pdo->prepare('SELECT final_score, final_grade_label, status FROM evaluations WHERE id = :eid');
            $stmt2->execute(['eid' => $evaluationId]);
            $eval = $stmt2->fetch(PDO::FETCH_ASSOC);
            $this->assertSame('completed', (string)$eval['status']);
            $this->assertSame('ممتاز', (string)$eval['final_grade_label']);
            $this->assertSame(5.0, (float)$eval['final_score'], 'final_score يجب أن يكون 5.0 (قيمة ممتاز).');
        } finally {
            $_SESSION = $previousSession;
            $_POST = [];
            $db->rollBack();
        }
    }

    public function testSchemaReturnsOptionsAndGradesForOverallGrade(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;

        try {
            $this->loginEvaluator();

            // إنشاء قالب وصفي مع overall_grade
            $pdo = $db->getPdo();
            $stamp = 'CD024S-' . bin2hex(random_bytes(3));
            $pdo->prepare('INSERT INTO form_templates (template_name, target_type, evaluation_mode, is_active, version)
                           VALUES (:name, "employee", "descriptive", 1, 1)')
                ->execute(['name' => 'قالب CD024 schema ' . $stamp]);
            $templateId = (int)$pdo->lastInsertId();

            $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index)
                           VALUES (:tid, "معيار", 100, 1)')
                ->execute(['tid' => $templateId]);
            $criterionId = (int)$pdo->lastInsertId();

            $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, section_weight, evaluation_options, order_index)
                           VALUES (:cid, "التقدير العام", "overall_grade", 0, :opts, 1)')
                ->execute(['cid' => $criterionId, 'opts' => json_encode(['scale_name' => 'السلم الخماسي'])]);

            $level = ob_get_level();
            ob_start();
            (new BulkEvaluationController())->schema($templateId);
            $raw = (string)ob_get_clean();
            while (ob_get_level() > $level) { ob_end_clean(); }

            $schema = json_decode($raw, true);
            $this->assertTrue(is_array($schema), 'استجابة schema ليست JSON.');
            $this->assertSame('descriptive', $schema['evaluation_mode']);
            $section = $schema['criteria'][0]['sections'][0];
            $this->assertSame('overall_grade', $section['type']);
            $this->assertSame('السلم الخماسي', $section['scale_name'] ?? '');
            $this->assertTrue(!empty($section['grades']), 'grades يجب أن تُعاد لأقسام overall_grade.');
            // السلم الخماسي له 5 تقديرات
            $this->assertTrue(count($section['grades']) >= 5, 'يجب أن يحتوي على 5 تقديرات على الأقل.');
        } finally {
            $_SESSION = $previousSession;
            while (ob_get_level() > 0) { ob_end_clean(); }
            $db->rollBack();
        }
    }
}
