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
<div class="container py-4" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="<?= APP_URL ?>/employees" class="text-decoration-none">الموظفون</a>
            <h1 class="h3 mt-2 mb-1"><?= $valueOrDash($employee->full_name) ?></h1>
            <div class="text-muted">
                الرقم الوظيفي: <?= $valueOrDash($employee->emp_code) ?>
                · <?= $valueOrDash($employee->position_title) ?>
                · <?= $valueOrDash($employee->department_name) ?>
            </div>
        </div>
        <a href="<?= APP_URL ?>/employees" class="btn btn-outline-secondary">العودة إلى الموظفين</a>
    </div>

    <ul class="nav nav-tabs mb-4" id="employee-profile-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profile-data-tab" data-bs-toggle="tab" data-bs-target="#profile-data" type="button" role="tab" aria-controls="profile-data" aria-selected="true">البيانات</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-evaluations-tab" data-bs-toggle="tab" data-bs-target="#profile-evaluations" type="button" role="tab" aria-controls="profile-evaluations" aria-selected="false">التقييمات</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-reports-tab" data-bs-toggle="tab" data-bs-target="#profile-reports" type="button" role="tab" aria-controls="profile-reports" aria-selected="false">التقارير</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-actions-tab" data-bs-toggle="tab" data-bs-target="#profile-actions" type="button" role="tab" aria-controls="profile-actions" aria-selected="false">الإجراءات</button>
        </li>
    </ul>

    <div class="tab-content" id="employee-profile-content">
        <section class="tab-pane fade show active" id="profile-data" role="tabpanel" aria-labelledby="profile-data-tab" tabindex="0">
            <div class="card">
                <div class="card-header fw-bold">البيانات الوظيفية</div>
                <dl class="row card-body mb-0">
                    <dt class="col-sm-3">الاسم الكامل</dt><dd class="col-sm-9"><?= $valueOrDash($employee->full_name) ?></dd>
                    <dt class="col-sm-3">الرقم الوظيفي</dt><dd class="col-sm-9"><?= $valueOrDash($employee->emp_code) ?></dd>
                    <dt class="col-sm-3">اللقب</dt><dd class="col-sm-9"><?= $valueOrDash($employee->nickname) ?></dd>
                    <dt class="col-sm-3">البريد الإلكتروني</dt><dd class="col-sm-9"><?= $valueOrDash($employee->personal_email) ?></dd>
                    <dt class="col-sm-3">الهاتف</dt><dd class="col-sm-9"><?= $valueOrDash($employee->phone) ?></dd>
                    <dt class="col-sm-3">الفرع</dt><dd class="col-sm-9"><?= $valueOrDash($employee->branch_name) ?></dd>
                    <dt class="col-sm-3">القسم</dt><dd class="col-sm-9"><?= $valueOrDash($employee->department_name) ?></dd>
                    <dt class="col-sm-3">المنصب</dt><dd class="col-sm-9"><?= $valueOrDash($employee->position_title) ?></dd>
                    <dt class="col-sm-3">المدير المباشر</dt><dd class="col-sm-9"><?= $valueOrDash($employee->manager_name) ?></dd>
                    <dt class="col-sm-3">تاريخ الالتحاق</dt><dd class="col-sm-9"><?= $valueOrDash($employee->join_date) ?></dd>
                    <dt class="col-sm-3">الحالة</dt><dd class="col-sm-9"><?= $employee->is_active ? 'نشط' : 'غير نشط' ?></dd>
                </dl>
            </div>
        </section>

        <section class="tab-pane fade" id="profile-evaluations" role="tabpanel" aria-labelledby="profile-evaluations-tab" tabindex="0">
            <div class="card">
                <div class="card-header fw-bold">سجل التقييمات المعتمدة والمكتملة</div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead><tr><th>التاريخ</th><th>النوع</th><th>القالب</th><th>الدرجة</th><th>التقدير</th></tr></thead>
                        <tbody>
                        <?php if ($report['records'] === []): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">لا توجد تقييمات مكتملة لهذا الموظف.</td></tr>
                        <?php else: ?>
                            <?php foreach ($report['records'] as $record): ?>
                                <tr>
                                    <td><?= $valueOrDash($record->evaluation_date) ?></td>
                                    <td><?= $valueOrDash($record->evaluation_type_label) ?></td>
                                    <td><?= $valueOrDash($record->template_name) ?></td>
                                    <td><?= $record->grade_value === null ? '—' : number_format((float)$record->grade_value, 2) . ($report['grading_scale_max_value'] === null ? '' : ' / ' . number_format($report['grading_scale_max_value'], 2)) ?></td>
                                    <td><?= $valueOrDash($record->grade_label) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="tab-pane fade" id="profile-reports" role="tabpanel" aria-labelledby="profile-reports-tab" tabindex="0">
            <p class="text-muted">المتوسطات من 100، وتُحسب من الدرجات الرقمية للتقييمات المعتمدة أو المكتملة المرتبطة مباشرة بالموظف. التقييمات الوصفية بلا درجة رقمية لا تدخل في المتوسط.</p>
            <form method="post" action="<?= APP_URL ?>/employees/profile/<?= (int)$employee->id ?>/reports" class="mb-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-primary">إنشاء التقرير وحفظ نسخة ثابتة</button>
            </form>
            <div class="row g-3 mb-4">
                <?php foreach ($typeLabels as $type => $label): ?>
                    <?php $summary = $report['averages'][$type]; ?>
                    <div class="col-sm-6 col-xl">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="small text-muted"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="h4 my-2"><?= $summary['average'] === null ? '—' : number_format($summary['average'], 2) . ' / 100' ?></div>
                                <div class="small"><?= $valueOrDash($summary['grade_label']) ?></div>
                                <div class="small text-muted mt-2"><?= (int)$summary['scored_count'] ?> من <?= (int)$summary['evaluation_count'] ?> تقييم</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$report['grading_scale_available']): ?>
                <div class="alert alert-warning">سلم التقدير الخماسي غير متاح؛ لا يمكن تحويل الدرجات العددية إلى تقديرات معيارية.</div>
            <?php endif; ?>
            <div class="alert alert-info">
                لا تدخل التقييمات التاريخية الإجمالية للدورات والورش في هذه المؤشرات؛ فهي غير مرتبطة بمعرّف موظف، ولا يمكن إسنادها إليه بدقة.
            </div>

            <div class="card">
                <div class="card-header fw-bold">المتوسط حسب السنة</div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
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

            <div class="card mt-4">
                <div class="card-header fw-bold">سجل التقارير المحفوظة</div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
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
                                        <a class="btn btn-sm btn-outline-primary" href="<?= $reportUrl ?>">عرض</a>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= $reportUrl ?>/export/pdf">PDF</a>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= $reportUrl ?>/export/xlsx">Excel</a>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= $reportUrl ?>/export/docx">Word</a>
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
            <div class="card">
                <div class="card-header fw-bold">الإجراءات المتاحة</div>
                <div class="card-body d-flex flex-wrap gap-2">
                    <?php if (App\Core\Auth::userHasPermission('evaluations.create')): ?>
                        <a class="btn btn-primary" href="<?= APP_URL ?>/evaluations/create?employee_id=<?= (int)$employee->id ?>">بدء تقييم جديد</a>
                    <?php endif; ?>
                    <?php if (App\Core\Auth::userHasPermission('employees.edit')): ?>
                        <a class="btn btn-outline-primary" href="<?= APP_URL ?>/employees/<?= (int)$employee->id ?>/edit">تعديل بيانات الموظف</a>
                    <?php endif; ?>
                    <?php if (!App\Core\Auth::userHasPermission('evaluations.create') && !App\Core\Auth::userHasPermission('employees.edit')): ?>
                        <span class="text-muted">لا توجد إجراءات إضافية متاحة لحسابك.</span>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>