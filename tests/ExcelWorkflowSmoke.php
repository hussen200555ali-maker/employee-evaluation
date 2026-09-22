<?php
declare(strict_types=1);

/* Excel acceptance smoke test — all 4 target types.
 * Run against the isolated employee_evaluation_excel_test database.
 * Tests: employee, course, unit (branch), employee_in_course
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'employee_evaluation_excel_test');
define('DB_CHARSET', 'utf8mb4');

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\EvaluationExcelService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function assertTrue(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }

$pdo = new PDO('mysql:host=localhost;dbname=employee_evaluation_excel_test;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

/* ── Common seed ────────────────────────────────────────────────────── */
$hash = password_hash('test1234', PASSWORD_DEFAULT);
$pdo->prepare("INSERT IGNORE INTO users (id, username, email, password_hash, status) VALUES (2, 'testuser', 'test@test.com', :hash, 'active')")->execute(['hash' => $hash]);
$userId = 2;

/* Branch + Department */
$pdo->exec("INSERT IGNORE INTO branches (id, name) VALUES (1, 'فرع اختبار عام')");
$pdo->exec("INSERT IGNORE INTO departments (id, name, branch_id) VALUES (1, 'قسم اختبار عام', 1)");

/* Evaluator employee */
$token = (string) time();
$pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')->execute(['code' => 'EVAL-' . $token, 'name' => 'مقيّم اختبار Excel']);
$evaluatorEmployeeId = (int) $pdo->lastInsertId();

$service = new EvaluationExcelService();
$directory = __DIR__ . '/tmp-excel';
if (!is_dir($directory)) { mkdir($directory, 0775, true); }

$passCount = 0;
$failures = [];

/* ═══════════════════════════════════════════════════════════════════════
   TEST 1 — EMPLOYEE (الموظف الفردي)
   ═══════════════════════════════════════════════════════════════════════ */
try {
    $t1 = $token . '_emp';

    $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')
        ->execute(['code' => 'EMP-' . $t1, 'name' => 'موظف اختبار']);
    $empId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO form_templates (template_name, target_type, is_active, version) VALUES (:name, "employee", 1, 1)')
        ->execute(['name' => 'قالب موظف ' . $t1]);
    $tplId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index) VALUES (:tid, "معيار", 100, 1)')
        ->execute(['tid' => $tplId]);
    $critId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, min_value, max_value, section_weight, order_index) VALUES (:cid, "بند", "percentage", 0, 100, 100, 1)')
        ->execute(['cid' => $critId]);
    $secId = (int) $pdo->lastInsertId();

    $path = $directory . '/emp-' . $t1 . '.xlsx';
    $wb = $service->buildTemplate($tplId, $evaluatorEmployeeId);
    $ws = $wb->getSheetByName('التقييمات');
    $ws->setCellValue('A6', 'EMP-' . $t1);
    $ws->setCellValue('B6', 'موظف اختبار');
    $ws->setCellValue('F6', '88');
    $ws->setCellValue('H6', date('Y-m-d'));
    $ws->setCellValue('I6', 'مقيّم اختبار');
    (new Xlsx($wb))->save($path);

    $preview = $service->previewWorkbookPath($path, 'emp.xlsx', filesize($path), $userId, $evaluatorEmployeeId);
    assertTrue($preview['valid'] === 1 && $preview['invalid'] === 0, 'T1: معاينة فاشلة');

    $commit = $service->commitBatch($preview['batch_id'], $userId, $evaluatorEmployeeId);
    assertTrue($commit['committed_rows'] === 1 && $commit['committed_evaluations'] === 1, 'T1: التزام فاشل');

    $eval = $pdo->query('SELECT id FROM evaluations WHERE template_id=' . $tplId . ' AND employee_code="EMP-' . $t1 . '" ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    assertTrue($eval !== false, 'T1: لم يُنشأ تقييم');

    $ans = $pdo->query('SELECT raw_value, normalized_score FROM evaluation_answers WHERE evaluation_id=' . $eval['id'] . ' AND section_id=' . $secId)->fetch(PDO::FETCH_ASSOC);
    assertTrue($ans !== false && (string) $ans['raw_value'] === '88' && (float) $ans['normalized_score'] === 88.0, 'T1: الدرجة غير صحيحة');

    echo "  [PASS] TEST 1 — employee\n";
    $passCount++;
} catch (\Throwable $e) {
    echo "  [FAIL] TEST 1 — employee: " . $e->getMessage() . "\n";
    $failures[] = 'employee: ' . $e->getMessage();
}

