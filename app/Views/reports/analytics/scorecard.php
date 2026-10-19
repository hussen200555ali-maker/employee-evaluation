<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<style>
.sc-hero{background:linear-gradient(135deg,#0d5e52 0%,#0a3d35 100%);color:#fff;border-radius:18px;padding:1.6rem;box-shadow:0 12px 32px rgba(13,94,82,.18);}
.sc-card{background:#fff;border:1px solid #e6efe9;border-radius:15px;box-shadow:0 8px 24px rgba(22,48,43,.06);padding:1.2rem;}
.brand{color:#0d5e52;} .gold{color:#c8943f;}
.arrow-up{color:#198754;font-weight:bold;} .arrow-down{color:#dc3545;font-weight:bold;}
@media print{.no-print{display:none!important;}}
</style>

<div class="container-fluid mt-3">
  <div class="sc-hero mb-3 no-print">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h3 class="mb-1"><i class="fas fa-id-badge me-2 gold"></i> بطاقة الأداء الفردية (Employee Scorecard)</h3>
        <small class="opacity-75">متوسط كل موظف مع اتجاه الدرجة — أسهم خضراء/حمراء للمقارنة</small>
      </div>
      <button onclick="window.print()" class="btn btn-light"><i class="fas fa-print"></i> طباعة</button>
    </div>
  </div>

  <div class="sc-card mb-3 no-print">
    <form id="scFilters" class="row g-3">
      <div class="col-md-2"><label class="form-label small fw-bold">السنة</label><input type="number" id="f_year" class="form-control" min="2000" max="2100"></div>
      <div class="col-md-2"><label class="form-label small fw-bold">الشهر</label><select id="f_month" class="form-select"><option value="">الكل</option><?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>"><?= $m ?></option><?php endfor; ?></select></div>
      <div class="col-md-2"><label class="form-label small fw-bold">الفرع</label><select id="f_branch" class="form-select"><option value="">الكل</option><?php foreach ($branches as $b): ?><option value="<?= (int)$b->id ?>"><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label small fw-bold">القسم</label><select id="f_department" class="form-select"><option value="">الكل</option><?php foreach ($departments as $d): ?><option value="<?= (int)$d->id ?>"><?= htmlspecialchars($d->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label small fw-bold">القالب</label><select id="f_template" class="form-select"><option value="">الكل</option><?php foreach ($templates as $t): ?><option value="<?= (int)$t->id ?>"><?= htmlspecialchars($t->template_name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-1 d-flex align-items-end"><button type="submit" class="btn btn-success w-100"><i class="fas fa-search"></i></button></div>
    </form>
  </div>

  <div class="sc-card">
    <div class="table-responsive">
      <table class="table table-bordered table-hover text-center">
        <thead><tr><th>الموظف</th><th>القسم</th><th>الفرع</th><th>متوسط الدرجة</th><th>أدنى</th><th>أعلى</th><th>عدد التقييمات</th><th>الاتجاه</th></tr></thead>
        <tbody id="scBody"><tr><td colspan="8" class="text-muted">جارٍ التحميل…</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  const BASE = '<?= APP_URL ?>';

  function collect() {
    const f = {};
    const y = document.getElementById('f_year').value.trim(); if (y) f.year = y;
    const m = document.getElementById('f_month').value; if (m) f.month = m;
    const br = document.getElementById('f_branch').value; if (br) f.branch_id = br;
    const dp = document.getElementById('f_department').value; if (dp) f.department_id = dp;
    const tp = document.getElementById('f_template').value; if (tp) f.template_id = tp;
    return f;
  }

  async function load() {
    const filters = collect();
    const [resOverall, resPrev] = await Promise.all([
      fetch(BASE + '/reports/builder/data', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ filters: filters, dimensions: ['employee_name', 'department_name', 'branch_name'], measures: ['avg_score', 'min_score', 'max_score', 'count_evaluations'] })
      }),
      fetch(BASE + '/reports/builder/data', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ filters: filters, dimensions: ['employee_name', 'year', 'month'], measures: ['avg_score'] })
      })
    ]);
    const overall = await resOverall.json();
    const prev = await resPrev.json();

    const body = document.getElementById('scBody');
    const rows = overall.rows || [];
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="8" class="text-muted">لا توجد بيانات</td></tr>';
      return;
    }

    const timeline = {};
    (prev.rows || []).forEach(function (r) {
      if (!r.employee_name) return;
      (timeline[r.employee_name] = timeline[r.employee_name] || []).push({
        y: parseInt(r.year, 10), m: parseInt(r.month, 10), avg: parseFloat(r.avg_score)
      });
    });
    Object.keys(timeline).forEach(function (k) {
      timeline[k].sort(function (a, b) { return (a.y - b.y) || (a.m - b.m); });
    });

    rows.sort(function (a, b) { return parseFloat(b.avg_score) - parseFloat(a.avg_score); });
    body.innerHTML = rows.map(function (r) {
      let arrow = '<span class="text-muted">—</span>';
      const t = timeline[r.employee_name] || [];
      if (t.length >= 2) {
        const last = t[t.length - 1].avg, before = t[t.length - 2].avg;
        if (last > before) arrow = '<span class="arrow-up"><i class="fas fa-arrow-up"></i> +' + (last - before).toFixed(1) + '</span>';
        else if (last < before) arrow = '<span class="arrow-down"><i class="fas fa-arrow-down"></i> ' + (last - before).toFixed(1) + '</span>';
        else arrow = '<span class="text-secondary">= ثابت</span>';
      }
      return '<tr><td class="text-start fw-bold">' + (r.employee_name || '-') + '</td>'
        + '<td>' + (r.department_name || '-') + '</td>'
        + '<td>' + (r.branch_name || '-') + '</td>'
        + '<td class="fw-bold">' + parseFloat(r.avg_score).toFixed(2) + '%</td>'
        + '<td>' + parseFloat(r.min_score).toFixed(1) + '</td>'
        + '<td>' + parseFloat(r.max_score).toFixed(1) + '</td>'
        + '<td>' + r.count_evaluations + '</td>'
        + '<td>' + arrow + '</td></tr>';
    }).join('');
  }

  document.getElementById('scFilters').addEventListener('submit', function (e) { e.preventDefault(); load(); });
  load();
});
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
