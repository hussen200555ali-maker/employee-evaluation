<?php
namespace App\Models;
use App\Core\Model;

class WorkshopEvaluation extends Model {

    public function create($data) {
        // data: workshop_id, expected_attendees, actual_attendees, program_regularity_weighted_score, notes, evaluated_by
        // سنحسب attendance_score و final_score هنا قبل الإدخال
        $expected = max(0, (int)$data['expected_attendees']);
        $actual = max(0, (int)$data['actual_attendees']);

        $attendance_ratio = $expected > 0 ? ($actual / $expected) : 0;
        $attendance_score = round($attendance_ratio * 80, 2); // 80% وزن الحضور

        $program_score = floatval($data['program_regularity_weighted_score']); // يفترض أن يكون 0..20
        if ($program_score < 0) $program_score = 0;
        if ($program_score > 20) $program_score = 20;

        $final_score = round($attendance_score + $program_score, 2);

        $sql = "INSERT INTO workshop_evaluations 
            (workshop_id, expected_attendees, actual_attendees, attendance_score, program_regularity_weighted_score, final_score, notes, evaluated_by)
            VALUES (:workshop_id, :expected_attendees, :actual_attendees, :attendance_score, :program_regularity_weighted_score, :final_score, :notes, :evaluated_by)";
        $this->db->query($sql);
        $this->db->bind(':workshop_id', $data['workshop_id']);
        $this->db->bind(':expected_attendees', $expected);
        $this->db->bind(':actual_attendees', $actual);
        $this->db->bind(':attendance_score', $attendance_score);
        $this->db->bind(':program_regularity_weighted_score', $program_score);
        $this->db->bind(':final_score', $final_score);
        $this->db->bind(':notes', $data['notes'] ?? null);
        $this->db->bind(':evaluated_by', $data['evaluated_by'] ?? null);
        $ok = $this->db->execute();
        if ($ok) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function findByWorkshopId($workshop_id) {
        $this->db->query("SELECT * FROM workshop_evaluations WHERE workshop_id = :wid");
        $this->db->bind(':wid', $workshop_id);
        return $this->db->fetch();
    }

    public function find($id) {
        $this->db->query("SELECT * FROM workshop_evaluations WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }

    /**
     * يعطي تفاصيل مع بيانات الورشة وبيانات الفرع/القسم والمقيم
     */
    public function getEvaluationDetails($evalId) {
        $sql = "SELECT we.*, tw.start_date, tw.end_date,
                       wc.title AS workshop_title,
                       b.name AS branch_name,
                       d.name AS department_name,
                       u.username AS evaluator_name
                FROM workshop_evaluations we
                JOIN training_workshops tw ON we.workshop_id = tw.id
                JOIN workshop_catalog wc ON tw.catalog_id = wc.id
                LEFT JOIN branches b ON tw.branch_id = b.id
                LEFT JOIN departments d ON tw.department_id = d.id
                LEFT JOIN users u ON we.evaluated_by = u.id
                WHERE we.id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $evalId);
        $eval = $this->db->fetch();
        return $eval;
    }
  public function getAll() {
        $sql = "SELECT we.*, w.title as workshop_title
                FROM workshop_evaluations we
                JOIN training_workshops w ON we.workshop_id = w.id
                ORDER BY we.created_at DESC";
        $this->db->query($sql);
        return $this->db->fetchAll();
    }

     public function findById($id) {
        $sql = "SELECT we.*, wc.title AS workshop_title,
                       w.start_date, w.end_date,
                       b.name AS branch_name,
                       d.name AS department_name
                FROM workshop_evaluations we
                JOIN training_workshops w ON we.workshop_id = w.id
                JOIN workshop_catalog wc ON w.catalog_id = wc.id
                LEFT JOIN branches b ON w.branch_id = b.id
                LEFT JOIN departments d ON w.department_id = d.id
                WHERE we.id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }
    // دوال تقريرية بسيطة مشابهة لدوال CourseEvaluation إن احتجت

// تابع للوحة عرض التقارير
/*public function getAverageScore() {
    $this->db->query("SELECT AVG(final_score) as avg_score FROM workshop_evaluations");
    $row = $this->db->fetch();
    return round($row->avg_score ?? 0, 2);
}
*/
public function getTopWorkshops($limit = 5) {
    $sql = "SELECT wc.title, we.final_score
            FROM workshop_evaluations we
            JOIN training_workshops tw ON we.workshop_id = tw.id
            JOIN workshop_catalog wc ON tw.catalog_id = wc.id
            ORDER BY we.final_score DESC
            LIMIT :limit";
    $this->db->query($sql);
    $this->db->bind(':limit', $limit);
    return $this->db->fetchAll();
}

public function getAverageScore() {
    $this->db->query("SELECT AVG(final_score) as avg_score FROM workshop_evaluations");
    return round($this->db->fetch()->avg_score ?? 0, 2);
}

  }
