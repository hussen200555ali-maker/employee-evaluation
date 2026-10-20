<?php
// المسار: app/Controllers/EvaluationController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Audit; // استيراد فئة التدقيق
use App\Models\Evaluation; // سنقوم بإنشاء هذا المودل
use App\Models\Employee;   // نحتاجه لجلب بيانات الموظف
use App\Models\Template; // <-- ▼▼▼ هذا هو السطر الصحيح ▼▼▼

class EvaluationController extends Controller {

    /**
     * =================================================================
     * يعرض الصفحة الرئيسية لوحدة التقييمات (قائمة الموظفين المجمعة)
     * =================================================================
     */
    public function index() {
        // صلاحية لعرض هذه الصفحة
        Auth::enforce('reports.view.all'); 

        // استدعاء مودل الموظفين لأننا نعرض قائمة موظفين
        $employeeModel = new Employee();

        // جلب الفلاتر من الرابط (GET request)
        $filters = [
            'search' => $_GET['search'] ?? '',
            'branch_id' => $_GET['branch_id'] ?? null,
            'department_id' => $_GET['department_id'] ?? null
        ];

        // الترقيم
        $perPage = 15;
        $currentPage = max(1, (int)($_GET['page'] ?? 1));
        $totalCount = $employeeModel->getEmployeesWithEvaluationSummaryCount($filters);
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = min($currentPage, $totalPages);

        // جلب قائمة الموظفين مع ملخص تقييماتهم
        $employees = $employeeModel->getEmployeesWithEvaluationSummary($filters, $currentPage, $perPage);

        // جلب بيانات الفروع والأقسام لملء قوائم الفلترة
        $branchModel = new \App\Models\Branch();
        $branches = $branchModel->getAll();
        
        $departmentModel = new \App\Models\Department();
        $departments = $departmentModel->getAll();

        $data = [
            'pageTitle' => 'ملفات أداء الموظفين',
            'employees' => $employees,
            'branches' => $branches,
            'departments' => $departments,
            'filters' => $filters,
            'pagination' => [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalCount' => $totalCount,
                'perPage' => $perPage
            ]
        ];

        $this->view('evaluations/index', $data);
    }
    /**
     * يعرض صفحة "بدء تقييم جديد".
     */
    public function create() {
        Auth::enforce('evaluations.create');
        
        $data = [
            'pageTitle' => 'بدء تقييم موظف جديد'
        ];

        $this->view('evaluations/create', $data);
    }

    /**
     * =================================================================
     * دالة جديدة: تخزين البيانات الأساسية للتقييم وبدء العملية
     * =================================================================
     */
/*    public function store() {
        // 1. التحقق من الصلاحية وأن الطلب من نوع POST
        Auth::enforce('evaluations.create');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/evaluations/create');
        }

        // 2. تجميع البيانات من النموذج
        $data = [
            'template_id' => (int)$_POST['template_id'],
            'evaluated_employee_id' => (int)$_POST['evaluated_employee_id'],
            'evaluator_employee_id' => (int)$_POST['evaluator_employee_id'],
            'evaluation_date' => $_POST['evaluation_date'],
            'created_by_user_id' => Auth::id()
        ];

        // 3. جلب "النسخة التاريخية" لبيانات الموظف المُقيَّم
        $employeeModel = new Employee();
        $employee = $employeeModel->getDetailsForSnapshot($data['evaluated_employee_id']);

        if (!$employee) {
            Flash::set('لم يتم العثور على الموظف المُراد تقييمه.', 'error');
            $this->redirect('/evaluations/create');
        }

        // إضافة البيانات التاريخية إلى مصفوفة الحفظ
        $data['employee_name'] = $employee->full_name;
        $data['employee_code'] = $employee->emp_code;
        $data['branch_name'] = $employee->branch_name;
        $data['department_name'] = $employee->department_name;
        $data['position_name'] = $employee->position_title;

        // 4. إنشاء سجل التقييم الجديد في قاعدة البيانات
        $evaluationModel = new Evaluation(); // سنقوم بإنشاء هذا المودل
        $newEvaluationId = $evaluationModel->create($data);

        if ($newEvaluationId) {
            // 5. تسجيل العملية في سجل التدقيق (Audit Log)
            Audit::log(
                'EVALUATION_STARTED',   // اسم الإجراء
                $newEvaluationId,       // معرف التقييم الجديد
                'evaluations',          // اسم الجدول
                [ // تفاصيل مهمة عن العملية
                    'evaluated' => $data['employee_name'] . " ({$data['employee_code']})",
                    'evaluator_id' => $data['evaluator_employee_id'],
                    'template_id' => $data['template_id']
                ]
            );

            // 6. إعادة توجيه المستخدم إلى صفحة تعبئة الاستمارة
            // سنقوم بإنشاء هذه الصفحة لاحقًا
            $this->redirect("/evaluations/{$newEvaluationId}/fill");

        } else {
            Flash::set('حدث خطأ غير متوقع أثناء بدء التقييم. يرجى المحاولة مرة أخرى.', 'error');
            $this->redirect('/evaluations/create');
        }
    }*/
         /**
     * =================================================================
     * دالة تخزين البيانات الأساسية للتقييم (النسخة النهائية مع التحقق من المنصب)
     * =================================================================
     */
    public function store() {
        // 1. التحقق من الصلاحية والطلب
        Auth::enforce('evaluations.create');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/evaluations/create');
        }

