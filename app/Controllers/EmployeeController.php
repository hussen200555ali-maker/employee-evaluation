<?php
// نحدد الـ Namespace ليعكس مكان الملف في هيكل المشروع.
namespace App\Controllers;

// استيراد الفئات التي سنحتاجها.
use App\Core\Controller; // الفئة الأساسية لوحدات التحكم.
use App\Core\Auth;       // فئة المصادقة والصلاحيات التي أنشأناها.
use App\Models\Employee;  // المودل المسؤول عن التعامل مع جدول الموظفين.
use App\Models\Position; // موديل المنصب للموظف
use App\Models\Branch;
use App\Models\Department;
use App\Core\Audit;
use App\Core\Flash;
use App\Models\Evaluation;

/**
 * EmployeeController
 * مسؤول عن كل الطلبات المتعلقة بالموظفين (عرض، إضافة، تعديل، إلخ).
 */
class EmployeeController extends Controller {

    /**
     * يعرض قائمة جميع الموظفين.
     * هذه الدالة تستجيب للطلب GET /employees.
     */

            public function index() {
    // CD-009: حارس صلاحية صريح (Auth::enforce يطبع 403 ويُنهي التنفيذ).
    Auth::enforce('employees.view');
        $filters=[
            'search'=>trim($_GET['search'] ?? ''),
            'branch_id'=>$_GET['branch_id'] ?? '',
            'department_id'=>$_GET['department_id'] ?? '',
            'position_id'=>$_GET['position_id'] ?? '',
            'is_active'=>$_GET['is_active'] ?? '',
            'sort'=>$_GET['sort'] ?? 'full_name',
            'dir'=>$_GET['dir'] ?? 'ASC',
        ];
        foreach(['branch_id','department_id','position_id'] as $k){ if($filters[$k]===''||$filters[$k]===null) continue; $filters[$k]=(int)$filters[$k]; if($filters[$k]<=0) $filters[$k]=''; }
        if($filters['is_active']!=='' && !in_array($filters['is_active'],['0','1'],true)) $filters['is_active']='';
        $page=max(1,(int)($_GET['page'] ?? 1)); $perPage=25; $offset=($page-1)*$perPage;
        $employeeModel=new Employee();
        $total=$employeeModel->countFiltered($filters);
        $totalPages=max(1,(int)ceil($total/$perPage));
        if($page>$totalPages){ $page=$totalPages; $offset=($page-1)*$perPage; }
        $employees=$employeeModel->getFiltered($filters,$perPage,$offset);
        $data=[
            'pageTitle'=>'قائمة الموظفين',
            'employees'=>$employees,
            'filters'=>$filters,
            'branches'=>(new Branch())->getAll(),
            'departments'=>!empty($filters['branch_id'])?(new Department())->findByBranch((int)$filters['branch_id']):(new Department())->getAll(),
            'positions'=>(new Position())->getAll(),
            'total'=>$total, 'page'=>$page, 'perPage'=>$perPage, 'totalPages'=>$totalPages,
        ];
        $this->view('employees/index',$data);
    }

    /**
     * يعرض الملف الشخصي لموظف محدد.
     * هذه الدالة تستجيب للطلب GET /employees/{id} (مثال: /employees/5).
     *
     * @param int $id - معرف الموظف الذي يتم تمريره من الـ Router.
     */
        public function show($id) {
    if (!Auth::check()) {
        header('Location: ' . APP_URL . '/login');
        exit();
    }

    // CD-009: حارس صلاحية صريح. الترتيب: تحويل غير المسجَّل أولًا (سلوك قائم
    // لا يُغيَّر)، ثم فرض الصلاحية.
    Auth::enforce('employees.view');

    $employeeModel = new Employee();
    $employee = $employeeModel->findById($id);

    if (!$employee) {
        return (new ErrorController())->notFound();
    }
    
    $data = [
        'pageTitle' => 'الملف الشخصي لـ ' . $employee->full_name,
        'employee' => $employee
    ];
    
    $this->view('employees/show', $data);
}


    /**
     * يعرض نموذج إضافة موظف جديد.
     * هذه الدالة تستجيب للطلب GET /employees/create.
     */
 public function create() {
        // CD-009: حارس صلاحية صريح.
        Auth::enforce('employees.create');
        
        $branchModel = new Branch();
        $departmentModel = new Department();
        $employeeModel = new Employee();
        $positionModel = new Position();

        $data = [
            'pageTitle' => 'إضافة موظف جديد',
            'branches' => $branchModel->getAll(),
            'departments' => $departmentModel->getAll(),
            'managers' => $employeeModel->getAll(),
            'positions' => $positionModel->getAll()
        ];
        $this->view('employees/create', $data);
    }
    /**
     * يخزن بيانات الموظف الجديد (النسخة النهائية مع التحقق من التكرار).
     */
    public function store() {
        // CD-009: حارس صلاحية صريح.
        Auth::enforce('employees.create');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/employees/create');
            return;
        }

