<?php
// auto_scheduler.php - المشغل التلقائي للنسخ الاحتياطي

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Core/BackupScheduler.php';

use App\Core\BackupScheduler;

// تقليل مستوى الإخطار
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// تعيين الوقت المحلي
date_default_timezone_set('Asia/Riyadh');

try {
    echo "[" . date('Y-m-d H:i:s') . "] بدء المشغل التلقائي للنسخ الاحتياطي...\n";
    
    // التحقق من اتصال قاعدة البيانات
    $db = \App\Core\Database::getInstance();
    echo "[" . date('Y-m-d H:i:s') . "] ✅ اتصال قاعدة البيانات نشط\n";
    
    // تشغيل الجدولة
    $scheduler = new BackupScheduler();
    $result = $scheduler->runAutoScheduler();
    
    echo "[" . date('Y-m-d H:i:s') . "] ✅ نتيجة الجدولة: " . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] 💥 خطأ في المشغل التلقائي: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] ✅ اكتمل المشغل التلقائي بنجاح\n";
?>