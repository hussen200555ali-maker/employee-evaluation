<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<div class="container">
  <div class="page-header"><h2><i class="fas fa-building text-primary"></i> بدء تقييم وحدة تنظيمية</h2></div>
  <div class="form-container">
    <form action="<?= APP_URL ?>/units/evaluations/store" method="POST" id="unit-eval-form">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label fw-bold">نوع الوحدة</label>
          <select name="entity_type" id="entity_type" class="form-select" required>
            <option value="branch">فرع</option>
            <option value="department">قسم</option>
          </select>
        </div>
        <div class="col-md-4"><label class="form-label fw-bold">الوحدة</label>
          <select name="entity_id" id="entity_id" class="form-select" required><option value="">-- اختر --</option></select>
        </div>
        <div class="col-md-4"><label class="form-label fw-bold">القالب (وحدة)</label>
          <select name="template_id" id="template_id" class="form-select" required><option value="">-- اختر القالب --</option></select>
        </div>
      </div>
      <div class="row g-3 mt-1">
        <div class="col-md-4"><label class="form-label fw-bold">الجدولة (اختياري)</label>
          <select name="schedule_id" id="schedule_id" class="form-select"><option value="">-- بدون جدولة (حر) --</option></select>
        </div>
        <div class="col-md-4"><label class="form-label fw-bold">تاريخ التقييم</label><input type="date" name="evaluation_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
      </div>
      <div class="form-actions mt-4"><button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-play"></i> بدء تقييم الوحدة</button></div>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const et=document.getElementById('entity_type'), eid=document.getElementById('entity_id'), tid=document.getElementById('template_id'), sid=document.getElementById('schedule_id');
  const branches=<?= json_encode($branches) ?>, depts=<?= json_encode($departments) ?>;
  function loadUnits(){const v=et.value; eid.innerHTML='<option value="">-- اختر --</option>'; const list=v==='branch'?branches:depts; list.forEach(o=>{const opt=document.createElement('option');opt.value=o.id;opt.textContent=o.name;eid.appendChild(opt);}); loadTemplates(); loadSchedules();}
  async function loadTemplates(){
    const type='unit'; const us=et.value;
    const res=await fetch(`${BASE_URL}/api/templates/by-scope/${type}?unit_scope=${us}`);
    const data=await res.json(); tid.innerHTML='<option value="">-- اختر القالب --</option>'; data.forEach(t=>{const o=document.createElement('option');o.value=t.id;o.textContent=t.template_name;tid.appendChild(o);});
  }
  async function loadSchedules(){
    if(!eid.value){sid.innerHTML='<option value="">-- بدون جدولة --</option>'; return;}
    // schedules filtered client-side from all schedules
    const all=<?= json_encode($schedules) ?>;
    sid.innerHTML='<option value="">-- بدون جدولة --</option>';
    all.filter(s=>s.entity_type===et.value && s.unit_id==eid.value).forEach(s=>{const o=document.createElement('option');o.value=s.id;o.textContent=s.title+' ('+s.periodicity+')';sid.appendChild(o);});
  }
  et.addEventListener('change',loadUnits); eid.addEventListener('change',loadSchedules);
  loadUnits();
});
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