/* ═══════════════════════════════════════════════════════════════════════
   TEST 2 — COURSE (الدورة ككيان)
   ═══════════════════════════════════════════════════════════════════════ */
try {
    $t2 = $token . '_crs';

    $pdo->prepare('INSERT INTO course_catalog (title, created_by) VALUES (:title, :uid)')
        ->execute(['title' => 'دورة اختبار ' . $t2, 'uid' => $userId]);
    $catalogId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO training_courses (catalog_id, branch_id, start_date, end_date, status, created_by) VALUES (:cat, 1, "2026-01-01", "2026-12-31", "ongoing", :uid)')
        ->execute(['cat' => $catalogId, 'uid' => $userId]);
    $courseId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO form_templates (template_name, target_type, is_active, version) VALUES (:name, "course", 1, 1)')
        ->execute(['name' => 'قالب دورة ' . $t2]);
    $tplId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index) VALUES (:tid, "معيار", 100, 1)')
        ->execute(['tid' => $tplId]);
    $critId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, min_value, max_value, section_weight, order_index) VALUES (:cid, "بند", "percentage", 0, 100, 100, 1)')
        ->execute(['cid' => $critId]);
    $secId = (int) $pdo->lastInsertId();

    $path = $directory . '/crs-' . $t2 . '.xlsx';
    $wb = $service->buildTemplate($tplId, $evaluatorEmployeeId);
    $ws = $wb->getSheetByName('التقييمات');
    for ($r = 6; $r <= 105; $r++) { for ($c = 'A'; $c <= 'K'; $c++) { $ws->setCellValue($c . $r, null); } }
    $ws->setCellValue('A6', 'دورة اختبار ' . $t2);
    $ws->setCellValue('B6', 'فرع اختبار عام');
    $ws->setCellValue('C6', '');
    $ws->setCellValue('D6', '2026-01-01');
    $ws->setCellValue('E6', '2026-12-31');
    $ws->setCellValue('F6', '75');
    $ws->setCellValue('G6', '');
    $ws->setCellValue('H6', date('Y-m-d'));
    $ws->setCellValue('I6', 'مقيّم اختبار');
    $ws->setCellValue('J6', 'مشرف');
    (new Xlsx($wb))->save($path);

    $preview = $service->previewWorkbookPath($path, 'crs.xlsx', filesize($path), $userId, $evaluatorEmployeeId);
    assertTrue($preview['valid'] === 1 && $preview['invalid'] === 0, 'T2: معاينة فاشلة — errors: ' . json_encode($preview['rows'][0]['errors'] ?? []));

    $commit = $service->commitBatch($preview['batch_id'], $userId, $evaluatorEmployeeId);
    assertTrue($commit['committed_rows'] === 1 && $commit['committed_evaluations'] === 1, 'T2: التزام فاشل');

    $eval = $pdo->query('SELECT id, entity_type, entity_id, final_score FROM evaluations WHERE template_id=' . $tplId . ' AND entity_type="course" ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    assertTrue($eval !== false, 'T2: لم يُنشأ تقييم');
    assertTrue($eval['entity_type'] === 'course', 'T2: entity_type خاطئ: ' . ($eval['entity_type'] ?? 'null'));
    assertTrue((float) $eval['final_score'] === 75.0, 'T2: final_score خاطئ');

    $ans = $pdo->query('SELECT raw_value, normalized_score FROM evaluation_answers WHERE evaluation_id=' . $eval['id'] . ' AND section_id=' . $secId)->fetch(PDO::FETCH_ASSOC);
    assertTrue($ans !== false && (string) $ans['raw_value'] === '75' && (float) $ans['normalized_score'] === 75.0, 'T2: الدرجة غير صحيحة');

    echo "  [PASS] TEST 2 — course\n";
    $passCount++;
} catch (\Throwable $e) {
    echo "  [FAIL] TEST 2 — course: " . $e->getMessage() . "\n";
    $failures[] = 'course: ' . $e->getMessage();
}

