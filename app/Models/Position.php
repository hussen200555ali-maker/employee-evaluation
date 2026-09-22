<?php
namespace App\Models;

use App\Core\Model;

class Position extends Model {

    /**
     * يجلب جميع المناصب من قاعدة البيانات.
     * @return array
     */
    public function getAll() {
        // ===> تحسين: جلب عدد الموظفين في كل منصب <===
        $sql = "SELECT p.*, COUNT(e.id) as employee_count
                FROM positions p
                LEFT JOIN employees e ON p.id = e.position_id
                GROUP BY p.id
                ORDER BY p.title ASC";
        $this->db->query($sql);
        return $this->db->fetchAll();
    }

    /**
     * ===> دالة جديدة <===
     * يبحث عن منصب بواسطة ID.
     * @param int $id
     * @return mixed
     */
    public function findById($id) {
        $this->db->query("SELECT * FROM positions WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }

    /**
     * ===> دالة جديدة <===
     * ينشئ منصباً جديداً.
     * @param array $data
     * @return mixed
     */
    public function create($data) {
        $this->db->query('INSERT INTO positions (title, category) VALUES (:title, :category)');
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':category', $data['category']);
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * ===> دالة جديدة <===
     * يحدّث بيانات منصب موجود.
     * @param array $data
     * @return bool
     */
    public function update($data) {
        $this->db->query('UPDATE positions SET title = :title, category = :category WHERE id = :id');
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':category', $data['category']);
        return $this->db->execute();
    }

    /**
     * ===> دالة جديدة <===
     * يحذف منصباً من قاعدة البيانات.
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $this->db->query('DELETE FROM positions WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * ===> دالة جديدة <===
     * يتحقق مما إذا كان المنصب مرتبطاً بأي موظفين.
     * @param int $id
     * @return bool
     */
    public function hasEmployees($id) {
        $this->db->query('SELECT COUNT(*) as count FROM employees WHERE position_id = :id');
        $this->db->bind(':id', $id);
        $row = $this->db->fetch();
        return $row->count > 0;
    }
}
