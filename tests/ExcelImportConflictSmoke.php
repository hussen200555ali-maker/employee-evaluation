<?php
declare(strict_types=1);

/* CD-042 — Excel import conflict smoke test.
 * Verifies that the import duplicate pre-check matches the DB unique key
 * uq_eval_once (employee + template + date + context) and fails gracefully:
 *   1) existing FINAL evaluation + different evaluator -> friendly Arabic error, full rollback
 *   2) existing DRAFT of ANOTHER evaluator            -> friendly Arabic error, draft untouched
 *   3) existing DRAFT of the SAME evaluator           -> updated in place (no duplicate)
 *
 * Run against the isolated employee_evaluation_excel_test database.
 *   C:\xampp\php\php.exe tests\ExcelImportConflictSmoke.php
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'employee_evaluation_excel_test');
define('DB_CHARSET', 'utf8mb4');

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\EvaluationExcelService;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

function makeTemplate(PDO $pdo, string $label): array
{
    $pdo->prepare('INSERT INTO form_templates (template_name, target_type, is_active, version) VALUES (:name, "employee", 1, 1)')
        ->execute(['name' => 'قالب تعارض ' . $label]);
    $tplId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO criteria (template_id, criterion_name, criterion_weight, order_index) VALUES (:tid, "معيار", 100, 1)')
        ->execute(['tid' => $tplId]);
    $critId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO criteria_sections (criterion_id, section_name, evaluation_type, min_value, max_value, section_weight, order_index) VALUES (:cid, "بند", "percentage", 0, 100, 100, 1)')
        ->execute(['cid' => $critId]);
    return [$tplId, (int) $pdo->lastInsertId()];
}

function writeEmployeeSheet(EvaluationExcelService $service, int $tplId, int $evaluatorEmployeeId, string $path, string $empCode, string $empName, string $date, string $evaluatorName, string $score): void
{
    $wb = $service->buildTemplate($tplId, $evaluatorEmployeeId);
    $ws = $wb->getSheetByName('التقييمات');
    for ($r = 6; $r <= 105; $r++) {
        for ($c = 'A'; $c <= 'K'; $c++) { $ws->setCellValue($c . $r, null); }
    }
    $ws->setCellValue('A6', $empCode);
    $ws->setCellValue('B6', $empName);
    $ws->setCellValue('F6', $score);
    $ws->setCellValue('H6', $date);
    $ws->setCellValue('I6', $evaluatorName);
    (new Xlsx($wb))->save($path);
}

$pdo = new PDO('mysql:host=localhost;dbname=' . DB_NAME . ';charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$service = new EvaluationExcelService();
$token = (string) time() . '_' . random_int(100, 999);
$directory = __DIR__ . '/tmp-excel';
if (!is_dir($directory)) { mkdir($directory, 0775, true); }

$pdo->prepare("INSERT IGNORE INTO users (id, username, email, password_hash, status) VALUES (2, 'testuser', 'test@test.com', :hash, 'active')")
    ->execute(['hash' => password_hash('test1234', PASSWORD_DEFAULT)]);
$userId = 2;
$pdo->exec("INSERT IGNORE INTO branches (id, name) VALUES (1, 'فرع اختبار عام')");

$evaluator1Name = 'مقيّم أول ' . $token;
$evaluator2Name = 'مقيّم ثاني ' . $token;
$pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')
    ->execute(['code' => 'EV1-' . $token, 'name' => $evaluator1Name]);
$evaluator1Id = (int) $pdo->lastInsertId();
$pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')
    ->execute(['code' => 'EV2-' . $token, 'name' => $evaluator2Name]);
$evaluator2Id = (int) $pdo->lastInsertId();

$passCount = 0;
$failures = [];

/* ═══════════════════════════════════════════════════════════════════════
   TEST 1 — FINAL evaluation exists + DIFFERENT evaluator
   ═══════════════════════════════════════════════════════════════════════ */
