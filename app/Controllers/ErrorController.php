<?php
// نحدد الـ Namespace ليعكس مكان الملف.
namespace App\Controllers;

// استيراد الفئة الأساسية للـ Controller.
use App\Core\Controller;

/**
 * ErrorController
 * مسؤول عن عرض صفحات الأخطاء المخصصة (مثل 404, 403).
 */
class ErrorController extends Controller {

    /**
     * يعرض صفحة "404 Not Found" (الصفحة غير موجودة).
     *
     * @param string $uri - الرابط الذي تسبب في الخطأ (اختياري).
     */
    public function notFound($uri = '') {
        // 1. تعيين كود الاستجابة HTTP إلى 404.
        http_response_code(404 );

        // 2. تجهيز البيانات التي سيتم تمريرها إلى الـ View.
        $data = [
            'pageTitle' => '404 - الصفحة غير موجودة',
            'uri' => htmlspecialchars($uri) // نمرر الرابط لعرضه في رسالة الخطأ.
        ];

        // 3. عرض الـ View المخصص لخطأ 404.
        $this->view('errors/404', $data);
    }

    /**
     * يعرض صفحة "403 Forbidden" (الوصول ممنوع).
     *
     * @param string $permission - اسم الصلاحية التي كانت مطلوبة (اختياري).
     */
    public function forbidden($permission = '') {
        // 1. تعيين كود الاستجابة HTTP إلى 403.
        http_response_code(403 );

        // 2. تجهيز البيانات للـ View.
        $data = [
            'pageTitle' => '403 - وصول ممنوع',
            'permission' => htmlspecialchars($permission) // نمرر اسم الصلاحية.
        ];

        // 3. عرض الـ View المخصص لخطأ 403.
        $this->view('errors/403', $data);
    }
}