        // 2. تجميع البيانات الأساسية
        $evaluatedEmployeeId = (int)$_POST['evaluated_employee_id'];
        
        // 3. جلب تفاصيل الموظف للتحقق
        $employeeModel = new Employee();
        $employee = $employeeModel->getDetailsForSnapshot($evaluatedEmployeeId);

        if (!$employee) {
            Flash::set('لم يتم العثور على الموظف المُراد تقييمه.', 'error');
            $this->redirect('/evaluations/create');
            return;
        }

        // ===> التحقق الحاسم هنا <===
        // إذا كان حقل المنصب (position_title) فارغاً، أوقف العملية وأظهر رسالة خطأ.
        if (empty($employee->position_title)) {
            $employeeProfileUrl = APP_URL . "/employees/{$evaluatedEmployeeId}/edit";
            $errorMessage = "لا يمكن بدء التقييم. الموظف '{$employee->full_name}' ليس لديه منصب وظيفي محدد.   
";
            $errorMessage .= "يرجى <a href='{$employeeProfileUrl}' target='_blank' class='alert-link'>تحديث ملف الموظف</a> أولاً وتحديد منصبه.";
            
            Flash::set($errorMessage, 'error');
            $this->redirect('/evaluations/create');
            return;
        }

        // 4. إذا كان كل شيء سليماً، قم بتجميع بيانات المقيّم (داخلي أو خارجي)
        $isExternal = isset($_POST['is_external_evaluator']) && $_POST['is_external_evaluator'] == '1';
        $evaluatorEmployeeId = null;
        $evaluatorName = trim($_POST['evaluator_name'] ?? '');
        $evaluatorTitle = trim($_POST['evaluator_title'] ?? '');
        if ($isExternal) {
            if ($evaluatorName === '' && $evaluatorTitle === '') {
                Flash::set('يجب إدخال مسؤول التقييم أو الصفة على الأقل للمقيّم الخارجي.', 'danger');
                $this->redirect('/evaluations/create');
                return;
            }
            if (preg_match('/^[=+@-]/', ltrim($evaluatorName)) || preg_match('/^[=+@-]/', ltrim($evaluatorTitle))) {
                Flash::set('اسم أو صفة المقيّم لا يمكن أن تبدأ بصيغة Excel.', 'danger');
                $this->redirect('/evaluations/create');
                return;
            }
        } else {
            $evaluatorEmployeeId = !empty($_POST['evaluator_employee_id']) ? (int)$_POST['evaluator_employee_id'] : null;
            if ($evaluatorEmployeeId) {
                $evalEmp = (new Employee())->findById($evaluatorEmployeeId);
                if ($evalEmp) { $evaluatorName = $evalEmp->full_name; $evaluatorTitle = $evalEmp->position_title ?? ''; }
            }
            if (!$evaluatorEmployeeId && $evaluatorName === '' && $evaluatorTitle === '') {
                Flash::set('يجب اختيار مقيّم داخلي أو إدخال بيانات مقيّم خارجي.', 'danger');
                $this->redirect('/evaluations/create');
                return;
            }
        }
        $data = [
            'template_id' => (int)$_POST['template_id'],
            'evaluated_employee_id' => $evaluatedEmployeeId,
            'evaluator_employee_id' => $evaluatorEmployeeId,
            'evaluator_name' => $evaluatorName,
            'evaluator_title' => $evaluatorTitle,
            'evaluation_date' => $_POST['evaluation_date'],
            'created_by_user_id' => Auth::id(),
            'employee_name' => $employee->full_name,
            'employee_code' => $employee->emp_code,
            'nickname' => $employee->nickname ?? null,
            'branch_name' => $employee->branch_name,
            'department_name' => $employee->department_name,
            'position_name' => $employee->position_title
        ];

