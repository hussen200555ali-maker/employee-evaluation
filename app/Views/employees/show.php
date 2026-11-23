<?php require_once APP_ROOT . '/app/Views/layouts/header.php';
$initials = implode('', array_map(fn($w)=> mb_substr($w,0,1,'UTF-8'), array_slice(explode(' ', trim($employee->full_name ?? '')),0,2))); $initials = $initials ?: '؟';
$code = $employee->emp_code ?: '—';
?>
<style>
/* v36 — تعديلات موضعية فقط بهوية التيل/الذهب (لا مساس بـ style.css العام) */
.emp-crumb{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;font-size:.82rem;color:#6f817b}
.emp-crumb a{color:#0d5e52;text-decoration:none;font-weight:700}
.emp-crumb a:hover{color:#c8943f}
.emp-crumb__sep{color:#c8943f;font-weight:900}
.emp-crumb__current{color:#16302b;font-weight:800}
.emp-avatar{width:76px;height:76px;border-radius:22px;flex-shrink:0;display:grid;place-items:center;background:linear-gradient(145deg,#147b6a,#093f37);border:2px solid #f3ca78;color:#fff;font-size:1.7rem;font-weight:900;box-shadow:0 10px 24px rgba(6,24,20,.28);letter-spacing:.02em}
.emp-sep{color:#f3ca78;padding:0 .25rem;font-weight:900}
.app-stat strong.emp-stat-text{font-size:1rem;line-height:1.25}
.emp-info-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}
.emp-info-item{display:flex;gap:.75rem;align-items:flex-start;padding:.75rem;border:1px solid #e2eee8;border-radius:14px;background:#fbfdfc}
.emp-info-icon{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;flex-shrink:0;background:#eef7f3;color:#c8943f;font-size:.9rem}
.emp-info-label{font-size:.72rem;color:#6f817b;font-weight:700;margin-bottom:.15rem}
.emp-info-value{font-size:.9rem;color:#16302b;font-weight:800;word-break:break-word}
.emp-actions .app-btn-gold,.emp-actions .app-btn-ghost{width:100%;justify-content:center}
@media (max-width:575.98px){.emp-info-grid{grid-template-columns:1fr}.emp-avatar{width:64px;height:64px;font-size:1.4rem}}
</style>

<div class="container py-3" dir="rtl">
    <nav class="emp-crumb mb-3" aria-label="مسار التنقل">
        <a href="<?= APP_URL ?>/employees"><i class="fas fa-users me-1"></i> الموظفون</a>
        <span class="emp-crumb__sep">›</span>
        <span class="emp-crumb__current">الملف الشخصي للموظف</span>
    </nav>

    <section class="app-hero" aria-labelledby="emp-show-title">
        <div class="app-hero__left">
            <span class="app-eyebrow"><i class="fas fa-user-tie"></i> الملف الشخصي للموظف</span>
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="emp-avatar" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper($initials,'UTF-8')) ?></div>
                <div>
                    <h1 id="emp-show-title"><?= htmlspecialchars($employee->full_name) ?></h1>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="app-chip app-chip--gold" dir="ltr"><i class="fas fa-id-badge me-1"></i><?= htmlspecialchars($code) ?></span>
                        <?php if($employee->is_active): ?>
                            <span class="app-chip app-chip--mint"><i class="fas fa-circle me-1" style="font-size:.5rem;vertical-align:middle"></i> نشط</span>
                        <?php else: ?>
                            <span class="app-chip app-chip--red">غير نشط</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <p>
                <i class="fas fa-briefcase me-1"></i><?= htmlspecialchars($employee->position_title ?? 'غير محدد') ?>
                <span class="emp-sep">·</span>
                <i class="fas fa-sitemap me-1"></i><?= htmlspecialchars($employee->department_name ?? '—') ?>
                <span class="emp-sep">·</span>
                <i class="fas fa-code-branch me-1"></i><?= htmlspecialchars($employee->branch_name ?? '—') ?>
            </p>
            <a class="app-hero__cta" href="<?= APP_URL ?>/employees"><i class="fas fa-arrow-right"></i> العودة إلى الموظفين</a>
        </div>
        <div class="app-hero__stats" aria-label="إحصائيات سريعة">
            <div class="app-stat"><i class="fas fa-id-badge"></i><strong dir="ltr"><?= htmlspecialchars($code) ?></strong><span>الرقم الوظيفي</span></div>
            <div class="app-stat"><i class="fas fa-user-tie"></i><strong class="emp-stat-text"><?= htmlspecialchars($employee->position_title ?? 'غير محدد') ?></strong><span>المنصب</span></div>
            <div class="app-stat"><i class="fas fa-sitemap"></i><strong class="emp-stat-text"><?= htmlspecialchars($employee->department_name ?? '—') ?></strong><span>القسم</span></div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="app-card">
                <div class="app-card__header">
                    <h2 class="app-card__title"><i class="fas fa-sitemap"></i> المعلومات الوظيفية <span class="app-card__sub">بيانات التعيين والتنظيم الإداري</span></h2>
                    <span class="app-count"><i class="fas fa-list-check"></i> 6 عناصر</span>
                </div>
                <div class="emp-info-grid p-3">
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-building"></i></div><div><div class="emp-info-label">الفرع</div><div class="emp-info-value"><?= htmlspecialchars($employee->branch_name ?? '—') ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-layer-group"></i></div><div><div class="emp-info-label">القسم</div><div class="emp-info-value"><?= htmlspecialchars($employee->department_name ?? '—') ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-user-tie"></i></div><div><div class="emp-info-label">المنصب</div><div class="emp-info-value"><?= htmlspecialchars($employee->position_title ?? 'غير محدد') ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-user-friends"></i></div><div><div class="emp-info-label">المدير المباشر</div><div class="emp-info-value"><?= htmlspecialchars($employee->manager_name ?? 'لا يوجد') ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-toggle-on"></i></div><div><div class="emp-info-label">الحالة</div><div class="emp-info-value"><?= $employee->is_active ? 'نشط' : 'غير نشط' ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-hashtag"></i></div><div><div class="emp-info-label">الرقم الوظيفي</div><div class="emp-info-value" dir="ltr"><?= htmlspecialchars($code) ?></div></div></div>
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="app-card h-100">
                <div class="app-card__header">
                    <h2 class="app-card__title"><i class="fas fa-bolt"></i> إجراءات سريعة</h2>
                </div>
                <div class="p-3 d-grid gap-2 emp-actions">
                    <a href="<?= APP_URL ?>/evaluations/create?employee_id=<?= (int)$employee->id ?>" class="app-btn-gold"><i class="fas fa-plus ms-1"></i> إنشاء تقييم جديد</a>
                    <a href="<?= APP_URL ?>/employees/<?= (int)$employee->id ?>/evaluations" class="app-btn-ghost"><i class="fas fa-history ms-1"></i> السجل التقييمي</a>
                    <a href="<?= APP_URL ?>/employees/profile/<?= (int)$employee->id ?>" class="app-btn-ghost"><i class="fas fa-id-card ms-1"></i> الملف التقييمي الكامل</a>
                    <?php if(App\Core\Auth::userHasPermission('employees.edit')): ?>
                        <a href="<?= APP_URL ?>/employees/<?= (int)$employee->id ?>/edit" class="app-btn-ghost"><i class="fas fa-edit ms-1"></i> تعديل البيانات</a>
                    <?php endif; ?>
                </div>
                <?php if(!empty($employee->nickname)): ?>
                    <div class="px-3 pb-3 small text-muted"><i class="fas fa-tag me-1" style="color:#c8943f"></i> اللقب: <strong class="text-dark"><?= htmlspecialchars($employee->nickname) ?></strong></div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
