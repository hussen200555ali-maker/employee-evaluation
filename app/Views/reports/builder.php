<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<style>
.rb-hero{background:linear-gradient(135deg,#0d5e52 0%,#0a3d35 100%);color:#fff;border-radius:18px;padding:1.6rem;box-shadow:0 12px 32px rgba(13,94,82,.18);}
.rb-card{background:#fff;border:1px solid #e6efe9;border-radius:15px;box-shadow:0 8px 24px rgba(22,48,43,.06);padding:1.2rem;}
.dim-btn.active{background:#0d5e52;color:#fff;border-color:#0d5e52;}
.rb-table th{background:#0d5e52;color:#fff;white-space:nowrap;}
.rb-table td{vertical-align:middle;}
.brand{color:#0d5e52;} .gold{color:#c8943f;}
@media print{.no-print{display:none!important;}}
</style>

<div class="container-fluid mt-3">
  <div class="rb-hero mb-3 no-print">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h3 class="mb-1"><i class="fas fa-toolbox me-2 gold"></i> صانع التقارير الديناميكي (CD-008)</h3>
        <small class="opacity-75">اختر الفلاتر والأبعاد والمقاييس — النتائج تُبنى عبر ReportEngine (whitelist + prepared statements)</small>
      </div>
      <div class="d-flex gap-2">
        <button id="btnExcel" class="btn btn-warning"><i class="fas fa-file-excel"></i> تصدير Excel</button>
        <button id="btnPdf" class="btn btn-danger"><i class="fas fa-file-pdf"></i> تصدير PDF</button>
        <button onclick="window.print()" class="btn btn-light"><i class="fas fa-print"></i> طباعة</button>
      </div>
    </div>
  </div>

  <div class="rb-card mb-3 no-print">
    <h6 class="fw-bold brand mb-3"><i class="fas fa-filter"></i> الفلاتر</h6>
    <div class="row g-3">
      <div class="col-md-2"><label class="form-label small fw-bold">السنة</label><input type="number" id="f_year" class="form-control" placeholder="مثال: 2026" min="2000" max="2100"></div>
      <div class="col-md-2"><label class="form-label small fw-bold">الشهر</label><select id="f_month" class="form-select"><option value="">الكل</option><?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>"><?= $m ?></option><?php endfor; ?></select></div>
      <div class="col-md-3"><label class="form-label small fw-bold">الفرع</label><select id="f_branch" class="form-select"><option value="">الكل</option><?php foreach ($branches as $b): ?><option value="<?= (int)$b->id ?>"><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label small fw-bold">القسم</label><select id="f_department" class="form-select"><option value="">الكل</option><?php foreach ($departments as $d): ?><option value="<?= (int)$d->id ?>"><?= htmlspecialchars($d->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label small fw-bold">القالب</label><select id="f_template" class="form-select"><option value="">الكل</option><?php foreach ($templates as $t): ?><option value="<?= (int)$t->id ?>"><?= htmlspecialchars($t->template_name) ?></option><?php endforeach; ?></select></div>
    </div>

    <hr class="my-3">
    <h6 class="fw-bold brand mb-2"><i class="fas fa-th"></i> الأبعاد (GROUP BY)</h6>
    <div id="dimContainer" class="d-flex flex-wrap gap-2 mb-3">
      <?php
      $dims = [
          'branch_name' => 'الفرع', 'department_name' => 'القسم', 'employee_name' => 'الموظف',
          'template_name' => 'القالب', 'criterion_name' => 'المعيار', 'month' => 'الشهر', 'year' => 'السنة',
      ];
      foreach ($dims as $k => $lbl):
      ?>
      <button type="button" class="btn btn-outline-secondary btn-sm dim-btn active" data-dim="<?= $k ?>"><i class="fas fa-check fa-xs me-1"></i><?= $lbl ?></button>
      <?php endforeach; ?>
    </div>

    <h6 class="fw-bold brand mb-2"><i class="fas fa-calculator"></i> المقاييس</h6>
    <div id="meaContainer" class="d-flex flex-wrap gap-2">
      <?php
      $meas = [
          'avg_score' => 'متوسط الدرجة', 'min_score' => 'أدنى درجة', 'max_score' => 'أعلى درجة',
          'count_evaluations' => 'عدد التقييمات', 'count_employees' => 'عدد الموظفين', 'completion_rate' => 'نسبة الإنجاز %',
      ];
      foreach ($meas as $k => $lbl):
      ?>
      <button type="button" class="btn btn-outline-secondary btn-sm mea-btn active" data-mea="<?= $k ?>"><i class="fas fa-check fa-xs me-1"></i><?= $lbl ?></button>
      <?php endforeach; ?>
    </div>

    <div class="mt-3">
      <button id="btnRun" class="btn btn-success btn-lg"><i class="fas fa-play"></i> إنشاء التقرير</button>
      <span id="runMeta" class="text-muted small ms-3"></span>
    </div>
  </div>

  <div class="rb-card">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="fw-bold brand mb-0"><i class="fas fa-table"></i> النتائج</h6>
      <span id="rowCount" class="badge bg-secondary">0 صف</span>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-hover rb-table text-center" id="resultTable">
        <thead><tr id="resultHead"><th class="text-muted">اختر الإعدادات ثم اضغط إنشاء التقرير</th></tr></thead>
        <tbody id="resultBody"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const CSRF = meta ? meta.content : '';
  const BASE = '<?= APP_URL ?>';

  function collect() {
    const filters = {};
    const y = document.getElementById('f_year').value.trim();
    if (y) filters.year = y;
    const m = document.getElementById('f_month').value;
    if (m) filters.month = m;
    const br = document.getElementById('f_branch').value;
    if (br) filters.branch_id = br;
    const dp = document.getElementById('f_department').value;
    if (dp) filters.department_id = dp;
    const tp = document.getElementById('f_template').value;
    if (tp) filters.template_id = tp;
    const dims = Array.from(document.querySelectorAll('.dim-btn.active')).map(function(b){ return b.dataset.dim; });
    const mea = Array.from(document.querySelectorAll('.mea-btn.active')).map(function(b){ return b.dataset.mea; });
    return { filters: filters, dimensions: dims, measures: mea };
  }

  document.querySelectorAll('.dim-btn, .mea-btn').forEach(function (btn) {
    btn.addEventListener('click', function () { btn.classList.toggle('active'); });
  });

  function esc(v) {
    if (v === null || v === undefined) return '';
    return String(v).replace(/[&<>"']/g, function (c) {
      return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
    });
  }

  function render(rows) {
    const head = document.getElementById('resultHead');
    const body = document.getElementById('resultBody');
    document.getElementById('rowCount').textContent = rows.length + ' صف';
    if (!rows.length) {
      head.innerHTML = '<th class="text-muted">لا توجد نتائج للاختيارات الحالية</th>';
      body.innerHTML = '';
      return;
    }
    const cols = Object.keys(rows[0]);
    head.innerHTML = cols.map(function (c) { return '<th>' + esc(c) + '</th>'; }).join('');
    body.innerHTML = rows.map(function (r) {
      return '<tr>' + cols.map(function (c) { return '<td>' + esc(r[c]) + '</td>'; }).join('') + '</tr>';
    }).join('');
  }

  document.getElementById('btnRun').addEventListener('click', async function () {
    const payload = collect();
    const t0 = performance.now();
    document.getElementById('runMeta').textContent = 'جارٍ التنفيذ…';
    try {
      const res = await fetch(BASE + '/reports/builder/data', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      const ms = Math.round(performance.now() - t0);
      if (data.success) {
        render(data.rows || []);
        document.getElementById('runMeta').textContent = 'زمن الاستعلام: ' + ms + 'ms';
      } else {
        document.getElementById('runMeta').textContent = 'فشل: ' + (data.message || 'خطأ غير معروف');
      }
    } catch (e) {
      document.getElementById('runMeta').textContent = 'خطأ اتصال: ' + e.message;
    }
  });

  async function exportTo(kind) {
    const payload = collect();
    const url = BASE + '/reports/builder/export/' + kind;
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(payload)
    });
    if (!res.ok) { alert('فشل التصدير (HTTP ' + res.status + ')'); return; }
    const blob = await res.blob();
    const cd = res.headers.get('Content-Disposition') || '';
    let name = 'report_' + kind + '.bin';
    const m = cd.match(/filename="?([^"]+)"?/);
    if (m) name = m[1];
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = name;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(a.href);
  }
  document.getElementById('btnExcel').addEventListener('click', function(){ exportTo('excel'); });
  document.getElementById('btnPdf').addEventListener('click', function(){ exportTo('pdf'); });
});
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
