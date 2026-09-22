<?php
// ظ†ط­ط¯ط¯ ط§ظ„ظ€ Namespace ظ„ظٹط¹ظƒط³ ظ…ظƒط§ظ† ط§ظ„ظ…ظ„ظپ.
namespace App\Models;

// ط§ط³طھظٹط±ط§ط¯ ط§ظ„ظپط¦ط© ط§ظ„ط£ط³ط§ط³ظٹط© ظ„ظ„ظ…ظˆط¯ظ„.
use App\Core\Model;

/**
 * ظ…ظˆط¯ظ„ ط§ظ„ظ‚ط³ظ… (Department)
 * ظ…ط³ط¤ظˆظ„ ط¹ظ† ظƒظ„ ط§ظ„ط¹ظ…ظ„ظٹط§طھ ط§ظ„ظ…طھط¹ظ„ظ‚ط© ط¨ط¬ط¯ظˆظ„ 'departments' ظپظٹ ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ.
 */
class Department extends Model {

  
    /**
     * ظٹط¬ظ„ط¨ ط¬ظ…ظٹط¹ ط§ظ„ط£ظ‚ط³ط§ظ… ظ…ط¹ ظ…ط¹ظ„ظˆظ…ط§طھ ط¥ط¶ط§ظپظٹط© (ط§ط³ظ… ط§ظ„ظپط±ط¹, ط¹ط¯ط¯ ط§ظ„ظ…ظˆط¸ظپظٹظ†, ط¥ط­طµط§ط¦ظٹط§طھ ط§ظ„ظˆط±ط´).
     *
     * @return array - ظ…طµظپظˆظپط© ظ…ظ† ظƒط§ط¦ظ†ط§طھ ط§ظ„ط£ظ‚ط³ط§ظ….
     */
    public function getAll() {
        // ط¬ظ…ظ„ط© SQL ظ„ط¬ظ„ط¨ ظƒظ„ ط§ظ„ط£ظ‚ط³ط§ظ… ظ…ط¹ ط§ط³ظ… ط§ظ„ظپط±ط¹طŒ ط¹ط¯ط¯ ط§ظ„ظ…ظˆط¸ظپظٹظ†طŒ ظˆط¥ط­طµط§ط¦ظٹط§طھ ط§ظ„ظˆط±ط´ ط§ظ„ظ…ظƒطھظ…ظ„ط©
        $sql = "SELECT 
                    d.*, 
                    b.name as branch_name,
                    COUNT(DISTINCT e.id) as employee_count,
                    COUNT(DISTINCT w.id) AS total_workshops,
                    AVG(we.final_score) AS avg_final_score
                FROM 
                    departments d
                LEFT JOIN 
                    branches b ON d.branch_id = b.id
                LEFT JOIN 
                    employees e ON d.id = e.department_id
                LEFT JOIN 
                    training_workshops w ON d.id = w.department_id AND w.status = 'completed'
                LEFT JOIN 
                    workshop_evaluations we ON w.id = we.workshop_id
                GROUP BY 
                    d.id, d.name, b.name
                ORDER BY 
                    b.name, d.name ASC";
        
        // طھط­ط¶ظٹط± ظˆطھظ†ظپظٹط° ط§ظ„ط§ط³طھط¹ظ„ط§ظ….
        $this->db->query($sql);
        return $this->db->fetchAll();
    }

