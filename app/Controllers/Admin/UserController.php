<?php
// نحدد الـ Namespace ليعكس مكان الملف داخل مجلد Admin.
namespace App\Controllers\Admin;

// استيراد الفئات التي سنحتاجها.
use App\Core\Controller;
use App\Core\Auth;
use App\Models\User; // مودل المستخدمين لجلب البيانات.
use App\Models\Role; // مودل الأدوار لجلب قائمة الأدوار للنافذة المنبثقة.

/**
 * UserController
 * مسؤول عن إدارة المستخدمين وأدوارهم من قبل مدير النظام.
 */
class UserController extends Controller {

    /**
     * دالة البناء (Constructor)
     * تعمل كحارس بوابة لهذه الوحدة بأكملها.
     */
    public function __construct() {
        // البند 7: حارس الوصول أصبح قائمًا على الصلاحيات بدل دور "Admin" وحده.
        // `Auth::can()` يتجاوز الفحص تلقائيًا لمن يملك الصلاحية (bypass عبر `Auth::isAdmin()`).
        // حارس كل عملية (create/edit/delete) موضوع داخل دالتها لتحديد العملية المسموحة.
        Auth::enforce('users.view');
    }

    /**
     * يعرض صفحة قائمة المستخدمين.
     * هذه الدالة تستجيب للطلب GET /admin/users.
     */
    public function index() {
        // إنشاء كائنات من المودلز التي نحتاجها.
        $userModel = new User();
        $roleModel = new Role(); // سنحتاجه لملء قائمة الأدوار في نافذة التعديل.

        // تجهيز البيانات التي سيتم تمريرها إلى الـ View.
        $data = [
            'pageTitle' => 'إدارة المستخدمين',
            'users' => $userModel->getAllWithDetails(), // دالة جديدة سننشئها في المودل.
            'roles' => $roleModel->getAll()             // دالة جديدة سننشئها في المودل.
        ];

        // عرض الـ View الخاص بإدارة المستخدمين.
        $this->view('admin/users/index', $data);
    }
      /**
     * ==================================================
     *           ===>  الدالة الجديدة هنا  <===
     * ==================================================
     * 
     * يحدّث دور مستخدم معين.
     * هذه الدالة تستجيب للطلب POST /admin/users/update-role.
     */
    public function updateRole() {
        // البند 7: تغيير دور مستخدم = عملية تعديل، تتطلب صلاحية `users.edit`.
        Auth::enforce('users.edit');
        // التأكد من أن الطلب هو POST وأن البيانات المطلوبة موجودة.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role_id'])) {
            
            // 1. الحصول على البيانات من الطلب.
            $userId = (int)$_POST['user_id'];
            $roleId = (int)$_POST['role_id'];

            // 2. إنشاء كائن من مودل المستخدم.
            $userModel = new \App\Models\User();
            
            // (ميزة أمان إضافية) لا تسمح للمدير بتغيير دوره بنفسه لمنع قفل الحساب.
            if ($userId === \App\Core\Auth::id()) {
                \App\Core\Flash::set('لا يمكنك تغيير دور حسابك الخاص.', 'warning');
                header('Location: ' . APP_URL . '/admin/users');
                exit();
            }

            // بند 3: منع خفض آخر مدير نظام نشط إلى دور غير إداري.
            if ($userModel->isLastActiveAdmin($userId) && !in_array($roleId, \App\Core\Auth::adminRoleIds(), true)) {
                \App\Core\Audit::log('LAST_ADMIN_PROTECTED', $userId, 'users', [
                    'reason' => 'demote',
                    'new_role_id' => $roleId,
                    'actor_id' => \App\Core\Auth::id(),
                ]);
                \App\Core\Auth::notifyAdmins(
                    'محاولة خفض آخر مدير نظام',
                    'رُفضت محاولة تغيير دور آخر مدير نظام نشط إلى دور غير إداري.'
                );
                \App\Core\Flash::set('لا يمكن خفض آخر مدير نظام نشط إلى دور غير إداري.', 'danger');
                header('Location: ' . APP_URL . '/admin/users');
                exit();
            }

            // 3. استدعاء دالة تحديث الدور في المودل.
            // البند 8: جلب الدور القديم قبل التحديث (لأن User::updateRole يحذف كل الأدوار ثم يُدرج الجديد).
            $dbAudit = \App\Core\Database::getInstance();
            $dbAudit->query("SELECT role_id FROM user_roles WHERE user_id = :user_id LIMIT 1");
            $dbAudit->bind(':user_id', $userId);
            $oldRoleRow = $dbAudit->fetch();
            $oldRoleId = $oldRoleRow ? (int)$oldRoleRow->role_id : null;

            try {
                if ($userModel->updateRole($userId, $roleId)) {
                    // 4. في حالة النجاح، سجل الإجراء (البند 8: القيم القديمة/الجديدة) وعيّن رسالة نجاح.
                    \App\Core\Audit::log('USER_ROLE_UPDATE', $userId, 'users', [
                        'old' => ['role_id' => $oldRoleId],
                        'new' => ['role_id' => (int)$roleId],
                    ]);
                    \App\Core\Flash::set('تم تحديث دور المستخدم بنجاح.', 'success');
                    $this->warnIfSingleActiveAdmin();
                } else {
                    // OOS-13: فشل التحديث بلا استثناء (دور غير موجود) — بعد rollback داخلي.
                    // يُسجَّل تدقيق الفشل بالشكل الموقّعي، ويعطى المستخدم رسالة محددة.
                    \App\Core\Audit::log('USER_ROLE_UPDATE_FAILED', $userId, 'users', [
                        'role_id' => (int)$roleId,
                        'reason' => 'invalid_role_or_transaction_failed',
                    ]);
                    // 5. في حالة الفشل، عيّن رسالة خطأ واضحة.
                    \App\Core\Flash::set('تعذّر تحديث دور المستخدم: الدور المحدد غير موجود.', 'danger');
                }
            } catch (\Exception $e) {
                // OOS-13: خطأ غير متوقع بعد rollback — يُسجَّل تدقيق الفشل ثم يُعاد إلقاء الاستثناء.
                \App\Core\Audit::log('USER_ROLE_UPDATE_FAILED', $userId, 'users', [
                    'role_id' => (int)$roleId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        // 6. في كل الحالات، أعد التوجيه إلى صفحة قائمة المستخدمين.
        header('Location: ' . APP_URL . '/admin/users');
        exit();
    }
    /**
 * ==================================================
 *           ===>  الدالة الجديدة الأولى هنا  <===
 * ==================================================
 * 
 * تعرض صفحة نموذج إنشاء مستخدم جديد.
 */
public function create() {
    // البند 7: عرض نموذج الإنشاء يتطلب صلاحية `users.create`.
    Auth::enforce('users.create');
    // نحتاج إلى قائمة الموظفين والأدوار لملء القوائم المنسدلة في النموذج
    $userModel = new \App\Models\User();
    $roleModel = new \App\Models\Role();
    $employeeModel = new \App\Models\Employee(); // سنحتاج مودل الموظفين

    $data = [
        'pageTitle' => 'إضافة مستخدم جديد',
        'roles' => $roleModel->getAll(),
        // دالة جديدة لجلب الموظفين الذين ليس لديهم حساب مستخدم بعد
        'employees' => $employeeModel->getUnlinkedEmployees() 
    ];

    $this->view('admin/users/create', $data);
}

/**
 * ==================================================
 *           ===>  الدالة الجديدة الثانية هنا  <===
 * ==================================================
 * 
 * تعالج بيانات نموذج إنشاء المستخدم الجديد وتحفظه.
 */
public function store() {
    // البند 7: إنشاء مستخدم يتطلب صلاحية `users.create`.
    Auth::enforce('users.create');
    // التأكد من أن الطلب هو POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . APP_URL . '/admin/users/create');
        exit();
    }

    // 1. جمع البيانات من النموذج
    $data = [
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email']),
        'password' => $_POST['password'],
        'password_confirm' => $_POST['password_confirm'],
        'role_id' => (int)$_POST['role_id'],
        'employee_id' => !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null,
    ];

    // 2. التحقق من صحة البيانات (Validation)
    if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
        \App\Core\Flash::set('الرجاء ملء جميع الحقول الإلزامية.', 'danger');
        header('Location: ' . APP_URL . '/admin/users/create');
        exit();
    }

    if ($data['password'] !== $data['password_confirm']) {
        \App\Core\Flash::set('كلمتا المرور غير متطابقتين.', 'danger');
        header('Location: ' . APP_URL . '/admin/users/create');
        exit();
    }

    // بند 11: فرض سياسة كلمة المرور قبل الإنشاء (رسائل موحدة من Auth)
    $policy = \App\Core\Auth::validatePasswordPolicy($data['password']);
    if (!$policy['ok']) {
        foreach ($policy['errors'] as $error) {
            \App\Core\Flash::set($error, 'danger');
        }
        header('Location: ' . APP_URL . '/admin/users/create');
        exit();
    }

    // بند 10: البوابات المرجعية قبل الاستدعاء — تمنع تمرير قيم غير موجودة أو مرتبطة إلى createUser.
    $roleGate = new \App\Models\Role();
    if (!$roleGate->findById($data['role_id'])) {
        \App\Core\Audit::log('USER_CREATE_BLOCKED_INVALID_ROLE', null, 'users', [
            'username' => $data['username'],
            'role_id' => $data['role_id'],
        ]);
        \App\Core\Flash::set('الدور المحدد غير موجود.', 'danger');
        header('Location: ' . APP_URL . '/admin/users/create');
        exit();
    }

    // الموظف اختياري: البوابتان التاليتان تعملان فقط عند إرسال employee_id.
    if ($data['employee_id'] !== null) {
        $employeeGate = new \App\Models\Employee();
        if (!$employeeGate->findById($data['employee_id'])) {
            \App\Core\Audit::log('USER_CREATE_BLOCKED_INVALID_EMPLOYEE', null, 'users', [
                'username' => $data['username'],
                'employee_id' => $data['employee_id'],
            ]);
            \App\Core\Flash::set('الموظف المحدد غير موجود.', 'danger');
            header('Location: ' . APP_URL . '/admin/users/create');
            exit();
        }

        // الموظف موجود، لكنه مرتبط بحساب مستخدم آخر (users.employee_id فريد).
        $dbGate = \App\Core\Database::getInstance();
        $dbGate->query("SELECT id FROM users WHERE employee_id = :emp_id LIMIT 1");
        $dbGate->bind(':emp_id', $data['employee_id']);
        $linkedUser = $dbGate->fetch();
        if ($linkedUser) {
            \App\Core\Audit::log('USER_CREATE_BLOCKED_EMPLOYEE_TAKEN', null, 'users', [
                'username' => $data['username'],
                'employee_id' => $data['employee_id'],
                'linked_user_id' => (int)$linkedUser->id,
            ]);
            \App\Core\Flash::set('هذا الموظف مرتبط بحساب مستخدم آخر.', 'danger');
            header('Location: ' . APP_URL . '/admin/users/create');
            exit();
        }
    }

    // 3. إنشاء كائن من مودل المستخدم
    $userModel = new \App\Models\User();

    // 4. استدعاء دالة الإنشاء في المودل
    $newUserId = $userModel->createUser($data);

    if ($newUserId) {
        // في حالة النجاح
        \App\Core\Audit::log('USER_CREATE', $newUserId, 'users', ['username' => $data['username'], 'role_id' => $data['role_id']]);
        \App\Core\Flash::set('تم إنشاء المستخدم الجديد بنجاح.', 'success');
        header('Location: ' . APP_URL . '/admin/users'); // إعادة توجيه لقائمة المستخدمين
    } else {
        // في حالة الفشل (غالباً بسبب اسم مستخدم أو بريد إلكتروني مكرر)
        \App\Core\Flash::set('فشل إنشاء المستخدم. قد يكون اسم المستخدم أو البريد الإلكتروني مستخدماً بالفعل.', 'danger');
        header('Location: ' . APP_URL . '/admin/users/create'); // البقاء في صفحة الإنشاء
    }
    exit();
}
/**
 * ==================================================
 *           ===>  الدالة الجديدة هنا  <===
 * ==================================================
 * 
 * تقوم بتغيير حالة المستخدم بين 'active' و 'suspended'.
 */
public function toggleStatus() {
    // البند 7: تفعيل/تعطيل مستخدم = عملية تعديل، تتطلب صلاحية `users.edit`.
    Auth::enforce('users.edit');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json = file_get_contents('php://input');
        $data = json_decode($json);

        if (!isset($data->user_id)) {
            http_response_code(400 );
            echo json_encode(['success' => false, 'message' => 'بيانات ناقصة.']);
            exit();
        }

        $userId = (int)$data->user_id;
        $userModel = new \App\Models\User();

        // لا تسمح للمدير بتعليق حسابه الخاص
        if ($userId === \App\Core\Auth::id()) {
            http_response_code(403 );
            echo json_encode(['success' => false, 'message' => 'لا يمكنك تعليق حسابك الخاص.']);
            exit();
        }

        // بند 3: منع تعليق آخر مدير نظام نشط.
        if ($userModel->isLastActiveAdmin($userId)) {
            \App\Core\Audit::log('LAST_ADMIN_PROTECTED', $userId, 'users', [
                'reason' => 'suspend',
                'actor_id' => \App\Core\Auth::id(),
            ]);
            \App\Core\Auth::notifyAdmins(
                'محاولة تعليق آخر مدير نظام',
                'رُفضت محاولة تعليق آخر مدير نظام نشط.'
            );
            http_response_code(403 );
            echo json_encode(['success' => false, 'message' => 'لا يمكن تعليق آخر مدير نظام نشط.']);
            exit();
        }

        // البند 8: التقاط الحالة القديمة قبل التغيير (findById يُرجع u.* ومنها status).
        $userBefore = $userModel->findById($userId);
        $oldStatus = $userBefore ? $userBefore->status : null;

        $newStatus = $userModel->toggleStatus($userId);

        if ($newStatus) {
            $action = ($newStatus === 'active') ? 'USER_ACTIVATED' : 'USER_SUSPENDED';
            // البند 8: تسجيل القيم القديمة/الجديدة.
            \App\Core\Audit::log($action, $userId, 'users', [
                'old' => ['status' => $oldStatus],
                'new' => ['status' => $newStatus],
            ]);
            $this->warnIfSingleActiveAdmin();
            echo json_encode(['success' => true, 'newStatus' => $newStatus, 'message' => 'تم تغيير حالة المستخدم بنجاح.']);
        } else {
            http_response_code(500 );
            echo json_encode(['success' => false, 'message' => 'فشل تغيير حالة المستخدم.']);
        }
        exit();
    }
}


public function edit($id) {
    // البند 7: عرض نموذج التعديل يتطلب صلاحية `users.edit`.
    Auth::enforce('users.edit');
    $userModel = new \App\Models\User();
    $employeeModel = new \App\Models\Employee();
    
    $user = $userModel->findById($id);
    if (!$user) {
        // معالجة حالة عدم العثور على المستخدم
        return (new \App\Controllers\ErrorController())->notFound();
    }

    $data = [
        'pageTitle' => 'تعديل المستخدم',
        'user' => $user,
        // جلب كل الموظفين غير المرتبطين + الموظف المرتبط بهذا المستخدم (إن وجد)
        'employees' => $employeeModel->getAvailableEmployeesForUser($user->employee_id)
    ];
    $this->view('admin/users/edit', $data);
}

public function update() {
    // البند 7: تحديث بيانات مستخدم يتطلب صلاحية `users.edit`.
    Auth::enforce('users.edit');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'id' => (int)$_POST['id'],
            'username' => trim($_POST['username']),
            'email' => trim($_POST['email']),
            'employee_id' => !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null,
      'password' => $_POST['password'] // جلب كلمة المرور الجديدة
          ];
   // 2. التحقق من كلمة المرور (فقط إذا لم تكن فارغة)
        if (!empty($data['password'])) {
            if ($data['password'] !== $_POST['password_confirm']) {
                \App\Core\Flash::set('كلمتا المرور الجديدتان غير متطابقتين.', 'danger');
                // إعادة التوجيه إلى نفس صفحة التعديل
                header('Location: ' . APP_URL . '/admin/users/edit/' . $data['id']);
                exit();
            }
            // بند 11: فرض سياسة كلمة المرور قبل التعديل (رسائل موحدة من Auth)
            $policy = \App\Core\Auth::validatePasswordPolicy($data['password']);
            if (!$policy['ok']) {
                foreach ($policy['errors'] as $error) {
                    \App\Core\Flash::set($error, 'danger');
                }
                header('Location: ' . APP_URL . '/admin/users/edit/' . $data['id']);
                exit();
            }
        }
           // 3. استدعاء المودل للتحديث
        $userModel = new \App\Models\User();
        if ($userModel->updateUser($data)) {
            \App\Core\Audit::log('USER_UPDATE', $data['id'], 'users', $data);
            \App\Core\Flash::set('تم تحديث بيانات المستخدم بنجاح.', 'success');
        } else {
            \App\Core\Flash::set('فشل تحديث البيانات. قد يكون اسم المستخدم أو البريد مكرراً.', 'danger');
        }
        header('Location: ' . APP_URL . '/admin/users');
        exit();
    }
}
// دالة حذف مستخدم اي تدميرة
public function destroy() {
    // البند 7: حذف مستخدم يتطلب صلاحية `users.delete`.
    Auth::enforce('users.delete');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)$_POST['id'];

        // لا تسمح للمدير بحذف حسابه
        if ($id === \App\Core\Auth::id()) {
            \App\Core\Flash::set('لا يمكنك حذف حسابك الخاص.', 'danger');
            header('Location: ' . APP_URL . '/admin/users');
            exit();
        }

        $userModel = new \App\Models\User();

        // بند 3: منع حذف آخر مدير نظام نشط.
        if ($userModel->isLastActiveAdmin($id)) {
            \App\Core\Audit::log('LAST_ADMIN_PROTECTED', $id, 'users', [
                'reason' => 'delete',
                'actor_id' => \App\Core\Auth::id(),
            ]);
            \App\Core\Auth::notifyAdmins(
                'محاولة حذف آخر مدير نظام',
                'رُفضت محاولة حذف آخر مدير نظام نشط. يجب إبقاء مدير نظام نشط واحد على الأقل.'
            );
            \App\Core\Flash::set('لا يمكن حذف آخر مدير نظام نشط.', 'danger');
            header('Location: ' . APP_URL . '/admin/users');
            exit();
        }

        // البند 12-b: منع حذف مستخدم ترتبط به سجلات لا تقبل الحذف التلقائي.
        //
        // قياس كل قيود المفاتيح الأجنبية المؤدية إلى users (لا افتراض):
        //   CASCADE (تُنظَّف ذاتيًا، 5): user_roles، notifications، admin_notifications،
        //                                csrf_tokens، password_history
        //   SET NULL (9):              audit_logs، backup_logs، backup_settings،
        //                                course_catalog، course_evaluations،
        //                                evaluation_schedules، form_templates (×2)،
        //                                training_courses، workshop_catalog
        //   RESTRICT (تمنع الحذف، 2):  evaluations.created_by_user_id
        //                                evaluation_import_batches.uploaded_by_user_id
        // بلا هذه البوابة كان الحذف ينتهي بخطأ قاعدة بيانات 1452 بدل رسالة مفهومة.
        // ملاحظة: evaluations لا ترتبط بـ users إلا عبر created_by_user_id؛ المُقيَّم
        // نفسه يُخزَّن كـ evaluator_employee_id (موظف) مع لقطة نصية للاسم والمسمى.
        $dbGate = \App\Core\Database::getInstance();
        // placeholders منفصلة عمدًا: PDO بوضع EMULATE_PREPARES=false لا يقبل
        // تكرار نفس الاسم في الجملة الواحدة.
        $dbGate->query("SELECT
                            (SELECT COUNT(*) FROM evaluations WHERE created_by_user_id = :id1) AS evals,
                            (SELECT COUNT(*) FROM evaluation_import_batches WHERE uploaded_by_user_id = :id2) AS batches");
        $dbGate->bind(':id1', $id);
        $dbGate->bind(':id2', $id);
        $refs = $dbGate->fetch();
        $evalCount = (int)($refs->evals ?? 0);
        $batchCount = (int)($refs->batches ?? 0);

        if ($evalCount > 0 || $batchCount > 0) {
            $parts = [];
            if ($evalCount > 0) {
                $parts[] = $evalCount === 1 ? 'تقييم واحد' : $evalCount . ' تقييمات';
            }
            if ($batchCount > 0) {
                $parts[] = $batchCount === 1 ? 'دفعة استيراد واحدة' : $batchCount . ' دفعات استيراد';
            }
            \App\Core\Audit::log('USER_DELETE_BLOCKED_REFERENCES', $id, 'users', [
                'evaluations_count'      => $evalCount,
                'import_batches_count'   => $batchCount,
                'actor_id'               => \App\Core\Auth::id(),
            ]);
            \App\Core\Flash::set(
                'لا يمكن حذف المستخدم لارتباطه بـ ' . implode(' و', $parts)
                . '. انقل السجلات المرتبطة إلى مستخدم آخر أو احذفها أولًا.',
                'danger'
            );
            header('Location: ' . APP_URL . '/admin/users');
            exit();
        }

        if ($userModel->deleteUser($id)) {
            \App\Core\Audit::log('USER_DELETE', $id, 'users');
            \App\Core\Flash::set('تم حذف المستخدم بنجاح.', 'success');
            $this->warnIfSingleActiveAdmin();
        } else {
            \App\Core\Flash::set('فشل حذف المستخدم.', 'danger');
        }
        header('Location: ' . APP_URL . '/admin/users');
        exit();
    }
}
/**
 * ==================================================
 *           ===>  الدالة الجديدة هنا  <===
 * ==================================================
 * 
 * تقوم بإعادة تعيين كلمة مرور مستخدم وإنشاء كلمة مرور عشوائية جديدة.
 */
