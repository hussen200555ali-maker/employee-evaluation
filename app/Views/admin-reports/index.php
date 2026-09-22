<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/archive.css">

<div class="eval-wrap"><div class="eval-container">
  <div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
      <div class="page-title"><div class="page-title-icon"><i class="fas fa-clipboard-list"></i></div> <?= $pageTitle ?></div>
      <div class="stats-summary">
        <div class="stat-card"><div class="label">إجمالي التقارير</div><div class="value"><?= $stats['total'] ?></div></div>
        <div class="stat-card"><div class="label">مكتملة</div><div class="value"><?= $stats['completed'] ?></div></div>
        <div class="stat-card"><div class="label">قيد المراجعة</div><div class="value"><?= $stats['pending'] ?></div></div>
        <div class="stat-card"><div class="label">متوسط المطابقة</div><div class="value"><?= $stats['avg_compliance'] ?>%</div></div>
      </div>
    </div>
  </div>
  
  <div class="filter-bar">
    <form method="GET" action="<?= APP_URL ?>/admin/reports" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;">
      <div class="filter-group">
        <label><i class="fas fa-filter"></i> الحالة:</label>
        <select name="status">
          <option value="">الكل</option>
          <option value="draft">مسودة</option>
          <option value="completed">مكتمل</option>
          <option value="approved">معتمد</option>
        </select>
      </div>
      <button type="submit" class="btn-apply"><i class="fas fa-search"></i> تصفية</button>
      <a href="<?= APP_URL ?>/admin/reports/create" class="btn btn-success"><i class="fas fa-plus"></i> إنشاء تقرير جديد</a>
    </form>
  </div>
  
  <?php if(!empty($reports)): ?>
    <table class="table table-hover" style="background:white;border-radius:12px;overflow:hidden;">
      <thead style="background:#0d5e52;color:white;">
        <tr>
          <th>#</th>
          <th>العنوان</th>
          <th>النوع</th>
          <th>الفترة</th>
          <th>نسبة المطابقة</th>
          <th>الحالة</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($reports as $index => $report): ?>
          <tr>
            <td><?= $index + 1 ?></td>
            <td><?= htmlspecialchars($report->report_title ?? $report->employee_name) ?></td>
            <td><span class="badge bg-info"><?= $report->entity_type ?></span></td>
            <td><?= $report->period_type ?></td>
            <td>
              <?php if($report->compliance_rate !== null): ?>
                <span class="badge" style="background:<?= $report->compliance_rate >= 90 ? '#22c55e' : ($report->compliance_rate >= 70 ? '#f59e0b' : '#ef4444') ?>">
                  <?= round($report->compliance_rate, 1) ?>%
                </span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php 
              $statusLabels = ['draft' => 'مسودة', 'completed' => 'مكتمل', 'approved' => 'معتمد', 'pending_review' => 'قيد المراجعة'];
              $statusColors = ['draft' => 'secondary', 'completed' => 'success', 'approved' => 'primary', 'pending_review' => 'warning'];
              $status = $report->status ?? 'draft';
              ?>
              <span class="badge bg-<?= $statusColors[$status] ?? 'secondary' ?>"><?= $statusLabels[$status] ?? $status ?></span>
            </td>
            <td>
              <a href="<?= APP_URL ?>/admin/reports/show/<?= $report->id ?>" class="btn btn-sm btn-outline-primary" title="عرض"><i class="fas fa-eye"></i></a>
              <a href="<?= APP_URL ?>/admin/reports/edit/<?= $report->id ?>" class="btn btn-sm btn-outline-secondary" title="تعديل"><i class="fas fa-edit"></i></a>
              <a href="<?= APP_URL ?>/admin/reports/print/<?= $report->id ?>" class="btn btn-sm btn-outline-success" title="طباعة" target="_blank"><i class="fas fa-print"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="empty-state"><i class="fas fa-clipboard"></i><p>لا توجد تقارير إدارية بعد</p></div>
  <?php endif; ?>
</div></div>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>