<?php
// app/Views/backup/index.php

$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
          <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">النسخ الاحتياطي للنظام</h5>
                        <a href="<?php echo APP_URL; ?>/backup/settings" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-cog"></i> إعدادات النسخ
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- إحصائيات سريعة -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6>آخر نسخة احتياطية</h6>
                                    <h4>
                                        <?php echo $lastBackup ? 
                                            date('Y-m-d H:i', strtotime($lastBackup->created_at)) : 
                                            'لا توجد'; ?>
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6>النسخ الناجحة</h6>
                                    <h4>
                                        <?php 
                                        $successCount = 0;
                                        foreach ($logs as $log) {
                                            if ($log->status === 'success') $successCount++;
                                        }
                                        echo $successCount;
                                        ?>
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6>إجمالي النسخ</h6>
                                    <h4><?php echo count($logs); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- أزرار الإجراءات -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-primary" onclick="createFullBackup()">
                                    <i class="fas fa-database"></i> نسخة كاملة
                                </button>
                                <button type="button" class="btn btn-success" onclick="createIncrementalBackup()">
                                    <i class="fas fa-sync"></i> نسخة تزايدية
                                </button>
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                                    <i class="fas fa-clock"></i> جدولة تلقائية
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- جدول سجل النسخ -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>اسم النسخة</th>
                                    <th>النوع</th>
                                    <th>الحجم</th>
                                    <th>الحالة</th>
                                    <th>المنشئ</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log->backup_name); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $log->backup_type === 'full' ? 'primary' : 'success'; ?>">
                                            <?php echo $log->backup_type === 'full' ? 'كاملة' : 'تزايدية'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $log->file_size ? 
                                            round($log->file_size / 1024 / 1024, 2) . ' MB' : 
                                            'غير متوفر'; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $log->status === 'success' ? 'success' : 
                                                  ($log->status === 'failed' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo $log->status === 'success' ? 'ناجح' : 
                                                  ($log->status === 'failed' ? 'فاشل' : 'قيد التنفيذ'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log->username ?? 'النظام'); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($log->created_at)); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($log->status === 'success' && file_exists($log->file_path)): ?>
                                              <a href="<?php echo APP_URL; ?>/backup/download/<?php echo $log->id; ?>" class="btn btn-info">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button class="btn btn-warning" onclick="restoreBackup(<?php echo $log->id; ?>)">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-danger" onclick="deleteBackup(<?php echo $log->id; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal الجدولة التلقائية -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">جدولة النسخ الاحتياطي التلقائي</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleForm">
                    <div class="mb-3">
                        <label class="form-label">نوع النسخ</label>
                        <select class="form-control" name="backup_type">
                            <option value="full">نسخ كامل</option>
                            <option value="incremental">نسخ تزايدي</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التكرار</label>
                        <select class="form-control" name="schedule_type" id="scheduleType">
                            <option value="daily">يومي</option>
                            <option value="weekly">أسبوعي</option>
                            <option value="monthly">شهري</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوقت</label>
                        <input type="time" class="form-control" name="schedule_time" value="02:00">
                    </div>
                    <div class="mb-3" id="dayField" style="display: none;">
                        <label class="form-label">يوم الأسبوع</label>
                        <select class="form-control" name="schedule_day">
                            <option value="saturday">السبت</option>
                            <option value="sunday">الأحد</option>
                            <option value="monday">الإثنين</option>
                            <option value="tuesday">الثلاثاء</option>
                            <option value="wednesday">الأربعاء</option>
                            <option value="thursday">الخميس</option>
                            <option value="friday">الجمعة</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" checked>
                        <label class="form-check-label">تفعيل الجدولة</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="saveSchedule()">حفظ الجدولة</button>
            </div>
        </div>
    </div>
</div>

<script>
function createFullBackup() {
    if (confirm('هل تريد إنشاء نسخة احتياطية كاملة؟ قد تستغرق هذه العملية بعض الوقت.')) {
        window.location.href = '<?php echo APP_URL; ?>/backup/full';
    }
}

function createIncrementalBackup() {
    if (confirm('هل تريد إنشاء نسخة احتياطية تزايدية؟')) {
        window.location.href = '<?php echo APP_URL; ?>/backup/incremental';
    }
}

function restoreBackup(id) {
    if (confirm('تحذير: استعادة النسخة الاحتياطية ستحذف جميع البيانات الحالية. هل تريد المتابعة؟')) {
        window.location.href = '<?php echo APP_URL; ?>/backup/restore/' + id;
    }
}

function deleteBackup(id) {
    if (confirm('هل أنت متأكد من حذف هذه النسخة الاحتياطية؟')) {
        window.location.href = '<?php echo APP_URL; ?>/backup/delete/' + id;
    }
}

// التحكم في إظهار حقل اليوم
document.getElementById('scheduleType').addEventListener('change', function() {
    const dayField = document.getElementById('dayField');
    dayField.style.display = this.value === 'weekly' ? 'block' : 'none';
});

function saveSchedule() {
    // تنفيذ حفظ الجدولة عبر AJAX
    alert('سيتم حفظ إعدادات الجدولة');
    $('#scheduleModal').modal('hide');
}
</script>

<?php
$this->view('layouts/footer');
?>