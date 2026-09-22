<?php
// في ملف /app/Models/TrainingCourse.php

namespace App\Models;
use App\Core\Model;

class TrainingCourse extends Model {

    /**
     * يجلب كل الدورات التدريبية مع معلومات إضافية.
     */
  // في ملف /app/Models/TrainingCourse.php
/**
     * ==================================================
     *           ===>  التصحيح الأول هنا  <===
     * ==================================================
     * قمنا بإضافة LEFT JOIN لجلب evaluation_id
     */
    public function getAll() {
        $sql = "SELECT 
                    tc.id, tc.start_date, tc.end_date, tc.status,
                    cc.title as course_title, 
                    b.name as branch_name,
                    d.name as department_name,
                    ce.id as evaluation_id 
                FROM training_courses tc
                JOIN course_catalog cc ON tc.catalog_id = cc.id
                LEFT JOIN branches b ON tc.branch_id = b.id
                LEFT JOIN departments d ON tc.department_id = d.id
                LEFT JOIN course_evaluations ce ON ce.course_id = tc.id
                ORDER BY tc.start_date DESC";
        $this->db->query($sql);
        return $this->db->fetchAll();
    }

   /**
     * ==================================================
     *           ===>  الدالة اضافة دورة جديدة  <===
     * ==================================================
     */
public function create($data) {
    $sql = "INSERT INTO training_courses 
               (catalog_id, branch_id, department_id, start_date, end_date, status, created_by) 
            VALUES (:catalog_id, :branch_id, :department_id, :start_date, :end_date, :status, :created_by)";
    
    $this->db->query($sql);
    $this->db->bind(':catalog_id', $data['catalog_id']);
    $this->db->bind(':branch_id', $data['branch_id']);
    $this->db->bind(':department_id', $data['department_id']);
    $this->db->bind(':start_date', $data['start_date']);
    $this->db->bind(':end_date', $data['end_date']);
    $this->db->bind(':status', $data['status']);
    $this->db->bind(':created_by', $data['created_by']);
    
    return $this->db->execute();
}

        /**
     * ==================================================
     *           ===>  أضف الدالة الجديدة هنا  <===
     * ==================================================
     * 
     * تنشئ سجلاً جديداً وتعيد الـ ID الخاص به.
     *
     * @param array $data بيانات الدورة.
     * @return mixed ID السجل الجديد عند النجاح, أو false عند الفشل.
     */
    public function createAndGetId($data) {
        if ($this->create($data)) {
            return $this->db->lastInsertId();
        }
        return false;
    }

public function update($data) {
    $sql = "UPDATE training_courses SET 
                catalog_id = :catalog_id,
                branch_id = :branch_id,
                department_id = :department_id,
                start_date = :start_date,
                end_date = :end_date,
                status = :status
            WHERE id = :id";
    
    $this->db->query($sql);
    $this->db->bind(':id', $data['id']);
    $this->db->bind(':catalog_id', $data['catalog_id']);
    $this->db->bind(':branch_id', $data['branch_id']);
    $this->db->bind(':department_id', $data['department_id']);
    $this->db->bind(':start_date', $data['start_date']);
    $this->db->bind(':end_date', $data['end_date']);
    $this->db->bind(':status', $data['status']);
    
    return $this->db->execute();
}

    public function delete($id) {
        $this->db->query("DELETE FROM training_courses WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

  

// ... ( دالة findById)
  public function findById($id) {
        $sql = "SELECT 
                    tc.*,
                    cc.title as course_title
                FROM training_courses tc
                JOIN course_catalog cc ON tc.catalog_id = cc.id
                WHERE tc.id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }


// في TrainingCourse.php (Model)
    public function updateStatus($id, $status) {
        $this->db->query("UPDATE training_courses SET status = :status WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':status', $status);
        return $this->db->execute();
    }
 /**
     * ==================================================
     *           ===>  أضف الدالة الجديدة هنا  <===
     * ==================================================
     * 
     * تجلب تقريراً مجمعاً لكل قسم داخل فرع معين.
     */
    public function getDepartmentLevelReportByBranch($branch_id) {
        $sql = "SELECT 
                    d.id as department_id,
                    d.name as department_name,
                    COUNT(DISTINCT tc.id) as total_courses,
                    AVG(ce.final_score) as avg_final_score
                FROM training_courses tc
                JOIN departments d ON tc.department_id = d.id
                JOIN course_evaluations ce ON tc.id = ce.course_id
                WHERE tc.branch_id = :branch_id
                GROUP BY d.id, d.name
                ORDER BY d.name";
        
        $this->db->query($sql);
        $this->db->bind(':branch_id', $branch_id);
        return $this->db->fetchAll();
    }

    public function findByNameAndBranch(string $title, ?int $branchId = null) {
        $sql = "SELECT tc.id, tc.catalog_id, tc.branch_id, tc.department_id, tc.start_date, tc.end_date, tc.status,
                       cc.title as course_title, b.name as branch_name, d.name as department_name
                FROM training_courses tc
                JOIN course_catalog cc ON tc.catalog_id = cc.id
                LEFT JOIN branches b ON tc.branch_id = b.id
                LEFT JOIN departments d ON tc.department_id = d.id
                WHERE TRIM(LOWER(cc.title)) = TRIM(LOWER(:title))";
        $params = ['title' => $title];
        if ($branchId !== null) {
            $sql .= " AND tc.branch_id = :branch_id";
            $params['branch_id'] = $branchId;
        }
        $sql .= " LIMIT 1";
        $this->db->query($sql);
        foreach ($params as $k => $v) { $this->db->bind(':' . $k, $v); }
        return $this->db->fetch() ?: null;
    }

    public function findOrCreateByName(string $title, ?int $branchId, ?int $deptId, string $status, string $startDate, string $endDate, int $userId): int {
        $catalogModel = new \App\Models\CourseCatalog();
        $catalog = $catalogModel->findByName($title);
        if (!$catalog) {
            $catalogId = $catalogModel->createGetId([
                'title' => trim($title),
                'description' => '',
                'category' => null,
                'created_by' => $userId,
            ]);
            if (!$catalogId) { throw new \RuntimeException('فشل إنشاء كتالوج الدورة: ' . $title); }
        } else {
            $catalogId = (int)$catalog['id'];
        }
        $existing = $this->findByNameAndBranch($title, $branchId);
        if ($existing) {
            return (int)$existing['id'];
        }
        $id = $this->createAndGetId([
            'catalog_id' => $catalogId,
            'branch_id' => $branchId,
            'department_id' => $deptId,
            'start_date' => $startDate ?: date('Y-m-d'),
            'end_date' => $endDate ?: date('Y-m-d'),
            'status' => $status ?: 'completed',
            'created_by' => $userId,
        ]);
        if (!$id) { throw new \RuntimeException('فشل إنشاء الدورة: ' . $title); }
        return (int)$id;
    }
}
