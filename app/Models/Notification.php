<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * إشعارات المستخدم (بند 15).
 *
 * لا يحتوي أي DELETE — سياسة الاحتفاظ:
 * تُحذف فقط الإشعارات غير المقروءة الأقدم من 90 يومًا ضمن cron التنظيف لبند 13
 * (is_read=0 AND created_at < NOW() - INTERVAL 90 DAY)؛ المقروءة تبقى للتدقيق.
 * الإشعارات القديمة (قبل بند 14) قابلة للعرض حتى لو كانت related_url فارغة.
 */
class Notification extends Model {

    /**
     * قائمة إشعارات المستخدم (الأحدث أولًا).
     * @return array<int, array{id:int,title:string,message:string,type:string,is_read:int,related_url:?string,created_at:string}>
     */
    public function forUser(int $userId): array {
        return $this->fetchAssoc(
            'SELECT id, title, message, type, is_read, related_url, created_at
             FROM notifications
             WHERE user_id = :uid
             ORDER BY created_at DESC, id DESC',
            [':uid' => $userId]
        );
    }

    /** عدد الإشعارات غير المقروءة. */
    public function unreadCount(int $userId): int {
        $rows = $this->fetchAssoc(
            'SELECT COUNT(*) AS c FROM notifications
             WHERE user_id = :uid AND is_read = 0',
            [':uid' => $userId]
        );
        return (int)($rows[0]['c'] ?? 0);
    }

    /**
     * أحدث إشعارات المستخدم (لشاشة الجرس).
     * @return array<int, array{id:int,title:string,message:string,is_read:int,related_url:?string,created_at:string}>
     */
    public function recentForUser(int $userId, int $limit = 5): array {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT id, title, message, is_read, related_url, created_at
             FROM notifications
             WHERE user_id = :uid
             ORDER BY created_at DESC, id DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** تعليم إشعار واحد كمقروء (مسار مُسند للمستخدم — لا يقبل قراءة إشعارات الغير). */
    public function markRead(int $id, int $userId): void {
        $stmt = $this->db->getPdo()->prepare(
            'UPDATE notifications SET is_read = 1
             WHERE id = :id AND user_id = :uid'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /** تعليم كل إشعارات المستخدم كمقروءة. */
    public function markAllRead(int $userId): void {
        $stmt = $this->db->getPdo()->prepare(
            'UPDATE notifications SET is_read = 1
             WHERE user_id = :uid'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /** تنفيذ استعلام جلب بمصفوفة مسماة — نمط DB المعتمد في المشروع. */
    private function fetchAssoc(string $sql, array $params = []): array {
        $stmt = $this->db->getPdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}