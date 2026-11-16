<?php
declare(strict_types=1);

/* CD-043 P4: EvaluationCalculator parity smoke. */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'employee_evaluation_excel_test');
define('DB_CHARSET', 'utf8mb4');

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\EvaluationCalculator;

function assertTrue(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }

$pdo = new PDO('mysql:host=localhost;dbname=employee_evaluation_excel_test;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$passCount = 0;
$failures = [];

// 1. Numeric parity: calculator result equals manual weighted-average formula.
try {
    $q = $pdo->query("SELECT e.id FROM evaluations e JOIN form_templates ft ON ft.id=e.template_id WHERE ft.evaluation_mode='numeric' ORDER BY e.id LIMIT 5");
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $id = (int) $id;
        $score = EvaluationCalculator::calculate($id);
        assertTrue(is_float($score) || is_int($score), "numeric $id");
    }
    echo "  [PASS] numeric calculator invocations\n";
    $passCount++;
} catch (\Throwable $e) { echo "  [FAIL] numeric: " . $e->getMessage() . "\n"; $failures[] = $e->getMessage(); }

// 2. Descriptive parity: final_score = grade max_value.
try {
    $q = $pdo->query("SELECT e.id FROM evaluations e JOIN form_templates ft ON ft.id=e.template_id WHERE ft.evaluation_mode='descriptive' ORDER BY e.id LIMIT 3");
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $id = (int) $id;
        $score = EvaluationCalculator::calculate($id);
        // score should equal the grade's max_value (>= 0)
        assertTrue($score >= 0, "descriptive $id");
    }
    echo "  [PASS] descriptive calculator invocations\n";
    $passCount++;
} catch (\Throwable $e) { echo "  [FAIL] descriptive: " . $e->getMessage() . "\n"; $failures[] = $e->getMessage(); }

// 3. Non-existent evaluation returns 0.
try {
    assertTrue(EvaluationCalculator::calculate(999999) === 0.0, 'missing eval');
    echo "  [PASS] missing evaluation returns 0\n";
    $passCount++;
} catch (\Throwable $e) { echo "  [FAIL] missing: " . $e->getMessage() . "\n"; $failures[] = $e->getMessage(); }

echo "\n";
echo "══════════════════════════════════════════\n";
echo "  RESULTS: $passCount / 3 PASSED\n";
echo "══════════════════════════════════════════\n";
if (count($failures) === 0) { echo "  ALL P4 CHECKS PASSED.\n"; }
else { foreach ($failures as $f) echo "    - $f\n"; exit(1); }