        // 5. إنشاء سجل التقييم
        $evaluationModel = new Evaluation();
        $newEvaluationId = $evaluationModel->create($data);

        if ($newEvaluationId) {
            Audit::log('EVALUATION_STARTED', $newEvaluationId, 'evaluations', [
                'evaluated' => $data['employee_name'],
                'template_id' => $data['template_id']
            ]);
            Flash::set('تم بدء عملية التقييم بنجاح!', 'success');
            $this->redirect("/evaluations/{$newEvaluationId}/fill");
        } else {
            Flash::set('حدث خطأ غير متوقع أثناء بدء التقييم.', 'error');
            $this->redirect('/evaluations/create');
        }
    }
     /**
     * =================================================================
     * دالة جديدة: عرض صفحة تعبئة استمارة التقييم
     * =================================================================
     * @param int $id - معرف التقييم (يأتي من الرابط)
     */
    /**
     * =================================================================
     * يعرض صفحة تعبئة استمارة التقييم (النسخة المحدثة للتعديل)
     * =================================================================
     * @param int $id - معرف التقييم
     */
    public function fill($id) {
        Auth::enforce('evaluations.fill');

        $evaluationModel = new Evaluation();
        $evaluation = $evaluationModel->findById($id);

        if (!$evaluation) {
            Flash::set('التقييم المطلوب غير موجود.', 'error');
            $this->redirect('/evaluations');
            return;
        }
        if (!$this->canEditAssignedEvaluation($evaluation)) {
            Flash::set('هذا التقييم غير مسند إلى حسابك.', 'error');
            $this->redirect('/dashboard');
            return;
        }

        $savedAnswers = [];
        if (in_array($evaluation->status, ['draft','pending_review','approved','completed'])) {
            $savedAnswers = $evaluationModel->getEvaluationAnswers($id);
        }
        // **************************

        $employeeModel = new Employee();
        $evaluator = $employeeModel->getDetailsForSnapshot($evaluation->evaluator_employee_id);

        $templateModel = new Template();
        $template = $templateModel->getTemplateWithStructure($evaluation->template_id);

        if (!$template) {
            Flash::set('قالب التقييم المرتبط بهذا التقييم غير موجود أو تم حذفه.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        $data = [
            'pageTitle' => 'تعبئة استمارة التقييم: ' . htmlspecialchars($evaluation->employee_name),
            'evaluation' => $evaluation,
            'evaluator' => $evaluator,
            'template' => $template,
            'savedAnswers' => $savedAnswers // <-- تمرير الإجابات المحفوظة إلى الواجهة
        ];

        $this->view('evaluations/fill', $data);
    } 
    
    /**
     * =================================================================
     * دالة جديدة: استقبال وحفظ نتائج استمارة التقييم
     * =================================================================
     */
  
    public function submit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/');
        }

        Auth::enforce('evaluations.fill');

        $evaluationId = (int)$_POST['evaluation_id'];
        $results = $_POST['results'] ?? [];

        if (empty($results) || empty($evaluationId)) {
            Flash::set('بيانات التقييم غير مكتملة.', 'error');
            $this->redirect("/evaluations/{$evaluationId}/fill");
            return;
        }

        $evaluationModel = new Evaluation();
        $evaluation = $evaluationModel->findById($evaluationId);
        if (!$evaluation || $evaluation->status !== 'draft' || !$this->canEditAssignedEvaluation($evaluation)) {
            Flash::set('لا يمكنك حفظ هذا التقييم لأنه غير مسند إليك أو لم يعد مسودة.', 'error');
            $this->redirect('/dashboard');
            return;
        }
        $success = $evaluationModel->saveEvaluationResults($evaluationId, $results);

        if ($success) {
            $finalScore = $evaluationModel->calculateFinalScore($evaluationId);
            $isUnit = in_array($evaluation->entity_type ?? 'employee', ['branch','department']);
            $nextStatus = $isUnit ? 'pending_review' : 'completed';
            if ($finalScore !== null) {
                $evaluationModel->updateScoreAndStatus($evaluationId, $finalScore, $nextStatus);
            } else {
                $evaluationModel->updateStatus($evaluationId, $nextStatus);
            }
            Audit::log('EVALUATION_SUBMITTED', $evaluationId, 'evaluations');
            if ($isUnit) Flash::set('تم حفظ تقييم الوحدة وإرساله للمراجعة.', 'success');
            else Flash::set('تم حفظ وإرسال التقييم بنجاح!', 'success');
            $this->redirect('/employees');
        } else {
            Flash::set('حدث خطأ أثناء حفظ التقييم.', 'error');
            $this->redirect("/evaluations/{$evaluationId}/fill");
        }
    }
    /**
     * =================================================================
     * دالة جديدة: عرض تفاصيل تقييم مكتمل
     * =================================================================
     * @param int $id - معرف التقييم (يأتي من الرابط)
     */
  /*  public function show($id) {
        // يمكنك إضافة صلاحية خاصة لعرض التقارير هنا
        Auth::enforce('reports.view.all'); // مثال لصلاحية

        $evaluationModel = new Evaluation();
        $evaluation = $evaluationModel->findById($id);

        if (!$evaluation) {
            Flash::set('التقييم المطلوب غير موجود.', 'error');
            $this->redirect('/dashboard');
            return;
        }

        // جلب تفاصيل المُقيِّم
        $employeeModel = new Employee();
        $evaluator = $employeeModel->getDetailsForSnapshot($evaluation->evaluator_employee_id);

        // جلب هيكل القالب المستخدم
        $templateModel = new Template();
        $template = $templateModel->getTemplateWithStructure($evaluation->template_id);

        // جلب إجابات التقييم
        $answers = $evaluationModel->getEvaluationAnswers($id);

        if (!$template) {
            Flash::set('قالب التقييم المرتبط بهذا التقييم غير موجود أو تم حذفه.', 'error');
            $this->redirect('/dashboard');
            return;
        }

        // تجميع كل البيانات في مصفوفة واحدة لإرسالها إلى الواجهة
        $data = [
            'pageTitle' => 'عرض تقييم: ' . htmlspecialchars($evaluation->employee_name),
            'evaluation' => $evaluation,
            'evaluator' => $evaluator,
            'template' => $template,
            'answers' => $answers // <-- إرسال الإجابات إلى الواجهة
        ];

        $this->view('evaluations/show', $data);
    }*/
