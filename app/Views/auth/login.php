<?php 
// تضمين الهيدر المشترك
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="login-page-container">
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-user-shield"></i>
            <h3>تسجيل الدخول إلى النظام</h3>
            <p>أدخل بياناتك للوصول إلى لوحة التحكم</p>
        </div>

        <div class="login-body">
            <?php 
            // عرض رسالة الخطأ إذا كانت موجودة بتصميم أفضل
            if (isset($error)): 
            ?>
                <div class="alert-message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo APP_URL; ?>/login" method="POST" class="login-form">
                <?php echo csrf_field(); // توكن الحماية (يُولَّد قبل المصادقة)
                ?>
                <div class="form-group">
                    <label for="username">اسم المستخدم أو البريد الإلكتروني</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="مثال: sysadmin أو admin@example.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">كلمة المرور</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                <!-- بند 9 (ر.ق 2): حُذف صف form-options بالكامل بعد إزالة رابط «نسيت كلمة المرور؟» الميت — إعادة التعيين عبر Admin فقط -->
                <button type="submit" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i>
                    تسجيل الدخول
                </button>
            </form>
        </div>
        <div class="login-assurance"><i class="fas fa-lock"></i> جلسة محمية وصلاحيات مخصصة حسب الدور</div>
    </div>
</div>

<?php 
// تضمين الفوتر المشترك
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
