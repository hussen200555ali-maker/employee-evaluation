<?php
// في ملف /app/Controllers/TrainingCourseController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\TrainingCourse;
use App\Models\Branch;
use App\Models\Department;
use App\Core\Flash; 
use App\Models\CourseCatalog;
use App\Models\Employee;
use App\Core\Database;
use App\Core\Audit as AuditLog;

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

    // CD-032: ربط الموظفين بالدورة (Enroll)
    public function enroll($id) {
        $courseModel = new TrainingCourse();
        $course = $courseModel->findById($id);
        if (!$course) { $this->redirect('/training/courses'); return; }
        $db = Database::getInstance();
        $db->query('SELECT cp.id, cp.employee_id, cp.status, e.full_name, e.emp_code, d.name AS dept_name, b.name AS branch_name FROM course_participants cp JOIN employees e ON e.id = cp.employee_id LEFT JOIN departments d ON d.id = e.department_id LEFT JOIN branches b ON b.id = e.branch_id WHERE cp.course_id = :cid ORDER BY e.full_name');
        $db->bind(':cid', (int)$id);
        $participants = $db->fetchAll() ?: [];
        $this->view('training/courses/enroll', ['pageTitle' => 'ربط الموظفين بالدورة', 'course' => $course, 'participants' => $participants, 'csrfToken' => Auth::generateCsrfToken()]);
    }

    public function enrollStore() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('/training/courses'); return; }
        Auth::validateCsrfToken($_POST['_token'] ?? '');
        $courseId = (int)($_POST['course_id'] ?? 0);
        $employeeIds = $_POST['employee_ids'] ?? [];
        if ($courseId < 1 || empty($employeeIds)) { Flash::set('لم يتم اختيار موظفين.', 'danger'); $this->redirect('/training/courses/enroll/' . $courseId); return; }
        $db = Database::getInstance(); $added = 0;
        foreach ($employeeIds as $eid) {
            $eid = (int)$eid; if ($eid < 1) continue;
            $db->query('INSERT IGNORE INTO course_participants (course_id, employee_id, status, enrolled_at) VALUES (:cid, :eid, "pending", NOW())');
            $db->bind(':cid', $courseId); $db->bind(':eid', $eid);
            if ($db->execute()) { $added++; }
        }
        Audit::log('COURSE_ENROLL', $courseId, 'training_courses', ['added' => $added]);
        Flash::set("تم تسجيل {$added} موظف.", 'success');
        $this->redirect('/training/courses/enroll/' . $courseId);
    }

    public function enrollRemove() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('/training/courses'); return; }
        Auth::validateCsrfToken($_POST['_token'] ?? '');
        $pid = (int)($_POST['participant_id'] ?? 0); $cid = (int)($_POST['course_id'] ?? 0);
        if ($pid > 0) { $db = Database::getInstance(); $db->query('DELETE FROM course_participants WHERE id = :id AND course_id = :cid'); $db->bind(':id', $pid); $db->bind(':cid', $cid); $db->execute(); Flash::set('تمت الإزالة.', 'success'); }
        $this->redirect('/training/courses/enroll/' . $cid);
    }

    // CD-032: تفاصيل الدورة + KPIs
    public function show($id) {
        $courseModel = new TrainingCourse();
        $course = $courseModel->findById($id);
        if (!$course) { $this->redirect('/training/courses'); return; }
        $db = Database::getInstance();
        $db->query('SELECT cp.id, cp.employee_id, cp.status, cp.completed_at, e.full_name, e.emp_code, d.name AS dept_name, ev.final_score, ev.id AS eval_id FROM course_participants cp JOIN employees e ON e.id = cp.employee_id LEFT JOIN departments d ON d.id = e.department_id LEFT JOIN evaluations ev ON ev.evaluated_employee_id = e.id AND ev.context_id = :cid AND ev.context_type = "course" WHERE cp.course_id = :cid2 ORDER BY e.full_name');
        $db->bind(':cid', (int)$id); $db->bind(':cid2', (int)$id);
        $participants = $db->fetchAll() ?: [];
        $total = count($participants); $completed = 0; $pending = 0;
        foreach ($participants as $p) { if ($p->status === 'completed') { $completed++; } else { $pending++; } }
        $this->view('training/courses/show', ['pageTitle' => 'تفاصيل الدورة', 'course' => $course, 'participants' => $participants, 'stats' => ['total'=>$total, 'completed'=>$completed, 'pending'=>$pending]]);
    }

    // CD-032: API — pending employees for bulk eval
    public function pendingEmployees($id) {
        $db = Database::getInstance();
        $db->query('SELECT e.id, e.full_name, e.emp_code, d.name AS department_name, b.name AS branch_name FROM course_participants cp JOIN employees e ON e.id = cp.employee_id LEFT JOIN departments d ON d.id = e.department_id LEFT JOIN branches b ON b.id = e.branch_id WHERE cp.course_id = :cid AND cp.status = "pending" AND e.is_active = 1 ORDER BY e.full_name');
        $db->bind(':cid', (int)$id);
        $this->json($db->fetchAll() ?: []);
    }

}

