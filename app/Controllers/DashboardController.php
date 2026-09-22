<?php
// نحدد الـ Namespace ليعكس مكان الملف.
namespace App\Controllers;

// استيراد الفئات التي سنحتاجها.
use App\Core\Controller;
use App\Core\Auth; // لا تنس استيراد فئة Auth
use App\Models\User;
use App\Services\RoleDashboardService;

/**
 * DashboardController
 * مسؤول عن عرض لوحة التحكم الرئيسية بعد تسجيل الدخول.
 */
class DashboardController extends Controller {

    /**
     * دالة البناء (Constructor)
     * تستخدم لحماية كل الدوال في هذا الـ Controller.
     */
    public function __construct() {
        // التحقق مما إذا كان المستخدم قد سجل دخوله.
        // إذا لم يكن، سيتم إعادة توجيهه إلى صفحة تسجيل الدخول.
        if (!Auth::check()) {
            header('Location: ' . APP_URL . '/login');
            exit();
        }
    }

    /**
     * دالة لعرض الصفحة الرئيسية للوحة التحكم.
     */
    public function index() {
        // ==================================================
        //           ===>  التصحيح الرئيسي هنا  <===
        // ==================================================
        
        // 1. نستدعي دالة Auth::user() التي تعيد كائن المستخدم الكامل.
        $user = Auth::user();

        // 2. نتحقق من أننا حصلنا على كائن المستخدم بنجاح قبل محاولة استخدامه.
        // هذا يضيف طبقة حماية إضافية.
        if (!$user) {
            // إذا لم يتم العثور على المستخدم لسبب ما، قم بتسجيل الخروج وإعادة التوجيه.
            Auth::logout();
            header('Location: ' . APP_URL . '/login');
            exit();
        }

        // 3. نمرر اسم المستخدم (username) إلى الـ View.
        // يمكنك أيضاً استخدام $user->full_name إذا كان المستخدم مرتبطاً بموظف وتريد عرض الاسم الكامل.
        $userModel = new User();
        $roles = $userModel->getUserRoles((int)Auth::id());
        $permissions = $userModel->getUserPermissions((int)Auth::id());
        $dashboard = (new RoleDashboardService())->forUser((int)Auth::id(), (int)($user->employee_id ?? 0), $permissions, $roles, (string)($user->full_name ?? $user->username));
        $data = [
            'pageTitle' => 'لوحة التحكم',
            'userName' => $user->username,
            'dashboard' => $dashboard,
        ];

        // عرض الـ View الخاص بلوحة التحكم وتمرير البيانات إليه.
        $this->view('dashboard/index', $data);
    }
}
