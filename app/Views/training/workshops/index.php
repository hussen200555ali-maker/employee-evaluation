<?php 
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="page-header">
    <h2><i class="fas fa-tools"></i> إدارة الورش التدريبية</h2>
    <div>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#exportModal" style="background:#c8943f;margin-left:8px;">
            <i class="fas fa-file-excel"></i> تصدير قالب تقييم
        </button>
        <a href="<?php echo APP_URL; ?>/training/workshops/create" class="btn-add">
            <i class="fas fa-plus"></i> إضافة ورشة جديدة
        </a>
    </div>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>عنوان الورشة</th>
                <th>الفرع / القسم</th>
                <th>تاريخ البدء</th>
                <th>تاريخ الانتهاء</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data['workshops'])): ?>
                <?php foreach ($data['workshops'] as $workshop): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($workshop->workshop_title); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($workshop->branch_name ?? 'غير محدد'); ?>
                            <?php if ($workshop->department_name): ?>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($workshop->department_name); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $workshop->start_date; ?></td>
                        <td><?php echo $workshop->end_date; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo htmlspecialchars($workshop->status); ?>">
                                <?php echo htmlspecialchars($workshop->status); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="<?php echo APP_URL; ?>/training/workshops/edit/<?php echo $workshop->id; ?>" 
                                   class="btn-action btn-edit" title="تعديل الورشة">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <?php if ($workshop->status === 'completed'): ?>
                                    <?php if (!empty($workshop->evaluation_id)): ?>
                                        <a href="<?php echo APP_URL . '/training/workshops/evaluations/show/' . $workshop->evaluation_id; ?>" 
                                           class="btn-action btn-view" title="عرض التقييم">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo APP_URL . '/training/workshops/evaluations/create/' . $workshop->id; ?>" 
                                           class="btn-action btn-success" title="تقييم الورشة">
                                            <i class="fas fa-star"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="btn-group">
                                    <button type="button" id="statusDropdown-<?php echo $workshop->id; ?>" 
                                            class="btn-action btn-warning dropdown-toggle" 
                                            data-bs-toggle="dropdown" aria-expanded="false" title="تغيير الحالة">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="statusDropdown-<?php echo $workshop->id; ?>">
                                        <li><a class="dropdown-item" href="#" onclick="submitStatusForm(<?php echo $workshop->id; ?>, 'scheduled'); return false;">مجدولة</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="submitStatusForm(<?php echo $workshop->id; ?>, 'completed'); return false;">مكتملة</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="submitStatusForm(<?php echo $workshop->id; ?>, 'canceled'); return false;">ملغاة</a></li>
                                    </ul>
                                </div>
                                <form class="d-inline" action="<?php echo APP_URL; ?>/training/workshops/delete" 
                                      method="POST" onsubmit="return confirm('هل أنت متأكد من أنك تريد حذف هذه الورشة؟');">
                <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?php echo $workshop->id; ?>">
                                    <button type="submit" class="btn-action btn-danger" title="حذف الورشة">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center p-4">لا توجد ورش تدريبية حالياً.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#0c2823,#0d5e52);color:#fff;">
        <h5 class="modal-title"><i class="fas fa-file-excel me-2"></i> تصدير قالب تقييم الورش</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold">قالب التقييم</label>
          <select id="exportTemplateSelect" class="form-select">
            <option value="">-- جاري تحميل القوالب...</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">وضع التصدير</label>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="exportMode" value="prefill" id="modePrefill" checked>
            <label class="form-check-label" for="modePrefill">
              <i class="fas fa-list-ul me-1"></i> بيانات موجودة (ورش مسجلة مسبقاً)
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="exportMode" value="empty" id="modeEmpty">
            <label class="form-check-label" for="modeEmpty">
              <i class="fas fa-file me-1"></i> قالب فارغ (إضافة ورش جديدة)
            </label>
          </div>
        </div>
        <div id="prefillSection" class="mb-3">
          <label class="form-label fw-bold">اختر الورش (اترك فارغًا لتصدير الكل)</label>
          <select id="prefillCourses" multiple class="form-select" size="6" style="max-height:200px;overflow-y:auto;">
          </select>
          <small class="text-muted">اضغط Ctrl لاختيار أكثر من ورشة</small>
        </div>
        <div class="mb-3">
          <label class="form-label">عدد الصفوف الأقصى</label>
          <input type="number" id="exportMaxRows" class="form-control" value="100" min="1" max="1000" style="width:120px;">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
        <a id="exportDownloadBtn" class="btn btn-success" href="#">
          <i class="fas fa-download me-1"></i> تنزيل القالب
        </a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var templateSelect = document.getElementById('exportTemplateSelect');
    var prefillSelect = document.getElementById('prefillCourses');
    var prefillSection = document.getElementById('prefillSection');
    var modePrefill = document.getElementById('modePrefill');
    var modeEmpty = document.getElementById('modeEmpty');
    var downloadBtn = document.getElementById('exportDownloadBtn');
    var maxRowsInput = document.getElementById('exportMaxRows');

    fetch('<?php echo APP_URL; ?>/api/training-templates?target_type=workshop')
        .then(function(r){ return r.json(); })
        .then(function(templates){
            templateSelect.innerHTML = '<option value="">-- اختر القالب --</option>';
            templates.forEach(function(t){
                var opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.template_name;
                templateSelect.appendChild(opt);
            });
            updateDownloadUrl();
        });

    fetch('<?php echo APP_URL; ?>/api/training-list?target_type=workshop')
        .then(function(r){ return r.json(); })
        .then(function(items){
            prefillSelect.innerHTML = '';
            items.forEach(function(item){
                var opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = (item.title || '') + ' — ' + (item.description || '');
                prefillSelect.appendChild(opt);
            });
        });

    function updateDownloadUrl(){
        var tid = templateSelect.value;
        var mode = document.querySelector('input[name="exportMode"]:checked').value;
        var maxRows = maxRowsInput.value || '100';
        if(!tid){ downloadBtn.href = '#'; return; }
        var url = '<?php echo APP_URL; ?>/evaluations/excel/training-template?template_id=' + encodeURIComponent(tid) + '&mode=' + encodeURIComponent(mode) + '&max_rows=' + encodeURIComponent(maxRows);
        if(mode === 'prefill'){
            var selected = [];
            Array.prototype.forEach.call(prefillSelect.options, function(opt){
                if(opt.selected) selected.push(opt.value);
            });
            if(selected.length > 0){
                url += '&workshop_ids=' + selected.join(',');
            }
        }
        downloadBtn.href = url;
    }

    modePrefill.addEventListener('change', function(){ prefillSection.style.display = ''; updateDownloadUrl(); });
    modeEmpty.addEventListener('change', function(){ prefillSection.style.display = 'none'; updateDownloadUrl(); });
    templateSelect.addEventListener('change', updateDownloadUrl);
    maxRowsInput.addEventListener('input', updateDownloadUrl);
    prefillSelect.addEventListener('change', updateDownloadUrl);
    updateDownloadUrl();
});
</script>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
