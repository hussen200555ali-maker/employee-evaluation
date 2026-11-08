<?php
namespace App\Models;
use App\Core\Model;

/**
 * CD-036 : TrainingCatalog — unified catalog (training_catalogs table)
 * Framework-matched: extends App\Core\Model, uses $this->db (FETCH_OBJ).
 */
class TrainingCatalog extends Model {

    public function all(bool $activeOnly = false): array {
        $sql = "SELECT * FROM training_catalogs";
        if ($activeOnly) { $sql .= " WHERE status = 'active'"; }
        $sql .= " ORDER BY name ASC";
        $this->db->query($sql);
        return $this->db->fetchAll() ?: [];
    }

    /** Active catalogs usable for a given program type (type matches OR 'both'). */
    public function byType(string $type): array {
        $this->db->query("SELECT * FROM training_catalogs
            WHERE status = 'active' AND (type = :type OR type = 'both')
            ORDER BY name ASC");
        $this->db->bind(':type', $type);
        return $this->db->fetchAll() ?: [];
    }

    public function find(int $id) {
        $this->db->query("SELECT * FROM training_catalogs WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch() ?: null;
    }

    /** @return int|false new id */
    public function create(array $d) {
        $this->db->query("INSERT INTO training_catalogs
            (name, type, description, category, status, created_by_user_id)
            VALUES (:name, :type, :description, :category, :status, :cb)");
        $this->db->bind(':name', $d['name']);
        $this->db->bind(':type', $d['type'] ?? 'both');
        $this->db->bind(':description', $d['description'] ?? null);
        $this->db->bind(':category', $d['category'] ?? null);
        $this->db->bind(':status', $d['status'] ?? 'active');
        $this->db->bind(':cb', $d['created_by_user_id'] ?? null);
        if ($this->db->execute()) { return $this->db->lastInsertId(); }
        return false;
    }

    public function update(int $id, array $d): bool {
        $this->db->query("UPDATE training_catalogs
            SET name = :name, type = :type, description = :description,
                category = :category, status = :status
            WHERE id = :id");
        $this->db->bind(':name', $d['name']);
        $this->db->bind(':type', $d['type'] ?? 'both');
        $this->db->bind(':description', $d['description'] ?? null);
        $this->db->bind(':category', $d['category'] ?? null);
        $this->db->bind(':status', $d['status'] ?? 'active');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function delete(int $id): bool {
        $this->db->query("DELETE FROM training_catalogs WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
