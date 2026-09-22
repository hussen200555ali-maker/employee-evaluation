<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<?php $ratings=(new App\Models\RatingSetting())->getAll(); function ratingForW($score,$ratings){ foreach($ratings as $r) if($score >= $r->min_score && $score <= $r->max_score) return $r; return end($ratings)?:(object)['label'=>'غير مصنف','color'=>'#64748b']; } ?>
<style>
:root{--brand:#0d5e52;--gold:#c8943f;--border:#e2e8f0;--muted:#64748b;}
.eval-wrap{background:#f0f4f8;min-height:100vh;padding:24px 0 40px;}
.eval-container{width:min(96%,1600px);margin:0 auto;}
.page-header{background:linear-gradient(135deg,#0c2823 0%,#0d5e52 55%,#115e4b 100%);border-radius:20px;padding:28px;color:#fff;}
.page-title{font-size:32px;font-weight:900;display:flex;align-items:center;gap:14px;}
.page-title-icon{width:56px;height:56px;border-radius:14px;background:rgba(255,255,255,.12);border:2px solid rgba(200,148,63,.3);display:flex;align-items:center;justify-content:center;color:var(--gold);}
.table-card{background:#fff;border:1px solid var(--border);border-radius:16px;overflow:hidden;}
.modern-table{width:100%;border-collapse:separate;border-spacing:0;}
.modern-table th{background:#e9edf2;padding:14px 16px;font-weight:700;border-bottom:2px solid var(--border);text-align:right;}
.modern-table td{padding:12px 14px;border-bottom:1px solid #f1f5f9;}
.score-ring-xs{width:44px;height:44px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:conic-gradient(var(--ring-color) calc(var(--score)*3.6deg),#eef2f7 0deg);}
.score-ring-xs::before{content:'';position:absolute;width:33px;height:33px;background:#fff;border-radius:50%;}
.score-ring-xs span{position:relative;font-weight:900;font-size:12px;}
.btn-view-sm{padding:7px 14px;border-radius:8px;background:var(--brand);color:#fff;text-decoration:none;font-weight:700;border:2px solid var(--brand);}
.count-pill{background:rgba(59,130,246,.08);color:#3b82f6;border-radius:20px;padding:4px 12px;font-weight:800;font-size:13px;}
</style>
<div class="eval-wrap"><div class="eval-container">
  <div class="page-header"><div class="page-title"><div class="page-title-icon"><i class="fas fa-tools"></i></div> ملفات أداء الورش</div></div>
  <div class="table-card" style="margin-top:20px;">
    <table class="modern-table">
      <thead><tr><th>الورشة</th><th>الفرع</th><th>عدد التقييمات</th><th>المتوسط</th><th>الإجراء</th></tr></thead>
      <tbody>
        <?php if(!empty($items)): foreach($items as $it): $avg=round($it->avg_score??0,1); $rat=ratingForW($avg,$ratings); $color=$rat->color; ?>
          <tr>
            <td><strong><?= htmlspecialchars($it->name) ?></strong></td>
            <td><?= htmlspecialchars($it->branch_name ?? '-') ?></td>
            <td><span class="count-pill"><?= (int)$it->cnt ?> تقييم</span></td>
            <td><?php if($it->cnt>0): ?><div class="score-ring-xs" style="--score:<?= $avg ?>;--ring-color:<?= $color ?>"><span style="color:<?= $color ?>"><?= $avg ?>%</span></div><?php else: ?>—<?php endif; ?></td>
            <td><a href="<?= APP_URL ?>/reports/unified?entity_type=workshop" class="btn-view-sm">عرض السجل</a></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--muted)">لا توجد ورش مقيمة</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div></div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
