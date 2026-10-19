<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<style>
.gap-hero{background:linear-gradient(135deg,#0d5e52 0%,#0a3d35 100%);color:#fff;border-radius:18px;padding:1.6rem;box-shadow:0 12px 32px rgba(13,94,82,.18);}
.gap-card{background:#fff;border:1px solid #e6efe9;border-radius:15px;box-shadow:0 8px 24px rgba(22,48,43,.06);padding:1.2rem;}
.brand{color:#0d5e52;} .gold{color:#c8943f;}
@media print{.no-print{display:none!important;}}
</style>

<div class="container-fluid mt-3">
  <div class="gap-hero mb-3 no-print">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h3 class="mb-1"><i class="fas fa-bullseye me-2 gold"></i> تحليل الفجوات (Gap Analysis)</h3>
        <small class="opacity-75">متوسط كل معيار مقابل المستهدف — الأعمدة الحمراء أقل من 60%</small>
      </div>
      <button onclick="window.print()" class="btn btn-light"><i class="fas fa-print"></i> طباعة</button>
    </div>
  </div>

  <div class="gap-card mb-3 no-print">
    <form id="gapFilters" class="row g-3">
      <div class="col-md-2"><label class="form-label small fw-bold">السنة</label><input type="number" id="f_year" class="form-control" min="2000" max="2100"></div>
      <div class="col-md-2"><label class="form-label small fw-bold">الشهر</label><select id="f_month" class="form-select"><option value="">الكل</option><?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>"><?= $m ?></option><?php endfor; ?></select></div>
      <div class="col-md-2"><label class="form-label small fw-bold">الفرع</label><select id="f_branch" class="form-select"><option value="">الكل</option><?php foreach ($branches as $b): ?><option value="<?= (int)$b->id ?>"><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label small fw-bold">القسم</label><select id="f_department" class="form-select"><option value="">الكل</option><?php foreach ($departments as $d): ?><option value="<?= (int)$d->id ?>"><?= htmlspecialchars($d->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label small fw-bold">القالب</label><select id="f_template" class="form-select"><option value="">الكل</option><?php foreach ($templates as $t): ?><option value="<?= (int)$t->id ?>"><?= htmlspecialchars($t->template_name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-1 d-flex align-items-end"><button type="submit" class="btn btn-success w-100"><i class="fas fa-search"></i></button></div>
    </form>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="gap-card text-center"><small class="text-muted">عدد المعايير</small><h3 id="kpiCount" class="brand">-</h3></div></div>
    <div class="col-md-3"><div class="gap-card text-center"><small class="text-muted">متوسط عام</small><h3 id="kpiAvg" class="brand">-</h3></div></div>
    <div class="col-md-3"><div class="gap-card text-center"><small class="text-muted">معايير حرجة (&lt;60%)</small><h3 id="kpiCritical" class="text-danger">-</h3></div></div>
    <div class="col-md-3"><div class="gap-card text-center"><small class="text-muted">أكبر فجوة</small><h3 id="kpiWorst" class="text-danger">-</h3></div></div>
  </div>

  <div class="gap-card mb-3">
    <h6 class="fw-bold brand"><i class="fas fa-chart-bar"></i> متوسط كل معيار (المستهدف 60%)</h6>
    <canvas id="gapChart" height="110"></canvas>
  </div>

  <div class="gap-card">
    <div class="table-responsive">
      <table class="table table-bordered table-hover text-center">
        <thead><tr><th>المعيار</th><th>متوسط الدرجة</th><th>الفجوة عن 60%</th><th>عدد التقييمات</th><th>الحالة</th></tr></thead>
        <tbody id="gapBody"><tr><td colspan="5" class="text-muted">جارٍ التحميل…</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/public/libs/chartjs/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  const BASE = '<?= APP_URL ?>';
  let chart = null;

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
    const res = await fetch(BASE + '/reports/analytics/gap/data', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ filters: collect() })
    });
    const data = await res.json();
    const rows = (data.rows || []).filter(function (r) { return r.criterion_name; });
    rows.sort(function (a, b) { return parseFloat(a.avg_score) - parseFloat(b.avg_score); });

    document.getElementById('kpiCount').textContent = rows.length;
    if (!rows.length) {
      document.getElementById('kpiAvg').textContent = '-';
      document.getElementById('kpiCritical').textContent = '0';
      document.getElementById('kpiWorst').textContent = '-';
      document.getElementById('gapBody').innerHTML = '<tr><td colspan="5" class="text-muted">لا توجد بيانات</td></tr>';
      if (chart) { chart.destroy(); chart = null; }
      return;
    }
    let sum = 0, crit = 0;
    rows.forEach(function (r) { const v = parseFloat(r.avg_score); sum += v; if (v < 60) crit++; });
    document.getElementById('kpiAvg').textContent = (sum / rows.length).toFixed(2) + '%';
    document.getElementById('kpiCritical').textContent = crit;
    document.getElementById('kpiWorst').textContent = rows[0].criterion_name + ' (' + parseFloat(rows[0].avg_score).toFixed(1) + '%)';

    document.getElementById('gapBody').innerHTML = rows.map(function (r) {
      const v = parseFloat(r.avg_score);
      const gap = (v - 60).toFixed(1);
      const critCls = v < 60 ? 'danger' : (v < 75 ? 'warning' : 'success');
      const critTxt = v < 60 ? 'حرج' : (v < 75 ? 'يحتاج متابعة' : 'جيد');
      return '<tr><td class="text-start">' + r.criterion_name + '</td>'
        + '<td class="fw-bold">' + v.toFixed(2) + '%</td>'
        + '<td class="fw-bold text-' + (gap < 0 ? 'danger' : 'success') + '">' + (gap > 0 ? '+' : '') + gap + '</td>'
        + '<td>' + r.count_evaluations + '</td>'
        + '<td><span class="badge bg-' + critCls + '">' + critTxt + '</span></td></tr>';
    }).join('');

    if (chart) chart.destroy();
    chart = new Chart(document.getElementById('gapChart'), {
      type: 'bar',
      data: {
        labels: rows.map(function (r) { return r.criterion_name; }),
        datasets: [{
          label: 'متوسط المعيار %',
          data: rows.map(function (r) { return parseFloat(r.avg_score); }),
          backgroundColor: rows.map(function (r) { return parseFloat(r.avg_score) < 60 ? '#dc3545' : '#0d5e52'; })
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100 } }
      }
    });
  }

  document.getElementById('gapFilters').addEventListener('submit', function (e) { e.preventDefault(); load(); });
  load();
});
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
