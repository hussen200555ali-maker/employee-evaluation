<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
$evaluatorName = $evaluatorName ?? '';
$evaluatorTitle = $evaluatorTitle ?? '';
$gradingScales = $gradingScales ?? [];
?>
<style>
.cd042-wrap{background:#f5f8f6;min-height:100vh;direction:rtl;font-family:'Cairo',sans-serif}
.cd042-page{max-width:1000px;margin:0 auto;padding:14px}
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
.cd042-btn{padding:9px 18px;border:none;border-radius:8px;font-family:'Cairo';font-size:12px;font-weight:700;cursor:pointer}
.cd042-btn-ok{background:#1b6b32;color:#fff}
.cd042-btn-p{background:#0c2823;color:#c8943f}
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
      <div class="cd042-fld"><label>عدد الصفوف</label><input type="number" id="cd042-max" value="100" min="1" max="1000"></div>
    </div>
    <div style="margin-top:10px"><a id="cd042-dl" class="cd042-btn cd042-btn-ok" href="<?= APP_URL ?>/evaluations/excel/template<?php if(!empty($templates)): ?>?template_id=<?= (int)$templates[0]->id ?><?php endif; ?>">⬇ تنزيل القالب</a></div>
  </div>
</div>
<div class="cd042-card">
  <div class="cd042-card-h">② رفع الملف المعبّأ</div>
  <div class="cd042-card-b">
    <form method="post" action="<?= APP_URL ?>/evaluations/excel/preview" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
      <input type="file" name="workbook" accept=".xlsx" required style="margin-bottom:10px">
      <button type="submit" class="cd042-btn cd042-btn-p">🔍 معاينة</button>
    </form>
  </div>
</div>
<?php else: ?>
<div class="cd042-stats">
  <div class="cd042-stat"><div class="v"><?= (int)($preview['total']??0) ?></div><div class="l">إجمالي</div></div>
  <div class="cd042-stat"><div class="v" style="color:#1b6b32"><?= (int)($preview['valid']??0) ?></div><div class="l">صالحة</div></div>
  <div class="cd042-stat"><div class="v" style="color:#c8943f"><?= (int)($preview['invalid']??0) ?></div><div class="l">أخطاء</div></div>
  <div class="cd042-stat"><div class="v"><?= htmlspecialchars($preview['template_name']??'-') ?></div><div class="l">القالب</div></div>
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
<form method="post" action="<?= APP_URL ?>/evaluations/excel/commit/<?= (int)($preview['batch_id']??0) ?>" style="margin-top:10px">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
  <button type="submit" class="cd042-btn cd042-btn-ok">✅ تأكيد الاستيراد — <?= (int)($preview['valid']??0) ?> تقييم</button>
  <a href="<?= APP_URL ?>/evaluations/excel/import" class="cd042-btn" style="background:#e8e8e8;color:#6c757d">إلغاء</a>
</form>
<?php else: ?>
<a href="<?= APP_URL ?>/evaluations/excel/import" class="cd042-btn cd042-btn-p">← العودة</a>
<?php endif; ?>
<?php endif; ?>
</div>
</div>
<script>
var APP_URL_CONST = "<?= APP_URL ?>";
(function(){
  var tpl=document.getElementById('cd042-tpl');
  var br=document.getElementById('cd042-br');
  var dept=document.getElementById('cd042-dept');
  var max=document.getElementById('cd042-max');
  var dl=document.getElementById('cd042-dl');
  var branches=<?= json_encode(array_reduce($branches??[],function($c,$b){$c[$b->id]=array_map(function($d){return['id'=>$d->id,'name'=>$d->name];},$b->departments??[]);return$c;},[]),JSON_UNESCAPED_UNICODE) ?>;
  function updateDl(){
    if(!tpl||!dl)return;
    var url=APP_URL_CONST+'/evaluations/excel/template?template_id='+encodeURIComponent(tpl.value);
    if(br&&br.value)url+='&branch_id='+encodeURIComponent(br.value);
    if(dept&&dept.value)url+='&department_id='+encodeURIComponent(dept.value);
    if(max)url+='&max_rows='+encodeURIComponent(max.value);
    dl.href=url;
  }
  if(br)br.addEventListener('change',function(){
    var list=branches[this.value]||[];
    dept.innerHTML='<option value="">الكل</option>';
    list.forEach(function(d){var o=document.createElement('option');o.value=d.id;o.textContent=d.name;dept.appendChild(o);});
    dept.disabled=!this.value;
    updateDl();
  });
  if(tpl)tpl.addEventListener('change',updateDl);
  if(max)max.addEventListener('input',updateDl);
  if(dept)dept.addEventListener('change',updateDl);
  updateDl();
})();
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
