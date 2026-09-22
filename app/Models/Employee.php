<?php
namespace App\Models;

use App\Core\Model;

/**
 * ظ…ظˆط¯ظ„ ط§ظ„ظ…ظˆط¸ظپ (Employee)
 * ظ…ط³ط¤ظˆظ„ ط¹ظ† ط§ظ„طھط¹ط§ظ…ظ„ ظ…ط¹ ط¬ط¯ظˆظ„ employees.
 */
class Employee extends Model {

    /**
     * ط¯ط§ظ„ط© ظ„ط¬ظ„ط¨ ط¬ظ…ظٹط¹ ط§ظ„ظ…ظˆط¸ظپظٹظ† ظ…ط¹ ظ…ط¹ظ„ظˆظ…ط§طھ ط¥ط¶ط§ظپظٹط© (ط§ط³ظ… ط§ظ„ظ‚ط³ظ… ظˆط§ظ„ظپط±ط¹).
     *
     * @return array - ظ…طµظپظˆظپط© ظ…ظ† ظƒط§ط¦ظ†ط§طھ ط§ظ„ظ…ظˆط¸ظپظٹظ†.
     */
      public function getAll() {
        // ===> ط§ظ„طھط¹ط¯ظٹظ„ ظ‡ظ†ط§: ط£ط¶ظپظ†ط§ LEFT JOIN ظ…ط¹ ط¬ط¯ظˆظ„ positions <===
        $sql = "SELECT e.*, 
                       p.title as position_title, -- ط¬ظ„ط¨ ط§ط³ظ… ط§ظ„ظ…ظ†طµط¨
                       d.name as department_name, 
                       b.name as branch_name, 
                       m.full_name as manager_name
                FROM employees e
                LEFT JOIN positions p ON e.position_id = p.id -- ط§ظ„ط±ط¨ط· ظ…ط¹ ط¬ط¯ظˆظ„ ط§ظ„ظ…ظ†ط§طµط¨
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN branches b ON e.branch_id = b.id
                LEFT JOIN employees m ON e.manager_id = m.id
                ORDER BY e.full_name ASC";
        
        $this->db->query($sql);
        return $this->db->fetchAll();
    }

