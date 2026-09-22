<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/archive.css">

<div class="eval-wrap"><div class="eval-container">
  <div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
      <div class="page-title"><div class="page-title-icon"><i class="fas fa-archive"></i></div> <?= $pageTitle ?></div>
      <a href="<?= APP_URL ?>/admin/reports" class="btn btn-light border"><i class="fas fa-arrow-right"></i> العودة</a>
    </div>
  </div>

  <?php if(!empty($reports)): ?>
    <table class="table table-hover" style="background:white;border-radius:12px;overflow:hidden;">
      <thead style="background:#0d5e52;color:white;">
        <tr>
          <th>#</th>
          <th>العنوان</th>
          <th>الفترة</th>
          <th>التاريخ</th>
          <th>نسبة المطابقة</th>
          <th>الحالة</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($reports as $index => $report): ?>
          <tr style="opacity:0.7;">
            <td><?= $index + 1 ?></td>
            <td><?= htmlspecialchars($report->report_title ?? 'تقرير #' . $report->id) ?></td>
            <td><?= $report->period_type ?? '—' ?></td>
            <td><?= $report->evaluation_date ?? '—' ?></td>
            <td>
              <?php if($report->compliance_rate !== null): ?>
                <span class="badge" style="background:#6b7280;"><?= round($report->compliance_rate, 1) ?>%</span>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
            <td><span class="badge bg-secondary">مؤرشف</span></td>
            <td>
              <a href="<?= APP_URL ?>/admin/reports/show/<?= $report->id ?>" class="btn btn-sm btn-outline-primary" title="عرض"><i class="fas fa-eye"></i></a>
              <a href="<?= APP_URL ?>/admin/reports/print/<?= $report->id ?>" class="btn btn-sm btn-outline-success" title="طباعة" target="_blank"><i class="fas fa-print"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="empty-state"><i class="fas fa-archive"></i><p>لا توجد تقارير مؤرشفة</p></div>
  <?php endif; ?>
</div></div>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
