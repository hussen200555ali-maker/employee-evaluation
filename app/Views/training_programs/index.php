<?php
/* CD-036.3 : training_programs/index.php — Programs Registry (CRUD + filters)
   معاد تصميمها 2026-11-19 بالهوية الموحدة للمشروع (hero teal + gold، filter strip، Card-table). */
require_once APP_ROOT . '/app/Views/layouts/header.php';
require_once APP_ROOT . '/app/Views/layouts/_flash_messages.php';
$programs = $programs ?? []; $filters = $filters ?? []; $branches = $branches ?? []; $departments = $departments ?? [];
$hasFilters = trim($filters['search'] ?? '') !== '' || !empty($filters['type']) || !empty($filters['branch_id']) || !empty($filters['department_id']) || !empty($filters['from_date']) || !empty($filters['to_date']);
$basePath = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
$canManage = App\Core\Auth::userHasPermission('training.manage');

// إحصائيات سريعة (للـ hero)
$totalPrograms = count($programs);
$totalParticipants = 0; $totalCourses = 0; $totalWorkshops = 0;
foreach ($programs as $_p) {
    $totalParticipants += (int)($_p->participant_count ?? 0);
    if (($_p->type ?? '') === 'workshop') $totalWorkshops++; else $totalCourses++;
}
?>

<style>
/* ====== training programs registry — brand redesign ====== */
.tp-page { padding: 1.1rem 1.1rem 2.4rem; }
.tp-page * { box-sizing: border-box; }

