<?php
// في ملف /app/Models/CourseEvaluation.php

namespace App\Models;
use App\Core\Model;

class CourseEvaluation extends Model {

    public function create($data) {
        // يستخدم الأسماء الصحيحة من قاعدة البيانات
        $sql = "INSERT INTO course_evaluations (course_id, expected_attendees, actual_attendees, program_regularity_weighted_score, participant_comprehension_weighted_score, final_score, notes, evaluated_by) VALUES (:course_id, :expected_attendees, :actual_attendees, :program_regularity_weighted_score, :participant_comprehension_weighted_score, :final_score, :notes, :evaluated_by)";
        $this->db->query($sql);
        $this->db->bind(':course_id', $data['course_id']);
        $this->db->bind(':expected_attendees', $data['expected_attendees']);
        $this->db->bind(':actual_attendees', $data['actual_attendees']);
        $this->db->bind(':program_regularity_weighted_score', $data['program_regularity_weighted_score']);
        $this->db->bind(':participant_comprehension_weighted_score', $data['participant_comprehension_weighted_score']);
        $this->db->bind(':final_score', $data['final_score']);
        $this->db->bind(':notes', $data['notes']);
        $this->db->bind(':evaluated_by', $data['evaluated_by']);
        return $this->db->execute();
    }

    public function findByCourseId($course_id) {
        $this->db->query("SELECT * FROM course_evaluations WHERE course_id = :course_id");
        $this->db->bind(':course_id', $course_id);
        return $this->db->fetch();
    }

    /**
     * ==================================================
     *           ===>  الدالة المصححة الأولى هنا  <===
     * ==================================================
     * تقوم بحساب القيم المشتقة تلقائياً وتستخدم الأسماء الصحيحة.
     */
    public function getEvaluationDetails($evaluationId) {
        $sql = "SELECT 
                    ce.*,
                    tc.start_date, tc.end_date,
                    cc.title as course_title,
                    b.name as branch_name,
                    d.name as department_name,
                    u.username as evaluator_name
                FROM course_evaluations ce
                JOIN training_courses tc ON ce.course_id = tc.id
                JOIN course_catalog cc ON tc.catalog_id = cc.id
                LEFT JOIN branches b ON tc.branch_id = b.id
                LEFT JOIN departments d ON tc.department_id = d.id
                LEFT JOIN users u ON ce.evaluated_by = u.id
                WHERE ce.id = :evaluation_id";
        
        $this->db->query($sql);
        $this->db->bind(':evaluation_id', $evaluationId);
        $eval = $this->db->fetch();

        if ($eval) {
            // حساب القيم المشتقة وإضافتها إلى الكائن
            $eval->attendance_score = ($eval->expected_attendees > 0) ? ($eval->actual_attendees / $eval->expected_attendees) * 50 : 0;
            // نستخدم الأسماء الصحيحة من قاعدة البيانات
            $eval->total_score = $eval->attendance_score + $eval->program_regularity_weighted_score + $eval->participant_comprehension_weighted_score;
        }

        return $eval;
    }

    /**
     * ==================================================
     *           ===>  الدالة المصححة الثانية هنا  <===
     * ==================================================
     * تقوم بحساب متوسط الأداء لكل فرع باستخدام الأسماء الصحيحة.
     */
     /**
     * يقوم بحساب متوسط الأداء لكل فرع.
     * تم تغيير اسم الحقل المرجع من avg_score إلى avg_final_score للتوحيد.
     *//*
    public function getBranchLevelReport() {
        $sql = "SELECT 
                    b.id as branch_id,
                    b.name as branch_name,
                    AVG(ce.final_score) as avg_final_score  -- <== تم التعديل هنا
                FROM branches b
                JOIN training_courses tc ON b.id = tc.branch_id
                LEFT JOIN course_evaluations ce ON tc.id = ce.course_id
                GROUP BY b.id, b.name
                ORDER BY b.name";
        $this->db->query($sql);
        return $this->db->fetchAll();
    }*/


    /**
     * يقوم بحساب متوسط الأداء لكل فرع.
     * ===> النسخة النهائية والمصححة للاستعلام <===
     * هذا يضمن ظهور كل الفروع، حتى التي ليس لديها دورات أو تقييمات.
     */
     /**
     * يقوم بحساب متوسط الأداء لكل فرع باستخدام استعلام فرعي.
     * ===> النسخة النهائية والمضمونة <===
     */
    public function getBranchLevelReport() {
        $sql = "SELECT
                    b.id AS branch_id,
                    b.name AS branch_name,
                    -- الاستعلام الفرعي يحسب المتوسط لكل فرع على حدة
                    (SELECT AVG(ce.final_score)
                     FROM course_evaluations ce
                     JOIN training_courses tc ON ce.course_id = tc.id
                     WHERE tc.branch_id = b.id) AS avg_final_score
                FROM
                    branches b
                ORDER BY
                    b.name";
        
        $this->db->query($sql);
        return $this->db->fetchAll();
    }

