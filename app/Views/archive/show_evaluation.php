<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<?php $ratings=(new App\Models\RatingSetting())->getAll(); function ratingFor($score,$ratings){ foreach($ratings as $r) if($score >= $r->min_score && $score <= $r->max_score) return $r; return end($ratings)?:(object)['label'=>'غير مصنف','color'=>'#64748b']; } ?>
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/archive.css">
<style>
.eval-container{width:min(96%,1200px);}
.info-bar{display:flex;gap:16px;flex-wrap:wrap;margin-top:16px;}
.info-pill{background:rgba(255,255,255,.12);padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;}
.memo-card{background:#fff;border-radius:16px;border:1px solid var(--border);padding:32px;box-shadow:0 2px 8px rgba(0,0,0,.04);margin-top:20px;}
.memo-header{text-align:center;margin-bottom:32px;border-bottom:3px double var(--brand);padding-bottom:24px;}
.memo-header h2{font-size:24px;font-weight:900;color:var(--brand);margin-bottom:8px;}
.memo-header p{color:var(--muted);font-size:14px;}
.score-display{text-align:center;margin:24px 0;}
.score-ring{width:120px;height:120px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:conic-gradient(var(--ring-color) calc(var(--score)*3.6deg),#eef2f7 0deg);position:relative;margin:0 auto;}
.score-ring::before{content:'';position:absolute;width:96px;height:96px;background:#fff;border-radius:50%;}
.score-ring span{position:relative;font-weight:900;font-size:28px;}
.rating-label{margin-top:8px;font-size:18px;font-weight:900;}
.section-card{background:#f8fafc;border-radius:12px;border:1px solid var(--border);padding:20px;margin-bottom:16px;}
.section-card h3{font-size:16px;font-weight:800;color:var(--brand);margin-bottom:12px;display:flex;align-items:center;gap:8px;}
.answer-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9;}
.answer-row:last-child{border-bottom:none;}
.answer-label{font-weight:700;color:var(--text);}
.answer-value{font-weight:800;color:var(--brand);}
.btn-print{padding:12px 24px;background:var(--brand);color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:15px;transition:all .2s;}
.btn-print:hover{background:var(--gold);}
.actions-bar{display:flex;gap:12px;margin-top:24px;justify-content:center;}
</style>
<div class="eval-wrap"><div class="eval-container">
  <div class="page-header">
    <div class="page-title"><div class="page-title-icon"><i class="fas fa-file-alt"></i></div> <?= $pageTitle ?></div>
    <div class="info-bar">
      <span class="info-pill"><i class="fas fa-user"></i> المقيّم: <?= htmlspecialchars($evaluation->evaluator_name ?? '-') ?></span>
      <span class="info-pill"><i class="fas fa-calendar"></i> التاريخ: <?= $evaluation->evaluation_date ?></span>
      <span class="info-pill"><i class="fas fa-file"></i> القالب: <?= htmlspecialchars($evaluation->template_name ?? '-') ?></span>
      <?php if(!empty($evaluation->course_title)): ?>
        <span class="info-pill"><i class="fas fa-chalkboard-teacher"></i> الدورة: <?= htmlspecialchars($evaluation->course_title) ?></span>
      <?php endif; ?>
      <?php if(!empty($evaluation->workshop_title)): ?>
        <span class="info-pill"><i class="fas fa-tools"></i> الورشة: <?= htmlspecialchars($evaluation->workshop_title) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="memo-card" id="memo-content">
    <div class="memo-header">
      <h2>بسم الله الرحمن الرحيم</h2>
      <h2>تقرير تقييم <?= !empty($evaluation->course_title) ? 'الدورة التدريبية' : 'الورشة التدريبية' ?></h2>
      <p><?= !empty($evaluation->course_title) ? htmlspecialchars($evaluation->course_title) : htmlspecialchars($evaluation->workshop_title ?? '') ?></p>
    </div>

    <?php $score = $evaluation->final_score ?? 0; $rat = ratingFor($score, $ratings); ?>
    <div class="score-display">
      <div class="score-ring" style="--score:<?= $score ?>;--ring-color:<?= $rat->color ?>">
        <span style="color:<?= $rat->color ?>"><?= round($score, 1) ?>%</span>
      </div>
      <div class="rating-label" style="color:<?= $rat->color ?>"><?= htmlspecialchars($rat->label) ?></div>
    </div>

    <?php
    $grouped = [];
    foreach ($answers as $a) {
        $sectionName = $a->section_name ?? 'قسم عام';
        $grouped[$sectionName][] = $a;
    }
    foreach ($grouped as $sectionName => $items):
    ?>
      <div class="section-card">
        <h3><i class="fas fa-folder-open" style="color:var(--gold)"></i> <?= htmlspecialchars($sectionName) ?></h3>
        <?php foreach ($items as $item): ?>
          <div class="answer-row">
            <span class="answer-label"><?= htmlspecialchars($item->section_name ?? '-') ?></span>
            <span class="answer-value">
              <?php if($item->normalized_score !== null): ?>
                <?= round($item->normalized_score, 1) ?> / <?= $item->max_score ?? 100 ?>
              <?php else: ?>
                <?= htmlspecialchars($item->raw_value ?? '-') ?>
              <?php endif; ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <?php if(!empty($evaluation->notes)): ?>
      <div class="section-card">
        <h3><i class="fas fa-sticky-note" style="color:var(--gold)"></i> ملاحظات</h3>
        <p style="color:var(--text);line-height:1.8;"><?= nl2br(htmlspecialchars($evaluation->notes)) ?></p>
      </div>
    <?php endif; ?>
  </div>

  <div class="actions-bar no-print">
    <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> طباعة</button>
    <a href="javascript:history.back()" class="btn-print" style="background:var(--muted);"><i class="fas fa-arrow-right"></i> رجوع</a>
  </div>
</div></div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
