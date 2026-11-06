<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Services\EmployeeReportService;
use App\Services\ReportExportService;
use App\Services\ReportSnapshotService;

/**
 * CD-021 — Reports Module (Implementation Phase)
 * Full implementation of create + archive + generate + printOnly.
 */
final class ReportsController extends Controller
{
    /**
     * Page 1: Create report — search employee + cascading filters + issue report.
     */
    public function create(): void
    {
        Auth::enforce('employees.profile');

        $search    = trim((string)($_GET['q'] ?? ''));
        $branchId  = (int)($_GET['branch_id'] ?? 0);
        $deptId    = (int)($_GET['department_id'] ?? 0);
        $results   = [];

        $branches = (new Branch())->getAll();

        if ($search !== '' || $branchId > 0 || $deptId > 0) {
            $results = $this->searchEmployees($search, $branchId, $deptId);
        }

        $this->view('reports/create', [
            'pageTitle'   => 'إصدار تقرير موظف',
            'search'      => $search,
            'branchId'    => $branchId,
            'deptId'      => $deptId,
            'branches'    => $branches,
            'results'     => $results,
            'csrfToken'   => Auth::generateCsrfToken(),
        ]);
    }

    /**
     * Page 2: Archive — paginated list of all report snapshots with search + filters.
     */
    public function archive(): void
    {
        Auth::enforce('employees.profile');

        $search   = trim((string)($_GET['q'] ?? ''));
        $branchId = (int)($_GET['branch_id'] ?? 0);
        $year     = (int)($_GET['year'] ?? 0);
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = 15;

        [$snapshots, $totalCount] = $this->searchSnapshots($search, $branchId, $year, $page, $perPage);
        $branches = (new Branch())->getAll();
        $years    = $this->getReportYears();

        $this->view('reports/archive', [
            'pageTitle'   => 'سجل التقارير المؤرشفة',
            'snapshots'   => $snapshots,
            'totalCount'  => $totalCount,
            'page'        => $page,
            'perPage'     => $perPage,
            'totalPages'  => (int)ceil($totalCount / $perPage),
            'search'      => $search,
            'branchId'    => $branchId,
            'year'        => $year,
            'branches'    => $branches,
            'years'       => $years,
            'csrfToken'   => Auth::generateCsrfToken(),
        ]);
    }

    /**
     * POST: Generate a report snapshot (immutable, saved to DB).
     */
    public function generate(): void
    {
        Auth::enforce('employees.profile');
        Auth::validateCsrfToken($_POST['_csrf'] ?? '');

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        if ($employeeId < 1) {
            Flash::set('معرّف الموظف غير صالح.', 'error');
            $this->redirect('/reports/create');
        }

        try {
            $snapshot = (new ReportSnapshotService())
                ->createForEmployee($employeeId, (int)Auth::id());

            Flash::set('تم إصدار التقرير رقم ' . $snapshot->report_number . ' وحفظه في السجل.', 'success');
            $this->redirect('/employees/profile/' . $employeeId . '/reports/' . (int)$snapshot->id);
        } catch (\Throwable $e) {
            Flash::set('تعذر إصدار التقرير: ' . $e->getMessage(), 'error');
            $this->redirect('/reports/create');
        }
    }

    /**
     * GET: Print-only PDF (transient — no DB save, no snapshot).
     */
    public function printOnly(int $employeeId): void
    {
        Auth::enforce('employees.profile');

        $reportService = new EmployeeReportService();
        $report = $reportService->getProfileReport($employeeId);

        $employee = (new Employee())->findById($employeeId);
        if (!$employee) {
            Flash::set('الموظف غير موجود.', 'error');
            $this->redirect('/reports/create');
        }

        // Build a transient snapshot (not saved to DB)
        $snapshot = (object)[
            'report_number' => 'PRINT-' . date('Ymd-His'),
            'created_at'    => date('Y-m-d H:i:s'),
            'snapshot_data' => [
                'employee' => [
                    'id'              => (int)$employee->id,
                    'emp_code'        => $employee->emp_code,
                    'full_name'       => $employee->full_name,
                    'position_title'  => $employee->position_title ?? '',
                    'department_name' => $employee->department_name ?? '',
                    'branch_name'     => $employee->branch_name ?? '',
                    'manager_name'    => $employee->manager_name ?? '',
                    'email'           => $employee->personal_email ?? null,
                    'phone'           => $employee->phone ?? null,
                    'hire_date'       => $employee->join_date ?? null,
                    'status'          => $employee->is_active ? 'نشط' : 'غير نشط',
                ],
                'report' => $report,
            ],
        ];

        $exportService = new ReportExportService();
        $result = $exportService->render('pdf', $snapshot);

        header('Content-Type: ' . $result['content_type']);
        header('Content-Disposition: inline; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . strlen($result['content']));
        echo $result['content'];
    }

    /**
     * Export an existing snapshot (from archive) in a specific format.
     */
    public function export(int $snapshotId, string $format): void
    {
        Auth::enforce('employees.profile');

        $allowedFormats = ['pdf', 'xlsx', 'docx'];
        if (!in_array($format, $allowedFormats, true)) {
            http_response_code(400);
            echo 'صيغة غير مدعومة.';
            return;
        }

        $db = Database::getInstance();
        $db->query('SELECT * FROM report_snapshots WHERE id = :id');
        $db->bind(':id', $snapshotId);
        $snapshot = $db->fetch();

        if (!$snapshot) {
            http_response_code(404);
            echo 'التقرير غير موجود.';
            return;
        }

        $snapshot->snapshot_data = json_decode($snapshot->snapshot_json, true);

        $exportService = new ReportExportService();
        $result = $exportService->render($format, $snapshot);

        header('Content-Type: ' . $result['content_type']);
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . strlen($result['content']));
        echo $result['content'];
    }

