<?php
/**
 * PagesController
 * مسؤول عن عرض الصفحات العامة في الموقع.
 */
namespace App\Controllers; // <--- هذا هو التصحيح

use App\Core\Controller; 
class PagesController extends Controller { // لاحظ أنه يرث من الفئة الأساسية

    /**
     * دالة لعرض الصفحة الرئيسية.
     */
    public function home() {
        // بيانات سيتم تمريرها إلى الـ View
        $data = [
            'pageTitle' => 'الصفحة الرئيسية',
            'welcomeMessage' => 'أهلاً بك في نظام تقييم الموظفين'
        ];

        // استدعاء دالة view() التي ورثناها من الـ Controller الأساسي
        // لعرض ملف الـ View وتمرير البيانات إليه.
        $this->view('pages/home', $data);
    }

    /**
     * دالة لعرض صفحة "من نحن".
     */
    public function about() {
        $data = [
            'pageTitle' => 'من نحن',
            'companyName' => 'شركة الأنظمة المتقدمة'
        ];

        $this->view('pages/about', $data);
    }
}
