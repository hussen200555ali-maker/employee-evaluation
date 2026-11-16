<?php
declare(strict_types=1);

/* CD-043 P2: Excel workflow smoke test — 4 target types + descriptive course.
 * Run against the isolated employee_evaluation_excel_test database.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'employee_evaluation_excel_test');
define('DB_CHARSET', 'utf8mb4');

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\EvaluationExcelService;
use App\Services\Excel\ExcelTemplateBuilder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function assertTrue(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }

$pdo = new PDO('mysql:host=localhost;dbname=employee_evaluation_excel_test;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$hash = password_hash('test1234', PASSWORD_DEFAULT);
$pdo->prepare("INSERT IGNORE INTO users (id, username, email, password_hash, status) VALUES (2, 'testuser', 'test@test.com', :hash, 'active')")->execute(['hash' => $hash]);
$userId = 2;

// evaluator employee
$token = (string) time();
$pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')->execute(['code' => 'EVAL-' . $token, 'name' => 'مقيّم اختبار Excel']);
$evaluatorEmployeeId = (int) $pdo->lastInsertId();

$service = new EvaluationExcelService();
$builder = new ExcelTemplateBuilder();
$directory = __DIR__ . '/tmp-excel';
if (!is_dir($directory)) { mkdir($directory, 0775, true); }

$passCount = 0;
$failures = [];

function colLetterFor(array $layout, string $key): string
{
    foreach ($layout['columns'] as $c) {
        if (($c['key'] ?? '') === $key) { return $c['letter']; }
    }
    throw new RuntimeException('Column not found: ' . $key);
}

function sectionColumnLetter(array $layout, int $sectionId, string $kind = 'section'): string
{
    foreach ($layout['columns'] as $c) {
        if (($c['section_id'] ?? null) === $sectionId && $c['kind'] === $kind) { return $c['letter']; }
    }
    throw new RuntimeException('Section column not found: ' . $sectionId);
}

function makeTemplate(PDO $pdo, string $name, string $type, string $mode = 'numeric'): int
{
    $pdo->prepare('INSERT INTO form_templates (template_name, target_type, evaluation_mode, is_active, version) VALUES (:name, :type, :mode, 1, 1)')
        ->execute(['name' => $name, 'type' => $type, 'mode' => $mode]);
    return (int) $pdo->lastInsertId();
}

function addSection(PDO $pdo, int $tplId, string $sectionName, string $evalType, array $options = [], int $min = 0, int $max = 100): int
{
    $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index) VALUES (:tid, "معيار", 100, 1)')
        ->execute(['tid' => $tplId]);
    $critId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, evaluation_options, min_value, max_value, section_weight, order_index, allow_notes) VALUES (:cid, :sn, :et, :opts, :mn, :mx, 100, 1, 0)')
        ->execute(['cid' => $critId, 'sn' => $sectionName, 'et' => $evalType, 'opts' => json_encode($options, JSON_UNESCAPED_UNICODE), 'mn' => $min, 'mx' => $max]);
    return (int) $pdo->lastInsertId();
}

/* ═══════════════════════════════════════════════════════════════════════
   TEST 1 — EMPLOYEE (فردي) عددی
   ═══════════════════════════════════════════════════════════════════════ */
