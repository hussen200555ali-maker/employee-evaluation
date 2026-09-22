<?php
// app/Views/backup/encryption_keys.php
$this->view('layouts/header', ['pageTitle' => $pageTitle]);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">🗝️ إدارة مفاتيح التشفير</h5>
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
                                    🗑️ حذف
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
    if (confirm('⚠️ هل أنت متأكد من حذف هذا المفتاح؟ لن تتمكن من فتح النسخ المشفرة بهذا المفتاح.')) {
        window.location.href = '<?php echo APP_URL; ?>/backup/encryption/delete-key/' + keyId;
    }
}
</script>

<?php $this->view('layouts/footer'); ?>