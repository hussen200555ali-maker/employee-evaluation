<?php
/**
 * بند 13 — cron التنظيف اليومي (يُشغّل يوميًا من مخطّط المهام).
 *
 * حدود الحذف (النطاق المعتمد 2026-09-21 — لا شيء آخر في هذا الملف):
 *  1) csrf_tokens: الرموز المستخدمة أو المنتهية والأقدم من 24 ساعة
 *     (used = 1 OR expires_at < NOW()) AND created_at < NOW() - INTERVAL 24 HOUR
 *  2) login_attempts: الأقدم من 24 ساعة (attempted_at < NOW() - INTERVAL 24 HOUR)
 *  3) notifications: غير المقروءة الأقدم من 90 يومًا فقط
 *     (is_read = 0 AND created_at < NOW() - INTERVAL 90 DAY)
 *     — المُحذوف لا يمس الإشعارات المقروءة: تبقى سجلًّا للتدقيق (سياسة بند 15).
 *
 * تُسجَّل كل عملية تنظيف في audit_logs.
 * لا "إعادة مصادقة للأعمال الحساسة" ولا أي بند آخر — تلك مؤجلة إلى CD-005 (OOS #9).
 */

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/config/config.php';
require_once $rootDir . '/config/database.php';

use App\Core\Database;
use App\Core\Audit;

date_default_timezone_set('Asia/Riyadh');

try {
    $db = Database::getInstance()->getPdo();
} catch (\Throwable $e) {
    $msg = 'CRON_CLEANUP_FATAL ' . $e->getMessage();
    error_log($msg);
    fwrite(STDERR, $msg . PHP_EOL);
    exit(1);
}

$exit = 0;

/** تنفيذ حذف من جدول + تسجيل في audit_logs + سطر تقرير. */
function cleanupTable(string $action, string $table, string $rule, string $sql, array $params, string $label): int
{
    global $db, $exit;
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->rowCount();
        Audit::log($action, null, $table, ['deleted' => $count, 'rule' => $rule]);
        echo "$label: $count" . PHP_EOL;
        return $count;
    } catch (\Throwable $e) {
        error_log("$action " . $e->getMessage());
        fwrite(STDERR, "$action ERROR " . $e->getMessage() . PHP_EOL);
        $exit = 1;
        return -1;
    }
}

cleanupTable(
    'CRON_CLEANUP_CSRF_TOKENS',
    'csrf_tokens',
    'used = 1 OR expires_at < NOW(), older than 24h',
    "DELETE FROM csrf_tokens
     WHERE created_at < NOW() - INTERVAL 24 HOUR
       AND (used = 1 OR expires_at < NOW())",
    [],
    'cleanup csrf_tokens'
);

cleanupTable(
    'CRON_CLEANUP_LOGIN_ATTEMPTS',
    'login_attempts',
    'older than 24h',
    'DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 24 HOUR',
    [],
    'cleanup login_attempts'
);

cleanupTable(
    'CRON_CLEANUP_NOTIFICATIONS',
    'notifications',
    'unread older than 90 days; read kept for audit',
    'DELETE FROM notifications WHERE is_read = 0 AND created_at < NOW() - INTERVAL 90 DAY',
    [],
    'cleanup notifications'
);

exit($exit);