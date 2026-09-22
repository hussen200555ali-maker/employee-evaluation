<?php
// app/Views/backup/pro_dashboard.php
$this->view('layouts/header', ['pageTitle' => 'لوحة التحكم الاحترافية للنسخ الاحتياطي']);

// تأكد من أن المتغيرات موجودة
$recentBackups = $recentBackups ?? [];
$stats = $stats ?? (object)['success_rate' => 0];
$health = $health ?? [];

// تحويل health إلى مصفوفة إذا كان كائناً
if (is_object($health)) {
    $healthArray = json_decode(json_encode($health), true);
} else {
    $healthArray = $health;
}
?>

<main class="container-fluid specialised-dashboard backup-dashboard">
    <!-- شريط الحالة السريع -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card backup-dashboard__hero text-white">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h4><?php echo $healthArray['disk_space']['usage_percent'] ?? 0 ?>%</h4>
                            <small>استخدام التخزين</small>
                        </div>
                        <div class="col-md-3">
                            <h4><?php echo $healthArray['error_rate'] ?? 0 ?>%</h4>
                            <small>معدل الأخطاء</small>
                        </div>
                        <div class="col-md-3">
                            <h4><?php echo $stats->success_rate ?? 0 ?>%</h4>
                            <small>معدل النجاح</small>
                        </div>
                        <div class="col-md-3">
                            <h4><?php echo $healthArray['backup_integrity']['success_rate'] ?? 0 ?>%</h4>
                            <small>سلامة النسخ</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- العمليات السريعة -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-bolt ms-1"></i> العمليات السريعة</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo APP_URL; ?>/backup/smart" class="btn btn-primary btn-lg">
                            <i class="fas fa-brain ms-1"></i> نسخ ذكي تلقائي
                        </a>
                        <a href="<?php echo APP_URL; ?>/backup/verify" class="btn btn-success">
                            <i class="fas fa-search ms-1"></i> فحص سلامة النسخ
                        </a>
                        <a href="<?php echo APP_URL; ?>/backup/health" class="btn btn-warning">
                            <i class="fas fa-heartbeat ms-1"></i> فحص صحة النظام
                        </a>
                        <a href="<?php echo APP_URL; ?>/backup/encryption" class="btn btn-info">
                            <i class="fas fa-lock ms-1"></i> إدارة التشفير
                        </a>
                    </div>
                </div>
            </div>

            <!-- حالة النظام -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-heartbeat ms-1"></i> حالة النظام</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $components = [
                        'disk_space' => 'مساحة التخزين',
                        'error_rate' => 'معدل الأخطاء', 
                        'backup_integrity' => 'سلامة النسخ',
                        'last_backup' => 'آخر نسخة'
                    ];
                    
                    foreach ($components as $component => $name): 
                        $statusInfo = $healthArray[$component] ?? [];
                        $statusValue = is_array($statusInfo) ? ($statusInfo['status'] ?? 'unknown') : 'unknown';
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><?php echo $name; ?></span>
                        <span class="badge bg-<?php echo $this->getStatusColor($statusValue); ?>">
                            <?php echo $this->getStatusText($statusValue); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- الإحصائيات والرسوم البيانية -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-chart-line ms-1"></i> أداء النسخ الاحتياطي</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="successRateChart" width="400" height="200"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="storageUsageChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- النسخ الحديثة -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-history ms-1"></i> آخر النسخ</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>النسخة</th>
                                    <th>النوع</th>
                                    <th>الحجم</th>
                                    <th>الحالة</th>
                                    <th>التشفير</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentBackups)): ?>
                                    <?php foreach ($recentBackups as $backup): ?>
                                    <tr>
                                        <td>
                                            <small><?php echo date('Y-m-d H:i', strtotime($backup->created_at)); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $backup->backup_type === 'full' ? 'primary' : 'success'; ?>">
                                                <?php echo $backup->backup_type === 'full' ? 'كاملة' : 'تزايدية'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $this->formatBytes($backup->file_size ?? 0); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $backup->status === 'success' ? 'success' : 'danger'; ?>">
                                                <?php echo $backup->status === 'success' ? 'ناجحة' : 'فاشلة'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $backup->is_encrypted ? 'success' : 'secondary'; ?>">
                                                <?php echo $backup->is_encrypted ? 'مشفر' : 'غير مشفر'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($backup->status === 'success'): ?>
                                                <a href="<?php echo APP_URL; ?>/backup/download/<?php echo $backup->id; ?>" class="btn btn-info">
                                                    📥
                                                </a>
                                                <a href="<?php echo APP_URL; ?>/backup/restore/<?php echo $backup->id; ?>" class="btn btn-warning" onclick="return confirm('تحذير: استعادة النسخة الاحتياطية ستحذف جميع البيانات الحالية. هل تريد المتابعة؟')">
                                                    🔄
                                                </a>
                                                <a href="<?php echo APP_URL; ?>/backup/delete/<?php echo $backup->id; ?>" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه النسخة الاحتياطية؟')">
                                                    🗑️
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">لا توجد نسخ احتياطية</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="<?php echo APP_URL; ?>/public/libs/chartjs/chart.umd.min.js"></script>
<script>
// مخطط معدل النجاح
const successCtx = document.getElementById('successRateChart');
if (successCtx) {
    new Chart(successCtx, {
        type: 'doughnut',
        data: {
            labels: ['ناجحة', 'فاشلة'],
            datasets: [{
                data: [<?php echo $stats->success_rate ?? 0; ?>, <?php echo max(0, 100 - ($stats->success_rate ?? 0)); ?>],
                backgroundColor: ['#28a745', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'معدل النجاح'
                }
            }
        }
    });
}

// مخطط استخدام التخزين
const storageCtx = document.getElementById('storageUsageChart');
if (storageCtx) {
    new Chart(storageCtx, {
        type: 'bar',
        data: {
            labels: ['مستخدم', 'متاح'],
            datasets: [{
                data: [<?php echo $healthArray['disk_space']['usage_percent'] ?? 0; ?>, <?php echo max(0, 100 - ($healthArray['disk_space']['usage_percent'] ?? 0)); ?>],
                backgroundColor: ['#007bff', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'استخدام التخزين %'
                }
            }
        }
    });
}
</script>

<?php
$this->view('layouts/footer');
?>
