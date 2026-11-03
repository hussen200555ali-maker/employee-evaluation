<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
$data = $snapshot->snapshot_data;
$employee = $data['employee'];
$report = $data['report'];
$valueOrDash = static function ($value): string {
    return ($value === null || $value === '') ? '—' : htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$typeLabels = [
    'overall' => 'المتوسط العام',
    'annual' => 'التقييم السنوي',
    'periodic' => 'التقييم الدوري',
    'course' => 'تقييمات الدورات',
    'workshop' => 'تقييمات الورش',
];
$reportUrl = APP_URL . '/employees/profile/' . $employeeId . '/reports/' . (int)$snapshot->id;
?>
<div class="container py-4" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="<?= APP_URL ?>/employees/profile/<?= $employeeId ?>" class="text-decoration-none">ملف الموظف</a>
            <h1 class="h3 mt-2 mb-1">تقرير تقييم الموظف</h1>
            <div class="text-muted">
                رقم التقرير: <?= $valueOrDash($snapshot->report_number) ?>
                · تاريخ الإنشاء: <?= $valueOrDash($snapshot->created_at) ?>
                · أنشأه: <?= $valueOrDash($data['created_by']['username'] ?? '') ?>
            </div>
        </div>
        <a href="<?= APP_URL ?>/employees/profile/<?= $employeeId ?>" class="btn btn-outline-secondary">العودة إلى الملف</a>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-outline-primary" href="<?= $reportUrl ?>/export/pdf">تصدير PDF</a>
        <a class="btn btn-outline-primary" href="<?= $reportUrl ?>/export/xlsx">تصدير Excel</a>
        <a class="btn btn-outline-primary" href="<?= $reportUrl ?>/export/docx">تصدير Word</a>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-bold">بيانات الموظف وقت إنشاء التقرير</div>
        <dl class="row card-body mb-0">
            <dt class="col-sm-3">الاسم الكامل</dt><dd class="col-sm-9"><?= $valueOrDash($employee['full_name'] ?? '') ?></dd>
            <dt class="col-sm-3">الرقم الوظيفي</dt><dd class="col-sm-9"><?= $valueOrDash($employee['emp_code'] ?? '') ?></dd>
            <dt class="col-sm-3">القسم</dt><dd class="col-sm-9"><?= $valueOrDash($employee['department_name'] ?? '') ?></dd>
            <dt class="col-sm-3">الفرع</dt><dd class="col-sm-9"><?= $valueOrDash($employee['branch_name'] ?? '') ?></dd>
            <dt class="col-sm-3">المنصب</dt><dd class="col-sm-9"><?= $valueOrDash($employee['position_title'] ?? '') ?></dd>
        </dl>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ($typeLabels as $type => $label): ?>
            <?php $summary = $report['averages'][$type]; ?>
            <div class="col-sm-6 col-xl">
                <div class="card h-100"><div class="card-body">
                    <div class="small text-muted"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="h4 my-2"><?= $summary['average'] === null ? '—' : number_format((float)$summary['average'], 2) ?></div>
                    <div><?= $valueOrDash($summary['grade_label']) ?></div>
                </div></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="card-header fw-bold">التقييمات المحفوظة في النسخة</div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead><tr><th>التاريخ</th><th>النوع</th><th>القالب</th><th>الدرجة</th><th>التقدير</th></tr></thead>
                <tbody>
                <?php if ($report['records'] === []): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">لا توجد تقييمات مكتملة لهذا الموظف وقت إنشاء التقرير.</td></tr>
                <?php else: ?>
                    <?php foreach ($report['records'] as $record): ?>
                        <tr>
                            <td><?= $valueOrDash($record['evaluation_date'] ?? '') ?></td>
                            <td><?= $valueOrDash($record['evaluation_type_label'] ?? '') ?></td>
                            <td><?= $valueOrDash($record['template_name'] ?? '') ?></td>
                            <td><?= $record['grade_value'] === null ? '—' : number_format((float)$record['grade_value'], 2) ?></td>
                            <td><?= $valueOrDash($record['grade_label'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
