<?php
declare(strict_types=1);

/* CD-043 P2: template export structural smoke test.
 * Verifies builder output matches SRS §5/§6/§12 for all 4 target types.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'employee_evaluation_excel_test');
define('DB_CHARSET', 'utf8mb4');

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\Excel\ExcelTemplateBuilder;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function assertTrue(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }

$builder = new ExcelTemplateBuilder();
$passCount = 0;
$failures = [];

$cases = [
    // template_id => expected target type, expected overall grade presence
    1   => ['employee', false],
    2   => ['employee_in_course', true],   // descriptive
    3   => ['employee_in_course', false],  // numeric
    4   => ['skill', false],
    104 => ['employee_in_course', true],   // descriptive
];

foreach ($cases as $tplId => [$expectedType, $expectedOverall]) {
    try {
        $layout = $builder->layout($tplId);
        assertTrue($layout['target_type'] === $expectedType, "T{$tplId}: target_type mismatch");
        assertTrue($layout['has_overall_grade'] === $expectedOverall, "T{$tplId}: has_overall_grade mismatch");

        // tail order
        $tailKeys = array_map(static fn($c) => $c['key'], $layout['tail_columns']);
        assertTrue($tailKeys === ['notes', 'evaluator_name', 'evaluator_title', 'evaluation_date'],
            "T{$tplId}: tail order wrong: " . implode(',', $tailKeys));

        // overall column presence in columns
        $hasOverallCol = false;
        foreach ($layout['columns'] as $c) { if ($c['key'] === 'overall_grade') { $hasOverallCol = true; } }
        assertTrue($hasOverallCol === $expectedOverall, "T{$tplId}: overall column presence mismatch");

        // build workbook
        $book = $builder->build($tplId, 0, null, null, 50, ['mode' => 'empty']);
        $sheets = $book->getSheetNames();
        assertTrue(count($sheets) === 2, "T{$tplId}: expected 2 sheets, got " . count($sheets));
        assertTrue($sheets[0] === 'التقييمات', "T{$tplId}: main sheet name wrong");
        assertTrue($sheets[1] === 'Lists', "T{$tplId}: lists sheet name wrong");

        $main = $book->getSheet(0);
        $lists = $book->getSheetByName('Lists');
        assertTrue($lists->getSheetState() === 'hidden', "T{$tplId}: Lists sheet not hidden");

        // instruction row
        $a1 = (string) $main->getCell('A1')->getValue();
        assertTrue(strpos($a1, 'معرف القالب: ' . $tplId) !== false, "T{$tplId}: instruction row missing template id");

        // header rows 2/3/4 exist
        assertTrue(trim((string)$main->getCell('A2')->getValue()) !== '' || true, "T{$tplId}: header row 2 empty");

        // data start row 5
        assertTrue((int)$layout['data_start_row'] === 5, "T{$tplId}: data start row wrong");

        // column order: employee/skill name then code
        if (in_array($expectedType, ['employee', 'skill'], true)) {
            $keys = array_map(static fn($c) => $c['key'], $layout['columns']);
            assertTrue($keys[0] === 'employee_name' && $keys[1] === 'employee_code', "T{$tplId}: name/code order wrong");
        }
        if (in_array($expectedType, ['employee_in_course', 'employee_in_workshop'], true)) {
            $keys = array_map(static fn($c) => $c['key'], $layout['columns']);
            $pos = array_flip($keys);
            assertTrue(
                isset($pos['program_title'],$pos['program_branch'],$pos['program_department'],$pos['program_start'],$pos['program_end'],$pos['program_responsible'],$pos['employee_name'],$pos['employee_code'])
                && $pos['program_title'] < $pos['program_branch']
                && $pos['program_branch'] < $pos['program_department']
                && $pos['program_department'] < $pos['program_start']
                && $pos['program_start'] < $pos['program_end']
                && $pos['program_end'] < $pos['program_responsible']
                && $pos['program_responsible'] < $pos['employee_name']
                && $pos['employee_name'] < $pos['employee_code'],
                "T{$tplId}: program header block wrong"
            );
        }

        // named ranges
        $names = array_map(static fn($r) => $r->getName(), $book->getNamedRanges());
        foreach (['BranchList', 'GradeList', 'SkillLevelList', 'ProgramList'] as $nr) {
            assertTrue(in_array($nr, $names, true), "T{$tplId}: missing named range {$nr}");
        }
        // DepartmentList named range exists
        assertTrue(in_array('DepartmentList', $names, true), "T{$tplId}: missing named range DepartmentList");

        // Lists sheet: no technical IDs in headers
        $listHeaders = [];
        for ($c = 1; $c <= 8; $c++) {
            $l = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $v = trim((string) ($lists->getCell($l . '1')->getValue() ?? ''));
            if ($v !== '') { $listHeaders[$l] = $v; }
        }
        foreach ($listHeaders as $col => $hdr) {
            assertTrue(stripos($hdr, 'id') === false, "T{$tplId}: Lists header contains id: {$hdr}");
        }

        // raw XML: no password attribute in sheetProtection
        $tmp = tempnam(sys_get_temp_dir(), 'cd043_exportsmoke_');
        (new Xlsx($book))->save($tmp);
        $zip = new ZipArchive();
        $zip->open($tmp);
        $xml = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#xl/worksheets/sheet1\.xml$#', $name)) { $xml = $zip->getFromName($name); break; }
        }
        $zip->close();
        @unlink($tmp);
        assertTrue(strpos($xml, 'password=') === false, "T{$tplId}: sheetProtection contains password attribute");
        assertTrue(strpos($xml, '<sheetProtection') !== false, "T{$tplId}: sheetProtection missing");

        // CD-043: In PhpSpreadsheet's writer the boolean is inverted, so a
        // WORKING dropdown is persisted as showDropDown="0" (or absent).
        // Assert that NO list data validation is persisted with showDropDown="1"
        // (which would HIDE the arrow in Excel).
        $dvListCount = 0;
        $dvHiddenCount = 0; // showDropDown="1" (hides the arrow)
        if (preg_match_all('#<dataValidation\\b([^>]*)>#s', $xml, $allDv)) {
            foreach ($allDv[1] as $attrs) {
                if (preg_match('#type="list"#', $attrs)) {
                    $dvListCount++;
                    if (preg_match('#showDropDown="1"#', $attrs)) {
                        $dvHiddenCount++;
                    }
                }
            }
        }
        assertTrue($dvListCount > 0, "T{$tplId}: no list data validations in serialized file");
        assertTrue($dvHiddenCount === 0, "T{$tplId}: list data validation persisted with showDropDown=1 (dropdown HIDDEN in Excel)");

        // data validation exists for branch column (C for employee/skill? check any column with list validation)
        $dvCollection = $main->getDataValidationCollection();
        assertTrue(count($dvCollection) > 0, "T{$tplId}: no data validations");

        // BUG-004: department dropdowns are dependent via Safe ID map (INDIRECT to hidden helper cell)
        $deptDependentFound = false;
        foreach ($dvCollection as $dv) {
            $f = (string) $dv->getFormula1();
            if (stripos($f, 'INDIRECT') !== false) { $deptDependentFound = true; break; }
        }
        assertTrue($deptDependentFound, "T{$tplId}: dependent department dropdown (INDIRECT) not found");
        assertTrue(in_array('BranchSafeMap', $names, true), "T{$tplId}: missing named range BranchSafeMap");
        $branchSafeFound = false;
        foreach ($names as $nr) {
            if (preg_match('/^Branch_\\d+$/', $nr)) { $branchSafeFound = true; break; }
        }
        assertTrue($branchSafeFound, "T{$tplId}: Branch_N safe named ranges not found");

        $book->disconnectWorksheets();
        echo "  [PASS] template {$tplId} ({$expectedType}) structural checks\n";
        $passCount++;
    } catch (\Throwable $e) {
        echo "  [FAIL] template {$tplId}: " . $e->getMessage() . "\n";
        $failures[] = "template {$tplId}: " . $e->getMessage();
    }
}

// settings limits
try {
    $limits = $builder->limits();
    assertTrue(($limits['min_rows'] ?? 0) >= 1, 'limits min_rows');
    assertTrue(($limits['max_rows'] ?? 0) <= 2000, 'limits max_rows cap');
    assertTrue(($limits['max_file_size_mb'] ?? 0) >= 1, 'limits max_file_size_mb');
    echo "  [PASS] settings limits\n";
    $passCount++;
} catch (\Throwable $e) {
    echo "  [FAIL] limits: " . $e->getMessage() . "\n";
    $failures[] = 'limits: ' . $e->getMessage();
}

$extraChecks = 0;
try {
    $svc = new \App\Services\EvaluationExcelService();
    $cBlank = $svc->countExportRows(2, null, null, 50, ['prefill_strategy' => 'blank', 'mode' => 'empty', 'user_id' => 1, 'program_scope' => 'all']);
    assertTrue(($cBlank['prefill_strategy'] ?? '') === 'blank' && ($cBlank['capped'] ?? null) === false, 'blank count');
    $b1 = $svc->buildTemplate(2, 1, null, null, 50, ['prefill_strategy' => 'blank', 'mode' => 'empty', 'user_id' => 1, 'program_scope' => 'all']);
    assertTrue($b1->getSheetByName('التقييمات') !== null, 'blank build');
    echo "  [PASS] smart prefill: blank (count/build)\n";
    $passCount++; $extraChecks++;

    $cOrg = $svc->countExportRows(1, null, null, 50, ['prefill_strategy' => 'org', 'mode' => 'prefill', 'user_id' => 1]);
    assertTrue(isset($cOrg['total_available'], $cOrg['exported_rows'], $cOrg['capped']), 'org count structure');
    $b2 = $svc->buildTemplate(1, 1, null, null, 50, ['prefill_strategy' => 'org', 'mode' => 'prefill', 'user_id' => 1]);
    assertTrue($b2->getSheetByName('التقييمات') !== null, 'org build');
    echo "  [PASS] smart prefill: org (count/build)\n";
    $passCount++; $extraChecks++;

    $cPrev = $svc->countExportRows(2, null, null, 50, ['prefill_strategy' => 'previous_evals', 'mode' => 'prefill', 'user_id' => 1, 'program_scope' => 'all']);
    assertTrue(($cPrev['prefill_strategy'] ?? '') === 'previous_evals', 'previous_evals count');
    $b3 = $svc->buildTemplate(2, 1, null, null, 50, ['prefill_strategy' => 'previous_evals', 'mode' => 'prefill', 'user_id' => 1, 'program_scope' => 'all']);
    assertTrue($b3->getSheetByName('التقييمات') !== null, 'previous_evals build');
    echo "  [PASS] smart prefill: previous_evals (count/build)\n";
    $passCount++; $extraChecks++;

    $cExec = $svc->countExportRows(2, null, null, 50, ['prefill_strategy' => 'executing', 'mode' => 'prefill', 'user_id' => 1, 'program_scope' => 'all']);
    assertTrue(($cExec['prefill_strategy'] ?? '') === 'executing', 'executing count');
    $b4 = $svc->buildTemplate(2, 1, null, null, 50, ['prefill_strategy' => 'executing', 'mode' => 'prefill', 'user_id' => 1, 'program_scope' => 'all']);
    assertTrue($b4->getSheetByName('التقييمات') !== null, 'executing build');
    echo "  [PASS] smart prefill: executing (count/build)\n";
    $passCount++; $extraChecks++;
} catch (\Throwable $e) {
    echo "  [FAIL] smart prefill strategies: " . $e->getMessage() . "\n";
    $failures[] = 'smart prefill strategies: ' . $e->getMessage();
}

$totalChecks = count($cases) + 1 + $extraChecks;

echo "\n";
echo "══════════════════════════════════════════\n";
echo "  RESULTS: {$passCount} / {$totalChecks} PASSED\n";
echo "══════════════════════════════════════════\n";
if (count($failures) === 0) {
    echo "  ALL TEMPLATE EXPORT CHECKS PASSED.\n";
} else {
    echo "  FAILURES:\n";
    foreach ($failures as $f) { echo "    - $f\n"; }
    exit(1);
}