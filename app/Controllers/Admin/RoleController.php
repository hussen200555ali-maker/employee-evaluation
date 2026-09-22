<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Role; // استيراد المودل

class RoleController extends Controller {

    public function __construct() {
        // حماية كل دوال هذه الوحدة
        Auth::can('roles.manage');
    }

    /**
     * ==================================================
     *           ===>  الدالة المحدّثة هنا  <===
     * ==================================================
     * 
     * يعرض صفحة إدارة الأدوار والصلاحيات.
     */
    public function index() {
        // 1. إنشاء كائن من مودل الأدوار.
        $roleModel = new Role();

        // 2. جلب كل الأدوار.
        $roles = $roleModel->getAll();
        
        // 3. جلب كل الصلاحيات المتاحة في النظام (مصنفة).
        $allPermissions = $roleModel->getAllPermissions();

        // 4. المرور على كل دور لجلب صلاحياته الخاصة.
        foreach ($roles as $role) {
            // إضافة خاصية جديدة لكل كائن دور تحتوي على مصفوفة IDs صلاحياته.
            $role->permissions = $roleModel->getRolePermissions($role->id);
        }

        // 5. تجهيز البيانات للـ View.
        $data = [
            'pageTitle' => 'إدارة الأدوار والصلاحيات',
            'roles' => $roles,
            'allPermissions' => $allPermissions
        ];

        // 6. عرض الـ View وتمرير البيانات إليه.
        $this->view('admin/roles/index', $data);
    }

    /**
 * ==================================================
 *           ===>  الدالة الجديدة هنا  <===
 * ==================================================
 * 
 * تحدّث صلاحية واحدة لدور معين (إضافة أو إزالة).
 * تستجيب للطلبات من نوع POST القادمة من JavaScript (AJAX).
 */
public function updatePermission() {
    // التأكد من أن الطلب هو POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405 ); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        exit();
    }

    // قراءة البيانات الخام من جسم الطلب (لأننا سنرسل JSON)
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    // التحقق من وجود البيانات المطلوبة
    if (!isset($data->role_id) || !isset($data->permission_id) || !isset($data->is_checked)) {
        http_response_code(400 ); // Bad Request
        echo json_encode(['success' => false, 'message' => 'بيانات ناقصة.']);
        exit();
    }

    // تنظيف البيانات
    $roleId = (int)$data->role_id;
    $permissionId = (int)$data->permission_id;
    $isChecked = (bool)$data->is_checked;

    // إنشاء كائن من المودل
    $roleModel = new \App\Models\Role();

    // بند 6 (CD-006): تعديل الصلاحيات مقصور على دور Admin — يمنع تصعيد الصلاحيات
    // عبر أدوار أخرى قد تملك roles.manage مستقبلاً.
    if (!Auth::isAdmin()) {
        http_response_code(403 ); // Forbidden
        echo json_encode(['success' => false, 'message' => 'تعديل الصلاحيات مقصور على مدير النظام.']);
        exit();
    }

    // جلب الدور للتأكد من أنه ليس دور "Admin"
    $role = $roleModel->findById($roleId);
    if ($role && $role->name === 'Admin') {
        http_response_code(403 ); // Forbidden
        echo json_encode(['success' => false, 'message' => 'لا يمكن تعديل صلاحيات دور المدير.']);
        exit();
    }

    // جلب الصلاحية المطلوبة (للتحقق من وجودها ولتسجيل اسمها).
    $permission = $roleModel->findPermissionById($permissionId);
    if (!$permission) {
        http_response_code(404 ); // Not Found
        echo json_encode(['success' => false, 'message' => 'الصلاحية غير موجودة.']);
        exit();
    }

    // بند 6 (CD-006): لا يمنح المُنفِّذ صلاحية لا يملكها (طبقة حماية إضافية لتصعيد الصلاحيات).
    if ($isChecked && !Auth::userHasPermission($permission->name)) {
        http_response_code(403 ); // Forbidden
        echo json_encode(['success' => false, 'message' => 'لا يمكنك منح صلاحية لا تملكها.']);
        exit();
    }