/* ═══════════════════════════════════════════════════════════════════════
   TEST 3 — UNIT (الوحدة الدورية — فرع)
   ═══════════════════════════════════════════════════════════════════════ */
try {
    $t3 = $token . '_unit';

    $pdo->exec("INSERT IGNORE INTO branches (id, name) VALUES (2, 'فرع اختبار وحدة')");
    $branchId = 2;

    $pdo->prepare('INSERT INTO form_templates (template_name, target_type, is_active, version) VALUES (:name, "unit", 1, 1)')
        ->execute(['name' => 'قالب وحدة ' . $t3]);
    $tplId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index) VALUES (:tid, "معيار", 100, 1)')
        ->execute(['tid' => $tplId]);
    $critId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, min_value, max_value, section_weight, order_index) VALUES (:cid, "بند", "percentage", 0, 100, 100, 1)')
        ->execute(['cid' => $critId]);
    $secId = (int) $pdo->lastInsertId();

    $path = $directory . '/unit-' . $t3 . '.xlsx';
    $wb = $service->buildTemplate($tplId, $evaluatorEmployeeId);
    $ws = $wb->getSheetByName('التقييمات');
    $ws->setCellValue('A6', 'branch');
    $ws->setCellValue('B6', 'فرع اختبار وحدة');
    $ws->setCellValue('C6', '2026-01');
    $ws->setCellValue('F6', '60');
    $ws->setCellValue('H6', date('Y-m-d'));
    $ws->setCellValue('I6', 'مقيّم اختبار');
    (new Xlsx($wb))->save($path);

    $preview = $service->previewWorkbookPath($path, 'unit.xlsx', filesize($path), $userId, $evaluatorEmployeeId);
    assertTrue($preview['valid'] === 1 && $preview['invalid'] === 0, 'T3: معاينة فاشلة');

    $commit = $service->commitBatch($preview['batch_id'], $userId, $evaluatorEmployeeId);
    assertTrue($commit['committed_rows'] === 1 && $commit['committed_evaluations'] === 1, 'T3: التزام فاشل');

    $eval = $pdo->query('SELECT id, entity_type, status, final_score FROM evaluations WHERE template_id=' . $tplId . ' AND entity_id=' . $branchId . ' ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    assertTrue($eval !== false, 'T3: لم يُنشأ تقييم');
    assertTrue($eval['entity_type'] === 'branch', 'T3: entity_type خاطئ: ' . ($eval['entity_type'] ?? 'null'));
    assertTrue($eval['status'] === 'completed', 'T3: الحالة يجب أن تكون completed (استيراد): ' . ($eval['status'] ?? 'null'));
    assertTrue((float) $eval['final_score'] === 60.0, 'T3: final_score خắcئ');

    $ans = $pdo->query('SELECT raw_value, normalized_score FROM evaluation_answers WHERE evaluation_id=' . $eval['id'] . ' AND section_id=' . $secId)->fetch(PDO::FETCH_ASSOC);
    assertTrue($ans !== false && (string) $ans['raw_value'] === '60' && (float) $ans['normalized_score'] === 60.0, 'T3: الدرجة غير صحيحة');

    echo "  [PASS] TEST 3 — unit (branch)\n";
    $passCount++;
} catch (\Throwable $e) {
    echo "  [FAIL] TEST 3 — unit (branch): " . $e->getMessage() . "\n";
    $failures[] = 'unit: ' . $e->getMessage();
}

/* ═══════════════════════════════════════════════════════════════════════
   TEST 4 — EMPLOYEE_IN_COURSE (موظف داخل دورة)
   ═══════════════════════════════════════════════════════════════════════ */
