<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TrainingWorkshop;
use App\Models\WorkshopEvaluation;
use App\Models\Branch;
use App\Models\Department;
use App\Core\Auth; // <--- أضف هذا السطر
 use App\Models\WorkshopReport;
 use App\Core\Audit;
class WorkshopReportController extends Controller {

  /* dashboard
  
  public function index() {
        $workshopModel = new TrainingWorkshop();
        $evaluationModel = new WorkshopEvaluation();

        $data = [
            'pageTitle' => 'لوحة معلومات الورش التدريبية',
            'total_workshops' => $workshopModel->countAll(),
            'status_counts' => $workshopModel->countByStatus(),
            'branch_distribution' => $workshopModel->countByBranch(),
            'monthly_stats' => $workshopModel->countByMonth(),
            'average_score' => $evaluationModel->getAverageScore(),
            'top_workshops' => $evaluationModel->getTopWorkshops()
        ];

        $this->view('training/workshops/dashboard', $data);
    }
*/    public function index() {
        $workshopModel = new TrainingWorkshop();
        $branchModel = new Branch();

        $data = [
            'pageTitle' => 'تقارير الورش التدريبية',
            'total_workshops' => $workshopModel->countAll(),
            'status_counts' => $workshopModel->countByStatus(),
            'average_score' => (new WorkshopEvaluation())->getAverageScore(),
            'branches' => $workshopModel->countByBranchWithIds()
        ];

        $this->view('reports/workshops/index', $data);
    }

    //dashoard 
    
    public function dashboard() {
        $model = new TrainingWorkshop();

        $data = [
            'pageTitle' => 'لوحة معلومات الورش',
            'total_workshops' => $model->getTotal(),
            'status_counts' => $model->getStatusCounts(),
            'average_score' => $model->getAverageScore(),
            'branch_distribution' => $model->getDistributionByBranch(),
            'top_workshops' => $model->getTopWorkshops()
        ];

        $this->view('reports/workshops/dashboard', $data);
    }
 /*public function getDepartmentsByBranch($branchId) {
        $departmentModel = new Department();
        $departments = $departmentModel->getWithWorkshopStats($branchId);

        ob_start();
        require APP_ROOT . '/app/Views/reports/workshops/partials/departments.php';
        $html = ob_get_clean();
        echo $html;
    }*//*
public function getDepartmentsByBranch($branchId) {
    $departmentModel = new Department();
    $departments = $departmentModel->getWithWorkshopStats($branchId);

    ob_start();
    require APP_ROOT . '/app/Views/reports/workshops/partials/departments.php';
    $html = ob_get_clean();
    echo $html;
}
*/
    /*public function getWorkshopsByDepartment($departmentId) {
        $workshopModel = new TrainingWorkshop();
        $workshops = $workshopModel->getByDepartmentWithEvaluations($departmentId);

        ob_start();
        require APP_ROOT . '/app/Views/reports/workshops/partials/workshops.php';
        $html = ob_get_clean();
        echo $html;
    }*/

