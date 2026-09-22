<?php
// test_chart.php

// تحديد المسار الجذري للمشروع
define('APP_ROOT', dirname(__DIR__));

// استدعاء ملف التحميل التلقائي لـ Composer
require_once APP_ROOT . '/vendor/autoload.php';

// استخدام الكلاسات من مساحات الأسماء الصحيحة
use pChart\pData;
use pChart\pChart;

echo "<h1>اختبار إنشاء صورة بسيط باستخدام pChart</h1>";

try {
    // 1. إعداد البيانات
    $DataSet = new pData();
    $DataSet->AddPoint([10, 20, 15], "Serie1"); // استخدام AddPoint
    $DataSet->AddSerie("Serie1");

    // 2. إعداد كائن الرسم
    $Chart = new pChart(700, 230);
    $fontPath = APP_ROOT . '/vendor/wp-statistics/pchart/src/Fonts/tahoma.ttf';
    $Chart->setFontProperties($fontPath, 8);
    $Chart->setGraphArea(60, 30, 680, 200);
    
    // 3. رسم الخلفية والمقياس
    $Chart->drawFilledRectangle(0, 0, 700, 230, 255, 255, 255);
    $Chart->drawScale($DataSet->GetData(), $DataSet->GetDataDescription(), SCALE_NORMAL, 150, 150, 150, TRUE, 0, 2);
    $Chart->drawGrid(4, TRUE, 230, 230, 230, 50);

    // 4. رسم المخطط
    $Chart->drawBarGraph($DataSet->GetData(), $DataSet->GetDataDescription(), TRUE);

    // 5. حفظ الصورة
    $filePath = APP_ROOT . '/public/uploads/test_image.png';
    $Chart->Render($filePath);

    // 6. عرض النتيجة
    if (file_exists($filePath)) {
        echo "<h2 style='color:green;'>نجاح! تم إنشاء الصورة بنجاح.</h2>";
        echo "<p>المسار: " . $filePath . "</p>";
        echo "<p>هذه هي الصورة التي تم إنشاؤها:</p>";
        echo '<img src="uploads/test_image.png" alt="Test Chart">';
    } else {
        echo "<h2 style='color:red;'>فشل! لم يتم إنشاء ملف الصورة.</h2>";
        echo "<p>تحقق من صلاحيات الكتابة على مجلد 'public/uploads'.</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color:red;'>حدث خطأ فادح:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
