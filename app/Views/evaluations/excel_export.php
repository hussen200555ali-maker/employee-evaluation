<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
$templates = $templates ?? [];
$branches = $branches ?? [];
$programs = $programs ?? [];
$limits = $limits ?? ['min_rows' => 50, 'max_rows' => 2000, 'max_file_size_mb' => 50];
$programTypes = ['employee_in_course', 'employee_in_workshop'];
?>
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/excel-flow.css?v=1.0">

<div class="xf-page">
  <div class="xf-container xf-container--narrow">
    <header class="xf-page-header">
      <div class="xf-page-header__row">
        <div>
          <h1 class="xf-page-header__title"><i class="fas fa-file-export" aria-hidden="true"></i> تصدير قالب تقييم Excel</h1>
          <p class="xf-page-header__sub">تهيئة نطاق التصدير واستراتيجية التعبئة الذكية (Smart Prefill) ثم تنزيل قالب .xlsx جاهز للاستخدام.</p>
        </div>
        <div class="xf-page-header__meta">
          <span class="xf-chip"><i class="fas fa-shield-alt"></i> تسجيل تدقيق تلقائي</span>
          <a class="xf-chip xf-chip--light" href="<?= APP_URL ?>/evaluations/excel/import"><i class="fas fa-file-import"></i> شاشة الاستيراد</a>
        </div>
      </div>
    </header>

    <ol class="xf-steps" aria-label="خطوات التصدير">
      <li class="xf-step is-active"><span class="xf-step__num">1</span> القالب والنطاق</li>
      <li class="xf-step"><span class="xf-step__num">2</span> البرنامج/المنفذ</li>
      <li class="xf-step"><span class="xf-step__num">3</span> طريقة التعبئة</li>
      <li class="xf-step"><span class="xf-step__num">4</span> الصفوف والتنزيل</li>
    </ol>

    <div class="xf-callout xf-callout--info" style="margin-bottom:14px">
      <i class="fas fa-info-circle"></i>
      <div>الصفوف المسموحة: <strong><?= (int)$limits['min_rows'] ?>–<?= (int)$limits['max_rows'] ?></strong> صفاً · الحد الأقصى لحجم الملف: <strong><?= (int)$limits['max_file_size_mb'] ?></strong> ميجابايت · يتم إنشاء ملف .xlsx بترويسة وورقة قوائم مساندة.</div>
    </div>

    <section class="xf-card">
      <div class="xf-card__header">
        <h2 class="xf-card__title"><i class="fas fa-sliders-h"></i> 1) نطاق التصدير</h2>
      </div>
      <div class="xf-card__body">
        <div class="xf-grid">
          <div class="xf-field xf-col-12">
            <label for="cd043-tpl">القالب (النشط)</label>
            <select id="cd043-tpl" class="xf-select">
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

          <div class="xf-field xf-col-6">
            <label for="cd043-branch">فرع الموظف (النطاق التنظيمي)</label>
            <select id="cd043-branch" class="xf-select">
              <option value="">كل الفروع</option>
              <?php foreach ($branches as $br): ?>
                <option value="<?= (int)$br->id ?>"><?= htmlspecialchars((string)$br->name) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="xf-help">يُستخدم لتحديد نطاق الموظفين (حسب الاستراتيجية).</div>
          </div>

          <div class="xf-field xf-col-6">
            <label for="cd043-dept">قسم الموظف (النطاق التنظيمي)</label>
            <select id="cd043-dept" class="xf-select" disabled>
              <option value="">كل الأقسام</option>
            </select>
            <div class="xf-help" id="cd043-dept-hint">يُفعّل بعد اختيار الفرع. في خيار "الجهة المنفذة" للدورات/الورش تُعطّل فلاتر الموظف ويُعتمد نطاق المنفذ.</div>
          </div>
        </div>

        <div class="xf-divider"></div>
        <p class="xf-section-desc"><i class="fas fa-chalkboard-teacher" style="color:var(--xf-primary)"></i> <strong>سياق البرنامج/الجهة المنفذة</strong> (يظهر للقوالب البرنامجية: دورة/ورشة)</p>
        <div class="xf-grid">
          <div class="xf-field xf-col-4" id="cd043-program-scope-wrap" style="display:none">
            <label for="cd043-program-scope">نطاق البرنامج</label>
            <select id="cd043-program-scope" class="xf-select">
              <option value="all">كل البرامج</option>
              <option value="single">برنامج واحد محدد</option>
            </select>
          </div>
          <div class="xf-field xf-col-8" id="cd043-program-wrap" style="display:none">
            <label for="cd043-program">البرنامج</label>
            <select id="cd043-program" class="xf-select" disabled>
              <option value="">— اختر برنامجاً —</option>
            </select>
            <div class="xf-help">تُفلتر القائمة حسب نوع القالب والفرع/القسم المنفذ.</div>
          </div>
          <div class="xf-field xf-col-6" id="cd043-program-branch-wrap" style="display:none">
            <label for="cd043-program-branch">الفرع المنفذ</label>
            <select id="cd043-program-branch" class="xf-select" disabled>
              <option value="">كل الفروع</option>
              <?php foreach ($branches as $br): ?>
                <option value="<?= (int)$br->id ?>"><?= htmlspecialchars((string)$br->name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="xf-field xf-col-6" id="cd043-program-dept-wrap" style="display:none">
            <label for="cd043-program-dept">القسم المنفذ</label>
            <select id="cd043-program-dept" class="xf-select" disabled>
              <option value="">كل الأقسام</option>
            </select>
          </div>
        </div>
      </div>
    </section>

    <section class="xf-card">
      <div class="xf-card__header">
        <h2 class="xf-card__title"><i class="fas fa-magic"></i> 2) استراتيجية التعبئة (Smart Prefill)</h2>
      </div>
      <div class="xf-card__body">
        <div class="xf-radio-grid" id="cd043-strategy-group" role="radiogroup" aria-label="طريقة تعبئة الملف">
          <label class="xf-radio-card" data-strategy="blank">
            <input type="radio" name="prefill_strategy" value="blank" checked>
            <div>
              <p class="xf-radio-card__title">1) قالب فارغ</p>
              <p class="xf-radio-card__desc">ترويسة وبنية جاهزة مع صفوف بيانات فارغة (الافتراضي).</p>
            </div>
          </label>
          <label class="xf-radio-card" data-strategy="org">
            <input type="radio" name="prefill_strategy" value="org">
            <div>
              <p class="xf-radio-card__title">2) الهيكل التنظيمي</p>
              <p class="xf-radio-card__desc">موظفو النطاق المحدد مع أحدث تقييم مرجعي لكل موظف (إن وجد) وفق القواعد.</p>
            </div>
          </label>
          <label class="xf-radio-card" data-strategy="previous_evals">
            <input type="radio" name="prefill_strategy" value="previous_evals">
            <div>
              <p class="xf-radio-card__title">3) التقييمات السابقة</p>
              <p class="xf-radio-card__desc">كل التقييمات المطابقة — صف مستقل لكل تقييم بسياقه التاريخي.</p>
            </div>
          </label>
          <label class="xf-radio-card" data-strategy="executing" id="cd043-radio-executing">
            <input type="radio" name="prefill_strategy" value="executing">
            <div>
              <p class="xf-radio-card__title">4) الجهة المنفذة</p>
              <p class="xf-radio-card__desc">للدورات/الورش فقط: وفق البرنامج والفرع/القسم المنفذ (مع تعطيل فلاتر الموظف).</p>
            </div>
          </label>
        </div>

        <div id="cd043-prefill-warning" class="xf-callout xf-callout--warning xf-mt-2" style="display:none">
          <i class="fas fa-exclamation-triangle"></i>
          <div><strong>تنبيه مرجعي:</strong> قد يحتوي الملف على بيانات/تقييمات سابقة للرجوع إليها. لتجنب رفض الصفوف كتقييم مكرر (<b>E-017</b>) غيّر <b>تاريخ التقييم</b> (فردي/مهاري) أو <b>سياق/بيانات البرنامج</b> (دورة/ورشة) قبل الاستيراد. النظام لا يُحدّث التقييمات القديمة (لا Upsert).</div>
        </div>
      </div>
    </section>

    <section class="xf-card">
      <div class="xf-card__header">
        <h2 class="xf-card__title"><i class="fas fa-list-ol"></i> 3) عدد الصفوف والتنزيل</h2>
      </div>
      <div class="xf-card__body">
        <div class="xf-grid" style="align-items:end">
          <div class="xf-field xf-col-4">
            <label for="cd043-rows">عدد صفوف البيانات</label>
            <input type="number" id="cd043-rows" class="xf-input" value="100" min="<?= max(50, (int)($limits['min_rows'] ?? 50)) ?>" max="<?= (int)($limits['max_rows'] ?? 2000) ?>" step="1">
            <div class="xf-help">الحد الأدنى <?= (int)$limits['min_rows'] ?> والحد الأقصى <?= (int)$limits['max_rows'] ?> صف.</div>
          </div>
          <div class="xf-field xf-col-8">
            <div class="xf-callout xf-callout--info" style="margin:0">
              <i class="fas fa-clipboard-check"></i>
              <div>عند تجاوز العدد المتاح للحد المطلوب سيظهر تأكيد قبل التنزيل يوضح (المُصدَّر من أصل المتاح)، مع تطبيق اقتصاص حتمي. تُسجَّل عملية التصدير في سجل التدقيق (المستخدم، الوقت، القالب، النطاق، الاستراتيجية، العدّ).</div>
            </div>
          </div>
        </div>

        <div class="xf-divider"></div>
        <div class="xf-actions">
          <div class="xf-actions__group">
            <a href="<?= APP_URL ?>/evaluations/excel/import" class="xf-btn xf-btn--ghost"><i class="fas fa-arrow-right"></i> العودة للاستيراد</a>
          </div>
          <div class="xf-actions__group">
            <a id="cd043-download" class="xf-btn xf-btn--primary is-disabled" href="#" aria-disabled="true">
              <i class="fas fa-download"></i> <span class="xf-btn-label">تنزيل ملف Excel</span>
            </a>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<!-- Modal: cap confirmation (A4) -->
