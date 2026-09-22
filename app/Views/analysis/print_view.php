<?php 
// ملف: app/Views/analysis/print_view.php (النسخة المحسنة مع التعديلات المطلوبة)

// ... (الدوال المساعدة ومعالجة البيانات تبقى كما هي) ...
if (!function_exists('render_performance_table_print')) {
    function render_performance_table_print($title, $data, $icon, $color_class, $isUnifiedDetailedView) {
    ?>
    <div class="performance-table-section">
        <div class="table-header bg-<?php echo $color_class; ?>-soft">
            <h5 class="mb-0 text-center"><i class="fas <?php echo $icon; ?> me-2"></i> <?php echo $title; ?></h5>
        </div>
        <?php if (empty($data)): ?>
            <div class="text-center text-muted p-3"><p class="mb-0">لا توجد كفاءات ضمن هذا النطاق.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle repeating-header">
                    <thead class="table-light">
                        <?php if ($isUnifiedDetailedView): ?>
                            <tr>
                                <th style="width: 12%; font-size: 8pt;" class="text-center">المعيار الرئيسي</th>
                                <th style="width: 18%; font-size: 8pt;" class="text-center">الكفاءة</th>
                                <th class="text-center" style="width: 12%; font-size: 8pt;">تاريخ التقييم</th>
                                <th class="text-center" style="width: 15%; font-size: 8pt;">اسم الاستمارة</th>
                                <th class="text-center" style="width: 15%; font-size: 8pt;">النتيجة المدخلة</th>
                                <th class="text-center" style="width: 15%; font-size: 8pt;">الدرجة</th>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <th style="width: 25%; font-size: 8pt;" class="text-center">المعيار الرئيسي</th>
                                <th style="width: 45%; font-size: 8pt;" class="text-center">الكفاءة (القسم الفرعي)</th>
                                <th class="text-center" style="width: 15%; font-size: 8pt;">النتيجة المدخلة</th>
                                <th class="text-center" style="width: 15%; font-size: 8pt;">متوسط الدرجة</th>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $sectionId => $competency): ?>
                            <?php foreach ($competency['history'] as $index => $history_item): ?>
                                <tr class="table-row">
                                    <?php if ($index === 0): ?>
                                        <td rowspan="<?php echo $competency['is_detailed'] ? count($competency['history']) + 1 : 1; ?>" class="small text-center">
                                            <?php echo htmlspecialchars($competency['criterion_name']); ?>
                                        </td>
                                        <td rowspan="<?php echo $competency['is_detailed'] ? count($competency['history']) + 1 : 1; ?>" class="small text-center">
                                            <?php echo htmlspecialchars($competency['section_name']); ?>
                                        </td>
                                    <?php endif; ?>

                                    <?php if ($isUnifiedDetailedView): ?>
                                        <td class="text-center small"><?php echo date('Y-m-d', strtotime($history_item['date'])); ?></td>
                                        <td class="text-center small"><?php echo htmlspecialchars($history_item['template_name']); ?></td>
                                    <?php endif; ?>

                                    <td class="text-center small">
                                        <?php echo format_raw_value_print($history_item['raw_value'], $history_item['evaluation_type'], $history_item['evaluation_options']); ?>
                                    </td>
                                    <td class="text-center small">
                                        <?php if ($isUnifiedDetailedView): ?>
                                            <?php echo $history_item['score']; ?>%
                                        <?php else: ?>
                                            <span class="badge fs-6 rounded-pill bg-<?php echo $color_class; ?>">
                                                <?php echo $competency['average_score']; ?>%
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if ($competency['is_detailed'] && $isUnifiedDetailedView): ?>
                                <tr class="table-light table-row">
                                    <td colspan="3" class="text-end fw-bold small">متوسط الأداء العام:</td>
                                    <td class="text-center fw-bold">
                                        <span class="badge fs-6 rounded-pill bg-<?php echo $color_class; ?>">
                                            <?php echo $competency['average_score']; ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php 
    }
}

if (!function_exists('format_raw_value_print')) {
    function format_raw_value_print($rawValue, $evalType, $optionsJson) {
        switch ($evalType) {
            case 'grade':
                $options = json_decode($optionsJson);
                if (is_array($options)) {
                    foreach ($options as $option) {
                        if (isset($option->value) && $option->value == $rawValue) {
                            return htmlspecialchars($option->label);
                        }
                    }
                }
                return htmlspecialchars($rawValue);
            case 'numeric':
                return htmlspecialchars(round($rawValue, 2));
            case 'percentage':
                return htmlspecialchars(round($rawValue, 2)) . '%';
            case 'boolean':
                return $rawValue == 1 ? 'نعم' : 'لا';
            default:
                return htmlspecialchars($rawValue);
        }
    }
}

