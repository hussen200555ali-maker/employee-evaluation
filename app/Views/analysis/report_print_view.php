<?php
// ملف: app/Views/analysis/report_print_view.php
// هذا هو القالم المصمم لعرض التقرير في وضع الطباعة المباشرة
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($reportTitle ?? 'تقرير الأداء'); ?></title>

    <!-- تضمين ملف CSS المحسن للطباعة -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/print-professional.css">

    <style>
        /* أنماط إضافية للطباعة المباشرة */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .report-content {
                padding: 0;
            }
        }

        /* إخفاء العناصر غير المرغوب في العرض العادي */
        .no-screen {
            display: none;
        }
    </style>
</head>
<body>

    <!-- زر للعودة للصفحة السابقة -->
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.history.back()" style="background-color: #0d6efd; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">العودة</button>
        <button onclick="window.print()" style="background-color: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-right: 10px;">طباعة التقرير</button>
    </div>

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