    public function getFiltered(array $filters = [], int $limit = 25, int $offset = 0): array {
        $sql = "SELECT e.*, p.title as position_title, d.name as department_name, b.name as branch_name, m.full_name as manager_name
                FROM employees e
                LEFT JOIN positions p ON e.position_id = p.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN branches b ON e.branch_id = b.id
                LEFT JOIN employees m ON e.manager_id = m.id
                WHERE 1=1";
        $params = [];
        if (!empty($filters['search'])) {
            $sql .= " AND (e.full_name LIKE :search_name OR e.emp_code LIKE :search_code OR e.nickname LIKE :search_nick)";
            $params[':search_name'] = '%' . $filters['search'] . '%';
            $params[':search_code'] = '%' . $filters['search'] . '%';
            $params[':search_nick'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['branch_id'])) { $sql .= " AND e.branch_id = :branch_id"; $params[':branch_id']=(int)$filters['branch_id']; }
        if (!empty($filters['department_id'])) { $sql .= " AND e.department_id = :department_id"; $params[':department_id']=(int)$filters['department_id']; }
        if (!empty($filters['position_id'])) { $sql .= " AND e.position_id = :position_id"; $params[':position_id']=(int)$filters['position_id']; }
        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) { $sql .= " AND e.is_active = :is_active"; $params[':is_active']=(int)$filters['is_active']; }
        $allowedSort=['full_name','emp_code','created_at','id']; $sort=in_array($filters['sort'] ?? 'full_name',$allowedSort)?$filters['sort']:'full_name'; $dir=strtoupper($filters['dir'] ?? 'ASC')==='DESC'?'DESC':'ASC';
        $sql .= " ORDER BY e.$sort $dir LIMIT :lim OFFSET :off";
        $this->db->query($sql);
        foreach($params as $k=>$v) $this->db->bind($k,$v);
        $this->db->bind(':lim',$limit); $this->db->bind(':off',$offset);
        return $this->db->fetchAll();
    }

    public function countFiltered(array $filters = []): int {
        $sql = "SELECT COUNT(*) as cnt FROM employees e WHERE 1=1";
        $params=[];
        if (!empty($filters['search'])) { $sql.=" AND (e.full_name LIKE :search_name OR e.emp_code LIKE :search_code OR e.nickname LIKE :search_nick)"; $params[':search_name']='%'.$filters['search'].'%'; $params[':search_code']='%'.$filters['search'].'%'; $params[':search_nick']='%'.$filters['search'].'%'; }
        if (!empty($filters['branch_id'])) { $sql.=" AND e.branch_id=:branch_id"; $params[':branch_id']=(int)$filters['branch_id']; }
        if (!empty($filters['department_id'])) { $sql.=" AND e.department_id=:department_id"; $params[':department_id']=(int)$filters['department_id']; }
        if (!empty($filters['position_id'])) { $sql.=" AND e.position_id=:position_id"; $params[':position_id']=(int)$filters['position_id']; }
        if (isset($filters['is_active']) && $filters['is_active']!=='' && $filters['is_active']!==null) { $sql.=" AND e.is_active=:is_active"; $params[':is_active']=(int)$filters['is_active']; }
        $this->db->query($sql);
        foreach($params as $k=>$v) $this->db->bind($k,$v);
        $r=$this->db->fetch(); return (int)($r->cnt ?? 0);
    }
    /**
      * ط¯ط§ظ„ط© ظ„ظ„ط¨ط­ط« ط¹ظ† ظ…ظˆط¸ظپ ظ…ط­ط¯ط¯ ط¨ظˆط§ط³ط·ط© ID.
     * (ط³ظ†ط­طھط§ط¬ظ‡ط§ ظ„ط§ط­ظ‚ط§ظ‹ ظ„طµظپط­ط© ط§ظ„ظ…ظ„ظپ ط§ظ„ط´ط®طµظٹ).
     *
     * @param int $id - ظ…ط¹ط±ظپ ط§ظ„ظ…ظˆط¸ظپ.
     * @return mixed - ظƒط§ط¦ظ† ط§ظ„ظ…ظˆط¸ظپ ط£ظˆ false.
     */
        public function findById($id) {
        // ===> ط§ظ„طھط¹ط¯ظٹظ„ ظ‡ظ†ط§: ط£ط¶ظپظ†ط§ LEFT JOIN ظ…ط¹ ط¬ط¯ظˆظ„ positions <===
        $sql = "SELECT e.*, 
                       p.title as position_title, -- ط¬ظ„ط¨ ط§ط³ظ… ط§ظ„ظ…ظ†طµط¨
                       d.name as department_name, 
                       b.name as branch_name, 
                       m.full_name as manager_name
                FROM employees e
                LEFT JOIN positions p ON e.position_id = p.id -- ط§ظ„ط±ط¨ط· ظ…ط¹ ط¬ط¯ظˆظ„ ط§ظ„ظ…ظ†ط§طµط¨
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN branches b ON e.branch_id = b.id
                LEFT JOIN employees m ON e.manager_id = m.id
                WHERE e.id = :id";

        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }

           /**
     * ط¯ط§ظ„ط© ظ„ط¥ط¶ط§ظپط© ظ…ظˆط¸ظپ ط¬ط¯ظٹط¯ ط¥ظ„ظ‰ ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ (ط§ظ„ظ†ط³ط®ط© ط§ظ„ظ…ط­ط¯ط«ط©).
     *
     * @param array $data - ظ…طµظپظˆظپط© طھط­طھظˆظٹ ط¹ظ„ظ‰ ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ…ظˆط¸ظپ.
     * @return mixed - ID ط§ظ„ظ…ظˆط¸ظپ ط§ظ„ط¬ط¯ظٹط¯ ط¹ظ†ط¯ ط§ظ„ظ†ط¬ط§ط­, ط£ظˆ false ط¹ظ†ط¯ ط§ظ„ظپط´ظ„.
     */
    public function create($data) {
        $sql = "INSERT INTO employees (emp_code, full_name, nickname, department_id, branch_id, manager_id, position_id, is_active) 
                VALUES (:emp_code, :full_name, :nickname, :department_id, :branch_id, :manager_id, :position_id, :is_active)";
    
        $this->db->query($sql);

        $this->db->bind(':emp_code', $data['emp_code']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':nickname', $data['nickname']); // ط§ظ„ط­ظ‚ظ„ ط§ظ„ط¬ط¯ظٹط¯
        $this->db->bind(':department_id', $data['department_id']);
        $this->db->bind(':branch_id', $data['branch_id']);
        $this->db->bind(':manager_id', $data['manager_id']);
        $this->db->bind(':position_id', $data['position_id']);
        $this->db->bind(':is_active', 1); // ط§ظ„ظ…ظˆط¸ظپ ط§ظ„ط¬ط¯ظٹط¯ ظ†ط´ط· ط¯ط§ط¦ظ…ط§ظ‹

        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

// ...
  /**
     * ظٹط­ط¯ظ‘ط« ط¨ظٹط§ظ†ط§طھ ظ…ظˆط¸ظپ ظ…ظˆط¬ظˆط¯ ظپظٹ ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ.
     *
     * @param int $id - ظ…ط¹ط±ظپ ط§ظ„ظ…ظˆط¸ظپ ط§ظ„ظ…ط±ط§ط¯ طھط­ط¯ظٹط«ظ‡.
     * @param array $data - ظ…طµظپظˆظپط© طھط­طھظˆظٹ ط¹ظ„ظ‰ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط© ظ„ظ„ظ…ظˆط¸ظپ.
     * @return bool - true ط¹ظ†ط¯ ط§ظ„ظ†ط¬ط§ط­, false ط¹ظ†ط¯ ط§ظ„ظپط´ظ„.
     */
       /**
     * ظٹط­ط¯ظ‘ط« ط¨ظٹط§ظ†ط§طھ ظ…ظˆط¸ظپ ظ…ظˆط¬ظˆط¯ ظپظٹ ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ (ط§ظ„ظ†ط³ط®ط© ط§ظ„ظ…ط­ط¯ط«ط©).
     *
     * @param int $id - ظ…ط¹ط±ظپ ط§ظ„ظ…ظˆط¸ظپ ط§ظ„ظ…ط±ط§ط¯ طھط­ط¯ظٹط«ظ‡.
     * @param array $data - ظ…طµظپظˆظپط© طھط­طھظˆظٹ ط¹ظ„ظ‰ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ط¬ط¯ظٹط¯ط© ظ„ظ„ظ…ظˆط¸ظپ.
     * @return bool - true ط¹ظ†ط¯ ط§ظ„ظ†ط¬ط§ط­, false ط¹ظ†ط¯ ط§ظ„ظپط´ظ„.
     */
   public function update($id, $data) {
        $sql = "UPDATE employees 
                SET 
                    emp_code = :emp_code, 
                    full_name = :full_name, 
                    nickname = :nickname,
                    department_id = :department_id, 
                    branch_id = :branch_id, 
                    manager_id = :manager_id, 
                    position_id = :position_id,
                    is_active = :is_active
                WHERE 
                    id = :id";
        
        $this->db->query($sql);

        $this->db->bind(':id', $id);
        $this->db->bind(':emp_code', $data['emp_code']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':nickname', $data['nickname']); // ط§ظ„ط­ظ‚ظ„ ط§ظ„ط¬ط¯ظٹط¯
        $this->db->bind(':department_id', $data['department_id']);
        $this->db->bind(':branch_id', $data['branch_id']);
        $this->db->bind(':manager_id', $data['manager_id']);
        $this->db->bind(':position_id', $data['position_id']);
        $this->db->bind(':is_active', $data['is_active']);

        return $this->db->execute();
    }

  
  
    /**
     * ظٹط­ط°ظپ ظ…ظˆط¸ظپط§ظ‹ ظ…ظ† ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ.
     *
     * @param int $id - ظ…ط¹ط±ظپ ط§ظ„ظ…ظˆط¸ظپ ط§ظ„ظ…ط±ط§ط¯ ط­ط°ظپظ‡.
     * @return bool - true ط¹ظ†ط¯ ط§ظ„ظ†ط¬ط§ط­, false ط¹ظ†ط¯ ط§ظ„ظپط´ظ„.
     */
    public function delete($id) {
        $this->db->query('DELETE FROM employees WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
 /**
     * ==================================================
     *           ===>  ط§ظ„ط¯ط§ظ„ط© ط§ظ„ط¬ط¯ظٹط¯ط© ظ‡ظ†ط§  <===
     * ==================================================
     * 
     * ظٹط¬ظ„ط¨ ظ‚ط§ط¦ظ…ط© ط¨ط§ظ„ظ…ظˆط¸ظپظٹظ† ط§ظ„ط°ظٹظ† ظ„ظٹط³ ظ„ط¯ظٹظ‡ظ… ط­ط³ط§ط¨ ظ…ط³طھط®ط¯ظ… ظ…ط±طھط¨ط· ط¨ظ‡ظ… ط¨ط¹ط¯.
     * طھط¬ظ„ط¨ ظپظ‚ط· ط§ظ„ظ…ظˆط¸ظپظٹظ† ط§ظ„ط°ظٹظ† ظ„ظٹط³ ظ„ط¯ظٹظ‡ظ… ط­ط³ط§ط¨ ظ…ط³طھط®ط¯ظ… ظ…ط±طھط¨ط· ط¨ظ‡ظ…طŒ ط­طھظ‰ ظ„ط§ طھط±ط¨ط· ظ…ظˆط¸ظپط§ظ‹ ظˆط§ط­ط¯ط§ظ‹ ط¨ط­ط³ط§ط¨ظٹظ† ط¹ظ† ط·ط±ظٹظ‚ ط§ظ„ط®ط·ط£.
     * @return array
     */
    public function getUnlinkedEmployees() {
        // ظ†ط³طھط®ط¯ظ… LEFT JOIN ظ„ظ„ط¨ط­ط« ط¹ظ† ط§ظ„ظ…ظˆط¸ظپظٹظ† ط§ظ„ط°ظٹظ† ظ„ظٹط³ ظ„ظ‡ظ… ظ…ظ‚ط§ط¨ظ„ ظپظٹ ط¬ط¯ظˆظ„ users
        $sql = "SELECT e.id, e.full_name, e.emp_code 
                FROM employees e
                LEFT JOIN users u ON e.id = u.employee_id
                WHERE u.id IS NULL
                ORDER BY e.full_name ASC";
        
        $this->db->query($sql);
        return $this->db->fetchAll();
    }
    
public function getAvailableEmployeesForUser($currentEmployeeId) {
    // ط¬ظ„ط¨ ط§ظ„ظ…ظˆط¸ظپظٹظ† ط؛ظٹط± ط§ظ„ظ…ط±طھط¨ط·ظٹظ† ط£ظˆ ط§ظ„ظ…ظˆط¸ظپ ط§ظ„ظ…ط±طھط¨ط· ط­ط§ظ„ظٹط§ظ‹ ط¨ظ‡ط°ط§ ط§ظ„ظ…ط³طھط®ط¯ظ…
    $sql = "SELECT e.id, e.full_name FROM employees e
            LEFT JOIN users u ON e.id = u.employee_id
            WHERE u.id IS NULL OR e.id = :current_employee_id";
    $this->db->query($sql);
    $this->db->bind(':current_employee_id', $currentEmployeeId);
    return $this->db->fetchAll();
}

/**
 * =================================================================
 * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: ط¬ظ„ط¨ ظƒظ„ طھظپط§طµظٹظ„ ط§ظ„ظ…ظˆط¸ظپ ظ„ط¥ظ†ط´ط§ط، "ظ†ط³ط®ط© طھط§ط±ظٹط®ظٹط©"
 * =================================================================
 * ظ‡ط°ظ‡ ط§ظ„ط¯ط§ظ„ط© طھط¬ظ„ط¨ ظƒظ„ ط§ظ„ط¨ظٹط§ظ†ط§طھ ط§ظ„ظ…ط·ظ„ظˆط¨ط© ظ„طھط®ط²ظٹظ†ظ‡ط§ ظپظٹ ط¬ط¯ظˆظ„ ط§ظ„طھظ‚ظٹظٹظ…ط§طھطŒ
 * ظ…ظ…ط§ ظٹط¶ظ…ظ† ط¯ظ‚ط© ط§ظ„طھظ‚ط§ط±ظٹط± ط§ظ„طھط§ط±ظٹط®ظٹط©.
 *
 * @param int $id - ظ…ط¹ط±ظپ ط§ظ„ظ…ظˆط¸ظپ.
 * @return object|false - ظƒط§ط¦ظ† ظٹط­طھظˆظٹ ط¹ظ„ظ‰ ظƒظ„ طھظپط§طµظٹظ„ ط§ظ„ظ…ظˆط¸ظپ ط£ظˆ false.
 */
public function getDetailsForSnapshot($id) {
    $sql = "SELECT 
                e.full_name, 
                e.emp_code,
                e.nickname,
                b.name as branch_name,
                d.name as department_name,
                p.title as position_title
            FROM 
                employees e
            LEFT JOIN 
                branches b ON e.branch_id = b.id
            LEFT JOIN 
                departments d ON e.department_id = d.id
            LEFT JOIN 
                positions p ON e.position_id = p.id
            WHERE 
                e.id = :id";

    $this->db->query($sql);
    $this->db->bind(':id', $id);
    return $this->db->fetch();
}

/**
 * =================================================================
 * ط¯ط§ظ„ط© ط¬ط¯ظٹط¯ط©: ظ„ظ„ط¨ط­ط« ط¹ظ† ط§ظ„ظ…ظˆط¸ظپظٹظ† ط§ظ„ظ†ط´ط·ظٹظ† (ظ„ظ„ط¥ظƒظ…ط§ظ„ ط§ظ„طھظ„ظ‚ط§ط¦ظٹ) - ط§ظ„ظ†ط³ط®ط© ط§ظ„ظ…طµط­ط­ط©
 * =================================================================
 * @param string $query - ط§ظ„ظ†طµ ط§ظ„ط°ظٹ ظٹطھظ… ط§ظ„ط¨ط­ط« ط¹ظ†ظ‡.
 * @return array - ظ…طµظپظˆظپط© ظ…ظ† ط§ظ„ظ…ظˆط¸ظپظٹظ† ط§ظ„ظ…طھط·ط§ط¨ظ‚ظٹظ†.
 */
    /**
     * Find employees by department ID
     */
    public function findByDepartment($departmentId): array
    {
        $this->db->query("SELECT e.*, p.title as position_title, d.name as department_name, b.name as branch_name
                          FROM employees e
                          LEFT JOIN positions p ON e.position_id = p.id
                          LEFT JOIN departments d ON e.department_id = d.id
                          LEFT JOIN branches b ON e.branch_id = b.id
                          WHERE e.department_id = :dept_id
                          ORDER BY e.full_name ASC");
        $this->db->bind(':dept_id', $departmentId);
        return $this->db->fetchAll();
    }

public function search($query, $limit = 10, $offset = 0) {
    // ===> ط§ظ„طھطµط­ظٹط­: ط§ط³طھط®ط¯ط§ظ… ط£ط³ظ…ط§ط، ظپط±ظٹط¯ط© ظ„ظ„ظ€ placeholders <===
    $sql = "SELECT id, emp_code, full_name, position_id 
            FROM employees 
            WHERE (full_name LIKE :query_name OR emp_code LIKE :query_code) AND is_active = 1 
            LIMIT 10";
            
    $this->db->query($sql);

    // ===> ط§ظ„طھطµط­ظٹط­: ط±ط¨ط· ظƒظ„ placeholder ط¨ظ‚ظٹظ…طھظ‡ ط§ظ„ط®ط§طµط© <===
    $this->db->bind(':query_name', '%' . $query . '%');
    $this->db->bind(':query_code', '%' . $query . '%');
    
    return $this->db->fetchAll();
}

 
    /**
     * =================================================================
     * ط¯ط§ظ„ط© ط¬ظ„ط¨ ط§ظ„ظ…ظˆط¸ظپظٹظ† ظ…ط¹ ظ…ظ„ط®طµ ظ„طھظ‚ظٹظٹظ…ط§طھظ‡ظ… (ط§ظ„ظ†ط³ط®ط© ط§ظ„ظ…طµط­ط­ط©)
     * =================================================================
     * @param array $filters - ظ…طµظپظˆظپط© طھط­طھظˆظٹ ط¹ظ„ظ‰ ظپظ„ط§طھط± ط§ظ„ط¨ط­ط«
     * @return array
     */
    public function getEmployeesWithEvaluationSummary($filters = [], $page = 1, $perPage = 15) {
        $sql = "SELECT 
                    e.id,
                    e.full_name,
                    e.emp_code,
                    p.title as position_title,
                    b.name as branch_name,
                    d.name as department_name,
                    COUNT(eval.id) as evaluations_count,
                    MAX(eval.evaluation_date) as last_evaluation_date,
                    AVG(eval.final_score) as average_score
                FROM 
                    employees e
                LEFT JOIN 
                    positions p ON e.position_id = p.id
                LEFT JOIN 
                    branches b ON e.branch_id = b.id
                LEFT JOIN 
                    departments d ON e.department_id = d.id
                LEFT JOIN 
                    evaluations eval ON e.id = eval.evaluated_employee_id
                WHERE 
                    1=1"; 

        $params = [];

        // طھط·ط¨ظٹظ‚ ظپظ„طھط± ط§ظ„ط¨ط­ط«
        if (!empty($filters['search'])) {
            // â–¼â–¼â–¼ ط§ظ„طھط¹ط¯ظٹظ„ ط§ظ„ط±ط¦ظٹط³ظٹ ظ‡ظ†ط§ â–¼â–¼â–¼
            // ط§ط³طھط®ط¯ط§ظ… ط£ط³ظ…ط§ط، ط¹ظ†ط§طµط± ظ†ط§ط¦ط¨ط© ظپط±ظٹط¯ط©: :search_name ظˆ :search_code
            $sql .= " AND (e.full_name LIKE :search_name OR e.emp_code LIKE :search_code)";
            // ط±ط¨ط· ظƒظ„ ط¹ظ†طµط± ظ†ط§ط¦ط¨ ط¨ظ‚ظٹظ…طھظ‡ ط§ظ„ط®ط§طµط© (ظˆظ‡ظٹ ظ†ظپط³ ظ‚ظٹظ…ط© ط§ظ„ط¨ط­ط«)
            $params[':search_name'] = '%' . $filters['search'] . '%';
            $params[':search_code'] = '%' . $filters['search'] . '%';
        }

        // طھط·ط¨ظٹظ‚ ظپظ„طھط± ط§ظ„ظپط±ط¹
        if (!empty($filters['branch_id'])) {
            $sql .= " AND e.branch_id = :branch_id";
            $params[':branch_id'] = (int)$filters['branch_id'];
        }
        
        // طھط·ط¨ظٹظ‚ ظپظ„طھط± ط§ظ„ظ‚ط³ظ…
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.department_id = :department_id";
            $params[':department_id'] = (int)$filters['department_id'];
        }

        $sql .= " GROUP BY e.id, e.full_name, e.emp_code, p.title, b.name, d.name";
        $sql .= " ORDER BY last_evaluation_date DESC, e.full_name ASC";

        $offset = max(0, ($page - 1) * $perPage);
        $sql .= " LIMIT :limit OFFSET :offset";

        $this->db->query($sql);

        // ط±ط¨ط· ط§ظ„ط¨ط§ط±ط§ظ…طھط±ط§طھ
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':limit', (int)$perPage);
        $this->db->bind(':offset', (int)$offset);

        return $this->db->fetchAll();
    }

    public function getEmployeesWithEvaluationSummaryCount($filters = []) {
        $sql = "SELECT COUNT(DISTINCT e.id)
                FROM employees e
                LEFT JOIN evaluations eval ON e.id = eval.evaluated_employee_id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (e.full_name LIKE :search_name OR e.emp_code LIKE :search_code)";
            $params[':search_name'] = '%' . $filters['search'] . '%';
            $params[':search_code'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['branch_id'])) {
            $sql .= " AND e.branch_id = :branch_id";
            $params[':branch_id'] = (int)$filters['branch_id'];
        }
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.department_id = :department_id";
            $params[':department_id'] = (int)$filters['department_id'];
        }

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return (int)$this->db->fetchColumn();
    }
  // ظپظٹ ظ…ظ„ظپ: app/Models/Employee.php