try {
    $t = $token . '_emp';
    $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')
        ->execute(['code' => 'EMP-' . $t, 'name' => 'موظف اختبار']);
    $empId = (int) $pdo->lastInsertId();

    $tplId = makeTemplate($pdo, 'قالب موظف ' . $t, 'employee', 'numeric');
    $secId = addSection($pdo, $tplId, 'بند', 'percentage');

    $layout = $builder->layout($tplId);
    $path = $directory . '/emp-' . $t . '.xlsx';
    $wb = $service->buildTemplate($tplId, $evaluatorEmployeeId);
    $ws = $wb->getSheetByName('التقييمات');
    $r = 5;
    $ws->setCellValue(colLetterFor($layout, 'employee_name') . $r, 'موظف اختبار');
    $ws->setCellValue(colLetterFor($layout, 'employee_code') . $r, 'EMP-' . $t);
    $ws->setCellValue(sectionColumnLetter($layout, $secId, 'section') . $r, '88');
    $ws->setCellValue(colLetterFor($layout, 'evaluator_name') . $r, 'مقيّم اختبار Excel');
    $ws->setCellValue(colLetterFor($layout, 'evaluation_date') . $r, date('Y-m-d'));
    (new Xlsx($wb))->save($path);

    $preview = $service->previewWorkbookPath($path, 'emp.xlsx', filesize($path), $userId, $evaluatorEmployeeId);
    assertTrue($preview['valid'] === 1 && $preview['invalid'] === 0, 'T1: معاينة فاشلة — ' . json_encode($preview['rows'][0]['errors'] ?? []));

    $commit = $service->commitBatch($preview['batch_id'], $userId, $evaluatorEmployeeId);
    assertTrue($commit['committed_rows'] === 1 && $commit['committed_evaluations'] === 1, 'T1: التزام فاشل');

    $eval = $pdo->query('SELECT id FROM evaluations WHERE template_id=' . $tplId . ' AND employee_code="EMP-' . $t . '" ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
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
   TEST 2 — EMPLOYEE_IN_COURSE (numeric, program header)
   ═══════════════════════════════════════════════════════════════════════ */
try {
    $t = $token . '_eic';
    $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')
        ->execute(['code' => 'EMP-' . $t, 'name' => 'موظف اختبار دورة']);
    $empId = (int) $pdo->lastInsertId();

    $tplId = makeTemplate($pdo, 'قالب موظف في دورة ' . $t, 'employee_in_course', 'numeric');
    $secId = addSection($pdo, $tplId, 'بند', 'percentage');

    $layout = $builder->layout($tplId);
    $path = $directory . '/eic-' . $t . '.xlsx';
    $wb = $service->buildTemplate($tplId, $evaluatorEmployeeId);
    $ws = $wb->getSheetByName('التقييمات');
    $r = 5;
    $ws->setCellValue(colLetterFor($layout, 'program_title') . $r, 'دورة اختبار سياق ' . $t);
    $ws->setCellValue(colLetterFor($layout, 'program_branch') . $r, 'الفرع الرئيسي - الرياض');
    $ws->setCellValue(colLetterFor($layout, 'program_department') . $r, 'الإدارة العليا');
    $ws->setCellValue(colLetterFor($layout, 'program_start') . $r, '2026-01-01');
    $ws->setCellValue(colLetterFor($layout, 'program_end') . $r, '2026-12-31');
    $ws->setCellValue(colLetterFor($layout, 'program_responsible') . $r, 'مسؤول اختبار');
    $ws->setCellValue(colLetterFor($layout, 'employee_name') . $r, 'موظف اختبار دورة');
    $ws->setCellValue(colLetterFor($layout, 'employee_code') . $r, 'EMP-' . $t);
    $ws->setCellValue(sectionColumnLetter($layout, $secId, 'section') . $r, '92');
    $ws->setCellValue(colLetterFor($layout, 'evaluator_name') . $r, 'مقيّم اختبار Excel');
    $ws->setCellValue(colLetterFor($layout, 'evaluation_date') . $r, date('Y-m-d'));
    (new Xlsx($wb))->save($path);

    $preview = $service->previewWorkbookPath($path, 'eic.xlsx', filesize($path), $userId, $evaluatorEmployeeId);
    assertTrue($preview['valid'] === 1 && $preview['invalid'] === 0, 'T2: معاينة فاشلة — ' . json_encode($preview['rows'][0]['errors'] ?? []));

    $commit = $service->commitBatch($preview['batch_id'], $userId, $evaluatorEmployeeId);
    assertTrue($commit['committed_rows'] === 1 && $commit['committed_evaluations'] === 1, 'T2: التزام فاشل');

    $eval = $pdo->query('SELECT id, context_type, context_id, final_score FROM evaluations WHERE template_id=' . $tplId . ' AND evaluated_employee_id=' . $empId . ' ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    assertTrue($eval !== false, 'T2: لم يُنشأ تقييم');
    assertTrue($eval['context_type'] === 'course', 'T2: context_type يجب أن يكون course');
    assertTrue((int) $eval['context_id'] > 0, 'T2: context_id خاطئ');
    assertTrue((float) $eval['final_score'] === 92.0, 'T2: final_score خاطئ');

    $prog = $pdo->query('SELECT id, type, title, executing_branch_id, executing_department_id FROM training_programs WHERE id=' . (int)$eval['context_id'])->fetch(PDO::FETCH_ASSOC);
    assertTrue($prog !== false && $prog['type'] === 'course', 'T2: program type خاطئ');
    assertTrue(trim((string)$prog['title']) === 'دورة اختبار سياق ' . $t, 'T2: program title خاطئ');

    echo "  [PASS] TEST 2 — employee_in_course (numeric)\n";
    $passCount++;
} catch (\Throwable $e) {
    echo "  [FAIL] TEST 2 — employee_in_course: " . $e->getMessage() . "\n";
    $failures[] = 'employee_in_course: ' . $e->getMessage();
}

/* ═══════════════════════════════════════════════════════════════════════
   TEST 3 — EMPLOYEE_IN_WORKSHOP (numeric, program header)
   ═══════════════════════════════════════════════════════════════════════ */
try {
    $t = $token . '_eiw';
    $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')
        ->execute(['code' => 'EMP-' . $t, 'name' => 'موظف اختبار ورشة']);
    $empId = (int) $pdo->lastInsertId();

    $tplId = makeTemplate($pdo, 'قالب موظف في ورشة ' . $t, 'employee_in_workshop', 'numeric');
    $secId = addSection($pdo, $tplId, 'بند', 'percentage');

    $layout = $builder->layout($tplId);
    $path = $directory . '/eiw-' . $t . '.xlsx';
    $wb = $service->buildTemplate($tplId, $evaluatorEmployeeId);
    $ws = $wb->getSheetByName('التقييمات');
    $r = 5;
    $ws->setCellValue(colLetterFor($layout, 'program_title') . $r, 'ورشة اختبار سياق ' . $t);
    $ws->setCellValue(colLetterFor($layout, 'program_branch') . $r, 'الفرع الرئيسي - الرياض');
    $ws->setCellValue(colLetterFor($layout, 'program_department') . $r, 'الإدارة العليا');
    $ws->setCellValue(colLetterFor($layout, 'program_start') . $r, '2026-02-01');
    $ws->setCellValue(colLetterFor($layout, 'program_end') . $r, '2026-03-01');
    $ws->setCellValue(colLetterFor($layout, 'program_responsible') . $r, 'مسؤول ورشة');
    $ws->setCellValue(colLetterFor($layout, 'employee_name') . $r, 'موظف اختبار ورشة');
    $ws->setCellValue(colLetterFor($layout, 'employee_code') . $r, 'EMP-' . $t);
    $ws->setCellValue(sectionColumnLetter($layout, $secId, 'section') . $r, '77');
    $ws->setCellValue(colLetterFor($layout, 'evaluator_name') . $r, 'مقيّم اختبار Excel');
    $ws->setCellValue(colLetterFor($layout, 'evaluation_date') . $r, date('Y-m-d'));
    (new Xlsx($wb))->save($path);

    $preview = $service->previewWorkbookPath($path, 'eiw.xlsx', filesize($path), $userId, $evaluatorEmployeeId);
    assertTrue($preview['valid'] === 1 && $preview['invalid'] === 0, 'T3: معاينة فاشلة — ' . json_encode($preview['rows'][0]['errors'] ?? []));

    $commit = $service->commitBatch($preview['batch_id'], $userId, $evaluatorEmployeeId);
    assertTrue($commit['committed_rows'] === 1 && $commit['committed_evaluations'] === 1, 'T3: التزام فاشل');

    $eval = $pdo->query('SELECT id, context_type, context_id, final_score FROM evaluations WHERE template_id=' . $tplId . ' AND evaluated_employee_id=' . $empId . ' ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    assertTrue($eval !== false, 'T3: لم يُنشأ تقييم');
    assertTrue($eval['context_type'] === 'workshop', 'T3: context_type يجب أن يكون workshop');
    assertTrue((int) $eval['context_id'] > 0, 'T3: context_id خاطئ');
    assertTrue((float) $eval['final_score'] === 77.0, 'T3: final_score خاطئ');

    echo "  [PASS] TEST 3 — employee_in_workshop (numeric)\n";
    $passCount++;
} catch (\Throwable $e) {
    echo "  [FAIL] TEST 3 — employee_in_workshop: " . $e->getMessage() . "\n";
    $failures[] = 'employee_in_workshop: ' . $e->getMessage();
}

/* ═══════════════════════════════════════════════════════════════════════
   TEST 4 — SKILL (مهاري)
   ═══════════════════════════════════════════════════════════════════════ */
try {
    $t = $token . '_skl';
    $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')
        ->execute(['code' => 'EMP-' . $t, 'name' => 'موظف اختبار مهارة']);

    $tplId = makeTemplate($pdo, 'قالب مهارة ' . $t, 'skill', 'numeric');
    $secId = addSection($pdo, $tplId, 'بند', 'percentage');

    $layout = $builder->layout($tplId);
    $path = $directory . '/skl-' . $t . '.xlsx';
    $wb = $service->buildTemplate($tplId, $evaluatorEmployeeId);
    $ws = $wb->getSheetByName('التقييمات');
    $r = 5;
    $ws->setCellValue(colLetterFor($layout, 'employee_name') . $r, 'موظف اختبار مهارة');
    $ws->setCellValue(colLetterFor($layout, 'employee_code') . $r, 'EMP-' . $t);
    $ws->setCellValue(sectionColumnLetter($layout, $secId, 'section') . $r, '65');
    $ws->setCellValue(colLetterFor($layout, 'evaluator_name') . $r, 'مقيّم اختبار Excel');
    $ws->setCellValue(colLetterFor($layout, 'evaluation_date') . $r, date('Y-m-d'));
    (new Xlsx($wb))->save($path);

    $preview = $service->previewWorkbookPath($path, 'skl.xlsx', filesize($path), $userId, $evaluatorEmployeeId);
    assertTrue($preview['valid'] === 1 && $preview['invalid'] === 0, 'T4: معاينة فاشلة — ' . json_encode($preview['rows'][0]['errors'] ?? []));

    $commit = $service->commitBatch($preview['batch_id'], $userId, $evaluatorEmployeeId);
    assertTrue($commit['committed_rows'] === 1 && $commit['committed_evaluations'] === 1, 'T4: التزام فاشل');

    echo "  [PASS] TEST 4 — skill\n";
    $passCount++;
} catch (\Throwable $e) {
    echo "  [FAIL] TEST 4 — skill: " . $e->getMessage() . "\n";
    $failures[] = 'skill: ' . $e->getMessage();
}

/* ═══════════════════════════════════════════════════════════════════════
   TEST 5 — EMPLOYEE_IN_COURSE (descriptive, overall grade guarantee 1)
   ═══════════════════════════════════════════════════════════════════════ */
try {
    $t = $token . '_desc';
    $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')
        ->execute(['code' => 'EMP-' . $t, 'name' => 'موظف اختبار وصفي']);
    $empId = (int) $pdo->lastInsertId();

    $tplId = makeTemplate($pdo, 'قالب وصفي ' . $t, 'employee_in_course', 'descriptive');
    $secId = addSection($pdo, $tplId, 'بند', 'percentage');
    // overall grade section
    $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index) VALUES (:tid, "تقييم الاستفادة", 0, 2)')
        ->execute(['tid' => $tplId]);
    $critId2 = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, evaluation_options, min_value, max_value, section_weight, order_index, allow_notes) VALUES (:cid, "التقدير العام", "overall_grade", :opts, 0, 100, 0, 1, 0)')
        ->execute(['cid' => $critId2, 'opts' => json_encode(['scale_name' => 'السلم الخماسي'], JSON_UNESCAPED_UNICODE)]);

    $layout = $builder->layout($tplId);
    assertTrue($layout['has_overall_grade'] === true, 'T5: has_overall_grade should be true');
    $path = $directory . '/desc-' . $t . '.xlsx';
    $wb = $service->buildTemplate($tplId, $evaluatorEmployeeId);
    $ws = $wb->getSheetByName('التقييمات');
    $r = 5;
    $ws->setCellValue(colLetterFor($layout, 'program_title') . $r, 'دورة وصفية ' . $t);
    $ws->setCellValue(colLetterFor($layout, 'program_branch') . $r, 'الفرع الرئيسي - الرياض');
    $ws->setCellValue(colLetterFor($layout, 'program_department') . $r, 'الإدارة العليا');
    $ws->setCellValue(colLetterFor($layout, 'program_start') . $r, '2026-01-01');
    $ws->setCellValue(colLetterFor($layout, 'program_end') . $r, '2026-12-31');
    $ws->setCellValue(colLetterFor($layout, 'program_responsible') . $r, 'مسؤول وصفي');
    $ws->setCellValue(colLetterFor($layout, 'employee_name') . $r, 'موظف اختبار وصفي');
    $ws->setCellValue(colLetterFor($layout, 'employee_code') . $r, 'EMP-' . $t);
    $ws->setCellValue(sectionColumnLetter($layout, $secId, 'section') . $r, '90');
    $ws->setCellValue(colLetterFor($layout, 'overall_grade') . $r, 'ممتاز');
    $ws->setCellValue(colLetterFor($layout, 'evaluator_name') . $r, 'مقيّم اختبار Excel');
    $ws->setCellValue(colLetterFor($layout, 'evaluation_date') . $r, date('Y-m-d'));
    (new Xlsx($wb))->save($path);

    $preview = $service->previewWorkbookPath($path, 'desc.xlsx', filesize($path), $userId, $evaluatorEmployeeId);
    assertTrue($preview['valid'] === 1 && $preview['invalid'] === 0, 'T5: معاينة فاشلة — ' . json_encode($preview['rows'][0]['errors'] ?? []));

    $commit = $service->commitBatch($preview['batch_id'], $userId, $evaluatorEmployeeId);
    assertTrue($commit['committed_rows'] === 1 && $commit['committed_evaluations'] === 1, 'T5: التزام فاشل');

    $eval = $pdo->query('SELECT id, final_score, final_grade_label, final_grade_value FROM evaluations WHERE template_id=' . $tplId . ' AND evaluated_employee_id=' . $empId . ' ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    assertTrue($eval !== false, 'T5: لم يُنشأ تقييم');
    assertTrue((string)$eval['final_grade_label'] === 'ممتاز', 'T5: final_grade_label خاطئ: ' . ($eval['final_grade_label'] ?? 'null'));
    assertTrue((float)$eval['final_grade_value'] === 5.0, 'T5: final_grade_value خاطئ: ' . ($eval['final_grade_value'] ?? 'null'));

    echo "  [PASS] TEST 5 — employee_in_course (descriptive, overall grade)\n";
    $passCount++;
} catch (\Throwable $e) {
    echo "  [FAIL] TEST 5 — descriptive: " . $e->getMessage() . "\n";
    $failures[] = 'descriptive: ' . $e->getMessage();
}

echo "\n";
echo "══════════════════════════════════════════\n";
echo "  RESULTS: $passCount / 5 PASSED\n";
echo "══════════════════════════════════════════\n";
if ($passCount === 5) {
    echo "  ALL TESTS PASSED — CD-043 P2 workflow verified.\n";
} else {
    echo "  FAILURES:\n";
    foreach ($failures as $f) { echo "    - $f\n"; }
    exit(1);
}
