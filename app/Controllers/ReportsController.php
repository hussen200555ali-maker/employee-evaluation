<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Template; 
use App\Models\Criterion;
use App\Models\ReportModel;
use App\Models\ReportTemplateModel;
use App\Models\Branch;
use App\Models\Department;
use App\Services\ReportEngine;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class ReportsController extends Controller {
    private ReportEngine $engine;

    public function __construct() {
        $this->engine = new ReportEngine();
    }

    public function builder() {
        $templateModel = new Template();
        $criteriaModel = new Criterion();
        $branchModel = new Branch();
        $deptModel = new Department();

        $data = [
            'pageTitle' => 'صانع التقارير (Report Builder)',
            'templates' => $templateModel->getAll(),
            'criteria' => $criteriaModel->getAllWithTemplateName(),
            'branches' => $branchModel->getAll(),
            'departments' => $deptModel->getAll(),
        ];

        $this->view('reports/builder', $data);
    }

    public function builderData()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $filters = $input['filters'] ?? [];
        $dimensions = $input['dimensions'] ?? [];
        $measures = $input['measures'] ?? ['avg_score', 'count_evaluations'];
        $res = $this->engine->build($filters, $dimensions, $measures);
        header('Content-Type: application/json');
        echo json_encode($res);
        exit;
    }

    public function builderExportExcel()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Invalid request');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $filters = $input['filters'] ?? [];
        $dimensions = $input['dimensions'] ?? [];
        $measures = $input['measures'] ?? [];
        $res = $this->engine->build($filters, $dimensions, $measures);
        $rows = $res['rows'] ?? [];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if (!empty($rows)) {
            $headers = array_keys((array)$rows[0]);
            $col = 'A';
            foreach ($headers as $h) {
                $sheet->setCellValue($col++ . '1', $h);
            }
            $r = 2;
            foreach ($rows as $row) {
                $rowArr = (array)$row;
                $col = 'A';
                foreach ($rowArr as $v) {
                    $sheet->setCellValue($col++ . $r, $v);
                }
                $r++;
            }
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="report_builder_' . date('Ymd_His') . '.xlsx"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function builderExportPdf()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Invalid request');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $filters = $input['filters'] ?? [];
        $dimensions = $input['dimensions'] ?? [];
        $measures = $input['measures'] ?? [];
        $res = $this->engine->build($filters, $dimensions, $measures);
        $rows = $res['rows'] ?? [];

        $html = '<html dir="rtl" lang="ar"><body><h2>صانع التقارير</h2>';
        if (!empty($rows)) {
            $headers = array_keys((array)$rows[0]);
            $html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%">';
            $html .= '<tr>';
            foreach ($headers as $h) {
                $html .= '<th>' . htmlspecialchars($h) . '</th>';
            }
            $html .= '</tr>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ((array)$row as $v) {
                    $html .= '<td>' . htmlspecialchars($v) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        $html .= '</body></html>';

        $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
        $mpdf->WriteHTML($html);
        $mpdf->Output('report_builder_' . date('Ymd_His') . '.pdf', 'D');
        exit;
    }

    public function generate() {
        try {
            $isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            if (!$isAjaxRequest || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
                throw new \Exception("Invalid request.");
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON format received.");
            }
            $config = [
                'report_title' => htmlspecialchars($input['report_title'] ?? 'تقرير الأداء العام'),
                'start_date' => !empty($input['start_date']) ? $input['start_date'] : null,
                'end_date' => !empty($input['end_date']) ? $input['end_date'] : null,
                'employee_columns' => $input['employee_columns'] ?? [],
                'courses_weight' => (float) ($input['courses_weight'] ?? 0),
                'workshops_weight' => (float) ($input['workshops_weight'] ?? 0),
            ];
            $reportModel = new ReportModel();
            $reportData = $reportModel->calculateCorporatePerformance($config);
            $templateModel = new Template();
            $criteriaModel = new Criterion();
            $templatesById = array_column($templateModel->getAll(), 'template_name', 'id');
            $criteriaById = array_column($criteriaModel->getAllWithTemplateName(), 'criterion_name', 'id');
            $data = [
                'pageTitle' => $config['report_title'],
                'reportData' => $reportData,
                'config' => $config,
                'templates' => $templatesById,
                'criteria' => $criteriaById
            ];
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        } catch (\Throwable $e) {
            error_log("Report Generation Error: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'An error occurred while generating the report.']);
            exit;
        }
    }
}
