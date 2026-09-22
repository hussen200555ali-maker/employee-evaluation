<?php 
// تضمين الهيدر المشترك.
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<main class="error-page-container">
    <section class="error-card error-card--forbidden">
        <span class="error-icon"><i class="fas fa-shield-alt"></i></span>
        <span class="eyebrow">حماية الصلاحيات</span>
        <div class="error-code">403</div>
        <div class="error-message">هذه المساحة ليست ضمن صلاحياتك</div>
        <p class="error-details">
            ليس لديك الصلاحيات اللازمة للوصول إلى هذه الصفحة أو تنفيذ هذا الإجراء.
            <?php if (!empty($permission)): ?>
                  
الصلاحية المطلوبة: <strong><?php echo $permission; ?></strong>
            <?php endif; ?>
        </p>
        <a href="<?php echo APP_URL; ?>/dashboard" class="btn btn-primary btn-back">
            <i class="fas fa-home"></i> العودة إلى لوحة التحكم
        </a>
    </section>
</main>

<?php 
// تضمين الفوتر المشترك.
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