try {
    [$tplId] = makeTemplate($pdo, $token . '_final');
    $empCode = 'CONF-F-' . $token;
    $empName = 'موظف تعارض نهائي ' . $token;
    $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')
        ->execute(['code' => $empCode, 'name' => $empName]);
    $empId = (int) $pdo->lastInsertId();
    $date = '2026-01-05';

    $path = $directory . '/conf-final-1-' . $token . '.xlsx';
    writeEmployeeSheet($service, $tplId, $evaluator1Id, $path, $empCode, $empName, $date, $evaluator1Name, '80');
    $preview = $service->previewWorkbookPath($path, 'conf-final-1.xlsx', filesize($path), $userId, $evaluator1Id);
    assertTrue($preview['valid'] === 1 && $preview['invalid'] === 0, 'T1: معاينة أولى فاشلة — ' . json_encode($preview['rows'][0]['errors'] ?? []));
    $service->commitBatch($preview['batch_id'], $userId, $evaluator1Id);

    $evalRow = $pdo->query('SELECT id, status, final_score, evaluator_employee_id FROM evaluations WHERE evaluated_employee_id=' . $empId . ' AND template_id=' . $tplId . ' AND evaluation_date="' . $date . '"')->fetch(PDO::FETCH_ASSOC);
    assertTrue($evalRow !== false && $evalRow['status'] === 'completed', 'T1: التقييم الأول لم يُثبّت completed');
    $evalId = (int) $evalRow['id'];
    $answersBefore = (int) $pdo->query('SELECT COUNT(*) FROM evaluation_answers WHERE evaluation_id=' . $evalId)->fetchColumn();

    // Same employee/template/date, DIFFERENT evaluator -> must be rejected cleanly
    $path2 = $directory . '/conf-final-2-' . $token . '.xlsx';
    writeEmployeeSheet($service, $tplId, $evaluator2Id, $path2, $empCode, $empName, $date, $evaluator2Name, '95');
    $preview2 = $service->previewWorkbookPath($path2, 'conf-final-2.xlsx', filesize($path2), $userId, $evaluator2Id);
    assertTrue($preview2['valid'] === 1 && $preview2['invalid'] === 0, 'T1: معاينة ثانية فاشلة');

    $message = '';
    try {
        $service->commitBatch($preview2['batch_id'], $userId, $evaluator2Id);
        throw new RuntimeException('T1: الالتزام نجح رغم وجود تقييم نهائي — يجب أن يُرفض');
    } catch (RuntimeException $e) {
        $message = $e->getMessage();
    }
    assertTrue(mb_strpos($message, 'يوجد تقييم نهائي') !== false, 'T1: الرسالة ليست ودّية: ' . $message);
    assertTrue(mb_strpos($message, '#' . $evalId) !== false, 'T1: الرسالة لا تتضمن رقم التقييم #' . $evalId . ' — ' . $message);
    assertTrue(mb_strpos($message, '1062') === false && mb_strpos($message, 'Duplicate entry') === false, 'T1: تسرّب نص قاعدة بيانات خام: ' . $message);

    $after = $pdo->query('SELECT id, status, final_score, evaluator_employee_id FROM evaluations WHERE evaluated_employee_id=' . $empId . ' AND template_id=' . $tplId . ' AND evaluation_date="' . $date . '"')->fetchAll(PDO::FETCH_ASSOC);
    assertTrue(count($after) === 1 && (int) $after[0]['id'] === $evalId, 'T1: أُنشئ صف إضافي — يجب بقاء صف واحد');
    assertTrue($after[0]['status'] === 'completed' && (float) $after[0]['final_score'] === 80.0, 'T1: تغيّر التقييم الأصلي');
    assertTrue((int) $after[0]['evaluator_employee_id'] === $evaluator1Id, 'T1: تغيّر مقيّم التقييم الأصلي');
    $answersAfter = (int) $pdo->query('SELECT COUNT(*) FROM evaluation_answers WHERE evaluation_id=' . $evalId)->fetchColumn();
    assertTrue($answersAfter === $answersBefore, 'T1: تغيّرت إجابات التقييم الأصلي');

    $batchStatus = $pdo->query('SELECT status FROM evaluation_import_batches WHERE id=' . (int) $preview2['batch_id'])->fetchColumn();
    assertTrue($batchStatus === 'previewed', 'T1: الدفعة لم تُرجع للحالة previewed بعد التراجع — ' . var_export($batchStatus, true));

    echo "  [PASS] TEST 1 — final conflict (different evaluator) rejected cleanly\n";
    $passCount++;
} catch (\Throwable $e) {
    echo "  [FAIL] TEST 1 — " . $e->getMessage() . "\n";
    $failures[] = 'final: ' . $e->getMessage();
}