    // استدعاء الدالة المناسبة في المودل بناءً على حالة مربع الاختيار
    if ($isChecked) {
        // إذا تم تحديد المربع، أضف الصلاحية
        $success = $roleModel->addPermissionToRole($roleId, $permissionId);
        $action = 'PERMISSION_ADDED';
    } else {
        // إذا تم إلغاء تحديد المربع، احذف الصلاحية
        $success = $roleModel->removePermissionFromRole($roleId, $permissionId);
        $action = 'PERMISSION_REMOVED';
    }

    // إعداد الاستجابة كـ JSON
    header('Content-Type: application/json');

    if ($success) {
        // بند 6 (CD-006): تسجيل القيمة قبل/بعد (old/new) مع اسم الصلاحية والمنفّذ.
        \App\Core\Audit::log($action, $roleId, 'roles', [
            'permission_id' => $permissionId,
            'permission_name' => $permission->name,
            'old' => !$isChecked,
            'new' => $isChecked,
            'actor_id' => Auth::id(),
        ]);
        echo json_encode(['success' => true, 'message' => 'تم تحديث الصلاحية بنجاح.']);
    } else {
        http_response_code(500 ); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث الصلاحية.']);
    }
    exit();

  }
  // في ملف App\Controllers\Admin\RoleController.php

// ... (بعد دالة index وقبل دالة updatePermission)

/**
 * ==================================================
 *           ===>  الدالة الجديدة الأولى هنا  <===
 * ==================================================
 * 
 * تعرض صفحة نموذج إنشاء دور جديد.
 */
public function create() {
    $data = [
        'pageTitle' => 'إضافة دور جديد'
    ];
    $this->view('admin/roles/create', $data);
}

/**
 * ==================================================
 *           ===>  الدالة الجديدة الثانية هنا  <===
 * ==================================================
 * 
 * تعالج بيانات النموذج وتقوم بحفظ الدور الجديد.
 */
public function store() {
    // التأكد من أن الطلب هو POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1. تنظيف البيانات القادمة من النموذج
        $roleName = trim($_POST['name']);
        $description = trim($_POST['description']);

        // 2. التحقق من أن البيانات ليست فارغة
        if (empty($roleName) || empty($description)) {
            \App\Core\Flash::set('الرجاء ملء جميع الحقول المطلوبة.', 'danger');
            header('Location: ' . APP_URL . '/admin/roles/create');
            exit();
        }

        // 3. إنشاء كائن من المودل
        $roleModel = new \App\Models\Role();

        // 4. استدعاء دالة الإنشاء في المودل
        if ($roleModel->createRole($roleName, $description)) {
            // في حالة النجاح، سجل الإجراء وعيّن رسالة نجاح
            \App\Core\Audit::log('ROLE_CREATE', null, 'roles', ['name' => $roleName]);
            \App\Core\Flash::set('تم إنشاء الدور الجديد بنجاح.', 'success');
        } else {
            // في حالة الفشل، عيّن رسالة خطأ
            \App\Core\Flash::set('حدث خطأ أثناء إنشاء الدور. قد يكون الاسم مستخدماً بالفعل.', 'danger');
        }

        // 5. أعد التوجيه إلى صفحة قائمة الأدوار
        header('Location: ' . APP_URL . '/admin/roles');
        exit();
    }
}
// في ملف App\Controllers\Admin\RoleController.php

// ... (بعد دالة store)

public function update() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)$_POST['id'];
        $description = trim($_POST['description']);

        $roleModel = new \App\Models\Role();
        if ($roleModel->updateRole($id, $description)) {
          //تجسل العملية  
          \App\Core\Audit::log('ROLE_UPDATE', $id, 'roles', ['description' => $description]);
            
          \App\Core\Flash::set('تم تحديث وصف الدور بنجاح.', 'success');
        } else {
            \App\Core\Flash::set('حدث خطأ أثناء تحديث الدور.', 'danger');
        }
        header('Location: ' . APP_URL . '/admin/roles');
        exit();
    }
}

public function destroy() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)$_POST['id'];
        
        $roleModel = new \App\Models\Role();
        if ($roleModel->deleteRole($id)) {
          //تجسيل عملية الحذف  
          \App\Core\Audit::log('ROLE_DELETE', $id, 'roles');
            \App\Core\Flash::set('تم حذف الدور بنجاح.', 'success');
        } else {
            \App\Core\Flash::set('لا يمكن حذف هذا الدور، قد يكون مرتبطاً بمستخدمين.', 'danger');
        }
        header('Location: ' . APP_URL . '/admin/roles');
        exit();
    }
}



}
