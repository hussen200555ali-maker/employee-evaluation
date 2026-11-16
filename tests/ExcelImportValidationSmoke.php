<?php
declare(strict_types=1);

/* CD-043 P3: validation/matching/scope unit smoke. */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'employee_evaluation_excel_test');
define('DB_CHARSET', 'utf8mb4');

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\Excel\EmployeeMatcher;
use App\Services\Excel\ProgramMatcher;
use App\Services\Excel\ExcelScope;
use App\Services\Excel\ExcelImportValidator;

function assertTrue(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }

$passCount = 0;
$failures = [];

// 1. normalizeName
try {
    assertTrue(EmployeeMatcher::normalizeName('  أحمَد  العتيبي  ') === 'احمد العتيبي', 'norm 1');
    assertTrue(EmployeeMatcher::normalizeName('فاطمة') === 'فاطمه', 'norm 2 (ة→ه)');
    assertTrue(EmployeeMatcher::normalizeName('مريمى') === 'مريمي', 'norm 3 (ى→ي)');
    assertTrue(EmployeeMatcher::namesEqual('أحمد العتيبي', 'احمد  العتيبي'), 'namesEqual 1');
    assertTrue(!EmployeeMatcher::namesEqual('أحمد', 'خالد'), 'namesEqual 2');
    echo "  [PASS] EmployeeMatcher normalization\n";
    $passCount++;
} catch (\Throwable $e) { echo "  [FAIL] EmployeeMatcher: " . $e->getMessage() . "\n"; $failures[] = $e->getMessage(); }

// 2. ProgramMatcher
try {
    $k1 = ProgramMatcher::matchKey('course', 'دورة', 'فرع', 'قسم', '2026-01-01', '2026-12-31');
    $k2 = ProgramMatcher::matchKey('course', 'دورة', 'فرع', 'قسم', '2026-01-01', '2026-12-31');
    assertTrue($k1 === $k2, 'matchKey equality');
    assertTrue(ProgramMatcher::normalizeDate('2026-01-01') === '2026-01-01', 'normalizeDate text');
    // Excel serial for 2026-01-01 = 46023
    assertTrue(ProgramMatcher::normalizeDate('46023') === '2026-01-01', 'normalizeDate serial');
    echo "  [PASS] ProgramMatcher\n";
    $passCount++;
} catch (\Throwable $e) { echo "  [FAIL] ProgramMatcher: " . $e->getMessage() . "\n"; $failures[] = $e->getMessage(); }

// 3. ExcelScope
try {
    assertTrue(ExcelScope::departmentBelongsToBranch(1, 1) === null, 'scope ok');
    assertTrue(ExcelScope::departmentBelongsToBranch(2, 1) !== null, 'scope mismatch');
    $errs = ExcelScope::validateBranchDepartment('فرع غير موجود', 'قسم غير موجود', ['فرع' => 1], ['قسم' => 2]);
    assertTrue(count($errs) === 2, 'scope errors count');
    echo "  [PASS] ExcelScope\n";
    $passCount++;
} catch (\Throwable $e) { echo "  [FAIL] ExcelScope: " . $e->getMessage() . "\n"; $failures[] = $e->getMessage(); }

// 4. ExcelImportValidator proxy (smoke: instantiate)
try {
    $v = new ExcelImportValidator();
    assertTrue($v->service() !== null, 'validator service');
    echo "  [PASS] ExcelImportValidator instantiate\n";
    $passCount++;
} catch (\Throwable $e) { echo "  [FAIL] ExcelImportValidator: " . $e->getMessage() . "\n"; $failures[] = $e->getMessage(); }

echo "\n";
echo "══════════════════════════════════════════\n";
echo "  RESULTS: $passCount / 4 PASSED\n";
echo "══════════════════════════════════════════\n";
if (count($failures) === 0) { echo "  ALL P3 UNIT CHECKS PASSED.\n"; }
else { foreach ($failures as $f) { echo "    - $f\n"; } exit(1); }
