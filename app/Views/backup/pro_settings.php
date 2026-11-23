<?php
$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>
<section class="app-hero" aria-labelledby="bk-pros" style="margin-bottom:1rem">
    <div class="app-hero__left">
        <span class="app-eyebrow"><i class="fas fa-wand-magic-sparkles"></i> احترافي</span>
        <h1 id="bk-pros">الإعدادات <span class="accent">الاحترافية</span> للنسخ</h1>
        <p>الذكاء الاصطناعي في التحقق، وتدوير النسخ الذكي، وسياسة التنظيف المؤتمتة.</p>
        <a class="app-hero__cta" href="<?php echo APP_URL; ?>/backup/dashboard"><i class="fas fa-chart-column"></i> لوحة القيادة</a>
    </div>
</section>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">الإعدادات الاحترافية للنسخ الاحتياطي</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo APP_URL; ?>
                <?= csrf_field() ?>/backup/settings/save">
                        <div class="row">
                            <!-- الإعدادات الأساسية -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>الإعدادات الأساسية</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">نوع النسخ الاحتياطي</label>
                                            <select name="backup_type" class="form-control">
                                                <option value="full" <?= ($settings->backup_type ?? 'full') == 'full' ? 'selected' : '' ?>>نسخ كامل</option>
                                                <option value="incremental" <?= ($settings->backup_type ?? 'full') == 'incremental' ? 'selected' : '' ?>>نسخ تزايدي</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">فترة الاحتفاظ (أيام)</label>
                                            <input type="number" name="retention_days" class="form-control" 
                                                   value="<?= $settings->retention_days ?? 30 ?>" min="1" max="365">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- إعدادات الجدولة -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>الجدولة التلقائية</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">نوع الجدولة</label>
                                            <select name="schedule_type" class="form-control" id="schedule_type">
                                                <option value="manual" <?= ($settings->schedule_type ?? 'manual') == 'manual' ? 'selected' : '' ?>>يدوي</option>
                                                <option value="daily" <?= ($settings->schedule_type ?? 'manual') == 'daily' ? 'selected' : '' ?>>يومي</option>
                                                <option value="weekly" <?= ($settings->schedule_type ?? 'manual') == 'weekly' ? 'selected' : '' ?>>أسبوعي</option>
                                                <option value="monthly" <?= ($settings->schedule_type ?? 'manual') == 'monthly' ? 'selected' : '' ?>>شهري</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">وقت التشغيل</label>
                                            <input type="time" name="schedule_time" class="form-control" 
                                                   value="<?= $settings->schedule_time ?? '02:00' ?>">
                                        </div>
                                        
                                        <div class="mb-3" id="schedule_day_container" style="display: none;">
                                            <!-- سيتم تعبئته بالجافاسكريبت -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- إعدادات متقدمة -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>الإعدادات المتقدمة</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="encryption_enabled" 
                                                           id="encryption_enabled" <?= ($settings->encryption_enabled ?? 0) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="encryption_enabled">تفعيل التشفير</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="compression_enabled" 
                                                           id="compression_enabled" <?= ($settings->compression_enabled ?? 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="compression_enabled">تفعيل الضغط</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="auto_cleanup" 
                                                           id="auto_cleanup" <?= ($settings->auto_cleanup ?? 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="auto_cleanup">التنظيف التلقائي</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                            <a href="<?php echo APP_URL; ?>/backup" class="btn btn-secondary">العودة</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('schedule_type').addEventListener('change', function() {
    const container = document.getElementById('schedule_day_container');
    if (this.value !== 'manual') {
        container.style.display = 'block';
        updateScheduleDayOptions(this.value);
    } else {
        container.style.display = 'none';
    }
});

function updateScheduleDayOptions(scheduleType) {
    const container = document.getElementById('schedule_day_container');
    let html = '<label class="form-label">يوم التشغيل</label>';
    
    if (scheduleType === 'weekly') {
        html += `
            <select name="schedule_day" class="form-control">
                <option value="sunday">الأحد</option>
                <option value="monday">الإثنين</option>
                <option value="tuesday">الثلاثاء</option>
                <option value="wednesday">الأربعاء</option>
                <option value="thursday">الخميس</option>
                <option value="friday">الجمعة</option>
                <option value="saturday">السبت</option>
            </select>`;
    } else if (scheduleType === 'monthly') {
        html += `
            <select name="schedule_day" class="form-control">
                <option value="1">اليوم الأول من الشهر</option>
                <option value="15">منتصف الشهر (15)</option>
                <option value="28">نهاية الشهر (28)</option>
            </select>`;
    } else {
        html = '';
    }
    
    container.innerHTML = html;
}

// التهيئة الأولية
document.addEventListener('DOMContentLoaded', function() {
    const scheduleType = document.getElementById('schedule_type').value;
    if (scheduleType !== 'manual') {
        document.getElementById('schedule_day_container').style.display = 'block';
        updateScheduleDayOptions(scheduleType);
    }
});
</script>

<?php
$this->view('layouts/footer');
?>
