<?php
/**
 * مشغّل الاختبارات (Test Runner) — لا يعتمد على حزم خارجية.
 *
 * الاستخدام:
 *   php tests/runner.php                # تشغيل كل الاختبارات
 *   php tests/runner.php RouterTest     # تشغيل ما يطابق النص فقط
 *
 * شيفرة الخروج: 0 عند النجاح الكامل، 1 عند وجود فشل أو خطأ.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/TestCase.php';

$filter = $argv[1] ?? '';

// ======================================================================
// اكتشاف حالات الاختبار
// ======================================================================
$casesDir = __DIR__ . '/cases';
$testFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($casesDir, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if ($file->isFile() && preg_match('/^(.+)Test\.php$/', $file->getFilename())) {
        if ($filter !== '' && stripos($file->getFilename(), $filter) === false) {
            continue;
        }
        $testFiles[] = $file->getPathname();
    }
}
sort($testFiles);

// ======================================================================
// التشغيل
// ======================================================================
$totals = ['pass' => 0, 'fail' => 0, 'error' => 0, 'skip' => 0];
$failures = [];
$start = microtime(true);

foreach ($testFiles as $file) {
    require_once $file;
    $class = basename($file, '.php');

    if (!class_exists($class)) {
        echo "؟ فئة الاختبار غير موجودة في: $file\n";
        $totals['error']++;
        $failures[] = "$class: فئة الاختبار غير موجودة.";
        continue;
    }

    $reflection = new ReflectionClass($class);
    $casePass = 0;
    $caseFail = 0;
    $caseSkip = 0;

    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $name = $method->getName();
        if (!str_starts_with($name, 'test') || $method->getDeclaringClass()->getName() !== $class) {
            continue;
        }

        $instance = $reflection->newInstance();
        try {
            $instance->setUp();
            try {
                $instance->{$name}();
            } finally {
                $instance->tearDown();
            }
            $casePass++;
            $totals['pass']++;
        } catch (TestSkippedException $e) {
            $caseSkip++;
            $totals['skip']++;
        } catch (TestFailureException $e) {
            $caseFail++;
            $totals['fail']++;
            $failures[] = sprintf('%s::%s — %s (ملف: %s:%d)', $class, $name, $e->getMessage(), $method->getFileName(), $method->getStartLine());
        } catch (Throwable $e) {
            $totals['error']++;
            $failures[] = sprintf('%s::%s — خطأ: %s في %s:%d', $class, $name, $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    $status = $caseFail > 0 ? 'فشل' : 'نجح';
    printf("%-28s %3d/%-3d %s\n", $class, $casePass, $casePass + $caseFail + $caseSkip, $status);
}

$elapsed = round(microtime(true) - $start, 2);

echo "\n============================================\n";
echo 'النتيجة النهائية:' . PHP_EOL;
echo '  نجحت:    ' . $totals['pass'] . PHP_EOL;
echo '  فشلت:    ' . $totals['fail'] . PHP_EOL;
echo '  أخطاء:   ' . $totals['error'] . PHP_EOL;
echo '  تخطّيت:  ' . $totals['skip'] . PHP_EOL;
echo '  الوقت:   ' . $elapsed . ' ثانية' . PHP_EOL;

if (!empty($failures)) {
    echo "\n--- تفاصيل الفشل ---\n";
    foreach ($failures as $f) {
        echo '  ✗ ' . $f . PHP_EOL;
    }
    echo "\nنتيجة: فشل\n";
    exit(1);
}

echo "\nنتيجة: نجاح ✓\n";
exit(0);
