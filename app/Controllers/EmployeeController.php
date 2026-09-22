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
    if (!Auth::can('employees.view')) {
        return (new ErrorController())->forbidden('employees.view');
    }
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
  /*  public function show($id) {
        // 1. التحقق من الصلاحية: هنا، الشرط أبسط. أي مستخدم مسجل دخوله يمكنه محاولة عرض ملف شخصي.
        // لاحقاً يمكن تعقيد هذا الشرط (مثلاً، المدير يرى فريقه فقط، والموظف يرى ملفه فقط).
        if (!Auth::check()) {
            // إذا لم يكن مسجلاً، أعد توجيهه لصفحة الدخول.
            header('Location: ' . APP_URL . '/login');
            exit();
        }
            

        // 2. إنشاء كائن من مودل الموظفين.
        $employeeModel = new Employee();
        // البحث عن الموظف المحدد باستخدام الـ ID.
        $employee = $employeeModel->findById($id);

        // 3. التحقق مما إذا تم العثور على الموظف.
        if (!$employee) {
            // إذا لم يتم العثور على الموظف في قاعدة البيانات، أرسل كود 404 (Not Found).
            http_response_code(404 );
            die('<h1>404 - غير موجود</h1><p>لم يتم العثور على الموظف المطلوب.</p>');
        }
        
        // 4. تجهيز البيانات للـ View.
        $data = [
            'pageTitle' => 'الملف الشخصي لـ ' . $employee->full_name,
            'employee' => $employee
        ];
        
        // 5. عرض الـ View الخاص بملف الموظف.
        $this->view('employees/show', $data);
    }*/
        public function show($id) {
    if (!Auth::check()) {
        header('Location: ' . APP_URL . '/login');
        exit();
    }

    $employeeModel = new Employee();
    $employee = $employeeModel->findById($id);

    if (!$employee) {
        // ===> التعديل هنا <===
        // بدلاً من طباعة رسالة بسيطة، نستدعي كنترولر الأخطاء لعرض صفحة 404 الكاملة.
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
  /*  public function create() {
        // 1. التحقق من الصلاحية: هل يملك المستخدم صلاحية 'employees.create'؟
        // هذه الصلاحية ممنوحة لـ 'Admin' و 'HR'.
        if (!Auth::can('employees.create')) {
            // 2. إذا لم يكن لديه الصلاحية، امنع الوصول.
            http_response_code(403 );
            die('<h1>403 - وصول ممنوع</h1><p>ليس لديك الصلاحية لإضافة موظف جديد.</p>');
        }
        
        // 3. إذا كان مصرحاً له، قم بتجهيز وعرض صفحة النموذج.
        $data = ['pageTitle' => 'إضافة موظف جديد'];
        $this->view('employees/create', $data);
    }*/

  /*  public function create() {
    // 1. التحقق من الصلاحية: هل يملك المستخدم صلاحية 'employees.create'؟
        // هذه الصلاحية ممنوحة لـ 'Admin' و 'HR'.
        if (!Auth::can('employees.create')) {
            // 2. إذا لم يكن لديه الصلاحية، امنع الوصول.
            http_response_code(403 );
            die('<h1>403 - وصول ممنوع</h1><p>ليس لديك الصلاحية لإضافة موظف جديد.</p>');
        }
        // ===> التعديل هنا: جلب البيانات للقوائم المنسدلة <===
        $branchModel = new Branch();
        $departmentModel = new Department();
        $employeeModel = new Employee();
        $positionModel = new Position(); // إنشاء كائن من مودل المناصب

        $data = [
            'pageTitle' => 'إضافة موظف جديد',
            'branches' => $branchModel->getAll(),
            'departments' => $departmentModel->getAll(),
            'managers' => $employeeModel->getAll(),
            'positions' => $positionModel->getAll() // جلب المناصب وإضافتها للبيانات
        ];
        $this->view('employees/create', $data);
    }*/
 public function create() {
        if (!Auth::can('employees.create')) {
            return (new ErrorController())->forbidden('employees.create');
        }
        
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
     * يخزن بيانات الموظف الجديد في قاعدة البيانات.
     * هذه الدالة تستجيب للطلب POST /employees.
     *//*
    public function store() {
        // 1. التحقق من الصلاحية مرة أخرى. هذا مهم لحماية نقطة النهاية (endpoint)
        // من الطلبات المباشرة (مثل استخدام أدوات مثل Postman).
        if (!Auth::can('employees.create')) {
            http_response_code(403 );
            die('<h1>403 - Forbidden</h1>');
        }

        // 2. التأكد من أن الطلب هو من نوع POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // إذا كان الطلب GET، أعد توجيهه لصفحة عرض النموذج.
            header('Location: ' . APP_URL . '/employees/create');
            exit();
        }

        // 3. (سيتم تحسينه لاحقاً) تنظيف وتجهيز البيانات القادمة من النموذج.
        $data = [
            'emp_code' => trim($_POST['emp_code']),
            'full_name' => trim($_POST['full_name']),
            'personal_email' => trim($_POST['personal_email']),
            'phone' => trim($_POST['phone']),
            'join_date' => trim($_POST['join_date']),
            'department_id' => (int)$_POST['department_id'],
            'branch_id' => (int)$_POST['branch_id'],
            'manager_id' => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        // 4. إنشاء كائن من مودل الموظفين.
        $employeeModel = new Employee();

        // 5. استدعاء دالة create في المودل لحفظ البيانات.
        if ($employeeModel->create($data)) {
            // إذا نجحت العملية، أعد التوجيه إلى صفحة قائمة الموظفين.
            // لاحقاً، سنضيف رسالة نجاح منبثقة.
               // ===> تسجيل الإجراء <===
            Audit::log('EMPLOYEE_CREATE', $newEmployeeId, 'employees', $data);
          // ===> تعيين رسالة النجاح <===
            Flash::set('تمت إضافة الموظف "' . $data['full_name'] . '" بنجاح.', 'success');
            header('Location: ' . APP_URL . '/employees');
            exit();
        } else {
            // إذا فشلت، اعرض رسالة خطأ.
            die('حدث خطأ أثناء إضافة الموظف.');
        }
    }*/
          /**
     * يخزن بيانات الموظف الجديد (النسخة المحدثة مع منطق التقييم).
     */
    
    /**
     * يخزن بيانات الموظف الجديد (النسخة النهائية المصححة).
     */
      /**
     * يخزن بيانات الموظف الجديد (النسخة النهائية مع التحقق من التكرار).
     */
    public function store() {
        if (!Auth::can('employees.create')) {
            return (new ErrorController())->forbidden('employees.create');
        }

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
        // يمكنك إضافة تحقق مماثل للاسم الكامل إذا أردت أن يكون فريدًا أيضًا

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

    /* public function store() {
          // 1. التحقق من الصلاحية مرة أخرى. هذا مهم لحماية نقطة النهاية (endpoint)
        // من الطلبات المباشرة (مثل استخدام أدوات مثل Postman).
        if (!Auth::can('employees.create')) {
            http_response_code(403 );
            die('<h1>403 - Forbidden</h1>');
        }

        // 2. التأكد من أن الطلب هو من نوع POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // إذا كان الطلب GET، أعد توجيهه لصفحة عرض النموذج.
            header('Location: ' . APP_URL . '/employees/create');
            exit();
        }
        // ===> التعديل هنا: إضافة position_id <===
        $data = [
            'emp_code' => trim($_POST['emp_code']),
            'full_name' => trim($_POST['full_name']),
            'personal_email' => trim($_POST['personal_email']),
            'phone' => trim($_POST['phone']),
            'join_date' => trim($_POST['join_date']),
            'department_id' => (int)$_POST['department_id'],
            'branch_id' => (int)$_POST['branch_id'],
            'manager_id' => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
            'position_id' => !empty($_POST['position_id']) ? (int)$_POST['position_id'] : null, // الحقل الجديد
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        $employeeModel = new Employee();
        $newEmployeeId = $employeeModel->create($data); // استلام الـ ID الجديد

        if ($newEmployeeId) {
          // ===> تسجيل عملية إنشاء موظف <===
            Audit::log('EMPLOYEE_CREATE', $newEmployeeId, 'employees', $data);
            Flash::set('تمت إضافة الموظف "' . $data['full_name'] . '" بنجاح.', 'success');
            header('Location: ' . APP_URL . '/employees');
            exit();
        } else {
            die('حدث خطأ أثناء إضافة الموظف.');
        }
    }*/
    /**
     * يعرض نموذج تعديل بيانات موظف.
     * هذه الدالة تستجيب للطلب GET /employees/{id}/edit.
     *
     * @param int $id - معرف الموظف.
     */
    // 1. التحقق من صلاحية 'employees.edit'.
         /**
     * يعرض نموذج تعديل بيانات موظف.
     *
     * @param int $id - معرف الموظف.
     *//*
    public function edit($id) {
        // 1. التحقق من الصلاحية.
        if (!Auth::can('employees.edit')) {
            http_response_code(403 );
            die('<h1>403 - وصول ممنوع</h1><p>ليس لديك الصلاحية لتعديل بيانات الموظفين.</p>');
        }

        
        // 2. إنشاء كائنات من كل المودلز التي نحتاجها.
        $employeeModel = new Employee();
        $branchModel = new Branch();
        $departmentModel = new Department(); // سنفترض وجود هذا المودل

        // 3. جلب كل البيانات المطلوبة للنموذج.
        $employee = $employeeModel->findById($id); // بيانات الموظف الحالي
        $branches = $branchModel->getAll();         // قائمة كل الفروع
        $departments = $departmentModel->getAll();   // قائمة كل الأقسام
        $managers = $employeeModel->getAll();        // قائمة كل الموظفين (كمدراء محتملين)

        // 4. التأكد من وجود الموظف.
        if (!$employee) {
            http_response_code(404 );
          // استدعاء ErrorController 
            return (new ErrorController())->forbidden('employees.edit');
        }

        // 5. تجهيز كل البيانات وتمريرها إلى الـ View.
        $data = [
            'pageTitle' => 'تعديل موظف',
            'employee' => $employee,
            'branches' => $branches,
            'departments' => $departments,
            'managers' => $managers
        ];

        // 6. عرض صفحة النموذج.
        $this->view('employees/edit', $data);
    }*/
        public function edit($id) {
        // 1. التحقق من الصلاحية.
        if (!Auth::can('employees.edit')) {
            http_response_code(403 );
            die('<h1>403 - وصول ممنوع</h1><p>ليس لديك الصلاحية لتعديل بيانات الموظفين.</p>');
        }
        $employeeModel = new Employee();
        $branchModel = new Branch();
        $departmentModel = new Department();
        $positionModel = new Position(); // ===> إنشاء كائن من مودل المناصب

        $employee = $employeeModel->findById($id);
        // 4. التأكد من وجود الموظف.
        if (!$employee) {
            http_response_code(404 );
          // استدعاء ErrorController 
            return (new ErrorController())->forbidden('employees.edit');
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
     * يعالج البيانات المرسلة من نموذج التعديل ويحفظها.
     * هذه الدالة تستجيب للطلب POST /employees/update.
     */
  /*  public function update() {
        // 1. التحقق من الصلاحية.
        if (!Auth::can('employees.edit')) {
            http_response_code(403 );
            die('<h1>403 - Forbidden</h1>');
        }

        // 2. التأكد من أن الطلب هو POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/employees');
            exit();
        }

        // 3. تجهيز البيانات القادمة من النموذج.
        $data = [
            'emp_code' => trim($_POST['emp_code']),
            'full_name' => trim($_POST['full_name']),
            'personal_email' => trim($_POST['personal_email']),
            'phone' => trim($_POST['phone']),
            'join_date' => trim($_POST['join_date']),
            'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
            'branch_id' => !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null,
            'manager_id' => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        // الحصول على معرف الموظف من الحقل المخفي.
        $id = (int)$_POST['id'];

        // 4. استدعاء دالة update في المودل.
        $employeeModel = new Employee();
        if ($employeeModel->update($id, $data)) {
         // ===> تسجيل الإجراء <===
            Audit::log('EMPLOYEE_UPDATE', $id, 'employees', $data);
          
          // ===> تعيين رسالة النجاح <===
            Flash::set('تم تحديث بيانات الموظف "' . $data['full_name'] . '" بنجاح.', 'success');

            // إذا نجح، أعد التوجيه إلى قائمة الموظفين.
            header('Location: ' . APP_URL . '/employees');
            exit();
        } else {
            // إذا فشل، اعرض رسالة خطأ.
           return (new ErrorController())->forbidden('employees.edit');
        }
    }*/
              /**
     * يعالج البيانات المرسلة من نموذج التعديل ويحفظها (النسخة المحدثة).
     */
    public function update() {
        if (!Auth::can('employees.edit')) {
            return (new ErrorController())->forbidden('employees.edit');
        }

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
  /*  public function destroy() {
        // 1. التحقق من الصلاحية.
        if (!Auth::can('employees.delete')) {
            http_response_code(403 );
             return (new ErrorController())->forbidden('employees.delete');
        }

        // 2. التأكد من أن الطلب هو POST وأن ID موجود.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            // 3. الحصول على ID الموظف.
            $id = (int)$_POST['id'];
            
            // 4. إنشاء كائن من المودل.
            $employeeModel = new Employee();

            // (ميزة إضافية) لا تسمح للمستخدم بحذف نفسه.
            if ($id === Auth::id()) {
                // لاحقاً سنحول هذه إلى رسالة خطأ جميلة.
                die('لا يمكنك حذف حسابك الخاص.');
            }

            // 5. استدعاء دالة الحذف في المودل.
            if ($employeeModel->delete($id)) {
                // ===> تسجيل عملية حذف موظف <===
            Audit::log('EMPLOYEE_DELETE', $id, 'employees', (array)$employeeData);
            
            // ===> تعيين رسالة النجاح <===
            Flash::set('تم حذف الموظف "' . $employeeData->full_name . '" بنجاح.', 'success');
            
            // إذا نجح الحذف، أعد التوجيه إلى قائمة الموظفين.
                header('Location: ' . APP_URL . '/employees');
                exit();
            }
        }
        // إذا فشل أي شيء، أعد التوجيه.
        header('Location: ' . APP_URL . '/employees');
        exit();
    }*/
           /**
     * يحذف موظفاً.
     * هذه الدالة تستجيب للطلب POST /employees/delete.
     */
    public function destroy() {
        // 1. التحقق من الصلاحية.
        if (!Auth::can('employees.delete')) {
            return (new ErrorController())->forbidden('employees.delete');
        }

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
     * دالة جديدة: عرض السجل التقييمي لموظف معين (مجمع حسب السنة)
     * =================================================================
     * @param int $employeeId - معرف الموظف (يأتي من الرابط)
     */
  /*  public function showEmployeeEvaluations($employeeId) {
        Auth::can('reports.view.all'); // صلاحية لعرض هذه الصفحة

        // جلب بيانات الموظف الأساسية
        $employeeModel = new Employee();
        $employee = $employeeModel->findById($employeeId);

        if (!$employee) {
            Flash::set('الموظف المطلوب غير موجود.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        // جلب ملخص التقييمات السنوية
        $evaluationModel = new Evaluation();
        $evaluationsByYear = $evaluationModel->getEvaluationsByEmployeeGroupedByYear($employeeId);

        $data = [
            'pageTitle' => 'السجل التقييمي لـ ' . htmlspecialchars($employee->full_name),
            'employee' => $employee,
            'evaluationsByYear' => $evaluationsByYear
        ];

        // سنقوم بإنشاء هذا الملف في الخطوة التالية
        $this->view('employees/evaluations_summary', $data);
    }
*/
      /**
     * =================================================================
     * دالة جديدة: عرض لوحة معلومات الأداء السنوي لموظف معين
     * =================================================================
     * @param int $employeeId - معرف الموظف
     * @param int $year - السنة المطلوبة
     */
    /**
     * =================================================================
     * يعرض لوحة معلومات الأداء التحليلية لعام محدد
     * =================================================================
     * @param int $employeeId
     * @param int $year
     */
     /**
     * =================================================================
     * دالة جديدة: عرض لوحة معلومات الأداء السنوي لموظف معين
     * =================================================================
     * @param int $employeeId - معرف الموظف
     * @param int $year - السنة المطلوبة
     */
    public function showEmployeeEvaluationsByYear($employeeId, $year) {
        Auth::can('reports.view.all');

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
    }/*
    public function showEmployeeEvaluationsByYear($employeeId, $year) {
        Auth::can('reports.view.all');

        $employeeModel = new Employee();
        $employee = $employeeModel->findById($employeeId);

        if (!$employee) {
            Flash::set('الموظف المطلوب غير موجود.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        $evaluationModel = new Evaluation();
        
        // *** استدعاء الدالة التحليلية مع تمرير السنة ***
        $analysis = $evaluationModel->analyzeEmployeePerformance($employeeId, $year);

        // جلب قائمة التقييمات الفردية لهذا العام
        $evaluations = $evaluationModel->getEvaluationsByEmployeeForYear($employeeId, $year);

        $data = [
            'pageTitle' => "تحليل أداء {$employee->full_name} لعام {$year}",
            'employee' => $employee,
            'year' => $year,
            'analysis' => $analysis,
            'evaluations' => $evaluations
        ];

        // عرض واجهة لوحة المعلومات التحليلية
        $this->view('employees/performance_dashboard', $data);
    }
*/
      /**
     * =================================================================
     * يعرض لوحة معلومات الأداء التحليلية للموظف (النسخة المحدثة)
     * =================================================================
     * @param int $employeeId
     */
  /*public function showEmployeeEvaluations($employeeId) {
        Auth::can('reports.view.all');

        $employeeModel = new Employee();
        $employee = $employeeModel->findById($employeeId);

        if (!$employee) {
            Flash::set('الموظف المطلوب غير موجود.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        $evaluationModel = new Evaluation();
        
        // *** استدعاء الدالة التحليلية الجديدة ***
        $analysis = $evaluationModel->analyzeEmployeePerformance($employeeId);

        // جلب قائمة التقييمات الفردية لعرضها في جدول
        $evaluations = $evaluationModel->getEvaluationsByEmployeeForYear($employeeId, null); // null لجلب كل السنوات

        $data = [
            'pageTitle' => 'تحليل أداء: ' . htmlspecialchars($employee->full_name),
            'employee' => $employee,
            'analysis' => $analysis, // تمرير مصفوفة التحليل
            'evaluations' => $evaluations
        ];

        // سنقوم بإنشاء/تحديث هذا الملف في الخطوة التالية
        $this->view('employees/performance_dashboard', $data);
    }*/
         /**
     * =================================================================
     * دالة جديدة: عرض السجل التقييمي لموظف معين (مجمع حسب السنة)
     * =================================================================
     * @param int $employeeId - معرف الموظف (يأتي من الرابط)
     */
  /*  public function showEmployeeEvaluations($employeeId) {
        Auth::can('reports.view.all'); // صلاحية لعرض هذه الصفحة

        // جلب بيانات الموظف الأساسية
        $employeeModel = new Employee();
        $employee = $employeeModel->findById($employeeId);

        if (!$employee) {
            Flash::set('الموظف المطلوب غير موجود.', 'error');
            $this->redirect('/evaluations');
            return;
        }

        // جلب ملخص التقييمات السنوية
        $evaluationModel = new Evaluation();
        $evaluationsByYear = $evaluationModel->getEvaluationsByEmployeeGroupedByYear($employeeId);

        $data = [
            'pageTitle' => 'السجل التقييمي لـ ' . htmlspecialchars($employee->full_name),
            'employee' => $employee,
            'evaluationsByYear' => $evaluationsByYear
        ];

        // سنقوم بإنشاء هذا الملف في الخطوة التالية
        $this->view('employees/evaluations_summary', $data);
    }*/
        // في ملف: app/Controllers/EmployeeController.php

// ... (داخل كلاس EmployeeController)

public function showEmployeeEvaluations($employeeId) {
    Auth::can('reports.view.all');

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

// ... (بقية دوال الكنترولر)

}