try {
    $t4 = $token . '_eic';

    $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')
        ->execute(['code' => 'EMP-' . $t4, 'name' => 'موظف اختبار دورة']);
    $empId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO course_catalog (title, created_by) VALUES (:title, :uid)')
        ->execute(['title' => 'دورة اختبار سياق ' . $t4, 'uid' => $userId]);
    $catalogId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO training_courses (catalog_id, branch_id, start_date, end_date, status, created_by) VALUES (:cat, 1, "2026-01-01", "2026-12-31", "ongoing", :uid)')
        ->execute(['cat' => $catalogId, 'uid' => $userId]);
    $courseId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO form_templates (template_name, target_type, is_active, version) VALUES (:name, "employee_in_course", 1, 1)')
        ->execute(['name' => 'قالب موظف في دورة ' . $t4]);
    $tplId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index) VALUES (:tid, "معيار", 100, 1)')
        ->execute(['tid' => $tplId]);
    $critId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, min_value, max_value, section_weight, order_index) VALUES (:cid, "بند", "percentage", 0, 100, 100, 1)')
        ->execute(['cid' => $critId]);
    $secId = (int) $pdo->lastInsertId();

    $path = $directory . '/eic-' . $t4 . '.xlsx';
    $wb = $service->buildTemplate($tplId, $evaluatorEmployeeId);
    $ws = $wb->getSheetByName('التقييمات');
    $ws->setCellValue('A6', 'EMP-' . $t4);
    $ws->setCellValue('B6', 'موظف اختبار دورة');
    $ws->setCellValue('C6', (string) $courseId);
    $ws->setCellValue('F6', '92');
    $ws->setCellValue('H6', date('Y-m-d'));
    $ws->setCellValue('I6', 'مقيّم اختبار');
    (new Xlsx($wb))->save($path);

    $preview = $service->previewWorkbookPath($path, 'eic.xlsx', filesize($path), $userId, $evaluatorEmployeeId);
    assertTrue($preview['valid'] === 1 && $preview['invalid'] === 0, 'T4: معاينة فاشلة');

    $commit = $service->commitBatch($preview['batch_id'], $userId, $evaluatorEmployeeId);
    assertTrue($commit['committed_rows'] === 1 && $commit['committed_evaluations'] === 1, 'T4: التزام فاشل');

    $eval = $pdo->query('SELECT id, entity_type, context_type, context_id, final_score FROM evaluations WHERE template_id=' . $tplId . ' AND evaluated_employee_id=' . $empId . ' ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    assertTrue($eval !== false, 'T4: لم يُنشأ تقييم');
    assertTrue($eval['entity_type'] === 'employee', 'T4: entity_type يجب أن يكون employee: ' . ($eval['entity_type'] ?? 'null'));
    assertTrue($eval['context_type'] === 'course', 'T4: context_type يجب أن يكون course: ' . ($eval['context_type'] ?? 'null'));
    assertTrue((int) $eval['context_id'] === $courseId, 'T4: context_id خاطئ');
    assertTrue((float) $eval['final_score'] === 92.0, 'T4: final_score خاطئ');

    $ans = $pdo->query('SELECT raw_value, normalized_score FROM evaluation_answers WHERE evaluation_id=' . $eval['id'] . ' AND section_id=' . $secId)->fetch(PDO::FETCH_ASSOC);
    assertTrue($ans !== false && (string) $ans['raw_value'] === '92' && (float) $ans['normalized_score'] === 92.0, 'T4: الدرجة غير صحيحة');

    echo "  [PASS] TEST 4 — employee_in_course\n";
    $passCount++;
} catch (\Throwable $e) {
    echo "  [FAIL] TEST 4 — employee_in_course: " . $e->getMessage() . "\n";
    $failures[] = 'employee_in_course: ' . $e->getMessage();
}

/* ═══════════════════════════════════════════════════════════════════════
   SUMMARY
   ═══════════════════════════════════════════════════════════════════════ */
echo "\n";
echo "══════════════════════════════════════════\n";
echo "  RESULTS: $passCount / 4 PASSED\n";
echo "══════════════════════════════════════════\n";
if ($passCount === 4) {
    echo "  ALL TESTS PASSED — Phase 3.5 Import/Export verified.\n";
} else {
    echo "  FAILURES:\n";
    foreach ($failures as $f) {
        echo "    - $f\n";
    }
    exit(1);
}
