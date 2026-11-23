<?php
$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>
<section class="app-hero" aria-labelledby="bk-set" style="margin-bottom:1rem">
    <div class="app-hero__left">
        <span class="app-eyebrow"><i class="fas fa-gear"></i> إعدادات</span>
        <h1 id="bk-set">إعدادات <span class="accent">النسخ الاحتياطي</span></h1>
        <p>جدولة النسخ التلقائية وسياسة الاحتفاظ بالنسخ القديمة ومعايير التحقق والمراقبة.</p>
        <a class="app-hero__cta" href="<?php echo APP_URL; ?>/backup"><i class="fas fa-arrow-right"></i> العودة إلى لوحة النسخ</a>
    </div>
</section>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        إعدادات النسخ الاحتياطي - الإصدار المبسط
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- تنبيه ترحيبي -->
                            <div class="alert alert-info mb-4">
                                <h6><i class="fas fa-info-circle me-2"></i>مرحباً بك ظپظٹ إعدادات النسخ الاحتياطي</h6>
                                <p class="mb-0">هنا يمكنك ضبط الإعدادات الأساسية للنسخ الاحتياطي التلقائي. لا تقلق، كل الخيارات موضحة بلغة بسيطة.</p>
                            </div>

                            <form method="POST" action="<?php echo APP_URL; ?>
                <?= csrf_field() ?>/backup/settings/save" id="backupSettingsForm">
                                
                                <!-- القسم 1: الإعدادات الأساسية -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-sliders-h me-2"></i>
                                            الإعدادات الأساسية
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-database me-2"></i>
                                                        نوع النسخ الاحتياطي
                                                    </label>
                                                    <select name="backup_type" class="form-select" id="backup_type">
                                                        <option value="full" <?= ($settings->backup_type ?? 'full') == 'full' ? 'selected' : '' ?>>
                                                            📦 نسخ كامل (يأخذ كل البيانات)
                                                        </option>
                                                        <option value="incremental" <?= ($settings->backup_type ?? 'full') == 'incremental' ? 'selected' : '' ?>>
                                                            🔄 نسخ تزايدي (يأخذ التغييرات الجديدة فقط)
                                                        </option>
                                                    </select>
                                                    <div class="form-text">
                                                        <small>
                                                            <i class="fas fa-lightbulb me-1"></i>
                                                            <span id="backup_type_help">
                                                                <?= ($settings->backup_type ?? 'full') == 'full' ? 
                                                                    'النسخ الكامل أفضل للأمان لكنه يأخذ مساحة أكبر' : 
                                                                    'النسخ التزايدي أسرع ويوفر مساحة' ?>
                                                            </span>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-clock me-2"></i>
                                                        فترة الاحتفاظ بالنسخ
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="number" name="retention_days" class="form-control" 
                                                               value="<?= $settings->retention_days ?? 30 ?>" min="1" max="365"
                                                               id="retention_days">
                                                        <span class="input-group-text">أيام</span>
                                                    </div>
                                                    <div class="form-text">
                                                        <small>
                                                            <i class="fas fa-calendar me-1"></i>
                                                            عدد الأيام التي تحتفظ فيها بالنسخ القديمة تلقائياً
                                                        </small>
                                                    </div>
                                                    <div class="mt-2">
                                                        <small class="text-muted" id="retention_example">
                                                            مثال: سيتم حذف النسخ الأقدم من <span id="retention_example_days"><?= $settings->retention_days ?? 30 ?></span> يوم تلقائياً
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- القسم 2: الجدولة التلقائية -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-robot me-2"></i>
                                            الجدولة التلقائية
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-calendar-alt me-2"></i>
                                                        نوع الجدولة
                                                    </label>
                                                    <select name="schedule_type" class="form-select" id="schedule_type">
                                                        <option value="manual" <?= ($settings->schedule_type ?? 'manual') == 'manual' ? 'selected' : '' ?>>
                                                            👆 يدوي (أقوم بالنسخ بنفسي)
                                                        </option>
                                                        <option value="daily" <?= ($settings->schedule_type ?? 'manual') == 'daily' ? 'selected' : '' ?>>
                                                            📅 يومي (كل يوم)
                                                        </option>
                                                        <option value="weekly" <?= ($settings->schedule_type ?? 'manual') == 'weekly' ? 'selected' : '' ?>>
                                                            📆 أسبوعي (كل أسبوع)
                                                        </option>
                                                        <option value="monthly" <?= ($settings->schedule_type ?? 'manual') == 'monthly' ? 'selected' : '' ?>>
                                                            🗓️ شهري (كل شهر)
                                                        </option>
                                                    </select>
                                                    <div class="form-text">
                                                        <small>
                                                            <i class="fas fa-question-circle me-1"></i>
                                                            اختر ظƒظٹظپ تريد أن يعمل النظام تلقائياً
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-clock me-2"></i>
                                                        وقت التشغيل التلقائي
                                                    </label>
                                                    <input type="time" name="schedule_time" class="form-control" 
                                                           value="<?= $settings->schedule_time ?? '02:00' ?>" 
                                                           id="schedule_time">
                                                    <div class="form-text">
                                                        <small>
                                                            <i class="fas fa-moon me-1"></i>
                                                            اختر وقتاً يكون فيه النظام قليل الاستخدام (مثل منتصف الليل)
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- خيارات الجدولة الإضافية -->
                        <div class="row schedule-options mt-3" style="display: <?= ($settings->schedule_type ?? 'manual') != 'manual' ? 'block' : 'none' ?>;">
                            <div class="col-12">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning bg-opacity-10">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-cog me-2"></i>
                                            إعدادات إضافية للجدولة
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="schedule_day_container">
                                            <!-- سيتم تعبئته بالجافاسكريبت -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- تنبيه الجدولة -->
                        <div class="alert alert-success mt-3" id="schedule_summary" style="display: <?= ($settings->schedule_type ?? 'manual') != 'manual' ? 'block' : 'none' ?>;">
                            <h6><i class="fas fa-check-circle me-2"></i>ملخص الجدولة</h6>
                            <p class="mb-0" id="schedule_summary_text">
                                <?php
                                $scheduleText = "سيقوم النظام بعمل نسخ احتياطي ";
                                if (($settings->schedule_type ?? 'manual') == 'daily') {
                                    $scheduleText .= "يومياً الساعة " . ($settings->schedule_time ?? '02:00');
                                } elseif (($settings->schedule_type ?? 'manual') == 'weekly') {
                                    $scheduleText .= "أسبوعياً كل " . ($settings->schedule_day ?? 'الأحد') . " الساعة " . ($settings->schedule_time ?? '02:00');
                                } elseif (($settings->schedule_type ?? 'manual') == 'monthly') {
                                    $scheduleText .= "شهرياً يوم " . ($settings->schedule_day ?? '1') . " الساعة " . ($settings->schedule_time ?? '02:00');
                                }
                                echo $scheduleText;
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                                <!-- القسم 3: الإعدادات المتقدمة (طي اختياري) -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-magic me-2"></i>
                                                الإعدادات المتقدمة (اختياري)
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="collapse" data-bs-target="#advancedSettings">
                                                <i class="fas fa-chevron-down me-1"></i>
                                                إظهار/إخفاء
                                            </button>
                                        </div>
                                    </div>
                                    <div class="collapse" id="advancedSettings">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="encryption_enabled" 
                                                                   id="encryption_enabled" <?= ($settings->encryption_enabled ?? 0) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-bold" for="encryption_enabled">
                                                                <i class="fas fa-lock me-2"></i>
                                                                تشفير النسخ
                                                            </label>
                                                        </div>
                                                        <div class="form-text">
                                                            <small>
                                                                <i class="fas fa-shield-alt me-1"></i>
                                                                يجعل النسخ أكثر أماناً ولكن يتطلب كلمة مرور لفتحها
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="compression_enabled" 
                                                                   id="compression_enabled" <?= ($settings->compression_enabled ?? 1) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-bold" for="compression_enabled">
                                                                <i class="fas fa-compress-alt me-2"></i>
                                                                ضغط النسخ
                                                            </label>
                                                        </div>
                                                        <div class="form-text">
                                                            <small>
                                                                <i class="fas fa-hdd me-1"></i>
                                                                يوفر مساحة تخزين ولكن قد يستغرق وقتاً أطول
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="auto_cleanup" 
                                                                   id="auto_cleanup" <?= ($settings->auto_cleanup ?? 1) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-bold" for="auto_cleanup">
                                                                <i class="fas fa-broom me-2"></i>
                                                                تنظيف تلقائي
                                                            </label>
                                                        </div>
                                                        <div class="form-text">
                                                            <small>
                                                                <i class="fas fa-trash-alt me-1"></i>
                                                                يحذف النسخ القديمة تلقائياً حسب فترة الاحتفاظ
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- أزرار الحفظ -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <button type="submit" class="btn btn-success btn-lg">
                                                    <i class="fas fa-save me-2"></i>
                                                    حفظ الإعدادات
                                                </button>
                                                <a href="<?php echo APP_URL; ?>/backup" class="btn btn-secondary">
                                                    <i class="fas fa-arrow-right me-2"></i>
                                                    العودة للنسخ الاحتياطي
                                                </a>
                                            </div>
                                            <button type="button" class="btn btn-outline-info" onclick="resetToDefaults()">
                                                <i class="fas fa-undo me-2"></i>
                                                الإعدادات الافتراضية
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- الشريط الجانبي للمعلومات -->
                        <div class="col-md-4">
                            <div class="sticky-top" style="top: 20px;">
                                <!-- بطاقة معلومات سريعة -->
                                <div class="card bg-light mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            معلومات سريعة
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <h6>💡 نصائح سريعة:</h6>
                                            <ul class="small ps-3 mb-0">
                                                <li>النسخ اليومي مناسب لبيانات مهمة</li>
                                                <li>النسخ الأسبوعي يوفر مساحة تخزين</li>
                                                <li>30 يوم فترة جيدة للاحتفاظ</li>
                                                <li>منتصف الليل وقت مثالي للنسخ</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- حالة النسخ الحالية -->
                                <div class="card bg-light">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-history me-2"></i>
                                            السجل الحالي
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($settings): ?>
                                            <div class="mb-3">
                                                <strong>📅 آخر نسخ احتياطي:</strong><br>
                                                <span class="<?= $settings->last_backup ? 'text-success' : 'text-warning' ?>">
                                                    <?= $settings->last_backup ? 
                                                        '<i class="fas fa-check-circle me-1"></i>' . date('Y-m-d H:i', strtotime($settings->last_backup)) : 
                                                        '<i class="fas fa-clock me-1"></i>لم يتم بعد' ?>
                                                </span>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <strong>🔄 النسخ التالي:</strong><br>
                                                <span class="<?= $settings->next_backup ? 'text-info' : 'text-secondary' ?>">
                                                    <?= $settings->next_backup ? 
                                                        '<i class="fas fa-calendar-check me-1"></i>' . date('Y-m-d H:i', strtotime($settings->next_backup)) : 
                                                        '<i class="fas fa-ban me-1"></i>غير مجدول' ?>
                                                </span>
                                            </div>

                                            <?php if ($settings->last_backup): ?>
                                                <?php
                                                $lastBackupTime = strtotime($settings->last_backup);
                                                $currentTime = time();
                                                $hoursSinceLastBackup = round(($currentTime - $lastBackupTime) / 3600);
                                                ?>
                                                <div class="alert <?= $hoursSinceLastBackup > 24 ? 'alert-warning' : 'alert-success' ?> small mb-0">
                                                    <i class="fas fa-clock me-1"></i>
                                                    آخر نسخ كان منذ <?= $hoursSinceLastBackup ?> ساعة
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                لا توجد إعدادات مسبقة
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحديث وصف نوع النسخ
    document.getElementById('backup_type').addEventListener('change', function() {
        const helpText = document.getElementById('backup_type_help');
        if (this.value === 'full') {
            helpText.textContent = 'النسخ الكامل أفضل للأمان لكنه يأخذ مساحة أكبر';
        } else {
            helpText.textContent = 'النسخ التزايدي أسرع ويوفر مساحة';
        }
    });

    // تحديث مثال فترة الاحتفاظ
    document.getElementById('retention_days').addEventListener('input', function() {
        document.getElementById('retention_example_days').textContent = this.value;
    });

    // التحكم ظپظٹ إظهار خيارات الجدولة
    document.getElementById('schedule_type').addEventListener('change', function() {
        const scheduleOptions = document.querySelector('.schedule-options');
        const scheduleSummary = document.getElementById('schedule_summary');
        
        if (this.value !== 'manual') {
            scheduleOptions.style.display = 'block';
            scheduleSummary.style.display = 'block';
            updateScheduleDayOptions(this.value);
            updateScheduleSummary();
        } else {
            scheduleOptions.style.display = 'none';
            scheduleSummary.style.display = 'none';
        }
    });

    // تحديث ملخص الجدولة عند تغيير الوقت
    document.getElementById('schedule_time').addEventListener('change', updateScheduleSummary);
});

