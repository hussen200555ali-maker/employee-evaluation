<?php
// ملف: app/Views/analysis/report_template_pdf_new.php
// هذا هو القالب النهائي المصمم خصيصًا للطباعة الاحترافية
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?php echo htmlspecialchars($reportTitle ?? 'تقرير الأداء'); ?></title>

    <!-- تضمين ملف CSS المحسن -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/pdf-styles.css">

    <style>
        /* تعريف الخطوط المتوافقة مع العربية */
        @font-face {
            font-family: 'Cairo';
            font-style: normal;
            font-weight: 400;
            src: url('<?php echo APP_URL; ?>/public/fonts/Cairo-Regular.ttf') format('truetype');
        }
        
        @font-face {
            font-family: 'Cairo';
            font-style: normal;
            font-weight: bold;
            src: url('<?php echo APP_URL; ?>/public/fonts/Cairo-Bold.ttf') format('truetype');
        }
        
        /* استخدام Cairo كخط افتراضي */
        body {
            font-family: 'Cairo', sans-serif !important;
        }
        
        /* أنماط إضافية مخصصة للتقارير الرسمية */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(0, 0, 0, 0.05);
            z-index: -1;
            font-weight: bold;
            pointer-events: none;
        }

        .report-content {
            padding: 20px;
        }

        .report-summary {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }

        .report-summary h3 {
            margin-top: 0;
            color: #0d6efd;
        }

        .key-metrics {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .metric-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            width: 30%;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
        }

        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 5px;
        }

        .metric-label {
            font-size: 12px;
            color: #6c757d;
        }

        .recommendations {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }

        .recommendations h3 {
            color: #0d6efd;
            margin-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }

        .recommendation-item {
            margin-bottom: 15px;
            padding-left: 25px;
            position: relative;
        }

        .recommendation-item:before {
            content: "•";
            position: absolute;
            left: 10px;
            color: #0d6efd;
            font-weight: bold;
        }

        @page {
            margin: 2cm;
            size: A4;
        }
    </style>
</head>
<body>

    <!-- ترويسة التقرير -->
    <div class="report-header">
        <div class="company-info">
            <div>
                <h2><?php echo htmlspecialchars($companyName ?? 'نظام تقييم الموظفين'); ?></h2>
                <p>المملكة العربية السعودية</p>
            </div>
            <div class="text-center">
                <img src="<?php echo APP_URL; ?>/public/assets/images/logo.png" alt="شعار الشركة" class="company-logo">
                <div class="report-title"><?php echo htmlspecialchars($reportTitle); ?></div>
            </div>
            <div>
                <p>تاريخ التقرير: <?php echo date('Y-m-d'); ?></p>
                <p>الموافق: <?php echo date('d/m/Y'); ?></p>
            </div>
        </div>
    </div>

    <!-- محتوى التقرير -->
    <div class="report-content">
        <div class="watermark">تقرار سري</div>

        <!-- ملخص التقرير -->
        <?php if (isset($analysis['summary']) && !empty($analysis['summary'])): ?>
        <div class="report-summary">
            <h3>ملخص التقرير</h3>
            <p><?php echo nl2br(htmlspecialchars($analysis['summary'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- المؤشرات الرئيسية -->
        <?php if (isset($analysis['key_metrics']) && !empty($analysis['key_metrics'])): ?>
        <div class="key-metrics">
            <?php foreach ($analysis['key_metrics'] as $metric): ?>
            <div class="metric-card">
                <div class="metric-value"><?php echo $metric['value']; ?></div>
                <div class="metric-label"><?php echo htmlspecialchars($metric['label']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- نقاط القوة -->
        <?php if (isset($analysis['classified_performance']) && !empty($analysis['classified_performance'])): ?>
            <?php if (!empty($analysis['classified_performance']['strong'])): ?>
            <div class="section">
                <div class="section-title">نقاط القوة (أداء ≥ 85%)</div>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>المعيار الرئيسي</th>
                            <th>الكفاءة (القسم الفرعي)</th>
                            <th style="width: 20%; text-align: center;">متوسط الدرجة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis['classified_performance']['strong'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['criterion_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['section_name']); ?></td>
                                <td style="text-align: center;"><span class="badge badge-success"><?php echo $item['score']; ?>%</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- الأداء الجيد -->
            <?php if (!empty($analysis['classified_performance']['medium'])): ?>
            <div class="section">
                <div class="section-title">الأداء الجيد (50% - 84.9%)</div>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>المعيار الرئيسي</th>
                            <th>الكفاءة (القسم الفرعي)</th>
                            <th style="width: 20%; text-align: center;">متوسط الدرجة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis['classified_performance']['medium'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['criterion_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['section_name']); ?></td>
                                <td style="text-align: center;"><span class="badge badge-primary"><?php echo $item['score']; ?>%</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- مجالات التحسين -->
            <?php if (!empty($analysis['classified_performance']['weak'])): ?>
            <div class="section">
                <div class="section-title">مجالات التحسين (أداء < 50%)</div>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>المعيار الرئيسي</th>
                            <th>الكفاءة (القسم الفرعي)</th>
                            <th style="width: 20%; text-align: center;">متوسط الدرجة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis['classified_performance']['weak'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['criterion_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['section_name']); ?></td>
                                <td style="text-align: center;"><span class="badge badge-danger"><?php echo $item['score']; ?>%</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- التوصيات -->
        <?php if (isset($analysis['recommendations']) && !empty($analysis['recommendations'])): ?>
        <div class="recommendations">
            <h3>التوصيات المقترحة</h3>
            <?php foreach ($analysis['recommendations'] as $recommendation): ?>
            <div class="recommendation-item"><?php echo nl2br(htmlspecialchars($recommendation)); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- تذييل التقرير -->
    <div class="report-footer">
        <p>تم إنشاء هذا التقرير بواسطة نظام تقييم الموظفين automatiquement - صفحة {PAGENO} من {nbpg}</p>
    </div>

</body>
</html>