<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\Template;
use App\Services\AveragesEngine;

/**
 * CD-022 — Bulk Evaluation Controller
 * Allows managers to evaluate multiple employees in one session.
 */
final class BulkEvaluationController extends Controller
{
    /**
     * GET /evaluations/bulk-create — show the bulk evaluation page.
     */
    public function create(): void
    {
        Auth::enforce('evaluations.create');

        $db = Database::getInstance();

        // Load available templates
        $db->query('SELECT id, template_name, evaluation_mode, evaluation_frequency
                    FROM form_templates ORDER BY template_name');
        $templates = $db->fetchAll();

        // Load branches for the filter dropdown
        $db->query('SELECT id, name FROM branches ORDER BY name');
        $branches = $db->fetchAll();

        // Get current user's employee info for evaluator fields
        $user = Auth::user();
        $evaluatorName = '';
        $evaluatorTitle = '';
        if ($user && !empty($user->employee_id)) {
            $empModel = new Employee();
            $emp = $empModel->findById((int)$user->employee_id);
            if ($emp) {
                $evaluatorName = $emp->full_name;
                $evaluatorTitle = $emp->position_title ?? '';
            }
        }

        $this->view('evaluations/bulk-create', [
            'pageTitle'     => 'التقييم الجماعي',
            'templates'     => $templates,
            'branches'      => $branches,
            'evaluatorName' => $evaluatorName,
            'evaluatorTitle'=> $evaluatorTitle,
            'csrfToken'     => Auth::generateCsrfToken(),
        ]);
    }

    /**
     * GET /api/templates/:id/schema — return template structure as JSON.
     */
    public function schema(int $templateId): void
    {
        Auth::enforce('evaluations.create');

        $template = (new Template())->getTemplateWithStructure($templateId);
        if (!$template) {
            $this->json(['error' => 'القالب غير موجود'], 404);
            return;
        }

        // Build a clean JSON structure for the frontend
        $schema = [
            'id'                 => (int)$template['id'],
            'template_name'      => $template['template_name'],
            'evaluation_mode'    => $template['evaluation_mode'] ?? 'numeric',
            'evaluation_frequency' => $template['evaluation_frequency'] ?? 'yearly',
            'criteria'           => [],
        ];

        foreach (($template['criteria'] ?? []) as $criterion) {
            $c = [
                'id'             => (int)$criterion['id'],
                'name'           => $criterion['criterion_name'],
                'weight'         => (float)$criterion['criterion_weight'],
                'sections'       => [],
            ];
            foreach (($criterion['sections'] ?? []) as $section) {
                $type = $section['evaluation_type'] ?? 'percentage';
                // CD-024: parse evaluation_options for grade/textarea/overall_grade
                $opts = null;
                if (!empty($section['evaluation_options'])) {
                    $opts = is_string($section['evaluation_options'])
                        ? json_decode($section['evaluation_options'], true)
                        : $section['evaluation_options'];
                }
                $secData = [
                    'id'             => (int)$section['id'],
                    'criterion_id'   => (int)$criterion['id'],
                    'name'           => $section['section_name'],
                    'type'           => $type,
                    'weight'         => (float)($section['section_weight'] ?? 0),
                    'min_value'      => isset($section['min_value']) ? (float)$section['min_value'] : null,
                    'max_value'      => isset($section['max_value']) ? (float)$section['max_value'] : null,
                    'options'        => $opts,
                ];
                // CD-024: for overall_grade, resolve the grading scale rows server-side
                if ($type === 'overall_grade' && is_array($opts) && !empty($opts['scale_name'])) {
                    $secData['scale_name'] = (string)$opts['scale_name'];
                    $secData['grades'] = [];
                    $scaleRows = (new \App\Models\GradingScale())->getByName((string)$opts['scale_name']);
                    foreach ($scaleRows as $g) {
                        $secData['grades'][] = [
                            'label'        => $g->grade_label,
                            'value'        => (float)$g->numeric_value,
                            'min_value'    => (float)$g->min_value,
                            'max_value'    => (float)$g->max_value,
                        ];
                    }
                }
                $c['sections'][] = $secData;
            }
            $schema['criteria'][] = $c;
        }

        $this->json($schema);
    }

    /**
     * POST /api/employees/quick-add — create a minimal employee (incomplete profile).
     */
    public function quickAdd(): void
    {
        Auth::enforce('evaluations.create');
        Auth::validateCsrfToken($_POST['_token'] ?? $_POST['_csrf'] ?? '');

        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $empCode  = trim((string)($_POST['emp_code'] ?? ''));
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $deptId   = (int)($_POST['department_id'] ?? 0);

        if ($fullName === '' || $empCode === '') {
            $this->json(['error' => 'الاسم والرقم الوظيفي مطلوبان'], 400);
            return;
        }

        $db = Database::getInstance();

        // Check for duplicate emp_code
        $db->query('SELECT id FROM employees WHERE emp_code = :code');
        $db->bind(':code', $empCode);
        if ($db->fetch()) {
            $this->json(['error' => 'الرقم الوظيفي موجود مسبقاً'], 409);
            return;
        }

        // Insert minimal employee
        $db->query(
            'INSERT INTO employees (emp_code, full_name, branch_id, department_id, is_active, profile_status, created_at)
             VALUES (:code, :name, :branch, :dept, 1, "incomplete", NOW())'
        );
        $db->bind(':code', $empCode);
        $db->bind(':name', $fullName);
        $db->bind(':branch', $branchId > 0 ? $branchId : null);
        $db->bind(':dept', $deptId > 0 ? $deptId : null);

        if (!$db->execute()) {
            $this->json(['error' => 'تعذر إنشاء الموظف'], 500);
            return;
        }

        $employeeId = (int)$db->lastInsertId();

        // Return the new employee data
        $db->query('SELECT e.id, e.emp_code, e.full_name, d.name AS department_name, b.name AS branch_name
                    FROM employees e
                    LEFT JOIN departments d ON d.id = e.department_id
                    LEFT JOIN branches b ON b.id = e.branch_id
                    WHERE e.id = :id');
        $db->bind(':id', $employeeId);
        $emp = $db->fetch();

        $this->json([
            'success' => true,
            'employee' => [
                'id'              => $employeeId,
                'emp_code'        => $emp->emp_code,
                'full_name'       => $emp->full_name,
                'department_name' => $emp->department_name ?? '',
                'branch_name'     => $emp->branch_name ?? '',
            ],
        ]);
    }

    /**
     * POST /evaluations/save-single — save one evaluation (individual save).
     */
    public function saveSingle(): void
    {
        Auth::enforce('evaluations.create');
        Auth::validateCsrfToken($_POST['_token'] ?? $_POST['_csrf'] ?? '');

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $templateId = (int)($_POST['template_id'] ?? 0);
        $evalDate   = trim((string)($_POST['evaluation_date'] ?? date('Y-m-d')));
        $results    = $_POST['results'] ?? [];
        $evaluatorName  = trim((string)($_POST['evaluator_name'] ?? ''));
        $evaluatorTitle = trim((string)($_POST['evaluator_title'] ?? ''));

        if ($employeeId < 1 || $templateId < 1 || empty($results)) {
            $this->json(['error' => 'بيانات ناقصة'], 400);
            return;
        }

        try {
            $evaluationId = $this->createEvaluation($employeeId, $templateId, $evalDate, $evaluatorName, $evaluatorTitle);
            $this->saveResults($evaluationId, $results, $templateId);

            $this->json([
                'success'       => true,
                'evaluation_id' => $evaluationId,
                'message'       => 'تم حفظ التقييم بنجاح',
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'فشل الحفظ: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /evaluations/bulk-save — save multiple evaluations (Save All).
     */
    public function bulkSave(): void
    {
        Auth::enforce('evaluations.create');

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        // CSRF: the body is JSON, so read the token from the decoded payload
        // (the frontend sends _token inside the JSON body).
        $csrfToken = is_array($data) ? ($data['_token'] ?? $data['_csrf'] ?? '') : '';
        Auth::validateCsrfToken($csrfToken);

        if (!$data || empty($data['evaluations'])) {
            $this->json(['error' => 'لا توجد تقييمات للحفظ'], 400);
            return;
        }

        $templateId = (int)($data['template_id'] ?? 0);
        $evalDate   = $data['evaluation_date'] ?? date('Y-m-d');
        $evaluatorName  = $data['evaluator_name'] ?? '';
        $evaluatorTitle = $data['evaluator_title'] ?? '';
        $evaluations    = $data['evaluations'];

        if ($templateId < 1) {
            $this->json(['error' => 'قالب غير محدد'], 400);
            return;
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        $saved = 0;
        $errors = [];

        try {
            foreach ($evaluations as $item) {
                $employeeId = (int)($item['employee_id'] ?? 0);
                $results    = $item['results'] ?? [];

                if ($employeeId < 1 || empty($results)) {
                    $errors[] = "موظف ID {$employeeId}: بيانات ناقصة";
                    continue;
                }

                try {
                    $evaluationId = $this->createEvaluation($employeeId, $templateId, $evalDate, $evaluatorName, $evaluatorTitle);
                    $this->saveResults($evaluationId, $results, $templateId);
                    $saved++;
                } catch (\Throwable $e) {
                    $errors[] = "موظف ID {$employeeId}: " . $e->getMessage();
                }
            }

            $db->commit();

            $this->json([
                'success'  => true,
                'saved'    => $saved,
                'errors'   => $errors,
                'message'  => "تم حفظ {$saved} تقييم" . ($errors ? " مع " . count($errors) . " خطأ" : ''),
            ]);
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->json(['error' => 'فشل الحفظ الجماعي: ' . $e->getMessage()], 500);
        }
    }

    // === Private helpers ===

    private function createEvaluation(int $employeeId, int $templateId, string $date, string $evalName, string $evalTitle): int
    {
        $db = Database::getInstance();
        $userId = (int)Auth::id();

        // Get evaluator employee_id
        $user = Auth::user();
        $evaluatorEmpId = $user && !empty($user->employee_id) ? (int)$user->employee_id : null;

        // Get template target_type for evaluation_type
        $db->query('SELECT target_type FROM form_templates WHERE id = :id');
        $db->bind(':id', $templateId);
        $tpl = $db->fetch();
        $evalType = 'annual';
        if ($tpl) {
            $targetType = $tpl->target_type;
            if ($targetType === 'course') $evalType = 'course';
            elseif ($targetType === 'workshop') $evalType = 'workshop';
        }

        // Get employee snapshot data
        $empModel = new Employee();
        $emp = $empModel->findById($employeeId);
        if (!$emp) throw new \RuntimeException("الموظف غير موجود");

        $db->query(
            'INSERT INTO evaluations
                (template_id, status, evaluation_date, evaluation_type, evaluated_employee_id,
                 evaluator_employee_id, evaluator_name, evaluator_title,
                 employee_name, employee_code, branch_name, department_name, position_name,
                 entity_type, entity_id, created_by_user_id, created_at)
             VALUES
                (:tpl, "draft", :date, :etype, :emp,
                 :eval_emp, :eval_name, :eval_title,
                 :emp_name, :emp_code, :branch, :dept, :pos,
                 "employee", :entity, :user, NOW())'
        );
        $db->bind(':tpl', $templateId);
        $db->bind(':date', $date);
        $db->bind(':etype', $evalType);
        $db->bind(':emp', $employeeId);
        $db->bind(':entity', $employeeId);
        $db->bind(':eval_emp', $evaluatorEmpId);
        $db->bind(':eval_name', $evalName);
        $db->bind(':eval_title', $evalTitle);
        $db->bind(':emp_name', $emp->full_name);
        $db->bind(':emp_code', $emp->emp_code);
        $db->bind(':branch', $emp->branch_name ?? '');
        $db->bind(':dept', $emp->department_name ?? '');
        $db->bind(':pos', $emp->position_title ?? '');
        $db->bind(':user', $userId);
        $db->execute();

        return (int)$db->lastInsertId();
    }

    private function saveResults(int $evaluationId, array $results, int $templateId): void
    {
        $db = Database::getInstance();
        $evalModel = new Evaluation();

        // CD-024: load template structure to know each section's type
        $template = (new Template())->getTemplateWithStructure($templateId);
        $sectionsMap = [];
        foreach (($template['criteria'] ?? []) as $criterion) {
            foreach (($criterion['sections'] ?? []) as $section) {
                $sectionsMap[(int)$section['id']] = $section;
            }
        }

        // Save answers — type-aware storage
        foreach ($results as $sectionId => $data) {
            if (strpos((string)$sectionId, 'notes') !== false || strpos((string)$sectionId, 'grade_label') !== false && !is_array($data)) {
                continue; // skip non-section keys (notes, legacy grade_label)
            }
            $sectionId = (int)$sectionId;
            if (!isset($sectionsMap[$sectionId])) continue;

            $section = $sectionsMap[$sectionId];
            $type = $section['evaluation_type'] ?? 'percentage';

            // Determine raw_value + normalized_score based on type + provided field
            $rawValue = '';
            $normalizedScore = null;

            if ($type === 'textarea' || $type === 'text') {
                $rawValue = (string)($data['text'] ?? '');
                $normalizedScore = null;
            } elseif ($type === 'overall_grade') {
                $rawValue = (string)($data['grade_label'] ?? '');
                $normalizedScore = null;
            } else {
                // numeric/percentage/grade/boolean/helper/formula/auto
                $score = isset($data['score']) ? (float)$data['score'] : null;
                if ($score !== null) {
                    $rawValue = (string)$score;
                    // Normalize: percentage/grade use raw value; numeric scales to 0-100
                    if ($type === 'numeric') {
                        $max = (float)($section['max_value'] ?? 100);
                        $min = (float)($section['min_value'] ?? 0);
                        $normalizedScore = ($max > $min) ? ((($score - $min) / ($max - $min)) * 100) : $score;
                    } else {
                        $normalizedScore = $score;
                    }
                }
            }

            $notes = $data['notes'] ?? '';

            $db->query(
                'INSERT INTO evaluation_answers (evaluation_id, section_id, raw_value, normalized_score, notes)
                 VALUES (:eval, :section, :raw, :score, :notes)'
            );
            $db->bind(':eval', $evaluationId);
            $db->bind(':section', $sectionId);
            $db->bind(':raw', $rawValue);
            $db->bind(':score', $normalizedScore);
            $db->bind(':notes', $notes);
            $db->execute();
        }

        // Calculate score and update status
        $isDescriptive = $this->isDescriptiveTemplate($templateId);
        if (!$isDescriptive) {
            $finalScore = $evalModel->calculateFinalScore($evaluationId);
            $evalModel->updateScoreAndStatus($evaluationId, $finalScore, 'completed');
        } else {
            // CD-024: descriptive — find the overall_grade section's answer
            $gradeLabel = '';
            $gradeValue = 0.0;
            // First: check per-section grade_label (new CD-024 format)
            foreach ($sectionsMap as $sid => $sec) {
                if (($sec['evaluation_type'] ?? '') === 'overall_grade') {
                    $ans = $results[$sid] ?? null;
                    if (is_array($ans) && !empty($ans['grade_label'])) {
                        $gradeLabel = (string)$ans['grade_label'];
                        break;
                    }
                }
            }
            // Fallback: legacy top-level grade_label or final_grade_label
            if (!$gradeLabel) {
                $gradeLabel = (string)($results['grade_label'] ?? $_POST['final_grade_label'] ?? '');
            }
            if ($gradeLabel) {
                // Look up numeric_value — prefer the scale_name linked to the section
                $scaleName = 'السلم الخماسي';
                foreach ($sectionsMap as $sec) {
                    if (($sec['evaluation_type'] ?? '') === 'overall_grade') {
                        $opts = is_string($sec['evaluation_options'] ?? '') ? json_decode($sec['evaluation_options'], true) : ($sec['evaluation_options'] ?? []);
                        if (is_array($opts) && !empty($opts['scale_name'])) {
                            $scaleName = (string)$opts['scale_name'];
                            break;
                        }
                    }
                }
                $val = (new \App\Models\GradingScale())->getNumericValueForLabel($scaleName, $gradeLabel);
                if ($val !== null) $gradeValue = $val;
            }
            $evalModel->updateScoreGradeAndStatus($evaluationId, $gradeValue > 0 ? $gradeValue : null, $gradeLabel, $gradeValue, 'completed');
        }
    }

    private function isDescriptiveTemplate(int $templateId): bool
    {
        $db = Database::getInstance();
        $db->query('SELECT evaluation_mode FROM form_templates WHERE id = :id');
        $db->bind(':id', $templateId);
        $tpl = $db->fetch();
        return $tpl && ($tpl->evaluation_mode === 'descriptive' || $tpl->evaluation_mode === 'mixed');
    }
}