public function resetPassword() {
    // البند 7: إعادة تعيين كلمة المرور = عملية تعديل، تتطلب صلاحية `users.edit`.
    Auth::enforce('users.edit');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json = file_get_contents('php://input');
        $data = json_decode($json);

        if (!isset($data->user_id)) {
            http_response_code(400 );
            echo json_encode(['success' => false, 'message' => 'بيانات ناقصة.']);
            exit();
        }

        $userId = (int)$data->user_id;
        $userModel = new \App\Models\User();

        // لا تسمح للمدير بإعادة تعيين كلمة مروره بهذه الطريقة
        if ($userId === \App\Core\Auth::id()) {
            http_response_code(403 );
            echo json_encode(['success' => false, 'message' => 'لا يمكنك استخدام هذه الميزة على حسابك.']);
            exit();
        }

        // 1. إنشاء كلمة مرور عشوائية قوية مطابقة لسياسة بند 11
        $newPassword = \App\Core\Auth::generateStrongPassword(12);

        // 2. تحديث كلمة المرور في قاعدة البيانات (بند 9: إلزام التغيير عند الدخول التالي)
        if ($userModel->updatePassword($userId, $newPassword, true)) {
            // 3. تسجيل الإجراء
            \App\Core\Audit::log('USER_PASSWORD_RESET', $userId, 'users');
            
            // 4. إرجاع كلمة المرور الجديدة إلى الواجهة الأمامية
            echo json_encode(['success' => true, 'newPassword' => $newPassword, 'message' => 'تم إعادة تعيين كلمة المرور بنجاح. يجب على المستخدم تغييرها عند الدخول التالي.']);
        } else {
            http_response_code(500 );
            $failMsg = !empty($userModel->lastPasswordErrors)
                ? implode(' ', $userModel->lastPasswordErrors)
                : 'فشل إعادة تعيين كلمة المرور.';
            echo json_encode(['success' => false, 'message' => $failMsg]);
        }
        exit();
    }
    }

    /**
     * بند 3: تنبيه المشرفين عند هبوط عدد مدراء النظام النشطين إلى واحد.
     */
    private function warnIfSingleActiveAdmin() {
        $userModel = new \App\Models\User();
        if ($userModel->activeAdminCount() === 1) {
            \App\Core\Auth::notifyAdmins(
                'تنبيه: مدير نظام واحد فقط',
                'لم يبقَ سوى مدير نظام نشط واحد. يُنصح بإضافة مدير نظام آخر لتفادي فقدان الوصول.'
            );
        }
    }
}

