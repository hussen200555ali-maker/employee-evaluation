<?php
// المسار: app/Controllers/EvaluationController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Audit; // استيراد فئة التدقيق
use App\Core\Database; // CD-037: Database::getInstance() في saveEvaluationContext/sync*
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
            'department_id' => $_GET['department_id'] ?? null,
            'template_id' => $_GET['template_id'] ?? null,
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

        // CD-040.7: قوالب (دورة/ورشة/مهاري) التي لديها تقييمات (لفلتر "شارك في")
        $dbTpl = \App\Core\Database::getInstance();
        $dbTpl->query("SELECT t.id, t.template_name, t.target_type, COUNT(e.id) AS eval_count
                       FROM form_templates t
                       JOIN evaluations e ON e.template_id = t.id
                       WHERE t.target_type IN ('employee_in_course','employee_in_workshop','skill')
                       GROUP BY t.id, t.template_name, t.target_type
                       ORDER BY t.target_type, t.template_name");
        $templatesWithEvals = $dbTpl->fetchAll();

        $data = [
            'pageTitle' => 'ملفات أداء الموظفين',
            'employees' => $employees,
            'branches' => $branches,
            'departments' => $departments,
            'templatesWithEvals' => $templatesWithEvals,
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

        // CD-040: pass templates, branches, evaluator info for the unified page
        $db = \App\Core\Database::getInstance();
        $db->query('SELECT id, template_name, evaluation_mode, evaluation_frequency, target_type
                    FROM form_templates WHERE is_active = 1 ORDER BY template_name');
        $templates = $db->fetchAll();

        $db->query('SELECT id, name FROM branches ORDER BY name');
        $branches = $db->fetchAll();

        $evaluatorName = '';
        $evaluatorTitle = '';
        $user = Auth::user();
        if ($user && !empty($user->employee_id)) {
            $empModel = new Employee();
            $emp = $empModel->findById((int)$user->employee_id);
            if ($emp) {
                $evaluatorName = $emp->full_name;
                $evaluatorTitle = $emp->position_title ?? '';
            }
        }

        $data = [
            'pageTitle'      => 'بدء تقييم جديد',
            'templates'      => $templates,
            'branches'       => $branches,
            'evaluatorName'  => $evaluatorName,
            'evaluatorTitle' => $evaluatorTitle,
            // CD-040.3.1: سلالم التقديرات للبطاقة السفلية (final_grade_id) —
            // كانت مفقودة فظهرت القائمة فارغة في وضع الإنشاء
            'gradingScales'  => (new \App\Models\GradingScale())->getActive(),
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

        // CD-040.6 (b): derive evaluation_type from template target_type (not POST).
        $evaluationType = 'annual';
        $tplIdForType = (int)($_POST['template_id'] ?? 0);
        if ($tplIdForType > 0) {
            $dbType = \App\Core\Database::getInstance();
            $dbType->query('SELECT target_type FROM form_templates WHERE id = :id');
            $dbType->bind(':id', $tplIdForType);
            $tplType = $dbType->fetch();
            if ($tplType) {
                $tt = $tplType->target_type;
                if ($tt === 'course' || $tt === 'employee_in_course') $evaluationType = 'course';
                elseif ($tt === 'workshop' || $tt === 'employee_in_workshop') $evaluationType = 'workshop';
            }
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
            $currentUserEmployeeId = (int)(Auth::user()->employee_id ?? 0);
            $evaluatorEmployeeId = !empty($_POST['evaluator_employee_id'])
                ? (int)$_POST['evaluator_employee_id']
                : ($currentUserEmployeeId > 0 ? $currentUserEmployeeId : null);
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
            'evaluation_type' => $evaluationType,
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
        $evaluator = !empty($evaluation->evaluator_employee_id)
            ? $employeeModel->getDetailsForSnapshot((int)$evaluation->evaluator_employee_id)
            : null;

        $templateModel = new Template();
        $template = $templateModel->getTemplateWithStructure($evaluation->template_id);

        if (!$template) {
            Flash::set('قالب التقييم المرتبط بهذا التقييم غير موجود أو تم حذفه.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        // CD-040: load templates list for the unified selector (single template pre-selected)
        $db = \App\Core\Database::getInstance();
        $db->query('SELECT id, template_name, evaluation_mode, evaluation_frequency, target_type
                    FROM form_templates WHERE is_active = 1 ORDER BY template_name');
        $templates = $db->fetchAll();

        $db->query('SELECT id, name FROM branches ORDER BY name');
        $branches = $db->fetchAll();

        $data = [
            'pageTitle'      => 'تعديل استمارة التقييم: ' . htmlspecialchars($evaluation->employee_name),
            'evaluation'     => $evaluation,
            'evaluator'      => $evaluator,
            'template'       => $template,
            'savedAnswers'   => $savedAnswers,
            'gradingScales'  => (new \App\Models\GradingScale())->getActive(),
            'templates'      => $templates,
            'branches'       => $branches,
            'evaluatorName'  => $evaluation->evaluator_name ?? ($evaluator->full_name ?? ''),
            'evaluatorTitle' => $evaluation->evaluator_title ?? ($evaluator->position_title ?? ''),
        ];

        // CD-040: render the SAME unified create.php view in edit mode
        $this->view('evaluations/create', $data);
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
        $template = (new Template())->getTemplateWithStructure($evaluation->template_id);
        if (!$template) {
            Flash::set('قالب التقييم المرتبط بهذا التقييم غير موجود.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        // CD-029: ربط التقييم بالدورة/الورشة — إلزامي لـ employee_in_course/workshop
        $targetType = $template['target_type'] ?? 'employee';
        $contextType = null;
        $contextId = null;
        if ($targetType === 'employee_in_course') {
            $contextId = (int)($_POST['context_course_id'] ?? 0);
            if ($contextId < 1) {
                Flash::set('يجب اختيار الدورة التدريبية قبل الحفظ.', 'error');
                $this->redirect("/evaluations/{$evaluationId}/fill");
                return;
            }
            $contextType = 'course';
        } elseif ($targetType === 'employee_in_workshop') {
            $contextId = (int)($_POST['context_workshop_id'] ?? 0);
            if ($contextId < 1) {
                Flash::set('يجب اختيار الورشة قبل الحفظ.', 'error');
                $this->redirect("/evaluations/{$evaluationId}/fill");
                return;
            }
            $contextType = 'workshop';
        }
        // حفظ context في التقييم إن وُجد
        if ($contextType && $contextId) {
            $this->saveEvaluationContext($evaluationId, $contextType, $contextId);
        }

        $manualGrade = null;
        $mode = $template['evaluation_mode'] ?? 'numeric';
        if (in_array($mode, ['descriptive', 'mixed'], true)) {
            // CD-023: أولاً، ابحث عن إجابة قسم "التقدير العام" في النتائج
            $overallGradeAnswer = $this->extractOverallGradeAnswer($template, $results);
            if ($overallGradeAnswer !== null) {
                $numericValue = (new \App\Models\GradingScale())->getNumericValueForLabel(
                    $overallGradeAnswer['scale_name'],
                    $overallGradeAnswer['grade_label']
                );
                if ($numericValue !== null) {
                    $manualGrade = (object)[
                        'grade_label'   => $overallGradeAnswer['grade_label'],
                        'numeric_value' => $numericValue,
                        'scale_name'    => $overallGradeAnswer['scale_name'],
                    ];
                }
            }
            // إن لم يُعثر على قسم "تقدير عام" (قالب قديم)، نعود للآلية القديمة
            if (!$manualGrade) {
                $gradeId = filter_var($_POST['final_grade_id'] ?? null, FILTER_VALIDATE_INT);
                $manualGrade = ($gradeId !== false && $gradeId !== null && $gradeId > 0)
                    ? (new \App\Models\GradingScale())->findActiveById($gradeId)
                    : null;
            }
            if (!$manualGrade) {
                Flash::set('اختر تقديرًا عامًا صالحًا من سلم التقديرات النشط.', 'danger');
                $this->redirect("/evaluations/{$evaluationId}/fill");
                return;
            }
        }

        $success = $evaluationModel->saveEvaluationResults($evaluationId, $results);

        if ($success) {
            $isDescriptive = ($template['evaluation_mode'] ?? 'numeric') === 'descriptive';
            // CD-026: للقوالب الوصفية، final_score على مقياس 0-100 (max_value) لا 1-5
            $finalScore = $isDescriptive
                ? $evaluationModel->calculateFinalScore($evaluationId)
                : $evaluationModel->calculateFinalScore($evaluationId);
            $isUnit = in_array($evaluation->entity_type ?? 'employee', ['branch','department']);
            $nextStatus = $isUnit ? 'pending_review' : 'completed';
            if ($manualGrade) {
                $updated = $evaluationModel->updateScoreGradeAndStatus(
                    $evaluationId,
                    $finalScore,
                    $manualGrade->grade_label,
                    (float)$manualGrade->numeric_value,
                    $nextStatus
                );
            } elseif ($finalScore !== null) {
                $updated = $evaluationModel->updateScoreAndStatus($evaluationId, $finalScore, $nextStatus);
            } else {
                $updated = $evaluationModel->updateStatus($evaluationId, $nextStatus);
            }

            if (!$updated) {
                Flash::set('تم حفظ الإجابات، لكن تعذر تحديث نتيجة التقييم وحالته. يرجى المحاولة مرة أخرى.', 'error');
                $this->redirect("/evaluations/{$evaluationId}/fill");
                return;
            }
            Audit::log('EVALUATION_SUBMITTED', $evaluationId, 'evaluations');

            // CD-036/CD-037: تحديث program_participants (الجدول الموحَّد) عند إكمال تقييم برنامج
            // context_id يشير الآن إلى training_programs.id (بعد الترحيل v4.1)
            if ($contextType && $contextId) {
                (new \App\Models\ProgramParticipant())->markCompleted(
                    $contextId,
                    (int)$evaluation->evaluated_employee_id,
                    (float)($finalScore ?? 0)
                );
            }

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
     * CD-040: حفظ موحّد — ينشئ تقييمًا جديدًا أو يحدّث مسودة موجودة في طلب واحد
     * POST /evaluations/save-unified (AJAX, returns JSON)
     * =================================================================
     */
    public function saveUnified() {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'طريقة غير مسموحة'], 405);
            return;
        }

        $perm = 'evaluations.create';
        $existingId = (int)($_POST['evaluation_id'] ?? 0);
        if ($existingId > 0) {
            $perm = 'evaluations.fill';
        }
        Auth::enforce($perm);
        Auth::validateCsrfToken($_POST['_token'] ?? $_POST['_csrf'] ?? '');

        $employeeId   = (int)($_POST['employee_id'] ?? 0);
        $templateId   = (int)($_POST['template_id'] ?? 0);
        $evalDate     = trim((string)($_POST['evaluation_date'] ?? date('Y-m-d')));
        $results      = $_POST['results'] ?? [];
        $evaluatorName  = trim((string)($_POST['evaluator_name'] ?? ''));
        $evaluatorTitle = trim((string)($_POST['evaluator_title'] ?? ''));
        $contextId    = (int)($_POST['context_id'] ?? 0);
        $contextType  = (string)($_POST['context_type'] ?? '');

        if ($employeeId < 1 || $templateId < 1 || empty($results)) {
            $this->json(['error' => 'بيانات ناقصة: الموظف والقالب والإجابات مطلوبة'], 400);
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();
            $evalModel = new Evaluation();

            if ($existingId > 0) {
                // UPDATE existing draft
                $evaluation = $evalModel->findById($existingId);
                if (!$evaluation) {
                    $this->json(['error' => 'التقييم غير موجود'], 404);
                    return;
                }
                if ($evaluation->status !== 'draft') {
                    $this->json(['error' => 'لا يمكن تعديل تقييم غير مسودة'], 400);
                    return;
                }
                if (!$this->canEditAssignedEvaluation($evaluation)) {
                    $this->json(['error' => 'هذا التقييم غير مسند إليك'], 403);
                    return;
                }
                $evaluationId = $existingId;
                $evalModel->saveEvaluationResults($evaluationId, $results);
                // refresh evaluator fields
                $db->query('UPDATE evaluations SET evaluator_name=:en, evaluator_title=:et, evaluation_date=:ed WHERE id=:id');
                $db->bind(':en', $evaluatorName);
                $db->bind(':et', $evaluatorTitle);
                $db->bind(':ed', $evalDate);
                $db->bind(':id', $evaluationId);
                $db->execute();
            } else {
                // CREATE new
                // CD-041.5: منع التكرار — نفس موظف + قالب + تاريخ (+ سياق)
                $ctxIdForCheck = $contextId > 0 ? $contextId : 0;
                $ctxTypeForCheck = $contextId > 0 ? $contextType : '';
                $existingEvalId = $evalModel->findExistingForEmployeeTemplateDate($employeeId, $templateId, $evalDate, $ctxIdForCheck, $ctxTypeForCheck);
                if ($existingEvalId) {
                    $this->json(['error' => 'يوجد تقييم مسبق (#' . $existingEvalId . ') لهذا الموظف بنفس القالب والتاريخ' . ($ctxIdForCheck > 0 ? ' والبرنامج' : '')], 409);
                    return;
                }
                $empModel = new Employee();
                $emp = $empModel->findById($employeeId);
                if (!$emp) {
                    $this->json(['error' => 'الموظف غير موجود'], 404);
                    return;
                }
                // CD-040.2: احترم evaluator_employee_id المُرسل من النموذج إن وُجد،
                // وإلا استخدم مستخدم الجلسة. ثم اجلب الاسم/الصفة من سجل ذلك الموظف
                // لضمان تطابق المعرف مع الاسم (كان يُتجاهل ويُحفظ من الجلسة).
                $user = Auth::user();
                $postedEvalId = (int)($_POST['evaluator_employee_id'] ?? 0);
                $evaluatorEmpId = $postedEvalId > 0
                    ? $postedEvalId
                    : ($user && !empty($user->employee_id) ? (int)$user->employee_id : null);
                if ($evaluatorEmpId) {
                    $evalEmp = $empModel->findById($evaluatorEmpId);
                    if ($evalEmp) {
                        $evaluatorName = $evalEmp->full_name;
                        $evaluatorTitle = $evaluatorTitle ?: ($evalEmp->position_title ?? '');
                    }
                }
                // template target → evaluation_type
                $db->query('SELECT target_type FROM form_templates WHERE id=:id');
                $db->bind(':id', $templateId);
                $tpl = $db->fetch();
                $evalType = 'annual';
                if ($tpl) {
                    $tt = $tpl->target_type;
                    if ($tt === 'course' || $tt === 'employee_in_course') $evalType = 'course';
                    elseif ($tt === 'workshop' || $tt === 'employee_in_workshop') $evalType = 'workshop';
                }
                try {
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
                    $db->bind(':date', $evalDate);
                    $db->bind(':etype', $evalType);
                    $db->bind(':emp', $employeeId);
                    $db->bind(':entity', $employeeId);
                    $db->bind(':eval_emp', $evaluatorEmpId);
                    $db->bind(':eval_name', $evaluatorName);
                    $db->bind(':eval_title', $evaluatorTitle);
                    $db->bind(':emp_name', $emp->full_name);
                    $db->bind(':emp_code', $emp->emp_code);
                    $db->bind(':branch', $emp->branch_name ?? '');
                    $db->bind(':dept', $emp->department_name ?? '');
                    $db->bind(':pos', $emp->position_title ?? '');
                    $uid = (int)Auth::id(); if ($uid < 1) $uid = 1;
                    $db->query('SELECT id FROM users WHERE id = :id'); $db->bind(':id', $uid);
                    if (!$db->fetch()) $uid = 1;
                    $db->bind(':user', $uid);
                    $db->execute();
                    $evaluationId = (int)$db->lastInsertId();
                    $evalModel->saveEvaluationResults($evaluationId, $results);
                } catch (\PDOException $e) {
                    // CD-042: قيد التفرد (1062) → تخطي بدل 500
                    if (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
                        $exId = $evalModel->findExistingForEmployeeTemplateDate($employeeId, $templateId, $evalDate, (int)$contextId, (string)$contextType);
                        $this->json(['error' => 'يوجد تقييم مسبق (#' . ($exId ?: '?') . ')', 'existing_id' => $exId], 409);
                        return;
                    }
                    throw $e;
                }
            }

            // context (course/workshop) + sync program_participants
            if ($contextId > 0 && in_array($contextType, ['course', 'workshop'], true)) {
                $db->query('UPDATE evaluations SET context_type=:ct, context_id=:cid WHERE id=:eid');
                $db->bind(':ct', $contextType);
                $db->bind(':cid', $contextId);
                $db->bind(':eid', $evaluationId);
                $db->execute();
            }

            // compute final score + finalize status
            $isUnit = false; // unified page is employee-only
            $nextStatus = $isUnit ? 'pending_review' : 'completed';
            $template = (new Template())->getTemplateWithStructure($templateId);
            $mode = $template['evaluation_mode'] ?? 'numeric';

            // CD-040.3: التقدير العام «ناتج نظام» — للوصفي/المختلط:
            // final_score = max_value للدرجة المختارة من grading_scales (مثلاً ممتاز→100)
            // grade_label يُحفظ مباشرة في evaluations. لا اعتماد على evaluation_answers.
            $manualGrade = null;
            $finalScore = null;
            if (in_array($mode, ['descriptive', 'mixed'], true)) {
                $gradeId = (int)($_POST['final_grade_id'] ?? 0);
                if ($gradeId > 0) {
                    $gs = (new \App\Models\GradingScale())->findActiveById($gradeId);
                    if ($gs) {
                        $manualGrade = $gs;
                        $finalScore = (float)$gs->max_value;  // ★ ممتاز → 100.00
                    }
                }
                // للقالب المختلط: إن لم يُختر تقدير، احسب من الأقسام الرقمية
                if ($finalScore === null && $mode === 'mixed') {
                    $finalScore = $evalModel->calculateFinalScore($evaluationId);
                }
            } else {
                // numeric: المتوسط المرجّح للأقسام
                $finalScore = $evalModel->calculateFinalScore($evaluationId);
            }

            if ($manualGrade) {
                $evalModel->updateScoreGradeAndStatus(
                    $evaluationId,
                    $finalScore,
                    $manualGrade->grade_label,
                    (float)$manualGrade->numeric_value,
                    $nextStatus
                );
            } elseif ($finalScore !== null) {
                $evalModel->updateScoreAndStatus($evaluationId, $finalScore, $nextStatus);
            } else {
                $evalModel->updateStatus($evaluationId, $nextStatus);
            }

            // sync program_participants
            if ($contextId > 0 && in_array($contextType, ['course', 'workshop'], true)) {
                (new \App\Models\ProgramParticipant())->markCompleted(
                    $contextId,
                    $employeeId,
                    (float)($finalScore ?? 0)
                );
            }

            Audit::log('EVALUATION_SAVED_UNIFIED', $evaluationId, 'evaluations', [
                'employee_id' => $employeeId,
                'template_id' => $templateId,
                'mode' => $existingId > 0 ? 'update' : 'create',
            ]);

            $this->json([
                'success'       => true,
                'evaluation_id' => $evaluationId,
                'final_score'   => $finalScore,
                'message'       => 'تم حفظ التقييم بنجاح',
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'فشل الحفظ: ' . $e->getMessage()], 500);
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

        // CD-040.5: بيانات البرنامج التدريبي (دورة/ورشة) لهذا التقييم — ناتج عرض فقط، بلا حالة مشارك
        $program = $this->fetchProgramForEvaluation($evaluation);

        // جلب التاريخ الهجري
        $hijriDate = convertToHijri($evaluation->evaluation_date);

        // تجهيز البيانات للعرض
        $data = [
            'pageTitle' => 'عرض تقييم: ' . htmlspecialchars($evaluation->employee_name),
            'evaluation' => $evaluation,
            'evaluator' => $evaluator,
            'template' => $template,
            'answers' => $answers,
            'hijriDate' => $hijriDate,
            'program' => $program,
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
        $program = $this->fetchProgramForEvaluation($evaluation);

        $data = [
            'pageTitle' => 'طباعة تقييم: ' . htmlspecialchars($evaluation->employee_name),
            'evaluation' => $evaluation,
            'evaluator' => $evaluator,
            'template' => $template,
            'answers' => $answers,
            'hijriDate' => $hijriDate,
            'program' => $program,
        ];

        // ▼▼▼ استدعاء ملف العرض الجديد الخاص بالطباعة ▼▼▼
        $this->view('evaluations/print', $data);
    }

    /**
     * CD-040.5: جلب بيانات البرنامج التدريبي (دورة/ورشة) لتقييم موظف داخل دورة/ورشة.
     * مصدر البيانات: evaluations.context_type/context_id → training_programs
     * + fallback entity_snapshot.course_title للتقييمات القديمة بدون context.
     * لا يعرض حالة مشارك — الموظف لا يُقيَّم إلا بعد إكمال الدورة (خارج نظام الدورات).
     */
    private function fetchProgramForEvaluation(object $evaluation): ?object {
        $contextType = $evaluation->context_type ?? null;
        $contextId = (int)($evaluation->context_id ?? 0);
        if (in_array($contextType, ['course', 'workshop'], true) && $contextId > 0) {
            $db = \App\Core\Database::getInstance();
            $db->query('SELECT tp.*, b.name AS branch_name, d.name AS department_name, c.name AS catalog_title
                        FROM training_programs tp
                        LEFT JOIN branches b ON b.id = tp.executing_branch_id
                        LEFT JOIN departments d ON d.id = tp.executing_department_id
                        LEFT JOIN training_catalogs c ON c.id = tp.catalog_id
                        WHERE tp.id = :id LIMIT 1');
            $db->bind(':id', $contextId);
            $row = $db->fetch();
            if ($row) {
                $row->is_legacy_snapshot = false;
                return $row;
            }
        }
        // fallback: لقطة قديمة بدون context (تقييمات قبل CD-036)
        if (!empty($evaluation->entity_snapshot)) {
            $snap = json_decode((string)$evaluation->entity_snapshot, true);
            if (is_array($snap) && !empty($snap['course_title'])) {
                return (object)[
                    'id' => null, 'type' => $contextType ?: 'course',
                    'title' => $snap['course_title'], 'description' => null,
                    'start_date' => null, 'end_date' => null,
                    'branch_name' => null, 'department_name' => null,
                    'catalog_title' => null, 'responsible_person' => null,
                    'status' => null, 'is_legacy_snapshot' => true,
                ];
            }
        }
        return null;
    }

    /**
     * CD-023: استخراج إجابة قسم "التقدير العام" من نتائج التقييم المُرسلة.
     * يبحث في هيكل القالب عن قسمٍ من نوع overall_grade، ثم يقرأ
     * grade_label من $_POST['results'][criterionId][sectionId].
     *
     * @return array{scale_name:string,grade_label:string}|null
     */
    private function extractOverallGradeAnswer(array $template, array $results): ?array {
        foreach ($template['criteria'] ?? [] as $criterion) {
            $critId = $criterion['id'];
            foreach ($criterion['sections'] ?? [] as $section) {
                if (($section['evaluation_type'] ?? '') === 'overall_grade') {
                    $sectionId = $section['id'];
                    $answer = $results[$critId][$sectionId] ?? null;
                    if (is_array($answer) && !empty($answer['grade_label'])) {
                        $opts = is_string($section['evaluation_options'] ?? '')
                            ? json_decode($section['evaluation_options'], true)
                            : ($section['evaluation_options'] ?? []);
                        return [
                            'scale_name'  => $opts['scale_name'] ?? 'السلم الخماسي',
                            'grade_label' => (string)$answer['grade_label'],
                        ];
                    }
                }
            }
        }
        return null;
    }

    /**
     * CD-029: حفظ context_type + context_id في جدول evaluations.
     */
    private function saveEvaluationContext(int $evaluationId, string $contextType, int $contextId): void {
        $db = Database::getInstance();
        $db->query('UPDATE evaluations SET context_type = :ctype, context_id = :cid WHERE id = :eid');
        $db->bind(':ctype', $contextType);
        $db->bind(':cid', $contextId);
        $db->bind(':eid', $evaluationId);
        $db->execute();
    }

    /**
     * CD-029: تحديث/إدراج سجل مشاركة الموظف في دورة → status='completed'.
     */
    private function syncCourseParticipant(int $courseId, int $employeeId): void {
        $db = Database::getInstance();
        // تحقق من وجود السجل
        $db->query('SELECT id FROM course_participants WHERE course_id = :cid AND employee_id = :eid LIMIT 1');
        $db->bind(':cid', $courseId);
        $db->bind(':eid', $employeeId);
        $existing = $db->fetch();
        if ($existing) {
            $db->query('UPDATE course_participants SET status = "completed", completed_at = NOW() WHERE id = :id');
            $db->bind(':id', (int)$existing->id);
            $db->execute();
        } else {
            $db->query('INSERT INTO course_participants (course_id, employee_id, status, enrolled_at, completed_at) VALUES (:cid, :eid, "completed", NOW(), NOW())');
            $db->bind(':cid', $courseId);
            $db->bind(':eid', $employeeId);
            $db->execute();
        }
    }

    /**
     * CD-029: تحديث/إدراج سجل مشاركة الموظف في ورشة → status='completed'.
     */
    private function syncWorkshopParticipant(int $workshopId, int $employeeId): void {
        $db = Database::getInstance();
        $db->query('SELECT id FROM workshop_participants WHERE workshop_id = :wid AND employee_id = :eid LIMIT 1');
        $db->bind(':wid', $workshopId);
        $db->bind(':eid', $employeeId);
        $existing = $db->fetch();
        if ($existing) {
            $db->query('UPDATE workshop_participants SET status = "completed", completed_at = NOW() WHERE id = :id');
            $db->bind(':id', (int)$existing->id);
            $db->execute();
        } else {
            $db->query('INSERT INTO workshop_participants (workshop_id, employee_id, status, enrolled_at, completed_at) VALUES (:wid, :eid, "completed", NOW(), NOW())');
            $db->bind(':wid', $workshopId);
            $db->bind(':eid', $employeeId);
            $db->execute();
        }
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
