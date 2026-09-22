<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
$permissions = $dashboard['permissions'];
$trendJson = json_encode($dashboard['trend'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$distributionJson = json_encode($dashboard['distribution'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$employeeLabel = in_array($dashboard['persona'], ['admin', 'hr'], true) ? 'الموظفون النشطون' : ($dashboard['persona'] === 'manager' ? 'أعضاء الفريق' : 'الموظفون المقيمون');
?>
<main class="container dashboard-shell role-dashboard" data-persona="<?= htmlspecialchars($dashboard['persona']) ?>">
    <section class="dashboard-hero">
        <div class="dashboard-hero__content">
            <span class="eyebrow"><i class="fas fa-shield-alt"></i> <?= htmlspecialchars($dashboard['persona_label']) ?> · <?= htmlspecialchars($dashboard['scope_label']) ?></span>
            <h1>صباح الأداء، <?= htmlspecialchars($dashboard['user_name']) ?></h1>
            <p>هذه القراءة مصممة لدورك الحالي، ولا تعرض إلا التقييمات والمهام التي يسمح بها نطاق حسابك.</p>
            <div class="dashboard-hero__actions">
                <?php if (!empty($permissions['evaluations.fill'])): ?><a class="btn btn-light dashboard-hero__button" href="<?= APP_URL ?>/evaluations/excel/import"><i class="fas fa-file-excel ms-1"></i> مركز Excel</a><?php endif; ?>
                <?php if (!empty($permissions['reports.view.all']) || !empty($permissions['reports.view.team'])): ?><a class="btn btn-outline-light dashboard-hero__outline" href="<?= APP_URL ?>/evaluations"><i class="fas fa-chart-line ms-1"></i> استعراض الأداء</a><?php endif; ?>
                <?php if (!empty($permissions['evaluations.create'])): ?><a class="btn btn-outline-light dashboard-hero__outline" href="<?= APP_URL ?>/evaluations/create"><i class="fas fa-plus ms-1"></i> بدء تقييم</a><?php endif; ?>
            </div>
        </div>
        <div class="dashboard-orbit" aria-hidden="true"><span></span><span></span><span></span><i class="fas fa-chart-line"></i></div>
    </section>

    <section class="metric-grid" aria-label="مؤشرات الأداء">
        <article class="metric-tile"><span class="metric-tile__icon metric-tile__icon--teal"><i class="fas fa-clipboard-check"></i></span><div><small>إجمالي التقييمات</small><strong><?= (int)$dashboard['summary']['evaluations'] ?></strong><em><?= htmlspecialchars($dashboard['scope_label']) ?></em></div></article>
        <article class="metric-tile"><span class="metric-tile__icon metric-tile__icon--gold"><i class="fas fa-hourglass-half"></i></span><div><small>مهام مفتوحة</small><strong><?= (int)$dashboard['summary']['open'] ?></strong><em>تتطلب متابعة</em></div></article>
        <article class="metric-tile"><span class="metric-tile__icon metric-tile__icon--blue"><i class="fas fa-chart-line"></i></span><div><small>متوسط الأداء</small><strong><?= number_format((float)$dashboard['summary']['average'], 1) ?><b>/100</b></strong><em>التقييمات المكتملة</em></div></article>
        <article class="metric-tile"><span class="metric-tile__icon metric-tile__icon--violet"><i class="fas fa-users"></i></span><div><small><?= htmlspecialchars($employeeLabel) ?></small><strong><?= (int)$dashboard['summary']['employees'] ?></strong><em>ضمن نطاق حسابك</em></div></article>
    </section>

    <section class="dashboard-analytics">
        <article class="analytics-card analytics-card--wide"><div class="analytics-card__head"><div><span class="eyebrow">الاتجاه الزمني</span><h2>حجم العمل ومتوسط النتيجة</h2></div><span class="chart-key"><i></i> آخر ستة أشهر</span></div><div class="chart-area"><canvas id="dashboardTrendChart" aria-label="رسم اتجاه التقييمات"></canvas></div></article>
        <article class="analytics-card"><div class="analytics-card__head"><div><span class="eyebrow">جودة الأداء</span><h2>توزيع النتائج</h2></div></div><div class="chart-area chart-area--donut"><canvas id="dashboardDistributionChart" aria-label="رسم توزيع النتائج"></canvas></div></article>
    </section>

    <section class="dashboard-lower-grid">
        <article class="dashboard-panel"><div class="dashboard-panel__head"><div><span class="eyebrow">طابور العمل</span><h2>مهام تتطلب إجراء</h2></div><span class="panel-count"><?= count($dashboard['tasks']) ?></span></div><?php if ($dashboard['tasks'] === []): ?><div class="empty-panel"><i class="fas fa-check-circle"></i><strong>لا توجد مهام مفتوحة</strong><p>تبدو مساحة عملك منظمة حالياً.</p></div><?php else: ?><div class="task-list"><?php foreach ($dashboard['tasks'] as $task): ?><div class="task-row"><span class="task-row__icon"><i class="fas fa-pen"></i></span><div><strong><?= htmlspecialchars($task['employee_name']) ?></strong><small><?= htmlspecialchars($task['template_name'] ?? 'قالب تقييم') ?> · <?= htmlspecialchars($task['evaluation_date']) ?></small></div><?php if (!empty($permissions['evaluations.fill'])): ?><a href="<?= APP_URL ?>/evaluations/<?= (int)$task['id'] ?>/fill" aria-label="فتح التقييم"><i class="fas fa-arrow-left"></i></a><?php endif; ?></div><?php endforeach; ?></div><?php endif; ?></article>
        <article class="dashboard-panel"><div class="dashboard-panel__head"><div><span class="eyebrow">آخر النشاط</span><h2>تقييمات حديثة</h2></div><span class="panel-count"><?= count($dashboard['recent']) ?></span></div><?php if ($dashboard['recent'] === []): ?><div class="empty-panel"><i class="fas fa-chart-area"></i><strong>لا توجد سجلات بعد</strong><p>ستظهر هنا أحدث التقييمات ضمن نطاقك.</p></div><?php else: ?><div class="activity-list"><?php foreach ($dashboard['recent'] as $item): ?><div class="activity-row"><span class="activity-avatar"><?= htmlspecialchars(mb_substr($item['employee_name'], 0, 1)) ?></span><div><strong><?= htmlspecialchars($item['employee_name']) ?></strong><small><?= htmlspecialchars($item['template_name'] ?? 'تقييم أداء') ?> · <?= htmlspecialchars($item['evaluation_date']) ?></small></div><div class="activity-score"><?php if ($item['status'] === 'completed'): ?><strong><?= number_format((float)$item['final_score'], 1) ?></strong><small>مكتمل</small><?php else: ?><strong>—</strong><small>مسودة</small><?php endif; ?></div></div><?php endforeach; ?></div><?php endif; ?></article>
    </section>

    <section class="dashboard-grid" aria-label="اختصارات مخصصة">
        <?php if (!empty($permissions['evaluations.fill'])): ?><a class="dashboard-shortcut" href="<?= APP_URL ?>/evaluations/excel/import"><span class="shortcut-icon shortcut-icon--green"><i class="fas fa-file-import"></i></span><span><strong>استيراد وتصدير النتائج</strong><small>قالب المقيّم، المعاينة، والالتزام الآمن</small></span><i class="fas fa-arrow-left"></i></a><?php endif; ?>
        <?php if (!empty($permissions['employees.view'])): ?><a class="dashboard-shortcut" href="<?= APP_URL ?>/employees"><span class="shortcut-icon shortcut-icon--blue"><i class="fas fa-users"></i></span><span><strong>دليل الموظفين</strong><small>استعرض السجل الوظيفي المتاح لك</small></span><i class="fas fa-arrow-left"></i></a><?php endif; ?>
        <?php if (!empty($permissions['reports.view.all']) || !empty($permissions['reports.view.team'])): ?><a class="dashboard-shortcut" href="<?= APP_URL ?>/reports/builder"><span class="shortcut-icon shortcut-icon--gold"><i class="fas fa-chart-pie"></i></span><span><strong>التقارير والتحليلات</strong><small>قراءة مؤشرات الأداء المعتمدة</small></span><i class="fas fa-arrow-left"></i></a><?php endif; ?>
        <?php if (!empty($permissions['templates.manage'])): ?><a class="dashboard-shortcut" href="<?= APP_URL ?>/templates"><span class="shortcut-icon shortcut-icon--violet"><i class="fas fa-pen-ruler"></i></span><span><strong>قوالب التقييم</strong><small>إدارة بنية المعايير والنماذج</small></span><i class="fas fa-arrow-left"></i></a><?php endif; ?>
    </section>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const trend = <?= $trendJson ?>;
    const distribution = <?= $distributionJson ?>;
    const common = {responsive:true, maintainAspectRatio:false, plugins:{legend:{labels:{font:{family:'Cairo'},usePointStyle:true,padding:18}}}};
    const trendCanvas = document.getElementById('dashboardTrendChart');
    if (trendCanvas && window.Chart) new Chart(trendCanvas, {type:'line',data:{labels:trend.labels,datasets:[{label:'عدد التقييمات',data:trend.counts,borderColor:'#0d5e52',backgroundColor:'rgba(13,94,82,.12)',borderWidth:3,tension:.38,fill:true,pointRadius:3,pointBackgroundColor:'#0d5e52'},{label:'متوسط الأداء',data:trend.averages,borderColor:'#c8943f',backgroundColor:'transparent',borderWidth:2.5,tension:.38,pointRadius:3,pointBackgroundColor:'#c8943f',yAxisID:'score'}]},options:{...common,scales:{x:{grid:{display:false},ticks:{font:{family:'Cairo'}}},y:{beginAtZero:true,grid:{color:'rgba(22,48,43,.07)'},ticks:{precision:0,font:{family:'Cairo'}}},score:{position:'left',min:0,max:100,grid:{drawOnChartArea:false},ticks:{font:{family:'Cairo'}}}}}});
    const distributionCanvas = document.getElementById('dashboardDistributionChart');
    if (distributionCanvas && window.Chart) new Chart(distributionCanvas, {type:'doughnut',data:{labels:distribution.labels,datasets:[{data:distribution.values,backgroundColor:['#0d5e52','#4f8b7d','#c8943f','#c85b54'],borderWidth:0,hoverOffset:6}]},options:{...common,cutout:'68%',plugins:{...common.plugins,legend:{position:'bottom',labels:{font:{family:'Cairo',size:11},usePointStyle:true,padding:14}}}}});
});
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
