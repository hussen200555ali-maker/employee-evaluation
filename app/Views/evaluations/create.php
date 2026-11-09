<?php
// المسار: app/Views/evaluations/create.php (CD-040 — Unified single-page entry interface)
// صفحة واحدة موحّدة لإنشاء وتعبئة التقييم بتصميم CD-038 المؤسسي
// تتكيّف ديناميكيًا مع القالب المختار وتبني حقول المعايير من /api/templates/:id/schema
// وضعان: «new» (إنشاء) و«edit» (تعديل مسودة موجودة عبر /evaluations/{id}/fill)

require_once APP_ROOT . '/app/Views/layouts/header.php';

/* وضع التشغيل: edit إن وُجد تقييم محمّل مسبقًا، وإلا new */
$cd040_mode = !empty($evaluation) ? 'edit' : 'new';
$cd040_templates = $templates ?? [];
$cd040_branches = $branches ?? [];
$cd040_evaluatorName = $evaluatorName ?? '';
$cd040_evaluatorTitle = $evaluatorTitle ?? '';

/* في وضع edit: بيانات التقييم المحمّلة */
$cd040_evaluation = $evaluation ?? null;
$cd040_saved_answers = $savedAnswers ?? [];
$cd040_grading_scales = $gradingScales ?? [];

/* تحويل savedAnswers إلى JSON للتعبة المسبقة (key=>{raw_value,notes}) */
$cd040_answers_json = [];
if (!empty($cd040_saved_answers)) {
    foreach ($cd040_saved_answers as $sid => $ans) {
        $cd040_answers_json[(int)$sid] = [
            'raw_value' => $ans->raw_value ?? '',
            'notes' => $ans->notes ?? '',
        ];
    }
}
$cd040_grading_json = [];
if (!empty($cd040_grading_scales)) {
    foreach ($cd040_grading_scales as $gs) {
        $cd040_grading_json[] = [
            'id' => (int)$gs->id,
            'scale_name' => $gs->scale_name ?? '',
            'grade_label' => $gs->grade_label ?? '',
            'numeric_value' => (float)($gs->numeric_value ?? 0),
        ];
    }
}
?>
<div class="cd040-wrap">
<div class="cd040-page">

<div class="cd040-hdr">
  <h2><?= $cd040_mode === 'edit' ? '✏️ تعديل استمارة التقييم' : '📝 بدء تقييم جديد' ?></h2>
  <div class="cd040-sub">واجهة موحّدة · تصميم مؤسسي ديناميكي حسب القالب</div>
</div>

<form id="unifiedEvalForm" autocomplete="off">
<?= csrf_field() ?>
<input type="hidden" name="evaluation_id" value="<?= (int)($cd040_evaluation->id ?? 0) ?>">
<input type="hidden" name="employee_id" id="employee_id" value="<?= (int)($cd040_evaluation->evaluated_employee_id ?? 0) ?>">
<input type="hidden" name="template_id" id="template_id_hidden" value="<?= (int)($cd040_evaluation->template_id ?? 0) ?>">

<!-- ====== القسم العلوي: الموظف + المُقيِّم ====== -->
<div class="cd040-topbar">
  <div class="cd040-tb-grid">
    <!-- الموظف -->
    <div class="cd040-tb-field">
      <label>👤 الموظف المُقيَّم <span class="cd040-req">*</span></label>
      <?php if ($cd040_mode === 'edit' && !empty($cd040_evaluation)): ?>
        <div class="cd040-locked">
          <strong><?= htmlspecialchars($cd040_evaluation->employee_name ?? '') ?></strong>
          <span><?= htmlspecialchars($cd040_evaluation->employee_code ?? '') ?> · <?= htmlspecialchars($cd040_evaluation->position_name ?? '') ?></span>
        </div>
      <?php else: ?>
        <div class="cd040-search-wrap">
          <input type="text" id="empSearch" placeholder="ابحث بالاسم أو الرقم الوظيفي..." oninput="cd040_searchEmp(this.value)">
          <span class="cd040-sicon">🔍</span>
          <div class="cd040-results" id="empResults"></div>
        </div>
        <div class="cd040-sel" id="empSelected">
          <div><span class="nm" id="empSelName"></span> <span class="code" id="empSelCode"></span></div>
          <a href="#" onclick="cd040_clearEmp();return false">✕ تغيير</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- المُقيِّم -->
    <div class="cd040-tb-field">
      <label>👔 المُقيِّم <span class="cd040-req">*</span></label>
      <div class="cd040-search-wrap">
        <input type="text" id="evalSearch" placeholder="ابحث بالاسم أو الرقم الوظيفي..." oninput="cd040_searchEval(this.value)"
               value="<?= htmlspecialchars($cd040_evaluatorName) ?>">
        <span class="cd040-sicon">🔍</span>
        <div class="cd040-results" id="evalResults"></div>
      </div>
      <input type="hidden" name="evaluator_employee_id" id="evaluator_employee_id" value="">
      <div class="cd040-sel <?= $cd040_evaluatorName ? 'show' : '' ?>" id="evalSelected">
        <div><span class="nm" id="evalSelName"><?= htmlspecialchars($cd040_evaluatorName) ?></span></div>
        <a href="#" onclick="cd040_clearEval();return false">✕ تغيير</a>
      </div>
    </div>
  </div>
</div>

