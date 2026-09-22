<?php
/**
 * إعدادات التطبيق الموحّدة — مصدر الحقيقة الوحيد
 * (بند 6: دمج config.php + app.php + performance.php وفق الجدول المعتمد)
 * opcache_reset() مستبعد عمدًا (يظل في كود النسخ الاحتياطي فقط).
 */

// =====================================================================
// إعدادات الذاكرة والأداء (قيم الدمج المعتمدة: memory_limit 2048M / max_execution_time 600)
// =====================================================================
ini_set('memory_limit', '2048M');        // 2GB (القيمة المعتمدة — رُفضت 4096M)
ini_set('max_execution_time', 600);      // 10 دقائق (القيمة المعتمدة — رُفضت 1200)
ini_set('max_input_time', 300);
ini_set('max_input_vars', 3000);
ini_set('post_max_size', '256M');
ini_set('upload_max_filesize', '256M');
ini_set('pcre.backtrack_limit', 1000000);
ini_set('pcre.recursion_limit', 1000000);
if (function_exists('gc_enable')) {
    gc_enable();
}
ini_set('zend.enable_gc', 1);

// =====================================================================
// مسار جذر التطبيق (يُعرَّف قبل كتلة تسجيل الأخطاء أدناه)
// =====================================================================
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// =====================================================================
// تسجيل الأخطاء (بند 6): إخفاء التفاصيل عن الزائر + التسجيل في ملف
// =====================================================================
error_reporting(E_ALL);
ini_set('display_errors', 0);            // لا تُعرض الأخطاء على الشاشة إطلاقًا
ini_set('log_errors', 1);                // تُسجَّل في ملف
define('DEBUG_MODE', false);             // وضع الإنتاج (بعد أن كان true)
if (!is_dir(APP_ROOT . '/storage/logs')) {
    @mkdir(APP_ROOT . '/storage/logs', 0777, true); // إنشاء دفاعي (مع إبقاء اسم الصلاحية على النظام)
}
ini_set('error_log', APP_ROOT . '/storage/logs/php-error.log'); // ملف أخطاء PHP الموحّد

// =====================================================================
// إعدادات الجلسة الموحّدة (تُنفَّذ قبل session_start)
// =====================================================================
ini_set('session.gc_maxlifetime', 1800);      // مهلة الخمول: 30 دقيقة
ini_set('session.cookie_lifetime', 0);        // الكوكي حتى إغلاق المتصفح
ini_set('session.cookie_httponly', 1);        // منع قراءة الكوكي من JavaScript
ini_set('session.cookie_samesite', 'Strict'); // حماية إضافية ضد CSRF
ini_set('session.cookie_secure', 0);          // يُفعَّل عند تفعيل HTTPS (A1-3)
ini_set('session.use_strict_mode', 1);        // رفض معرّفات الجلسة غير الصادرة عن الخادم
ini_set('session.use_only_cookies', 1);       // تعطيل تمرير معرّف الجلسة عبر الرابط
if (session_status() === PHP_SESSION_NONE) {
    session_name('EVALSESSID');
}

// اسم التطبيق
define('APP_NAME', 'نظام تقييم الموظفين');

// رابط التطبيق الأساسي (يُكتشف تلقائياً)
// SCRIPT_NAME = /employee-evaluation/public/index.php → نريد /employee-evaluation
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$basePath = rtrim(dirname(dirname($scriptName)), '/\\');
$detectedUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
             . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
             . ($basePath === '/' ? '' : $basePath);
define('APP_URL', $detectedUrl);

// إعدادات CSRF
define('CSRF_GRACE_PERIOD_MINUTES', 240); // 4 ساعات

// إعدادات المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');
define('APP_TIMEZONE', 'Asia/Riyadh');
define('APP_TIMEZONE_OFFSET', '+03:00');

// إعدادات النسخ الاحتياطي (قيم الدمج المعتمدة)
define('BACKUP_MEMORY_LIMIT', '2048M');
define('BACKUP_MAX_EXECUTION_TIME', 600);
define('BACKUP_BATCH_SIZE', 100);