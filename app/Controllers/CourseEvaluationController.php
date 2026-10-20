<?php
// في ملف /app/Controllers/CourseEvaluationController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Models\TrainingCourse;
use App\Models\CourseEvaluation;

class CourseEvaluationController extends Controller {

    public function __construct() {
        Auth::enforce('training.evaluate');
    }

    public function create($course_id) {
        $courseModel = new TrainingCourse();
        $course = $courseModel->findById($course_id);

        if (!$course) {
            return (new ErrorController())->notFound();
        }

        // التحقق مما إذا كانت الدورة قد تم تقييمها بالفعل
        $evalModel = new CourseEvaluation();
        if ($evalModel->findByCourseId($course_id)) {
            \App\Core\Flash::set('هذه الدورة تم تقييمها بالفعل.', 'warning');
            $this->redirect('/training/courses');
            return;
        }

        $data = [
            // ===> التصحيح الأول: استخدام course_title <===
            'pageTitle' => 'تقييم دورة: ' . htmlspecialchars($course->course_title),
            'course' => $course
        ];
        $this->view('training/evaluations/create', $data);
    }

   public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $evalModel = new CourseEvaluation();
            $courseId = $_POST['course_id'];

            if ($evalModel->findByCourseId($courseId)) {
                Flash::set('خطأ: محاولة تقييم دورة تم تقييمها بالفعل.', 'danger');
                $this->redirect('/training/courses');
                return;
            }

            // ===> التصحيح النهائي يبدأ هنا <===

            // 1. جلب المدخلات وتحويلها إلى أرقام
            $actual_attendees = (int)$_POST['actual_attendees'];
            $expected_attendees = (int)$_POST['expected_attendees'];
            $program_regularity = (float)$_POST['program_regularity_weighted_score'];
            $participant_comprehension = (float)$_POST['participant_comprehension_weighted_score'];

            // 2. حساب مكون الحضور (50 درجة)
            $attendance_score = 0;
            if ($expected_attendees > 0) {
                $attendance_score = ($actual_attendees / $expected_attendees) * 50;
            }

            // 3. حساب الدرجة النهائية (من 100)
            $final_score = $attendance_score + $program_regularity + $participant_comprehension;

            // 4. تجميع البيانات الكاملة للحفظ
            $data = [
                'course_id' => $courseId,
                'actual_attendees' => $actual_attendees,
                'expected_attendees' => $expected_attendees,
                'program_regularity_weighted_score' => $program_regularity,
                'participant_comprehension_weighted_score' => $participant_comprehension,
                'final_score' => $final_score, // <--- الآن الدرجة النهائية موجودة
                'notes' => $_POST['notes'] ?? '', // إضافة حقل الملاحظات
                'evaluated_by' => Auth::id()
            ];

            if ($evalModel->create($data)) {
                Flash::set('تم حفظ التقييم بنجاح. الدرجة النهائية: ' . number_format($final_score, 2) . '%', 'success');
                $this->redirect('/training/courses');
            } else {
                Flash::set('حدث خطأ أثناء حفظ التقييم.', 'danger');
                $this->redirect('/training/evaluations/create/' . $courseId);
            }
        }
    }

    /**
     * ==================================================
     *           ===>  الدالة المحدثة هنا  <===
     * ==================================================
     * تعرض صفحة تفاصيل تقييم محفوظ.
     */
  /*  public function show($id) {
        $evalModel = new CourseEvaluation();
        
        // ===> سنحتاج إلى دالة جديدة لجلب التقييم مع كل تفاصيله <===
        $evaluation = $evalModel->getEvaluationDetails($id); 

        if (!$evaluation) {
            return (new ErrorController())->notFound();
        }

        $data = [
            'pageTitle' => 'تفاصيل تقييم دورة: ' . htmlspecialchars($evaluation->course_title),
            'eval' => $evaluation
        ];

        // إنشاء واستخدام واجهة عرض جديدة
        $this->view('training/evaluations/show', $data);
    }*/
        // في ملف: app/Controllers/CourseEvaluationController.php

public function show($id) {
    Auth::enforce('training.view');

    $evaluationModel = new \App\Models\CourseEvaluation();
    $evaluation = $evaluationModel->findById($id);

    if (!$evaluation) {
        Flash::set('التقييم غير موجود', 'danger');
        $this->redirect('/training/courses');
        return;
    }

    // ===> الجزء الجديد الذي تمت إضافته <===
    // 1. مصفوفة لترجمة أسماء الأيام
    $days_in_arabic = [
        'Saturday'  => 'السبت', 'Sunday'    => 'الأحد', 'Monday'    => 'الإثنين',
        'Tuesday'   => 'الثلاثاء', 'Wednesday' => 'الأربعاء', 'Thursday'  => 'الخميس',
        'Friday'    => 'الجمعة'
    ];
    // 2. الحصول على اسم اليوم الحالي باللغة العربية
    $today_in_arabic = $days_in_arabic[date('l')];

    // 3. تجهيز البيانات للعرض
    $data = [
        'pageTitle'       => 'عرض تقييم دورة',
        'eval'            => $evaluation,
        'printed_by'      => Auth::user()->username, // اسم المستخدم الذي يطبع
        'today_in_arabic' => $today_in_arabic       // اسم اليوم بالعربي
    ];

    $this->view('training/evaluations/show', $data);
}

}

