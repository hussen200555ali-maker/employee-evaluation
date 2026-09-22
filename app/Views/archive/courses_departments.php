<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<?php $ratings=(new App\Models\RatingSetting())->getAll(); function ratingFor($score,$ratings){ foreach($ratings as $r) if($score >= $r->min_score && $score <= $r->max_score) return $r; return end($ratings)?:(object)['label'=>'غير مصنف','color'=>'#64748b']; } ?>
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/archive.css">
<?php
$evalStatuses = ['draft'=>'مسودة','pending_review'=>'قيد المراجعة','approved'=>'معتمد','completed'=>'مكتمل','expired'=>'منتهي'];
$currentFrom = $_GET['from'] ?? '';
$currentTo = $_GET['to'] ?? '';
$currentStatus = $_GET['status'] ?? '';
$currentSearch = $_GET['search'] ?? '';
$filterBase = APP_URL . '/archive/courses/branches/' . $parentBranchId . '/departments';
?>
<div class="eval-wrap"><div class="eval-container">
  <div class="page-header">
    <div class="breadcrumb-bar">
      <a href="<?= APP_URL ?>/archive/courses/branches">الفرع الرئيسي</a>
      <span class="sep">/</span><span class="current">الأقسام</span>
    </div>
    <div class="page-title-sm"><div class="page-title-icon-sm"><i class="fas fa-sitemap"></i></div> <?= $pageTitle ?></div>
  </div>
  <div class="filter-bar">
    <form method="GET" action="<?= $filterBase ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;">
      <div class="filter-group"><label>من:</label><input type="date" name="from" value="<?= htmlspecialchars($currentFrom) ?>"></div>
      <div class="filter-group"><label>إلى:</label><input type="date" name="to" value="<?= htmlspecialchars($currentTo) ?>"></div>
      <div class="filter-group" style="flex:1;min-width:180px;">
        <label><i class="fas fa-search"></i> بحث:</label>
        <input type="text" name="search" placeholder="بحث في اسم الدورة..." value="<?= htmlspecialchars($currentSearch) ?>" style="width:100%;">
      </div>
      <div class="filter-sep"></div>
      <div class="filter-group">
        <label><i class="fas fa-filter"></i> حالة:</label>
        <select name="status">
          <option value="">الكل (مقيّم فقط)</option>
          <?php foreach($evalStatuses as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= $currentStatus === $val ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn-apply"><i class="fas fa-search"></i> تصفية</button>
      <?php if($currentFrom || $currentTo || $currentStatus || $currentSearch): ?>
        <a href="<?= $filterBase ?>" class="btn-clear"><i class="fas fa-times"></i> مسح</a>
      <?php endif; ?>
      <span class="result-count">عرض <strong><?= count($departments) ?></strong> قسم<?php if($currentSearch): ?> (بحث: "<?= htmlspecialchars($currentSearch) ?>")<?php endif; ?></span>
    </form>
  </div>
  <?php if(!empty($departments)): ?>
    <div class="cards-grid cols-3">
      <?php foreach($departments as $d): $avg=round($d->avg_score ?? 0, 1); $rat=ratingFor($avg,$ratings); $color=$rat->color; ?>
        <a href="<?= APP_URL ?>/archive/courses/departments/<?= $d->department_id ?>/years?from=<?= urlencode($currentFrom) ?>&to=<?= urlencode($currentTo) ?>&status=<?= urlencode($currentStatus) ?>&search=<?= urlencode($currentSearch) ?>" class="dept-card">
          <div class="card-name"><i class="fas fa-folder"></i> <?= htmlspecialchars($d->department_name) ?></div>
          <div class="card-stats">
            <span class="stat-pill total"><i class="fas fa-layer-group"></i> <?= (int)$d->total_courses ?> دورة</span>
            <span class="stat-pill evaluated"><i class="fas fa-check-circle"></i> <?= (int)$d->evaluated_count ?> مقيّمة</span>
            <?php if($d->evaluated_count > 0): ?>
              <div class="score-ring-xs" style="--score:<?= $avg ?>;--ring-color:<?= $color ?>"><span style="color:<?= $color ?>"><?= $avg ?>%</span></div>
            <?php else: ?>
              <span style="color:#94a3b8;font-size:13px;">—</span>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state"><i class="fas fa-folder-open"></i><p>لا توجد أقسام في هذا الفرع</p></div>
  <?php endif; ?>
</div></div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
