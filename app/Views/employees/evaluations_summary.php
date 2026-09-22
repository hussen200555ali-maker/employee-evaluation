<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<?php
$allScores = [];
$totalCount = 0;
if (!empty($evaluationsByYear)) {
    foreach ($evaluationsByYear as $s) {
        $n = (int)$s->evaluations_count;
        $totalCount += $n;
        for ($i = 0; $i < $n; $i++) {
            $allScores[] = (float)$s->average_score;
        }
    }
}
$overallAvg = $totalCount > 0 ? round(array_sum($allScores) / count($allScores), 2) : null;
$overallMax = !empty($evaluationsByYear) ? max(array_column($evaluationsByYear, 'max_score')) : null;
$overallMin = !empty($evaluationsByYear) ? min(array_column($evaluationsByYear, 'min_score')) : null;
$bestYear = null;
if (!empty($evaluationsByYear)) {
    $bestYearObj = $evaluationsByYear[0];
    foreach ($evaluationsByYear as $s) {
        if ((float)$s->average_score > (float)$bestYearObj->average_score) {
            $bestYearObj = $s;
        }
    }
    $bestYear = $bestYearObj;
}
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap');
:root{--brand:#0d5e52;--deep:#0c2823;--gold:#c8943f;--canvas:#f0f4f8;--card:#fff;--border:#e2e8f0;--muted:#64748b;--text:#1e293b;--shadow-sm:0 2px 8px rgba(15,42,68,.06);--shadow-md:0 8px 24px rgba(15,42,68,.10);--shadow-lg:0 16px 40px rgba(15,42,68,.14);}
*{font-family:'Tajawal',sans-serif;}
.sr-wrap{ background:var(--canvas); min-height:100vh; padding:clamp(12px,2vw,24px) 0 clamp(24px,3vw,40px); position:relative; overflow:hidden;}
.sr-wrap::before{content:'';position:fixed;inset:0;pointer-events:none;background:radial-gradient(ellipse at 15% 10%,rgba(13,94,82,.05) 0%,transparent 50%),radial-gradient(ellipse at 85% 90%,rgba(200,148,63,.04) 0%,transparent 50%);z-index:0;}
.sr-container{width:min(96%,1600px);margin:0 auto;padding:0 clamp(8px,1.5vw,20px);position:relative;z-index:1;}

.hero-card{background:linear-gradient(135deg,#0c2823 0%,#0d5e52 55%,#115e4b 100%);border-radius:20px;padding:clamp(24px,3vw,40px);color:#fff;position:relative;overflow:hidden;margin-bottom:32px;backdrop-filter:blur(12px);box-shadow:0 16px 48px rgba(12,40,35,.25),0 2px 8px rgba(0,0,0,.08);border:1px solid rgba(255,255,255,.08);}
.hero-card::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 80% 20%,rgba(200,148,63,.12) 0%,transparent 40%),radial-gradient(circle at 20% 80%,rgba(255,255,255,.04) 0%,transparent 40%);pointer-events:none;}
.hero-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(200,148,63,.4),transparent);}
.hero-top{display:flex;align-items:center;gap:clamp(16px,2vw,24px);flex-wrap:wrap;position:relative;}
.hero-avatar{width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,.12);border:3px solid var(--gold);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:900;color:#fff;flex-shrink:0;box-shadow:0 0 24px rgba(200,148,63,.25);}
.hero-info{flex:1;min-width:0;}
.hero-info h2{margin:0;font-size:clamp(18px,2vw,26px);font-weight:900;color:#fff;line-height:1.3;}
.hero-info p{margin:4px 0 0;font-size:clamp(12px,1.1vw,14px);color:rgba(255,255,255,.7);}
.hero-info p span{margin:0 4px;}
.hero-avg{position:relative;margin-top:clamp(16px,2vw,24px);text-align:center;}
.hero-avg-circle{width:clamp(120px,15vw,180px);height:clamp(120px,15vw,180px);border-radius:50%;background:rgba(200,148,63,.1);border:3px solid rgba(200,148,63,.3);display:flex;flex-direction:column;align-items:center;justify-content:center;margin:0 auto;box-shadow:0 0 40px rgba(200,148,63,.15),inset 0 0 30px rgba(200,148,63,.05);}
.hero-avg-val{font-size:clamp(26px,3.5vw,40px);font-weight:900;color:var(--gold);line-height:1;text-shadow:0 0 30px rgba(200,148,63,.3);}
.hero-avg-lbl{font-size:clamp(11px,1vw,14px);color:rgba(255,255,255,.65);margin-top:4px;}
.hero-meta{display:flex;justify-content:center;gap:clamp(16px,2vw,32px);margin-top:clamp(12px,1.5vw,20px);flex-wrap:wrap;}
.hero-meta-item{font-size:clamp(11px,1vw,13px);color:rgba(255,255,255,.6);display:flex;align-items:center;gap:6px;}
.hero-meta-item i{color:var(--gold);}

.kpi-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:clamp(12px,1.5vw,20px);margin-bottom:32px;}
.kpi-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:clamp(16px,2vw,24px);text-align:center;box-shadow:var(--shadow-sm);transition:all .3s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden;}
.kpi-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md);}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;transition:height .2s;}
.kpi-card:hover::before{height:4px;}
.kpi-card.blue::before{background:#3b82f6;}
.kpi-card.gold::before{background:var(--gold);}
.kpi-card.red::before{background:#ef4444;}
.kpi-card.green::before{background:var(--brand);}
.kpi-icon-wrap{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:20px;transition:transform .2s;}
.kpi-card:hover .kpi-icon-wrap{transform:scale(1.08);}
.kpi-card.blue .kpi-icon-wrap{background:rgba(59,130,246,.1);color:#3b82f6;}
.kpi-card.gold .kpi-icon-wrap{background:rgba(200,148,63,.1);color:var(--gold);}
.kpi-card.red .kpi-icon-wrap{background:rgba(239,68,68,.1);color:#ef4444;}
.kpi-card.green .kpi-icon-wrap{background:rgba(13,94,82,.1);color:var(--brand);}
.kpi-val{font-size:clamp(28px,3vw,40px);font-weight:900;line-height:1;margin-bottom:4px;}
.kpi-card.blue .kpi-val{color:#3b82f6;}
.kpi-card.gold .kpi-val{color:var(--gold);}
.kpi-card.red .kpi-val{color:#ef4444;}
.kpi-card.green .kpi-val{color:var(--brand);}
.kpi-lbl{font-size:clamp(11px,1vw,13px);font-weight:700;color:var(--muted);}
.kpi-sub{font-size:10px;color:#94a3b8;margin-top:2px;}

.year-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:clamp(16px,2vw,24px);margin-bottom:32px;}
.year-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);transition:all .3s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;}
.year-card:hover{transform:translateY(-6px);box-shadow:0 12px 36px rgba(15,42,68,.12);border-color:var(--gold);}
.year-card-head{background:linear-gradient(135deg,#0c2823 0%,#0d5e52 60%,#115e4b 100%);padding:clamp(12px,1.5vw,18px) clamp(16px,2vw,20px);display:flex;justify-content:space-between;align-items:center;position:relative;overflow:hidden;}
.year-card-head::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(200,148,63,.4),transparent);}
.year-card-head h4{margin:0;font-size:clamp(14px,1.3vw,17px);font-weight:800;color:#fff;display:flex;align-items:center;gap:8px;}
.year-card-head h4 i{color:var(--gold);font-size:14px;}
.year-card-head .year-badge{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.2);border-radius:20px;padding:3px 10px;font-size:11px;color:#fff;backdrop-filter:blur(4px);}
.year-card-body{padding:clamp(16px,2vw,24px);flex:1;display:flex;flex-direction:column;gap:16px;}
.year-card-top{display:flex;align-items:center;gap:clamp(12px,1.5vw,20px);}
.score-ring{width:80px;height:80px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;position:relative;background:conic-gradient(var(--brand) calc(var(--score)*3.6deg),#eef2f7 0deg);box-shadow:0 2px 10px rgba(13,94,82,.1);transition:transform .3s;}
.year-card:hover .score-ring{transform:scale(1.05);}
.score-ring::before{content:'';position:absolute;width:64px;height:64px;background:#fff;border-radius:50%;box-shadow:inset 0 1px 3px rgba(0,0,0,.04);}
.score-ring span{position:relative;font-weight:900;font-size:18px;color:var(--brand);letter-spacing:-.5px;}

.bar-chart{flex:1;display:flex;flex-direction:column;gap:7px;}
.bar-item{display:flex;align-items:center;gap:8px;}
.bar-label{font-size:11px;color:var(--muted);width:48px;text-align:right;flex-shrink:0;font-weight:600;display:flex;align-items:center;justify-content:flex-end;gap:5px;}
.bar-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;}
.bar-dot.gold{background:var(--gold);}
.bar-dot.green{background:var(--brand);}
.bar-dot.red{background:#ef4444;}
.bar-track{flex:1;height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;position:relative;}
.bar-fill{height:100%;border-radius:4px;transition:width .6s ease;}
.bar-fill.green{background:linear-gradient(90deg,#0d5e52,#10b981);}
.bar-fill.gold{background:linear-gradient(90deg,#c8943f,#e8b86a);}
.bar-fill.red{background:linear-gradient(90deg,#ef4444,#f87171);}
.bar-val{font-size:10px;font-weight:700;color:var(--text);width:36px;text-align:left;flex-shrink:0;}

.year-card-stats{display:flex;justify-content:center;gap:clamp(12px,1.5vw,24px);padding-top:12px;border-top:1px solid var(--border);}
.year-stat{text-align:center;}
.year-stat-val{font-size:clamp(16px,1.3vw,20px);font-weight:900;color:var(--text);}
.year-stat-lbl{font-size:10px;color:var(--muted);}

.single-eval-badge{display:flex;align-items:center;gap:6px;background:#f0faf7;border:1px solid #d1e7dd;border-radius:10px;padding:6px 12px;font-size:12px;color:var(--brand);font-weight:600;}
.single-eval-badge i{font-size:14px;}

.year-card-foot{padding:clamp(12px,1.5vw,16px) clamp(16px,2vw,20px);background:#f8fafc;border-top:1px solid var(--border);text-align:center;}
.btn-year-detail{display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;background:var(--brand);color:#fff;border:2px solid var(--brand);transition:all .3s ease;}
.btn-year-detail:hover{background:var(--gold);border-color:var(--gold);color:#fff;transform:translateY(-1px);box-shadow:0 4px 12px rgba(200,148,63,.3);}

.empty-state{text-align:center;padding:clamp(40px,6vw,80px) 20px;}
.empty-state i{font-size:56px;color:#cbd5e1;}
.empty-state h4{margin:16px 0 0;font-size:18px;font-weight:700;color:var(--muted);}

.back-btn{display:inline-flex;align-items:center;gap:6px;padding:10px 24px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;background:transparent;color:var(--brand);border:2px solid var(--border);transition:all .25s ease;}
.back-btn:hover{border-color:var(--brand);background:var(--brand);color:#fff;}

@keyframes fadeIn{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:none;}}
.hero-card,.kpi-card,.year-card{animation:fadeIn .5s ease both;}
.kpi-card:nth-child(2){animation-delay:.1s;}
.kpi-card:nth-child(3){animation-delay:.2s;}
.kpi-card:nth-child(4){animation-delay:.3s;}

@media(max-width:900px){.kpi-strip{grid-template-columns:repeat(2,1fr);} .year-grid{grid-template-columns:1fr;}}
@media(max-width:600px){.kpi-strip{grid-template-columns:1fr;} .hero-top{flex-direction:column;text-align:center;} .year-card-top{flex-direction:column;text-align:center;} .bar-chart{width:100%;}}
</style>

<div class="sr-wrap">
<div class="sr-container">

<?php if (empty($evaluationsByYear)): ?>

    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h4>لا توجد تقييمات مكتملة لهذا الموظف حتى الآن</h4>
    </div>

<?php else: ?>

    <!-- Hero Card -->
    <div class="hero-card">
        <div class="hero-top">
            <div class="hero-avatar"><?php echo mb_substr($employee->full_name,0,1,'UTF-8'); ?></div>
            <div class="hero-info">
                <h2><?php echo htmlspecialchars($employee->full_name); ?></h2>
                <p>
                    <span>#<?php echo htmlspecialchars($employee->emp_code); ?></span>
                    <span>·</span>
                    <span><?php echo htmlspecialchars($employee->position_title ?? '—'); ?></span>
                    <span>·</span>
                    <span><?php echo htmlspecialchars($employee->branch_name ?? ''); ?></span>
                    <span>·</span>
                    <span><?php echo htmlspecialchars($employee->department_name ?? ''); ?></span>
                </p>
            </div>
        </div>
        <div class="hero-avg">
            <div class="hero-avg-circle">
                <div class="hero-avg-val"><?php echo $overallAvg !== null ? $overallAvg . '%' : '—'; ?></div>
                <div class="hero-avg-lbl">متوسط الأداء العام</div>
            </div>
        </div>
        <div class="hero-meta">
            <div class="hero-meta-item"><i class="fas fa-clipboard-list"></i> <?php echo $totalCount; ?> تقييمات مكتملة</div>
            <div class="hero-meta-item"><i class="fas fa-calendar-alt"></i> <?php echo count($evaluationsByYear); ?> <?php echo count($evaluationsByYear) > 1 ? 'سنوات' : 'سنة'; ?></div>
            <?php if ($overallMax !== null): ?>
                <div class="hero-meta-item"><i class="fas fa-trophy"></i> أعلى: <?php echo $overallMax; ?>%</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Strip -->
    <div class="kpi-strip">
        <div class="kpi-card blue">
            <div class="kpi-icon-wrap"><i class="fas fa-clipboard-list"></i></div>
            <div class="kpi-val"><?php echo $totalCount; ?></div>
            <div class="kpi-lbl">عدد التقييمات</div>
            <div class="kpi-sub">مكتملة</div>
        </div>
        <div class="kpi-card gold">
            <div class="kpi-icon-wrap"><i class="fas fa-trophy"></i></div>
            <div class="kpi-val"><?php echo $overallMax !== null ? $overallMax . '%' : '—'; ?></div>
            <div class="kpi-lbl">أعلى درجة</div>
            <div class="kpi-sub">نقطة قوة</div>
        </div>
        <div class="kpi-card red">
            <div class="kpi-icon-wrap"><i class="fas fa-arrow-down"></i></div>
            <div class="kpi-val"><?php echo $overallMin !== null ? $overallMin . '%' : '—'; ?></div>
            <div class="kpi-lbl">أدنى درجة</div>
            <div class="kpi-sub">نقطة تحسين</div>
        </div>
        <?php if ($bestYear): ?>
        <div class="kpi-card green">
            <div class="kpi-icon-wrap"><i class="fas fa-trophy"></i></div>
            <div class="kpi-val"><?php echo $bestYear->evaluation_year; ?></div>
            <div class="kpi-lbl">أفضل سنة</div>
            <div class="kpi-sub">بمتوسط <?php echo $bestYear->average_score; ?>%</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Year Cards -->
    <div class="year-grid">
        <?php foreach ($evaluationsByYear as $summary):
            $hijriData = convertToHijri($summary->first_evaluation_date);
            $hijriYear = $hijriData ? $hijriData->year : '';
            $scoreClamped = max(0, min(100, (float)$summary->average_score));
        ?>
        <div class="year-card">
            <div class="year-card-head">
                <h4><i class="fas fa-calendar-alt"></i> تقييمات عام <?php echo $summary->evaluation_year; ?> م</h4>
                <?php if ($hijriYear): ?>
                    <span class="year-badge"><?php echo $hijriYear; ?> هـ</span>
                <?php endif; ?>
            </div>
            <div class="year-card-body">
                <div class="year-card-top">
                    <div class="score-ring" style="--score:<?php echo $scoreClamped; ?>">
                        <span><?php echo rtrim(rtrim(number_format($scoreClamped,1),'0'),'.'); ?>%</span>
                    </div>

                    <?php if ($summary->evaluations_count > 1): ?>
                    <div class="bar-chart">
                        <?php
                        $max = (float)$summary->max_score;
                        $avg = (float)$summary->average_score;
                        $min = (float)$summary->min_score;
                        ?>
                        <div class="bar-item">
                            <span class="bar-label">أعلى <span class="bar-dot gold"></span></span>
                            <div class="bar-track"><div class="bar-fill gold" style="width:<?php echo $max; ?>%"></div></div>
                            <span class="bar-val"><?php echo $max; ?>%</span>
                        </div>
                        <div class="bar-item">
                            <span class="bar-label">متوسط <span class="bar-dot green"></span></span>
                            <div class="bar-track"><div class="bar-fill green" style="width:<?php echo $avg; ?>%"></div></div>
                            <span class="bar-val"><?php echo $avg; ?>%</span>
                        </div>
                        <div class="bar-item">
                            <span class="bar-label">أدنى <span class="bar-dot red"></span></span>
                            <div class="bar-track"><div class="bar-fill red" style="width:<?php echo $min; ?>%"></div></div>
                            <span class="bar-val"><?php echo $min; ?>%</span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="single-eval-badge">
                        <i class="fas fa-file-alt"></i>
                        <span>تقييم واحد فقط</span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="year-card-stats">
                    <div class="year-stat">
                        <div class="year-stat-val"><?php echo $summary->evaluations_count; ?></div>
                        <div class="year-stat-lbl">تقييم</div>
                    </div>
                    <div class="year-stat">
                        <div class="year-stat-val" style="color:var(--gold);"><?php echo $summary->max_score; ?>%</div>
                        <div class="year-stat-lbl">أعلى</div>
                    </div>
                    <div class="year-stat">
                        <div class="year-stat-val" style="color:#ef4444;"><?php echo $summary->min_score; ?>%</div>
                        <div class="year-stat-lbl">أدنى</div>
                    </div>
                    <div class="year-stat">
                        <div class="year-stat-val" style="color:var(--brand);"><?php echo $summary->average_score; ?>%</div>
                        <div class="year-stat-lbl">متوسط</div>
                    </div>
                </div>
            </div>
            <div class="year-card-foot">
                <a href="<?php echo APP_URL . '/employees/' . $employee->id . '/evaluations/' . $summary->evaluation_year; ?>" class="btn-year-detail">
                    <i class="fas fa-search-plus"></i> عرض تفاصيل العام
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

    <div style="text-align:center;">
        <a href="<?php echo APP_URL; ?>/evaluations" class="back-btn">
            <i class="fas fa-arrow-right"></i> العودة إلى قائمة التقييمات
        </a>
    </div>

</div>
</div>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
