<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($pageTitle ?? 'طباعة تقرير'); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Tajawal:wght@400;500;700;800&display=swap');
:root{ --navy:#0f2a44; --gold:#c5a880; --gold-light:#e8d5b5; --bg:#f4f5f7; --border:#d6d9e0; --text:#1a2332; --muted:#6b7280;}
*{font-family:'Tajawal','Amiri',serif; -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important;}
@page{ size:A4; margin:6mm 10mm;}
body{ margin:0; background:#fff; color:var(--text); font-size:11px;}
.a4-page{ width:190mm; margin:0 auto; background:#fff; padding:0;}
.doc-header{ background: linear-gradient(180deg, #fafbfc 0%, #ffffff 100%); border:1px solid var(--border); border-radius:7px 7px 0 0; border-bottom:2px solid var(--navy); padding:8px 12px; display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:8px;}
.header-emblem{ width:32px; height:32px; border:1.2px solid var(--gold); border-radius:50%; display:flex; align-items:center; justify-content:center; background:#fff; flex-shrink:0;}
.header-emblem i{ font-size:14px; color:var(--navy);}
.header-titles{ flex:1; text-align:center;}
.header-titles .kingdom{ font-size:8px; font-weight:700; color:var(--navy); letter-spacing:.7px; margin:0;}
.header-titles .company{ font-size:8.5px; color:var(--muted); margin:0 0 1px;}
.header-titles .basmala{ font-family:'Amiri',serif; font-size:9.5px; color:#2d3a4f; margin:0;}
.header-titles .doc-title{ font-size:13.5px; font-weight:800; color:var(--navy); margin:2px 0 0; position:relative; display:inline-block; padding-bottom:2px;}
.header-titles .doc-title::after{ content:''; position:absolute; bottom:0; left:15%; right:15%; height:2px; background: linear-gradient(90deg, transparent, var(--gold), transparent);}
.header-meta{ min-width:118px; text-align:left; font-size:8px; line-height:1.35; color:#334155; background:#f8fafc; border:1px solid #e8eaed; border-radius:6px; padding:4px 6px;}
.header-meta p{ margin:0;}
.section{ margin-bottom:6px; }
.section-head{ display:flex; align-items:center; gap:6px; margin-bottom:4px; padding-bottom:3px; border-bottom:1.5px solid var(--navy);}
.section-num{ width:18px; height:18px; background:var(--navy); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:9.5px; font-weight:800; flex-shrink:0;}
.section-head h2{ margin:0; font-size:10.5px; font-weight:800; color:var(--navy);}
.section-head .line{ flex:1; height:1px; background:#e8eaed;}
.info-card{ border:1px solid var(--border); border-radius:6px; overflow:hidden;}
.info-grid{ display:grid; grid-template-columns:1fr 1fr; }
.info-cell{ padding:4px 8px; display:flex; flex-direction:column; gap:1px; border-bottom:1px solid #f1f3f5; border-left:1px solid #f1f3f5;}
.info-cell:nth-child(even){ border-left:none;}
.info-cell:nth-last-child(-n+2){ border-bottom:none;}
.info-cell label{ font-size:7.5px; font-weight:700; color:var(--muted); display:flex; align-items:center; gap:3px; justify-content:center;}
.info-cell label i{ color:var(--gold); font-size:7.5px;}
.info-cell span{ font-size:10px; font-weight:600; color:var(--text); text-align:center; display:block; width:100%;}
.criterion-card{ border:1px solid var(--border); border-radius:6px; overflow:hidden; margin-bottom:5px; break-inside:avoid;}
.criterion-head{ background:var(--navy); color:#fff; padding:4px 10px; display:flex; justify-content:space-between; align-items:center; border-bottom:1.2px solid var(--gold);}
.criterion-head h3{ margin:0; font-size:10.5px; font-weight:800; color:#fff !important; display:flex; align-items:center; gap:5px;}
.criterion-head h3 i{ color:var(--gold); font-size:9px; background:rgba(255,255,255,.12); width:16px; height:16px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;}
.criterion-head .count{ background:#fff; color:var(--navy); border-radius:20px; padding:1px 6px; font-size:9px; font-weight:800;}
.eval-table{ width:100%; border-collapse:collapse; font-size:10px;}
.eval-table th{ background:#f8f9fb; color:var(--navy); font-weight:700; font-size:9px; padding:4px 6px; text-align:center; border-bottom:1px solid var(--border);}
.eval-table th:first-child{ text-align:right; padding-right:10px;}
.eval-table td{ padding:4px 7px; border-bottom:1px solid #f1f3f5; font-size:10px;}
.eval-table td:nth-child(2){ text-align:center; width:20%;}
.badge{ display:inline-block; min-width:48px; padding:2px 6px; border-radius:20px; font-size:9.5px; font-weight:700; text-align:center; border:1px solid;}
.badge-excellent{ background:#ecfdf5; color:#065f46; border-color:#a7f3d0;}
.badge-good{ background:#eff6ff; color:#1e40af; border-color:#bfdbfe;}
.badge-average{ background:#fef3c7; color:#92400e; border-color:#fde68a;}
.badge-na{ background:#f8fafc; color:#94a3b8; border-color:#e2e8f0;}
.evaluator-card{ background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%); border:1px solid var(--border); border-radius:6px; padding:7px; display:grid; grid-template-columns:repeat(3,1fr); gap:6px;}
.eval-person{ text-align:center; padding:5px; background:#fff; border:1px solid #e8eaed; border-radius:5px;}
.eval-person .lbl{ font-size:8px; font-weight:700; color:var(--muted); margin-bottom:2px; display:flex; align-items:center; justify-content:center; gap:3px;}
.eval-person .lbl i{ color:var(--gold);}
.eval-person .val{ font-size:10px; font-weight:700; color:var(--navy);}
.signature-area{ display:flex; justify-content:space-between; gap:8px; margin-top:8px; padding-top:6px; border-top:1px dashed #d6d9e0;}
.sig-box{ text-align:center; flex:1;}
.sig-line{ width:100px; height:1px; background:var(--navy); margin:12px auto 4px; position:relative;}
.sig-line::after{ content:'✦'; position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); background:#fff; padding:0 3px; color:var(--gold); font-size:6px;}
.sig-label{ font-size:9px; font-weight:700; color:var(--navy);}
.sig-sub{ font-size:8px; color:var(--muted);}
.doc-footer{ border-top:1px solid var(--navy); margin-top:10px; padding-top:6px; display:flex; justify-content:space-between; font-size:8px; color:var(--muted);}
@media print{ body{background:#fff;} }
</style>
</head>
<body onload="window.print()">
<div class="a4-page">
  <header class="doc-header">
    <div class="header-emblem"><span style="font-size:14px; font-weight:800; color:var(--navy);">HR</span></div>
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
      <?php endif; ?>
      <p style="margin-top:4px; padding-top:4px; border-top:1px dashed #e2e8f0; font-size:9px;"><i class="fas fa-hashtag" style="color:var(--gold);"></i> تقرير #<?php echo str_pad($evaluation->id,5,'0',STR_PAD_LEFT); ?></p>
    </div>
  </header>

  <div style="padding:14px 18px 10px;">
    <div class="section">
      <div class="section-head"><div class="section-num">١</div><h2>بيانات الموظف المُقيَّم</h2><div class="line"></div></div>
      <div class="info-card"><div class="info-grid">
        <div class="info-cell"><label><i class="fas fa-user"></i> اسم الموظف</label><span><?php echo htmlspecialchars($evaluation->employee_name); ?></span></div>
        <div class="info-cell"><label><i class="fas fa-hashtag"></i> الرقم الوظيفي</label><span dir="ltr"><?php echo htmlspecialchars($evaluation->employee_code); ?></span></div>
        <div class="info-cell"><label><i class="fas fa-tag"></i> الكنية / الصفة</label><span><?php echo htmlspecialchars($evaluation->nickname ?: '— لا يوجد —'); ?></span></div>
        <div class="info-cell"><label><i class="fas fa-briefcase"></i> المنصب</label><span><?php echo htmlspecialchars($evaluation->position_name ?: '—'); ?></span></div>
        <div class="info-cell"><label><i class="fas fa-building"></i> الفرع</label><span><?php echo htmlspecialchars($evaluation->branch_name); ?></span></div>
        <div class="info-cell"><label><i class="fas fa-users"></i> القسم</label><span><?php echo htmlspecialchars($evaluation->department_name); ?></span></div>
      </div></div>
    </div>

    <div class="section">
      <div class="section-head"><div class="section-num">٢</div><h2>معايير التقييم</h2><div class="line"></div></div>
      <?php foreach($template['criteria'] as $criterion): ?>
      <div class="criterion-card">
        <div class="criterion-head"><h3><i class="fas fa-tasks"></i> <?php echo htmlspecialchars($criterion['criterion_name']); ?></h3><span class="count"><?php echo count($criterion['sections']); ?> بنود</span></div>
        <table class="eval-table"><thead><tr><th style="width:42%">عنصر التقييم</th><th>المستوى</th><th style="width:36%">ملاحظات</th></tr></thead><tbody>
        <?php foreach($criterion['sections'] as $section): ?>
          <?php $answer=$answers[$section['id']]??null; $display=null; $notes=''; $cls='badge-na';
            if($answer){ if($section['evaluation_type']==='grade'){ $opts=json_decode($section['evaluation_options'],true); foreach($opts as $opt){ if((string)($opt['value']??'')===(string)$answer->raw_value || (string)($opt['label']??'')===(string)$answer->raw_value){ $display=$opt['label']; break; } } } else $display=$answer->raw_value; $notes=$answer->notes ?? ''; $low=strtolower((string)$display); if($display!==null && (strpos($low,'ممتاز')!==false || $display==='طبق' || $display==='قرأ' || $display==='رفع' || $display==='إلتزم')) $cls='badge-excellent'; elseif($display!==null && (strpos($low,'جيد')!==false || $display==='96' || $display==='91')) $cls='badge-good'; elseif($display==='—' || $display===null) $cls='badge-na'; else $cls='badge-average'; }
          ?>
          <tr><td><?php echo htmlspecialchars($section['section_name']); ?></td><td><span class="badge <?php echo $cls; ?>"><?php echo htmlspecialchars($display ?? '—'); ?></span></td><td><?php echo $notes ? nl2br(htmlspecialchars($notes)) : '<span style="color:#cbd5e1;">—</span>'; ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="section">
      <div class="section-head"><div class="section-num">٣</div><h2>بيانات القائم بالتقييم</h2><div class="line"></div></div>
      <div class="evaluator-card">
        <div class="eval-person"><div class="lbl"><i class="fas fa-user-tie"></i> الاسم</div><div class="val"><?php echo htmlspecialchars($evaluation->evaluator_name ?? $evaluator->full_name ?? 'غير محدد'); ?></div></div>
        <div class="eval-person"><div class="lbl"><i class="fas fa-award"></i> الصفة</div><div class="val"><?php echo htmlspecialchars($evaluation->evaluator_title ?? $evaluator->position_title ?? '—'); ?></div></div>
        <div class="eval-person"><div class="lbl"><i class="fas fa-calendar"></i> التاريخ</div><div class="val" dir="ltr"><?php echo date('Y/m/d', strtotime($evaluation->evaluation_date)); ?></div></div>
      </div>
      <div class="signature-area">
        <div class="sig-box"><div class="sig-line"></div><div class="sig-label">توقيع المقيّم</div><div class="sig-sub"><?php echo htmlspecialchars($evaluation->evaluator_name ?? ''); ?></div></div>
        <div class="sig-box"><div class="sig-line"></div><div class="sig-label">توقيع مسؤول النظام</div><div class="sig-sub"><?php echo htmlspecialchars(\App\Core\Auth::user()->username); ?></div></div>
        <div class="sig-box"><div class="sig-line"></div><div class="sig-label">الختم الرسمي</div><div class="sig-sub" style="color:#94a3b8;">—</div></div>
      </div>
    </div>
  </div>

  <footer class="doc-footer">
    <div><span style="display:inline-block; width:6px; height:6px; background:var(--gold); border-radius:50%; vertical-align:middle; margin-left:4px;"></span> وثيقة سرية — للاستخدام الداخلي فقط</div>
    <div>المستخدم: <?php echo htmlspecialchars(\App\Core\Auth::user()->username); ?> · <?php echo date('Y/m/d H:i'); ?></div>
  </footer>
</div>
<script>window.onafterprint=function(){ window.close(); };</script>
</body>
</html>
