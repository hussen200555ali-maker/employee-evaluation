<?php
// نحدد الـ Namespace.
namespace App\Controllers;

// استيراد كل الفئات التي سنحتاجها.
use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Audit;
use App\Models\Department;
use App\Models\Branch; // سنحتاجه لعرض قائمة الفروع في النماذج

/**
 * DepartmentController
 * مسؤول عن كل الطلبات المتعلقة بالأقسام.
 */
class DepartmentController extends Controller {

    /**
     * يعرض صفحة إدارة الأقسام.
     */
    public function index() {
        // 1. التحقق من الصلاحية.
        Auth::enforce('departments.view');

        // 2. إنشاء كائنات من المودلز.
        $departmentModel = new Department();
        $branchModel = new Branch();

        // 3. جلب البيانات.
        $departments = $departmentModel->getAll();
        $branches = $branchModel->getAll(); // لجلب الفروع للنماذج

        // 4. تجهيز البيانات للـ View.
        $data = [
            'pageTitle' => 'إدارة الأقسام',
            'departments' => $departments,
            'branches' => $branches
        ];

        // 5. عرض الـ View.
        $this->view('departments/index', $data);
    }

    /**
     * يخزن قسماً جديداً.
     */
    public function store() {
        // 1. التحقق من الصلاحية.
        Auth::enforce('departments.create');

        // 2. التأكد من أن الطلب هو POST.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 3. تجهيز البيانات.
            $data = [
                'name' => trim($_POST['name']),
                'branch_id' => !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null
            ];

            // 4. إنشاء القسم عبر المودل.
            $departmentModel = new Department();
            $newDepartmentId = $departmentModel->create($data);

            if ($newDepartmentId) {
                // 5. تسجيل الإجراء في سجل التدقيق.
                Audit::log('DEPARTMENT_CREATE', $newDepartmentId, 'departments', $data);
                // 6. تعيين رسالة نجاح.
                Flash::set('تم إضافة القسم "' . $data['name'] . '" بنجاح.', 'success');
            } else {
                // 7. تعيين رسالة خطأ في حالة الفشل.
                Flash::set('حدث خطأ أثناء إضافة القسم.', 'error');
            }
        }
        // 8. إعادة التوجيه في كل الحالات.
        header('Location: ' . APP_URL . '/departments');
        exit();
    }

    /**
     * يحدّث قسماً موجوداً.
     */
    public function update() {
        // 1. التحقق من الصلاحية.
        Auth::enforce('departments.edit');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'id' => (int)$_POST['id'],
                'name' => trim($_POST['name']),
                'branch_id' => !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null
            ];

            $departmentModel = new Department();
            if ($departmentModel->update($data)) {
                Audit::log('DEPARTMENT_UPDATE', $data['id'], 'departments', $data);
                Flash::set('تم تحديث القسم "' . $data['name'] . '" بنجاح.', 'success');
            } else {
                Flash::set('حدث خطأ أثناء تحديث القسم.', 'error');
            }
        }
        header('Location: ' . APP_URL . '/departments');
        exit();
    }

    /**
     * يحذف قسماً.
     */
    public function destroy() {
        // 1. التحقق من الصلاحية.
        Auth::enforce('departments.delete');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            $departmentModel = new Department();
            
            // 2. قبل الحذف، تحقق مما إذا كان القسم يحتوي على موظفين.
            if ($departmentModel->hasEmployees($id)) {
                Flash::set('لا يمكن حذف هذا القسم لأنه يحتوي على موظفين مرتبطين به.', 'error');
                header('Location: ' . APP_URL . '/departments');
                exit();
            }

            // 3. جلب بيانات القسم لتسجيلها قبل الحذف.
            $departmentData = $departmentModel->findById($id);

            // 4. تنفيذ الحذف.
            if ($departmentModel->delete($id)) {
                Audit::log('DEPARTMENT_DELETE', $id, 'departments', (array)$departmentData);
                Flash::set('تم حذف القسم "' . $departmentData->name . '" بنجاح.', 'success');
            } else {
                Flash::set('حدث خطأ أثناء حذف القسم.', 'error');
            }
        }
        header('Location: ' . APP_URL . '/departments');
        exit();
    }
}
