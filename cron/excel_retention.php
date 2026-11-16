<?php
/**
 * CD-043 P5: retention cleanup for evaluation_import_batches (disabled by default).
 *
 * هذا السكربت معطّل افتراضياً. لتفعيله، أضف/حدّث الإعداد:
 *   INSERT INTO settings (setting_key, setting_value) VALUES ('excel.retention_enabled', '1')
 *   ON DUPLICATE KEY UPDATE setting_value='1';
 * أو через لوحة الإدارة (إن وُجدت). بدون هذا الإعداد، يخرج السكربت فوراً (0 حذف).
 *
 * الحذف (عند التفعيل):
 *   - batches completed أقدم من retention_batches_days (365) يوم.
 *   - batches rejected/failed أقدم من retention_rejected_batches_days (30) يوم.
 *   - audit_logs أقدم من retention_audit_months (12) شهر (اختياري).
 */

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/config/config.php';
require_once $rootDir . '/config/database.php';

use App\Core\Database;

date_default_timezone_set('Asia/Riyadh');

try {
    $db = Database::getInstance()->getPdo();
} catch (\Throwable $e) {
    fwrite(STDERR, 'FATAL ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Gate: disabled by default.
$enabled = 0;
try {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'excel.retention_enabled' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($row) { $enabled = (int) $row['setting_value']; }
} catch (\Throwable $e) { $enabled = 0; }

if ($enabled !== 1) {
    echo "excel retention disabled (excel.retention_enabled != 1). Nothing to do.\n";
    exit(0);
}

// Read retention days
$days = 365; $rejectedDays = 30;
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('retention_batches_days','retention_rejected_batches_days')");
    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
        if ($r['setting_key'] === 'retention_batches_days') $days = (int) $r['setting_value'];
        if ($r['setting_key'] === 'retention_rejected_batches_days') $rejectedDays = (int) $r['setting_value'];
    }
} catch (\Throwable $e) { /* defaults */ }

$total = 0;
try {
    $stmt = $db->prepare("DELETE FROM evaluation_import_batches WHERE status='completed' AND created_at < (NOW() - INTERVAL :d DAY)");
    $stmt->execute(['d' => $days]);
    $total += $stmt->rowCount();
    echo "deleted completed batches older than {$days} days: " . $stmt->rowCount() . PHP_EOL;
} catch (\Throwable $e) { fwrite(STDERR, $e->getMessage() . PHP_EOL); }

try {
    $stmt = $db->prepare("DELETE FROM evaluation_import_batches WHERE status IN ('previewed','failed') AND created_at < (NOW() - INTERVAL :d DAY)");
    $stmt->execute(['d' => $rejectedDays]);
    $total += $stmt->rowCount();
    echo "deleted rejected/previewed batches older than {$rejectedDays} days: " . $stmt->rowCount() . PHP_EOL;
} catch (\Throwable $e) { fwrite(STDERR, $e->getMessage() . PHP_EOL); }

echo "total deleted: $total\n";
exit(0);
