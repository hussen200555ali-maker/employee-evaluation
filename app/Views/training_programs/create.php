<?php
/* CD-036 : training_programs/create.php — Screen 2 & 3 (create OR edit) */
require_once APP_ROOT . '/app/Views/layouts/header.php';
require_once APP_ROOT . '/app/Views/layouts/_flash_messages.php';
$type = $type ?? 'course'; $typeLabel = $typeLabel ?? 'دورة تدريبية'; $typeIcon = $typeIcon ?? 'fa-graduation-cap';
$catalogs = $catalogs ?? []; $branches = $branches ?? []; $departments = $departments ?? [];
$p = $editProgram ?? null; $isEdit = $p !== null; $pid = $isEdit ? (int)$p->id : 0;
?>
<style>
.tr-card{background:#fff;border:1px solid #e6dfcf;border-radius:12px;padding:22px;margin-bottom:20px;box-shadow:0 1px 3px rgba(12,40,35,.08);}
.tr-title{color:#0c2823;font-size:18px;font-weight:800;margin:0 0 2px;}
.tr-sub{color:#6b6b6b;font-size:13px;margin:0 0 16px;}
.tr-label{font-weight:700;color:#0c2823;font-size:14px;margin-bottom:4px;display:block;}
.tr-input{width:100%;padding:11px 13px;border:1px solid #d4d0c8;border-radius:8px;font-family:'Cairo',sans-serif;font-size:15px;box-sizing:border-box;min-height:42px;background:#fff;color:#1a1a1a;}
.tr-input:focus{outline:none;border-color:#c8943f;box-shadow:0 0 0 3px rgba(200,148,63,.15);}
.tr-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.tr-btn{padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;border:none;font-family:'Cairo',sans-serif;text-decoration:none;display:inline-block;font-size:14px;}
.tr-btn-gold{background:#c8943f;color:#0c2823;}
.tr-btn-out{background:transparent;border:1.5px solid #0c2823;color:#0c2823;}
.req{color:#c8943f;}.hint{font-size:12px;color:#8a8a8a;margin-top:4px;}
@media(max-width:640px){.tr-grid2{grid-template-columns:1fr;}}
</style>

<section class="app-hero" aria-labelledby="tp-form-title">
    <div class="app-hero__left">
        <span class="app-eyebrow"><i class="fas fa-graduation-cap"></i> إدارة التدريب والتطوير</span>
        <h1 id="tp-form-title"><?php echo $isEdit ? 'تعديل' : 'إنشاء'; ?> <span class="accent"><?php echo htmlspecialchars($typeLabel); ?></span><?php echo $isEdit ? '' : ' جديدة'; ?></h1>
        <p>الاسم يؤخذ من الكتالوج لتوحيد التسميات — مع تحديد الفرع والقسم المنفِذين والمسؤول عن التنفيذ.</p>
        <a class="app-hero__cta" href="<?php echo APP_URL; ?>/training/programs"><i class="fas fa-arrow-right"></i> العودة إلى السجل</a>
    </div>
    <div class="app-hero__stats" aria-label="اختصارات">
        <div class="app-stat"><i class="fas fa-clipboard-list"></i><strong><?php echo count($catalogs); ?></strong><span>تصنيف متاح</span></div>
        <div class="app-stat"><i class="fas fa-building"></i><strong><?php echo count($branches); ?></strong><span>فرع</span></div>
        <div class="app-stat"><i class="fas fa-sitemap"></i><strong><?php echo count($departments); ?></strong><span>قسم</span></div>
    </div>
</section>

<div class="tr-card">
    <div class="tr-sub">الاسم من الكتالوج — مع تحديد الجهة المنفّذة والمسؤول</div>
    <form id="programForm" method="POST" action="<?php echo $isEdit ? APP_URL.'/training/programs/'.$pid.'/update' : APP_URL.'/training/programs/store'; ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">

        <div>
            <label class="tr-label"><i class="fas fa-tag"></i> اسم <?php echo htmlspecialchars($typeLabel); ?> <span class="req">*</span></label>
            <select class="tr-input" name="catalog_id" required>
                <option value="">اختر من الكتالوج...</option>
                <?php foreach ($catalogs as $c): ?>
                    <option value="<?php echo (int)$c->id; ?>" data-name="<?php echo htmlspecialchars($c->name); ?>" <?php echo ($isEdit && (int)($p->catalog_id ?? 0) === (int)$c->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c->name); ?></option>
                <?php endforeach; ?>
                <?php if (!$catalogs): ?><option value="" disabled>لا توجد تصنيفات مطابقة — أضف تصنيفًا أولًا</option><?php endif; ?>
            </select>
            <input type="hidden" name="title" value="<?php echo $isEdit ? htmlspecialchars($p->title ?? '') : ''; ?>">
            <div class="hint">يتم اختيار الاسم من الكتالوج لتوحيد التسميات</div>
        </div>

        <div class="tr-grid2" style="margin-top:16px">
            <div>
                <label class="tr-label"><i class="fas fa-building"></i> الفرع المنفّذ <span class="req">*</span></label>
                <select class="tr-input" name="executing_branch_id" id="branchSel" required onchange="loadDepts(this)">
                    <option value="">اختر الفرع...</option>
                    <?php foreach ($branches as $b): ?><option value="<?php echo (int)$b->id; ?>" <?php echo ($isEdit && (int)($p->executing_branch_id ?? 0) === (int)$b->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b->name); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="tr-label"><i class="fas fa-sitemap"></i> القسم المنفّذ</label>
                <select class="tr-input" name="executing_department_id" id="deptSel"><option value="">عام (غير محدد)</option></select>
                <div class="hint">اختياري — إذا لم يُحدد تُحسب <?php echo htmlspecialchars($typeLabel); ?> للفرع</div>
            </div>
        </div>

        <div class="tr-grid2" style="margin-top:16px">
            <div><label class="tr-label"><i class="fas fa-calendar-days"></i> تاريخ البدء <span class="req">*</span></label><input class="tr-input" type="date" name="start_date" required value="<?php echo $isEdit ? htmlspecialchars($p->start_date ?? '') : ''; ?>"></div>
            <div><label class="tr-label"><i class="fas fa-calendar-days"></i> تاريخ الانتهاء <span class="req">*</span></label><input class="tr-input" type="date" name="end_date" required value="<?php echo $isEdit ? htmlspecialchars($p->end_date ?? '') : ''; ?>"></div>
        </div>

        <div style="margin-top:16px"><label class="tr-label"><i class="fas fa-user-tie"></i> المسؤول عن <?php echo htmlspecialchars($typeLabel); ?> <span class="req">*</span></label><input class="tr-input" name="responsible_person" required placeholder="اسم المدرب أو المسؤول" value="<?php echo $isEdit ? htmlspecialchars($p->responsible_person ?? '') : ''; ?>"></div>

        <div style="margin-top:16px;text-align:left">
            <button class="tr-btn tr-btn-gold" type="submit"><i class="fas fa-floppy-disk"></i> <?php echo $isEdit ? 'حفظ التعديلات' : 'حفظ '.htmlspecialchars($typeLabel); ?></button>
            <a class="tr-btn tr-btn-out" href="<?php echo APP_URL; ?>/training/programs" style="margin-right:8px">إلغاء</a>
        </div>
    </form>
</div>
<script>
var DEPTS = <?php echo json_encode(array_map(function($d){ return ['id'=>(int)$d->id,'name'=>$d->name,'branch_id'=>(int)($d->branch_id ?? 0)]; }, $departments)); ?>;
var EDIT_DEPT = <?php echo $isEdit ? (int)($p->executing_department_id ?? 0) : '0'; ?>;
(function(){ // sync title from catalog select
    var sel = document.querySelector('select[name=catalog_id]');
    function sync(){ var o = sel.options[sel.selectedIndex]; document.querySelector('input[name=title]').value = o ? (o.dataset.name||'') : ''; }
    sel.addEventListener('change', sync); sync();
})();
function loadDepts(sel){
    var ds = document.getElementById('deptSel'); ds.innerHTML = '<option value="">عام (غير محدد)</option>';
    var bid = parseInt(sel.value);
    DEPTS.filter(function(d){ return d.branch_id === bid; }).forEach(function(d){
        var o = document.createElement('option'); o.value = d.id; o.textContent = d.name; ds.appendChild(o);
    });
}
(function(){ // edit mode: load departments for the saved branch + reselect saved dept
    var bs = document.getElementById('branchSel');
    if (bs && bs.value) { loadDepts(bs); if (EDIT_DEPT) { document.getElementById('deptSel').value = String(EDIT_DEPT); } }
})();
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
