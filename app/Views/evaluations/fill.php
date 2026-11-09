<?php
// ملف: app/Views/evaluations/fill.php (CD-039 — Enterprise dynamic redesign)
// استبدال واجهة الإدخال الفردي بتصميم CD-038 v2 الديناميكي
// يبني حقول المعايير ديناميكيًا من بنية القالب: criteria → sections (evaluation_type)
// يحافظ على عقد الإرسال: results[{criterion_id}][{section_id}][score|text|grade_label] + [notes]

require_once APP_ROOT . '/app/Views/layouts/header.php';

/* ===== helpers ===== */
function cd039_arabic_num($n){
    $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    return strtr((string)$n, '0123456789', implode('', $ar));
}
function cd039_esc($s){
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
function cd039_type_badge($type){
    $map = [
        'percentage'    => ['#1b6b32','نسبة مئوية'],
        'numeric'       => ['#1565c0','رقمي'],
        'grade'         => ['#c8943f','تقدير'],
        'textarea'      => ['#7b1fa2','نصي'],
        'boolean'       => ['#c62828','نعم / لا'],
        'overall_grade' => ['#c8943f','تقدير عام'],
        'formula'       => ['#dc3545','صيغة'],
        'auto'          => ['#0dcaf0','تلقائي'],
        'helper'        => ['#6c757d','مساعد'],
    ];
    $m = $map[$type] ?? $map['numeric'];
    return '<span class="cd039-ft"><span class="dot" style="background:'.$m[0].'"></span>'.$m[1].'</span>';
}
function cd039_has_quantitative($template){
    if(empty($template['criteria'])) return false;
    foreach($template['criteria'] as $c){
        foreach($c['sections'] ?? [] as $s){
            if(in_array($s['evaluation_type'] ?? '', ['percentage','numeric'])) return true;
        }
    }
    return false;
}
/* render one section (field row) by evaluation_type — preserves the exact results[][] contract */
function cd039_render_section($section, $criterion_id, $savedAnswers, $gradingScales, $si){
    global $cd039_global_template_mode;
    $sid   = (int)$section['id'];
    $type  = $section['evaluation_type'] ?? 'numeric';
    $name  = "results[{$criterion_id}][{$sid}]";
    $saved = $savedAnswers[$sid] ?? null;
    $raw   = $saved->raw_value ?? '';
    $notes = $saved->notes ?? '';
    $mv    = isset($section['max_value']) ? (int)$section['max_value'] : 100;
    $mn    = isset($section['min_value']) ? (int)$section['min_value'] : 0;
    $weight= isset($section['section_weight']) ? (float)$section['section_weight'] : 0;
    $allowNotes = !in_array($type, ['textarea','overall_grade'], true);

    $desc = !empty($section['section_description']) ? '<div class="cd039-fd">'.cd039_esc($section['section_description']).'</div>' : '';
    $out = '<div class="cd039-field" data-section-id="'.$sid.'" data-criterion-id="'.$criterion_id.'" data-type="'.cd039_esc($type).'">';
    $out .= '<div class="cd039-fh"><div><div class="cd039-fn">'.cd039_esc($section['section_name']).'</div>'.$desc.'</div>'.cd039_type_badge($type).'</div>';

    switch($type){
        case 'percentage':
            $out .= '<div class="cd039-perc"><div class="piw"><input type="number" class="cd039-eval cd039-score" data-max="'.$mv.'" name="'.$name.'[score]" min="0" max="'.$mv.'" step="1" placeholder="أدخل النسبة" value="'.cd039_esc($raw).'" oninput="cd039_updateBar(this);cd039_calc()"><span class="sign">٪</span></div><div class="ptr"><div class="pfill" style="width:'.cd039_esc($raw !== '' ? min(100,(float)$raw) : 0).'%"></div></div></div>';
            $out .= '<div class="cd039-scale"><span>٠</span><span>'.cd039_arabic_num((int)round($mv/2)).'</span><span>'.cd039_arabic_num($mv).'</span></div>';
            break;
        case 'numeric':
            $out .= '<div class="cd039-num"><input type="number" class="cd039-eval cd039-score" data-max="'.$mv.'" name="'.$name.'[score]" min="'.$mn.'" max="'.$mv.'" step="1" placeholder="أدخل الدرجة" value="'.cd039_esc($raw).'" oninput="cd039_calc()"><span class="nmax">من '.cd039_arabic_num($mv).'</span></div>';
            break;
        case 'grade':
            $opts = is_string($section['evaluation_options'] ?? '') ? json_decode($section['evaluation_options']) : ($section['evaluation_options'] ?? []);
            $optsHtml = '';
            if(is_array($opts) || is_object($opts)){
                foreach($opts as $opt){
                    $val = is_object($opt) ? $opt->value : $opt['value'];
                    $lbl = is_object($opt) ? $opt->label : $opt['label'];
                    $sel = ($raw !== '' && $val == $raw) ? 'selected' : '';
                    $optsHtml .= '<option value="'.cd039_esc($val).'" '.$sel.'>'.cd039_esc($lbl).'</option>';
                }
            }
            $out .= '<div class="cd039-grade"><select class="cd039-eval" name="'.$name.'[score]"><option value="">— اختر التقدير —</option>'.$optsHtml.'</select></div>';
            break;
        case 'boolean':
            $yesChk = ($raw === '1' || $raw === 1) ? 'sel-yes' : '';
            $noChk  = ($raw === '0' || $raw === 0) ? 'sel-no' : '';
            $out .= '<div class="cd039-bool"><button type="button" class="cd039-bopt '.$yesChk.'" data-val="1" onclick="cd039_bool(this)">✓ نعم</button><button type="button" class="cd039-bopt '.$noChk.'" data-val="0" onclick="cd039_bool(this)">✕ لا</button><input type="hidden" class="cd039-eval cd039-score" name="'.$name.'[score]" value="'.cd039_esc($raw === '' ? '' : $raw).'"></div>';
            break;
        case 'textarea':
            $textOpts = is_string($section['evaluation_options'] ?? '') ? json_decode($section['evaluation_options'], true) : ($section['evaluation_options'] ?? []);
            $maxChars = (int)($textOpts['max_chars'] ?? 1000);
            $textReq  = !empty($textOpts['required']);
            $out .= '<div class="cd039-text"><textarea class="cd039-eval" name="'.$name.'[text]" rows="4" maxlength="'.$maxChars.'" '.($textReq?'required':'').' placeholder="اكتب إجابتك الوصفية هنا... (حد أقصى '.cd039_arabic_num($maxChars).' حرف)">'.cd039_esc($raw).'</textarea></div>';
            break;
        case 'overall_grade':
            $gradeOpts = is_string($section['evaluation_options'] ?? '') ? json_decode($section['evaluation_options'], true) : ($section['evaluation_options'] ?? []);
            $scaleName = $gradeOpts['scale_name'] ?? 'السلم الخماسي';
            $sectionGrades = [];
            if(!empty($gradingScales)){
                foreach($gradingScales as $gs){
                    if(($gs->scale_name ?? '') === $scaleName) $sectionGrades[] = $gs;
                }
            }
            $optsHtml = '';
            foreach($sectionGrades as $gs){
                $sel = ($raw !== '' && $gs->grade_label === $raw) ? 'selected' : '';
                $optsHtml .= '<option value="'.cd039_esc($gs->grade_label).'" '.$sel.'>'.cd039_esc($gs->grade_label).' ('.cd039_esc($gs->numeric_value).')</option>';
            }
            $req = ($cd039_global_template_mode === 'descriptive') ? 'required' : '';
            $out .= '<div class="cd039-overall-inline"><div class="cd039-oh"><div class="cd039-og"><div class="cd039-oi">★</div><div><div class="cd039-ot">التقدير العام</div><div class="cd039-os">سلم: '.cd039_esc($scaleName).'</div></div></div>'.cd039_type_badge('overall_grade').'</div><select class="cd039-eval cd039-overall" name="'.$name.'[grade_label]" data-scale-name="'.cd039_esc($scaleName).'" '.$req.'><option value="">— اختر التقدير العام —</option>'.$optsHtml.'</select></div>';
            break;
        case 'formula':
            $fconf = json_decode($section['formula_config'] ?? '{}', true);
            $out .= '<div class="cd039-formula"><input type="text" readonly class="cd039-eval cd039-formula-res" data-section-id="'.$sid.'" data-formula-config="'.cd039_esc($section['formula_config'] ?? '{}').'" value="'.cd039_esc($raw !== '' ? $raw : '0').'"><small class="cd039-hint">⚙️ الصيغة: '.cd039_esc($fconf['formula'] ?? '').'</small></div>';
            break;
        case 'auto':
            $fconf = json_decode($section['formula_config'] ?? '{}', true);
            $out .= '<div class="cd039-formula"><input type="text" readonly class="cd039-eval cd039-auto-res" data-section-id="'.$sid.'" data-auto-source="'.cd039_esc($fconf['auto_source'] ?? '').'" value="'.cd039_esc($raw).'"><small class="cd039-hint">🤖 يُملأ تلقائيًا من النظام</small></div>';
            break;
        case 'helper':
            $out .= '<div class="cd039-num"><input type="number" class="cd039-eval cd039-helper" data-section-id="'.$sid.'" name="'.$name.'[score]" min="'.$mn.'" max="'.$mv.'" step="any" placeholder="أدخل قيمة مساعدة" value="'.cd039_esc($raw).'"></div><small class="cd039-hint">ℹ️ حقل مساعد — لا يُحتسب في الوزن</small>';
            break;
        default:
            $out .= '<div class="cd039-text"><input type="text" class="cd039-eval" name="'.$name.'[score]" placeholder="أدخل القيمة" value="'.cd039_esc($raw).'"></div>';
    }

    // notes field (except textarea/overall_grade)
    if($allowNotes){
        $out .= '<div class="cd039-notes"><label>📝 ملاحظات وشواهد</label><textarea name="'.$name.'[notes]" rows="2" placeholder="اكتب ملاحظاتك هنا...">'.cd039_esc($notes).'</textarea></div>';
    }
    $out .= '</div>';
    return $out;
}
$cd039_global_gradingScales = $gradingScales ?? [];
$cd039_global_template_mode = $template['evaluation_mode'] ?? 'numeric';
// patch: overall_grade needs template mode inside the function — pass via closure var
?>

<div class="cd039-wrap">
<div class="cd039-page">

<div class="cd039-hdr">
  <h2>📝 تعبئة استمارة التقييم</h2>
  <div class="cd039-sub"><?= cd039_esc($template['template_name'] ?? '') ?> · <?= cd039_esc($template['evaluation_mode'] ?? '') ?></div>
</div>

<form action="<?= APP_URL ?>/evaluations/submit" method="POST" id="evaluation-form">
<?= csrf_field() ?>
<input type="hidden" name="evaluation_id" value="<?= (int)$evaluation->id ?>">

<!-- بطاقة معلومات التقييم -->
<div class="cd039-card cd039-info-card">
  <div class="cd039-info-grid">
    <div class="cd039-info-item"><span>👤 الموظف</span><strong><?= cd039_esc($evaluation->employee_name) ?></strong></div>
    <div class="cd039-info-item"><span>💼 المنصب</span><strong><?= cd039_esc($evaluation->position_name ?? '') ?></strong></div>
    <div class="cd039-info-item"><span>👔 القائم بالتقييم</span><strong><?= cd039_esc($evaluation->evaluator_name ?? ($evaluator->full_name ?? 'غير محدد')) ?></strong></div>
    <div class="cd039-info-item"><span>📅 تاريخ التقييم</span><strong>
      <?php
        $hijriDate = function_exists('convertToHijri') ? convertToHijri($evaluation->evaluation_date) : null;
        echo cd039_esc($evaluation->evaluation_date ? date('d-m-Y', strtotime($evaluation->evaluation_date)) : '');
        if($hijriDate) echo ' — '.cd039_esc($hijriDate->full_date ?? '');
      ?>
    </strong></div>
  </div>
</div>

<?php
$targetType = $template['target_type'] ?? 'employee';
if($targetType === 'employee_in_course' || $targetType === 'employee_in_workshop'):
    $isCourse = ($targetType === 'employee_in_course');
    $ctxName  = $isCourse ? 'context_course_id' : 'context_workshop_id';
    $programs = (new \App\Models\TrainingProgram())->all($isCourse ? 'course' : 'workshop');
?>
<div class="cd039-card cd039-gold-card">
  <div class="cd039-ttl gold"><?= $isCourse ? '📚 الدورة التدريبية' : '🏆 الورشة' ?> — إلزامي <span class="cd039-req">*</span></div>
  <select name="<?= $ctxName ?>" class="cd039-select" required onchange="cd039_showProgInfo(this,'progInfo')">
    <option value="">— اختر <?= $isCourse ? 'الدورة' : 'الورشة' ?> —</option>
    <?php if($programs): foreach($programs as $p): ?>
      <option value="<?= (int)$p->id ?>"
              data-branch="<?= cd039_esc(($p->branch_name ?? '—').(($p->department_name ?? '') ? ' — '.$p->department_name : '')) ?>"
              data-dates="<?= cd039_esc(($p->start_date ?? '—').' → '.($p->end_date ?? '—')) ?>"
              data-trainer="<?= cd039_esc($p->responsible_person ?? '—') ?>">
        <?= cd039_esc($p->title) ?>
      </option>
    <?php endforeach; endif; ?>
  </select>
  <div class="cd039-prog-info" id="progInfo">
    <div class="cd039-ttl" style="font-size:11px;margin-bottom:6px">📋 معلومات البرنامج</div>
    <div class="cd039-pi-grid">
      <div class="cd039-pi-item"><label>🏢 الجهة المنفّذة</label><strong id="piBranch">—</strong></div>
      <div class="cd039-pi-item"><label>📅 التواريخ</label><strong id="piDates">—</strong></div>
      <div class="cd039-pi-item"><label>👤 المسؤول</label><strong id="piTrainer">—</strong></div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if(in_array($template['evaluation_mode'] ?? 'numeric', ['descriptive','mixed'], true) && !empty($gradingScales)): ?>
<div class="cd039-card cd039-gold-card">
  <div class="cd039-ttl gold">⭐ التقدير العام (يدوي)</div>
  <select name="final_grade_id" class="cd039-select" required>
    <option value="">— اختر التقدير العام —</option>
    <?php foreach($gradingScales as $grade): ?>
      <option value="<?= (int)$grade->id ?>"><?= cd039_esc($grade->scale_name.' — '.$grade->grade_label) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<?php endif; ?>

<!-- المعايير الديناميكية -->
<div class="cd039-stack">
<?php
$si = 0;
foreach($template['criteria'] ?? [] as $criterion_index => $criterion):
    $si++;
    $cid = (int)$criterion['id'];
    echo '<div class="cd039-sec" id="criterion-'.$cid.'">';
    echo '<div class="cd039-sec-h"><span class="cd039-sec-no">'.cd039_arabic_num($si).'</span><div class="cd039-sec-t"><div class="cd039-sec-ttl">'.cd039_esc($criterion['criterion_name']).'</div>';
    if(!empty($criterion['criterion_description'])) echo '<div class="cd039-sec-sub">'.cd039_esc($criterion['criterion_description']).'</div>';
    echo '</div>';
    if(!empty($criterion['criterion_weight'])) echo '<span class="cd039-sec-w">الوزن '.cd039_arabic_num((float)$criterion['criterion_weight']).'٪</span>';
    echo '<span class="cd039-sec-score" id="crit-score-'.$cid.'">٠٫٠</span>';
    echo '</div><div class="cd039-sec-body">';
    foreach($criterion['sections'] ?? [] as $section_index => $section){
        echo cd039_render_section($section, $cid, $savedAnswers ?? [], $cd039_global_gradingScales, $si);
    }
    echo '</div></div>';
endforeach;
?>
</div>

<?php if(cd039_has_quantitative($template)): ?>
<div class="cd039-live"><div><div class="cd039-lbl">الدرجة المحسوبة آليًا</div><div class="cd039-val" id="totalScore">٠٫٠</div></div><div class="cd039-gr" id="totalGrade">—</div></div>
<?php endif; ?>

<!-- بطاقة المُقيِّم -->
<div class="cd039-ev">
  <div class="cd039-ev-h">👤 بيانات المُقيِّم</div>
  <div class="cd039-ev-b">
    <div class="cd039-ev-grid">
      <div class="cd039-ev-field"><label>اسم المُقيِّم <span class="cd039-req">*</span></label><input type="text" name="evaluator_name" value="<?= cd039_esc($evaluation->evaluator_name ?? '') ?>" placeholder="الاسم الكامل" required></div>
      <div class="cd039-ev-field"><label>صفة المُقيِّم</label><input type="text" name="evaluator_title" value="<?= cd039_esc($evaluation->evaluator_title ?? '') ?>" placeholder="مدير مباشر / مشرف"></div>
      <div class="cd039-ev-field"><label>تاريخ التقييم <span class="cd039-req">*</span></label><input type="date" name="evaluation_date" value="<?= cd039_esc($evaluation->evaluation_date ?? date('Y-m-d')) ?>" required></div>
      <div class="cd039-ev-field full"><label>ملاحظات المُقيِّم العامة</label><textarea name="evaluator_notes" rows="3" placeholder="ملاحظات شاملة عن أداء الموظف..."></textarea></div>
    </div>
  </div>
</div>

<div class="cd039-bar">
  <button type="reset" class="cd039-btn cd039-btn-l" onclick="cd039_reset()">↺ تفريغ</button>
  <button type="submit" class="cd039-btn cd039-btn-p">💾 حفظ وإرسال التقييم</button>
</div>
</form>

</div>
</div>

<style>
.cd039-wrap{background:#f5f8f6;min-height:100vh;direction:rtl;font-family:'Cairo',sans-serif}
.cd039-page{max-width:960px;margin:0 auto;padding:18px;color:#1a2e2a}
.cd039-page *{box-sizing:border-box}
.cd039-hdr{background:linear-gradient(135deg,#0c2823,#0d5e52);color:#fff;padding:16px 22px;border-radius:12px;margin-bottom:14px;position:relative}
.cd039-hdr::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:#c8943f}
.cd039-hdr h2{font-size:17px;font-weight:800;margin:0}
.cd039-hdr .cd039-sub{font-size:11px;opacity:.85;margin-top:3px}
.cd039-card{background:#fff;border:1px solid #e0e8e4;border-radius:10px;padding:14px;margin-bottom:12px}
.cd039-gold-card{border:2px solid #c8943f}
.cd039-info-card{background:linear-gradient(180deg,#fbfdfc,#f1f7f3)}
.cd039-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.cd039-info-item{display:flex;flex-direction:column;gap:2px}
.cd039-info-item span{font-size:10px;color:#6c757d;font-weight:600}
.cd039-info-item strong{font-size:13px;color:#0c2823}
.cd039-ttl{font-weight:800;color:#0c2823;font-size:13px;margin-bottom:10px;display:flex;align-items:center;gap:6px}
.cd039-ttl.gold{color:#c8943f}
.cd039-select{width:100%;padding:9px 11px;border:1px solid #e0e8e4;border-radius:6px;font-family:'Cairo';font-size:13px;background:#fff}
.cd039-select:focus{border-color:#c8943f;outline:none;box-shadow:0 0 0 .12rem rgba(200,148,63,.12)}
.cd039-prog-info{background:#fffbf3;border:1px solid #c8943f;border-radius:8px;padding:10px;margin-top:8px;opacity:0;transition:opacity .3s}
.cd039-prog-info.show{opacity:1}
.cd039-pi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.cd039-pi-item{background:#fff;padding:6px 10px;border-radius:5px;border:1px solid #e0e8e4}
.cd039-pi-item label{display:block;font-size:9px;color:#6c757d}
.cd039-pi-item strong{font-size:11px}
.cd039-stack{display:flex;flex-direction:column;gap:14px}
.cd039-sec{background:#fff;border:1px solid #e0e8e4;border-radius:12px;overflow:hidden;box-shadow:0 1px 2px rgba(12,40,35,.04);transition:box-shadow .25s}
.cd039-sec:hover{box-shadow:0 6px 22px rgba(12,40,35,.09)}
.cd039-sec-h{display:flex;align-items:center;gap:12px;padding:14px 18px;background:linear-gradient(180deg,#fbfdfc,#f1f7f3);border-bottom:1px solid #e0e8e4;position:relative}
.cd039-sec-h::before{content:'';position:absolute;right:0;top:0;bottom:0;width:4px;background:linear-gradient(180deg,#c8943f,#a87830)}
.cd039-sec-no{flex:0 0 auto;width:32px;height:32px;border-radius:50%;background:#0c2823;color:#c8943f;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;box-shadow:0 2px 6px rgba(12,40,35,.18)}
.cd039-sec-t{flex:1;min-width:0}
.cd039-sec-ttl{font-weight:800;color:#0c2823;font-size:14px;line-height:1.35}
.cd039-sec-sub{font-size:11px;color:#6c757d;margin-top:2px;font-weight:500}
.cd039-sec-w{flex:0 0 auto;background:#faefd9;color:#c8943f;padding:5px 13px;border-radius:999px;font-size:11px;font-weight:700;border:1px solid rgba(200,148,63,.28)}
.cd039-sec-score{flex:0 0 auto;background:#0c2823;color:#c8943f;padding:5px 13px;border-radius:999px;font-size:11px;font-weight:800;font-family:'Cairo'}
.cd039-sec-body{padding:4px 18px}
.cd039-field{padding:14px 0;border-bottom:1px dashed #e0e8e4}
.cd039-field:last-child{border-bottom:none}
.cd039-fh{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:9px}
.cd039-fn{font-size:13px;font-weight:700;color:#1a2e2a;line-height:1.5}
.cd039-fd{font-size:11px;color:#6c757d;margin-top:2px;font-weight:500}
.cd039-ft{flex:0 0 auto;font-size:10px;color:#5a6b62;background:#f1f5f3;border:1px solid #e0e8e4;padding:3px 9px;border-radius:6px;font-weight:600;white-space:nowrap;display:inline-flex;align-items:center;gap:5px}
.cd039-ft .dot{width:6px;height:6px;border-radius:50%;display:inline-block}
.cd039-perc{display:flex;align-items:center;gap:12px}
.cd039-perc .piw{position:relative;flex:0 0 auto}
.cd039-perc .piw input{width:78px;padding:8px 24px 8px 8px;border:1px solid #e0e8e4;border-radius:8px;text-align:center;font-size:15px;font-weight:800;font-family:'Cairo';color:#0c2823;background:#fff}
.cd039-perc .piw input:focus{border-color:#c8943f;outline:none;box-shadow:0 0 0 3px rgba(200,148,63,.15)}
.cd039-perc .piw .sign{position:absolute;left:8px;top:50%;transform:translateY(-50%);font-size:13px;color:#6c757d;font-weight:700}
.cd039-perc .ptr{flex:1;height:10px;background:#f5f8f6;border-radius:6px;overflow:hidden;border:1px solid #e0e8e4}
.cd039-perc .pfill{height:100%;background:linear-gradient(90deg,#c8943f,#a87830);border-radius:6px;transition:width .35s cubic-bezier(.4,0,.2,1)}
.cd039-scale{display:flex;justify-content:space-between;font-size:9px;color:#6c757d;margin-top:4px;padding:0 2px}
.cd039-num{display:flex;align-items:center;gap:10px}
.cd039-num input{width:88px;padding:8px;border:1px solid #e0e8e4;border-radius:8px;text-align:center;font-size:15px;font-weight:800;font-family:'Cairo';color:#0c2823;background:#fff}
.cd039-num input:focus{border-color:#c8943f;outline:none;box-shadow:0 0 0 3px rgba(200,148,63,.15)}
.cd039-num .nmax{font-size:12px;color:#6c757d;font-weight:600}
.cd039-grade select,.cd039-overall-inline select{width:100%;max-width:360px;padding:9px 14px 9px 32px;border:1px solid #e0e8e4;border-radius:8px;font-family:'Cairo';font-size:13px;font-weight:600;background:#fff;color:#1a2e2a;cursor:pointer;appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:left 14px center}
.cd039-grade select:focus,.cd039-overall-inline select:focus{border-color:#c8943f;outline:none;box-shadow:0 0 0 3px rgba(200,148,63,.15)}
.cd039-text textarea,.cd039-notes textarea{width:100%;padding:10px 12px;border:1px solid #e0e8e4;border-radius:8px;font-family:'Cairo';font-size:13px;line-height:1.65;resize:vertical;background:#fcfcfc;color:#1a2e2a}
.cd039-text textarea{min-height:74px}
.cd039-notes{margin-top:10px}
.cd039-notes textarea{min-height:48px;font-size:12px}
.cd039-notes label{display:block;font-size:10px;color:#6c757d;font-weight:600;margin-bottom:3px}
.cd039-text textarea:focus,.cd039-notes textarea:focus{border-color:#c8943f;outline:none;box-shadow:0 0 0 3px rgba(200,148,63,.15);background:#fff}
.cd039-bool{display:inline-flex;background:#f5f8f6;border:1px solid #e0e8e4;border-radius:10px;padding:4px;gap:4px}
.cd039-bopt{padding:8px 24px;border:none;border-radius:7px;background:transparent;font-family:'Cairo';font-size:13px;font-weight:700;color:#6c757d;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:6px}
.cd039-bopt:hover{color:#0c2823;background:rgba(255,255,255,.6)}
.cd039-bopt.sel-yes{background:#e6f4ea;color:#1b6b32;box-shadow:0 1px 3px rgba(27,107,50,.25)}
.cd039-bopt.sel-no{background:#fdecea;color:#c62828;box-shadow:0 1px 3px rgba(198,40,40,.22)}
.cd039-formula input{width:100%;padding:9px 12px;border:1px solid #e0e8e4;border-radius:8px;font-family:'Cairo';font-size:13px;background:#f8f9fa;color:#495057}
.cd039-hint{display:block;font-size:11px;color:#6c757d;margin-top:4px}
.cd039-overall-inline{background:linear-gradient(135deg,#fffbf3,#faf3e3);border:1.5px solid #c8943f;border-radius:12px;padding:16px;position:relative;overflow:hidden;box-shadow:0 2px 10px rgba(200,148,63,.1)}
.cd039-overall-inline::before{content:'';position:absolute;top:-20px;left:-20px;width:100px;height:100px;background:radial-gradient(circle,rgba(200,148,63,.18),transparent 70%);pointer-events:none}
.cd039-oh{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;position:relative}
.cd039-og{display:flex;align-items:center;gap:10px}
.cd039-oi{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#c8943f,#a87830);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;box-shadow:0 3px 8px rgba(200,148,63,.3)}
.cd039-ot{font-weight:800;color:#0c2823;font-size:14px}
.cd039-os{font-size:11px;color:#6c757d;margin-top:2px}
.cd039-overall-inline select{border:1px solid #c8943f;color:#0c2823;font-weight:700}
.cd039-live{background:#0c2823;color:#c8943f;border-radius:10px;padding:12px 18px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center}
.cd039-live .cd039-lbl{font-size:11px;opacity:.85}
.cd039-live .cd039-val{font-size:24px;font-weight:800}
.cd039-live .cd039-gr{font-size:12px;font-weight:700;padding:4px 12px;border-radius:999px;background:#c8943f;color:#0c2823}
.cd039-ev{border:2px solid #c8943f;border-radius:10px;overflow:hidden;margin-bottom:12px}
.cd039-ev-h{background:linear-gradient(90deg,#fbf3e2,#f6e8cf);padding:8px 14px;font-weight:700;color:#c8943f;font-size:12px;border-bottom:2px solid #c8943f}
.cd039-ev-b{padding:12px 14px}
.cd039-ev-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.cd039-ev-field label{display:block;font-size:10px;color:#6c757d;font-weight:600;margin-bottom:3px}
.cd039-ev-field input,.cd039-ev-field textarea{width:100%;padding:7px 9px;border:1px solid #e0e8e4;border-radius:5px;font-family:'Cairo';font-size:12px}
.cd039-ev-field input:focus,.cd039-ev-field textarea:focus{border-color:#c8943f;outline:none}
.cd039-ev-field.full{grid-column:1/-1}
.cd039-bar{position:sticky;bottom:0;background:rgba(245,248,246,.97);backdrop-filter:blur(6px);border-top:1px solid #e0e8e4;padding:12px 0;display:flex;justify-content:flex-end;gap:8px}
.cd039-btn{padding:10px 22px;border-radius:8px;font-size:13px;font-weight:700;font-family:'Cairo';cursor:pointer;border:none;transition:transform .15s}
.cd039-btn-p{background:#0c2823;color:#c8943f}
.cd039-btn-p:hover{transform:translateY(-1px);box-shadow:0 3px 10px rgba(12,40,35,.3)}
.cd039-btn-l{background:#e8e8e8;color:#6c757d}
.cd039-req{color:#c8943f;font-weight:800}
@media(max-width:768px){.cd039-info-grid,.cd039-pi-grid,.cd039-ev-grid{grid-template-columns:1fr}.cd039-perc{flex-wrap:wrap}.cd039-perc .ptr{flex-basis:100%}.cd039-grade select,.cd039-overall-inline select{max-width:100%}}
</style>

<script>
const evaluationTemplate = <?php echo json_encode($template, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>;
function cd039_arabic_num(n){return String(n).replace(/[0-9]/g,function(d){return '٠١٢٣٤٥٦٧٨٩'[+d];});}
function cd039_grade_text(s){if(s>=90)return 'ممتاز';if(s>=80)return 'جيد جدًا';if(s>=70)return 'جيد';if(s>=60)return 'مقبول';return s>0?'ضعيف':'—';}

function cd039_calc(){
  // aggregate per-criterion and total
  var critScores = {};
  evaluationTemplate.criteria.forEach(function(c){ critScores[c.id] = {sum:0, n:0, weight: parseFloat(c.criterion_weight)||0}; });
  var totalSum=0, totalN=0;
  document.querySelectorAll('.cd039-score').forEach(function(inp){
    var field = inp.closest('.cd039-field');
    if(!field) return;
    var sid = field.dataset.sectionId, cid = field.dataset.criterionId;
    var v = parseFloat(inp.value)||0;
    var max = parseFloat(inp.getAttribute('data-max'))||100;
    if(max>0){
      var norm = (v/max)*100;
      if(critScores[cid]){ critScores[cid].sum += norm; critScores[cid].n++; }
      totalSum += norm; totalN++;
    }
  });
  // per-criterion badge
  Object.keys(critScores).forEach(function(cid){
    var c = critScores[cid];
    var avg = c.n>0 ? Math.round(c.sum/c.n*10)/10 : 0;
    var el = document.getElementById('crit-score-'+cid);
    if(el) el.textContent = cd039_arabic_num(avg.toFixed(1));
  });
  // total
  var total = totalN>0 ? Math.round(totalSum/totalN*10)/10 : 0;
  var ts = document.getElementById('totalScore'); if(ts) ts.textContent = cd039_arabic_num(total.toFixed(1));
  var tg = document.getElementById('totalGrade'); if(tg) tg.textContent = cd039_grade_text(total);
}
function cd039_updateBar(input){
  var max = parseFloat(input.getAttribute('data-max'))||100;
  var v = Math.min(max, Math.max(0, parseFloat(input.value)||0));
  var pct = (v/max)*100;
  var ctrl = input.closest('.cd039-perc'); var fill = ctrl ? ctrl.querySelector('.pfill') : null;
  if(fill) fill.style.width = pct+'%';
  cd039_calc();
}
function cd039_bool(btn){
  var wrap = btn.parentElement;
  var opts = wrap.querySelectorAll('.cd039-bopt');
  opts.forEach(function(s){ s.classList.remove('sel-yes','sel-no'); });
  var isYes = btn.getAttribute('data-val')==='1';
  btn.classList.add(isYes ? 'sel-yes' : 'sel-no');
  var hidden = wrap.querySelector('input[type=hidden]');
  if(hidden){ hidden.value = isYes ? '1' : '0'; cd039_calc(); }
}
function cd039_showProgInfo(sel, infoId){
  var o = sel.options[sel.selectedIndex]; var box = document.getElementById(infoId);
  if(!o || !o.value){ if(box) box.classList.remove('show'); return; }
  if(box){ box.classList.add('show');
    document.getElementById('piBranch').textContent = o.dataset.branch || '—';
    document.getElementById('piDates').textContent = o.dataset.dates || '—';
    document.getElementById('piTrainer').textContent = o.dataset.trainer || '—';
  }
}
function cd039_reset(){
  document.querySelectorAll('.cd039-score').forEach(function(i){ i.value=''; });
  document.querySelectorAll('.pfill').forEach(function(f){ f.style.width='0%'; });
  document.querySelectorAll('.cd039-bopt').forEach(function(b){ b.classList.remove('sel-yes','sel-no'); });
  cd039_calc();
}
document.addEventListener('DOMContentLoaded', cd039_calc);
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
