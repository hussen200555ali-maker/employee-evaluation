<?php

use App\Core\Database;
use App\Services\EmployeeReportService;

class EmployeeReportServiceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();

        $db = Database::getInstance();
        $db->query("SELECT COUNT(*) FROM grading_scales
                    WHERE scale_name = 'السلم الخماسي' AND is_active = 1");
        if ((int)$db->fetchColumn() === 0) {
            $this->markSkipped('السلم الخماسي النشط مطلوب لاختبار تحويل الدرجات.');
        }
    }

    public function testAveragesMapScoresAndPreserveEmptyYears(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            [$employeeId, $templateId, $userId] = $this->createEmployeeFixture($db);
            $this->insertEvaluation($db, $employeeId, $templateId, $userId, 'annual', '2023-04-10', 95, null, null);
            $this->insertEvaluation($db, $employeeId, $templateId, $userId, 'course', '2023-08-12', 85, null, null);
            $this->insertEvaluation($db, $employeeId, $templateId, $userId, 'workshop', '2025-02-20', 75, null, null);
            $this->insertEvaluation($db, $employeeId, $templateId, $userId, 'periodic', '2025-04-20', null, 'جيد جدًا', 4);
            $this->insertEvaluation($db, $employeeId, $templateId, $userId, 'annual', '2024-01-01', 100, null, null, 'draft');

            $report = (new EmployeeReportService($db))->getProfileReport($employeeId);

            $this->assertSame(4, count($report['records']));
            $this->assertEquals(5.0, $report['grading_scale_max_value']);
            $this->assertEquals(5.0, $report['averages']['annual']['average']);
            $this->assertSame('ممتاز', $report['averages']['annual']['grade_label']);
            $this->assertEquals(4.0, $report['averages']['course']['average']);
            $this->assertEquals(3.0, $report['averages']['workshop']['average']);
            $this->assertEquals(4.0, $report['averages']['periodic']['average']);
            $this->assertEquals(4.0, $report['averages']['overall']['average']);
            $this->assertSame('جيد جدًا', $report['averages']['overall']['grade_label']);

            $this->assertSame(3, count($report['yearly']));
            $this->assertSame(2023, $report['yearly'][0]['year']);
            $this->assertEquals(4.5, $report['yearly'][0]['average']);
            $this->assertSame(2024, $report['yearly'][1]['year']);
            $this->assertSame(0, $report['yearly'][1]['evaluation_count']);
            $this->assertNull($report['yearly'][1]['average']);
            $this->assertNull($report['yearly'][1]['grade_label']);
            $this->assertSame(2025, $report['yearly'][2]['year']);
            $this->assertEquals(3.5, $report['yearly'][2]['average']);
        } finally {
            $db->rollBack();
        }
    }

    public function testEmptyHistoryReturnsNullAveragesAndNoYears(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            [$employeeId] = $this->createEmployeeFixture($db);
            $report = (new EmployeeReportService($db))->getProfileReport($employeeId);

            $this->assertSame([], $report['records']);
            $this->assertSame([], $report['yearly']);
            foreach ($report['averages'] as $summary) {
                $this->assertSame(0, $summary['evaluation_count']);
                $this->assertSame(0, $summary['scored_count']);
                $this->assertNull($summary['average']);
                $this->assertNull($summary['grade_label']);
            }
        } finally {
            $db->rollBack();
        }
    }

    private function createEmployeeFixture(Database $db): array
    {
        $db->query('SELECT id FROM form_templates ORDER BY id LIMIT 1');
        $templateId = (int)$db->fetchColumn();
        $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        $userId = (int)$db->fetchColumn();
        $this->assertTrue($templateId > 0 && $userId > 0, 'Fixture template and user are required.');

        $code = 'CD017-' . bin2hex(random_bytes(6));
        $db->query('INSERT INTO employees (emp_code, full_name) VALUES (:code, :name)');
        $db->bind(':code', $code);
        $db->bind(':name', 'CD017 Report Fixture');
        $db->execute();

        return [(int)$db->lastInsertId(), $templateId, $userId];
    }

    private function insertEvaluation(
        Database $db,
        int $employeeId,
        int $templateId,
        int $userId,
        string $type,
        string $date,
        ?float $score,
        ?string $gradeLabel,
        ?float $gradeValue,
        string $status = 'completed'
    ): void {
        $db->query(
            'INSERT INTO evaluations
                (template_id, status, evaluation_date, evaluation_type, evaluated_employee_id,
                 employee_name, employee_code, branch_name, department_name, position_name,
                 final_score, final_grade_label, final_grade_value, created_by_user_id)
             SELECT :template_id, :status, :evaluation_date, :evaluation_type, e.id,
                    e.full_name, e.emp_code, \'\', \'\', \'\',
                    :final_score, :grade_label, :grade_value, :created_by
             FROM employees e WHERE e.id = :employee_id'
        );
        $db->bind(':template_id', $templateId);
        $db->bind(':status', $status);
        $db->bind(':evaluation_date', $date);
        $db->bind(':evaluation_type', $type);
        $db->bind(':final_score', $score);
        $db->bind(':grade_label', $gradeLabel);
        $db->bind(':grade_value', $gradeValue);
        $db->bind(':created_by', $userId);
        $db->bind(':employee_id', $employeeId);
        $db->execute();
    }
}