        // 1. التحقق من صحة البيانات الأساسية
        $emp_code = trim($_POST['emp_code']);
        $full_name = trim($_POST['full_name']);
        if (empty($emp_code) && empty($full_name)) {
            Flash::set('يجب إدخال الرقم الوظيفي أو الاسم الكامل على الأقل.', 'error');
            $this->redirect('/employees/create');
            return;
        }

        $branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
        $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        if (empty($branch_id) && empty($department_id)) {
            Flash::set('يجب اختيار الفرع أو القسم على الأقل.', 'error');
            $this->redirect('/employees/create');
            return;
        }

        // ==================================================
        //           ===>  التحقق من التكرار هنا  <===
        // ==================================================
        $employeeModel = new Employee();

        // تحقق فقط إذا كان الرقم الوظيفي غير فارغ
        if (!empty($emp_code)) {
            if ($employeeModel->findByEmpCode($emp_code)) {
                // إذا تم العثور على موظف بنفس الرقم، أظهر رسالة خطأ وأعد التوجيه
                Flash::set('الرقم الوظيفي "' . htmlspecialchars($emp_code) . '" مستخدم بالفعل. الرجاء إدخال رقم آخر.', 'error');
                $this->redirect('/employees/create');
                return;
            }
        }

        // 2. تجميع بيانات الموظف
        $employeeData = [
            'emp_code' => !empty($emp_code) ? $emp_code : null,
            'full_name' => !empty($full_name) ? $full_name : null,
            'nickname' => !empty($_POST['nickname']) ? trim($_POST['nickname']) : null,
            'branch_id' => $branch_id,
            'department_id' => $department_id,
            'manager_id' => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
            'position_id' => (int)$_POST['position_id'],
        ];

        // 3. إنشاء الموظف
        $newEmployeeId = $employeeModel->create($employeeData);

        if (!$newEmployeeId) {
            Flash::set('حدث خطأ فادح أثناء حفظ بيانات الموظف.', 'error');
            $this->redirect('/employees/create');
            return;
        }
        
        Audit::log('EMPLOYEE_CREATE', $newEmployeeId, 'employees', $employeeData);

        // 4. تحديد الإجراء التالي
        $action = $_POST['action'] ?? 'save';

