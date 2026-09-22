<?php
// ملف: app/Controllers/AnalysisController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\Department;
use App\Models\Branch;
use App\Models\Template;
use App\Core\Flash;
use App\Models\AnalysisModel;
// ▼▼▼ استيراد المكتبات اللازمة للتصدير ▼▼▼
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class AnalysisController extends Controller {

    /**
     * =================================================================
     * التحسين 1: تحسين إدارة الذاكرة والوقت في المُنشئ
     * =================================================================
     */
  
    /**
     * =================================================================
     * يعرض الصفحة الرئيسية لمنصة التحليل مع توفير كل البيانات اللازمة
     * =================================================================
     */
    public function index() {
        Auth::can('analysis.view');

        // 1. جلب قائمة الموظفين
        $employeeModel = new Employee();
        $employees = $employeeModel->getEmployeesWithCompletedEvaluations();

        // 2. جلب قائمة الأقسام
        $departmentModel = new Department();
        $departments = $departmentModel->getAll();

        // 3. جلب قائمة الفروع
        $branchModel = new Branch();
        $branches = $branchModel->getAll();

        // 4. جلب قائمة قوالب التقييم
        $templateModel = new Template();
        $templates = $templateModel->getActiveTemplates();

        // 5. تجميع كل البيانات في مصفوفة واحدة
        $data = [
            'pageTitle' => 'التحليل الاستراتيجي للأداء',
            'employees' => $employees,
            'departments' => $departments,
            'branches' => $branches,
            'templates' => $templates
        ];

        $this->view('analysis/index', $data);
    }

    /**
     * =================================================================
     * التحسين 2: تقسيم الدوال الكبيرة - تحليل الموظف الفردي
     * =================================================================
     */
    public function showEmployeeAnalysis() {
        Auth::can('analysis.view');

        if (!isset($_GET['employee_id']) || empty($_GET['employee_id'])) {
            $this->redirect('/analysis');
            return;
        }

        $employeeId = (int)$_GET['employee_id'];
        
        $employeeModel = new Employee();
        $employee = $employeeModel->findById($employeeId);

        if (!$employee) {
            $this->redirect('/analysis');
            return;
        }

        $evaluationModel = new Evaluation();
        
        // 1. جلب التحليل الشامل (لكل القوالب)
        $overallAnalysis = $evaluationModel->analyzeEmployeePerformance($employeeId);

        // 2. جلب أنواع القوالب المستخدمة لهذا الموظف
        $usedTemplates = $evaluationModel->getUsedTemplateTypesForEmployee($employeeId);

        // 3. جلب التحليل لكل نوع قالب على حدة
        $analysisByTemplate = [];
        foreach ($usedTemplates as $template) {
            $analysisByTemplate[] = [
                'template_name' => $template->template_name,
                'analysis_data' => $evaluationModel->analyzeEmployeePerformance($employeeId, null, $template->id)
            ];
        }

        $data = [
            'pageTitle' => 'تحليل أداء: ' . htmlspecialchars($employee->full_name),
            'employee' => $employee,
            'overallAnalysis' => $overallAnalysis,
            'analysisByTemplate' => $analysisByTemplate,
        ];

        $this->view('analysis/employee_dashboard_multi', $data);
    }

    /**
     * =================================================================
     * التحسين 3: تقسيم دالة report الكبيرة إلى دوال مساعدة
     * =================================================================
     */
  public function report() {
    Auth::can('analysis.view');

    try {
        // تسجيل محاولة الوصول
        error_log("Analysis Report Accessed: " . $_SERVER['REQUEST_URI']);

        // استخراج الفلاتر من الطلب
        $filters = $this->extractReportFilters();
        
        // تسجيل الفلاتر
        error_log("Report Filters: " . json_encode($filters));
        
        // التحقق من صحة المعاملات
        if (!$this->validateReportParameters($filters)) {
            error_log("Validation failed for filters: " . json_encode($filters));
            return;
        }

        // توليد بيانات التقرير
        $analysisData = $this->generateReportData($filters);
        
        // التحقق من وجود بيانات
        if (empty($analysisData['analysis']) || 
            empty($analysisData['analysis']['classified_performance'])) {
            
            Flash::set('لا توجد بيانات كافية لعرض التقرير في النطاق المحدد.', 'info');
            $this->redirect('/analysis');
            return;
        }
        
        // عرض التقرير
        $this->displayReport($analysisData, $filters);
        
    } catch (\Exception $e) {
        // معالجة الأخطاء مع تفاصيل أكثر
        error_log("Analysis Report Full Error: " . $e->getMessage());
        error_log("Stack Trace: " . $e->getTraceAsString());
        
        Flash::set('حدث خطأ غير متوقع أثناء إنشاء التقرير. يرجى المحاولة مرة أخرى أو مراجعة مسؤول النظام.', 'error');
        $this->redirect('/analysis');
    }
}

    /**
     * =================================================================
     * الدوال المساعدة المحسنة لمعالجة التقرير
     * =================================================================
     */

    /**
     * استخراج وتنظيف معاملات التقرير
     */
    private function extractReportFilters() {
        return [
            'level' => $_GET['level'] ?? null,
            'target_id' => isset($_GET['target_id']) ? (int)$_GET['target_id'] : null,
            'scope' => $_GET['scope'] ?? 'overall',
            'template_id' => ($_GET['scope'] === 'by_template' && isset($_GET['template_id'])) ? (int)$_GET['template_id'] : null,
            'position' => $_GET['position'] ?? null,
            'year' => $_GET['year'] ?? null
        ];
    }

    /**
     * التحقق من صحة معاملات التقرير
     */
    private function validateReportParameters($filters) {
    if (!$filters['level'] || $filters['level'] === 'employee') { // إزالة الأقواس الزائدة
        Flash::set('هذا التقرير مخصص للتحليل الجماعي فقط (قسم، فرع، شركة).', 'warning');
        $this->redirect('/analysis');
        return false;
    }
        if ($filters['level'] !== 'company' && !$filters['target_id']) {
            Flash::set('الرجاء تحديد كيان صالح للتحليل (قسم أو فرع).', 'error');
            $this->redirect('/analysis');
            return false;
        }

        return true;
    }

    /**
 * التحقق من وجود بيانات للتحليل
 */
private function hasDataForAnalysis($employeeIds, $filters) {
    if (empty($employeeIds)) {
        return false;
    }
    
    $analysisModel = new AnalysisModel();
    
    // تحقق سريع إذا كان هناك أي تقييمات لهؤلاء الموظفين
    $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
    $sql = "SELECT COUNT(*) as count FROM evaluations 
            WHERE evaluated_employee_id IN ($placeholders) 
            AND status = 'completed'";
    
    $this->db->query($sql);
    foreach ($employeeIds as $index => $id) {
        $this->db->bind($index + 1, $id);
    }
    
    $result = $this->db->fetch();
    return $result && $result->count > 0;
}
    /**
     * توليد بيانات التقرير مع التخزين المؤقت
     */
private function generateReportData($filters) {
    $employeeModel = new Employee();
    $analysisModel = new AnalysisModel();
    
    $employeeIds = $this->getEmployeeIdsForAnalysis($filters, $employeeModel);
    $entityInfo = $this->getEntityInfo($filters, $employeeIds);

    if (empty($employeeIds)) {
        Flash::set('لا يوجد موظفون في هذا النطاق لإجراء التحليل.', 'info');
        $this->redirect('/analysis');
        exit();
    }

    // جلب البيانات مباشرة بدون تحقق مسبق (سيتم التعامل مع البيانات الفارغة في الواجهة)
    try {
        if (method_exists($analysisModel, 'getCachedPerformanceAnalysis')) {
            $analysis = $analysisModel->getCachedPerformanceAnalysis(
                $employeeIds, 
                $filters['template_id'], 
                $filters['position'], 
                $filters['year']
            );
        } else {
            $analysis = $analysisModel->getPerformanceAnalysis(
                $employeeIds, 
                $filters['template_id'], 
                $filters['position'], 
                $filters['year']
            );
        }

        return [
            'entityInfo' => $entityInfo,
            'analysis' => $analysis
        ];
        
    } catch (\Exception $e) {
        error_log("Analysis Data Error: " . $e->getMessage());
        
        // في حالة الخطأ، نعيد بيانات فارغة
        return [
            'entityInfo' => $entityInfo,
            'analysis' => [
                'classified_performance' => [
                    'strong' => [], 
                    'medium' => [], 
                    'weak' => []
                ],
                'stats' => [
                    'average_performance' => 0,
                    'strong_competencies_count' => 0,
                    'weak_competencies_count' => 0,
                    'total_employees_analyzed' => 0
                ]
            ]
        ];
    }
}
    /**
     * الحصول على معرفات الموظفين للتحليل
     */
    private function getEmployeeIdsForAnalysis($filters, $employeeModel) {
        switch ($filters['level']) {
            case 'department':
                return $employeeModel->getEmployeeIdsByDepartment($filters['target_id']);
            case 'branch':
                return $employeeModel->getEmployeeIdsByBranch($filters['target_id']);
            case 'company':
                return $employeeModel->getAllActiveEmployeeIds();
            default:
                return [];
        }
    }

    /**
     * الحصول على معلومات الكيان
     */
    private function getEntityInfo($filters, $employeeIds) {
        switch ($filters['level']) {
            case 'department':
                $department = (new Department())->findByIdWithManager($filters['target_id']);
                if (!$department) {
                    Flash::set('القسم المحدد غير موجود.', 'error');
                    $this->redirect('/analysis');
                    return [];
                }
                return [
                    'type' => 'القسم',
                    'name' => $department->name,
                    'manager' => $department->manager_name ?? 'غير محدد',
                    'employee_count' => count($employeeIds),
                    'icon' => 'fa-sitemap'
                ];
            case 'branch':
                $branch = (new Branch())->findByIdWithManager($filters['target_id']);
                if (!$branch) {
                    Flash::set('الفرع المحدد غير موجود.', 'error');
                    $this->redirect('/analysis');
                    return [];
                }
                return [
                    'type' => 'الفرع',
                    'name' => $branch->name,
                    'manager' => $branch->manager_name ?? 'غير محدد',
                    'employee_count' => count($employeeIds),
                    'icon' => 'fa-building'
                ];
            case 'company':
                return [
                    'type' => 'الشركة ككل',
                    'name' => 'نظام تقييم الموظفين',
                    'manager' => 'الإدارة العليا',
                    'employee_count' => count($employeeIds),
                    'icon' => 'fa-industry'
                ];
            default:
                return [];
        }
    }

    /**
     * عرض التقرير
     */
  /*  private function displayReport($data, $filters) {
        $data['pageTitle'] = 'تحليل أداء: ' . $data['entityInfo']['name'];
        $data['filters'] = $filters;
        
        $this->view('analysis/group_report_view', $data);
    }*/

    /**
     * معالجة أخطاء التقرير
     */
    private function handleReportError($exception) {
        error_log("Analysis Report Error: " . $exception->getMessage());
        Flash::set('حدث خطأ أثناء إنشاء التقرير. يرجى المحاولة مرة أخرى.', 'error');
        $this->redirect('/analysis');
    }

    /**
     * =================================================================
     * التحسين 4: دالة التقرير الفردي المحسنة
     * =================================================================
     */
    public function showIndividualReport($employeeId) {
        Auth::can('analysis.view');

        // قراءة بارامترات إضافية (اختياري)
        $scope = $_GET['scope'] ?? 'overall';
        $templateId = ($scope === 'by_template' && isset($_GET['template_id'])) ? (int)$_GET['template_id'] : null;

        // استدعاء الدالة الخاصة التي تحتوي على منطق التقرير الفردي
        $this->generateIndividualReport((int)$employeeId, $templateId);
    }

    /**
     * دالة خاصة لإنشاء وعرض التقرير الفردي (النسخة المحسنة)
     */
    private function generateIndividualReport($employeeId, $templateId) {
        // --- الخطوة 1: استدعاء النماذج ---
        $employeeModel = new Employee();
        $evaluationModel = new Evaluation();

        // --- الخطوة 2: جلب بيانات الموظف ---
        $employee = $employeeModel->findById($employeeId);
        if (!$employee) {
            Flash::set('الموظف المحدد غير موجود.', 'error');
            $this->redirect('/analysis');
            return;
        }

        // --- الخطوة 3: جمع كل البيانات التحليلية المطلوبة للتقرير ---
        $analysis = $evaluationModel->analyzeSingleEmployeePerformance([$employeeId], $templateId);
        $performanceHistory = $evaluationModel->getEmployeePerformanceHistory($employeeId);
        $performanceStats = $evaluationModel->getEmployeeOverallStats($employeeId);
        $latestEvaluation = !empty($performanceHistory) ? end($performanceHistory) : null;
        $competencyTrendData = $evaluationModel->getCompetencyTrendData($employeeId);
        $historyTableData = $evaluationModel->getDetailedPerformanceTrend($employeeId);

        // --- الخطوة 4: بناء عنوان التقرير ---
        $reportTitle = "تحليل الأداء الفردي: " . htmlspecialchars($employee->full_name);
        if ($templateId) {
            $template = (new Template())->findById($templateId);
            if ($template) {
                $reportTitle .= " (قالب: " . htmlspecialchars($template->template_name) . ")";
            }
        }

        // --- الخطوة 5: تجهيز مصفوفة البيانات النهائية ---
        $data = [
            'pageTitle'        => $reportTitle,
            'reportTitle'      => $reportTitle,
            'employee'         => $employee,
            'analysis'         => $analysis,
            'performanceHistory' => $performanceHistory,
            'performanceStats'   => $performanceStats,
            'latestEvaluation'   => $latestEvaluation,
            'detailedTrend'    => [
                'history_table_data' => $historyTableData,
                'trend_chart_data'   => $competencyTrendData
            ]
        ];

        // --- الخطوة 6: عرض الواجهة ---
        $this->view('analysis/individual_report_view', $data);
    }

    /**
     * =================================================================
     * التحسين 5: دوال التصدير المحسنة مع معالجة الأخطاء
     * =================================================================
     */
    public function export() {
        Auth::can('analysis.view');

        // حل مشكلة الرابط - إزالة بارامتر 'url' المزعج
        $queryParams = $_GET;
        unset($queryParams['url']);

        // إعادة استخدام نفس منطق دالة report()
        $level = $queryParams['level'] ?? null;
        $targetId = isset($queryParams['target_id']) ? (int)$queryParams['target_id'] : null;
        $scope = $queryParams['scope'] ?? 'overall';
        $templateId = ($scope === 'by_template' && isset($queryParams['template_id'])) ? (int)$queryParams['template_id'] : null;

        if (!$level || ($level !== 'company' && !$targetId)) {
            exit('Invalid parameters for export.');
        }

        $employeeModel = new Employee();
        $employeeIds = [];
        $reportTitle = '';

        switch ($level) {
            case 'employee':
                $employeeIds = [$targetId];
                $employee = $employeeModel->findById($targetId);
                $reportTitle = "تحليل أداء الموظف: " . htmlspecialchars($employee->full_name);
                break;
            case 'department':
                $employeeIds = $employeeModel->getEmployeeIdsByDepartment($targetId);
                $department = (new Department())->findById($targetId);
                $reportTitle = "تحليل أداء القسم: " . htmlspecialchars($department->name);
                break;
            case 'branch':
                $employeeIds = $employeeModel->getEmployeeIdsByBranch($targetId);
                $branch = (new Branch())->findById($targetId);
                $reportTitle = "تحليل أداء الفرع: " . htmlspecialchars($branch->name);
                break;
            case 'company':
                $employeeIds = $employeeModel->getAllActiveEmployeeIds();
                $reportTitle = "تحليل أداء الشركة ككل";
                break;
        }

        if ($templateId) {
            $template = (new Template())->findById($templateId);
            if ($template) {
                $reportTitle .= " (قالب: " . htmlspecialchars($template->template_name) . ")";
            }
        }

        $evaluationModel = new Evaluation();
        $analysis = $evaluationModel->getPerformanceAnalysis($employeeIds, $templateId);

        $data = [
            'reportTitle' => $reportTitle,
            'analysis' => $analysis,
            'companyName' => 'اسم شركتك هنا',
            'reportDate' => date('Y-m-d')
        ];

        ob_start();
        view('analysis/report_template_pdf', $data);
        $html = ob_get_clean();

        // استخدام Dompdf أو TCPDF حسب المتاح
        $this->renderPdfWithDompdf($html, $level);
    }

    /**
     * Export evaluations report to Excel
     */
    public function exportExcel(): void
    {
        Auth::can('analysis.view');

        $level = $_GET['level'] ?? null;
        $targetId = isset($_GET['target_id']) ? (int)$_GET['target_id'] : null;

        $evalModel = new Evaluation();
        $evaluations = $evalModel->getAllForApi();

        \App\Services\ExportService::exportEvaluations($evaluations, 'تقرير التقييمات');
    }

    /**
     * Export employees list to Excel
     */
    public function exportExcelEmployees(): void
    {
        Auth::can('analysis.view');

        $empModel = new Employee();
        $employees = $empModel->getAll();

        \App\Services\ExportService::exportEmployees($employees, 'قائمة الموظفين');
    }

    /**
     * =================================================================
     * التحسين 6: دالة التصدير المحسنة بالمخططات
     * =================================================================
     */
    public function exportPdfWithCharts() {
        set_time_limit(300);
        Auth::can('analysis.view');

        try {
            $exportData = $this->prepareExportData();
            $this->generatePdfExport($exportData);
            
        } catch (\Exception $e) {
            $this->handleExportError($e);
        }
    }

    /**
     * تجهيز بيانات التصدير
     */
    private function prepareExportData() {
        $employeeId = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;
        if (!$employeeId) {
            throw new \Exception('لم يتم تحديد الموظف.');
        }

        $employeeModel = new Employee();
        $evaluationModel = new Evaluation();
        
        $employee = $employeeModel->findById($employeeId);
        if (!$employee) {
            throw new \Exception('الموظف غير موجود.');
        }

        return [
            'employee' => $employee,
            'analysis' => $evaluationModel->analyzeSingleEmployeePerformance([$employeeId]),
            'performanceStats' => $evaluationModel->getEmployeeOverallStats($employeeId),
            'latestEvaluation' => $this->getLatestEvaluation($employeeId, $evaluationModel)
        ];
    }

    /**
     * الحصول على آخر تقييم
     */
    private function getLatestEvaluation($employeeId, $evaluationModel) {
        $history = $evaluationModel->getEmployeePerformanceHistory($employeeId);
        return !empty($history) ? end($history) : null;
    }

    /**
     * توليد تصدير PDF
     */
    private function generatePdfExport($data) {
        // استخدام التخزين المؤقت للصور
        $chartPaths = $this->generateChartsWithCache($data);
        $data['chartPaths'] = $chartPaths;

        ob_start();
        $this->view('analysis/report_pdf_template', $data);
        $html = ob_get_clean();

        $this->renderPdfWithMpdf($html, $data['employee']);
        
        // تنظيف الملفات المؤقتة
        $this->cleanupTempFiles($chartPaths);
    }

    /**
     * توليد المخططات مع التخزين المؤقت
     */
    private function generateChartsWithCache($data) {
        $cacheKey = 'charts_' . md5(serialize($data['analysis']));
        $cacheDir = APP_ROOT . '/public/uploads/temp_charts/';
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $chartPaths = [];
        // تنفيذ توليد المخططات مع التخزين المؤقت
        // ... (الكود الحالي الخاص بتوليد المخططات)

        return $chartPaths;
    }

    /**
     * =================================================================
     * التحسين 7: دوال الطباعة المحسنة
     * =================================================================
     */
  // في AnalysisController - تحديث دالة printView
public function printView() {
    Auth::can('analysis.view');

    $level = $_GET['level'] ?? 'employee';
    $targetId = isset($_GET['target_id']) ? (int)$_GET['target_id'] : null;

    if ($level !== 'employee' || !$targetId) {
        Flash::set('صفحة الطباعة هذه مخصصة لتقارير الموظفين الفردية فقط.', 'error');
        $this->redirect('/analysis');
        return;
    }

    $employeeModel = new Employee();
    $evaluationModel = new Evaluation();
    
    $employee = $employeeModel->findById($targetId);
    if (!$employee) {
        Flash::set('الموظف المحدد غير موجود.', 'error');
        $this->redirect('/analysis');
        return;
    }
    
    // جلب البيانات المحسنة مع مخطط الأداء
    $analysis = $evaluationModel->analyzeSingleEmployeePerformance([$targetId]);
    $detailedTrend = ['history_table_data' => $evaluationModel->getDetailedPerformanceTrend($targetId)];
    $performanceHistory = $evaluationModel->getEmployeePerformanceHistory($targetId);
    $latestEvaluation = !empty($performanceHistory) ? end($performanceHistory) : null;
    $performanceStats = $evaluationModel->getEmployeeOverallStats($targetId);

    $reportTitle = "تقرير الأداء الفردي: " . htmlspecialchars($employee->full_name);

    $data = [
        'pageTitle'        => 'طباعة: ' . $reportTitle,
        'reportTitle'      => $reportTitle,
        'employee'         => $employee,
        'analysis'         => $analysis, // يحتوي على performance_chart_data
        'detailedTrend'    => $detailedTrend,
        'performanceHistory' => $performanceHistory,
        'latestEvaluation' => $latestEvaluation,
        'performanceStats' => $performanceStats,
    ];

    $this->view('analysis/print_view', $data);
}

    /**
     * =================================================================
     * التحسين 8: دالة الطباعة المتقدمة مع التوصيات الذكية
     * =================================================================
     */
  // في App\Controllers\AnalysisController - تحديث دالة printViewLevel

// في App\Controllers\AnalysisController - تحديث دالة printViewLevel

public function printViewLevel() {
    Auth::can('analysis.view');
    
    try {
        $level = $_GET['level'] ?? 'company';
        $targetId = isset($_GET['target_id']) ? (int)$_GET['target_id'] : null;

        $analysisModel = new AnalysisModel();
        
        // جلب البيانات الإحصائية المحسنة مع البيانات الحقيقية
        $branchStats = $analysisModel->getBranchStatsWithRealData();
        $overallAverages = $analysisModel->getOverallAverages();
        
        if ($level === 'branch' && $targetId) {
            $departmentStats = $analysisModel->getDepartmentStatsByBranch($targetId);
            $branchCompetencyStats = $analysisModel->getBranchCompetencyStats($targetId);
        } else {
            $departmentStats = [];
            $branchCompetencyStats = null;
        }

        // البيانات الهرمية (الحالية)
        $employeeModel = new Employee();
        $employeeIds = $this->getEmployeeIdsForScope($level, $targetId);
        $entityInfo = $this->getEntityInfoForPrint($level, $targetId, $employeeIds);
        
        $analysis = $analysisModel->getPerformanceAnalysis($employeeIds);
        $hierarchicalData = $this->generateHierarchicalData([
            'level' => $level,
            'target_id' => $targetId
        ]);

        $data = [
            'entityInfo' => $entityInfo,
            'analysis' => $analysis,
            'hierarchicalData' => $hierarchicalData,
            'branchStats' => $branchStats,
            'departmentStats' => $departmentStats,
            'branchCompetencyStats' => $branchCompetencyStats,
            'overallAverages' => $overallAverages,
            'filters' => ['level' => $level, 'target_id' => $targetId],
            'pageTitle' => 'طباعة تقرير ' . ($entityInfo['type'] ?? 'غير محدد'),
            'userName' => $_SESSION['user_name'] ?? 'مستخدم النظام'
        ];

        $this->view('analysis/print-view-level', $data);

    } catch (Exception $e) {
        error_log("Error in printViewLevel: " . $e->getMessage());
        echo "حدث خطأ في تحميل صفحة الطباعة: " . $e->getMessage();
    }
}
    /**
     * =================================================================
     * التحسين 9: نظام التوصيات الذكية المحسن
     * =================================================================
     */
    private function generateSmartRecommendations(array $analysis): array {
        $recommendations = [];
        $weakCompetencies = $analysis['classified_performance']['weak'] ?? [];

        // فرز الكفاءات الضعيفة من الأقل أداءً إلى الأعلى
        usort($weakCompetencies, fn($a, $b) => $a['average_score'] <=> $b['average_score']);

        if (empty($weakCompetencies)) {
            $recommendations[] = [
                'title' => 'الحفاظ على الأداء المتميز',
                'description' => 'لم يتم تحديد فجوات أداء حرجة. يوصى بالاستمرار في مراقبة الأداء العالي وتكريم الفرق المتميزة للحفاظ على هذا المستوى من التميز.'
            ];
            return $recommendations;
        }

        // توصية عامة
        $recommendations[] = [
            'title' => 'التركيز على مجالات التحسين ذات الأولوية',
            'description' => 'يوصى بتركيز جهود التطوير والتدريب على الكفاءات التي يقل متوسط أدائها عن 50%، حيث أن تحسينها سيحقق الأثر الأكبر على الأداء العام.'
        ];

        // توصيات محددة لأضعف 3 كفاءات
        $priorityItems = array_slice($weakCompetencies, 0, 3);
        foreach ($priorityItems as $item) {
            $recommendations[] = [
                'title' => 'إجراء مقترح لكفاءة: ' . htmlspecialchars($item['section_name']),
                'description' => "نظرًا لأن متوسط الأداء في هذه الكفاءة هو ({$item['average_score']}%)، يُقترح تصميم ورشة عمل مركزة أو برنامج تدريبي موجه لمعالجة جوانب الضعف المحددة."
            ];
        }

        return $recommendations;
    }

    /**
     * =================================================================
     * الدوال المساعدة الإضافية المحسنة
     * =================================================================
     */

    /**
     * الحصول على معرفات الموظفين للنطاق المحدد
     */
    private function getEmployeeIdsForScope(string $level, ?int $targetId): array {
        $employeeModel = new Employee();
        $employeeIds = [];

        switch ($level) {
            case 'department':
                if ($targetId) {
                    $employeeIds = $employeeModel->getEmployeeIdsByDepartment($targetId);
                }
                break;
            case 'branch':
                if ($targetId) {
                    $employeeIds = $employeeModel->getEmployeeIdsByBranch($targetId);
                }
                break;
            case 'company':
            case 'organization':
                $employeeIds = $employeeModel->getAllActiveEmployeeIds();
                break;
        }
        return $employeeIds;
    }

    /**
     * الحصول على معلومات الكيان للطباعة
     */
    private function getEntityInfoForPrint(string $level, ?int $targetId, array $employeeIds): array {
        $entityInfo = [];

        switch ($level) {
            case 'department':
                $department = (new Department())->findByIdWithManager($targetId);
                if ($department) {
                    $entityInfo = [
                        'type' => 'القسم',
                        'name' => $department->name,
                        'manager' => $department->manager_name ?? 'غير محدد',
                        'employee_count' => count($employeeIds),
                        'icon' => 'fa-sitemap'
                    ];
                }
                break;
            case 'branch':
                $branch = (new Branch())->findByIdWithManager($targetId);
                if ($branch) {
                    $entityInfo = [
                        'type' => 'الفرع',
                        'name' => $branch->name,
                        'manager' => $branch->manager_name ?? 'غير محدد',
                        'employee_count' => count($employeeIds),
                        'icon' => 'fa-building'
                    ];
                }
                break;
            case 'company':
            case 'organization':
                $entityInfo = [
                    'type' => 'الشركة ككل',
                    'name' => 'نظام تقييم الموظفين',
                    'manager' => 'الإدارة العليا',
                    'employee_count' => count($employeeIds),
                    'icon' => 'fa-industry'
                ];
                break;
        }
        return $entityInfo;
    }

    /**
     * =================================================================
     * دوال PDF المساعدة المحسنة
     * =================================================================
     */

    /**
     * عرض PDF باستخدام mPDF
     */
    private function renderPdfWithMpdf($html, $employee) {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8', 'format' => 'A4',
            'tempDir' => APP_ROOT . '/app/storage/temp',
            'fontDir' => array_merge($fontDirs, [APP_ROOT . '/public/fonts']),
            'fontdata' => $fontData + ['cairo' => ['R' => 'Cairo-Regular.ttf', 'B' => 'Cairo-Bold.ttf']],
            'default_font' => 'cairo'
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        
        $mpdf->WriteHTML($html);
        $filename = 'report-' . str_replace(' ', '_', $employee->emp_code) . '.pdf';
        $mpdf->Output($filename, 'I');
    }

    /**
     * معالجة أخطاء التصدير
     */
    private function handleExportError($exception) {
        error_log("PDF Export Error: " . $exception->getMessage());
        
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        echo "<script>
            alert('حدث خطأ في تصدير الملف: " . addslashes($exception->getMessage()) . "');
            window.history.back();
        </script>";
        exit;
    }

    /**
     * تنظيف الملفات المؤقتة
     */
    private function cleanupTempFiles($chartPaths) {
        foreach ($chartPaths as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * =================================================================
     * دوال PDF القديمة المحفوظة للتوافق
     * =================================================================
     */

      public function printProfessional() {
        // زيادة الوقت المسموح به (احتياطي)
        set_time_limit(300);

        // 1. التحقق من صلاحية المستخدم
        Auth::can('analysis.view');

        // 2. قراءة وتأمين المدخلات من رابط الـ URL
        $level = $_GET['level'] ?? null;
        $targetId = isset($_GET['target_id']) ? (int)$_GET['target_id'] : null;
        $scope = $_GET['scope'] ?? 'overall';
        $templateId = ($scope === 'by_template' && isset($_GET['template_id'])) ? (int)$_GET['template_id'] : null;

        // 3. التحقق من وجود البارامترات الأساسية
        if (!$level || ($level !== 'company' && !$targetId)) {
            Flash::set('الرجاء تحديد كل الخيارات المطلوبة لإنشاء التقرير.', 'error');
            $this->redirect('/analysis');
            return;
        }

        // 4. تحديد عنوان التقرير
        $reportTitle = '';
        switch ($level) {
            case 'employee':
                $employee = (new Employee())->findById($targetId);
                if ($employee) {
                    $reportTitle = "تقرير أداء الموظف: " . htmlspecialchars($employee->full_name);
                }
                break;
            case 'department':
                $department = (new Department())->findById($targetId);
                if ($department) {
                    $reportTitle = "تقرير أداء القسم: " . htmlspecialchars($department->name);
                }
                break;
            case 'branch':
                $branch = (new Branch())->findById($targetId);
                if ($branch) {
                    $reportTitle = "تقرير أداء الفرع: " . htmlspecialchars($branch->name);
                }
                break;
            case 'company':
                $reportTitle = "تقرير أداء الشركة";
                break;
        }

        // إضافة اسم القالب إلى عنوان التقرير إذا كان التحليل مخصصاً
        if ($templateId) {
            $template = (new Template())->findById($templateId);
            if ($template) {
                $reportTitle .= " (قالب: " . htmlspecialchars($template->template_name) . ")";
            }
        }

        // 5. تحديد قائمة الموظفين المستهدفين
        $employeeModel = new Employee();
        $employeeIds = [];

        switch ($level) {
            case 'employee':
                $employeeIds = [$targetId];
                break;
            case 'department':
                $employeeIds = $employeeModel->getEmployeeIdsByDepartment($targetId);
                break;
            case 'branch':
                $employeeIds = $employeeModel->getEmployeeIdsByBranch($targetId);
                break;
            case 'company':
                $employeeIds = $employeeModel->getAllActiveEmployeeIds();
                break;
        }

        // 6. استدعاء المحرك التحليلي
        $evaluationModel = new Evaluation();
        $analysis = $evaluationModel->getPerformanceAnalysis($employeeIds, $templateId);

        // 7. تجهيز البيانات النهائية
        $data = [
            'reportTitle' => $reportTitle,
            'analysis' => $analysis,
            'companyName' => 'نظام تقييم الموظفين',
            'reportDate' => date('Y-m-d')
        ];

        // 8. تحويل البيانات إلى PDF
        ob_start();
        $this->view('analysis/report_template_pdf_new', $data);
        $html = ob_get_clean();

        // 9. إعداد TCPDF
        // إنشاء كائن TCPDF
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // تعيين معلومات المستند
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('نظام تقييم الموظفين');
        $pdf->SetTitle('تقرير أداء الموظفين');
        $pdf->SetSubject('تقرير أداء');
        $pdf->SetKeywords('تقييم, أداء, موظفين');
        
        // تعيين الهوامش
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // تعيين الخط الافتراضي
        // تعيين مسار مجلد الخطوط
        define('K_PATH_FONTS', APP_ROOT . '/app/Libraries/TCPDF/fonts/');
        $pdf->SetFont('tahoma', '', 12);
        
        // إضافة صفحة
        $pdf->AddPage();
        
        // 10. معالجة النص العربي
        try {
            $arabic = new \KhaledAlshamaa\Arabic\I18N();
            $p = $arabic->arIdentify($html);
            for ($i = count($p) - 1; $i >= 0; $i -= 2) {
                $utf8ar = $arabic->utf8Glyphs(substr($html, $p[$i - 1], $p[$i] - $p[$i - 1]));
                $html = substr_replace($html, $utf8ar, $p[$i - 1], $p[$i] - $p[$i - 1]);
            }
        } catch (Exception $e) {
            // في حالة عدم وجود المكتبة، نستخدم طريقة بديلة
            // استبدال الأحرف العربية ببدائلها المباشرة
            $html = str_replace([
                'أ', 'إ', 'آ', 'ة', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي', 'ى', 'ء', 'ؤ', 'ئ', 'ّ', 'ـ', 'ْ', 'ُ', 'ِ', 'ً', 'ٍ', 'ٌ', 'ْ'
            ], [
                'ا', 'ا', 'ا', 'ه', 'ت', 'ت', 'ج', 'ح', 'خ', 'د', 'د', 'ر', 'ز', 'س', 'س', 'ص', 'ص', 'ط', 'ط', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي', 'ي', 'ء', 'و', 'ي', '', '', '', '', '', '', ''
            ], $html);
        }
        
        // 11. كتابة محتوى HTML
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // 12. عرض الـ PDF
        $filename = 'performance_report_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I');
        exit();
    }

      /**
     * =================================================================
     * دالة بديلة مبسطة للتصدير (إذا استمرت المشاكل)
     * =================================================================
     */
    public function exportSimplePdf() {
        Auth::can('analysis.view');

        $targetId = isset($_GET['target_id']) ? (int)$_GET['target_id'] : null;
        if (!$targetId) {
            Flash::set('لم يتم تحديد موظف.', 'error');
            $this->redirect('/analysis');
            return;
        }

        // جلب البيانات مباشرة بدلاً من استدعاء دالة report
        $employeeModel = new Employee();
        $evaluationModel = new Evaluation();
        
        $employee = $employeeModel->findById($targetId);
        if (!$employee) {
            Flash::set('الموظف المحدد غير موجود.', 'error');
            $this->redirect('/analysis');
            return;
        }

        $analysis = $evaluationModel->analyzeSingleEmployeePerformance([$targetId]);
        $reportTitle = "تقرير الأداء الفردي: " . htmlspecialchars($employee->full_name);

        $data = [
            'reportTitle' => $reportTitle,
            'employee' => $employee,
            'analysis' => $analysis
        ];

        // تحميل مكتبة TCPDF
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        $pdf->SetCreator('نظام التقييم');
        $pdf->SetAuthor('نظام التقييم');
        $pdf->SetTitle($reportTitle);
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->SetFont('dejavusans', '', 10);

        $pdf->AddPage();
        $pdf->setRTL(true);

        // محتوى بسيط
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 10, $reportTitle, 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('dejavusans', '', 12);
        $pdf->MultiCell(0, 8, 'تم إنشاء التقرير بنجاح. لتصدير النسخة الكاملة مع المخططات، استخدم زر "تصدير PDF احترافي".', 0, 'R');
        
        $filename = 'تقرير_مبسط_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I');
        exit;
    }


    /**
     * =================================================================
     * دالة مساعدة: إضافة ترويسة مخصصة للـ PDF
     * =================================================================
     */  private function addCustomHeader($pdf, $employee, $title = '') {
        $pdf->SetY(10);
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 10, 'تقرير الأداء الفردي', 0, 1, 'C');
        
        if (!empty($title)) {
            $pdf->SetFont('dejavusans', 'B', 14);
            $pdf->Cell(0, 8, $title, 0, 1, 'C');
        }
        
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 6, 'تاريخ التقرير: ' . date('Y-m-d'), 0, 1, 'C');
        $pdf->Cell(0, 6, 'الموظف: ' . $employee->full_name . ' (' . $employee->emp_code . ')', 0, 1, 'C');
        
        $pdf->Line(10, $pdf->GetY() + 2, 200, $pdf->GetY() + 2);
        $pdf->Ln(10);
    }