function updateScheduleDayOptions(scheduleType) {
    const container = document.getElementById('schedule_day_container');
    let html = '';
    
    if (scheduleType === 'weekly') {
        html = `
            <label class="form-label fw-bold">
                <i class="fas fa-calendar-day me-2"></i>
                اختر يوم الأسبوع
            </label>
            <select name="schedule_day" class="form-select" onchange="updateScheduleSummary()">
                <option value="sunday" <?= ($settings->schedule_day ?? 'sunday') == 'sunday' ? 'selected' : '' ?>>الأحد</option>
                <option value="monday" <?= ($settings->schedule_day ?? 'sunday') == 'monday' ? 'selected' : '' ?>>الإثنين</option>
                <option value="tuesday" <?= ($settings->schedule_day ?? 'sunday') == 'tuesday' ? 'selected' : '' ?>>الثلاثاء</option>
                <option value="wednesday" <?= ($settings->schedule_day ?? 'sunday') == 'wednesday' ? 'selected' : '' ?>>الأربعاء</option>
                <option value="thursday" <?= ($settings->schedule_day ?? 'sunday') == 'thursday' ? 'selected' : '' ?>>الخميس</option>
                <option value="friday" <?= ($settings->schedule_day ?? 'sunday') == 'friday' ? 'selected' : '' ?>>الجمعة</option>
                <option value="saturday" <?= ($settings->schedule_day ?? 'sunday') == 'saturday' ? 'selected' : '' ?>>السبت</option>
            </select>
            <div class="form-text">
                <small><i class="fas fa-calendar me-1"></i>اختر اليوم الذي تريد أن يعمل فيه النسخ التلقائي</small>
            </div>
        `;
    } else if (scheduleType === 'monthly') {
        html = `
            <label class="form-label fw-bold">
                <i class="fas fa-calendar-alt me-2"></i>
                اختر يوم الشهر
            </label>
            <select name="schedule_day" class="form-select" onchange="updateScheduleSummary()">
                <option value="1" <?= ($settings->schedule_day ?? '1') == '1' ? 'selected' : '' ?>>اليوم الأول من الشهر</option>
                <option value="15" <?= ($settings->schedule_day ?? '1') == '15' ? 'selected' : '' ?>>منتصف الشهر (15)</option>
                <option value="28" <?= ($settings->schedule_day ?? '1') == '28' ? 'selected' : '' ?>>نهاية الشهر (28)</option>
            </select>
            <div class="form-text">
                <small><i class="fas fa-calendar me-1"></i>اختر اليوم الذي تريد أن يعمل فيه النسخ التلقائي كل شهر</small>
            </div>
        `;
    } else {
        html = '';
    }
    
    container.innerHTML = html;
}

