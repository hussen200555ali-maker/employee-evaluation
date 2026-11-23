<?php
// app/Views/backup/encryption_keys.php
$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>
<section class="app-hero" aria-labelledby="bk-keys" style="margin-bottom:1rem">
    <div class="app-hero__left">
        <span class="app-eyebrow"><i class="fas fa-key"></i> الأمان</span>
        <h1 id="bk-keys">مفاتيح <span class="accent">التشفير</span></h1>
        <p>إدارة وتدوير مفاتيح التشفير التي تحمي ملفات النسخ الاحتياطية.</p>
        <a class="app-hero__cta" href="<?php echo APP_URL; ?>/backup"><i class="fas fa-arrow-right"></i> العودة إلى النسخ</a>
    </div>
</section>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-key"></i> إدارة مفاتيح التشفير</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>اسم المفتاح</th>
                            <th>النسخة المرتبطة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($keys as $key): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key->key_name); ?></td>
                            <td><?php echo htmlspecialchars($key->backup_name); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($key->created_at)); ?></td>
                            <td>
                                <button class="btn btn-danger btn-sm" 
                                        onclick="deleteKey(<?php echo $key->id; ?>)">
                                    <i class="fas fa-trash"></i> حذف
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function deleteKey(keyId) {
    if (confirm('<i class="fas fa-triangle-exclamation"></i> هل أنت متأكد من حذف هذا المفتاح؟ لن تتمكن من فتح النسخ المشفرة بهذا المفتاح.')) {
        window.location.href = '<?php echo APP_URL; ?>/backup/encryption/delete-key/' + keyId;
    }
}
</script>

<?php $this->view('layouts/footer'); ?>