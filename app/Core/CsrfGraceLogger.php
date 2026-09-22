<?php
namespace App\Core;

use App\Models\Audit;

/**
 * مسجل فترة السماح لـ CSRF
 * يسجل استخدام فترة السماح، يرسل إشعارات للمشرفين، ويغلقها تلقائياً بعد 4 ساعات
 */
class CsrfGraceLogger {

    private static $sessionKey = 'csrf_grace_started_at';

    /**
     * بدء فترة السماح (تُستدعى عند أول تفعيل)
     */
    public static function startGracePeriod(): void {
        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = time();
            self::log('CSRF Grace Period: بدأت فترة السماح (4 ساعات)');
            self::notifyAdmins('تم تفعيل فترة سماح CSRF', 'فترة السماح لـ CSRF مدتها 4 ساعات. سيتم إغلاقها تلقائياً.');
        }
    }

    /**
     * هل فترة السماح نشطة؟
     */
    public static function isGraceActive(): bool {
        if (!isset($_SESSION[self::$sessionKey])) {
            return false;
        }
        $elapsed = time() - $_SESSION[self::$sessionKey];
        return $elapsed < CSRF_GRACE_PERIOD_MINUTES * 60;
    }

    /**
     * الوقت المتبقي من فترة السماح بالثواني
     */
    public static function getRemainingSeconds(): int {
        if (!isset($_SESSION[self::$sessionKey])) {
            return 0;
        }
        $elapsed = time() - $_SESSION[self::$sessionKey];
        $remaining = (CSRF_GRACE_PERIOD_MINUTES * 60) - $elapsed;
        return max(0, $remaining);
    }

    /**
     * تسجيل استخدام فترة السماح (يُستدعى عند كل طلب POST بدون توكن صحيح)
     */
    public static function logGraceUsage(string $action, string $detail = ''): void {
        if (!self::isGraceActive()) {
            return;
        }
        $remaining = self::getRemainingSeconds();
        $remainingMin = round($remaining / 60);
        self::log("CSRF Grace Usage: {$action} | {$detail} | متبقي {$remainingMin} دقيقة");

        if ($remainingMin > 0 && $remainingMin <= 15) {
            self::notifyAdmins(
                'فترة سماح CSRF على وشك الانتهاء',
                "متبقي {$remainingMin} دقيقة قبل الإغلاق التلقائي."
            );
        }
    }

    /**
     * إغلاق فترة السماح يدوياً
     */
    public static function disableGracePeriod(): void {
        if (isset($_SESSION[self::$sessionKey])) {
            self::log('CSRF Grace Period: تم إغلاق فترة السماح');
            self::notifyAdmins('تم إغلاق فترة سماح CSRF', 'فترة السماح لـ CSRF تم إغلاقها. التحقق الإلزامي نشط الآن.');
            unset($_SESSION[self::$sessionKey]);
        }
    }

    /**
     * التحقق الدوري: يُستدعى عند كل طلب للتحقق من انتهاء المدة
     */
    public static function checkAndAutoClose(): void {
        if (!isset($_SESSION[self::$sessionKey])) {
            return;
        }
        if (!self::isGraceActive()) {
            self::log('CSRF Grace Period: انتهت المدة - إغلاق تلقائي');
            self::notifyAdmins('انتهت فترة سماح CSRF تلقائياً', 'تم إغلاق فترة السماح بعد انقضاء 4 ساعات. التحقق الإلزامي نشط الآن.');
            unset($_SESSION[self::$sessionKey]);
        }
    }

    // ======================================================================
    // دوال مساعدة خاصة
    // ======================================================================

    private static function log(string $message): void {
        $db = Database::getInstance();
        try {
            $db->query("INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address, created_at)
                        VALUES (:user_id, :action, 'csrf_grace', :details, :ip, NOW())");
            $db->bind(':user_id', Auth::id());
            $db->bind(':action', $message);
            $db->bind(':details', $message);
            $db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $db->execute();
        } catch (\Exception $e) {
            error_log('CsrfGraceLogger: فشل تسجيل الحدث - ' . $e->getMessage());
        }
    }

    private static function notifyAdmins(string $title, string $message): void {
        $roleIds = Auth::adminRoleIds();
        if ($roleIds === []) {
            return;
        }
        $db = Database::getInstance();
        try {
            $ph = [];
            foreach (array_keys($roleIds) as $i) {
                $ph[] = ':rid' . $i;
            }
            $in = implode(',', $ph);
            $db->query("INSERT INTO notifications (user_id, title, message, type, created_at)
                        SELECT id, :title, :message, 'warning', NOW()
                        FROM users
                        WHERE status = 'active'
                        AND id IN (
                            SELECT ur.user_id FROM user_roles ur WHERE ur.role_id IN ($in)
                        )");
            $db->bind(':title', $title);
            $db->bind(':message', $message);
            foreach ($roleIds as $i => $rid) {
                $db->bind(':rid' . $i, $rid);
            }
            $db->execute();
        } catch (\Exception $e) {
            error_log('CsrfGraceLogger: فشل إرسال إشعار - ' . $e->getMessage());
        }
    }
}
