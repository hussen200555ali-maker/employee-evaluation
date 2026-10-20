<?php
// في ملف /app/Controllers/TrainingReportController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\CourseEvaluation;
use App\Models\TrainingReport;
class TrainingReportController extends Controller {

    public function __construct() {
        // لا نتحقق من الصلاحية هنا لأن دوال الـ API يجب أن تكون متاحة
        // إذا كان المستخدم يستطيع رؤية الصفحة أصلاً.
        // Auth::can('reports.view.all'); 
    }

   public function dashboard() {
        Auth::enforce('reports.view.all');
        $evaluationModel = new CourseEvaluation();
        $reportData = $evaluationModel->getBranchLevelReport();
        $data = [
            'pageTitle' => 'لوحة معلومات أداء التدريب',
            'reportData' => $reportData
        ];
        $this->view('reports/training/dashboard', $data);
    }
// في ملف /app/Controllers/TrainingReportController.php
/*public function dashboard() {
    Auth::enforce('reports.view.all');
    
    // سنحتاج لدالة تجلب الهيكل التنظيمي
    $branchModel = new \App\Models\Branch(); // افترض أن لديك هذا المودل
    $branches = $branchModel->getAllWithDepartments(); // افترض أن هذه الدالة تجلب الفروع مع أقسامها

    $data = [
        'pageTitle' => 'مركز التقارير',
        'branches' => $branches
    ];

    $this->view('reports/training/dashboard', $data);
}
*/
    /**
     * دالة API: تجلب بيانات تقرير الأقسام لفرع معين وترجعها كـ JSON.
     * ===> تم إضافة try-catch block <===
     */
  /*  public function getDepartmentsByBranch($branch_id) {
        Auth::enforce('reports.view.all'); // يجب التحقق من الصلاحية في دوال الـ API أيضاً
        try {
            $evaluationModel = new \App\Models\CourseEvaluation();
            $reportData = $evaluationModel->getDepartmentLevelReportByBranch($branch_id);

            header('Content-Type: application/json');
            echo json_encode($reportData);

        } catch (\Exception $e) {
            // في حالة حدوث أي خطأ، أرسل رد خطأ بصيغة JSON
            http_response_code(500 ); // Internal Server Error
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'An internal server error occurred.',
                'message' => $e->getMessage() // إرسال رسالة الخطأ الفعلية للمساعدة في التصحيح
            ]);
        }
        exit(); // إيقاف التنفيذ بعد إرسال الـ JSON
    }
*/

