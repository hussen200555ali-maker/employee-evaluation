<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
$evaluatorName = $evaluatorName ?? '';
$evaluatorTitle = $evaluatorTitle ?? '';
$gradingScales = $gradingScales ?? [];
$templates = $templates ?? [];
$branches = $branches ?? [];
$preview = $preview ?? null;
$templateStructures = $templateStructures ?? [];
$csrfToken = $csrfToken ?? '';
$typeLabels = [
    'employee' => 'فردي',
    'skill' => 'مهاري',
    'employee_in_course' => 'دورة',
    'employee_in_workshop' => 'ورشة',
    'course' => 'دورة (وصفي)',
    'workshop' => 'ورشة (وصفي)',
    'unit' => 'وحدة',
];
$typeIcons = [
    'employee' => 'fa-user',
    'skill' => 'fa-tools',
    'employee_in_course' => 'fa-chalkboard-teacher',
    'employee_in_workshop' => 'fa-users-cog',
    'course' => 'fa-book',
    'workshop' => 'fa-cogs',
    'unit' => 'fa-sitemap',
];
$typeOrder = ['employee','skill','employee_in_course','employee_in_workshop','course','workshop','unit'];
$templatesByType = [];
foreach ($templates as $tpl) {
    $templatesByType[$tpl->target_type][] = $tpl;
}
$availableTypes = array_values(array_intersect($typeOrder, array_keys($templatesByType)));
?>
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/excel-flow.css?v=1.0">

