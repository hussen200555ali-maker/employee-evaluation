      </div> <!-- إغلاق <div class="container"> من الهيدر -->
    </main> 
    <!-- نهاية المحتوى الرئيسي -->

    <!-- =================================================================== -->
    <!--  الفوتر السفلي (يبقى كما هو تماماً بدون أي تغيير) -->
    <!-- =================================================================== -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h3 class="logo-text"><?php echo APP_NAME; ?></h3>
                    <p>
                        نظام متكامل لإدارة وتقييم أداء الموظفين بكفاءة وفعالية،
                        مما يساعد على اتخاذ قرارات استراتيجية مبنية على البيانات.
                    </p>
                </div>
                <div class="footer-section links">
                    <h3>روابط سريعة</h3>
                    <ul>
                        <li><a href="#">الأسئلة الشائعة</a></li>
                        <li><a href="#">سياسة الخصوصية</a></li>
                        <li><a href="#">شروط الاستخدام</a></li>
                        <li><a href="#">تواصل معنا</a></li>
                    </ul>
                </div>
                <div class="footer-section contact">
                    <h3>تواصل معنا</h3>
                    <span><i class="fas fa-phone"></i> &nbsp; 123-456-789</span>
                    <span><i class="fas fa-envelope"></i> &nbsp; info@company.com</span>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> | <?php echo APP_NAME; ?> | جميع الحقوق محفوظة
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
