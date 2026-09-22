<?php
/**
 * فئة القاعدة لجميع حالات الاختبار (dependency-free TestCase).
 *
 * توفر دوال التأكيد (assertions) ودوال مساعدة لتشغيل عمليات فرعية
 * (subprocesses) عبر CLI لمحاكاة طلبات HTTP كاملة.
 */

class TestFailureException extends Exception {}
class TestSkippedException extends Exception {}

abstract class TestCase
{
    protected array $tmpFiles = [];
    protected array $createdApiKeys = [];

    public function setUp(): void
    {
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTP_X_API_KEY']);
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        $this->tmpFiles = [];
        $this->createdApiKeys = [];
    }

    public function tearDown(): void
    {
        foreach ($this->tmpFiles as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
        $this->tmpFiles = [];
        $this->createdApiKeys = [];
    }

    // ======================================================================
    // أدوات داخلية
    // ======================================================================

    protected function fail(string $message): never
    {
        throw new TestFailureException($message);
    }

    protected function markSkipped(string $reason): never
    {
        throw new TestSkippedException($reason);
    }

    protected function skipIfDbUnavailable(string $extra = ''): void
    {
        if (!db_available()) {
            $this->markSkipped('قاعدة البيانات غير متاحة — تخطي.' . ($extra ? ' ' . $extra : ''));
        }
    }

    protected function skipIfNoExtension(string $ext): void
    {
        if (!php_ext_available($ext)) {
            $this->markSkipped("إضافة PHP '$ext' غير مفعّلة — تخطي.");
        }
    }

    /**
     * إنشاء مسار ملف مؤقت
     */
    protected function tempFile(string $suffix = 'tmp'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'eval_test_') ?: sys_get_temp_dir() . '/eval_test_' . uniqid();
        if ($suffix !== 'tmp') {
            $new = $path . '.' . $suffix;
            @unlink($path);
            $path = $new;
        }
        $this->tmpFiles[] = $path;
        return $path;
    }

    /**
     * تشغيل سكربت PHP عبر CLI في عملية فرعية معزولة.
     *
     * @param array $args  المسار + الحجج
     * @param string $stdoutFile  اختياري: ملف يلتقط stdout
     * @return array{exit:int, stdout:string, stderr:string}
     */
    protected function runCli(array $args, string $stdoutFile = ''): array
    {
        $cmd = array_merge([PHP_BINARY], $args);
        $cmdLine = implode(' ', array_map('escapeshellarg', $cmd));

        $descriptors = [
            1 => $stdoutFile !== '' ? ['file', $stdoutFile, 'w'] : ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $cwd = defined('APP_ROOT') ? APP_ROOT . '/public' : null;
        $proc = proc_open($cmdLine, $descriptors, $pipes, $cwd);
        if (!is_resource($proc)) {
            $this->fail('تعذّر تشغيل العملية الفرعية: ' . $cmdLine);
        }
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $stdout = '';
        if ($stdoutFile === '' && isset($pipes[1])) {
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
        }
        $exit = proc_close($proc);
        return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    // ======================================================================
    // دوال التأكيد (Assertions)
    // ======================================================================

    protected function assertTrue(bool $condition, string $message = ''): void
    {
        if ($condition !== true) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'متوقع true، وجد: ' . var_export($condition, true));
        }
    }

