<?php
/* CD-036 : training_programs/show.php — Screen 4 (details + filters, NO link button) */
require_once APP_ROOT . '/app/Views/layouts/header.php';
require_once APP_ROOT . '/app/Views/layouts/_flash_messages.php';
$p = $program ?? null; $stats = $stats ?? []; $evaluated = $evaluated ?? []; $departments = $departments ?? []; $filters = $filters ?? [];
$hasFilters = trim($filters['search'] ?? '') !== '' || !empty($filters['department_id']) || !empty($filters['grade']);
$basePath = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
function grade_label(float $s): array { if ($s>=90) return ['ممتاز','gold']; if ($s>=80) return ['جيد جدًا','green']; if ($s>=70) return ['جيد','muted']; return ['مقبول','muted']; }
?>
<style>
.tr-back{margin-bottom:10px}
.tr-val{font-size:15px;color:#1a1a1a;}
.tr-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
.tr-kpi{background:#f7f5f0;border-top:4px solid #c8943f;border-radius:12px;padding:16px;text-align:center;}
.tr-kpi .n{font-size:28px;font-weight:800;color:#0c2823;}
.tr-kpi .l{font-size:12.5px;color:#6b6b6b;margin-top:6px;display:flex;align-items:center;justify-content:center;gap:.4rem;}
.tr-kpi .l i{color:#c8943f;}
.tr-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:2px;}
.tr-grade.gold{color:#c8943f;font-weight:800;}.tr-grade.green{color:#0c2823;font-weight:700;}.tr-grade.muted{color:#8a8a8a;font-weight:700;}
.empty{text-align:center;color:#8a8a8a;padding:18px;}
@media(max-width:640px){.tr-kpis{grid-template-columns:1fr;}}
</style>

<div class="tr-back app-hero__ghosts" style="margin-bottom:.8rem"><a class="app-btn-ghost" href="<?php echo APP_URL; ?>/training/programs"><i class="fas fa-arrow-right"></i> العودة إلى سجل البرامج</a></div>

<section class="app-hero" aria-labelledby="tp-show-title">
    <div class="app-hero__left">
        <span class="app-eyebrow"><i class="fas fa-graduation-cap"></i> سجل البرامج التدريبية</span>
        <h1 id="tp-show-title" style="font-size:clamp(1.4rem,2vw + .8rem,2rem)"><?php echo htmlspecialchars($p->title ?? ''); ?></h1>
        <p>تفاصيل البرنامج وإحصائيات الموظفين المقيّمين بعد الانتهاء منه.</p>
    </div>
    <div class="app-hero__stats" aria-label="إحصائيات سريعة">
        <div class="app-stat"><i class="fas fa-users"></i><strong><?php echo (int)($stats['evaluated_count'] ?? 0); ?></strong><span>مقيّم</span></div>
        <div class="app-stat"><i class="fas fa-star"></i><strong><?php echo ($stats['avg_score'] !== null && $stats['avg_score'] !== false) ? htmlspecialchars((string)$stats['avg_score']) : '—'; ?></strong><span>متوسط الدرجات</span></div>
        <div class="app-stat"><i class="fas fa-circle-check"></i><strong><?php echo (int)($stats['completion_pct'] ?? 0); ?>%</strong><span>نسبة الإكمال</span></div>
    </div>
</section>

<?php if (!$p): ?>
    <div class="tr-card"><div class="app-empty" style="border:0;margin:0"><i class="fas fa-circle-info"></i><h3>البرنامج غير موجود</h3><p>تأكد من الرابط أو عُد إلى سجل البرامج.</p></div></div>
<?php else: ?>

<div class="tr-card">
    <div class="tr-title"><i class="fas fa-clipboard-list"></i> معلومات البرنامج</div>
    <div class="tr-sub">ملخص الأداء بعد تقييم الموظفين</div>
    <div class="tr-grid2">
        <div><div class="tr-label"><i class="fas fa-building"></i> الجهة المنفّذة</div><div class="tr-val"><?php echo htmlspecialchars($p->branch_name ?? '—'); ?><?php echo !empty($p->department_name) ? ' — '.htmlspecialchars($p->department_name) : ''; ?></div></div>
        <div><div class="tr-label"><i class="fas fa-user-tie"></i> المسؤول</div><div class="tr-val"><?php echo htmlspecialchars($p->responsible_person ?? '—'); ?></div></div>
        <div><div class="tr-label"><i class="fas fa-calendar-days"></i> تاريخ البدء</div><div class="tr-val"><?php echo htmlspecialchars($p->start_date ?? '—'); ?></div></div>
        <div><div class="tr-label"><i class="fas fa-calendar-days"></i> تاريخ الانتهاء</div><div class="tr-val"><?php echo htmlspecialchars($p->end_date ?? '—'); ?></div></div>
    </div>
</div>

<div class="tr-card">
    <div class="tr-title"><i class="fas fa-chart-column"></i> الإحصائيات</div>
    <div class="tr-kpis">
        <div class="tr-kpi"><div class="n"><?php echo (int)($stats['evaluated_count'] ?? 0); ?></div><div class="l"><i class="fas fa-users"></i> إجمالي المقيّمين</div></div>
        <div class="tr-kpi"><div class="n"><?php echo ($stats['avg_score'] !== null && $stats['avg_score'] !== false) ? htmlspecialchars((string)$stats['avg_score']) : '—'; ?></div><div class="l"><i class="fas fa-star"></i> متوسط الدرجات</div></div>
        <div class="tr-kpi"><div class="n"><?php echo (int)($stats['completion_pct'] ?? 0); ?>%</div><div class="l"><i class="fas fa-circle-check"></i> نسبة الإكمال</div></div>
    </div>
</div>

<div class="tr-card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
    <div class="tr-title"><i class="fas fa-list-check"></i> الموظفون المقيّمون</div>
        <div><a class="tr-btn tr-btn-out tr-btn-sm" href="#"><i class="fas fa-file-pdf"></i> تصدير PDF</a> <a class="tr-btn tr-btn-out tr-btn-sm" href="#" style="margin-right:6px"><i class="fas fa-file-excel"></i> تصدير Excel</a></div>
    </div>

    <form method="GET" action="" class="tr-filters">
        <div class="tr-ff" style="flex:1 1 240px;min-width:200px"><label class="tr-label"><i class="fas fa-magnifying-glass"></i> بحث</label><input class="tr-input" type="text" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="ابحث باسم الموظف أو الرقم الوظيفي..."></div>
        <div class="tr-ff" style="flex:1 1 180px;min-width:150px"><label class="tr-label"><i class="fas fa-sitemap"></i> القسم</label><select class="tr-input" name="department_id" onchange="this.form.submit()"><option value="">الكل</option><?php foreach ($departments as $d): ?><option value="<?php echo (int)$d->id; ?>" <?php echo ((int)($filters['department_id']??0)===(int)$d->id)?'selected':''; ?>><?php echo htmlspecialchars($d->name); ?></option><?php endforeach; ?></select></div>
        <div class="tr-ff" style="flex:1 1 160px;min-width:140px"><label class="tr-label"><i class="fas fa-star"></i> التقدير</label><select class="tr-input" name="grade" onchange="this.form.submit()"><option value="">الكل</option><?php foreach (['ممتاز','جيد جدًا','جيد','مقبول'] as $g): ?><option value="<?php echo $g; ?>" <?php echo (($filters['grade']??'')===$g)?'selected':''; ?>><?php echo $g; ?></option><?php endforeach; ?></select></div>
        <div class="tr-ff" style="flex:0 0 auto"><button type="submit" class="tr-btn tr-btn-gold tr-btn-sm">تطبيق</button> <a class="tr-btn tr-btn-out tr-btn-sm" href="<?php echo htmlspecialchars($basePath); ?>" style="margin-right:6px"><i class="fas fa-rotate-right"></i> تفريغ</a></div>
    </form>
    <?php if ($hasFilters): ?><div class="tr-note">عرض <?php echo count($evaluated); ?> نتيجة — الفلاتر مفعّلة.</div><?php endif; ?>

    <div class="table-responsive">
        <table class="tr-table">
            <thead><tr><th>#</th><th><i class="fas fa-user"></i> الموظف</th><th><i class="fas fa-sitemap"></i> القسم</th><th>الدرجة</th><th>التقدير</th><th><i class="fas fa-calendar-days"></i> التاريخ</th></tr></thead>
            <tbody>
            <?php foreach ($evaluated as $i => $row):
                $score = (float)($row->final_score ?? 0);
                [$gLabel, $gClass] = grade_label($score);
            ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo htmlspecialchars($row->employee_name ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($row->department_name ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($row->final_score ?? '—'); ?></td>
                    <td><span class="tr-grade <?php echo $gClass; ?>"><?php echo $gLabel; ?></span></td>
                    <td><?php echo htmlspecialchars($row->completed_at ?? '—'); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$evaluated): ?><tr><td colspan="6" class="empty"><?php echo $hasFilters ? 'لا توجد نتائج مطابقة للفلاتر.' : 'لا يوجد موظفون مقيّمون بعد — الربط يتم من شاشة التقييم'; ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