/* ═══════════════════════════════════════════════════════════════════════
   TEST 2 — DRAFT of ANOTHER evaluator -> reject, do not overwrite
   ═══════════════════════════════════════════════════════════════════════ */
try {
    [$tplId] = makeTemplate($pdo, $token . '_draft_other');
    $empCode = 'CONF-D-' . $token;
    $empName = 'موظف تعارض مسودة ' . $token;
    $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')
        ->execute(['code' => $empCode, 'name' => $empName]);
    $empId = (int) $pdo->lastInsertId();
    $date = '2026-01-06';

    $ins = $pdo->prepare('INSERT INTO evaluations (template_id, entity_type, entity_id, evaluated_employee_id, evaluator_employee_id, evaluator_name, evaluation_date, created_by_user_id, employee_name, employee_code, branch_name, department_name, position_name, notes, status) VALUES (:tid, "employee", :eid, :eid, :evid, :evname, :edate, :uid, :ename, :ecode, "", "", "", :notes, "draft")');
    $ins->execute(['tid' => $tplId, 'eid' => $empId, 'evid' => $evaluator1Id, 'evname' => $evaluator1Name, 'edate' => $date, 'uid' => $userId, 'ename' => $empName, 'ecode' => $empCode, 'notes' => 'مسودة المقيّم الأول']);
    $draftId = (int) $pdo->lastInsertId();

    $path = $directory . '/conf-draft-other-' . $token . '.xlsx';
    writeEmployeeSheet($service, $tplId, $evaluator2Id, $path, $empCode, $empName, $date, $evaluator2Name, '70');
    $preview = $service->previewWorkbookPath($path, 'conf-draft-other.xlsx', filesize($path), $userId, $evaluator2Id);
    assertTrue($preview['valid'] === 1 && $preview['invalid'] === 0, 'T2: معاينة فاشلة');

    $message = '';
    try {
        $service->commitBatch($preview['batch_id'], $userId, $evaluator2Id);
        throw new RuntimeException('T2: الالتزام نجح رغم وجود مسودة لمقيّم آخر');
    } catch (RuntimeException $e) {
        $message = $e->getMessage();
    }
    assertTrue(mb_strpos($message, 'لمقيّم آخر') !== false, 'T2: الرسالة غير واضحة: ' . $message);
    assertTrue(mb_strpos($message, '#' . $draftId) !== false, 'T2: الرسالة لا تتضمن رقم المسودة #' . $draftId . ' — ' . $message);
    assertTrue(mb_strpos($message, '1062') === false && mb_strpos($message, 'Duplicate entry') === false, 'T2: تسرّب نص قاعدة بيانات خام: ' . $message);

    $draft = $pdo->query('SELECT status, evaluator_employee_id, evaluator_name, notes FROM evaluations WHERE id=' . $draftId)->fetch(PDO::FETCH_ASSOC);
    assertTrue($draft !== false && $draft['status'] === 'draft', 'T2: تغيّرت حالة المسودة');
    assertTrue((int) $draft['evaluator_employee_id'] === $evaluator1Id && $draft['evaluator_name'] === $evaluator1Name, 'T2: استُبدل مقيّم المسودة');
    assertTrue($draft['notes'] === 'مسودة المقيّم الأول', 'T2: تغيّرت ملاحظات المسودة');
    $count = (int) $pdo->query('SELECT COUNT(*) FROM evaluations WHERE evaluated_employee_id=' . $empId . ' AND template_id=' . $tplId . ' AND evaluation_date="' . $date . '"')->fetchColumn();
    assertTrue($count === 1, 'T2: أُنشئ صف إضافي');
    $answers = (int) $pdo->query('SELECT COUNT(*) FROM evaluation_answers WHERE evaluation_id=' . $draftId)->fetchColumn();
    assertTrue($answers === 0, 'T2: أُضيفت إجابات للمسودة رغم الرفض');

    echo "  [PASS] TEST 2 — other evaluator's draft protected\n";
    $passCount++;
} catch (\Throwable $e) {
    echo "  [FAIL] TEST 2 — " . $e->getMessage() . "\n";
    $failures[] = 'draft_other: ' . $e->getMessage();
}

