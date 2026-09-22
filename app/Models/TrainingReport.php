<?php
// ملف جديد: /app/Models/TrainingReport.php

namespace App\Models;
use App\Core\Model;

class TrainingReport extends Model {

    /**
     * يجلب كل الدورات المقيمة مع كل تفاصيلها بناءً على شرط معين.
     */
   private function getAllEvaluatedCourses($whereClause = "1=1", $params = []) {
        $sql = "SELECT 
                    b.id as branch_id, b.name as branch_name,
                    d.id as department_id, d.name as department_name,
                    tc.start_date, tc.end_date,
                    cc.title as course_title,
                    ce.id as evaluation_id,
                    ce.expected_attendees,
                    ce.actual_attendees,
                    (CASE WHEN ce.expected_attendees > 0 THEN (ce.actual_attendees / ce.expected_attendees) * 50 ELSE 0 END) as attendance_score,
                    ce.program_regularity_weighted_score,
                    ce.participant_comprehension_weighted_score,
                    ce.final_score
                FROM course_evaluations ce
                JOIN training_courses tc ON ce.course_id = tc.id
                JOIN course_catalog cc ON tc.catalog_id = cc.id
                JOIN departments d ON tc.department_id = d.id
                JOIN branches b ON d.branch_id = b.id
                WHERE {$whereClause}
                ORDER BY b.name, d.name, tc.start_date";
        
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->fetchAll();
    }

    /**
     * يجهز بيانات التقرير الشامل للشركة.
     */
    public function getCompanyReport() {
        $allCourses = $this->getAllEvaluatedCourses();
        return $this->structureData($allCourses, 'company', 'التقرير الشامل لأداء التدريب');
    }

    /**
     * يجهز بيانات تقرير فرع معين.
     */
    public function getBranchReport($branchId) {
        $courses = $this->getAllEvaluatedCourses("b.id = :id", [':id' => $branchId]);
        if (empty($courses)) return null;
        $title = "تقرير أداء فرع: " . htmlspecialchars($courses[0]->branch_name);
        return $this->structureData($courses, 'branch', $title);
    }

    /**
     * يجهز بيانات تقرير قسم معين.
     */
    public function getDepartmentReport($departmentId) {
        $courses = $this->getAllEvaluatedCourses("d.id = :id", [':id' => $departmentId]);
        if (empty($courses)) return null;
        $title = "تقرير أداء قسم: " . htmlspecialchars($courses[0]->department_name);
        return $this->structureData($courses, 'department', $title);
    }

    /**
     * دالة مساعدة لهيكلة البيانات بشكل هرمي.
     */
  /**
     * دالة مساعدة لهيكلة البيانات بشكل هرمي وضمان أن المخرجات هي مصفوفات.
     */
  /*  private function structureData($courses, $level, $title) {
        $branches = [];
        foreach ($courses as $course) {
            // تجميع الفروع
            if (!isset($branches[$course->branch_id])) {
                $branches[$course->branch_id] = [
                    'id' => $course->branch_id,
                    'name' => $course->branch_name,
                    'departments' => [] // تهيئة كمصفوفة
                ];
            }
            // تجميع الأقسام داخل كل فرع
            if (!isset($branches[$course->branch_id]['departments'][$course->department_id])) {
                $branches[$course->branch_id]['departments'][$course->department_id] = [
                    'id' => $course->department_id,
                    'name' => $course->department_name,
                    'courses' => [] // تهيئة كمصفوفة
                ];
            }
            // إضافة الدورة إلى القسم الصحيح
            $branches[$course->branch_id]['departments'][$course->department_id]['courses'][] = $course;
        }

        // ===> التصحيح الرئيسي: تحويل الكائنات إلى مصفوفات <===
        // المرور على كل فرع لتحويل كائن الأقسام إلى مصفوفة أقسام
        foreach ($branches as $branchId => &$branch) { // استخدام (&) للتعديل المباشر
            $branch['departments'] = array_values($branch['departments']);
        }

        // تحويل كائن الفروع إلى مصفوفة فروع
        $structuredBranches = array_values($branches);

        return ['level' => $level, 'title' => $title, 'branches' => $structuredBranches];
    }*/
        private function structureData($courses, $level, $title) {
    $branches = [];
    foreach ($courses as $course) {
        // استخدام القيمة الافتراضية فارغة إذا كانت القيمة الأصلية null
        $branchName = $course->branch_name ?? '';
        $departmentName = $course->department_name ?? '';

        // تجميع الفروع
        if (!isset($branches[$course->branch_id])) {
            $branches[$course->branch_id] = [
                'id' => $course->branch_id,
                'name' => $branchName, // استخدام القيمة المعالجة بدلاً من الأصلية
                'departments' => []
            ];
        }
        
        // تجميع الأقسام داخل كل فرع
        if (!isset($branches[$course->branch_id]['departments'][$course->department_id])) {
            $branches[$course->branch_id]['departments'][$course->department_id] = [
                'id' => $course->department_id,
                'name' => $departmentName, // استخدام القيمة المعالجة بدلاً من الأصلية
                'courses' => []
            ];
        }
        
        // إضافة الدورة إلى القسم الصحيح
        $branches[$course->branch_id]['departments'][$course->department_id]['courses'][] = $course;
    }

    // تحويل الكائنات إلى مصفوفات
    foreach ($branches as $branchId => &$branch) {
        $branch['departments'] = array_values($branch['departments']);
    }

    $structuredBranches = array_values($branches);

    return ['level' => $level, 'title' => $title, 'branches' => $structuredBranches];
}
}
