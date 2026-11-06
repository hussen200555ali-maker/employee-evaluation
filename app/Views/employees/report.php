<?php
// CD-021 Final: WYSIWYG A4 report preview — no sidebar, no topbar
$data = $snapshot->snapshot_data;
$employee = $data['employee'];
$report = $data['report'];
$e = static function ($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$getVal = static function ($obj, $key, $default = '') {
    if (is_array($obj)) return $obj[$key] ?? $default;
    if (is_object($obj)) return $obj->{$key} ?? $default;
    return $default;
};
$reportUrl = APP_URL . '/employees/profile/' . $employeeId . '/reports/' . (int)$snapshot->id;
$logoPath = dirname(__DIR__, 2) . '/public/assets/images/logo.png';
// الأصول العامة تُخدَم من مجلد public، وبدونه يعطي المسار 500 ويظهر الشعار مكسوراً.
$logoUrl = APP_URL . '/public/assets/images/logo.png';
$records = $report['records'] ?? [];
$averages = $report['averages'] ?? [];

// Group records by type (conditional rendering)
$courseRecords = array_values(array_filter($records, fn($r) => ($getVal($r, 'evaluation_type') ?? '') === 'course'));
$workshopRecords = array_values(array_filter($records, fn($r) => ($getVal($r, 'evaluation_type') ?? '') === 'workshop'));
$annualRecords = array_values(array_filter($records, fn($r) => in_array($getVal($r, 'evaluation_type') ?? '', ['annual', 'periodic'])));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مسند تقييم — <?= $e($snapshot->report_number) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
<style>
/* === Page Background === */
body { margin:0; padding:0; background:#e2e8f0; font-family:'Cairo',sans-serif; }

/* === Floating Toolbar === */
.toolbar {
    position:fixed; top:0; left:0; right:0; z-index:1000;
    background:#0c2823; border-bottom:2px solid #c8943f;
    padding:10px 24px; display:flex; align-items:center; justify-content:space-between;
    box-shadow:0 2px 8px rgba(0,0,0,0.15);
}
.toolbar .tb-info { color:#c8943f; font-size:13px; font-weight:700; }
.toolbar .tb-info span { color:rgba(255,255,255,0.5); font-size:11px; font-weight:400; display:block; }
.toolbar .tb-actions { display:flex; gap:8px; }
.toolbar .tb-btn {
    padding:8px 16px; border-radius:6px; font-size:12px; font-weight:700;
    cursor:pointer; font-family:'Cairo'; border:none; text-decoration:none;
    display:inline-flex; align-items:center; gap:4px; transition:opacity 0.2s;
}
.toolbar .tb-btn:hover { opacity:0.85; }
.toolbar .tb-back { background:transparent; color:rgba(255,255,255,0.7); border:1px solid rgba(255,255,255,0.2); }
.toolbar .tb-pdf { background:#8b2222; color:#fff; }
.toolbar .tb-excel { background:#0d5e52; color:#fff; }
.toolbar .tb-word { background:#1e50a0; color:#fff; }
.toolbar .tb-print { background:#c8943f; color:#0c2823; }

/* === A4 Paper Container === */
.a4-paper {
    background:#ffffff; width:210mm; min-height:297mm;
    margin:70px auto 30px; padding:20mm;
    box-shadow:0 4px 12px rgba(0,0,0,0.12);
    box-sizing:border-box; position:relative;
}

/* === Report Content (matches PDF design) === */
* { box-sizing:border-box; }
.report-content { color:#333; font-size:11px; line-height:1.6; }

/* Cover section */
.cover-section { background:#0c2823; color:#c8943f; padding:30px 20px; text-align:center; border-radius:4px; margin-bottom:20px; }
.cover-section .cover-logo { max-height:60px; max-width:180px; margin-bottom:16px; }
.cover-section h1 { color:#c8943f; font-size:22px; margin:10px 0; }
.cover-section .emp-name { color:#fff; font-size:18px; margin:16px 0 4px; }
.cover-section .emp-meta { color:rgba(255,255,255,0.6); font-size:11px; }
.cover-section .report-no { color:#c8943f; font-size:12px; margin-top:12px; border-top:1px solid #c8943f; padding-top:8px; width:70%; margin-left:auto; margin-right:auto; }
.cover-section .confidential { border:1px solid #c8943f; color:#c8943f; padding:6px 16px; display:inline-block; margin-top:20px; font-size:10px; }

/* Basmala */
.basmala { text-align:center; font-size:15px; color:#0c2823; margin:16px 0 12px; font-weight:bold; }

/* Section title */
.section-title { background:#0c2823; color:#c8943f; padding:6px 12px; font-weight:bold; font-size:12px; margin:16px 0 6px; }

/* Employee data grid */
.emp-grid { display:grid; grid-template-columns:1fr 1fr; gap:0; margin-bottom:12px; }
.emp-grid .emp-cell { padding:6px 10px; border-bottom:1px solid #eee; }
.emp-grid .emp-cell .lbl { font-size:9px; color:#6c757d; }
.emp-grid .emp-cell .val { font-size:11px; font-weight:600; color:#333; }

/* Tables */
table.data { width:100%; border-collapse:collapse; margin:6px 0 10px; }
table.data th { background:#0c2823; color:#fff; padding:6px; font-size:10px; text-align:right; font-weight:700; }
table.data td { padding:5px 6px; border:1px solid #ddd; font-size:10px; text-align:right; }
table.data tr:nth-child(even) { background:#f5f0e8; }
table.data tr.footer-row { background:#f0e0c8; font-weight:bold; }

/* Signature table */
table.sig { width:100%; border-collapse:collapse; margin-top:16px; }
table.sig th { background:#f5f0e8; color:#0c2823; padding:8px; text-align:center; font-size:10px; border:1px solid #c8943f; }
table.sig td { padding:14px 8px; text-align:center; font-size:9px; color:#999; border:1px solid #ddd; height:30px; }

/* Empty state */
.empty-state { text-align:center; padding:30px; color:#999; font-size:13px; }

/* Report footer */
.report-footer { text-align:center; font-size:8px; color:#999; margin-top:20px; padding-top:10px; border-top:1px solid #eee; }

/* === Print CSS === */
@media print {
    body { background:#fff; }
    .toolbar { display:none !important; }
    .a4-paper { margin:0; box-shadow:none; width:auto; min-height:auto; padding:0; }
}
</style>
</head>
<body>

<!-- === Floating Toolbar === -->
<div class="toolbar">
    <div class="tb-info">
        <?= $e($snapshot->report_number) ?>
        <span><?= $e($snapshot->created_at) ?> · <?= $e($data['created_by']['username'] ?? '') ?></span>
    </div>
    <div class="tb-actions">
        <a class="tb-btn tb-back" href="<?= APP_URL ?>/reports/archive">↩ السجل</a>
        <a class="tb-btn tb-back" href="<?= APP_URL ?>/employees/profile/<?= $employeeId ?>">↩ الملف</a>
        <a class="tb-btn tb-pdf" href="<?= $reportUrl ?>/export/pdf">📄 PDF</a>
        <a class="tb-btn tb-excel" href="<?= $reportUrl ?>/export/xlsx">📊 Excel</a>
        <a class="tb-btn tb-word" href="<?= $reportUrl ?>/export/docx">📝 Word</a>
        <button class="tb-btn tb-print" onclick="window.print()">🖨 طباعة</button>
    </div>
</div>

<!-- === A4 Paper === -->
<div class="a4-paper">
    <div class="report-content">

        <!-- Cover -->
        <div class="cover-section">
            <img class="cover-logo" src="<?= $e($logoUrl) ?>" alt="شعار">
            <h1>مسند تقييم أداء موظف</h1>
            <div class="emp-name"><?= $e($employee['full_name'] ?? '') ?></div>
            <div class="emp-meta"><?= $e($employee['emp_code'] ?? '') ?> · <?= $e($employee['department_name'] ?? '') ?> · <?= $e($employee['branch_name'] ?? '') ?></div>
            <div class="report-no">رقم المسند: <?= $e($snapshot->report_number) ?><br>تاريخ الإصدار: <?= $e($snapshot->created_at) ?></div>
            <div class="confidential">سرّي — للاستخدام الداخلي فقط</div>
        </div>

        <!-- Basmala -->
        <div class="basmala">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</div>

        <!-- Employee Data -->
        <div class="section-title">بيانات الموظف</div>
        <div class="emp-grid">
            <?php
            $fields = [
                'الاسم' => $employee['full_name'] ?? '', 'الرقم الوظيفي' => $employee['emp_code'] ?? '',
                'القسم' => $employee['department_name'] ?? '', 'الفرع' => $employee['branch_name'] ?? '',
                'المنصب' => $employee['position_title'] ?? '', 'المدير المباشر' => $employee['manager_name'] ?? '',
                'البريد' => $employee['email'] ?? '', 'الهاتف' => $employee['phone'] ?? '',
                'تاريخ الالتحاق' => $employee['hire_date'] ?? '', 'الحالة' => $employee['status'] ?? '',
            ];
            foreach ($fields as $label => $value):
            ?>
                <div class="emp-cell"><div class="lbl"><?= $e($label) ?></div><div class="val"><?= $e($value) ?></div></div>
            <?php endforeach; ?>
        </div>

        <!-- Courses Section (Conditional) -->
        <?php if (!empty($courseRecords)): ?>
        <div class="section-title">الدورات التدريبية</div>
        <p style="font-size:9px;color:#6c757d;margin:3px 0;">عدد الدورات: (<?= count($courseRecords) ?>)</p>
        <table class="data"><thead><tr><th>اسم الدورة</th><th>التاريخ</th><th>التقدير</th></tr></thead><tbody>
            <?php foreach ($courseRecords as $rec): ?>
            <tr><td><?= $e($getVal($rec, 'template_name')) ?></td><td><?= $e($getVal($rec, 'evaluation_date')) ?></td><td><?= $e($getVal($rec, 'grade_label')) ?></td></tr>
            <?php endforeach; ?>
            <tr class="footer-row"><td colspan="2" style="text-align:left;">التقدير العام</td><td><?= $e($averages['course']['grade_label'] ?? '—') ?></td></tr>
        </tbody></table>
        <?php endif; ?>

        <!-- Workshops Section (Conditional) -->
        <?php if (!empty($workshopRecords)): ?>
        <div class="section-title">ورش العمل</div>
        <p style="font-size:9px;color:#6c757d;margin:3px 0;">عدد الورش: (<?= count($workshopRecords) ?>)</p>
        <table class="data"><thead><tr><th>اسم الورشة</th><th>التاريخ</th><th>التقدير</th></tr></thead><tbody>
            <?php foreach ($workshopRecords as $rec): ?>
            <tr><td><?= $e($getVal($rec, 'template_name')) ?></td><td><?= $e($getVal($rec, 'evaluation_date')) ?></td><td><?= $e($getVal($rec, 'grade_label')) ?></td></tr>
            <?php endforeach; ?>
            <tr class="footer-row"><td colspan="2" style="text-align:left;">التقدير العام</td><td><?= $e($averages['workshop']['grade_label'] ?? '—') ?></td></tr>
        </tbody></table>
        <?php endif; ?>

        <!-- Annual Evaluations Section (Conditional) -->
        <?php if (!empty($annualRecords)): ?>
        <div class="section-title">التقييمات السنوية</div>
        <p style="font-size:9px;color:#6c757d;margin:3px 0;">عدد التقييمات: (<?= count($annualRecords) ?>)</p>
        <table class="data"><thead><tr><th>السنة</th><th>القالب</th><th>الدرجة / 100</th><th>التقدير</th></tr></thead><tbody>
            <?php foreach ($annualRecords as $rec):
                $score = $getVal($rec, 'final_score') ?? $getVal($rec, 'grade_value');
                $year = $getVal($rec, 'evaluation_date') ? date('Y', strtotime($getVal($rec, 'evaluation_date'))) : '';
            ?>
            <tr><td><?= $e($year) ?></td><td><?= $e($getVal($rec, 'template_name')) ?></td><td><?= $score !== null && $score !== '' ? number_format((float)$score, 2) : '—' ?></td><td><?= $e($getVal($rec, 'grade_label')) ?></td></tr>
            <?php endforeach; ?>
            <tr class="footer-row"><td colspan="3" style="text-align:left;">التقدير العام للتقييمات السنوية</td><td><?= $e($averages['annual']['grade_label'] ?? '—') ?></td></tr>
        </tbody></table>
        <?php endif; ?>

        <!-- Empty State -->
        <?php if (empty($courseRecords) && empty($workshopRecords) && empty($annualRecords)): ?>
        <div class="empty-state">لا توجد تقييمات مسجلة لهذا الموظف حتى الآن.</div>
        <?php endif; ?>

        <!-- Signatures -->
        <div class="section-title">التوقيعات والاعتماد</div>
        <table class="sig"><thead><tr><th>الموظف</th><th>المدير المباشر</th><th>مدير الموارد البشرية</th></tr></thead><tbody>
            <tr><td>الاسم: ___________</td><td>الاسم: ___________</td><td>الاسم: ___________</td></tr>
            <tr><td>التوقيع: _________</td><td>التوقيع: _________</td><td>التوقيع: _________</td></tr>
            <tr><td>التاريخ: __/__/__</td><td>التاريخ: __/__/__</td><td>التاريخ: __/__/__</td></tr>
        </tbody></table>

        <!-- Report Footer -->
        <div class="report-footer">
            تم إنشاء هذا المسند آلياً بواسطة نظام تقييم أداء الموظفين · رقم <?= $e($snapshot->report_number) ?>
        </div>

    </div>
</div>

</body>
</html>
