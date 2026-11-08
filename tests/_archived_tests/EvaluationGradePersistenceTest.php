<?php

use App\Core\Database;
use App\Models\Evaluation;

class EvaluationGradePersistenceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
    }

    public function testNumericEvaluationPersistsGradeLabelAndValue(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $code = 'T-GRADE-' . bin2hex(random_bytes(5));
            $db->query('INSERT INTO employees (emp_code, full_name) VALUES (:code, :name)');
            $db->bind(':code', $code);
            $db->bind(':name', 'Test Grade');
            $db->execute();
            $employeeId = (int)$db->lastInsertId();

            $db->query('SELECT id FROM form_templates ORDER BY id LIMIT 1');
            $templateId = (int)$db->fetchColumn();
            $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
            $userId = (int)$db->fetchColumn();
            $this->assertTrue($templateId > 0 && $userId > 0, 'Fixture template and user are required.');

            $db->query(
                "INSERT INTO evaluations
                    (template_id, status, evaluation_date, evaluation_type, evaluated_employee_id,
                     employee_name, employee_code, branch_name, department_name, position_name,
                     final_score, created_by_user_id)
                 VALUES
                    (:template_id, 'draft', '2025-01-15', 'annual', :employee_id,
                     'Test Grade', :employee_code, '', '', '', NULL, :user_id)"
            );
            $db->bind(':template_id', $templateId);
            $db->bind(':employee_id', $employeeId);
            $db->bind(':employee_code', $code);
            $db->bind(':user_id', $userId);
            $db->execute();
            $evaluationId = (int)$db->lastInsertId();

            $this->assertTrue((new Evaluation())->updateScoreAndStatus($evaluationId, 90.0, 'completed'));

            $db->query('SELECT final_score, final_grade_label, final_grade_value FROM evaluations WHERE id = :id');
            $db->bind(':id', $evaluationId);
            $row = $db->fetch();
            $this->assertEquals(90.0, (float)$row->final_score);
            $this->assertSame('ممتاز', $row->final_grade_label);
            $this->assertEquals(5.0, (float)$row->final_grade_value);
        } finally {
            $db->rollBack();
        }
    }
}
