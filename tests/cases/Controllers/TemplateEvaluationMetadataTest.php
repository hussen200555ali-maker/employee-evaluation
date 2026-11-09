<?php

use App\Core\Database;
use App\Models\Evaluation;
use App\Models\Template;

class TemplateEvaluationMetadataTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
    }

    public function testCreateAndUpdateEvaluationMetadata(): void
    {
        $db = Database::getInstance();
        $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        $userId = (int)$db->fetchColumn();
        $this->assertTrue($userId > 0, 'يلزم وجود مستخدم لاختبار إنشاء القالب.');

        $templateModel = new Template();
        $evaluationId = null;
        $name = 'CD016 Metadata ' . bin2hex(random_bytes(6));
        $templateId = null;

        try {
            $templateId = (int)$templateModel->createTemplateWithStructure([
                'template_name' => $name,
                'template_description' => 'CD-016 metadata test',
                'target_position_id' => null,
                'target_type' => 'employee',
                'evaluation_mode' => 'descriptive',
                'evaluation_frequency' => 'quarterly',
                'target_entity_id' => null,
                'unit_scope' => null,
                'is_active' => 1,
                'user_id' => $userId,
            ], []);
            $this->assertTrue($templateId > 0);

            $created = $templateModel->getTemplateWithStructure($templateId);
            $this->assertSame('descriptive', $created['evaluation_mode']);
            $this->assertSame('quarterly', $created['evaluation_frequency']);

            $templateModel->updateTemplateWithStructure($templateId, [
                'template_name' => $name,
                'template_description' => 'CD-016 metadata test',
                'target_position_id' => null,
                'target_type' => 'employee',
                'evaluation_mode' => 'mixed',
                'evaluation_frequency' => 'monthly',
                'target_entity_id' => null,
                'unit_scope' => null,
                'is_active' => 1,
                'user_id' => $userId,
            ], []);

            $updated = $templateModel->getTemplateWithStructure($templateId);
            $this->assertSame('mixed', $updated['evaluation_mode']);
            $this->assertSame('monthly', $updated['evaluation_frequency']);

            $evaluationModel = new Evaluation();
            $evaluationId = (int)$evaluationModel->create([
                'template_id' => $templateId,
                'entity_type' => 'employee',
                'evaluation_type' => 'periodic',
                'evaluated_employee_id' => null,
                'evaluator_employee_id' => null,
                'evaluation_date' => date('Y-m-d'),
                'created_by_user_id' => $userId,
                'employee_name' => 'CD016 Test Employee',
                'employee_code' => 'CD016-' . bin2hex(random_bytes(4)),
                'branch_name' => '',
                'department_name' => '',
                'position_name' => '',
                'final_grade_label' => 'Example',
                'final_grade_value' => 4.0,
            ]);
            $this->assertTrue($evaluationId > 0);

            $createdEvaluation = $evaluationModel->findById($evaluationId);
            $this->assertSame('periodic', $createdEvaluation->evaluation_type);
            $this->assertSame('Example', $createdEvaluation->final_grade_label);
            $this->assertSame('4.00', (string)$createdEvaluation->final_grade_value);

            $evaluationModel->updateScoreGradeAndStatus($evaluationId, null, 'Updated', 5.0, 'completed');
            $updatedEvaluation = $evaluationModel->findById($evaluationId);
            $this->assertSame('Updated', $updatedEvaluation->final_grade_label);
            $this->assertSame('completed', $updatedEvaluation->status);
        } finally {
            if ($evaluationId !== null && $evaluationId > 0) {
                $db->query('DELETE FROM evaluations WHERE id = :id');
                $db->bind(':id', $evaluationId);
                $db->execute();
            }
            if ($templateId !== null && $templateId > 0) {
                $db->query('DELETE FROM form_templates WHERE id = :id');
                $db->bind(':id', $templateId);
                $db->execute();
            }
        }
    }
}
