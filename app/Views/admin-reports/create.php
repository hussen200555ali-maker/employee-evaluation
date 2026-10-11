<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/archive.css">
<style>
    .section-row.helper-section { border-left: 3px solid #6c757d; background: #f8f9fa; }
    .section-row.formula-section { border-left: 3px solid #dc3545; background: #fff5f5; }
    .section-row.auto-section { border-left: 3px solid #0dcaf0; background: #f0fbff; }
    .criterion-block { border: 2px solid #e9ecef; border-radius: 10px; }
    .criterion-header { background: linear-gradient(135deg, #0d5e52, #0a4a42); color: white; border-radius: 8px 8px 0 0; padding: 12px 15px; }
    .weight-badge { font-size: 12px; }
</style>

<div class="eval-wrap"><div class="eval-container">
  <div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
      <div class="page-title"><div class="page-title-icon"><i class="fas fa-plus-circle"></i></div> <?= $pageTitle ?></div>
      <a href="<?= APP_URL ?>/admin/reports" class="btn btn-light border"><i class="fas fa-arrow-right"></i> العودة</a>
    </div>
  </div>

  <form method="POST" action="<?= APP_URL ?>/admin/reports/store" id="reportForm">
    <?= csrf_field() ?>

    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-bold">عنوان التقرير *</label>
            <input type="text" name="report_title" class="form-control" required placeholder="مثال: تقرير الأداء الإداري">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">القالب *</label>
            <select name="template_id" class="form-select" required id="template-select">
              <?php foreach($templates as $t): ?>
                <option value="<?= $t->id ?>"><?= htmlspecialchars($t->template_name) ?></option>
              <?php endforeach; ?>
              <?php if(empty($templates)): ?>
                <option disabled>لا توجد قوالب إدارية متاحة</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">الكيان *</label>
            <select name="entity_type" class="form-select" required id="entity_type">
              <option value="branch">فرع</option>
              <option value="department">قسم</option>
              <option value="organization">المؤسسة ككل</option>
            </select>
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-3">
            <label class="form-label fw-bold">الفرع/القسم</label>
            <select name="entity_id" class="form-select" id="entity-select">
              <option value="">-- اختر --</option>
              <?php foreach($branches as $b): ?>
                <option value="<?= $b->id ?>" data-type="branch"><?= htmlspecialchars($b->name) ?></option>
              <?php endforeach; ?>
              <?php foreach($departments as $d): ?>
                <option value="<?= $d->id ?>" data-type="department" style="display:none;"><?= htmlspecialchars($d->name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">نوع الفترة *</label>
            <select name="period_type" class="form-select" required>
              <option value="monthly">شهري</option>
              <option value="quarterly">ربع سنوي</option>
              <option value="semiannual">نصف سنوي</option>
              <option value="annual">سنوي</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">من تاريخ *</label>
            <input type="date" name="period_start" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">إلى تاريخ *</label>
            <input type="date" name="period_end" class="form-control" required>
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-3">
            <label class="form-label fw-bold">القوة الفعلية</label>
            <input type="number" name="actual_force" class="form-control" min="0" value="0" id="actual_force_input">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">القوة المطبقه</label>
            <input type="number" name="applied_force" class="form-control" min="0" value="0">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">الحالة</label>
            <select name="status" class="form-select">
              <option value="draft">مسودة</option>
              <option value="pending_review">قيد المراجعة</option>
              <option value="completed">مكتمل</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">المقيّمون</label>
            <select name="evaluators[]" class="form-select" multiple size="2">
              <?php foreach($employees as $emp): ?>
                <option value="<?= $emp->id ?>"><?= htmlspecialchars($emp->full_name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- أقسام التقييم -->
    <?php if(!empty($templateStructure['criteria'])): ?>
    <div class="card mb-4">
      <div class="card-header criterion-header">
        <h5 class="mb-0"><i class="fas fa-list"></i> أقسام التقييم</h5>
      </div>
      <div class="card-body" id="sections-container">
        <?php foreach($templateStructure['criteria'] as $criterionIndex => $criterion): ?>
        <div class="criterion-block mb-4" data-criterion-id="<?= $criterion['id'] ?>">
          <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-top">
            <h6 class="text-success mb-0">
              <span class="section-ref-badge"><?= $criterionIndex + 1 ?></span> <?= htmlspecialchars($criterion['criterion_name']) ?>
            </h6>
            <span class="badge bg-success weight-badge">وزن: <?= $criterion['criterion_weight'] ?>%</span>
          </div>
          <div class="p-3">
            <?php foreach($criterion['sections'] as $sectionIndex => $section):
              $sectionType = $section['evaluation_type'] ?? '';
              $sectionConfig = json_decode($section['formula_config'] ?? '{}', true);
              $isHelper = ($sectionType === 'helper');
              $isFormula = ($sectionType === 'formula');
              $isAuto = ($sectionType === 'auto');
              $rowClass = $isHelper ? 'helper-section' : ($isFormula ? 'formula-section' : ($isAuto ? 'auto-section' : ''));
              $refCode = chr(65 + $sectionIndex) . ($criterionIndex + 1);
            ?>
            <div class="section-row mb-3 p-3 rounded <?= $rowClass ?>"
                 data-section-id="<?= $section['id'] ?>"
                 data-section-type="<?= $sectionType ?>"
                 data-formula='<?= htmlspecialchars($section['formula_config'] ?? '{}') ?>'>
              
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold mb-0">
                  <span class="section-ref-badge"><?= $refCode ?></span>
                  <?= htmlspecialchars($section['section_name']) ?>
                  <?php if($isHelper): ?>
                    <span class="badge bg-secondary"><i class="fas fa-database"></i> حقل مساعد</span>
                  <?php elseif($isFormula): ?>
                    <span class="badge bg-danger"><i class="fas fa-calculator"></i> صيغة حسابية</span>
                  <?php elseif($isAuto): ?>
                    <span class="badge bg-info"><i class="fas fa-cog"></i> تلقائي</span>
                  <?php endif; ?>
                </label>
                <?php if(!$isHelper && !$isFormula && !$isAuto && !empty($section['section_weight'])): ?>
                  <span class="badge bg-outline-secondary"><?= $section['section_weight'] ?>%</span>
                <?php endif; ?>
              </div>

              <?php if($sectionType === 'percentage'): ?>
                <input type="number" name="answers[<?= $section['id'] ?>]" 
                       class="form-control section-input"
                       min="0" max="100" step="0.01"
                       placeholder="أدخل النسبة (0-100)">

              <?php elseif($sectionType === 'numeric'): ?>
                <input type="number" name="answers[<?= $section['id'] ?>]"
                       class="form-control section-input"
                       min="<?= $section['min_value'] ?? 0 ?>"
                       max="<?= $section['max_value'] ?? 100 ?>"
                       step="0.01"
                       placeholder="أدخل القيمة">

              <?php elseif($sectionType === 'grade'): ?>
                <select name="answers[<?= $section['id'] ?>]" class="form-select section-input">
                  <option value="">-- اختر التقدير --</option>
                  <?php
                  $options = json_decode($section['evaluation_options'] ?? '[]', true);
                  foreach($options as $option):
                    $val = is_array($option) ? ($option['value'] ?? '') : $option;
                    $lbl = is_array($option) ? ($option['label'] ?? $val) : $option;
                  ?>
                    <option value="<?= $val ?>"><?= htmlspecialchars($lbl) ?></option>
                  <?php endforeach; ?>
                </select>

              <?php elseif($sectionType === 'boolean'): ?>
                <div class="d-flex gap-4">
                  <div class="form-check">
                    <input type="radio" name="answers[<?= $section['id'] ?>]" value="1" class="form-check-input section-input" id="yes_<?= $section['id'] ?>">
                    <label class="form-check-label" for="yes_<?= $section['id'] ?>">نعم</label>
                  </div>
                  <div class="form-check">
                    <input type="radio" name="answers[<?= $section['id'] ?>]" value="0" class="form-check-input section-input" id="no_<?= $section['id'] ?>">
                    <label class="form-check-label" for="no_<?= $section['id'] ?>">لا</label>
                  </div>
                </div>

              <?php elseif($sectionType === 'helper'): ?>
                <input type="number" name="answers[<?= $section['id'] ?>]"
                       class="form-control section-input helper-input"
                       min="<?= $section['min_value'] ?? 0 ?>"
                       max="<?= $section['max_value'] ?? 999999 ?>"
                       step="any"
                       placeholder="أدخل قيمة مساعدة (تُستخدم في الصيغ الحسابية)"
                       data-section-id="<?= $section['id'] ?>">
                <small class="text-muted"><i class="fas fa-info-circle"></i> هذا الحقل لا يُحتسب في وزن الاستمارة - مخصص كمدخل للصيغ الحسابية</small>

              <?php elseif($sectionType === 'formula'): ?>
                <input type="text" readonly
                       class="form-control bg-light formula-result"
                       data-section-id="<?= $section['id'] ?>"
                       data-formula-config='<?= htmlspecialchars($section['formula_config'] ?? '{}') ?>'
                       value="0"
                       placeholder="يُحسب تلقائياً">
                <small class="text-muted"><i class="fas fa-calculator"></i> الصيغة: <?= htmlspecialchars($sectionConfig['formula'] ?? '') ?></small>

              <?php elseif($sectionType === 'auto'): ?>
                <input type="text" readonly
                       class="form-control bg-light auto-result"
                       data-auto-source="<?= htmlspecialchars($sectionConfig['auto_source'] ?? '') ?>"
                       data-section-id="<?= $section['id'] ?>"
                       value=""
                       placeholder="يُملأ تلقائياً من النظام">
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php else: ?>
      <div class="alert alert-warning">القالب المحدد لا يحتوي على أقسام. يرجى اختيار قالب آخر.</div>
    <?php endif; ?>

    <div class="mb-4">
      <label class="form-label fw-bold">ملاحظات</label>
      <textarea name="notes" class="form-control" rows="3" placeholder="أضف ملاحظات التقرير هنا..."></textarea>
    </div>

    <div style="text-align:center;">
      <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save"></i> حفظ التقرير</button>
      <a href="<?= APP_URL ?>/admin/reports" class="btn btn-secondary btn-lg me-2">إلغاء</a>
    </div>
  </form>
</div></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // فلترة الكيان حسب النوع
    const entityTypeSelect = document.getElementById('entity_type');
    const entitySelect = document.getElementById('entity-select');
    function filterEntity() {
        const v = entityTypeSelect.value;
        entitySelect.querySelectorAll('option[data-type="branch"]').forEach(o => o.style.display = v === 'branch' ? '' : 'none');
        entitySelect.querySelectorAll('option[data-type="department"]').forEach(o => o.style.display = v === 'department' ? '' : 'none');
        entitySelect.value = '';
    }
    entityTypeSelect.addEventListener('change', filterEntity);
    filterEntity();

    // حساب الصيغ تلقائياً
    function calculateAllFormulas() {
        const values = {};
        document.querySelectorAll('.section-row').forEach(row => {
            const sectionId = row.dataset.sectionId;
            const input = row.querySelector('.section-input, .helper-input');
            if (input && input.name) {
                values[sectionId] = parseFloat(input.value) || 0;
            }
        });

        // حساب الأقسام التلقائية
        document.querySelectorAll('.auto-result').forEach(el => {
            const source = el.dataset.autoSource;
            if (source === 'actual_force') {
                el.value = document.getElementById('actual_force_input')?.value || 0;
            } else if (source === 'total') {
                let total = 0;
                Object.values(values).forEach(v => total += parseFloat(v) || 0);
                el.value = total.toFixed(2);
            }
        });

        // حساب الصيغ
        document.querySelectorAll('.formula-result').forEach(formulaEl => {
            const config = JSON.parse(formulaEl.dataset.formulaConfig || '{}');
            if (!config.formula) return;
            
            let formula = config.formula.replace(/^=/, '');
            
            // استبدال مراجع الأعمدة بالقيم
            const sectionRows = document.querySelectorAll('.section-row');
            sectionRows.forEach(row => {
                const sId = row.dataset.sectionId;
                const input = row.querySelector('.section-input, .helper-input');
                const val = input ? (parseFloat(input.value) || 0) : 0;
                
                // تحديد العمود بناءً على الموقع
                let colIndex = 0;
                let criterionIndex = 0;
                const allSections = Array.from(sectionRows);
                const myIndex = allSections.indexOf(row);
                
                // حساب العمود والصف من الموضع
                let tempCriterion = null;
                let tempCol = 0;
                let tempRow = 0;
                for (let i = 0; i <= myIndex; i++) {
                    const r = allSections[i];
                    if (r.parentElement.closest('.criterion-block') !== tempCriterion) {
                        tempCriterion = r.parentElement.closest('.criterion-block');
                        tempRow++;
                        tempCol = 0;
                    }
                    tempCol++;
                    if (i === myIndex) {
                        const colLetter = String.fromCharCode(64 + tempCol);
                        const rowNum = tempRow;
                        const regex = new RegExp(colLetter + rowNum, 'g');
                        formula = formula.replace(regex, val);
                    }
                }
            });

            // حساب التعبير
            try {
                const sanitized = formula.replace(/[^0-9+\-*/().\s]/g, '');
                if (sanitized.trim()) {
                    const result = Function('"use strict"; return (' + sanitized + ')')();
                    formulaEl.value = isNaN(result) ? 0 : parseFloat(result.toFixed(4));
                }
            } catch(e) {
                formulaEl.value = 'خطأ';
            }
        });
    }

    // ربط الأحداث
    document.querySelectorAll('.section-input, .helper-input, #actual_force_input').forEach(input => {
        input.addEventListener('input', calculateAllFormulas);
        input.addEventListener('change', calculateAllFormulas);
    });

    // حساب أولي
    calculateAllFormulas();
});
</script>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
