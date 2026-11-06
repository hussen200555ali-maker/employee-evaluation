<?php
/**
 * CD-022 — اختبارات التقييم الجماعي (Bulk Evaluation)
 *
 * يغطي هذا الملف الستة المسارات الرئيسية:
 *   - create()        : عرض صفحة التقييم الجماعي
 *   - schema()        : واجهة JSON لبنية القالب
 *   - quickAdd()      : إضافة موظف مختصر (profile_status=incomplete)
 *   - quickAdd()      : رفض الرقم الوظيفي المكرر
 *   - saveSingle()    : حفظ تقييم فردي مع حساب الدرجة النهائية
 *   - Dashboard widget: ظهور تنبيه الملفات غير المكتملة على لوحة التحكم
 *
 * يعتمد على نفس نمط ReportsArchiveTest: معاملة قابلة للتراجع +
 * إعداد جلسة + التقاط المخرجات عبر ob_start.
 */

use App\Core\Auth;
use App\Core\Database;
use App\Controllers\BulkEvaluationController;
use App\Controllers\DashboardController;
use App\Services\RoleDashboardService;

class BulkEvaluationControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
    }

    /**
     * تهيئة جلسة مستخدم بصلاحية evaluations.create.
     * يعيد معرّف مستخدم حقيقي من قاعدة البيانات.
     */
    private function loginEvaluator(): int
    {
        $db = Database::getInstance();
        $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        $userId = (int)$db->fetchColumn();
        $this->assertTrue($userId > 0, 'مطلوب مستخدم أساسي في قاعدة البيانات لاختبارات الجلسة.');

        $_SESSION['user_id']          = $userId;
        $_SESSION['user_roles']       = ['Manager'];
        $_SESSION['user_permissions'] = ['evaluations.create', 'employees.view', 'evaluations.fill'];
        $_SESSION['user_object']      = (object)[
            'id'          => $userId,
            'username'    => 'bulk-tester',
            'full_name'   => 'مختبر التقييم الجماعي',
            'employee_id' => 0,
        ];

        return $userId;
    }

    /**
     * إنشاء قالب رقمي بسيط (معيار واحد + بند واحد) ضمن المعاملة الحالية.
     * يعيد [templateId, sectionId].
     */
    private function makeNumericTemplate(): array
    {
        $pdo = Database::getInstance()->getPdo();
        $stamp = 'CD022-' . bin2hex(random_bytes(3));

        $pdo->prepare('INSERT INTO form_templates (template_name, target_type, evaluation_mode, is_active, version)
                       VALUES (:name, "employee", "numeric", 1, 1)')
            ->execute(['name' => 'قالب اختبار CD022 ' . $stamp]);
        $templateId = (int)$pdo->lastInsertId();

        $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index)
                       VALUES (:tid, "معيار CD022", 100, 1)')
            ->execute(['tid' => $templateId]);
        $criterionId = (int)$pdo->lastInsertId();

        $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, min_value, max_value, section_weight, order_index)
                       VALUES (:cid, "بند CD022", "percentage", 0, 100, 100, 1)')
            ->execute(['cid' => $criterionId]);
        $sectionId = (int)$pdo->lastInsertId();

        return [$templateId, $sectionId];
    }

    public function testBulkCreatePageRendersTemplateSelector(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;
        $level = ob_get_level();

        try {
            $this->loginEvaluator();

            ob_start();
            (new BulkEvaluationController())->create();
            $html = (string)ob_get_clean();

            $this->assertStringContainsString('التقييم الجماعي', $html);
            $this->assertStringContainsString('templateSelect', $html);
            $this->assertStringContainsString('evaluatorName', $html);
        } finally {
            while (ob_get_level() > $level) { ob_end_clean(); }
            $_SESSION = $previousSession;
            $db->rollBack();
        }
    }

    public function testSchemaReturnsTemplateStructureAsJson(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;
        $level = ob_get_level();

        try {
            $this->loginEvaluator();
            [$templateId, $sectionId] = $this->makeNumericTemplate();

            ob_start();
            (new BulkEvaluationController())->schema($templateId);
            $raw = (string)ob_get_clean();

            $data = json_decode($raw, true);
            $this->assertTrue(is_array($data), 'استجابة schema ليست JSON صالحًا: ' . substr($raw, 0, 200));
            $this->assertSame($templateId, (int)($data['id'] ?? 0));
            $this->assertNotEmpty($data['criteria'] ?? [], 'القالب يجب أن يحتوي على معيار واحد على الأقل.');
            $firstCriterion = $data['criteria'][0];
            $this->assertNotEmpty($firstCriterion['sections'] ?? [], 'المعيار يجب أن يحتوي على بند واحد على الأقل.');
            $this->assertSame($sectionId, (int)$firstCriterion['sections'][0]['id']);
        } finally {
            while (ob_get_level() > $level) { ob_end_clean(); }
            $_SESSION = $previousSession;
            $db->rollBack();
        }
    }

    public function testQuickAddCreatesIncompleteEmployee(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;
        $level = ob_get_level();

        try {
            $this->loginEvaluator();
            $csrf = Auth::generateCsrfToken();

            $code = 'CD022-' . bin2hex(random_bytes(4));
            $_POST = [
                '_token'    => $csrf,
                'full_name' => 'موظف اختبار جماعي',
                'emp_code'  => $code,
                'branch_id' => 0,
                'department_id' => 0,
            ];

            ob_start();
            (new BulkEvaluationController())->quickAdd();
            $raw = (string)ob_get_clean();

            $data = json_decode($raw, true);
            $this->assertTrue(is_array($data), 'استجابة quickAdd ليست JSON: ' . substr($raw, 0, 200));
            $this->assertTrue(!empty($data['success']), 'متوقع success=true. استجابة: ' . $raw);
            $employeeId = (int)($data['employee']['id'] ?? 0);
            $this->assertTrue($employeeId > 0, 'معرّف الموظف الجديد غير صحيح.');

            // التأكد من ضبط profile_status = incomplete
            $pdo = $db->getPdo();
            $stmt = $pdo->prepare('SELECT profile_status FROM employees WHERE id = :id');
            $stmt->execute(['id' => $employeeId]);
            $status = (string)$stmt->fetchColumn();
            $this->assertSame('incomplete', $status, 'الموظف المُضاف مختصرًا يجب أن تكون حالته incomplete.');
        } finally {
            while (ob_get_level() > $level) { ob_end_clean(); }
            $_SESSION = $previousSession;
            $_POST = [];
            $db->rollBack();
        }
    }

    public function testQuickAddRejectsDuplicateCode(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;
        $level = ob_get_level();

        try {
            $this->loginEvaluator();
            $csrf = Auth::generateCsrfToken();

            $code = 'CD022DUP-' . bin2hex(random_bytes(3));

            // الإضافة الأولى — يجب أن تنجح
            $_POST = [
                '_token' => $csrf, 'full_name' => 'الأول', 'emp_code' => $code,
                'branch_id' => 0, 'department_id' => 0,
            ];
            ob_start();
            (new BulkEvaluationController())->quickAdd();
            $first = (string)ob_get_clean();
            $firstData = json_decode($first, true);
            $this->assertTrue(!empty($firstData['success']), 'الإضافة الأولى يجب أن تنجح.');

            // الإضافة الثانية بنفس الرقم — يجب أن تُرفض
            $_POST = [
                '_token' => $csrf, 'full_name' => 'الثاني', 'emp_code' => $code,
                'branch_id' => 0, 'department_id' => 0,
            ];
            ob_start();
            (new BulkEvaluationController())->quickAdd();
            $second = (string)ob_get_clean();
            $secondData = json_decode($second, true);

            $this->assertTrue(isset($secondData['error']), 'متوقع رفض الرقم المكرر. استجابة: ' . $second);
            $this->assertStringContainsString('موجود', (string)($secondData['error'] ?? ''));
        } finally {
            while (ob_get_level() > $level) { ob_end_clean(); }
            $_SESSION = $previousSession;
            $_POST = [];
            $db->rollBack();
        }
    }

    public function testSaveSingleCreatesCompletedEvaluationWithScore(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;
        $level = ob_get_level();

        try {
            $this->loginEvaluator();
            [$templateId, $sectionId] = $this->makeNumericTemplate();

            // إنشاء موظف لاختبار الحفظ الفردي
            $pdo = $db->getPdo();
            $empCode = 'CD022SAVE-' . bin2hex(random_bytes(3));
            $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active, profile_status)
                           VALUES (:code, :name, 1, "complete")')
                ->execute(['code' => $empCode, 'name' => 'موظف حفظ فردي']);
            $employeeId = (int)$pdo->lastInsertId();

            $csrf = Auth::generateCsrfToken();
            $_POST = [
                '_token'           => $csrf,
                'employee_id'      => $employeeId,
                'template_id'      => $templateId,
                'evaluation_date'  => date('Y-m-d'),
                'evaluator_name'   => 'مقيّم اختبار',
                'evaluator_title'  => 'مدير',
                'results'          => [
                    $sectionId => ['score' => 85, 'notes' => 'أداء جيد جداً'],
                ],
            ];

            ob_start();
            (new BulkEvaluationController())->saveSingle();
            $raw = (string)ob_get_clean();

            $data = json_decode($raw, true);
            $this->assertTrue(is_array($data), 'استجابة saveSingle ليست JSON: ' . substr($raw, 0, 200));
            $this->assertTrue(!empty($data['success']), 'متوقع success=true. استجابة: ' . $raw);
            $evaluationId = (int)($data['evaluation_id'] ?? 0);
            $this->assertTrue($evaluationId > 0, 'معرّف التقييم غير صحيح.');

            // التأكد من اكتمال التقييم وحساب الدرجة النهائية
            $stmt = $pdo->prepare('SELECT status, final_score FROM evaluations WHERE id = :id');
            $stmt->execute(['id' => $evaluationId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertSame('completed', (string)($row['status'] ?? ''), 'التقييم يجب أن يكون مكتملًا.');
            $this->assertTrue(($row['final_score'] ?? null) !== null, 'الدرجة النهائية يجب أن تكون محسوبة.');
            $this->assertSame(85.0, (float)$row['final_score'], 'الدرجة النهائية يجب أن تساوي 85.');
        } finally {
            while (ob_get_level() > $level) { ob_end_clean(); }
            $_SESSION = $previousSession;
            $_POST = [];
            $db->rollBack();
        }
    }

    public function testDashboardShowsIncompleteProfilesWidget(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;
        $level = ob_get_level();

        try {
            $this->loginEvaluator();

            // إنشاء موظف مختصر (incomplete) مباشرة
            $pdo = $db->getPdo();
            $code = 'CD022W-' . bin2hex(random_bytes(3));
            $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active, profile_status)
                           VALUES (:code, :name, 1, "incomplete")')
                ->execute(['code' => $code, 'name' => 'موظف لوحة التحكم']);
            $employeeId = (int)$pdo->lastInsertId();

            // 1) على مستوى الخدمة: يجب أن يعيد incomplete_profiles هذا الموظف.
            //    نمرر صلاحيات admin بشكل صريح لضمان شخصية admin وحتمية النتيجة
            //    (DashboardController::index() يعيد اشتقاق الصلاحيات من قاعدة البيانات،
            //    لذا نختبر الخدمة والعرض بشكل منفصل ومضبوط).
            $service = new RoleDashboardService();
            $dashboard = $service->forUser(
                (int)$_SESSION['user_id'],
                0,
                ['evaluations.create', 'employees.view', 'reports.view.all'],
                ['Admin'],
                'مختبر لوحة التحكم'
            );
            $this->assertSame('admin', $dashboard['persona'], 'الشخصية يجب أن تكون admin للوصول لكل الملفات.');
            $this->assertNotEmpty($dashboard['incomplete_profiles'], 'الخدمة يجب أن تعيد قائمة الملفات غير المكتملة.');
            $found = false;
            foreach ($dashboard['incomplete_profiles'] as $emp) {
                if ((int)($emp['id'] ?? 0) === $employeeId) { $found = true; break; }
            }
            $this->assertTrue($found, 'الموظف المختصر يجب أن يظهر ضمن incomplete_profiles.');

            // 2) على مستوى العرض: نمرر بيانات الخدمة المضبوطة مباشرة إلى العرض
            //    عبر فئة مجهولة تتيح استدعاء view() المحمية — هذا يثبت أن القالب
            //    يرسم التنبيه عند توفر البيانات دون الاعتماد على صلاحيات المستخدم في قاعدة البيانات.
            $viewData = [
                'pageTitle' => 'لوحة التحكم',
                'userName'  => 'مختبر لوحة التحكم',
                'dashboard' => $dashboard,
            ];

            $renderer = new class extends DashboardController {
                public function renderWith(array $data): void
                {
                    $this->view('dashboard/index', $data);
                }
            };

            ob_start();
            $renderer->renderWith($viewData);
            $html = (string)ob_get_clean();

            $this->assertStringContainsString('cd022-incomplete-alert', $html, 'العرض يجب أن يحتوي على تنبيه الملفات غير المكتملة.');
            $this->assertStringContainsString('موظف لوحة التحكم', $html, 'اسم الموظف المختصر يجب أن يظهر في التنبيه.');
            $this->assertStringContainsString('التقييم الجماعي', $html, 'زر فتح التقييم الجماعي يجب أن يظهر.');
        } finally {
            while (ob_get_level() > $level) { ob_end_clean(); }
            $_SESSION = $previousSession;
            $db->rollBack();
        }
    }
}
