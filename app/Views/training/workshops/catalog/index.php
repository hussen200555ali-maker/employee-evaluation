<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-tools"></i> كتالوج الورش</h2>
        <a href="<?php echo APP_URL; ?>/training/workshops/catalog/create" class="btn btn-success">
            <i class="fas fa-plus"></i> إضافة ورشة جديدة
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>اسم الورشة</th>
                        <th>الوصف</th>
                        <th>التصنيف</th>
                        <th>تاريخ الإنشاء</th>
                        <th>آخر تحديث</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data['workshops'])): ?>
                        <?php foreach ($data['workshops'] as $workshop): ?>
                            <tr>
                                <td><?php echo $workshop->id; ?></td>
                                <td><?php echo htmlspecialchars($workshop->title); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($workshop->description)); ?></td>
                                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($workshop->category ?: 'غير مصنف'); ?></span></td>
                                <td><?php echo date("Y-m-d", strtotime($workshop->created_at)); ?></td>
                                <td><?php echo date("Y-m-d", strtotime($workshop->updated_at)); ?></td>
                                <td>
                                    <a href="<?php echo APP_URL; ?>/training/workshops/catalog/edit/<?php echo $workshop->id; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="<?php echo APP_URL; ?>/training/workshops/catalog/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل تريد حذف هذه الورشة؟');">
                <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?php echo $workshop->id; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-muted">🚫 لا توجد ورش مسجلة حالياً</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>

