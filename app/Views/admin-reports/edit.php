<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/archive.css">
<style>
    .section-row.helper-section { border-left: 3px solid #6c757d; background: #f8f9fa; }
    .section-row.formula-section { border-left: 3px solid #dc3545; background: #fff5f5; }
    .section-row.auto-section { border-left: 3px solid #0dcaf0; background: #f0fbff; }
    .criterion-block { border: 2px solid #e9ecef; border-radius: 10px; }
    .criterion-header { background: linear-gradient(135deg, #0d5e52, #0a4a42); color: white; border-radius: 8px 8px 0 0; padding: 12px 15px; }
</style>

<div class="eval-wrap"><div class="eval-container">
  <div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
      <div class="page-title"><div class="page-title-icon"><i class="fas fa-edit"></i></div> <?= $pageTitle ?></div>
      <a href="<?= APP_URL ?>/admin/reports/show/<?= $report->id ?>" class="btn btn-light border"><i class="fas fa-arrow-right"></i> العودة</a>
    </div>
  </div>

  <form method="POST" action="<?= APP_URL ?>/admin/reports/update/<?= $report->id ?>" id="reportForm">
    <?= csrf_field() ?>

    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-bold">عنوان التقرير *</label>
            <input type="text" name="report_title" class="form-control" required value="<?= htmlspecialchars($report->report_title ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">القالب</label>
            <input type="text" class="form-control" disabled value="<?= htmlspecialchars($template['template_name'] ?? '') ?>">
            <input type="hidden" name="template_id" value="<?= $report->template_id ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">الحالة</label>
            <select name="status" class="form-select">
              <?php foreach(['draft'=>'مسودة','pending_review'=>'قيد المراجعة','completed'=>'مكتمل','approved'=>'معتمد'] as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $report->status === $val ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-3">
            <label class="form-label fw-bold">نوع الفترة *</label>
            <select name="period_type" class="form-select" required>
              <?php foreach(['monthly'=>'شهري','quarterly'=>'ربع سنوي','semiannual'=>'نصف سنوي','annual'=>'سنوي'] as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= ($report->period_type ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">من تاريخ *</label>
            <input type="date" name="period_start" class="form-control" required value="<?= $report->period_start ?? '' ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">إلى تاريخ *</label>
            <input type="date" name="period_end" class="form-control" required value="<?= $report->period_end ?? '' ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">القوة الفعلية</label>
            <input type="number" name="actual_force" class="form-control" min="0" value="<?= $report->actual_force ?? 0 ?>" id="actual_force_input">
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-3">
            <label class="form-label fw-bold">القوة المطبقه</label>
            <input type="number" name="applied_force" class="form-control" min="0" value="<?= $report->applied_force ?? 0 ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">نسبة المطابقة</label>
            <div class="form-control bg-light"><?= round($report->compliance_rate ?? 0, 1) ?>%</div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">المقيّمون</label>
            <div class="bg-light p-2 rounded">
              <?php if(!empty($evaluators)): ?>
                <?php foreach($evaluators as $ev): ?>
                  <span class="badge bg-primary me-1"><?= htmlspecialchars($ev->full_name ?? '') ?></span>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="text-muted">لا يوجد مقيّمون</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- أقسام التقييم -->
    <?php if(!empty($template['criteria'])): ?>
    <div class="card mb-4">
      <div class="card-header criterion-header">
        <h5 class="mb-0"><i class="fas fa-list"></i> أقسام التقييم</h5>
      </div>
      <div class="card-body" id="sections-container">
        <?php foreach($template['criteria'] as $criterionIndex => $criterion): ?>
        <div class="criterion-block mb-4" data-criterion-id="<?= $criterion['id'] ?>">
          <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-top">
            <h6 class="text-success mb-0">
              <span class="section-ref-badge"><?= $criterionIndex + 1 ?></span> <?= htmlspecialchars($criterion['criterion_name']) ?>
            </h6>
            <span class="badge bg-success">وزن: <?= $criterion['criterion_weight'] ?>%</span>
          </div>
          <div class="p-3">
            <?php foreach($criterion['sections'] as $sectionIndex => $section):
              $sectionType = $section['evaluation_type'] ?? '';
              $sectionConfig = json_decode($section['formula_config'] ?? '{}', true);
              $isHelper = ($sectionType === 'helper');
              $isFormula = ($sectionType === 'formula');
              $isAuto = ($sectionType === 'auto');
              $rowClass = $isHelper ? 'helper-section' : ($isFormula ? 'formula-section' : ($isAuto ? 'auto-section' : ''));
              $savedValue = $answersMap[$section['id']] ?? '';
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
                       value="<?= htmlspecialchars($savedValue) ?>">

              <?php elseif($sectionType === 'numeric'): ?>
                <input type="number" name="answers[<?= $section['id'] ?>]"
                       class="form-control section-input"
                       min="<?= $section['min_value'] ?? 0 ?>"
                       max="<?= $section['max_value'] ?? 100 ?>"
                       step="0.01"
                       value="<?= htmlspecialchars($savedValue) ?>">

              <?php elseif($sectionType === 'grade'): ?>
                <select name="answers[<?= $section['id'] ?>]" class="form-select section-input">
                  <option value="">-- اختر التقدير --</option>
                  <?php
                  $options = json_decode($section['evaluation_options'] ?? '[]', true);
                  foreach($options as $option):
                    $val = is_array($option) ? ($option['value'] ?? '') : $option;
                    $lbl = is_array($option) ? ($option['label'] ?? $val) : $option;
                  ?>
                    <option value="<?= $val ?>" <?= ((string)$val === (string)$savedValue) ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                  <?php endforeach; ?>
                </select>

              <?php elseif($sectionType === 'boolean'): ?>
                <div class="d-flex gap-4">
                  <div class="form-check">
                    <input type="radio" name="answers[<?= $section['id'] ?>]" value="1" class="form-check-input section-input" id="yes_<?= $section['id'] ?>" <?= $savedValue == '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="yes_<?= $section['id'] ?>">نعم</label>
                  </div>
                  <div class="form-check">
                    <input type="radio" name="answers[<?= $section['id'] ?>]" value="0" class="form-check-input section-input" id="no_<?= $section['id'] ?>" <?= $savedValue == '0' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="no_<?= $section['id'] ?>">لا</label>
                  </div>
                </div>

              <?php elseif($sectionType === 'helper'): ?>
                <input type="number" name="answers[<?= $section['id'] ?>]"
                       class="form-control section-input helper-input"
                       min="<?= $section['min_value'] ?? 0 ?>"
                       max="<?= $section['max_value'] ?? 999999 ?>"
                       step="any"
                       value="<?= htmlspecialchars($savedValue) ?>"
                       data-section-id="<?= $section['id'] ?>">
                <small class="text-muted"><i class="fas fa-info-circle"></i> حقل مساعد - لا يُحتسب في الوزن</small>

              <?php elseif($sectionType === 'formula'): ?>
                <input type="text" readonly
                       class="form-control bg-light formula-result"
                       data-section-id="<?= $section['id'] ?>"
                       data-formula-config='<?= htmlspecialchars($section['formula_config'] ?? '{}') ?>'
                       value="<?= htmlspecialchars($savedValue) ?>">
                <small class="text-muted"><i class="fas fa-calculator"></i> الصيغة: <?= htmlspecialchars($sectionConfig['formula'] ?? '') ?></small>

              <?php elseif($sectionType === 'auto'): ?>
                <input type="text" readonly
                       class="form-control bg-light auto-result"
                       data-auto-source="<?= htmlspecialchars($sectionConfig['auto_source'] ?? '') ?>"
                       data-section-id="<?= $section['id'] ?>"
                       value="<?= htmlspecialchars($savedValue) ?>">
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="mb-4">
      <label class="form-label fw-bold">ملاحظات</label>
      <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($report->notes ?? '') ?></textarea>
    </div>

    <div style="text-align:center;">
      <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save"></i> تحديث التقرير</button>
      <a href="<?= APP_URL ?>/admin/reports/show/<?= $report->id ?>" class="btn btn-secondary btn-lg me-2">إلغاء</a>
    </div>
  </form>
</div></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function calculateAllFormulas() {
        const values = {};
        document.querySelectorAll('.section-row').forEach(row => {
            const sectionId = row.dataset.sectionId;
            const input = row.querySelector('.section-input, .helper-input');
            if (input && input.name) {
                values[sectionId] = parseFloat(input.value) || 0;
            }
        });

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

        document.querySelectorAll('.formula-result').forEach(formulaEl => {
            const config = JSON.parse(formulaEl.dataset.formulaConfig || '{}');
            if (!config.formula) return;
            
            let formula = config.formula.replace(/^=/, '');
            const sectionRows = document.querySelectorAll('.section-row');
            
            sectionRows.forEach(row => {
                const sId = row.dataset.sectionId;
                const input = row.querySelector('.section-input, .helper-input');
                const val = input ? (parseFloat(input.value) || 0) : 0;
                
                let tempCriterion = null;
                let tempCol = 0;
                let tempRow = 0;
                const allSections = Array.from(sectionRows);
                const myIndex = allSections.indexOf(row);
                
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
                        const regex = new RegExp(colLetter + tempRow, 'g');
                        formula = formula.replace(regex, val);
                    }
                }
            });

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

    document.querySelectorAll('.section-input, .helper-input, #actual_force_input').forEach(input => {
        input.addEventListener('input', calculateAllFormulas);
        input.addEventListener('change', calculateAllFormulas);
    });

    calculateAllFormulas();
});
</script>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
