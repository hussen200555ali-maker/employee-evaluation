<?php
namespace App\Models;
use App\Core\Model;

class WorkshopCatalog extends Model {

    public function getAll() {
        $this->db->query("SELECT * FROM workshop_catalog ORDER BY created_at DESC");
        return $this->db->fetchAll();
    }

    public function find($id) {
        $this->db->query("SELECT * FROM workshop_catalog WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO workshop_catalog (title, description, category, created_by) 
                VALUES (:title, :description, :category, :created_by)";
        $this->db->query($sql);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':category', $data['category']);
        $this->db->bind(':created_by', $data['created_by']);
        return $this->db->execute();
    }

    public function update($data) {
        $sql = "UPDATE workshop_catalog 
                   SET title = :title, description = :description, category = :category
                 WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':category', $data['category']);
        return $this->db->execute();
    }

  public function hasWorkshops($id) {
    $this->db->query("SELECT COUNT(*) as cnt FROM training_workshops WHERE catalog_id = :id");
    $this->db->bind(':id', $id);
    $row = $this->db->fetch();
    return $row && $row->cnt > 0;
}

public function findByName(string $title) {
    $this->db->query("SELECT * FROM workshop_catalog WHERE TRIM(LOWER(title)) = TRIM(LOWER(:title)) LIMIT 1");
    $this->db->bind(':title', $title);
    return $this->db->fetch() ?: null;
}

public function createGetId($data) {
    if ($this->create($data)) {
        return $this->db->lastInsertId();
    }
    return false;
}

public function delete($id) {
    // حماية: لا تحذف إذا هناك ورش مرتبطة
    if ($this->hasWorkshops($id)) {
        return false;
    }

    $this->db->query("DELETE FROM workshop_catalog WHERE id = :id");
    $this->db->bind(':id', $id);
    return $this->db->execute();
}

}
