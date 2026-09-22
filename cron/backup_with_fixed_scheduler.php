<?php
// cron/backup.php - نسخة محسنة مع تنفيذ النسخ الاحتياطي الفعلي

// تحديد المسارات بشكل صحيح
$rootDir = dirname(__DIR__);

// تحميل الملفات الأساسية أولاً
require_once $rootDir . '/config/config.php';
require_once $rootDir . '/config/database.php';

// تحميل ملفات Core الأساسية
require_once $rootDir . '/app/Core/Database.php';
require_once $rootDir . '/app/Core/Model.php';

// تحميل ملفات النسخ الاحتياطي - الترتيب مهم!
require_once $rootDir . '/app/Core/BackupEncryption.php';
require_once $rootDir . '/app/Core/BackupOptimizer.php';
require_once $rootDir . '/app/Core/BackupMonitor.php';
// استخدام نسخة محسنة من BackupScheduler
require_once $rootDir . '/app/Core/BackupScheduler_fixed.php';

// تحميل Models
require_once $rootDir . '/app/Models/Backup.php';
require_once $rootDir . '/app/Models/BackupSetting.php';

use App\Core\BackupScheduler;
use App\Models\Backup;
use App\Models\BackupSetting;

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// تقليل مستوى الإخطار
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// تعيين الوقت المحلي
date_default_timezone_set('Asia/Riyadh');

// تجاهل تحذيرات إعدادات PHP
function suppressPhpWarnings($errno, $errstr) {
    if (strpos($errstr, 'Unable to load dynamic library') !== false) {
        return true; // تجاهل تحذيرات تحميل المكتبات
    }
    return false;
}
set_error_handler('suppressPhpWarnings');

try {
    echo "[" . date('Y-m-d H:i:s') . "] بدء عملية النسخ الاحتياطي المحسنة...
";

    // التحقق من اتصال قاعدة البيانات أولاً
    try {
        $db = \App\Core\Database::getInstance();
        echo "[" . date('Y-m-d H:i:s') . "] ✅ اتصال قاعدة البيانات نشط
";
    } catch (Exception $e) {
        echo "[" . date('Y-m-d H:i:s') . "] ❌ فشل اتصال قاعدة البيانات: " . $e->getMessage() . "
";
        exit(1);
    }

    // إنشاء وتشغيل Scheduler للتحقق من المهام المجدولة
    $scheduler = new BackupScheduler();
    echo "[" . date('Y-m-d H:i:s') . "] التحقق من المهام المجدولة...
";

    // استخدام runAutoScheduler المحسن مع نافذة زمنية
    $result = $scheduler->runAutoScheduler();

    if ($result && isset($result['status'])) {
        switch ($result['status']) {
            case 'success':
                echo "[" . date('Y-m-d H:i:s') . "] ✅ تم تنفيذ النسخ المجدول بنجاح
";
                if (isset($result['filename'])) {
                    echo "[" . date('Y-m-d H:i:s') . "] 📁 ملف النسخ: " . $result['filename'] . "
";
                }
                break;

            case 'skipped':
                echo "[" . date('Y-m-d H:i:s') . "] ⏭️ تم تخطي النسخ: " . $result['reason'] . "
";
                break;

            case 'waiting':
                echo "[" . date('Y-m-d H:i:s') . "] ⏳ في انتظار وقت النسخ التالي
";
                break;

            case 'error':
                echo "[" . date('Y-m-d H:i:s') . "] ❌ خطأ في النسخ المجدول: " . $result['message'] . "
";
                exit(1);
        }
    } else {
        // استخدام checkScheduledBackups كخيار احتياطي
        if (method_exists($scheduler, 'checkScheduledBackups')) {
            $scheduler->checkScheduledBackups();
            echo "[" . date('Y-m-d H:i:s') . "] ✅ تم التحقق من المهام المجدولة
";
        } else {
            echo "[" . date('Y-m-d H:i:s') . "] ⚠️ لا توجد دوال جدولة متاحة
";
        }
    }

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] 💥 خطأ غير متوقع: " . $e->getMessage() . "
";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] ✅ اكتملت عملية النسخ الاحتياطي بنجاح
";

// إعادة معالج الأخطاء إلى الوضع الافتراضي
restore_error_handler();
?>