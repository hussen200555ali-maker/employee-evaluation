<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<div class="container py-4 excel-workspace" dir="rtl">
    <div class="excel-workspace__hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="eyebrow"><i class="fas fa-shield-alt"></i> تبادل بيانات محكوم</span>
            <h2 class="mb-1">استيراد نتائج التقييم من Excel</h2>
            <p class="mb-0">كل صف = تقييم واحد. حتى 100 صف افتراضياً، 1000 كحد أقصى. استخدم القالب المنزّل.</p>
        </div>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="fas fa-download me-1"></i> تنزيل القالب</h6>
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-bold">نوع التقييم</label>
                    <select id="evalTypeSelect" class="form-select">
                        <option value="employee">موظف فردي</option>
                        <option value="employee_in_course">موظف داخل دورة / ورشة</option>
                        <option value="course">دورة / ورشة (ككيان)</option>
                        <option value="unit">وحدة تنظيمية</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">القالب</label>
                    <select id="templateSelect" class="form-select">
                        <?php if (!empty($templates)): foreach ($templates as $tpl): ?>
                        <option value="<?= (int) $tpl->id ?>" data-type="<?= htmlspecialchars($tpl->target_type) ?>"><?= htmlspecialchars($tpl->template_name) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="col-md-2" id="fillModeWrap">
                    <label class="form-label">وضع التعبئة</label>
                    <select id="fillMode" class="form-select">
                        <option value="empty">قالب فارغ</option>
                        <option value="branch">تعبئة من فرع</option>
                        <option value="department">تعبئة من قسم</option>
                    </select>
                </div>
                <div class="col-md-2" id="branchWrap" style="display:none;">
                    <label class="form-label">الفرع</label>
                    <select id="branchSelect" class="form-select">
                        <option value="">-- اختر الفرع --</option>
                        <?php if (!empty($branches)): foreach ($branches as $br): ?>
                        <option value="<?= (int) $br->id ?>"><?= htmlspecialchars($br->name) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="col-md-2" id="deptWrap" style="display:none;">
                    <label class="form-label">القسم</label>
                    <select id="deptSelect" class="form-select"><option value="">-- اختر القسم --</option></select>
                </div>
                <div class="col-md-2" id="periodWrap" style="display:none;">
                    <label class="form-label fw-bold">الفترة</label>
                    <input type="text" id="periodInput" class="form-control" placeholder="YYYY-MM" maxlength="7">
                    <div class="form-text" style="font-size:0.75rem;">مثلاً: 2026-01</div>
                </div>
                <div class="col-md-2" id="maxRowsWrap">
                    <label class="form-label">عدد الموظفين</label>
                    <input type="number" id="maxRowsInput" class="form-control" min="1" max="1000" value="100">
                    <div id="maxRowsHint" class="form-text" style="font-size:0.75rem;"></div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <a id="downloadTemplateBtn" class="btn btn-success" href="<?= APP_URL ?>/evaluations/excel/template<?php if (!empty($templates)): ?>?template_id=<?= (int) $templates[0]->id ?><?php endif; ?>"><i class="fas fa-file-excel ms-1"></i> تنزيل</a>
                    <small class="text-muted align-self-center" style="font-size:0.7rem;">100 افتراضي/1000 أقصى</small>
                </div>
            </div>
        </div>
    </div>
    <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
        <i class="fas fa-info-circle mt-1"></i>
        <div class="small" id="instructionsBox">
            <strong>تعليمات:</strong>
            <span id="instructionsText">الأعمدة A-E (الرقم الوظيفي، الاسم، الصفة، الفرع، القسم) — يجب إدخال الرقم الوظيفي أو الاسم على الأقل. عند إنشاء موظف جديد يُستخدم الفرع/القسم. الأعمدة F+ بنود القالب. الأعمدة الأخيرة: ملاحظة عامة، تاريخ التقييم YYYY-MM-DD أو DD-MM-YYYY، مسؤول التقييم، الصفة.</span>
            <span>القوائم والتلوين للمساعدة فقط — التحقق النهائي خادمي.</span>
        </div>
    </div>
