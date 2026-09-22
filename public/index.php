<?php
/**
 * نقطة الدخول الرئيسية للتطبيق (Front Controller)
 */
// ??? أضف هذا السطر لتضمين كل المكتبات التي تم تثبيتها عبر Composer ???
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../app/Core/helpers.php';

// تحميل الإعدادات أولاً: تُهيِّئ الجلسة (session_name + gc) قبل بدئها
require_once '../config/config.php';
require_once '../config/database.php';

session_start();

// تعريف المسار الجذري للمشروع (يفترض أنه موجود لديك)
//define('APP_ROOT', dirname(__DIR__));

// ??? أضف هذا السطر هنا ???
// تعريف المسار إلى مجلد vendor الخاص بـ Composer
define('VENDOR_PATH', APP_ROOT . '/vendor');

$router = require_once '../routes/web.php';

// --- الجزء الأهم: تحليل الرابط (الطريقة المباشرة) ---

// الحصول على الرابط الذي تم تمريره من .htaccess عبر متغير 'url'.
// نستخدم '??' لوضع قيمة فارغة (للصفحة الرئيسية) في حال لم يكن المتغير موجوداً.
$uri = $_GET['url'] ?? '';

// تنظيف الرابط من أي شرطات مائلة زائدة في البداية أو النهاية.
$uri = trim($uri, '/');

// الحصول على نوع الطلب (GET, POST, etc.)
$requestType = $_SERVER['REQUEST_METHOD'];

// --- تفعيل حماية CSRF لجميع طلبات POST ---
\App\Core\Auth::enforceCsrfToken();

// --- بند 8: انعاش الصلاحيات — إبطال الجلسة عند تغيّر نسخة الصلاحيات (نافذة 60 ثانية) ---
\App\Core\Auth::invalidateSessionIfPermissionsChanged();

// --- تشغيل التطبيق ---
try {
    // اطلب من الـ Router أن يجد المسار الصحيح وينفذ الـ Controller المناسب.
    $router->direct($uri, $requestType);
} catch (Exception $e) {
    // بند 6: لا كشف معلومات للزائر — نُسجِّل الاستثناء كاملًا في الملف ونعرض صفحة عامة
    error_log('[FATAL] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL . $e->getTraceAsString());
    http_response_code(500);
    echo '<h1>حدث خطأ غير متوقع</h1>';
}
