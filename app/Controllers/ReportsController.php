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

        $search   = trim((string)($_GET['q'] ?? ''));
        $branchId = (int)($_GET['branch_id'] ?? 0);
        $year     = (int)($_GET['year'] ?? 0);
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = 15;

        // Phase 1 security: archive respects the same organizational scope.
        $scopeService = new ReportScopeService();
        $scope = $scopeService->resolve();
        if ($scope['mode'] === ReportScopeService::MODE_NONE) {
            (new ErrorController())->forbidden('reports.view');
            return;
        }
        if ($scope['mode'] !== ReportScopeService::MODE_ALL) {
            $branchId = (int)($scope['branch_id'] ?? 0);
        }

        [$snapshots, $totalCount] = $this->searchSnapshots($search, $branchId, $year, $page, $perPage);

        $branches = (new Branch())->getAll();
        if ($scope['mode'] !== ReportScopeService::MODE_ALL && $branchId > 0) {
            $branches = array_values(array_filter(
                $branches,
                static fn(object $branch): bool => (int)$branch->id === $branchId
            ));
        }
        $years = $this->getReportYears();

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
            'scope'       => $scope,
            'scopeLabel'  => (string)$scope['label'],
            'csrfToken'   => Auth::generateCsrfToken(),
        ]);
    }

    /**
     * POST: Generate a report snapshot (immutable, saved to DB).
     */
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

    private function searchSnapshots(string $search, int $branchId, int $year, int $page, int $perPage): array
    {
        $db = Database::getInstance();
        $offset = ($page - 1) * $perPage;

        $where = ' WHERE 1=1';
        $params = [];

        // Phase 1 security: mandatory archive scope (joined to the current employee org record).
        $where .= (new ReportScopeService())->applySnapshotConstraint('rs', 'se', $params);
        $scopeJoin = ' LEFT JOIN employees se ON se.id = rs.employee_id';

        if ($search !== '') {
            $where .= " AND (rs.report_number LIKE :search
                        OR JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.full_name')) LIKE :search2
                        OR JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.emp_code')) LIKE :search3)";
            $params[':search']  = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }
        if ($branchId > 0) {
            // التقرير يخزّن اسم الفرع وقت الإصدار، لذا نطابق الاسم مع الفرع المطلوب.
            $where .= " AND JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.branch_name'))
                        = (SELECT b.name FROM branches b WHERE b.id = :branch_id)";
            $params[':branch_id'] = $branchId;
        }
        if ($year > 0) {
            $where .= ' AND YEAR(rs.created_at) = :year';
            $params[':year'] = $year;
        }

        // Count
        $db->query('SELECT COUNT(*) FROM report_snapshots rs' . $scopeJoin . $where);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $totalCount = (int)$db->fetchColumn();

        // Data
        $sql = "SELECT rs.id, rs.report_number, rs.employee_id, rs.created_at,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.full_name')) AS full_name,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.emp_code')) AS emp_code,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.employee.branch_name')) AS branch_name,
                       JSON_UNQUOTE(JSON_EXTRACT(rs.snapshot_json, '$.created_by.username')) AS created_by
                FROM report_snapshots rs" . $scopeJoin . $where
            . ' ORDER BY rs.id DESC LIMIT :limit OFFSET :offset';
        $db->query($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $db->bind(':limit', $perPage);
        $db->bind(':offset', $offset);

        return [$db->fetchAll(), $totalCount];
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
