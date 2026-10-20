<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Models\Template;
use App\Models\Evaluation;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Department;
use App\Services\FormulaEngine;

class AdminReportController extends Controller {

    public function __construct() {
        Auth::enforce('admin_reports.view');
    }

    public function index() {
        $evaluationModel = new Evaluation();
        $reports = $evaluationModel->getAdminReports();
        $stats = [
            'total' => count($reports),
            'completed' => count(array_filter($reports, fn($r) => $r->status === 'completed')),
            'pending' => count(array_filter($reports, fn($r) => $r->status === 'pending_review')),
            'avg_compliance' => $this->calculateAvgCompliance($reports)
        ];
        $pageTitle = 'التقارير الإدارية والتنظيمية';
        require APP_ROOT . '/app/Views/admin-reports/index.php';
    }

    public function create() {
        $templateModel = new Template();
        $branchModel = new Branch();
        $departmentModel = new Department();
        $employeeModel = new Employee();

        $templates = $templateModel->getActiveByType('admin');
        $branches = $branchModel->getAll();
        $departments = $departmentModel->getAll();
        $employees = $employeeModel->getAll();

        $templateStructure = null;
        if (!empty($templates)) {
            $templateStructure = $templateModel->getTemplateWithStructure($templates[0]->id);
        }

        $pageTitle = 'إنشاء تقرير إداري جديد';
        require APP_ROOT . '/app/Views/admin-reports/create.php';
    }

    public function loadTemplate() {
        $templateModel = new Template();
        $templateId = (int)($_GET['id'] ?? 0);
        header('Content-Type: application/json');
        if ($templateId) {
            $template = $templateModel->getTemplateWithStructure($templateId);
            echo json_encode($template ?? [], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([]);
        }
        exit;
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/reports');
        }

        $evaluationModel = new Evaluation();
        $templateModel = new Template();
        $formulaEngine = new FormulaEngine();

        $data = [
            'template_id' => $_POST['template_id'],
            'entity_type' => $_POST['entity_type'],
            'entity_id' => $_POST['entity_id'] ?? null,
            'report_title' => $_POST['report_title'],
            'period_type' => $_POST['period_type'],
            'period_start' => $_POST['period_start'],
            'period_end' => $_POST['period_end'],
            'actual_force' => $_POST['actual_force'] ?? 0,
            'applied_force' => $_POST['applied_force'] ?? 0,
            'status' => $_POST['status'] ?? 'draft',
            'notes' => $_POST['notes'] ?? '',
            'created_by' => Auth::id()
        ];

        if ($data['actual_force'] > 0) {
            $data['compliance_rate'] = round(($data['applied_force'] / $data['actual_force']) * 100, 2);
        }

        $reportId = $evaluationModel->createAdminReport($data);

        $template = $templateModel->getTemplateWithStructure($_POST['template_id']);
        $userAnswers = $_POST['answers'] ?? [];
        $computedValues = $formulaEngine->computeAllSections($template, $userAnswers, (int)$data['actual_force']);

        foreach ($computedValues as $sectionId => $value) {
            $evaluationModel->saveAdminAnswer($reportId, (int)$sectionId, $value);
        }

        if (!empty($_POST['evaluators'])) {
            foreach ($_POST['evaluators'] as $evaluatorId) {
                $evaluationModel->addReportEvaluator($reportId, (int)$evaluatorId);
            }
        }

        Flash::set('success', 'تم حفظ التقرير بنجاح');
        $this->redirect('/admin/reports/show/' . $reportId);
    }

    public function show($id) {
        $evaluationModel = new Evaluation();
        $templateModel = new Template();

        $report = $evaluationModel->getAdminReportById($id);
        if (!$report) {
            return (new ErrorController())->notFound();
        }

        $template = $templateModel->getTemplateWithStructure($report->template_id);
        $answers = $evaluationModel->getAdminAnswers($id);
        $evaluators = $evaluationModel->getReportEvaluators($id);

        $answersMap = [];
        foreach ($answers as $a) {
            $answersMap[$a->section_id] = $a->raw_value;
        }

        $pageTitle = $report->report_title;
        require APP_ROOT . '/app/Views/admin-reports/show.php';
    }

