<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<style>
.rating-wrap{max-width:900px;margin:0 auto;}
.rating-card{background:#fff;border:1px solid #e6efe9;border-radius:16px;box-shadow:0 8px 24px rgba(22,48,43,.06);padding:1.5rem;}
.rating-row{display:grid;grid-template-columns:1.2fr .6fr .6fr .7fr 40px;gap:10px;align-items:center;margin-bottom:10px;padding:10px;background:#f8fafb;border-radius:10px;border:1px solid #edf3f0;}
.rating-row.header{background:#0d5e52;color:#fff;font-weight:800;border:none;}
.btn-add{background:#0d5e52;color:#fff;border:none;border-radius:10px;padding:10px 18px;font-weight:700;}
.btn-add:hover{background:#c8943f;color:#fff;}
.btn-del{background:#ef4444;color:#fff;border:none;width:36px;height:36px;border-radius:8px;}
</style>
<div class="container rating-wrap">
  <div class="page-header" style="background:linear-gradient(135deg,#0c2823 0%,#0d5e52 100%);color:#fff;border-radius:16px;padding:20px;margin-bottom:16px;">
    <h3><i class="fas fa-sliders-h" style="color:#c8943f"></i> إعدادات التصنيف - التحكم المرن المطلق</h3>
    <small style="opacity:.8">أضف/احذف فئات، غيّر المسميات (ممتاز/يحتاج تدخل)، وحدد العتبات من-إلى - تنعكس فوراً على كل التقارير (4 أنواع)</small>
  </div>
  <div class="rating-card">
    <form action="<?= APP_URL ?>/settings/rating/save" method="POST" id="ratingForm">
      <?= csrf_field() ?>
      <div class="rating-row header"><div>المسمى</div><div>من درجة</div><div>إلى درجة</div><div>اللون</div><div></div></div>
      <div id="rows">
        <?php if(!empty($settings)): foreach($settings as $s): ?>
          <div class="rating-row">
            <input type="text" name="label[]" class="form-control" value="<?= htmlspecialchars($s->label) ?>" required placeholder="ممتاز">
            <input type="number" name="min_score[]" class="form-control" value="<?= $s->min_score ?>" step="0.01" min="0" max="100" required>
            <input type="number" name="max_score[]" class="form-control" value="<?= $s->max_score ?>" step="0.01" min="0" max="100" required>
            <input type="color" name="color[]" class="form-control" value="<?= htmlspecialchars($s->color) ?>" style="height:38px;padding:4px">
            <button type="button" class="btn-del" onclick="this.closest('.rating-row').remove()"><i class="fas fa-trash"></i></button>
          </div>
        <?php endforeach; else: ?>
          <div class="rating-row">
            <input type="text" name="label[]" class="form-control" value="ممتاز" required>
            <input type="number" name="min_score[]" class="form-control" value="90" step="0.01" required>
            <input type="number" name="max_score[]" class="form-control" value="100" step="0.01" required>
            <input type="color" name="color[]" class="form-control" value="#0d5e52" style="height:38px">
            <button type="button" class="btn-del" onclick="this.closest('.rating-row').remove()"><i class="fas fa-trash"></i></button>
          </div>
        <?php endif; ?>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button type="button" class="btn-add" onclick="addRow()"><i class="fas fa-plus"></i> إضافة فئة</button>
        <button type="submit" class="btn btn-warning" style="margin-right:auto;background:#c8943f;color:#fff;border:none;border-radius:10px;padding:10px 24px;font-weight:800;">حفظ الإعدادات</button>
      </div>
      <small class="text-muted d-block mt-2">مثال: 3 فئات (ممتاز، متوسط، ضعيف) أو 5 فئات - النظام يدعم أي عدد</small>
    </form>
  </div>
  <div class="alert alert-info mt-3"><i class="fas fa-info-circle"></i> تنعكس فوراً على: لوحات التحكم، التقارير الإحصائية، الموحدة، ملفات الأداء - لكل الأنواع الأربعة دون تعديل القوالب</div>
</div>
<script>
function addRow(){
  const r=document.createElement('div'); r.className='rating-row';
  r.innerHTML=`<input type="text" name="label[]" class="form-control" placeholder="جديد" required><input type="number" name="min_score[]" class="form-control" value="0" step="0.01" required><input type="number" name="max_score[]" class="form-control" value="100" step="0.01" required><input type="color" name="color[]" class="form-control" value="#0d5e52" style="height:38px"><button type="button" class="btn-del" onclick="this.closest('.rating-row').remove()"><i class="fas fa-trash"></i></button>`;
  document.getElementById('rows').appendChild(r);
}
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