            /**
     * ==================================================
     *  API: يجلب الورش المقيمة لقسم معين، مجمعة حسب العنوان
     *  (النسخة الجديدة والمحسّنة)
     * ==================================================
     */
    public function getWorkshopsByDepartment($departmentId) {
        // خطوة أمان
        $departmentId = filter_var($departmentId, FILTER_VALIDATE_INT);
        if ($departmentId === false) {
            http_response_code(400 );
            echo '<div class="alert alert-danger">معرف القسم غير صالح.</div>';
            return;
        }

        try {
            $workshopModel = new \App\Models\TrainingWorkshop();
            // هذه الدالة موجودة بالفعل في الموديل وترجع كل الورش للقسم
            $workshops = $workshopModel->getByDepartmentWithEvaluations($departmentId);

            // ===> هذا هو منطق التجميع الجديد <===
            $groupedWorkshops = [];
            foreach ($workshops as $workshop) {
                // استخدم عنوان الورشة كمفتاح للتجميع
                $key = $workshop->workshop_title; 
                
                // إذا كان هذا أول ظهور لعنوان الورشة، قم بإنشاء مجموعة جديدة
                if (!isset($groupedWorkshops[$key])) {
                    $groupedWorkshops[$key] = [
                        'workshop_title' => $key,
                        'instances' => [] // مصفوفة لتخزين كل الورش التي لها نفس العنوان
                    ];
                }
                // أضف الورشة الحالية إلى مجموعتها
                $groupedWorkshops[$key]['instances'][] = $workshop;
            }

            // تحويل المصفوفة الترابطية إلى مصفوفة عادية لإرسالها
            $data['groupedWorkshops'] = array_values($groupedWorkshops);

            // عرض ملف العرض الجزئي الجديد وتمرير البيانات المجمعة إليه
            ob_start();
            require APP_ROOT . '/app/Views/reports/workshops/partials/workshops.php';
            $html = ob_get_clean();
            echo $html;

        } catch (\Exception $e) {
            http_response_code(500 );
            // لا تعرض رسالة الخطأ للمستخدم النهائي
            echo '<div class="alert alert-danger">حدث خطأ في الخادم أثناء جلب الورش.</div>';
        }
    }

/*
    public function printReportPage() {
        $model = new TrainingWorkshop();

        $data = [
            'pageTitle' => 'تقرير الورش (قابل للطباعة)',
            'total_workshops' => $model->getTotal(),
            'status_counts' => $model->getStatusCounts(),
            'average_score' => $model->getAverageScore(),
            'branch_distribution' => $model->getDistributionByBranch(),
            'top_workshops' => $model->getTopWorkshops()
        ];

        $this->view('reports/workshops/print', $data);
    }
*//*
public function getDepartmentsByBranch($branchId) {
    // خطوة أمان: تأكد من أن المعرف هو رقم صحيح
    $branchId = filter_var($branchId, FILTER_VALIDATE_INT);
    if ($branchId === false) {
        // إذا لم يكن رقمًا، أرجع رسالة خطأ واضحة
        echo '<div class="alert alert-danger text-center">معرف الفرع غير صالح.</div>';
        return;
    }

    // استدعاء الدالة الجديدة التي أنشأناها في الموديل
    $departmentModel = new \App\Models\Department();
    $departments = $departmentModel->getWithWorkshopStats($branchId);

    // الآن، بدلاً من عرض رسالة عامة، نعرض رسالة دقيقة
    if (empty($departments)) {
        echo '<div class="alert alert-secondary text-center">لا توجد أقسام لديها ورش عمل مقيّمة في هذا الفرع.</div>';
        return;
    }

    // إذا وجدت بيانات، قم بعرضها باستخدام ملف العرض الجزئي
    ob_start();
    // تأكد من أن هذا المسار صحيح
    require_once APP_ROOT . '/app/Views/reports/workshops/partials/departments.php';
    $html = ob_get_clean();
    echo $html;
}*/
/*
public function getDepartmentsByBranch($branchId) {
    // خطوة أمان: تأكد من أن المعرف هو رقم صحيح
    $branchId = filter_var($branchId, FILTER_VALIDATE_INT);
    if ($branchId === false || $branchId <= 0)   {
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
     * ==================================================
     *  API: يجلب الأقسام التابعة لفرع معين مع إحصائيات الورش
     *  هذه هي الدالة التي يستدعيها الجافاسكريبت
     * ==================================================
     */
    public function getDepartmentsByBranch($branchId) {
        // خطوة أمان: تأكد من أن المعرف هو رقم صحيح
        $branchId = filter_var($branchId, FILTER_VALIDATE_INT);
        if ($branchId === false || $branchId <= 0) {
            // إذا لم يكن رقمًا، أرجع استجابة JSON تحتوي على خطأ
            header('Content-Type: application/json');
            http_response_code(400 ); // Bad Request
            echo json_encode(['error' => 'معرف الفرع غير صالح.']);
            return;
        }

        try {
            // استدعاء الدالة الصحيحة من موديل Department
            $departmentModel = new \App\Models\Department();
            $departments = $departmentModel->getWithWorkshopStats($branchId);

            // إرسال البيانات كـ JSON إلى الجافاسكريبت
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($departments);

        } catch (\Exception $e) {
            // في حالة حدوث أي خطأ في قاعدة البيانات
            header('Content-Type: application/json');
            http_response_code(500 ); // Internal Server Error
            echo json_encode(['error' => 'حدث خطأ في الخادم.']);
        }
    }


