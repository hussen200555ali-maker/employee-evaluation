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
use App\Services\ReportScopeService;
use App\Services\ReportSnapshotService;

/**
 * CD-021 — Reports Module (Implementation Phase)
 * Full implementation of create + archive + generate + printOnly.
 */
final class ReportsController extends Controller
{
    /** @var string[] */
    private const ARCHIVE_TYPES = ['employee', 'periodic', 'course', 'workshop', 'skill'];

    /** @var int[] */
    private const ARCHIVE_PER_PAGE = [10, 15, 25, 50];

    /** @var array<string,string> */
    private const ARCHIVE_SORTS = [
        'newest'    => 'rs.id DESC',
        'oldest'    => 'rs.id ASC',
        'report_no' => 'rs.report_number DESC',
        'name_asc'  => "JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.full_name')) ASC",
        'name_desc' => "JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.full_name')) DESC",
        'branch'    => "JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.branch_name')) ASC",
    ];

    /**
     * Page 1: Create report — search employee + cascading filters + issue report.
     */
    public function create(): void
    {
        Auth::enforce('employees.profile');

        $search    = trim((string)($_GET['q'] ?? ''));
        $branchId  = (int)($_GET['branch_id'] ?? 0);
        $deptId    = (int)($_GET['department_id'] ?? 0);
        $evalType  = strtolower(trim((string)($_GET['eval_type'] ?? '')));
        $dateFrom  = $this->normalizeDate($_GET['date_from'] ?? '');
        $dateTo    = $this->normalizeDate($_GET['date_to'] ?? '');
        $results   = [];

        // نوع التقييم: فردي / دورة / ورشة / مهاري فقط (الكل = بلا قيد).
        if (!in_array($evalType, ['employee', 'course', 'workshop', 'skill'], true)) {
            $evalType = '';
        }
        // فترة معكوسة => نبادلها لتفادي نتائج فارغة غير مفهومة.
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // فلتر القسم مرتبط بالفرع: بلا فرع محدد يُتجاهل أي قسم قادم من رابط قديم.
        if ($branchId <= 0) {
            $deptId = 0;
        }

        // Phase 1 security: scope is resolved server-side and can only narrow results.
        $scopeService = new ReportScopeService();
        $scope = $scopeService->resolve();
        if ($scope['mode'] === ReportScopeService::MODE_NONE) {
            (new ErrorController())->forbidden('reports.view');
            return;
        }
        if ($scope['mode'] !== ReportScopeService::MODE_ALL) {
            $branchId = (int)($scope['branch_id'] ?? 0);
            if ($scope['mode'] === ReportScopeService::MODE_DEPARTMENT) {
                $deptId = (int)($scope['department_id'] ?? 0);
            } elseif ($scope['mode'] === ReportScopeService::MODE_EMPLOYEE) {
                $deptId = 0;
            }
        }

        $branches = (new Branch())->getAll();
        if ($scope['mode'] !== ReportScopeService::MODE_ALL && $branchId > 0) {
            $branches = array_values(array_filter(
                $branches,
                static fn(object $branch): bool => (int)$branch->id === $branchId
            ));
        }

        $departments = $branchId > 0 ? (new Department())->findByBranch($branchId) : [];
        if ($scope['mode'] === ReportScopeService::MODE_DEPARTMENT && $deptId > 0) {
            $departments = array_values(array_filter(
                $departments,
                static fn(object $department): bool => (int)$department->id === $deptId
            ));
        }

        if ($search !== '' || $branchId > 0 || $deptId > 0 || $evalType !== '' || $dateFrom !== '' || $dateTo !== '') {
            $results = $this->searchEmployees($search, $branchId, $deptId, $evalType, $dateFrom, $dateTo);
        }

        $this->view('reports/create', [
            'pageTitle'   => 'إصدار تقرير موظف',
            'search'      => $search,
            'branchId'    => $branchId,
            'deptId'      => $deptId,
            'evalType'    => $evalType,
            'dateFrom'    => $dateFrom,
            'dateTo'      => $dateTo,
            'branches'    => $branches,
            'departments' => $departments,
            'results'     => $results,
            'scope'       => $scope,
            'scopeLabel'  => (string)$scope['label'],
            'csrfToken'   => Auth::generateCsrfToken(),
        ]);
    }

