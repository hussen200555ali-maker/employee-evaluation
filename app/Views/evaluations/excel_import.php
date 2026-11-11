<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
$evaluatorName = $evaluatorName ?? '';
$evaluatorTitle = $evaluatorTitle ?? '';
$gradingScales = $gradingScales ?? [];
$templates = $templates ?? [];
$branches = $branches ?? [];
$preview = $preview ?? null;
$templateStructures = $templateStructures ?? [];
$csrfToken = $csrfToken ?? '';
$typeLabels = [
    'employee' => 'فردي',
    'skill' => 'مهاري',
    'employee_in_course' => 'دورة',
    'employee_in_workshop' => 'ورشة',
    'course' => 'دورة (وصفي)',
    'workshop' => 'ورشة (وصفي)',
    'unit' => 'وحدة',
];
$typeIcons = [
    'employee' => '①',
    'skill' => '④',
    'employee_in_course' => '②',
    'employee_in_workshop' => '③',
    'course' => '⑤',
    'workshop' => '⑥',
    'unit' => '⑦',
];
$typeOrder = ['employee','skill','employee_in_course','employee_in_workshop','course','workshop','unit'];
$templatesByType = [];
foreach ($templates as $tpl) {
    $templatesByType[$tpl->target_type][] = $tpl;
}
$availableTypes = array_values(array_intersect($typeOrder, array_keys($templatesByType)));
?>
<style>
.cd042-wrap{background:#f5f8f6;min-height:100vh;direction:rtl;font-family:'Cairo',sans-serif}
.cd042-page{max-width:1100px;margin:0 auto;padding:14px}
.cd042-hdr{background:linear-gradient(135deg,#0c2823,#0d5e52);color:#fff;padding:14px 20px;border-radius:10px;margin-bottom:10px;position:relative}
.cd042-hdr::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:#c8943f}
.cd042-hdr h2{font-size:16px;font-weight:800;margin:0}
.cd042-hdr .sub{font-size:11px;opacity:.8;margin-top:2px}
.cd042-alert{padding:10px 14px;border-radius:8px;font-size:11px;display:flex;gap:8px;margin-bottom:10px;background:#fff3cd;color:#856404;border:1px solid #ffe69b}
.cd042-card{background:#fff;border:1px solid #e0e8e4;border-radius:10px;margin-bottom:10px;overflow:hidden}
.cd042-card-h{background:linear-gradient(180deg,#fbfdfc,#f1f7f3);padding:10px 16px;border-bottom:1px solid #e0e8e4;font-weight:800;color:#0c2823;font-size:13px;display:flex;align-items:center;gap:8px}
.cd042-card-b{padding:14px 16px}
.cd042-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr 1fr;gap:10px;align-items:end}
.cd042-fld label{display:block;font-size:10px;color:#6c757d;font-weight:700;margin-bottom:3px}
.cd042-fld select,.cd042-fld input{width:100%;padding:8px 10px;border:1px solid #e0e8e4;border-radius:6px;font-family:'Cairo';font-size:12px;background:#fff}
.cd042-btn{padding:9px 18px;border:none;border-radius:8px;font-family:'Cairo';font-size:12px;font-weight:700;cursor:pointer;display:inline-block;text-decoration:none}
.cd042-btn-ok{background:#1b6b32;color:#fff}
.cd042-btn-p{background:#0c2823;color:#c8943f}
.cd042-btn-ghost{background:#e8e8e8;color:#6c757d}
.cd042-btn-ok:hover,.cd042-btn-p:hover{transform:translateY(-1px)}
.cd042-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:10px}
.cd042-stat{background:#fff;border:1px solid #e0e8e4;border-radius:8px;padding:10px;text-align:center}
.cd042-stat .v{font-size:20px;font-weight:800;color:#0c2823}
.cd042-stat .l{font-size:10px;color:#6c757d;font-weight:600;margin-top:2px}
.cd042-table{width:100%;border-collapse:collapse;font-size:11px}
.cd042-table th{background:#0c2823;color:#c8943f;padding:8px 6px;text-align:center;font-size:10px}
.cd042-table td{padding:6px;border-bottom:1px solid #e0e8e4;text-align:center}
.cd042-badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:9px;font-weight:700}
.cd042-badge-ok{background:#e6f4ea;color:#1b6b32}
.cd042-badge-new{background:#faefd9;color:#c8943f}
.cd042-badge-err{background:#fdecea;color:#c62828}
/* preview panel */
.preview-wrap{background:#fff;border:1px solid #e0e8e4;border-radius:10px;margin-bottom:10px;overflow:hidden}
.preview-tabs{display:flex;gap:4px;padding:8px 12px;background:linear-gradient(180deg,#fbfdfc,#f1f7f3);border-bottom:1px solid #e0e8e4;flex-wrap:wrap}
.ptab{padding:6px 14px;border:1.5px solid #e0e8e4;border-radius:7px;background:#fff;cursor:pointer;font-family:'Cairo';font-size:11px;font-weight:700;color:#6c757d;transition:all .15s}
.ptab:hover{border-color:#c8943f}
.ptab.active{background:#0c2823;color:#c8943f;border-color:#0c2823}
.legend{display:flex;gap:10px;flex-wrap:wrap;padding:10px 14px;background:#fff;border-radius:8px;margin-bottom:10px;font-size:9px;border:1px solid #e0e8e4}
.lg{display:flex;align-items:center;gap:4px}
.lg-c{width:14px;height:14px;border-radius:3px;border:1px solid #e0e8e4}
.preview-scroll{overflow-x:auto;padding:10px 12px}
table.xl{border-collapse:collapse;font-size:10px;min-width:700px}
table.xl th{padding:6px 4px;text-align:center;font-weight:700;border:1px solid #d1d5db}
table.xl th.col-ltr{background:#0c2823;color:#c8943f;font-size:9px}
table.xl th.emp{background:#0c2823;color:#fff}
table.xl th.crit{background:#0c2823;color:#fff}
table.xl th.sec{background:#d1e7dd;color:#0c2823;font-size:9px}
table.xl th.gnotes{background:#374151;color:#fff;font-size:9px}
table.xl th.eval{background:#374151;color:#fff;font-size:9px}
table.xl th .star{color:#f87171}
table.xl th .w{display:block;font-size:8px;color:#c8943f;font-weight:600}
table.xl th .dd{display:block;font-size:8px;color:#9ca3af}
table.xl th .tag{display:block;font-size:8px;color:#6b7280}
table.xl td{padding:4px 6px;border:1px solid #e5e7eb;text-align:center;font-size:10px}
table.xl td.d{background:#fffbeb}
table.xl td.new{background:#faefd9}
table.xl td.rn{background:#f3f4f6;color:#6b7280;font-weight:700}
/* upload zone */
.upload-zone{border:2px dashed #c8943f;border-radius:10px;padding:24px;text-align:center;background:#faf7f0;cursor:pointer;transition:all .15s}
.upload-zone:hover,.upload-zone.drag{background:#f5efe3;border-color:#0c2823}
.upload-zone .icon{font-size:32px;margin-bottom:6px}
.upload-zone .title{font-size:13px;font-weight:700;color:#0c2823}
.upload-zone .sub{font-size:10px;color:#6c757d;margin-top:4px}
.upload-info{margin-top:8px;font-size:11px;color:#1b6b32;font-weight:600}
/* sticky bar */
.sticky-bar{position:sticky;bottom:0;background:rgba(255,255,255,.85);backdrop-filter:blur(6px);border-top:1px solid #e0e8e4;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:12px;border-radius:10px 10px 0 0}
.sticky-bar .note{font-size:10px;color:#6c757d}
.sticky-bar .actions{display:flex;gap:8px}
@media(max-width:768px){.cd042-grid{grid-template-columns:1fr 1fr}.cd042-stats{grid-template-columns:1fr 1fr}.preview-scroll{overflow-x:scroll}table.xl{min-width:900px}.sticky-bar{flex-direction:column;align-items:stretch}}
</style>
<div class="cd042-wrap">
<div class="cd042-page">
<div class="cd042-hdr">
  <h2>📥 استيراد نتائج التقييم من Excel</h2>
  <div class="sub">CD-042 · تنزيل قالب ← تعبئة ← رفع ← معاينة ← التزام</div>
</div>
<div class="cd042-alert">
  <span>⚠️</span>
  <div><strong>ملاحظة:</strong> يمكنك إضافة موظفين جدد من خلال هذا الملف. تأكد من إدخال (الاسم أو الرقم الوظيفي) و (الفرع أو القسم) لكل موظف.</div>
</div>
<?php if ($preview === null): ?>
<!-- ===== Preview panel: template structure ===== -->
<div class="preview-wrap">
  <div class="preview-tabs" id="cd042-tabs"></div>
  <div class="legend">
    <div class="lg"><div class="lg-c" style="background:#0c2823"></div>الترويسة</div>
    <div class="lg"><div class="lg-c" style="background:#d1e7dd"></div>الأقسام</div>
    <div class="lg"><div class="lg-c" style="background:#c8943f"></div>الوزن</div>
    <div class="lg"><div class="lg-c" style="background:#fffbeb"></div>قائمة منسدلة</div>
    <div class="lg"><div class="lg-c" style="background:#f0f7ff"></div>رقم</div>
    <div class="lg"><div class="lg-c" style="background:#fcfcfc"></div>نصّي</div>
    <div class="lg"><div class="lg-c" style="background:#faefd9"></div>موظف جديد</div>
  </div>
  <div class="preview-scroll" id="cd042-preview"></div>
</div>
<!-- ===== Export panel ===== -->
<div class="cd042-card">
  <div class="cd042-card-h">① تنزيل قالب Excel</div>
  <div class="cd042-card-b">
    <div class="cd042-grid">
      <div class="cd042-fld"><label>القالب</label>
        <select id="cd042-tpl"><?php if(!empty($templates)): foreach($templates as $tpl): ?><option value="<?= (int)$tpl->id ?>" data-type="<?= htmlspecialchars($tpl->target_type) ?>"><?= htmlspecialchars($tpl->template_name) ?></option><?php endforeach; endif; ?></select>
      </div>
      <div class="cd042-fld"><label>الفرع</label>
        <select id="cd042-br"><option value="">الكل</option><?php if(!empty($branches)): foreach($branches as $br): ?><option value="<?= (int)$br->id ?>"><?= htmlspecialchars($br->name) ?></option><?php endforeach; endif; ?></select>
      </div>
      <div class="cd042-fld"><label>القسم</label><select id="cd042-dept" disabled><option value="">الكل</option></select></div>
      <div class="cd042-fld"><label>عدد الصفوف</label><input type="number" id="cd042-max" value="100" min="1" max="2000"></div>
    </div>
    <div style="margin-top:6px;font-size:10px;color:#6c757d">الحد: ١ صف كحد أدنى · ٢٠٠٠ صف كحد أقصى · القوائم المنسدلة للفرع/القسم تُصدّر حسب الاختيار</div>
    <div style="margin-top:10px"><a id="cd042-dl" class="cd042-btn cd042-btn-ok" href="<?= APP_URL ?>/evaluations/excel/template<?php if(!empty($templates)): ?>?template_id=<?= (int)$templates[0]->id ?><?php endif; ?>">⬇ تنزيل القالب</a></div>
  </div>
</div>
<!-- ===== Upload zone ===== -->
<div class="cd042-card">
  <div class="cd042-card-h">② رفع الملف المعبّأ</div>
  <div class="cd042-card-b">
    <form method="post" action="<?= APP_URL ?>/evaluations/excel/preview" enctype="multipart/form-data" id="cd042-upload-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <label class="upload-zone" id="cd042-zone" for="cd042-file">
        <div class="icon">📁</div>
        <div class="title">اسحب ملف XLSX هنا أو انقر للاختيار</div>
        <div class="sub">حد أقصى ٢٠ ميغابايت (يتّسع لـ 2000 موظف) · .xlsx فقط · استعمل القالب المُصدّر</div>
        <input type="file" id="cd042-file" name="workbook" accept=".xlsx" required hidden>
      </label>
      <div class="upload-info" id="cd042-fileinfo"></div>
      <div style="margin-top:10px"><button type="submit" class="cd042-btn cd042-btn-p">🔍 معاينة</button></div>
    </form>
  </div>
</div>
<?php else: ?>
<div class="cd042-stats">
  <div class="cd042-stat"><div class="v"><?= (int)($preview['total']??0) ?></div><div class="l">إجمالي الصفوف</div></div>
  <div class="cd042-stat"><div class="v" style="color:#1b6b32"><?= (int)($preview['valid']??0) ?></div><div class="l">صالحة</div></div>
  <div class="cd042-stat"><div class="v" style="color:#c8943f"><?= (int)($preview['new_employees']??0) ?></div><div class="l">موظف جديد</div></div>
  <div class="cd042-stat"><div class="v" style="color:#c62828"><?= (int)($preview['invalid']??0) ?></div><div class="l">أخطاء</div></div>
</div>
<?php if(($preview['invalid']??0)>0): ?><div class="cd042-alert" style="background:#fdecea;color:#c62828;border-color:#f5c6cb"><span>✕</span><div>يوجد أخطاء — صحّحها وأعد الرفع.</div></div><?php endif; ?>
<div class="cd042-card">
  <div class="cd042-card-h">معاينة البيانات</div>
  <div style="overflow-x:auto">
    <table class="cd042-table">
      <thead><tr><th>#</th><th>الحالة</th><th>الرقم</th><th>الاسم</th><th>الفرع</th><th>القسم</th><th>التاريخ</th><th>الدرجات</th><th>النتيجة</th></tr></thead>
      <tbody>
      <?php foreach(($preview['rows']??[]) as $row): ?>
        <?php $err = !empty($row['errors']); $isNew = !empty($row['is_new_employee']); ?>
        <tr style="background:<?= $err?'#fdecea':($isNew?'#faefd9':'#f0fdf4') ?>">
          <td><?= (int)($row['row']??0) ?></td>
          <td><?php if($err): ?><span class="cd042-badge cd042-badge-err">✕ خطأ</span><?php elseif($isNew): ?><span class="cd042-badge cd042-badge-new">＋ جديد</span><?php else: ?><span class="cd042-badge cd042-badge-ok">✓ صالح</span><?php endif; ?></td>
          <td><?= htmlspecialchars($row['payload']['emp_code']??'—') ?></td>
          <td><?= htmlspecialchars($row['payload']['full_name']??'—') ?></td>
          <td><?= htmlspecialchars($row['payload']['branch_name']??'—') ?></td>
          <td><?= htmlspecialchars($row['payload']['department_name']??'—') ?></td>
          <td dir="ltr"><?= htmlspecialchars($row['payload']['evaluation_date']??'—') ?></td>
          <td><?php if(!empty($row['section_values'])): foreach($row['section_values'] as $v): ?><span style="border:1px solid #e0e8e4;padding:1px 4px;border-radius:3px;margin:1px"><?= htmlspecialchars((string)$v) ?></span><?php endforeach; else: ?>—<?php endif; ?></td>
          <td><?php if($err): ?><ul style="margin:0;padding-right:14px;font-size:10px;color:#c62828"><?php foreach($row['errors'] as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul><?php else: ?>✓<?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if(($preview['invalid']??0)==0 && ($preview['valid']??0)>0): ?>
<form method="post" action="<?= APP_URL ?>/evaluations/excel/commit/<?= (int)($preview['batch_id']??0) ?>" id="cd042-commit-form" style="margin-top:10px">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
  <div class="sticky-bar">
    <div class="note">القالب محمي: تعديل البيانات فقط — لا إضافة أعمدة أو تغيير أنواع الخلايا</div>
    <div class="actions">
      <a href="<?= APP_URL ?>/evaluations/excel/import" class="cd042-btn cd042-btn-ghost">↺ تفريغ</a>
      <button type="submit" class="cd042-btn cd042-btn-ok" id="cd042-commit-btn">✅ تأكيد الاستيراد — <?= (int)($preview['valid']??0) ?> تقييم</button>
    </div>
  </div>
</form>
<?php else: ?>
<a href="<?= APP_URL ?>/evaluations/excel/import" class="cd042-btn cd042-btn-p">← العودة</a>
<?php endif; ?>
<?php endif; ?>
</div>
</div>
<script>
var APP_URL_CONST = "<?= APP_URL ?>";
var CD042_STRUCTURES = <?= json_encode($templateStructures, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
var CD042_TEMPLATES = <?= json_encode(array_map(function($t){return ['id'=>(int)$t->id,'name'=>$t->template_name,'type'=>$t->target_type];}, $templates), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
var CD042_TYPE_LABELS = <?= json_encode($typeLabels, JSON_UNESCAPED_UNICODE) ?>;
var CD042_TYPE_ICONS = <?= json_encode($typeIcons, JSON_UNESCAPED_UNICODE) ?>;
var CD042_TYPE_ORDER = <?= json_encode($typeOrder) ?>;
(function(){
  // Group templates by type
  var byType = {};
  CD042_TEMPLATES.forEach(function(t){ (byType[t.type]=byType[t.type]||[]).push(t); });
  var tabsEl = document.getElementById('cd042-tabs');
  var previewEl = document.getElementById('cd042-preview');
  var tplSel = document.getElementById('cd042-tpl');
  var brSel = document.getElementById('cd042-br');
  var deptSel = document.getElementById('cd042-dept');
  var maxInp = document.getElementById('cd042-max');
  var dlLink = document.getElementById('cd042-dl');
  var branches = <?= json_encode(array_reduce($branches ?? [], function($c,$b){ $c[$b->id]=array_map(function($d){return ['id'=>$d->id,'name'=>$d->name];}, $b->departments ?? []); return $c; }, []), JSON_UNESCAPED_UNICODE) ?>;

  function esc(s){ return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  function updateDl(){
    if(!tplSel||!dlLink) return;
    var url = APP_URL_CONST + '/evaluations/excel/template?template_id=' + encodeURIComponent(tplSel.value);
    if(brSel && brSel.value) url += '&branch_id=' + encodeURIComponent(brSel.value);
    if(deptSel && deptSel.value) url += '&department_id=' + encodeURIComponent(deptSel.value);
    if(maxInp) url += '&max_rows=' + encodeURIComponent(maxInp.value);
    dlLink.href = url;
  }

  function renderPreview(tplId){
    var st = CD042_STRUCTURES[tplId];
    if(!st || !previewEl) return;
    var cols = st.columns || [];
    // group consecutive sections by 'group'
    // letter row
    var html = '<table class="xl"><thead>';
    // row 1: letters
    html += '<tr><th class="col-ltr" style="min-width:28px"></th>';
    var i = 0;
    while (i < cols.length) {
      var span = 1;
      if (cols[i].group) {
        while (i + span < cols.length && cols[i+span].group === cols[i].group) span++;
      }
      var letters = cols[i].letter + (span>1 ? '–' + cols[i+span-1].letter : '');
      html += '<th class="col-ltr" colspan="' + span + '">' + letters + '</th>';
      i += span;
    }
    html += '</tr>';
    // row 2: fixed/tail labels (rowspan 2) + criterion headers (colspan)
    html += '<tr><th class="rn"></th>';
    i = 0;
    while (i < cols.length) {
      var c = cols[i];
      if (c.kind === 'fixed' || c.kind === 'tail') {
        html += '<th class="' + (c.kind==='tail'?'gnotes':'emp') + '" rowspan="2">' + esc(c.label) + '</th>';
        i++;
      } else if (c.kind === 'section') {
        // criterion group
        var grp = c.group;
        var span = 1;
        while (i + span < cols.length && cols[i+span].kind === 'section' && cols[i+span].group === grp) span++;
        // find weight from st.criteria
        var crit = (st.criteria || []).filter(function(x){return x.name===grp;})[0];
        var wtxt = crit ? ('الوزن ' + Math.round(crit.weight) + '٪') : '';
        html += '<th class="crit" colspan="' + span + '">' + esc(grp) + '<span class="w">' + wtxt + '</span></th>';
        i += span;
      } else {
        i++;
      }
    }
    html += '</tr>';
    // row 3: section labels
    html += '<tr><th class="rn"></th>';
    cols.forEach(function(c){
      if (c.kind === 'section') {
        var cls = 'sec';
        var tag = '';
        var t = c.section_type;
        if (t === 'percentage') tag = 'نسبة ٠–١٠٠';
        else if (t === 'numeric') tag = 'رقمي';
        else if (t === 'boolean') tag = 'نعم/لا ▼';
        else if (t === 'grade' || t === 'multiple_choice') tag = 'قائمة ▼';
        else if (t === 'textarea' || t === 'text') tag = 'نص';
        else if (t === 'overall_grade') tag = 'تقدير ▼';
        var req = c.label.indexOf('*')>=0 ? '<span class="star">*</span>' : '';
        html += '<th class="' + cls + '">' + esc(c.label.replace(' *','')) + (req?' *':'') + '<span class="tag">' + tag + '</span></th>';
      }
    });
    html += '</tr></thead><tbody>';
    // sample rows
    (st.sample_rows || []).forEach(function(row, idx){
      var isNew = (idx === (st.sample_rows.length-1));
      html += '<tr>';
      html += '<td class="rn">' + (3+idx) + '</td>';
      cols.forEach(function(c){
        var v = row[c.letter];
        var cellCls = isNew ? 'new' : 'd';
        var kindCls = '';
        if (c.kind === 'fixed') {
          if (c.label.indexOf('فرع')>=0) kindCls='dd';
          else if (c.label.indexOf('قسم')>=0) kindCls='dd';
          else if (c.label.indexOf('رقم')>=0) kindCls='num';
          else kindCls='txt';
        } else if (c.kind === 'section') {
          var t = c.section_type;
          if (t === 'boolean') kindCls='dd';
          else if (t === 'percentage' || t === 'numeric') kindCls='num';
          else kindCls='txt';
        } else if (c.kind === 'tail') {
          if (c.label.indexOf('تاريخ')>=0) kindCls='num';
          else kindCls='txt';
        }
        html += '<td class="' + cellCls + ' ' + kindCls + '">' + esc(v) + '</td>';
      });
      html += '</tr>';
    });
    html += '</tbody></table>';
    previewEl.innerHTML = html;
  }

  function showType(type){
    // activate tab
    document.querySelectorAll('.ptab').forEach(function(t){t.classList.remove('active');});
    document.querySelectorAll('.ptab').forEach(function(t){ if(t.getAttribute('data-type')===type) t.classList.add('active'); });
    // update main template select to first template of this type
    var list = byType[type] || [];
    if (list.length && tplSel) {
      tplSel.value = list[0].id;
      // if multiple templates, show a small selector above preview
      if (list.length > 1) {
        var selHtml = '<div style="padding:8px 12px;background:#f9fafb;border-bottom:1px solid #e0e8e4;font-size:11px;display:flex;align-items:center;gap:8px"><label style="font-weight:700;color:#0c2823">القالب:</label><select id="cd042-type-tpl" style="padding:4px 8px;border:1px solid #e0e8e4;border-radius:6px;font-family:Cairo;font-size:11px">';
        list.forEach(function(t){ selHtml += '<option value="' + t.id + '">' + esc(t.name) + '</option>'; });
        selHtml += '</select></div>';
        // remove previous
        var prev = document.getElementById('cd042-type-tpl-wrap');
        if (prev) prev.remove();
        var wrap = document.createElement('div');
        wrap.id = 'cd042-type-tpl-wrap';
        wrap.innerHTML = selHtml;
        previewEl.parentNode.insertBefore(wrap, previewEl);
        document.getElementById('cd042-type-tpl').addEventListener('change', function(){ tplSel.value = this.value; renderPreview(parseInt(this.value)); updateDl(); });
      } else {
        var prev = document.getElementById('cd042-type-tpl-wrap');
        if (prev) prev.remove();
      }
      renderPreview(list[0].id);
      updateDl();
    }
  }

  // Build tabs
  if (tabsEl) {
    var firstType = null;
    CD042_TYPE_ORDER.forEach(function(type){
      if (byType[type] && byType[type].length) {
        if (!firstType) firstType = type;
        var btn = document.createElement('div');
        btn.className = 'ptab';
        btn.setAttribute('data-type', type);
        btn.textContent = (CD042_TYPE_ICONS[type] || '') + ' ' + (CD042_TYPE_LABELS[type] || type);
        btn.addEventListener('click', function(){ showType(type); });
        tabsEl.appendChild(btn);
      }
    });
    if (firstType) showType(firstType);
  }

  // Branch/department cascading
  if (brSel) brSel.addEventListener('change', function(){
    var list = branches[this.value] || [];
    deptSel.innerHTML = '<option value="">الكل</option>';
    list.forEach(function(d){ var o = document.createElement('option'); o.value = d.id; o.textContent = d.name; deptSel.appendChild(o); });
    deptSel.disabled = !this.value;
    updateDl();
  });
  if (tplSel) tplSel.addEventListener('change', function(){
    renderPreview(parseInt(this.value));
    updateDl();
  });
  if (maxInp) maxInp.addEventListener('input', updateDl);
  if (deptSel) deptSel.addEventListener('change', updateDl);
  updateDl();

  // Drag and drop
  var zone = document.getElementById('cd042-zone');
  var fileInput = document.getElementById('cd042-file');
  var fileInfo = document.getElementById('cd042-fileinfo');
  if (zone && fileInput) {
    zone.addEventListener('click', function(e){ if (e.target !== fileInput) fileInput.click(); });
    zone.addEventListener('dragover', function(e){ e.preventDefault(); zone.classList.add('drag'); });
    zone.addEventListener('dragleave', function(){ zone.classList.remove('drag'); });
    zone.addEventListener('drop', function(e){ e.preventDefault(); zone.classList.remove('drag'); if (e.dataTransfer && e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; showFileInfo(); } });
    fileInput.addEventListener('change', showFileInfo);
  }
  function showFileInfo(){
    if (!fileInput || !fileInput.files.length) { if (fileInfo) fileInfo.textContent=''; return; }
    var f = fileInput.files[0];
    var sizeMB = (f.size / (1024*1024)).toFixed(2);
    if (fileInfo) fileInfo.textContent = f.name + ' — ' + sizeMB + ' MB';
    if (f.size > 20*1024*1024) { if (fileInfo) fileInfo.textContent += ' · ⚠️ dépasse 20MB'; }
    if (!/\.xlsx$/i.test(f.name)) { if (fileInfo) fileInfo.textContent += ' · ⚠️ .xlsx uniquement'; }
  }
})();
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