    /**
     * ==================================================
     *      ===>  الدالة الجديدة والأساسية للطباعة  <===
     * ==================================================
     * هذه الدالة تعرض صفحة طباعة حقيقية يتم إنشاؤها من الخادم.
     */
  /*  public function printReportPage() {
        // لاحقًا، يمكنك إضافة منطق لجلب البيانات هنا بناءً على GET parameters
        // مثل level و id
        
        $level = $_GET['level'] ?? 'all';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        // حاليًا، سنقوم فقط بإنشاء تقرير شامل بسيط كمثال
        $workshopModel = new \App\Models\TrainingWorkshop();
        $allWorkshops = $workshopModel->getAll(); // افترض أن هذه الدالة تجلب كل الورش

        // يمكنك بناء HTML هنا أو تمرير البيانات مباشرة إلى الـ view
        $reportHtml = "<h1>هذا هو التقرير الشامل</h1>";
        $reportHtml .= "<p>عدد الورش: " . count($allWorkshops) . "</p>";
        // (يمكنك بناء جدول كامل هنا في المستقبل)

        // تمرير البيانات إلى واجهة العرض الخاصة بالطباعة
        $data = [
            'title' => 'تقرير الورش التدريبية',
            'reportHtml' => $reportHtml,
            'printedBy' => Auth::user()->username ?? 'مستخدم غير معروف'
        ];

        // تأكد من أن ملف العرض 'reports/print_template.php' موجود
        $this->view('reports/print_template', $data);
    }*/
    /**
     * ==================================================
     *      ===>  الدالة الجديدة والأساسية للطباعة  <===
     *      (النسخة الاحترافية التي تنشئ تقرير HTML)
     * ==================================================
     */
    public function printReportPage() {
        // التأكد من أن المستخدم لديه الصلاحية
        \App\Core\Auth::enforce('training.view'); // أو أي صلاحية مناسبة
        
        $level = $_GET['level'] ?? 'company';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        try {
           Audit::log('WORKSHOP_REPORT_PRINTED', $id, 'reports', ['level' => $level]);
            $reportModel = new \App\Models\WorkshopReport(); // استخدام الموديل الجديد
            $reportData = null;

            // جلب البيانات بناءً على مستوى التقرير المطلوب
            switch ($level) {
                case 'branch':
                    if ($id) $reportData = $reportModel->getBranchReport($id);
                    break;
                // يمكنك إضافة case 'department' لاحقًا إذا أردت
                default: // 'company' أو 'all'
                    $reportData = $reportModel->getCompanyReport();
                    break;
            }

            if ($reportData === null || empty($reportData['branches'])) {
                die("لا توجد بيانات قابلة للطباعة حسب المعايير المحددة.");
            }

            // إنشاء HTML للجداول والتفاصيل باستخدام دالة مساعدة
            $reportHtml = $this->generateWorkshopReportHtml($reportData);

            // تمرير البيانات إلى واجهة العرض الخاصة بالطباعة
            // تأكد من وجود ملف 'print_template.php' في مجلد 'reports'
            $this->view('reports/print_template', [
                'title' => $reportData['title'],
                'reportHtml' => $reportHtml,
                'printedBy' => \App\Core\Auth::user()->username 
            ]);

        } catch (\Exception $e) {
            // عرض خطأ بسيط وواضح في حالة الفشل
            die("حدث خطأ أثناء إعداد التقرير: " . $e->getMessage());
        }
    }

