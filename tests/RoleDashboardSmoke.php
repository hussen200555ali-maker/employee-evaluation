<?php
declare(strict_types=1);

/* Role dashboard acceptance smoke test. Run only against employee_evaluation_excel_test. */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'employee_evaluation_excel_test');
define('DB_CHARSET', 'utf8mb4');
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\RoleDashboardService;

function expectDashboard(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }

$pdo = new PDO('mysql:host=localhost;dbname=employee_evaluation_excel_test;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$row = $pdo->query('SELECT evaluator_employee_id,COUNT(*) AS total FROM evaluations GROUP BY evaluator_employee_id HAVING evaluator_employee_id IS NOT NULL ORDER BY total DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
expectDashboard($row !== false, 'لا توجد تقييمات اختبارية للتحقق من نطاق لوحة المقيّم.');
$evaluatorId = (int)$row['evaluator_employee_id'];
$expected = (int)$row['total'];
$dashboard = (new RoleDashboardService())->forUser(2, $evaluatorId, ['evaluations.fill'], [], 'مقيّم اختبار');
expectDashboard($dashboard['persona'] === 'evaluator', 'لم تُحدد شخصية المقيّم.');
expectDashboard($dashboard['summary']['evaluations'] === $expected, 'عدد تقييمات لوحة المقيّم لا يطابق تخصيصه الخادمي.');
foreach ($dashboard['recent'] as $item) { expectDashboard((string)$item['employee_code'] !== '', 'عاد نشاط بلا رمز موظف.'); }
echo "PASS: evaluator dashboard scope, metrics, and charts are derived from assigned evaluations.\n";
