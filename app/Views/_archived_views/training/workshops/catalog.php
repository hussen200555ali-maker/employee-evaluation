<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
    <h2><i class="fas fa-list"></i> إدارة كتالوج الورش</h2>
    <form action="<?php echo APP_URL; ?>/training/workshops/catalog/store" method="POST" style="display:flex; gap:10px;">
                <?= csrf_field() ?>
        <input type="text" name="title" class="form-control" placeholder="اسم الورشة" required>
        <button type="submit" class="btn btn-success">إضافة</button>
    </form>
</div>

<div class="table-responsive mt-3">
    <table class="data-table">
        <thead>
            <tr>
                <th>اسم الورشة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data['catalog_workshops'])): ?>
                <?php foreach ($data['catalog_workshops'] as $workshop): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($workshop->title); ?></td>
                        <td>
                            <form action="<?php echo APP_URL; ?>/training/workshops/catalog/delete" method="POST" style="display:inline;" onsubmit="return confirm('حذف الورشة؟');">
                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?php echo $workshop->id; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="2" class="text-center">لا توجد ورش في الكتالوج</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>

