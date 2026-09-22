<?php

namespace App\Models;

use App\Core\Model;

class ReportModel extends Model {

    /**
     * يحسب الأداء المؤسسي الكامل بناءً على منطق الحساب المباشر.
     * @param array $config - إعدادات التقرير.
     * @return array - بيانات التقرير المجمعة.
     */
    public function calculateCorporatePerformance(array $config): array {
        // جلب الهيكل التنظيمي للفروع والأقسام
        $this->db->query("
            SELECT b.id AS branch_id, b.name AS branch_name, d.id AS department_id, d.name AS department_name
            FROM branches b
            LEFT JOIN departments d ON b.id = d.branch_id
            WHERE d.id IS NOT NULL -- نختار فقط الفروع التي لديها أقسام
            ORDER BY b.id, d.id
        ");
        $structure = $this->db->fetchAll();

        $branchesData = [];

        foreach ($structure as $item) {
            $branchId = $item->branch_id;
            $departmentId = $item->department_id;

            if (!isset($branchesData[$branchId])) {
                $branchesData[$branchId] = [
                    'branch_name' => $item->branch_name,
                    'departments' => [],
                    'department_scores_for_branch_avg' => [] // مصفوفة لتجميع نتائج الأقسام لحساب متوسط الفرع
                ];
            }

            // 1. حساب نتائج أعمدة تقييم الموظفين
            $employeeScores = [];
            $departmentTotal = 0;
            foreach ($config['employee_columns'] as $column) {
                list($type, $id) = explode('-', $column['source']);
                // جلب متوسط الدرجة للعمود (النتيجة تكون من 0 إلى 100)
                $score = $this->getAverageScoreForDepartment($departmentId, $type, $id, $config['start_date'], $config['end_date']);
                $employeeScores[] = $score;
                
                // إضافة الجزء الموزون من هذه الدرجة إلى إجمالي القسم
                $departmentTotal += $score * ((float)$column['weight'] / 100);
            }

            // 2. حساب نتائج الدورات والورش
            $coursesScore = $this->getAverageTrainingScore($departmentId, 'course', $config['start_date'], $config['end_date']);
            $workshopsScore = $this->getAverageTrainingScore($departmentId, 'workshop', $config['start_date'], $config['end_date']);

            // 3. إضافة الأجزاء الموزونة من الدورات والورش إلى إجمالي القسم
            $departmentTotal += $coursesScore * ((float)$config['courses_weight'] / 100);
            $departmentTotal += $workshopsScore * ((float)$config['workshops_weight'] / 100);

            // إضافة بيانات القسم المحسوبة
            $branchesData[$branchId]['departments'][] = [
                'department_name' => $item->department_name,
                'scores' => $employeeScores, // الدرجات الخام (من 100) لكل عمود
                    'courses_score' => $coursesScore,
                    'workshops_score' => $workshopsScore,
                'department_total' => $departmentTotal // النتيجة النهائية للقسم من 100
            ];

            // تجميع نتيجة القسم لحساب متوسط الفرع لاحقاً
            $branchesData[$branchId]['department_scores_for_branch_avg'][] = $departmentTotal;
        }

        // --- حساب متوسطات الفروع والشركة ---
        $companyTotalScores = [];
        foreach ($branchesData as &$branch) { // استخدام & لتمرير المرجع
            if (!empty($branch['department_scores_for_branch_avg'])) {
                // إجمالي تقييم الفرع = المتوسط الحسابي البسيط لنتائج أقسامه
                $branch['branch_total'] = array_sum($branch['department_scores_for_branch_avg']) / count($branch['department_scores_for_branch_avg']);
                $companyTotalScores[] = $branch['branch_total'];
            } else {
                $branch['branch_total'] = 0;
            }
            // تنظيف المصفوفة المؤقتة
            unset($branch['department_scores_for_branch_avg']);
        }
        unset($branch); // إزالة المرجع لضمان عدم حدوث أخطاء

        // المتوسط العام للشركة = المتوسط الحسابي البسيط لنتائج الفروع
        $companyTotal = !empty($companyTotalScores) ? array_sum($companyTotalScores) / count($companyTotalScores) : 0;

        return [
            'branches' => array_values($branchesData),
            'company_total' => $companyTotal
        ];
    }

    /**
     * دالة مساعدة لجلب متوسط نتيجة تقييم لقسم معين (نسخة محسّنة).
     */
    private function getAverageScoreForDepartment($departmentId, $type, $sourceId, $startDate, $endDate): float {
        $sql = "SELECT AVG(ea.normalized_score) as avg_score
                FROM evaluation_answers ea
                JOIN evaluations e ON ea.evaluation_id = e.id
                JOIN employees emp ON e.evaluated_employee_id = emp.id
                JOIN criteria_sections cs ON ea.section_id = cs.id
                JOIN criteria c ON cs.criterion_id = c.id
                WHERE emp.department_id = :department_id";

        if ($type === 'template') {
            $sql .= " AND e.template_id = :source_id";
        } else { // criterion
            $sql .= " AND c.id = :source_id";
        }

        if ($startDate && $endDate) {
            $sql .= " AND e.evaluation_date BETWEEN :start_date AND :end_date";
        }

        $this->db->query($sql);
        $this->db->bind(':department_id', $departmentId);
        $this->db->bind(':source_id', $sourceId);
        if ($startDate && $endDate) {
            $this->db->bind(':start_date', $startDate);
            $this->db->bind(':end_date', $endDate);
        }
        
        $result = $this->db->fetch();
        return $result && $result->avg_score ? (float)$result->avg_score : 0.0;
    }

    /**
     * دالة مساعدة لجلب متوسط نتيجة التدريب لقسم معين (نسخة محسّنة).
     */
    private function getAverageTrainingScore($departmentId, $type, $startDate, $endDate): float {
        $baseSql = "SELECT AVG(eval.final_score) as avg_score FROM ";
        $table = ($type === 'course') ? "course_evaluations" : "workshop_evaluations";
        $joinTable = ($type === 'course') ? "training_courses" : "training_workshops";
        $joinKey = ($type === 'course') ? "course_id" : "workshop_id";

        $sql = $baseSql . $table . " eval JOIN " . $joinTable . " training ON eval." . $joinKey . " = training.id WHERE training.department_id = :department_id";

        if ($startDate && $endDate) {
            // نفترض أن تواريخ التدريب هي start_date
            $sql .= " AND training.start_date BETWEEN :start_date AND :end_date";
        }

        $this->db->query($sql);
        $this->db->bind(':department_id', $departmentId);
        if ($startDate && $endDate) {
            $this->db->bind(':start_date', $startDate);
            $this->db->bind(':end_date', $endDate);
        }

        $result = $this->db->fetch();
        return $result && $result->avg_score ? (float)$result->avg_score : 0.0;
    }
}