    /**
     * ==================================================
     *           ===>  الدالة المصححة الثالثة هنا  <===
     * ==================================================
     * تقوم بحساب متوسط الأداء لكل قسم داخل فرع معين باستخدام الأسماء الصحيحة.
     */
   /**
     * يقوم بحساب متوسط الأداء لكل قسم داخل فرع معين.
     * تم تغيير اسم الحقل المرجع من avg_total_score إلى avg_final_score للتوحيد.
     */  /**
     * يقوم بحساب متوسط الأداء لكل قسم داخل فرع معين.
     * ===> التصحيح هنا: استخدام LEFT JOIN <===
     * هذا يضمن ظهور القسم حتى لو لم يكن لديه تقييمات بعد.
     */
      /**
     * يقوم بحساب متوسط الأداء لكل قسم داخل فرع معين.
     * ===> النسخة النهائية والمصححة للاستعلام <===
     */
  /*  public function getDepartmentLevelReportByBranch($branchId) {
        // هذا الاستعلام يضمن أننا نبدأ بالأقسام الصحيحة التابعة للفرع،
        // ثم نجلب الدورات والتقييمات المرتبطة بها إن وجدت.
        $sql = "SELECT
                    d.id AS department_id,
                    d.name AS department_name,
                    AVG(ce.final_score) AS avg_final_score
                FROM
                    departments AS d
                LEFT JOIN
                    training_courses AS tc ON d.id = tc.department_id
                LEFT JOIN
                    course_evaluations AS ce ON tc.id = ce.course_id
                WHERE
                    d.branch_id = :branch_id
                GROUP BY
                    d.id, d.name
                ORDER BY
                    d.name";
        
        $this->db->query($sql);
        $this->db->bind(':branch_id', $branchId);
        return $this->db->fetchAll();
    }*/

        
    /**
     * يقوم بحساب متوسط الأداء لكل قسم داخل فرع معين.
     * ===> النسخة النهائية والمصححة للاستعلام <===
     * هذا يضمن ظهور كل الأقسام في الفرع، حتى التي ليس لديها دورات أو تقييمات.
     */
    /**
     * يقوم بحساب متوسط الأداء لكل قسم داخل فرع معين باستخدام استعلام فرعي.
     * ===> النسخة النهائية والمضمونة <===
     */
    public function getDepartmentLevelReportByBranch($branchId) {
        $sql = "SELECT
                    d.id AS department_id,
                    d.name AS department_name,
                    -- الاستعلام الفرعي يحسب المتوسط لكل قسم على حدة
                    (SELECT AVG(ce.final_score)
                     FROM course_evaluations ce
                     JOIN training_courses tc ON ce.course_id = tc.id
                     WHERE tc.department_id = d.id) AS avg_final_score
                FROM
                    departments d
                WHERE
                    d.branch_id = :branch_id
                ORDER BY
                    d.name";
        
        $this->db->query($sql);
        $this->db->bind(':branch_id', $branchId);
        return $this->db->fetchAll();
    }
/**
     * يجلب تقريراً مجمعاً للدورات المقيمة لقسم معين.
     * تم توحيد الأسماء لتكون متوافقة مع JavaScript.
     */
    public function getGroupedCoursesReportByDepartment($departmentId) {
        $sql = "SELECT 
                    ce.id as evaluation_id,
                    ce.actual_attendees,
                    ce.expected_attendees,
                    (CASE WHEN ce.expected_attendees > 0 THEN (ce.actual_attendees / ce.expected_attendees * 50) ELSE 0 END) as attendance_component,
                    ce.program_regularity_weighted_score as regularity_component,
                    ce.participant_comprehension_weighted_score as comprehension_component,
                    ce.final_score, -- <== تم استخدام final_score مباشرة
                    tc.start_date,
                    cc.title as course_title
                FROM course_evaluations ce
                JOIN training_courses tc ON ce.course_id = tc.id
                JOIN course_catalog cc ON tc.catalog_id = cc.id
                WHERE tc.department_id = :department_id
                ORDER BY cc.title, tc.start_date DESC";
        
        $this->db->query($sql);
        $this->db->bind(':department_id', $departmentId);
        return $this->db->fetchAll();
    }
    
    // في ملف: app/Models/CourseEvaluation.php

// ... (الدوال الأخرى) ...

    /**
     * ==================================================
     *      ===>  النسخة النهائية والمحسّنة هنا  <===
     * ==================================================
     * تجلب بيانات تقييم واحد وتقوم بحساب الدرجات الديناميكية.
     */
    public function findById($id) {
        // 1. جلب البيانات الأساسية من قاعدة البيانات
        $sql = "SELECT 
                    ce.*,
                    tc.start_date, tc.end_date,
                    cc.title AS course_title,
                    b.name AS branch_name,
                    d.name AS department_name,
                    u.username AS evaluator_name
                FROM course_evaluations ce
                JOIN training_courses tc ON ce.course_id = tc.id
                JOIN course_catalog cc ON tc.catalog_id = cc.id
                LEFT JOIN branches b ON tc.branch_id = b.id
                LEFT JOIN departments d ON tc.department_id = d.id
                LEFT JOIN users u ON ce.evaluated_by = u.id
                WHERE ce.id = :id";

        $this->db->query($sql);
        $this->db->bind(':id', $id);
        $evaluation = $this->db->fetch();

        // إذا لم يتم العثور على التقييم، أرجع false
        if (!$evaluation) {
            return false;
        }

        // 2. ===> حساب القيم الديناميكية المفقودة <===

        // حساب درجة الحضور (من 50)
        if ($evaluation->expected_attendees > 0) {
            $attendance_ratio = $evaluation->actual_attendees / $evaluation->expected_attendees;
            $evaluation->attendance_score = $attendance_ratio * 50;
        } else {
            $evaluation->attendance_score = 0;
        }

        // حساب النتيجة الإجمالية (مجموع الدرجات الموزونة)
        $evaluation->total_score = $evaluation->attendance_score + 
                                   $evaluation->program_regularity_weighted_score + 
                                   $evaluation->participant_comprehension_weighted_score;

        // 3. إرجاع الكائن الكامل مع كل البيانات المحسوبة
        return $evaluation;
    }

} // نهاية الكلاس

