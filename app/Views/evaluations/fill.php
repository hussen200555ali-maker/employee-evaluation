<?php 
// ملف: app/Views/evaluations/fill.php (v5.0 - مع نتيجة القسم)
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<style>
/* ... (جميع التنسيقات السابقة تبقى كما هي) ... */
/* =============================================================== */
/*        Interactive Evaluation Dashboard Styles (v3.0)         */
/* =============================================================== */

/* 1. إعدادات الصفحة العامة */
body {
    background-color: #f4f7f9;
}

/* تنسيقات الأقسام الجديدة */
.section-entry.helper-section { border-left: 4px solid #6c757d; background: #f8f9fa; }
.section-entry.formula-section { border-left: 4px solid #dc3545; background: #fff5f5; }
.section-entry.auto-section { border-left: 4px solid #0dcaf0; background: #f0fbff; }

/* 2. حاوية التخطيط الرئيسية (العمودين) */
.evaluation-layout {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
}

/* 3. الشريط الجانبي للتنقل (قائمة المعايير) */
.evaluation-sidebar {
    position: sticky;
    top: 20px;
    width: 280px;
    flex-shrink: 0;
}

.navigation-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    overflow: hidden;
}

.navigation-card .card-header {
    background-color: #343a40;
    color: #fff;
    padding: 15px 20px;
    font-weight: 600;
}

.navigation-card .list-group-item {
    border: none;
    padding: 12px 20px;
    font-size: 0.95rem;
    color: #495057;
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
}

.navigation-card .list-group-item:hover {
    background-color: #f8f9fa;
    color: #0d6efd;
}

.navigation-card .list-group-item.active {
    background-color: #e9ecef;
    color: #0d6efd;
    font-weight: bold;
    border-left-color: #0d6efd;
}

/* 4. منطقة المحتوى الرئيسية */
.evaluation-content {
    flex-grow: 1;
}

/* 5. بطاقة معلومات التقييم */
.evaluation-details-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 20px;
    margin-bottom: 2rem;
}
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.info-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    font-size: 0.9rem;
}

/* 6. تصميم بطاقة المعيار (Accordion) */
.criterion-accordion .accordion-item {
    margin-bottom: 1.5rem;
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border: none;
    overflow: hidden;
}

.criterion-accordion .accordion-button {
    font-size: 1.2rem;
    font-weight: 600;
    background-color: #fff;
    color: #343a40;
    padding: 1rem 1.5rem;
}

.criterion-accordion .accordion-button:not(.collapsed) {
    background-color: #0d6efd;
    color: #fff;
    box-shadow: none;
}

.criterion-accordion .accordion-button:focus {
    box-shadow: none;
}

.criterion-accordion .accordion-body {
    padding: 0;
}