function updateScheduleSummary() {
    const scheduleType = document.getElementById('schedule_type').value;
    const scheduleTime = document.getElementById('schedule_time').value;
    const scheduleDay = document.querySelector('select[name="schedule_day"]')?.value;
    
    let summaryText = 'سيقوم النظام بعمل نسخ احتياطي ';
    
    if (scheduleType === 'daily') {
        summaryText += `يومياً الساعة ${scheduleTime}`;
    } else if (scheduleType === 'weekly') {
        const days = {
            'sunday': 'الأحد',
            'monday': 'الإثنين', 
            'tuesday': 'الثلاثاء',
            'wednesday': 'الأربعاء',
            'thursday': 'الخميس',
            'friday': 'الجمعة',
            'saturday': 'السبت'
        };
        summaryText += `أسبوعياً كل ${days[scheduleDay]} الساعة ${scheduleTime}`;
    } else if (scheduleType === 'monthly') {
        summaryText += `شهرياً يوم ${scheduleDay} الساعة ${scheduleTime}`;
    }
    
    document.getElementById('schedule_summary_text').textContent = summaryText;
}

function resetToDefaults() {
    if (confirm('هل تريد استعادة الإعدادات الافتراضية؟ سيتم فقدان الإعدادات الحالية.')) {
        document.getElementById('backup_type').value = 'full';
        document.getElementById('retention_days').value = '30';
        document.getElementById('schedule_type').value = 'manual';
        document.getElementById('schedule_time').value = '02:00';
        document.getElementById('encryption_enabled').checked = false;
        document.getElementById('compression_enabled').checked = true;
        document.getElementById('auto_cleanup').checked = true;
        
        // تحديث الواجهة
        document.getElementById('backup_type').dispatchEvent(new Event('change'));
        document.getElementById('schedule_type').dispatchEvent(new Event('change'));
        document.getElementById('retention_days').dispatchEvent(new Event('input'));
        
        alert('تم استعادة الإعدادات الافتراضية. لا تنس حفظ التغييرات.');
    }
}

// تهيئة أولية عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    updateScheduleDayOptions(document.getElementById('schedule_type').value);
    updateScheduleSummary();
});
</script>

<?php
$this->view('layouts/footer');
?>