<!-- ====== القالب + التاريخ ====== -->
<div class="cd040-card">
  <div class="cd040-ttl">📋 إعدادات التقييم</div>
  <div class="cd040-cfg-grid">
    <div class="cd040-cfg-field">
      <label>قالب التقييم <span class="cd040-req">*</span></label>
      <select id="templateSelect" name="template_id_select" <?= $cd040_mode === 'edit' ? 'disabled' : '' ?> onchange="cd040_onTemplate(this.value)">
        <option value="">— اختر القالب —</option>
        <?php foreach ($cd040_templates as $tpl): ?>
          <option value="<?= (int)$tpl->id ?>"
            data-mode="<?= htmlspecialchars($tpl->evaluation_mode ?? '') ?>"
            data-target="<?= htmlspecialchars($tpl->target_type ?? '') ?>"
            <?= ($cd040_evaluation->template_id ?? 0) == $tpl->id ? 'selected' : '' ?>>
            <?= htmlspecialchars($tpl->template_name) ?>
            <?php if (($tpl->evaluation_mode ?? '') !== 'numeric'): ?> · <?= htmlspecialchars($tpl->evaluation_mode) ?><?php endif; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="cd040-cfg-field">
      <label>تاريخ التقييم <span class="cd040-req">*</span></label>
      <input type="date" id="evalDate" name="evaluation_date" value="<?= htmlspecialchars($cd040_evaluation->evaluation_date ?? date('Y-m-d')) ?>" required>
      <small class="cd040-hijri" id="hijriDisplay"></small>
    </div>
  </div>
</div>

<!-- ====== منطقة البرنامج (دورة/ورشة) — تُملأ ديناميكيًا ====== -->
<div class="cd040-card cd040-gold-card" id="programCard" style="display:none">
  <div class="cd040-ttl gold"><span id="programLabel">📚 البرنامج</span> — إلزامي <span class="cd040-req">*</span></div>
  <select id="programSelect" name="context_id" class="cd040-select" onchange="cd040_showProgInfo(this)">
    <option value="">— اختر —</option>
  </select>
  <input type="hidden" name="context_type" id="contextType" value="">
  <div class="cd040-prog-info" id="progInfo">
    <div class="cd040-ttl" style="font-size:11px;margin-bottom:6px">📋 معلومات البرنامج</div>
    <div class="cd040-pi-grid">
      <div class="cd040-pi-item"><label>🏢 الجهة</label><strong id="piBranch">—</strong></div>
      <div class="cd040-pi-item"><label>📅 التواريخ</label><strong id="piDates">—</strong></div>
      <div class="cd040-pi-item"><label>👤 المسؤول</label><strong id="piTrainer">—</strong></div>
    </div>
  </div>
</div>

<!-- ====== التقدير العام اليدوي (للوضع الوصفي/المختلط) ====== -->
<div class="cd040-card cd040-gold-card" id="manualGradeCard" style="display:none">
  <div class="cd040-ttl gold">⭐ التقدير العام (يدوي)</div>
  <select name="final_grade_id" class="cd040-select" id="finalGradeSelect">
    <option value="">— اختر التقدير العام —</option>
    <?php foreach ($cd040_grading_scales as $gs): ?>
      <option value="<?= (int)$gs->id ?>"><?= htmlspecialchars(($gs->scale_name ?? '').' — '.($gs->grade_label ?? '')) ?></option>
    <?php endforeach; ?>
  </select>
</div>

<!-- ====== المعايير الديناميكية ====== -->
<div class="cd040-stack" id="criteriaStack">
  <div class="cd040-placeholder" id="criteriaPlaceholder">اختر قالبًا لعرض حقول التقييم…</div>
</div>

<!-- ====== الدرجة الحيّة ====== -->
<div class="cd040-live" id="liveScoreBar" style="display:none">
  <div><div class="cd040-lbl">الدرجة المحسوبة آليًا</div><div class="cd040-val" id="liveScore">٠٫٠</div></div>
  <div class="cd040-gr" id="liveGrade">—</div>
</div>

<!-- ====== بطاقة المُقيِّم التفصيلية ====== -->
<div class="cd040-ev">
  <div class="cd040-ev-h">👤 بيانات المُقيِّم</div>
  <div class="cd040-ev-b">
    <div class="cd040-ev-grid">
      <div class="cd040-ev-field"><label>اسم المُقيِّم</label><input type="text" name="evaluator_name" id="evalNameInput" value="<?= htmlspecialchars($cd040_evaluatorName ?? ($cd040_evaluation->evaluator_name ?? '')) ?>" placeholder="الاسم الكامل"></div>
      <div class="cd040-ev-field"><label>صفة المُقيِّم</label><input type="text" name="evaluator_title" id="evalTitleInput" value="<?= htmlspecialchars($cd040_evaluatorTitle ?? ($cd040_evaluation->evaluator_title ?? '')) ?>" placeholder="مدير مباشر / مشرف"></div>
      <div class="cd040-ev-field full"><label>ملاحظات عامة</label><textarea name="evaluator_notes" rows="2" placeholder="ملاحظات شاملة..."></textarea></div>
    </div>
  </div>
</div>

<!-- ====== شريط الحفظ ====== -->
<div class="cd040-bar">
  <button type="button" class="cd040-btn cd040-btn-l" onclick="cd040_reset()">↺ تفريغ</button>
  <button type="submit" class="cd040-btn cd040-btn-p" id="saveBtn">💾 حفظ التقييم</button>
</div>
</form>

</div>
</div>