/* ═══════════════════════════════════════════════════════════════════════
   TEST 3 — DRAFT of the SAME evaluator -> update in place (no duplicate)
   ═══════════════════════════════════════════════════════════════════════ */
try {
    [$tplId] = makeTemplate($pdo, $token . '_draft_same');
    $empCode = 'CONF-S-' . $token;
    $empName = 'موظف تعارض مسودة نفس المقيّم ' . $token;
    $pdo->prepare('INSERT INTO employees (emp_code, full_name, is_active) VALUES (:code, :name, 1)')
        ->execute(['code' => $empCode, 'name' => $empName]);
    $empId = (int) $pdo->lastInsertId();
    $date = '2026-01-07';

    $ins = $pdo->prepare('INSERT INTO evaluations (template_id, entity_type, entity_id, evaluated_employee_id, evaluator_employee_id, evaluator_name, evaluation_date, created_by_user_id, employee_name, employee_code, branch_name, department_name, position_name, notes, status) VALUES (:tid, "employee", :eid, :eid, :evid, :evname, :edate, :uid, :ename, :ecode, "", "", "", :notes, "draft")');
    $ins->execute(['tid' => $tplId, 'eid' => $empId, 'evid' => $evaluator1Id, 'evname' => $evaluator1Name, 'edate' => $date, 'uid' => $userId, 'ename' => $empName, 'ecode' => $empCode, 'notes' => 'مسودة قابلة للتحديث']);
    $draftId = (int) $pdo->lastInsertId();

    $path = $directory . '/conf-draft-same-' . $token . '.xlsx';
    writeEmployeeSheet($service, $tplId, $evaluator1Id, $path, $empCode, $empName, $date, $evaluator1Name, '66');
    $preview = $service->previewWorkbookPath($path, 'conf-draft-same.xlsx', filesize($path), $userId, $evaluator1Id);
    assertTrue($preview['valid'] === 1 && $preview['invalid'] === 0, 'T3: معاينة فاشلة');

    $commit = $service->commitBatch($preview['batch_id'], $userId, $evaluator1Id);
    assertTrue($commit['committed_rows'] === 1 && $commit['committed_evaluations'] === 1, 'T3: الالتزام فشل');

    $rows = $pdo->query('SELECT id, status, final_score FROM evaluations WHERE evaluated_employee_id=' . $empId . ' AND template_id=' . $tplId . ' AND evaluation_date="' . $date . '"')->fetchAll(PDO::FETCH_ASSOC);
    assertTrue(count($rows) === 1 && (int) $rows[0]['id'] === $draftId, 'T3: لم يُحدّث نفس الصف — أُنشئ صف جديد');
    assertTrue($rows[0]['status'] === 'completed', 'T3: الحالة لم تصبح completed');
    assertTrue((float) $rows[0]['final_score'] === 66.0, 'T3: الدرجة النهائية غير صحيحة: ' . var_export($rows[0]['final_score'], true));
    $answers = (int) $pdo->query('SELECT COUNT(*) FROM evaluation_answers WHERE evaluation_id=' . $draftId)->fetchColumn();
    assertTrue($answers === 1, 'T3: عدد الإجابات غير صحيح');

    echo "  [PASS] TEST 3 — same evaluator's draft updated in place\n";
    $passCount++;
} catch (\Throwable $e) {
    echo "  [FAIL] TEST 3 — " . $e->getMessage() . "\n";
    $failures[] = 'draft_same: ' . $e->getMessage();
}

/* ═══════════════════════════════════════════════════════════════════════
   SUMMARY
   ═══════════════════════════════════════════════════════════════════════ */
echo "\n";
echo "══════════════════════════════════════════\n";
echo "  RESULTS: $passCount / 3 PASSED\n";
echo "══════════════════════════════════════════\n";
if ($passCount === 3) {
    echo "  ALL TESTS PASSED — CD-042 import conflict handling verified.\n";
} else {
    echo "  FAILURES:\n";
    foreach ($failures as $f) { echo "    - $f\n"; }
    exit(1);
}
