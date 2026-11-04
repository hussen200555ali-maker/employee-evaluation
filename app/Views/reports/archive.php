<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<div class="container-fluid">
    <h2><?= htmlspecialchars($pageTitle ?? 'سجل التقارير', ENT_QUOTES, 'UTF-8') ?></h2>
    <hr>
    <?php if ($snapshots !== []): ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>رقم التقرير</th><th>الموظف</th><th>الرقم الوظيفي</th><th>تاريخ الإنشاء</th><th>أنشأه</th><th>عرض</th></tr></thead>
                <tbody>
                <?php foreach ($snapshots as $snapshot): ?>
                    <tr>
                        <td><?= htmlspecialchars($snapshot->report_number, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($snapshot->full_name ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($snapshot->emp_code ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($snapshot->created_at, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($snapshot->created_by ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a class="btn btn-sm btn-primary" href="<?= APP_URL ?>/employees/profile/<?= (int)$snapshot->employee_id ?>/reports/<?= (int)$snapshot->id ?>">عرض</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">لا توجد تقارير صادرة.</div>
    <?php endif; ?>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
