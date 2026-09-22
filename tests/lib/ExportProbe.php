<?php
/**
 * مسبار التصدير — ينفّذ ExportService ببيانات ثابتة ويكتب المخرجات إلى stdout
 * بحيث يلتقطها الاختبار عبر إعادة توجيه stdout إلى ملف.
 *
 * الاستخدام:
 *   php tests/lib/ExportProbe.php <evaluations|employees|csv>
 */

if (php_sapi_name() !== 'cli') {
    exit(1);
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';

$type = $argv[1] ?? '';

$evaluations = [
    (object) [
        'final_score'    => 85.5,
        'status'         => 'completed',
        'employee_name'  => 'علي أحمد',
        'employee_code'  => 'EMP001',
        'department_name'=> 'تقنية المعلومات',
        'branch_name'    => 'الرياض',
        'template_name'  => 'تقييم سنوي شامل',
        'evaluation_date'=> '2026-01-15',
    ],
];

$employees = [
    (object) [
        'full_name'       => 'علي أحمد',
        'emp_code'        => 'EMP001',
        'personal_email'  => 'ali@example.com',
        'phone'           => '0550000000',
        'department_name' => 'تقنية المعلومات',
        'branch_name'     => 'الرياض',
        'position_title'  => 'مهندس برمجيات',
        'is_active'       => 1,
    ],
];

switch ($type) {
    case 'evaluations':
        \App\Services\ExportService::exportEvaluations($evaluations);
        break;
    case 'employees':
        \App\Services\ExportService::exportEmployees($employees);
        break;
    case 'csv':
        \App\Services\ExportService::exportCsv($evaluations, ['employee_name', 'final_score'], 'test.csv');
        break;
    default:
        fwrite(STDERR, "نوع تصدير غير معروف: $type\n");
        exit(1);
}