    /**
     * Page 2: Archive — paginated list of all report snapshots with search + filters.
     */
    public function archive(): void
    {
        Auth::enforce('employees.profile');

        $filters = $this->normalizeArchiveFilters($_GET);
        $filters['view'] = in_array((string)($_GET['view'] ?? 'list'), ['list', 'employee'], true)
            ? (string)($_GET['view'] ?? 'list')
            : 'list';
        $page = max(1, (int)($_GET['page'] ?? 1));

        // Phase 1 security: archive respects the same organizational scope.
        $scopeService = new ReportScopeService();
        $scope = $scopeService->resolve();
        if ($scope['mode'] === ReportScopeService::MODE_NONE) {
            (new ErrorController())->forbidden('reports.view');
            return;
        }
        if ($scope['mode'] !== ReportScopeService::MODE_ALL) {
            $scopeBranchId = (int)($scope['branch_id'] ?? 0);
            if ($scopeBranchId > 0) {
                $filters['branch_id'] = $scopeBranchId;
            }
        }

        $employeeRows = [];
        $snapshots = [];
        if ($filters['view'] === 'employee') {
            [$employeeRows, $totalCount] = $this->searchEmployeesArchive($filters, $page, $filters['per_page']);
        } else {
            [$snapshots, $totalCount] = $this->searchSnapshots($filters, $page, $filters['per_page']);
        }
        $totalPages = (int)ceil($totalCount / $filters['per_page']);
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
            if ($filters['view'] === 'employee') {
                [$employeeRows, $totalCount] = $this->searchEmployeesArchive($filters, $page, $filters['per_page']);
            } else {
                [$snapshots, $totalCount] = $this->searchSnapshots($filters, $page, $filters['per_page']);
            }
        }

        $stats   = $this->getArchiveStats($filters);
        $issuers = $this->getArchiveIssuers($filters);

        $branches = (new Branch())->getAll();
        if ($scope['mode'] !== ReportScopeService::MODE_ALL && $filters['branch_id'] > 0) {
            $branches = array_values(array_filter(
                $branches,
                static fn(object $branch): bool => (int)$branch->id === $filters['branch_id']
            ));
        }
        $years = $this->getReportYears();

