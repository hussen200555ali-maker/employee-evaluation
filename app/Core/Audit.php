<?php
namespace App\Core;

/**
 * فئة Audit
 * فئة مساعدة لتسجيل الإجراءات المهمة في النظام.
 */
class Audit {

    /**
     * يسجل إجراءً في جدول audit_logs.
     *
     * @param string $action - وصف الإجراء (مثل 'EMPLOYEE_CREATE').
     * @param int|null $entity_id - معرف السجل الذي تأثر (مثل ID الموظف الجديد).
     * @param string|null $entity_type - اسم الجدول الذي تأثر (مثل 'employees').
     * @param array|null $details - مصفوفة من التفاصيل الإضافية.
     */
    public static function log($action, $entity_id = null, $entity_type = null, $details = null) {
        try {
            // الحصول على اتصال قاعدة البيانات.
            $db = Database::getInstance();

            // تحضير الاستعلام.
            $db->query('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) 
                        VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address)');

            // ربط القيم.
            $db->bind(':user_id', Auth::id()); // معرف المستخدم الحالي.
            $db->bind(':action', $action);
            $db->bind(':entity_type', $entity_type);
            $db->bind(':entity_id', $entity_id);
            // تحويل مصفوفة التفاصيل إلى JSON لتخزينها بسهولة.
            $db->bind(':details', $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null);
            $db->bind(':ip_address', $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN');

            // تنفيذ الاستعلام.
            $db->execute();

        } catch (\Exception $e) {
            // في حالة فشل تسجيل الإجراء، لا توقف التطبيق،
            // ولكن يمكنك تسجيل الخطأ في ملف logs على الخادم.
            error_log('Audit Log Failed: ' . $e->getMessage());
        }
    }
}
