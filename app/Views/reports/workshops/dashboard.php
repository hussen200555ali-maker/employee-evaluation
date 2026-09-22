<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-tachometer-alt"></i> لوحة معلومات الورش التدريبية</h2>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> طباعة التقرير
        </button>
    </div>

    <!-- KPIs -->
    <div class="row text-center mt-4">
        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <h5>إجمالي الورش</h5>
                <p class="fs-3 text-primary"><?php echo $data['total_workshops']; ?></p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <h5>ورش مكتملة</h5>
                <p class="fs-3 text-success">
                    <?php echo $data['status_counts']['completed'] ?? 0; ?>
                </p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <h5>ورش ملغاة</h5>
                <p class="fs-3 text-danger">
                    <?php echo $data['status_counts']['canceled'] ?? 0; ?>
                </p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <h5>متوسط التقييم</h5>
                <p class="fs-3 text-dark">
                    <?php echo $data['average_score']; ?> / 100
                </p>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mt-5">
        <div class="col-md-6">
            <canvas id="statusChart"></canvas>
        </div>
        <div class="col-md-6">
            <canvas id="branchChart"></canvas>
        </div>
    </div>

    <!-- Top Workshops -->
    <div class="mt-5">
        <h4><i class="fas fa-star"></i> أفضل الورش</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>عنوان الورشة</th>
                    <th>التقييم النهائي</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['top_workshops'] as $w): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($w->title); ?></td>
                        <td><?php echo $w->final_score; ?> / 100</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js -->
<script src="<?php echo APP_URL; ?>/public/libs/chartjs/chart.umd.min.js"></script>
<script>
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'bar',
    data: {
        labels: ['مجدولة', 'جارية', 'مكتملة', 'ملغاة'],
        datasets: [{
            label: 'عدد الورش',
            data: [
                <?php echo $data['status_counts']['scheduled'] ?? 0; ?>,
                <?php echo $data['status_counts']['ongoing'] ?? 0; ?>,
                <?php echo $data['status_counts']['completed'] ?? 0; ?>,
                <?php echo $data['status_counts']['canceled'] ?? 0; ?>
            ],
            backgroundColor: ['#0d6efd','#ffc107','#198754','#dc3545']
        }]
    }
});

const branchCtx = document.getElementById('branchChart').getContext('2d');
new Chart(branchCtx, {
    type: 'pie',
    data: {
        labels: [<?php foreach($data['branch_distribution'] as $b) echo "'$b->branch_name',"; ?>],
        datasets: [{
            label: 'ورش حسب الفروع',
            data: [<?php foreach($data['branch_distribution'] as $b) echo $b->cnt.","; ?>],
            backgroundColor: ['#0dcaf0','#fd7e14','#198754','#6f42c1','#dc3545']
        }]
    }
});
</script>

<!-- Print Styles -->
<style>
@media print {
    body {
        font-family: "Times New Roman", serif;
        font-size: 14pt;
    }
    .btn, .page-header button, nav, footer {
        display: none !important; /* إخفاء الأزرار عند الطباعة */
    }
    .container-fluid {
        margin: 0 auto;
        width: 210mm; /* عرض A4 */
        height: 297mm; /* طول A4 */
    }
    .card, .table {
        page-break-inside: avoid; /* منع تقطيع الجداول */
    }
}
</style>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
