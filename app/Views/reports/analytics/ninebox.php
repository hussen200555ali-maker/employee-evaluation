<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<style>
.nb-hero{background:linear-gradient(135deg,#0d5e52 0%,#0a3d35 100%);color:#fff;border-radius:18px;padding:1.6rem;box-shadow:0 12px 32px rgba(13,94,82,.18);}
.nb-card{background:#fff;border:1px solid #e6efe9;border-radius:15px;box-shadow:0 8px 24px rgba(22,48,43,.06);padding:1.2rem;}
.brand{color:#0d5e52;} .gold{color:#c8943f;}
.nb-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}
.nb-cell{border-radius:12px;padding:.8rem;min-height:170px;border:1px solid #e6efe9;}
.nb-cell h6{font-size:.8rem;margin-bottom:.5rem;}
.nb-chip{display:inline-block;background:#fff;border:1px solid #d8e6e0;border-radius:8px;padding:.2rem .5rem;margin:2px;font-size:.75rem;}
.nb-axis-title{writing-mode:vertical-rl;transform:rotate(180deg);font-weight:bold;color:#0d5e52;}
@media print{.no-print{display:none!important;} .nb-cell{break-inside:avoid;}}
</style>

<div class="container-fluid mt-3">
  <div class="nb-hero mb-3 no-print">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h3 class="mb-1"><i class="fas fa-th-large me-2 gold"></i> مصفوفة الأداء 9-Box</h3>
        <small class="opacity-75">المحور الأفقي: النتيجة النهائية (متوسط الكفاءة العام هو المحور الرأسي) — حدود الخلايا 60% و 80%</small>
      </div>
      <button onclick="window.print()" class="btn btn-light"><i class="fas fa-print"></i> طباعة</button>
    </div>
  </div>

  <div class="nb-card mb-3 no-print">
    <form id="nbFilters" class="row g-3">
      <div class="col-md-2"><label class="form-label small fw-bold">السنة</label><input type="number" id="f_year" class="form-control" min="2000" max="2100"></div>
      <div class="col-md-2"><label class="form-label small fw-bold">الشهر</label><select id="f_month" class="form-select"><option value="">الكل</option><?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>"><?= $m ?></option><?php endfor; ?></select></div>
      <div class="col-md-2"><label class="form-label small fw-bold">الفرع</label><select id="f_branch" class="form-select"><option value="">الكل</option><?php foreach ($branches as $b): ?><option value="<?= (int)$b->id ?>"><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><label class="form-label small fw-bold">القسم</label><select id="f_department" class="form-select"><option value="">الكل</option><?php foreach ($departments as $d): ?><option value="<?= (int)$d->id ?>"><?= htmlspecialchars($d->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label small fw-bold">القالب</label><select id="f_template" class="form-select"><option value="">الكل</option><?php foreach ($templates as $t): ?><option value="<?= (int)$t->id ?>"><?= htmlspecialchars($t->template_name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-1 d-flex align-items-end"><button type="submit" class="btn btn-success w-100"><i class="fas fa-search"></i></button></div>
    </form>
  </div>

  <div class="nb-card">
    <div class="d-flex align-items-stretch gap-2">
      <div class="nb-axis-title d-flex align-items-center justify-content-center" style="min-height:520px;">متوسط الكفاءة العام (Y)</div>
      <div class="flex-grow-1">
        <div class="nb-grid" id="nbGrid"></div>
        <div class="text-center fw-bold brand mt-2">النتيجة النهائية / الأداء العام (X) →</div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  const BASE = '<?= APP_URL ?>';

  const cells = [
    { id: 'c11', label: '1. أداء منخفض / كفاءة منخفضة', bg: '#f8d7da', x: 'low',  y: 'low'  },
    { id: 'c12', label: '2. أداء متوسط / كفاءة منخفضة', bg: '#fff3cd', x: 'mid',  y: 'low'  },
    { id: 'c13', label: '3. أداء مرتفع / كفاءة منخفضة', bg: '#ffeeba', x: 'high', y: 'low'  },
    { id: 'c21', label: '4. أداء منخفض / كفاءة متوسطة', bg: '#ffeeba', x: 'low',  y: 'mid'  },
    { id: 'c22', label: '5. أداء متوسط / كفاءة متوسطة', bg: '#d1e7dd', x: 'mid',  y: 'mid'  },
    { id: 'c23', label: '6. أداء مرتفع / كفاءة متوسطة', bg: '#badbcc', x: 'high', y: 'mid'  },
    { id: 'c31', label: '7. أداء منخفض / كفاءة مرتفعة', bg: '#badbcc', x: 'low',  y: 'high' },
    { id: 'c32', label: '8. أداء متوسط / كفاءة مرتفعة', bg: '#badbcc', x: 'mid',  y: 'high' },
    { id: 'c33', label: '9. أداء مرتفع / كفاءة مرتفعة (نجم)', bg: '#a3cfbb', x: 'high', y: 'high' }
  ];

  function band(v) { return v < 60 ? 'low' : (v < 80 ? 'mid' : 'high'); }

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
    const res = await fetch(BASE + '/reports/analytics/ninebox/data', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ filters: collect() })
    });
    const data = await res.json();
    const rows = data.rows || [];
    const buckets = {};
    cells.forEach(function (c) { buckets[c.id] = []; });
    rows.forEach(function (r) {
      const x = parseFloat(r.x_score), y = parseFloat(r.y_score);
      if (isNaN(x) || isNaN(y)) return;
      const xb = band(x), yb = band(y);
      const cell = cells.find(function (c) { return c.x === xb && c.y === yb; });
      if (cell) buckets[cell.id].push(r);
    });
    document.getElementById('nbGrid').innerHTML = cells.map(function (c) {
      const list = buckets[c.id];
      const chips = list.map(function (r) {
        return '<span class="nb-chip" title="X=' + parseFloat(r.x_score).toFixed(1) + ' / Y=' + parseFloat(r.y_score).toFixed(1) + '">'
          + esc(r.evaluated_name) + ' (' + parseFloat(r.x_score).toFixed(0) + ')</span>';
      }).join('');
      return '<div class="nb-cell" style="background:' + c.bg + '">'
        + '<h6 class="fw-bold">' + c.label + ' — ' + list.length + '</h6>' + chips + '</div>';
    }).join('');
  }

  document.getElementById('nbFilters').addEventListener('submit', function (e) { e.preventDefault(); load(); });
  load();
});
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
