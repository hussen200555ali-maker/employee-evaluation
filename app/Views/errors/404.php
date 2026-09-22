<?php 
// تضمين الهيدر المشترك.
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<main class="error-page-container">
    <section class="error-card error-card--missing">
        <span class="error-icon"><i class="fas fa-compass"></i></span>
        <span class="eyebrow">مسار غير متاح</span>
        <div class="error-code">404</div>
        <div class="error-message">لا يمكن العثور على الصفحة المطلوبة</div>
        <p class="error-details">
            قد يكون الرابط الذي اتبعته خاطئاً أو قد تم حذف الصفحة.
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
