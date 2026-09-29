<?php require_once APP_ROOT . '/app/Views/layouts/header.php';
$initials = implode('', array_map(fn($w)=> mb_substr($w,0,1,'UTF-8'), array_slice(explode(' ', trim($employee->full_name ?? '')),0,2))); $initials = $initials ?: '؟';
$code = $employee->emp_code ?: '—';
?>
<style>
.profile-hero{background:linear-gradient(135deg,#0b5ed7 0%,#0dcaf0 55%,#20c997 100%);border-radius:20px;color:#fff;overflow:hidden;position:relative;box-shadow:0 12px 40px rgba(13,110,253,.25)}
.profile-hero::after{content:'';position:absolute;inset:0;background:radial-gradient(600px 200px at 85% 10%, rgba(255,255,255,.22), transparent 60%)}
.profile-avatar-lg{width:88px;height:88px;border-radius:22px;background:rgba(255,255,255,.18);border:2px solid rgba(255,255,255,.35);backdrop-filter:blur(10px);display:flex;align-items:center;justify-content:center;font-size:1.9rem;font-weight:800;flex-shrink:0}
.info-card{background:#fff;border:1px solid #eef1f6;border-radius:16px;box-shadow:0 8px 28px rgba(0,0,0,.06);overflow:hidden}
.info-card h4{font-size:1rem;font-weight:800;margin:0;padding:1rem 1.25rem;border-bottom:1px solid #f0f2f7;background:linear-gradient(180deg,#f8f9ff,#fff)}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:0}
.info-item{display:flex;gap:.85rem;padding:1rem 1.25rem;border-bottom:1px solid #f5f7fb}
.info-item:nth-child(odd){background:rgba(248,249,255,.5)}
.info-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:linear-gradient(135deg,#0d6efd,#0dcaf0);color:#fff}
.badge-code{background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:999px;padding:.3rem .7rem;font-size:.8rem;backdrop-filter:blur(6px)}
.status-active{background:linear-gradient(135deg,#198754,#20c997);color:#fff;padding:.35rem .75rem;border-radius:999px;font-size:.8rem}
.status-inactive{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);color:#fff;padding:.35rem .75rem;border-radius:999px;font-size:.8rem}
.btn-glass{background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:999px;backdrop-filter:blur(6px)}
.btn-glass:hover{background:#fff;color:#0b5ed7}
</style>
<div class="container py-3" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav class="small text-muted"><a href="<?= APP_URL ?>/employees" class="text-decoration-none">الموظفون</a> <span class="mx-1">›</span> <span class="text-dark fw-bold">الملف الشخصي</span></nav>
        <a href="<?= APP_URL ?>/employees" class="btn btn-light border" style="border-radius:999px"><i class="fas fa-arrow-right ms-1"></i> العودة للقائمة</a>
    </div>

    <div class="profile-hero p-4 p-md-4 mb-4 position-relative">
        <div class="position-relative" style="z-index:1">
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <div class="profile-avatar-lg"><?= htmlspecialchars(mb_strtoupper($initials,'UTF-8')) ?></div>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h2 class="mb-0 fw-bold" style="letter-spacing:.2px"><?= htmlspecialchars($employee->full_name) ?></h2>
                        <span class="badge-code"><i class="fas fa-id-badge me-1"></i><?= htmlspecialchars($code) ?></span>
                        <?php if($employee->is_active): ?><span class="status-active"><i class="fas fa-circle me-1" style="font-size:.5rem;vertical-align:middle"></i> نشط</span><?php else: ?><span class="status-inactive">غير نشط</span><?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2 small opacity-75">
                        <span><i class="fas fa-briefcase me-1"></i><?= htmlspecialchars($employee->position_title ?? 'غير محدد') ?></span>
                        <span>•</span><span><i class="fas fa-sitemap me-1"></i><?= htmlspecialchars($employee->department_name ?? '—') ?></span>
                        <span>•</span><span><i class="fas fa-code-branch me-1"></i><?= htmlspecialchars($employee->branch_name ?? '—') ?></span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <?php if(App\Core\Auth::userHasPermission('employees.edit')): ?><a href="<?= APP_URL ?>/employees/<?= (int)$employee->id ?>/edit" class="btn btn-glass"><i class="fas fa-edit ms-1"></i> تعديل</a><?php endif; ?>
                    <a href="<?= APP_URL ?>/employees/<?= (int)$employee->id ?>/evaluations" class="btn btn-glass"><i class="fas fa-chart-line ms-1"></i> السجل التقييمي</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="info-card">
                <h4><i class="fas fa-sitemap me-2 text-primary"></i> المعلومات الوظيفية</h4>
                <div class="info-grid">
                    <div class="info-item"><div class="info-icon"><i class="fas fa-building"></i></div><div><div class="small text-muted">الفرع</div><div class="fw-bold"><?= htmlspecialchars($employee->branch_name ?? '—') ?></div></div></div>
                    <div class="info-item"><div class="info-icon" style="background:linear-gradient(135deg,#20c997,#0dcaf0)"><i class="fas fa-layer-group"></i></div><div><div class="small text-muted">القسم</div><div class="fw-bold"><?= htmlspecialchars($employee->department_name ?? '—') ?></div></div></div>
                    <div class="info-item"><div class="info-icon" style="background:linear-gradient(135deg,#ffc107,#fd7e14)"><i class="fas fa-user-tie"></i></div><div><div class="small text-muted">المنصب</div><div class="fw-bold"><?= htmlspecialchars($employee->position_title ?? 'غير محدد') ?></div></div></div>
                    <div class="info-item"><div class="info-icon" style="background:linear-gradient(135deg,#6f42c1,#d63384)"><i class="fas fa-user-friends"></i></div><div><div class="small text-muted">المدير المباشر</div><div class="fw-bold"><?= htmlspecialchars($employee->manager_name ?? 'لا يوجد') ?></div></div></div>
                    <div class="info-item"><div class="info-icon" style="background:linear-gradient(135deg,#198754,#20c997)"><i class="fas fa-toggle-on"></i></div><div><div class="small text-muted">الحالة</div><div class="fw-bold"><?= $employee->is_active ? 'نشط' : 'غير نشط' ?></div></div></div>
                    <div class="info-item"><div class="info-icon" style="background:linear-gradient(135deg,#0d6efd,#6f42c1)"><i class="fas fa-hashtag"></i></div><div><div class="small text-muted">الرقم الوظيفي</div><div class="fw-bold" dir="ltr"><?= htmlspecialchars($code) ?></div></div></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="info-card h-100">
                <h4><i class="fas fa-bolt me-2 text-warning"></i> إجراءات سريعة</h4>
                <div class="p-3 d-grid gap-2">
                    <a href="<?= APP_URL ?>/evaluations/create?employee_id=<?= (int)$employee->id ?>" class="btn btn-primary" style="border-radius:12px"><i class="fas fa-plus ms-1"></i> إنشاء تقييم جديد</a>
                    <a href="<?= APP_URL ?>/employees/<?= (int)$employee->id ?>/evaluations" class="btn btn-outline-primary" style="border-radius:12px"><i class="fas fa-history ms-1"></i> عرض السجل التقييمي</a>
                    <?php if(App\Core\Auth::userHasPermission('employees.edit')): ?><a href="<?= APP_URL ?>/employees/<?= (int)$employee->id ?>/edit" class="btn btn-light border" style="border-radius:12px"><i class="fas fa-edit ms-1"></i> تعديل البيانات</a><?php endif; ?>
                </div>
                <?php if(!empty($employee->nickname)): ?><div class="px-3 pb-3 small text-muted"><i class="fas fa-tag me-1"></i> اللقب: <strong class="text-dark"><?= htmlspecialchars($employee->nickname) ?></strong></div><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
