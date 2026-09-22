<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Template; 
use App\Models\Criterion;
use App\Models\ReportModel;
use App\Models\ReportTemplateModel; // تأكد من وجود هذا السطر

class ReportsController extends Controller {

    public function __construct() {
        // Auth::can('reports.view.all');
    }

    /**
     * يعرض صفحة بناء التقرير التفاعلية.
     */
    public function builder() {
        $templateModel = new Template();
        $criteriaModel = new Criterion();

        $data = [
            'pageTitle' => 'بناء تقرير الأداء المؤسسي',
            'templates' => $templateModel->getAll(),
            'criteria' => $criteriaModel->getAllWithTemplateName() 
        ];

        $this->view('reports/interactive_report', $data);
    }

    /**
     * يولد بيانات التقرير ويعيدها كبيانات JSON للـ API.
     */
    public function generate() {
        try {
            $isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            if (!$isAjaxRequest || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
                throw new \Exception("Invalid request.");
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON format received.");
            }

            $config = [
                'report_title' => htmlspecialchars($input['report_title'] ?? 'تقرير الأداء العام'),
                'start_date' => !empty($input['start_date']) ? $input['start_date'] : null,
                'end_date' => !empty($input['end_date']) ? $input['end_date'] : null,
                'employee_columns' => $input['employee_columns'] ?? [],
                'courses_weight' => (float) ($input['courses_weight'] ?? 0),
                'workshops_weight' => (float) ($input['workshops_weight'] ?? 0),
            ];

            $reportModel = new ReportModel();
            $reportData = $reportModel->calculateCorporatePerformance($config);

            $templateModel = new Template();
            $criteriaModel = new Criterion();
            $templatesById = array_column($templateModel->getAll(), 'template_name', 'id');
            $criteriaById = array_column($criteriaModel->getAllWithTemplateName(), 'criterion_name', 'id');

            $data = [
                'pageTitle' => $config['report_title'],
                'reportData' => $reportData,
                'config' => $config,
                'templates' => $templatesById,
                'criteria' => $criteriaById
            ];

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $data]);
            exit;

        } catch (\Throwable $e) {
            error_log("Report Generation Error: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500 );
            echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
            exit;
        }
    }

    // =============================================================
    //  ▼▼▼ هذه هي الدالة الصحيحة التي كانت مفقودة ▼▼▼
    // =============================================================
    /**
     * API endpoint لحفظ إعدادات التقرير كقالب.
     * هذه الدالة هي التي تستقبل الطلب من JavaScript.
     */
    public function saveTemplate() {
        // تأكد أن الطلب هو POST و AJAX
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(405 ); // Method Not Allowed
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        try {
            $state = json_decode(file_get_contents('php://input'), true);

            if (empty($state) || empty($state['report_title'])) {
                throw new \Exception("بيانات التقرير غير كاملة أو العنوان مفقود.");
            }

            // إنشاء كائن من الموديل واستدعاء دالة الحفظ
            $reportTemplateModel = new ReportTemplateModel();
            $templateId = $reportTemplateModel->saveTemplate($state);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'تم حفظ قالب التقرير بنجاح!',
                'template_id' => $templateId
            ]);
            exit;

        } catch (\Throwable $e) {
            // التعامل مع أي خطأ يحدث أثناء الحفظ
            error_log("Save Template Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            header('Content-Type: application/json');
            http_response_code(500 );
            echo json_encode([
                'success' => false, 
                'message' => 'Server Error: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    // في ReportsController.php

public function printReport() {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new \Exception("Invalid request method for printing.");
        }

        // =============================================================
        //  ▼▼▼ هذا هو السطر الذي تم إصلاحه ▼▼▼
        // =============================================================
        // بدلاً من قراءة php://input، نقرأ حقل 'json' من بيانات POST
        if (empty($_POST['json'])) {
            throw new \Exception("No report data received for printing.");
        }
        $input = json_decode($_POST['json'], true);
        // =============================================================
        //  ▲▲▲ نهاية منطقة الإصلاح ▲▲▲
        // =============================================================

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON format for printing. Error: " . json_last_error_msg());
        }

        // ... باقي الكود يبقى كما هو تماماً ...

        $config = [
            'report_title' => htmlspecialchars($input['report_title'] ?? 'تقرير الأداء العام'),
            'start_date' => !empty($input['start_date']) ? $input['start_date'] : null,
            'end_date' => !empty($input['end_date']) ? $input['end_date'] : null,
            'employee_columns' => $input['employee_columns'] ?? [],
            'courses_weight' => (float) ($input['courses_weight'] ?? 0),
            'workshops_weight' => (float) ($input['workshops_weight'] ?? 0),
        ];

        $reportModel = new ReportModel();
        $reportData = $reportModel->calculateCorporatePerformance($config);

        $templateModel = new Template();
        $criteriaModel = new Criterion();
        $templatesById = array_column($templateModel->getAll(), 'template_name', 'id');
        $criteriaById = array_column($criteriaModel->getAllWithTemplateName(), 'criterion_name', 'id');

        $data = [
            'pageTitle' => $config['report_title'],
            'reportData' => $reportData,
            'config' => $config,
            'templates' => $templatesById,
            'criteria' => $criteriaById
        ];

        $this->view('reports/print_report', $data);

    } catch (\Throwable $e) {
        echo "<h1>خطأ أثناء إعداد التقرير للطباعة</h1><p>" . $e->getMessage() . "</p>";
        error_log("Print Report Error: " . $e->getMessage());
    }
}

}
