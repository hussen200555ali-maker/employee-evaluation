<?php
// صفحة طباعة احترافية لتقرير التحليل
// احفظ هذا الملف في: app/Views/analysis/printable_report.php
// الاستخدام المقترح: ارجع الرابط إلى هذه الصفحة من زر "تصدير التقرير (PDF)" أو افتحها في نافذة جديدة.
// المتغيرات المتوقعة: $analysis, $entityInfo, $reportTitle (اختياري), $performanceHistory, $detailedTrend, $employee (اختياري)

?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?php echo htmlspecialchars($reportTitle ?? 'تقرير تحليلي'); ?></title>

<link href="<?php echo APP_URL; ?>/public/libs/bootstrap-5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?php echo APP_URL; ?>/public/libs/fontawesome/css/all.min.css">

<style>
    /* خطوط (اختياري - تأكد من وجود الملفات) */
    @font-face {
        font-family: 'Cairo';
        src: url('<?php echo APP_URL; ?>/fonts/Cairo-Regular.ttf') format('truetype');
        font-weight: 400;
    }
    body { font-family: 'Cairo', sans-serif; color:#111; background:#fff; margin:0; padding:0; font-size:11pt; }

    /* إعدادات الطباعة العامة */
    @page { size: A4 landscape; margin: 12mm 12mm 12mm 12mm; }
    @media print {
        html, body { height: auto; }
        .no-print { display: none !important; }
        .print-footer { position: fixed; bottom: 0; left: 12mm; right: 12mm; height: 14mm; font-size:9pt; border-top:1px solid #ddd; background:#fff; display: table; }
        .print-header { position: fixed; top: 0; left: 12mm; right: 12mm; height: 28mm; border-bottom:1px solid #ddd; background:#fff; display: block; padding-bottom:6px; }
        #report-body { margin-top: 34mm; margin-bottom: 20mm; }
        /* تكرار رأس الجداول في الطباعة */
        table.repeating thead { display: table-header-group; }
        table.repeating tfoot { display: table-footer-group; }
    }

    /* تخطيط الشاشة (غير مطبوع) */
    .report-wrap { max-width: 1200px; margin: 18px auto; padding: 10px; }
    .print-actions { margin-bottom:12px; }

    /* رأس التقرير وبطاقات الملخص */
    .report-header { display:flex; gap:12px; align-items:center; }
    .entity-card { width: 260px; border:1px solid #e6e6e6; border-radius:6px; padding:10px; background:#fff; }
    .kpis { display:flex; gap:12px; flex:1; }
    .kpi { flex:1; border-radius:8px; padding:12px; color:#fff; position:relative; overflow:hidden; min-height:84px; }
    .kpi .icon { position:absolute; top:8px; left:8px; opacity:0.12; font-size:72px; }
    .kpi.primary { background: linear-gradient(45deg,#0d6efd,#0dcaf0); }
    .kpi.success { background: linear-gradient(45deg,#198754,#20c997); }
    .kpi.danger { background: linear-gradient(45deg,#dc3545,#fd7e14); }

    /* أقسام التقرير */
    .section { margin-bottom: 14px; page-break-inside: avoid; }
    .section h4 { font-size:12pt; margin-bottom:8px; }

    /* جداول */
    table { width:100%; border-collapse:collapse; font-size:10pt; }
    table th, table td { border: 1px solid #ddd; padding:5px 6px; vertical-align: middle; }
    table thead th { background:#f5f5f5; font-weight:700; }

    /* مصفوفة وأعمدة الرسم */
    .charts-row { display:flex; gap:12px; }
    .charts-row .chart-box { flex:1; background:#fff; border:1px solid #eaeaea; border-radius:6px; padding:10px; min-height:240px; }

    /* تذييل الطباعة مع عداد الصفحة */
    .print-footer table { width:100%; border:none; }
    .print-footer .page-number::after { content: counter(page); }
    .print-footer .page-total::after { content: counter(pages); } /* قد لا يدعمه كل المتصفحات */

    /* تحسين للطباعة: تجنب كسر الصفحات داخل البطاقات والتفاصيل */
    .avoid-break { page-break-inside: avoid; }

    /* تحسين للصور المسحوبة من الكانفاس */
    .chart-img { display:block; width:100%; height:auto; border:1px solid #eee; }

    /* فوتر على الشاشة */
    .screen-footer { text-align:center; margin-top:12px; color:#666; font-size:12px; }
</style>
</head>
<body>

<div class="report-wrap container-fluid">
    <div class="no-print print-actions d-flex justify-content-between align-items-center">
        <div>
            <a href="#" id="btn-print" class="btn btn-primary"><i class="fas fa-print me-2"></i>طباعة / حفظ كـ PDF</a>
            <a href="<?php echo APP_URL; ?>/analysis" class="btn btn-secondary ms-2"><i class="fas fa-arrow-left me-2"></i>العودة للواجهة</a>
        </div>
        <div>
            <small class="text-muted">قم بمعاينة التقرير قبل حفظه كملف PDF للحصول على أفضل نتائج.</small>
        </div>
    </div>

    <!-- ترويسة الطباعة (ستظهر فقط عند الطباعة) -->
    <div class="print-header d-none d-print-block">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div style="text-align:right; font-size:10pt;">
                <strong>المملكة العربية السعودية</strong><br>
                اسم المنظمة
            </div>
            <div style="text-align:center;">
                <h3 style="margin:0;"><?php echo htmlspecialchars($reportTitle ?? 'تقرير تحليلي مفصل'); ?></h3>
                <div style="font-size:9pt; color:#666;">تاريخ التقرير: <?php echo date('Y-m-d H:i'); ?></div>
            </div>
            <div style="text-align:left; font-size:10pt;">
                <?php if (!empty($entityInfo)): ?>
                    <div><strong><?php echo htmlspecialchars($entityInfo['name'] ?? ''); ?></strong></div>
                    <div class="text-muted" style="font-size:9pt;"><?php echo htmlspecialchars($entityInfo['type'] ?? ''); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- المحتوى الفعلي -->
    <div id="report-body">

        <!-- رأس التقرير وبطاقات KPI -->
        <div class="section avoid-break">
            <div class="report-header">
                <div class="entity-card">
                    <div style="display:flex; gap:10px; align-items:center;">
                        <div style="font-size:36px;"><i class="fas <?php echo htmlspecialchars($entityInfo['icon'] ?? 'fa-building'); ?>"></i></div>
                        <div>
                            <div style="font-weight:700;"><?php echo htmlspecialchars($entityInfo['name'] ?? '-'); ?></div>
                            <div class="text-muted" style="font-size:10pt;"><?php echo htmlspecialchars($entityInfo['type'] ?? '-'); ?></div>
                        </div>
                    </div>
                    <hr style="margin:10px 0;">
                    <div style="font-size:10pt;">
                        <div><strong>المدير:</strong> <?php echo htmlspecialchars($entityInfo['manager'] ?? '-'); ?></div>
                        <div><strong>الموظفين:</strong> <?php echo htmlspecialchars($entityInfo['employee_count'] ?? '-'); ?></div>
                    </div>
                </div>

                <div class="kpis">
                    <div class="kpi primary">
                        <i class="fas fa-rocket icon"></i>
                        <div style="z-index:2;">
                            <div style="font-size:22pt; font-weight:800;"><?php echo ($analysis['stats']['average_performance'] ?? 0); ?>%</div>
                            <div style="opacity:0.9;">متوسط الأداء العام</div>
                        </div>
                    </div>
                    <div class="kpi success">
                        <i class="fas fa-star icon"></i>
                        <div style="z-index:2;">
                            <div style="font-size:22pt; font-weight:800;"><?php echo ($analysis['stats']['strong_competencies_count'] ?? 0); ?></div>
                            <div style="opacity:0.9;">كفاءات قوية</div>
                        </div>
                    </div>
                    <div class="kpi danger">
                        <i class="fas fa-flag icon"></i>
                        <div style="z-index:2;">
                            <div style="font-size:22pt; font-weight:800;"><?php echo ($analysis['stats']['weak_competencies_count'] ?? 0); ?></div>
                            <div style="opacity:0.9;">كفاءات للتطوير</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- قسم المخططات الملخصة -->
        <div class="section charts-row">
            <div class="chart-box">
                <h4>متوسط أداء المعايير الرئيسية</h4>
                <div style="height:300px;">
                    <canvas id="criteriaBarChart" class="avoid-break"></canvas>
                </div>
            </div>
            <div class="chart-box">
                <h4>مصفوفة الأهمية والأداء</h4>
                <div style="height:300px;">
                    <canvas id="quadrantChart" class="avoid-break"></canvas>
                </div>
            </div>
        </div>

        <!-- قسم الجداول التحليلية -->
        <div class="section avoid-break">
            <h4>تفاصيل التحليل حسب الكفاءة</h4>

            <?php
            // دالة مساعدة سريعة لرندر مجموعات الكفاءات
            function render_print_table($title, $items, $color_class = 'primary') {
                echo "<div class='mt-3 avoid-break'>";
                echo "<h5 style='color:#333;'>$title</h5>";
                if (empty($items)) {
                    echo "<p class='text-muted'>لا توجد بيانات.</p>";
                } else {
                    echo "<div style='overflow:auto;'>";
                    echo "<table class='repeating'>";
                    echo "<thead><tr><th>المعيار</th><th>الكفاءة (قسم فرعي)</th><th class='text-center'>متوسط الأداء</th><th class='text-center'>قيمة الأداء (%)</th><th class='text-center'>توزيع</th></tr></thead><tbody>";
                    foreach ($items as $it) {
                        $avg = isset($it['average_score']) ? round($it['average_score'],2) : 0;
                        $strong = intval($it['strong_count'] ?? 0);
                        $medium = intval($it['medium_count'] ?? 0);
                        $weak = intval($it['weak_count'] ?? 0);
                        $total = max(1, intval($it['total_employees'] ?? ($strong+$medium+$weak)));
                        $sp = round(($strong/$total)*100,1);
                        $mp = round(($medium/$total)*100,1);
                        $wp = round(($weak/$total)*100,1);
                        echo "<tr>";
                        echo "<td style='width:18%;'>".htmlspecialchars($it['criterion_name'] ?? '-')."</td>";
                        echo "<td style='width:30%;'>".htmlspecialchars($it['section_name'] ?? $it['sectionName'] ?? '-')."</td>";
                        echo "<td class='text-center' style='width:10%;'><strong>{$avg}%</strong></td>";
                        echo "<td class='text-center' style='width:12%;'>{$avg}%</td>";
                        echo "<td style='width:30%;'>";
                        echo "<div style='display:flex; align-items:center; gap:8px;'>";
                        echo "<div style='flex:1; height:10px; background:#eee; border-radius:6px; overflow:hidden; display:flex;'>";
                        echo "<div style='width:{$wp}%; background:#dc3545;'></div>";
                        echo "<div style='width:{$mp}%; background:#0d6efd;'></div>";
                        echo "<div style='width:{$sp}%; background:#198754;'></div>";
                        echo "</div>";
                        echo "<div style='min-width:70px; text-align:left; font-size:9pt;'>";
                        echo "<span class='text-danger' style='margin-left:6px;'>{$weak} /</span>";
                        echo "<span class='text-primary' style='margin-left:6px;'>{$medium} /</span>";
                        echo "<span class='text-success'>{$strong}</span>";
                        echo "</div>";
                        echo "</div>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody></table></div>";
                }
                echo "</div>";
            }

            $perf = $analysis['classified_performance'] ?? ['strong'=>[], 'medium'=>[], 'weak'=>[]];
            render_print_table('نقاط القوة (≥ 85%)', $perf['strong'], 'success');
            render_print_table('أداء متوسط (50% - 84.9%)', $perf['medium'], 'primary');
            render_print_table('جوانب تحتاج تطوير (< 50%)', $perf['weak'], 'danger');
            ?>
        </div>

        <!-- السجل التاريخي (ملخص) -->
        <?php if (!empty($performanceHistory) && is_array($performanceHistory)): ?>
        <div class="section avoid-break">
            <h4>تطور الأداء العام</h4>
            <div style="height:200px;"><canvas id="performanceTrendChart"></canvas></div>
            <div class="mt-2">
                <small class="text-muted">هذا المخطط يعرض نتائج التقييمات عبر الزمن.</small>
            </div>
        </div>
        <?php endif; ?>

        <!-- ملاحظة واختتام -->
        <div class="section avoid-break">
            <h4>ملاحظات وتوصيات</h4>
            <ol>
                <li>اعمل على تحسين الكفاءات ذات الأهمية العالية والأداء المنخفض أولاً.</li>
                <li>استغلال نقاط القوة لتعزيز أداء الفريق أو المؤسسة.</li>
                <li>مراجعة خطط التدريب بناءً على الفجوات الظاهرة في التقرير.</li>
            </ol>
        </div>

    </div> <!-- نهاية report-body -->

    <div class="no-print screen-footer">صفحة معاينة الطباعة — افتح طباعة المتصفح أو استخدم زر "طباعة / حفظ كـ PDF".</div>

</div> <!-- نهاية report-wrap -->

<!-- تذييل الطباعة -->
<div class="print-footer d-none d-print-table">
    <table>
        <tr>
            <td style="text-align:right; font-size:9pt;">الموظف: <?php echo htmlspecialchars($employee->full_name ?? '-'); ?></td>
            <td style="text-align:center; font-size:9pt;">صفحة <span class="page-number"></span> من <span class="page-total"></span></td>
            <td style="text-align:left; font-size:9pt;">نظام تقييم الموظفين</td>
        </tr>
    </table>
</div>

<!-- مكتبات جافاسكربت -->
<script src="<?php echo APP_URL; ?>/public/libs/chartjs/chart.umd.min.js"></script>
<script src="<?php echo APP_URL; ?>/public/libs/chartjs/chartjs-plugin-datalabels.min.js"></script>
<script src="<?php echo APP_URL; ?>/public/libs/chartjs/chartjs-plugin-annotation.min.js"></script>

<script>
// تحويل بيانات PHP إلى جافاسكربت
const analysis = <?php echo json_encode($analysis ?? []); ?>;
const performanceHistory = <?php echo json_encode($performanceHistory ?? []); ?>;
const criteriaData = analysis.criteria_performance || [];

// تسجيل الإضافات
Chart.register(ChartDataLabels);
if (window.chartjsAnnotation) {
    Chart.register(window.chartjsAnnotation);
}

// --- دوال إنشاء المخططات ---
let charts = {};

function createCriteriaBarChart() {
    const ctx = document.getElementById('criteriaBarChart').getContext('2d');
    const labels = criteriaData.map(c => c.criterion_name);
    const data = criteriaData.map(c => c.average_score);
    if (charts.criteriaBarChart) charts.criteriaBarChart.destroy();

    charts.criteriaBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{ label: 'متوسط الأداء', data: data, backgroundColor: 'rgba(13,110,253,0.8)' }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display:false },
                datalabels: { anchor:'end', align:'end', formatter: v => v + '%', font: {size:10} }
            },
            scales: {
                x: { min:0, max:100, ticks:{ callback: v => v + '%' } }
            }
        }
    });
}

function createQuadrantChart() {
    const ctx = document.getElementById('quadrantChart').getContext('2d');
    const avgPerf = (analysis.stats && analysis.stats.average_performance) ? analysis.stats.average_performance : 50;
    const items = (criteriaData.length ? criteriaData : []).map(d => ({
        x: d.average_score,
        y: d.average_score, // إن أردت وزن أهمية مختلف استبدل y
        label: d.criterion_name
    }));

    if (charts.quadrantChart) charts.quadrantChart.destroy();
    charts.quadrantChart = new Chart(ctx, {
        type: 'scatter',
        data: { datasets: [{ label:'المعايير', data: items, backgroundColor:'rgba(255,193,7,0.9)', pointRadius:8 }] },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins: {
                legend:{ display:false },
                tooltip: {
                    callbacks: {
                        label: ctx => `${ctx.raw.label}: الأداء ${ctx.raw.x}%`
                    }
                },
                annotation: {
                    annotations: {
                        lineX: { type:'line', xMin: avgPerf, xMax: avgPerf, borderColor:'rgba(220,53,69,0.5)', borderWidth:2, borderDash:[6,6], label:{content:'متوسط الأداء', enabled:true} }
                    }
                }
            },
            scales: {
                x: { min:0, max:100, title: { display:true, text:'الأداء' } },
                y: { min:0, max:100, title: { display:true, text:'الأهمية' } }
            }
        }
    });
}

function createTrendChart() {
    const ctx = document.getElementById('performanceTrendChart')?.getContext('2d');
    if (!ctx || !performanceHistory || performanceHistory.length === 0) return;

    const labels = performanceHistory.map(h => (new Date(h.evaluation_date)).toLocaleDateString('ar-SA'));
    const data = performanceHistory.map(h => h.final_score || 0);

    if (charts.trendChart) charts.trendChart.destroy();
    charts.trendChart = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets: [{ label:'النتيجة النهائية', data, borderColor:'rgb(13,110,253)', backgroundColor:'rgba(13,110,253,0.1)', fill:true }] },
        options: { responsive:true, maintainAspectRatio:false, plugins: { legend:{ display:false } }, scales: { y:{ min:0, max:100 } } }
    });
}

// استدعاء الإنشاء
createCriteriaBarChart();
createQuadrantChart();
createTrendChart();

// --- قبل الطباعة: استبدال الكانفاس بصور لضمان ظهور المخططات في PDF ---
function replaceCanvasesWithImages() {
    document.querySelectorAll('canvas').forEach(canvas => {
        try {
            // بعض المتصفحات قد تمنع toDataURL لصور من مصادر مختلفة، نعالج هذا بالـ try/catch
            const dataURL = canvas.toDataURL('image/png');
            const img = document.createElement('img');
            img.src = dataURL;
            img.className = 'chart-img';
            // نحافظ على أبعاد العنصر
            img.style.width = canvas.clientWidth + 'px';
            img.style.height = canvas.clientHeight + 'px';
            canvas.parentNode.insertBefore(img, canvas);
            canvas.style.display = 'none';
        } catch (e) {
            // فشل التحويل -> نترك الكانفاس كما هو (سيظهر لكنه قد لا يطبع بشكل متوقع في بعض المتصفحات)
            console.warn('تعذر تحويل canvas إلى صورة:', e);
        }
    });
}

// --- حساب إجمالي الصفحات كاحتياط (بعض المتصفحات لا تدعم counter(pages)) ---
function computeTotalPagesFallback() {
    const content = document.getElementById('report-body');
    if (!content) return;
    // تقريب من mm إلى px (96 DPI)
    const mmToPx = mm => mm * 96 / 25.4;
    // ارتفاع ورقة A4 في الاتجاه العرضي = 210mm ، نأخذ هوامش @page
    const pageHeightMm = 210 - 12 - 12; // top&bottom margins من @page
    const pageHeightPx = mmToPx(pageHeightMm);
    const contentHeight = content.scrollHeight;
    let totalPages = Math.ceil(contentHeight / pageHeightPx);
    if (totalPages < 1) totalPages = 1;
    document.querySelectorAll('.page-total').forEach(el => {
        if (!el.textContent || el.textContent.trim() === '') el.textContent = totalPages;
    });
}

// زر الطباعة - يحول المخططات إلى صور ثم يطلب الطباعة
document.getElementById('btn-print').addEventListener('click', function (e) {
    e.preventDefault();
    // أولاً، استبدال الكانفاسات بصور (محاولة)
    replaceCanvasesWithImages();
    // حساب إجمالي الصفحات كاحتياط
    computeTotalPagesFallback();
    // تأخير بسيط لضمان أن الصور تم إدراجها
    setTimeout(() => window.print(), 350);
});

// دعم أحداث beforeprint/afterprint
window.addEventListener('beforeprint', function () {
    replaceCanvasesWithImages();
    computeTotalPagesFallback();
});
window.addEventListener('afterprint', function () {
    // لإعادة تحميل الصفحة بعد الطباعة لإرجاع الكانفاسات (اختياري) - هنا نخطي لإعادة تحميل بسيطة
    // التعليق الافتراضي: لا نفعل إعادة تحميل تلقائية، لأنها قد تزعج المستخدم.
    // location.reload();
});
</script>

</body>
</html>