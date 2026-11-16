<?php
/**
 * CD-043: مشغّل الاختبارات على قاعدة الاختبار المعزولة.
 *
 * السبب: `config/database.php` يثبّت `DB_NAME` على قاعدة UAT. تشغيل
 * `tests/runner.php` مباشرةً يكتب في UAT (ومنها اختبارات تُدخل بيانات).
 * هذا الغلاف يثبّت الثوابت على `employee_evaluation_excel_test` قبل تحميل
 * bootstrap، دون تعديل أي إعداد إنتاجي.
 *
 * الاستخدام:
 *   php tests/run_isolated.php                # كل الاختبارات
 *   php tests/run_isolated.php CD042Duplicate # تصفية بالاسم
 *
 * شيفرة الخروج: 0 عند النجاح الكامل، 1 عند وجود فشل أو خطأ.
 */
declare(strict_types=1);

const CD043_TEST_DB = 'employee_evaluation_excel_test';

define('DB_HOST', getenv('APP_TEST_DB_HOST') ?: 'localhost');
define('DB_USER', getenv('APP_TEST_DB_USER') ?: 'root');
define('DB_PASS', getenv('APP_TEST_DB_PASS') !== false ? (string) getenv('APP_TEST_DB_PASS') : '');
define('DB_NAME', CD043_TEST_DB);
define('DB_CHARSET', 'utf8mb4');

// config/database.php يعرّف الثوابت نفسها بلا فحص `defined()`. نكتم تحذير
// "Constant already defined" فقط، مع إبقاء بقية التحذيرات ظاهرة.
set_error_handler(static function (int $errno, string $errstr): bool {
    return str_contains($errstr, 'already defined');
});

$argv = ['runner.php', $argv[1] ?? ''];
$_SERVER['argv'] = $argv;
$argc = count($argv);
$_SERVER['argc'] = $argc;

require __DIR__ . '/runner.php';