public function getEmployeesWithCompletedEvaluations() {
    $sql = "SELECT DISTINCT e.id, e.full_name, e.emp_code 
            FROM employees e
            JOIN evaluations eval ON e.id = eval.evaluated_employee_id
            WHERE eval.status IN ('approved','completed')
            ORDER BY e.full_name ASC";
    $this->db->query($sql);
    return $this->db->fetchAll();
}
  

        /**
     * =================================================================
     *            ===>  ط§ظ„ط¯ظˆط§ظ„ ط§ظ„ط¬ط¯ظٹط¯ط© ظ„ظ„طھط­ظ„ظٹظ„ ط§ظ„ط¬ظ…ط§ط¹ظٹ  <===
     * =================================================================
     */

    /**
     * ظٹط¬ظ„ط¨ IDs ط¬ظ…ظٹط¹ ط§ظ„ظ…ظˆط¸ظپظٹظ† ط§ظ„ظ†ط´ط·ظٹظ† ظپظٹ ظ‚ط³ظ… ظ…ط¹ظٹظ†.
     *
     * @param int $departmentId
     * @return array
     */
  public function getEmployeeIdsByDepartment($departmentId) {
    $this->db->query("SELECT id FROM employees WHERE department_id = :dept_id AND is_active = 1");
    $this->db->bind(':dept_id', (int)$departmentId);
    $results = $this->db->fetchAll();
    return array_map(function($row) {
        return $row->id;
    }, $results);
}

    /**
     * ظٹط¬ظ„ط¨ IDs ط¬ظ…ظٹط¹ ط§ظ„ظ…ظˆط¸ظپظٹظ† ط§ظ„ظ†ط´ط·ظٹظ† ظپظٹ ظپط±ط¹ ظ…ط¹ظٹظ†.
     *
     * @param int $branchId
     * @return array
     */
