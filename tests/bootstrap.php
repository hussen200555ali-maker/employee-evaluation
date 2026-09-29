<?php
/**
 * اختبارات النظام — بيئة التمهيد (Bootstrap)
 *
 * يحمّل هذا الملف إعدادات التطبيق والـ autoloader ويوفر دوال مساعدة
 * للتحقق من توفر قاعدة البيانات والإضافات (extensions) المطلوبة.
 *
 * لا يعتمد على أي حزمة اختبار خارجية (dependency-free) ليتوافق مع
 * سياسة المشروع "تعمل بدون إنترنت".
 */

if (php_sapi_name() !== 'cli') {
    exit(1);
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Composer autoloader (يقوم أيضاً بتحميل app/Core/helpers.php عبر files)
require_once APP_ROOT . '/vendor/autoload.php';

// إعدادات التطبيق العامة
require_once APP_ROOT . '/config/config.php';

// إعدادات الاتصال بقاعدة البيانات
require_once APP_ROOT . '/config/database.php';

if (!defined('TEST_ROOT')) {
    define('TEST_ROOT', __DIR__);
}

// OOS-15b: RouterTest كان يُسقط 3 اختبارات بخطأ "فئة التحكم غير موجودة".
// السبب: composer.json يربط App\ فقط، ولا يوجد autoload-dev لـ Tests\، و
// Composer غير مثبَّت على هذا النظام فلا يمكن إعادة توليد خرائط التحميل.
// نحمّل الـ fixture تحميلًا صريحًا (بديل متوافق مع سياسة المشروع بلا إنترنت).
require_once TEST_ROOT . '/Fixtures/HelloController.php';

// بدء جلسة حتى تعمل دوال Auth و CSRF كما في بيئة الويب
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * هل قاعدة البيانات متاحة؟
 */
function db_available(): bool
{
    static $available = null;
    if ($available !== null) {
        return $available;
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_TIMEOUT => 3]
        );
        $pdo = null;
        $available = true;
    } catch (Throwable $e) {
        $available = false;
    }
    return $available;
}

/**
 * هل يوجد جدول معيّن في قاعدة البيانات؟
 */
function db_has_table(string $table): bool
{
    if (!db_available()) {
        return false;
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_TIMEOUT => 3]
        );
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :tbl');
        $stmt->bindValue(':db', DB_NAME);
        $stmt->bindValue(':tbl', $table);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * هل الإضافة المطلوبة مفعّلة في PHP؟
 */
function php_ext_available(string $ext): bool
{
    return extension_loaded($ext);
}
