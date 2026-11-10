<?php
// CD-041: التقييم الجماعي — تصميم مصفوفة (Matrix) بديل كامل للإصدار السابق
// عقد الإرسال: results مسطح حسب section_id — {score|text|grade_label, notes}
require_once APP_ROOT . '/app/Views/layouts/header.php';

$cd041_templates_json = json_encode($templates ?? [], JSON_UNESCAPED_UNICODE);
$cd041_branches_json  = json_encode($branches ?? [], JSON_UNESCAPED_UNICODE);
$cd041_scales_json    = json_encode($gradingScales ?? [], JSON_UNESCAPED_UNICODE);
?>
<style>
:root{
    --cd041-brand:#0c2823; --cd041-brand2:#143b32; --cd041-gold:#c8943f;
    --cd041-bg:#f5f8f6; --cd041-border:#d8e2dc; --cd041-text:#1c2b27;
    --cd041-muted:#6b7c75; --cd041-ok:#1f6f4d; --cd041-warn:#a86b1f; --cd041-err:#a8443a;
}
.cd041-wrap{font-family:'Cairo',sans-serif;color:var(--cd041-text);padding-bottom:40px;}
.cd041-card{background:#fff;border:1px solid var(--cd041-border);border-radius:12px;box-shadow:0 1px 4px rgba(12,40,35,.05);}
.cd041-header{background:linear-gradient(135deg,#0c2823 0%,#143b32 55%,#0c2823 100%);color:#fff;padding:20px 26px;border-radius:12px;border-bottom:3px solid var(--cd041-gold);display:flex;align-items:center;gap:14px;margin-bottom:16px;}
.cd041-header .cd041-h-icon{width:42px;height:42px;border-radius:10px;background:rgba(200,148,63,.18);border:1px solid rgba(200,148,63,.45);display:flex;align-items:center;justify-content:center;color:var(--cd041-gold);font-size:18px;}
.cd041-header h1{margin:0;font-size:21px;font-weight:700;}
.cd041-header .cd041-sub{font-size:12px;color:#b9d4c8;margin-top:3px;}
.cd041-header .cd041-tag{margin-inline-start:auto;background:rgba(200,148,63,.18);color:var(--cd041-gold);padding:6px 14px;border-radius:20px;font-size:11px;font-weight:700;border:1px solid rgba(200,148,63,.4);}

.cd041-setup{display:grid;grid-template-columns:1.6fr 1fr 1.4fr 1fr;gap:14px;align-items:end;padding:16px 18px;margin-bottom:14px;}
.cd041-fld label{display:block;font-size:11px;color:var(--cd041-muted);margin-bottom:5px;font-weight:700;}
.cd041-sel,.cd041-inp{width:100%;padding:9px 12px;border:1px solid var(--cd041-border);border-radius:8px;font-size:13px;font-family:'Cairo',sans-serif;background:#fff;color:var(--cd041-text);}
.cd041-sel:focus,.cd041-inp:focus{outline:none;border-color:var(--cd041-gold);box-shadow:0 0 0 3px rgba(200,148,63,.15);}
.cd041-sec-title{font-size:13px;font-weight:700;color:var(--cd041-brand);margin:6px 4px 10px;display:flex;align-items:center;gap:8px;}
.cd041-sec-title .cd041-dot{width:8px;height:8px;border-radius:50%;background:var(--cd041-gold);}

.cd041-prog{padding:14px 18px;margin-bottom:14px;display:none;}
.cd041-prog.open{display:block;}
.cd041-prog-grid{display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1.4fr;gap:12px;align-items:end;}
.cd041-prog-info{margin-top:12px;padding:0;background:#fff;border:2px solid var(--cd041-gold);border-radius:12px;font-size:12px;display:none;overflow:hidden;box-shadow:0 8px 22px rgba(200,148,63,.18);}
.cd041-prog-info.open{display:block;animation:cd041-fadeIn .3s ease;}
@keyframes cd041-fadeIn{from{opacity:0;transform:translateY(6px);}to{opacity:1;transform:none;}}
.cd041-prog-info .cd041-pi-head{background:linear-gradient(135deg,#0c2823 0%,#0d5e52 100%);color:#fff;padding:10px 14px;display:flex;align-items:center;gap:10px;font-weight:800;font-size:13px;}
.cd041-prog-info .cd041-pi-head i{color:var(--cd041-gold);font-size:14px;background:rgba(255,255,255,.12);width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;}
.cd041-prog-info .cd041-pi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:0;}
.cd041-prog-info .cd041-pi-cell{padding:10px 12px;display:flex;flex-direction:column;gap:3px;border-bottom:1px solid #f1f3f5;border-left:1px solid #f1f3f5;text-align:center;transition:background .2s;}
.cd041-prog-info .cd041-pi-cell:hover{background:#fffbf3;}
.cd041-prog-info .cd041-pi-cell:nth-child(3n){border-left:none;}
.cd041-prog-info .cd041-pi-cell:nth-last-child(-n+3){border-bottom:none;}
.cd041-prog-info .cd041-pi-cell b{color:var(--cd041-muted);font-weight:700;font-size:10px;margin-bottom:2px;display:flex;align-items:center;justify-content:center;gap:4px;}
.cd041-prog-info .cd041-pi-cell b i{color:var(--cd041-gold);font-size:10px;}
.cd041-prog-info .cd041-pi-cell span{color:var(--cd041-text);font-weight:700;font-size:12.5px;}
.cd041-prog-info .cd041-pi-cell span.empty{color:#94a3b8;font-weight:400;font-style:italic;}
@media(max-width:700px){.cd041-prog-info .cd041-pi-grid{grid-template-columns:repeat(2,1fr);}.cd041-prog-info .cd041-pi-cell:nth-child(2n){border-left:none;}.cd041-prog-info .cd041-pi-cell:nth-last-child(-n+2){border-bottom:none;}}
@media(max-width:460px){.cd041-prog-info .cd041-pi-grid{grid-template-columns:1fr;}.cd041-prog-info .cd041-pi-cell{border-left:none;}}

.cd041-empbar{padding:14px 18px;margin-bottom:14px;display:flex;gap:12px;align-items:end;flex-wrap:wrap;}
.cd041-empbar .cd041-search{flex:1;min-width:240px;position:relative;}
.cd041-count{font-size:12px;color:var(--cd041-muted);font-weight:700;padding:9px 12px;background:#f5f8f6;border-radius:8px;border:1px solid var(--cd041-border);white-space:nowrap;}
.cd041-count b{color:var(--cd041-brand);font-size:14px;}

.cd041-dd{position:absolute;inset-inline-start:0;inset-block-start:100%;background:#fff;border:1px solid var(--cd041-border);border-radius:8px;box-shadow:0 6px 20px rgba(12,40,35,.12);max-height:280px;overflow-y:auto;z-index:60;min-width:100%;display:none;margin-top:4px;}
.cd041-dd-item{padding:9px 12px;cursor:pointer;border-bottom:1px solid #eef3f0;font-size:12px;}
.cd041-dd-item:hover{background:#f5f0e8;}
.cd041-dd-item b{color:var(--cd041-brand);}
.cd041-dd-item small{color:var(--cd041-muted);font-size:10px;}

.cd041-qa{padding:14px 18px;margin-bottom:14px;background:#fbfdfc;border:1px dashed var(--cd041-gold);border-radius:10px;display:none;}
.cd041-qa.open{display:block;}
.cd041-qa-grid{display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr auto;gap:10px;align-items:end;}
.cd041-qa legend{font-size:12px;font-weight:700;color:var(--cd041-brand);margin-bottom:8px;}

.cd041-toolbar{padding:12px 18px;margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;}
.cd041-btn{padding:8px 14px;border:1px solid var(--cd041-border);background:#fff;color:var(--cd041-text);border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;font-family:'Cairo',sans-serif;transition:all .15s;}
.cd041-btn:hover{background:#f5f8f6;border-color:var(--cd041-gold);}
.cd041-btn-primary{background:var(--cd041-brand);color:#fff;border-color:var(--cd041-brand);}
.cd041-btn-primary:hover{background:var(--cd041-brand2);}
.cd041-btn-gold{background:var(--cd041-gold);color:#fff;border-color:var(--cd041-gold);}
.cd041-btn-gold:hover{background:#b8842f;}
.cd041-btn-danger{color:var(--cd041-err);border-color:#e6c9c4;}
.cd041-btn-danger:hover{background:#fbeeed;}
.cd041-btn-ghost{background:transparent;}
.cd041-grade-tool{display:inline-flex;gap:6px;align-items:center;}

.cd041-matrix-wrap{background:#fff;border:1px solid var(--cd041-border);border-radius:12px;overflow:hidden;margin-bottom:14px;}
.cd041-matrix-scroll{overflow-x:auto;max-height:62vh;overflow-y:auto;}
.cd041-matrix{width:100%;border-collapse:separate;border-spacing:0;font-size:12px;}
.cd041-matrix thead tr:first-child th{position:sticky;top:0;z-index:3;}
.cd041-matrix thead tr:last-child th{position:sticky;top:34px;z-index:2;}
.cd041-matrix thead th{background:var(--cd041-brand);color:#fff;padding:9px 8px;font-weight:700;text-align:center;white-space:nowrap;border-inline-start:1px solid rgba(255,255,255,.08);}
.cd041-matrix thead th:first-child{border-inline-start:none;}
.cd041-matrix thead .cd041-c-crit{background:#0a4a42;font-size:11.5px;font-weight:800;letter-spacing:.2px;border-bottom:2px solid var(--cd041-gold);}
.cd041-matrix thead .cd041-c-sec{font-size:11px;max-width:130px;}
.cd041-matrix thead .cd041-w{display:block;font-size:9px;color:var(--cd041-gold);font-weight:600;margin-top:2px;}
.cd041-matrix tbody td{padding:6px 8px;border-bottom:1px solid #eef3f0;text-align:center;vertical-align:middle;border-inline-start:1px solid #f3f7f5;}
.cd041-matrix tbody td:first-child{border-inline-start:none;}
.cd041-matrix tbody tr:hover{background:#f7fbf9;}
.cd041-matrix tbody tr:nth-child(even){background:#fafdfb;}
.cd041-matrix tbody tr:nth-child(even):hover{background:#f3f9f6;}
.cd041-c-idx{width:34px;color:var(--cd041-muted);font-weight:700;}
.cd041-c-emp{text-align:start !important;min-width:160px;max-width:220px;}
.cd041-c-emp .cd041-emp-name{font-weight:700;color:var(--cd041-brand);font-size:12px;}
.cd041-c-emp .cd041-emp-meta{font-size:10px;color:var(--cd041-muted);margin-top:1px;}
.cd041-c-score{width:62px;}
.cd041-c-grade{width:108px;}
.cd041-c-status{width:78px;}
.cd041-c-act{width:36px;}

.cd041-input-mini{width:52px;text-align:center;border:1px solid var(--cd041-border);border-radius:6px;padding:4px 5px;font-size:12px;font-family:'Cairo',sans-serif;background:#fff;}
.cd041-input-mini:focus{outline:none;border-color:var(--cd041-gold);box-shadow:0 0 0 2px rgba(200,148,63,.18);}
.cd041-progress{width:52px;height:5px;background:#e3ebe7;border-radius:4px;overflow:hidden;margin:4px auto 0;}
.cd041-progress span{display:block;height:100%;background:linear-gradient(90deg,var(--cd041-gold),#e0b35a);transition:width .2s;}
.cd041-bool{display:inline-flex;gap:3px;}
.cd041-bool button{border:1px solid var(--cd041-border);background:#fff;padding:3px 8px;border-radius:6px;font-size:10px;cursor:pointer;font-family:'Cairo',sans-serif;font-weight:700;color:var(--cd041-muted);}
.cd041-bool button.on-yes{background:var(--cd041-ok);color:#fff;border-color:var(--cd041-ok);}
.cd041-bool button.on-no{background:var(--cd041-err);color:#fff;border-color:var(--cd041-err);}
.cd041-text-btn{border:1px solid var(--cd041-border);background:#fff;padding:4px 9px;border-radius:6px;cursor:pointer;font-size:13px;line-height:1;}
.cd041-text-btn:hover{border-color:var(--cd041-gold);}
.cd041-text-btn.has{background:#fdf3e2;border-color:var(--cd041-gold);color:var(--cd041-brand);}
.cd041-notes-btn{border:1px solid var(--cd041-border);background:#fff;padding:2px 6px;border-radius:4px;cursor:pointer;font-size:11px;line-height:1;margin-right:2px;vertical-align:middle;}
.cd041-notes-btn:hover{border-color:var(--cd041-gold);}
.cd041-notes-btn.has{background:#fdf3e2;border-color:var(--cd041-gold);color:var(--cd041-brand);}
.cd041-grade-sel{border:1px solid var(--cd041-border);border-radius:6px;padding:4px 6px;font-size:11px;font-family:'Cairo',sans-serif;background:#fff;max-width:100px;}
.cd041-grade-readonly{font-weight:700;color:var(--cd041-brand);font-size:12px;padding:4px 6px;display:inline-block;min-width:48px;}
.cd041-score{font-weight:800;color:var(--cd041-brand);font-size:13px;}
.cd041-score.low{color:var(--cd041-err);}
.cd041-status{font-size:10px;padding:3px 9px;border-radius:12px;font-weight:700;display:inline-block;white-space:nowrap;}
.cd041-status.done{background:#e2f3ea;color:var(--cd041-ok);}
.cd041-status.pending{background:#fdf3e2;color:var(--cd041-warn);}
.cd041-status.empty{background:#eef2f0;color:#9aa8a2;}
.cd041-remove{background:none;border:none;color:#c0584f;cursor:pointer;font-size:14px;padding:2px 6px;border-radius:6px;}
.cd041-remove:hover{background:#fbeeed;}
.cd041-empty{padding:40px 20px;text-align:center;color:var(--cd041-muted);font-size:13px;}

.cd041-modal-bg{position:fixed;inset:0;background:rgba(12,40,35,.45);display:none;align-items:center;justify-content:center;z-index:200;padding:20px;}
.cd041-modal-bg.open{display:flex;}
.cd041-modal{background:#fff;border-radius:14px;width:560px;max-width:100%;max-height:88vh;overflow:auto;box-shadow:0 20px 60px rgba(12,40,35,.3);border-top:3px solid var(--cd041-gold);}
.cd041-modal-head{background:var(--cd041-brand);color:#fff;padding:14px 18px;display:flex;align-items:center;gap:10px;border-radius:11px 11px 0 0;}
.cd041-modal-head h3{margin:0;font-size:15px;font-weight:700;}
.cd041-modal-head .cd041-close{margin-inline-start:auto;background:none;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;}
.cd041-modal-body{padding:18px;}
.cd041-modal-body textarea{width:100%;min-height:140px;border:1px solid var(--cd041-border);border-radius:8px;padding:10px 12px;font-size:13px;font-family:'Cairo',sans-serif;resize:vertical;}
.cd041-modal-body textarea:focus{outline:none;border-color:var(--cd041-gold);box-shadow:0 0 0 3px rgba(200,148,63,.15);}
.cd041-charcount{text-align:end;font-size:11px;color:var(--cd041-muted);margin-top:4px;}
.cd041-modal-foot{padding:12px 18px;border-top:1px solid var(--cd041-border);display:flex;gap:10px;align-items:center;}
.cd041-chk{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--cd041-muted);cursor:pointer;}

.cd041-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px;}
.cd041-sum-item{background:#fff;border:1px solid var(--cd041-border);border-radius:10px;padding:14px 16px;text-align:center;}
.cd041-sum-item .cd041-sum-n{display:block;font-size:22px;font-weight:800;color:var(--cd041-brand);}
.cd041-sum-item .cd041-sum-l{display:block;font-size:11px;color:var(--cd041-muted);margin-top:4px;font-weight:700;}

.cd041-savebar{padding:14px 18px;display:flex;gap:10px;align-items:center;justify-content:flex-end;}

.cd041-toast{position:fixed;bottom:24px;inset-inline-start:24px;background:var(--cd041-brand);color:#fff;padding:12px 18px;border-radius:8px;font-size:13px;font-weight:600;z-index:300;box-shadow:0 8px 24px rgba(12,40,35,.25);opacity:0;transform:translateY(10px);transition:all .2s;}
.cd041-toast.show{opacity:1;transform:translateY(0);}
.cd041-toast.err{background:var(--cd041-err);}
.cd041-toast.ok{background:var(--cd041-ok);}
/* CD-041.4: sticky validation banner (stays until dismissed or fixed) */
.cd041-sticky{position:sticky;top:0;z-index:50;margin:0 0 12px;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:700;display:none;align-items:center;gap:10px;box-shadow:0 4px 14px rgba(0,0,0,.12);animation:cd041-fadeIn .2s ease;}
.cd041-sticky.show{display:flex;}
.cd041-sticky.err{background:#fef2f2;color:#991b1b;border:1.5px solid #fecaca;}
.cd041-sticky.ok{background:#ecfdf5;color:#065f46;border:1.5px solid #a7f3d0;}
.cd041-sticky.info{background:#eff6ff;color:#1e40af;border:1.5px solid #bfdbfe;}
.cd041-sticky .cd041-sticky-ico{font-size:16px;flex:0 0 auto;}
.cd041-sticky .cd041-sticky-msg{flex:1;}
.cd041-sticky .cd041-sticky-btn{background:transparent;border:1px solid currentColor;border-radius:6px;padding:4px 10px;font-size:11.5px;font-weight:700;cursor:pointer;font-family:inherit;}
.cd041-sticky .cd041-sticky-btn:hover{background:currentColor;color:#fff;}
.cd041-sticky .cd041-sticky-x{background:transparent;border:none;font-size:18px;line-height:1;cursor:pointer;color:inherit;opacity:.7;padding:0 4px;}
.cd041-sticky .cd041-sticky-x:hover{opacity:1;}

@media(max-width:980px){
    .cd041-setup,.cd041-prog-grid,.cd041-qa-grid{grid-template-columns:1fr 1fr;}
    .cd041-summary{grid-template-columns:1fr 1fr;}
}
</style>

<div class="cd041-wrap">

    <!-- ============ Header ============ -->
    <div class="cd041-header">
        <div class="cd041-h-icon"><i class="fas fa-table"></i></div>
        <div>
            <h1>التقييم الجماعي</h1>
            <div class="cd041-sub">إدخال نتائج تقييم عدّة موظفين في مصفوفة واحدة</div>
        </div>
        <span class="cd041-tag">CD-041 · Matrix</span>
    </div>

    <!-- ============ Setup bar ============ -->
    <div class="cd041-card cd041-setup">
        <div class="cd041-fld">
            <label>قالب التقييم</label>
            <select id="cd041-template" class="cd041-sel">
                <option value="">— اختر القالب —</option>
                <?php foreach ($templates as $tpl): ?>
                    <option value="<?= (int)$tpl->id ?>" data-mode="<?= htmlspecialchars($tpl->evaluation_mode ?? 'numeric', ENT_QUOTES) ?>" data-target="<?= htmlspecialchars($tpl->target_type ?? 'employee', ENT_QUOTES) ?>"><?= htmlspecialchars($tpl->template_name ?? '', ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cd041-fld">
            <label>تاريخ التقييم</label>
            <input type="date" id="cd041-date" class="cd041-inp" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="cd041-fld">
            <label>المقيم <button type="button" class="cd041-btn cd041-btn-ghost" style="padding:0 6px;font-size:10px;" onclick="cd041_toggleQa('cd041-qa-eval')">+ مقيم جديد</button></label>
            <input type="text" id="cd041-evaluator" class="cd041-inp" value="<?= htmlspecialchars($evaluatorName ?? '', ENT_QUOTES) ?>" placeholder="ابحث بالاسم أو الكود..." autocomplete="off">
        </div>
        <div class="cd041-fld">
            <label>المسمى الوظيفي للمقيم</label>
            <input type="text" id="cd041-evaluator-title" class="cd041-inp" value="<?= htmlspecialchars($evaluatorTitle ?? '', ENT_QUOTES) ?>" placeholder="مثال: مدير الموارد البشرية">
        </div>
    </div>

    <!-- ============ Quick-add: evaluator ============ -->
    <div class="cd041-qa" id="cd041-qa-eval">
        <legend>إضافة مقيم جديد سريعًا</legend>
        <div class="cd041-qa-grid">
            <div class="cd041-fld"><label>الكود</label><input type="text" id="cd041-qa-eval-code" class="cd041-inp" placeholder="EMP-000"></div>
            <div class="cd041-fld"><label>الاسم</label><input type="text" id="cd041-qa-eval-name" class="cd041-inp" placeholder="الاسم الكامل"></div>
            <div class="cd041-fld"><label>الفرع</label>
                <select id="cd041-qa-eval-branch" class="cd041-sel" onchange="cd041_loadDepts(this.value,'cd041-qa-eval-dept')">
                    <option value="">— اختر الفرع —</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= (int)$b->id ?>"><?= htmlspecialchars($b->name ?? '', ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cd041-fld"><label>القسم</label><select id="cd041-qa-eval-dept" class="cd041-sel" disabled><option value="">— اختر الفرع أولًا —</option></select></div>
            <div class="cd041-fld"><label>المسمى الوظيفي</label><select id="cd041-qa-eval-pos" class="cd041-sel" disabled><option value="">— اختر —</option></select></div>
            <div class="cd041-fld" style="align-self:end;"><button type="button" class="cd041-btn cd041-btn-primary" onclick="cd041_quickAddEvaluator()">حفظ المقيم</button></div>
        </div>
    </div>

    <!-- ============ Program picker (conditional) ============ -->
    <div class="cd041-card cd041-prog" id="cd041-prog-wrap">
        <div class="cd041-sec-title"><span class="cd041-dot"></span>اختيار البرنامج / الدورة</div>
        <div class="cd041-prog-grid">
            <div class="cd041-fld"><label>الفرع</label>
                <select id="cd041-prog-branch" class="cd041-sel" onchange="cd041_loadDepts(this.value,'cd041-prog-dept');cd041_renderProgramOptions();">
                    <option value="">كل الفروع</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= (int)$b->id ?>"><?= htmlspecialchars($b->name ?? '', ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cd041-fld"><label>القسم</label><select id="cd041-prog-dept" class="cd041-sel" disabled onchange="cd041_renderProgramOptions()"><option value="">كل الأقسام</option></select></div>
            <div class="cd041-fld"><label>من تاريخ</label><input type="date" id="cd041-prog-from" class="cd041-inp" onchange="cd041_renderProgramOptions()"></div>
            <div class="cd041-fld"><label>إلى تاريخ</label><input type="date" id="cd041-prog-to" class="cd041-inp" onchange="cd041_renderProgramOptions()"></div>
            <div class="cd041-fld"><label>البرنامج</label><select id="cd041-program" class="cd041-sel" onchange="cd041_onProgramChange()"><option value="">— اختر البرنامج —</option></select></div>
        </div>
        <div class="cd041-prog-info" id="cd041-prog-info"></div>
    </div>

    <!-- ============ Employee add bar ============ -->
    <div class="cd041-card cd041-empbar">
        <div class="cd041-search">
            <label style="display:block;font-size:11px;color:var(--cd041-muted);margin-bottom:5px;font-weight:700;">بحث وإضافة موظف <button type="button" class="cd041-btn cd041-btn-ghost" style="padding:0 6px;font-size:10px;" onclick="cd041_toggleQa('cd041-qa-emp')">+ موظف جديد</button></label>
            <input type="text" id="cd041-emp-search" class="cd041-inp" placeholder="ابحث بالاسم أو الكود أو اختر من القائمة..." autocomplete="off">
            <div class="cd041-dd" id="cd041-emp-dd"></div>
        </div>
        <div class="cd041-fld" style="min-width:170px;">
            <label>تصفية بالفرع</label>
            <select id="cd041-emp-branch" class="cd041-sel" onchange="cd041_loadDepts(this.value,'cd041-emp-dept')">
                <option value="">كل الفروع</option>
                <?php foreach ($branches as $b): ?>
                    <option value="<?= (int)$b->id ?>"><?= htmlspecialchars($b->name ?? '', ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cd041-fld" style="min-width:170px;">
            <label>تصفية بالقسم</label>
            <select id="cd041-emp-dept" class="cd041-sel" disabled><option value="">اختر الفرع أولًا</option></select>
        </div>
        <div class="cd041-count" id="cd041-count">عدد الموظفين: <b>0</b></div>
    </div>

    <!-- ============ Quick-add: employee ============ -->
    <div class="cd041-qa" id="cd041-qa-emp">
        <legend>إضافة موظف جديد سريعًا</legend>
        <div class="cd041-qa-grid">
            <div class="cd041-fld"><label>الكود</label><input type="text" id="cd041-qa-emp-code" class="cd041-inp" placeholder="EMP-000"></div>
            <div class="cd041-fld"><label>الاسم</label><input type="text" id="cd041-qa-emp-name" class="cd041-inp" placeholder="الاسم الكامل"></div>
            <div class="cd041-fld"><label>الفرع</label>
                <select id="cd041-qa-emp-branch" class="cd041-sel" onchange="cd041_loadDepts(this.value,'cd041-qa-emp-dept')">
                    <option value="">— اختر الفرع —</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= (int)$b->id ?>"><?= htmlspecialchars($b->name ?? '', ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cd041-fld"><label>القسم</label><select id="cd041-qa-emp-dept" class="cd041-sel" disabled><option value="">— اختر الفرع أولًا —</option></select></div>
            <div class="cd041-fld"><label>المسمى الوظيفي</label><select id="cd041-qa-emp-pos" class="cd041-sel" disabled><option value="">— اختر —</option></select></div>
            <div class="cd041-fld" style="align-self:end;"><button type="button" class="cd041-btn cd041-btn-primary" onclick="cd041_quickAddEmployee()">حفظ الموظف</button></div>
        </div>
    </div>

    <!-- ============ Bulk toolbar ============ -->
    <div class="cd041-card cd041-toolbar">
        <button type="button" class="cd041-btn" onclick="cd041_applyTopToAll()"><i class="fas fa-copy"></i> تطبيق الأعلى على الكل</button>
        <button type="button" class="cd041-btn" onclick="cd041_fillDefault()"><i class="fas fa-magic"></i> ملء افتراضي</button>
        <span id="cd041-grade-tool" class="cd041-grade-tool" style="display:none;">
            <select id="cd041-grade-all-sel" class="cd041-grade-sel"><option value="">— اختر التقدير —</option></select>
            <button type="button" class="cd041-btn cd041-btn-gold" onclick="cd041_applyGradeToAll()">تطبيق التقدير على الكل</button>
        </span>
        <button type="button" class="cd041-btn cd041-btn-danger" onclick="cd041_clearAll()"><i class="fas fa-eraser"></i> تفريغ الكل</button>
    </div>

    <!-- ============ Sticky validation banner ============ -->
    <div class="cd041-sticky" id="cd041-sticky" role="alert" aria-live="assertive">
        <span class="cd041-sticky-ico" id="cd041-sticky-ico">⚠️</span>
        <span class="cd041-sticky-msg" id="cd041-sticky-msg"></span>
        <button type="button" class="cd041-sticky-btn" id="cd041-sticky-fix" style="display:none;">تصحيح</button>
        <button type="button" class="cd041-sticky-x" id="cd041-sticky-x" aria-label="إغلاق">×</button>
    </div>

    <!-- ============ Matrix table ============ -->
    <div class="cd041-matrix-wrap">
        <div class="cd041-matrix-scroll">
            <table class="cd041-matrix">
                <thead><tr id="cd041-matrix-head"><th class="cd041-c-idx">#</th><th class="cd041-c-emp">الموظف</th><th class="cd041-empty" colspan="4" style="color:#b9d4c8;font-weight:400;">اختر قالبًا لبناء أعمدة المعايير</th></tr></thead>
                <tbody id="cd041-matrix-body"></tbody>
            </table>
        </div>
        <div class="cd041-empty" id="cd041-empty-msg">أضف موظفين واختر قالبًا لبدء التقييم الجماعي.</div>
    </div>

    <!-- ============ Summary ============ -->
    <div class="cd041-summary">
        <div class="cd041-sum-item"><span class="cd041-sum-n" id="cd041-sum-total">0</span><span class="cd041-sum-l">الإجمالي</span></div>
        <div class="cd041-sum-item"><span class="cd041-sum-n" id="cd041-sum-done">0</span><span class="cd041-sum-l">مكتمل</span></div>
        <div class="cd041-sum-item"><span class="cd041-sum-n" id="cd041-sum-avg">-</span><span class="cd041-sum-l">متوسط النتيجة</span></div>
        <div class="cd041-sum-item"><span class="cd041-sum-n" id="cd041-sum-grade">-</span><span class="cd041-sum-l">التقدير العام</span></div>
    </div>

    <!-- ============ Save bar ============ -->
    <div class="cd041-card cd041-savebar">
        <button type="button" class="cd041-btn cd041-btn-danger" onclick="cd041_reset()">تصفير الكل</button>
        <button type="button" class="cd041-btn cd041-btn-ghost" onclick="cd041_preview()">معاينة الحمولة</button>
        <button type="button" class="cd041-btn cd041-btn-primary" onclick="cd041_saveAll()"><i class="fas fa-save"></i> حفظ الكل</button>
    </div>

</div>

<!-- ============ Text modal ============ -->
<div class="cd041-modal-bg" id="cd041-modal">
    <div class="cd041-modal">
        <div class="cd041-modal-head">
            <i class="fas fa-keyboard" style="color:var(--cd041-gold);"></i>
            <h3 id="cd041-modal-title">إدخال نصي</h3>
            <button type="button" class="cd041-close" onclick="cd041_closeModal()">&times;</button>
        </div>
        <div class="cd041-modal-body">
            <div id="cd041-modal-meta" style="font-size:11px;color:var(--cd041-muted);margin-bottom:8px;"></div>
            <textarea id="cd041-modal-text" maxlength="2000" oninput="document.getElementById('cd041-modal-cc').textContent=this.value.length+'/2000'"></textarea>
            <div class="cd041-charcount" id="cd041-modal-cc">0/2000</div>
        </div>
        <div class="cd041-modal-foot">
            <label class="cd041-chk" id="cd041-modal-all-wrap"><input type="checkbox" id="cd041-modal-all"> تطبيق على كل الموظفين</label>
            <span style="margin-inline-start:auto;"></span>
            <button type="button" class="cd041-btn cd041-btn-ghost" onclick="cd041_closeModal()">إلغاء</button>
            <button type="button" class="cd041-btn cd041-btn-primary" id="cd041-modal-save" onclick="cd041_saveModal()">حفظ</button>
        </div>
    </div>
</div>

<div class="cd041-toast" id="cd041-toast"></div>

<script>
const CD041_APP_URL = "<?= defined('APP_URL') ? htmlspecialchars(APP_URL, ENT_QUOTES) : '' ?>";
const CD041_CSRF    = "<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES) ?>";
const CD041_SCALES  = <?= $cd041_scales_json ?>;
const CD041_BRANCHES = <?= $cd041_branches_json ?>;
const CD041_TEMPLATES = <?= $cd041_templates_json ?>;

/* ===== state ===== */
var cd041_template = null;        // full schema
var cd041_columns  = [];          // flattened sections (excluding overall_grade)
var cd041_employees = [];         // [{id, emp_code, full_name, branch_name, department_name, position_title}]
var cd041_results  = {};          // {empId: {sectionId: {score, text, notes}}}
var cd041_final_grades = {};      // {empId: gradeId}
var cd041_program  = null;
var cd041_programs = [];
var cd041_evaluator_emp = null;
var cd041_modal_ctx = null;       // {empId, secId, mode}

/* ===== helpers ===== */
function cd041_esc(s){var d=document.createElement('div');d.textContent=(s==null?'':String(s));return d.innerHTML;}
function cd041_short(s,n){s=s==null?'':String(s);n=n||14;return s.length>n?cd041_esc(s.slice(0,n))+'...':cd041_esc(s);}
function cd041_type(t){return t||'numeric';}
function cd041_toast(msg,kind){var el=document.getElementById('cd041-toast');el.textContent=msg;el.className='cd041-toast show '+(kind||'');clearTimeout(window._cd041_t);window._cd041_t=setTimeout(function(){el.className='cd041-toast';},2600);}
/* ===== sticky validation banner (CD-041.4) ===== */
var cd041_stickyTimer=null;
function cd041_sticky(msg, kind, fixFn){
    var el=document.getElementById('cd041-sticky'); if(!el) return;
    var ico=document.getElementById('cd041-sticky-ico');
    var txt=document.getElementById('cd041-sticky-msg');
    var fix=document.getElementById('cd041-sticky-fix');
    el.className='cd041-sticky show '+(kind||'err');
    if(ico) ico.textContent = (kind==='ok'?'✅':(kind==='info'?'ℹ️':'⚠️'));
    if(txt) txt.textContent = msg||'';
    if(fix){ if(typeof fixFn==='function'){ fix.style.display='inline-block'; fix.onclick=fixFn; } else { fix.style.display='none'; fix.onclick=null; } }
    clearTimeout(cd041_stickyTimer);
    if(kind==='ok'){ cd041_stickyTimer=setTimeout(cd041_dismissSticky, 4000); }
}
function cd041_dismissSticky(){ var el=document.getElementById('cd041-sticky'); if(el) el.className='cd041-sticky'; }
function cd041_highlightField(el){ if(!el) return; el.style.borderColor='#dc2626'; el.style.boxShadow='0 0 0 3px rgba(220,38,38,.18)'; el.focus && el.focus(); }
function cd041_clearHighlight(){ document.querySelectorAll('.cd041-sel,.cd041-inp').forEach(function(e){ e.style.borderColor=''; e.style.boxShadow=''; }); }

/* ===== template loading ===== */
function cd041_targetToContext(tt){
    if(tt==='employee_in_course'||tt==='course') return 'course';
    if(tt==='employee_in_workshop'||tt==='workshop') return 'workshop';
    return '';
}
document.getElementById('cd041-template').addEventListener('change', function(){
    var id = parseInt(this.value,10);
    var opt = this.options[this.selectedIndex];
    if(!id){ cd041_template=null; cd041_columns=[]; document.getElementById('cd041-prog-wrap').classList.remove('open'); cd041_renderMatrix(); return; }
    fetch(CD041_APP_URL+'/api/templates/'+id+'/schema').then(function(r){return r.json();}).then(function(s){
        cd041_template = s;
        cd041_template.target_type = opt.getAttribute('data-target') || 'employee';
        cd041_template.evaluation_mode = s.evaluation_mode || opt.getAttribute('data-mode') || 'numeric';
        cd041_columns = [];
        (s.criteria||[]).forEach(function(c){
            (c.sections||[]).forEach(function(sec){
                if(cd041_type(sec.type||sec.evaluation_type)==='overall_grade') return;
                sec._crit_weight = parseFloat(c.weight)||0;
                cd041_columns.push(sec);
            });
        });
        cd041_results = {}; cd041_final_grades = {};
        var ctx = cd041_targetToContext(cd041_template.target_type);
        var pw = document.getElementById('cd041-prog-wrap');
        if(ctx){ pw.classList.add('open'); cd041_loadPrograms(ctx); }
        else { pw.classList.remove('open'); cd041_program=null; }
        var desc = (cd041_template.evaluation_mode==='descriptive');
        document.getElementById('cd041-grade-tool').style.display = desc?'':'none';
        cd041_buildGradeAllSel();
        cd041_renderMatrix();
        cd041_toast('تم تحميل القالب: '+cd041_short(s.template_name,30),'ok');
    }).catch(function(){ cd041_toast('تعذر تحميل القالب','err'); });
});

/* ===== departments cascade ===== */
function cd041_loadDepts(branchId, selId){
    var sel = document.getElementById(selId);
    sel.innerHTML = '<option value="">— جارٍ التحميل —</option>';
    if(!branchId){ sel.innerHTML='<option value="">'+(selId.indexOf('prog')>-1?'كل الأقسام':'اختر الفرع أولًا')+'</option>'; sel.disabled=true; return; }
    sel.disabled=false;
    fetch(CD041_APP_URL+'/api/departments/by-branch/'+branchId).then(function(r){return r.json();}).then(function(list){
        var html='<option value="">'+(selId.indexOf('prog')>-1?'كل الأقسام':'— اختر القسم —')+'</option>';
        (list||[]).forEach(function(d){ html+='<option value="'+d.id+'">'+cd041_esc(d.name)+'</option>'; });
        sel.innerHTML=html;
    }).catch(function(){ sel.innerHTML='<option value="">— تعذر التحميل —</option>'; });
}

/* ===== positions ===== */
function cd041_loadPositions(selId){
    var sel=document.getElementById(selId);
    if(!sel) return;
    sel.innerHTML='<option value="">— جارٍ التحميل —</option>';
    fetch(CD041_APP_URL+'/api/positions').then(function(r){return r.json();}).then(function(list){
        var html='<option value="">— اختر المسمى —</option>';
        (list||[]).forEach(function(p){ html+='<option value="'+p.id+'">'+cd041_esc(p.title)+'</option>'; });
        sel.innerHTML=html; sel.disabled=false;
    }).catch(function(){ sel.innerHTML='<option value="">— تعذر التحميل —</option>'; });
}

/* ===== program picker ===== */
function cd041_loadPrograms(type){
    fetch(CD041_APP_URL+'/api/training-programs/list/'+type).then(function(r){return r.json();}).then(function(list){
        cd041_programs = list||[]; cd041_renderProgramOptions();
    }).catch(function(){ cd041_programs=[]; cd041_renderProgramOptions(); });
}
function cd041_renderProgramOptions(){
    var sel=document.getElementById('cd041-program');
    var bId=document.getElementById('cd041-prog-branch').value;
    var dSel=document.getElementById('cd041-prog-dept');
    var dName = dSel.value ? (dSel.options[dSel.selectedIndex]||{}).text : '';
    var dFrom=document.getElementById('cd041-prog-from').value;
    var dTo=document.getElementById('cd041-prog-to').value;
    var bName = bId ? ((CD041_BRANCHES.filter(function(b){return String(b.id)===String(bId);})[0])||{}).name : '';
    var list = cd041_programs.filter(function(p){
        if(bName && p.branch_name!==bName) return false;
        if(dName && dName.indexOf('اختر')===-1 && dName.indexOf('كل')===-1 && p.department_name!==dName) return false;
        if(dFrom && p.start_date && p.start_date < dFrom) return false;
        if(dTo && p.end_date && p.end_date > dTo) return false;
        return true;
    });
    var html='<option value="">— اختر البرنامج ('+list.length+') —</option>';
    list.forEach(function(p){
        var label = (p.title||'برنامج')+' - '+(p.branch_name||'كل الفروع')+'/'+(p.department_name||'')+' - '+(p.start_date||'')+' > '+(p.end_date||'');
        html+='<option value="'+p.id+'" data-title="'+cd041_esc(p.title)+'">'+cd041_esc(label)+'</option>';
    });
    sel.innerHTML=html;
}
function cd041_onProgramChange(){
    var sel=document.getElementById('cd041-program');
    var id=parseInt(sel.value,10);
    var info=document.getElementById('cd041-prog-info');
    if(!id){ cd041_program=null; info.classList.remove('open'); return; }
    cd041_program = cd041_programs.filter(function(p){return parseInt(p.id,10)===id;})[0]||null;
    if(!cd041_program){ info.classList.remove('open'); return; }
    var p=cd041_program;
    // عدد الأيام
    var days = '';
    if (p.start_date && p.end_date) {
        var s = new Date(p.start_date), e = new Date(p.end_date);
        if (!isNaN(s) && !isNaN(e)) {
            var diff = Math.round((e - s) / 86400000) + 1;
            if (diff > 0) days = ' (' + diff + ' ' + (diff === 1 ? 'يوم' : 'أيام') + ')';
        }
    }
    var typeLabel = (p.type === 'workshop') ? 'ورشة' : 'دورة';
    var typeIcon = (p.type === 'workshop') ? 'fa-chalkboard-teacher' : 'fa-graduation-cap';
    info.innerHTML =
        '<div class="cd041-pi-head"><i class="fas '+typeIcon+'"></i> ' + cd041_esc(p.title) + ' <span style="margin-right:auto;font-size:10px;opacity:.85;font-weight:600;">' + typeLabel + '</span></div>' +
        '<div class="cd041-pi-grid">' +
            '<div class="cd041-pi-cell"><b><i class="fas fa-book-open"></i> عنوان البرنامج</b><span>' + cd041_esc(p.title) + '</span></div>' +
            '<div class="cd041-pi-cell"><b><i class="fas fa-calendar-alt"></i> تواريخ البرنامج</b><span>' + cd041_esc(p.start_date||'-') + ' → ' + cd041_esc(p.end_date||'-') + days + '</span></div>' +
            '<div class="cd041-pi-cell"><b><i class="fas fa-building"></i> الفرع المنفّذ</b><span>' + cd041_esc(p.branch_name||'-') + '</span></div>' +
            '<div class="cd041-pi-cell"><b><i class="fas fa-users"></i> القسم المنفّذ</b><span>' + cd041_esc(p.department_name||'-') + '</span></div>' +
            '<div class="cd041-pi-cell"><b><i class="fas fa-user-tie"></i> المسؤول</b><span>' + cd041_esc(p.responsible_person||'-') + '</span></div>' +
            '<div class="cd041-pi-cell"><b><i class="fas fa-tag"></i> نوع البرنامج</b><span>' + typeLabel + '</span></div>' +
        '</div>';
    info.classList.add('open');
}

/* ===== employee search ===== */
function cd041_bindSearch(inputId, ddId, onSelect){
    var input=document.getElementById(inputId), box=document.getElementById(ddId);
    var timer=null;
    input.addEventListener('input', function(){
        var q=input.value.trim();
        clearTimeout(timer);
        if(q.length<2){ box.style.display='none'; return; }
        timer=setTimeout(function(){
            fetch(CD041_APP_URL+'/api/employees/search?q='+encodeURIComponent(q)).then(function(r){return r.json();}).then(function(list){
                if(!list||!list.length){ box.style.display='none'; return; }
                box.innerHTML = list.map(function(e){
                    return '<div class="cd041-dd-item" data-id="'+e.id+'"><b>'+cd041_esc(e.full_name)+'</b> <small>'+cd041_esc(e.emp_code)+' - '+(e.branch_name||'')+(e.department_name?' / '+e.department_name:'')+'</small></div>';
                }).join('');
                box.style.display='block';
                [].forEach.call(box.querySelectorAll('.cd041-dd-item'), function(it){
                    it.addEventListener('click', function(){
                        var id=it.getAttribute('data-id');
                        var emp=list.filter(function(x){return String(x.id)===id;})[0];
                        input.value=''; box.style.display='none';
                        onSelect(emp);
                    });
                });
            }).catch(function(){ box.style.display='none'; });
        },250);
    });
    document.addEventListener('click', function(e){ if(!input.parentNode.contains(e.target)) box.style.display='none'; });
}
cd041_bindSearch('cd041-emp-search','cd041-emp-dd', function(emp){ cd041_addEmployee(emp); });
cd041_bindSearch('cd041-evaluator','cd041-emp-dd', function(emp){
    cd041_evaluator_emp=emp;
    document.getElementById('cd041-evaluator').value=emp.full_name;
    var t=document.getElementById('cd041-evaluator-title');
    if(!t.value.trim() && emp.position_title) t.value=emp.position_title;
    cd041_toast('تم اختيار المقيم: '+emp.full_name,'ok');
});

/* ===== employee management ===== */
function cd041_addEmployee(emp){
    if(!emp||!emp.id){ return; }
    if(cd041_employees.some(function(e){return e.id===emp.id;})){ cd041_toast('الموظف مضاف بالفعل','err'); return; }
    cd041_employees.push(emp);
    cd041_results[emp.id]={}; cd041_final_grades[emp.id]=null;
    cd041_renderMatrix();
    cd041_toast('تمت إضافة '+emp.full_name,'ok');
}
function cd041_removeEmployee(id){
    cd041_employees = cd041_employees.filter(function(e){return e.id!==id;});
    delete cd041_results[id]; delete cd041_final_grades[id];
    cd041_renderMatrix();
}
function cd041_updateCount(){
    document.getElementById('cd041-count').innerHTML='عدد الموظفين: <b>'+cd041_employees.length+'</b>';
}

/* ===== quick-add forms ===== */
function cd041_toggleQa(id){
    var el=document.getElementById(id);
    el.classList.toggle('open');
    if(el.classList.contains('open')){
        if(id==='cd041-qa-emp'){ cd041_loadPositions('cd041-qa-emp-pos'); }
        if(id==='cd041-qa-eval'){ cd041_loadPositions('cd041-qa-eval-pos'); }
    }
}
function cd041_quickAddEmployee(){
    var fd=new FormData();
    fd.append('full_name', document.getElementById('cd041-qa-emp-name').value.trim());
    fd.append('emp_code', document.getElementById('cd041-qa-emp-code').value.trim());
    fd.append('branch_id', document.getElementById('cd041-qa-emp-branch').value);
    fd.append('department_id', document.getElementById('cd041-qa-emp-dept').value);
    fd.append('_token', CD041_CSRF);
    if(!fd.get('full_name')){ cd041_toast('أدخل الاسم','err'); return; }
    fetch(CD041_APP_URL+'/api/employees/quick-add',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
        if(res.success && res.employee){
            cd041_addEmployee(res.employee);
            document.getElementById('cd041-qa-emp').classList.remove('open');
            ['cd041-qa-emp-code','cd041-qa-emp-name'].forEach(function(i){document.getElementById(i).value='';});
            cd041_toast('تمت إضافة الموظف بنجاح','ok');
        } else { cd041_toast(res.message||'فشل الإضافة','err'); }
    }).catch(function(){ cd041_toast('خطأ في الاتصال','err'); });
}
function cd041_quickAddEvaluator(){
    var fd=new FormData();
    fd.append('full_name', document.getElementById('cd041-qa-eval-name').value.trim());
    fd.append('emp_code', document.getElementById('cd041-qa-eval-code').value.trim());
    fd.append('branch_id', document.getElementById('cd041-qa-eval-branch').value);
    fd.append('department_id', document.getElementById('cd041-qa-eval-dept').value);
    fd.append('_token', CD041_CSRF);
    if(!fd.get('full_name')){ cd041_toast('أدخل الاسم','err'); return; }
    fetch(CD041_APP_URL+'/api/employees/quick-add',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
        if(res.success && res.employee){
            cd041_evaluator_emp=res.employee;
            document.getElementById('cd041-evaluator').value=res.employee.full_name;
            document.getElementById('cd041-qa-eval').classList.remove('open');
            cd041_toast('تمت إضافة المقيم','ok');
        } else { cd041_toast(res.message||'فشل الإضافة','err'); }
    }).catch(function(){ cd041_toast('خطأ في الاتصال','err'); });
}

/* ===== matrix rendering ===== */
function cd041_renderHead(){
    var head=document.getElementById('cd041-matrix-head');
    // إزالة صف المعايير (إن وُجد) لإعادة بنائه
    var critRow=document.getElementById('cd041-criteria-head');
    if(critRow && critRow.parentNode) critRow.parentNode.removeChild(critRow);

    if(!cd041_template || !cd041_columns.length){
        head.innerHTML='<th class="cd041-c-idx">#</th><th class="cd041-c-emp">الموظف</th><th colspan="4" style="color:#b9d4c8;font-weight:400;">اختر قالبًا لبناء أعمدة المعايير</th><th class="cd041-c-score">النتيجة</th><th class="cd041-c-grade">التقدير</th><th class="cd041-c-status">الحالة</th><th class="cd041-c-act"></th>';
        return;
    }

    // === صف المعايير (علوي) — colspan = عدد أقسام كل معيار ===
    var critHtml='';
    (cd041_template.criteria||[]).forEach(function(crit){
        var n=(crit.sections||[]).filter(function(sec){ return cd041_type(sec.type||sec.evaluation_type)!=='overall_grade'; }).length;
        if(n>0){
            critHtml+='<th colspan="'+n+'" class="cd041-c-crit" title="'+cd041_esc(crit.name||crit.criterion_name)+'">'+cd041_short(crit.name||crit.criterion_name,22)+'<span class="cd041-w">'+(crit.weight||crit.criterion_weight||0)+'%</span></th>';
        }
    });

    var critTr=document.createElement('tr');
    critTr.id='cd041-criteria-head';
    critTr.innerHTML=
        '<th rowspan="2" class="cd041-c-idx">#</th>'+
        '<th rowspan="2" class="cd041-c-emp">الموظف</th>'+
        critHtml+
        '<th rowspan="2" class="cd041-c-score">النتيجة</th>'+
        '<th rowspan="2" class="cd041-c-grade">التقدير</th>'+
        '<th rowspan="2" class="cd041-c-status">الحالة</th>'+
        '<th rowspan="2" class="cd041-c-act"></th>';
    head.parentNode.insertBefore(critTr, head);

    // === صف الأقسام (سفلي) — كما كان ===
    var h='';
    cd041_columns.forEach(function(col){
        var t=cd041_type(col.type||col.evaluation_type);
        h+='<th title="'+cd041_esc(col.name)+'" class="cd041-c-sec">'+cd041_short(col.name,18)+'<span class="cd041-w">'+(col.weight||0)+'%</span></th>';
    });
    head.innerHTML=h;
}
function cd041_cellHtml(col, empId){
    var t=cd041_type(col.type||col.evaluation_type);
    var r=(cd041_results[empId]||{})[col.id]||{};
    // CD-041.2: notes 📝 icon — only when allow_notes is enabled in the template
    var allowNotes = (col.allow_notes===undefined||col.allow_notes===null)?true:(parseInt(col.allow_notes)===1||col.allow_notes===true);
    var notesBtn = function(){
        if(!allowNotes) return '';
        var has = r.notes && r.notes.trim();
        return ' <button type="button" class="cd041-notes-btn '+(has?'has':'')+'" onclick="cd041_openNotesModal('+empId+','+col.id+')" title="ملاحظات">📝</button>';
    };
    if(t==='percentage'){
        var v=r.score||''; var pct=v!==''?Math.max(0,Math.min(100,parseFloat(v)||0)):0;
        return '<input type="number" class="cd041-input-mini" min="0" max="100" step="1" value="'+cd041_esc(v)+'" oninput="cd041_onNum(this,'+empId+','+col.id+',\'pct\')"><div class="cd041-progress" id="cd041-bar-'+empId+'-'+col.id+'"><span style="width:'+pct+'%"></span></div>'+notesBtn();
    }
    if(t==='numeric'){
        var mv=col.max_value||100;
        return '<input type="number" class="cd041-input-mini" min="0" max="'+mv+'" step="0.5" value="'+cd041_esc(r.score||'')+'" oninput="cd041_onNum(this,'+empId+','+col.id+',\'num\')">'+notesBtn();
    }
    if(t==='boolean'){
        var on = r.score==='1', off = r.score==='0';
        return '<div class="cd041-bool"><button type="button" class="'+(on?'on-yes':'')+'" onclick="cd041_onBool('+empId+','+col.id+',1,this)">نعم</button><button type="button" class="'+(off?'on-no':'')+'" onclick="cd041_onBool('+empId+','+col.id+',0,this)">لا</button></div>'+notesBtn();
    }
    if(t==='textarea'){
        var has = r.text && r.text.trim();
        return '<button type="button" class="cd041-text-btn '+(has?'has':'')+'" onclick="cd041_openTextModal('+empId+','+col.id+')">📝</button>';
    }
    return '';
}
function cd041_gradeCellHtml(empId){
    var mode = cd041_template ? cd041_template.evaluation_mode : 'numeric';
    if(mode==='descriptive'){
        var opts='<option value="">—</option>';
        CD041_SCALES.forEach(function(s){ opts+='<option value="'+s.id+'"'+(cd041_final_grades[empId]==s.id?' selected':'')+'>'+cd041_esc(s.grade_label)+'</option>'; });
        return '<select class="cd041-grade-sel" onchange="cd041_onFinalGrade('+empId+',this.value)">'+opts+'</select>';
    }
    return '<span class="cd041-grade-readonly" id="cd041-grade-'+empId+'">-</span>';
}
function cd041_renderMatrix(){
    cd041_renderHead();
    var body=document.getElementById('cd041-matrix-body');
    var empty=document.getElementById('cd041-empty-msg');
    if(!cd041_employees.length){
        body.innerHTML=''; empty.style.display='block'; cd041_updateCount(); cd041_updateSummary(); return;
    }
    empty.style.display='none';
    var rows='';
    cd041_employees.forEach(function(emp,idx){
        rows+='<tr>';
        rows+='<td class="cd041-c-idx">'+(idx+1)+'</td>';
        rows+='<td class="cd041-c-emp"><div class="cd041-emp-name">'+cd041_esc(emp.full_name)+'</div><div class="cd041-emp-meta">'+cd041_esc(emp.emp_code||'')+(emp.branch_name?' - '+emp.branch_name:'')+'</div></td>';
        if(!cd041_template || !cd041_columns.length){
            rows+='<td colspan="4" style="color:var(--cd041-muted);font-size:11px;">اختر قالبًا لعرض المعايير</td>';
        } else {
            cd041_columns.forEach(function(col){ rows+='<td>'+cd041_cellHtml(col,emp.id)+'</td>'; });
            rows+='<td class="cd041-c-score"><span class="cd041-score" id="cd041-score-'+emp.id+'">-</span></td>';
            rows+='<td class="cd041-c-grade">'+cd041_gradeCellHtml(emp.id)+'</td>';
            rows+='<td class="cd041-c-status"><span class="cd041-status empty" id="cd041-status-'+emp.id+'">فارغ</span></td>';
        }
        rows+='<td class="cd041-c-act"><button type="button" class="cd041-remove" title="حذف" onclick="cd041_removeEmployee('+emp.id+')"><i class="fas fa-times"></i></button></td>';
        rows+='</tr>';
    });
    body.innerHTML=rows;
    cd041_updateCount();
    cd041_employees.forEach(function(emp){ cd041_updateRow(emp.id); });
}

/* ===== result setters ===== */
function cd041_setResult(empId, secId, key, val){
    if(!cd041_results[empId]) cd041_results[empId]={};
    if(!cd041_results[empId][secId]) cd041_results[empId][secId]={};
    cd041_results[empId][secId][key]=val;
}
function cd041_delResult(empId, secId, key){
    if(cd041_results[empId] && cd041_results[empId][secId]){ delete cd041_results[empId][secId][key]; if(!cd041_results[empId][secId].score && !cd041_results[empId][secId].text && !cd041_results[empId][secId].notes) delete cd041_results[empId][secId]; }
}
function cd041_onNum(el, empId, secId, kind){
    var v=el.value;
    if(v===''){ cd041_delResult(empId,secId,'score'); }
    else { var n=parseFloat(v); if(isNaN(n)) return; if(kind==='pct') n=Math.max(0,Math.min(100,n)); cd041_setResult(empId,secId,'score',String(n)); if(kind==='pct'){ var bar=document.getElementById('cd041-bar-'+empId+'-'+secId); if(bar){ bar.querySelector('span').style.width=Math.max(0,Math.min(100,n))+'%'; } } }
    cd041_updateRow(empId);
}
function cd041_onBool(empId, secId, val, btn){
    cd041_setResult(empId,secId,'score',val?'1':'0');
    var wrap=btn.parentNode; [].forEach.call(wrap.querySelectorAll('button'),function(b){b.className='';});
    btn.className = val?'on-yes':'on-no';
    cd041_updateRow(empId);
}
function cd041_onFinalGrade(empId, val){
    if(val==='') cd041_final_grades[empId]=null;
    else cd041_final_grades[empId]=parseInt(val,10);
    cd041_updateRow(empId);
}

/* ===== scoring & grades ===== */
function cd041_norm(sec, r){
    if(!r) return null;
    var t=cd041_type(sec.type||sec.evaluation_type);
    if(t==='textarea') return null;
    if(t==='boolean'){ return r.score==='1'?100:(r.score==='0'?0:null); }
    var v=parseFloat(r.score); if(isNaN(v)) return null;
    if(t==='percentage') return Math.max(0,Math.min(100,v));
    var max=parseFloat(sec.max_value)||100; if(max<=0) return null;
    return Math.max(0,Math.min(100,(v/max)*100));
}
function cd041_calcScore(empId){
    if(!cd041_template) return null;
    // لا تُعرض/تُحتسب نتيجة until اكتمال جميع الأقسام (تجنب 100% لصف جزئي
    // عندما يُملأ قسم واحد بـ 100 وتُترك البقية فارغة).
    if(!cd041_isRowDone(empId)) return null;
    var mode=cd041_template.evaluation_mode;
    if(mode==='descriptive'){
        var gid=cd041_final_grades[empId];
        if(!gid) return null;
        var sc=CD041_SCALES.filter(function(s){return String(s.id)===String(gid);})[0];
        if(!sc) return null;
        var mv=parseFloat(sc.max_value); return isNaN(mv)?(parseFloat(sc.numeric_value)||0):mv;
    }
    var total=0, cwUsed=0;
    (cd041_template.criteria||[]).forEach(function(crit){
        var cw=parseFloat(crit.weight)||0; var sum=0, wsum=0;
        (crit.sections||[]).forEach(function(sec){
            if(cd041_type(sec.type||sec.evaluation_type)==='overall_grade') return;
            var sw=parseFloat(sec.weight)||0;
            var r=(cd041_results[empId]||{})[sec.id];
            var n=cd041_norm(sec,r);
            if(n!==null){ sum+=n*sw; wsum+=sw; }
        });
        if(wsum>0){ total += (sum/wsum)*cw; cwUsed+=cw; }
    });
    if(cwUsed<=0) return null;
    return Math.round((total/cwUsed)*10)/10;
}
function cd041_deriveGrade(score){
    if(score===null||score===undefined||isNaN(score)) return null;
    // CD-041.3: range matching (min_value/max_value) — NOT numeric_value (1..5) vs score (0..100)
    var best=null;
    CD041_SCALES.forEach(function(s){
        var mn=parseFloat(s.min_value), mx=parseFloat(s.max_value);
        if(!isNaN(mn)&&!isNaN(mx) && score>=mn && score<=mx){ best=s; }
    });
    if(best) return {id: best.id, label: best.grade_label};
    // fallback: threshold labels (consistent with السلم الخماسي)
    var label;
    if(score>=90) label='ممتاز'; else if(score>=80) label='جيد جدًا'; else if(score>=70) label='جيد'; else if(score>=60) label='مقبول'; else label='ضعيف';
    return {id: null, label: label};
}
function cd041_isRowDone(empId){
    if(!cd041_columns.length) return false;
    for(var i=0;i<cd041_columns.length;i++){
        var col=cd041_columns[i]; var r=(cd041_results[empId]||{})[col.id]; var t=cd041_type(col.type||col.evaluation_type);
        if(t==='textarea'){ if(!(r&&r.text&&r.text.trim())) return false; }
        else { if(!r||r.score===undefined||r.score===null||r.score==='') return false; }
    }
    if(cd041_template && cd041_template.evaluation_mode==='descriptive' && !cd041_final_grades[empId]) return false;
    return true;
}
function cd041_hasAny(empId){
    var r=cd041_results[empId]||{}; return Object.keys(r).length>0;
}
function cd041_updateRow(empId){
    if(!cd041_template) return;
    var mode=cd041_template.evaluation_mode;
    var score=cd041_calcScore(empId);
    var scoreEl=document.getElementById('cd041-score-'+empId);
    if(scoreEl){ scoreEl.textContent = score===null?'-':score; scoreEl.className='cd041-score'+(score!==null&&score<60?' low':''); }
    if(mode!=='descriptive'){
        var gradeEl=document.getElementById('cd041-grade-'+empId);
        if(gradeEl){ var g=cd041_deriveGrade(score); gradeEl.textContent = g?g.label:'-'; }
    }
    var statusEl=document.getElementById('cd041-status-'+empId);
    if(statusEl){
        var done=cd041_isRowDone(empId), any=cd041_hasAny(empId);
        statusEl.className='cd041-status '+(done?'done':(any?'pending':'empty'));
        statusEl.textContent = done?'مكتمل':(any?'جزئي':'فارغ');
    }
    cd041_updateSummary();
}
function cd041_updateSummary(){
    var total=cd041_employees.length, done=0, sum=0, cnt=0;
    cd041_employees.forEach(function(emp){
        if(cd041_isRowDone(emp.id)) done++;
        var s=cd041_calcScore(emp.id);
        if(s!==null){ sum+=s; cnt++; }
    });
    document.getElementById('cd041-sum-total').textContent=total;
    document.getElementById('cd041-sum-done').textContent=done;
    document.getElementById('cd041-sum-avg').textContent = cnt>0 ? (Math.round((sum/cnt)*10)/10) : '-';
    var avg = cnt>0 ? (sum/cnt) : null;
    var g = cd041_deriveGrade(avg);
    document.getElementById('cd041-sum-grade').textContent = g ? g.label : '-';
}

/* ===== text modal ===== */
function cd041_openNotesModal(empId, secId){
    var col = cd041_columns.filter(function(c){return c.id===secId;})[0];
    var emp = cd041_employees.filter(function(e){return e.id===empId;})[0];
    if(!col||!emp) return;
    cd041_modal_ctx = {empId:empId, secId:secId, mode:'notes'};
    document.getElementById('cd041-modal-title').textContent = '📝 ملاحظات: '+cd041_short(col.name,30);
    document.getElementById('cd041-modal-meta').textContent = 'الموظف: '+emp.full_name+' ('+emp.emp_code+')';
    var r=(cd041_results[empId]||{})[secId]||{};
    var ph = col.notes_placeholder || 'اكتب ملاحظاتك هنا...';
    var ta=document.getElementById('cd041-modal-text'); ta.value=r.notes||''; ta.readOnly=false;
    ta.placeholder=ph;
    document.getElementById('cd041-modal-cc').textContent=ta.value.length+'/2000';
    document.getElementById('cd041-modal-all-wrap').style.display='';
    document.getElementById('cd041-modal-all').checked=false;
    document.getElementById('cd041-modal').classList.add('open'); ta.focus();
}
function cd041_openTextModal(empId, secId){
    var col = cd041_columns.filter(function(c){return c.id===secId;})[0];
    var emp = cd041_employees.filter(function(e){return e.id===empId;})[0];
    if(!col||!emp) return;
    cd041_modal_ctx = {empId:empId, secId:secId, mode:'text'};
    document.getElementById('cd041-modal-title').textContent = 'إدخال نصي: '+cd041_short(col.name,30);
    document.getElementById('cd041-modal-meta').textContent = 'الموظف: '+emp.full_name+' ('+emp.emp_code+')';
    var r=(cd041_results[empId]||{})[secId]||{};
    var ta=document.getElementById('cd041-modal-text'); ta.value=r.text||''; ta.readOnly=false;
    document.getElementById('cd041-modal-cc').textContent=ta.value.length+'/2000';
    document.getElementById('cd041-modal-all-wrap').style.display='';
    document.getElementById('cd041-modal-all').checked=false;
    document.getElementById('cd041-modal').classList.add('open'); ta.focus();
}
function cd041_closeModal(){ document.getElementById('cd041-modal').classList.remove('open'); cd041_modal_ctx=null; }
function cd041_saveModal(){
    if(!cd041_modal_ctx){ cd041_closeModal(); return; }
    var ctx=cd041_modal_ctx;
    if(ctx.mode==='text'){
        var txt=document.getElementById('cd041-modal-text').value;
        var all=document.getElementById('cd041-modal-all').checked;
        if(all){
            cd041_employees.forEach(function(emp){ cd041_setResult(emp.id, ctx.secId, 'text', txt); cd041_updateRow(emp.id); });
            cd041_toast('تم تطبيق النص على الكل','ok');
        } else {
            cd041_setResult(ctx.empId, ctx.secId, 'text', txt);
            cd041_updateRow(ctx.empId);
        }
        cd041_renderMatrix();
    } else if(ctx.mode==='notes'){
        var nts=document.getElementById('cd041-modal-text').value;
        var allN=document.getElementById('cd041-modal-all').checked;
        if(allN){
            cd041_employees.forEach(function(emp){ cd041_setResult(emp.id, ctx.secId, 'notes', nts); cd041_updateRow(emp.id); });
            cd041_toast('تم تطبيق الملاحظات على الكل','ok');
        } else {
            cd041_setResult(ctx.empId, ctx.secId, 'notes', nts);
            cd041_updateRow(ctx.empId);
        }
        cd041_renderMatrix();
    } else if(ctx.mode==='preview'){
        // nothing to persist
    }
    cd041_closeModal();
}

/* ===== bulk toolbar actions ===== */
function cd041_applyTopToAll(){
    if(!cd041_employees.length){ cd041_toast('لا يوجد موظفون','err'); return; }
    var src=cd041_employees[0]; var sr=cd041_results[src.id]||{};
    if(!Object.keys(sr).length){ cd041_toast('لا توجد قيم في الصف الأول','err'); return; }
    cd041_employees.forEach(function(emp){
        if(emp.id===src.id) return;
        var copy={};
        cd041_columns.forEach(function(col){ var v=sr[col.id]; if(v){ var c={}; if(v.score!==undefined) c.score=v.score; if(v.text!==undefined) c.text=v.text; if(v.notes!==undefined) c.notes=v.notes; copy[col.id]=c; } });
        cd041_results[emp.id]=copy;
        if(cd041_template && cd041_template.evaluation_mode==='descriptive'){ cd041_final_grades[emp.id]=cd041_final_grades[src.id]; }
    });
    cd041_renderMatrix();
    cd041_toast('تم تطبيق قيم الصف الأول على الكل','ok');
}
function cd041_fillDefault(){
    if(!cd041_columns.length){ cd041_toast('اختر قالبًا','err'); return; }
    cd041_employees.forEach(function(emp){
        cd041_columns.forEach(function(col){
            var t=cd041_type(col.type||col.evaluation_type);
            if(t==='percentage'){ cd041_setResult(emp.id,col.id,'score','100'); }
            else if(t==='numeric'){ cd041_setResult(emp.id,col.id,'score',String(col.max_value||100)); }
            else if(t==='boolean'){ cd041_setResult(emp.id,col.id,'score','1'); }
        });
    });
    cd041_renderMatrix();
    cd041_toast('تم الملء الافتراضي','ok');
}
function cd041_buildGradeAllSel(){
    var sel=document.getElementById('cd041-grade-all-sel');
    var html='<option value="">— اختر التقدير —</option>';
    CD041_SCALES.forEach(function(s){ html+='<option value="'+s.id+'">'+cd041_esc(s.grade_label)+'</option>'; });
    sel.innerHTML=html;
}
function cd041_applyGradeToAll(){
    var v=document.getElementById('cd041-grade-all-sel').value;
    if(!v){ cd041_toast('اختر التقدير أولًا','err'); return; }
    cd041_employees.forEach(function(emp){ cd041_final_grades[emp.id]=parseInt(v,10); cd041_updateRow(emp.id); });
    cd041_renderMatrix();
    cd041_toast('تم تطبيق التقدير على الكل','ok');
}
function cd041_clearAll(){
    if(!cd041_employees.length){ return; }
    if(!confirm('تفريغ كل النتائج؟')) return;
    cd041_employees.forEach(function(emp){ cd041_results[emp.id]={}; cd041_final_grades[emp.id]=null; });
    cd041_renderMatrix();
    cd041_toast('تم التفريغ','ok');
}
function cd041_reset(){
    if(!confirm('تصفير الكل سيحذف الموظفين والنتائج. متابعة؟')) return;
    cd041_employees=[]; cd041_results={}; cd041_final_grades={}; cd041_program=null;
    document.getElementById('cd041-prog-info').classList.remove('open');
    document.getElementById('cd041-program').value='';
    cd041_renderMatrix();
    cd041_toast('تم التصفير','ok');
}

/* ===== build payload ===== */
function cd041_buildPayload(){
    var date=document.getElementById('cd041-date').value;
    var evalName=document.getElementById('cd041-evaluator').value.trim();
    var evalTitle=document.getElementById('cd041-evaluator-title').value.trim();
    var contextId=0, contextType='general';
    if(cd041_program && cd041_template){ contextType = cd041_targetToContext(cd041_template.target_type) || 'general'; contextId = cd041_program.id; }
    var evaluations = cd041_employees.map(function(emp){
        var res={};
        cd041_columns.forEach(function(col){
            var r=(cd041_results[emp.id]||{})[col.id]||{};
            var entry={}; var t=cd041_type(col.type||col.evaluation_type);
            if(t==='textarea'){ entry.text = r.text||''; }
            else { entry.score = (r.score!==undefined&&r.score!==null)?String(r.score):''; }
            entry.notes = r.notes||'';
            res[col.id]=entry;
        });
        var finalGradeId = cd041_final_grades[emp.id]||null;
        if(cd041_template && (cd041_template.evaluation_mode==='numeric'||cd041_template.evaluation_mode==='mixed')){
            var g=cd041_deriveGrade(cd041_calcScore(emp.id)); finalGradeId = g?g.id:null;
        }
        return {employee_id: emp.id, results: res, final_grade_id: finalGradeId};
    });
    return {
        _token: CD041_CSRF,
        template_id: cd041_template?cd041_template.id:null,
        evaluation_date: date,
        evaluator_name: evalName,
        evaluator_title: evalTitle,
        context_id: contextId,
        context_type: contextType,
        evaluations: evaluations
    };
}

/* ===== preview & save ===== */
function cd041_preview(){
    if(!cd041_template){ cd041_toast('اختر القالب','err'); return; }
    var p=cd041_buildPayload();
    cd041_modal_ctx={mode:'preview'};
    document.getElementById('cd041-modal-title').textContent='معاينة الحمولة (JSON)';
    document.getElementById('cd041-modal-meta').textContent='عدد التقييمات: '+p.evaluations.length+' · القالب: '+cd041_template.id;
    var ta=document.getElementById('cd041-modal-text'); ta.value=JSON.stringify(p,null,2); ta.readOnly=true;
    document.getElementById('cd041-modal-cc').textContent=ta.value.length+' حرف';
    document.getElementById('cd041-modal-all-wrap').style.display='none';
    document.getElementById('cd041-modal').classList.add('open');
}
function cd041_saveAll(){
    cd041_clearHighlight();
    cd041_dismissSticky();
    var firstMissing=null, msg='';
    if(!cd041_template){ msg='اختر القالب أولًا'; firstMissing=document.getElementById('cd041-template'); }
    else if(!cd041_employees.length){ msg='أضف موظفين أولًا (ابحث أو ＋ إضافة)'; firstMissing=document.getElementById('cd041-emp-search'); }
    else if(!document.getElementById('cd041-date').value){ msg='اختر تاريخ التقييم'; firstMissing=document.getElementById('cd041-date'); }
    else if(!document.getElementById('cd041-evaluator').value.trim()){ msg='أدخل اسم المقيّم'; firstMissing=document.getElementById('cd041-evaluator'); }
    if(msg){
        cd041_sticky('⚠️ ' + msg + ' — لا يمكن الحفظ قبل إكمال الشروط.', 'err', function(){ if(firstMissing){ cd041_highlightField(firstMissing); firstMissing.scrollIntoView({behavior:'smooth',block:'center'}); } });
        if(firstMissing){ cd041_highlightField(firstMissing); }
        cd041_toast(msg,'err');
        return;
    }
    var payload=cd041_buildPayload();
    var btn=document.querySelector('button[onclick*="cd041_saveAll"]') || [...document.querySelectorAll('button')].find(function(b){return (b.textContent||'').trim()==='حفظ الكل';});
    var old=btn ? btn.innerHTML : '';
    if(btn){ btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> جارٍ الحفظ...'; btn.disabled=true; }
    fetch(CD041_APP_URL+'/evaluations/bulk-save',{
        method:'POST',
        headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify(payload)
    }).then(function(r){return r.json();}).then(function(res){
        if(btn){ btn.innerHTML=old; btn.disabled=false; }
        if(res && res.success){
            cd041_sticky('✅ ' + (res.message||'تم الحفظ') + ' — ' + (res.saved||0) + ' تقييم', 'ok');
            cd041_toast((res.message||'تم الحفظ')+' ('+(res.saved||0)+' تقييم)','ok');
        } else {
            cd041_sticky('❌ ' + ((res&&res.message)?res.message:'فشل الحفظ'), 'err');
            cd041_toast((res&&res.message)?res.message:'فشل الحفظ','err');
            if(res && res.errors){ console.warn('CD-041 save errors', res.errors); }
        }
    }).catch(function(){ if(btn){ btn.innerHTML=old; btn.disabled=false; } cd041_toast('خطأ في الاتصال بالخادم','err'); });
}

/* ===== init ===== */
var sx=document.getElementById('cd041-sticky-x'); if(sx) sx.addEventListener('click', cd041_dismissSticky);
cd041_buildGradeAllSel();
cd041_updateSummary();
console.log('[CD-041] bulk-create matrix view ready');
</script>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