    // === Private helpers ===

    private function searchEmployees(string $search, int $branchId, int $deptId): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT e.id, e.emp_code, e.full_name, e.position_id,
                       p.title AS position_title, d.name AS department_name,
                       b.name AS branch_name, e.is_active
                FROM employees e
                LEFT JOIN positions p ON p.id = e.position_id
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN branches b ON b.id = e.branch_id
                WHERE e.is_active = 1
                  AND e.emp_code IS NOT NULL AND e.emp_code <> ""';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (e.full_name LIKE :search OR e.emp_code LIKE :search2)';
            $params[':search']  = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }
        if ($branchId > 0) {
            $sql .= ' AND e.branch_id = :branch_id';
            $params[':branch_id'] = $branchId;
        }
        if ($deptId > 0) {
            $sql .= ' AND e.department_id = :dept_id';
            $params[':dept_id'] = $deptId;
        }
        $sql .= ' ORDER BY e.full_name LIMIT 50';

        $db->query($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }

        $employees = $db->fetchAll();

        // Attach evaluation summary per employee
        $reportService = new EmployeeReportService();
        foreach ($employees as $emp) {
            $emp->eval_summary = $this->buildEvalSummary($emp->id, $reportService);
        }

        return $employees;
    }

    private function buildEvalSummary(int $employeeId, EmployeeReportService $service): string
    {
        $report = $service->getProfileReport($employeeId);
        $records = $report['records'] ?? [];

        if (empty($records)) {
            return 'لا توجد تقييمات';
        }

        $types = [];
        foreach ($records as $r) {
            $type = $r['evaluation_type'] ?? $r['evaluation_type_label'] ?? 'غير محدد';
            $types[$type] = ($types[$type] ?? 0) + 1;
        }

        $parts = [];
        foreach ($types as $type => $count) {
            $labels = [
                'annual' => 'تقييم سنوي', 'periodic' => 'تقييم دوري',
                'course' => 'دورة', 'workshop' => 'ورشة',
            ];
            $label = $labels[$type] ?? $type;
            $parts[] = $count > 1 ? $count . ' ' . $label . 'ات' : $label;
        }

        return count($records) . ' تقييمات: ' . implode(' + ', $parts);
    }

    private function searchSnapshots(string $search, int $branchId, int $year, int $page, int $perPage): array
    {
        $db = Database::getInstance();
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT rs.id, rs.report_number, rs.employee_id, rs.created_at,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.full_name')) AS full_name,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.emp_code')) AS emp_code,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.branch_name')) AS branch_name,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.created_by.username')) AS created_by
                FROM report_snapshots rs
                WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (rs.report_number LIKE :search
                        OR JSON_EXTRACT(rs.snapshot_json, '$.employee.full_name') LIKE :search2
                        OR JSON_EXTRACT(rs.snapshot_json, '$.employee.emp_code') LIKE :search3)";
            $params[':search']  = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }
        if ($year > 0) {
            $sql .= ' AND YEAR(rs.created_at) = :year';
            $params[':year'] = $year;
        }

        // Count
        $countSql = str_replace('SELECT rs.id, rs.report_number, rs.employee_id, rs.created_at,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, \'$.employee.full_name\')) AS full_name,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, \'$.employee.emp_code\')) AS emp_code,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, \'$.employee.branch_name\')) AS branch_name,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, \'$.created_by.username\')) AS created_by',
            'SELECT COUNT(*)', $sql);
        $db->query($countSql);
        foreach ($params as $k => $v) { $db->bind($k, $v); }
        $totalCount = (int)$db->fetchColumn();

        // Data
        $sql .= ' ORDER BY rs.id DESC LIMIT :limit OFFSET :offset';
        $db->query($sql);
        foreach ($params as $k => $v) { $db->bind($k, $v); }
        $db->bind(':limit', $perPage);
        $db->bind(':offset', $offset);

        return [$db->fetchAll(), $totalCount];
    }

    private function getReportYears(): array
    {
        $db = Database::getInstance();
        $db->query('SELECT DISTINCT YEAR(created_at) AS yr FROM report_snapshots ORDER BY yr DESC');
        return array_map(fn($r) => (int)$r->yr, $db->fetchAll());
    }
}
