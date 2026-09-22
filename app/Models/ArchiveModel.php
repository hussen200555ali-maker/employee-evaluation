<?php
namespace App\Models;
use App\Core\Model;

class ArchiveModel extends Model {

    private function statusCondition(string $entityType, string $alias = 'e'): string {
        return "{$alias}.entity_type='{$entityType}'";
    }

    private function evalStatusFilter(?string $status): string {
        if ($status && $status !== 'all' && in_array($status, ['draft','pending_review','approved','completed','expired','archived'], true)) {
            $safe = addslashes($status);
            return " AND e.status = '{$safe}'";
        }
        return " AND e.status IN ('approved','completed')";
    }

    private function bindEvalStatus(&$params, ?string $status): void {
        // no-op — status is inlined safely via allowlist
    }

    private function courseAvgSubquery(?string $status = null): string {
        return "(SELECT AVG(e.final_score) FROM evaluations e WHERE e.entity_type='course' AND e.entity_id=tc.id" . $this->evalStatusFilter($status);
    }
    private function courseCountSubquery(?string $status = null): string {
        return "(SELECT COUNT(*) FROM evaluations e WHERE e.entity_type='course' AND e.entity_id=tc.id" . $this->evalStatusFilter($status);
    }
    private function workshopAvgSubquery(?string $status = null): string {
        return "(SELECT AVG(e.final_score) FROM evaluations e WHERE e.entity_type='workshop' AND e.entity_id=tw.id" . $this->evalStatusFilter($status);
    }
    private function workshopCountSubquery(?string $status = null): string {
        return "(SELECT COUNT(*) FROM evaluations e WHERE e.entity_type='workshop' AND e.entity_id=tw.id" . $this->evalStatusFilter($status);
    }

    private function applyDateFilter(string $tableAlias, ?string $from, ?string $to): string {
        $sql = '';
        if ($from && $from !== '') {
            $sql .= " AND {$tableAlias}.end_date >= :date_from";
        }
        if ($to && $to !== '') {
            $sql .= " AND {$tableAlias}.end_date <= :date_to";
        }
        return $sql;
    }

    private function bindDateParams(&$params, ?string $from, ?string $to): void {
        if ($from && $from !== '') $params[':date_from'] = $from;
        if ($to && $to !== '') $params[':date_to'] = $to;
    }

    private function bindAll(array &$params, ?string $from, ?string $to, ?string $status): void {
        $this->bindDateParams($params, $from, $to);
        $this->bindEvalStatus($params, $status);
    }

    // ===== COURSE ARCHIVE =====

