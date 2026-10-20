      </div> <!-- إغلاق <div class="container"> من الهيدر -->
    </main> 
    <!-- نهاية المحتوى الرئيسي -->

    <!-- =================================================================== -->
    <!--  الفوتر السفلي (يبقى كما هو تماماً بدون أي تغيير) -->
    <!-- =================================================================== -->
    <footer class="main-footer main-footer--thin">
        <div class="container">
            <div class="main-footer__line">
                <span>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> — جميع الحقوق محفوظة</span>
                <span class="main-footer__sep">·</span>
                <span>توقيت الرياض (Asia/Riyadh)</span>
            </div>
        </div>
    </footer>

    </div> <!-- /.app-main -->
</div> <!-- /.app-shell -->

    <?php /*
     * ===================================================================
     *  ▼▼▼ بداية منطقة التعديل: تحميل ملفات JavaScript المحلية ▼▼▼
     * ===================================================================
     *
     *  التعليق العملي: تم استبدال الروابط الخارجية (CDN) بالروابط المحلية
     *  لجعل الموقع أسرع. الترتيب مهم: jQuery أولاً، ثم Bootstrap الذي يعتمد عليه.
     *
     */ ?>

    <!-- 1. مكتبة jQuery (تم تحويلها إلى محلية) -->
    <script src="<?php echo APP_URL; ?>/public/libs/jquery/jquery-3.6.0.min.js"></script>

    <!-- 2. حزمة Bootstrap JS (تم تحويلها إلى محلية) -->
    <script src="<?php echo APP_URL; ?>/public/libs/bootstrap-5.1.3/js/bootstrap.bundle.min.js"></script>

    <!-- 3. مكتبة SweetAlert2 (تم تحويلها إلى محلية) -->
    <script src="<?php echo APP_URL; ?>/public/libs/sweetalert2/dist/sweetalert2.all.min.js"></script>

    <?php /* ▲▲▲ نهاية منطقة التعديل ▲▲▲ */ ?>


    <!-- (بدون تغيير) -->
    <script>
        const APP_URL = "<?php echo APP_URL; ?>";
    </script>

    <!-- ملفات JavaScript الرئيسية (بدون تغيير) -->
    <script src="<?php echo APP_URL; ?>/public/js/main.js"></script>
    <script src="<?php echo APP_URL; ?>/public/js/print-logic.js"></script>
    <script src="<?php echo APP_URL; ?>/public/js/evaluation-creator.js"></script>
    <script src="<?php echo APP_URL; ?>/public/js/sidebar.js"></script>

</body>
</html>
