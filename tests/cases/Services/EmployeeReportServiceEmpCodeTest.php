<?php

use App\Core\Database;
use App\Services\EmployeeReportService;

class EmployeeReportServiceEmpCodeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
    }

    public function testEmployeeWithEmptyEmpCodeExcludedFromReport(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            [$employeeId, $templateId, $userId] = $this->createEmployeeFixture($db, '');
            $this->insertEvaluation($db, $employeeId, $templateId, $userId, 'annual', '2025-01-15', 90.0, null, null, 'completed');
            $report = (new EmployeeReportService($db))->getProfileReport($employeeId);
            $this->assertSame([], $report['records']);
            $this->assertSame([], $report['yearly']);
            foreach ($report['averages'] as $summary) {
                $this->assertSame(0, $summary['evaluation_count']);
                $this->assertNull($summary['average']);
            }
        } finally { $db->rollBack(); }
    }

    public function testEmployeeWithValidEmpCodeIncludedInReport(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            [$employeeId, $templateId, $userId] = $this->createEmployeeFixture($db, 'CD020-VALID-001');
            $this->insertEvaluation($db, $employeeId, $templateId, $userId, 'annual', '2025-01-15', 90.0, null, null, 'completed');
            $report = (new EmployeeReportService($db))->getProfileReport($employeeId);
            $this->assertSame(1, count($report['records']));
            $this->assertSame('annual', $report['records'][0]->evaluation_type);
            $this->assertEquals(90.0, (float)$report['records'][0]->final_score);
            $this->assertSame(1, $report['averages']['annual']['evaluation_count']);
            $this->assertEquals(90.0, $report['averages']['annual']['average']);
        } finally { $db->rollBack(); }
    }

    private function createEmployeeFixture(Database $db, string $empCode): array
    {
        $db->query('SELECT id FROM form_templates ORDER BY id LIMIT 1');
        $templateId = (int)$db->fetchColumn();
        $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        $userId = (int)$db->fetchColumn();
        $this->assertTrue($templateId > 0 && $userId > 0);
        $name = 'CD020 EmpCode Fixture ' . bin2hex(random_bytes(3));
        $db->query('INSERT INTO employees (emp_code, full_name) VALUES (:code, :name)');
        $db->bind(':code', $empCode);
        $db->bind(':name', $name);
        $db->execute();
        return [(int)$db->lastInsertId(), $templateId, $userId];
    }

    private function insertEvaluation(Database $db, int $employeeId, int $templateId, int $userId, string $type, string $date, ?float $score, ?string $gradeLabel, ?float $gradeValue, string $status = 'completed'): void
    {
        $db->query('INSERT INTO evaluations (template_id, status, evaluation_date, evaluation_type, evaluated_employee_id, employee_name, employee_code, branch_name, department_name, position_name, final_score, final_grade_label, final_grade_value, created_by_user_id) SELECT :template_id, :status, :evaluation_date, :evaluation_type, e.id, e.full_name, e.emp_code, \'\', \'\', \'\', :final_score, :grade_label, :grade_value, :created_by FROM employees e WHERE e.id = :employee_id');
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
