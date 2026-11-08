<?php
/**
 * CD-023 — اختبارات القوالب الوصفية (Descriptive Templates)
 *
 * يغطي ثلاثة محاور:
 *   1. validateWeights: القالب الوصفي يُعفى من شرط الـ 100% ويُشترط فيه "التقدير العام".
 *   2. calculateFinalScore: القسم النصي (textarea) لا يؤثر على الدرجة النهائية.
 *   3. calculateFinalScore: التقدير العام يُحسب بشكل صحيح في القالب الوصفي.
 *
 * يعتمد على نفس نمط BulkEvaluationControllerTest: معاملة قابلة للتراجع.
 */

use App\Core\Database;
use App\Controllers\BuilderController;
use App\Models\Evaluation;
use App\Models\Template;

class DescriptiveTemplatesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
    }

    /**
     * CD-023: إعداد جلسة مدير بصلاحية templates.manage.
     * ضروري لأن BuilderController::__construct يستدعي Auth::enforce('templates.manage')
     * الذي يُنفّذ exit() عند غياب الجلسة — مما يقتل مشغّل الاختبارات.
     */
    private function loginAsTemplateManager(): int
    {
        $db = Database::getInstance();
        $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        $userId = (int)$db->fetchColumn();
        $this->assertTrue($userId > 0, 'مطلوب مستخدم أساسي في قاعدة البيانات لاختبارات الباني.');

        $_SESSION['user_id']          = $userId;
        $_SESSION['user_roles']       = ['Admin'];
        $_SESSION['user_permissions'] = ['templates.manage'];
        $_SESSION['user_object']      = (object)[
            'id'        => $userId,
            'username'  => 'cd023-test',
            'full_name' => 'مختبر CD-023',
        ];
        return $userId;
    }

    /**
     * استدعاء ميثود خاصة عبر الانعكاس (reflection).
     */
    private function invokeValidateWeights(BuilderController $ctrl, array $structure, string $mode): ?string
    {
        $reflection = new \ReflectionClass($ctrl);
        $method = $reflection->getMethod('validateWeights');
        $method->setAccessible(true);
        return $method->invoke($ctrl, $structure, $mode);
    }

    /**
     * CD-023: جلب معرّف مستخدم صالح من قاعدة البيانات.
     * ضروري لأن جدول evaluations له قيد FK (eval_fk_creator) على
     * created_by_user_id → users(id)، فإدراج تقييم بدونه يفشل بـ 1452.
     */
    private function fetchAnyUserId(): int
    {
        $db = Database::getInstance();
        $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        $userId = (int)$db->fetchColumn();
        $this->assertTrue($userId > 0, 'مطلوب مستخدم أساسي في قاعدة البيانات لإسناد created_by_user_id.');
        return $userId;
    }

    /**
     * بناء قالب رقمي بسيط + إدراج تقييم + إجابة واحدة.
     * يعدّ: [evaluationId, sectionId]
     */
    private function makeNumericEvaluationWithSection(float $score, array $extraSections = []): array
    {
        $pdo = Database::getInstance()->getPdo();
        $stamp = 'CD023-' . bin2hex(random_bytes(3));

        // قالب رقمي
        $pdo->prepare('INSERT INTO form_templates (template_name, target_type, evaluation_mode, is_active, version)
                       VALUES (:name, "employee", "numeric", 1, 1)')
            ->execute(['name' => 'قالب CD023 رقمي ' . $stamp]);
        $templateId = (int)$pdo->lastInsertId();

        $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index)
                       VALUES (:tid, "معيار CD023", 100, 1)')
            ->execute(['tid' => $templateId]);
        $criterionId = (int)$pdo->lastInsertId();

        // قسم النسبة المئوية (المرجَّح)
        $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, min_value, max_value, section_weight, order_index)
                       VALUES (:cid, "بند CD023", "percentage", 0, 100, 100, 1)')
            ->execute(['cid' => $criterionId]);
        $sectionId = (int)$pdo->lastInsertId();

        // أقسام إضافية (مثل textarea) تُضاف للمعيار نفسه
        foreach ($extraSections as $extra) {
            $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, section_weight, evaluation_options, order_index)
                           VALUES (:cid, :name, :type, 0, :opts, :ord)')
                ->execute([
                    'cid'  => $criterionId,
                    'name' => $extra['name'],
                    'type' => $extra['type'],
                    'opts' => $extra['opts'] ?? null,
                    'ord'  => $extra['order'] ?? 2,
                ]);
        }

        // موظف + تقييم
        $empCode = 'CD023N-' . bin2hex(random_bytes(3));
        $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active, profile_status)
                       VALUES (:code, :name, 1, "complete")')
            ->execute(['code' => $empCode, 'name' => 'موظف CD023 رقمي']);
        $employeeId = (int)$pdo->lastInsertId();

        // CD-023: created_by_user_id مطلوب لقيد FK eval_fk_creator
        $creatorId = $this->fetchAnyUserId();
        $pdo->prepare('INSERT INTO evaluations (template_id, status, evaluation_date, evaluation_type, evaluated_employee_id, employee_name, employee_code, entity_type, entity_id, created_by_user_id, created_at)
                       VALUES (:tpl, "draft", CURDATE(), "annual", :emp, :ename, :ecode, "employee", :entity, :creator, NOW())')
            ->execute([
                'tpl' => $templateId, 'emp' => $employeeId, 'entity' => $employeeId,
                'creator' => $creatorId,
                'ename' => 'موظف CD023 رقمي', 'ecode' => $empCode,
            ]);
        $evaluationId = (int)$pdo->lastInsertId();

        // إجابة القسم المرجَّح
        $pdo->prepare('INSERT INTO evaluation_answers (evaluation_id, section_id, raw_value, normalized_score)
                       VALUES (:eid, :sid, :raw, :norm)')
            ->execute(['eid' => $evaluationId, 'sid' => $sectionId, 'raw' => (string)$score, 'norm' => $score]);

        return [$evaluationId, $sectionId, $templateId];
    }

    // ====================================================================
    //  الاختبار 1: القالب الوصفي يُعفى من شرط الـ 100% ويُشترط فيه التقدير العام
    // ====================================================================

    public function testValidateWeightsAllowsDescriptiveWithout100Percent(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;

        try {
            $this->loginAsTemplateManager();
            // هيكل وصفي: معيار وزنه 50 (ليس 100) + قسم تقدير عام
            $structure = [
                [
                    'name'        => 'معيار وصفي',
                    'description' => '',
                    'weight'      => 50,  // ليس 100 — مقبول للوصفي
                    'sections'    => [
                        ['type' => 'textarea', 'weight' => 0, 'name' => 'ملاحظات وصفية'],
                        ['type' => 'overall_grade', 'weight' => 0, 'name' => 'التقدير العام'],
                    ],
                ],
            ];

            $ctrl = new BuilderController();
            $error = $this->invokeValidateWeights($ctrl, $structure, 'descriptive');

            $this->assertNull($error, 'القالب الوصفي يجب ألا يطلب شرط الـ 100%. رسالة: ' . (string)$error);
        } finally {
            $_SESSION = $previousSession;
            $db->rollBack();
        }
    }

    public function testValidateWeightsRequiresOverallGradeForDescriptive(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;

        try {
            $this->loginAsTemplateManager();
            // هيكل وصفي بدون قسم تقدير عام
            $structure = [
                [
                    'name'        => 'معيار وصفي',
                    'description' => '',
                    'weight'      => 50,
                    'sections'    => [
                        ['type' => 'textarea', 'weight' => 0, 'name' => 'ملاحظات'],
                    ],
                ],
            ];

            $ctrl = new BuilderController();
            $error = $this->invokeValidateWeights($ctrl, $structure, 'descriptive');

            $this->assertTrue($error !== null, 'القالب الوصفي بدون تقدير عام يجب أن يُرفض.');
            $this->assertStringContainsString('التقدير العام', (string)$error);
        } finally {
            $_SESSION = $previousSession;
            $db->rollBack();
        }
    }

    public function testValidateWeightsNumericStillEnforces100Percent(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;

        try {
            $this->loginAsTemplateManager();
            // هيكل رقمي: معيار وزنه 50 (ليس 100) + قسم percentage
            $structure = [
                [
                    'name'        => 'معيار رقمي',
                    'description' => '',
                    'weight'      => 50,
                    'sections'    => [
                        ['type' => 'percentage', 'weight' => 100, 'name' => 'بند'],
                    ],
                ],
            ];

            $ctrl = new BuilderController();
            $error = $this->invokeValidateWeights($ctrl, $structure, 'numeric');

            $this->assertTrue($error !== null, 'القالب الرقمي يجب أن يُطبّق شرط الـ 100%.');
            $this->assertStringContainsString('100%', (string)$error);
        } finally {
            $_SESSION = $previousSession;
            $db->rollBack();
        }
    }

    // ====================================================================
    //  الاختبار 2: القسم النصي لا يؤثر على حساب الدرجة النهائية
    // ====================================================================

    public function testTextSectionDoesNotAffectFinalScore(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // قالب رقمي: معيار (وزن 100) يحوي قسم percentage (وزن 100) + قسم textarea (وزن 0)
            [$evaluationId, $sectionId, $templateId] = $this->makeNumericEvaluationWithSection(80.0, [
                ['name' => 'ملاحظات وصفية', 'type' => 'textarea', 'opts' => json_encode(['max_chars' => 500, 'required' => false]), 'order' => 2],
            ]);

            // أضف إجابة للقسم النصي (raw_value = نص طويل)
            $pdo = $db->getPdo();
            $textSectionId = (int)$pdo->query("SELECT id FROM criteria_sections WHERE criterion_id = (SELECT id FROM criteria WHERE template_id = {$templateId} LIMIT 1) AND evaluation_type = 'textarea' LIMIT 1")->fetchColumn();
            $this->assertTrue($textSectionId > 0, 'قسم textarea يجب أن يُنشأ.');

            $pdo->prepare('INSERT INTO evaluation_answers (evaluation_id, section_id, raw_value, normalized_score)
                           VALUES (:eid, :sid, :raw, NULL)')
                ->execute(['eid' => $evaluationId, 'sid' => $textSectionId, 'raw' => 'هذا نص وصفي طويل لا يجب أن يؤثر على الدرجة.']);

            $evalModel = new Evaluation();
            $score = $evalModel->calculateFinalScore($evaluationId);

            $this->assertNotNull($score, 'الدرجة النهائية يجب أن تُحسب (ليست null).');
            $this->assertSame(80.0, (float)$score, 'الدرجة يجب أن تكون 80.0 — القسم النصي لا يؤثر.');
        } finally {
            $db->rollBack();
        }
    }

    // ====================================================================
    //  الاختبار 3: التقدير العام يُحسب بشكل صحيح في القالب الوصفي
    // ====================================================================

    public function testOverallGradeCalculatesCorrectlyForDescriptive(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $pdo = $db->getPdo();
            $stamp = 'CD023D-' . bin2hex(random_bytes(3));

            // قالب وصفي: معيار واحد يحوي قسم textarea + قسم overall_grade
            $pdo->prepare('INSERT INTO form_templates (template_name, target_type, evaluation_mode, is_active, version)
                           VALUES (:name, "employee", "descriptive", 1, 1)')
                ->execute(['name' => 'قالب CD023 وصفي ' . $stamp]);
            $templateId = (int)$pdo->lastInsertId();

            $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index)
                           VALUES (:tid, "معيار وصفي", 100, 1)')
                ->execute(['tid' => $templateId]);
            $criterionId = (int)$pdo->lastInsertId();

            // قسم نصي
            $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, section_weight, evaluation_options, order_index)
                           VALUES (:cid, "ملاحظات", "textarea", 0, :opts, 1)')
                ->execute(['cid' => $criterionId, 'opts' => json_encode(['max_chars' => 1000, 'required' => false])]);
            $textSectionId = (int)$pdo->lastInsertId();

            // قسم التقدير العام — سلم السلم الخماسي
            $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, section_weight, evaluation_options, order_index)
                           VALUES (:cid, "التقدير العام", "overall_grade", 0, :opts, 2)')
                ->execute(['cid' => $criterionId, 'opts' => json_encode(['scale_name' => 'السلم الخماسي'])]);
            $gradeSectionId = (int)$pdo->lastInsertId();

            // موظف + تقييم
            $empCode = 'CD023D-' . bin2hex(random_bytes(3));
            $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active, profile_status)
                           VALUES (:code, :name, 1, "complete")')
                ->execute(['code' => $empCode, 'name' => 'موظف CD023 وصفي']);
            $employeeId = (int)$pdo->lastInsertId();

            // CD-023: created_by_user_id مطلوب لقيد FK eval_fk_creator
            $creatorId = $this->fetchAnyUserId();
            $pdo->prepare('INSERT INTO evaluations (template_id, status, evaluation_date, evaluation_type, evaluated_employee_id, employee_name, employee_code, entity_type, entity_id, created_by_user_id, created_at)
                           VALUES (:tpl, "draft", CURDATE(), "annual", :emp, :ename, :ecode, "employee", :entity, :creator, NOW())')
                ->execute([
                    'tpl' => $templateId, 'emp' => $employeeId, 'entity' => $employeeId,
                    'creator' => $creatorId,
                    'ename' => 'موظف CD023 وصفي', 'ecode' => $empCode,
                ]);
            $evaluationId = (int)$pdo->lastInsertId();

            // إجابة القسم النصي
            $pdo->prepare('INSERT INTO evaluation_answers (evaluation_id, section_id, raw_value, normalized_score)
                           VALUES (:eid, :sid, :raw, NULL)')
                ->execute(['eid' => $evaluationId, 'sid' => $textSectionId, 'raw' => 'أداء ممتاز مع التزام عالٍ.']);

            // إجابة التقدير العام = "ممتاز" (CD-026: max_value = 100.0 على مقياس 0-100)
            $pdo->prepare('INSERT INTO evaluation_answers (evaluation_id, section_id, raw_value, normalized_score)
                           VALUES (:eid, :sid, :raw, NULL)')
                ->execute(['eid' => $evaluationId, 'sid' => $gradeSectionId, 'raw' => 'ممتاز']);

            $evalModel = new Evaluation();
            $score = $evalModel->calculateFinalScore($evaluationId);

            $this->assertNotNull($score, 'الدرجة النهائية للقالب الوصفي يجب أن تُحسب من التقدير العام.');
            $this->assertSame(100.0, (float)$score, 'CD-026: الدرجة يجب أن تكون 100.0 (max_value لـ "ممتاز" على مقياس 0-100).');
        } finally {
            $db->rollBack();
        }
    }
}
