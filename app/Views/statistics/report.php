<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<style>
.stat-filters{background:#fff;border:1px solid #e6efe9;border-radius:14px;box-shadow:0 8px 24px rgba(22,48,43,.06);padding:1.2rem;margin-bottom:1rem;}
.a4-page{background:#fff;width:210mm;min-height:297mm;margin:0 auto;padding:12mm 14mm;border:1px solid #e5e7eb;box-shadow:0 10px 30px rgba(0,0,0,.08);font-family:'Tajawal',sans-serif;}
.a4-header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #0d5e52;padding-bottom:8px;margin-bottom:10px;}
.a4-header .hr-badge{width:54px;height:54px;border:2px solid #c8943f;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#0d5e52;font-weight:900;}
.a4-title{text-align:center;margin:8px 0 12px;}
.a4-title h3{margin:0;color:#0d5e52;font-weight:900;font-size:16px;}
.a4-title p{margin:2px 0;color:#64748b;font-size:11px;}
.a4-section{border:1px solid #0d5e52;border-radius:8px;overflow:hidden;margin-bottom:10px;}
.a4-section-header{background:#0d2845;color:#fff;padding:6px 10px;font-weight:800;font-size:12px;display:flex;justify-content:space-between;align-items:center;}
.a4-table{width:100%;border-collapse:collapse;font-size:11px;}
.a4-table th{background:#f1f5f9;color:#0d5e52;padding:6px 8px;border:1px solid #e2e8f0;text-align:center;font-weight:800;}
.a4-table td{padding:6px 8px;border:1px solid #e2e8f0;text-align:center;}
.badge-level{padding:2px 8px;border-radius:10px;font-weight:800;font-size:10px;color:#fff;}
.badge-level.high{background:#0d5e52;} .badge-level.mid{background:#c8943f;} .badge-level.low{background:#ef4444;}
.sig-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-top:16px;text-align:center;font-size:11px;}
.sig-line{border-top:1px solid #0d5e52;padding-top:6px;margin-top:18px;}
@media print{body{background:#fff}.stat-filters,.no-print{display:none!important} .a4-page{border:none;box-shadow:none;margin:0;width:100%}}
</style>
<div class="container">
  <div class="stat-filters no-print">
    <h6 class="fw-bold" style="color:#0d5e52"><i class="fas fa-filter"></i> الفلاتر الذكية</h6>
    <form id="statFilterForm" class="row g-3">
      <div class="col-md-2"><label class="small fw-bold">نوع التقييم</label>
        <select id="entity_filter" class="form-select">
          <option value="all">الكل</option>
          <option value="employee">فردي حر</option>
          <option value="employee_in_course">موظف في دورة</option>
          <option value="course">دورة ككيان</option>
          <option value="employee_in_workshop">موظف في ورشة</option>
          <option value="workshop">ورشة ككيان</option>
          <option value="unit">وحدة تنظيمية</option>
        </select>
      </div>
      <div class="col-md-2"><label class="small fw-bold">المستوى</label>
        <select id="level" class="form-select">
          <option value="company">الشركة ككل</option>
          <option value="branch">فرع</option>
          <option value="department">قسم</option>
        </select>
      </div>
      <div class="col-md-2"><label class="small fw-bold">الفرع</label>
        <select id="branch_id" class="form-select"><option value="">الكل</option><?php foreach($branches as $b): ?><option value="<?= $b->id ?>"><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?></select>
      </div>
      <div class="col-md-2"><label class="small fw-bold">القسم</label>
        <select id="department_id" class="form-select"><option value="">الكل</option><?php foreach($departments as $d): ?><option value="<?= $d->id ?>"><?= htmlspecialchars($d->name) ?></option><?php endforeach; ?></select>
      </div>
      <div class="col-md-2"><label class="small fw-bold">من تاريخ</label><input type="date" id="start_date" class="form-control"></div>
      <div class="col-md-2"><label class="small fw-bold">إلى تاريخ</label><input type="date" id="end_date" class="form-control"></div>
      <div class="col-12 d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-success"><i class="fas fa-search"></i> تطبيق</button>
        <button type="button" class="btn btn-outline-secondary" data-range="month">الشهر الحالي</button>
        <button type="button" class="btn btn-outline-secondary" data-range="quarter">الربع الحالي</button>
        <button type="button" class="btn btn-outline-secondary" data-range="year">السنة الحالية</button>
        <button type="button" onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> طباعة A4</button>
        <a id="excelBtn" href="#" class="btn btn-warning"><i class="fas fa-file-excel"></i> تصدير Excel</a>
        <a id="pdfBtn" href="#" class="btn btn-dark" onclick="window.print();return false;"><i class="fas fa-file-pdf"></i> PDF</a>
      </div>
    </form>
  </div>

  <div class="a4-page" id="a4Report">
    <div class="a4-header">
      <div style="font-size:10px;color:#64748b;line-height:1.4">المملكة العربية السعودية<br>شركة ...<br>تاريخ: <?= date('Y/m/d') ?></div>
      <div style="text-align:center"><div style="font-size:11px;color:#0d5e52;font-weight:800">التقرير الإحصائي لتقييم الأداء</div><div style="font-size:9px;color:#c8943f">Statistical Performance Report</div></div>
      <div class="hr-badge">HR</div>
    </div>
    <div class="a4-title"><h3>التقرير الإحصائي المجمع</h3><p id="reportPeriod">الفترة: الكل | النوع: الكل | المستوى: الشركة</p></div>

    <div class="a4-section">
      <div class="a4-section-header"><span>1. بيانات التقرير</span><span id="reportMeta" style="font-size:10px;">—</span></div>
      <table class="a4-table">
        <tr><th>عدد التقييمات</th><th>المتوسط العام</th><th>أعلى درجة</th><th>أدنى درجة</th><th>مقارنة بمتوسط الشركة</th></tr>
        <tr><td id="cnt">-</td><td id="avg">-</td><td id="max">-</td><td id="min">-</td><td id="companyAvg">-</td></tr>
      </table>
    </div>

    <div class="a4-section">
      <div class="a4-section-header"><span>2. توزيع الفئات (ديناميكي حسب الإعدادات)</span><span id="distSubtitle" style="font-size:10px;"></span></div>
      <table class="a4-table">
        <tr><th>الفئة</th><th>النطاق</th><th>العدد</th><th>النسبة</th><th>المستوى</th></tr>
        <tbody id="distBody"><tr><td colspan="5">جاري التحميل...</td></tr></tbody>
      </table>
    </div>

    <div class="a4-section">
      <div class="a4-section-header"><span>3. التفصيل حسب الجهة</span><span id="detailTitle" style="font-size:10px;">مرتبة حسب المتوسط</span></div>
      <table class="a4-table">
        <thead><tr><th>الجهة</th><th>النوع</th><th>العدد</th><th>المتوسط</th><th>أعلى</th><th>أدنى</th><th>المستوى</th></tr></thead>
        <tbody id="byBranchBody"><tr><td colspan="7">جاري التحميل...</td></tr></tbody>
      </table>
    </div>

    <div class="sig-grid">
      <div><div class="sig-line">توقيع المدير المباشر</div></div>
      <div><div class="sig-line">توقيع الموارد البشرية</div></div>
      <div><div class="sig-line">توقيع الإدارة العليا</div></div>
    </div>
    <div style="text-align:center;margin-top:10px;font-size:8px;color:#94a3b8;">وثيقة رسمية - نظام تقييم الموظفين - <?= date('Y/m/d H:i') ?></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
  const branchSel=document.getElementById('branch_id'), deptSel=document.getElementById('department_id');
  const allDepts=<?= json_encode($departments) ?>;
  branchSel.addEventListener('change',function(){
    const bid=this.value; deptSel.innerHTML='<option value="">الكل</option>';
    allDepts.filter(d=> !bid || d.branch_id==bid).forEach(d=>{
      const o=document.createElement('option'); o.value=d.id; o.textContent=d.name; deptSel.appendChild(o);
    });
  });
  document.querySelectorAll('[data-range]').forEach(btn=>{
    btn.addEventListener('click',function(){
      const r=this.dataset.range, now=new Date(); let s,e;
      if(r==='month'){ s=new Date(now.getFullYear(),now.getMonth(),1); e=new Date(now.getFullYear(),now.getMonth()+1,0); }
      if(r==='quarter'){ const q=Math.floor(now.getMonth()/3); s=new Date(now.getFullYear(),q*3,1); e=new Date(now.getFullYear(),q*3+3,0); }
      if(r==='year'){ s=new Date(now.getFullYear(),0,1); e=new Date(now.getFullYear(),11,31); }
      document.getElementById('start_date').value=s.toISOString().slice(0,10);
      document.getElementById('end_date').value=e.toISOString().slice(0,10);
    });
  });
  async function load(){
    const p=new URLSearchParams({
      entity_filter:document.getElementById('entity_filter').value,
      branch_id:branchSel.value,
      department_id:deptSel.value,
      start_date:document.getElementById('start_date').value,
      end_date:document.getElementById('end_date').value
    });
    document.getElementById('excelBtn').href=`<?= APP_URL ?>/reports/statistics/export?`+p.toString();
    document.getElementById('reportPeriod').textContent=`الفترة: ${p.get('start_date')||'البداية'} - ${p.get('end_date')||'الآن'} | النوع: ${p.get('entity_filter')} | المستوى: ${document.getElementById('level').value}`;
    const res=await fetch(`<?= APP_URL ?>/api/reports/statistics?`+p.toString());
    if(res.status===403){ document.getElementById('a4Report').innerHTML='<div class="alert alert-danger">403 - نطاق غير مصرح</div>'; return; }
    const data=await res.json();
    document.getElementById('cnt').textContent=data.stats.cnt;
    document.getElementById('avg').textContent=data.stats.avg+'%';
    document.getElementById('max').textContent=data.stats.max+'%';
    document.getElementById('min').textContent=data.stats.min+'%';
    document.getElementById('companyAvg').textContent=data.stats.company_avg+'%';
    document.getElementById('reportMeta').textContent=`إجمالي ${data.stats.cnt} تقييم`;
    const total=data.stats.cnt||1;
    const ratings=data.ratings||[];
    document.getElementById('distSubtitle').textContent=ratings.map(r=>`${r.label} ${r.min_score}-${r.max_score}`).join(' | ');
    const distBody=document.getElementById('distBody');
    if(!data.dist.length) distBody.innerHTML='<tr><td colspan="5">لا بيانات</td></tr>';
    else distBody.innerHTML=data.dist.map(d=>{
      const pct=(d.cnt/total*100).toFixed(1);
      return `<tr><td>${d.label}</td><td>${d.min}-${d.max}</td><td>${d.cnt}</td><td>${pct}%</td><td><span class="badge-level" style="background:${d.color}">${d.label}</span></td></tr>`;
    }).join('');
    function getRating(score){
      for(const r of ratings){ if(score>=parseFloat(r.min_score) && score<=parseFloat(r.max_score)) return r; }
      return ratings[ratings.length-1]||{label:'غير مصنف',color:'#64748b'};
    }
    const tb=document.getElementById('byBranchBody');
    if(!data.byBranch.length) tb.innerHTML='<tr><td colspan="7">لا بيانات في الفترة</td></tr>';
    else tb.innerHTML=data.byBranch.map(r=>{
      const rat=getRating(parseFloat(r.avg_score));
      return `<tr><td>${r.name}</td><td>${r.entity_type||''}</td><td>${r.cnt}</td><td>${parseFloat(r.avg_score).toFixed(1)}%</td><td>${r.max_s?parseFloat(r.max_s).toFixed(1):'-'}%</td><td>${r.min_s?parseFloat(r.min_s).toFixed(1):'-'}%</td><td><span class="badge-level" style="background:${rat.color}">${rat.label}</span></td></tr>`;
    }).join('');
  }
  document.getElementById('statFilterForm').addEventListener('submit',e=>{e.preventDefault();load();});
  load();
});
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
