<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ReportEngine;
use App\Models\Template;
use App\Models\Branch;
use App\Models\Department;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class ReportsAnalyticsController extends Controller
{
    private ReportEngine $engine;

    public function __construct()
    {
        $this->engine = new ReportEngine();
    }

    public function gap()
    {
        $templateModel = new Template();
        $branchModel = new Branch();
        $deptModel = new Department();

        $data = [
            'pageTitle' => 'تحليل الفجوات (Gap Analysis)',
            'templates' => $templateModel->getAll(),
            'branches' => $branchModel->getAll(),
            'departments' => $deptModel->getAll(),
        ];
        $this->view('reports/analytics/gap', $data);
    }

    public function gapData()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $filters = $input['filters'] ?? [];
        $res = $this->engine->build($filters, ['criterion', 'criterion_name'], ['avg_score', 'count_evaluations']);
        header('Content-Type: application/json');
        echo json_encode($res);
        exit;
    }

    public function scorecard()
    {
        $templateModel = new Template();
        $branchModel = new Branch();
        $deptModel = new Department();

        $data = [
            'pageTitle' => 'بطاقة الأداء الفردية (Employee Scorecard)',
            'templates' => $templateModel->getAll(),
            'branches' => $branchModel->getAll(),
            'departments' => $deptModel->getAll(),
        ];
        $this->view('reports/analytics/scorecard', $data);
    }

    public function ninebox()
    {
        $templateModel = new Template();
        $branchModel = new Branch();
        $deptModel = new Department();

        $data = [
            'pageTitle' => 'مصفوفة الأداء 9-Box (9-Box Grid)',
            'templates' => $templateModel->getAll(),
            'branches' => $branchModel->getAll(),
            'departments' => $deptModel->getAll(),
        ];
        $this->view('reports/analytics/ninebox', $data);
    }

    public function nineboxData()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $filters = $input['filters'] ?? [];

        $this->db = new \App\Core\Database();
        $where = [];
        $params = [];
        if (!empty($filters['branch_id'])) {
            $where[] = 'v.branch_id = :branch_id';
            $params[':branch_id'] = $filters['branch_id'];
        }
        if (!empty($filters['department_id'])) {
            $where[] = 'v.department_id = :department_id';
            $params[':department_id'] = $filters['department_id'];
        }
        if (!empty($filters['template_id'])) {
            $where[] = 'v.template_id = :template_id';
            $params[':template_id'] = $filters['template_id'];
        }
        if (!empty($filters['year'])) {
            $where[] = 'v.eval_year = :year';
            $params[':year'] = $filters['year'];
        }
        if (!empty($filters['month'])) {
            $where[] = 'v.eval_month = :month';
            $params[':month'] = $filters['month'];
        }
        $w = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT v.evaluated_employee_id, v.evaluated_name, v.department_name, v.branch_name, 
                       MAX(v.normalized_total) AS x_score, ROUND(AVG(v.score),4) AS y_score
                FROM vw_evaluation_facts v $w
                GROUP BY v.evaluated_employee_id, v.evaluated_name, v.department_name, v.branch_name";
        $this->db->query($sql);
        foreach ($params as $k => $v) {
            $this->db->bind($k, $v);
        }
        $rows = $this->db->fetchAll();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'rows' => $rows]);
        exit;
    }

    public function executive()
    {
        $templateModel = new Template();
        $branchModel = new Branch();
        $deptModel = new Department();

        $data = [
            'pageTitle' => 'لوحة القيادة التنفيذية (Executive Dashboard)',
            'templates' => $templateModel->getAll(),
            'branches' => $branchModel->getAll(),
            'departments' => $deptModel->getAll(),
        ];
        $this->view('reports/analytics/executive', $data);
    }

    public function executiveData()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $filters = $input['filters'] ?? [];

        $this->db = new \App\Core\Database();
        $where = [];
        $params = [];
        foreach (['branch_id', 'department_id', 'template_id', 'year', 'month'] as $k) {
            if (!empty($filters[$k])) {
                if ($k === 'year') {
                    $where[] = 'es.eval_year = :year';
                } elseif ($k === 'month') {
                    $where[] = 'es.eval_month = :month';
                } else {
                    $where[] = "es.$k = :$k";
                }
                $params[":$k"] = $filters[$k];
            }
        }
        $w = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        $kpis = [];
        $sqlKpi = "SELECT ROUND(AVG(es.normalized_total),4) AS overall_avg,
                          ROUND(COUNT(DISTINCT CASE WHEN es.status IN ('completed','submitted') THEN es.evaluation_id END)*100.0/NULLIF(COUNT(DISTINCT es.evaluation_id),0),2) AS completion_rate,
                          COUNT(DISTINCT es.evaluated_employee_id) AS employees_evaluated,
                          COUNT(DISTINCT CASE WHEN es.status IN ('completed','submitted') THEN es.evaluation_id END) AS evaluations_completed
                   FROM vw_employee_scores es $w";
        $this->db->query($sqlKpi);
        foreach ($params as $kk => $vv) {
            $this->db->bind($kk, $vv);
        }
        $kpis = $this->db->fetch();

        $topBranches = [];
        $sqlTop = "SELECT es.branch_id, es.branch_name, ROUND(AVG(es.normalized_total),4) AS avg_score
                    FROM vw_employee_scores es $w
                    GROUP BY es.branch_id, es.branch_name
                    ORDER BY avg_score DESC
                    LIMIT 3";
        $this->db->query($sqlTop);
        foreach ($params as $kk => $vv) {
            $this->db->bind($kk, $vv);
        }
        $topBranches = $this->db->fetchAll();

        $bottomDepts = [];
        $sqlBot = "SELECT es.department_id, es.department_name, ROUND(AVG(es.normalized_total),4) AS avg_score
                    FROM vw_employee_scores es $w
                    GROUP BY es.department_id, es.department_name
                    ORDER BY avg_score ASC
                    LIMIT 3";
        $this->db->query($sqlBot);
        foreach ($params as $kk => $vv) {
            $this->db->bind($kk, $vv);
        }
        $bottomDepts = $this->db->fetchAll();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'kpis' => $kpis, 'top_branches' => $topBranches, 'bottom_departments' => $bottomDepts]);
        exit;
    }
}
