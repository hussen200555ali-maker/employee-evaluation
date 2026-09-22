<?php
/**
 * فئة التطبيق الرئيسية (App)
 * مسؤولة عن بدء تشغيل التطبيق وتنسيق العمل بين المكونات.
 */
namespace App\Core;
class App {
    // متغير لتخزين كائن الـ Router.
    protected $router;

    /**
     * دالة البناء (Constructor) التي يتم تشغيلها عند إنشاء نسخة من App.
     */
    public function __construct() {
        // استدعاء دالة run() لبدء تشغيل التطبيق.
        $this->run();
    }

    /**
     * الدالة الرئيسية لتشغيل التطبيق.
     */
    public function run() {
        try {
            // تحميل المسارات من ملف routes.php (سنقوم بإنشائه لاحقاً).
            // Router::load() سيعيد لنا كائن Router مملوء بالمسارات.
            $this->router = Router::load('../routes/web.php');

            // الحصول على الرابط المطلوب من متغير 'url' الذي أرسله ملف .htaccess.
            // trim() تزيل أي مسافات أو '/' من بداية ونهاية الرابط.
          //  $uri = trim($_GET['url'] ?? '', '/');
// في ملف: app/Core/App.php

// ▼▼▼ هذا هو الإصلاح الحاسم والنهائي ▼▼▼
// 1. احصل على الرابط من 'url'.
$uri = $_GET['url'] ?? '';
// 2. افصل الرابط عند علامة الاستفهام '?' للحصول على الجزء النظيف فقط.
$uri = explode('?', $uri)[0];
// 3. قم بإزالة أي مسافات أو '/' من البداية والنهاية.
$uri = trim($uri, '/');

            // الحصول على نوع الطلب (GET أو POST).
            $requestType = $_SERVER['REQUEST_METHOD'];

            // توجيه الطلب باستخدام الـ Router.
            // الـ Router سيقوم بتشغيل الـ Controller المناسب ويعيد النتيجة.
            $this->router->direct($uri, $requestType);

        } catch (Exception $e) {
            // في حالة حدوث أي استثناء (Exception) من الـ Router (مثل صفحة غير موجودة).
            
            // التحقق إذا كنا في وضع التصحيح (Debug Mode).
            if (DEBUG_MODE) {
                // إذا نعم، اعرض رسالة الخطأ التفصيلية للمطور.
                echo '<h1>Error</h1>';
                echo '<p><b>Message:</b> ' . $e->getMessage() . '</p>';
                echo '<p><b>File:</b> ' . $e->getFile() . '</p>';
                echo '<p><b>Line:</b> ' . $e->getLine() . '</p>';
            } else {
                // إذا كنا في وضع الإنتاج، اعرض صفحة خطأ عامة للمستخدم.
                // يمكنك هنا تضمين ملف view لصفحة 404.
                http_response_code(404 );
                echo "<h1>404 Not Found</h1>";
                // require 'app/views/errors/404.php';
            }
        }
    }
}
