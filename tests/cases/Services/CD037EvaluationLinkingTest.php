<?php
/**
 * CD-037 — اختبارات إعادة بناء واجهات التقييم وربطها بالبرامج التدريبية.
 * يغطي: اشتقاق نوع البرنامج، ربط التقييم، مزامنة المشارك، تقرير الموظف،
 *        وعقود الواجهات (fill / bulk-create / الشريط الجانبي والمسارات).
 */

use App\Core\Database;
use App\Controllers\BulkEvaluationController;
use App\Services\EmployeeReportService;

class CD037EvaluationLinkingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
    }

    private function invokePrivate(object $object, string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod($object, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($object, $args);
    }

    private function userAndBranch(Database $db): array
    {
        $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        $userId = (int)$db->fetchColumn();
        $db->query('SELECT id FROM branches ORDER BY id LIMIT 1');
        $branchId = (int)$db->fetchColumn();
        $this->assertTrue($userId > 0, 'يلزم وجود مستخدم لاختبارات CD-037.');
        if ($branchId < 1) {
            $this->markSkipped('يلزم وجود فرع لإنشاء برنامج تدريبي.');
        }
        return [$userId, $branchId];
    }

    private function createTemplate(Database $db, string $targetType, string $suffix): int
    {
        $db->query(
            'INSERT INTO form_templates (template_name, target_type, evaluation_mode, is_active, version)
             VALUES (:name, :target, "numeric", 1, 1)'
        );
        $db->bind(':name', 'CD037 ' . $suffix . ' ' . bin2hex(random_bytes(4)));
        $db->bind(':target', $targetType);
        $db->execute();
        return (int)$db->lastInsertId();
    }

    private function createEmployee(Database $db): array
    {
        $code = 'CD037-' . bin2hex(random_bytes(5));
        $db->query('INSERT INTO employees (emp_code, full_name) VALUES (:code, :name)');
        $db->bind(':code', $code);
        $db->bind(':name', 'CD037 Employee');
        $db->execute();
        return [(int)$db->lastInsertId(), $code];
    }

    private function createProgram(Database $db, int $branchId, int $userId): int
    {
        $db->query(
            'INSERT INTO training_programs
                (type, title, executing_branch_id, start_date, end_date, responsible_person, status, created_by)
             VALUES ("course", :title, :branch, CURDATE(), CURDATE(), :person, "ongoing", :user)'
        );
        $db->bind(':title', 'CD037 Program ' . bin2hex(random_bytes(4)));
        $db->bind(':branch', $branchId);
        $db->bind(':person', 'CD037 Trainer');
        $db->bind(':user', $userId);
        $db->execute();
        return (int)$db->lastInsertId();
    }

    private function createEvaluation(Database $db, int $templateId, int $employeeId, string $code, int $userId, array $overrides = []): int
    {
        $type = $overrides['evaluation_type'] ?? 'annual';
        $status = $overrides['status'] ?? 'draft';
        $score = $overrides['final_score'] ?? null;
        $contextType = $overrides['context_type'] ?? null;
        $contextId = $overrides['context_id'] ?? null;

        $db->query(
            'INSERT INTO evaluations
                (template_id, entity_type, entity_id, status, evaluation_date, evaluation_type,
                 evaluated_employee_id, employee_name, employee_code, branch_name, department_name,
                 position_name, final_score, context_type, context_id, created_by_user_id)
             VALUES
                (:template, "employee", :entity, :status, CURDATE(), :type,
                 :employee, "CD037 Employee", :code, "", "",
                 "", :score, :ctype, :cid, :user)'
        );
        $db->bind(':template', $templateId);
        $db->bind(':entity', $employeeId);
        $db->bind(':status', $status);
        $db->bind(':type', $type);
        $db->bind(':employee', $employeeId);
        $db->bind(':code', $code);
        $db->bind(':score', $score);
        $db->bind(':ctype', $contextType);
        $db->bind(':cid', $contextId);
        $db->bind(':user', $userId);
        $db->execute();
        return (int)$db->lastInsertId();
    }

    public function testProgramContextTypeIsDerivedFromTemplate(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $employeeTpl = $this->createTemplate($db, 'employee', 'employee');
            $courseTpl = $this->createTemplate($db, 'employee_in_course', 'course');
            $workshopTpl = $this->createTemplate($db, 'employee_in_workshop', 'workshop');

            $controller = new BulkEvaluationController();
            $this->assertNull($this->invokePrivate($controller, 'programContextTypeForTemplate', [$employeeTpl]));
            $this->assertSame('course', $this->invokePrivate($controller, 'programContextTypeForTemplate', [$courseTpl]));
            $this->assertSame('workshop', $this->invokePrivate($controller, 'programContextTypeForTemplate', [$workshopTpl]));
        } finally {
            $db->rollBack();
        }
    }

    public function testApplyProgramContextLinksEvaluationAndCompletesParticipant(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            [$userId, $branchId] = $this->userAndBranch($db);
            [$employeeId, $code] = $this->createEmployee($db);
            $templateId = $this->createTemplate($db, 'employee_in_course', 'link');
            $programId = $this->createProgram($db, $branchId, $userId);

            $db->query('INSERT INTO program_participants (program_id, employee_id, status) VALUES (:pid, :eid, "pending")');
            $db->bind(':pid', $programId);
            $db->bind(':eid', $employeeId);
            $db->execute();

            $evaluationId = $this->createEvaluation($db, $templateId, $employeeId, $code, $userId);

            $controller = new BulkEvaluationController();
            $this->invokePrivate($controller, 'applyProgramContext', [$evaluationId, 'course', $programId, $employeeId]);

            $db->query('SELECT evaluation_type, context_type, context_id FROM evaluations WHERE id = :id');
            $db->bind(':id', $evaluationId);
            $evaluation = $db->fetch();
            $this->assertSame('course', $evaluation->evaluation_type);
            $this->assertSame('course', $evaluation->context_type);
            $this->assertSame($programId, (int)$evaluation->context_id);

            $db->query('SELECT status, final_score FROM program_participants WHERE program_id = :pid AND employee_id = :eid');
            $db->bind(':pid', $programId);
            $db->bind(':eid', $employeeId);
            $participant = $db->fetch();
            $this->assertSame('completed', $participant->status);
            $this->assertNotNull($participant->final_score);
        } finally {
            $db->rollBack();
        }
    }

    public function testProfileReportNormalizesProgramContextAndReturnsProgramTitle(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            [$userId, $branchId] = $this->userAndBranch($db);
            [$employeeId, $code] = $this->createEmployee($db);
            $templateId = $this->createTemplate($db, 'employee_in_course', 'report');
            $programId = $this->createProgram($db, $branchId, $userId);

            $this->createEvaluation($db, $templateId, $employeeId, $code, $userId, [
                'status' => 'completed',
                'final_score' => 88,
                'evaluation_type' => 'annual', // بيانات قديمة: يجب أن تُصحَّح إلى course
                'context_type' => 'course',
                'context_id' => $programId,
            ]);

            $db->query('SELECT title FROM training_programs WHERE id = :id');
            $db->bind(':id', $programId);
            $programTitle = (string)$db->fetchColumn();

            $report = (new EmployeeReportService($db))->getProfileReport($employeeId);
            $this->assertCount(1, $report['records']);

            $record = $report['records'][0];
            $this->assertSame('course', $record->evaluation_type);
            $this->assertSame('دورة', $record->evaluation_type_label);
            $this->assertSame($programTitle, $record->program_title);
            $this->assertSame(1, $report['averages']['course']['evaluation_count']);
            $this->assertSame(0, $report['averages']['annual']['evaluation_count']);
            $this->assertSame(88.0, (float)$report['averages']['course']['average']);
        } finally {
            $db->rollBack();
        }
    }

    public function testFillViewUsesSharedProgramInfoAndPreselectsSavedContext(): void
    {
        $content = file_get_contents(APP_ROOT . '/app/Views/evaluations/fill.php');
        $this->assertIsString($content);
        $this->assertSame(1, substr_count($content, 'function showProgramInfo'), 'يجب تعريف showProgramInfo مرة واحدة فقط.');
        $this->assertStringContainsString('$savedContextId', $content);
        $this->assertStringContainsString("\$savedContextId === (int)\$p->id ? 'selected'", $content);
        $this->assertStringContainsString('DOMContentLoaded', $content, 'يجب رسم بطاقة البرنامج المحفوظ عند التحميل.');
    }

    public function testBulkCreateDerivesContextTypeAndLoadsProgramParticipants(): void
    {
        $content = file_get_contents(APP_ROOT . '/app/Views/evaluations/bulk-create.php');
        $this->assertIsString($content);
        $this->assertStringContainsString('function getContextSelection', $content);
        $this->assertStringContainsString('getContextSelection()', $content);
        $this->assertStringContainsString('employee_in_workshop', $content);
        $this->assertStringContainsString('api/training-programs/', $content);
        $this->assertStringContainsString('pending-employees', $content);
        $this->assertSame(2, substr_count($content, 'if (needsProgram && !ctx.id)'), 'يجب التحقق من البرنامج في الحفظ الفردي والجماعي.');
        $this->assertStringNotContainsString("ctxType = ctxId ? 'course'", $content, 'لا يجوز تثبيت النوع course.');

        $routes = file_get_contents(APP_ROOT . '/routes/web.php');
        $this->assertStringContainsString('api/training-programs/:id/pending-employees', $routes);
        $this->assertStringContainsString('api/templates/for-evaluation/:position_id', $routes);
    }

    public function testBulkEndpointsEnforceCsrfAndRequiredProgramServerSide(): void
    {
        $content = file_get_contents(APP_ROOT . '/app/Controllers/BulkEvaluationController.php');
        $this->assertIsString($content);
        $this->assertStringContainsString("if (!Auth::validateCsrfToken(\$_POST['_token']", $content);
        $this->assertStringContainsString('if (!Auth::validateCsrfToken($csrfToken))', $content);
        $this->assertStringContainsString('programContextTypeForTemplate', $content);
        $this->assertStringContainsString('applyProgramContext', $content);
        $this->assertStringContainsString('يجب اختيار البرنامج التدريبي', $content);
        $this->assertStringContainsString('markCompleted', $content);
        $this->assertStringContainsString("evaluation_type = :etype", $content, 'يجب تحديث evaluation_type مع الـcontext.');
    }
}
