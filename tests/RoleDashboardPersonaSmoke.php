<?php
declare(strict_types=1);

/* Verifies role-persona routing for the dashboard against the isolated test database. */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'employee_evaluation_excel_test');
define('DB_CHARSET', 'utf8mb4');
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\RoleDashboardService;

function expectPersona(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }

$pdo = new PDO('mysql:host=localhost;dbname=employee_evaluation_excel_test;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$employeeId = (int)$pdo->query('SELECT id FROM employees ORDER BY id DESC LIMIT 1')->fetchColumn();
expectPersona($employeeId > 0, 'لا توجد هوية موظف لاختبار شخصيات اللوحة.');
$service = new RoleDashboardService();
$cases = [
    ['admin', 0, [], ['Admin']],
    ['hr', $employeeId, ['reports.view.all', 'employees.view'], []],
    ['manager', $employeeId, ['reports.view.team'], []],
    ['evaluator', $employeeId, ['evaluations.fill'], []],
    ['self', $employeeId, [], []],
];
foreach ($cases as [$expected, $scopeEmployee, $permissions, $roles]) {
    $dashboard = $service->forUser(2, $scopeEmployee, $permissions, $roles, 'اختبار اللوحة');
    expectPersona($dashboard['persona'] === $expected, 'لم تُحدد شخصية ' . $expected . ' بصورة صحيحة.');
    expectPersona(count($dashboard['trend']['labels']) === 6, 'لا تحتوي سلسلة الاتجاه على ستة أشهر لشخصية ' . $expected . '.');
    expectPersona(count($dashboard['distribution']['values']) === 4, 'لا يحتوي توزيع النتائج على الفئات المطلوبة لشخصية ' . $expected . '.');
}
echo "PASS: administrator, HR, manager, evaluator, and limited-user dashboard personas.\n";
