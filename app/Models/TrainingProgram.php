<?php
namespace App\Models;
use App\Core\Model;

/**
 * CD-036 : TrainingProgram — unified programs (training_programs table)
 * Framework-matched: extends App\Core\Model, uses $this->db (FETCH_OBJ).
 */
class TrainingProgram extends Model {

    private const JOIN = "SELECT tp.*, b.name AS branch_name, d.name AS department_name, tc.name AS catalog_name
        FROM training_programs tp
        LEFT JOIN branches          b  ON b.id  = tp.executing_branch_id
        LEFT JOIN departments       d  ON d.id  = tp.executing_department_id
        LEFT JOIN training_catalogs tc ON tc.id = tp.catalog_id";

    public function all(?string $type = null): array {
        $sql  = self::JOIN;
        $params = [];
        if ($type !== null) { $sql .= " WHERE tp.type = :type"; $params[':type'] = $type; }
        $sql .= " ORDER BY tp.start_date DESC, tp.id DESC";
        $this->db->query($sql);
        foreach ($params as $k => $v) { $this->db->bind($k, $v); }
        return $this->db->fetchAll() ?: [];
    }

    public function find(int $id) {
        $this->db->query(self::JOIN . " WHERE tp.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch() ?: null;
    }

    /** @return int|false new id */
    public function create(array $d) {
        $this->db->query("INSERT INTO training_programs
            (type, catalog_id, title, description, executing_branch_id, executing_department_id,
             start_date, end_date, responsible_person, status, created_by)
            VALUES (:type, :catalog_id, :title, :description, :branch_id, :dept_id,
                    :start_date, :end_date, :responsible, :status, :created_by)");
        $this->db->bind(':type', $d['type']);
        $this->db->bind(':catalog_id', $d['catalog_id'] ?? null);
        $this->db->bind(':title', $d['title']);
        $this->db->bind(':description', $d['description'] ?? null);
        $this->db->bind(':branch_id', $d['executing_branch_id']);
        $this->db->bind(':dept_id', $d['executing_department_id'] ?? null);
        $this->db->bind(':start_date', $d['start_date']);
        $this->db->bind(':end_date', $d['end_date']);
        $this->db->bind(':responsible', $d['responsible_person']);
        $this->db->bind(':status', $d['status'] ?? 'scheduled');
        $this->db->bind(':created_by', $d['created_by'] ?? null);
        if ($this->db->execute()) { return $this->db->lastInsertId(); }
        return false;
    }

    public function update(int $id, array $d): bool {
        $this->db->query("UPDATE training_programs SET
            type = :type, catalog_id = :catalog_id, title = :title, description = :description,
            executing_branch_id = :branch_id, executing_department_id = :dept_id,
            start_date = :start_date, end_date = :end_date,
            responsible_person = :responsible, status = :status
            WHERE id = :id");
        $this->db->bind(':type', $d['type']);
        $this->db->bind(':catalog_id', $d['catalog_id'] ?? null);
        $this->db->bind(':title', $d['title']);
        $this->db->bind(':description', $d['description'] ?? null);
        $this->db->bind(':branch_id', $d['executing_branch_id']);
        $this->db->bind(':dept_id', $d['executing_department_id'] ?? null);
        $this->db->bind(':start_date', $d['start_date']);
        $this->db->bind(':end_date', $d['end_date']);
        $this->db->bind(':responsible', $d['responsible_person']);
        $this->db->bind(':status', $d['status'] ?? 'scheduled');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /** Statistics for the details screen (Screen 4). */
    public function stats(int $id): array {
        $this->db->query("SELECT COUNT(*) FROM program_participants WHERE program_id = :id AND status = 'completed'");
        $this->db->bind(':id', $id);
        $evaluated = (int) $this->db->fetchColumn();

        $this->db->query("SELECT AVG(final_score) FROM program_participants WHERE program_id = :id AND final_score IS NOT NULL");
        $this->db->bind(':id', $id);
        $avg = $this->db->fetchColumn();

        $this->db->query("SELECT COUNT(*) FROM program_participants WHERE program_id = :id");
        $this->db->bind(':id', $id);
        $total = (int) $this->db->fetchColumn();

        return [
            'evaluated_count'    => $evaluated,
            'avg_score'          => ($avg !== null && $avg !== false) ? round((float) $avg, 1) : null,
            'completion_pct'     => $total > 0 ? (int) round(($evaluated / $total) * 100) : 0,
            'total_participants' => $total,
        ];
    }

    /* ============ CD-036.3 : Registry search + delete ============ */

    public function search(array $f = []): array {
        $sql = "SELECT tp.*, b.name AS branch_name, d.name AS department_name, tc.name AS catalog_name,
                   (SELECT COUNT(*) FROM program_participants pp WHERE pp.program_id = tp.id) AS participant_count
                FROM training_programs tp
                LEFT JOIN branches          b  ON b.id  = tp.executing_branch_id
                LEFT JOIN departments       d  ON d.id  = tp.executing_department_id
                LEFT JOIN training_catalogs tc ON tc.id = tp.catalog_id";
        $where = []; $params = [];
        $search = trim((string)($f['search'] ?? ''));
        if ($search !== '') {
            $where[] = "(tp.title LIKE :search1 OR tc.name LIKE :search2)";
            $params[':search1'] = "%{$search}%";
            $params[':search2'] = "%{$search}%";
        }
        if (!empty($f['type']) && in_array($f['type'], ['course','workshop'], true)) {
            $where[] = "tp.type = :type"; $params[':type'] = $f['type'];
        }
        if (!empty($f['branch_id']))     { $where[] = "tp.executing_branch_id = :bid";     $params[':bid'] = (int)$f['branch_id']; }
        if (!empty($f['department_id'])) { $where[] = "tp.executing_department_id = :did"; $params[':did'] = (int)$f['department_id']; }
        if (!empty($f['from_date']))     { $where[] = "tp.start_date >= :fd";              $params[':fd'] = $f['from_date']; }
        if (!empty($f['to_date']))       { $where[] = "tp.end_date <= :td";                $params[':td'] = $f['to_date']; }
        if ($where) { $sql .= " WHERE " . implode(" AND ", $where); }
        $sql .= " ORDER BY tp.start_date DESC, tp.id DESC";
        $this->db->query($sql);
        foreach ($params as $k => $v) { $this->db->bind($k, $v); }
        return $this->db->fetchAll() ?: [];
    }

    public function participantCount(int $id): int {
        $this->db->query("SELECT COUNT(*) FROM program_participants WHERE program_id = :id");
        $this->db->bind(':id', $id);
        return (int) $this->db->fetchColumn();
    }

    public function hasParticipants(int $id): bool {
        return $this->participantCount($id) > 0;
    }

    /** Caller MUST verify hasParticipants() === false first. */
    public function delete(int $id): bool {
        $this->db->query("DELETE FROM training_programs WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