        $this->view('reports/archive', [
            'pageTitle'     => 'سجل التقارير المؤرشفة',
            'viewMode'      => $filters['view'],
            'employeeRows'  => $employeeRows,
            'employeeDetail'=> null,
            'employeeId'    => 0,
            'snapshots'     => $snapshots,
            'totalCount'    => $totalCount,
            'page'          => $page,
            'perPage'       => $filters['per_page'],
            'totalPages'    => $totalPages,
            'filters'       => $filters,
            'stats'         => $stats,
            'issuers'       => $issuers,
            'branches'      => $branches,
            'years'         => $years,
            'scope'         => $scope,
            'scopeLabel'    => (string)$scope['label'],
            'csrfToken'     => Auth::generateCsrfToken(),
        ]);
    }

    public function archiveEmployee(int $employeeId): void
    {
        Auth::enforce('employees.profile');

        if ($employeeId < 1 || !(new ReportScopeService())->canAccessEmployee($employeeId)) {
            (new ErrorController())->forbidden('reports.view');
            return;
        }

        $filters = $this->normalizeArchiveFilters($_GET);
        $filters['employee_id'] = $employeeId;
        $filters['view'] = 'list';
        $page = max(1, (int)($_GET['page'] ?? 1));

        [$snapshots, $totalCount] = $this->searchSnapshots($filters, $page, $filters['per_page']);
        $totalPages = (int)ceil($totalCount / $filters['per_page']);
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
            [$snapshots, $totalCount] = $this->searchSnapshots($filters, $page, $filters['per_page']);
        }

        $stats = $this->getArchiveStats($filters);
        $issuers = $this->getArchiveIssuers($filters);

        $branches = (new Branch())->getAll();
        $years = $this->getReportYears();

        $employee = (new Employee())->findById($employeeId);

        // آخر تقرير للموظف (مستقل عن pagination/sort).
        $latestParams = [];
        $latestWhere = $this->buildArchiveWhere($filters, $latestParams);
        $latestDb = Database::getInstance();
        $latestDb->query(
            'SELECT rs.report_number, rs.created_at
             FROM report_snapshots rs
             LEFT JOIN employees se ON se.id = rs.employee_id'
            . $latestWhere
            . ' ORDER BY rs.id DESC LIMIT 1'
        );
        foreach ($latestParams as $key => $value) {
            $latestDb->bind($key, $value);
        }
        $latestReport = $latestDb->fetch();

        $this->view('reports/archive', [
            'pageTitle'      => 'سجل تقارير الموظف',
            'viewMode'       => 'detail',
            'employeeDetail' => $employee,
            'employeeId'     => $employeeId,
            'latestReport'   => $latestReport,
            'snapshots'      => $snapshots,
            'totalCount'     => $totalCount,
            'page'           => $page,
            'perPage'        => $filters['per_page'],
            'totalPages'     => $totalPages,
            'filters'        => $filters,
            'stats'          => $stats,
            'issuers'        => $issuers,
            'branches'       => $branches,
            'years'          => $years,
            'scope'          => (new ReportScopeService())->resolve(),
            'scopeLabel'     => (new ReportScopeService())->label(),
            'csrfToken'      => Auth::generateCsrfToken(),
        ]);
    }
    public function generate(): void
    {
        Auth::enforce('employees.profile');
        // الحقل القياسي في التطبيق هو _token، ونتسامح مع _csrf و csrf_token أيضاً.
        $csrfToken = $_POST['_csrf'] ?? $_POST['_token'] ?? $_POST['csrf_token'] ?? '';
        if (!Auth::validateCsrfToken($csrfToken)) {
            Flash::set('انتهت صلاحية الجلسة أو الرمز الأمني غير صالح. أعد المحاولة.', 'error');
            $this->redirect('/reports/create');
        }

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        if ($employeeId < 1) {
            Flash::set('معرّف الموظف غير صالح.', 'error');
            $this->redirect('/reports/create');
        }

        // Phase 1 security: direct POST cannot bypass the organizational scope.
        if (!(new ReportScopeService())->canAccessEmployee($employeeId)) {
            (new ErrorController())->forbidden('reports.view');
            return;
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

        // Phase 1 security: direct print links cannot bypass the organizational scope.
        if (!(new ReportScopeService())->canAccessEmployee((int)$employeeId)) {
            (new ErrorController())->forbidden('reports.view');
            return;
        }

        $employee = (new Employee())->findById($employeeId);
        if (!$employee) {
            Flash::set('الموظف غير موجود.', 'error');
            $this->redirect('/reports/create');
        }

        $reportService = new EmployeeReportService();
        $report = $reportService->getProfileReport($employeeId);

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

        // Phase 1 security: only snapshots inside the caller's scope are exportable.
        if (!(new ReportScopeService())->canAccessSnapshot($snapshot)) {
            (new ErrorController())->forbidden('reports.view');
            return;
        }

        try {
            $snapshot->snapshot_data = json_decode($snapshot->snapshot_json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            http_response_code(500);
            echo 'تعذر قراءة بيانات التقرير.';
            return;
        }
        unset($snapshot->snapshot_json);

        $exportService = new ReportExportService();
        $result = $exportService->render($format, $snapshot);

        header('Content-Type: ' . $result['content_type']);
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . strlen($result['content']));
        echo $result['content'];
    }

    // === Private helpers ===

    /**
     * Accepts only a real Y-m-d date; anything else is ignored (empty string).
     */
    private function normalizeDate(mixed $value): string
    {
        $value = trim((string)$value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }
        [$year, $month, $day] = array_map('intval', explode('-', $value));
        return checkdate($month, $day, $year) ? $value : '';
    }

    private function searchEmployees(
        string $search,
        int $branchId,
        int $deptId,
        string $evalType = '',
        string $dateFrom = '',
        string $dateTo = ''
    ): array {
        $db = Database::getInstance();

        // BUG-H + CD-040.6: one canonical type expression shared by aggregation and filtering.
        $typeExpr = "CASE
                        WHEN t.target_type = 'employee' THEN 'employee'
                        WHEN t.target_type = 'skill' THEN 'skill'
                        WHEN t.target_type IN ('employee_in_course', 'course') THEN 'course'
                        WHEN t.target_type IN ('employee_in_workshop', 'workshop') THEN 'workshop'
                        WHEN ev.evaluation_type IN ('annual', 'periodic') THEN ev.evaluation_type
                        WHEN ev.evaluation_type IS NOT NULL AND ev.evaluation_type <> '' THEN ev.evaluation_type
                        ELSE 'annual'
                     END";

        // N+1 fix: employee rows + their evaluation type counts in ONE grouped query.
        $sql = "SELECT emp.id, emp.emp_code, emp.full_name, emp.position_id,
                       p.title AS position_title, d.name AS department_name,
                       b.name AS branch_name, emp.is_active,
                       COUNT(ev.id) AS eval_total,
                       SUM(CASE WHEN ev.id IS NOT NULL AND ({$typeExpr}) = 'employee' THEN 1 ELSE 0 END) AS eval_employee,
                       SUM(CASE WHEN ev.id IS NOT NULL AND ({$typeExpr}) = 'annual' THEN 1 ELSE 0 END) AS eval_annual,
                       SUM(CASE WHEN ev.id IS NOT NULL AND ({$typeExpr}) = 'periodic' THEN 1 ELSE 0 END) AS eval_periodic,
                       SUM(CASE WHEN ev.id IS NOT NULL AND ({$typeExpr}) = 'course' THEN 1 ELSE 0 END) AS eval_course,
                       SUM(CASE WHEN ev.id IS NOT NULL AND ({$typeExpr}) = 'workshop' THEN 1 ELSE 0 END) AS eval_workshop,
                       SUM(CASE WHEN ev.id IS NOT NULL AND ({$typeExpr}) = 'skill' THEN 1 ELSE 0 END) AS eval_skill
                FROM employees emp
                LEFT JOIN positions p ON p.id = emp.position_id
                LEFT JOIN departments d ON d.id = emp.department_id
                LEFT JOIN branches b ON b.id = emp.branch_id
                LEFT JOIN evaluations ev
                       ON ev.evaluated_employee_id = emp.id
                      AND ev.status IN ('approved', 'completed')
                LEFT JOIN form_templates t ON t.id = ev.template_id
                WHERE emp.is_active = 1
                  AND emp.emp_code IS NOT NULL AND emp.emp_code <> ''";
        $params = [];

        // Mandatory server-side scope (cannot be widened by GET parameters).
        $sql .= (new ReportScopeService())->applyEmployeeConstraint('emp', $params);

        if ($search !== '') {
            $sql .= ' AND (emp.full_name LIKE :search OR emp.emp_code LIKE :search2)';
            $params[':search']  = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }
        if ($branchId > 0) {
            $sql .= ' AND emp.branch_id = :branch_id';
            $params[':branch_id'] = $branchId;
        }
        if ($deptId > 0) {
            $sql .= ' AND emp.department_id = :dept_id';
            $params[':dept_id'] = $deptId;
        }
        // Advanced filters apply to the evaluations feeding the summary. When active they
        // intentionally narrow the employee list to those having at least one match.
        if ($evalType !== '') {
            $sql .= " AND ({$typeExpr}) = :eval_type";
            $params[':eval_type'] = $evalType;
        }
        if ($dateFrom !== '') {
            $sql .= ' AND ev.evaluation_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $sql .= ' AND ev.evaluation_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }

        $sql .= ' GROUP BY emp.id, emp.emp_code, emp.full_name, emp.position_id, p.title,
                          d.name, b.name, emp.is_active
                  ORDER BY emp.full_name
                  LIMIT 50';

        $db->query($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }

        $employees = $db->fetchAll();
        foreach ($employees as $emp) {
            $emp->eval_summary = $this->buildEvalSummary($emp);
        }

        return $employees;
    }

    /**
     * Formats the card summary from pre-aggregated counts (no DB access, no N+1).
     */
    private function buildEvalSummary(object $row): string
    {
        $total = (int)($row->eval_total ?? 0);
        if ($total < 1) {
            return 'لا توجد تقييمات';
        }

        $counts = [
            'employee' => (int)($row->eval_employee ?? 0),
            'annual'   => (int)($row->eval_annual ?? 0),
            'periodic' => (int)($row->eval_periodic ?? 0),
            'course'   => (int)($row->eval_course ?? 0),
            'workshop' => (int)($row->eval_workshop ?? 0),
            'skill'    => (int)($row->eval_skill ?? 0),
        ];

        // صيغ الجمع العربية الصحيحة لكل نوع تقييم.
        $forms = [
            'employee' => ['one' => 'تقييم فردي عام', 'two' => 'تقييمان فرديان', 'few' => 'تقييمات فردية', 'many' => 'تقييماً فردياً'],
            'annual'   => ['one' => 'تقييم سنوي', 'two' => 'تقييمان سنويان', 'few' => 'تقييمات سنوية', 'many' => 'تقييماً سنوياً'],
            'periodic' => ['one' => 'تقييم دوري', 'two' => 'تقييمان دوريان', 'few' => 'تقييمات دورية', 'many' => 'تقييماً دورياً'],
            'course'   => ['one' => 'دورة', 'two' => 'دورتان', 'few' => 'دورات', 'many' => 'دورة'],
            'workshop' => ['one' => 'ورشة', 'two' => 'ورشتان', 'few' => 'ورش', 'many' => 'ورشة'],
            'skill'    => ['one' => 'تقييم مهاري', 'two' => 'تقييمان مهاريان', 'few' => 'تقييمات مهارية', 'many' => 'تقييماً مهاريًا'],
        ];

        $formatCount = static function (int $count, array $form): string {
            if ($count === 1) {
                return $form['one'];
            }
            if ($count === 2) {
                return $form['two'];
            }
            return $count . ' ' . ($count <= 10 ? $form['few'] : $form['many']);
        };

        $parts = [];
        foreach ($forms as $type => $form) {
            if ($counts[$type] > 0) {
                $parts[] = $formatCount($counts[$type], $form);
            }
        }

        $known = array_sum($counts);
        $other = $total - $known;
        if ($other > 0) {
            $parts[] = $other === 1 ? 'تقييم آخر' : ($other === 2 ? 'تقييمان آخران' : $other . ' تقييمات أخرى');
        }

        $totalLabel = match (true) {
            $total === 1 => 'تقييم واحد',
            $total === 2 => 'تقييمان',
            $total <= 10 => $total . ' تقييمات',
            default => $total . ' تقييماً',
        };

        return $totalLabel . ': ' . implode(' + ', $parts);
    }

    private function searchEmployeesArchive(array $filters, int $page, int $perPage): array
    {
        $db = Database::getInstance();
        $offset = ($page - 1) * $perPage;

        $params = [];
        $where = $this->buildArchiveWhere($filters, $params);
        $scopeJoin = ' LEFT JOIN employees se ON se.id = rs.employee_id';

        // Count distinct employees.
        $db->query('SELECT COUNT(DISTINCT rs.employee_id) FROM report_snapshots rs' . $scopeJoin . $where);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $totalCount = (int)$db->fetchColumn();

        $sort = (string)($filters['sort'] ?? 'newest');
        $orderBy = match ($sort) {
            'oldest'   => 'latest_created_at ASC, latest_id ASC',
            'name_asc' => 'full_name ASC',
            'name_desc'=> 'full_name DESC',
            'branch'   => 'branch_name ASC, full_name ASC',
            'count'    => 'report_count DESC, full_name ASC',
            'report_no'=> 'latest_report_number DESC',
            default    => 'latest_created_at DESC, latest_id DESC',
        };

        $sql = "SELECT rs.employee_id AS id,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.full_name')) AS full_name,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.emp_code')) AS emp_code,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.branch_name')) AS branch_name,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.department_name')) AS department_name,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.position_title')) AS position_title,
                       COUNT(*) AS report_count,
                       MAX(rs.id) AS latest_id,
                       MAX(rs.created_at) AS latest_created_at,
                       (SELECT rs2.report_number FROM report_snapshots rs2 WHERE rs2.employee_id = rs.employee_id ORDER BY rs2.id DESC LIMIT 1) AS latest_report_number,
                       AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.report.averages.overall.average')) AS DECIMAL(8,2))) AS avg_score
                FROM report_snapshots rs" . $scopeJoin . $where
            . " GROUP BY rs.employee_id,
                        JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.full_name')),
                        JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.emp_code')),
                        JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.branch_name')),
                        JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.department_name')),
                        JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.position_title'))
            " . ' ORDER BY ' . $orderBy . ' LIMIT :limit OFFSET :offset';

        $db->query($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $db->bind(':limit', $perPage);
        $db->bind(':offset', $offset);

        return [$db->fetchAll(), $totalCount];
    }

    private function searchSnapshots(array $filters, int $page, int $perPage): array
    {
        $db = Database::getInstance();
        $offset = ($page - 1) * $perPage;

        $params = [];
        $where = $this->buildArchiveWhere($filters, $params);
        $scopeJoin = ' LEFT JOIN employees se ON se.id = rs.employee_id';

        // Count
        $db->query('SELECT COUNT(*) FROM report_snapshots rs' . $scopeJoin . $where);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $totalCount = (int)$db->fetchColumn();

        $orderBy = self::ARCHIVE_SORTS[$filters['sort']] ?? self::ARCHIVE_SORTS['newest'];

        // Data
        $sql = "SELECT rs.id, rs.report_number, rs.employee_id, rs.created_at, rs.created_by_user_id,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.full_name')) AS full_name,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.emp_code')) AS emp_code,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.branch_name')) AS branch_name,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.department_name')) AS department_name,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.position_title')) AS position_title,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.created_by.username')) AS created_by,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.report.averages')) AS averages_json,
                       JSON_EXTRACT(rs.snapshot_json, '$.report.records[*].target_type') AS record_targets_json,
                       JSON_EXTRACT(rs.snapshot_json, '$.report.records[*].evaluation_type') AS record_eval_types_json,
                       CAST(JSON_LENGTH(JSON_EXTRACT(rs.snapshot_json, '$.report.records')) AS UNSIGNED) AS records_count
                FROM report_snapshots rs" . $scopeJoin . $where
            . ' ORDER BY ' . $orderBy . ', rs.id DESC LIMIT :limit OFFSET :offset';
        $db->query($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $db->bind(':limit', $perPage);
        $db->bind(':offset', $offset);

        return [$db->fetchAll(), $totalCount];
    }

    /**
     * Normalizes every archive GET parameter through strict whitelists.
     *
     * @return array{q:string,branch_id:int,year:int,type:string,date_from:string,date_to:string,created_by:int,sort:string,per_page:int}
     */
    private function normalizeArchiveFilters(array $input): array
    {
        $search   = trim((string)($input['q'] ?? ''));
        $branchId = max(0, (int)($input['branch_id'] ?? 0));
        $year     = (int)($input['year'] ?? 0);
        if ($year < 1900 || $year > 2200) {
            $year = 0;
        }

        $type = strtolower(trim((string)($input['type'] ?? '')));
        if (!in_array($type, self::ARCHIVE_TYPES, true)) {
            $type = '';
        }

        $dateFrom = $this->normalizeDate($input['date_from'] ?? '');
        $dateTo   = $this->normalizeDate($input['date_to'] ?? '');
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $createdBy = max(0, (int)($input['created_by'] ?? 0));
        $sort      = (string)($input['sort'] ?? 'newest');
        if (!array_key_exists($sort, self::ARCHIVE_SORTS)) {
            $sort = 'newest';
        }

        $perPage = (int)($input['per_page'] ?? 15);
        if (!in_array($perPage, self::ARCHIVE_PER_PAGE, true)) {
            $perPage = 15;
        }

        return [
            'q'          => $search,
            'branch_id'  => $branchId,
            'year'       => $year,
            'type'       => $type,
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
            'created_by' => $createdBy,
            'sort'       => $sort,
            'per_page'   => $perPage,
        ];
    }

    /**
     * Builds the shared archive predicate (scope + all active filters).
     */
    private function buildArchiveWhere(array $filters, array &$params): string
    {
        $where = ' WHERE 1=1';
        $where .= (new ReportScopeService())->applySnapshotConstraint('rs', 'se', $params);

        if (($filters['q'] ?? '') !== '') {
            $where .= " AND (rs.report_number LIKE :f_search
                        OR JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.full_name')) LIKE :f_search2
                        OR JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.emp_code')) LIKE :f_search3)";
            $params[':f_search']  = '%' . $filters['q'] . '%';
            $params[':f_search2'] = '%' . $filters['q'] . '%';
            $params[':f_search3'] = '%' . $filters['q'] . '%';
        }

        if (($filters['branch_id'] ?? 0) > 0) {
            // التقرير يخزّن اسم الفرع وقت الإصدار، لذا نطابق الاسم مع الفرع المطلوب.
            $where .= " AND JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.branch_name'))
                        = (SELECT b.name FROM branches b WHERE b.id = :f_branch_id)";
            $params[':f_branch_id'] = (int)$filters['branch_id'];
        }

        if (($filters['year'] ?? 0) > 0) {
            $where .= ' AND YEAR(rs.created_at) = :f_year';
            $params[':f_year'] = (int)$filters['year'];
        }

        if (($filters['type'] ?? '') !== '') {
            // تصنيف النوع محفوظ داخل النسخة (averages) — يغطي النسخ القديمة والجديدة بلا تعديل JSON.
            $where .= " AND CAST(JSON_EXTRACT(rs.snapshot_json, '$.report.averages." . $filters['type'] . ".evaluation_count') AS UNSIGNED) > 0";
        }

        if (($filters['date_from'] ?? '') !== '') {
            $where .= ' AND DATE(rs.created_at) >= :f_date_from';
            $params[':f_date_from'] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where .= ' AND DATE(rs.created_at) <= :f_date_to';
            $params[':f_date_to'] = $filters['date_to'];
        }

        if (($filters['employee_id'] ?? 0) > 0) {
            $where .= ' AND rs.employee_id = :f_employee_id';
            $params[':f_employee_id'] = (int)$filters['employee_id'];
        }

        return $where;
    }

    /**
     * One aggregate query for the KPI cards, over the same filtered dataset.
     *
     * @return array{total:int,employees:int,this_month:int,avg_score:?float,last_issue:?string}
     */
    private function getArchiveStats(array $filters): array
    {
        $db = Database::getInstance();
        $params = [];
        $where = $this->buildArchiveWhere($filters, $params);

        $sql = "SELECT COUNT(*) AS total,
                       COUNT(DISTINCT rs.employee_id) AS employees,
                       SUM(CASE WHEN rs.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS this_month,
                       AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.report.averages.overall.average')) AS DECIMAL(8,2))) AS avg_score,
                       MAX(rs.created_at) AS last_issue
                FROM report_snapshots rs
                LEFT JOIN employees se ON se.id = rs.employee_id" . $where;

        $db->query($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $row = $db->fetch();

        return [
            'total'      => (int)($row->total ?? 0),
            'employees'  => (int)($row->employees ?? 0),
            'this_month' => (int)($row->this_month ?? 0),
            'avg_score'  => $row->avg_score !== null ? round((float)$row->avg_score, 1) : null,
            'last_issue' => $row->last_issue !== null ? (string)$row->last_issue : null,
        ];
    }

    /**
     * Issuers available within the current filters (the issuer filter itself is ignored).
     *
     * @return object[] id, username, total
     */
    private function getArchiveIssuers(array $filters): array
    {
        $db = Database::getInstance();
        $issuerFilters = $filters;
        $issuerFilters['created_by'] = 0;

        $params = [];
        $where = $this->buildArchiveWhere($issuerFilters, $params);

        $sql = "SELECT rs.created_by_user_id AS id,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.created_by.username')) AS username,
                       COUNT(*) AS total
                FROM report_snapshots rs
                LEFT JOIN employees se ON se.id = rs.employee_id" . $where
            . " GROUP BY rs.created_by_user_id,
                        JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.created_by.username'))
                ORDER BY total DESC, username ASC";

        $db->query($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        return $db->fetchAll();
    }

    private function getReportYears(): array
    {
        $db = Database::getInstance();
        $params = [];
        $scopeSql = (new ReportScopeService())->applySnapshotConstraint('rs', 'se', $params);
        $sql = 'SELECT DISTINCT YEAR(rs.created_at) AS yr
                FROM report_snapshots rs
                LEFT JOIN employees se ON se.id = rs.employee_id
                WHERE 1=1' . $scopeSql;
        $db->query($sql . ' ORDER BY yr DESC');
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        return array_map(fn($r) => (int)$r->yr, $db->fetchAll());
    }
}
