<?php
namespace App\Models;

use App\Core\Model;

class EvaluationSchedule extends Model {

    public function getAll() {
        $sql = "SELECT s.*, t.template_name, 
                CASE WHEN s.entity_type='branch' THEN b.name ELSE d.name END as unit_name,
                u.username as created_by_name
                FROM evaluation_schedules s
                JOIN form_templates t ON s.template_id=t.id
                LEFT JOIN branches b ON s.entity_type='branch' AND s.unit_id=b.id
                LEFT JOIN departments d ON s.entity_type='department' AND s.unit_id=d.id
                LEFT JOIN users u ON s.created_by=u.id
                ORDER BY s.created_at DESC";
        $this->db->query($sql);
        return $this->db->fetchAll();
    }

    public function getByUnit($entityType, $unitId) {
        $this->db->query("SELECT s.*, t.template_name FROM evaluation_schedules s JOIN form_templates t ON s.template_id=t.id WHERE s.entity_type=:etype AND s.unit_id=:uid AND s.is_active=1 ORDER BY s.start_date DESC");
        $this->db->bind(':etype', $entityType);
        $this->db->bind(':uid', $unitId);
        return $this->db->fetchAll();
    }

    public function findById($id) {
        $this->db->query("SELECT * FROM evaluation_schedules WHERE id=:id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO evaluation_schedules (template_id, entity_type, unit_id, periodicity, title, start_date, end_date, is_active, created_by)
                VALUES (:tid, :etype, :uid, :period, :title, :sdate, :edate, :active, :uid_creator)";
        $this->db->query($sql);
        $this->db->bind(':tid', $data['template_id']);
        $this->db->bind(':etype', $data['entity_type']);
        $this->db->bind(':uid', $data['unit_id']);
        $this->db->bind(':period', $data['periodicity']);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':sdate', $data['start_date']);
        $this->db->bind(':edate', $data['end_date']);
        $this->db->bind(':active', $data['is_active']);
        $this->db->bind(':uid_creator', $data['created_by']);
        $this->db->execute();
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE evaluation_schedules SET template_id=:tid, entity_type=:etype, unit_id=:uid, periodicity=:period, title=:title, start_date=:sdate, end_date=:edate, is_active=:active WHERE id=:id";
        $this->db->query($sql);
        $this->db->bind(':tid', $data['template_id']);
        $this->db->bind(':etype', $data['entity_type']);
        $this->db->bind(':uid', $data['unit_id']);
        $this->db->bind(':period', $data['periodicity']);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':sdate', $data['start_date']);
        $this->db->bind(':edate', $data['end_date']);
        $this->db->bind(':active', $data['is_active']);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function delete($id) {
        $this->db->query("DELETE FROM evaluation_schedules WHERE id=:id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function hasEvaluations($scheduleId) {
        $this->db->query("SELECT COUNT(*) as cnt FROM evaluations WHERE schedule_id=:sid");
        $this->db->bind(':sid', $scheduleId);
        $r = $this->db->fetch();
        return $r && $r->cnt > 0;
    }
}
