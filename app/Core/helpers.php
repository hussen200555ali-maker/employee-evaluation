<?php
/**
 * المسار: app/Core/helpers.php
 * يحتوي هذا الملف على دوال مساعدة عامة يمكن استدعاؤها من أي مكان في التطبيق.
 */

/**
 * دالة مساعدة لإضافة حقل CSRF مخفي في النماذج
 * @return string كود HTML لحقل CSRF المخفي
 */
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        $token = \App\Core\Auth::generateCsrfToken();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('convertToHijri')) {
    /**
     * تحويل التاريخ الميلادي إلى هجري (النسخة المصححة والنهائية).
     * 
     * @param string|null $gregorianDateString التاريخ الميلادي.
     * @return object|null كائن يحتوي على تفاصيل التاريخ الهجري.
     */
    function convertToHijri($gregorianDateString) {
        try {
            if (empty($gregorianDateString) || ($timestamp = strtotime($gregorianDateString)) === false) {
                return null;
            }

            // تحديد الرمز الدولي الدقيق لتقويم أم القرى
            $locale = 'ar-SA-u-ca-islamic-umalqura';
            
            // تحديد المنطقة الزمنية لضمان دقة التحويل
            $timezone = 'Asia/Riyadh';

            // --- إنشاء المنسقات مع تحديد التقويم والمنطقة الزمنية بشكل صريح ---

            // 1. منسق للحصول على التاريخ كنص كامل (مثال: السبت، ٠٢ شعبان ١٤٤٧)
            $fullDateFormatter = new \IntlDateFormatter($locale, \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, $timezone, \IntlDateFormatter::TRADITIONAL);

            // 2. منسق للحصول على أجزاء التاريخ منفصلة
            $dayFormatter = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $timezone, \IntlDateFormatter::TRADITIONAL, 'd');
            $monthFormatter = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $timezone, \IntlDateFormatter::TRADITIONAL, 'M');
            $monthNameFormatter = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $timezone, \IntlDateFormatter::TRADITIONAL, 'MMMM');
            $yearFormatter = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $timezone, \IntlDateFormatter::TRADITIONAL, 'y');
            
            // 3. منسق لاسم اليوم (يجب أن يستخدم نفس التقويم لضمان التطابق)
            $weekdayFormatter = new \IntlDateFormatter($locale, \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, $timezone, \IntlDateFormatter::TRADITIONAL, 'EEEE');

            // بناء الكائن النهائي بالبيانات الهجرية الصحيحة
            return (object) [
                'full_date'  => $fullDateFormatter->format($timestamp),
                'day'        => $dayFormatter->format($timestamp),
                'month'      => $monthFormatter->format($timestamp),
                'month_name' => $monthNameFormatter->format($timestamp),
                'year'       => $yearFormatter->format($timestamp),
                'weekday'    => $weekdayFormatter->format($timestamp),
            ];

        } catch (\Exception $e) {
            error_log('Hijri Conversion Error: ' . $e->getMessage());
            return null;
        }
    }
}