    public function edit($id) {
        $evaluationModel = new Evaluation();
        $templateModel = new Template();
        $branchModel = new Branch();
        $departmentModel = new Department();
        $employeeModel = new Employee();

        $report = $evaluationModel->getAdminReportById($id);
        if (!$report) {
            return (new ErrorController())->notFound();
        }

        $template = $templateModel->getTemplateWithStructure($report->template_id);
        $templates = $templateModel->getActiveByType('admin');
        $branches = $branchModel->getAll();
        $departments = $departmentModel->getAll();
        $employees = $employeeModel->getAll();
        $answers = $evaluationModel->getAdminAnswers($id);
        $evaluators = $evaluationModel->getReportEvaluators($id);

        $answersMap = [];
        foreach ($answers as $a) {
            $answersMap[$a->section_id] = $a->raw_value;
        }

        $pageTitle = 'تعديل التقرير: ' . $report->report_title;
        require APP_ROOT . '/app/Views/admin-reports/edit.php';
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/reports');
        }

        $evaluationModel = new Evaluation();
        $templateModel = new Template();
        $formulaEngine = new FormulaEngine();

        $data = [
            'report_title' => $_POST['report_title'],
            'period_type' => $_POST['period_type'],
            'period_start' => $_POST['period_start'],
            'period_end' => $_POST['period_end'],
            'actual_force' => $_POST['actual_force'] ?? 0,
            'applied_force' => $_POST['applied_force'] ?? 0,
            'status' => $_POST['status'] ?? 'draft',
            'notes' => $_POST['notes'] ?? ''
        ];

        if ($data['actual_force'] > 0) {
            $data['compliance_rate'] = round(($data['applied_force'] / $data['actual_force']) * 100, 2);
        }

        $evaluationModel->updateAdminReport($id, $data);

        $report = $evaluationModel->getAdminReportById($id);
        $template = $templateModel->getTemplateWithStructure($report->template_id);
        $userAnswers = $_POST['answers'] ?? [];
        $computedValues = $formulaEngine->computeAllSections($template, $userAnswers, (int)$data['actual_force']);

        $evaluationModel->deleteAdminAnswers($id);
        foreach ($computedValues as $sectionId => $value) {
            $evaluationModel->saveAdminAnswer($id, (int)$sectionId, $value);
        }

        if (!empty($_POST['evaluators'])) {
            foreach ($_POST['evaluators'] as $evaluatorId) {
                $evaluationModel->addReportEvaluator($id, (int)$evaluatorId);
            }
        }

        Flash::set('success', 'تم تحديث التقرير بنجاح');
        $this->redirect('/admin/reports/show/' . $id);
    }

    public function print($id) {
        $evaluationModel = new Evaluation();
        $templateModel = new Template();

        $report = $evaluationModel->getAdminReportById($id);
        if (!$report) {
            return (new ErrorController())->notFound();
        }

        $template = $templateModel->getTemplateWithStructure($report->template_id);
        $answers = $evaluationModel->getAdminAnswers($id);
        $evaluators = $evaluationModel->getReportEvaluators($id);

        $answersMap = [];
        foreach ($answers as $a) {
            $answersMap[$a->section_id] = $a->raw_value;
        }

        require APP_ROOT . '/app/Views/admin-reports/print.php';
    }

    public function archive() {
        $evaluationModel = new Evaluation();
        $reports = $evaluationModel->getAdminReportsArchived();
        $pageTitle = 'أرشيف التقارير الإدارية';
        require APP_ROOT . '/app/Views/admin-reports/archive.php';
    }

    private function calculateAvgCompliance($reports) {
        $total = 0;
        $count = 0;
        foreach ($reports as $report) {
            if ($report->compliance_rate !== null) {
                $total += $report->compliance_rate;
                $count++;
            }
        }
        return $count > 0 ? round($total / $count, 1) : 0;
    }
}

