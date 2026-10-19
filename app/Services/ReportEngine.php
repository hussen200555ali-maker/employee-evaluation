<?php

namespace App\Services;

use App\Core\Database;

class ReportEngine
{
    private Database $db;
    private array $allowedDimensions = [
        'branch' => 'v.branch_id',
        'branch_name' => 'v.branch_name',
        'department' => 'v.department_id',
        'department_name' => 'v.department_name',
        'month' => 'v.eval_month',
        'year' => 'v.eval_year',
        'template' => 'v.template_id',
        'template_name' => 'v.template_name',
        'criterion' => 'v.criterion_id',
        'criterion_name' => 'v.criterion_name',
        'employee' => 'v.evaluated_employee_id',
        'employee_name' => 'v.evaluated_name',
    ];
    private array $allowedFilters = [
        'year' => 'v.eval_year',
        'month' => 'v.eval_month',
        'start_date' => 'v.evaluation_date',
        'end_date' => 'v.evaluation_date',
        'branch_id' => 'v.branch_id',
        'department_id' => 'v.department_id',
        'template_id' => 'v.template_id',
        'status' => 'v.status',
        'evaluated_employee_id' => 'v.evaluated_employee_id',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function build(array $filters = [], array $dimensions = [], array $measures = []): array
    {
        $fromTable = 'vw_evaluation_facts v';
        $groupBy = [];
        $select = [];
        $where = [];
        $params = [];

        foreach ($dimensions as $dim) {
            if (isset($this->allowedDimensions[$dim])) {
                $col = $this->allowedDimensions[$dim];
                $select[] = "$col AS $dim";
                $groupBy[] = $col;
            }
        }

        if (empty($groupBy)) {
            $select[] = 'COUNT(*) AS count_rows';
        }

        foreach ($measures as $m) {
            switch ($m) {
                case 'avg_score':
                    $select[] = 'ROUND(AVG(v.score), 4) AS avg_score';
                    break;
                case 'min_score':
                    $select[] = 'MIN(v.score) AS min_score';
                    break;
                case 'max_score':
                    $select[] = 'MAX(v.score) AS max_score';
                    break;
                case 'count_evaluations':
                    $select[] = 'COUNT(DISTINCT v.evaluation_id) AS count_evaluations';
                    break;
                case 'count_employees':
                    $select[] = 'COUNT(DISTINCT v.evaluated_employee_id) AS count_employees';
                    break;
                case 'completion_rate':
                    $select[] = "ROUND(COUNT(DISTINCT CASE WHEN v.status IN ('completed','submitted') THEN v.evaluation_id END)*100.0/NULLIF(COUNT(DISTINCT v.evaluation_id),0),2) AS completion_rate";
                    break;
            }
        }

        $this->applyFilters($filters, $where, $params);

        $sql = 'SELECT ' . implode(', ', $select) . ' FROM ' . $fromTable;
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        if (!empty($groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $groupBy);
        }

        $this->db->query($sql);
        foreach ($params as $k => $v) {
            $this->db->bind($k, $v);
        }
        $rows = $this->db->fetchAll();

        return ['success' => true, 'rows' => $rows, 'meta' => ['sql' => $sql, 'params' => $params]];
    }

    private function applyFilters(array $filters, array &$where, array &$params): void
    {
        foreach ($filters as $key => $val) {
            if ($val === null || $val === '') {
                continue;
            }
            if (isset($this->allowedFilters[$key])) {
                $col = $this->allowedFilters[$key];
                if ($key === 'start_date') {
                    $where[] = "$col >= :start_date";
                    $params[':start_date'] = $val;
                } elseif ($key === 'end_date') {
                    $where[] = "$col <= :end_date";
                    $params[':end_date'] = $val;
                } else {
                    $where[] = "$col = :$key";
                    $params[":$key"] = $val;
                }
            }
        }
    }
}
