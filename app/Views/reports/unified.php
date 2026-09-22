<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<style>
.unified-hero{background:linear-gradient(135deg,#0d5e52 0%,#0a3d35 100%);color:#fff;border-radius:18px;padding:1.8rem;box-shadow:0 12px 32px rgba(13,94,82,.18);}
.filter-card{background:#fff;border:1px solid #e6efe9;border-radius:15px;box-shadow:0 8px 24px rgba(22,48,43,.06);padding:1.2rem;}
.stat-card{background:#fff;border:1px solid #edf3f0;border-radius:15px;box-shadow:0 8px 24px rgba(22,48,43,.06);padding:1rem;text-align:center;}
.chart-card{background:#fff;border:1px solid #edf3f0;border-radius:15px;box-shadow:0 8px 24px rgba(22,48,43,.06);padding:1.2rem;}
.cross-table th{background:#0d5e52;color:#fff;}
.gold{color:#c8943f;} .brand{color:#0d5e52;}
@media print{.no-print{display:none!important;} .chart-card{break-inside:avoid;}}
</style>
<div class="container">
  <div class="unified-hero mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <div><h3 class="mb-1"><i class="fas fa-chart-line me-2 gold"></i> التقارير الموحدة - لوحة القيادة</h3><small class="opacity-75">مقارنة أفراد / دورات / ورش / وحدات - متوسطات حية (approved/completed فقط)</small></div>
      <div class="no-print d-flex gap-2">
        <button onclick="window.print()" class="btn btn-light"><i class="fas fa-print"></i> طباعة A4</button>
        <a id="exportBtn" href="<?= APP_URL ?>/reports/unified/export" class="btn btn-warning"><i class="fas fa-file-csv"></i> تصدير Excel</a>
      </div>
    </div>
  </div>

  <div class="filter-card mb-3 no-print">
    <form id="filterForm" class="row g-3">
      <div class="col-md-2"><label class="form-label small fw-bold">نوع الكيان</label><select id="entity_type" class="form-select"><option value="all">الكل</option><option value="employee">فردي</option><option value="course">دورة</option><option value="workshop">ورشة</option><option value="branch">فرع</option><option value="department">قسم</option></select></div>
      <div class="col-md-2"><label class="form-label small fw-bold">الفرع</label><select id="branch_id" class="form-select"><option value="">الكل</option><?php foreach($branches as $b): ?><option value="<?= $b->id ?>"><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label small fw-bold">القسم</label><select id="department_id" class="form-select"><option value="">الكل</option><?php foreach($departments as $d): ?><option value="<?= $d->id ?>"><?= htmlspecialchars($d->name) ?> (<?= $d->branch_name ?>)</option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label small fw-bold">من</label><input type="date" id="start_date" class="form-control"></div>
      <div class="col-md-2"><label class="form-label small fw-bold">إلى</label><input type="date" id="end_date" class="form-control"></div>
      <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-success w-100"><i class="fas fa-search"></i> تطبيق</button></div>
    </form>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="stat-card"><small class="text-muted">إجمالي التقييمات الحية</small><h3 id="totalCnt" class="brand">-</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><small class="text-muted">متوسط عام</small><h3 id="avgScore" class="brand">-</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><small class="text-muted">أفراد</small><h3 id="empCnt">-</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><small class="text-muted">وحدات/دورات</small><h3 id="unitCnt">-</h3></div></div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="chart-card"><h6 class="fw-bold"><i class="fas fa-bullseye brand"></i> الرادار - متوسط كل مهمة</h6><canvas id="radarChart" height="220"></canvas></div></div>
    <div class="col-lg-6"><div class="chart-card"><h6 class="fw-bold"><i class="fas fa-chart-line brand"></i> الخط الزمني الشهري</h6><canvas id="lineChart" height="220"></canvas></div></div>
  </div>

  <div class="chart-card mb-3">
    <h6 class="fw-bold gold"><i class="fas fa-balance-scale"></i> التحليل المتقاطع - الوحدة مقابل متوسط أفرادها (الكنز الاستراتيجي)</h6>
    <small class="text-muted">يكشف إذا كان تراجع الوحدة سببه الإدارة أم الأفراد - الفرق = متوسط الوحدة - متوسط الأفراد</small>
    <div class="table-responsive mt-2">
      <table class="table table-bordered text-center">
        <thead><tr><th>الوحدة</th><th>النوع</th><th>متوسط الوحدة</th><th>متوسط الأفراد</th><th>الفرق</th><th>التشخيص</th></tr></thead>
        <tbody id="crossBody"><tr><td colspan="6" class="text-muted">لا بيانات وحدات مقيمة بعد</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/public/libs/chartjs/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const radarCtx=document.getElementById('radarChart'), lineCtx=document.getElementById('lineChart');
  let radarChart,lineChart;
  async function load(){
    const p=new URLSearchParams({
      entity_type:document.getElementById('entity_type').value,
      branch_id:document.getElementById('branch_id').value,
      department_id:document.getElementById('department_id').value,
      start_date:document.getElementById('start_date').value,
      end_date:document.getElementById('end_date').value
    });
    document.getElementById('exportBtn').href=`<?= APP_URL ?>/reports/unified/export?`+p.toString();
    const res=await fetch(`<?= APP_URL ?>/api/reports/unified?`+p.toString());
    if(res.status===403){document.body.innerHTML='<div class=\"alert alert-danger m-5\">403 - نطاق غير مصرح</div>'; return;}
    const data=await res.json();
    // stats
    const byType=data.byType||[]; let total=0,avg=0; byType.forEach(b=>{total+=parseInt(b.cnt);});
    document.getElementById('totalCnt').textContent=total;
    document.getElementById('avgScore').textContent=data.line.length? (data.line.reduce((a,c)=>a+parseFloat(c.avg_score),0)/data.line.length).toFixed(2)+'%':'-';
    document.getElementById('empCnt').textContent=(byType.find(b=>b.entity_type==='employee')?.cnt||0);
    document.getElementById('unitCnt').textContent=byType.filter(b=>['course','workshop','branch','department'].includes(b.entity_type)).reduce((a,c)=>a+parseInt(c.cnt),0);
    // radar
    const rLabels=data.radar.map(r=>r.section_name), rData=data.radar.map(r=>parseFloat(r.avg_score));
    if(radarChart) radarChart.destroy();
    radarChart=new Chart(radarCtx,{type:'radar',data:{labels:rLabels,datasets:[{label:'متوسط المهمة',data:rData,backgroundColor:'rgba(13,94,82,.15)',borderColor:'#0d5e52',pointBackgroundColor:'#c8943f'}]},options:{scales:{r:{beginAtZero:true,max:100}}}});
    // line
    const lLabels=data.line.map(r=>r.m), lData=data.line.map(r=>parseFloat(r.avg_score));
    if(lineChart) lineChart.destroy();
    lineChart=new Chart(lineCtx,{type:'line',data:{labels:lLabels,datasets:[{label:'متوسط شهري',data:lData,borderColor:'#0d5e52',backgroundColor:'rgba(13,94,82,.08)',tension:.42,fill:true}]},options:{scales:{y:{beginAtZero:true,max:100}}}});
    // cross
    const tb=document.getElementById('crossBody');
    if(!data.cross.length) tb.innerHTML='<tr><td colspan=\"6\" class=\"text-muted\">لا بيانات وحدات مقيمة بعد</td></tr>';
    else tb.innerHTML=data.cross.map(c=>{
      const diag=c.diff>5?'إدارة قوية':c.diff<-5?'تراجع أفراد':'متوازن';
      const cls=c.diff>5?'success':c.diff<-5?'danger':'warning';
      return `<tr><td>${c.unit_name}</td><td>${c.unit_type}</td><td>${c.unit_avg}%</td><td>${c.emp_avg}%</td><td class=\"fw-bold text-${cls}\">${c.diff>0?'+':''}${c.diff}%</td><td><span class=\"badge bg-${cls}\">${diag}</span></td></tr>`;
    }).join('');
  }
  document.getElementById('filterForm').addEventListener('submit',e=>{e.preventDefault();load();});
  load();
});
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
