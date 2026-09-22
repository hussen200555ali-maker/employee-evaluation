<?php 
// لا نستخدم الهيدر والفوتر العامين، لأن هذه الصفحة مصممة كوثيقة مستقلة.
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير تقييم ورشة عمل: <?php echo htmlspecialchars($data['evaluation']->workshop_title); ?></title>
    
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/local-fonts.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/libs/fontawesome/css/all.min.css">
    <link href="<?php echo APP_URL; ?>/public/libs/bootstrap-5.1.3/css/bootstrap.min.css" rel="stylesheet">

<style>
    :root {
        /* تعريف الألوان الأساسية لـ Bootstrap 5.2 لتسهيل استخدامها */
        --bs-blue: #0d6efd;
        --bs-green: #198754;
        --bs-secondary: #6c757d;
        --bs-dark: #212529;
        --bs-light-gray: #f1f3f5;
        --bs-header-bg: #f8f9fa;
        --bs-border-color: #dee2e6;
        --bs-dark-header: #343a40;
        --bs-white: #fff;
    }
    body {
        background-color: #e9ecef;
        font-family: 'Cairo', sans-serif;
        font-size: 11pt;
    }
    .page-container {
        width: 210mm;
        min-height: 297mm;
        margin: 1.5rem auto;
        padding: 1.5cm;
        background-color: var(--bs-white);
        border: 1px solid #ccc;
        box-shadow: 0 0 20px rgba(0,0,0,0.15);
        display: flex;
        flex-direction: column;
    }
    
    /* (بقية الأنماط الأساسية تبقى كما هي) */
    .report-header { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 100px; border-bottom: 2px solid #444; padding-bottom: 15px; margin-bottom: 30px; }
    .report-header .header-side.right { text-align: center; }
    .report-header .header-side.left { text-align: right; }
    .report-header .header-center { text-align: center; }
    .report-header .basmala { font-size: 1.2rem; font-weight: bold; margin: 0; }
    .report-header .company-logo { max-width: 80px; margin: 5px auto; }
    .report-header h1 { margin: 0; font-size: 1.4rem; color: #000; font-weight: 700; }
    .report-header .header-side { font-size: 0.9rem; color: #555; font-weight: 600; }
    .section-title { font-size: 14pt; font-weight: 600; color: var(--bs-dark-header); border-bottom: 2px solid var(--bs-border-color); padding-bottom: 8px; margin-bottom: 20px; }
    .result-card { border: 1px solid var(--bs-border-color); border-radius: 8px; text-align: center; display: flex; flex-direction: column; height: 100%; }
    .result-card .card-header { font-weight: 600; padding: 10px; background-color: var(--bs-header-bg); border-bottom: 1px solid var(--bs-border-color); }
    .result-card .card-body { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; justify-content: center; }
    .result-card .score { font-size: 30pt; font-weight: 700; line-height: 1.1; }
    .result-card .score-label { font-size: 9pt; color: var(--bs-secondary); }
    .notes-section { margin-top: 30px; }
    .page-footer { margin-top: auto; text-align: center; font-size: 9pt; color: #888; border-top: 1px solid #ccc; padding-top: 10px; }
    .print-footer { display: none; }
    .action-buttons { position: fixed; top: 20px; left: 20px; z-index: 100; display: flex; flex-direction: column; gap: 10px; }

    /* ===> الحل الاحترافي والنهائي للطباعة <=== */
    @media print {
        /* 1. القاعدة الأساسية لإجبار طباعة الألوان */
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;

        /* 2. تهيئة الصفحة وإزالة أي هوامش أو تأثيرات غير مرغوبة */
        body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: var(--bs-white) !important;
        }
        .page-container {
            width: 100% !important;
            min-height: initial !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }
        .action-buttons, .page-footer {
            display: none !important;
        }

        /* 3. إعادة تعريف صريحة وقوية لكل الألوان والخلفيات المستخدمة */
        .text-primary { color: var(--bs-blue) !important; }
        .text-success { color: var(--bs-green) !important; }
        .text-secondary { color: var(--bs-secondary) !important; }
        .text-dark { color: var(--bs-dark) !important; }
        .text-muted { color: var(--bs-secondary) !important; }
        
        .bg-light {
            background-color: var(--bs-header-bg) !important;
        }

        .result-card {
            border: 1px solid var(--bs-border-color) !important;
            break-inside: avoid; /* منع تقسيم العنصر بين صفحتين */
        }
        .result-card .card-header {
            background-color: var(--bs-header-bg) !important;
            border-bottom: 1px solid var(--bs-border-color) !important;
        }
        
        /* استهداف البطاقة الأخيرة (النتيجة النهائية) بشكل دقيق */
        .result-card[style*="background-color"] {
             background-color: var(--bs-light-gray) !important;
        }
        .result-card .card-header[style*="background-color"] {
            background-color: var(--bs-dark-header) !important;
            color: var(--bs-white) !important;
        }

        /* 4. ضمان عمل تخطيط Bootstrap */
        .row { display: flex !important; flex-wrap: wrap !important; }
        .col-md-3 { flex: 0 0 25% !important; max-width: 25% !important; }
        .col-md-6 { flex: 0 0 50% !important; max-width: 50% !important; }
        .col-12 { flex: 0 0 100% !important; max-width: 100% !important; }

        /* 5. إظهار وتنسيق تذييل الطباعة */
        .print-footer {
            display: block !important;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #666 !important;
            padding: 5px 0;
        }
    }
</style>

</head>
<body>

    <div class="action-buttons">
        <button onclick="window.print()" class="btn btn-primary btn-lg shadow"><i class="fas fa-print me-2"></i> طباعة</button>
        <a href="<?php echo APP_URL; ?>/training/workshops" class="btn btn-secondary shadow"><i class="fas fa-arrow-right me-2"></i> عودة</a>
    </div>

    <div class="page-container">
        <header class="report-header">
            <!-- ===> 3. إصلاح تنسيق الترويسة باستخدام   
 <=== -->
 
            <div class="header-side right">
                <span style="display:block">المملكة العربية السعودية</span>  

                <span>مدينة الرياض</span>
            </div>
            <div class="header-center">
                <p class="basmala">بسم الله الرحمن الرحيم</p>
                <img src="<?php echo APP_URL; ?>/public/assets/images/logo.png" alt="شعار الشركة" class="company-logo">
                <h1>تقرير تقييم ورشة عمل</h1>
            </div>
            <div class="header-side left">
                <span style="display:block">التاريخ: <?php echo date('Y-m-d'); ?></span>  

                <span>اليوم: <?php echo ['Saturday' => 'السبت', 'Sunday' => 'الأحد', 'Monday' => 'الإثنين', 'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس', 'Friday' => 'الجمعة'][date('l')]; ?></span>
            </div>
        </header>

        <main>
            <section>
                <h2 class="section-title">بيانات الورشة</h2>
                <div class="row bg-light p-3 rounded border mb-4">
                    <div class="col-12 mb-2">
                        <small class="text-muted">عنوان الورشة</small>
                        <h4 class="mb-0"><?php echo htmlspecialchars($data['evaluation']->workshop_title); ?></h4>
                    </div>
                    <div class="col-md-3"><small class="text-muted">الفرع:</small><h6><?php echo htmlspecialchars($data['evaluation']->branch_name ?? 'N/A'); ?></h6></div>
                    <div class="col-md-3"><small class="text-muted">القسم:</small><h6><?php echo htmlspecialchars($data['evaluation']->department_name ?? 'N/A'); ?></h6></div>
                    <div class="col-md-3"><small class="text-muted">تاريخ البدء:</small><h6><?php echo $data['evaluation']->start_date; ?></h6></div>
                    <div class="col-md-3"><small class="text-muted">تاريخ الانتهاء:</small><h6><?php echo $data['evaluation']->end_date; ?></h6></div>
                </div>
            </section>

            <section>
                <h2 class="section-title">نتائج التقييم</h2>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="result-card h-100">
                            <div class="card-header">الحضور المفترض</div>
                            <div class="card-body"><div class="score text-secondary"><?php echo $data['evaluation']->expected_attendees; ?></div></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="result-card h-100">
                            <div class="card-header">الحضور الفعلي</div>
                            <div class="card-body"><div class="score text-secondary"><?php echo $data['evaluation']->actual_attendees; ?></div></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="result-card h-100">
                            <div class="card-header">نتيجة الحضور <span class="score-label">(الوزن: 80%)</span></div>
                            <div class="card-body"><div class="score text-primary"><?php echo number_format($data['evaluation']->attendance_score, 1); ?>%</div></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="result-card h-100">
                            <div class="card-header">نتيجة انتظام البرنامج <span class="score-label">(الوزن: 20%)</span></div>
                            <div class="card-body"><div class="score text-success"><?php echo number_format($data['evaluation']->program_regularity_weighted_score, 1); ?>%</div></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="result-card h-100" style="background-color: #f1f3f5;">
                            <div class="card-header" style="background-color: #343a40; color: #fff;">النتيجة النهائية للورشة</div>
                            <div class="card-body"><div class="score text-dark" style="font-size: 40pt;"><?php echo number_format($data['evaluation']->final_score, 1); ?>%</div></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="notes-section">
                <h2 class="section-title" style="border-bottom: none; margin-bottom: 10px;">ملاحظات وتوصيات المقيم</h2>
                <p class="mb-0 p-3 bg-light border rounded"><?php echo !empty($data['evaluation']->notes) ? nl2br(htmlspecialchars($data['evaluation']->notes)) : '<span class="text-muted">-- لا توجد ملاحظات مسجلة --</span>'; ?></p>
            </section>
        </main>

        <footer class="page-footer">
            هذا التقرير تم إنشاؤه بواسطة نظام تقييم الموظفين &copy; <?php echo date('Y'); ?>
        </footer>
        
        <div class="print-footer page-footer">
            تمت الطباعة بواسطة: <?php echo htmlspecialchars($data['printedBy']); ?> | تاريخ الطباعة: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>

</body>
</html>
