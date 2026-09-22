<?php 
// تضمين الهيدر المشترك
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<main class="container home-landing">
    <section class="home-hero">
        <div class="home-hero__content">
            <span class="eyebrow"><i class="fas fa-compass"></i> إدارة أداء واضحة وقابلة للقياس</span>
            <h1><?php echo htmlspecialchars($welcomeMessage); ?></h1>
            <p>منصة عربية موحدة لإدارة الموظفين والتقييمات والتقارير، بمساحات عمل تختلف حسب الدور وتحافظ على وصول كل مستخدم إلى ما يخصه فقط.</p>
            <div class="home-hero__actions">
                <?php if (\App\Core\Auth::check()): ?>
                    <a class="btn btn-light" href="<?php echo APP_URL; ?>/dashboard"><i class="fas fa-chart-line ms-1"></i> فتح لوحة التحكم</a>
                <?php else: ?>
                    <a class="btn btn-light" href="<?php echo APP_URL; ?>/login"><i class="fas fa-sign-in-alt ms-1"></i> تسجيل الدخول</a>
                <?php endif; ?>
                <a class="btn btn-outline-light" href="<?php echo APP_URL; ?>/about"><i class="fas fa-arrow-left ms-1"></i> تعرّف على النظام</a>
            </div>
        </div>
        <div class="home-hero__visual" aria-hidden="true">
            <div class="hero-metric hero-metric--primary"><i class="fas fa-chart-line"></i><strong>82.4</strong><span>متوسط الأداء</span></div>
            <div class="hero-metric hero-metric--secondary"><i class="fas fa-users"></i><strong>248</strong><span>تقييمات مسجلة</span></div>
            <div class="hero-orbit"><i class="fas fa-shield-alt"></i></div>
        </div>
    </section>
    <section class="home-feature-grid" aria-label="قدرات النظام">
        <article><span class="home-feature-icon"><i class="fas fa-user-check"></i></span><h2>تقييمات محكومة</h2><p>مسارات تقييم مخصصة وأذونات مفروضة من الخادم لكل دور.</p></article>
        <article><span class="home-feature-icon home-feature-icon--gold"><i class="fas fa-file-excel"></i></span><h2>Excel آمن</h2><p>قوالب للمقيّم مع معاينة، تحقق، وسجل أخطاء قبل الالتزام.</p></article>
        <article><span class="home-feature-icon home-feature-icon--blue"><i class="fas fa-chart-pie"></i></span><h2>قراءة أداء عملية</h2><p>مؤشرات ورسوم تحليليّة تفصل بين بيانات المؤسسة وبيانات المستخدم.</p></article>
    </section>
</main>

<?php 
// تضمين الفوتر المشترك
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
