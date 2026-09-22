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
require_once $rootDir . '/app/Core/BackupScheduler.php';

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

    // استخدام checkScheduledBackups بدلاً من runAllScheduledTasks إذا كانت غير موجودة
    if (method_exists($scheduler, 'runAllScheduledTasks')) {
        $scheduler->runAllScheduledTasks();
    } else if (method_exists($scheduler, 'checkScheduledBackups')) {
        $scheduler->checkScheduledBackups();
        echo "[" . date('Y-m-d H:i:s') . "] ✅ تم التحقق من المهام المجدولة
";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] ⚠️ لا توجد دوال جدولة متاحة
";
    }

    // تنفيذ نسخ احتياطي فعلي
    echo "[" . date('Y-m-d H:i:s') . "] بدء تنفيذ النسخ الاحتياطي الفعلي...
";

    // الحصول على إعدادات النسخ الاحتياطي
    $settingsModel = new BackupSetting();
    $settings = $settingsModel->getCurrentSettings();

    // تحديد نوع النسخ الاحتياطي
    $backupType = 'full'; // افتراضي نسخة كاملة
    if ($settings && $settings->backup_type) {
        $backupType = $settings->backup_type;
    }

    // إنشاء نسخة احتياطية
    $backupModel = new Backup();
    $result = [];

    if ($backupType === 'full') {
        echo "[" . date('Y-m-d H:i:s') . "] إنشاء نسخة احتياطية كاملة...
";
        $result = $backupModel->createFullBackup(1); // user_id = 1 للنظام
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] إنشاء نسخة احتياطية تزايدية...
";
        $result = $backupModel->createIncrementalBackup(1); // user_id = 1 للنظام
    }

    // التحقق من نتيجة النسخ الاحتياطي
    if ($result && isset($result['success']) && $result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] ✅ تم إنشاء النسخة الاحتياطية بنجاح
";
        if (isset($result['filename'])) {
            echo "[" . date('Y-m-d H:i:s') . "] 📁 ملف النسخ: " . $result['filename'] . "
";
        }
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] ❌ فشل إنشاء النسخة الاحتياطية
";
        if (isset($result['error'])) {
            echo "[" . date('Y-m-d H:i:s') . "] سبب الخطأ: " . $result['error'] . "
";
        }
        exit(1);
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