    protected function assertFalse(bool $condition, string $message = ''): void
    {
        if ($condition !== false) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'متوقع false، وجد: ' . var_export($condition, true));
        }
    }

    protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $this->fail(($message !== '' ? $message . ' — ' : '')
                . 'متوقع: ' . var_export($expected, true)
                . ' وجد: ' . var_export($actual, true));
        }
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected != $actual) {
            $this->fail(($message !== '' ? $message . ' — ' : '')
                . 'متوقع: ' . var_export($expected, true)
                . ' وجد: ' . var_export($actual, true));
        }
    }

    protected function assertNull(mixed $value, string $message = ''): void
    {
        if ($value !== null) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'متوقع null، وجد: ' . var_export($value, true));
        }
    }

    protected function assertNotNull(mixed $value, string $message = ''): void
    {
        if ($value === null) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'متوقع قيمة غير null.');
        }
    }

    protected function assertEmpty(mixed $value, string $message = ''): void
    {
        if (!empty($value)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'متوقع قيمة فارغة.');
        }
    }

    protected function assertNotEmpty(mixed $value, string $message = ''): void
    {
        if (empty($value)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'متوقع قيمة غير فارغة.');
        }
    }

    protected function assertIsArray(mixed $value, string $message = ''): void
    {
        if (!is_array($value)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'متوقع مصفوفة، وجد: ' . gettype($value));
        }
    }

    protected function assertIsString(mixed $value, string $message = ''): void
    {
        if (!is_string($value)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'متوقع نص، وجد: ' . gettype($value));
        }
    }

    protected function assertIsInt(mixed $value, string $message = ''): void
    {
        if (!is_int($value)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'متوقع عدد صحيح، وجد: ' . gettype($value));
        }
    }

    protected function assertIsFloat(mixed $value, string $message = ''): void
    {
        if (!is_float($value)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'متوقع عدد عشري، وجد: ' . gettype($value));
        }
    }

    protected function assertIsObject(mixed $value, string $message = ''): void
    {
        if (!is_object($value)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'متوقع كائن، وجد: ' . gettype($value));
        }
    }

    protected function assertInstanceOf(string $class, mixed $value, string $message = ''): void
    {
        if (!$value instanceof $class) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . "متوقع مثيل من $class.");
        }
    }

    protected function assertCount(int $expected, array $array, string $message = ''): void
    {
        $actual = count($array);
        if ($actual !== $expected) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . "متوقع عدد $expected، وجد $actual.");
        }
    }

    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        if (!array_key_exists($key, $array)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . "المفتاح '$key' غير موجود في المصفوفة.");
        }
    }

    protected function assertObjectHasProperty(string $prop, object $obj, string $message = ''): void
    {
        if (!property_exists($obj, $prop)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . "الخاصية '$prop' غير موجودة في الكائن.");
        }
    }

    protected function assertContains(mixed $needle, array $haystack, string $message = ''): void
    {
        if (!in_array($needle, $haystack)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'القيمة غير موجودة في المصفوفة.');
        }
    }

    protected function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . "النص '$needle' غير موجود في الناتج.");
        }
    }

    protected function assertMatches(string $pattern, string $subject, string $message = ''): void
    {
        if (!preg_match($pattern, $subject)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . 'الناتج لا يطابق النمط: ' . $pattern);
        }
    }

    protected function assertGreaterThan(mixed $expected, mixed $actual, string $message = ''): void
    {
        if (!($actual > $expected)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . "متوقع أكبر من $expected، وجد $actual.");
        }
    }

    protected function assertLessThanOrEqual(mixed $expected, mixed $actual, string $message = ''): void
    {
        if (!($actual <= $expected)) {
            $this->fail(($message !== '' ? $message . ' — ' : '') . "متوقع <= $expected، وجد $actual.");
        }
    }

    /**
     * يتحقق من أن الدالة ترمي استثناء/خطأ من نوع معيّن.
     */
    protected function assertThrows(callable $fn, string $class, string $message = ''): Throwable
    {
        try {
            $fn();
        } catch (Throwable $e) {
            if ($e instanceof $class) {
                return $e;
            }
            $this->fail(($message !== '' ? $message . ' — ' : '')
                . "متوقع استثناء $class، وجد: " . get_class($e) . ' (' . $e->getMessage() . ')');
        }
        $this->fail(($message !== '' ? $message . ' — ' : '') . "لم يتم رمي الاستثناء $class.");
    }

    /**
     * يتحقق من أن النص يمثل JSON صالحاً
     */
    protected function assertJson(mixed $value): object|array|null
    {
        $this->assertIsString($value);
        $decoded = json_decode($value, false, 512, JSON_THROW_ON_ERROR);
        return $decoded;
    }
}
