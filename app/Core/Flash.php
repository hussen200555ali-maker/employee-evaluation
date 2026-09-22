<?php
// نحدد الـ Namespace ليعكس مكان الملف في هيكل المشروع.
namespace App\Core;

/**
 * فئة رسائل Flash
 * 
 * مسؤولة عن إنشاء وعرض رسائل مؤقتة للمستخدم (مثل "تم الحفظ بنجاح").
 * الرسالة يتم تخزينها في الجلسة (Session) وتُعرض مرة واحدة فقط ثم يتم حذفها.
 */
class Flash {

    /**
     * مفتاح الجلسة الذي سيتم تخزين الرسائل تحته.
     * @var string
     */
    const FLASH_KEY = 'flash_messages';

    /**
     * تقوم بتعيين رسالة flash جديدة.
     *
     * @param string $message - نص الرسالة المراد عرضها.
     * @param string $type - نوع الرسالة (success, error, info, warning) لتحديد لونها في CSS.
     */
    public static function set($message, $type = 'success') {
        if (!isset($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = [];
        }
        $_SESSION[self::FLASH_KEY][] = ['message' => $message, 'type' => $type];
    }

    /**
     * تقوم بجلب كل رسائل flash المخزنة حالياً وحذفها من الجلسة.
     *
     * @return array - مصفوفة تحتوي على الرسائل، أو مصفوفة فارغة.
     */
    public static function get() {
        if (isset($_SESSION[self::FLASH_KEY])) {
            $messages = $_SESSION[self::FLASH_KEY];
            unset($_SESSION[self::FLASH_KEY]);
            return $messages;
        }
        return [];
    }

    /**
     * تقوم بعرض كل رسائل flash مباشرة كـ HTML.
     * هذه هي الدالة التي كان يستدعيها الهيدر.
     */
    public static function display() {
        $messages = self::get();
        foreach ($messages as $flash) {
            echo '<div class="alert-message ' . htmlspecialchars($flash['type']) . '">
                    <i class="fas fa-info-circle"></i> ' . htmlspecialchars($flash['message']) . '
                  </div>';
        }
    }
}
