<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
// CD-028 UX active — profile with KPI cards + tabs

$valueOrDash = static function ($value): string {
    return ($value === null || $value === '') ? '—' : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
$typeLabels = [
    'overall' => 'المتوسط العام',
    'annual' => 'التقييم السنوي',
    'periodic' => 'التقييم الدوري',
    'course' => 'تقييمات الدورات',
    'workshop' => 'تقييمات الورش',
];
?>
<style>
/* v37 — تعديلات موضعية فقط بهوية التيل/الذهب (لا مساس بـ style.css العام) */
.emp-crumb{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;font-size:.82rem;color:#6f817b;margin-bottom:.85rem}
.emp-crumb a{color:#0d5e52;text-decoration:none;font-weight:700}
.emp-crumb a:hover{color:#c8943f}
.emp-crumb__sep{color:#c8943f;font-weight:900}
.emp-crumb__current{color:#16302b;font-weight:800}
.emp-avatar{width:74px;height:74px;border-radius:22px;flex-shrink:0;display:grid;place-items:center;background:linear-gradient(145deg,#147b6a,#093f37);border:2px solid #f3ca78;color:#fff;font-size:1.65rem;font-weight:900;box-shadow:0 10px 24px rgba(6,24,20,.28);letter-spacing:.02em}
.emp-sep{color:#f3ca78;padding:0 .25rem;font-weight:900}
.emp-profile-tabs{gap:.35rem;border-bottom:1px solid #e2eee8;flex-wrap:wrap}
.emp-profile-tabs .nav-link{border:0!important;border-radius:12px 12px 0 0;color:#34443d;font-weight:800;font-size:.88rem;padding:.7rem 1rem;background:transparent;display:inline-flex;align-items:center;gap:.5rem}
.emp-profile-tabs .nav-link i{color:#c8943f}
.emp-profile-tabs .nav-link:hover{color:#093f37;background:#f4f9f6}
.emp-profile-tabs .nav-link.active{color:#fff!important;background:linear-gradient(90deg,#093f37,#0d6658)!important;box-shadow:inset 0 -3px 0 0 #c8943f}
.emp-info-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}
.emp-info-item{display:flex;gap:.75rem;align-items:flex-start;padding:.75rem;border:1px solid #e2eee8;border-radius:14px;background:#fbfdfc}
.emp-info-icon{width:34px;height:34px;border-radius:11px;display:grid;place-items:center;flex-shrink:0;background:#eef7f3;color:#c8943f;font-size:.85rem}
.emp-info-label{font-size:.72rem;color:#6f817b;font-weight:700;margin-bottom:.15rem}
.emp-info-value{font-size:.9rem;color:#16302b;font-weight:800;word-break:break-word}
.emp-kpi{font-size:1.5rem!important;font-weight:900!important;color:#093f37!important;margin:.35rem 0!important;line-height:1.2!important}
.emp-note{border:1px solid #cfe3d9;border-right:4px solid #0d6658;background:#f4faf7;color:#34443d;border-radius:12px;padding:.8rem 1rem;font-size:.85rem;margin-bottom:1rem}
.emp-note--warn{border-color:#ecd9a8;border-right-color:#c8943f;background:#fdf8ec;color:#6b4c0c}
.emp-muted{color:#6f817b;font-size:.85rem}
.emp-btn-sm{min-height:34px;padding:.35rem .7rem;font-size:.78rem;border-radius:9px}
.emp-actions{display:inline-flex;gap:.5rem;flex-wrap:wrap;align-items:center}
@media (max-width:575.98px){.emp-info-grid{grid-template-columns:1fr}.emp-avatar{width:62px;height:62px;font-size:1.35rem}}
</style>

<div class="container py-3" dir="rtl">
    <nav class="emp-crumb" aria-label="مسار التنقل">
        <a href="<?= APP_URL ?>/employees"><i class="fas fa-users me-1"></i> الموظفون</a>
        <span class="emp-crumb__sep">›</span>
        <span class="emp-crumb__current">الملف التقييمي للموظف</span>
    </nav>

    <section class="app-hero" aria-labelledby="emp-profile-title">
        <div class="app-hero__left">
            <span class="app-eyebrow"><i class="fas fa-user-tie"></i> ملف الموظف التقييمي</span>
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="emp-avatar" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(implode('', array_map(fn($w) => mb_substr($w, 0, 1, 'UTF-8'), array_slice(explode(' ', trim($employee->full_name ?? '')), 0, 2))) ?: '؟', 'UTF-8')) ?></div>
                <div>
                    <h1 id="emp-profile-title"><?= $valueOrDash($employee->full_name) ?></h1>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="app-chip app-chip--gold" dir="ltr"><i class="fas fa-id-badge me-1"></i><?= $valueOrDash($employee->emp_code) ?></span>
                        <?php if($employee->is_active): ?>
                            <span class="app-chip app-chip--mint"><i class="fas fa-circle me-1" style="font-size:.5rem;vertical-align:middle"></i> نشط</span>
                        <?php else: ?>
                            <span class="app-chip app-chip--red">غير نشط</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <p>
                <i class="fas fa-briefcase me-1"></i><?= $valueOrDash($employee->position_title) ?>
                <span class="emp-sep">·</span>
                <i class="fas fa-sitemap me-1"></i><?= $valueOrDash($employee->department_name) ?>
                <span class="emp-sep">·</span>
                <i class="fas fa-code-branch me-1"></i><?= $valueOrDash($employee->branch_name) ?>
            </p>
            <a class="app-hero__cta" href="<?= APP_URL ?>/employees"><i class="fas fa-arrow-right"></i> العودة إلى الموظفين</a>
        </div>
        <div class="app-hero__stats" aria-label="إحصائيات">
            <div class="app-stat"><i class="fas fa-id-badge"></i><strong dir="ltr"><?= $valueOrDash($employee->emp_code) ?></strong><span>الرقم الوظيفي</span></div>
            <div class="app-stat"><i class="fas fa-briefcase"></i><strong><?= $valueOrDash($employee->position_title) ?></strong><span>المنصب</span></div>
            <div class="app-stat"><i class="fas fa-sitemap"></i><strong><?= $valueOrDash($employee->department_name) ?></strong><span>القسم</span></div>
        </div>
    </section>

    <ul class="nav nav-tabs emp-profile-tabs mb-4" id="employee-profile-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profile-data-tab" data-bs-toggle="tab" data-bs-target="#profile-data" type="button" role="tab" aria-controls="profile-data" aria-selected="true"><i class="fas fa-id-card"></i> البيانات</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-evaluations-tab" data-bs-toggle="tab" data-bs-target="#profile-evaluations" type="button" role="tab" aria-controls="profile-evaluations" aria-selected="false"><i class="fas fa-clipboard-check"></i> التقييمات</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-reports-tab" data-bs-toggle="tab" data-bs-target="#profile-reports" type="button" role="tab" aria-controls="profile-reports" aria-selected="false"><i class="fas fa-chart-line"></i> التقارير</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-actions-tab" data-bs-toggle="tab" data-bs-target="#profile-actions" type="button" role="tab" aria-controls="profile-actions" aria-selected="false"><i class="fas fa-bolt"></i> الإجراءات</button>
        </li>
    </ul>

    <div class="tab-content" id="employee-profile-content">
        <section class="tab-pane fade show active" id="profile-data" role="tabpanel" aria-labelledby="profile-data-tab" tabindex="0">
            <div class="app-card">
                <div class="app-card__header">
                    <h2 class="app-card__title"><i class="fas fa-id-card"></i> البيانات الوظيفية <span class="app-card__sub">بيانات التعيين والملف الشخصي</span></h2>
                    <span class="app-count"><i class="fas fa-list-check"></i> 11 عنصراً</span>
                </div>
                <div class="emp-info-grid p-3">
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-user"></i></div><div><div class="emp-info-label">الاسم الكامل</div><div class="emp-info-value"><?= $valueOrDash($employee->full_name) ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-id-badge"></i></div><div><div class="emp-info-label">الرقم الوظيفي</div><div class="emp-info-value" dir="ltr"><?= $valueOrDash($employee->emp_code) ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-tag"></i></div><div><div class="emp-info-label">اللقب</div><div class="emp-info-value"><?= $valueOrDash($employee->nickname) ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-envelope"></i></div><div><div class="emp-info-label">البريد الإلكتروني</div><div class="emp-info-value" dir="ltr"><?= $valueOrDash($employee->personal_email) ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-phone"></i></div><div><div class="emp-info-label">الهاتف</div><div class="emp-info-value" dir="ltr"><?= $valueOrDash($employee->phone) ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-building"></i></div><div><div class="emp-info-label">الفرع</div><div class="emp-info-value"><?= $valueOrDash($employee->branch_name) ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-layer-group"></i></div><div><div class="emp-info-label">القسم</div><div class="emp-info-value"><?= $valueOrDash($employee->department_name) ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-user-tie"></i></div><div><div class="emp-info-label">المنصب</div><div class="emp-info-value"><?= $valueOrDash($employee->position_title) ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-user-friends"></i></div><div><div class="emp-info-label">المدير المباشر</div><div class="emp-info-value"><?= $valueOrDash($employee->manager_name) ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-calendar-check"></i></div><div><div class="emp-info-label">تاريخ الالتحاق</div><div class="emp-info-value" dir="ltr"><?= $valueOrDash($employee->join_date) ?></div></div></div>
                    <div class="emp-info-item"><div class="emp-info-icon"><i class="fas fa-toggle-on"></i></div><div><div class="emp-info-label">الحالة</div><div class="emp-info-value"><?= $employee->is_active ? 'نشط' : 'غير نشط' ?></div></div></div>
                </div>
            </div>
        </section>

        <section class="tab-pane fade" id="profile-evaluations" role="tabpanel" aria-labelledby="profile-evaluations-tab" tabindex="0">
            <div class="app-card">
                <div class="app-card__header">
                    <h2 class="app-card__title"><i class="fas fa-clipboard-check"></i> سجل التقييمات المعتمدة والمكتملة</h2>
                </div>
                <div class="table-responsive">
                    <table class="tr-table align-middle mb-0">
                        <!-- CD-033/CD-037: جدول التقييمات مع نوع + اسم البرنامج -->
                        <thead><tr><th>التاريخ</th><th>النوع</th><th>القالب</th><th>الدرجة</th><th>التقدير</th><th><i class="fas fa-graduation-cap"></i> البرنامج</th></tr></thead>
                        <tbody>
                        <?php if ($report['records'] === []): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد تقييمات مكتملة لهذا الموظف.</td></tr>
                        <?php else: ?>
                            <?php foreach ($report['records'] as $record): ?>
                                <tr>
                                    <td><?= $valueOrDash($record->evaluation_date) ?></td>
                                    <td><?= $valueOrDash($record->evaluation_type_label) ?></td>
                                    <td><?= $valueOrDash($record->template_name) ?></td>
                                    <td><?= $record->grade_value === null ? '—' : number_format((float)$record->grade_value, 2) . ($report['grading_scale_max_value'] === null ? '' : ' / ' . number_format($report['grading_scale_max_value'], 2)) ?></td>
                                    <td><?= $valueOrDash($record->grade_label) ?></td>
                                    <td>
                                        <?php
                                        // CD-033/CD-037: عرض اسم البرنامج إن وُجد
                                        $ctxType = $record->context_type ?? '';
                                        $ctxId = $record->context_id ?? null;
                                        $programName = $record->program_name ?? null;
                                        if ($ctxType === 'course' && $ctxId):
                                            echo '<span class="app-chip app-chip--gold"><i class="fas fa-book"></i> ' . htmlspecialchars($programName ?: ('دورة #' . (int)$ctxId)) . '</span>';
                                        elseif ($ctxType === 'workshop' && $ctxId):
                                            echo '<span class="app-chip app-chip--blue"><i class="fas fa-tools"></i> ' . htmlspecialchars($programName ?: ('ورشة #' . (int)$ctxId)) . '</span>';
                                        else:
                                            echo '—';
                                        endif;
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="tab-pane fade" id="profile-reports" role="tabpanel" aria-labelledby="profile-reports-tab" tabindex="0">
            <div class="emp-note"><i class="fas fa-circle-info me-1" style="color:#c8943f"></i> المتوسطات من 100، وتُحسب من الدرجات الرقمية للتقييمات المعتمدة أو المكتملة المرتبطة مباشرة بالموظف. التقييمات الوصفية بلا درجة رقمية لا تدخل في المتوسط.</div>
            <form method="post" action="<?= APP_URL ?>/employees/profile/<?= (int)$employee->id ?>/reports" class="mb-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="app-btn-gold"><i class="fas fa-file-circle-plus ms-1"></i> إنشاء التقرير وحفظ نسخة ثابتة</button>
            </form>
            <div class="row g-3 mb-4">
                <?php foreach ($typeLabels as $type => $label): ?>
                    <?php $summary = $report['averages'][$type]; ?>
                    <div class="col-sm-6 col-xl">
                        <div class="app-card h-100">
                            <div class="p-3">
                                <div class="small emp-muted"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="emp-kpi"><?= $summary['average'] === null ? '—' : number_format($summary['average'], 2) . ' / 100' ?></div>
                                <div class="small"><?= $valueOrDash($summary['grade_label']) ?></div>
                                <div class="small emp-muted mt-2"><i class="fas fa-chart-bar me-1" style="color:#c8943f"></i><?= (int)$summary['scored_count'] ?> من <?= (int)$summary['evaluation_count'] ?> تقييم</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$report['grading_scale_available']): ?>
                <div class="emp-note emp-note--warn"><i class="fas fa-triangle-exclamation me-1"></i> سلم التقدير الخماسي غير متاح؛ لا يمكن تحويل الدرجات العددية إلى تقديرات معيارية.</div>
            <?php endif; ?>
            <div class="emp-note">
                <i class="fas fa-circle-info me-1" style="color:#c8943f"></i>
                لا تدخل التقييمات التاريخية الإجمالية للدورات والورش في هذه المؤشرات؛ فهي غير مرتبطة بمعرّف موظف، ولا يمكن إسنادها إليه بدقة.
            </div>

            <div class="app-card">
                <div class="app-card__header">
                    <h2 class="app-card__title"><i class="fas fa-calendar-years"></i> المتوسط حسب السنة</h2>
                </div>
                <div class="table-responsive">
                    <table class="tr-table mb-0">
                        <thead><tr><th>السنة</th><th>عدد التقييمات</th><th>المتوسط</th><th>التقدير الأقرب</th></tr></thead>
                        <tbody>
                        <?php if ($report['yearly'] === []): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">لا توجد بيانات سنوية حتى الآن.</td></tr>
                        <?php else: ?>
                            <?php foreach ($report['yearly'] as $year): ?>
                                <tr>
                                    <td><?= (int)$year['year'] ?></td>
                                    <td><?= (int)$year['evaluation_count'] ?></td>
                                    <td><?= $year['average'] === null ? '—' : number_format($year['average'], 2) . ' / 100' ?></td>
                                    <td><?= $valueOrDash($year['grade_label']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="app-card mt-4">
                <div class="app-card__header">
                    <h2 class="app-card__title"><i class="fas fa-archive"></i> سجل التقارير المحفوظة</h2>
                </div>
                <div class="table-responsive">
                    <table class="tr-table align-middle mb-0">
                        <thead><tr><th>رقم التقرير</th><th>تاريخ الإنشاء</th><th>أنشأه</th><th>التقرير والتصدير</th></tr></thead>
                        <tbody>
                        <?php if ($reportSnapshots === []): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">لم تُحفظ تقارير لهذا الموظف بعد.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reportSnapshots as $savedReport): ?>
                                <?php $reportUrl = APP_URL . '/employees/profile/' . (int)$employee->id . '/reports/' . (int)$savedReport->id; ?>
                                <tr>
                                    <td><?= htmlspecialchars($savedReport->report_number, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($savedReport->created_at, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($savedReport->created_by_username, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <div class="emp-actions">
                                            <a class="app-btn-gold emp-btn-sm" href="<?= $reportUrl ?>"><i class="fas fa-eye ms-1"></i> عرض</a>
                                            <a class="app-btn-ghost emp-btn-sm" href="<?= $reportUrl ?>/export/pdf"><i class="fas fa-file-pdf ms-1"></i> PDF</a>
                                            <a class="app-btn-ghost emp-btn-sm" href="<?= $reportUrl ?>/export/xlsx"><i class="fas fa-file-excel ms-1"></i> Excel</a>
                                            <a class="app-btn-ghost emp-btn-sm" href="<?= $reportUrl ?>/export/docx"><i class="fas fa-file-word ms-1"></i> Word</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="tab-pane fade" id="profile-actions" role="tabpanel" aria-labelledby="profile-actions-tab" tabindex="0">
            <div class="app-card">
                <div class="app-card__header">
                    <h2 class="app-card__title"><i class="fas fa-bolt"></i> الإجراءات المتاحة</h2>
                </div>
                <div class="p-3 d-flex flex-wrap gap-2">
                    <?php if (App\Core\Auth::userHasPermission('evaluations.create')): ?>
                        <a class="app-btn-gold" href="<?= APP_URL ?>/evaluations/create?employee_id=<?= (int)$employee->id ?>"><i class="fas fa-plus ms-1"></i> بدء تقييم جديد</a>
                    <?php endif; ?>
                    <?php if (App\Core\Auth::userHasPermission('employees.edit')): ?>
                        <a class="app-btn-ghost" href="<?= APP_URL ?>/employees/<?= (int)$employee->id ?>/edit"><i class="fas fa-edit ms-1"></i> تعديل بيانات الموظف</a>
                    <?php endif; ?>
                    <?php if (!App\Core\Auth::userHasPermission('evaluations.create') && !App\Core\Auth::userHasPermission('employees.edit')): ?>
                        <span class="emp-muted">لا توجد إجراءات إضافية متاحة لحسابك.</span>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
