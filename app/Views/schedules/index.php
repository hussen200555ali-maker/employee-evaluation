<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<div class="container">
  <div class="page-header d-flex justify-content-between align-items-center">
    <h2><i class="fas fa-calendar-alt text-primary"></i> جدولة تقييم الوحدات</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal"><i class="fas fa-plus"></i> جدولة جديدة</button>
  </div>
  <div class="table-responsive mt-3">
    <table class="table table-bordered">
      <thead><tr><th>العنوان</th><th>القالب</th><th>الوحدة</th><th>الدورية</th><th>الفترة</th><th>إجراءات</th></tr></thead>
      <tbody>
        <?php if(!empty($schedules)): foreach($schedules as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s->title) ?></td>
            <td><?= htmlspecialchars($s->template_name) ?></td>
            <td><?= htmlspecialchars($s->unit_name ?? $s->entity_type.'#'.$s->unit_id) ?> (<?= $s->entity_type ?>)</td>
            <td><?= htmlspecialchars($s->periodicity) ?></td>
            <td><?= $s->start_date ?> → <?= $s->end_date ?? 'مفتوح' ?></td>
            <td>
              <form action="<?= APP_URL ?>/schedules/delete" method="POST" class="d-inline" onsubmit="return confirm('حذف الجدولة؟')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $s->id ?>">
                <button class="btn btn-sm btn-danger">حذف</button>
              </form>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6" class="text-center">لا توجد جدولات.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addScheduleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?= APP_URL ?>/schedules/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-header"><h5>جدولة جديدة</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label>القالب (وحدة فقط)</label>
            <select name="template_id" class="form-select" required>
              <option value="">-- اختر قالب وحدة --</option>
              <?php foreach($templates as $t): ?><option value="<?= $t->id ?>"><?= htmlspecialchars($t->template_name) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3"><label>نوع الوحدة</label>
            <select name="entity_type" id="sched_entity_type" class="form-select" required>
              <option value="branch">فرع</option>
              <option value="department">قسم</option>
            </select>
          </div>
          <div class="mb-3"><label>الوحدة</label>
            <select name="unit_id" id="sched_unit_id" class="form-select" required>
              <option value="">-- اختر --</option>
            </select>
          </div>
          <div class="mb-3"><label>الدورية</label>
            <select name="periodicity" class="form-select">
              <option value="daily">يومي</option><option value="weekly">أسبوعي</option><option value="monthly" selected>شهري</option><option value="quarterly">ربع سنوي</option><option value="semiannual">نصف سنوي</option><option value="annual">سنوي</option><option value="custom">مخصص</option>
            </select>
          </div>
          <div class="mb-3"><label>العنوان</label><input type="text" name="title" class="form-control" required></div>
          <div class="mb-3"><label>تاريخ البداية</label><input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
          <div class="mb-3"><label>تاريخ النهاية</label><input type="date" name="end_date" class="form-control"></div>
          <div class="form-check"><input type="checkbox" name="is_active" checked> نشط</div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button><button type="submit" class="btn btn-primary">حفظ</button></div>
      </form>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const et=document.getElementById('sched_entity_type'), uid=document.getElementById('sched_unit_id');
  const branches=<?= json_encode($branches) ?>, depts=<?= json_encode($departments) ?>;
  function load(){const v=et.value; uid.innerHTML='<option value="">-- اختر --</option>'; const list=v==='branch'?branches:depts; list.forEach(o=>{const opt=document.createElement('option');opt.value=o.id;opt.textContent=o.name;uid.appendChild(opt);});}
  et.addEventListener('change',load); load();
});
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