<div id="cd043-export-modal" class="xf-modal-backdrop" style="display:none" aria-hidden="true">
  <div class="xf-modal" role="dialog" aria-modal="true" aria-labelledby="cd043-modal-title">
    <div class="xf-modal__header">
      <i class="fas fa-exclamation-circle" style="color:var(--xf-warning);font-size:1.1rem"></i>
      <h3 id="cd043-modal-title" class="xf-modal__title">تأكيد التصدير</h3>
    </div>
    <div class="xf-modal__body">
      <p id="cd043-modal-msg" class="xf-mb-0"></p>
    </div>
    <div class="xf-modal__footer">
      <button type="button" id="cd043-modal-cancel" class="xf-btn xf-btn--secondary">إلغاء</button>
      <button type="button" id="cd043-modal-confirm" class="xf-btn xf-btn--primary"><i class="fas fa-check"></i> متابعة التنزيل</button>
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
    if (!selectEl) return;
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
    if (branch) branch.disabled = opt4;
    var deptEnabled = !opt4 && !!(branch && branch.value);
    if (dept) dept.disabled = !deptEnabled;
    if (!opt4 && dept) {
      fillDepartments(dept, branch ? branch.value : '', 'كل الأقسام');
    }
  }

  function syncStrategyUI() {
    var isProgram = isProgramType();
    if (radioExec) {
      var input = radioExec.querySelector('input');
      if (!isProgram) {
        radioExec.classList.add('is-disabled');
        if (input) { input.disabled = true; if (input.checked) { var b = document.querySelector('input[name="prefill_strategy"][value="blank"]'); if (b) b.checked = true; } }
      } else {
        radioExec.classList.remove('is-disabled');
        if (input) { input.disabled = false; }
      }
    }
    var s = getStrategy();
    if (warning) warning.style.display = (s === 'org' || s === 'previous_evals' || s === 'executing') ? 'flex' : 'none';
    syncEmployeeScopeFilters();
    syncProgramBlock();
  }

  function fillPrograms() {
    if (!program) return;
    var type = programType();
    var bId = progBranch && progBranch.value ? parseInt(progBranch.value, 10) : 0;
    var dId = progDept && progDept.value ? parseInt(progDept.value, 10) : 0;
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
    program.disabled = !(programScope && programScope.value === 'single');
  }

  function syncProgramBlock() {
    var isProgram = isProgramType();
    var strategy = getStrategy();
    var optExec = isProgram && strategy === 'executing';
    if (programScopeWrap) programScopeWrap.style.display = isProgram ? '' : 'none';
    var single = isProgram && programScope && programScope.value === 'single';
    var showExecFilters = isProgram && (single || optExec);
    if (programWrap) programWrap.style.display = single ? '' : 'none';
    if (progBranchWrap) progBranchWrap.style.display = showExecFilters ? '' : 'none';
    if (progDeptWrap) progDeptWrap.style.display = showExecFilters ? '' : 'none';
    if (progBranch) progBranch.disabled = !showExecFilters;
    if (progDept) progDept.disabled = !showExecFilters || !(progBranch && progBranch.value);
    if (showExecFilters && progDept) {
      fillDepartments(progDept, progBranch ? progBranch.value : '', 'كل الأقسام');
      progDept.disabled = !(progBranch && progBranch.value);
    }
    if (single) { fillPrograms(); } else if (program) { program.disabled = true; }
  }

  function buildParams() {
    var templateId = tpl ? tpl.value : '';
    if (!templateId) return null;
    var params = new URLSearchParams();
    params.set('template_id', templateId);
    var strategy = getStrategy();
    params.set('prefill_strategy', strategy);
    if (branch && branch.value) params.set('branch_id', branch.value);
    if (dept && dept.value) params.set('department_id', dept.value);
    if (isProgramType()) {
      params.set('program_scope', programScope ? programScope.value : 'all');
      if (programScope && programScope.value === 'single') {
        if (!program || !program.value) return null;
        params.set('program_id', program.value);
      }
      if (progBranch && progBranch.value) params.set('executing_branch_id', progBranch.value);
      if (progDept && progDept.value) params.set('executing_department_id', progDept.value);
    }
    var minRows = parseInt(rows.getAttribute('min'), 10) || 50;
    var maxRows = parseInt(rows.getAttribute('max'), 10) || 2000;
    var value = parseInt(rows.value, 10);
    if (!value || value < minRows) value = minRows;
    if (value > maxRows) value = maxRows;
    rows.value = value;
    params.set('max_rows', String(value));
    return params;
  }

  function updateLink() {
    if (!link) return;
    var params = buildParams();
    if (!params) {
      link.classList.add('is-disabled');
      link.setAttribute('href', '#');
      link.setAttribute('aria-disabled', 'true');
      link.dataset.ready = '0';
      return;
    }
    link.setAttribute('href', APP + '/evaluations/excel/export/download?' + params.toString());
    link.dataset.ready = '1';
    link.classList.remove('is-disabled');
    link.setAttribute('aria-disabled', 'false');
  }

  function setLinkLoading(loading) {
    if (!link) return;
    var label = link.querySelector('.xf-btn-label');
    if (loading) {
      link.classList.add('is-loading', 'is-disabled');
      link.setAttribute('aria-disabled', 'true');
      if (label) label.textContent = 'جارٍ التحقق من العدد...';
      if (!link.querySelector('.xf-spinner')) {
        var sp = document.createElement('span');
        sp.className = 'xf-spinner';
        sp.setAttribute('aria-hidden', 'true');
        link.insertBefore(sp, label || link.firstChild);
      }
    } else {
      link.classList.remove('is-loading');
      var sp = link.querySelector('.xf-spinner');
      if (sp) sp.remove();
      if (label) label.textContent = 'تنزيل ملف Excel';
      updateLink();
    }
  }

  function showExportModal(message, onConfirm) {
    var modal = document.getElementById('cd043-export-modal');
    var msg = document.getElementById('cd043-modal-msg');
    var ok = document.getElementById('cd043-modal-confirm');
    var cancel = document.getElementById('cd043-modal-cancel');
    if (!modal || !msg || !ok || !cancel) { if (confirm(message)) onConfirm(); return; }
    msg.textContent = message;
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    function close() {
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
      ok.onclick = null; cancel.onclick = null; modal.onclick = null;
    }
    cancel.onclick = close;
    modal.onclick = function (e) { if (e.target === modal) close(); };
    ok.onclick = function () { close(); onConfirm(); };
  }

  if (link) {
    link.addEventListener('click', function (e) {
      if (link.classList.contains('is-disabled') || link.dataset.ready !== '1') { e.preventDefault(); return; }
      var params = buildParams();
      if (!params) { e.preventDefault(); return; }
      e.preventDefault();
      var downloadUrl = APP + '/evaluations/excel/export/download?' + params.toString();
      var countUrl = APP + '/evaluations/excel/export/count?' + params.toString();
      setLinkLoading(true);
      fetch(countUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json().catch(function(){ return {ok:false,message:'تعذّر قراءة الاستجابة من الخادم.'}; }); })
        .then(function (data) {
          setLinkLoading(false);
          if (!data || !data.ok) { alert((data && data.message) ? data.message : 'تعذّر حساب عدد السجلات المتاحة حالياً.'); return; }
          var total = parseInt(data.total_available, 10) || 0;
          var exported = parseInt(data.exported_rows, 10) || 0;
          var capped = !!data.capped;
          if (capped && total > exported) {
            showExportModal('سيتم تصدير ' + exported + ' صفاً من أصل ' + total + ' متاح (تجاوز الحد الأقصى المحدد). هل ترغب في المتابعة بالتنزيل؟', function () {
              window.location.href = downloadUrl;
            });
            return;
          }
          window.location.href = downloadUrl;
        })
        .catch(function () {
          setLinkLoading(false);
          alert('تعذّر التحقق من عدد السجلات حالياً. يرجى المحاولة مرة أخرى.');
        });
    });
  }

  function onStrategyChange() { syncStrategyUI(); updateLink(); }

  if (tpl) tpl.addEventListener('change', function () { syncProgramBlock(); syncStrategyUI(); updateLink(); });
  if (branch) branch.addEventListener('change', function () {
    fillDepartments(dept, branch.value, 'كل الأقسام');
    if (dept) dept.disabled = !branch.value || (getStrategy()==='executing' && isProgramType());
    updateLink();
  });
  if (dept) dept.addEventListener('change', updateLink);
  if (programScope) programScope.addEventListener('change', function () { syncProgramBlock(); updateLink(); });
  if (program) program.addEventListener('change', updateLink);
  if (progBranch) progBranch.addEventListener('change', function () {
    fillDepartments(progDept, progBranch.value, 'كل الأقسام');
    if (progDept) { progDept.value = ''; progDept.disabled = !progBranch.value; }
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
