<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
$course = $course ?? null;
$participants = $participants ?? [];
$stats = $stats ?? ['total' => 0, 'completed' => 0, 'pending' => 0];
?>
<!-- CD-033.1: تفاصيل الدورة — بدون أزرار ربط (الربط يتم أثناء التقييم) -->
<div class="container mt-4">
    <!-- بطاقة معلومات الدورة -->
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

    <!-- بطاقات الإحصائيات -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center text-white" style="background:linear-gradient(135deg,#0c2823,#0d5e52);">
                <div class="card-body"><div style="font-size:28px;font-weight:800;color:#c8943f;"><?= (int)$stats['total'] ?></div><small>إجمالي الموظفين المُقيَّمين</small></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center" style="background:#d4edda;border:1px solid #c3e6cb;">
                <div class="card-body"><div style="font-size:28px;font-weight:800;color:#155724;"><?= (int)$stats['completed'] ?></div><small style="color:#155724;">تقييمات مكتملة</small></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center" style="background:var(--gold-bg,#faefd9);border:1px solid #c8943f;">
                <div class="card-body"><div style="font-size:28px;font-weight:800;color:#c8943f;">
                    <?php
                    // متوسط الدرجات
                    $scores = [];
                    foreach ($participants as $p) {
                        if ($p->final_score !== null) { $scores[] = (float)$p->final_score; }
                    }
                    echo count($scores) > 0 ? number_format(array_sum($scores) / count($scores), 1) : '—';
                    ?>
                </div><small style="color:#c8943f;">متوسط الدرجات</small></div>
            </div>
        </div>
    </div>

    <!-- جدول الموظفين المُقيَّمين فقط -->
    <?php if (empty($participants)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="fas fa-info-circle fa-2x mb-2"></i><br>
            <strong>لم يتم تقييم أي موظف في هذه الدورة حتى الآن.</strong><br>
            <small class="text-muted">يتم ربط الموظفين بالدورة تلقائياً عند إنشاء تقييم لهم في هذه الدورة.</small>
        </div>
    <?php else: ?>
    <table class="table table-hover">
        <thead><tr><th>الموظف</th><th>الرقم</th><th>القسم</th><th>الدرجة</th><th>التقدير</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($participants as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p->full_name) ?></td>
                <td><?= htmlspecialchars($p->emp_code) ?></td>
                <td><?= htmlspecialchars($p->dept_name ?? '—') ?></td>
                <td><?= $p->final_score !== null ? '<strong style="color:#0c2823;">' . number_format((float)$p->final_score, 2) . '</strong>' : '—' ?></td>
                <td><?= htmlspecialchars($p->grade_label ?? '—') ?></td>
                <td>
                    <?php if ($p->eval_id): ?>
                        <a href="<?= APP_URL ?>/evaluations/<?= (int)$p->eval_id ?>" class="btn btn-sm btn-outline-secondary">عرض</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
