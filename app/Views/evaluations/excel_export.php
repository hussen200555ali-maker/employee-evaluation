<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
$templates = $templates ?? [];
$branches = $branches ?? [];
$programs = $programs ?? [];
$limits = $limits ?? ['min_rows' => 50, 'max_rows' => 2000, 'max_file_size_mb' => 50];
$typeLabels = [
    'employee' => 'فردي',
    'skill' => 'مهاري',
    'employee_in_course' => 'موظف داخل دورة',
    'employee_in_workshop' => 'موظف داخل ورشة',
];
$programTypes = ['employee_in_course', 'employee_in_workshop'];
?>
<style>
.cd043-wrap{background:#f5f8f6;min-height:100vh;direction:rtl;font-family:'Cairo',sans-serif}
.cd043-page{max-width:1000px;margin:0 auto;padding:14px}
.cd043-hdr{background:linear-gradient(135deg,#0c2823,#0d5e52);color:#fff;padding:14px 20px;border-radius:10px;margin-bottom:10px;position:relative}
.cd043-hdr::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:#c8943f}
.cd043-hdr h2{font-size:16px;font-weight:800;margin:0}
.cd043-hdr .sub{font-size:11px;opacity:.85;margin-top:2px}
.cd043-card{background:#fff;border:1px solid #e0e8e4;border-radius:10px;margin-bottom:10px;overflow:hidden}
.cd043-card-h{background:linear-gradient(180deg,#fbfdfc,#f1f7f3);padding:10px 16px;border-bottom:1px solid #e0e8e4;font-weight:800;color:#0c2823;font-size:13px;display:flex;align-items:center;gap:8px}
.cd043-card-b{padding:14px 16px;display:grid;grid-template-columns:1fr 1fr;gap:12px}
.cd043-fld{min-width:0}
.cd043-fld.full{grid-column:1 / -1}
.cd043-fld label{display:block;font-size:10px;color:#6c757d;font-weight:700;margin-bottom:4px}
.cd043-fld select,.cd043-fld input{width:100%;padding:8px 10px;border:1px solid #e0e8e4;border-radius:6px;font-family:'Cairo';font-size:12px;background:#fff}
.cd043-fld select:disabled,.cd043-fld input:disabled{background:#f3f4f6;color:#9ca3af}
.cd043-hint{font-size:10px;color:#6c757d;margin-top:4px;line-height:1.6}
.cd043-actions{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.cd043-btn{padding:10px 22px;border:none;border-radius:8px;font-family:'Cairo';font-size:12px;font-weight:700;cursor:pointer;display:inline-block;text-decoration:none}
.cd043-btn-ok{background:#1b6b32;color:#fff}
.cd043-btn-ok.disabled{background:#9ca3af;pointer-events:none}
.cd043-btn-ghost{background:#e8e8e8;color:#6c757d}
.cd043-alert{padding:10px 14px;border-radius:8px;font-size:11px;background:#fff3cd;color:#856404;border:1px solid #ffe69b;margin-bottom:10px}
.cd043-steps{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;font-size:10px;color:#0c2823}
.cd043-step{background:#e8f5e9;border:1px solid #c8e6c9;border-radius:999px;padding:4px 10px;font-weight:700}
.cd043-radio-group{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.cd043-radio{border:1px solid #e0e8e4;border-radius:8px;padding:10px 12px;display:flex;gap:8px;align-items:flex-start;cursor:pointer;background:#fff}
.cd043-radio input{margin-top:3px}
.cd043-radio .t{font-size:12px;font-weight:700;color:#0c2823}
.cd043-radio .d{font-size:10px;color:#6c757d;margin-top:2px;line-height:1.5}
.cd043-radio.disabled{opacity:.55;cursor:not-allowed;background:#f8faf9}
.cd043-warning{display:none;padding:10px 14px;border-radius:8px;font-size:11px;background:#fff3cd;color:#856404;border:1px solid #ffe69b;margin-top:10px;line-height:1.7}
@media(max-width:768px){.cd043-card-b,.cd043-radio-group{grid-template-columns:1fr}}
</style>
<div class="cd043-wrap">
<div class="cd043-page">
  <div class="cd043-hdr">
    <h2>📤 تصدير قالب تقييم Excel</h2>
    <div class="sub">CD-043 / Smart Prefill · اختر النطاق وطريقة التعبئة ثم نزّل قالباً جاهزاً — ملف .xlsx فقط</div>
  </div>

  <div class="cd043-steps">
    <span class="cd043-step">① القالب</span>
    <span class="cd043-step">② الفرع/القسم</span>
    <span class="cd043-step">③ البرنامج</span>
    <span class="cd043-step">④ طريقة التعبئة</span>
    <span class="cd043-step">⑤ الصفوف</span>
    <span class="cd043-step">⑥ تنزيل</span>
  </div>

  <div class="cd043-alert">
    الصفوف المسموحة: <?= (int)$limits['min_rows'] ?>–<?= (int)$limits['max_rows'] ?> صفاً · الحد الأقصى لحجم الملف: <?= (int)$limits['max_file_size_mb'] ?> ميجابايت · يُنشأ ملف بترويسة واحدة وورقة قوائم مخفية.
  </div>

  <div class="cd043-card">
    <div class="cd043-card-h">①–③ نطاق التصدير</div>
    <div class="cd043-card-b">
      <div class="cd043-fld full">
        <label for="cd043-tpl">القالب (نشط فقط)</label>
        <select id="cd043-tpl">
          <?php
            $types = ['employee','skill','employee_in_course','employee_in_workshop'];
            $labels = ['employee'=>'فردي','skill'=>'مهاري','employee_in_course'=>'موظف داخل دورة','employee_in_workshop'=>'موظف داخل ورشة'];
            $hasAny = false;
            foreach ($types as $t):
              $group = array_values(array_filter($templates, static fn($tpl) => (string)$tpl->target_type === $t));
          ?>
            <optgroup label="<?= htmlspecialchars($labels[$t]) ?>">
              <?php if ($group === []): ?>
                <option value="" disabled>لا توجد قوالب نشطة</option>
              <?php else: foreach ($group as $tpl): $hasAny = true; ?>
                <option value="<?= (int)$tpl->id ?>" data-type="<?= htmlspecialchars((string)$tpl->target_type) ?>">
                  <?= htmlspecialchars((string)$tpl->template_name) ?>
                </option>
              <?php endforeach; endif; ?>
            </optgroup>
          <?php endforeach; ?>
          <?php if (!$hasAny): ?>
            <option value="" disabled>لا توجد قوالب نشطة</option>
          <?php endif; ?>
        </select>
      </div>

      <div class="cd043-fld">
        <label for="cd043-branch">نطاق الفرع (الموظف/الهيكل)</label>
        <select id="cd043-branch">
          <option value="">كل الفروع</option>
          <?php foreach ($branches as $br): ?>
            <option value="<?= (int)$br->id ?>"><?= htmlspecialchars((string)$br->name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="cd043-fld">
        <label for="cd043-dept">نطاق القسم (الموظف/الهيكل)</label>
        <select id="cd043-dept" disabled>
          <option value="">كل الأقسام</option>
        </select>
        <div class="cd043-hint" id="cd043-dept-hint">يُفعّل بعد اختيار فرع. (في الخيار 4 للدورات/الورش تُعطّل فلاتر الموظف ويُعتمد المنفذ)</div>
      </div>

      <div class="cd043-fld" id="cd043-program-scope-wrap" style="display:none">
        <label for="cd043-program-scope">نطاق البرنامج (دورة/ورشة)</label>
        <select id="cd043-program-scope">
          <option value="all">كل البرامج</option>
          <option value="single">برنامج واحد</option>
        </select>
      </div>

      <div class="cd043-fld" id="cd043-program-wrap" style="display:none">
        <label for="cd043-program">اسم البرنامج</label>
        <select id="cd043-program" disabled>
          <option value="">— اختر برنامجاً —</option>
        </select>
        <div class="cd043-hint">يُفلتر حسب نوع القالب والفرع/القسم المنفذ.</div>
      </div>

      <div class="cd043-fld" id="cd043-program-branch-wrap" style="display:none">
        <label for="cd043-program-branch">الفرع المنفذ</label>
        <select id="cd043-program-branch" disabled>
          <option value="">كل الفروع</option>
          <?php foreach ($branches as $br): ?>
            <option value="<?= (int)$br->id ?>"><?= htmlspecialchars((string)$br->name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="cd043-fld" id="cd043-program-dept-wrap" style="display:none">
        <label for="cd043-program-dept">القسم المنفذ</label>
        <select id="cd043-program-dept" disabled>
          <option value="">كل الأقسام</option>
        </select>
      </div>
    </div>
  </div>

  <div class="cd043-card">
    <div class="cd043-card-h">④ طريقة تعبئة الملف (Pre-fill)</div>
    <div class="cd043-card-b">
      <div class="cd043-fld full">
        <div class="cd043-radio-group" id="cd043-strategy-group">
          <label class="cd043-radio" data-strategy="blank">
            <input type="radio" name="prefill_strategy" value="blank" checked>
            <div><div class="t">1) قالب فارغ (الافتراضي)</div><div class="d">ترويسة وصفوف فارغة فقط.</div></div>
          </label>
          <label class="cd043-radio" data-strategy="org">
            <input type="radio" name="prefill_strategy" value="org">
            <div><div class="t">2) تعبئة حسب الهيكل التنظيمي</div><div class="d">موظفو النطاق التنظيمي المحدد، مع أحدث تقييم مرجعي (إن وجد) وفق القواعد.</div></div>
          </label>
          <label class="cd043-radio" data-strategy="previous_evals">
            <input type="radio" name="prefill_strategy" value="previous_evals">
            <div><div class="t">3) تعبئة حسب التقييمات السابقة</div><div class="d">كل التقييمات السابقة المطابقة (كل تقييم في صف مستقل). <b>(Phase 2)</b></div></div>
          </label>
          <label class="cd043-radio" data-strategy="executing" id="cd043-radio-executing">
            <input type="radio" name="prefill_strategy" value="executing">
            <div><div class="t">4) تعبئة حسب الجهة المنفذة</div><div class="d">للدورات/الورش فقط وفق البرنامج والجهة المنفذة. <b>(Phase 2)</b></div></div>
          </label>
        </div>
        <div id="cd043-prefill-warning" class="cd043-warning">
          تنبيه: الملف سيُعبأ بتقييمات/بيانات سابقة (إن وجدت) كمرجع. لتجنب رفض الصفوف كـ (تقييم مكرر / E-017) يجب عليك تغيير <b>تاريخ التقييم</b> للأنواع الفردية/المهارية، أو تغيير <b>بيانات/سياق البرنامج</b> للدورات والورش قبل الاستيراد. النظام لا يقوم بتحديث التقييمات القديمة (لا Upsert).
        </div>
      </div>
    </div>
  </div>

  <div class="cd043-card">
    <div class="cd043-card-h">⑤ عدد الصفوف</div>
    <div class="cd043-card-b">
      <div class="cd043-fld">
        <label for="cd043-rows">عدد صفوف البيانات</label>
        <input type="number" id="cd043-rows" value="100" min="<?= max(50, (int)($limits['min_rows'] ?? 50)) ?>" max="<?= (int)($limits['max_rows'] ?? 2000) ?>" step="1">
      </div>
      <div class="cd043-fld">
        <div class="cd043-hint">الحد الأدنى <?= (int)$limits['min_rows'] ?> والحد الأقصى <?= (int)$limits['max_rows'] ?> صفاً. عند تجاوز المتاح سيتم الاقتصاص وفق ترتيب حتمي (تنبيه تأكيد تفصيلي في Phase 3).</div>
      </div>
    </div>
  </div>

  <div class="cd043-card">
    <div class="cd043-card-h">⑥ تنزيل القالب</div>
    <div class="cd043-card-b">
      <div class="cd043-actions">
        <div class="cd043-hint">سيتم تسجيل عملية التصدير في سجل التدقيق (المستخدم، الوقت، القالب، النطاق، الاستراتيجية، عدد الصفوف).</div>
        <a id="cd043-download" class="cd043-btn cd043-btn-ok disabled" href="#">⬇ تنزيل ملف Excel</a>
      </div>
    </div>
  </div>

  <div style="margin-bottom:14px">
    <a href="<?= APP_URL ?>/evaluations/excel/import" class="cd043-btn cd043-btn-ghost">← شاشة الاستيراد</a>
  </div>
</div>
</div>
<script>
(function () {
  var APP = <?= json_encode(APP_URL, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var PROGRAMS = <?= json_encode(array_map(static function ($p) {
      return [
          'id' => (int)$p['id'],
          'type' => (string)$p['type'],
          'title' => (string)$p['title'],
          'branch_id' => isset($p['executing_branch_id']) ? (int)$p['executing_branch_id'] : 0,
          'dept_id' => isset($p['executing_department_id']) ? (int)$p['executing_department_id'] : 0,
      ];
  }, $programs), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var BRANCHES = <?= json_encode(array_reduce($branches, static function ($carry, $b) {
      $carry[(int)$b->id] = array_map(static fn($d) => ['id' => (int)$d->id, 'name' => (string)$d->name], $b->departments ?? []);
      return $carry;
  }, []), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var PROGRAM_TYPES = <?= json_encode($programTypes) ?>;

  var tpl = document.getElementById('cd043-tpl');
  var branch = document.getElementById('cd043-branch');
  var dept = document.getElementById('cd043-dept');
  var programScopeWrap = document.getElementById('cd043-program-scope-wrap');
  var programScope = document.getElementById('cd043-program-scope');
  var programWrap = document.getElementById('cd043-program-wrap');
  var program = document.getElementById('cd043-program');
  var progBranchWrap = document.getElementById('cd043-program-branch-wrap');
  var progBranch = document.getElementById('cd043-program-branch');
  var progDeptWrap = document.getElementById('cd043-program-dept-wrap');
  var progDept = document.getElementById('cd043-program-dept');
  var rows = document.getElementById('cd043-rows');
  var link = document.getElementById('cd043-download');
  var warning = document.getElementById('cd043-prefill-warning');
  var radioExec = document.getElementById('cd043-radio-executing');

  function getStrategy() {
    var checked = document.querySelector('input[name="prefill_strategy"]:checked');
    return checked ? checked.value : 'blank';
  }
  function targetType() {
    if (!tpl || !tpl.options.length) return '';
    var opt = tpl.options[tpl.selectedIndex];
    return opt ? (opt.getAttribute('data-type') || '') : '';
  }
  function isProgramType() { return PROGRAM_TYPES.indexOf(targetType()) !== -1; }
  function programType() { return targetType() === 'employee_in_workshop' ? 'workshop' : 'course'; }

  function fillDepartments(selectEl, branchId, allLabel) {
    selectEl.innerHTML = '<option value="">' + allLabel + '</option>';
    var items = BRANCHES[branchId] || [];
    items.forEach(function (d) {
      var o = document.createElement('option');
      o.value = String(d.id);
      o.textContent = d.name;
      selectEl.appendChild(o);
    });
  }

  function syncEmployeeScopeFilters() {
    var s = getStrategy();
    var opt4 = isProgramType() && s === 'executing';
    // Q1/Q5: in option 4 disable employee branch/dept filters
    branch.disabled = opt4;
    var deptEnabled = !opt4 && !!branch.value;
    dept.disabled = !deptEnabled;
    if (opt4) {
      // keep UI consistent (values ignored by backend for opt4)
      // do not clear to avoid user confusion on switch back, but disable
    } else {
      fillDepartments(dept, branch.value, 'كل الأقسام');
    }
  }

  function syncStrategyUI() {
    var isProgram = isProgramType();
    if (radioExec) {
      var input = radioExec.querySelector('input');
      if (!isProgram) {
        radioExec.classList.add('disabled');
        if (input) { input.disabled = true; if (input.checked) { document.querySelector('input[name="prefill_strategy"][value="blank"]').checked = true; } }
      } else {
        radioExec.classList.remove('disabled');
        if (input) { input.disabled = false; }
      }
    }
    var s = getStrategy();
    warning.style.display = (s === 'org' || s === 'previous_evals' || s === 'executing') ? 'block' : 'none';
    syncEmployeeScopeFilters();
  }

  function fillPrograms() {
    if (!program) return;
    var type = programType();
    var bId = progBranch.value ? parseInt(progBranch.value, 10) : 0;
    var dId = progDept.value ? parseInt(progDept.value, 10) : 0;
    var current = program.value;
    program.innerHTML = '<option value="">— اختر برنامجاً —</option>';
    PROGRAMS.filter(function (p) {
      if (p.type !== type) return false;
      if (bId && p.branch_id !== bId) return false;
      if (dId && p.dept_id !== dId) return false;
      return true;
    }).forEach(function (p) {
      var o = document.createElement('option');
      o.value = String(p.id);
      o.textContent = p.title;
      program.appendChild(o);
    });
    if (current) { program.value = current; }
    program.disabled = programScope.value !== 'single';
  }

  function syncProgramBlock() {
    var isProgram = isProgramType();
    programScopeWrap.style.display = isProgram ? '' : 'none';
    var single = isProgram && programScope.value === 'single';
    programWrap.style.display = single ? '' : 'none';
    progBranchWrap.style.display = single ? '' : 'none';
    progDeptWrap.style.display = single ? '' : 'none';
    progBranch.disabled = !single;
    progDept.disabled = !single || !progBranch.value;
    if (single) {
      fillPrograms();
      if (!progBranch.value && branch.value) { progBranch.value = branch.value; progDept.disabled = !progBranch.value; fillPrograms(); }
    } else {
      if (program) program.disabled = true;
    }
  }

  function updateLink() {
    if (!link) return;
    var templateId = tpl ? tpl.value : '';
    if (!templateId) { link.classList.add('disabled'); link.setAttribute('href', '#'); return; }
    var params = new URLSearchParams();
    params.set('template_id', templateId);
    var strategy = getStrategy();
    params.set('prefill_strategy', strategy);
    if (branch.value) params.set('branch_id', branch.value);
    if (dept.value) params.set('department_id', dept.value);
    if (isProgramType()) {
      params.set('program_scope', programScope.value);
      if (programScope.value === 'single') {
        if (!program.value) { link.classList.add('disabled'); link.setAttribute('href', '#'); return; }
        params.set('program_id', program.value);
      }
      if (progBranch.value) params.set('executing_branch_id', progBranch.value);
      if (progDept.value) params.set('executing_department_id', progDept.value);
    }
    var minRows = parseInt(rows.getAttribute('min'), 10) || 50;
    var maxRows = parseInt(rows.getAttribute('max'), 10) || 2000;
    var value = parseInt(rows.value, 10);
    if (!value || value < minRows) value = minRows;
    if (value > maxRows) value = maxRows;
    rows.value = value;
    params.set('max_rows', String(value));
    link.setAttribute('href', APP + '/evaluations/excel/export/download?' + params.toString());
    link.classList.remove('disabled');
  }

  function onStrategyChange() { syncStrategyUI(); updateLink(); }

  if (tpl) tpl.addEventListener('change', function () { syncProgramBlock(); syncStrategyUI(); updateLink(); });
  if (branch) branch.addEventListener('change', function () {
    fillDepartments(dept, branch.value, 'كل الأقسام');
    dept.disabled = !branch.value || (getStrategy()==='executing' && isProgramType());
    if (progBranch && progBranch.value && progBranch.value !== branch.value) {
      progBranch.value = branch.value;
      fillDepartments(progDept, progBranch.value, 'كل الأقسام');
      progDept.value = '';
      fillPrograms();
    }
    updateLink();
  });
  if (dept) dept.addEventListener('change', updateLink);
  if (programScope) programScope.addEventListener('change', function () { syncProgramBlock(); updateLink(); });
  if (program) program.addEventListener('change', updateLink);
  if (progBranch) progBranch.addEventListener('change', function () {
    fillDepartments(progDept, progBranch.value, 'كل الأقسام');
    progDept.value = '';
    progDept.disabled = !progBranch.value;
    fillPrograms();
    updateLink();
  });
  if (progDept) progDept.addEventListener('change', function () { fillPrograms(); updateLink(); });
  if (rows) rows.addEventListener('input', updateLink);
  document.querySelectorAll('input[name="prefill_strategy"]').forEach(function(r){ r.addEventListener('change', onStrategyChange); });

  fillDepartments(dept, '', 'كل الأقسام');
  fillDepartments(progDept, '', 'كل الأقسام');
  syncProgramBlock();
  syncStrategyUI();
  updateLink();
})();
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