<div class="xf-page">
  <div class="xf-container">
    <header class="xf-page-header">
      <div class="xf-page-header__row">
        <div>
          <h1 class="xf-page-header__title"><i class="fas fa-file-import" aria-hidden="true"></i> استيراد تقييمات Excel</h1>
          <p class="xf-page-header__sub">تنزيل/مطابقة القالب، رفع الملف، معاينة النتائج والتحقق، ثم اعتماد الاستيراد ضمن تدفق مؤسسي آمن.</p>
        </div>
        <div class="xf-page-header__meta">
          <span class="xf-chip"><i class="fas fa-shield-alt"></i> تحقق قبل الالتزام</span>
          <a class="xf-chip xf-chip--light" href="<?= APP_URL ?>/evaluations/excel/export"><i class="fas fa-file-export"></i> شاشة التصدير</a>
        </div>
      </div>
    </header>

    <ol class="xf-steps" aria-label="خطوات الاستيراد">
      <li class="xf-step <?= $preview === null ? 'is-active' : '' ?>"><span class="xf-step__num">1</span> القالب والرفع</li>
      <li class="xf-step <?= $preview !== null ? 'is-active' : '' ?>"><span class="xf-step__num">2</span> المعاينة والتحقق</li>
      <li class="xf-step"><span class="xf-step__num">3</span> اعتماد الاستيراد</li>
    </ol>

    <?php if ($preview === null): ?>
      <div class="xf-callout xf-callout--info" style="margin-bottom:14px">
        <i class="fas fa-info-circle"></i>
        <div><strong>ملاحظة:</strong> يمكن إضافة موظفين جدد عبر الملف عند استيفاء الحقول المطلوبة (الاسم أو الرقم الوظيفي، والفرع/القسم حسب القالب). استخدم القالب المطابق لتجنب أخطاء البنية.</div>
      </div>

      <section class="xf-card">
        <div class="xf-card__header">
          <h2 class="xf-card__title"><i class="fas fa-table"></i> معاينة هيكل القوالب</h2>
          <div class="xf-muted" style="font-size:.8rem">استعرض الأعمدة والمتطلبات حسب نوع القالب قبل التعبئة</div>
        </div>
        <div class="xf-tabs" id="cd042-tabs"></div>
        <div class="xf-legend">
          <span class="xf-legend__item"><span class="xf-swatch" style="background:#334e68"></span> حقول تعريف/ثابتة</span>
          <span class="xf-legend__item"><span class="xf-swatch" style="background:#0f766e"></span> معيار/مجموعة</span>
          <span class="xf-legend__item"><span class="xf-swatch" style="background:#e6f4f1"></span> أقسام التقييم</span>
          <span class="xf-legend__item"><span class="xf-swatch" style="background:#eff8ff"></span> قائمة/اختيار</span>
          <span class="xf-legend__item"><span class="xf-swatch" style="background:#f8fbff"></span> رقمي/تاريخ</span>
          <span class="xf-legend__item"><span class="xf-swatch" style="background:#fffaf0"></span> صف مثال</span>
        </div>
        <div class="xf-card__body xf-card__body--flush">
          <div id="cd042-preview" class="xf-table-wrap xf-table-wrap--preview" style="padding:10px 12px"></div>
        </div>
      </section>

      <div class="xf-grid">
        <section class="xf-card xf-col-6">
          <div class="xf-card__header">
            <h2 class="xf-card__title"><i class="fas fa-file-download"></i> الحصول على القالب</h2>
          </div>
          <div class="xf-card__body">
            <p class="xf-section-desc">لضمان توافق البنية والاعتمادات (القوائم، النطاق، عدد الصفوف، واستراتيجيات التعبئة)، استخدم شاشة التصدير المعتمدة.</p>
            <a href="<?= APP_URL ?>/evaluations/excel/export" class="xf-btn xf-btn--secondary"><i class="fas fa-external-link-alt"></i> فتح شاشة التصدير</a>
          </div>
        </section>

        <section class="xf-card xf-col-6">
          <div class="xf-card__header">
            <h2 class="xf-card__title"><i class="fas fa-cloud-upload-alt"></i> رفع الملف للمعاينة</h2>
          </div>
          <div class="xf-card__body">
            <form method="post" action="<?= APP_URL ?>/evaluations/excel/preview" enctype="multipart/form-data" id="cd042-upload-form">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
              <label class="xf-upload" id="cd042-zone" for="cd042-file">
                <div class="xf-upload__icon"><i class="fas fa-file-excel"></i></div>
                <p class="xf-upload__title">اسحب ملف XLSX هنا أو انقر للاختيار</p>
                <p class="xf-upload__sub">الامتداد .xlsx فقط · الحد الأقصى 20 ميجابايت · يُفضّل استخدام القالب المُصدَّر من النظام</p>
                <input type="file" id="cd042-file" name="workbook" accept=".xlsx" required hidden>
              </label>
              <div class="xf-fileinfo" id="cd042-fileinfo"></div>
              <div class="xf-actions" style="margin-top:12px;justify-content:flex-end">
                <button type="submit" class="xf-btn xf-btn--primary" id="cd042-preview-btn"><i class="fas fa-search"></i> <span class="xf-btn-label">معاينة والتحقق</span></button>
              </div>
            </form>
          </div>
        </section>
      </div>

    <?php else: ?>
      <?php
        $total = (int)($preview['total'] ?? 0);
        $valid = (int)($preview['valid'] ?? 0);
        $invalid = (int)($preview['invalid'] ?? 0);
        $newEmp = (int)($preview['new_employees'] ?? 0);
      ?>
      <div class="xf-stats">
        <div class="xf-stat xf-stat--neutral">
          <div class="xf-stat__icon"><i class="fas fa-list"></i></div>
          <div><div class="xf-stat__value"><?= $total ?></div><div class="xf-stat__label">إجمالي الصفوف</div></div>
        </div>
        <div class="xf-stat xf-stat--success">
          <div class="xf-stat__icon"><i class="fas fa-check-circle"></i></div>
          <div><div class="xf-stat__value"><?= $valid ?></div><div class="xf-stat__label">صفوف صالحة</div></div>
        </div>
        <div class="xf-stat xf-stat--warning">
          <div class="xf-stat__icon"><i class="fas fa-user-plus"></i></div>
          <div><div class="xf-stat__value"><?= $newEmp ?></div><div class="xf-stat__label">موظفون جدد</div></div>
        </div>
        <div class="xf-stat xf-stat--danger">
          <div class="xf-stat__icon"><i class="fas fa-exclamation-triangle"></i></div>
          <div><div class="xf-stat__value"><?= $invalid ?></div><div class="xf-stat__label">صفوف بأخطاء</div></div>
        </div>
      </div>

      <?php if ($invalid > 0): ?>
        <div class="xf-callout xf-callout--danger" style="margin-bottom:14px">
          <i class="fas fa-times-circle"></i>
          <div><strong>توجد أخطاء تحتاج معالجة.</strong> يرجى تصحيح الصفوف الموضحة أدناه في ملف Excel ثم إعادة الرفع. لن يتم اعتماد الاستيراد إلا بعد خلوه من الأخطاء.</div>
        </div>
      <?php else: ?>
        <div class="xf-callout xf-callout--success" style="margin-bottom:14px">
          <i class="fas fa-check-circle"></i>
          <div><strong>الملف جاهز للاعتماد.</strong> تمت معاينة جميع الصفوف بنجاح ويمكنك تأكيد الاستيراد.</div>
        </div>
      <?php endif; ?>

      <section class="xf-card">
        <div class="xf-card__header">
          <h2 class="xf-card__title"><i class="fas fa-clipboard-list"></i> نتائج المعاينة</h2>
          <div class="xf-muted" style="font-size:.8rem">يتم تمييز الصفوف والأعمدة المهمة لتسهيل المراجعة</div>
        </div>
        <div class="xf-card__body xf-card__body--flush">
          <div class="xf-table-wrap" style="max-height:65vh">
            <table class="xf-table">
              <thead>
                <tr>
                  <th class="is-status is-id">#</th>
                  <th class="is-status">الحالة</th>
                  <th class="is-id col-important">الرقم الوظيفي</th>
                  <th class="col-important">الاسم</th>
                  <th class="col-important">الفرع</th>
                  <th class="col-important">القسم</th>
                  <th class="col-important">تاريخ التقييم</th>
                  <th>مقتطف/درجات</th>
                  <th style="min-width:260px">النتيجة والملاحظات</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach (($preview['rows'] ?? []) as $row): ?>
                <?php
                  $err = !empty($row['errors']);
                  $isNew = !empty($row['is_new_employee']);
                  $rowCls = $err ? 'xf-row--err' : ($isNew ? 'xf-row--new' : 'xf-row--ok');
                ?>
                <tr class="<?= $rowCls ?>">
                  <td class="is-status is-id xf-num"><?= (int)($row['row'] ?? 0) ?></td>
                  <td class="is-status">
                    <?php if ($err): ?>
                      <span class="xf-badge xf-badge--err"><i class="fas fa-times-circle"></i> خطأ</span>
                    <?php elseif ($isNew): ?>
                      <span class="xf-badge xf-badge--new"><i class="fas fa-user-plus"></i> جديد</span>
                    <?php else: ?>
                      <span class="xf-badge xf-badge--ok"><i class="fas fa-check-circle"></i> صالح</span>
                    <?php endif; ?>
                  </td>
                  <td class="is-id col-important"><?= htmlspecialchars($row['payload']['emp_code'] ?? '—') ?></td>
                  <td class="col-important"><?= htmlspecialchars($row['payload']['full_name'] ?? '—') ?></td>
                  <td class="col-important"><?= htmlspecialchars($row['payload']['branch_name'] ?? '—') ?></td>
                  <td class="col-important"><?= htmlspecialchars($row['payload']['department_name'] ?? '—') ?></td>
                  <td class="col-important xf-num"><?= htmlspecialchars($row['payload']['evaluation_date'] ?? '—') ?></td>
                  <td>
                    <?php if (!empty($row['section_values'])): ?>
                      <?php $vals = array_values($row['section_values']); $shown = array_slice($vals, 0, 3); ?>
                      <span class="xf-muted" title="<?= htmlspecialchars(implode(' | ', array_map('strval', $vals))) ?>">
                        <?= htmlspecialchars(implode(' · ', array_map(static fn($v) => mb_strimwidth((string)$v, 0, 24, '…'), $shown))) ?>
                        <?= count($vals) > 3 ? ' …' : '' ?>
                      </span>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                  <td>
                    <?php if ($err): ?>
                      <ul class="xf-errors"><?php foreach ($row['errors'] as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                    <?php else: ?>
                      <span class="xf-badge xf-badge--ok"><i class="fas fa-check"></i> جاهز للاستيراد</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <?php if ($invalid === 0 && $valid > 0): ?>
        <form method="post" action="<?= APP_URL ?>/evaluations/excel/commit/<?= (int)($preview['batch_id'] ?? 0) ?>" id="cd042-commit-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <div class="xf-sticky-bar">
            <div class="xf-sticky-bar__note"><i class="fas fa-lock"></i> القالب المعتمد: تم التحقق من البنية — سيتم استيراد <strong><?= $valid ?></strong> صفاً صالحاً فقط.</div>
            <div class="xf-actions__group">
              <a href="<?= APP_URL ?>/evaluations/excel/import" class="xf-btn xf-btn--secondary"><i class="fas fa-redo"></i> إعادة الرفع</a>
              <button type="submit" class="xf-btn xf-btn--success" id="cd042-commit-btn"><i class="fas fa-check-double"></i> <span class="xf-btn-label">تأكيد واعتماد الاستيراد</span></button>
            </div>
          </div>
        </form>
      <?php else: ?>
        <div class="xf-sticky-bar">
          <div class="xf-sticky-bar__note"><i class="fas fa-info-circle"></i> لا يمكن الاعتماد لوجود أخطاء أو عدم وجود صفوف صالحة. قم بتصحيح الملف وأعد المعاينة.</div>
          <div class="xf-actions__group">
            <a href="<?= APP_URL ?>/evaluations/excel/import" class="xf-btn xf-btn--primary"><i class="fas fa-arrow-right"></i> العودة وإعادة الرفع</a>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</div>

<script>
var APP_URL_CONST = "<?= APP_URL ?>";
var CD042_STRUCTURES = <?= json_encode($templateStructures, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
var CD042_TEMPLATES = <?= json_encode(array_map(function($t){return ['id'=>(int)$t->id,'name'=>$t->template_name,'type'=>$t->target_type];}, $templates), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
var CD042_TYPE_LABELS = <?= json_encode($typeLabels, JSON_UNESCAPED_UNICODE) ?>;
var CD042_TYPE_ICONS = <?= json_encode($typeIcons, JSON_UNESCAPED_UNICODE) ?>;
var CD042_TYPE_ORDER = <?= json_encode($typeOrder) ?>;

(function(){
  var byType = {};
  CD042_TEMPLATES.forEach(function(t){ (byType[t.type]=byType[t.type]||[]).push(t); });
  var tabsEl = document.getElementById('cd042-tabs');
  var previewEl = document.getElementById('cd042-preview');
  var tplSel = document.getElementById('cd042-tpl');
  var brSel = document.getElementById('cd042-br');
  var deptSel = document.getElementById('cd042-dept');
  var maxInp = document.getElementById('cd042-max');
  var dlLink = document.getElementById('cd042-dl');
  var branches = <?= json_encode(array_reduce($branches ?? [], function($c,$b){ $c[$b->id]=array_map(function($d){return ['id'=>$d->id,'name'=>$d->name];}, $b->departments ?? []); return $c; }, []), JSON_UNESCAPED_UNICODE) ?>;

  function esc(s){ return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  function updateDl(){
    if(!tplSel||!dlLink) return;
    var url = APP_URL_CONST + '/evaluations/excel/template?template_id=' + encodeURIComponent(tplSel.value);
    if(brSel && brSel.value) url += '&branch_id=' + encodeURIComponent(brSel.value);
    if(deptSel && deptSel.value) url += '&department_id=' + encodeURIComponent(deptSel.value);
    if(maxInp) url += '&max_rows=' + encodeURIComponent(maxInp.value);
    dlLink.href = url;
  }

  function isImportantCol(c){
    if(!c) return false;
    if(c.kind === 'tail') return (c.key === 'evaluation_date' || c.key === 'evaluator_name');
    if(c.kind === 'fixed') return ['employee_name','employee_code','employee_branch','employee_department','program_title'].indexOf(c.key) !== -1;
    return c.kind === 'overall_grade';
  }

  function renderPreview(tplId){
    var st = CD042_STRUCTURES[tplId];
    if(!st || !previewEl) return;
    var cols = st.columns || [];
    var html = '<div class="xf-table-wrap"><table class="xl"><thead>';
    html += '<tr><th class="col-ltr" style="min-width:34px">#</th>';
    var i = 0;
    while (i < cols.length) {
      var span = 1;
      if (cols[i].group) {
        while (i + span < cols.length && cols[i+span].group === cols[i].group) span++;
      }
      var letters = cols[i].letter + (span>1 ? '–' + cols[i+span-1].letter : '');
      html += '<th class="col-ltr" colspan="' + span + '">' + letters + '</th>';
      i += span;
    }
    html += '</tr>';
    html += '<tr><th class="rn"></th>';
    i = 0;
    while (i < cols.length) {
      var c = cols[i];
      if (c.kind === 'fixed' || c.kind === 'tail') {
        html += '<th class="' + (c.kind==='tail'?'gnotes':'emp') + '" rowspan="2">' + esc(c.label) + '</th>';
        i++;
      } else if (c.kind === 'section' || c.kind === 'overall_grade') {
        var grp = c.group || (c.kind==='overall_grade' ? 'التقدير العام' : '');
        var span = 1;
        while (i + span < cols.length && ((cols[i+span].kind === 'section' || cols[i+span].kind === 'overall_grade') && (cols[i+span].group || '') === grp)) span++;
        var crit = (st.criteria || []).filter(function(x){return x.name===grp;})[0];
        var wtxt = crit ? ('الوزن ' + Math.round(crit.weight) + '٪') : '';
        html += '<th class="crit" colspan="' + span + '">' + esc(grp) + (wtxt ? '<span class="w" style="display:block;font-size:.7rem;opacity:.9">' + wtxt + '</span>' : '') + '</th>';
        i += span;
      } else {
        i++;
      }
    }
    html += '</tr><tr><th class="rn"></th>';
    cols.forEach(function(c){
      if (c.kind === 'section' || c.kind === 'overall_grade') {
        var tag = '';
        var t = c.section_type;
        if (t === 'percentage') tag = 'نسبة 0–100';
        else if (t === 'numeric') tag = 'رقمي';
        else if (t === 'boolean') tag = 'نعم/لا';
        else if (t === 'grade' || t === 'multiple_choice') tag = 'قائمة';
        else if (t === 'textarea' || t === 'text' || t === 'descriptive') tag = 'نص';
        else if (t === 'overall_grade' || c.kind === 'overall_grade') tag = 'تقدير';
        var req = (c.label || '').indexOf('*')>=0 ? ' *' : '';
        html += '<th class="sec">' + esc((c.label||'').replace(' *','')) + req + (tag ? '<span class="tag" style="display:block;font-size:.68rem;opacity:.85">' + tag + '</span>' : '') + '</th>';
      }
    });
    html += '</tr></thead><tbody>';
    (st.sample_rows || []).forEach(function(row, idx){
      var isNew = (idx === (st.sample_rows.length-1));
      html += '<tr>';
      html += '<td class="rn">' + (3+idx) + '</td>';
      cols.forEach(function(c){
        var v = row[c.letter];
        var cls = isNew ? 'new' : 'd';
        if (isImportantCol(c)) cls += ' num';
        var t = c.section_type;
        if (c.kind === 'fixed' && (c.key.indexOf('branch')!==-1 || c.key.indexOf('department')!==-1)) cls += ' dd';
        if (c.kind === 'section' && (t === 'boolean' || t === 'grade' || t === 'multiple_choice')) cls += ' dd';
        html += '<td class="' + cls + '">' + esc(v) + '</td>';
      });
      html += '</tr>';
    });
    html += '</tbody></table></div>';
    previewEl.innerHTML = html;
  }

  function showType(type){
    document.querySelectorAll('.ptab').forEach(function(t){t.classList.remove('active');});
    document.querySelectorAll('.ptab').forEach(function(t){ if(t.getAttribute('data-type')===type) t.classList.add('active'); });
    var list = byType[type] || [];
    if (list.length && tplSel) {
      tplSel.value = list[0].id;
      var wrapPrev = document.getElementById('cd042-type-tpl-wrap');
      if (list.length > 1) {
        var selHtml = '<div style="padding:10px 14px;background:#f8fafc;border-bottom:1px solid #d9e2ec;font-size:.84rem;display:flex;align-items:center;gap:10px"><label style="font-weight:700;color:#334e68">القالب:</label><select id="cd042-type-tpl" class="xf-select" style="max-width:420px;min-height:36px;padding:4px 10px">';
        list.forEach(function(t){ selHtml += '<option value="' + t.id + '">' + esc(t.name) + '</option>'; });
        selHtml += '</select></div>';
        if (wrapPrev) wrapPrev.remove();
        var wrap = document.createElement('div');
        wrap.id = 'cd042-type-tpl-wrap';
        wrap.innerHTML = selHtml;
        previewEl.parentNode.insertBefore(wrap, previewEl);
        document.getElementById('cd042-type-tpl').addEventListener('change', function(){ tplSel.value = this.value; renderPreview(parseInt(this.value)); updateDl(); });
      } else {
        if (wrapPrev) wrapPrev.remove();
      }
      renderPreview(list[0].id);
      updateDl();
    } else if (previewEl) {
      previewEl.innerHTML = '<div style="padding:18px;color:#627d98;font-size:.9rem">لا تتوفر معاينة هيكل لهذا النوع حالياً.</div>';
    }
  }

  if (tabsEl) {
    var firstType = null;
    CD042_TYPE_ORDER.forEach(function(type){
      if (CD042_TYPE_LABELS[type]) {
        if (!firstType && byType[type] && byType[type].length) firstType = type;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ptab xf-tab' + ((!byType[type] || !byType[type].length) ? ' disabled' : '');
        btn.setAttribute('data-type', type);
        btn.innerHTML = '<i class="fas ' + (CD042_TYPE_ICONS[type] || 'fa-file') + '" aria-hidden="true"></i> ' + esc(CD042_TYPE_LABELS[type] || type);
        if (byType[type] && byType[type].length) {
          btn.addEventListener('click', function(){ showType(type); });
        }
        tabsEl.appendChild(btn);
      }
    });
    if (firstType) showType(firstType);
    else if (CD042_TYPE_ORDER.length) showType(CD042_TYPE_ORDER[0]);
  }

  if (brSel) brSel.addEventListener('change', function(){
    var list = branches[this.value] || [];
    if (deptSel) {
      deptSel.innerHTML = '<option value="">الكل</option>';
      list.forEach(function(d){ var o = document.createElement('option'); o.value = d.id; o.textContent = d.name; deptSel.appendChild(o); });
      deptSel.disabled = !this.value;
    }
    updateDl();
  });
  if (tplSel) tplSel.addEventListener('change', function(){ renderPreview(parseInt(this.value)); updateDl(); });
  if (maxInp) maxInp.addEventListener('input', updateDl);
  if (deptSel) deptSel.addEventListener('change', updateDl);
  updateDl();

  var zone = document.getElementById('cd042-zone');
  var fileInput = document.getElementById('cd042-file');
  var fileInfo = document.getElementById('cd042-fileinfo');
  if (zone && fileInput) {
    zone.addEventListener('click', function(e){ if (e.target !== fileInput) fileInput.click(); });
    zone.addEventListener('dragover', function(e){ e.preventDefault(); zone.classList.add('drag'); });
    zone.addEventListener('dragleave', function(){ zone.classList.remove('drag'); });
    zone.addEventListener('drop', function(e){ e.preventDefault(); zone.classList.remove('drag'); if (e.dataTransfer && e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; showFileInfo(); } });
    fileInput.addEventListener('change', showFileInfo);
  }
  function showFileInfo(){
    if (!fileInput || !fileInput.files.length) { if (fileInfo) { fileInfo.textContent=''; fileInfo.style.color=''; } return; }
    var f = fileInput.files[0];
    var sizeMB = (f.size / (1024*1024)).toFixed(2);
    var ok = /\.xlsx$/i.test(f.name) && f.size <= 20*1024*1024;
    if (fileInfo) {
      fileInfo.textContent = f.name + ' — ' + sizeMB + ' ميجابايت';
      fileInfo.style.color = ok ? 'var(--xf-success)' : 'var(--xf-danger)';
      if (f.size > 20*1024*1024) fileInfo.textContent += ' · يتجاوز الحد الأقصى (20 ميجابايت)';
      if (!/\.xlsx$/i.test(f.name)) fileInfo.textContent += ' · الامتداد المدعوم: .xlsx فقط';
    }
  }

  function setBtnLoading(btn, loading, loadingText) {
    if (!btn) return;
    var label = btn.querySelector('.xf-btn-label');
    if (loading) {
      btn.disabled = true;
      btn.classList.add('is-loading');
      if (!btn.dataset.origLabel && label) btn.dataset.origLabel = label.textContent;
      if (label && loadingText) label.textContent = loadingText;
      if (!btn.querySelector('.xf-spinner')) {
        var sp = document.createElement('span'); sp.className = 'xf-spinner'; sp.setAttribute('aria-hidden','true');
        btn.insertBefore(sp, label || btn.firstChild);
      }
    } else {
      btn.disabled = false;
      btn.classList.remove('is-loading');
      var sp = btn.querySelector('.xf-spinner'); if (sp) sp.remove();
      if (label && btn.dataset.origLabel) label.textContent = btn.dataset.origLabel;
    }
  }

  var uploadForm = document.getElementById('cd042-upload-form');
  if (uploadForm) {
    uploadForm.addEventListener('submit', function(){
      setBtnLoading(document.getElementById('cd042-preview-btn'), true, 'جارٍ تحليل الملف...');
    });
  }
  var commitForm = document.getElementById('cd042-commit-form');
  if (commitForm) {
    commitForm.addEventListener('submit', function(){
      setBtnLoading(document.getElementById('cd042-commit-btn'), true, 'جارٍ اعتماد الاستيراد...');
    });
  }
})();
</script>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
