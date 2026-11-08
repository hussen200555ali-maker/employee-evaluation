<?php
/* CD-036 : training_programs/show.php — Screen 4 (details + filters, NO link button) */
require_once APP_ROOT . '/app/Views/layouts/header.php';
require_once APP_ROOT . '/app/Views/layouts/_flash_messages.php';
$p = $program ?? null; $stats = $stats ?? []; $evaluated = $evaluated ?? []; $departments = $departments ?? []; $filters = $filters ?? [];
$icon = (($p->type ?? 'course') === 'workshop') ? '🏆' : '📚';
$hasFilters = trim($filters['search'] ?? '') !== '' || !empty($filters['department_id']) || !empty($filters['grade']);
$basePath = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
function grade_label(float $s): array { if ($s>=90) return ['ممتاز','gold']; if ($s>=80) return ['جيد جدًا','green']; if ($s>=70) return ['جيد','muted']; return ['مقبول','muted']; }
?>
<style>
.tr-back{margin-bottom:10px}
.tr-card{background:#fff;border:1px solid #e6dfcf;border-radius:12px;padding:22px;margin-bottom:18px;box-shadow:0 1px 3px rgba(12,40,35,.08);}
.tr-title{color:#0c2823;font-size:18px;font-weight:800;margin:0 0 2px;}
.tr-sub{color:#6b6b6b;font-size:13px;margin:0 0 14px;}
.tr-label{font-weight:700;color:#0c2823;font-size:13px;margin-bottom:2px;display:block;}
.tr-val{font-size:15px;color:#1a1a1a;}
.tr-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.tr-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
.tr-kpi{background:#f7f5f0;border-top:4px solid #c8943f;border-radius:10px;padding:16px;text-align:center;}
.tr-kpi .n{font-size:28px;font-weight:800;color:#0c2823;}
.tr-kpi .l{font-size:12.5px;color:#6b6b6b;margin-top:2px;}
.tr-input{width:100%;padding:11px 13px;border:1px solid #d4d0c8;border-radius:8px;font-family:'Cairo',sans-serif;font-size:15px;box-sizing:border-box;min-height:42px;background:#fff;color:#1a1a1a;}
.tr-input:focus{outline:none;border-color:#c8943f;box-shadow:0 0 0 3px rgba(200,148,63,.15);}
.tr-filters{display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;margin:14px 0 4px;}
.tr-ff{display:flex;flex-direction:column;}
.tr-note{font-size:12px;color:#8a8a8a;margin-top:8px;}
.tr-btn{padding:9px 18px;border-radius:8px;font-weight:700;cursor:pointer;border:none;font-family:'Cairo',sans-serif;text-decoration:none;display:inline-block;font-size:14px;}
.tr-btn-out{background:transparent;border:1.5px solid #0c2823;color:#0c2823;}
.tr-btn-gold{background:#c8943f;color:#0c2823;}
.tr-table{width:100%;border-collapse:collapse;margin-top:10px;}
.tr-table th{background:#0c2823;color:#fff;padding:11px 12px;text-align:right;font-size:13px;}
.tr-table td{padding:10px 12px;border-bottom:1px solid #e6dfcf;font-size:14px;}
.tr-table tbody tr:nth-child(even) td{background:#faf8f3;}
.tr-table tbody tr:hover td{background:#f0ede4;}
.tr-grade.gold{color:#c8943f;font-weight:800;}.tr-grade.green{color:#0c2823;font-weight:700;}.tr-grade.muted{color:#8a8a8a;font-weight:700;}
.empty{text-align:center;color:#8a8a8a;padding:18px;}
@media(max-width:640px){.tr-grid2,.tr-kpis,.tr-filters{grid-template-columns:1fr;}}
</style>

<div class="tr-back"><a class="tr-btn tr-btn-out tr-btn-sm" href="<?php echo APP_URL; ?>/training/programs">← العودة إلى سجل البرامج</a></div>

<div class="page-header"><h2><i class="fas fa-<?php echo ($p->type??'course')==='workshop'?'tools':'chalkboard-teacher'; ?>"></i> <?php echo $icon; ?> تفاصيل: <?php echo htmlspecialchars($p->title ?? ''); ?></h2></div>

<?php if (!$p): ?>
    <div class="tr-card"><div class="empty">البرنامج غير موجود</div></div>
<?php else: ?>

<div class="tr-card">
    <div class="tr-title">معلومات البرنامج</div>
    <div class="tr-sub">ملخص الأداء بعد تقييم الموظفين</div>
    <div class="tr-grid2">
        <div><div class="tr-label">🏢 الجهة المنفّذة</div><div class="tr-val"><?php echo htmlspecialchars($p->branch_name ?? '—'); ?><?php echo !empty($p->department_name) ? ' — '.htmlspecialchars($p->department_name) : ''; ?></div></div>
        <div><div class="tr-label">👤 المسؤول</div><div class="tr-val"><?php echo htmlspecialchars($p->responsible_person ?? '—'); ?></div></div>
        <div><div class="tr-label">📅 تاريخ البدء</div><div class="tr-val"><?php echo htmlspecialchars($p->start_date ?? '—'); ?></div></div>
        <div><div class="tr-label">📅 تاريخ الانتهاء</div><div class="tr-val"><?php echo htmlspecialchars($p->end_date ?? '—'); ?></div></div>
    </div>
</div>

<div class="tr-card">
    <div class="tr-title">📊 الإحصائيات</div>
    <div class="tr-kpis">
        <div class="tr-kpi"><div class="n"><?php echo (int)($stats['evaluated_count'] ?? 0); ?></div><div class="l">📊 إجمالي المقيّمين</div></div>
        <div class="tr-kpi"><div class="n"><?php echo ($stats['avg_score'] !== null && $stats['avg_score'] !== false) ? htmlspecialchars($stats['avg_score']) : '—'; ?></div><div class="l">⭐ متوسط الدرجات</div></div>
        <div class="tr-kpi"><div class="n"><?php echo (int)($stats['completion_pct'] ?? 0); ?>%</div><div class="l">✅ نسبة الإكمال</div></div>
    </div>
</div>

<div class="tr-card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <div class="tr-title" style="margin:0">الموظفون المقيّمون</div>
        <div><a class="tr-btn tr-btn-out tr-btn-sm" href="#">تصدير PDF</a> <a class="tr-btn tr-btn-out tr-btn-sm" href="#" style="margin-right:6px">تصدير Excel</a></div>
    </div>

    <form method="GET" action="" class="tr-filters">
        <div class="tr-ff" style="flex:1 1 240px;min-width:200px"><label class="tr-label">🔍 بحث</label><input class="tr-input" type="text" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="ابحث باسم الموظف أو الرقم الوظيفي..."></div>
        <div class="tr-ff" style="flex:1 1 180px;min-width:150px"><label class="tr-label">📋 القسم</label><select class="tr-input" name="department_id" onchange="this.form.submit()"><option value="">الكل</option><?php foreach ($departments as $d): ?><option value="<?php echo (int)$d->id; ?>" <?php echo ((int)($filters['department_id']??0)===(int)$d->id)?'selected':''; ?>><?php echo htmlspecialchars($d->name); ?></option><?php endforeach; ?></select></div>
        <div class="tr-ff" style="flex:1 1 160px;min-width:140px"><label class="tr-label">⭐ التقدير</label><select class="tr-input" name="grade" onchange="this.form.submit()"><option value="">الكل</option><?php foreach (['ممتاز','جيد جدًا','جيد','مقبول'] as $g): ?><option value="<?php echo $g; ?>" <?php echo (($filters['grade']??'')===$g)?'selected':''; ?>><?php echo $g; ?></option><?php endforeach; ?></select></div>
        <div class="tr-ff" style="flex:0 0 auto"><button type="submit" class="tr-btn tr-btn-gold tr-btn-sm">تطبيق</button> <a class="tr-btn tr-btn-out tr-btn-sm" href="<?php echo htmlspecialchars($basePath); ?>" style="margin-right:6px">↺ تفريغ</a></div>
    </form>
    <?php if ($hasFilters): ?><div class="tr-note">عرض <?php echo count($evaluated); ?> نتيجة — الفلاتر مفعّلة.</div><?php endif; ?>

    <div class="table-responsive">
        <table class="tr-table">
            <thead><tr><th>#</th><th>👤 الموظف</th><th>📋 القسم</th><th>الدرجة</th><th>التقدير</th><th>📅 التاريخ</th></tr></thead>
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
