<?php
// في ملف /app/Views/admin/users/create.php
// CD-006 / بند 2: نموذج إنشاء مستخدم جديد (أُعيدت كتابته من الصفر — كان يحتوي markup باني القوالب).
require_once APP_ROOT . '/app/Views/layouts/header.php';
?>

<div class="page-header">
    <h2><i class="fas fa-user-plus"></i> إضافة مستخدم جديد</h2>
    <a href="<?php echo APP_URL; ?>/admin/users" class="btn-back">
        <i class="fas fa-arrow-right"></i> العودة
    </a>
</div>

<div class="form-container">
    <form action="<?php echo APP_URL; ?>/admin/users" method="POST">
        <?= csrf_field() ?>

        <h4><i class="fas fa-user-circle"></i> معلومات الحساب</h4>
        <div class="form-row">
            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($data['old']['username'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($data['old']['email'] ?? ''); ?>" required>
            </div>
        </div>

        <hr class="my-4">
        <h4><i class="fas fa-key"></i> كلمة المرور</h4>
        <div class="form-row">
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">تأكيد كلمة المرور</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
            </div>
        </div>

        <hr class="my-4">
        <h4><i class="fas fa-user-tag"></i> الدور والربط الوظيفي</h4>
        <div class="form-row">
            <div class="form-group">
                <label for="role_id">الدور</label>
                <select id="role_id" name="role_id" class="form-select" required>
                    <option value="">-- اختر دوراً --</option>
                    <?php foreach ($data['roles'] as $role): ?>
                        <option value="<?php echo $role->id; ?>" <?php if (($data['old']['role_id'] ?? '') == $role->id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($role->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="employee_id">ربط الحساب بموظف (اختياري)</label>
                <select id="employee_id" name="employee_id" class="form-select">
                    <option value="">-- لا يوجد ربط (حساب إداري) --</option>
                    <?php foreach ($data['employees'] as $employee): ?>
                        <option value="<?php echo $employee->id; ?>" <?php if (($data['old']['employee_id'] ?? '') == $employee->id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($employee->full_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit-form">
                <i class="fas fa-save"></i> إنشاء المستخدم
            </button>
        </div>
    </form>
</div>

<?php
require_once APP_ROOT . '/app/Views/layouts/footer.php';
?>
