<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<div class="container-fluid">
    <h2><?= htmlspecialchars($pageTitle ?? 'توليد تقرير موظف', ENT_QUOTES, 'UTF-8') ?></h2>
    <hr>
    <form method="GET" action="<?= APP_URL ?>/reports/create" class="mb-4">
        <div class="input-group">
            <input type="search" name="q" class="form-control"
                   value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="ابحث بالاسم أو الرقم الوظيفي..." aria-label="بحث عن موظف">
            <button type="submit" class="btn btn-primary">بحث</button>
        </div>
    </form>

    <?php if ($search !== '' && $results !== []): ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>الاسم</th><th>الرقم الوظيفي</th><th>القسم</th><th>الفرع</th><th>إجراء</th></tr></thead>
                <tbody>
                <?php foreach ($results as $employee): ?>
                    <tr>
                        <td><?= htmlspecialchars($employee->full_name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($employee->emp_code, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($employee->department_name ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($employee->branch_name ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form method="POST" action="<?= APP_URL ?>/employees/profile/<?= (int)$employee->id ?>/reports">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm btn-success">توليد تقرير</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($search !== ''): ?>
        <div class="alert alert-info">لا توجد نتائج مطابقة.</div>
    <?php endif; ?>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
