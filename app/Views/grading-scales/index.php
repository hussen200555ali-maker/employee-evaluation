<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<div class="container py-4">
    <div class="page-header">
        <h2><i class="fas fa-star text-warning"></i> إدارة سلم التقديرات</h2>
    </div>
    <p class="text-muted">تُستخدم القيمة الرقمية لربط الدرجة بالتقدير المناسب. لا تتداخل نطاقات الدرجات داخل السلم الواحد.</p>

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold">إضافة تقدير</div>
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/grading-scales">
                <?= csrf_field() ?>
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label" for="scale_name">اسم السلم</label>
                        <input class="form-control" id="scale_name" name="scale_name" maxlength="100" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="grade_label">التقدير</label>
                        <input class="form-control" id="grade_label" name="grade_label" maxlength="50" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="min_value">من</label>
                        <input class="form-control" id="min_value" name="min_value" type="number" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="max_value">إلى</label>
                        <input class="form-control" id="max_value" name="max_value" type="number" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label" for="numeric_value">القيمة</label>
                        <input class="form-control" id="numeric_value" name="numeric_value" type="number" min="0" max="9.99" step="0.01" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label" for="sort_order">الترتيب</label>
                        <input class="form-control" id="sort_order" name="sort_order" type="number" min="0" value="0">
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-success w-100" type="submit" aria-label="إضافة"><i class="fas fa-plus"></i></button>
                    </div>
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input" id="is_active_new" name="is_active" type="checkbox" checked>
                    <label class="form-check-label" for="is_active_new">مفعّل</label>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($scales)): ?>
        <div class="alert alert-info">لا توجد تقديرات معرفة.</div>
    <?php else: ?>
        <?php $currentScale = null; ?>
        <?php foreach ($scales as $scale): ?>
            <?php if ($currentScale !== $scale->scale_name): ?>
                <?php if ($currentScale !== null): ?></div><?php endif; ?>
                <?php $currentScale = $scale->scale_name; ?>
                <h4 class="mt-4"><?= htmlspecialchars($currentScale, ENT_QUOTES, 'UTF-8') ?></h4>
                <div class="table-responsive mb-4">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>اسم السلم</th><th>التقدير</th><th>من</th><th>إلى</th><th>القيمة الرقمية</th><th>الترتيب</th><th>الحالة</th><th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
            <?php endif; ?>
            <tr>
                <td>
                    <form id="update-<?= (int)$scale->id ?>" method="POST" action="<?= APP_URL ?>/grading-scales">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$scale->id ?>">
                    </form>
                    <input form="update-<?= (int)$scale->id ?>" class="form-control mb-2" name="scale_name" maxlength="100" value="<?= htmlspecialchars($scale->scale_name, ENT_QUOTES, 'UTF-8') ?>" required>
                </td>
                    <td><input form="update-<?= (int)$scale->id ?>" class="form-control" name="grade_label" maxlength="50" value="<?= htmlspecialchars($scale->grade_label, ENT_QUOTES, 'UTF-8') ?>" required></td>
                    <td><input form="update-<?= (int)$scale->id ?>" class="form-control" name="min_value" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string)$scale->min_value, ENT_QUOTES, 'UTF-8') ?>" required></td>
                    <td><input form="update-<?= (int)$scale->id ?>" class="form-control" name="max_value" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string)$scale->max_value, ENT_QUOTES, 'UTF-8') ?>" required></td>
                    <td><input form="update-<?= (int)$scale->id ?>" class="form-control" name="numeric_value" type="number" min="0" max="9.99" step="0.01" value="<?= htmlspecialchars((string)$scale->numeric_value, ENT_QUOTES, 'UTF-8') ?>" required></td>
                    <td><input form="update-<?= (int)$scale->id ?>" class="form-control" name="sort_order" type="number" min="0" value="<?= (int)$scale->sort_order ?>"></td>
                    <td>
                        <div class="form-check">
                            <input form="update-<?= (int)$scale->id ?>" class="form-check-input" id="active-<?= (int)$scale->id ?>" name="is_active" type="checkbox" <?= $scale->is_active ? 'checked' : '' ?>>
                            <label class="form-check-label" for="active-<?= (int)$scale->id ?>"><?= $scale->is_active ? 'مفعّل' : 'غير مفعّل' ?></label>
                        </div>
                    </td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-primary" form="update-<?= (int)$scale->id ?>" type="submit"><i class="fas fa-save"></i> حفظ</button>
                        <form id="delete-<?= (int)$scale->id ?>" class="d-inline" method="POST" action="<?= APP_URL ?>/grading-scales/delete" onsubmit="return confirm('حذف هذا التقدير؟');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$scale->id ?>">
                        </form>
                        <button class="btn btn-sm btn-outline-danger" form="delete-<?= (int)$scale->id ?>" type="submit"><i class="fas fa-trash"></i></button>
                    </td>
            </tr>
        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
    <?php endif; ?>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
