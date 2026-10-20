<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Audit;
use App\Models\Position;

class PositionsController extends Controller {

    /**
     * يعرض صفحة إدارة المناصب.
     */
    public function index() {
        // لاحقاً، يمكنك إنشاء صلاحية مخصصة مثل 'positions.manage'
     Auth::enforce('positions.manage');
        $positionModel = new Position();
        $data = [
            'pageTitle' => 'إدارة المناصب الوظيفية',
            'positions' => $positionModel->getAll()
        ];

           $this->view('employees/positions/index', $data);
    }

    /**
     * ينشئ منصباً جديداً.
     */
    public function store() {
       Auth::enforce('positions.manage');

        $data = [
            'title' => trim($_POST['title']),
            'category' => trim($_POST['category'])
        ];

        // تحقق بسيط من المدخلات
        if (empty($data['title']) || !in_array($data['category'], ['manager', 'deputy', 'staff', 'other'])) {
            Flash::set('البيانات المدخلة غير صالحة.', 'error');
       // ===> مسار التوجيه <===
            header('Location: ' . APP_URL . '/employees/positions');
            exit();
        }

        $positionModel = new Position();
        $newId = $positionModel->create($data);

        if ($newId) {
          Audit::log(
                'POSITION_CREATE',      // اسم الإجراء
                $newId,                 // ID السجل الجديد الذي تم إنشاؤه
                'positions',            // اسم الجدول
                $data                   // تفاصيل البيانات التي تم إدخالها
            );
            Flash::set('تمت إضافة المنصب بنجاح.', 'success');
        } else {
            Flash::set('حدث خطأ أثناء إضافة المنصب.', 'error');
        }

        header('Location: ' . APP_URL . '/employees/positions');
        exit();
    }

    /**
     * يحدّث بيانات منصب موجود.
     */
    public function update() {
       Auth::enforce('positions.manage');

        $data = [
            'id' => (int)$_POST['id'],
            'title' => trim($_POST['title']),
            'category' => trim($_POST['category'])
        ];

        if (empty($data['title']) || !in_array($data['category'], ['manager', 'deputy', 'staff', 'other'])) {
            Flash::set('البيانات المدخلة غير صالحة.', 'error');
            header('Location: ' . APP_URL . '/employees/positions');
            exit();
        }

        $positionModel = new Position();
        if ($positionModel->update($data)) {
            Audit::log(
                'POSITION_UPDATE',      // اسم الإجراء
                $data['id'],            // ID السجل الذي تم تحديثه
                'positions',            // اسم الجدول
                $data                   // تفاصيل البيانات الجديدة
            );
            Flash::set('تم تحديث المنصب بنجاح.', 'success');
        } else {
            Flash::set('حدث خطأ أثناء تحديث المنصب.', 'error');
        }

        header('Location: ' . APP_URL . '/employees/positions');
        exit();
    }

    /**
     * يحذف منصباً.
     */
    public function destroy() {
         Auth::enforce('positions.manage');

        $id = (int)$_POST['id'];
        $positionModel = new Position();

        // تحقق مما إذا كان المنصب مستخدماً قبل الحذف
        if ($positionModel->hasEmployees($id)) {
            Flash::set('لا يمكن حذف هذا المنصب لأنه مرتبط بموظفين حاليين.', 'error');
            header('Location: ' . APP_URL . '/employees/positions');
            exit();
        }
         // ===> جلب البيانات قبل الحذف لتسجيلها <===
        $positionData = $positionModel->findById($id); // للحصول على البيانات قبل الحذف للتسجيل

        if ($positionModel->delete($id)) {
           // ===> تسجيل عملية الحذف <===
            Audit::log(
                'POSITION_DELETE',      // اسم الإجراء
                $id,                    // ID السجل الذي تم حذفه
                'positions',            // اسم الجدول
                (array)$positionData    // تفاصيل السجل المحذوف
            );

            Flash::set('تم حذف المنصب بنجاح.', 'success');
        } else {
            Flash::set('حدث خطأ أثناء حذف المنصب.', 'error');
        }

        header('Location: ' . APP_URL . '/employees/positions');
        exit();
    }
}

