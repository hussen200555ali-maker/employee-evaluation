<?php
namespace App\Models;
use App\Core\Model;

class TrainingWorkshop extends Model {

    public function getAll() {
    $sql = "SELECT 
                tw.*,
                wc.title AS workshop_title,
                b.name AS branch_name,
                d.name AS department_name,
                we.id AS evaluation_id
            FROM training_workshops tw
            JOIN workshop_catalog wc ON tw.catalog_id = wc.id
            LEFT JOIN branches b ON tw.branch_id = b.id
            LEFT JOIN departments d ON tw.department_id = d.id
            LEFT JOIN workshop_evaluations we ON we.workshop_id = tw.id
            ORDER BY tw.start_date DESC";
    $this->db->query($sql);
    return $this->db->fetchAll();
}


  public function create($data) {
    $sql = "INSERT INTO training_workshops 
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

public function createAndGetId($data) {
    if ($this->create($data)) {
        return $this->db->lastInsertId();
    }
    return false;
}

  public function findById($id) {
    $sql = "SELECT 
                tw.*, 
                wc.title AS workshop_title
            FROM training_workshops tw
            JOIN workshop_catalog wc ON tw.catalog_id = wc.id
            WHERE tw.id = :id";
    $this->db->query($sql);
    $this->db->bind(':id', $id);
    return $this->db->fetch();
}


public function update($data) {
    $sql = "UPDATE training_workshops SET 
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
        $this->db->query("DELETE FROM training_workshops WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // لوحة العرض لتقارير
  /*  public function countAll() {
    $this->db->query("SELECT COUNT(*) as cnt FROM training_workshops");
    $row = $this->db->fetch();
    return $row->cnt ?? 0;
}

public function countByStatus() {
    $this->db->query("SELECT status, COUNT(*) as cnt FROM training_workshops GROUP BY status");
    return $this->db->fetchAll();
}
*/
public function countByBranch() {
    $this->db->query("SELECT b.name as branch_name, COUNT(*) as cnt
                      FROM training_workshops tw
                      LEFT JOIN branches b ON tw.branch_id = b.id
                      GROUP BY b.name");
    return $this->db->fetchAll();
}

public function countByMonth() {
    $this->db->query("SELECT DATE_FORMAT(start_date, '%Y-%m') as month, COUNT(*) as cnt
                      FROM training_workshops
                      GROUP BY month ORDER BY month ASC");
    return $this->db->fetchAll();
}
public function getTotal() {
    $this->db->query("SELECT COUNT(*) as cnt FROM training_workshops");
    return $this->db->fetch()->cnt ?? 0;
}

public function getStatusCounts() {
    $this->db->query("SELECT status, COUNT(*) as cnt FROM training_workshops GROUP BY status");
    $rows = $this->db->fetchAll();
    $result = [];
    foreach ($rows as $r) {
        $result[$r->status] = $r->cnt;
    }
    return $result;
}

public function getAverageScore() {
    $this->db->query("SELECT AVG(final_score) as avg_score FROM workshop_evaluations");
    return round($this->db->fetch()->avg_score ?? 0, 2);
}

public function getDistributionByBranch() {
    $this->db->query("SELECT b.name as branch_name, COUNT(*) as cnt
                      FROM training_workshops tw
                      JOIN branches b ON tw.branch_id = b.id
                      GROUP BY b.name");
    return $this->db->fetchAll();
}

public function getTopWorkshops() {
    $this->db->query("SELECT wc.title, we.final_score
                      FROM workshop_evaluations we
                      JOIN training_workshops tw ON we.workshop_id = tw.id
                      JOIN workshop_catalog wc ON tw.catalog_id = wc.id
                      ORDER BY we.final_score DESC
                      LIMIT 5");
    return $this->db->fetchAll();
}
public function countAll() {
    $this->db->query("SELECT COUNT(*) as cnt FROM training_workshops");
    return $this->db->fetch()->cnt ?? 0;
}

public function countByStatus() {
    $this->db->query("SELECT status, COUNT(*) as cnt FROM training_workshops GROUP BY status");
    $rows = $this->db->fetchAll();
    $result = [];
    foreach ($rows as $r) {
        $result[$r->status] = $r->cnt;
    }
    return $result;
}

public function countByBranchWithIds() {
    $sql = "SELECT b.id, b.name, COUNT(tw.id) as workshop_count
            FROM branches b
            LEFT JOIN training_workshops tw ON tw.branch_id = b.id
            GROUP BY b.id, b.name";
    $this->db->query($sql);
    return $this->db->fetchAll();
}
/*
public function getByDepartmentWithEvaluations($departmentId) {
    $sql = "SELECT tw.*, wc.title as workshop_title, we.final_score
            FROM training_workshops tw
            JOIN workshop_catalog wc ON tw.catalog_id = wc.id
            LEFT JOIN workshop_evaluations we ON we.workshop_id = tw.id
            WHERE tw.department_id = :dept";
    $this->db->query($sql);
    $this->db->bind(':dept', $departmentId);
    return $this->db->fetchAll();
}*/
// في ملف app/Models/TrainingWorkshop.php
/*
public function getByDepartmentWithEvaluations($departmentId) {
    // ===> هذا هو الاستعلام المصحح <===
    // لقد أضفنا كل الحقول من جدول workshop_evaluations (we.*)
    $sql = "SELECT 
                tw.*, 
                wc.title as workshop_title, 
                we.final_score,
                we.expected_attendees,         -- أضفنا هذا
                we.actual_attendees,           -- أضفنا هذا
                we.program_regularity_weighted_score -- أضفنا هذا
            FROM training_workshops tw
            JOIN workshop_catalog wc ON tw.catalog_id = wc.id
            -- استخدمنا LEFT JOIN لضمان ظهور الورشة حتى لو لم يتم تقييمها بعد
            LEFT JOIN workshop_evaluations we ON we.workshop_id = tw.id
            WHERE tw.department_id = :dept
            ORDER BY tw.start_date DESC"; // تم إضافة ترتيب ليكون التقرير منظماً

    $this->db->query($sql);
    $this->db->bind(':dept', $departmentId);
    return $this->db->fetchAll();
}
*/
// في ملف app/Models/TrainingWorkshop.php

public function getByDepartmentWithEvaluations($departmentId) {
    // ===> هذا هو الاستعلام المصحح والنهائي <===
    // لقد أضفنا we.id AS evaluation_id لجلب معرف التقييم
    $sql = "SELECT 
                tw.*, 
                wc.title as workshop_title, 
                we.id as evaluation_id,  -- <--- هذا هو السطر الأهم الذي تم إضافته
                we.final_score,
                we.expected_attendees,
                we.actual_attendees,
                we.program_regularity_weighted_score
            FROM training_workshops tw
            JOIN workshop_catalog wc ON tw.catalog_id = wc.id
            LEFT JOIN workshop_evaluations we ON we.workshop_id = tw.id
            WHERE tw.department_id = :dept
            ORDER BY tw.start_date DESC";

    $this->db->query($sql);
    $this->db->bind(':dept', $departmentId);
    return $this->db->fetchAll();
}

    public function findByNameAndBranch(string $title, ?int $branchId = null) {
        $sql = "SELECT tw.id, tw.catalog_id, tw.branch_id, tw.department_id, tw.start_date, tw.end_date, tw.status,
                       wc.title as workshop_title, b.name as branch_name, d.name as department_name
                FROM training_workshops tw
                JOIN workshop_catalog wc ON tw.catalog_id = wc.id
                LEFT JOIN branches b ON tw.branch_id = b.id
                LEFT JOIN departments d ON tw.department_id = d.id
                WHERE TRIM(LOWER(wc.title)) = TRIM(LOWER(:title))";
        $params = ['title' => $title];
        if ($branchId !== null) {
            $sql .= " AND tw.branch_id = :branch_id";
            $params['branch_id'] = $branchId;
        }
        $sql .= " LIMIT 1";
        $this->db->query($sql);
        foreach ($params as $k => $v) { $this->db->bind(':' . $k, $v); }
        return $this->db->fetch() ?: null;
    }

    public function findOrCreateByName(string $title, ?int $branchId, ?int $deptId, string $status, string $startDate, string $endDate, int $userId): int {
        $catalogModel = new \App\Models\WorkshopCatalog();
        $catalog = $catalogModel->findByName($title);
        if (!$catalog) {
            $catalogId = $catalogModel->createGetId([
                'title' => trim($title),
                'description' => '',
                'category' => null,
                'created_by' => $userId,
            ]);
            if (!$catalogId) { throw new \RuntimeException('فشل إنشاء كتالوج الورشة: ' . $title); }
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
        if (!$id) { throw new \RuntimeException('فشل إنشاء الورشة: ' . $title); }
        return (int)$id;
    }

}
