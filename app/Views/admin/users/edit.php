<?php 
// في ملف /app/Views/admin/users/edit.php
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="page-header">
    <h2><i class="fas fa-pencil-alt"></i> تعديل المستخدم: <?php echo htmlspecialchars($data['user']->username); ?></h2>
    <a href="<?php echo APP_URL; ?>/admin/users" class="btn-back">
        <i class="fas fa-arrow-right"></i> العودة
    </a>
</div>

<div class="form-container">
    <form action="<?php echo APP_URL; ?>/admin/users/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?php echo $data['user']->id; ?>">
        
        <h4><i class="fas fa-user-circle"></i> معلومات الحساب</h4>
        <div class="form-row">
            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($data['user']->username); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($data['user']->email); ?>" required>
            </div>
        </div>

        <!-- ================================================== -->
        <!--           ===>  أضف القسم الجديد هنا  <===           -->
        <!-- ================================================== -->
        <hr class="my-4">
        <h4><i class="fas fa-key"></i> تغيير كلمة المرور (اختياري)</h4>
        <p class="text-muted">اترك الحقول التالية فارغة إذا كنت لا ترغب في تغيير كلمة المرور.</p>
        <div class="form-row">
            <div class="form-group">
                <label for="password">كلمة المرور الجديدة</label>
                <input type="password" id="password" name="password" class="form-control">
            </div>
            <div class="form-group">
                <label for="password_confirm">تأكيد كلمة المرور الجديدة</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control">
            </div>
        </div>
        <h4><i class="fas fa-briefcase"></i> الربط الوظيفي</h4>
        <div class="form-group">
            <label for="employee_id">ربط الحساب بموظف (اختياري)</label>
            <select id="employee_id" name="employee_id" class="form-select">
                <option value="">-- لا يوجد ربط (حساب إداري) --</option>
                <?php foreach ($data['employees'] as $employee): ?>
                    <option value="<?php echo $employee->id; ?>" <?php if ($data['user']->employee_id == $employee->id) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($employee->full_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit-form">
                <i class="fas fa-save"></i> حفظ التعديلات
            </button>
        </div>
    </form>
</div>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
