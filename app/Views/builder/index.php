<?php 
// المسار: app/Views/builder/index.php (النسخة النهائية والموحدة)
require_once APP_ROOT . '/app/Views/layouts/header-builder.php'; 
?>
<link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/builder.css">

<div class="form-builder-container">

    <!-- 1. الشريط الجانبي: مكتبة العناصر -->
    <aside class="sidebar-panel">
        <div class="sidebar-header">
            <a href="<?php echo APP_URL; ?>/templates" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
            <h5 class="mb-0">مكتبة العناصر</h5>
        </div>
        <div class="element-library">
            <div class="element-group">
                <h6><i class="fas fa-layer-group"></i> العناصر الأساسية</h6>
                <div class="draggable-element" data-type="criterion" draggable="true">
                    <i class="fas fa-folder-plus text-primary"></i>
                    <div>
                        <strong>معيار جديد</strong>
                        <small>حاوية رئيسية لمجموعة من الأقسام.</small>
                    </div>
                </div>
            </div>
            <div class="element-group">
                <h6><i class="fas fa-tasks"></i> أنواع الأقسام</h6>
                <div class="draggable-element" data-type="section" data-section-type="percentage" draggable="true">
                    <i class="fas fa-percent text-success"></i>
                    <div><strong>قسم نسبة مئوية</strong><small>إدخال قيمة من 0 إلى 100.</small></div>
                </div>
                <div class="draggable-element" data-type="section" data-section-type="numeric" draggable="true">
                    <i class="fas fa-hashtag text-info"></i>
                    <div><strong>قسم رقمي</strong><small>إدخال قيمة ضمن نطاق.</small></div>
                </div>
                <div class="draggable-element" data-type="section" data-section-type="grade" draggable="true">
                    <i class="fas fa-star text-warning"></i>
                    <div><strong>قسم تقدير</strong><small>اختيار من قائمة (ممتاز، جيد...).</small></div>
                </div>
                <div class="draggable-element" data-type="section" data-section-type="boolean" draggable="true">
                    <i class="fas fa-check-circle text-secondary"></i>
                    <div><strong>قسم نعم/لا</strong><small>إجابة منطقية بسيطة.</small></div>
                </div>
                <div class="draggable-element" data-type="section" data-section-type="formula" draggable="true">
                    <i class="fas fa-calculator text-danger"></i>
                    <div><strong>قسم صيغة حسابية</strong><small>صيغة Excel مثل =B2/C2*100.</small></div>
                </div>
                <div class="draggable-element" data-type="section" data-section-type="auto" draggable="true">
                    <i class="fas fa-cog text-muted"></i>
                    <div><strong>قسم تلقائي</strong><small>بيانات من النظام تلقائياً.</small></div>
                </div>
                <div class="draggable-element" data-type="section" data-section-type="helper" draggable="true">
                    <i class="fas fa-database text-primary"></i>
                    <div><strong>حقل مساعد للحسابات</strong><small>مدخل لعمليات الصيغ (لا يُحتسب).</small></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- 2. مساحة العمل الرئيسية -->
    <main class="main-workspace">
        <div class="toolbar">
            <form id="builderForm" method="POST" action="<?php echo APP_URL; ?>/builder/save">
                <?= csrf_field() ?>
                
                <?php if ($isEditing): ?>
                    <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                <?php endif; ?>
                
                <div id="structure-hidden-fields"></div>

                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><i class="fas fa-drafting-compass me-2"></i><?php echo $pageTitle; ?></h4>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo APP_URL; ?>/templates" class="btn btn-light border"><i class="fas fa-arrow-right me-1"></i> العودة</a>
                        <button type="button" class="btn btn-outline-secondary" data-action="preview"><i class="fas fa-eye"></i> معاينة</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> حفظ الاستمارة</button>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="template_name" name="template_name" placeholder="اسم الاستمارة" required value="<?php echo htmlspecialchars($template['template_name'] ?? ''); ?>">
                            <label for="template_name">اسم الاستمارة *</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <?php $tt = $template['target_type'] ?? 'employee'; ?>
                            <select class="form-select" id="target_type" name="target_type">
                                <option value="employee" <?= $tt==='employee'?'selected':'' ?>>موظف فردي عام</option>
                                <option value="employee_in_course" <?= $tt==='employee_in_course'?'selected':'' ?>>موظف داخل دورة</option>
                                <option value="employee_in_workshop" <?= $tt==='employee_in_workshop'?'selected':'' ?>>موظف داخل ورشة</option>
                                <option value="course" <?= $tt==='course'?'selected':'' ?>>دورة ككيان</option>
                                <option value="workshop" <?= $tt==='workshop'?'selected':'' ?>>ورشة ككيان</option>
                                <option value="unit" <?= $tt==='unit'?'selected':'' ?>>وحدة تنظيمية</option>
                                <option value="admin" <?= $tt==='admin'?'selected':'' ?>>إداري/تنظيمي</option>
                            </select>
                            <label for="target_type">نوع القالب (7 أنواع عامة)</label>
                        </div>
                    </div>
                    <div class="col-md-3" id="pos-wrapper">
                        <div class="form-floating">
                            <select class="form-select" id="target_position_id" name="target_position_id">
                                <option value="">-- قالب عام (لكل المناصب) --</option>
                                <?php if (!empty($positions)): ?>
                                    <?php foreach ($positions as $position): ?>
                                        <option value="<?php echo $position->id; ?>" 
                                            <?php echo (isset($template) && isset($template['target_position_id']) && $template['target_position_id'] == $position->id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($position->title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <label for="target_position_id">المنصب (للفردي فقط)</label>
                        </div>
                    </div>
                    <div class="col-md-2" id="unit-scope-wrapper" style="display:none;">
                        <div class="form-floating">
                            <?php $us = $template['unit_scope'] ?? ''; ?>
                            <select class="form-select" id="unit_scope" name="unit_scope">
                                <option value="">-- عام (فرع+قسم) --</option>
                                <option value="branch" <?= $us==='branch'?'selected':'' ?>>فرع فقط</option>
                                <option value="department" <?= $us==='department'?'selected':'' ?>>قسم فقط</option>
                            </select>
                            <label for="unit_scope">نطاق الوحدة</label>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center pt-3" id="active-wrapper">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo (isset($template['is_active']) && $template['is_active'] == 1) ? 'checked' : (!isset($template) ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="is_active">نشط</label>
                        </div>
                    </div>
                </div>
                <script>document.addEventListener('DOMContentLoaded',function(){const t=document.getElementById('target_type'),p=document.getElementById('pos-wrapper'),u=document.getElementById('unit-scope-wrapper');function tog(){const v=t.value;p.style.display=(v==='employee')?'':'none';u.style.display=(v==='unit')?'':'none';}t.addEventListener('change',tog);tog();const m={employee:{c:'معيار',s:'بند'},employee_in_course:{c:'معيار مشاركة',s:'هدف تدريبي'},employee_in_workshop:{c:'معيار مشاركة',s:'هدف'},course:{c:'محور',s:'عنصر'},workshop:{c:'محور',s:'فقرة'},unit:{c:'مهمة تشغيلية',s:'هدف تشغيلي'},admin:{c:'معيار إداري',s:'عمود/صف'}};t.addEventListener('change',e=>{const l=m[e.target.value];if(!l)return;document.querySelectorAll('.element-group h6').forEach(h=>{if(h.textContent.includes('العناصر')||h.textContent.includes('معيار'))h.innerHTML='<i class=\"fas fa-layer-group\"></i> '+l.c;});});});</script>
                <div class="form-floating mt-3">
                    <textarea class="form-control" placeholder="وصف الاستمارة" id="template_description" name="template_description" style="height: 70px"><?php echo htmlspecialchars($template['template_description'] ?? ''); ?></textarea>
                    <label for="template_description">وصف الاستمارة (اختياري)</label>
                </div>
            </form>
        </div>
        
        <div class="row g-3 align-items-center mt-1 p-3 bg-light border-top border-bottom">
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="auto-distribute-weights" checked>
                    <label class="form-check-label small" for="auto-distribute-weights">توزيع تلقائي للأوزان</label>
                </div>
            </div>
            <div class="col-md-8 d-flex justify-content-end">
                <div class="d-flex align-items-center">
                    <span class="me-2 small fw-bold">إجمالي وزن الاستمارة:</span>
                    <span id="total-weight-indicator" class="weight-badge total">0.0%</span>
                </div>
            </div>
        </div>

        <div class="workspace-canvas">
            <div id="form-structure-container"></div>
            <div class="drop-zone main-drop-zone" id="mainDropZone">
                <i class="fas fa-plus"></i><span>اسحب "معيار جديد" إلى هنا للبدء</span>
            </div>
        </div>
    </main>

    <!-- 3. لوحة الخصائص الجانبية -->
    <aside class="properties-panel">
        <div class="sidebar-header">
            <h5 class="mb-0">الخصائص</h5>
        </div>
        <div class="properties-body">
            <div id="properties-content">
                <div class="no-selection-message">
                    <i class="fas fa-mouse-pointer"></i>
                    <p>اضغط على أي عنصر لعرض خصائصه وتعديلها.</p>
                </div>
            </div>
        </div>
    </aside>

</div>

<script>
    const isEditing = <?php echo json_encode($isEditing); ?>;
    const initialFormStructure = <?php echo $formStructureJson; ?>;
</script>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer-builder.php'; 
?>