    public function getCourseBranchCards(?string $from = null, ?string $to = null, ?string $status = null, string $search = ''): array {
        $dateFilter = $this->applyDateFilter('tc', $from, $to);
        $params = [];
        $this->bindAll($params, $from, $to, $status);
        $searchFilter = '';
        if ($search !== '') {
            $searchFilter = " AND EXISTS (SELECT 1 FROM training_courses tc2 JOIN course_catalog cc2 ON tc2.catalog_id=cc2.id WHERE tc2.branch_id=b.id AND cc2.title LIKE :search_branch)";
            $params[':search_branch'] = "%{$search}%";
        }
        $sql = "SELECT b.id as branch_id, b.name as branch_name,
                COUNT(DISTINCT tc.id) as total_courses,
                {$this->courseCountSubquery($status)}) as evaluated_count,
                {$this->courseAvgSubquery($status)}) as avg_score
                FROM branches b
                LEFT JOIN training_courses tc ON tc.branch_id = b.id
                WHERE 1=1 {$dateFilter} {$searchFilter}
                GROUP BY b.id, b.name
                ORDER BY b.name";
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->fetchAll();
    }

    public function getCourseDepartmentCards(int $branchId, ?string $from = null, ?string $to = null, ?string $status = null, string $search = ''): array {
        $dateFilter = $this->applyDateFilter('tc', $from, $to);
        $params = [':branch_id' => $branchId, ':branch_id2' => $branchId];
        $this->bindAll($params, $from, $to, $status);
        $searchFilter = '';
        if ($search !== '') {
            $searchFilter = " AND EXISTS (SELECT 1 FROM training_courses tc2 JOIN course_catalog cc2 ON tc2.catalog_id=cc2.id WHERE tc2.department_id=d.id AND tc2.branch_id=b.id AND cc2.title LIKE :search_dept)";
            $params[':search_dept'] = "%{$search}%";
        }
        $sql = "SELECT d.id as department_id, d.name as department_name,
                COUNT(DISTINCT tc.id) as total_courses,
                {$this->courseCountSubquery($status)}) as evaluated_count,
                {$this->courseAvgSubquery($status)}) as avg_score
                FROM departments d
                LEFT JOIN training_courses tc ON tc.department_id = d.id AND tc.branch_id = :branch_id
                LEFT JOIN branches b ON d.branch_id = b.id
                WHERE d.branch_id = :branch_id2 {$dateFilter} {$searchFilter}
                GROUP BY d.id, d.name
                ORDER BY d.name";
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->fetchAll();
    }

    public function getCourseYearCards(int $departmentId, ?string $from = null, ?string $to = null, ?string $status = null): array {
        $dateFilter = $this->applyDateFilter('tc', $from, $to);
        $params = [':dept_id' => $departmentId];
        $this->bindAll($params, $from, $to, $status);
        $sql = "SELECT YEAR(tc.end_date) as eval_year,
                COUNT(DISTINCT tc.id) as total_courses,
                {$this->courseCountSubquery($status)}) as evaluated_count,
                {$this->courseAvgSubquery($status)}) as avg_score
                FROM training_courses tc
                WHERE tc.department_id = :dept_id {$dateFilter}
                GROUP BY YEAR(tc.end_date)
                ORDER BY eval_year DESC";
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->fetchAll();
    }

    public function getCourseRecords(int $departmentId, int $year, ?string $from = null, ?string $to = null, string $search = '', ?string $status = null): array {
        $dateFilter = $this->applyDateFilter('tc', $from, $to);
        $params = [':dept_id' => $departmentId, ':year' => $year];
        $this->bindAll($params, $from, $to, $status);
        $searchFilter = '';
        if ($search !== '') {
            $searchFilter = " AND (cc.title LIKE :search OR b.name LIKE :search2 OR d.name LIKE :search3)";
            $params[':search'] = "%{$search}%";
            $params[':search2'] = "%{$search}%";
            $params[':search3'] = "%{$search}%";
        }
        $safeStatus = ($status && $status !== 'all' && in_array($status, ['draft','pending_review','approved','completed','expired','archived'], true)) ? addslashes($status) : null;
        $evalStatusCol = $safeStatus ? "AND e4.status = '{$safeStatus}'" : "AND e4.status IN ('approved','completed')";
        $evalStatusCol2 = $safeStatus ? "AND e5.status = '{$safeStatus}'" : "AND e5.status IN ('approved','completed')";
        $sql = "SELECT tc.id, tc.start_date, tc.end_date, tc.status as course_status,
                cc.title as course_title, b.name as branch_name, d.name as department_name,
                {$this->courseCountSubquery($status)}) as eval_count,
                {$this->courseAvgSubquery($status)}) as avg_score,
                (SELECT MAX(e4.final_score) FROM evaluations e4 WHERE e4.entity_type='course' AND e4.entity_id=tc.id {$evalStatusCol}) as max_score,
                (SELECT MIN(e5.final_score) FROM evaluations e5 WHERE e5.entity_type='course' AND e5.entity_id=tc.id {$evalStatusCol2}) as min_score
                FROM training_courses tc
                JOIN course_catalog cc ON tc.catalog_id = cc.id
                LEFT JOIN branches b ON tc.branch_id = b.id
                LEFT JOIN departments d ON tc.department_id = d.id
                WHERE tc.department_id = :dept_id AND YEAR(tc.end_date) = :year {$dateFilter} {$searchFilter}
                ORDER BY tc.end_date DESC";
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->fetchAll();
    }

    // ===== WORKSHOP ARCHIVE =====

    public function getWorkshopBranchCards(?string $from = null, ?string $to = null, ?string $status = null, string $search = ''): array {
        $dateFilter = $this->applyDateFilter('tw', $from, $to);
        $params = [];
        $this->bindAll($params, $from, $to, $status);
        $searchFilter = '';
        if ($search !== '') {
            $searchFilter = " AND EXISTS (SELECT 1 FROM training_workshops tw2 JOIN workshop_catalog wc2 ON tw2.catalog_id=wc2.id WHERE tw2.branch_id=b.id AND wc2.title LIKE :search_branch)";
            $params[':search_branch'] = "%{$search}%";
        }
        $sql = "SELECT b.id as branch_id, b.name as branch_name,
                COUNT(DISTINCT tw.id) as total_workshops,
                {$this->workshopCountSubquery($status)}) as evaluated_count,
                {$this->workshopAvgSubquery($status)}) as avg_score
                FROM branches b
                LEFT JOIN training_workshops tw ON tw.branch_id = b.id
                WHERE 1=1 {$dateFilter} {$searchFilter}
                GROUP BY b.id, b.name
                ORDER BY b.name";
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->fetchAll();
    }

    public function getWorkshopDepartmentCards(int $branchId, ?string $from = null, ?string $to = null, ?string $status = null, string $search = ''): array {
        $dateFilter = $this->applyDateFilter('tw', $from, $to);
        $params = [':branch_id' => $branchId, ':branch_id2' => $branchId];
        $this->bindAll($params, $from, $to, $status);
        $searchFilter = '';
        if ($search !== '') {
            $searchFilter = " AND EXISTS (SELECT 1 FROM training_workshops tw2 JOIN workshop_catalog wc2 ON tw2.catalog_id=wc2.id WHERE tw2.department_id=d.id AND tw2.branch_id=b.id AND wc2.title LIKE :search_dept)";
            $params[':search_dept'] = "%{$search}%";
        }
        $sql = "SELECT d.id as department_id, d.name as department_name,
                COUNT(DISTINCT tw.id) as total_workshops,
                {$this->workshopCountSubquery($status)}) as evaluated_count,
                {$this->workshopAvgSubquery($status)}) as avg_score
                FROM departments d
                LEFT JOIN training_workshops tw ON tw.department_id = d.id AND tw.branch_id = :branch_id
                LEFT JOIN branches b ON d.branch_id = b.id
                WHERE d.branch_id = :branch_id2 {$dateFilter} {$searchFilter}
                GROUP BY d.id, d.name
                ORDER BY d.name";
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->fetchAll();
    }

    public function getWorkshopYearCards(int $departmentId, ?string $from = null, ?string $to = null, ?string $status = null): array {
        $dateFilter = $this->applyDateFilter('tw', $from, $to);
        $params = [':dept_id' => $departmentId];
        $this->bindAll($params, $from, $to, $status);
        $sql = "SELECT YEAR(tw.end_date) as eval_year,
                COUNT(DISTINCT tw.id) as total_workshops,
                {$this->workshopCountSubquery($status)}) as evaluated_count,
                {$this->workshopAvgSubquery($status)}) as avg_score
                FROM training_workshops tw
                WHERE tw.department_id = :dept_id {$dateFilter}
                GROUP BY YEAR(tw.end_date)
                ORDER BY eval_year DESC";
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->fetchAll();
    }

    public function getWorkshopRecords(int $departmentId, int $year, ?string $from = null, ?string $to = null, string $search = '', ?string $status = null): array {
        $dateFilter = $this->applyDateFilter('tw', $from, $to);
        $params = [':dept_id' => $departmentId, ':year' => $year];
        $this->bindAll($params, $from, $to, $status);
        $searchFilter = '';
        if ($search !== '') {
            $searchFilter = " AND (wc.title LIKE :search OR b.name LIKE :search2 OR d.name LIKE :search3)";
            $params[':search'] = "%{$search}%";
            $params[':search2'] = "%{$search}%";
            $params[':search3'] = "%{$search}%";
        }
        $safeStatus = ($status && $status !== 'all' && in_array($status, ['draft','pending_review','approved','completed','expired','archived'], true)) ? addslashes($status) : null;
        $evalStatusCol = $safeStatus ? "AND e4.status = '{$safeStatus}'" : "AND e4.status IN ('approved','completed')";
        $evalStatusCol2 = $safeStatus ? "AND e5.status = '{$safeStatus}'" : "AND e5.status IN ('approved','completed')";
        $sql = "SELECT tw.id, tw.start_date, tw.end_date, tw.status as workshop_status,
                wc.title as workshop_title, b.name as branch_name, d.name as department_name,
                {$this->workshopCountSubquery($status)}) as eval_count,
                {$this->workshopAvgSubquery($status)}) as avg_score,
                (SELECT MAX(e4.final_score) FROM evaluations e4 WHERE e4.entity_type='workshop' AND e4.entity_id=tw.id {$evalStatusCol}) as max_score,
                (SELECT MIN(e5.final_score) FROM evaluations e5 WHERE e5.entity_type='workshop' AND e5.entity_id=tw.id {$evalStatusCol2}) as min_score
                FROM training_workshops tw
                JOIN workshop_catalog wc ON tw.catalog_id = wc.id
                LEFT JOIN branches b ON tw.branch_id = b.id
                LEFT JOIN departments d ON tw.department_id = d.id
                WHERE tw.department_id = :dept_id AND YEAR(tw.end_date) = :year {$dateFilter} {$searchFilter}
                ORDER BY tw.end_date DESC";
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->fetchAll();
    }

    // ===== EVALUATION DETAIL (for memo) =====

    public function getCourseEvaluation(int $courseId): ?object {
        $sql = "SELECT e.*, ft.template_name, cc.title as course_title
                FROM evaluations e
                LEFT JOIN form_templates ft ON e.template_id = ft.id
                LEFT JOIN training_courses tc ON e.entity_id = tc.id
                LEFT JOIN course_catalog cc ON tc.catalog_id = cc.id
                WHERE e.entity_type='course' AND e.entity_id=:id AND e.status IN ('approved','completed')
                ORDER BY e.evaluation_date DESC LIMIT 1";
        $this->db->query($sql);
        $this->db->bind(':id', $courseId);
        return $this->db->fetch() ?: null;
    }

    public function getWorkshopEvaluation(int $workshopId): ?object {
        $sql = "SELECT e.*, ft.template_name, wc.title as workshop_title
                FROM evaluations e
                LEFT JOIN form_templates ft ON e.template_id = ft.id
                LEFT JOIN training_workshops tw ON e.entity_id = tw.id
                LEFT JOIN workshop_catalog wc ON tw.catalog_id = wc.id
                WHERE e.entity_type='workshop' AND e.entity_id=:id AND e.status IN ('approved','completed')
                ORDER BY e.evaluation_date DESC LIMIT 1";
        $this->db->query($sql);
        $this->db->bind(':id', $workshopId);
        return $this->db->fetch() ?: null;
    }

    public function getEvaluationAnswers(int $evaluationId): array {
        $sql = "SELECT ea.*, cs.section_name, cs.section_weight as max_score
                FROM evaluation_answers ea
                LEFT JOIN criteria_sections cs ON ea.section_id = cs.id
                WHERE ea.evaluation_id = :id
                ORDER BY cs.order_index, ea.section_id";
        $this->db->query($sql);
        $this->db->bind(':id', $evaluationId);
        return $this->db->fetchAll();
    }

    // ===== OVERALL STATS =====

    public function getCourseOverallStats(?string $from = null, ?string $to = null, ?string $status = null): array {
        $dateFilter = $this->applyDateFilter('tc', $from, $to);
        $params = [];
        $this->bindAll($params, $from, $to, $status);
        $evalFilter = $this->evalStatusFilter($status);
        $sql = "SELECT COUNT(DISTINCT tc.id) as total,
                COUNT(DISTINCT CASE WHEN e.status IN ('approved','completed') THEN e.id END) as evaluated,
                AVG(CASE WHEN e.status IN ('approved','completed') THEN e.final_score END) as avg_score
                FROM training_courses tc
                LEFT JOIN evaluations e ON e.entity_type='course' AND e.entity_id=tc.id
                WHERE 1=1 {$dateFilter}";
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        $row = $this->db->fetch();
        if (!$row) return ['total' => 0, 'evaluated' => 0, 'avg_score' => 0];
        return ['total' => (int)$row->total, 'evaluated' => (int)$row->evaluated, 'avg_score' => round((float)($row->avg_score ?? 0), 1)];
    }

    public function getWorkshopOverallStats(?string $from = null, ?string $to = null, ?string $status = null): array {
        $dateFilter = $this->applyDateFilter('tw', $from, $to);
        $params = [];
        $this->bindAll($params, $from, $to, $status);
        $evalFilter = $this->evalStatusFilter($status);
        $sql = "SELECT COUNT(DISTINCT tw.id) as total,
                COUNT(DISTINCT CASE WHEN e.status IN ('approved','completed') THEN e.id END) as evaluated,
                AVG(CASE WHEN e.status IN ('approved','completed') THEN e.final_score END) as avg_score
                FROM training_workshops tw
                LEFT JOIN evaluations e ON e.entity_type='workshop' AND e.entity_id=tw.id
                WHERE 1=1 {$dateFilter}";
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        $row = $this->db->fetch();
        if (!$row) return ['total' => 0, 'evaluated' => 0, 'avg_score' => 0];
        return ['total' => (int)$row->total, 'evaluated' => (int)$row->evaluated, 'avg_score' => round((float)($row->avg_score ?? 0), 1)];
    }
}
