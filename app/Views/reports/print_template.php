<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($data['title']); ?></title>
    
    <!-- تحميل الموارد المحلية (Offline-First) -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/local-fonts.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/libs/fontawesome/css/all.min.css">
    <link href="<?php echo APP_URL; ?>/public/libs/bootstrap-5.1.3/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- استخدام ملف CSS للتقرير مباشرة -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/print-report.css">
    
    <style>
        /* تنسيقات خاصة بالطباعة */
        @media print {
            .print-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 10px;
                color: #666;
                padding: 5px 0;
                background: white;
                border-top: 1px solid #ddd;
            }
            
            /* التأكد من أن المحتوى لا يتداخل مع الفوتر */
            .report-body {
                margin-bottom: 40px !important;
            }
        }
        
        /* إخفاء الفوتر في العرض العادي */
        @media screen {
            .print-footer {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="report-header">
            <!-- القسم الأيمن -->
            <div class="header-side">
                <?php
                // 1. إنشاء مصفوفة لترجمة أسماء الأيام
                $days_in_arabic = [
                    'Saturday'  => 'السبت',
                    'Sunday'    => 'الأحد',
                    'Monday'    => 'الإثنين',
                    'Tuesday'   => 'الثلاثاء',
                    'Wednesday' => 'الأربعاء',
                    'Thursday'  => 'الخميس',
                    'Friday'    => 'الجمعة'
                ];

                // 2. الحصول على اسم اليوم باللغة الإنجليزية
                $day_in_english = date('l');

                // 3. ترجمة اسم اليوم إلى العربية
                $day_in_arabic = $days_in_arabic[$day_in_english];
                ?>

                <!-- 4. طباعة التاريخ واليوم بالتنسيق المطلوب -->
                <table class="info-table">
                    <tr>
                        <td>التاريخ</td>
                        <td>:</td>
                        <td><?php echo date('Y-m-d'); ?></td>
                    </tr>
                    <tr>
                        <td>الموافق</td>
                        <td>:</td>
                        <td>&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;</td>
                    </tr>
                    <tr>
                        <td>اليوم</td>
                        <td>:</td>
                        <td><?php echo $day_in_arabic; ?></td>
                    </tr>
                </table>
            </div>

            <!-- القسم الأوسط -->
            <div class="header-center">
                <p class="basmala">بسم الله الرحمن الرحيم</p>
                <img src="<?php echo APP_URL; ?>/public/assets/images/logo.png" alt="شعار الشركة" class="company-logo">
                <h1><?php echo htmlspecialchars($data['title']); ?></h1>
            </div>

            <!-- القسم الأيسر -->
            <div class="header-side">
                <span>المملكة العربية السعودية</span>
                <span>مدينة الرياض</span>
            </div>
        </div>

        <div class="report-body">
            <?php echo $data['reportHtml']; // طباعة HTML التقرير الذي تم إنشاؤه ?>
        </div>
        
      <!-- ================================================== -->
<!--          ===>  التذييل بالتصميم الجديد  <===         -->
<!-- ================================================== -->
<div class="print-footer">
    <span class="footer-item">
        تمت الطباعة بواسطة: <?php echo htmlspecialchars($data['printedBy']); ?>
    </span>
    <span class="footer-separator">|</span>
    <span class="footer-item">
        تاريخ الطباعة: <?php echo date('Y-m-d H:i:s'); ?>
    </span>
    <!-- الترقيم سيتم إضافته تلقائيًا هنا بواسطة CSS -->
</div>


    <!-- السكريبت الذي سيقوم بالطباعة تلقائياً -->
    <script>
    window.onload = function() {
        // إضافة تأخير بسيط كشبكة أمان أخيرة لضمان اكتمال العرض
        setTimeout(function() {
            window.print();
        }, 500); // تأخير نصف ثانية
    };
    </script>
</body>
</html>