<style>
.cd040-wrap{background:#f5f8f6;min-height:100vh;direction:rtl;font-family:'Cairo',sans-serif}
.cd040-page{max-width:960px;margin:0 auto;padding:18px;color:#1a2e2a}
.cd040-page *{box-sizing:border-box}
.cd040-hdr{background:linear-gradient(135deg,#0c2823,#0d5e52);color:#fff;padding:16px 22px;border-radius:12px;margin-bottom:14px;position:relative}
.cd040-hdr::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:#c8943f}
.cd040-hdr h2{font-size:17px;font-weight:800;margin:0}
.cd040-hdr .cd040-sub{font-size:11px;opacity:.85;margin-top:3px}
.cd040-topbar{background:#fff;border:1px solid #e0e8e4;border-radius:10px;padding:14px;margin-bottom:12px}
.cd040-tb-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.cd040-tb-field label{display:block;font-size:11px;color:#6c757d;font-weight:700;margin-bottom:5px}
.cd040-locked{background:#f1f7f3;border:1px solid #e0e8e4;border-radius:8px;padding:10px 12px}
.cd040-locked strong{display:block;font-size:13px;color:#0c2823}
.cd040-locked span{font-size:11px;color:#6c757d}
.cd040-search-wrap{position:relative}
.cd040-search-wrap input{width:100%;padding:9px 32px 9px 11px;border:1px solid #e0e8e4;border-radius:6px;font-family:'Cairo';font-size:13px;background:#fff}
.cd040-search-wrap input:focus{border-color:#c8943f;outline:none;box-shadow:0 0 0 .12rem rgba(200,148,63,.12)}
.cd040-sicon{position:absolute;right:8px;top:50%;transform:translateY(-50%);font-size:14px;opacity:.5;pointer-events:none}
.cd040-results{position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e0e8e4;border-radius:0 0 8px 8px;box-shadow:0 4px 14px rgba(0,0,0,.1);z-index:100;max-height:240px;overflow-y:auto;display:none}
.cd040-results.show{display:block}
.cd040-results .item{padding:8px 12px;border-bottom:1px solid #e0e8e4;cursor:pointer;font-size:12px}
.cd040-results .item:hover{background:#faefd9}
.cd040-results .item .nm{font-weight:700;color:#0c2823}
.cd040-results .item .meta{font-size:10px;color:#6c757d;margin-top:2px}
.cd040-results .add{padding:8px 12px;background:#fffbf3;border-top:2px solid #c8943f;text-align:center;color:#c8943f;font-weight:700;cursor:pointer;font-size:12px}
.cd040-sel{margin-top:6px;padding:8px 12px;background:#faefd9;border:1px solid #c8943f;border-radius:6px;font-size:12px;display:none}
.cd040-sel.show{display:flex;align-items:center;justify-content:space-between;gap:8px}
.cd040-sel .nm{font-weight:700;color:#0c2823}
.cd040-sel .code{color:#6c757d;font-size:11px}
.cd040-sel a{font-size:11px;color:#c85b54;text-decoration:none;font-weight:700}
.cd040-card{background:#fff;border:1px solid #e0e8e4;border-radius:10px;padding:14px;margin-bottom:12px}
.cd040-gold-card{border:2px solid #c8943f}
.cd040-ttl{font-weight:800;color:#0c2823;font-size:13px;margin-bottom:10px}
.cd040-ttl.gold{color:#c8943f}
.cd040-cfg-grid{display:grid;grid-template-columns:2fr 1fr;gap:12px}
.cd040-cfg-field label{display:block;font-size:11px;color:#6c757d;font-weight:700;margin-bottom:4px}
.cd040-cfg-field select,.cd040-cfg-field input,.cd040-select{width:100%;padding:9px 11px;border:1px solid #e0e8e4;border-radius:6px;font-family:'Cairo';font-size:13px;background:#fff}
.cd040-cfg-field select:focus,.cd040-cfg-field input:focus,.cd040-select:focus{border-color:#c8943f;outline:none;box-shadow:0 0 0 .12rem rgba(200,148,63,.12)}
.cd040-hijri{display:block;font-size:10px;color:#1b6b32;font-weight:600;margin-top:3px}
.cd040-prog-info{background:#fffbf3;border:1px solid #c8943f;border-radius:8px;padding:10px;margin-top:8px;opacity:0;transition:opacity .3s}
.cd040-prog-info.show{opacity:1}
.cd040-pi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.cd040-pi-item{background:#fff;padding:6px 10px;border-radius:5px;border:1px solid #e0e8e4}
.cd040-pi-item label{display:block;font-size:9px;color:#6c757d}
.cd040-pi-item strong{font-size:11px}
.cd040-stack{display:flex;flex-direction:column;gap:14px;min-height:80px}
.cd040-placeholder{text-align:center;color:#6c757d;padding:40px;font-size:13px}
.cd040-sec{background:#fff;border:1px solid #e0e8e4;border-radius:12px;overflow:hidden;box-shadow:0 1px 2px rgba(12,40,35,.04);transition:box-shadow .25s}
.cd040-sec:hover{box-shadow:0 6px 22px rgba(12,40,35,.09)}
.cd040-sec-h{display:flex;align-items:center;gap:12px;padding:14px 18px;background:linear-gradient(180deg,#fbfdfc,#f1f7f3);border-bottom:1px solid #e0e8e4;position:relative}
.cd040-sec-h::before{content:'';position:absolute;right:0;top:0;bottom:0;width:4px;background:linear-gradient(180deg,#c8943f,#a87830)}
.cd040-sec-no{flex:0 0 auto;width:32px;height:32px;border-radius:50%;background:#0c2823;color:#c8943f;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px}
.cd040-sec-t{flex:1;min-width:0}
.cd040-sec-ttl{font-weight:800;color:#0c2823;font-size:14px}
.cd040-sec-sub{font-size:11px;color:#6c757d;margin-top:2px}
.cd040-sec-w{flex:0 0 auto;background:#faefd9;color:#c8943f;padding:5px 13px;border-radius:999px;font-size:11px;font-weight:700;border:1px solid rgba(200,148,63,.28)}
.cd040-sec-body{padding:4px 18px}
.cd040-field{padding:14px 0;border-bottom:1px dashed #e0e8e4}
.cd040-field:last-child{border-bottom:none}
.cd040-fh{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:9px}
.cd040-fn{font-size:13px;font-weight:700;color:#1a2e2a;line-height:1.5}
.cd040-fd{font-size:11px;color:#6c757d;margin-top:2px}
.cd040-ft{flex:0 0 auto;font-size:10px;color:#5a6b62;background:#f1f5f3;border:1px solid #e0e8e4;padding:3px 9px;border-radius:6px;font-weight:600;white-space:nowrap;display:inline-flex;align-items:center;gap:5px}
.cd040-ft .dot{width:6px;height:6px;border-radius:50%}
.cd040-perc{display:flex;align-items:center;gap:12px}
.cd040-perc .piw{position:relative;flex:0 0 auto}
.cd040-perc .piw input{width:78px;padding:8px 24px 8px 8px;border:1px solid #e0e8e4;border-radius:8px;text-align:center;font-size:15px;font-weight:800;font-family:'Cairo';color:#0c2823;background:#fff}
.cd040-perc .piw input:focus{border-color:#c8943f;outline:none;box-shadow:0 0 0 3px rgba(200,148,63,.15)}
.cd040-perc .piw .sign{position:absolute;left:8px;top:50%;transform:translateY(-50%);font-size:13px;color:#6c757d;font-weight:700}
.cd040-perc .ptr{flex:1;height:10px;background:#f5f8f6;border-radius:6px;overflow:hidden;border:1px solid #e0e8e4}
.cd040-perc .pfill{height:100%;background:linear-gradient(90deg,#c8943f,#a87830);border-radius:6px;transition:width .35s}
.cd040-scale{display:flex;justify-content:space-between;font-size:9px;color:#6c757d;margin-top:4px;padding:0 2px}
.cd040-num{display:flex;align-items:center;gap:10px}
.cd040-num input{width:88px;padding:8px;border:1px solid #e0e8e4;border-radius:8px;text-align:center;font-size:15px;font-weight:800;font-family:'Cairo';color:#0c2823;background:#fff}
.cd040-num input:focus{border-color:#c8943f;outline:none;box-shadow:0 0 0 3px rgba(200,148,63,.15)}
.cd040-num .nmax{font-size:12px;color:#6c757d;font-weight:600}
.cd040-grade select,.cd040-overall-inline select{width:100%;max-width:360px;padding:9px 14px 9px 32px;border:1px solid #e0e8e4;border-radius:8px;font-family:'Cairo';font-size:13px;font-weight:600;background:#fff;color:#1a2e2a;cursor:pointer;appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:left 14px center}
.cd040-grade select:focus,.cd040-overall-inline select:focus{border-color:#c8943f;outline:none;box-shadow:0 0 0 3px rgba(200,148,63,.15)}
.cd040-text textarea,.cd040-notes textarea{width:100%;padding:10px 12px;border:1px solid #e0e8e4;border-radius:8px;font-family:'Cairo';font-size:13px;line-height:1.65;resize:vertical;background:#fcfcfc;color:#1a2e2a}
.cd040-text textarea{min-height:74px}
.cd040-notes{margin-top:10px}
.cd040-notes textarea{min-height:48px;font-size:12px}
.cd040-notes label{display:block;font-size:10px;color:#6c757d;font-weight:600;margin-bottom:3px}
.cd040-text textarea:focus,.cd040-notes textarea:focus{border-color:#c8943f;outline:none;box-shadow:0 0 0 3px rgba(200,148,63,.15);background:#fff}
.cd040-bool{display:inline-flex;background:#f5f8f6;border:1px solid #e0e8e4;border-radius:10px;padding:4px;gap:4px}
.cd040-bopt{padding:8px 24px;border:none;border-radius:7px;background:transparent;font-family:'Cairo';font-size:13px;font-weight:700;color:#6c757d;cursor:pointer;transition:all .2s}
.cd040-bopt:hover{color:#0c2823;background:rgba(255,255,255,.6)}
.cd040-bopt.sel-yes{background:#e6f4ea;color:#1b6b32;box-shadow:0 1px 3px rgba(27,107,50,.25)}
.cd040-bopt.sel-no{background:#fdecea;color:#c62828;box-shadow:0 1px 3px rgba(198,40,40,.22)}
.cd040-overall-inline{background:linear-gradient(135deg,#fffbf3,#faf3e3);border:1.5px solid #c8943f;border-radius:12px;padding:16px;position:relative;overflow:hidden}
.cd040-overall-inline::before{content:'';position:absolute;top:-20px;left:-20px;width:100px;height:100px;background:radial-gradient(circle,rgba(200,148,63,.18),transparent 70%);pointer-events:none}
.cd040-oh{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;position:relative}
.cd040-og{display:flex;align-items:center;gap:10px}
.cd040-oi{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#c8943f,#a87830);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800}
.cd040-ot{font-weight:800;color:#0c2823;font-size:14px}
.cd040-os{font-size:11px;color:#6c757d;margin-top:2px}
.cd040-overall-inline select{border:1px solid #c8943f;color:#0c2823;font-weight:700}
.cd040-live{background:#0c2823;color:#c8943f;border-radius:10px;padding:12px 18px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center}
.cd040-live .cd040-lbl{font-size:11px;opacity:.85}
.cd040-live .cd040-val{font-size:24px;font-weight:800}
.cd040-live .cd040-gr{font-size:12px;font-weight:700;padding:4px 12px;border-radius:999px;background:#c8943f;color:#0c2823}
.cd040-ev{border:2px solid #c8943f;border-radius:10px;overflow:hidden;margin-bottom:12px}
.cd040-ev-h{background:linear-gradient(90deg,#fbf3e2,#f6e8cf);padding:8px 14px;font-weight:700;color:#c8943f;font-size:12px;border-bottom:2px solid #c8943f}
.cd040-ev-b{padding:12px 14px}
.cd040-ev-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.cd040-ev-field label{display:block;font-size:10px;color:#6c757d;font-weight:600;margin-bottom:3px}
.cd040-ev-field input,.cd040-ev-field textarea{width:100%;padding:7px 9px;border:1px solid #e0e8e4;border-radius:5px;font-family:'Cairo';font-size:12px}
.cd040-ev-field input:focus,.cd040-ev-field textarea:focus{border-color:#c8943f;outline:none}
.cd040-ev-field.full{grid-column:1/-1}
.cd040-bar{position:sticky;bottom:0;background:rgba(245,248,246,.97);backdrop-filter:blur(6px);border-top:1px solid #e0e8e4;padding:12px 0;display:flex;justify-content:flex-end;gap:8px}
.cd040-btn{padding:10px 22px;border-radius:8px;font-size:13px;font-weight:700;font-family:'Cairo';cursor:pointer;border:none;transition:transform .15s}
.cd040-btn-p{background:#0c2823;color:#c8943f}
.cd040-btn-p:hover{transform:translateY(-1px);box-shadow:0 3px 10px rgba(12,40,35,.3)}
.cd040-btn-p:disabled{opacity:.6;cursor:not-allowed;transform:none}
.cd040-btn-l{background:#e8e8e8;color:#6c757d}
.cd040-req{color:#c8943f;font-weight:800}
.cd040-toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);padding:12px 22px;border-radius:10px;font-weight:700;font-size:13px;box-shadow:0 4px 20px rgba(0,0,0,.2);z-index:9999;opacity:0;transition:opacity .3s;pointer-events:none}
.cd040-toast.show{opacity:1}
@media(max-width:768px){.cd040-tb-grid,.cd040-cfg-grid,.cd040-pi-grid,.cd040-ev-grid{grid-template-columns:1fr}.cd040-perc{flex-wrap:wrap}.cd040-perc .ptr{flex-basis:100%}.cd040-grade select,.cd040-overall-inline select{max-width:100%}}
</style>

<script>
const APP_URL = "<?= APP_URL ?>";
const CD040_MODE = "<?= $cd040_mode ?>";
const CD040_SAVED_ANSWERS = <?= json_encode($cd040_answers_json) ?>;
const CD040_GRADING_SCALES = <?= json_encode($cd040_grading_json) ?>;
const CD040_EVAL_TEMPLATE_ID = <?= (int)($cd040_evaluation->template_id ?? 0) ?>;
let cd040_currentSchema = null;
let cd040_currentTargetType = '';

function cd040_anabic(n){return String(n).replace(/[0-9]/g,function(d){return '٠١٢٣٤٥٦٧٨٩'[+d];});}
function cd040_grade_text(s){if(s>=90)return 'ممتاز';if(s>=80)return 'جيد جدًا';if(s>=70)return 'جيد';if(s>=60)return 'مقبول';return s>0?'ضعيف':'—';}
function cd040_esc(s){return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

/* ===== type metadata ===== */
const CD040_TYPE = {
  percentage:{dot:'#1b6b32',label:'نسبة مئوية'},
  numeric:{dot:'#1565c0',label:'رقمي'},
  grade:{dot:'#c8943f',label:'تقدير'},
  textarea:{dot:'#7b1fa2',label:'نصي'},
  boolean:{dot:'#c62828',label:'نعم / لا'},
  overall_grade:{dot:'#c8943f',label:'تقدير عام'},
  formula:{dot:'#dc3545',label:'صيغة'},
  auto:{dot:'#0dcaf0',label:'تلقائي'},
  helper:{dot:'#6c757d',label:'مساعد'}
};
function cd040_badge(t){var m=CD040_TYPE[t]||CD040_TYPE.numeric;return '<span class="cd040-ft"><span class="dot" style="background:'+m.dot+'"></span>'+m.label+'</span>';}

/* ===== employee/evaluator search ===== */
let cd040_searchTimer;
function cd040_searchEmp(q){
  q=q.trim();
  var box=document.getElementById('empResults');
  if(q.length<2){box.classList.remove('show');box.innerHTML='';return;}
  clearTimeout(cd040_searchTimer);
  cd040_searchTimer=setTimeout(function(){
    fetch(APP_URL+'/api/employees/search?q='+encodeURIComponent(q),{headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.json();})
      .then(function(list){
        if(!list||list.length===0){
          box.innerHTML='<div class="item" style="text-align:center;color:#6c757d">لا توجد نتائج</div>';
          box.classList.add('show');return;
        }
        box.innerHTML=list.map(function(e){
          return '<div class="item" onclick="cd040_pickEmp('+e.id+',\''+cd040_esc(e.full_name).replace(/'/g,"\\'")+'\',\''+cd040_esc(e.emp_code||'').replace(/'/g,"\\'")+'\')"><div class="nm">'+cd040_esc(e.full_name)+' <span style="font-size:10px;color:#6c757d">('+cd040_esc(e.emp_code||'')+')</span></div><div class="meta">'+cd040_esc(e.branch_name||'')+' / '+cd040_esc(e.department_name||'')+' — '+cd040_esc(e.position_title||e.position_name||'')+'</div></div>';
        }).join('');
        box._list=list; box.classList.add('show');
      }).catch(function(){box.classList.remove('show');});
  },250);
}
function cd040_pickEmp(id,name,code){
  document.getElementById('empSearch').value='';
  document.getElementById('employee_id').value=id;
  document.getElementById('empSelName').textContent=name;
  document.getElementById('empSelCode').textContent='('+code+')';
  document.getElementById('empSelected').classList.add('show');
  document.getElementById('empResults').classList.remove('show');
}
function cd040_clearEmp(){
  document.getElementById('empSearch').value='';
  document.getElementById('employee_id').value='';
  document.getElementById('empSelected').classList.remove('show');
  document.getElementById('empResults').classList.remove('show');
}
function cd040_searchEval(q){
  q=q.trim(); var box=document.getElementById('evalResults');
  if(q.length<2){box.classList.remove('show');box.innerHTML='';return;}
  clearTimeout(cd040_searchTimer);
  cd040_searchTimer=setTimeout(function(){
    fetch(APP_URL+'/api/employees/search?q='+encodeURIComponent(q),{headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.json();})
      .then(function(list){
        if(!list||list.length===0){box.innerHTML='<div class="item" style="text-align:center;color:#6c757d">لا توجد نتائج</div>';box.classList.add('show');return;}
        box.innerHTML=list.map(function(e){
          return '<div class="item" onclick="cd040_pickEval('+e.id+',\''+cd040_esc(e.full_name).replace(/'/g,"\\'")+'\')"><div class="nm">'+cd040_esc(e.full_name)+'</div><div class="meta">'+cd040_esc(e.position_title||e.position_name||'')+'</div></div>';
        }).join('');
        box.classList.add('show');
      }).catch(function(){box.classList.remove('show');});
  },250);
}
function cd040_pickEval(id,name){
  document.getElementById('evalSearch').value=name;
  document.getElementById('evaluator_employee_id').value=id;
  document.getElementById('evalSelName').textContent=name;
  document.getElementById('evalSelected').classList.add('show');
  document.getElementById('evalNameInput').value=name;
  document.getElementById('evalResults').classList.remove('show');
}
function cd040_clearEval(){
  document.getElementById('evalSearch').value='';
  document.getElementById('evaluator_employee_id').value='';
  document.getElementById('evalSelected').classList.remove('show');
  document.getElementById('evalResults').classList.remove('show');
}
document.addEventListener('click',function(e){
  if(!e.target.closest('.cd040-search-wrap')){
    document.querySelectorAll('.cd040-results').forEach(function(b){b.classList.remove('show');});
  }
});

/* ===== template schema fetch + criteria rendering ===== */
function cd040_onTemplate(id){
  document.getElementById('template_id_hidden').value=id;
  if(!id){document.getElementById('criteriaStack').innerHTML='<div class="cd040-placeholder">اختر قالبًا لعرض حقول التقييم…</div>';return;}
  // detect target_type from the option
  var sel=document.getElementById('templateSelect');
  var opt=sel.options[sel.selectedIndex];
  var mode=opt?opt.getAttribute('data-mode'):'numeric';
  var target=opt?opt.getAttribute('data-target'):'employee';
  cd040_currentTargetType=target;
  // show program picker if course/workshop
  if(target==='employee_in_course'||target==='course'){cd040_loadPrograms('course');}
  else if(target==='employee_in_workshop'||target==='workshop'){cd040_loadPrograms('workshop');}
  else{document.getElementById('programCard').style.display='none';}
  // show manual grade for descriptive/mixed
  document.getElementById('manualGradeCard').style.display=(mode==='descriptive'||mode==='mixed')?'block':'none';
  // fetch schema
  fetch(APP_URL+'/api/templates/'+id+'/schema').then(function(r){return r.json();}).then(function(sch){
    cd040_currentSchema=sch;
    cd040_renderCriteria(sch);
  }).catch(function(err){
    document.getElementById('criteriaStack').innerHTML='<div class="cd040-placeholder" style="color:#c62828">تعذّر تحميل بنية القالب: '+cd040_esc(err.message)+'</div>';
  });
}

function cd040_renderCriteria(sch){
  var stack=document.getElementById('criteriaStack');
  if(!sch||!sch.criteria||sch.criteria.length===0){
    stack.innerHTML='<div class="cd040-placeholder">لا توجد معايير في هذا القالب.</div>';return;
  }
  var hasQuant=false;
  var html='';
  var si=0;
  sch.criteria.forEach(function(c){
    si++;
    var body='';
    c.sections.forEach(function(sec){
      if(sec.type==='percentage'||sec.type==='numeric') hasQuant=true;
      body+=cd040_renderSection(sec,c.id);
    });
    html+='<div class="cd040-sec"><div class="cd040-sec-h"><span class="cd040-sec-no">'+cd040_arabic(si)+'</span><div class="cd040-sec-t"><div class="cd040-sec-ttl">'+cd040_esc(c.name)+'</div></div><span class="cd040-sec-w">الوزن '+cd040_arabic(c.weight)+'٪</span></div><div class="cd040-sec-body">'+body+'</div></div>';
  });
  stack.innerHTML=html;
  document.getElementById('liveScoreBar').style.display=hasQuant?'flex':'none';
  // pre-fill saved answers if edit mode
  if(CD040_MODE==='edit'){cd040_prefillAnswers();}
  cd040_calc();
}

function cd040_renderSection(sec,cid){
  var sid=sec.id, type=sec.type||'numeric', mv=sec.max_value||100, mn=sec.min_value||0;
  var name='results['+cid+']['+sid+']';
  var desc=sec.name;
  var html='<div class="cd040-field" data-section-id="'+sid+'" data-criterion-id="'+cid+'" data-type="'+cd040_esc(type)+'">';
  html+='<div class="cd040-fh"><div><div class="cd040-fn">'+cd040_esc(desc)+'</div></div>'+cd040_badge(type)+'</div>';
  switch(type){
    case 'percentage':
      html+='<div class="cd040-perc"><div class="piw"><input type="number" class="cd040-score" data-max="'+mv+'" name="'+name+'[score]" min="0" max="'+mv+'" step="1" placeholder="أدخل النسبة" oninput="cd040_updateBar(this);cd040_calc()"><span class="sign">٪</span></div><div class="ptr"><div class="pfill" style="width:0%"></div></div></div><div class="cd040-scale"><span>٠</span><span>'+cd040_arabic(Math.round(mv/2))+'</span><span>'+cd040_arabic(mv)+'</span></div>';
      break;
    case 'numeric':
      html+='<div class="cd040-num"><input type="number" class="cd040-score" data-max="'+mv+'" name="'+name+'[score]" min="'+mn+'" max="'+mv+'" step="1" placeholder="أدخل الدرجة" oninput="cd040_calc()"><span class="nmax">من '+cd040_arabic(mv)+'</span></div>';
      break;
    case 'grade':
      var opts=(sec.options||[]).map(function(o){var v=o.value!==undefined?o.value:o;var l=o.label!==undefined?o.label:o;return '<option value="'+cd040_esc(v)+'">'+cd040_esc(l)+'</option>';}).join('');
      html+='<div class="cd040-grade"><select name="'+name+'[score]"><option value="">— اختر التقدير —</option>'+opts+'</select></div>';
      break;
    case 'boolean':
      html+='<div class="cd040-bool"><button type="button" class="cd040-bopt" data-val="1" onclick="cd040_bool(this)">✓ نعم</button><button type="button" class="cd040-bopt" data-val="0" onclick="cd040_bool(this)">✕ لا</button><input type="hidden" class="cd040-score" name="'+name+'[score]" value=""></div>';
      break;
    case 'textarea':
      var to=sec.options||{}; var mc=to.max_chars||1000;
      html+='<div class="cd040-text"><textarea name="'+name+'[text]" rows="4" maxlength="'+mc+'" placeholder="اكتب... (حد أقصى '+cd040_arabic(mc)+' حرف)"></textarea></div>';
      break;
    case 'overall_grade':
      var scale=sec.scale_name||'السلم الخماسي';
      var grades=sec.grades||[];
      var opts=grades.map(function(g){return '<option value="'+cd040_esc(g.label)+'">'+cd040_esc(g.label)+' ('+g.value+')</option>';}).join('');
      html+='<div class="cd040-overall-inline"><div class="cd040-oh"><div class="cd040-og"><div class="cd040-oi">★</div><div><div class="cd040-ot">التقدير العام</div><div class="cd040-os">سلم: '+cd040_esc(scale)+'</div></div></div>'+cd040_badge('overall_grade')+'</div><select name="'+name+'[grade_label]"><option value="">— اختر التقدير —</option>'+opts+'</select></div>';
      break;
    default:
      html+='<div class="cd040-text"><input type="text" name="'+name+'[score]" placeholder="أدخل القيمة"></div>';
  }
  // notes (except textarea/overall_grade)
  if(type!=='textarea'&&type!=='overall_grade'){
    html+='<div class="cd040-notes"><label>📝 ملاحظات</label><textarea name="'+name+'[notes]" rows="2" placeholder="ملاحظاتك..."></textarea></div>';
  }
  html+='</div>';
  return html;
}

function cd040_arabic(n){return cd040_arabic_num(n);}

/* ===== pre-fill saved answers (edit mode) ===== */
function cd040_prefillAnswers(){
  if(!CD040_SAVED_ANSWERS||Object.keys(CD040_SAVED_ANSWERS).length===0)return;
  document.querySelectorAll('.cd040-field').forEach(function(field){
    var sid=field.getAttribute('data-section-id');
    var type=field.getAttribute('data-type');
    var ans=CD040_SAVED_ANSWERS[sid]; if(!ans)return;
    var raw=ans.raw_value||''; var notes=ans.notes||'';
    if(type==='percentage'){
      var inp=field.querySelector('input[type=number]'); if(inp){inp.value=raw; cd040_updateBar(inp);}
    }else if(type==='numeric'){
      var inp=field.querySelector('input[type=number]'); if(inp)inp.value=raw;
    }else if(type==='grade'){
      var sel=field.querySelector('select'); if(sel)sel.value=raw;
    }else if(type==='textarea'){
      var ta=field.querySelector('textarea'); if(ta)ta.value=raw;
    }else if(type==='boolean'){
      var hidden=field.querySelector('input[type=hidden]'); if(hidden)hidden.value=raw;
      var btns=field.querySelectorAll('.cd040-bopt');
      btns.forEach(function(b){
        b.classList.remove('sel-yes','sel-no');
        if(b.getAttribute('data-val')===String(raw)){b.classList.add(raw==='1'||raw===1?'sel-yes':'sel-no');}
      });
    }else if(type==='overall_grade'){
      var sel=field.querySelector('select'); if(sel)sel.value=raw;
    }
    var notesTa=field.querySelector('.cd040-notes textarea'); if(notesTa)notesTa.value=notes;
  });
}

/* ===== live score ===== */
function cd040_calc(){
  var sum=0,n=0;
  document.querySelectorAll('.cd040-score').forEach(function(inp){
    var v=parseFloat(inp.value)||0; var max=parseFloat(inp.getAttribute('data-max'))||100;
    if(max>0&&inp.value!==''){sum+=(v/max)*100;n++;}
  });
  var avg=n>0?Math.round(sum/n*10)/10:0;
  var sv=document.getElementById('liveScore'); if(sv)sv.textContent=cd040_arabic(avg.toFixed(1));
  var gv=document.getElementById('liveGrade'); if(gv)gv.textContent=cd040_grade_text(avg);
}
function cd040_updateBar(input){
  var max=parseFloat(input.getAttribute('data-max'))||100;
  var v=Math.min(max,Math.max(0,parseFloat(input.value)||0));
  var ctrl=input.closest('.cd040-perc'); var fill=ctrl?ctrl.querySelector('.pfill'):null;
  if(fill)fill.style.width=((v/max)*100)+'%';
}
function cd040_bool(btn){
  var wrap=btn.parentElement;
  wrap.querySelectorAll('.cd040-bopt').forEach(function(s){s.classList.remove('sel-yes','sel-no');});
  var isYes=btn.getAttribute('data-val')==='1';
  btn.classList.add(isYes?'sel-yes':'sel-no');
  var hidden=wrap.querySelector('input[type=hidden]'); if(hidden){hidden.value=isYes?'1':'0';cd040_calc();}
}

/* ===== program picker ===== */
function cd040_loadPrograms(type){
  var card=document.getElementById('programCard');
  card.style.display='block';
  document.getElementById('contextType').value=type;
  document.getElementById('programLabel').textContent=type==='course'?'📚 الدورة التدريبية':'🏆 الورشة';
  fetch(APP_URL+'/api/training-programs/list/'+type).then(function(r){return r.json();}).then(function(list){
    var sel=document.getElementById('programSelect');
    sel.innerHTML='<option value="">— اختر —</option>';
    (list||[]).forEach(function(p){
      var o=document.createElement('option'); o.value=p.id;
      o.textContent=p.title+' — '+(p.branch_name||'')+'/'+(p.department_name||'')+' — '+(p.start_date||'')+' → '+(p.end_date||'');
      o.dataset.branch=(p.branch_name||'—')+(p.department_name?' — '+p.department_name:'');
      o.dataset.dates=(p.start_date||'—')+' → '+(p.end_date||'—');
      o.dataset.trainer=p.responsible_person||'—';
      sel.appendChild(o);
    });
  }).catch(function(){});
}
function cd040_showProgInfo(sel){
  var o=sel.options[sel.selectedIndex]; var box=document.getElementById('progInfo');
  if(!o||!o.value){box.classList.remove('show');return;}
  box.classList.add('show');
  document.getElementById('piBranch').textContent=o.dataset.branch||'—';
  document.getElementById('piDates').textContent=o.dataset.dates||'—';
  document.getElementById('piTrainer').textContent=o.dataset.trainer||'—';
}

/* ===== hijri date ===== */
function cd040_updateHijri(){
  var d=document.getElementById('evalDate').value; var el=document.getElementById('hijriDisplay');
  if(!d){el.textContent='';return;}
  fetch(APP_URL+'/api/to-hijri?date='+d).then(function(r){return r.json();}).then(function(data){
    el.textContent=data.hijri?('الموافق: '+data.hijri):'';
  }).catch(function(){el.textContent='';});
}

/* ===== save ===== */
document.getElementById('unifiedEvalForm').addEventListener('submit',function(e){
  e.preventDefault();
  cd040_save();
});
function cd040_save(){
  var empId=document.getElementById('employee_id').value;
  var tplId=document.getElementById('template_id_hidden').value;
  if(!empId||empId<1){cd040_toast('⚠️ اختر الموظف أولًا','#c62828');return;}
  if(!tplId||tplId<1){cd040_toast('⚠️ اختر القالب أولًا','#c62828');return;}
  var btn=document.getElementById('saveBtn'); btn.disabled=true; btn.textContent='⏳ جارٍ الحفظ...';
  var formData=new FormData(document.getElementById('unifiedEvalForm'));
  // ensure context_type included
  fetch(APP_URL+'/evaluations/save-unified',{method:'POST',body:formData,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){return r.json();})
    .then(function(res){
      btn.disabled=false; btn.textContent='💾 حفظ التقييم';
      if(res.success){
        cd040_toast('✅ '+res.message,'#1b6b32');
        setTimeout(function(){window.location.href=APP_URL+'/evaluations/show/'+res.evaluation_id;},1200);
      }else{
        cd040_toast('❌ '+(res.error||'فشل الحفظ'),'#c62828');
      }
    }).catch(function(err){
      btn.disabled=false; btn.textContent='💾 حفظ التقييم';
      cd040_toast('❌ خطأ في الاتصال','#c62828');
    });
}
function cd040_toast(msg,color){
  var t=document.createElement('div'); t.className='cd040-toast'; t.textContent=msg; t.style.background=color||'#1b6b32'; t.style.color='#fff';
  document.body.appendChild(t);
  setTimeout(function(){t.classList.add('show');},10);
  setTimeout(function(){t.classList.remove('show');setTimeout(function(){t.remove();},300);},2800);
}
function cd040_reset(){
  document.querySelectorAll('.cd040-score').forEach(function(i){i.value='';});
  document.querySelectorAll('.pfill').forEach(function(f){f.style.width='0%';});
  document.querySelectorAll('.cd040-bopt').forEach(function(b){b.classList.remove('sel-yes','sel-no');});
  document.querySelectorAll('textarea').forEach(function(t){if(t.name!=='evaluator_notes')t.value='';});
  cd040_calc();
}

/* ===== init ===== */
document.addEventListener('DOMContentLoaded',function(){
  cd040_updateHijri();
  document.getElementById('evalDate').addEventListener('change',cd040_updateHijri);
  // edit mode: auto-load the template's schema
  if(CD040_MODE==='edit'&&CD040_EVAL_TEMPLATE_ID>0){
    cd040_onTemplate(CD040_EVAL_TEMPLATE_ID);
  }
});
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
