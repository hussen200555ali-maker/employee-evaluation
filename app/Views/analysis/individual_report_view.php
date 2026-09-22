<?php 
// ملف: app/Views/analysis/individual_report_view.php
require_once APP_ROOT . '/app/Views/layouts/header.php'; 

// =================================================================
//   ===>  الخطوة 1: معالجة بيانات المخطط الجاهزة من الواجهة الخلفية (النسخة النهائية)  <===
// =================================================================
$barChartData = ['labels' => [], 'scores' => [], 'backgroundColors' => [], 'borderColors' => [], 'competencyCounts' => []];

// تحقق من وجود بيانات المخطط الجاهزة التي أرسلتها الواجهة الخلفية
if (isset($analysis) && !empty($analysis['performance_chart_data']['labels'])) {
    
    // --- أ. اسحب البيانات الجاهزة مباشرة (بما في ذلك عدد الكفاءات المدمجة) ---
    $barChartData['labels'] = $analysis['performance_chart_data']['labels'];
    $barChartData['scores'] = $analysis['performance_chart_data']['scores'];
    // ▼▼▼ [إضافة احترافية] جلب عدد الكفاءات المدمجة لكل متوسط ▼▼▼
    $barChartData['competencyCounts'] = $analysis['performance_chart_data']['counts'];

    // --- ب. بناء مصفوفات الألوان بناءً على الدرجات ---
    foreach ($barChartData['scores'] as $score) {
        if ($score >= 80) {
            $barChartData['backgroundColors'][] = 'rgba(25, 135, 84, 0.8)';
            $barChartData['borderColors'][] = 'rgb(25, 135, 84)';
        } elseif ($score < 60) {
            $barChartData['backgroundColors'][] = 'rgba(220, 53, 69, 0.8)';
            $barChartData['borderColors'][] = 'rgb(220, 53, 69)';
        } else {
            $barChartData['backgroundColors'][] = 'rgba(13, 110, 253, 0.8)';
            $barChartData['borderColors'][] = 'rgb(13, 110, 253)';
        }
    }
}
?>



<!-- --- 2. المكتبات والأنماط (CSS) --- -->
<script src="<?php echo APP_URL; ?>/public/libs/chartjs/chart.umd.min.js"></script>
<script src="<?php echo APP_URL; ?>/public/libs/chartjs/chartjs-plugin-datalabels.min.js"></script>

