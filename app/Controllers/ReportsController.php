<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

final class ReportsController extends Controller
{
    public function create(): void
    {
        Auth::enforce('employees.profile');
        $search = trim((string)($_GET['q'] ?? ''));
        $results = [];

        if ($search !== '') {
            $db = Database::getInstance();
            $db->query(
                'SELECT e.id, e.emp_code, e.full_name, d.name AS department_name, b.name AS branch_name
                 FROM employees e
                 LEFT JOIN departments d ON d.id = e.department_id
                 LEFT JOIN branches b ON b.id = e.branch_id
                 WHERE e.is_active = 1
                   AND e.emp_code IS NOT NULL AND e.emp_code <> \'\'
                   AND (e.full_name LIKE :name_search OR e.emp_code LIKE :code_search)
                 ORDER BY e.full_name
                 LIMIT 50'
            );
            $db->bind(':name_search', '%' . $search . '%');
            $db->bind(':code_search', '%' . $search . '%');
            $results = $db->fetchAll();
        }

        $this->view('reports/create', [
            'pageTitle' => 'توليد تقرير موظف',
            'search' => $search,
            'results' => $results,
            'csrfToken' => Auth::generateCsrfToken(),
        ]);
    }

    public function archive(): void
    {
        Auth::enforce('employees.profile');
        $db = Database::getInstance();
        $db->query(
            "SELECT id, report_number, employee_id, created_at,
                    JSON_UNQUOTE(JSON_EXTRACT(snapshot_json, '$.employee.full_name')) AS full_name,
                    JSON_UNQUOTE(JSON_EXTRACT(snapshot_json, '$.employee.emp_code')) AS emp_code,
                    JSON_UNQUOTE(JSON_EXTRACT(snapshot_json, '$.created_by.username')) AS created_by
             FROM report_snapshots
             ORDER BY id DESC
             LIMIT 500"
        );

        $this->view('reports/archive', [
            'pageTitle' => 'سجل التقارير',
            'snapshots' => $db->fetchAll(),
        ]);
    }
}
