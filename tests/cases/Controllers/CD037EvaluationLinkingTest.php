<?php
/**
 * CD-037 — integration tests for unified training-program evaluation linking.
 * Database writes are enclosed in a transaction and rolled back in tearDown.
 */

use App\Core\Database;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\ProgramParticipant;
use App\Services\EmployeeReportService;
use App\Services\EvaluationProgramContextService;

class CD037EvaluationLinkingTest extends TestCase
{
    private ?Database $db = null;
    private int $userId = 0;

    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable('تتطلب اختبارات CD-037 قاعدة بيانات التدريب الموحدة.');
        $this->db = Database::getInstance();
        $this->db->beginTransaction();
        $this->db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        $this->userId = (int)$this->db->fetchColumn();
        if ($this->userId < 1) {
            $this->markSkipped('لا يوجد مستخدم صالح في قاعدة الاختبار.');
        }
    }

    public function tearDown(): void
    {
        if ($this->db && $this->db->getPdo()->inTransaction()) {
            $this->db->rollBack();
        }
        parent::tearDown();
    }

    private function fixture(string $programType, string $targetType): array
    {
        $this->db->query('SELECT id FROM training_programs WHERE type = :type ORDER BY id LIMIT 1');
        $this->db->bind(':type', $programType);
        $programId = (int)$this->db->fetchColumn();

        $this->db->query("SELECT id FROM employees WHERE emp_code IS NOT NULL AND emp_code <> '' AND is_active = 1 ORDER BY id LIMIT 1");
        $employeeId = (int)$this->db->fetchColumn();

        $this->db->query('SELECT id FROM form_templates WHERE target_type = :target AND is_active = 1 ORDER BY id LIMIT 1');
        $this->db->bind(':target', $targetType);
        $templateId = (int)$this->db->fetchColumn();

        if ($programId < 1 || $employeeId < 1 || $templateId < 1) {
            $this->markSkipped("لا تتوفر fixtures نشطة لـ {$programType}/{$targetType}.");
        }
        return [$programId, $employeeId, $templateId];
    }

    private function createEvaluation(int $employeeId, int $templateId, string $evaluationType): int
    {
        $employee = (new Employee())->findById($employeeId);
        if (!$employee) {
            $this->markSkipped('تعذر تحميل بيانات موظف الاختبار.');
        }
        $id = (new Evaluation())->create([
            'template_id' => $templateId,
            'entity_type' => 'employee',
            'entity_id' => $employeeId,
            'evaluated_employee_id' => $employeeId,
            'evaluator_employee_id' => $employeeId,
            'evaluator_name' => 'CD-037 integration test',
            'evaluator_title' => 'اختبار آلي',
            'evaluation_date' => date('Y-m-d'),
            'evaluation_type' => $evaluationType,
            'created_by_user_id' => $this->userId,
            'employee_name' => $employee->full_name ?? 'موظف اختبار',
            'employee_code' => $employee->emp_code ?? 'CD037',
            'nickname' => $employee->nickname ?? null,
            'branch_name' => $employee->branch_name ?? '',
            'department_name' => $employee->department_name ?? '',
            'position_name' => $employee->position_title ?? '',
        ]);
        if (!$id) {
            $this->fail('تعذر إنشاء سجل تقييم fixture.');
        }
        return (int)$id;
    }

    public function testCourseContextIsValidatedAndPersistedWithParticipantCompletion(): void
    {
        [$programId, $employeeId, $templateId] = $this->fixture('course', 'employee_in_course');
        $service = new EvaluationProgramContextService($this->db);
        $context = $service->resolveContext('employee_in_course', $programId, 'course');
        $this->assertSame(['type' => 'course', 'id' => $programId], $context);

        $evaluationId = $this->createEvaluation($employeeId, $templateId, 'annual');
        $service->persist($evaluationId, $employeeId, 'course', $context, 85.5);

        $this->db->query('SELECT evaluation_type, context_type, context_id FROM evaluations WHERE id = :id');
        $this->db->bind(':id', $evaluationId);
        $evaluation = $this->db->fetch();
        $this->assertSame('course', $evaluation->evaluation_type);
        $this->assertSame('course', $evaluation->context_type);
        $this->assertSame($programId, (int)$evaluation->context_id);

        $this->db->query('SELECT status, final_score FROM program_participants WHERE program_id = :pid AND employee_id = :eid');
        $this->db->bind(':pid', $programId);
        $this->db->bind(':eid', $employeeId);
        $participant = $this->db->fetch();
        $this->assertSame('completed', $participant->status);
        $this->assertEquals(85.5, (float)$participant->final_score);
    }

    public function testWorkshopContextIsValidatedAndPersisted(): void
    {
        [$programId, $employeeId, $templateId] = $this->fixture('workshop', 'employee_in_workshop');
        $service = new EvaluationProgramContextService($this->db);
        $context = $service->resolveContext('employee_in_workshop', $programId, 'workshop');
        $evaluationId = $this->createEvaluation($employeeId, $templateId, 'annual');
        $service->persist($evaluationId, $employeeId, 'workshop', $context, 91.25);

        $this->db->query('SELECT context_type, context_id FROM evaluations WHERE id = :id');
        $this->db->bind(':id', $evaluationId);
        $evaluation = $this->db->fetch();
        $this->assertSame('workshop', $evaluation->context_type);
        $this->assertSame($programId, (int)$evaluation->context_id);
    }

    public function testBulkCourseSaveLinksEveryEmployeeAndCompletesParticipants(): void
    {
        [$programId, , $templateId] = $this->fixture('course', 'employee_in_course');
        $this->db->query("SELECT id FROM employees WHERE emp_code IS NOT NULL AND emp_code <> '' AND is_active = 1 ORDER BY id LIMIT 2");
        $employees = array_map(static fn(object $row): int => (int)$row->id, $this->db->fetchAll());
        if (count($employees) < 2) {
            $this->markSkipped('لا يتوفر موظفان نشطان لاختبار الحفظ الجماعي.');
        }

        $contextService = new EvaluationProgramContextService($this->db);
        $context = $contextService->resolveContext('employee_in_course', $programId, 'course');
        $evaluationIds = [];
        foreach ($employees as $employeeId) {
            $evaluationId = $this->createEvaluation($employeeId, $templateId, 'annual');
            $contextService->persist($evaluationId, $employeeId, 'course', $context, 88.0);
            $evaluationIds[] = $evaluationId;
        }

        foreach ($evaluationIds as $index => $evaluationId) {
            $this->db->query('SELECT context_type, context_id FROM evaluations WHERE id = :id');
            $this->db->bind(':id', $evaluationId);
            $evaluation = $this->db->fetch();
            $this->assertSame('course', $evaluation->context_type);
            $this->assertSame($programId, (int)$evaluation->context_id);

            $this->db->query('SELECT status FROM program_participants WHERE program_id = :pid AND employee_id = :eid');
            $this->db->bind(':pid', $programId);
            $this->db->bind(':eid', $employees[$index]);
            $this->assertSame('completed', $this->db->fetch()->status);
        }
    }

    public function testTrainingTemplateRejectsMissingContext(): void
    {
        [$programId] = $this->fixture('course', 'employee_in_course');
        $this->assertTrue($programId > 0);
        $this->assertThrows(
            fn() => (new EvaluationProgramContextService($this->db))->resolveContext('employee_in_course', 0, ''),
            InvalidArgumentException::class
        );
    }

    public function testTrainingTemplateRejectsProgramOfWrongType(): void
    {
        [$courseId] = $this->fixture('course', 'employee_in_course');
        $this->assertThrows(
            fn() => (new EvaluationProgramContextService($this->db))->resolveContext('employee_in_workshop', $courseId, 'workshop'),
            InvalidArgumentException::class
        );
    }

    public function testEmployeeProfileShowsSkillTemplateAsMahariWithoutNewEnumValue(): void
    {
        $this->db->query("SELECT id FROM employees WHERE emp_code IS NOT NULL AND emp_code <> '' AND is_active = 1 ORDER BY id LIMIT 1");
        $employeeId = (int)$this->db->fetchColumn();
        $this->db->query("SELECT id FROM form_templates WHERE target_type = 'skill' AND is_active = 1 ORDER BY id LIMIT 1");
        $templateId = (int)$this->db->fetchColumn();
        if ($employeeId < 1 || $templateId < 1) {
            $this->markSkipped('لا يتوفر قالب مهاري نشط أو موظف صالح.');
        }

        $evaluationId = $this->createEvaluation($employeeId, $templateId, 'annual');
        $this->db->query("UPDATE evaluations SET status = 'completed', final_score = 82.0 WHERE id = :id");
        $this->db->bind(':id', $evaluationId);
        $this->db->execute();
        $report = (new EmployeeReportService($this->db))->getProfileReport($employeeId);
        $found = false;
        $expectedSkillCount = 0;
        $expectedAnnualCount = 0;
        foreach ($report['records'] as $record) {
            if (($record->template_target_type ?? null) === 'skill') {
                $expectedSkillCount++;
            }
            if ($record->evaluation_type === 'annual' && ($record->template_target_type ?? null) !== 'skill') {
                $expectedAnnualCount++;
            }
            if ((int)$record->id === $evaluationId) {
                $found = $record->evaluation_type_label === 'مهاري';
            }
        }
        $this->assertTrue($found, 'يُشتق تصنيف مهاري من target_type دون تغيير enum قاعدة البيانات.');
        $this->assertSame($expectedSkillCount, $report['averages']['skill']['evaluation_count']);
        $this->assertSame($expectedAnnualCount, $report['averages']['annual']['evaluation_count']);
    }

    public function testPendingEmployeeApiModelReturnsExpectedEmployeeFields(): void
    {
        [$programId, $employeeId] = $this->fixture('course', 'employee_in_course');
        $this->db->query("INSERT INTO program_participants (program_id, employee_id, status) VALUES (:pid, :eid, 'pending')
            ON DUPLICATE KEY UPDATE status = 'pending'");
        $this->db->bind(':pid', $programId);
        $this->db->bind(':eid', $employeeId);
        $this->db->execute();

        $rows = (new ProgramParticipant())->pendingForProgram($programId);
        $found = false;
        foreach ($rows as $row) {
            if ((int)$row->id === $employeeId && isset($row->full_name, $row->emp_code)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'تُرجع قائمة المعلّقين المعرّف والاسم والرقم الوظيفي للواجهة.');
    }

    public function testProfileShowsProgramNameForCompletedEvaluation(): void
    {
        [$programId, $employeeId, $templateId] = $this->fixture('course', 'employee_in_course');
        $evaluationId = $this->createEvaluation($employeeId, $templateId, 'course');
        $this->db->query("UPDATE evaluations SET status = 'completed', final_score = 80.0 WHERE id = :id");
        $this->db->bind(':id', $evaluationId);
        $this->db->execute();
        $service = new EvaluationProgramContextService($this->db);
        $context = $service->resolveContext('employee_in_course', $programId, 'course');
        $service->persist($evaluationId, $employeeId, 'course', $context, 80.0);

        $report = (new EmployeeReportService($this->db))->getProfileReport($employeeId);
        $found = false;
        foreach ($report['records'] as $record) {
            if ((int)$record->id === $evaluationId) {
                $found = $record->program_name !== null && $record->program_name !== '';
                break;
            }
        }
        $this->assertTrue($found, 'يعرض التقرير اسم البرنامج المرتبط بالتقييم.');
    }
}