/**
     * =================================================================
     * دالة مساعدة: إضافة تذييل للـ PDF
     * =================================================================
     */
    private function addCustomFooter($pdf) {
        $pdf->SetY(-15);
        $pdf->SetFont('dejavusans', 'I', 8);
        $pdf->Cell(0, 10, 'صفحة ' . $pdf->getAliasNumPage() . ' من ' . $pdf->getAliasNbPages(), 0, 0, 'C');
    }
      private function fixArabicText($text) {
        // استبدال الأحرف العربية ببدائلها المباشرة
        $replacements = [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ة' => 'ه',
            'ت' => 'ت', 'ث' => 'ت', 'ج' => 'ج', 'ح' => 'ح',
            'خ' => 'خ', 'د' => 'د', 'ذ' => 'د', 'ر' => 'ر',
            'ز' => 'ز', 'س' => 'س', 'ش' => 'س', 'ص' => 'ص',
            'ض' => 'ص', 'ط' => 'ط', 'ظ' => 'ط', 'ع' => 'ع',
            'غ' => 'غ', 'ف' => 'ف', 'ق' => 'ق', 'ك' => 'ك',
            'ل' => 'ل', 'م' => 'م', 'ن' => 'ن', 'ه' => 'ه',
            'و' => 'و', 'ي' => 'ي', 'ى' => 'ي', 'ء' => 'ء'
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    
/**
 * توليد البيانات الهرمية للعرض الجديد
 */
private function generateHierarchicalData($filters) {
    $templateId = $filters['template_id'] ?? null;
    $yearFilter = $filters['year'] ?? null;
    
    $analysisModel = new AnalysisModel();
    
    try {
        switch ($filters['level']) {
            case 'company':
                return $analysisModel->getCompanyHierarchy($templateId, $yearFilter);
                
            case 'branch':
                $branchId = $filters['target_id'];
                return [
                    'type' => 'branch',
                    'average_performance' => $analysisModel->calculateBranchAverage($branchId, $templateId, $yearFilter),
                    'entities' => $analysisModel->getBranchHierarchy($branchId, $templateId, $yearFilter)
                ];
                
            case 'department':
                $departmentId = $filters['target_id'];
                return [
                    'type' => 'department', 
                    'average_performance' => $analysisModel->calculateDepartmentAverage($departmentId, $templateId, $yearFilter),
                    'entities' => $analysisModel->getDepartmentEmployees($departmentId, $templateId, $yearFilter)
                ];
                
            default:
                return [
                    'type' => $filters['level'],
                    'average_performance' => 0,
                    'entities' => []
                ];
        }
    } catch (\Exception $e) {
        error_log("Hierarchical Data Generation Error: " . $e->getMessage());
        return [
            'type' => $filters['level'],
            'average_performance' => 0,
            'entities' => []
        ];
    }
}

/**
 * تحديث دالة displayReport لتمرير البيانات الجديدة
 */
/**
 * عرض التقرير
 */
private function displayReport($data, $filters) {
    $data['pageTitle'] = 'تحليل أداء: ' . $data['entityInfo']['name'];
    $data['filters'] = $filters;
    
    // جلب البيانات الهرمية للتحليل الجديد
    try {
        $hierarchicalData = $this->generateHierarchicalData($filters);
        $data['hierarchicalData'] = $hierarchicalData;
    } catch (\Exception $e) {
        // في حالة حدوث خطأ، نمرر بيانات فارغة
        error_log("Hierarchical Data Error: " . $e->getMessage());
        $data['hierarchicalData'] = [
            'type' => $filters['level'],
            'average_performance' => 0,
            'entities' => []
        ];
    }
    
    $this->view('analysis/group_report_view', $data);
}
/**
 * تصنيف كفاءات الموظف حسب المعايير الجديدة
 */
private function classifyEmployeeCompetencies($competencies) {
    $classified = ['strong' => [], 'medium' => [], 'weak' => []];
    $totalScore = 0;
    $count = 0;

    foreach ($competencies as $comp) {
        if ($comp->score === null) {
            continue;
        }
        
        $score = (float)$comp->score;
        $totalScore += $score;
        $count++;

        $compData = [
            'section_name' => $comp->section_name ?? 'غير معروف',
            'criterion_name' => $comp->criterion_name ?? '',
            'template_name' => $comp->template_name ?? '',
            'score' => $score,
            'evaluation_date' => $comp->evaluation_date ?? ''
        ];

        // التصنيف حسب النطاقات الجديدة
        if ($score >= 80) {
            $classified['strong'][] = $compData;
        } elseif ($score >= 60) {
            $classified['medium'][] = $compData;
        } else {
            $classified['weak'][] = $compData;
        }
    }

    $average = $count > 0 ? round($totalScore / $count, 2) : 0;

    return [
        'competencies' => $classified,
        'average_performance' => $average,
        'total_competencies' => $count
    ];
}
}