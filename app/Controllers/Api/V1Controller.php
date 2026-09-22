<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\ApiKey;

class V1Controller extends Controller
{
    private ?object $apiKey = null;

    /**
     * Authenticate request via X-API-Key header
     */
    private function authenticate(): void
    {
        $key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
        if (empty($key)) {
            $this->jsonResponse(['error' => 'مفتاح API مطلوب (X-API-Key header)'], 401);
        }
        $apiKeyModel = new ApiKey();
        $this->apiKey = $apiKeyModel->findByKey($key);
        if (!$this->apiKey) {
            $this->jsonResponse(['error' => 'مفتاح API غير صالح أو منتهي الصلاحية'], 401);
        }
        $apiKeyModel->touch($this->apiKey->id);
    }

    /**
     * Check if the API key has a specific permission
     */
    private function authorize(string $permission): void
    {
        $apiKeyModel = new ApiKey();
        if (!$apiKeyModel->hasPermission($this->apiKey, $permission)) {
            $this->jsonResponse(['error' => 'لا تملك صلاحية الوصول إلى هذا المورد'], 403);
        }
    }

    /**
     * Send JSON response
     */
    protected function jsonResponse(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // ======================================================================
    // Employees Endpoints
    // ======================================================================

    /**
     * GET /api/v1/employees
     */
    public function listEmployees(): void
    {
        $this->authenticate();
        $this->authorize('employees.read');

        $employeeModel = new Employee();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        $departmentId = $_GET['department_id'] ?? '';
        $active = $_GET['active'] ?? '';

        if ($search) {
            $employees = $employeeModel->search($search, $limit, $offset);
            $total = count($employeeModel->search($search, 1000000, 0));
        } elseif ($departmentId) {
            $employees = $employeeModel->findByDepartment($departmentId);
            $total = count($employees);
        } else {
            $all = $employeeModel->getAll();
            $total = count($all);
            $employees = array_slice($all, $offset, $limit);
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $employees,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => max(1, (int)ceil($total / $limit)),
            ],
        ]);
    }

    /**
     * GET /api/v1/employees/:id
     */
    public function showEmployee(int $id): void
    {
        $this->authenticate();
        $this->authorize('employees.read');

        $employeeModel = new Employee();
        $employee = $employeeModel->findById($id);

        if (!$employee) {
            $this->jsonResponse(['success' => false, 'error' => 'الموظف غير موجود'], 404);
        }

        $this->jsonResponse(['success' => true, 'data' => $employee]);
    }

    // ======================================================================
    // Evaluations Endpoints
    // ======================================================================

    /**
     * GET /api/v1/evaluations
     */
    public function listEvaluations(): void
    {
        $this->authenticate();
        $this->authorize('evaluations.read');

        $evalModel = new Evaluation();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;
        $employeeId = $_GET['employee_id'] ?? '';
        $status = $_GET['status'] ?? '';
        $fromDate = $_GET['from'] ?? '';
        $toDate = $_GET['to'] ?? '';

        $all = $evalModel->getAllForApi($employeeId, $status, $fromDate, $toDate);
        $total = count($all);
        $evaluations = array_slice($all, $offset, $limit);

        $this->jsonResponse([
            'success' => true,
            'data' => $evaluations,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => max(1, (int)ceil($total / $limit)),
            ],
        ]);
    }

    /**
     * GET /api/v1/evaluations/:id
     */
    public function showEvaluation(int $id): void
    {
        $this->authenticate();
        $this->authorize('evaluations.read');

        $evalModel = new Evaluation();
        $evaluation = $evalModel->getFullEvaluationData($id);

        if (!$evaluation) {
            $this->jsonResponse(['success' => false, 'error' => 'التقييم غير موجود'], 404);
        }

        $this->jsonResponse(['success' => true, 'data' => $evaluation]);
    }

    // ======================================================================
    // Health Check
    // ======================================================================

    /**
     * GET /api/v1/health
     */
    public function health(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'version' => '1.0.0',
            'app' => 'Employee Evaluation System',
            'time' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
