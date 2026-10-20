<?php
// في ملف /app/Controllers/TrainingCourseController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\TrainingCourse;
use App\Models\Branch;
use App\Models\Department;
use App\Core\Flash; 
use App\Models\CourseCatalog; // ===> استيراد المودل الجديد

class TrainingCourseController extends Controller {

    public function __construct() {
        Auth::enforce('training.manage');
    }

    public function index() {
        $courseModel = new TrainingCourse();
        $data = [
            'pageTitle' => 'إدارة الدورات التدريبية',
            'courses' => $courseModel->getAll()
        ];
        $this->view('training/courses/index', $data);
    }

     /**
     * يعرض نموذج إنشاء دورة جديدة.
     * يمرر قائمة الفروع وقائمة الكتالوج إلى الواجهة.
     */
public function create() {
        $branchModel = new Branch();
        $catalogModel = new CourseCatalog();

        $data = [
            'pageTitle' => 'إضافة دورة منفذة جديدة',
            'branches' => $branchModel->getAll(),
            'catalog_courses' => $catalogModel->getAll(),
            'departments' => [] // إرسال مصفوفة فارغة لمنع الأخطاء
        ];
        $this->view('training/courses/create', $data);
    }

     public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseModel = new TrainingCourse();
            
            // ===> التصحيح الأول: التأكد من أن القسم ليس فارغاً (بناءً على ملاحظتك) <===
            if (empty($_POST['department_id'])) {
                \App\Core\Flash::set('الرجاء اختيار القسم المنفذ للدورة.', 'danger');
                // إعادة المستخدم إلى نموذج الإنشاء مع البيانات التي أدخلها
                // (هذه ميزة متقدمة يمكن إضافتها لاحقاً، الآن سنقوم بإعادة التوجيه فقط)
                $this->redirect('/training/courses/create');
                return; // إيقاف التنفيذ
            }

          $data = [
                    'catalog_id' => $_POST['catalog_id'],
                    'branch_id' => $_POST['branch_id'],
                    'department_id' => $_POST['department_id'],
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'status' => $_POST['status'], 
                    'created_by' => Auth::id()
                ];

            // ===> التصحيح الثاني: استدعاء الدالة من المودل بشكل صحيح <===
            // بدلاً من محاولة الحصول على lastInsertId هنا، سنجعله مسؤولية المودل.
            $newCourseId = $courseModel->createAndGetId($data);

            if ($newCourseId) {
                \App\Core\Flash::set('تمت إضافة الدورة بنجاح.', 'success');
                // الآن يمكننا تسجيل الإجراء بالـ ID الصحيح
                \App\Core\Audit::log('TRAINING_COURSE_CREATE', $newCourseId, 'training_courses', $data);
            } else {
                \App\Core\Flash::set('حدث خطأ أثناء إضافة الدورة.', 'danger');
            }
            $this->redirect('/training/courses');
        }
    }

   /**
     * يعرض نموذج تعديل دورة موجودة.
     * يمرر كل البيانات اللازمة بما في ذلك الأقسام المرتبطة بالفرع المحدد.
     */
      public function edit($id) {
        $courseModel = new TrainingCourse();
        $branchModel = new Branch();
        $departmentModel = new Department();
        $catalogModel = new CourseCatalog();

        $course = $courseModel->findById($id);
        if (!$course) {
            $this->redirect('/training/courses');
            return;
        }

        $data = [
            'pageTitle' => 'تعديل دورة منفذة',
            'course' => $course,
            'branches' => $branchModel->getAll(),
            'departments' => $departmentModel->getForBranch($course->branch_id),
            'catalog_courses' => $catalogModel->getAll()
        ];
        $this->view('training/courses/edit', $data);
    }

     public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseModel = new TrainingCourse();

            // ===> التصحيح الثالث: التأكد من أن القسم ليس فارغاً في التحديث أيضاً <===
            if (empty($_POST['department_id'])) {
                \App\Core\Flash::set('الرجاء اختيار القسم المنفذ للدورة.', 'danger');
                $this->redirect('/training/courses/edit/' . $_POST['id']);
                return;
            }
            
          $data = [
                    'id' => $_POST['id'],
                    'catalog_id' => $_POST['catalog_id'],
                    'branch_id' => $_POST['branch_id'],
                    'department_id' => $_POST['department_id'],
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'status' => $_POST['status'] // <--- أضف هذا السطر
                ];

            if ($courseModel->update($data)) {
                \App\Core\Flash::set('تم تحديث الدورة بنجاح.', 'success');
                \App\Core\Audit::log('TRAINING_COURSE_UPDATE', $data['id'], 'training_courses', $data);
            } else {
                \App\Core\Flash::set('حدث خطأ أثناء تحديث الدورة.', 'danger');
            }
            $this->redirect('/training/courses');
        }
    }

    // ... (دوال الحذف وتغيير الحالة تبقى كما هي) ...
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseModel = new TrainingCourse();
            $id = $_POST['id'];
            if ($courseModel->delete($id)) {
                \App\Core\Flash::set('تم حذف الدورة بنجاح.', 'success');
                \App\Core\Audit::log('TRAINING_COURSE_DELETE', $id, 'training_courses');
            } else {
                \App\Core\Flash::set('حدث خطأ أثناء حذف الدورة.', 'danger');
            }
            $this->redirect('/training/courses');
        }
    }
    
public function destroy() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)$_POST['id'];
        
        $courseModel = new TrainingCourse();
        if ($courseModel->delete($id)) {
            // ==================================================
            //           ===>  أضف السطر التالي هنا  <===
            // ==================================================
            \App\Core\Audit::log('TRAINING_COURSE_DELETE', $id, 'training_courses');

            Flash::set('تم حذف الدورة بنجاح.', 'success');
        } else {
            Flash::set('حدث خطأ أثناء حذف الدورة.', 'danger');
        }
        header('Location: ' . APP_URL . '/training/courses');
        exit();
    }
}

    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseModel = new TrainingCourse();
            $id = $_POST['id'];
            $status = $_POST['status'];
            if ($courseModel->updateStatus($id, $status)) {
                \App\Core\Flash::set('تم تحديث حالة الدورة بنجاح.', 'success');
                \App\Core\Audit::log('TRAINING_COURSE_STATUS_UPDATE', $id, 'training_courses', ['new_status' => $status]);
            } else {
                \App\Core\Flash::set('حدث خطأ أثناء تحديث الحالة.', 'danger');
            }
            $this->redirect('/training/courses');
        }
    }

}

