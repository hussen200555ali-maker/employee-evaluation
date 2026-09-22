<?php 
// بند 10: شاشة تغيير كلمة المرور — تُفتح إما إلزامًا بعد إعادة تعيين Admin (بند 9) أو طوعًا من أي جلسة موثقة
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="login-page-container">
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-key"></i>
            <h3>تغيير كلمة المرور</h3>
            <p>اختر كلمة مرور جديدة قوية لمتابعة استخدام النظام</p>
        </div>

        <div class="login-body">
            <?php if (isset($error)): ?>
                <div class="alert-message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo APP_URL; ?>/change-password" method="POST" class="login-form">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="current_password">كلمة المرور الحالية</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="current_password" name="current_password" placeholder="••••••••" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">كلمة المرور الجديدة</label>
                    <div class="input-with-icon">
                        <i class="fas fa-shield-alt"></i>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password_confirm">تأكيد كلمة المرور الجديدة</label>
                    <div class="input-with-icon">
                        <i class="fas fa-check-circle"></i>
                        <input type="password" id="password_confirm" name="password_confirm" placeholder="••••••••" required>
                    </div>
                </div>

                <!-- بند 11: مرجع سياسة كلمة المرور المعتمدة -->
                <div class="policy-hint">
                    <i class="fas fa-info-circle"></i>
                    <ul>
                        <li><?php echo htmlspecialchars($policy['min_length'] ?? '8 أحرف على الأقل'); ?></li>
                        <li><?php echo htmlspecialchars($policy['variety'] ?? '3 من 4 أنواع: أحرف كبيرة وصغيرة وأرقام ورموز'); ?></li>
                        <li><?php echo htmlspecialchars($policy['common'] ?? 'ليست من الكلمات الشائعة'); ?></li>
                        <li><?php echo htmlspecialchars($policy['history'] ?? 'ليست من آخر 3 كلمات مستخدمة'); ?></li>
                    </ul>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-check"></i>
                    تغيير كلمة المرور
                </button>
            </form>
        </div>
        <div class="login-assurance"><i class="fas fa-user-lock"></i> سيُطلب منك هذا التغيير عند إعادة تعيين الإدارة لكلمة مرورك</div>
    </div>
</div>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>