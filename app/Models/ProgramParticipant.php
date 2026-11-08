<?php
namespace App\Models;
use App\Core\Model;

/**
 * CD-036 : ProgramParticipant — unified participants (program_participants table)
 * Framework-matched: extends App\Core\Model, uses $this->db (FETCH_OBJ).
 */
class ProgramParticipant extends Model {

    private const JOIN = "SELECT pp.*, e.name AS employee_name, e.employee_number, d.name AS department_name
        FROM program_participants pp
        JOIN employees     e ON e.id = pp.employee_id
        LEFT JOIN departments d ON d.id = e.department_id";

    /**
     * Evaluated employees (status='completed') with optional filters (CD-036.2).
     * $filters = [search, department_id, grade]
     */
    public function evaluatedForProgram(int $programId, array $filters = []): array {
        $where  = ["pp.program_id = :pid", "pp.status = 'completed'"];
        $params = [':pid' => $programId];

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = "(e.name LIKE :s OR e.employee_number LIKE :s)";
            $params[':s'] = "%{$search}%";
        }
        if (!empty($filters['department_id'])) {
            $where[] = "e.department_id = :did";
            $params[':did'] = (int)$filters['department_id'];
        }
        if (!empty($filters['grade'])) {
            $range = $this->gradeRange($filters['grade']);
            if ($range) {
                $where[] = "(pp.final_score IS NOT NULL AND pp.final_score >= :gmin AND pp.final_score < :gmax)";
                $params[':gmin'] = $range[0];
                $params[':gmax'] = $range[1];
            }
        }

        $sql = self::JOIN . " WHERE " . implode(" AND ", $where)
             . " ORDER BY pp.final_score DESC, pp.completed_at DESC";
        $this->db->query($sql);
        foreach ($params as $k => $v) { $this->db->bind($k, $v); }
        return $this->db->fetchAll() ?: [];
    }

    public function allForProgram(int $programId): array {
        $this->db->query(self::JOIN . " WHERE pp.program_id = :pid ORDER BY pp.status, pp.created_at");
        $this->db->bind(':pid', $programId);
        return $this->db->fetchAll() ?: [];
    }

    /** Sync from an evaluation submission (CD-029). */
    public function markCompleted(int $programId, int $employeeId, float $finalScore): bool {
        $this->db->query("INSERT INTO program_participants
            (program_id, employee_id, status, final_score, completed_at)
            VALUES (:pid, :eid, 'completed', :score, NOW())
            ON DUPLICATE KEY UPDATE
                status = 'completed',
                final_score = VALUES(final_score),
                completed_at = NOW()");
        $this->db->bind(':pid', $programId);
        $this->db->bind(':eid', $employeeId);
        $this->db->bind(':score', $finalScore);
        return $this->db->execute();
    }

    /** Map a grade label to a [min, max) final_score range. */
    public function gradeRange(string $grade): ?array {
        switch ($grade) {
            case 'ممتاز':    return [90, 1000];
            case 'جيد جدًا': return [80, 90];
            case 'جيد':      return [70, 80];
            case 'مقبول':    return [0, 70];
        }
        return null;
    }

    /** Distinct departments among evaluated employees of a program (filter dropdown). */
    public function departmentsForProgram(int $programId): array {
        $this->db->query("SELECT DISTINCT d.id, d.name
            FROM program_participants pp
            JOIN employees e ON e.id = pp.employee_id
            LEFT JOIN departments d ON d.id = e.department_id
            WHERE pp.program_id = :pid AND pp.status = 'completed' AND d.id IS NOT NULL
            ORDER BY d.name ASC");
        $this->db->bind(':pid', $programId);
        return $this->db->fetchAll() ?: [];
    }

    public function remove(int $programId, int $employeeId): bool {
        $this->db->query("DELETE FROM program_participants WHERE program_id = :pid AND employee_id = :eid");
        $this->db->bind(':pid', $programId);
        $this->db->bind(':eid', $employeeId);
        return $this->db->execute();
    }
}