    /**
     * دالة مساعدة خاصة لإنشاء HTML تقرير الورش.
     */
  /*  private function generateWorkshopReportHtml($data) {
        $html = '';
        foreach ($data['branches'] as $branch) {
            $html .= '<h2 class="branch-title">' . htmlspecialchars($branch['name']) . '</h2>';

            foreach ($branch['departments'] as $dept) {
                if (empty($dept->workshops)) continue;

                $html .= "<h3 class='department-title'>" . htmlspecialchars($dept->name) . "</h3>";
                $html .= "<table class='report-table'>
                            <thead>
                                <tr>
                                    <th>عنوان الورشة</th>
                                    <th>تاريخ البدء</th>
                                    <th>تاريخ الانتهاء</th>
                                    <th>الحالة</th>
                                    <th>النتيجة النهائية</th>
                                </tr>
                            </thead>
                            <tbody>";
                
                foreach ($dept->workshops as $workshop) {
                    $final_score = $workshop->final_score ?? 0;
                    $html .= "<tr>
                                <td class='course-title'>" . htmlspecialchars($workshop->workshop_title) . "</td>
                                <td>{$workshop->start_date}</td>
                                <td>{$workshop->end_date}</td>
                                <td>{$workshop->status}</td>
                                <td><strong>" . number_format($final_score, 1) . "%</strong></td>
                            </tr>";
                }
                $html .= "</tbody></table>";
            }
        }
        return $html;
    }*/
    /**
     * ==================================================
     *  دالة إنشاء HTML التقرير (النسخة التحليلية المفصلة)
     * ==================================================
     */

