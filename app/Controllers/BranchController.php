<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Branch;
use App\Models\Employee; // سنحتاجه لجلب قائمة المدراء
use App\Core\Flash;
/**
 * BranchController
 * مسؤول عن كل الطلبات المتعلقة بالفروع.
 */
class BranchController extends Controller {

    /**
     * يعرض صفحة إدارة الفروع.
     */
    public function index() {
        // التحقق من الصلاحية: فقط الأدوار المصرح لها يمكنها عرض الفروع.
        if (!Auth::can('branches.view')) { // سنفترض وجود هذه الصلاحية
          
              return (new ErrorController())->forbidden('branches.view');
        }

        // إنشاء كائنات من المودلز التي نحتاجها.
        $branchModel = new Branch();
        $employeeModel = new Employee(); // لجلب المدراء المحتملين للنماذج

        // جلب البيانات.
        $branches = $branchModel->getAll();
        $managers = $employeeModel->getAll(); // يمكن تحسين هذا لاحقاً لجلب المدراء فقط

        // تجهيز البيانات للـ View.
        $data = [
            'pageTitle' => 'إدارة الفروع',
            'branches' => $branches,
            'managers' => $managers
        ];

        // عرض الـ View.
        $this->view('branches/index', $data);
    }

    /**
     * يخزن فرعاً جديداً.
     */
    public function store() {
        // التحقق من الصلاحية.
        if (!Auth::can('branches.create')) {
            
          return (new ErrorController())->forbidden('branches.create');
        }

        // التأكد من أن الطلب هو POST.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // تجهيز البيانات.
            $data = [
                'name' => trim($_POST['name']),
                'location' => trim($_POST['location']),
                'manager_id' => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null
            ];

            // (لاحقاً سنضيف التحقق من صحة البيانات هنا)

            // إنشاء فرع جديد عبر المودل.
            $branchModel = new Branch();
            if ($branchModel->create($data)) {
                // إذا نجح، أعد التوجيه مع رسالة نجاح (سنضيفها لاحقاً).
                header('Location: ' . APP_URL . '/branches');
                exit();
            }
        }
        // إذا فشل، أعد التوجيه.
        header('Location: ' . APP_URL . '/branches');
        exit();
    }

    /**
     * يحدّث فرعاً موجوداً.
     */
    public function update() {
        // التحقق من الصلاحية.
        if (!Auth::can('branches.edit')) {
             return (new ErrorController())->forbidden('branches.edit');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'id' => (int)$_POST['id'],
                'name' => trim($_POST['name']),
                'location' => trim($_POST['location']),
                'manager_id' => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null
            ];

            $branchModel = new Branch();
            if ($branchModel->update($data)) {
                header('Location: ' . APP_URL . '/branches');
                exit();
            }
        }
        header('Location: ' . APP_URL . '/branches');
        exit();
    }

    /**
     * يحذف فرعاً.
     */
    /**
     * يحذف فرعاً.
     * هذه الدالة تستجيب للطلب POST /branches/delete.
     */
    public function destroy() {
        // 1. التحقق من الصلاحية.
        if (!Auth::can('branches.delete')) {
            // إذا لم يكن لديه الصلاحية، أوقف التنفيذ.
             return (new ErrorController())->forbidden('branches.delete');
        }

        // 2. التأكد من أن الطلب هو POST وأن ID موجود.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            // 3. الحصول على ID الفرع.
            $id = (int)$_POST['id'];
            
            // 4. إنشاء كائن من مودل الفرع.
            $branchModel = new Branch();

            
            // 5. قبل الحذف، تحقق مما إذا كان الفرع يحتوي على أقسام مرتبطة به.
            if ($branchModel->hasDepartments($id)) {
                // 6. إذا كان يحتوي على أقسام، لا تقم بالحذف.
                // بدلاً من ذلك، قم بتعيين رسالة خطأ باستخدام نظام الإشعارات.
                // نستخدم النوع 'error' لعرضها باللون الأحمر.
                Flash::set('لا يمكن حذف هذا الفرع لأنه يحتوي على أقسام مرتبطة به. يرجى نقل أو حذف الأقسام أولاً.', 'error');
                
                // 7. أعد توجيه المستخدم مرة أخرى إلى صفحة الفروع.
                // سيقوم الـ View بعرض رسالة الخطأ التي قمنا بتعيينها.
                header('Location: ' . APP_URL . '/branches');
                exit();
            }

            // 8. إذا كان الفرع فارغاً، قم بتنفيذ عملية الحذف.
            if ($branchModel->delete($id)) {
                // 9. في حالة النجاح، قم بتعيين رسالة نجاح.
                Flash::set('تم حذف الفرع بنجاح.', 'success');
            } else {
                // 10. في حالة فشل الحذف لسبب آخر، قم بتعيين رسالة خطأ عامة.
                Flash::set('حدث خطأ غير متوقع أثناء محاولة حذف الفرع.', 'error');
            }
        }

        // 11. في كل الحالات، أعد التوجيه إلى صفحة الفروع في النهاية.
        header('Location: ' . APP_URL . '/branches');
        exit();
    }
    public function verifyIntegrity() {
    Auth::can('backup.manage');
    
    try {
        $monitor = new \App\Core\BackupMonitor();
        
        // استدعاء الدالة الصحيحة
        $results = $monitor->verifyBackupIntegrity();
        
        if ($results['success_rate'] == 100) {
            Flash::set('✅ جميع النسخ سليمة ومؤكدة (' . $results['verified'] . '/' . $results['total'] . ')', 'success');
        } else if ($results['success_rate'] > 80) {
            Flash::set('⚠️ معظم النسخ سليمة: ' . $results['verified'] . '/' . $results['total'] . ' سليمة (' . $results['success_rate'] . '%)', 'warning');
        } else {
            Flash::set('❌ مشكلة في النسخ: ' . $results['verified'] . '/' . $results['total'] . ' سليمة (' . $results['success_rate'] . '%)', 'danger');
        }
        
    } catch (\Exception $e) {
        Flash::set('❌ فشل في فحص سلامة النسخ: ' . $e->getMessage(), 'danger');
    }
    
    $this->redirect('/backup');
}
}