    /**
     * ==================================================
     *           ===>  النسخة النظيفة والنهائية <===
     * ==================================================
     */
    public function getDepartmentsByBranch($branch_id) {
        try {
            // التأكد من أن المستخدم لديه الصلاحية
            Auth::enforce('reports.view.all');

            $evaluationModel = new \App\Models\CourseEvaluation();
            $reportData = $evaluationModel->getDepartmentLevelReportByBranch($branch_id);

            // إرسال هيدر JSON
            header('Content-Type: application/json');
            // طباعة البيانات بصيغة JSON
            echo json_encode($reportData);

        } catch (\Exception $e) {
            // في حالة حدوث أي خطأ، أرسل استجابة خطأ بصيغة JSON
            http_response_code(500 );
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
        // إيقاف التنفيذ لضمان عدم طباعة أي شيء آخر
        exit();
    }
    
      /*  public function getDepartmentsByBranch($branchId) {
    // خطوة أمان: تأكد من أن المعرف هو رقم صحيح
    $branchId = filter_var($branchId, FILTER_VALIDATE_INT);
    if ($branchId === false) {
        // إذا لم يكن رقمًا، أرجع استجابة JSON تحتوي على خطأ
        header('Content-Type: application/json');
        http_response_code(400 ); // Bad Request
        echo json_encode(['error' => 'معرف الفرع غير صالح.']);
        return;
    }

    try {
        $departmentModel = new \App\Models\Department();
        // استدعاء الدالة من الموديل
        $departments = $departmentModel->getWithWorkshopStats($branchId);

        // إرسال البيانات كـ JSON
        header('Content-Type: application/json');
        echo json_encode($departments);

    } catch (\Exception $e) {
        // في حالة حدوث أي خطأ آخر، أرجع استجابة JSON تحتوي على الخطأ
        header('Content-Type: application/json');
        http_response_code(500 ); // Internal Server Error
        // لا تعرض رسالة الخطأ الفعلية للمستخدم النهائي لدواعي الأمان
        echo json_encode(['error' => 'حدث خطأ في الخادم.']);
    }
}*/


    /**
     * API: يجلب الدورات المقيمة لقسم معين.
     */
    public function getCoursesByDepartment($department_id) {
        Auth::enforce('reports.view.all');
        try {
            $reportModel = new \App\Models\CourseEvaluation();
            $courses = $reportModel->getGroupedCoursesReportByDepartment($department_id);

            $groupedResult = [];
            foreach ($courses as $course) {
                $key = $course->course_title; 
                if (!isset($groupedResult[$key])) {
                    $groupedResult[$key] = [
                        'course_title' => $key, // تم التعديل هنا ليتوافق مع JS
                        'courses' => []
                    ];
                }
                $groupedResult[$key]['courses'][] = $course;
            }

            header('Content-Type: application/json');
            echo json_encode(array_values($groupedResult));

        } catch (\Exception $e) {
            http_response_code(500 );
            header('Content-Type: application/json');
            echo json_encode(['error' => 'An internal server error occurred.', 'message' => $e->getMessage()]);
        }
        exit();
    }
     /**
     * ==================================================
     *           ===>  دالة الـ API الجديدة للتقارير <===
     * ==================================================
     * تجلب بيانات التقرير بشكل هرمي.
     * /api/report?level=company
     * /api/report?level=branch&id=1
     * /api/report?level=department&id=5
     */
   /**
     * ==================================================
     *           ===>  دالة الـ API الجديدة للتقارير <===
     * ==================================================
     * تجلب بيانات التقرير بشكل هرمي ومفصل.
     * أمثلة على الاستدعاء:
     * /api/report?level=company
     * /api/report?level=branch&id=1
     * /api/report?level=department&id=5
     */
    public function getReportData() {
        Auth::enforce('reports.view.all');
        
        $level = $_GET['level'] ?? 'company';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        try {
            $reportModel = new TrainingReport(); // استخدام المودل الجديد
            $data = null;

            switch ($level) {
                case 'branch':
                    if ($id) $data = $reportModel->getBranchReport($id);
                    break;
                case 'department':
                    if ($id) $data = $reportModel->getDepartmentReport($id);
                    break;
                default: // 'company'
                    $data = $reportModel->getCompanyReport();
                    break;
            }

            if ($data === null) {
                http_response_code(404 );
                echo json_encode(['error' => 'No data found for the specified criteria.']);
                exit();
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data, JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            http_response_code(500 );
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'An internal server error occurred.', 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * ==================================================
     *      ===>  الدالة الجديدة والأساسية للطباعة  <===
     * ==================================================
     * هذه الدالة تعرض صفحة طباعة حقيقية يتم إنشاؤها من الخادم.
     */
    public function printReportPage() {
        Auth::enforce('reports.view.all');
        
        $level = $_GET['level'] ?? 'company';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        try {
            $reportModel = new \App\Models\TrainingReport();
            $reportData = null;

            switch ($level) {
                case 'branch':
                    if ($id) $reportData = $reportModel->getBranchReport($id);
                    break;
                case 'department':
                    if ($id) $reportData = $reportModel->getDepartmentReport($id);
                    break;
                default:
                    $reportData = $reportModel->getCompanyReport();
                    break;
            }

            if ($reportData === null) {
                die("لا توجد بيانات لعرضها.");
            }

            // إنشاء HTML للجداول والتفاصيل
            $reportHtml = $this->generateReportHtmlForPhp($reportData);

            // تمرير البيانات إلى واجهة العرض الخاصة بالطباعة
            $this->view('reports/print_template', [
                'title' => $reportData['title'],
                'reportHtml' => $reportHtml,
                'printedBy' => Auth::user()->username 
            ]);

        } catch (\Exception $e) {
            die("حدث خطأ أثناء إعداد التقرير: " . $e->getMessage());
        }
    }

    /**
     * دالة مساعدة (PHP) لإنشاء HTML التقرير.
     * (هذه نسخة PHP من الدالة التي كانت في JavaScript)
     */
  
    /**
     * ==================================================
     *           ===>  الدالة المصححة هنا  <===
     * ==================================================
     * دالة مساعدة (PHP) لإنشاء HTML التقرير.
     * تم إضافة التحقق من القيم الفارغة (null) قبل استخدام number_format.
     */
  private function generateReportHtmlForPhp($data) {
    $html = '';
    $totals = [
        'total_courses' => 0,
        'total_attendees' => 0,
        'total_average_attendance' => 0,
        'total_average_regularity' => 0,
        'total_average_comprehension' => 0,
        'total_average_final' => 0,
    ];

    foreach ($data['branches'] as $branch) {
        $html .= '<h2 class="branch-title">' . htmlspecialchars($branch['name']) . '</h2>';

        foreach ($branch['departments'] as $dept) {
            $departmentCoursesHtml = '';
            foreach ($dept['courses'] as $course) {
                $attendance_score = $course->attendance_score ?? 0; // استخدام القيمة 0 إذا كانت null
                $regularity_score = $course->program_regularity_weighted_score ?? 0; // استخدام القيمة 0 إذا كانت null
                $comprehension_score = $course->participant_comprehension_weighted_score ?? 0; // استخدام القيمة 0 إذا كانت null
                $final_score = $course->final_score ?? 0; // استخدام القيمة 0 إذا كانت null

                $departmentCoursesHtml .= "<tr>
                    <td class='course-title'>" . htmlspecialchars($course->course_title) . "</td>
                    <td>{$course->start_date}</td>
                    <td>{$course->end_date}</td>
                    <td>{$course->expected_attendees}</td>
                    <td>{$course->actual_attendees}</td>
                    <td>" . number_format($attendance_score, 2) . "%</td>
                    <td>" . number_format($regularity_score, 2) . "%</td>
                    <td>" . number_format($comprehension_score, 2) . "%</td>
                    <td><strong>" . number_format($final_score, 2) . "%</strong></td>
                </tr>";

                // تحديث الإجماليات
                $totals['total_courses']++;
                $totals['total_attendees'] += $course->actual_attendees;
                $totals['total_average_attendance'] += $attendance_score;
                $totals['total_average_regularity'] += $regularity_score;
                $totals['total_average_comprehension'] += $comprehension_score;
                $totals['total_average_final'] += $final_score;
            }

            // إضافة جدول لكل قسم
            $html .= "<div class='department-report'>
                <h3 class='department-title'>" . htmlspecialchars($dept['name']) . "</h3>
                <table class='report-table'>
                    <thead>
                        <tr>
                            <th>اسم الدورة</th><th>تاريخ البدء</th><th>تاريخ الانتهاء</th>
                            <th>المفترض</th><th>الفعلي</th><th>الحضور (50%)</th>
                            <th>الانتظام (30%)</th><th>الاستيعاب (20%)</th>
                            <th>النتيجة النهائية</th>
                        </tr>
                    </thead>
                    <tbody>{$departmentCoursesHtml}</tbody>
                </table>
            </div>";
        }

        // إضافة فاصل صفحة بعد كل فرع، إذا كان هناك المزيد من الفروع
      //  $html .= '<div class="page-break"></div>';
    }

    // حساب المتوسطات
    $average_attendance = $totals['total_courses'] > 0 ? $totals['total_average_attendance'] / $totals['total_courses'] : 0;
    $average_regularity = $totals['total_courses'] > 0 ? $totals['total_average_regularity'] / $totals['total_courses'] : 0;
    $average_comprehension = $totals['total_courses'] > 0 ? $totals['total_average_comprehension'] / $totals['total_courses'] : 0;
    $average_final = $totals['total_courses'] > 0 ? $totals['total_average_final'] / $totals['total_courses'] : 0;

    // إضافة إحصائيات شاملة
    $html .= "<h2 class='branch-title'>الإحصائيات الشاملة</h2>
        <table class='report-table'>
            <thead>
                <tr>
                    <th>إجمالي الدورات</th>
                    <th>إجمالي الحضور</th>
                    <th>متوسط الحضور (%)</th>
                    <th>متوسط الانتظام (%)</th>
                    <th>متوسط الاستيعاب (%)</th>
                    <th>متوسط النتيجة النهائية (%)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{$totals['total_courses']}</td>
                    <td>{$totals['total_attendees']}</td>
                    <td>" . number_format($average_attendance, 2) . "%</td>
                    <td>" . number_format($average_regularity, 2) . "%</td>
                    <td>" . number_format($average_comprehension, 2) . "%</td>
                    <td>" . number_format($average_final, 2) . "%</td>
                </tr>
            </tbody>
        </table>";

    return $html;
}

}
  