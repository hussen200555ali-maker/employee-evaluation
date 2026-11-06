<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
?>
<style>
.archive-filter-bar { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
.archive-filter-bar select, .archive-filter-bar input {
    padding:9px 14px; border:1px solid #ddd; border-radius:8px; font-family:'Cairo'; font-size:13px;
}
.archive-filter-bar input { flex:1; min-width:180px; }
.archive-filter-bar .btn-go { background:#0c2823; color:#c8943f; border:none; padding:9px 20px; border-radius:8px; cursor:pointer; font-weight:700; }
.archive-table { width:100%; border-collapse:collapse; background:#fff; border-radius:0 0 12px 12px; overflow:hidden; box-shadow:0 1px 6px rgba(0,0,0,0.06); }
.archive-table thead th { background:#0c2823; color:#fff; padding:10px 14px; text-align:right; font-size:12px; font-weight:700; }
.archive-table tbody td { padding:10px 14px; border-bottom:1px solid #eee; font-size:12px; text-align:right; }
.archive-table tbody tr:nth-child(even) { background:#f5f0e8; }
.archive-table tbody tr:hover { background:rgba(200,148,63,0.05); }
.rpt-num { font-weight:700; color:#0c2823; }
.actions-cell { position:relative; display:flex; gap:6px; }
.btn-view { background:#0c2823; color:#c8943f; border:none; padding:5px 12px; border-radius:6px; cursor:pointer; font-size:11px; font-weight:600; text-decoration:none; }
.btn-export-dd { background:#f5f0e8; color:#0c2823; border:1px solid #ddd; padding:5px 12px; border-radius:6px; cursor:pointer; font-size:11px; font-weight:600; }
.export-menu { display:none; position:absolute; top:100%; left:0; background:#fff; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,0.12); min-width:140px; z-index:100; padding:4px; margin-top:2px; }
.export-menu.show { display:block; }
.export-menu a { display:flex; align-items:center; gap:6px; padding:8px 12px; font-size:11px; color:#333; text-decoration:none; border-radius:6px; }
.export-menu a:hover { background:#f5f0e8; }
.pagination { display:flex; justify-content:center; gap:4px; padding:16px 0; }
.pagination a { width:34px; height:34px; border-radius:8px; border:1px solid #ddd; background:#fff; color:#6c757d; font-size:12px; font-weight:600; display:flex; align-items:center; justify-content:center; text-decoration:none; }
.pagination a.active { background:#0c2823; color:#c8943f; border-color:#0c2823; }
.pagination a:hover { border-color:#c8943f; color:#0c2823; }
.pagination a.nav { width:auto; padding:0 10px; }
.empty-state { text-align:center; padding:50px 20px; }
.empty-state .icon { font-size:42px; color:#ddd; margin-bottom:12px; }
.empty-state .text { font-size:14px; color:#999; font-weight:600; }
.empty-state .hint { font-size:11px; color:#ccc; margin-top:6px; }
.stats-row { display:flex; gap:12px; margin-bottom:20px; }
.stat-box { background:#fff; border:1px solid #eee; border-radius:12px; padding:16px; flex:1; text-align:center; box-shadow:0 1px 4px rgba(0,0,0,0.03); }
.stat-box .val { font-size:24px; font-weight:900; color:#0c2823; }
.stat-box .lbl { font-size:11px; color:#999; margin-top:2px; }
.stat-box.hl { background:#0c2823; border-color:#c8943f; }
.stat-box.hl .val { color:#c8943f; }
.stat-box.hl .lbl { color:rgba(255,255,255,0.5); }
</style>

<div class="container-fluid">
    <h2><?= htmlspecialchars($pageTitle ?? '') ?></h2>
    <hr>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box hl"><div class="val"><?= $totalCount ?? 0 ?></div><div class="lbl">إجمالي التقارير</div></div>
        <div class="stat-box"><div class="val"><?= count($snapshots ?? []) ?></div><div class="lbl">في هذه الصفحة</div></div>
        <div class="stat-box"><div class="val"><?= $totalPages ?? 1 ?></div><div class="lbl">عدد الصفحات</div></div>
    </div>

    <!-- Filters + Search -->
    <form method="GET" action="/reports/archive" class="archive-filter-bar">
        <select name="branch_id">
            <option value="0">كل الفروع</option>
            <?php foreach ($branches ?? [] as $b): ?>
                <option value="<?= (int)$b->id ?>" <?= ($branchId ?? 0) === (int)$b->id ? 'selected' : '' ?>><?= htmlspecialchars($b->name) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="year">
            <option value="0">كل السنوات</option>
            <?php foreach ($years ?? [] as $y): ?>
                <option value="<?= $y ?>" <?= ($year ?? 0) === $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="بحث برقم المرجع، اسم الموظف، أو الرقم الوظيفي...">
        <button type="submit" class="btn-go">🔍 بحث</button>
    </form>

    <!-- Table -->
    <table class="archive-table">
        <thead>
            <tr>
                <th>رقم التقرير</th>
                <th>الموظف</th>
                <th>الرقم الوظيفي</th>
                <th>الفرع</th>
                <th>تاريخ الإصدار</th>
                <th>أصدره</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($snapshots)): ?>
                <?php foreach ($snapshots as $snap): ?>
                    <tr>
                        <td class="rpt-num"><?= htmlspecialchars($snap->report_number) ?></td>
                        <td><?= htmlspecialchars($snap->full_name) ?></td>
                        <td><?= htmlspecialchars($snap->emp_code) ?></td>
                        <td><?= htmlspecialchars($snap->branch_name ?? '') ?></td>
                        <td><?= htmlspecialchars($snap->created_at) ?></td>
                        <td><?= htmlspecialchars($snap->created_by ?? '') ?></td>
                        <td>
                            <div class="actions-cell">
                                <a href="/employees/profile/<?= (int)$snap->employee_id ?>/reports/<?= (int)$snap->id ?>" class="btn-view">👁 عرض</a>
                                <button class="btn-export-dd" onclick="toggleExport(<?= (int)$snap->id ?>)">⬇ تصدير ▾</button>
                                <div class="export-menu" id="export-<?= (int)$snap->id ?>">
                                    <a href="/reports/export/<?= (int)$snap->id ?>/pdf">📄 تصدير PDF</a>
                                    <a href="/reports/export/<?= (int)$snap->id ?>/xlsx">📊 تصدير Excel</a>
                                    <a href="/reports/export/<?= (int)$snap->id ?>/docx">📝 تصدير Word</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr class="empty-state">
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="icon">📋</div>
                            <div class="text">لا توجد تقارير صادرة حالياً</div>
                            <div class="hint">عند إصدار تقارير، ستظهر هنا في السجل المؤرشف</div>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if (($totalPages ?? 1) > 1): ?>
    <div class="pagination">
        <?php if (($page ?? 1) > 1): ?>
            <a class="nav" href="?q=<?= urlencode($search ?? '') ?>&branch_id=<?= $branchId ?? 0 ?>&year=<?= $year ?? 0 ?>&page=<?= ($page ?? 1) - 1 ?>">‹ السابق</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= min($totalPages ?? 1, 10); $i++): ?>
            <a class="<?= ($page ?? 1) === $i ? 'active' : '' ?>" href="?q=<?= urlencode($search ?? '') ?>&branch_id=<?= $branchId ?? 0 ?>&year=<?= $year ?? 0 ?>&page=<?= $i ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if (($totalPages ?? 1) > 10): ?>
            <span style="color:#999; padding:0 4px;">…</span>
            <a href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
        <?php endif; ?>
        <?php if (($page ?? 1) < ($totalPages ?? 1)): ?>
            <a class="nav" href="?q=<?= urlencode($search ?? '') ?>&branch_id=<?= $branchId ?? 0 ?>&year=<?= $year ?? 0 ?>&page=<?= ($page ?? 1) + 1 ?>">التالي ›</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Toggle export dropdown
function toggleExport(id) {
    // Close all other menus
    document.querySelectorAll('.export-menu').forEach(function(m) {
        if (m.id !== 'export-' + id) m.classList.remove('show');
    });
    // Toggle the clicked one
    var menu = document.getElementById('export-' + id);
    menu.classList.toggle('show');
}
// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.actions-cell')) {
        document.querySelectorAll('.export-menu').forEach(function(m) { m.classList.remove('show'); });
    }
});
</script>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