public function getEmployeeIdsByBranch($branchId) {
    $this->db->query("SELECT id FROM employees WHERE branch_id = :branch_id AND is_active = 1");
    $this->db->bind(':branch_id', (int)$branchId);
    $results = $this->db->fetchAll();
    return array_map(function($row) {
        return $row->id;
    }, $results);
}


    /**
     * ظٹط¬ظ„ط¨ IDs ط¬ظ…ظٹط¹ ط§ظ„ظ…ظˆط¸ظپظٹظ† ط§ظ„ظ†ط´ط·ظٹظ† ظپظٹ ط§ظ„ط´ط±ظƒط©.
     *
     * @return array
     */
public function getAllActiveEmployeeIds() {
    $this->db->query("SELECT id FROM employees WHERE is_active = 1");
    $results = $this->db->fetchAll();
    return array_map(function($row) {
        return $row->id;
    }, $results);
}
 /**
     * ==================================================
     *           ===>  ط§ظ„ط¯ط§ظ„ط© ط§ظ„ط¬ط¯ظٹط¯ط© ظ‡ظ†ط§  <===
     * ==================================================
     * 
     * ظٹط¨ط­ط« ط¹ظ† ظ…ظˆط¸ظپ ط¨ظˆط§ط³ط·ط© ط§ظ„ط±ظ‚ظ… ط§ظ„ظˆط¸ظٹظپظٹ.
     *
     * @param string $empCode ط§ظ„ط±ظ‚ظ… ط§ظ„ظˆط¸ظٹظپظٹ ظ„ظ„ظ…ظˆط¸ظپ.
     * @return mixed ظƒط§ط¦ظ† ط§ظ„ظ…ظˆط¸ظپ ط¥ط°ط§ طھظ… ط§ظ„ط¹ط«ظˆط± ط¹ظ„ظٹظ‡, ط£ظˆ false ط¥ط°ط§ ظ„ظ… ظٹطھظ….
     */
    public function findByEmpCode($empCode) {
        // ظ„ط§ ظ†ظ†ظپط° ط§ظ„ط§ط³طھط¹ظ„ط§ظ… ط¥ط°ط§ ظƒط§ظ† ط§ظ„ط±ظ‚ظ… ط§ظ„ظˆط¸ظٹظپظٹ ظپط§ط±ط؛ظ‹ط§
        if (empty($empCode)) {
            return false;
        }

        $this->db->query("SELECT id, emp_code FROM employees WHERE emp_code = :emp_code");
        $this->db->bind(':emp_code', $empCode);
        return $this->db->fetch();
    }
}