    /**
     * ظٹظ†ط´ط¦ ظ‚ط³ظ…ط§ظ‹ ط¬ط¯ظٹط¯ط§ظ‹ ظپظٹ ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ.
     *
     * @param array $data - ظ…طµظپظˆظپط© طھط­طھظˆظٹ ط¹ظ„ظ‰ ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ‚ط³ظ… (name, branch_id).
     * @return mixed - ظٹط¹ظٹط¯ ID ط§ظ„ظ‚ط³ظ… ط§ظ„ط¬ط¯ظٹط¯ ط¹ظ†ط¯ ط§ظ„ظ†ط¬ط§ط­, ط£ظˆ false ط¹ظ†ط¯ ط§ظ„ظپط´ظ„.
     */
    public function create($data) {
        $this->db->query('INSERT INTO departments (name, branch_id) VALUES (:name, :branch_id)');
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':branch_id', $data['branch_id']);
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * ظٹط­ط¯ظ‘ط« ط¨ظٹط§ظ†ط§طھ ظ‚ط³ظ… ظ…ظˆط¬ظˆط¯.
     *
     * @param array $data - ظ…طµظپظˆظپط© طھط­طھظˆظٹ ط¹ظ„ظ‰ ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ‚ط³ظ… (id, name, branch_id).
     * @return bool - true ط¹ظ†ط¯ ط§ظ„ظ†ط¬ط§ط­, false ط¹ظ†ط¯ ط§ظ„ظپط´ظ„.
     */
    public function update($data) {
        $this->db->query('UPDATE departments SET name = :name, branch_id = :branch_id WHERE id = :id');
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':branch_id', $data['branch_id']);
        return $this->db->execute();
    }

    /**
     * ظٹط­ط°ظپ ظ‚ط³ظ…ط§ظ‹ ظ…ظ† ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ.
     *
     * @param int $id - ظ…ط¹ط±ظپ ط§ظ„ظ‚ط³ظ… ط§ظ„ظ…ط±ط§ط¯ ط­ط°ظپظ‡.
     * @return bool - true ط¹ظ†ط¯ ط§ظ„ظ†ط¬ط§ط­, false ط¹ظ†ط¯ ط§ظ„ظپط´ظ„.
     */
    public function delete($id) {
        $this->db->query('DELETE FROM departments WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * ظٹط¬ظ„ط¨ ظ‚ط³ظ…ط§ظ‹ ظˆط§ط­ط¯ط§ظ‹ ط¨ظˆط§ط³ط·ط© ID.
     * (ط³ظ†ط­طھط§ط¬ظ‡ ظ„طھط³ط¬ظٹظ„ ط§ظ„ط¨ظٹط§ظ†ط§طھ ظ‚ط¨ظ„ ط§ظ„ط­ط°ظپ ظپظٹ ط³ط¬ظ„ ط§ظ„طھط¯ظ‚ظٹظ‚).
     *
     * @param int $id - ظ…ط¹ط±ظپ ط§ظ„ظ‚ط³ظ….
     * @return mixed - ظƒط§ط¦ظ† ط§ظ„ظ‚ط³ظ… ط£ظˆ false.
     */
    public function findById($id) {
        $this->db->query("SELECT * FROM departments WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }

    /**
     * ظٹطھط­ظ‚ظ‚ ظ…ظ…ط§ ط¥ط°ط§ ظƒط§ظ† ط§ظ„ظ‚ط³ظ… ظٹط­طھظˆظٹ ط¹ظ„ظ‰ ظ…ظˆط¸ظپظٹظ†.
     *
     * @param int $id - ظ…ط¹ط±ظپ ط§ظ„ظ‚ط³ظ….
     * @return bool - true ط¥ط°ط§ ظƒط§ظ† ظٹط­طھظˆظٹ ط¹ظ„ظ‰ ظ…ظˆط¸ظپظٹظ†طŒ false ط¥ط°ط§ ظ„ظ… ظٹظƒظ†.
     */
    public function hasEmployees($id) {
        $this->db->query('SELECT COUNT(*) as count FROM employees WHERE department_id = :id');
        $this->db->bind(':id', $id);
        $row = $this->db->fetch();
        return $row->count > 0;
    }
     /*
     * ظٹط¨ط­ط« ط¹ظ† ظƒظ„ ط§ظ„ط£ظ‚ط³ط§ظ… ط§ظ„طھط§ط¨ط¹ط© ظ„ظپط±ط¹ ظ…ط¹ظٹظ†.
     *
     * @param int $branchId
     * @return array
     */
    public function findByBranch($branchId) {
        $sql = "SELECT id, name FROM departments WHERE branch_id = :branch_id ORDER BY name ASC";
        $this->db->query($sql);
        $this->db->bind(':branch_id', (int)$branchId);
        return $this->db->fetchAll();
    }
    /**
     * ==================================================
     *           ===>  ط£ط¶ظپ ط§ظ„ط¯ط§ظ„ط© ط§ظ„ط¬ط¯ظٹط¯ط© ظ‡ظ†ط§  <===
     * ==================================================
     * 
     * ظٹط¬ظ„ط¨ ظƒظ„ ط§ظ„ط£ظ‚ط³ط§ظ… ط§ظ„طھط§ط¨ط¹ط© ظ„ظپط±ط¹ ظ…ط¹ظٹظ†.
     * ظ‡ط°ظ‡ ط§ظ„ط¯ط§ظ„ط© ط¶ط±ظˆط±ظٹط© ظ„ظ…ظ„ط، ط§ظ„ظ‚ظˆط§ط¦ظ… ط§ظ„ظ…ظ†ط³ط¯ظ„ط© ط§ظ„ط¯ظٹظ†ط§ظ…ظٹظƒظٹط©.
     *
     * @param int $branchId ظ…ط¹ط±ظپ ط§ظ„ظپط±ط¹.
     * @return array ظ…طµظپظˆظپط© ظ…ظ† ظƒط§ط¦ظ†ط§طھ ط§ظ„ط£ظ‚ط³ط§ظ….
     */
    public function getForBranch($branchId) {
        // ط§ظ„طھط£ظƒط¯ ظ…ظ† ط£ظ† ط§ظ„ظ…ط¹ط±ظپ ظ‡ظˆ ط±ظ‚ظ… طµط­ظٹط­ ظ„ظ…ظ†ط¹ SQL Injection
        if (!filter_var($branchId, FILTER_VALIDATE_INT)) {
            return []; // ط¥ط±ط¬ط§ط¹ ظ…طµظپظˆظپط© ظپط§ط±ط؛ط© ط¥ط°ط§ ظƒط§ظ† ط§ظ„ظ…ط¹ط±ظپ ط؛ظٹط± طµط§ظ„ط­
        }

        $sql = "SELECT id, name FROM departments WHERE branch_id = :branch_id ORDER BY name ASC";
        $this->db->query($sql);
        $this->db->bind(':branch_id', $branchId);
        return $this->db->fetchAll();
    }
  
// ظپظٹ ظ…ظ„ظپ app/Models/Department.php
public function getWithWorkshopStats($branchId) {
    $sql = "SELECT 
                d.id, 
                d.name, 
                COUNT(DISTINCT tw.id) as workshop_count,
                AVG(we.final_score) as average_score
            FROM departments d
            JOIN training_workshops tw  ON d.id = tw.department_id
            LEFT JOIN workshop_evaluations we ON tw.id = we.workshop_id
            WHERE tw.branch_id = :branchId
            GROUP BY d.id, d.name
            HAVING workshop_count > 0
            ORDER BY d.name ASC";
            
    $this->db->query($sql);
    $this->db->bind(':branchId', $branchId);
    return $this->db->fetchAll();
}
/**
 * ==================================================
 *           ===>  ط§ظ„ط¯ط§ظ„ط© ط§ظ„ط¬ط¯ظٹط¯ط© ظ‡ظ†ط§  <===
 * ==================================================
 * 
 * طھط¨ط­ط« ط¹ظ† ظ‚ط³ظ… ظ…ط¹ظٹظ† ظˆطھط¬ظ„ط¨ ظ…ط¹ظ‡ ط§ط³ظ… ط§ظ„ظ…ط¯ظٹط± (ط¥ظ† ظˆط¬ط¯).
 *
 * @param int $id ظ…ط¹ط±ظپ ط§ظ„ظ‚ط³ظ….
 * @return mixed ظƒط§ط¦ظ† ط§ظ„ظ‚ط³ظ… ظ…ط¹ ط§ط³ظ… ط§ظ„ظ…ط¯ظٹط± ط£ظˆ false.
 */
public function findByIdWithManager($id) {
    // ط§ظ„ط£ظ‚ط³ط§ظ… ظ‚ط¯ ظ„ط§ ظٹظƒظˆظ† ظ„ظ‡ط§ ظ…ط¯ظٹط± ظ…ط¨ط§ط´ط±طŒ ظ„ط°ط§ ظ†ط¨ط­ط« ط¹ظ† ظ…ط¯ظٹط± ط§ظ„ظپط±ط¹
    $sql = "SELECT d.*, e.full_name as manager_name
            FROM departments d
            LEFT JOIN branches b ON d.branch_id = b.id
            LEFT JOIN employees e ON b.manager_id = e.id
            WHERE d.id = :id";
    $this->db->query($sql);
    $this->db->bind(':id', $id);
    return $this->db->fetch();
}


}

