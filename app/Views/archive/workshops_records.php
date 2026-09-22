<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<?php $ratings=(new App\Models\RatingSetting())->getAll(); function ratingFor($score,$ratings){ foreach($ratings as $r) if($score >= $r->min_score && $score <= $r->max_score) return $r; return end($ratings)?:(object)['label'=>'غير مصنف','color'=>'#64748b']; } ?>
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/archive.css">
<?php
$evalStatuses = ['draft'=>'مسودة','pending_review'=>'قيد المراجعة','approved'=>'معتمد','completed'=>'مكتمل','expired'=>'منتهي'];
$currentFrom = $_GET['from'] ?? '';
$currentTo = $_GET['to'] ?? '';
$currentSearch = $_GET['search'] ?? '';
$currentStatus = $_GET['status'] ?? '';
$filterBase = APP_URL . '/archive/workshops/records/' . $parentDeptId . '/' . $year;
?>
<div class="eval-wrap"><div class="eval-container">
  <div class="page-header">
    <div class="breadcrumb-bar">
      <a href="<?= APP_URL ?>/archive/workshops/branches">الفرع الرئيسي</a>
      <span class="sep">/</span>
      <a href="<?= APP_URL ?>/archive/workshops/branches/<?= $parentBranchId ?? 0 ?>/departments">الأقسام</a>
      <span class="sep">/</span>
      <a href="<?= APP_URL ?>/archive/workshops/departments/<?= $parentDeptId ?>/years">السنوات</a>
      <span class="sep">/</span><span class="current">سجلات <?= $year ?></span>
    </div>
    <div class="page-title-sm"><div class="page-title-icon-sm"><i class="fas fa-list-alt"></i></div> <?= $pageTitle ?></div>
  </div>
  <div class="filter-bar">
    <form method="GET" action="<?= $filterBase ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;">
      <div class="filter-group" style="flex:1;min-width:200px;">
        <label><i class="fas fa-search"></i> بحث:</label>
        <input type="text" name="search" placeholder="اسم الورشة، الفرع، القسم..." value="<?= htmlspecialchars($currentSearch) ?>" style="flex:1;">
      </div>
      <div class="filter-sep"></div>
      <div class="filter-group"><label>من:</label><input type="date" name="from" value="<?= htmlspecialchars($currentFrom) ?>"></div>
      <div class="filter-group"><label>إلى:</label><input type="date" name="to" value="<?= htmlspecialchars($currentTo) ?>"></div>
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
      <button type="submit" class="btn-apply"><i class="fas fa-search"></i> بحث</button>
      <?php if($currentSearch || $currentFrom || $currentTo || $currentStatus): ?>
        <a href="<?= $filterBase ?>" class="btn-clear"><i class="fas fa-times"></i> مسح</a>
      <?php endif; ?>
      <span class="result-count">عرض <strong><?= count($records) ?></strong> سجل</span>
    </form>
  </div>
  <div class="table-card">
    <table class="modern-table">
      <thead><tr><th>#</th><th>الورشة</th><th>الفرع / القسم</th><th>من → إلى</th><th>التقييمات</th><th>المتوسط</th><th>أعلى / أدنى</th><th>حالة الورشة</th><th>الإجراء</th></tr></thead>
      <tbody>
        <?php if(!empty($records)): foreach($records as $i=>$r): $avg=round($r->avg_score ?? 0, 1); $rat=ratingFor($avg,$ratings); $color=$rat->color; ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($r->workshop_title) ?></strong></td>
            <td><?= htmlspecialchars($r->branch_name ?? '-') ?> / <?= htmlspecialchars($r->department_name ?? '-') ?></td>
            <td><small><?= $r->start_date ?> → <?= $r->end_date ?></small></td>
            <td><span class="count-pill"><?= (int)$r->eval_count ?></span></td>
            <td>
              <?php if($r->eval_count > 0): ?>
                <div class="score-ring-xs" style="--score:<?= $avg ?>;--ring-color:<?= $color ?>"><span style="color:<?= $color ?>"><?= $avg ?>%</span></div>
              <?php else: ?>
                <span style="color:#94a3b8">—</span>
              <?php endif; ?>
            </td>
            <td><small><?= $r->max_score !== null ? round($r->max_score,1).'%' : '-' ?> / <?= $r->min_score !== null ? round($r->min_score,1).'%' : '-' ?></small></td>
            <td><span class="status-badge <?= $r->workshop_status ?>"><?= match($r->workshop_status){'completed'=>'مكتملة','ongoing'=>'جارية','scheduled'=>'مجدولة','canceled'=>'ملغاة',default=>$r->workshop_status} ?></span></td>
            <td><a href="<?= APP_URL ?>/archive/workshops/show/<?= $r->id ?>" class="btn-view-sm"><i class="fas fa-eye"></i> عرض</a></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--muted)">لا توجد سجلات ورش</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div></div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
