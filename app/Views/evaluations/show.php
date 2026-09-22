<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Tajawal:wght@400;500;700;800&display=swap');
:root{ --navy:#0f2a44; --navy-light:#1e3a5f; --gold:#c5a880; --gold-light:#e8d5b5; --bg:#f4f5f7; --card:#ffffff; --border:#d6d9e0; --border-light:#e8eaed; --text:#1a2332; --muted:#6b7280; --accent:#0f766e; }
*{font-family:'Tajawal','Amiri',serif;}
.page-actions{ max-width:210mm; margin:0 auto 14px; display:flex; justify-content:space-between; align-items:center; padding:0 2px;}
.page-actions .breadcrumb{ font-size:12px; color:var(--muted); display:flex; align-items:center; gap:6px;}
.page-actions .breadcrumb a{ color:var(--muted); text-decoration:none;}
.page-actions .breadcrumb a:hover{ color:var(--navy);}
.btn-print{ background:var(--navy); color:#fff; border:none; padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:7px; box-shadow:0 2px 8px rgba(15,42,68,.2);}
.btn-print:hover{ background:var(--navy-light); color:#fff;}
.a4-container{ background:var(--bg); padding:24px 0 36px;}
.a4-page{ background:var(--card); width:210mm; min-height:285mm; margin:0 auto; box-shadow:0 12px 48px rgba(15,42,68,.12), 0 2px 12px rgba(0,0,0,.06); border-radius:3px; overflow:hidden; position:relative;}
.a4-page::before{ content:''; position:absolute; top:0; left:0; right:0; height:4px; background: linear-gradient(90deg, var(--navy) 0%, var(--gold) 50%, var(--navy) 100%);}
.doc-header{ background: linear-gradient(180deg, #fafbfc 0%, #ffffff 100%); border-bottom:1px solid var(--border); padding:18px 22px 16px; display:flex; justify-content:space-between; align-items:flex-start; gap:16px;}
.header-emblem{ width:52px; height:52px; border:1.5px solid var(--gold); border-radius:50%; display:flex; align-items:center; justify-content:center; background:#fff; flex-shrink:0;}
.header-emblem i{ font-size:22px; color:var(--navy);}
.header-titles{ flex:1; text-align:center; padding:0 12px;}
.header-titles .kingdom{ font-size:10px; font-weight:700; color:var(--navy); letter-spacing:1.2px; text-transform:uppercase; margin:0;}
.header-titles .company{ font-size:11px; color:var(--muted); margin:1px 0 6px;}
.header-titles .basmala{ font-family:'Amiri',serif; font-size:13px; color:#2d3a4f; margin:0; font-style:italic;}
.header-titles .doc-title{ font-size:17px; font-weight:800; color:var(--navy); margin:6px 0 0; letter-spacing:.3px; position:relative; display:inline-block; padding-bottom:6px;}
.header-titles .doc-title::after{ content:''; position:absolute; bottom:0; left:15%; right:15%; height:2.5px; background: linear-gradient(90deg, transparent, var(--gold), transparent); border-radius:2px;}
.header-meta{ min-width:155px; text-align:left; font-size:10.5px; line-height:1.65; color:#334155; background:#f8fafc; border:1px solid var(--border-light); border-radius:8px; padding:8px 10px;}
.header-meta p{ margin:1px 0;}
.header-meta strong{ color:var(--navy); font-weight:700;}
.doc-body{ padding:18px 22px 10px;}
.section{ margin-bottom:16px;}
.section-head{ display:flex; align-items:center; gap:10px; margin-bottom:10px; padding-bottom:7px; border-bottom:2px solid var(--navy);}
.section-num{ width:28px; height:28px; background:var(--navy); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; flex-shrink:0;}
.section-head h2{ margin:0; font-size:13.5px; font-weight:800; color:var(--navy); letter-spacing:.2px;}
.section-head .line{ flex:1; height:1px; background:var(--border-light); margin-right:8px;}
.info-card{ background:#fff; border:1px solid var(--border); border-radius:10px; overflow:hidden;}
.info-grid{ display:grid; grid-template-columns:1fr 1fr; }
.info-cell{ padding:10px 14px; display:flex; flex-direction:column; gap:3px; border-bottom:1px solid #f1f3f5; border-left:1px solid #f1f3f5;}
.info-cell:nth-child(even){ border-left:none;}
.info-cell:nth-last-child(-n+2){ border-bottom:none;}
.info-cell label{ font-size:10px; font-weight:700; color:var(--muted); letter-spacing:.4px; text-transform:uppercase; display:flex; align-items:center; gap:5px; justify-content:center;}
.info-cell label i{ color:var(--gold); font-size:10px;}
.info-cell span{ font-size:13px; font-weight:600; color:var(--text); text-align:center; display:block; width:100%;}
.info-cell span.empty{ color:#9ca3b8; font-weight:400; font-style:italic;}
.criterion-card{ background:#fff; border:1px solid var(--border); border-radius:10px; overflow:hidden; margin-bottom:12px; box-shadow:0 1px 3px rgba(15,42,68,.04);}
.criterion-head{ background:var(--navy); color:#fff; padding:10px 14px; display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid var(--gold);}
.criterion-head h3{ margin:0; font-size:13px; font-weight:800; color:#ffffff !important; display:flex; align-items:center; gap:7px; letter-spacing:.2px; text-shadow:0 1px 2px rgba(0,0,0,.25);}
.criterion-head h3 i{ color:var(--gold); font-size:12px; background:rgba(255,255,255,.12); width:24px; height:24px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;}
.criterion-head .count{ background:#fff; color:var(--navy); border:none; border-radius:20px; padding:3px 10px; font-size:11px; font-weight:800; box-shadow:0 1px 3px rgba(0,0,0,.15);}
.eval-table{ width:100%; border-collapse:collapse; font-size:12px;}
.eval-table thead th{ background:#f8f9fb; color:var(--navy); font-weight:700; font-size:11px; padding:8px 10px; text-align:center; border-bottom:1px solid var(--border); letter-spacing:.3px;}
.eval-table thead th:first-child{ text-align:right; padding-right:14px;}
.eval-table tbody td{ padding:9px 12px; border-bottom:1px solid #f1f3f5; vertical-align:middle;}
.eval-table tbody tr:last-child td{ border-bottom:none;}
.eval-table tbody td:first-child{ color:#1e293b; font-weight:500; font-size:12px;}
.eval-table tbody td:nth-child(2){ text-align:center; width:22%;}
.eval-table td:last-child{ color:#64748b; font-size:11.5px; text-align:center;}
.badge{ display:inline-block; min-width:68px; padding:5px 10px; border-radius:20px; font-size:11.5px; font-weight:700; text-align:center; border:1px solid;}
.badge-excellent{ background:#ecfdf5; color:#065f46; border-color:#a7f3d0;}
.badge-good{ background:#eff6ff; color:#1e40af; border-color:#bfdbfe;}
.badge-average{ background:#fef3c7; color:#92400e; border-color:#fde68a;}
.badge-weak{ background:#fef2f2; color:#991b1b; border-color:#fecaca;}
.badge-na{ background:#f8fafc; color:#94a3b8; border-color:#e2e8f0;}
.evaluator-card{ background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%); border:1px solid var(--border); border-radius:10px; padding:16px; display:grid; grid-template-columns:repeat(3,1fr); gap:12px; position:relative; overflow:hidden;}
.evaluator-card::before{ content:''; position:absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg, var(--navy), var(--gold), var(--navy));}
.eval-person{ text-align:center; padding:10px 8px; background:#fff; border:1px solid var(--border-light); border-radius:8px;}
.eval-person .lbl{ font-size:10px; font-weight:700; color:var(--muted); letter-spacing:.5px; margin-bottom:5px; display:flex; align-items:center; justify-content:center; gap:5px;}
.eval-person .lbl i{ color:var(--gold); font-size:10px;}
.eval-person .val{ font-size:13px; font-weight:700; color:var(--navy);}
.signature-area{ display:flex; justify-content:space-between; align-items:flex-end; margin-top:18px; padding-top:14px; border-top:1px dashed #d6d9e0;}
.sig-box{ text-align:center; min-width:160px;}
.sig-line{ width:160px; height:1px; background:var(--navy); margin:28px auto 6px; position:relative;}
.sig-line::after{ content:'✦'; position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); background:#fff; padding:0 6px; color:var(--gold); font-size:8px;}
.sig-label{ font-size:10.5px; font-weight:700; color:var(--navy);}
.sig-sub{ font-size:10px; color:var(--muted);}
.doc-footer{ background:#f8f9fb; border-top:1px solid var(--border); padding:10px 22px; display:flex; justify-content:space-between; align-items:center; font-size:10px; color:var(--muted);}
.doc-footer .left{ display:flex; align-items:center; gap:6px;}
.doc-footer .dot{ width:4px; height:4px; background:var(--gold); border-radius:50%;}
@media print{ .page-actions{display:none;} .a4-container{padding:0; background:#fff;} .a4-page{box-shadow:none; border:none; width:auto;}}
@media (max-width:700px){ .a4-page{width:96%;} .info-grid{grid-template-columns:1fr;} .evaluator-card{grid-template-columns:1fr;} .header-emblem{display:none;}}
</style>

<div class="page-actions">
  <div class="breadcrumb"><a href="<?php echo APP_URL; ?>/evaluations"><i class="fas fa-folder"></i> الملفات</a> <i class="fas fa-chevron-left" style="font-size:10px;"></i> <span>معاينة التقرير</span></div>
  <a href="<?php echo APP_URL . '/evaluations/print/' . $evaluation->id; ?>" target="_blank" class="btn-print"><i class="fas fa-print"></i> طباعة رسمية</a>
</div>

<div class="a4-container">
<div class="a4-page">
  <header class="doc-header">
    <div class="header-emblem"><span style="font-size:15px; font-weight:800; color:var(--navy); letter-spacing:.5px;">HR</span></div>
    <div class="header-titles">
      <p class="kingdom">المملكة العربية السعودية</p>
      <p class="company">اسم الشركة — وحدة الموارد البشرية</p>
      <p class="basmala">بسم الله الرحمن الرحيم</p>
      <h1 class="doc-title"><?php echo htmlspecialchars($template['template_name']); ?></h1>
    </div>
    <div class="header-meta">
      <p><strong>التاريخ:</strong> <?php echo date('Y/m/d', strtotime($evaluation->evaluation_date)); ?></p>
      <?php if(isset($hijriDate) && $hijriDate): ?>
        <p><strong>هجري:</strong> <?php echo "{$hijriDate->day} {$hijriDate->month_name} {$hijriDate->year}"; ?></p>
        <p><strong>اليوم:</strong> <?php echo htmlspecialchars($hijriDate->weekday); ?></p>
      <?php else: ?>
        <p><strong>الرقم:</strong> <?php echo htmlspecialchars($evaluation->employee_code); ?></p>
      <?php endif; ?>
      <p style="margin-top:4px; padding-top:4px; border-top:1px dashed #e2e8f0; font-size:10px; color:#64748b;"><i class="fas fa-hashtag" style="color:var(--gold);"></i> تقرير #<?php echo str_pad($evaluation->id,5,'0',STR_PAD_LEFT); ?></p>
    </div>
  </header>

  <div class="doc-body">
    <div class="section">
      <div class="section-head"><div class="section-num">١</div><h2>بيانات الموظف المُقيَّم</h2><div class="line"></div></div>
      <div class="info-card">
        <div class="info-grid">
          <div class="info-cell"><label><i class="fas fa-user"></i> اسم الموظف</label><span><?php echo htmlspecialchars($evaluation->employee_name); ?></span></div>
          <div class="info-cell"><label><i class="fas fa-hashtag"></i> الرقم الوظيفي</label><span dir="ltr"><?php echo htmlspecialchars($evaluation->employee_code); ?></span></div>
          <div class="info-cell"><label><i class="fas fa-tag"></i> الكنية / الصفة</label><span class="<?php echo empty($evaluation->nickname)?'empty':''; ?>"><?php echo htmlspecialchars($evaluation->nickname ?: '— لا يوجد —'); ?></span></div>
          <div class="info-cell"><label><i class="fas fa-briefcase"></i> المنصب</label><span><?php echo htmlspecialchars($evaluation->position_name ?: '—'); ?></span></div>
          <div class="info-cell"><label><i class="fas fa-building"></i> الفرع</label><span><?php echo htmlspecialchars($evaluation->branch_name); ?></span></div>
          <div class="info-cell"><label><i class="fas fa-users"></i> القسم</label><span><?php echo htmlspecialchars($evaluation->department_name); ?></span></div>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-head"><div class="section-num">٢</div><h2>معايير التقييم</h2><div class="line"></div></div>
      <?php foreach($template['criteria'] as $cIdx=>$criterion): ?>
      <div class="criterion-card">
        <div class="criterion-head"><h3><i class="fas fa-tasks"></i> <?php echo htmlspecialchars($criterion['criterion_name']); ?></h3><span class="count"><?php echo count($criterion['sections']); ?> بنود</span></div>
        <table class="eval-table">
          <thead><tr><th style="width:42%">عنصر التقييم</th><th>المستوى</th><th style="width:36%">ملاحظات</th></tr></thead>
          <tbody>
          <?php foreach($criterion['sections'] as $section): ?>
            <?php $answer=$answers[$section['id']]??null; $display=null; $notes=''; $cls='badge-na';
              if($answer){
                if($section['evaluation_type']==='grade'){ $opts=json_decode($section['evaluation_options'],true); foreach($opts as $opt){ if((string)($opt['value']??'')===(string)$answer->raw_value || (string)($opt['label']??'')===(string)$answer->raw_value){ $display=$opt['label']; break; } } if($display===null && is_string($opts[0]??null)) $display=$answer->raw_value; }
                else $display=$answer->raw_value;
                $notes=$answer->notes ?? '';
                $low=strtolower((string)$display); if($display!==null && (strpos($low,'ممتاز')!==false || $display==='طبق' || $display==='قرأ' || $display==='رفع' || $display==='إلتزم')) $cls='badge-excellent'; elseif($display!==null && (strpos($low,'جيد')!==false || $display==='96' || $display==='91')) $cls='badge-good'; elseif($display==='—' || $display===null) $cls='badge-na'; else $cls='badge-average';
              }
            ?>
            <tr><td><?php echo htmlspecialchars($section['section_name']); ?></td><td><span class="badge <?php echo $cls; ?>"><?php echo htmlspecialchars($display ?? '—'); ?></span></td><td><?php echo $notes ? nl2br(htmlspecialchars($notes)) : '<span style="color:#cbd5e1;">—</span>'; ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="section">
      <div class="section-head"><div class="section-num">٣</div><h2>بيانات القائم بالتقييم</h2><div class="line"></div></div>
      <div class="evaluator-card">
        <div class="eval-person"><div class="lbl"><i class="fas fa-user-tie"></i> الاسم</div><div class="val"><?php echo htmlspecialchars($evaluation->evaluator_name ?? $evaluator->full_name ?? 'غير محدد'); ?></div></div>
        <div class="eval-person"><div class="lbl"><i class="fas fa-award"></i> الصفة</div><div class="val"><?php echo htmlspecialchars($evaluation->evaluator_title ?? $evaluator->position_title ?? '—'); ?></div></div>
        <div class="eval-person"><div class="lbl"><i class="fas fa-calendar"></i> تاريخ التقييم</div><div class="val" dir="ltr"><?php echo date('Y/m/d', strtotime($evaluation->evaluation_date)); ?></div></div>
      </div>
      <div class="signature-area">
        <div class="sig-box"><div class="sig-line"></div><div class="sig-label">توقيع المقيّم</div><div class="sig-sub"><?php echo htmlspecialchars($evaluation->evaluator_name ?? ''); ?></div></div>
        <div class="sig-box"><div class="sig-line"></div><div class="sig-label">توقيع مسؤول النظام</div><div class="sig-sub"><?php echo htmlspecialchars(\App\Core\Auth::user()->username); ?></div></div>
        <div class="sig-box"><div class="sig-line"></div><div class="sig-label">الختم الرسمي</div><div class="sig-sub" style="color:#94a3b8;">—</div></div>
      </div>
    </div>
  </div>

  <footer class="doc-footer">
    <div class="left"><span class="dot"></span> وثيقة سرية — للاستخدام الداخلي فقط</div>
    <div>المستخدم: <?php echo htmlspecialchars(\App\Core\Auth::user()->username); ?> · <?php echo date('Y/m/d H:i'); ?></div>
  </footer>
</div>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
