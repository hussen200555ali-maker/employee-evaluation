<?php

use App\Core\Database;

class BackfillGradesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
    }

    public function testBackfillGradesFillsNullLabels(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $db->query('SELECT id FROM form_templates ORDER BY id LIMIT 1');
            $templateId = (int)$db->fetchColumn();
            $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
            $userId = (int)$db->fetchColumn();
            $this->assertTrue($templateId > 0 && $userId > 0, 'Fixture template and user are required.');

            $code = 'CD035-BF-' . bin2hex(random_bytes(4));
            $db->query('INSERT INTO employees (emp_code, full_name) VALUES (:code, :name)');
            $db->bind(':code', $code);
            $db->bind(':name', 'Backfill Grade Fixture');
            $db->execute();
            $employeeId = (int)$db->lastInsertId();

            $db->query(
                "INSERT INTO evaluations
                    (template_id, status, evaluation_date, evaluation_type, evaluated_employee_id,
                     employee_name, employee_code, branch_name, department_name, position_name,
                     final_score, final_grade_label, final_grade_value, created_by_user_id)
                 VALUES
                    (:template_id, 'completed', '2025-01-15', 'annual', :employee_id,
                     'Backfill Grade Fixture', :employee_code, '', '', '', 90.00, NULL, NULL, :user_id)"
            );
            $db->bind(':template_id', $templateId);
            $db->bind(':employee_id', $employeeId);
            $db->bind(':employee_code', $code);
            $db->bind(':user_id', $userId);
            $db->execute();
            $evaluationId = (int)$db->lastInsertId();

            $migration = file_get_contents(APP_ROOT . '/database/migrations/2026_11_04_CD020_S35_backfill_grades.sql');
            $this->assertNotFalse($migration);
            $db->getPdo()->exec($migration);

            $db->query('SELECT final_grade_label, final_grade_value FROM evaluations WHERE id = :id');
            $db->bind(':id', $evaluationId);
            $row = $db->fetch();
            $this->assertSame('ممتاز', $row->final_grade_label);
            $this->assertEquals(5.0, (float)$row->final_grade_value);
        } finally {
            $db->rollBack();
        }
    }
}
