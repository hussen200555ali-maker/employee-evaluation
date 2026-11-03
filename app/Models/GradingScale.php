<?php

namespace App\Models;

use App\Core\Model;

class GradingScale extends Model {

    public function getAll(): array {
        $this->db->query(
            'SELECT * FROM grading_scales ORDER BY scale_name, sort_order, min_value, id'
        );
        return $this->db->fetchAll();
    }

    public function findById(int $id): ?object {
        $this->db->query('SELECT * FROM grading_scales WHERE id = :id');
        $this->db->bind(':id', $id);
        $scale = $this->db->fetch();
        return $scale ?: null;
    }

    public function findActiveById(int $id): ?object {
        $this->db->query('SELECT * FROM grading_scales WHERE id = :id AND is_active = 1');
        $this->db->bind(':id', $id);
        $scale = $this->db->fetch();
        return $scale ?: null;
    }

    public function getActive(): array {
        $this->db->query(
            'SELECT * FROM grading_scales
             WHERE is_active = 1
             ORDER BY scale_name, sort_order, min_value, id'
        );
        return $this->db->fetchAll();
    }

    public function hasOverlappingRange(
        string $scaleName,
        float $minValue,
        float $maxValue,
        ?int $excludeId = null
    ): bool {
        $sql =
            'SELECT COUNT(*) FROM grading_scales
             WHERE scale_name = :scale_name
               AND min_value <= :max_value
               AND max_value >= :min_value';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }
        $this->db->query($sql);
        $this->db->bind(':scale_name', $scaleName);
        $this->db->bind(':min_value', $minValue);
        $this->db->bind(':max_value', $maxValue);
        if ($excludeId !== null) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        return (int)$this->db->fetchColumn() > 0;
    }

    public function gradeLabelExists(string $scaleName, string $gradeLabel, ?int $excludeId = null): bool {
        $sql = 'SELECT COUNT(*) FROM grading_scales
                WHERE scale_name = :scale_name AND grade_label = :grade_label';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }
        $this->db->query($sql);
        $this->db->bind(':scale_name', $scaleName);
        $this->db->bind(':grade_label', $gradeLabel);
        if ($excludeId !== null) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        return (int)$this->db->fetchColumn() > 0;
    }

    public function create(array $data): int {
        $this->db->query(
            'INSERT INTO grading_scales
                (scale_name, grade_label, min_value, max_value, numeric_value, sort_order, is_active)
             VALUES
                (:scale_name, :grade_label, :min_value, :max_value, :numeric_value, :sort_order, :is_active)'
        );
        $this->bindData($data);
        if (!$this->db->execute()) {
            throw new \RuntimeException('تعذر إنشاء درجة التقدير.');
        }
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $this->db->query(
            'UPDATE grading_scales
             SET scale_name = :scale_name,
                 grade_label = :grade_label,
                 min_value = :min_value,
                 max_value = :max_value,
                 numeric_value = :numeric_value,
                 sort_order = :sort_order,
                 is_active = :is_active
             WHERE id = :id'
        );
        $this->bindData($data);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function delete(int $id): bool {
        $this->db->query('DELETE FROM grading_scales WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    private function bindData(array $data): void {
        $this->db->bind(':scale_name', $data['scale_name']);
        $this->db->bind(':grade_label', $data['grade_label']);
        $this->db->bind(':min_value', $data['min_value']);
        $this->db->bind(':max_value', $data['max_value']);
        $this->db->bind(':numeric_value', $data['numeric_value']);
        $this->db->bind(':sort_order', $data['sort_order']);
        $this->db->bind(':is_active', $data['is_active']);
    }
}
