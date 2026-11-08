<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
$course = $course ?? null;
$participants = $participants ?? [];
$stats = $stats ?? ['total' => 0, 'completed' => 0, 'pending' => 0];
?>
<!-- CD-032: تفاصيل الدورة + KPIs -->
<div class="container mt-4">
    <div class="card shadow-sm mb-4" style="border:2px solid #c8943f;border-radius:12px;">
        <div class="card-header" style="background:linear-gradient(90deg,#fbf3e2,#f6e8cf);border-bottom:2px solid #c8943f;">
            <strong style="color:#c8943f;"><i class="fas fa-book"></i> معلومات الدورة</strong>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><small class="text-muted">اسم الدورة</small><br><strong><?= htmlspecialchars($course->title ?? $course->description ?? '') ?></strong></div>
                <div class="col-md-3"><small class="text-muted">الجهة المنفذة</small><br><strong><?= htmlspecialchars($course->branch_name ?? '') ?></strong></div>
                <div class="col-md-3"><small class="text-muted">التواريخ</small><br><strong><?= htmlspecialchars($course->start_date ?? '') ?> → <?= htmlspecialchars($course->end_date ?? '') ?></strong></div>
                <div class="col-md-3"><small class="text-muted">الحالة</small><br><strong><?= htmlspecialchars($course->status ?? '') ?></strong></div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center text-white" style="background:linear-gradient(135deg,#0c2823,#0d5e52);">
                <div class="card-body"><div style="font-size:28px;font-weight:800;color:#c8943f;"><?= (int)$stats['total'] ?></div><small>إجمالي المسجلين</small></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center" style="background:#d4edda;border:1px solid #c3e6cb;">
                <div class="card-body"><div style="font-size:28px;font-weight:800;color:#155724;"><?= (int)$stats['completed'] ?></div><small style="color:#155724;">تم التقييم</small></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center" style="background:#fff3cd;border:1px solid #ffeeba;">
                <div class="card-body"><div style="font-size:28px;font-weight:800;color:#856404;"><?= (int)$stats['pending'] ?></div><small style="color:#856404;">بانتظار التقييم</small></div>
            </div>
        </div>
    </div>

    <table class="table table-hover">
        <thead><tr><th>الموظف</th><th>الرقم</th><th>القسم</th><th>حالة التقييم</th><th>الدرجة</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($participants as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p->full_name) ?></td>
                <td><?= htmlspecialchars($p->emp_code) ?></td>
                <td><?= htmlspecialchars($p->dept_name ?? '—') ?></td>
                <td><?php if ($p->status === 'completed'): ?><span class="badge bg-success">مكتمل</span><?php else: ?><span class="badge bg-warning text-dark">بانتظار</span><?php endif; ?></td>
                <td><?= $p->final_score !== null ? '<strong style="color:#0c2823;">' . number_format((float)$p->final_score, 2) . '</strong>' : '—' ?></td>
                <td><?php if ($p->status !== 'completed'): ?><a href="<?= APP_URL ?>/evaluations/bulk-create" class="btn btn-sm btn-outline-primary">تقييم</a><?php else: ?><a href="<?= APP_URL ?>/evaluations/<?= (int)$p->eval_id ?>" class="btn btn-sm btn-outline-secondary">عرض</a><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($stats['pending'] > 0): ?>
    <div class="text-center mt-3">
        <div style="background:linear-gradient(90deg,#0c2823,#0d5e52);border-radius:10px;padding:14px;">
            <a href="<?= APP_URL ?>/evaluations/bulk-create" style="color:#c8943f;font-weight:700;font-size:14px;text-decoration:none;">📊 تقييم جميع الموظفين المتبقين في هذه الدورة ←</a>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