    /**
     * =================================================================
     *  دالة إنشاء HTML التقرير (النسخة النهائية الكاملة والموثقة)
     * =================================================================
     * تقوم هذه الدالة بتحويل مصفوفة بيانات هرمية إلى تقرير HTML احترافي 
     * مصمم للطباعة، مع جداول مفصلة وملخص إحصائي شامل.
     *
     * @param array $data مصفوفة تحتوي على بيانات التقرير الكاملة.
     * @return string سلسلة نصية تحتوي على كود الـ HTML النهائي للتقرير.
     */
    private function generateWorkshopReportHtml($data) {
        // نبدأ بسلسلة HTML فارغة لنقوم ببنائها خطوة بخطوة.
        $html = '';
        
        // الخطوة 1: تهيئة مصفوفة لتجميع الإحصائيات الشاملة
        // سنستخدم هذه المصفوفة لتخزين كل الأرقام التي نحتاجها لجدول الملخص النهائي.
        $summary = [
            'total_workshops' => 0,
            'total_expected' => 0,
            'total_actual' => 0,
            'total_attendance_score' => 0,
            'total_regularity_score' => 0,
            'total_final_score' => 0,
        ];

        // الخطوة 2: المرور على كل فرع في البيانات لإنشاء الجداول وجمع الإحصائيات
        foreach ($data['branches'] as $branch) {
            
            // ===> حل مشكلة تداخل الطباعة <===
            // نضع كل فرع (عنوانه وجداوله) داخل حاوية خاصة.
            // خاصية 'page-break-inside: avoid;' تطلب من المتصفح بقوة "عدم تقطيع هذه الحاوية".
            // إذا لم تتسع الحاوية في الصفحة الحالية، سينقلها المتصفح بأكملها إلى الصفحة التالية.
            $html .= '<div style="page-break-inside: avoid;">';

            // إضافة عنوان الفرع بتنسيق مميز.
            $html .= '<h2 class="branch-title">' . htmlspecialchars($branch['name']) . '</h2>';

            // المرور على كل قسم داخل الفرع الحالي.
            foreach ($branch['departments'] as $dept) {
                // إذا كان القسم لا يحتوي على ورش، نتجاهله وننتقل إلى القسم التالي.
                if (empty($dept->workshops)) continue;

                // إضافة عنوان القسم.
                $html .= "<h3 class='department-title'>" . htmlspecialchars($dept->name) . "</h3>";
                
                // إنشاء هيكل الجدول وترويسته المفصلة.
                $html .= "<table class='report-table'>
                            <thead>
                                <tr>
                                    <th style='width: 25%;'>عنوان الورشة</th>
                                    <th>تاريخ البدء</th>
                                    <th>تاريخ الانتهاء</th>
                                    <th>الحضور المفترض</th>
                                    <th>الحضور الفعلي</th>
                                    <th>نتيجة الحضور (80%)</th>
                                    <th>نتيجة الانتظام (20%)</th>
                                    <th>النتيجة النهائية</th>
                                </tr>
                            </thead>
                            <tbody>";
                
                // المرور على كل ورشة داخل القسم الحالي.
                foreach ($dept->workshops as $workshop) {
                    
                    // الخطوة 3: إجراء الحسابات الدقيقة لكل ورشة على حدة
                    $expected = $workshop->expected_attendees ?? 0;
                    $actual = $workshop->actual_attendees ?? 0;
                    
                    // حساب درجة الحضور (التي تمثل 80% من النتيجة).
                    // نتحقق من أن الحضور المفترض أكبر من صفر لتجنب القسمة على صفر.
                    $attendance_score = ($expected > 0) ? ($actual / $expected) * 80 : 0;
                    // نستخدم دالة min() لضمان أن النتيجة لا تتجاوز 80% كحد أقصى.
                    $attendance_score = min($attendance_score, 80);

                    // جلب درجة الانتظام (التي تمثل 20% من النتيجة).
                    $regularity_score = $workshop->program_regularity_weighted_score ?? 0;

                    // النتيجة النهائية هي مجموع الدرجتين المحسوبتين.
                    $final_score = $attendance_score + $regularity_score;

                    // الخطوة 4: تحديث مصفوفة الإحصائيات الشاملة بالقيم الجديدة
                    $summary['total_workshops']++;
                    $summary['total_expected'] += $expected;
                    $summary['total_actual'] += $actual;
                    $summary['total_attendance_score'] += $attendance_score;
                    $summary['total_regularity_score'] += $regularity_score;
                    $summary['total_final_score'] += $final_score;

                    // إضافة صف جديد في الجدول يحتوي على كل بيانات الورشة المحسوبة.
                    $html .= "<tr>
                                <td class='course-title'>" . htmlspecialchars($workshop->workshop_title) . "</td>
                                <td>{$workshop->start_date}</td>
                                <td>{$workshop->end_date}</td>
                                <td>{$expected}</td>
                                <td>{$actual}</td>
                                <td>" . number_format($attendance_score, 1) . "%</td>
                                <td>" . number_format($regularity_score, 1) . "%</td>
                                <td><strong>" . number_format($final_score, 1) . "%</strong></td>
                            </tr>";
                }
                // إغلاق جسم الجدول.
                $html .= "</tbody></table>";
            }
            // إغلاق حاوية الفرع.
            $html .= '</div>'; 
        }

        // الخطوة 5: حساب المتوسطات النهائية لجدول الملخص
        // نتحقق من أن عدد الورش أكبر من صفر لتجنب القسمة على صفر.
        $avg_attendance = ($summary['total_workshops'] > 0) ? $summary['total_attendance_score'] / $summary['total_workshops'] : 0;
        $avg_regularity = ($summary['total_workshops'] > 0) ? $summary['total_regularity_score'] / $summary['total_workshops'] : 0;
        $avg_final = ($summary['total_workshops'] > 0) ? $summary['total_final_score'] / $summary['total_workshops'] : 0;

        // الخطوة 6: إنشاء جدول الملخص الشامل بالتصميم الأفقي المطلوب
        // 'page-break-before: always;' تجبر هذا الجزء على البدء في صفحة جديدة دائمًا.
        //$html .= '<div style="page-break-before: always; page-break-inside: avoid;">';
      $html .= '<div style="page-break-inside: avoid; margin-top: 30px;">';

        $html .= '<h2 class="branch-title">ملخص التقرير</h2>';
        $html .= "<table class='report-table'>
                    <thead class='table-dark'>
                        <!-- الصف الأول: العناوين -->
                        <tr>
                            <th>إجمالي الورش</th>
                            <th>إجمالي الحضور المفترض</th>
                            <th>إجمالي الحضور الفعلي</th>
                            <th>متوسط نتيجة الحضور (80%)</th>
                            <th>متوسط نتيجة الانتظام (20%)</th>
                            <th>متوسط النتيجة النهائية</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- الصف الثاني: القيم -->
                        <tr style='background-color: #f8f9fa; font-weight: bold;'>
                            <td>{$summary['total_workshops']}</td>
                            <td>{$summary['total_expected']}</td>
                            <td>{$summary['total_actual']}</td>
                            <td>" . number_format($avg_attendance, 2) . "%</td>
                            <td>" . number_format($avg_regularity, 2) . "%</td>
                            <td>" . number_format($avg_final, 2) . "%</td>
                        </tr>
                    </tbody>
                  </table>";
        $html .= '</div>'; // إغلاق حاوية الملخص

        // الخطوة 7: إرجاع سلسلة الـ HTML الكاملة ليتم عرضها.
        return $html;
    }



    }

