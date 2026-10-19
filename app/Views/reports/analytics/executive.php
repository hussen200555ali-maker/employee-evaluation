<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<style>
.ex-hero{background:linear-gradient(135deg,#0d5e52 0%,#0a3d35 100%);color:#fff;border-radius:18px;padding:1.6rem;box-shadow:0 12px 32px rgba(13,94,82,.18);}
.ex-card{background:#fff;border:1px solid #e6efe9;border-radius:15px;box-shadow:0 8px 24px rgba(22,48,43,.06);padding:1.2rem;}
.kpi{background:#fff;border:1px solid #edf3f0;border-radius:15px;box-shadow:0 8px 24px rgba(22,48,43,.06);padding:1rem;text-align:center;}
.kpi h2{color:#0d5e52;}
.brand{color:#0d5e52;} .gold{color:#c8943f;}
@media print{.no-print{display:none!important;}}
</style>

<div class="container-fluid mt-3">
  <div class="ex-hero mb-3 no-print">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h3 class="mb-1"><i class="fas fa-tachometer-alt me-2 gold"></i> لوحة القيادة التنفيذية (Executive Dashboard)</h3>
        <small class="opacity-75">4 مؤشرات رئيسية + أفضل 3 فروع + أسوأ 3 أقسام</small>
      </div>
      <button onclick="window.print()" class="btn btn-light"><i class="fas fa-print"></i> طباعة</button>
    </div>
  </div>

  <div class="ex-card mb-3 no-print">
    <form id="exFilters" class="row g-3">
      <div class="col-md-2"><label class="form-label small fw-bold">السنة</label><input type="number" id="f_year" class="form-control" min="2000" max="2100"></div>
      <div class="col-md-2"><label class="form-label small fw-bold">الشهر</label><select id="f_month" class="form-select"><option value="">الكل</option><?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>"><?= $m ?></option><?php endfor; ?></select></div>
      <div class="col-md-2"><label class="form-label small fw-bold">الفرع</label><select id="f_branch" class="form-select"><option value="">الكل</option><?php foreach ($branches as $b): ?><option value="<?= (int)$b->id ?>"><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label small fw-bold">القسم</label><select id="f_department" class="form-select"><option value="">الكل</option><?php foreach ($departments as $d): ?><option value="<?= (int)$d->id ?>"><?= htmlspecialchars($d->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label small fw-bold">القالب</label><select id="f_template" class="form-select"><option value="">الكل</option><?php foreach ($templates as $t): ?><option value="<?= (int)$t->id ?>"><?= htmlspecialchars($t->template_name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-1 d-flex align-items-end"><button type="submit" class="btn btn-success w-100"><i class="fas fa-search"></i></button></div>
    </form>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="kpi"><small class="text-muted">المتوسط العام</small><h2 id="kpiAvg">-</h2><small class="text-muted">من 100</small></div></div>
    <div class="col-md-3"><div class="kpi"><small class="text-muted">نسبة الإنجاز</small><h2 id="kpiCompletion">-</h2><small class="text-muted">من التقييمات</small></div></div>
    <div class="col-md-3"><div class="kpi"><small class="text-muted">الموظفون المُقيَّمون</small><h2 id="kpiEmployees">-</h2><small class="text-muted">فرد</small></div></div>
    <div class="col-md-3"><div class="kpi"><small class="text-muted">التقييمات المكتملة</small><h2 id="kpiCompleted">-</h2><small class="text-muted">تقييم</small></div></div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="ex-card">
        <h6 class="fw-bold brand"><i class="fas fa-trophy gold"></i> أفضل 3 فروع</h6>
        <div class="table-responsive">
          <table class="table table-bordered text-center">
            <thead><tr><th>#</th><th>الفرع</th><th>متوسط الدرجة</th></tr></thead>
            <tbody id="topBranches"><tr><td colspan="3" class="text-muted">جارٍ التحميل…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="ex-card">
        <h6 class="fw-bold text-danger"><i class="fas fa-exclamation-triangle"></i> أسوأ 3 أقسام</h6>
        <div class="table-responsive">
          <table class="table table-bordered text-center">
            <thead><tr><th>#</th><th>القسم</th><th>متوسط الدرجة</th></tr></thead>
            <tbody id="bottomDepts"><tr><td colspan="3" class="text-muted">جارٍ التحميل…</td></tr></tbody>
          </table>
        </div>
      </div>
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

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
    });
  }

  async function load() {
    const res = await fetch(BASE + '/reports/analytics/executive/data', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ filters: collect() })
    });
    const data = await res.json();
    const k = data.kpis || {};
    document.getElementById('kpiAvg').textContent = k.overall_avg != null ? parseFloat(k.overall_avg).toFixed(2) + '%' : '-';
    document.getElementById('kpiCompletion').textContent = k.completion_rate != null ? parseFloat(k.completion_rate).toFixed(1) + '%' : '-';
    document.getElementById('kpiEmployees').textContent = k.employees_evaluated != null ? k.employees_evaluated : '-';
    document.getElementById('kpiCompleted').textContent = k.evaluations_completed != null ? k.evaluations_completed : '-';

    const tb = data.top_branches || [];
    document.getElementById('topBranches').innerHTML = tb.length
      ? tb.map(function (r, i) {
          return '<tr><td>' + (i + 1) + '</td><td class="text-start">' + esc(r.branch_name) + '</td><td class="fw-bold">' + parseFloat(r.avg_score).toFixed(2) + '%</td></tr>';
        }).join('')
      : '<tr><td colspan="3" class="text-muted">لا بيانات</td></tr>';

    const bd = data.bottom_departments || [];
    document.getElementById('bottomDepts').innerHTML = bd.length
      ? bd.map(function (r, i) {
          return '<tr><td>' + (i + 1) + '</td><td class="text-start">' + esc(r.department_name) + '</td><td class="fw-bold text-danger">' + parseFloat(r.avg_score).toFixed(2) + '%</td></tr>';
        }).join('')
      : '<tr><td colspan="3" class="text-muted">لا بيانات</td></tr>';
  }

  document.getElementById('exFilters').addEventListener('submit', function (e) { e.preventDefault(); load(); });
  load();
});
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