/* Hero */
.tp-hero {
    position: relative; isolation: isolate; overflow: hidden; border-radius: 20px;
    padding: clamp(1.4rem, 2.4vw + 1rem, 2.2rem); margin-bottom: 1.1rem;
    color: #effaf5; background: linear-gradient(120deg,#093f37 0%,#0d6658 60%,#147b6a 100%);
    box-shadow: 0 18px 40px rgba(9,63,55,.22);
    display: flex; align-items: center; justify-content: space-between; gap: 1.4rem; flex-wrap: wrap;
}
.tp-hero::after {
    content: ''; position: absolute; inset: 0; z-index: -1; opacity: .42;
    background-image: radial-gradient(circle at 90% 22%, rgba(255,255,255,.2) 0 2px, transparent 3px);
    background-size: 18px 18px;
}
.tp-hero::before {
    content: ''; position: absolute; z-index: -1; top: -30%; left: -6%; width: 16rem; height: 16rem;
    border-radius: 50%; background: radial-gradient(circle, rgba(200,148,63,.22), transparent 65%); filter: blur(18px);
}
.tp-hero__left { flex: 1 1 24rem; min-width: 18rem; }
.eyebrow {
    display: inline-flex; align-items: center; gap: .5rem;
    color: #c6eadf; font-size: .74rem; font-weight: 800; letter-spacing: .08em;
    text-transform: uppercase; margin-bottom: .6rem;
}
.eyebrow i { color: #f3ca78; font-size: .95rem; }
.tp-hero h1 {
    color: #fff; font-weight: 900; letter-spacing: -.02em;
    font-size: clamp(1.55rem, 2.2vw + .8rem, 2.3rem); line-height: 1.3; margin: 0 0 .55rem;
}
.tp-hero h1 .accent { color: #f3ca78; }
.tp-hero p { color: rgba(255,255,255,.78); max-width: 34rem; font-size: .92rem; line-height: 1.7; margin: 0 0 1rem; }
.tp-hero__cta {
    display: inline-flex; align-items: center; gap: .55rem;
    background: linear-gradient(90deg,#f3ca78,#c8943f);
    color: #3a2a06; font-weight: 800; border-radius: 12px; padding: .72rem 1.15rem;
    text-decoration: none; border: 0; cursor: pointer;
    box-shadow: 0 12px 28px rgba(200,148,63,.32); font-family: inherit; font-size: .9rem;
    transition: transform .18s ease;
}
.tp-hero__cta:hover { transform: translateY(-2px); }
.tp-hero__stats { display: flex; gap: .65rem; flex-wrap: wrap; flex: 0 0 auto; }
.tp-stat {
    min-width: 6.8rem; padding: .85rem .95rem; border-radius: 16px;
    border: 1px solid rgba(255,255,255,.18); background: rgba(255,255,255,.1);
    backdrop-filter: blur(10px);
}
.tp-stat i { color: #f3ca78; font-size: .8rem; display: block; margin-bottom: .35rem; }
.tp-stat strong { display: block; color: #fff; font-weight: 900; font-size: 1.45rem; line-height: 1; letter-spacing: -.02em; }
.tp-stat span { font-size: .7rem; color: rgba(255,255,255,.7); display: block; margin-top: .35rem; }

/* Filter strip */
.tp-filters {
    display: flex; flex-wrap: wrap; gap: .6rem; align-items: flex-end;
    padding: .7rem .75rem; margin-bottom: .9rem;
    background: rgba(255,255,255,.9); border: 1px solid #e2eee8; border-radius: 16px;
    box-shadow: 0 4px 14px rgba(22,48,43,.06);
}
.tp-field { display: flex; flex-direction: column; gap: .3rem; flex: 1 1 140px; min-width: 118px; }
.tp-field--search { flex: 1 1 220px; min-width: 180px; }
.tp-field label {
    display: inline-flex; align-items: center; gap: .4rem;
    font-size: .72rem; font-weight: 800; color: #34443d; letter-spacing: -.01em;
}
.tp-field label i { color: #c8943f; width: .9rem; text-align: center; }
.tp-field input, .tp-field select {
    padding: .55rem .7rem; border-radius: 11px; border: 1px solid #d6e3dd;
    background: #f8fbf9; color: #16302b; font-family: inherit; font-size: .86rem; min-height: 40px;
    transition: border-color .18s, box-shadow .18s;
}
.tp-field input:focus, .tp-field select:focus {
    outline: none; border-color: #0d5e52; background: #fff;
    box-shadow: 0 0 0 .22rem rgba(13,94,82,.14);
}
.tp-filter-actions { display: flex; gap: .5rem; align-items: center; flex: 0 0 auto; }
.tp-btn-gold {
    display: inline-flex; align-items: center; gap: .45rem;
    background: linear-gradient(90deg,#f3ca78,#c8943f); color: #3a2a06;
    border: 0; border-radius: 11px; padding: .6rem 1.1rem; min-height: 40px;
    font-family: inherit; font-size: .86rem; font-weight: 800; cursor: pointer;
    box-shadow: 0 8px 18px rgba(200,148,63,.3); transition: transform .18s, filter .18s;
}
.tp-btn-gold:hover { transform: translateY(-1px); filter: brightness(1.03); }
.tp-btn-ghost {
    display: inline-flex; align-items: center; gap: .45rem;
    background: #fff; color: #34443d; border: 1.5px solid #d6e3dd; border-radius: 11px;
    padding: .6rem 1rem; min-height: 40px; font-family: inherit; font-size: .86rem; font-weight: 700;
    text-decoration: none; transition: border-color .18s, color .18s;
}
.tp-btn-ghost:hover { border-color: #c8943f; color: #093f37; }
.tp-filter-note { flex: 0 0 100%; font-size: .74rem; color: #0d5e52; font-weight: 700; padding: 0 .1rem .1rem; }

/* Card + table */
.tp-card {
    background: #fff; border: 1px solid #e2eee8; border-radius: 18px;
    box-shadow: 0 6px 22px rgba(22,48,43,.06); overflow: hidden;
}
.tp-card__header {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: .8rem 1.1rem; border-bottom: 1px solid #eef4f0; background: linear-gradient(180deg,#fff,#fbfdfc);
}
.tp-card__title { color: #16302b; font-weight: 800; font-size: .98rem; letter-spacing: -.01em; display: flex; align-items: center; gap: .5rem; margin: 0; }
.tp-card__title i { color: #c8943f; }
.tp-card__sub { font-size: .74rem; color: #6f817b; display: block; margin-top: .2rem; font-weight: 600; }
.tp-count {
    display: inline-flex; align-items: center; gap: .45rem;
    background: #f4f9f6; border: 1px solid #e2eee8; border-radius: 999px;
    padding: .35rem .85rem; font-size: .78rem; font-weight: 800; color: #0d5e52; white-space: nowrap;
}
.tp-table-wrap { overflow-x: auto; }
.tp-table { width: 100%; border-collapse: collapse; }
.tp-table thead th {
    background: #093f37; color: #fff; font-weight: 800; font-size: .8rem;
    text-align: right; padding: .72rem .8rem; border-bottom: 3px solid #c8943f; white-space: nowrap;
}
.tp-table tbody td { padding: .62rem .8rem; border-bottom: 1px solid #eef4f0; font-size: .85rem; color: #34443d; vertical-align: middle; }
.tp-table tbody tr:nth-child(even) { background: #fdfaf4; }
.tp-table tbody tr:hover { background: #f4f9f6; }
.tp-table tbody tr:last-child td { border-bottom: 0; }
.tp-num { font-variant-numeric: tabular-nums; color: #6f817b; font-weight: 700; }
.tp-name { font-weight: 800; color: #16302b; display: inline-flex; align-items: center; gap: .45rem; }
.tp-chip {
    display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .6rem; border-radius: 999px;
    font-size: .7rem; font-weight: 800; border: 1px solid transparent; white-space: nowrap;
}
.tp-chip--course { background: #eff6ff; color: #1858a2; border-color: #bfdbfe; }
.tp-chip--workshop { background: #fdf6e8; color: #8d5e0c; border-color: #ecd9a8; }
.tp-partic { display: inline-grid; place-items: center; min-width: 2rem; height: 2rem; padding: 0 .45rem; border-radius: 10px; background: #f4f9f6; border: 1px solid #e2eee8; font-size: .85rem; font-weight: 900; color: #093f37; font-variant-numeric: tabular-nums; }
.tp-date { font-variant-numeric: tabular-nums; color: #34443d; font-weight: 600; font-size: .82rem; }

.tp-row-actions { display: flex; align-items: center; gap: .4rem; }
.icon-action {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2.1rem; height: 2.1rem; border-radius: 10px; border: 1px solid #e2eee8;
    background: #fff; color: #34443d; text-decoration: none; cursor: pointer;
    transition: background .16s, color .16s, border-color .16s; font-size: .82rem;
}
.icon-action:hover { background: #0d5e52; border-color: #0d5e52; color: #fff; }
.icon-action--danger:hover { background: #b03a2e; border-color: #b03a2e; color: #fff; }
.icon-action--disabled { opacity: .45; cursor: not-allowed; }
.icon-action--disabled:hover { background: #fff; color: #34443d; border-color: #e2eee8; }

/* Empty state */
.tp-empty { padding: 2.2rem 1rem; text-align: center; color: #6f8a80; }
.tp-empty i { font-size: 2.1rem; color: #c8943f; opacity: .7; display: block; margin-bottom: .5rem; }
.tp-empty h3 { font-size: 1rem; font-weight: 800; color: #16302b; margin: 0 0 .35rem; }
.tp-empty p { font-size: .82rem; margin: 0 0 .8rem; }

@media (max-width: 720px) {
    .tp-page { padding: .7rem .7rem 1.6rem; }
    .tp-hero { padding: 1.25rem; }
    .tp-filter-actions { width: 100%; justify-content: flex-start; }
}
</style>

<div class="tp-page">

    <!-- ====== Hero Banner ====== -->
    <section class="tp-hero" aria-labelledby="tp-title">
        <div class="tp-hero__left">
            <span class="eyebrow"><i class="fas fa-graduation-cap"></i> إدارة التدريب والتطوير</span>
            <h1 id="tp-title">سجل <span class="accent">البرامج التدريبية</span></h1>
            <p>شاشة موحدة لعرض وإدارة كل الدورات والورش التدريبية المعتمدة — مع تصفية حسب النوع والفرع والقسم والفترة الزمنية.</p>
            <?php if ($canManage): ?>
                <a class="tp-hero__cta" href="<?php echo APP_URL; ?>/training/programs/create">
                    <i class="fas fa-plus"></i> إنشاء برنامج جديد
                </a>
            <?php else: ?>
                <span class="tp-hero__cta" style="background: linear-gradient(90deg,#f4f9f6,#fff); color:#34443d; box-shadow:none; cursor: default">
                    <i class="fas fa-eye"></i> وضع العرض فقط
                </span>
            <?php endif; ?>
        </div>
        <div class="tp-hero__stats" aria-label="إحصائيات سريعة">
            <div class="tp-stat"><i class="fas fa-layer-group"></i><strong><?php echo (int)$totalPrograms; ?></strong><span>برنامج</span></div>
            <div class="tp-stat"><i class="fas fa-users"></i><strong><?php echo (int)$totalParticipants; ?></strong><span>مقيّم</span></div>
            <div class="tp-stat"><i class="fas fa-graduation-cap"></i><strong><?php echo (int)$totalCourses; ?></strong><span>دورة</span></div>
            <div class="tp-stat"><i class="fas fa-chalkboard-user"></i><strong><?php echo (int)$totalWorkshops; ?></strong><span>ورشة</span></div>
        </div>
    </section>

    <!-- ====== Filter strip ====== -->
    <form method="GET" action="" class="tp-filters" role="search" aria-label="تصفية البرامج">
        <div class="tp-field tp-field--search">
            <label for="tp-search"><i class="fas fa-magnifying-glass"></i> بحث بالاسم</label>
            <input id="tp-search" type="text" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="اسم البرنامج…" autocomplete="off">
        </div>
        <div class="tp-field">
            <label for="tp-type"><i class="fas fa-layer-group"></i> النوع</label>
            <select id="tp-type" name="type" onchange="this.form.submit()">
                <option value="">الكل</option>
                <option value="course" <?php echo ($filters['type']??'')==='course'?'selected':''; ?>>دورة</option>
                <option value="workshop" <?php echo ($filters['type']??'')==='workshop'?'selected':''; ?>>ورشة</option>
            </select>
        </div>
        <div class="tp-field">
            <label for="tp-branch"><i class="fas fa-building"></i> الفرع</label>
            <select id="tp-branch" name="branch_id" onchange="this.form.submit()">
                <option value="">الكل</option>
                <?php foreach ($branches as $b): ?><option value="<?php echo (int)$b->id; ?>" <?php echo ((int)($filters['branch_id']??0)===(int)$b->id)?'selected':''; ?>><?php echo htmlspecialchars($b->name); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="tp-field">
            <label for="tp-dept"><i class="fas fa-sitemap"></i> القسم</label>
            <select id="tp-dept" name="department_id" onchange="this.form.submit()">
                <option value="">الكل</option>
                <?php foreach ($departments as $d): ?><option value="<?php echo (int)$d->id; ?>" <?php echo ((int)($filters['department_id']??0)===(int)$d->id)?'selected':''; ?>><?php echo htmlspecialchars($d->name); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="tp-field">
            <label for="tp-from"><i class="fas fa-calendar-days"></i> من تاريخ</label>
            <input id="tp-from" type="date" name="from_date" value="<?php echo htmlspecialchars($filters['from_date'] ?? ''); ?>">
        </div>
        <div class="tp-field">
            <label for="tp-to"><i class="fas fa-calendar-days"></i> إلى تاريخ</label>
            <input id="tp-to" type="date" name="to_date" value="<?php echo htmlspecialchars($filters['to_date'] ?? ''); ?>">
        </div>
        <div class="tp-filter-actions">
            <button type="submit" class="tp-btn-gold"><i class="fas fa-magnifying-glass"></i> بحث</button>
            <a class="tp-btn-ghost" href="<?php echo htmlspecialchars($basePath); ?>"><i class="fas fa-rotate-right"></i> تفريغ الفلاتر</a>
        </div>
        <?php if ($hasFilters): ?><div class="tp-filter-note"><i class="fas fa-circle-info"></i> الفلاتر مفعّلة — يظهر الآن <strong><?php echo count($programs); ?></strong> برنامج.</div><?php endif; ?>
    </form>

    <!-- ====== Card + Table ====== -->
    <section class="tp-card" aria-labelledby="tp-list-title">
        <div class="tp-card__header">
            <div>
                <h2 class="tp-card__title" id="tp-list-title"><i class="fas fa-list-ul"></i> البرامج التدريبية المعتمدة</h2>
                <small class="tp-card__sub">محدّثة حسب الفلاتر — الأحدث تدرجاً زمنياً كما ورد من النظام.</small>
            </div>
            <span class="tp-count"><i class="fas fa-layer-group"></i> <?php echo (int)count($programs); ?> برنامج</span>
        </div>
        <div class="tp-table-wrap">
            <table class="tp-table">
                <thead><tr><th>#</th><th>اسم البرنامج</th><th>الجهة المنفّذة</th><th>البدء</th><th>الانتهاء</th><th style="text-align:center">المقيّمون</th><th style="text-align:center">إجراءات</th></tr></thead>
                <tbody>
                <?php foreach ($programs as $i => $p):
                    $isWorkshop = ($p->type ?? '') === 'workshop';
                    $typeIcon = $isWorkshop ? 'fa-chalkboard-user' : 'fa-graduation-cap';
                    $typeLabel = $isWorkshop ? 'ورشة' : 'دورة';
                    $chipClass = $isWorkshop ? 'tp-chip tp-chip--workshop' : 'tp-chip tp-chip--course';
                    $pc = (int)($p->participant_count ?? 0);
                    $pid = (int)$p->id;
                    $dept = !empty($p->department_name) ? ' — ' . htmlspecialchars($p->department_name) : '';
                ?>
                    <tr>
                        <td class="tp-num"><?php echo $i + 1; ?></td>
                        <td>
                            <span class="tp-name"><i class="fas <?php echo $typeIcon; ?>" style="color: <?php echo $isWorkshop ? '#c8943f' : '#1858a2'; ?>"></i><?php echo htmlspecialchars($p->title); ?></span>
                            <div style="margin-top:.2rem"><span class="<?php echo $chipClass; ?>"><?php echo $typeLabel; ?></span></div>
                        </td>
                        <td><?php echo htmlspecialchars($p->branch_name ?? '—') . $dept; ?></td>
                        <td class="tp-date"><?php echo htmlspecialchars($p->start_date ?? '—'); ?></td>
                        <td class="tp-date"><?php echo htmlspecialchars($p->end_date ?? '—'); ?></td>
                        <td style="text-align:center"><span class="tp-partic" title="عدد الموظفين المقيّمين"><?php echo $pc; ?></span></td>
                        <td style="text-align:center">
                            <div class="tp-row-actions">
                                <a class="icon-action" href="<?php echo APP_URL; ?>/training/programs/<?php echo $pid; ?>" title="عرض" aria-label="عرض البرنامج"><i class="fas fa-eye"></i></a>
                                <?php if ($canManage): ?>
                                    <a class="icon-action" href="<?php echo APP_URL; ?>/training/programs/<?php echo $pid; ?>/edit" title="تعديل" aria-label="تعديل البرنامج"><i class="fas fa-pen-to-square"></i></a>
                                    <?php if ($pc > 0): ?>
                                        <span class="icon-action icon-action--disabled" title="لا يمكن الحذف: البرنامج لديه مشاركون مقيّمون" aria-label="الحذف معطّل"><i class="fas fa-lock"></i></span>
                                    <?php else: ?>
                                        <form method="POST" action="<?php echo APP_URL; ?>/training/programs/<?php echo $pid; ?>/delete" style="display:inline;margin:0" onsubmit="return confirm('حذف البرنامج «<?php echo htmlspecialchars($p->title, ENT_QUOTES); ?>»؟ لا يمكن التراجع.')">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="icon-action icon-action--danger" title="حذف" aria-label="حذف البرنامج"><i class="fas fa-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$programs): ?>
                    <tr><td colspan="7">
                        <div class="tp-empty" role="status">
                            <i class="fas fa-clipboard-list"></i>
                            <h3><?php echo $hasFilters ? 'لا توجد برامج مطابقة للفلاتر' : 'لا توجد برامج بعد'; ?></h3>
                            <p><?php echo $hasFilters ? 'جرّب تغيير كلمة البحث أو الفروع أو التواريخ.' : 'ابدأ بإنشاء أول برنامج تدريبي — ستظهر هنا جميع البرامج مع إحصائياتها.'; ?></p>
                            <?php if ($canManage && !$hasFilters): ?>
                                <a class="tp-hero__cta" href="<?php echo APP_URL; ?>/training/programs/create"><i class="fas fa-plus"></i> إنشاء برنامج جديد</a>
                            <?php endif; ?>
                        </div>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