// ... (داخل كلاس EvaluationController)

  public function show($id) {
        // التحقق من الصلاحية
        Auth::enforce('reports.view.all');

        $evaluationModel = new \App\Models\Evaluation();
        $evaluation = $evaluationModel->findById($id);

        // التأكد من أن التقييم موجود
        if (!$evaluation) {
            Flash::set('التقييم المطلوب غير موجود.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        // السماح بعرض التقييمات حسب الحالة (بالنسبة لتقييمات الوحدات والpegawai)
        $allowedStatuses = ['completed', 'approved', 'pending_review', 'draft'];
        if (!in_array($evaluation->status, $allowedStatuses)) {
            Flash::set('حالة التقييم لا تسمح بالعرض.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        // جلب البيانات المرتبطة
        $employeeModel = new \App\Models\Employee();
        $evaluator = $evaluation->evaluator_employee_id ? $employeeModel->findById($evaluation->evaluator_employee_id) : null;

        $templateModel = new \App\Models\Template();
        $template = $templateModel->getTemplateWithStructure($evaluation->template_id);
        $answers = $evaluationModel->getEvaluationAnswers($evaluation->id);

        // جلب التاريخ الهجري
        $hijriDate = convertToHijri($evaluation->evaluation_date);

        // تجهيز البيانات للعرض
        $data = [
            'pageTitle' => 'عرض تقييم: ' . htmlspecialchars($evaluation->employee_name),
            'evaluation' => $evaluation,
            'evaluator' => $evaluator,
            'template' => $template,
            'answers' => $answers,
            'hijriDate' => $hijriDate
        ];

        $this->view('evaluations/show', $data);
    }

// ... (بقية الدوال)

     /**
     * =================================================================
     * دالة جديدة: حذف تقييم
     * =================================================================
     */
    public function destroy() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/');
        }

        // صلاحية خاصة للحذف
        Auth::enforce('evaluations.delete'); // ستحتاج لإضافة هذه الصلاحية في قاعدة البيانات

        $evaluationId = (int)$_POST['evaluation_id'];

        $evaluationModel = new Evaluation();
        $evaluation = $evaluationModel->findById($evaluationId);

        if (!$evaluation) {
            Flash::set('التقييم المطلوب حذفه غير موجود.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        if ($evaluationModel->delete($evaluationId)) {
            Audit::log('EVALUATION_DELETED', $evaluationId, 'evaluations', ['employee_name' => $evaluation->employee_name]);
            Flash::set('تم حذف التقييم بنجاح.', 'success');
        } else {
            Flash::set('حدث خطأ أثناء حذف التقييم.', 'error');
        }

        // إعادة التوجيه إلى صفحة السجل السنوي للموظف
        $year = date('Y', strtotime($evaluation->evaluation_date));
        $this->redirect("/employees/{$evaluation->evaluated_employee_id}/evaluations/{$year}");
    }

    /**
     * =================================================================
     * دالة جديدة: إعادة فتح تقييم مكتمل لتعديله
     * =================================================================
     */
    public function reopen() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/');
        }

        Auth::enforce('evaluations.edit'); // صلاحية جديدة للتعديل

        $evaluationId = (int)$_POST['evaluation_id'];

        $evaluationModel = new Evaluation();
        // نستخدم دالة updateScoreAndStatus لتغيير الحالة وإعادة تعيين الدرجة إلى NULL
        if ($evaluationModel->updateScoreAndStatus($evaluationId, null, 'draft')) {
            Audit::log('EVALUATION_REOPENED', $evaluationId, 'evaluations');
            Flash::set('تم إعادة فتح التقييم. يمكنك الآن تعديله.', 'info');
            // توجيه المستخدم مباشرة إلى صفحة التعبئة
            $this->redirect("/evaluations/{$evaluationId}/fill");
        } else {
            Flash::set('حدث خطأ أثناء إعادة فتح التقييم.', 'error');
            $this->redirect('/evaluations');
        }

}
 /**
     * =================================================================
     * دالة جديدة: عرض صفحة مخصصة للطباعة فقط
     * =================================================================
     * @param int $id - معرف التقييم
     */
    public function printView($id) {
        // يمكنك استخدام نفس صلاحيات العرض
        Auth::enforce('reports.view.all');

        $evaluationModel = new \App\Models\Evaluation();
        $evaluation = $evaluationModel->findById($id);

        if (!$evaluation || $evaluation->status !== 'completed') {
            Flash::set('التقييم المطلوب غير موجود أو لم يكتمل بعد.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        // جلب كافة البيانات المطلوبة للتقرير
        $employeeModel = new \App\Models\Employee();
        $evaluator = $employeeModel->findById($evaluation->evaluator_employee_id);

        $templateModel = new \App\Models\Template();
        $template = $templateModel->getTemplateWithStructure($evaluation->template_id);
        $answers = $evaluationModel->getEvaluationAnswers($evaluation->id);
        $hijriDate = convertToHijri($evaluation->evaluation_date);

        $data = [
            'pageTitle' => 'طباعة تقييم: ' . htmlspecialchars($evaluation->employee_name),
            'evaluation' => $evaluation,
            'evaluator' => $evaluator,
            'template' => $template,
            'answers' => $answers,
            'hijriDate' => $hijriDate
        ];

        // ▼▼▼ استدعاء ملف العرض الجديد الخاص بالطباعة ▼▼▼
        $this->view('evaluations/print', $data);
    }

    /** Only the assigned evaluator may change a draft; administrators retain their established report access. */
    private function canEditAssignedEvaluation(object $evaluation): bool {
        if (Auth::userHasPermission('reports.view.all')) {
            return true;
        }
        if (in_array($evaluation->entity_type ?? 'employee', ['branch','department']) && Auth::userHasPermission('units.review')) {
            return true;
        }
        $user = Auth::user();
        return $user && (int)($user->employee_id ?? 0) > 0 && (int)$evaluation->evaluator_employee_id === (int)$user->employee_id;
    }
}

