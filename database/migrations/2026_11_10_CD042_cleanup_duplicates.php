<?php
/**
 * CD-042: 清理重复评估 (cleanup duplicate evaluations)
 *
 * Usage:
 *   php database/migrations/2026_11_10_CD042_cleanup_duplicates.php            (dry-run, default)
 *   php database/migrations/2026_11_10_CD042_cleanup_duplicates.php --apply   (执行)
 *
 * Rule: keep the most complete row per (employee, template, date, context):
 *   1) most evaluation_answers
 *   2) final_score IS NOT NULL
 *   3) lowest id (earliest)
 * Backs up removed rows + answers to storage/backups/CD-042/ before deletion.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

$apply = in_array('--apply', $argv ?? [], true);

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'employee_evaluation_uat';
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// find duplicate groups
$groups = $pdo->query("
    SELECT evaluated_employee_id AS emp, template_id AS tpl, evaluation_date AS dt,
           COALESCE(context_id,0) AS ctx, COUNT(*) AS c
    FROM evaluations
    GROUP BY evaluated_employee_id, template_id, evaluation_date, COALESCE(context_id,0)
    HAVING COUNT(*) > 1
")->fetchAll();

echo "Duplicate groups: " . count($groups) . PHP_EOL;

$toDelete = []; // eval ids
$report = [];
$totalExtra = 0;

foreach ($groups as $g) {
    $stmt = $pdo->prepare("
        SELECT e.id, e.final_score,
               (SELECT COUNT(*) FROM evaluation_answers a WHERE a.evaluation_id = e.id) AS ans
        FROM evaluations e
        WHERE e.evaluated_employee_id = :emp AND e.template_id = :tpl
          AND e.evaluation_date = :dt AND COALESCE(e.context_id,0) = :ctx
        ORDER BY e.id
    ");
    $stmt->execute([':emp'=>$g['emp'], ':tpl'=>$g['tpl'], ':dt'=>$g['dt'], ':ctx'=>$g['ctx']]);
    $rows = $stmt->fetchAll();

    // pick keeper: most answers, then final_score not null, then lowest id
    $keeper = null;
    foreach ($rows as $r) {
        if ($keeper === null) { $keeper = $r; continue; }
        $better = false;
        if ((int)$r['ans'] > (int)$keeper['ans']) $better = true;
        elseif ((int)$r['ans'] === (int)$keeper['ans']) {
            $rHas = $r['final_score'] !== null;
            $kHas = $keeper['final_score'] !== null;
            if ($rHas && !$kHas) $better = true;
            elseif ($rHas && $kHas && (float)$r['final_score'] > (float)$keeper['final_score']) $better = true;
            elseif ($rHas === $kHas && (float)$r['final_score'] === (float)$keeper['final_score'] && (int)$r['id'] < (int)$keeper['id']) $better = true;
            elseif (!$rHas && !$kHas && (int)$r['id'] < (int)$keeper['id']) $better = true;
        }
        if ($better) $keeper = $r;
    }

    $delIds = [];
    foreach ($rows as $r) {
        if ((int)$r['id'] === (int)$keeper['id']) continue;
        $delIds[] = (int)$r['id'];
        $toDelete[] = (int)$r['id'];
    }
    $totalExtra += count($delIds);

    $report[] = [
        'employee_id' => (int)$g['emp'],
        'template_id' => (int)$g['tpl'],
        'date'        => $g['dt'],
        'context'     => (int)$g['ctx'],
        'total'       => count($rows),
        'keeper'      => (int)$keeper['id'],
        'deleted'     => $delIds,
    ];

    echo sprintf("emp=%d tpl=%d date=%s ctx=%d : total=%d keeper=%d delete=%s\n",
        $g['emp'], $g['tpl'], $g['dt'], $g['ctx'], count($rows), $keeper['id'], implode(',', $delIds));
}

echo "Total extra rows to delete: {$totalExtra}\n";

if (!$apply) {
    echo "\nDRY RUN — no changes. Re-run with --apply to execute.\n";
    exit(0);
}

// backup
$backupDir = __DIR__ . '/../../storage/backups/CD-042';
if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);
$ts = date('Ymd_His');
$backupFile = $backupDir . '/duplicates_' . $ts . '.json';

$backup = ['report' => $report, 'evaluations' => [], 'answers' => []];
if ($toDelete) {
    $ph = implode(',', array_fill(0, count($toDelete), '?'));
    $st = $pdo->prepare("SELECT * FROM evaluations WHERE id IN ($ph)");
    $st->execute($toDelete);
    $backup['evaluations'] = $st->fetchAll();
    $st = $pdo->prepare("SELECT * FROM evaluation_answers WHERE evaluation_id IN ($ph)");
    $st->execute($toDelete);
    $backup['answers'] = $st->fetchAll();
}
file_put_contents($backupFile, json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Backup written: {$backupFile}\n";

// delete
$pdo->beginTransaction();
try {
    if ($toDelete) {
        $ph = implode(',', array_fill(0, count($toDelete), '?'));
        $st = $pdo->prepare("DELETE FROM evaluation_answers WHERE evaluation_id IN ($ph)");
        $st->execute($toDelete);
        $st = $pdo->prepare("DELETE FROM evaluations WHERE id IN ($ph)");
        $st->execute($toDelete);
    }
    $pdo->commit();
    echo "Deleted {$totalExtra} duplicate evaluations (+ their answers).\n";
} catch (\Throwable $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// verify
$remaining = (int)$pdo->query("
    SELECT COUNT(*) FROM (
        SELECT evaluated_employee_id, template_id, evaluation_date, COALESCE(context_id,0) c
        FROM evaluations GROUP BY evaluated_employee_id, template_id, evaluation_date, COALESCE(context_id,0)
        HAVING COUNT(*) > 1
    ) x
")->fetchColumn();
echo "Remaining duplicate groups: {$remaining}\n";
exit($remaining > 0 ? 1 : 0);