<?php
// ملف: app/Views/analysis/report_enhanced_view.php
// هذا هو القالم المصمم لعرض التقرير بواجهة محسنة
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($reportTitle ?? 'تقرير الأداء'); ?></title>

    <!-- تضمين ملفات CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/style.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/print-professional.css">

    <!-- تضمين Font Awesome للأيقونات -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/libs/fontawesome/css/all.min.css">

    <style>
        /* أنماط إضافية للعرض المحسن */
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .report-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-button i {
            margin-left: 8px;
        }

        .btn-print {
            background-color: #28a745;
            color: white;
        }

        .btn-print:hover {
            background-color: #218838;
        }

        .btn-pdf {
            background-color: #dc3545;
            color: white;
        }

        .btn-pdf:hover {
            background-color: #c82333;
        }

        .btn-excel {
            background-color: #28a745;
            color: white;
        }

        .btn-excel:hover {
            background-color: #218838;
        }

        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }

        .performance-chart {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .performance-chart h3 {
            color: #0d6efd;
            margin-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }

        .chart-placeholder {
            width: 100%;
            height: 250px;
            background-color: #e9ecef;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 16px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .report-container {
                padding: 0;
            }

            .report-actions {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- شاشة التحميل -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner"></div>
    </div>

    <!-- حاوية التقرير -->
    <div class="report-container">
        <!-- أزرار الإجراءات -->
        <div class="report-actions no-print">
            <div>
                <a href="<?php echo APP_URL; ?>/analysis" class="action-button btn-print">
                    <i class="fas fa-arrow-left"></i>
                    العودة للتحليلات
                </a>
            </div>
            <div>
                <button onclick="window.print()" class="action-button btn-print">
                    <i class="fas fa-print"></i>
                    طباعة التقرير
                </button>
                <a href="<?php echo APP_URL; ?>/analysis/exportPdf?level=<?php echo $_GET['level']; ?>&target_id=<?php echo $_GET['target_id']; ?>&scope=<?php echo $_GET['scope']; ?>" 
                   class="action-button btn-pdf" onclick="showLoading()">
                    <i class="fas fa-file-pdf"></i>
                    تصدير PDF
                </a>
                <a href="<?php echo APP_URL; ?>/analysis/export/excel?level=<?php echo $_GET['level'] ?? ''; ?>&target_id=<?php echo $_GET['target_id'] ?? ''; ?>" 
                   class="action-button btn-excel">
                    <i class="fas fa-file-excel"></i>
                    تصدير Excel
                </a>
                <a href="<?php echo APP_URL; ?>/analysis/export/excel-employees" 
                   class="action-button btn-excel">
                    <i class="fas fa-users"></i>
                    قائمة الموظفين Excel
                </a>
            </div>
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

            <!-- مخطط الأداء العام -->
            <?php if (isset($analysis['performance_trend']) && !empty($analysis['performance_trend'])): ?>
            <div class="performance-chart">
                <h3>مخطط تطور الأداء</h3>
                <div class="chart-container">
                    <div class="chart-placeholder">
                        <i class="fas fa-chart-line fa-3x"></i>
                        <p>مخطط تطور الأداء سيتم عرضه هنا</p>
                    </div>
                </div>
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

            <!-- ملخص التقرير -->
            <?php if (isset($analysis['summary']) && !empty($analysis['summary'])): ?>
            <div class="report-summary">
                <h3>ملخص التقرير</h3>
                <p><?php echo nl2br(htmlspecialchars($analysis['summary'])); ?></p>
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
    </div>

    <!-- تذييل التقرير -->
    <div class="report-footer">
        <p>تم إنشاء هذا التقرير بواسطة نظام تقييم الموظفين automatiquement - صفحة {PAGENO} من {nbpg}</p>
    </div>

    <!-- تضمين ملف JavaScript المحسن -->
    <script src="<?php echo APP_URL; ?>/public/js/pdf-export-enhancer.js"></script>

    <script>
        // دالة لإظهار شاشة التحميل
        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'block';

            // إخفاء شاشة التحميل بعد 3 ثوانٍ
            setTimeout(function() {
                document.getElementById('loadingSpinner').style.display = 'none';
            }, 3000);
        }
    </script>

</body>
</html>