$barChartData = ['labels' => [], 'scores' => [], 'backgroundColors' => []];
if (isset($analysis['performance_chart_data']) && !empty($analysis['performance_chart_data']['labels'])) {
    $barChartData['labels'] = $analysis['performance_chart_data']['labels'];
    $barChartData['scores'] = $analysis['performance_chart_data']['scores'];
    foreach ($analysis['performance_chart_data']['scores'] as $score) {
        if ($score >= 85) {
            $barChartData['backgroundColors'][] = 'rgba(25, 135, 84, 0.8)';
        } elseif ($score < 50) {
            $barChartData['backgroundColors'][] = 'rgba(220, 53, 69, 0.8)';
        } else {
            $barChartData['backgroundColors'][] = 'rgba(13, 110, 253, 0.8)';
        }
    }
}

$isAnyDetailed = false;
if (isset($analysis) && isset($analysis['classified_performance'])) {
    $allCompetencies = array_merge(
        $analysis['classified_performance']['strong'],
        $analysis['classified_performance']['medium'],
        $analysis['classified_performance']['weak']
    );
    foreach ($allCompetencies as $competency) {
        if ($competency['is_detailed']) {
            $isAnyDetailed = true;
            break;
        }
    }
}

// الحصول على إحصائيات الأداء
$totalEvaluations = $performanceStats->total_evaluations ?? 0;
$chartNote = $totalEvaluations <= 1 
    ? "يعرض هذا المخطط <strong>جميع الكفاءات الفرعية</strong> من التقييم الواحد."
    : "يعرض هذا المخطط <strong>متوسط الأداء العام</strong> للكفاءات المتشابهة عبر جميع التقييمات.";

