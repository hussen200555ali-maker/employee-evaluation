<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap');
:root{ --brand:#0d5e52; --brand-deep:#0c2823; --navy:#0c2823; --gold:#c8943f; --gold-light:#fef3c7; --bg:#f4f7f5; --canvas:#f4f7f5; --card:#fff; --border:#dce7e2; --line:#dce7e2; --muted:#4e625d; --text:#16302b; --ink:#16302b; --shadow-sm:0 2px 8px rgba(15,42,68,.06); --shadow-md:0 8px 24px rgba(15,42,68,.10); --shadow-lg:0 16px 40px rgba(15,42,68,.14);}
*{font-family:'Tajawal',sans-serif;}
.year-wrap{ background:var(--canvas); min-height:100vh; padding: clamp(12px, 2vw, 20px) 0 clamp(20px, 3vw, 32px); position:relative; overflow:hidden;}
.year-wrap::before{ content:''; position:fixed; inset:0; pointer-events:none; background: radial-gradient(ellipse at 20% 10%, rgba(13,94,82,.04) 0%, transparent 50%), radial-gradient(ellipse at 90% 90%, rgba(200,148,63,.04) 0%, transparent 50%); z-index:0;}
.year-container{ width:min(96%, 1400px); margin:0 auto; padding:0 clamp(8px, 1.5vw, 16px); position:relative; z-index:1;}
.top-head{ background:linear-gradient(135deg, #0c2823 0%, #0d5e52 55%, #115e4b 100%); border-radius:16px; padding:clamp(16px, 1.8vw, 22px) clamp(18px, 2.2vw, 24px); color:#ffffff !important; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; box-shadow:0 10px 32px rgba(12,40,35,.22), 0 2px 8px rgba(0,0,0,.08); border:1px solid rgba(255,255,255,.10); position:relative; overflow:hidden;}
.top-head::before{ content:''; position:absolute; inset:0; background: radial-gradient(circle at 85% 30%, rgba(200,148,63,.12) 0%, transparent 40%), radial-gradient(circle at 15% 80%, rgba(255,255,255,.05) 0%, transparent 40%); pointer-events:none;}
.top-head::after{ content:''; position:absolute; bottom:0; left:0; right:0; height:1px; background:linear-gradient(90deg, transparent, rgba(200,148,63,.4), transparent);}
.top-head h2{ margin:0; font-size:clamp(15px, 1.4vw, 18px); font-weight:800; display:flex; align-items:center; gap:8px; color:#ffffff !important;}
.top-head h2 *{ color:#ffffff !important;}
.top-head h2 i{ color:var(--gold) !important; background:rgba(255,255,255,.14); width:32px; height:32px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:14px; border:1px solid rgba(255,255,255,.12);}
.top-head .meta{ font-size:12.5px; color:#e2e8f0 !important; display:flex; align-items:center; gap:8px; flex-wrap:wrap;}
.top-head .meta *{ color:#e2e8f0 !important;}
.top-head .meta strong{ color:#ffffff !important; font-weight:700;}
.top-head .meta i{ color:var(--gold) !important;}
.top-head .hijri{ background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.22); border-radius:20px; padding:4px 10px; font-size:11.5px; color:#fff !important; backdrop-filter:blur(4px);}
.employee-brief{ background:var(--card); border:1px solid var(--border); border-radius:14px; padding:16px 18px; display:flex; align-items:center; gap:14px; margin:16px 0 18px; box-shadow:var(--shadow-sm); transition:all .2s; }
.employee-brief:hover{ box-shadow:var(--shadow-md); border-color:#cbdde0;}
.avatar{ width:48px; height:48px; border-radius:50%; background:linear-gradient(135deg, var(--brand), #2a4a6b); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:18px; flex-shrink:0;}
.emp-info h3{ margin:0; font-size:14px; font-weight:800; color:var(--text);}
.emp-info p{ margin:2px 0 0; font-size:12px; color:var(--muted);}
.kpi-grid{ display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:clamp(8px, 1.2vw, 14px); margin-bottom:0;}
.kpi-card{ background:var(--card); border:1px solid var(--border); border-radius:14px; padding:16px 14px; text-align:center; box-shadow:var(--shadow-sm); position:relative; overflow:hidden; transition:all .25s cubic-bezier(.4,0,.2,1);}
.kpi-card::before{ content:''; position:absolute; top:0; left:0; right:0; height:3px; background:var(--brand); transition:height .2s;}
.kpi-card:hover{ transform:translateY(-3px); box-shadow:var(--shadow-md); border-color:#cbdde0;}
.kpi-card:hover::before{ height:4px;}
.kpi-card.gold::before{ background:linear-gradient(90deg, var(--gold), #e8b86a);}
.kpi-card .kpi-icon{ width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg, #e6f0ed, #d1e7dd); color:var(--brand); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; font-size:15px; box-shadow:0 2px 6px rgba(13,94,82,.08); transition:transform .2s;}
.kpi-card:hover .kpi-icon{ transform:scale(1.05);}
.kpi-card.gold .kpi-icon{ background:linear-gradient(135deg, #fef3c7, #fde68a); color:#92400e;}
.kpi-card .kpi-val{ font-size:24px; font-weight:800; color:var(--brand); line-height:1; letter-spacing:-.5px;}
.kpi-card .kpi-lbl{ font-size:11px; font-weight:700; color:var(--muted); margin-top:5px; letter-spacing:.2px;}
.kpi-card .kpi-sub{ font-size:10.5px; color:#94a3b8; margin-top:3px;}
.eval-grid{ display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:clamp(10px, 1.4vw, 16px);}
.eval-card{ background:var(--card); border:1px solid var(--border); border-radius:14px; overflow:hidden; box-shadow:var(--shadow-sm); transition:all .25s cubic-bezier(.4,0,.2,1); display:flex; flex-direction:column; position:relative;}
.eval-card::before{ content:''; position:absolute; top:0; left:0; right:0; height:2px; background:transparent; transition:background .2s;}
.eval-card:hover{ transform:translateY(-3px); box-shadow:var(--shadow-lg); border-color:#cbdde0;}
.eval-card:hover::before{ background:linear-gradient(90deg, var(--brand), var(--gold));}
.eval-card-head{ background:#f8fafc; color:var(--text); padding:10px 12px; display:flex; justify-content:space-between; align-items:center; gap:8px; border-bottom:1px solid var(--border);}
.eval-card-head h5{ margin:0; font-size:13px; font-weight:800; color:var(--text); white-space:normal; overflow:visible; text-overflow:clip; max-width:68%; line-height:1.4; text-shadow:none;}
.eval-card-head .date-badge{ background:var(--brand); color:#fff; border:none; border-radius:20px; padding:3px 9px; font-size:10px; font-weight:700; box-shadow:0 1px 3px rgba(0,0,0,.12); flex-shrink:0;}
.eval-card-body{ padding:14px; text-align:center; flex:1; display:flex; flex-direction:column; gap:8px;}
.score-ring{ width:76px; height:76px; border-radius:50%; margin:0 auto; display:flex; align-items:center; justify-content:center; position:relative; background: conic-gradient(var(--brand) calc(var(--score)*3.6deg), #eef2f7 0deg); box-shadow:0 2px 8px rgba(13,94,82,.08); transition:transform .3s;}
.eval-card:hover .score-ring{ transform:scale(1.04);}
.score-ring::before{ content:''; position:absolute; width:60px; height:60px; background:#fff; border-radius:50%; box-shadow:inset 0 1px 3px rgba(0,0,0,.04);}
.score-ring span{ position:relative; font-weight:800; font-size:19px; color:var(--brand); letter-spacing:-.5px;}
.progress{ height:6px; background:#e2e8f0; border-radius:20px; overflow:hidden;}
.progress-bar{ height:100%; border-radius:20px; background:linear-gradient(90deg, var(--brand), #3b82f6); transition:width .4s;}
.eval-meta{ font-size:11px; color:var(--muted); display:flex; align-items:center; justify-content:center; gap:4px;}
.eval-card-foot{ padding:0 12px; background:#f8fafc; border-top:1px solid var(--border); display:flex; justify-content:center; gap:6px; flex-wrap:wrap; max-height:0; overflow:hidden; opacity:0; transition:all .3s ease;}
.eval-card:hover .eval-card-foot{ padding:10px 12px; max-height:60px; opacity:1;}
.btn-sm{ padding:5px 10px; font-size:11px; border-radius:8px; font-weight:600; border:1px solid; text-decoration:none; display:inline-flex; align-items:center; gap:4px;}
.btn-outline-primary{ background:#fff; color:var(--brand); border-color:var(--border);}
.btn-outline-primary:hover{ background:var(--brand); color:#fff;}
.eval-grid:has(> :only-child){ grid-template-columns:min(420px, 100%); justify-content:center;}
@keyframes fadeIn{ from{opacity:0; transform:translateY(6px);} to{opacity:1; transform:none;} }
.year-wrap{ font-size:clamp(11px, 0.85vw, 13px);}
.kpi-card .kpi-val{ font-size:clamp(18px, 1.6vw, 22px);}
.eval-card-head h5{ font-size:clamp(11px, 0.9vw, 12px);}

.main-content-grid{ display:grid; grid-template-columns:60% 40%; gap:24px; align-items:start; margin-bottom:16px;}
.charts-column{ min-width:0; display:flex; flex-direction:column; gap:16px;}
.records-column{ min-width:0; display:flex; flex-direction:column; gap:16px;}
.chart-card{ background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px; box-shadow:var(--shadow-sm); transition:all .25s ease;}
.chart-card:hover{ transform:translateY(-2px); box-shadow:var(--shadow-md);}
.chart-card h4{ margin:0 0 12px; font-size:13px; font-weight:800; color:var(--text); display:flex; align-items:center; gap:8px;}
.chart-card h4 i{ color:var(--brand); background:#e6f0ed; width:28px; height:28px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:12px;}
.chart-hint{ font-size:10.5px; color:var(--muted); text-align:center; margin-top:8px; display:flex; align-items:center; justify-content:center; gap:5px;}
.unified-chart{ background:var(--card); border-radius:14px; padding:18px; box-shadow:var(--shadow-sm); border:1px solid var(--border); position:relative; overflow:hidden; margin-bottom:0;}
.unified-chart::before{ content:''; position:absolute; inset:0; background:radial-gradient(ellipse at 30% 20%, rgba(13,94,82,.03) 0%, transparent 50%); pointer-events:none;}
.unified-chart h4{ margin:0 0 14px; font-size:13px; font-weight:800; color:var(--text); display:flex; align-items:center; gap:8px; position:relative;}
.unified-chart h4 i{ color:var(--brand); background:#e6f0ed; width:28px; height:28px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:12px;}
.unified-chart .hint{ font-size:11px; color:var(--muted); text-align:center; margin-top:8px; display:flex; align-items:center; justify-content:center; gap:6px;}
.unified-chart canvas{ position:relative; z-index:1;}
@media(max-width:900px){ .kpi-grid{grid-template-columns:repeat(2,1fr);} .eval-grid{grid-template-columns:1fr !important;} .main-content-grid{grid-template-columns:1fr !important;} .charts-column{position:static;} .eval-card-foot{max-height:none; opacity:1; padding:10px 12px;}}
@media(max-width:600px){ .kpi-grid{grid-template-columns:1fr;} .eval-grid{grid-template-columns:1fr;} .top-head{flex-direction:column; text-align:center;} }
</style>

<div class="year-wrap">
<div class="year-container">
<?php
$sampleDate = !empty($evaluations) ? $evaluations[0]->evaluation_date : $year . '-06-15';
$hijriData = convertToHijri($sampleDate);
$hijriYear = $hijriData ? $hijriData->year : '';
$scores = array_filter(array_column($evaluations, 'final_score'), fn($v)=> is_numeric($v));
$avg = count($scores) > 0 ? round(array_sum($scores)/count($scores),2) : null;
$maxScore = $scores ? max($scores) : null;
$minScore = $scores ? min($scores) : null;
?>
  <div class="top-head">
    <h2><i class="fas fa-chart-line"></i> تقييمات عام <?php echo $year; ?> م <?php if($hijriYear) echo "/ $hijriYear هـ"; ?></h2>
    <div class="meta">
      <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($employee->full_name); ?></span>
      <span class="hijri"><?php echo $hijriData ? htmlspecialchars($hijriData->full_date) : ''; ?></span>
    </div>
  </div>

  <div class="employee-brief">
    <div class="avatar"><?php echo mb_substr($employee->full_name,0,1,'UTF-8'); ?></div>
    <div class="emp-info" style="flex:1;">
      <h3><?php echo htmlspecialchars($employee->full_name); ?> <span style="font-weight:400; color:#94a3b8; font-size:12px;">#<?php echo htmlspecialchars($employee->emp_code); ?></span></h3>
      <p><?php echo htmlspecialchars($employee->position_title ?? '—'); ?> · <?php echo htmlspecialchars($employee->branch_name ?? ''); ?> · <?php echo htmlspecialchars($employee->department_name ?? ''); ?></p>
    </div>
    <a href="<?php echo APP_URL . '/employees/' . $employee->id . '/evaluations'; ?>" class="btn-sm btn-outline-primary"><i class="fas fa-arrow-right"></i> السجل السنوي</a>
  </div>

  <?php if(empty($evaluations)): ?>
    <div class="kpi-card" style="text-align:center; padding:24px;"><i class="fas fa-inbox" style="font-size:28px; color:#cbd5e1;"></i><p style="margin:8px 0 0; color:var(--muted);">لا توجد تقييمات في هذا العام</p></div>
  <?php else: ?>
  <div class="main-content-grid">
    <div class="charts-column">
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-icon"><i class="fas fa-star"></i></div>
          <div class="kpi-val"><?php echo $avg!==null ? $avg.'%' : '—'; ?></div>
          <div class="kpi-lbl">متوسط الأداء</div>
          <div class="kpi-sub">من <?php echo count($evaluations); ?> تقييمات</div>
        </div>
        <div class="kpi-card gold">
          <div class="kpi-icon"><i class="fas fa-trophy"></i></div>
          <div class="kpi-val" style="color:#92400e;"><?php echo $maxScore!==null ? $maxScore.'%' : '—'; ?></div>
          <div class="kpi-lbl">أعلى درجة</div>
          <div class="kpi-sub">نقطة قوة</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon"><i class="fas fa-building"></i></div>
          <div class="kpi-val"><?php echo isset($comparisonStats->department_average) && $comparisonStats->department_average!==null ? round($comparisonStats->department_average,1).'%' : '—'; ?></div>
          <div class="kpi-lbl">متوسط القسم</div>
          <div class="kpi-sub">مقارنة</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon"><i class="fas fa-globe"></i></div>
          <div class="kpi-val"><?php echo isset($comparisonStats->company_average) && $comparisonStats->company_average!==null ? round($comparisonStats->company_average,1).'%' : '—'; ?></div>
          <div class="kpi-lbl">متوسط الشركة</div>
          <div class="kpi-sub">عام <?php echo $year; ?></div>
        </div>
      </div>

      <div class="chart-card">
        <h4><i class="fas fa-bullseye"></i> خريطة الكفاءات</h4>
        <div style="height:260px; position:relative;"><canvas id="radarChart"></canvas></div>
        <div class="chart-hint"><i class="fas fa-info-circle"></i> كل محور = متوسط معيار — مرر لرؤية القيمة</div>
      </div>

      <div class="unified-chart">
        <h4><i class="fas fa-chart-area"></i> رؤية شاملة — تطور أدائك وموقعك بين الآخرين</h4>
        <?php if(count($evaluations) === 1): ?>
          <div style="background:#f0faf7; border:1px solid #d1e7dd; border-radius:10px; padding:10px 12px; margin-bottom:12px; display:flex; align-items:center; gap:10px; font-size:12px; color:var(--text);">
            <i class="fas fa-lightbulb" style="color:var(--gold); font-size:14px;"></i>
            <span>تقييم واحد فقط في <?php echo $year; ?> — أضف تقييماً آخر لرؤية خط التطور الكامل. موقعك الحالي <strong style="color:var(--brand);"><?php echo $avg!==null ? $avg.'%' : '—'; ?></strong> مقابل القسم <strong style="color:var(--gold);"><?php echo $comparisonStats->department_average ?? '—'; ?>%</strong> والشركة <strong><?php echo $comparisonStats->company_average ?? '—'; ?>%</strong></span>
          </div>
        <?php endif; ?>
        <div style="height:300px; position:relative;"><canvas id="unifiedChart"></canvas></div>
        <div class="hint"><i class="fas fa-info-circle"></i> كل نقطة = تقييم — مرر لرؤية الاستمارة والنسبة والتغيير — اضغط على النقطة للانتقال للبطاقة</div>
      </div>
    </div>

    <div class="records-column">
      <div class="eval-grid">
        <?php foreach($evaluations as $eval): ?>
          <?php $score = is_numeric($eval->final_score) ? (float)$eval->final_score : 0; $scoreClamped = max(0,min(100,$score)); $hijri = convertToHijri($eval->evaluation_date); ?>
          <div class="eval-card" data-eval-id="<?php echo $eval->id; ?>" data-template="<?php echo htmlspecialchars($eval->template_name); ?>">
            <div class="eval-card-head">
              <h5 title="<?php echo htmlspecialchars($eval->template_name); ?>"><?php echo htmlspecialchars($eval->template_name); ?></h5>
              <span class="date-badge" dir="ltr"><?php echo date('Y/m/d', strtotime($eval->evaluation_date)); ?></span>
            </div>
            <div class="eval-card-body">
              <div class="score-ring" style="--score:<?php echo $scoreClamped; ?>"><span><?php echo rtrim(rtrim(number_format($score,1), '0'), '.'); ?>%</span></div>
              <div class="progress"><div class="progress-bar" style="width:<?php echo $scoreClamped; ?>%"></div></div>
              <div class="eval-meta"><i class="fas fa-user-tie" style="color:var(--gold);"></i> <?php echo htmlspecialchars($eval->evaluator_name ?? '—'); ?></div>
              <div style="font-size:10px; color:#94a3b8;"><?php echo $hijri ? htmlspecialchars($hijri->full_date) : ''; ?></div>
            </div>
            <div class="eval-card-foot">
              <a href="<?php echo APP_URL . '/evaluations/show/' . $eval->id; ?>" class="btn-sm btn-outline-primary"><i class="fas fa-eye"></i> عرض</a>
              <?php if($eval->status==='completed'): ?>
                <form action="<?php echo APP_URL . '/evaluations/reopen'; ?>" method="POST" class="d-inline" onsubmit="return confirm('إعادة فتح للتعديل؟');"><?php echo csrf_field(); ?><input type="hidden" name="evaluation_id" value="<?php echo $eval->id; ?>"><button class="btn-sm" style="background:#fff; border:1px solid #e2e8f0; color:#475569;"><i class="fas fa-edit"></i> تعديل</button></form>
              <?php else: ?>
                <a href="<?php echo APP_URL . '/evaluations/' . $eval->id . '/fill'; ?>" class="btn-sm" style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46;"><i class="fas fa-play"></i> إكمال</a>
              <?php endif; ?>
              <form action="<?php echo APP_URL . '/evaluations/delete'; ?>" method="POST" class="d-inline" onsubmit="return confirm('حذف التقييم؟');"><?php echo csrf_field(); ?><input type="hidden" name="evaluation_id" value="<?php echo $eval->id; ?>"><button class="btn-sm" style="background:#fff; border:1px solid #fecaca; color:#991b1b;"><i class="fas fa-trash"></i></button></form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div style="margin-top:16px; text-align:center;">
    <a href="<?php echo APP_URL . '/employees/' . $employee->id . '/evaluations'; ?>" class="btn-sm btn-outline-primary"><i class="fas fa-arrow-right"></i> العودة للسجل السنوي</a>
  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    if (typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }

    const radarData = <?php echo json_encode($radarData ?? ['labels' => [], 'scores' => []], JSON_UNESCAPED_UNICODE); ?>;
    const trendData = <?php echo json_encode($trendData ?? ['labels' => [], 'scores' => []], JSON_UNESCAPED_UNICODE); ?>;
    const trendTemplates = <?php $tmpT = []; if (!empty($evaluations)) { foreach ($evaluations as $e) { $tmpT[] = $e->template_name; } } echo json_encode($tmpT, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const deptAvg = <?php echo json_encode($comparisonStats->department_average ?? null); ?>;
    const compAvg = <?php echo json_encode($comparisonStats->company_average ?? null); ?>;

    Chart.defaults.font.family = 'Tajawal';

    if (document.getElementById('radarChart') && Array.isArray(radarData.labels) && radarData.labels.length) {
        new Chart(document.getElementById('radarChart'), {
            type: 'radar',
            data: {
                labels: radarData.labels,
                datasets: [{
                    label: 'المتوسط',
                    data: radarData.scores,
                    backgroundColor: 'rgba(13,94,82,.13)',
                    borderColor: '#0d5e52',
                    pointBackgroundColor: '#c8943f',
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                            backdropColor: 'transparent',
                            color: '#64748b',
                            callback: function (v) { return v + '%'; }
                        },
                        grid: { color: '#e2e8f0' },
                        pointLabels: { color: '#16302b', font: { size: 11, weight: '700' } }
                    }
                },
                plugins: {
                    legend: { display: false },
                    datalabels: { display: false },
                    tooltip: {
                        backgroundColor: '#0c2823',
                        titleFont: { family: 'Tajawal', weight: '700' },
                        bodyFont: { family: 'Tajawal' },
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': ' + ctx.parsed.r + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    if (document.getElementById('unifiedChart') && Array.isArray(trendData.labels) && trendData.labels.length) {
        const deptLine = trendData.labels.map(function () {
            return deptAvg !== null ? Number(deptAvg) : null;
        });
        const compLine = trendData.labels.map(function () {
            return compAvg !== null ? Number(compAvg) : null;
        });

        const unified = new Chart(document.getElementById('unifiedChart'), {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [
                    {
                        label: 'الموظف',
                        data: trendData.scores,
                        borderColor: '#0d5e52',
                        backgroundColor: 'rgba(13,94,82,.12)',
                        fill: true,
                        tension: 0.42,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#0d5e52',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: '#c8943f',
                        borderWidth: 2.8
                    },
                    {
                        label: 'القسم',
                        data: deptLine,
                        borderColor: '#c8943f',
                        backgroundColor: 'transparent',
                        borderDash: [6, 4],
                        pointRadius: 0,
                        borderWidth: 1.8,
                        fill: false,
                        tension: 0.2
                    },
                    {
                        label: 'الشركة',
                        data: compLine,
                        borderColor: '#64748b',
                        backgroundColor: 'transparent',
                        borderDash: [3, 5],
                        pointRadius: 0,
                        borderWidth: 1.5,
                        fill: false,
                        tension: 0.2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(0,0,0,.06)' },
                        ticks: {
                            color: '#64748b',
                            callback: function (v) { return v + '%'; }
                        },
                        border: { color: 'rgba(0,0,0,.08)' }
                    },
                    x: {
                        grid: { color: 'rgba(0,0,0,.04)' },
                        ticks: { color: '#64748b', font: { size: 10 } },
                        border: { color: 'rgba(0,0,0,.08)' }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            color: '#334155',
                            font: { family: 'Tajawal', size: 11, weight: '600' },
                            boxWidth: 14,
                            boxHeight: 3,
                            usePointStyle: true,
                            pointStyle: 'line',
                            padding: 14
                        }
                    },
                    datalabels: { display: false },
                    tooltip: {
                        backgroundColor: '#0c2823',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(200,148,63,.3)',
                        borderWidth: 1,
                        padding: 10,
                        titleFont: { family: 'Tajawal', weight: '700' },
                        bodyFont: { family: 'Tajawal' },
                        callbacks: {
                            title: function (items) {
                                const index = items[0].dataIndex;
                                return (trendTemplates[index] || '') + ' — ' + items[0].label;
                            },
                            label: function (ctx) {
                                const value = ctx.parsed.y;
                                if (value === null) {
                                    return ctx.dataset.label + ': —';
                                }

                                let suffix = '';
                                if (ctx.datasetIndex === 0 && ctx.dataIndex > 0) {
                                    const previous = ctx.dataset.data[ctx.dataIndex - 1];
                                    if (previous !== null) {
                                        const diff = (value - previous).toFixed(1);
                                        suffix = (diff > 0 ? ' ▲ +' + diff + '%' : diff < 0 ? ' ▼ ' + diff + '%' : ' — 0%');
                                    }
                                }

                                return ctx.dataset.label + ': ' + value + '%' + suffix;
                            }
                        }
                    }
                }
            }
        });

        document.getElementById('unifiedChart').onclick = function (evt) {
            const points = unified.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
            if (!points.length) {
                return;
            }

            const index = points[0].index;
            const cards = document.querySelectorAll('.eval-card');
            if (cards[index]) {
                cards[index].scrollIntoView({ behavior: 'smooth', block: 'center' });
                cards[index].style.outline = '2px solid #c8943f';
                setTimeout(function () {
                    cards[index].style.outline = '';
                }, 1800);
            }
        };
    }

    document.querySelectorAll('.eval-card').forEach(function (card, index) {
        card.addEventListener('mouseenter', function () {
            const chart = Chart.getChart('unifiedChart');
            if (chart && chart.data.labels && chart.data.labels[index] !== undefined) {
                chart.setActiveElements([{ datasetIndex: 0, index: index }]);
                chart.update();
            }
        });

        card.addEventListener('mouseleave', function () {
            const chart = Chart.getChart('unifiedChart');
            if (chart) {
                chart.setActiveElements([]);
                chart.update();
            }
        });
    });
})();
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