        if ($action === 'save_and_evaluate') {
            // --- منطق الحفظ والتقييم ---
            $evaluationData = [
                'template_id' => (int)$_POST['template_id'],
                'evaluated_employee_id' => $newEmployeeId,
                'evaluator_employee_id' => (int)$_POST['evaluator_employee_id'],
                'evaluation_date' => $_POST['evaluation_date'],
                'created_by_user_id' => Auth::id(),
            ];

            $snapshot = $employeeModel->getDetailsForSnapshot($newEmployeeId);
            if ($snapshot) {
                $evaluationData['employee_name'] = $snapshot->full_name;
                $evaluationData['employee_code'] = $snapshot->emp_code;
                $evaluationData['branch_name'] = $snapshot->branch_name;
                $evaluationData['department_name'] = $snapshot->department_name;
                $evaluationData['position_name'] = $snapshot->position_title;
            }

            $evaluationModel = new Evaluation();
            $newEvaluationId = $evaluationModel->create($evaluationData);

            if ($newEvaluationId) {
                Flash::set('تم حفظ الموظف بنجاح. يمكنك الآن بدء تقييمه.', 'success');
                header('Location: ' . APP_URL . "/evaluations/{$newEvaluationId}/fill");
                exit();
            } else {
                Flash::set('تم حفظ الموظف، ولكن حدث خطأ أثناء بدء التقييم.', 'error');
                header('Location: ' . APP_URL . '/employees');
                exit();
            }

        } else {
            // --- منطق الحفظ فقط ---
            Flash::set('تمت إضافة الموظف بنجاح.', 'success');
            header('Location: ' . APP_URL . '/employees');
            exit();
        }
    }

    /**
     * يعرض نموذج تعديل بيانات موظف.
     * هذه الدالة تستجيب للطلب GET /employees/{id}/edit.
     *
     * @param int $id - معرف الموظف.
     */
        public function edit($id) {
        // CD-009: حارس صلاحية صريح.
        Auth::enforce('employees.edit');

        $employeeModel = new Employee();
        $branchModel = new Branch();
        $departmentModel = new Department();
        $positionModel = new Position(); // ===> إنشاء كائن من مودل المناصب

        $employee = $employeeModel->findById($id);
        // 4. التأكد من وجود الموظف.
        if (!$employee) {
            // CD-009: كان يُرجع forbidden() (403) رغم أنه 404. صُحِّح إلى notFound().
            return (new ErrorController())->notFound();
        }
        // ===> التعديل هنا: إضافة positions إلى البيانات <===
        $data = [
            'pageTitle' => 'تعديل موظف',
            'employee' => $employee,
            'branches' => $branchModel->getAll(),
            'departments' => $departmentModel->getAll(),
            'managers' => $employeeModel->getAll(),
            'positions' => $positionModel->getAll() // جلب المناصب
        ];

        $this->view('employees/edit', $data);
    }  
              /**
     * يعالج البيانات المرسلة من نموذج التعديل ويحفظها (النسخة المحدثة).
     */
    public function update() {
        // CD-009: حارس صلاحية صريح.
        Auth::enforce('employees.edit');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/employees');
            return;
        }

        $id = (int)$_POST['id'];

        // 1. التحقق من صحة البيانات الأساسية
        $emp_code = trim($_POST['emp_code']);
        $full_name = trim($_POST['full_name']);
        if (empty($emp_code) && empty($full_name)) {
            Flash::set('يجب إدخال الرقم الوظيفي أو الاسم الكامل على الأقل.', 'error');
            $this->redirect("/employees/{$id}/edit");
            return;
        }

        $branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
        $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        if (empty($branch_id) && empty($department_id)) {
            Flash::set('يجب اختيار الفرع أو القسم على الأقل.', 'error');
            $this->redirect("/employees/{$id}/edit");
            return;
        }

        // 2. التحقق من تكرار الرقم الوظيفي (مع استثناء الموظف الحالي)
        $employeeModel = new Employee();
        if (!empty($emp_code)) {
            $existingEmployee = $employeeModel->findByEmpCode($emp_code);
            if ($existingEmployee && $existingEmployee->id != $id) {
                Flash::set('الرقم الوظيفي "' . htmlspecialchars($emp_code) . '" مستخدم بالفعل لموظف آخر.', 'error');
                $this->redirect("/employees/{$id}/edit");
                return;
            }
        }

        // 3. تجهيز البيانات القادمة من النموذج.
        $data = [
            'emp_code' => !empty($emp_code) ? $emp_code : null,
            'full_name' => !empty($full_name) ? $full_name : null,
            'nickname' => !empty($_POST['nickname']) ? trim($_POST['nickname']) : null,
            'department_id' => $department_id,
            'branch_id' => $branch_id,
            'manager_id' => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
            'position_id' => (int)$_POST['position_id'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        // 4. استدعاء دالة update في المودل.
        if ($employeeModel->update($id, $data)) {
            Audit::log('EMPLOYEE_UPDATE', $id, 'employees', $data);
            Flash::set('تم تحديث بيانات الموظف بنجاح.', 'success');
            $this->redirect('/employees');
        } else {
            Flash::set('حدث خطأ أثناء تحديث بيانات الموظف.', 'error');
            $this->redirect("/employees/{$id}/edit");
        }
    }

  
    /**
     * يحذف موظفاً.
     * هذه الدالة تستجيب للطلب POST /employees/delete.
     */
    public function destroy() {
        // CD-009: حارس صلاحية صريح.
        Auth::enforce('employees.delete');

        // 2. التأكد من أن الطلب هو POST وأن ID موجود.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            $employeeModel = new Employee();

            // (ميزة إضافية) لا تسمح للمستخدم بحذف نفسه.
            // ملاحظة: هذا يتحقق من معرف المستخدم (user_id) وليس معرف الموظف (employee_id).
            // قد تحتاج إلى منطق أكثر تعقيدًا إذا كانا مختلفين.
            $user = Auth::user();
            if ($user && $user->employee_id === $id) {
                Flash::set('لا يمكنك حذف حسابك الخاص من قائمة الموظفين.', 'error');
                $this->redirect('/employees');
                return;
            }

            // ===> التصحيح هنا: جلب بيانات الموظف قبل الحذف <===
            $employeeData = $employeeModel->findById($id);

            // التحقق من أن الموظف موجود بالفعل قبل محاولة حذفه
            if (!$employeeData) {
                Flash::set('الموظف المطلوب غير موجود أو تم حذفه بالفعل.', 'error');
                $this->redirect('/employees');
                return;
            }

            // 5. استدعاء دالة الحذف في المودل.
            try {
                if ($employeeModel->delete($id)) {
                    // الآن يمكننا استخدام $employeeData بأمان
                    
                    // ===> تسجيل عملية حذف موظف <===
                    Audit::log('EMPLOYEE_DELETE', $id, 'employees', (array)$employeeData);
                
                    // ===> تعيين رسالة النجاح <===
                    Flash::set('تم حذف الموظف "' . htmlspecialchars($employeeData->full_name) . '" بنجاح.', 'success');
                
                    // إذا نجح الحذف، أعد التوجيه إلى قائمة الموظفين.
                    $this->redirect('/employees');
                    return; // استخدام return يوقف التنفيذ وهو أفضل من exit()
                } else {
                    // في حالة فشل الحذف لسبب غير متوقع (مثل مشكلة في قاعدة البيانات)
                    Flash::set('حدث خطأ أثناء محاولة حذف الموظف.', 'error');
                }
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    Flash::set('لا يمكن حذف الموظف "' . htmlspecialchars($employeeData->full_name) . '" لأنه مرتبط ببيانات أخرى (تقييمات أو سجلات). قم أولاً بحذف البيانات المرتبطة به.', 'error');
                } else {
                    Flash::set('حدث خطأ غير متوقع أثناء حذف الموظف.', 'error');
                }
            }
        }
        
        // إذا فشل أي شيء، أعد التوجيه.
        $this->redirect('/employees');
    }

      /**
     * =================================================================
     * دالة جديدة: عرض لوحة معلومات الأداء السنوي لموظف معين
     * =================================================================
     * @param int $employeeId - معرف الموظف
     * @param int $year - السنة المطلوبة
     */
    public function showEmployeeEvaluationsByYear($employeeId, $year) {
        // CD-009: حارس صلاحية صريح.
        Auth::enforce('reports.view.all');

        $employeeModel = new Employee();
        $employee = $employeeModel->findById($employeeId);

        if (!$employee) {
            Flash::set('الموظف المطلوب غير موجود.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        $evaluationModel = new Evaluation();
        $evaluations = $evaluationModel->getEvaluationsByEmployeeForYear($employeeId, $year);
        $comparisonStats = $evaluationModel->getComparisonStatsForYear($year, $employee->department_id);
        $radarData = $evaluationModel->getYearRadarData((int)$employeeId, (int)$year);
        $trendData = $evaluationModel->getYearTrendData((int)$employeeId, (int)$year);
        $templateBreakdown = $evaluationModel->getYearTemplateBreakdown((int)$employeeId, (int)$year);
        $sectionBreakdown = $evaluationModel->getYearSectionBreakdown((int)$employeeId, (int)$year);

        $data = [
            'pageTitle' => "أداء الموظف {$employee->full_name} لعام {$year}",
            'employee' => $employee,
            'year' => $year,
            'evaluations' => $evaluations,
            'comparisonStats' => $comparisonStats,
            'radarData' => $radarData,
            'trendData' => $trendData,
            'templateBreakdown' => $templateBreakdown,
            'sectionBreakdown' => $sectionBreakdown
        ];

        $this->view('employees/evaluations_by_year', $data);
    }

    /**
     * =================================================================
     * دالة جديدة: عرض السجل التقييمي لموظف معين (مجمع حسب السنة)
     * =================================================================
     * @param int $employeeId - معرف الموظف (يأتي من الرابط)
     */
    public function showEmployeeEvaluations($employeeId) {
        // CD-009: حارس صلاحية صريح.
        Auth::enforce('reports.view.all');

        $employeeModel = new Employee();
        $employee = $employeeModel->findById($employeeId);

        if (!$employee) {
            Flash::set('الموظف المطلوب غير موجود.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        $evaluationModel = new Evaluation();

        // ▼▼▼ بداية التعديل هنا ▼▼▼
        // استدعاء الدالة الجديدة بدلاً من القديمة
        $evaluationsByYear = $evaluationModel->getAnnualSummaryWithActualDate($employeeId);
        // ▲▲▲ نهاية التعديل ▲▲▲

        $data = [
            'pageTitle' => 'السجل التقييمي لـ ' . htmlspecialchars($employee->full_name),
            'employee' => $employee,
            'evaluationsByYear' => $evaluationsByYear
        ];

        $this->view('employees/evaluations_summary', $data);
    }

}
