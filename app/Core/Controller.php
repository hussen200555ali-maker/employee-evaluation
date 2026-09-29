<?php
/**
 * فئة التحكم الأساسية (Base Controller)
 * جميع وحدات التحكم الأخرى سترث من هذه الفئة.
 */

namespace App\Core;


class Controller {
    /**
     * دالة لتحميل وعرض ملف View.
     *
     * @param string $view - اسم ملف الـ View (مثل 'dashboard/index').
     * @param array $data - مصفوفة من البيانات لتمريرها إلى الـ View.
     */
    protected function view($view, $data = []) {
        // استخراج عناصر مصفوفة البيانات إلى متغيرات منفصلة.
        // مثال: إذا كانت $data = ['name' => 'Ali']، سيتم إنشاء متغير $name = 'Ali'.
        extract($data);

        // تكوين المسار الكامل لملف الـ View.
        // OOS-15: كان المسار نسبيًا لمجلد التشغيل ("../app/Views/...")، فكان يفشل
        // خارجه ويُسقط كل طلب يعرض واجهة. الآن يُبنى من APP_ROOT (معرّف مطلق).
        $viewFile = APP_ROOT . "/app/Views/{$view}.php";

        // التحقق من وجود ملف الـ View.
        if (is_file($viewFile)) {
            // إذا كان موجوداً، قم بتضمينه لعرضه.
            require_once $viewFile;
        } else {
            // إذا لم يكن موجوداً، اعرض رسالة خطأ.
            die("View '{$view}' does not exist.");
        }
    }

      /**
     * ==================================================
     *           ===>  أضف الدالة الجديدة هنا  <===
     * ==================================================
     * 
     * دالة مساعدة لإعادة توجيه المستخدم إلى رابط آخر.
     *
     * @param string $path المسار الذي سيتم إعادة التوجيه إليه (مثال: '/users').
     */
    protected function redirect($path) {
        header('Location: ' . APP_URL . $path);
        exit();
    }

       /**
     * ==================================================
     *           ===>  الدالة الجديدة والمهمة هنا  <===
     * ==================================================
     * 
     * يُرجع استجابة بصيغة JSON مع كود الحالة المناسب.
     * هذه الدالة ضرورية لواجهات برمجة التطبيقات (APIs) التي تتواصل مع JavaScript.
     *
     * @param mixed $data البيانات المراد إرسالها.
     * @param int $statusCode كود حالة HTTP (مثال: 200 للنجاح, 400 أو 500 للخطأ).
     */
    protected function json($data, $statusCode = 200) {
        // منع أي مخرجات HTML غير مرغوب فيها قد تسبب خطأ في تحليل JSON.
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        // 1. إعداد الهيدر لإعلام المتصفح أن الاستجابة هي JSON بالترميز الصحيح.
        header('Content-Type: application/json; charset=utf-8');
        
        // 2. تعيين كود حالة HTTP (مهم جدًا للتعامل مع الأخطاء في JavaScript).
        http_response_code($statusCode );
        
        // 3. طباعة البيانات بصيغة JSON مع ضمان عرض الحروف العربية بشكل صحيح.
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        // 4. إيقاف التنفيذ فورًا لضمان عدم إرسال أي مخرجات إضافية.
        exit();
    }
}
