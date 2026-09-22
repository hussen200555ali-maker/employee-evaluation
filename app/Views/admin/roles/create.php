<?php 
// في ملف /app/Views/admin/roles/create.php

require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> إضافة دور جديد</h2>
    <a href="<?php echo APP_URL; ?>/admin/roles" class="btn-back">
        <i class="fas fa-arrow-right"></i> العودة إلى قائمة الأدوار
    </a>
</div>

<div class="form-container">
    <form action="<?php echo APP_URL; ?>/admin/roles" method="POST">
        <?= csrf_field() ?>
        
        <!-- اسم الدور -->
        <div class="form-group mb-3">
            <label for="name">اسم الدور (باللغة الإنجليزية، بدون مسافات)</label>
            <input type="text" id="name" name="name" class="form-control" required pattern="[A-Za-z_]+" title="الرجاء استخدام حروف إنجليزية فقط بدون مسافات.">
            <small class="form-text text-muted">مثال: `Supervisor`, `DataEntry`, `Accountant`</small>
        </div>

        <!-- وصف الدور -->
        <div class="form-group mb-4">
            <label for="description">وصف الدور (باللغة العربية)</label>
            <input type="text" id="description" name="description" class="form-control" required>
            <small class="form-text text-muted">مثال: "مشرف فريق، مسؤول عن مراجعة التقييمات."</small>
        </div>

        <!-- زر الحفظ -->
        <div class="form-actions">
            <button type="submit" class="btn-submit-form">
                <i class="fas fa-save"></i> حفظ الدور
            </button>
        </div>

    </form>
</div>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