<?php if ($preview === null): ?>
    <section class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= APP_URL ?>/evaluations/excel/preview" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <label class="form-label fw-bold">ملف XLSX</label>
                <input type="file" name="workbook" class="form-control" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                <div class="form-text">الحد الأقصى 10 ميغابايت و 100 صف افتراضي / 1000 أقصى. استخدم القالب المنزّل ولا تغيّر الترويسات.</div>
                <button class="btn btn-primary mt-3"><i class="fas fa-upload ms-1"></i> رفع ومعاينة</button>
            </form>
        </div>
    </section>
<?php else: ?>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="alert alert-secondary mb-0">القالب: <strong><?= htmlspecialchars($preview['template_name'] ?? '-') ?></strong></div></div>
        <div class="col-md-3"><div class="alert alert-secondary mb-0">إجمالي الصفوف: <strong><?= (int) $preview['total'] ?></strong></div></div>
        <div class="col-md-3"><div class="alert alert-success mb-0">صالحة: <strong><?= (int) $preview['valid'] ?></strong></div></div>
        <div class="col-md-3"><div class="alert <?= (int) $preview['invalid'] > 0 ? 'alert-danger' : 'alert-success' ?> mb-0">غير صالحة: <strong><?= (int) $preview['invalid'] ?></strong></div></div>
    </div>
    <?php if ((int) $preview['invalid'] > 0): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2"><i class="fas fa-exclamation-triangle"></i><span><strong>تم رفض الملف بالكامل.</strong> صحّح الأخطاء وأعد الرفع.</span></div>
    <?php endif; ?>
    <section class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 align-middle" style="font-size: 0.85rem;">
                <thead class="table-dark"><tr>
                    <th>الصف</th>
                    <?php $targetType = $preview['target_type'] ?? 'employee'; ?>
                    <?php if ($targetType === 'unit'): ?>
                        <th>نوع الكيان</th><th>رمز الكيان</th><th>الفترة</th>
                    <?php elseif (in_array($targetType, ['employee_in_course','employee_in_workshop'])): ?>
                        <th>رقم الموظف</th><th>اسم الموظف</th><th>السياق</th>
                    <?php elseif (in_array($targetType, ['course','workshop'])): ?>
                        <th>العنوان</th><th>الفرع</th><th>القسم</th><th>تاريخ البداية</th><th>تاريخ النهاية</th>
                    <?php else: ?>
                        <th>الرقم</th><th>الاسم</th><th>الصفة</th><th>الفرع</th><th>القسم</th>
                    <?php endif; ?>
                    <th>تاريخ التقييم</th><th>درجات البنود</th><th>ملاحظة</th><th>النتيجة</th>
                </tr></thead>
                <tbody>
                <?php foreach ($preview['rows'] as $row): ?>
                    <?php $hasError = !empty($row['errors']); ?>
                    <tr class="<?= $hasError ? 'table-danger' : 'table-success' ?>">
                        <td class="text-center fw-bold"><?= (int) $row['row'] ?></td>
                        <?php if ($targetType === 'unit'): ?>
                            <td><?= htmlspecialchars($row['payload']['unit_type'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['payload']['unit_code'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['payload']['period'] ?? '') ?></td>
                        <?php elseif (in_array($targetType, ['employee_in_course','employee_in_workshop'])): ?>
                            <td><?= htmlspecialchars($row['payload']['emp_code'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['payload']['full_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['payload']['course_id'] ?? $row['payload']['workshop_id'] ?? '') ?></td>
                        <?php elseif (in_array($targetType, ['course','workshop'])): ?>
                            <td><?= htmlspecialchars($row['payload']['full_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['payload']['branch_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['payload']['department_name'] ?? '') ?></td>
                            <td dir="ltr" class="text-center"><?= htmlspecialchars($row['payload']['start_date'] ?? '') ?></td>
                            <td dir="ltr" class="text-center"><?= htmlspecialchars($row['payload']['end_date'] ?? '') ?></td>
                        <?php else: ?>
                            <td><?= htmlspecialchars($row['payload']['emp_code'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['payload']['full_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['payload']['nickname'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['payload']['branch_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['payload']['department_name'] ?? '') ?></td>
                        <?php endif; ?>
                        <td dir="ltr" class="text-center"><?= htmlspecialchars($row['payload']['evaluation_date'] ?? '') ?></td>
                        <td><?php if (!empty($row['section_values'])): foreach ($row['section_values'] as $secId => $val): ?><span class="badge bg-light text-dark border me-1 mb-1"><?= htmlspecialchars((string) $val) ?></span><?php endforeach; else: ?><span class="text-muted">—</span><?php endif; ?></td>
                        <td><?= htmlspecialchars($row['payload']['notes'] ?? '') ?></td>
                        <td><?php if (!$hasError): ?><span class="badge bg-success"><i class="fas fa-check"></i> صالح</span><?php else: ?><ul class="mb-0 ps-3 small text-danger"><?php foreach ($row['errors'] as $errMsg): ?><li><?= htmlspecialchars($errMsg) ?></li><?php endforeach; ?></ul><?php if (!empty($row['columns_with_errors'])): ?><div class="small text-muted mt-1">الأعمدة: <?= htmlspecialchars(implode('، ', $row['columns_with_errors'])) ?></div><?php endif; ?><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php if ((int) $preview['invalid'] === 0 && (int) $preview['valid'] > 0): ?>
        <form method="post" action="<?= APP_URL ?>/evaluations/excel/commit/<?= (int) $preview['batch_id'] ?>" class="mt-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <button class="btn btn-success btn-lg"><i class="fas fa-check-double ms-1"></i> تأكيد الالتزام — <?= (int) $preview['valid'] ?> تقييماً</button>
            <a href="<?= APP_URL ?>/evaluations/excel/import" class="btn btn-outline-secondary btn-lg ms-2">إلغاء</a>
        </form>
    <?php else: ?>
        <div class="mt-3 d-flex gap-2"><a href="<?= APP_URL ?>/evaluations/excel/import" class="btn btn-primary"><i class="fas fa-arrow-right ms-1"></i> العودة</a><button class="btn btn-success" disabled>تأكيد الالتزام (معطّل — يوجد أخطاء)</button></div>
    <?php endif; ?>
<?php endif; ?>
</div>
<script>
(function(){
    async function refreshCsrf(){
        try{
            var r=await fetch('<?= APP_URL ?>/csrf/token',{headers:{'X-Requested-With':'XMLHttpRequest'}});
            var j=await r.json();
            if(j.token){
                document.querySelectorAll('input[name="csrf_token"],input[name="_token"]').forEach(function(i){i.value=j.token;});
                var m=document.querySelector('meta[name="csrf-token"]'); if(m) m.content=j.token;
                return j.token;
            }
        }catch(e){}
        return null;
    }
    document.querySelectorAll('form[method="post"][action*="excel/preview"],form[method="post"][action*="excel/commit"]').forEach(function(f){
        f.addEventListener('submit', function(e){
            var tok=f.querySelector('input[name="csrf_token"],input[name="_token"]');
            var meta=document.querySelector('meta[name="csrf-token"]');
            if(tok && meta && !tok.value) tok.value=meta.content;
        });
    });
    var _origFetch=window.fetch;
    window.fetch=function(url,opts){
        opts=opts||{};
        if(typeof url==='string' && url.indexOf('/evaluations/excel/preview')!==-1 && opts.method==='POST'){
            return _origFetch(url,opts).then(function(res){
                if(res.status===403) return refreshCsrf().then(function(t){
                    if(!t) throw res;
                    if(opts.headers) opts.headers['X-CSRF-Token']=t;
                    if(opts.body instanceof FormData){ opts.body.set('csrf_token',t); opts.body.set('_token',t); }
                    return _origFetch(url,opts);
                });
                return res;
            });
        }
        return _origFetch(url,opts);
    };

    var evalType=document.getElementById('evalTypeSelect');
    var tpl=document.getElementById('templateSelect');
    var mode=document.getElementById('fillMode');
    var br=document.getElementById('branchSelect');
    var dept=document.getElementById('deptSelect');
    var brWrap=document.getElementById('branchWrap');
    var deptWrap=document.getElementById('deptWrap');
    var fillModeWrap=document.getElementById('fillModeWrap');
    var periodWrap=document.getElementById('periodWrap');
    var periodInput=document.getElementById('periodInput');
    var maxRowsWrap=document.getElementById('maxRowsWrap');
    var btn=document.getElementById('downloadTemplateBtn');
    var maxRowsInput=document.getElementById('maxRowsInput');
    var maxRowsHint=document.getElementById('maxRowsHint');
    var instructionsText=document.getElementById('instructionsText');
    var branchDepts = <?= json_encode(array_reduce($branches ?? [], function($c,$b){ $c[$b->id]=array_map(fn($d)=>['id'=>$d->id,'name'=>$d->name], $b->departments ?? []); return $c; }, []), JSON_UNESCAPED_UNICODE) ?>;

    var typeInstructions={
        'employee':'الأعمدة A-E (الرقم الوظيفي، الاسم، الصفة، الفرع، القسم) — يجب إدخال الرقم الوظيفي أو الاسم على الأقل. عند إنشاء موظف جديد يُستخدم الفرع/القسم. الأعمدة F+ بنود القالب. الأعمدة الأخيرة: ملاحظة عامة، تاريخ التقييم YYYY-MM-DD أو DD-MM-YYYY، مسؤول التقييم، الصفة.',
        'employee_in_course':'الأعمدة: رقم الموظف أو الاسم + معرّف الدورة (course_id) + تاريخ التقييم + درجات البنود + ملاحظة + مسؤول التقييم + الصفة.',
        'employee_in_workshop':'الأعمدة: رقم الموظف أو الاسم + معرّف الورشة (workshop_id) + تاريخ التقييم + درجات البنود + ملاحظة + مسؤول التقييم + الصفة.',
        'course':'الأعمدة: عنوان الدورة + الفرع + القسم + تاريخ البداية + تاريخ النهاية + درجات البنود + ملاحظة + تاريخ التقييم + مسؤول التقييم + الصفة.',
        'workshop':'الأعمدة: عنوان الورشة + الفرع + القسم + تاريخ البداية + تاريخ النهاية + درجات البنود + ملاحظة + تاريخ التقييم + مسؤول التقييم + الصفة.',
        'unit':'الأعمدة: نوع الكيان (branch/department) + رمز الكيان (اسم الفرع/القسم) + الفترة (YYYY-MM) + تاريخ التقييم + درجات البنود + ملاحظة + مسؤول التقييم + الصفة. يجب إدخال الرمز والفترة.'
    };

    var typeLabels={
        'employee':'موظف فردي',
        'employee_in_course':'موظف داخل دورة',
        'employee_in_workshop':'موظف داخل ورشة',
        'course':'دورة (ككيان)',
        'workshop':'ورشة (ككيان)',
        'unit':'وحدة تنظيمية'
    };

    var typeToOption={
        'employee':'employee',
        'employee_in_course':'employee_in_course',
        'employee_in_workshop':'employee_in_course',
        'course':'course',
        'workshop':'course',
        'unit':'unit'
    };

    function getSelectedType(){
        if(!evalType) return 'employee';
        return evalType.value;
    }

    function filterTemplates(){
        var selectedType=getSelectedType();
        var options=tpl.querySelectorAll('option');
        var firstVisible=null;
        options.forEach(function(opt){
            if(!opt.dataset.type){ opt.style.display=''; if(!firstVisible) firstVisible=opt; return; }
            var match=opt.dataset.type===selectedType;
            opt.style.display=match?'':'none';
            if(match && !firstVisible) firstVisible=opt;
        });
        if(firstVisible) tpl.value=firstVisible.value;
        updateBtn();
    }

    function toggleFields(){
        var t=getSelectedType();
        var isUnit=(t==='unit');
        var isCourseLike=(t==='course'||t==='workshop');
        var isContext=(t==='employee_in_course'||t==='employee_in_workshop');
        var isEmployee=(t==='employee');

        brWrap.style.display=(isEmployee)?'':'none';
        deptWrap.style.display='none';
        fillModeWrap.style.display=(isEmployee)?'':'none';
        periodWrap.style.display=isUnit?'':'none';
        maxRowsWrap.style.display='';

        if(isUnit){ mode.value='empty'; }
        if(isContext||isCourseLike){ mode.value='empty'; }

        if(isUnit){
            var mo=mode?mode.value:'empty';
            brWrap.style.display=(mo==='branch'||mo==='department')?'':'none';
            deptWrap.style.display=(mo==='department')?'':'none';
        }

        instructionsText.textContent=typeInstructions[t]||typeInstructions['employee'];
        filterTemplates();
    }

    function updateBtn(){
        var tid=tpl?tpl.value:'';
        var t=getSelectedType();
        var m=mode?mode.value:'empty';
        var maxRows=maxRowsInput?maxRowsInput.value:'100';
        var url='<?= APP_URL ?>/evaluations/excel/template?template_id='+encodeURIComponent(tid);
        if(t==='unit'){
            if(periodInput&&periodInput.value) url+='&period='+encodeURIComponent(periodInput.value);
        } else {
            if(m==='branch' && br && br.value) url+='&branch_id='+encodeURIComponent(br.value);
            if(m==='department' && br && br.value) url+='&branch_id='+encodeURIComponent(br.value);
            if(m==='department' && dept && dept.value) url+='&department_id='+encodeURIComponent(dept.value);
        }
        url+='&max_rows='+encodeURIComponent(maxRows);
        if(btn) btn.href=url;
    }

    function validateMaxRows(){
        var v=parseInt(maxRowsInput.value,10);
        if(isNaN(v) || v<1){ maxRowsHint.textContent=''; return; }
        if(v>1000){ maxRowsHint.textContent='الحد الأقصى 1000'; maxRowsHint.className='form-text text-danger'; }
        else if(v>100){ maxRowsHint.textContent='قد يستغرق وقتاً أطول مع الأعداد الكبيرة'; maxRowsHint.className='form-text text-warning'; }
        else { maxRowsHint.textContent=''; }
    }

    if(evalType) evalType.addEventListener('change', toggleFields);
    if(mode) mode.addEventListener('change', function(){
        var t=getSelectedType();
        if(t==='unit'){
            var m=mode?mode.value:'empty';
            brWrap.style.display=(m==='branch'||m==='department')?'':'none';
            deptWrap.style.display=(m==='department')?'':'none';
        }
        updateBtn();
    });
    if(tpl) tpl.addEventListener('change', updateBtn);
    if(maxRowsInput){ maxRowsInput.addEventListener('input', function(){ validateMaxRows(); updateBtn(); }); maxRowsInput.addEventListener('change', function(){ validateMaxRows(); updateBtn(); }); }
    if(br) br.addEventListener('change', function(){
        var list=branchDepts[this.value]||[];
        dept.innerHTML='<option value="">-- اختر القسم --</option>';
        list.forEach(function(d){ var o=document.createElement('option'); o.value=d.id; o.textContent=d.name; dept.appendChild(o); });
        updateBtn();
    });
    if(dept) dept.addEventListener('change', updateBtn);
    if(periodInput) periodInput.addEventListener('input', updateBtn);

    toggleFields();
    validateMaxRows();
})();
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
