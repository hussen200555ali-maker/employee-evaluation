<?php
// app/Views/backup/health_reports.php
$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>
<section class="app-hero" aria-labelledby="bk-health" style="margin-bottom:1rem">
    <div class="app-hero__left">
        <span class="app-eyebrow"><i class="fas fa-chart-column"></i> المراقبة</span>
        <h1 id="bk-health">تقارير <span class="accent">صحة النسخ</span> الاحتياطي</h1>
        <p>مراقبة تلقائية لسلامة النسخ — كسر في النسخة يكتشف فوراً مع تنبيهات فورية.</p>
        <a class="app-hero__cta" href="<?php echo APP_URL; ?>/backup"><i class="fas fa-arrow-right"></i> العودة إلى النسخ</a>
    </div>
</section>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-chart-column"></i> تقارير صحة نظام النسخ الاحتياطي</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>نوع الفحص</th>
                            <th>الحالة</th>
                            <th>التفاصيل</th>
                            <th>وقت الفحص</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($healthLogs as $log): 
                            $details = json_decode($log->details, true);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log->check_type); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $log->status === 'healthy' ? 'success' : 
                                         ($log->status === 'warning' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo $log->status === 'healthy' ? 'سليم' : 
                                           ($log->status === 'warning' ? 'تحذير' : 'حرج'); ?>
                                </span>
                            </td>
                            <td>
                                <small>
                                    <?php 
                                    if ($log->check_type === 'scheduled_check' && $details) {
                                        echo "مساحة: " . ($details['disk_space']['free_gb'] ?? 0) . " GB, ";
                                        echo "أخطاء: " . ($details['error_rate'] ?? 0) . "%";
                                    }
                                    ?>
                                </small>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($log->checked_at)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->view('layouts/footer'); ?>