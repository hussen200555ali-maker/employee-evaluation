<?php 
// ملف: app/Views/analysis/report_view.php (النسخة الاحترافية v3.0)
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<!-- تضمين مكتبة Chart.js -->
<script src="<?php echo APP_URL; ?>/public/libs/chartjs/chart.umd.min.js"></script>

<!-- CSS خاص بالطباعة -->
<style>
    @media print {
        body * {
            visibility: hidden; /* إخفاء كل شيء أولاً */
        }
        #print-area, #print-area * {
            visibility: visible; /* إظهار منطقة الطباعة ومحتوياتها فقط */
        }
        #print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none; /* إخفاء الأزرار عند الطباعة */
        }
        .card {
            border: 1px solid #dee2e6 !important;
            box-shadow: none !important;
        }
        a {
            text-decoration: none;
            color: #000;
        }
        .badge {
            border: 1px solid #ccc;
        }
    }
</style>

<div class="container mt-4">
    <div id="print-area">
        <div class="page-header d-flex justify-content-between align-items-center mb-4 no-print">
            <h2 class="mb-0"><i class="fas fa-file-signature text-primary"></i> تقرير التحليل</h2>
            <div>
                <!-- زر الطباعة الجديد -->
                <button onclick="window.print( );" class="btn btn-info me-2">
                    <i class="fas fa-print"></i> طباعة
                </button>
                <!-- زر تصدير PDF (يجب أن يعمل الآن) -->
                <a href="<?php echo APP_URL . '/analysis/export?' . $_SERVER['QUERY_STRING']; ?>" class="btn btn-danger me-2" target="_blank">
                    <i class="fas fa-file-pdf"></i> تصدير PDF
                </a>
                <a href="<?php echo APP_URL; ?>/analysis" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> العودة
                </a>
            </div>
        </div>

        <!-- عنوان التقرير -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light py-3">
                <h4 class="mb-0 text-center fw-bold"><?php echo $reportTitle; ?></h4>
                <p class="text-center text-muted mb-0">
                    تم تحليل <?php echo $analysis['totalEvaluations']; ?> إجابة من <?php echo $analysis['totalEmployees']; ?> موظف/موظفين
                </p>
            </div>
        </div>

        <?php if ($analysis['totalEvaluations'] === 0): ?>
            <div class="alert alert-warning text-center" role="alert">
                <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> لا توجد بيانات!</h4>
                <p class="mb-0">لم يتم العثور على تقييمات مكتملة تطابق معايير البحث.</p>
            </div>
        <?php else: ?>
            <!-- قسم الكفاءات المتميزة -->
            <div class="card mb-4">
                <div class="card-header bg-success bg-opacity-10 border-success">
                    <h5 class="mb-0 text-success"><i class="fas fa-star"></i> كفاءات متميزة (متوسط أداء ≥ 85%)</h5>
                </div>
                <?php if (empty($analysis['analysis']['excellent'])): ?>
                    <div class="card-body text-muted">لا توجد كفاءات ضمن هذا النطاق.</div>
                <?php else: ?>
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>الكفاءة</th>
                                <th class="text-center">متوسط الأداء</th>
                                <th class="text-center">عدد المتميزين</th>
                                <th class="text-center">عدد المحتاجين للتطوير</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analysis['analysis']['excellent'] as $name => $data): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($name); ?></td>
                                <td class="text-center"><span class="badge bg-success fs-6"><?php echo $data['average']; ?>%</span></td>
                                <td class="text-center"><?php echo $data['strong_count']; ?> من <?php echo $data['total_employees']; ?></td>
                                <td class="text-center"><?php echo $data['weak_count']; ?> من <?php echo $data['total_employees']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- قسم الكفاءات الجيدة -->
            <div class="card mb-4">
                <div class="card-header bg-primary bg-opacity-10 border-primary">
                    <h5 class="mb-0 text-primary"><i class="fas fa-check"></i> كفاءات جيدة (60% ≤ متوسط < 85%)</h5>
                </div>
                <?php if (empty($analysis['analysis']['good'])): ?>
                    <div class="card-body text-muted">لا توجد كفاءات ضمن هذا النطاق.</div>
                <?php else: ?>
                     <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>الكفاءة</th>
                                <th class="text-center">متوسط الأداء</th>
                                <th class="text-center">عدد المتميزين</th>
                                <th class="text-center">عدد المحتاجين للتطوير</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analysis['analysis']['good'] as $name => $data): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($name); ?></td>
                                <td class="text-center"><span class="badge bg-primary fs-6"><?php echo $data['average']; ?>%</span></td>
                                <td class="text-center"><?php echo $data['strong_count']; ?> من <?php echo $data['total_employees']; ?></td>
                                <td class="text-center"><?php echo $data['weak_count']; ?> من <?php echo $data['total_employees']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- قسم الكفاءات التي تحتاج لتطوير -->
            <div class="card mb-4">
                <div class="card-header bg-danger bg-opacity-10 border-danger">
                    <h5 class="mb-0 text-danger"><i class="fas fa-chart-line"></i> كفاءات تحتاج إلى تطوير (متوسط أداء < 60%)</h5>
                </div>
                <?php if (empty($analysis['analysis']['needs_improvement'])): ?>
                    <div class="card-body text-muted">لا توجد كفاءات ضمن هذا النطاق.</div>
                <?php else: ?>
                     <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>الكفاءة</th>
                                <th class="text-center">متوسط الأداء</th>
                                <th class="text-center">عدد المتميزين</th>
                                <th class="text-center">عدد المحتاجين للتطوير</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analysis['analysis']['needs_improvement'] as $name => $data): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($name); ?></td>
                                <td class="text-center"><span class="badge bg-danger fs-6"><?php echo $data['average']; ?>%</span></td>
                                <td class="text-center"><?php echo $data['strong_count']; ?> من <?php echo $data['total_employees']; ?></td>
                                <td class="text-center"><?php echo $data['weak_count']; ?> من <?php echo $data['total_employees']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- الرسم البياني -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> متوسط الأداء في كل الكفاءات (مرتبة من الأعلى للأدنى)</h5>
                </div>
                <div class="card-body">
                    <canvas id="analysisChart"></canvas>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('analysisChart').getContext('2d');
                const chartData = <?php echo json_encode($analysis['performanceChartData']); ?>;
                new Chart(ctx, { /* ... نفس كود الجافاسكريبت للرسم البياني من الرد السابق ... */ });
            });
            </script>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($performanceHistory) && count($performanceHistory) > 1): ?>
    <!-- قسم تحليل تطور الأداء -->
    <div class="card mt-4 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-chart-line text-primary"></i> تقرير تطور الأداء الفردي</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">الرسم البياني التالي يوضح تطور المتوسط الكلي لأداء الموظف عبر التقييمات المختلفة.</p>
            <canvas id="performanceTrendChart" style="max-height: 300px;"></canvas>
            
            <h6 class="mt-5 border-top pt-3">سجل التقييمات التاريخي</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mt-2">
                    <thead class="table-light">
                        <tr>
                            <th>تاريخ التقييم</th>
                            <th>نوع الاستمارة</th>
                            <th class="text-center">المتوسط الكلي</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($performanceHistory) as $historyItem): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($historyItem->evaluation_date)); ?></td>
                            <td><?php echo htmlspecialchars($historyItem->template_name); ?></td>
                            <td class="text-center fw-bold"><?php echo round($historyItem->final_score, 2); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- هذا الجزء خاص بالرسم البياني الخطي الجديد ---
        const trendCtx = document.getElementById('performanceTrendChart').getContext('2d');
        const historyData = <?php echo json_encode($performanceHistory); ?>;
        
        const trendLabels = historyData.map(item => new Date(item.evaluation_date).toLocaleDateString('ar-SA'));
        const trendScores = historyData.map(item => item.final_score);

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'تطور متوسط الأداء',
                    data: trendScores,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false, // قد يكون من الأفضل عدم البدء من الصفر لإظهار الفروقات بشكل أوضح
                        min: Math.min(...trendScores) - 10 > 0 ? Math.min(...trendScores) - 5 : 40,
                        max: 100,
                        ticks: {
                            callback: function(value) { return value.toFixed(0) + '%' }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + '%';
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
<?php endif; ?>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