<style>
    /* --- أنماط العرض على الشاشة (الكود الأصلي الخاص بك مع تحسينات طفيفة) --- */
    @font-face {
        font-family: 'Cairo';
        src: url('<?php echo APP_URL; ?>/fonts/Cairo-Regular.ttf') format('truetype');
        font-weight: 400;
    }
    @font-face {
        font-family: 'Cairo';
        src: url('<?php echo APP_URL; ?>/fonts/Cairo-Bold.ttf') format('truetype');
        font-weight: 700;
    }
    body {
        font-family: 'Cairo', sans-serif !important;
        background-color: #f8f9fa;
    }
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #eef2f7;
        padding: 1rem 1.5rem;
        font-weight: 700;
    }
    .print-header, .print-footer, .print-only {
        display: none;
    }
    .employee-info-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 1rem;
    }
    .employee-info-table td {
        vertical-align: middle;
        padding: 0.25rem 0.5rem;
    }
    .performance-score-card {
        background: linear-gradient(135deg, #f5f7fa, #eef2f7);
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .performance-score-large {
        font-size: 2.8rem;
        font-weight: 800;
    }
    .performance-table .card-header {
        color: #fff;
        border-radius: 10px 10px 0 0;
    }
    .performance-table .card-header.bg-success-soft { background-color: #198754 !important; }
    .performance-table .card-header.bg-primary-soft { background-color: #0d6efd !important; }
    .performance-table .card-header.bg-danger-soft  { background-color: #dc3545 !important; }
    .accordion-button:not(.collapsed) {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
/* أنماط وسيلة الإيضاح المخصصة */
.legend-category ul {
    padding-right: 0; /* إزالة الحشوة الافتراضية للقائمة */
    text-align: right; /* محاذاة النص لليمين */
}

.legend-category li {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.legend-color-box {
    width: 14px;
    height: 14px;
    border-radius: 3px;
    margin-left: 8px; /* مسافة بين المربع والنص */
    flex-shrink: 0; /* منع المربع من التقلص */
}
/* --- أنماط تأثير التلاشي التفاعلي --- */

/* 1. تطبيق تأثير انتقال سلس على كل عناصر القائمة */
.legend-category li {
    transition: opacity 0.3s ease-in-out; /* هذا هو السطر الأهم */
}

/* 2. كلاس جديد سيتم إضافته بالجافاسكريبت لجعل العناصر باهتة */
.legend-item-faded {
    opacity: 0.3; /* تقليل الشفافية لجعل العنصر باهتاً */
}

/* 3. كلاس جديد سيتم إضافته بالجافاسكريبت لإبراز العنصر النشط (اختياري لكنه يحسن المظهر) */
.legend-item-active {
    transform: scale(1.03); /* تكبير طفيف للعنصر النشط */
    font-weight: 600; /* جعل الخط أعرض قليلاً */
}

    /* ================================================== */
    /*   ===>  أنماط الطباعة الاحترافية المدمجة  <===     */
    /* ================================================== */
    @media print {
        /* 1. إعدادات الصفحة العامة */
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        body {
            font-family: 'Times New Roman', Times, serif !important;
            font-size: 10pt !important;
            background: #fff !important;
            color: #000 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }

        /* 2. إخفاء عناصر الشاشة وإظهار عناصر الطباعة */
        .no-print, .navbar, .main-header, .main-footer, .btn, .accordion-button::after, .page-header {
            display: none !important;
        }
        .print-header, .print-footer {
            display: table !important;
            position: fixed;
            width: calc(100% - 30mm);
            left: 15mm;
            right: 15mm;
            background: white !important;
            z-index: 1000;
            font-size: 9pt;
        }
        .print-header {
            top: 0;
            padding-top: 5mm;
            border-bottom: 1.5px solid #000;
            padding-bottom: 5px;
        }
        .print-footer {
            bottom: 0;
            padding-bottom: 5mm;
            border-top: 1.5px solid #000;
            padding-top: 5px;
        }

        /* 3. تعديل تخطيط المحتوى الرئيسي */
        #report-content {
            padding-top: 2cm !important;
            padding-bottom: 2cm !important;
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
        }

        /* 4. تحسين مظهر البطاقات والجداول للطباعة */
        .card {
            border: 1px solid #ccc !important;
            page-break-inside: avoid !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }
        .card-header {
            background-color: #f0f0f0 !important;
            color: #000 !important;
            border-radius: 0 !important;
        }
        .table {
            font-size: 9pt !important;
        }
        .table th, .table td {
            padding: 5px 8px !important;
            border: 1px solid #bbb !important;
            background-color: white !important;
        }
        .table thead th {
            background-color: #e0e0e0 !important;
            color: #000 !important;
        }
        .accordion-collapse {
            display: block !important;
        }
        .chart-container, #donutChartContainer {
            page-break-inside: avoid !important;
            height: auto !important;
        }
        canvas {
            max-width: 100% !important;
            border: 1px solid #ddd !important;
        }
        .performance-table .card-header {
            color: #000 !important;
            background-color: #e9ecef !important;
        }
    }
</style>

<!-- --- 3. هيكل الترويسة والتذييل (مخفي في العرض العادي) --- -->
<div class="print-header">
    <table style="width: 100%; border: none;">
        <tr>
            <td style="width: 33%; text-align: right; font-size: 8pt; vertical-align: top;">
                المملكة العربية السعودية  
اسم الشركة
            </td>
            <td style="width: 34%; text-align: center; vertical-align: top;">
                <h3 style="margin: 0; font-size: 11pt; font-weight: bold;">تقرير الأداء الفردي</h3>
            </td>
            <td style="width: 33%; text-align: left; font-size: 8pt; vertical-align: top;">
                تاريخ التقرير: <?php echo date('Y-m-d'); ?>
            </td>
        </tr>
    </table>
</div>
<div class="print-footer">
    <table style="width: 100%; border: none;">
        <tr>
            <td style="width: 33%; text-align: right; font-size: 8pt;">
                الموظف: <?php echo htmlspecialchars($employee->full_name ?? ''); ?>
            </td>
            <td style="width: 34%; text-align: center; font-size: 8pt;">
                <!-- الترقيم سيتم عبر CSS -->
            </td>
            <td style="width: 33%; text-align: left; font-size: 8pt;">
                نظام تقييم الموظفين
            </td>
        </tr>
    </table>
</div>

<!-- --- 4. المحتوى الرئيسي للصفحة --- -->
<div id="report-content" class="container mt-4 main-content">
    <!-- شريط العنوان والأزرار (يختفي عند الطباعة) -->
    <div class="page-header d-flex justify-content-between align-items-center mb-4 no-print">
        <h2 class="mb-0"><i class="fas fa-user-tie text-primary"></i> <?php echo htmlspecialchars($reportTitle ?? 'تقرير الأداء الفردي'); ?></h2>
        <div>
  <button id="print-report-btn" class="btn btn-primary me-2">
    <i class="fas fa-print"></i> طباعة التقرير
</button>

<script>
document.getElementById('print-report-btn').addEventListener('click', function() {
    const printUrl = `<?php echo APP_URL; ?>/analysis/print?level=employee&target_id=<?php echo $employee->id; ?>`;
    window.open(printUrl, '_blank');
});
</script>
            <a href="<?php echo APP_URL; ?>/analysis" class="btn btn-secondary">
          عودة
          </a>
        </div>
    </div>

    <!-- بطاقة معلومات الموظف (الكود الأصلي الخاص بك) -->
    <div class="card mb-4 employee-info-card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-id-card text-primary me-2"></i>بطاقة معلومات الموظف</h5>
        </div>
        <div class="card-body p-4">
            <div class="row align-items-stretch">
                <div class="col-lg-8">
                    <table class="employee-info-table">
                        <tr>
                            <td><i class="fas fa-user"></i> <strong>الموظف:</strong></td>
                            <td><?php echo htmlspecialchars($employee->full_name ?? 'N/A'); ?></td>
                            <td><i class="fas fa-sitemap"></i> <strong>القسم:</strong></td>
                            <td><?php echo htmlspecialchars($employee->department_name ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-id-badge"></i> <strong>الرقم الوظيفي:</strong></td>
                            <td><?php echo htmlspecialchars($employee->emp_code ?? 'N/A'); ?></td>
                            <td><i class="fas fa-building"></i> <strong>الفرع:</strong></td>
                            <td><?php echo htmlspecialchars(string: $employee->branch_name ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-briefcase"></i> <strong>المنصب:</strong></td>
                            <td><?php echo htmlspecialchars($employee->position_title ?? 'N/A'); ?></td>
                            <td><i class="fas fa-calendar-alt"></i> <strong>تاريخ التعيين:</strong></td>
                            <td><?php echo !empty($employee->join_date) ? date('Y-m-d', strtotime($employee->join_date)) : 'غير محدد'; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-lg-4 mt-3 mt-lg-0">
                    <div class="performance-score-card">
                        <div class="row w-100">
                            <div class="col-6 border-end">
                                <div class="performance-score">
                                    <div class="performance-score-large text-primary"><?php echo round($latestEvaluation->final_score ?? 0, 1); ?>%</div>
                                    <small class="performance-score-label">نتيجة آخر تقييم</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="performance-score">
                                    <div class="performance-score-large text-success"><?php echo round($performanceStats->average_score ?? 0, 1); ?>%</div>
                                    <small class="performance-score-label">متوسط الأداء العام</small>
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <small class="text-muted"><i class="fas fa-history me-1"></i> عدد التقييمات المكتملة: <strong><?php echo $performanceStats->total_evaluations ?? 0; ?></strong></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- جداول نقاط القوة والضعف (الكود الأصلي الخاص بك) -->
  <?php
/**
 * =================================================================
 *   دالة عرض جداول الأداء (النسخة النهائية v6.0 - التصميم الموحد)
 * =================================================================
 * التحسينات:
 * - تستقبل متغير `$isUnifiedDetailedView` لتحديد التصميم الموحد للجدول.
 * - ترسم دائماً المحتوى الكامل لكل كفاءة، سواء كانت بصف واحد أو عدة صفوف.
 * - لا يوجد أعمدة فارغة، حيث يتم ملء البيانات حتى في حالة التقييم الواحد.
 */
function render_performance_table_v2($title, $data, $icon, $color_class, $isUnifiedDetailedView) {
?>
<div class="card mb-4 performance-table">
    <div class="card-header bg-<?php echo $color_class; ?>-soft">
        <h5 class="mb-0"><i class="fas <?php echo $icon; ?> me-2"></i> <?php echo $title; ?></h5>
    </div>
    <?php if (empty($data)): ?>
        <div class="card-body text-center text-muted p-4"><p class="mb-0">لا توجد كفاءات ضمن هذا النطاق.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                
                <thead class="table-light">
                    <?php if ($isUnifiedDetailedView): // إذا كان العرض الموحد تفصيلياً ?>
                        <tr>
                            <th style="width: 15%;">المعيار الرئيسي</th>
                            <th style="width: 20%;">الكفاءة</th>
                            <th class="text-center" style="width: 15%;">تاريخ التقييم</th>
                            <th class="text-center" style="width: 15%;">اسم الاستمارة</th>
                            <th class="text-center" style="width: 15%;">النتيجة المدخلة</th>
                            <th class="text-center" style="width: 20%;">الدرجة</th>
                        </tr>
                    <?php else: // إذا كان العرض الموحد بسيطاً ?>
                        <tr>
                            <th style="width: 30%;">المعيار الرئيسي</th>
                            <th style="width: 40%;">الكفاءة (القسم الفرعي)</th>
                            <th class="text-center" style="width: 15%;">النتيجة المدخلة</th>
                            <th class="text-center" style="width: 15%;">متوسط الدرجة</th>
                        </tr>
                    <?php endif; ?>
                </thead>
                
                <tbody>
                    <?php foreach ($data as $sectionId => $competency): ?>
                        
                        <?php // دائماً نعرض التاريخ التفصيلي لكل كفاءة ?>
                        <?php foreach ($competency['history'] as $index => $history_item): ?>
                            <tr>
                                <?php if ($index === 0): // ارسم الخلايا المدمجة في الصف الأول فقط ?>
                                    <td rowspan="<?php echo $competency['is_detailed'] ? count($competency['history']) + 1 : 1; ?>">
                                        <?php echo htmlspecialchars($competency['criterion_name']); ?>
                                    </td>
                                    <td rowspan="<?php echo $competency['is_detailed'] ? count($competency['history']) + 1 : 1; ?>">
                                        <?php echo htmlspecialchars($competency['section_name']); ?>
                                    </td>
                                <?php endif; ?>

                                <?php if ($isUnifiedDetailedView): // إذا كان العرض الموحد تفصيلياً ?>
                                    <td class="text-center small"><?php echo date('Y-m-d', strtotime($history_item['date'])); ?></td>
                                    <td class="text-center small"><?php echo htmlspecialchars($history_item['template_name']); ?></td>
                                <?php endif; ?>

                                <td class="text-center small">
                                    <?php echo format_raw_value($history_item['raw_value'], $history_item['evaluation_type'], $history_item['evaluation_options']); ?>
                                </td>
                                <td class="text-center small">
                                    <?php if ($isUnifiedDetailedView): ?>
                                        <?php echo $history_item['score']; ?>%
                                    <?php else: // في العرض البسيط، هذا العمود هو لمتوسط الدرجة ?>
                                        <span class="badge fs-6 rounded-pill bg-<?php echo $color_class; ?>">
                                            <?php echo $competency['average_score']; ?>%
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php // صف المتوسط (يظهر فقط إذا كانت الكفاءة تفصيلية والعرض الموحد تفصيلي) ?>
                        <?php if ($competency['is_detailed'] && $isUnifiedDetailedView): ?>
                            <tr class="table-light">
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

// ... (باقي الكود ودالة format_raw_value تبقى كما هي) ...


// ▼▼▼ دالة مساعدة جديدة: ضعها مع الدالة السابقة ▼▼▼
function format_raw_value($rawValue, $evalType, $optionsJson) {
    
          switch ($evalType) {
        case 'grade':
            $options = json_decode($optionsJson ?? '[]');
            if (is_array($options)) {
                foreach ($options as $option) {
                    if (isset($option->value) && $option->value == $rawValue) {
                        return htmlspecialchars($option->label);
                    }
                }
            }
            return htmlspecialchars($rawValue); // fallback
        case 'numeric':
            return htmlspecialchars(round($rawValue, 2)); // تقريب القيمة الرقمية
        case 'percentage':
            return htmlspecialchars(round($rawValue, 2)) . '%';
        case 'boolean':
            return $rawValue == 1 ? 'نعم' : 'لا';
        default:
            return htmlspecialchars($rawValue);
    }
}

// =================================================================
//          ===>  هذا هو الكود المصحح لاستدعاء الدالة  <===
// =================================================================
// =================================================================
//          ===>  الخطوة 1: منطق القرار العام هنا  <===
// =================================================================
if (isset($analysis) && isset($analysis['classified_performance'])) {
    
    // --- أ. تحديد وضع العرض العام للتقرير ---
    $isAnyDetailed = false;
    // دمج كل الكفاءات من كل الفئات في مصفوفة واحدة للفحص
    $allCompetencies = array_merge(
        $analysis['classified_performance']['strong'],
        $analysis['classified_performance']['medium'],
        $analysis['classified_performance']['weak']
    );

    // البحث عن وجود كفاءة واحدة على الأقل تتطلب عرضاً تفصيلياً
    foreach ($allCompetencies as $competency) {
        if ($competency['is_detailed']) {
            $isAnyDetailed = true;
            break; // وجدنا واحدة، هذا يكفي
        }
    }

    // --- ب. استدعاء دوال العرض مع تمرير وضع العرض الموحد ---
    render_performance_table_v2(
    'نقاط القوة (أداء ≥ 80%)', 
    $analysis['classified_performance']['strong'], 
    'fa-star', 
    'success',
    $isAnyDetailed
);
  // الجديد
render_performance_table_v2(
    'الأداء المتوسط (60% - 79.9%)', 
    $analysis['classified_performance']['medium'], 
    'fa-check-circle', 
    'primary',
    $isAnyDetailed
);
  // الجديد
render_performance_table_v2(
    'جوانب تحتاج إلى تطوير (أداء < 60%)', 
    $analysis['classified_performance']['weak'], 
    'fa-flag', 
    'danger',
    $isAnyDetailed
);
}
?>

  

    <!-- المخططات البيانية (الكود الأصلي الخاص بك) -->
  <?php if (!empty($barChartData['labels'])): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>ملخص أداء الكفاءات</h5>
    </div>
    <div class="card-body">
       <  <!-- ▼▼▼ تحديث الملاحظة التوضيحية بناءً على عدد التقييمات ▼▼▼ -->
        <?php 
        $totalEvaluations = $performanceStats->total_evaluations ?? 0;
        $chartNote = $totalEvaluations <= 1 
            ? "يعرض هذا المخطط <strong>جميع الكفاءات الفرعية</strong> من التقييم الواحد."
            : "يعرض هذا المخطط <strong>متوسط الأداء العام</strong> للكفاءات المتشابهة عبر جميع التقييمات.";
        ?>
        <p class="chart-card-note">
            <strong>ملاحظة:</strong> <?php echo $chartNote; ?>
        </p>
      
        <!-- حاوية المخطط الدائري -->
        <div class="d-flex justify-content-center mb-4">
            <div id="donutChartContainer" style="max-width: 350px; width: 100%;">
                <canvas id="performanceBarChart"></canvas>
            </div>
        </div>

        <!-- حاوية وسيلة الإيضاح المخصصة (المناطق الثلاث) -->
        <div id="custom-legend-container" class="row g-3 text-center">
            <!-- سيتم ملء هذه المناطق ديناميكياً باستخدام JavaScript -->
            <div class="col-md-4">
                <div class="legend-category p-3 border rounded h-100">
                    <h6 class="legend-title text-success"><i class="fas fa-star me-1"></i> نقاط القوة (≥ 80%)</h6>
                    <hr class="my-2">
                    <ul id="legend-strong" class="list-unstyled mb-0"></ul>
                </div>
            </div>
          <!-- في قسم وسائل الإيضاح للمخطط الدائري -->

<div class="col-md-4">
    <div class="legend-category p-3 border rounded h-100">
        <h6 class="legend-title text-primary"><i class="fas fa-check-circle me-1"></i> أداء متوسط (60-79%)</h6>
        <hr class="my-2">
        <ul id="legend-good" class="list-unstyled mb-0"></ul>
    </div>
</div>
<div class="col-md-4">
    <div class="legend-category p-3 border rounded h-100">
        <h6 class="legend-title text-danger"><i class="fas fa-flag me-1"></i> جوانب للتطوير (< 60%)</h6>
        <hr class="my-2">
        <ul id="legend-weak" class="list-unstyled mb-0"></ul>
    </div>
</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ================================================================= -->
<!--      ===>  2. المخطط الخطي الجديد (تطور الأداء عبر الزمن)  <===       -->
<!-- ================================================================= -->
<!-- ================================================================= -->
<!--      ===>  2. المخطط الخطي الجديد (تطور الأداء عبر الزمن)  <===       -->
<!-- ================================================================= -->
<?php 
// ▼▼▼ [التحسين المطلوب] ▼▼▼
// تحقق أولاً من وجود إحصائيات الأداء وأن عدد التقييمات أكبر من 1
// ثم تحقق من وجود بيانات فعلية للمخطط لضمان عدم عرض بطاقة فارغة
if (isset($performanceStats) && $performanceStats->total_evaluations > 1 && isset($detailedTrend['trend_chart_data']) && !empty($detailedTrend['trend_chart_data']['datasets'])): 
?>
<div id="trend-chart-card" class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0 print-title"><i class="fas fa-chart-line text-info me-2"></i>مخطط تطور أداء الكفاءات عبر الزمن</h5>
    </div>
    <div class="card-body">
        <p class="mb-4 print-description text-muted small">
            يوضح هذا المخطط كيف تغير مستوى أداء الموظف في كل كفاءة على حدة مع مرور الوقت، مما يساعد على رصد التطور والتحسن.
        </p>
        <div class="chart-container" style="height: 350px;">
            <canvas id="competencyTrendChart"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

    <!-- السجل التاريخي (الكود الأصلي الخاص بك) -->
    <?php if (isset($detailedTrend['history_table_data']) && !empty($detailedTrend['history_table_data'])): ?>
    <div id="historyAccordionCard" class="card shadow-sm mb-4">
        <div class="card-header bg-light no-print"><h5 class="mb-0"><i class="fas fa-history text-info"></i> سجل التقييمات التاريخي المفصل</h5></div>
        <div class="print-only" style="text-align: center; padding: 10px; background-color: #f0f0f0; border-bottom: 1px solid #ddd;"><h4 style="margin: 0; font-size: 12pt;">سجل التقييمات التاريخي المفصل</h4></div>
        <div class="card-body">
            <p class="text-muted small no-print mb-3">انقر على أي تقييم لعرض تفاصيله الكاملة ومخطط الأداء الخاص به.</p>
            <div class="accordion" id="historyAccordion">
                <?php 
                $reversed_history = array_reverse($detailedTrend['history_table_data'], true);
                foreach ($reversed_history as $eval_id => $eval_data): 
                    $evaluation_date = isset($eval_data['evaluation_date']) && !empty($eval_data['evaluation_date']) ? date('Y-m-d', strtotime($eval_data['evaluation_date'])) : 'غير محدد';
                    $final_score = isset($eval_data['final_score']) ? round($eval_data['final_score'], 1) : 0;
                ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-<?php echo $eval_id; ?>">
                        <button class="accordion-button collapsed no-print" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $eval_id; ?>">
                            <i class="fas fa-calendar-alt me-2"></i> تقييم <?php echo $evaluation_date; ?> <span class="badge bg-primary ms-2"><?php echo $final_score; ?>%</span>
                        </button>
                    </h2>
                  <div id="collapse-<?php echo $eval_id; ?>" class="accordion-collapse collapse" 
                         aria-labelledby="heading-<?php echo $eval_id; ?>" data-bs-parent="#historyAccordion">
                        <div class="accordion-body p-3">
                            <div class="row g-3 align-items-center  print-flex-container">
                                <div class="col-lg-6 col-md-12 print-flex-left">
                                    <h6 class="text-center mb-2 print-subtitle">مخطط الأداء للكفاءات</h6>
                                    <div class="chart-container">
                                        <canvas id="radarChart-<?php echo $eval_id; ?>" height="250"></canvas>
                                    </div>
                                    <div class="d-flex justify-content-center align-items-center mt-2 small text-muted flex-wrap">
                                        <div class="d-flex align-items-center mx-1 my-1">
                                            <span style="width: 10px; height: 10px; background-color: rgba(25, 135, 84, 0.3); border: 1px solid rgba(25, 135, 84, 0.5); border-radius: 2px; margin-left: 3px;"></span>
                                            <span style="font-size: 7pt;">أداء متميز (≥ 80%)</span>
                                        </div>
                                        <div class="d-flex align-items-center mx-1 my-1">
                                            <span style="width: 10px; height: 10px; background-color: rgba(255, 193, 7, 0.3); border: 1px solid rgba(255, 193, 7, 0.5); border-radius: 2px; margin-left: 3px;"></span>
                                            <span style="font-size: 7pt;">أداء جيد (60-79.9%)</span>
                                        </div>
                                        <div class="d-flex align-items-center mx-1 my-1">
                                            <span style="width: 10px; height: 10px; background-color: rgba(220, 53, 69, 0.3); border: 1px solid rgba(220, 53, 69, 0.5); border-radius: 2px; margin-left: 3px;"></span>
                                            <span style="font-size: 7pt;">يحتاج لتطوير (&lt; 60%)</span>
                                        </div>
                                    </div>
                                </div>
                                  <div class="col-lg-6 col-md-12 print-flex-right">
                                    <h6 class="text-center mb-2 print-subtitle">التفاصيل الرقمية</h6>
                                    
                                    <!-- ▼▼▼ بداية التعديل: إضافة حاوية التوسيط ▼▼▼ -->
                                    <div class="d-flex justify-content-center align-items-center">
                                      <div class="table-responsive" style="max-height: 280px; width: 100%;">
    <table class="table table-bordered table-sm mb-0">
        <thead class="table-light">
            <tr>
                <th class="text-center" style="width: 20%; font-size: 8pt; font-weight: 700;">المعيار</th>
                <th class="text-center" style="width: 35%; font-size: 8pt; font-weight: 700;">الكفاءة</th>
                <th class="text-center" style="width: 15%; font-size: 8pt; font-weight: 700;">النتيجة المدخلة</th>
                <th class="text-center" style="width: 15%; font-size: 8pt; font-weight: 700;">الدرجة الموحدة</th>
                <th class="text-center" style="width: 15%; font-size: 8pt; font-weight: 700;">نتيجة المعيار</th>
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
                            <td rowspan="<?php echo $sections_count; ?>" class="align-middle text-center small bg-light fw-bold" style="font-size: 7pt; font-weight: 600;">
                                <?php echo htmlspecialchars($criterion_name); ?>
                            </td>
                        <?php endif; ?>
                        
                        <td class="text-center" style="font-size: 7pt; font-weight: 600;"><?php echo htmlspecialchars($section['section_name']); ?></td>
                        
                        <!-- ================================================== -->
                        <!--          ===>  المنطق الذكي الجديد هنا  <===         -->
                        <!-- ================================================== -->
                        <td class="text-center" style="font-size: 7pt; font-weight: 600;">
                            <?php
                                $rawValue = $section['raw_value'];
                                $evalType = $section['evaluation_type'];
                                $options = json_decode($section['evaluation_options'] ?? '[]');

                                switch ($evalType) {
                                    case 'grade':
                                        $displayValue = $rawValue; // قيمة افتراضية
                                        if (is_array($options)) {
                                            foreach ($options as $option) {
                                                if (isset($option->value) && $option->value == $rawValue) {
                                                    $displayValue = $option->label; // اعرض اسم التقدير
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
                        <!-- ================================================== -->

                        <td class="text-center" style="font-size: 7pt; font-weight: 600;"><?php echo $section['score']; ?>%</td>
                        
                        <?php if ($index === 0): ?>
                            <td rowspan="<?php echo $sections_count; ?>" class="text-center fw-bold align-middle bg-light" style="font-size: 8pt; font-weight: 600;">
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
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (isset($performanceHistory) && count($performanceHistory) > 1): ?>
    <div id="trend-chart-card" class="card shadow-sm mb-4">
        <div class="card-header"><h5 class="mb-0 print-title"><i class="fas fa-chart-line text-info me-2"></i>تطور الأداء العام</h5></div>
        <div class="card-body">
            <p class="mb-4 print-description">الرسم البياني التالي يوضح تطور المتوسط الكلي لأداء الموظف.</p>
            <div class="chart-container" style="height: 200px;"><canvas id="performanceTrendChart"></canvas></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // =================================================================
    // 0. الإعدادات العامة والمصفوفات (من الكود الأصلي الخاص بك)
    // =================================================================
    const charts = {};
    Chart.register(ChartDataLabels);
    Chart.defaults.animation = { duration: 500 };
    Chart.defaults.font.family = 'Cairo, sans-serif';

    const barChartData = <?php echo json_encode($barChartData ?? null); ?>;
    const performanceHistory = <?php echo json_encode($performanceHistory ?? null); ?>;
    const detailedTrend = <?php echo json_encode($detailedTrend ?? null); ?>;
    const historyTableData = detailedTrend ? detailedTrend.history_table_data : null;

    // =================================================================
    // 1. وظائف إنشاء المخططات (من الكود الأصلي الخاص بك)
    // =================================================================

/**
 * =================================================================
 *   ===>  دالة إنشاء المخطط الدائري (النسخة النهائية v4.0)  <===
 * =================================================================
 * التحسينات:
 * - إعادة وسيلة الإيضاح الافتراضية لربط اللون بالاسم.
 * - استخدام `boxWidth` و `padding` لتنظيم وسيلة الإيضاح.
 * - الحفاظ على عرض النسب المئوية على الشرائح.
 */
/**
 * =================================================================
 *   ===>  دالة إنشاء المخطط الدائري مع وسيلة إيضاح مخصصة (النسخة النهائية)  <===
 * =================================================================
 * التحسينات:
 * - تعطيل وسيلة الإيضاح الافتراضية.
 * - بناء وسيلة إيضاح مخصصة ومقسمة إلى ثلاث فئات (قوة، جيد، تطوير).
 * - التعامل مع حالة عدم وجود بيانات في فئة معينة.
 */
function createBarChart() {
    const barCtx = document.getElementById('performanceBarChart')?.getContext('2d');
    if (!barCtx || !barChartData || !barChartData.labels || barChartData.labels.length === 0) return;

    // --- 1. إنشاء المخطط الدائري ---
    charts.barChart = new Chart(barCtx, {
        type: 'doughnut',
        data: {
            labels: barChartData.labels,
            datasets: [{
                data: barChartData.scores,
                backgroundColor: barChartData.backgroundColors,
                borderColor: '#000',
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
                // =================================================================
    //          ===>  المنطق الجديد للتفاعل عند التمرير  <===
    // =================================================================
    onHover: (event, chartElement) => {
        // 1. الحصول على كل عناصر وسيلة الإيضاح المخصصة
        const allLegendItems = document.querySelectorAll('#custom-legend-container li');

        // 2. التحقق إذا كان المؤشر فوق إحدى شرائح المخطط
        if (chartElement.length > 0) {
            // جلب العنصر النشط
            const activeElement = chartElement[0];
            const activeIndex = activeElement.index;

            // 3. المرور على كل عناصر وسيلة الإيضاح
            allLegendItems.forEach((item, itemIndex) => {
                // إذا لم يكن هذا هو العنصر المرتبط بالشريحة النشطة
                if (itemIndex !== activeIndex) {
                    // أضف كلاس التلاشي
                    item.classList.add('legend-item-faded');
                } else {
                    // إذا كان هو العنصر النشط، قم بإزالة أي تلاشي وأضف كلاس الإبراز
                    item.classList.remove('legend-item-faded');
                    item.classList.add('legend-item-active'); // اختياري لكنه يعطي تأثيراً جميلاً
                }
            });
        } else {
            // 4. إذا ابتعد المؤشر عن كل الشرائح، أعد كل شيء لحالته الطبيعية
            allLegendItems.forEach(item => {
                item.classList.remove('legend-item-faded');
                item.classList.remove('legend-item-active');
            });
        }
    },

          plugins: {
                        // 1. تعطيل وسيلة الإيضاح الافتراضية (تبقى كما هي)
                        legend: { 
                            display: false 
                        },

                        // 2. [الحل] إعادة تفعيل وتخصيص التلميحات (Tooltips)
                        tooltip: {
                            enabled: true, // التأكد من أنها مفعلة
                            backgroundColor: 'rgba(0, 0, 0, 0.8)', // لون خلفية التلميح
                            titleFont: {
                                size: 14,
                                weight: 'bold',
                                family: 'Cairo, sans-serif'
                            },
                            bodyFont: {
                                size: 12,
                                family: 'Cairo, sans-serif'
                            },
                            boxPadding: 8, // حشوة داخلية
                            callbacks: {
                                // تخصيص محتوى التلميح
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        // عرض النسبة المئوية مع تقريبها
                                        label += Math.round(context.parsed) + '%';
                                    }
                                    return label;
                                }
                            }
                        },

                        // 3. إعدادات عرض النسب على الشرائح (تبقى كما هي)
                        datalabels: {
                            formatter: (value) => value > 5 ? Math.round(value) + '%' : '',
                            color: '#fff',
                            font: { weight: 'bold', size: 12 },
                            textShadow: {
                                color: 'rgba(0, 0, 0, 0.7)',
                                blur: 4
                            }
                        }
                    }

        }
    });

    // --- 2. بناء وسيلة الإيضاح المخصصة ديناميكياً ---
    const legendStrongUl = document.getElementById('legend-strong');
    const legendGoodUl = document.getElementById('legend-good');
    const legendWeakUl = document.getElementById('legend-weak');

    // مسح أي محتوى قديم
    legendStrongUl.innerHTML = '';
    legendGoodUl.innerHTML = '';
    legendWeakUl.innerHTML = '';

    let strongCount = 0, goodCount = 0, weakCount = 0;

    barChartData.labels.forEach((label, index) => {
        const score = barChartData.scores[index];
        const color = barChartData.backgroundColors[index];

        // إنشاء عنصر القائمة (<li>)
        const listItem = document.createElement('li');
        listItem.innerHTML = `
            <span class="legend-color-box" style="background-color: ${color};"></span>
            <span>${label} (${Math.round(score)}%)</span>
        `;

        // تصنيف العنصر ووضعه في القائمة المناسبة
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

    // --- 3. عرض رسالة في حال كانت إحدى الفئات فارغة ---
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


 // =================================================================
    //   ===>  2. دالة إنشاء المخطط الخطي الجديد (تطور الأداء)  <===
    // =================================================================
    // في ملف: individual_report_view.php

function createCompetencyTrendChart() {
    const trendCtx = document.getElementById('competencyTrendChart')?.getContext('2d');
    const trendData = detailedTrend ? detailedTrend.trend_chart_data : null;
    
    if (!trendCtx || !trendData || trendData.datasets.length === 0) {
        const trendCard = document.getElementById('trend-chart-card');
        if (trendCard) trendCard.style.display = 'none';
        return;
    }

    // =================================================================
    //          ===>  ▼▼▼ بداية التعديلات المطلوبة ▼▼▼  <===
    // =================================================================

    // 1. تعديل على البيانات القادمة لجعل الألوان أكثر وضوحاً
    const processedDatasets = trendData.datasets.map(dataset => {
        // إزالة الشفافية من لون الخلفية (لوسيلة الإيضاح) وزيادة سماكة الخط
        dataset.backgroundColor = dataset.borderColor; // اجعل لون الخلفية هو نفسه لون الحدود
        dataset.borderWidth = 2.5; // زيادة سماكة الخط قليلاً
        dataset.pointRadius = 4; // حجم النقطة
        dataset.pointHoverRadius = 6; // حجم النقطة عند التمرير
        return dataset;
    });

    charts.competencyTrendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.labels.map(dateStr => new Date(dateStr).toLocaleDateString('ar-SA', { month: 'short', year: 'numeric' })),
            datasets: processedDatasets // <-- استخدام البيانات المُعالجة
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
                        boxWidth: 10, // زيادة حجم مربع اللون قليلاً
                        padding: 20,
                        font: {
                            size: 11 // حجم خط وسيلة الإيضاح
                        }
                    }
                },
                tooltip: {
                    // 2. تعديل موضع التلميحات لتجنب تغطية النقاط العليا
                    position: 'average', // <-- يضع التلميح في منتصف النقاط على المحور الرأسي
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: { size: 13 },
                    bodyFont: { size: 12 },
                    boxPadding: 6,
                }
            },
            scales: {
                y: {
                    min: 0,
                    // 3. إضافة هامش علوي بسيط للمحور Y (احتياطي)
                    suggestedMax: 105, // <-- يعطي مساحة إضافية في الأعلى
                    title: { display: true, text: 'درجة الأداء (%)', font: { size: 13 } }
                },
                x: {
                    title: { display: true, text: 'تاريخ التقييم', font: { size: 13 } }
                }
            }
        }
    });
    // =================================================================
    //          ===>  ▲▲▲ نهاية التعديلات المطلوبة ▲▲▲  <===
    // =================================================================
}


    // في ملف: individual_report_view.php

function createTrendChart() {
    const trendCtx = document.getElementById('performanceTrendChart')?.getContext('2d');
    if (!trendCtx || !performanceHistory || performanceHistory.length < 2) return;

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
                borderWidth: 3,
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 5, // زيادة حجم النقطة قليلاً
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                datalabels: {
                    align: 'end', // <-- التعديل الأول: يضع الملصق داخل النقطة في الأعلى
                    offset: 8,    // <-- التعديل الثاني: يدفعه للأعلى قليلاً خارج النقطة
                    backgroundColor: 'rgba(13, 110, 253, 0.9)',
                    borderRadius: 4,
                    color: '#000',
                    padding: 6,
                    font: { size: 10, weight: 'bold' },
                    formatter: (value) => Math.round(value) + '%'
                }
            },
            scales: {
                y: {
                    // التعديل الثالث: إضافة هامش علوي للمحور
                    suggestedMax: 105, 
                    suggestedMin: Math.max(0, Math.min(...scores) - 10)
                }
            }
        }
    });
}

/**
 * =================================================================
 *   ===>  دالة إنشاء المخطط العنكبوتي (النسخة النهائية مع تلوين النقاط)  <===
 * =================================================================
 * التحسينات:
 * - تلوين كل نقطة على المخطط بلون مختلف (أخضر، أصفر، أحمر) بناءً على درجتها.
 * - تلوين خلفية النقطة (pointBackgroundColor) لتمييزها.
 * - إضافة وسيلة إيضاح بسيطة أسفل المخطط لتفسير الألوان.
 * - تعديل لون المساحة الداخلية (backgroundColor) ليكون محايداً.
 */
function createRadarChart(evalId) {
    const canvas = document.getElementById(`radarChart-${evalId}`);
    if (!canvas || Chart.getChart(canvas) || !historyTableData) return;

    const evalData = historyTableData[evalId];
    let labels = [], scores = [];

    if (evalData && evalData.criteria) {
        for (const criterionName in evalData.criteria) {
            evalData.criteria[criterionName].sections.forEach(section => {
                labels.push(section.section_name);
                scores.push(section.score);
            });
        }
    }

    if (labels.length > 2) {
        // --- 1. [الحل] إنشاء مصفوفات الألوان الديناميكية ---
        const pointBackgroundColors = scores.map(score => {
            if (score >= 80) return 'rgba(25, 135, 84, 1)';    // أخضر للقوة
            if (score < 60) return 'rgba(220, 53, 69, 1)';     // أحمر للضعف
            return 'rgba(255, 193, 7, 1)'; // أصفر/برتقالي للجيد
        });

        charts[`radarChart_${evalId}`] = new Chart(canvas, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'مستوى الأداء',
                    data: scores,
                    // --- 2. تطبيق الألوان على المخطط ---
                    pointBackgroundColor: pointBackgroundColors, // لون خلفية النقطة
                    pointBorderColor: '#fff', // حدود بيضاء للنقاط
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: pointBackgroundColors,
                    pointRadius: 5, // زيادة حجم النقطة قليلاً
                    pointHoverRadius: 7,
                    
                    fill: true,
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',  // لون أزرق شفاف ومحايد للمساحة
                    borderColor: 'rgba(13, 110, 253, 0.6)',      // لون أزرق للخطوط
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false }, // إخفاء وسيلة الإيضاح الافتراضية
                    tooltip: { // تخصيص التلميح
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
                        ticks: { stepSize: 25, backdropColor: 'transparent' },
                        pointLabels: {
                            font: { size: 10, family: 'Cairo, sans-serif' }
                        },
                        grid: { color: '#e9ecef' } // لون شبكة المخطط
                    } 
                }
            }
        });

        // --- 3. إضافة وسيلة الإيضاح المخصصة للألوان أسفل المخطط ---
        // لقد قمت بالفعل بتضمينها في كود HTML في رد سابق، هذا الكود للتأكيد.
        // يمكنك التأكد من وجود الكود التالي في ملف HTML أسفل حاوية المخطط.
        /*
        <div class="d-flex justify-content-center align-items-center mt-2 small text-muted flex-wrap">
            <div class="d-flex align-items-center mx-2"><span style="width: 12px; height: 12px; background-color: rgba(25, 135, 84, 1); border-radius: 60%; margin-left: 4px;"></span>أداء متميز</div>
            <div class="d-flex align-items-center mx-2"><span style="width: 12px; height: 12px; background-color: rgba(255, 193, 7, 1); border-radius: 60%; margin-left: 4px;"></span>أداء جيد</div>
            <div class="d-flex align-items-center mx-2"><span style="width: 12px; height: 12px; background-color: rgba(220, 53, 69, 1); border-radius: 60%; margin-left: 4px;"></span>يحتاج لتطوير</div>
        </div>
        */

    } else {
        // في حال عدم وجود بيانات كافية، اعرض رسالة
        canvas.parentElement.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted p-4">يتطلب عرض المخطط وجود 3 كفاءات على الأقل.</div>';
    }
}

  
    // =================================================================
    // 2. معالجات الأحداث للطباعة والتفاعل (النسخة المُبسَّطة والفعالة)
    // =================================================================

    // --- التحضير للطباعة (الجزء الأهم) ---
    window.addEventListener('beforeprint', () => {
        // 1. تعطيل الحركات في المخططات لجعلها ثابتة وواضحة في الطباعة
        Chart.defaults.animation = false;
        Object.values(charts).forEach(chart => {
            if (chart) {
                chart.options.animation = false;
                chart.resize(); // إعادة حساب الحجم للطباعة
                chart.update('none');
            }
        });

        // 2. فتح كل أقسام الأكورديون المخفية لضمان طباعة محتواها
        document.querySelectorAll('.accordion-collapse').forEach(collapse => {
            if (!collapse.classList.contains('show')) {
                new bootstrap.Collapse(collapse, { toggle: false }).show();
            }
        });
    });

    // --- ما بعد الطباعة ---
    window.addEventListener('afterprint', () => {
        // إعادة تفعيل الحركات في المخططات للعرض العادي
        Chart.defaults.animation = { duration: 500 };
        Object.values(charts).forEach(chart => {
            if (chart) {
                chart.options.animation = { duration: 500 };
                chart.update();
            }
        });
    });

    // --- ربط حدث الفتح اليدوي للأكورديون لإنشاء المخططات عند الحاجة ---
    document.querySelectorAll('.accordion-collapse').forEach(collapseElement => {
        collapseElement.addEventListener('shown.bs.collapse', function () {
            const evalId = this.id.replace('collapse-', '');
            createRadarChart(evalId);
        }, { once: true }); // { once: true } تضمن أن يتم استدعاء الدالة مرة واحدة فقط
    });

    // =================================================================
    // 3. التنفيذ الأولي (إنشاء المخططات الرئيسية عند تحميل الصفحة)
    // =================================================================
    
    createBarChart();
    createTrendChart();
createCompetencyTrendChart();
    // ملاحظة: الكود الخاص بتصدير PDF يبقى كما هو لأنه يعتمد على زر آخر.
});
</script>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>