/* 7. تصميم قسم الإدخال */
.section-entry {
    padding: 1.5rem;
    transition: background-color 0.2s ease;
}
.section-entry:not(:last-child) {
    border-bottom: 1px solid #e9ecef;
}
.section-entry-header h6 {
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.section-entry-header p {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

/* 8. شريط الإجراءات السفلي */
.evaluation-action-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    box-shadow: 0 -4px 15px rgba(0,0,0,0.1);
    padding: 1rem 0;
    z-index: 1030;
}

/* 9. تنسيقات لوحة النتائج الحية */
.live-scoreboard {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
}
.scoreboard-header {
    background-color: #198754;
    color: #fff;
    padding: 15px 20px;
    font-weight: 600;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}
.total-score-display {
    text-align: center;
    padding: 20px;
    font-size: 3rem;
    font-weight: 700;
    color: #198754;
    border-bottom: 1px solid #e9ecef;
    transition: color 0.3s ease;
}
.total-score-display small {
    font-size: 1rem;
    font-weight: 500;
    color: #6c757d;
    display: block;
}
.criteria-scores-list {
    list-style: none;
    padding: 10px 0;
    margin: 0;
}
.criteria-scores-list li {
    display: flex;
    justify-content: space-between;
    padding: 8px 20px;
    font-size: 0.9rem;
}
.criteria-scores-list li .score {
    font-weight: bold;
    color: #495057;
}

/* 10. تنسيق شارة نتيجة المعيار */
.accordion-button .criterion-score-badge {
    margin-right: auto;
    margin-left: 1rem;
    font-size: 0.9rem;
    font-weight: normal;
    background-color: rgba(255,255,255,0.2);
    padding: 0.3em 0.6em;
    border-radius: 4px;
}
.accordion-button.collapsed .criterion-score-badge {
    background-color: #e9ecef;
    color: #495057;
}

/* ▼▼▼ 11. تنسيق جديد لنتيجة القسم ▼▼▼ */
.section-score-display {
    font-size: 0.8rem;
    color: #198754; /* أخضر */
    font-weight: bold;
    margin-top: 0.5rem;
    display: block;
    text-align: left;
}
/* ▲▲▲ نهاية التنسيق الجديد ▲▲▲ */

/* طبقة مظهر موحدة لصفحة التقييم: تقلل الكتل الثقيلة وتبرز بيانات القرار والإدخال. */
.evaluation-layout { gap: 1.25rem; }
.evaluation-sidebar { top: 6.4rem; width: 17rem; }
.navigation-card, .evaluation-details-card, .live-scoreboard { border: 1px solid var(--line); border-radius: 15px; box-shadow: var(--shadow-card); }
.navigation-card .card-header { background: linear-gradient(120deg, var(--brand-deep), var(--brand)); padding: .95rem 1.1rem; }
.navigation-card .list-group-item { color: #49605a; border-left: 0; border-right: 3px solid transparent; padding: .7rem 1rem; }
.navigation-card .list-group-item:hover, .navigation-card .list-group-item.active { color: var(--brand); background: var(--brand-soft); border-right-color: var(--brand); border-left-color: transparent; }
.evaluation-details-card { padding: 1.3rem; margin-bottom: 1.25rem; background: linear-gradient(180deg, #fff, #fbfdfc); }
.info-grid { gap: .6rem 1.2rem; }.info-item { padding: .5rem 0; border-bottom: 1px dashed #dfeae5; color: #65756f; }.info-item strong { color: var(--ink); }
.criterion-accordion .accordion-item { margin-bottom: 1rem; border: 1px solid var(--line); border-radius: 15px !important; box-shadow: var(--shadow-card); }
.criterion-accordion .accordion-button { color: var(--ink); padding: 1.05rem 1.2rem; font-size: 1rem; }.criterion-accordion .accordion-button:not(.collapsed) { background: var(--brand-soft); color: var(--brand-deep); }.criterion-accordion .accordion-button::after { margin-right: auto; margin-left: 0; }.accordion-button .criterion-score-badge, .accordion-button.collapsed .criterion-score-badge { color: var(--brand); background: rgba(13,94,82,.09); border-radius: 999px; font-weight: 800; }
.section-entry { padding: 1.25rem; }.section-entry:hover { background: #fbfdfc; }.section-entry-header h6 { color: var(--ink); font-weight: 800; }.section-entry-header p { color: #73837e; }.section-score-display { color: var(--brand); text-align: right; }
.live-scoreboard { overflow: hidden; }.scoreboard-header { background: linear-gradient(120deg, var(--brand-deep), var(--brand)); padding: .9rem 1.1rem; }.total-score-display { color: var(--brand); font-size: 2.65rem; padding: 1.15rem; background: #f6fbf8; }.criteria-scores-list li { padding: .65rem 1.1rem; border-bottom: 1px solid #edf3f0; }.criteria-scores-list li .score { color: var(--brand); }
.evaluation-action-bar { background: rgba(255,255,255,.95); border-top: 1px solid var(--line); box-shadow: 0 -8px 24px rgba(22,48,43,.08); }.evaluation-action-bar .btn { min-width: 9rem; }
@media (max-width: 991.98px) { .evaluation-layout { display: block; }.evaluation-sidebar { position: static; width: auto; margin-bottom: 1rem; }.navigation-card .list-group { display: flex; overflow-x: auto; }.navigation-card .list-group-item { white-space: nowrap; border-right: 0; border-bottom: 3px solid transparent; }.navigation-card .list-group-item.active { border-bottom-color: var(--brand); }.evaluation-action-bar { position: sticky; bottom: 0; } }
</style>

<form action="<?php echo APP_URL; ?>/evaluations/submit" method="POST" id="evaluation-form">
    <?= csrf_field() ?>
    <input type="hidden" name="evaluation_id" value="<?php echo $evaluation->id; ?>">

    <div class="container mt-4">
        <div class="evaluation-layout">

            <aside class="evaluation-sidebar d-none d-lg-block">
                <div class="live-scoreboard">
                    <div class="scoreboard-header">
                        <i class="fas fa-tachometer-alt"></i> النتيجة المباشرة
                    </div>
                    <div class="total-score-display" id="total-score-display">
                        0.00<small>%</small>
                    </div>
                    <ul class="criteria-scores-list" id="criteria-scores-list">
                        <!-- JavaScript will populate this -->
                    </ul>
                </div>

                <div class="navigation-card">
                    <div class="card-header">
                        <i class="fas fa-list-ul"></i> معايير التقييم
                    </div>
                    <div class="list-group list-group-flush" id="criteria-navigation">
                        <?php foreach ($template['criteria'] as $criterion_index => $criterion): ?>
                            <a href="#criterion-<?php echo $criterion['id']; ?>" class="list-group-item list-group-item-action">
                                <?php echo ($criterion_index + 1) . '. ' . htmlspecialchars($criterion['criterion_name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>

            <main class="evaluation-content">
                
                <section class="evaluation-details-card">
                    <div class="info-grid">
                        <div class="info-item"><span>الموظف:</span> <strong><?php echo htmlspecialchars($evaluation->employee_name); ?></strong></div>
                        <div class="info-item"><span>القائم بالتقييم:</span> <strong><?php echo htmlspecialchars($evaluation->evaluator_name ?? $evaluator->full_name ?? 'غير محدد'); ?></strong></div>
                        <div class="info-item"><span>صفة المقيّم:</span> <strong><?php echo htmlspecialchars($evaluation->evaluator_title ?? $evaluator->position_title ?? ''); ?></strong></div>
                        <div class="info-item"><span>المنصب:</span> <strong><?php echo htmlspecialchars($evaluation->position_name); ?></strong></div>
                        <div class="info-item"><span>تاريخ التقييم:</span>  <strong>
                                <center><?php
                                    $hijriDate = convertToHijri($evaluation->evaluation_date);
                                    echo date('d-m-Y', strtotime($evaluation->evaluation_date)) . ' م';
                                    echo "  
" ;
                                    if ($hijriDate) {
                                        echo htmlspecialchars($hijriDate->full_date);
                                    }
                                ?>
                                </center> 
                            </strong>
                          </div>
                    </div>
                </section>

                <div class="accordion criterion-accordion" id="criteriaAccordion">
                    <?php foreach ($template['criteria'] as $criterion_index => $criterion): ?>
                        <div class="accordion-item" id="criterion-<?php echo $criterion['id']; ?>">
                            <h2 class="accordion-header" id="heading-<?php echo $criterion['id']; ?>">
                                <button class="accordion-button <?php echo $criterion_index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $criterion['id']; ?>">
                                    <?php echo ($criterion_index + 1) . '. ' . htmlspecialchars($criterion['criterion_name']); ?>
                                    <span class="criterion-score-badge" id="criterion-score-<?php echo $criterion['id']; ?>">0.0 / <?php echo number_format((float)$criterion['criterion_weight'], 1); ?></span>
                                </button>
                            </h2>
                            <div id="collapse-<?php echo $criterion['id']; ?>" class="accordion-collapse collapse <?php echo $criterion_index == 0 ? 'show' : ''; ?>">
                                <div class="accordion-body">
                                    <?php foreach ($criterion['sections'] as $section_index => $section): ?>
                                        <?php
                                            $fieldName = "results[{$criterion['id']}][{$section['id']}]";
                                            $savedAnswer = $savedAnswers[$section['id']] ?? null;
                                        ?>
                                        <div class="section-entry" 
                                             data-section-id="<?php echo $section['id']; ?>"
                                             data-criterion-id="<?php echo $criterion['id']; ?>">
                                            
                                            <div class="section-entry-header">
                                                <h6><?php echo ($criterion_index + 1) . '.' . ($section_index + 1) . ' ' . htmlspecialchars($section['section_name']); ?></h6>
                                                <p><?php echo htmlspecialchars($section['section_description']); ?></p>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <label class="form-label fw-bold small">التقييم:</label>
                                                    <?php
                                                    $sectionType = $section['evaluation_type'] ?? '';
                                                    $sectionConfig = json_decode($section['formula_config'] ?? '{}', true);
                                                    $isHelper = ($sectionType === 'helper');
                                                    $isFormula = ($sectionType === 'formula');
                                                    $isAuto = ($sectionType === 'auto');
                                                    $rowClass = $isHelper ? 'helper-section' : ($isFormula ? 'formula-section' : ($isAuto ? 'auto-section' : ''));
                                                    ?>
                                                    <?php if ($sectionType === 'numeric'): ?>
                                                        <input type="number" class="form-control evaluation-input" name="<?php echo $fieldName; ?>[score]" min="<?php echo $section['min_value']; ?>" max="<?php echo $section['max_value']; ?>" step="1" placeholder="أدخل الدرجة" value="<?php echo htmlspecialchars($savedAnswer->raw_value ?? ''); ?>">
                                                    <?php elseif ($sectionType === 'percentage'): ?>
                                                        <div class="input-group"><input type="number" class="form-control evaluation-input" name="<?php echo $fieldName; ?>[score]" min="0" max="100" step="1" placeholder="أدخل النسبة" value="<?php echo htmlspecialchars($savedAnswer->raw_value ?? ''); ?>"><span class="input-group-text">%</span></div>
                                                    <?php elseif ($sectionType === 'grade'): ?>
                                                        <select class="form-select evaluation-input" name="<?php echo $fieldName; ?>[score]">
                                                            <option value="">-- اختر التقدير --</option>
                                                            <?php 
                                                                $savedValue = $savedAnswer->raw_value ?? null;
                                                                $options = is_string($section['evaluation_options']) ? json_decode($section['evaluation_options']) : $section['evaluation_options'];
                                                                if (is_array($options) || is_object($options)) {
                                                                    foreach ($options as $option) {
                                                                        $isSelected = ($savedValue !== null && $option->value == $savedValue) ? 'selected' : '';
                                                                        echo "<option value='" . htmlspecialchars($option->value) . "' {$isSelected}>" . htmlspecialchars($option->label) . "</option>";
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    <?php elseif ($sectionType === 'boolean'): ?>
                                                        <div class="d-flex gap-4">
                                                            <div class="form-check">
                                                                <input type="radio" name="<?php echo $fieldName; ?>[score]" value="1" class="form-check-input evaluation-input" id="yes_<?php echo $section['id']; ?>" <?= ($savedAnswer->raw_value ?? '') == '1' ? 'checked' : '' ?>>
                                                                <label class="form-check-label" for="yes_<?php echo $section['id']; ?>">نعم</label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input type="radio" name="<?php echo $fieldName; ?>[score]" value="0" class="form-check-input evaluation-input" id="no_<?php echo $section['id']; ?>" <?= ($savedAnswer->raw_value ?? '') == '0' ? 'checked' : '' ?>>
                                                                <label class="form-check-label" for="no_<?php echo $section['id']; ?>">لا</label>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($sectionType === 'helper'): ?>
                                                        <input type="number" name="<?php echo $fieldName; ?>[score]"
                                                               class="form-control evaluation-input helper-input"
                                                               min="<?= $section['min_value'] ?? 0 ?>"
                                                               max="<?= $section['max_value'] ?? 999999 ?>"
                                                               step="any"
                                                               placeholder="أدخل قيمة مساعدة"
                                                               value="<?php echo htmlspecialchars($savedAnswer->raw_value ?? ''); ?>"
                                                               data-section-id="<?= $section['id'] ?>">
                                                        <small class="text-muted"><i class="fas fa-info-circle"></i> حقل مساعد - لا يُحتسب في الوزن</small>
                                                    <?php elseif ($sectionType === 'formula'): ?>
                                                        <input type="text" readonly
                                                               class="form-control bg-light formula-result"
                                                               data-section-id="<?= $section['id'] ?>"
                                                               data-formula-config='<?= htmlspecialchars($section['formula_config'] ?? '{}') ?>'
                                                               value="<?php echo htmlspecialchars($savedAnswer->raw_value ?? '0'); ?>">
                                                        <small class="text-muted"><i class="fas fa-calculator"></i> الصيغة: <?= htmlspecialchars($sectionConfig['formula'] ?? '') ?></small>
                                                    <?php elseif ($sectionType === 'auto'): ?>
                                                        <input type="text" readonly
                                                               class="form-control bg-light auto-result"
                                                               data-auto-source="<?= htmlspecialchars($sectionConfig['auto_source'] ?? '') ?>"
                                                               data-section-id="<?= $section['id'] ?>"
                                                               value="<?php echo htmlspecialchars($savedAnswer->raw_value ?? ''); ?>">
                                                        <small class="text-muted"><i class="fas fa-cog"></i> يُملأ تلقائياً من النظام</small>
                                                    <?php else: ?>
                                                        <input type="text" class="form-control evaluation-input" name="<?php echo $fieldName; ?>[score]" placeholder="أدخل القيمة" value="<?php echo htmlspecialchars($savedAnswer->raw_value ?? ''); ?>">
                                                    <?php endif; ?>
                                                    
                                                    <!-- ▼▼▼ إضافة مكان عرض نتيجة القسم ▼▼▼ -->
                                                    <small class="section-score-display" id="section-score-<?php echo $section['id']; ?>">
                                                        الدرجة: 0.00 / <?php echo number_format((float)$section['section_weight'], 2); ?>
                                                    </small>
                                                    <!-- ▲▲▲ نهاية مكان عرض النتيجة ▲▲▲ -->
                                                </div>
                                                <div class="col-md-7">
                                                    <label class="form-label fw-bold small">الملاحظات والشواهد:</label>
                                                    <textarea class="form-control" name="<?php echo $fieldName; ?>[notes]" rows="2" placeholder="اكتب ملاحظاتك هنا..."><?php echo htmlspecialchars($savedAnswer->notes ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <div class="evaluation-action-bar">
        <div class="container">
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary btn-lg shadow-lg">
                    <i class="fas fa-check-circle me-2"></i>حفظ وإرسال التقييم
                </button>
            </div>
        </div>
    </div>
</form>

<div style="height: 120px;"></div>

<script>
    const evaluationTemplate = <?php echo json_encode($template, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>;
</script>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('evaluation-form');
    const totalScoreDisplay = document.getElementById('total-score-display');
    const criteriaScoresList = document.getElementById('criteria-scores-list');
    const inputs = form.querySelectorAll('.evaluation-input');

    function calculateScores() {
        let overallScore = 0;
        const criteriaScores = {};
        const values = {};

        evaluationTemplate.criteria.forEach(criterion => {
            criteriaScores[criterion.id] = { score: 0, weight: parseFloat(criterion.criterion_weight) };
        });

        // جمع القيم أولاً
        document.querySelectorAll('.section-entry').forEach(entry => {
            const sectionId = entry.dataset.sectionId;
            const input = entry.querySelector('.evaluation-input, .helper-input');
            if (input && input.name) {
                values[sectionId] = parseFloat(input.value) || 0;
            }
        });

        // حساب الأقسام التلقائية
        document.querySelectorAll('.auto-result').forEach(el => {
            const source = el.dataset.autoSource;
            const sectionId = el.dataset.sectionId;
            if (source === 'actual_force') {
                el.value = document.querySelector('input[name="actual_force"]')?.value || 0;
            } else if (source === 'total') {
                let total = 0;
                Object.values(values).forEach(v => total += parseFloat(v) || 0);
                el.value = total.toFixed(2);
            }
            values[sectionId] = parseFloat(el.value) || 0;
        });

        // حساب الصيغ
        document.querySelectorAll('.formula-result').forEach(formulaEl => {
            const config = JSON.parse(formulaEl.dataset.formulaConfig || '{}');
            const sectionId = formulaEl.dataset.sectionId;
            if (!config.formula) return;
            
            let formula = config.formula.replace(/^=/, '');
            const sectionRows = document.querySelectorAll('.section-entry');
            
            sectionRows.forEach(row => {
                const sId = row.dataset.sectionId;
                const input = row.querySelector('.evaluation-input, .helper-input');
                const val = input ? (parseFloat(input.value) || 0) : (values[sId] || 0);
                
                let tempCriterion = null;
                let tempCol = 0;
                let tempRow = 0;
                const allSections = Array.from(sectionRows);
                const myIndex = allSections.indexOf(row);
                
                for (let i = 0; i <= myIndex; i++) {
                    const r = allSections[i];
                    if (r.parentElement.closest('.accordion-item') !== tempCriterion) {
                        tempCriterion = r.parentElement.closest('.accordion-item');
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
                    values[sectionId] = parseFloat(formulaEl.value) || 0;
                }
            } catch(e) {
                formulaEl.value = 'خطأ';
            }
        });

        inputs.forEach(input => {
            const sectionEntry = input.closest('.section-entry');
            if (!sectionEntry) return;

            const sectionId = sectionEntry.dataset.sectionId;
            const criterionId = sectionEntry.dataset.criterionId;
            
            const criterion = evaluationTemplate.criteria.find(c => c.id == criterionId);
            if (!criterion) return;

            const section = criterion.sections.find(s => s.id == sectionId);
            if (!section) return;

            let rawValue = input.value;
            let normalizedScore = 0;
            const type = section.evaluation_type;

            // تخطي الأقسام غير القابلة للحساب (helper, formula, auto)
            if (type === 'helper' || type === 'formula' || type === 'auto') {
                return;
            }

            if (rawValue !== '' && !isNaN(rawValue)) {
                rawValue = parseFloat(rawValue);

                if (type === 'numeric') {
                    const min = parseFloat(section.min_value || 0);
                    const max = parseFloat(section.max_value || 100);
                    if (max > min) {
                        normalizedScore = Math.max(0, Math.min(100, ((rawValue - min) / (max - min)) * 100));
                    }
                } else if (type === 'grade' || type === 'percentage' || type === 'boolean') {
                    normalizedScore = Math.max(0, Math.min(100, rawValue));
                }
            }
            
            const sectionWeight = parseFloat(section.section_weight);
            const scoreForCriterion = (normalizedScore * sectionWeight) / 100;
            criteriaScores[criterionId].score += scoreForCriterion;

            const sectionScoreDisplay = document.getElementById(`section-score-${sectionId}`);
            if (sectionScoreDisplay) {
                sectionScoreDisplay.textContent = `الدرجة: ${scoreForCriterion.toFixed(2)} / ${sectionWeight.toFixed(2)}`;
            }
        });

        for (const id in criteriaScores) {
            const criterion = criteriaScores[id];
            overallScore += (criterion.score * criterion.weight) / 100;
        }

        updateUI(overallScore, criteriaScores);
    }

    function updateUI(overallScore, criteriaScores) {
        totalScoreDisplay.innerHTML = `${overallScore.toFixed(2)}<small>%</small>`;
        
        if (overallScore >= 85) {
            totalScoreDisplay.style.color = '#198754';
        } else if (overallScore >= 60) {
            totalScoreDisplay.style.color = '#ffc107';
        } else {
            totalScoreDisplay.style.color = '#dc3545';
        }

        criteriaScoresList.innerHTML = '';
        evaluationTemplate.criteria.forEach(criterion => {
            const score = criteriaScores[criterion.id].score;
            const weight = parseFloat(criterion.criterion_weight);
            
            const li = document.createElement('li');
            li.innerHTML = `
                <span>${criterion.criterion_name}</span>
                <span class="score">${score.toFixed(2)} / ${weight.toFixed(1)}</span>
            `;
            criteriaScoresList.appendChild(li);

            const badge = document.getElementById(`criterion-score-${criterion.id}`);
            if (badge) {
                badge.textContent = `${score.toFixed(2)} / ${weight.toFixed(1)}`;
            }
        });
    }

    inputs.forEach(input => {
        input.addEventListener('change', calculateScores);
        input.addEventListener('keyup', calculateScores);
    });

    // ربط أحداث حقول المساعد والتلقائي
    document.querySelectorAll('.helper-input, .auto-result, .formula-result').forEach(input => {
        input.addEventListener('input', calculateScores);
        input.addEventListener('change', calculateScores);
    });

    // قم بتشغيل الحساب عند تحميل الصفحة لأول مرة ليعكس القيم المحفوظة
    calculateScores();
});
</script>