// تحديد إذا كان هناك بيانات للمخططات
$hasChartData = !empty($barChartData['labels']);
$hasTrendData = isset($performanceHistory) && count($performanceHistory) > 1;
$hasCompetencyTrendData = isset($performanceStats) && $performanceStats->total_evaluations > 1 && 
                         isset($detailedTrend['trend_chart_data']) && !empty($detailedTrend['trend_chart_data']['datasets']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle ?? 'طباعة التقرير'); ?></title>
    
    <link href="<?php echo APP_URL; ?>/public/libs/bootstrap-5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/libs/fontawesome/css/all.min.css">
    
    <style>
        /* --- خطوط مخصصة --- */
        @font-face {
            font-family: 'Cairo';
            src: url('<?php echo APP_URL; ?>/public/fonts/Cairo-Regular.ttf') format('truetype');
            font-weight: 400;
        }
        @font-face {
            font-family: 'Cairo';
            src: url('<?php echo APP_URL; ?>/public/fonts/Cairo-Bold.ttf') format('truetype');
            font-weight: 700;
        }

        /* --- أنماط عامة --- */
        body {
            font-family: 'Cairo', 'Arial', sans-serif !important;
            background-color: #fff !important;
            color: #000 !important;
            margin: 0;
            padding: 0;
            font-size: 9pt;
            line-height: 1.3;
        }

        /* --- إعدادات الطباعة الاحترافية للوضع الأفقي --- */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                box-shadow: none !important;
                text-shadow: none !important;
            }
            
            body {
                font-family: 'Cairo', 'Arial', sans-serif !important;
                font-size: 9pt !important;
                background: #fff !important;
                color: #000 !important;
                margin: 0 !important;
                padding: 0 !important;
                counter-reset: page;
                width: 100%;
                height: 100%;
            }
            
            .no-print {
                display: none !important;
            }
            
            /* تحسين الترويسة والتذييل للوضع الأفقي */
            .print-header {
                display: block !important;
                width: 100%;
                border-bottom: 1px solid #000;
                padding-bottom: 3mm;
                margin-bottom: 5mm;
            }
            
            .print-footer {
                display: table !important;
                position: fixed;
                bottom: 0;
                width: calc(100% - 20mm);
                left: 10mm;
                right: 10mm;
                background: white !important;
                z-index: 1000;
                font-size: 7pt;
                padding: 1mm 0;
                border-top: 1px solid #000;
                height: 5mm;
                max-height: 5mm;
            }
            
            .print-footer .page-number::after {
                content: counter(page);
            }

            .print-footer .page-total::after {
                content: counter(pages);
            }
          
            @page {
                size: A4 landscape; /* الوضع الأفقي */
                margin: 10mm 15mm 15mm 15mm;
                counter-increment: page;
            }
          
            /* المحتوى الرئيسي للوضع الأفقي */
            #report-content {
                margin-top: 5mm !important;
                margin-bottom: 5mm !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: none !important;
            }
            
            /* تحسين تدفق المحتوى للوضع الأفقي */
            .content-section {
                margin-bottom: 6px;
                page-break-inside: avoid;
            }
            
            /* الجداول والتقسيم الذكي للوضع الأفقي */
            .performance-table-section {
                page-break-inside: auto;
                margin-bottom: 8px;
            }
            
            .table-header {
                background-color: #f0f0f0 !important;
                color: #000 !important;
                padding: 3px 6px !important;
                font-size: 8pt !important;
                border: 1px solid #ccc;
                page-break-after: avoid;
            }
            
            .table-header.bg-success-soft { 
                background-color: #d1e7dd !important; 
                color: #0f5132 !important; 
            }
            .table-header.bg-primary-soft { 
                background-color: #cce5ff !important; 
                color: #004085 !important; 
            }
            .table-header.bg-danger-soft  { 
                background-color: #f8d7da !important; 
                color: #721c24 !important; 
            }
            
            /* الجداول مع تكرار العناوين للوضع الأفقي */
            .repeating-header {
                page-break-inside: auto;
                border: 1px solid #ccc;
            }
            
            .repeating-header thead {
                display: table-header-group;
            }
            
            .table-row {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            .table {
                font-size: 7pt !important;
                margin-bottom: 0 !important;
                border-collapse: collapse !important;
                width: 100%;
            }
            
            .table th, .table td {
                padding: 2px 3px !important;
                border: 1px solid #999 !important;
                background-color: white !important;
                text-align: center !important;
                vertical-align: middle !important;
            }
            
            .table thead th {
                background-color: #e0e0e0 !important;
                color: #000 !important;
                font-weight: bold;
                border-bottom: 2px solid #666 !important;
                text-align: center !important;
            }
            
            /* تحسين المخططات للوضع الأفقي */
            .chart-container, #donutChartContainer {
                page-break-inside: avoid;
                height: auto !important;
            }
            
            canvas {
                max-width: 100% !important;
                border: 1px solid #ddd !important;
            }
            
            /* تحسين الشارات للوضع الأفقي */
            .badge {
                font-size: 6pt !important;
                padding: 1px 3px !important;
            }
            
            /* تحسين البطاقات للوضع الأفقي */
            .card {
                border: 1px solid #ccc !important;
                page-break-inside: avoid;
                margin-bottom: 6px !important;
                background: white !important;
            }
            
            .card-header {
                background-color: #f0f0f0 !important;
                color: #000 !important;
                padding: 3px 6px !important;
                font-size: 8pt !important;
                text-align: center !important;
            }

            /* تحسين الأكورديون للطباعة */
            .accordion-collapse {
                display: block !important;
            }

            /* تحسين تخطيط الصفوف للوضع الأفقي */
            .row {
                margin-left: -5px;
                margin-right: -5px;
            }
            
            .col-md-4, .col-md-5, .col-md-6, .col-md-7, .col-md-8, .col-md-12 {
                padding-left: 5px;
                padding-right: 5px;
            }
        }

        /* --- أنماط العرض على الشاشة --- */
        @media screen {
            .report-container {
                background: #fff;
                margin: 20px auto;
                padding: 20px;
                max-width: 1400px;
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
            }
            
            .print-header, .print-footer {
                display: none;
            }
            
            .performance-table-section {
                border: 1px solid #dee2e6;
                border-radius: 4px;
                margin-bottom: 15px;
                overflow: hidden;
            }
            
            .table-header {
                padding: 6px 10px;
                font-weight: bold;
                text-align: center;
            }
        }

        /* --- تنسيقات إضافية للطباعة الأفقي --- */
        .employee-info-section {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 6px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #333;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }
        
        .info-table th, .info-table td {
            border: 1px solid #ccc;
            padding: 3px 4px;
            text-align: center !important;
            vertical-align: middle;
        }
        
        .info-table thead th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center !important;
        }
        
        .performance-summary-section {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            text-align: center;
        }
        
        .summary-item {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 6px;
            text-align: center;
        }
        
        .summary-value {
            font-size: 12pt;
            font-weight: bold;
            line-height: 1.1;
        }
        
        .summary-label {
            font-size: 6pt;
            color: #666;
            margin-top: 2px;
        }
        
        .historical-section {
            margin-top: 10px;
        }
        
        .evaluation-card-history {
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        
        .evaluation-card-header {
            background-color: #e9ecef;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 7pt;
            border-bottom: 1px solid #ccc;
            text-align: center;
        }
        
        .evaluation-card-body {
            padding: 6px;
        }
        
        .chart-title {
            text-align: center;
            font-size: 7pt;
            font-weight: bold;
            margin-bottom: 4px;
        }
        
        /* تحسين الجداول التفصيلية للوضع الأفقي */
        .detail-table-container {
            max-height: none !important;
            overflow: visible !important;
        }
        
        .detail-table {
            font-size: 6pt !important;
        }
        
        .detail-table th, .detail-table td {
            padding: 1px 2px !important;
            text-align: center !important;
            vertical-align: middle !important;
        }

        /* أنماط وسيلة الإيضاح للمخطط الدائري للوضع الأفقي */
        .legend-category {
            page-break-inside: avoid;
        }
        
        .legend-category ul {
            padding-right: 0;
            text-align: center;
        }

        .legend-category li {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            font-size: 0.8rem;
            justify-content: center;
        }

        .legend-color-box {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            margin-left: 6px;
            flex-shrink: 0;
        }

        /* تخطيط أفقي محسن */
        .horizontal-layout {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .horizontal-item {
            flex: 1;
            min-width: 200px;
        }

        /* تحسين عرض المخططات في الوضع الأفقي */
        .chart-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        
        .chart-col {
            flex: 1;
            min-width: 300px;
            margin-bottom: 8px;
        }

        /* تنسيقات جديدة للمحاذاة المركزية */
        .center-content {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .table-center {
            margin: 0 auto;
            text-align: center;
        }

        /* تنسيق محسّن للسجل التاريخي */
        .historical-details-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
        }

        .historical-chart {
            flex: 1;
            max-width: 45%;
        }

        .historical-table {
            flex: 1;
            max-width: 55%;
        }

        /* رسالة بديلة عند عدم وجود مخططات */
        .no-chart-message {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
            border: 1px dashed #ccc;
            border-radius: 4px;
            background: #f9f9f9;
        }
    </style>
</head>
<body>

    <!-- زر الطباعة (للعرض على الشاشة فقط) -->
    <div class="text-center my-3 no-print">
        <button onclick="printReport()" class="btn btn-primary btn-lg">
            <i class="fas fa-print"></i> طباعة التقرير (وضع أفقي)
        </button>
        <button onclick="window.close()" class="btn btn-secondary btn-lg ms-2">
            <i class="fas fa-times"></i> إغلاق
        </button>
    </div>

    <!-- حاوية التقرير -->
    <div class="report-container">
        <!-- الترويسة (للطباعة فقط) -->
        <div class="print-header">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="width: 33%; text-align: right; font-size: 6pt; vertical-align: top;">
                        المملكة العربية السعودية<br>
                        اسم الشركة
                    </td>
                    <td style="width: 34%; text-align: center; vertical-align: top;">
                        <h3 style="margin: 0; font-size: 8pt; font-weight: bold;">تقرير الأداء الفردي</h3>
                    </td>
                    <td style="width: 33%; text-align: left; font-size: 6pt; vertical-align: top;">
                        تاريخ التقرير: <?php echo date('Y-m-d'); ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- المحتوى الفعلي للتقرير -->
        <div id="report-content">
            <!-- عنوان التقرير -->
            <div class="content-section">
                <h2 class="text-center mb-2" style="font-size: 11pt; font-weight: bold;">
                    <?php echo htmlspecialchars($reportTitle ?? 'تقرير الأداء الفردي'); ?>
                </h2>
            </div>
            
            <!-- قسم معلومات الموظف -->
            <div class="employee-info-section content-section">
                <h4 class="section-title">1. معلومات الموظف</h4>
                <table class="info-table table-center">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> الموظف</th>
                            <th><i class="fas fa-id-badge"></i> الرقم الوظيفي</th>
                            <th><i class="fas fa-briefcase"></i> المنصب</th>
                            <th><i class="fas fa-sitemap"></i> القسم</th>
                            <th><i class="fas fa-building"></i> الفرع</th>
                            <th><i class="fas fa-user-tie"></i> المدير المباشر</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($employee->full_name ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($employee->emp_code ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($employee->position_title ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($employee->department_name ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($employee->branch_name ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($employee->manager_name ?? 'غير محدد'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- قسم ملخص الأداء -->
            <div class="performance-summary-section content-section">
                <h4 class="section-title">2. ملخص الأداء</h4>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-value text-primary"><?php echo round($latestEvaluation->final_score ?? 0, 1); ?>%</div>
                        <div class="summary-label">نتيجة آخر تقييم</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value text-success"><?php echo round($performanceStats->average_score ?? 0, 1); ?>%</div>
                        <div class="summary-label">متوسط الأداء العام</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?php echo $performanceStats->total_evaluations ?? 0; ?></div>
                        <div class="summary-label">عدد التقييمات المكتملة</div>
                    </div>
                </div>
                
                <!-- مخطط تطور الأداء العام -->
                <?php if ($hasTrendData): ?>
                <div class="chart-row">
                    <div class="chart-col">
                        <div class="card mb-2 content-section">
                            <div class="card-header">
                                <h5 class="mb-0 text-center"><i class="fas fa-chart-line text-info me-2"></i>تطور الأداء العام</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="height: 150px;">
                                    <canvas id="performanceTrendChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- صف المخططات (ترتيب أفقي) -->
            <?php if ($hasChartData): ?>
            <div class="chart-row">
                <!-- المخطط الدائري -->
                <div class="chart-col">
                    <div class="card mb-2 content-section">
                        <div class="card-header">
                            <h5 class="mb-0 text-center"><i class="fas fa-chart-pie me-2"></i>ملخص أداء الكفاءات</h5>
                        </div>
                        <div class="card-body">
                            <p class="chart-card-note text-center small text-muted mb-2">
                                <strong>ملاحظة:</strong> <?php echo $chartNote; ?>
                            </p>
                            
                            <div class="d-flex justify-content-center mb-2">
                                <div id="donutChartContainer" style="max-width: 250px; width: 100%; height: 150px;">
                                    <canvas id="performanceBarChart"></canvas>
                                </div>
                            </div>

                            <!-- وسيلة الإيضاح للمخطط الدائري -->
                            <div class="content-section">
                                <h6 class="section-title text-center">وسيلة إيضاح أداء الكفاءات</h6>
                                <div id="custom-legend-container" class="row g-2 text-center">
                                    <div class="col-md-4">
                                        <div class="legend-category p-2 border rounded h-100">
                                            <h6 class="legend-title text-success" style="font-size: 8pt;"><i class="fas fa-star me-1"></i> نقاط القوة (≥ 80%)</h6>
                                            <hr class="my-1">
                                            <ul id="legend-strong" class="list-unstyled mb-0" style="font-size: 7pt;"></ul>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="legend-category p-2 border rounded h-100">
                                            <h6 class="legend-title text-primary" style="font-size: 8pt;"><i class="fas fa-check-circle me-1"></i> أداء متوسط (60-79%)</h6>
                                            <hr class="my-1">
                                            <ul id="legend-good" class="list-unstyled mb-0" style="font-size: 7pt;"></ul>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="legend-category p-2 border rounded h-100">
                                            <h6 class="legend-title text-danger" style="font-size: 8pt;"><i class="fas fa-flag me-1"></i> جوانب للتطوير (< 60%)</h6>
                                            <hr class="my-1">
                                            <ul id="legend-weak" class="list-unstyled mb-0" style="font-size: 7pt;"></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="content-section">
                <div class="no-chart-message">
                    <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                    <strong>لا توجد بيانات كافية لعرض المخططات</strong><br>
                    <small>يحتاج النظام إلى بيانات تقييم كافية لتوليد المخططات التحليلية</small>
                </div>
            </div>
            <?php endif; ?>

            <!-- مخطط تطور الكفاءات عبر الزمن -->
            <?php if ($hasCompetencyTrendData): ?>
            <div class="content-section">
                <div id="trend-chart-card" class="card shadow-sm mb-2">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 print-title text-center"><i class="fas fa-chart-line text-info me-2"></i>مخطط تطور أداء الكفاءات عبر الزمن</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2 print-description text-muted small text-center">
                            يوضح هذا المخطط كيف تغير مستوى أداء الموظف في كل كفاءة على حدة مع مرور الوقت، مما يساعد على رصد التطور والتحسن.
                        </p>
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="competencyTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- جداول نقاط القوة والضعف -->
            <?php if (isset($analysis['classified_performance'])): ?>
            <div class="content-section">
                <h4 class="section-title">3. تحليل الأداء التفصيلي</h4>
                
                <!-- نقاط القوة -->
                <div class="performance-table-section">
                    <?php 
                    render_performance_table_print(
                        'نقاط القوة (أداء ≥ 80%)', 
                        $analysis['classified_performance']['strong'], 
                        'fa-star', 
                        'success',
                        $isAnyDetailed
                    );
                    ?>
                </div>

                <!-- الأداء الجيد -->
                <div class="performance-table-section">
                    <?php
                    render_performance_table_print(
                        'الأداء المتوسط (60% - 79.9%)', 
                        $analysis['classified_performance']['medium'], 
                        'fa-check-circle', 
                        'primary',
                        $isAnyDetailed
                    );
                    ?>
                </div>

                <!-- جوانب التطوير -->
                <div class="performance-table-section">
                    <?php
                    render_performance_table_print(
                        'جوانب تحتاج إلى تطوير (أداء < 60%)', 
                        $analysis['classified_performance']['weak'], 
                        'fa-flag', 
                        'danger',
                        $isAnyDetailed
                    );
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- السجل التاريخي المفصل -->
            <?php if (isset($detailedTrend['history_table_data']) && !empty($detailedTrend['history_table_data'])): ?>
            <div class="historical-section content-section">
                <h4 class="section-title">4. سجل التقييمات التاريخي المفصل</h4>
                
                <?php 
                $reversed_history = array_reverse($detailedTrend['history_table_data'], true);
                $counter = 0;
                foreach ($reversed_history as $eval_id => $eval_data): 
                    $counter++;
                    $evaluation_date = isset($eval_data['evaluation_date']) ? date('Y-m-d', strtotime($eval_data['evaluation_date'])) : 'غير محدد';
                    $final_score = isset($eval_data['final_score']) ? round($eval_data['final_score'], 1) : 0;
                ?>
                <div class="evaluation-card-history">
                    <div class="evaluation-card-header">
                        <i class="fas fa-calendar-alt"></i> تقييم بتاريخ: <?php echo $evaluation_date; ?>
                        <span class="badge bg-primary">النتيجة النهائية: <?php echo $final_score; ?>%</span>
                    </div>
                    <div class="evaluation-card-body">
                        <div class="historical-details-container">
                            <div class="historical-chart">
                                <h6 class="chart-title">مخطط الأداء للكفاءات</h6>
                                <div class="chart-container">
                                    <canvas id="radarChart-<?php echo $eval_id; ?>" height="150"></canvas>
                                </div>
                                <div class="d-flex justify-content-center align-items-center mt-1 small text-muted flex-wrap">
                                    <div class="d-flex align-items-center mx-1 my-1">
                                        <span style="width: 8px; height: 8px; background-color: rgba(25, 135, 84, 0.3); border: 1px solid rgba(25, 135, 84, 0.5); border-radius: 2px; margin-left: 2px;"></span>
                                        <span style="font-size: 6pt;">أداء متميز (≥ 85%)</span>
                                    </div>
                                    <div class="d-flex align-items-center mx-1 my-1">
                                        <span style="width: 8px; height: 8px; background-color: rgba(255, 193, 7, 0.3); border: 1px solid rgba(255, 193, 7, 0.5); border-radius: 2px; margin-left: 2px;"></span>
                                        <span style="font-size: 6pt;">أداء جيد (50-84.9%)</span>
                                    </div>
                                </div>
                            </div>
                            <div class="historical-table">
                                <h6 class="chart-title">التفاصيل الرقمية</h6>
                                <div class="detail-table-container">
                                    <table class="table table-bordered table-sm mb-0 detail-table repeating-header table-center">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="font-size: 5pt; width: 20%;">المعيار</th>
                                                <th style="font-size: 5pt; width: 35%;">الكفاءة</th>
                                                <th style="font-size: 5pt; width: 15%;">النتيجة المدخلة</th>
                                                <th style="font-size: 5pt; width: 15%;">الدرجة الموحدة</th>
                                                <th style="font-size: 5pt; width: 15%;">نتيجة المعيار</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php 
                                        if (isset($eval_data['criteria'])):
                                            foreach ($eval_data['criteria'] as $criterion_name => $criterion_data): 
                                                $sections_count = count($criterion_data['sections']);
                                        ?>
                                                <?php foreach ($criterion_data['sections'] as $index => $section): ?>
                                                    <tr>
                                                        <?php if ($index === 0): ?>
                                                            <td rowspan="<?php echo $sections_count; ?>" class="align-middle bg-light fw-bold" style="font-size: 6pt; font-weight: 600;">
                                                                <?php echo htmlspecialchars($criterion_name); ?>
                                                            </td>
                                                        <?php endif; ?>
                                                        
                                                        <td style="font-size: 6pt; font-weight: 600;"><?php echo htmlspecialchars($section['section_name']); ?></td>
                                                        
                                                        <td style="font-size: 6pt; font-weight: 600;">
                                                            <?php
                                                                $rawValue = $section['raw_value'];
                                                                $evalType = $section['evaluation_type'];
                                                                $options = json_decode($section['evaluation_options']);

                                                                switch ($evalType) {
                                                                    case 'grade':
                                                                        $displayValue = $rawValue;
                                                                        if (is_array($options)) {
                                                                            foreach ($options as $option) {
                                                                                if (isset($option->value) && $option->value == $rawValue) {
                                                                                    $displayValue = $option->label;
                                                                                    break;
                                                                                }
                                                                            }
                                                                        }
                                                                        echo htmlspecialchars($displayValue);
                                                                        break;
                                                                    
                                                                    case 'numeric':
                                                                        echo htmlspecialchars($rawValue);
                                                                        break;

                                                                    case 'percentage':
                                                                        echo htmlspecialchars($rawValue) . '%';
                                                                        break;
                                                                    
                                                                    case 'boolean':
                                                                        echo $rawValue == 1 ? 'نعم' : 'لا';
                                                                        break;

                                                                    default:
                                                                        echo htmlspecialchars($rawValue);
                                                                        break;
                                                                }
                                                            ?>
                                                        </td>
                                                        
                                                        <td style="font-size: 6pt; font-weight: 600;"><?php echo $section['score']; ?>%</td>
                                                        
                                                        <?php if ($index === 0): ?>
                                                            <td rowspan="<?php echo $sections_count; ?>" class="fw-bold align-middle bg-light" style="font-size: 7pt; font-weight: 600;">
                                                                <?php echo round($criterion_data['criterion_score'], 2); ?>%
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                        <?php 
                                                endforeach;
                                            endif;
                                        ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- التذييل (للطباعة فقط) -->
        <div class="print-footer">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="width: 33%; text-align: right; font-size: 6pt;">
                        الموظف: <?php echo htmlspecialchars($employee->full_name ?? ''); ?>
                    </td>
                    <td style="width: 34%; text-align: center; font-size: 6pt;">
                        صفحة <span class="page-number"></span> من <span class="page-total"></span>
                    </td>
                    <td style="width: 33%; text-align: left; font-size: 6pt;">
                        نظام تقييم الموظفين
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- تحميل ملفات JavaScript -->
    <script src="<?php echo APP_URL; ?>/public/libs/chartjs/chart.umd.min.js"></script>
    <script src="<?php echo APP_URL; ?>/public/libs/chartjs/chartjs-plugin-datalabels.min.js"></script>
    <script>
    // دالة محسنة للطباعة
    function printReport() {
        // إعطاء وقت إضافي لتحميل المخططات
        setTimeout(() => {
            window.print();
        }, 1000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        // تعطيل الحركات للطباعة
        Chart.defaults.animation = false;
        
        // التحقق من وجود ChartDataLabels قبل التسجيل
        if (typeof ChartDataLabels !== 'undefined') {
            Chart.register(ChartDataLabels);
        }
        
        Chart.defaults.font.family = 'Cairo, Arial, sans-serif';
        Chart.defaults.font.size = 7;
        
        const charts = {};
        const barChartData = <?php echo json_encode($barChartData ?? null); ?>;
        const performanceHistory = <?php echo json_encode($performanceHistory ?? null); ?>;
        const detailedTrend = <?php echo json_encode($detailedTrend ?? null); ?>;
        const historyTableData = detailedTrend ? detailedTrend.history_table_data : null;

        // --- دوال إنشاء المخططات ---
        
        // المخطط الدائري
        function createBarChart() {
            const barCtx = document.getElementById('performanceBarChart');
            if (!barCtx || !barChartData || !barChartData.labels || barChartData.labels.length === 0) {
                console.log('No data for bar chart');
                return;
            }

            try {
                charts.barChart = new Chart(barCtx, {
                    type: 'doughnut',
                    data: {
                        labels: barChartData.labels,
                        datasets: [{
                            data: barChartData.scores,
                            backgroundColor: barChartData.backgroundColors,
                            borderColor: '#0d0b0b',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { 
                                display: false
                            },
                            datalabels: {
                                formatter: (value) => value > 5 ? Math.round(value) + '%' : '',
                                color: '#fff',
                                font: { weight: 'bold', size: 8 }
                            }
                        }
                    }
                });

                // بناء وسيلة الإيضاح المخصصة
                buildCustomLegend();
                
            } catch (error) {
                console.error('Error creating bar chart:', error);
            }
        }

        // بناء وسيلة الإيضاح
        function buildCustomLegend() {
            const legendStrongUl = document.getElementById('legend-strong');
            const legendGoodUl = document.getElementById('legend-good');
            const legendWeakUl = document.getElementById('legend-weak');

            if (!legendStrongUl || !legendGoodUl || !legendWeakUl) return;

            legendStrongUl.innerHTML = '';
            legendGoodUl.innerHTML = '';
            legendWeakUl.innerHTML = '';

            let strongCount = 0, goodCount = 0, weakCount = 0;

            barChartData.labels.forEach((label, index) => {
                const score = barChartData.scores[index];
                const color = barChartData.backgroundColors[index];

                const listItem = document.createElement('li');
                listItem.innerHTML = `
                    <span class="legend-color-box" style="background-color: ${color};"></span>
                    <span>${label} (${Math.round(score)}%)</span>
                `;

                if (score >= 80) {
                    legendStrongUl.appendChild(listItem);
                    strongCount++;
                } else if (score < 60) {
                    legendWeakUl.appendChild(listItem);
                    weakCount++;
                } else {
                    legendGoodUl.appendChild(listItem);
                    goodCount++;
                }
            });

            // عرض رسالة إذا لم توجد بيانات
            if (strongCount === 0) {
                legendStrongUl.innerHTML = '<li class="text-muted small">لا يوجد</li>';
            }
            if (goodCount === 0) {
                legendGoodUl.innerHTML = '<li class="text-muted small">لا يوجد</li>';
            }
            if (weakCount === 0) {
                legendWeakUl.innerHTML = '<li class="text-muted small">لا يوجد</li>';
            }
        }

        // مخطط تطور الأداء
        function createTrendChart() {
            const trendCtx = document.getElementById('performanceTrendChart');
            if (!trendCtx || !performanceHistory || performanceHistory.length < 2) {
                console.log('No data for trend chart');
                return;
            }

            try {
                const labels = performanceHistory.map(h => new Date(h.evaluation_date).toLocaleDateString('ar-SA', { year: 'numeric', month: 'short' }));
                const scores = performanceHistory.map(h => h.final_score || 0);

                charts.trendChart = new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'النتيجة النهائية',
                            data: scores,
                            borderColor: 'rgb(13, 110, 253)',
                            borderWidth: 2,
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { 
                                suggestedMin: Math.max(0, Math.min(...scores) - 10), 
                                suggestedMax: 100,
                                ticks: { font: { size: 6 } }
                            },
                            x: { 
                                ticks: { font: { size: 6 } }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error creating trend chart:', error);
            }
        }

        // مخطط تطور الكفاءات عبر الزمن
        function createCompetencyTrendChart() {
            const trendCtx = document.getElementById('competencyTrendChart');
            const trendData = detailedTrend ? detailedTrend.trend_chart_data : null;
            
            if (!trendCtx || !trendData || trendData.datasets.length === 0) {
                const trendCard = document.getElementById('trend-chart-card');
                if (trendCard) trendCard.style.display = 'none';
                console.log('No data for competency trend chart');
                return;
            }

            try {
                const processedDatasets = trendData.datasets.map(dataset => {
                    return {
                        ...dataset,
                        backgroundColor: dataset.borderColor,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    };
                });

                charts.competencyTrendChart = new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: trendData.labels.map(dateStr => new Date(dateStr).toLocaleDateString('ar-SA', { month: 'short', year: 'numeric' })),
                        datasets: processedDatasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 8,
                                    padding: 15,
                                    font: {
                                        size: 8, weight: 'bold'
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                min: 0,
                                suggestedMax: 105,
                                title: { display: true, text: 'درجة الأداء (%)', font: { size: 9, weight: 'bold' } }
                            },
                            x: {
                                title: { display: true, text: 'تاريخ التقييم', font: { size: 9, weight: 'bold' } }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error creating competency trend chart:', error);
            }
        }

        // المخططات العنكبوتية للسجل التاريخي
        function createRadarChart(evalId) {
            const canvas = document.getElementById(`radarChart-${evalId}`);
            if (!canvas || !historyTableData || !historyTableData[evalId]) return;
            
            const evalData = historyTableData[evalId];
            let labels = [], scores = [];
            
            if (evalData.criteria) {
                for (const criterionName in evalData.criteria) {
                    evalData.criteria[criterionName].sections.forEach(section => {
                        labels.push(section.section_name);
                        scores.push(section.score);
                    });
                }
            }
            
            if (labels.length > 2) {
                try {
                    const pointBackgroundColors = scores.map(score => {
                        if (score >= 85) return 'rgba(25, 135, 84, 1)';
                        if (score < 50) return 'rgba(220, 53, 69, 1)';
                        return 'rgba(255, 193, 7, 1)';
                    });

                    charts[`radarChart_${evalId}`] = new Chart(canvas, {
                        type: 'radar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'مستوى الأداء',
                                data: scores,
                                pointBackgroundColor: pointBackgroundColors,
                                pointBorderColor: '#fff',
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: pointBackgroundColors,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                fill: true,
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                borderColor: 'rgba(13, 110, 253, 0.6)',
                                borderWidth: 1.5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { 
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return ` ${context.raw}%`;
                                        }
                                    }
                                }
                            },
                            scales: { 
                                r: { 
                                    min: 0, 
                                    max: 100, 
                                    ticks: { stepSize: 25, backdropColor: 'transparent', font: { size: 6 } },
                                    pointLabels: {
                                        font: { size: 7, weight: 'bold', family: 'Cairo, sans-serif' }
                                    },
                                    grid: { color: '#06396b' }
                                } 
                            }
                        }
                    });
                } catch (error) {
                    console.error(`Error creating radar chart for eval ${evalId}:`, error);
                }
            }
        }

        // --- تنفيذ المخططات ---
        console.log('Starting chart creation...');
        
        // إنشاء المخططات مع معالجة الأخطاء
        try {
            createBarChart();
            createTrendChart();
            createCompetencyTrendChart();
            
            // رسم المخططات العنكبوتية للسجل التاريخي
            if (historyTableData) {
                for (const evalId in historyTableData) {
                    createRadarChart(evalId);
                }
            }
            
            console.log('All charts created successfully');
        } catch (error) {
            console.error('Error creating charts:', error);
        }

        // طباعة تلقائية بعد تحميل الصفحة (مع وقت إضافي)
        setTimeout(() => {
            console.log('Auto-printing after charts loaded');
            // يمكن تفعيل هذا إذا أردت الطباعة التلقائية
            // window.print();
        }, 2000);
    });
    </script>
</body>
</html>