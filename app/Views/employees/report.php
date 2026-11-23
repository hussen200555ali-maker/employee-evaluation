<?php
// CD-028 UX active — WYSIWYG A4 report preview
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
// تواريخ الوثيقة (ميلادي + هجري umalqura) — نفس دالة masthead تصدير PDF.
$dates = \App\Services\ReportExportService::getDocumentDates($snapshot->created_at ?? '');
// (حُذف مسار الشعار: لم يعد الغلاف الداكن موجوداً — الترويسة تعتمد أيقونة fa-file-signature)
$records = $report['records'] ?? [];
$averages = $report['averages'] ?? [];

// CD-043: إعادة احتساب المتوسطات حسب target_type (للنسخ القديمة/الانتقالية).
$deriveType = static function ($r) use ($getVal): string {
    $target = $getVal($r, 'target_type', null);
    if ($target === 'employee') return 'employee';
    if ($target === 'employee_in_course' || $target === 'course') return 'course';
    if ($target === 'employee_in_workshop' || $target === 'workshop') return 'workshop';
    if ($target === 'skill') return 'skill';
    $legacy = (string)($getVal($r, 'evaluation_type', 'annual') ?: 'annual');
    if ($legacy === '' || $legacy === 'annual') return 'employee';
    return $legacy;
};
$scaleDb = \App\Core\Database::getInstance();
$scaleDb->query("SELECT grade_label, min_value, max_value, numeric_value FROM grading_scales WHERE scale_name = :scale_name AND is_active = 1 ORDER BY sort_order, min_value, id");
$scaleDb->bind(':scale_name', 'السلم الخماسي');
$scale = $scaleDb->fetchAll();
$engine = new \App\Services\AveragesEngine();
$objects = [];
foreach ($records as $record) {
    $obj = new stdClass();
    if (is_array($record)) {
        foreach ($record as $key => $value) {
            $obj->{$key} = $value;
        }
    } elseif (is_object($record)) {
        foreach (get_object_vars($record) as $key => $value) {
            $obj->{$key} = $value;
        }
    }
    $obj->evaluation_type = $deriveType($record);
    $objects[] = $obj;
}
$averages = [];
foreach (['overall', 'employee', 'annual', 'periodic', 'course', 'workshop', 'skill'] as $type) {
    $matching = $type === 'overall'
        ? $objects
        : array_values(array_filter($objects, static fn($record): bool => $record->evaluation_type === $type));
    $averages[$type] = $engine->summarize($matching, $scale);
}

// Group records by type (conditional rendering)
// CD-040.6 (a2'): derive from target_type (not raw evaluation_type).
$typeOf = static function ($r) use ($getVal) {
    $target = $getVal($r, 'target_type', null);
    // BUG-H: individual general evaluations are their own type, never "annual".
    if ($target === 'employee') return 'employee';
    if ($target === 'employee_in_course' || $target === 'course') return 'course';
    if ($target === 'employee_in_workshop' || $target === 'workshop') return 'workshop';
    if ($target === 'skill') return 'skill';
    $legacy = (string)($getVal($r, 'evaluation_type', 'annual') ?: 'annual');
    // النسخ القديمة بدون target_type: "annual" (fallback قديم) يُعامل كفردي.
    if ($legacy === '' || $legacy === 'annual') return 'employee';
    return $legacy;
};
$courseRecords = array_values(array_filter($records, fn($r) => $typeOf($r) === 'course'));
$workshopRecords = array_values(array_filter($records, fn($r) => $typeOf($r) === 'workshop'));
$individualRecords = array_values(array_filter($records, fn($r) => $typeOf($r) === 'employee'));
$annualRecords = array_values(array_filter($records, fn($r) => in_array($typeOf($r), ['annual', 'periodic'])));
$skillRecords = array_values(array_filter($records, fn($r) => $typeOf($r) === 'skill'));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تقرير التقييم الشامل للموظف — <?= $e($snapshot->report_number) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/public/libs/fontawesome/css/all.min.css">
<?php require_once APP_ROOT . '/app/Views/evaluations/_document_styles.php'; ?>
<style>
/* === Page Background === */
body { margin:0; padding:0; background:#e2e8f0; font-family:'Cairo',sans-serif; }

/* === Floating Toolbar (navy/gold — نفس نظام الوثيقة) === */
.toolbar {
    position:fixed; top:0; left:0; right:0; z-index:1000;
    background:var(--navy); border-bottom:2px solid var(--gold);
    padding:10px 24px; display:flex; align-items:center; justify-content:space-between;
    box-shadow:0 2px 10px rgba(15,42,68,0.35);
}
.toolbar .tb-info { color:var(--gold); font-size:13px; font-weight:700; }
.toolbar .tb-info span { color:rgba(255,255,255,0.55); font-size:11px; font-weight:400; display:block; }
.toolbar .tb-actions { display:flex; gap:8px; }
.toolbar .tb-btn {
    padding:8px 16px; border-radius:8px; font-size:12px; font-weight:700;
    cursor:pointer; font-family:'Cairo'; border:none; text-decoration:none;
    display:inline-flex; align-items:center; gap:4px; transition:all 0.2s;
}
.toolbar .tb-btn:hover { transform:translateY(-1px); opacity:0.95; }
.toolbar .tb-back { background:transparent; color:rgba(255,255,255,0.7); border:1px solid rgba(255,255,255,0.22); }
.toolbar .tb-pdf { background:#8b2222; color:#fff; }
.toolbar .tb-excel { background:#0d5e52; color:#fff; }
.toolbar .tb-word { background:#1e50a0; color:#fff; }
.toolbar .tb-print { background:var(--gold); color:var(--navy); }

/* === A4 Official Report Page — كلغة وثيقة المرفقات (flex عمودي، هوامش داخلية) === */
.a4-container.report-wrap { margin:64px auto 0; padding:0; }
.report-page {
    background:#ffffff; width:210mm; min-height:297mm; margin:0 auto 30px;
    box-shadow:0 12px 48px rgba(15,42,68,.12), 0 2px 12px rgba(0,0,0,.06);
    border-radius:3px; padding:0; box-sizing:border-box; position:relative;
    display:flex; flex-direction:column; overflow:visible;
}
.report-page::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background: linear-gradient(90deg, #0f2a44 0%, #c5a880 50%, #0f2a44 100%); border-radius:3px 3px 0 0; }
.doc-employee { font-size:16pt; font-weight:800; color:#000; line-height:1.25; margin-top:4px; }

/* === Report Content (is inside .doc-body = padding 18/22 من shared styles) === */
* { box-sizing:border-box; }
.report-content { flex:1 1 auto; color:#1a2332; font-size:11px; line-height:1.7; }
.section-head { margin:16px 0 10px; }
.section-head:first-of-type { margin-top:14px; }
.section-count { font-size:9px; color:var(--muted); margin:2px 0 8px; }
.program-grid.cols-4 { grid-template-columns:repeat(4,1fr); }

/* Employee data — gold program-card (unified with document language) */
.emp-card { margin-bottom:14px; }

/* Tables — document language */
table.data { width:100%; border-collapse:collapse; margin:6px 0 12px; border:1px solid var(--border); border-radius:8px; overflow:hidden; font-size:10px; background:#fff; }
table.data th { background:var(--navy); color:#fff; padding:9px 10px; font-size:11.5px; text-align:center; font-weight:800; letter-spacing:.2px; border-bottom:2px solid var(--gold); }
table.data td { padding:8px 10px; border:1px solid #e8eaed; font-size:11.5px; text-align:center; color:#1a2332; font-weight:600; }
table.data tr:nth-child(even) { background:#fbf9f3; }
table.data tr.footer-row td { background:var(--gold-light); color:var(--navy); font-weight:800; border-top:2px solid var(--gold); text-align:right; }

/* Signature table — document language */
table.sig { width:100%; border-collapse:collapse; margin:16px 0 14mm; border:1px solid var(--gold-light); }
table.sig th { background:var(--navy); color:#fff; padding:8px; text-align:center; font-size:10px; border:1px solid var(--navy); border-bottom:2px solid var(--gold); }
table.sig td { padding:16px 10px; text-align:center; font-size:9.5px; color:var(--muted); border:1px solid #e8eaed; height:32px; background:#fff; }
table.sig tr:nth-child(even) td { background:#fbf9f3; }

/* Basmala style comes from _document_styles.php (.header-titles .basmala) */

/* Section title */
.section-title { background:#0c2823; color:#c8943f; padding:6px 12px; font-weight:bold; font-size:12px; margin:16px 0 6px; }

/* Employee data grid styles removed in v2 — replaced by .program-card (document language) */

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

/* Empty state — document language */
.empty-state { text-align:center; padding:34px 18px; color:var(--muted); font-size:13px; border:1px dashed var(--gold-light); background:#fdfbf6; border-radius:8px; margin:10px 0; }

/* === Official footer (v5): ثلاثي متماثل + رفعه عن الحافة === */
.report-page .doc-footer { display:grid; grid-template-columns:1fr auto 1fr; align-items:center; gap:10px; margin-bottom:7mm; padding:12px 22px; }
.report-page .doc-footer .foot-right { display:flex; align-items:center; gap:6px; justify-self:start; }
.report-page .doc-footer .foot-center { text-align:center; color:var(--navy); font-weight:800; }
.report-page .doc-footer .foot-center strong { color:var(--navy); font-weight:900; border-bottom:2px solid var(--gold); padding-bottom:1px; }
.report-page .doc-footer .foot-left { text-align:left; font-size:10px; color:var(--muted); justify-self:end; }

/* === Attachments Section === */
#attachment-section-title {     margin: -26px 562px 6px; }
/* === Print CSS — WYSIWYG: الطباعة = الشاشة تماماً (A4، مقياس 1:1، الألوان تُطبع) === */
@page { size: A4; margin: 0; }
@media print {
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { background:#fff; }
    .toolbar { display:none !important; }
    .a4-container { padding:0; background:#fff; margin:0; }
    .a4-container.report-wrap { margin:0; padding:0; } /* تغلب على قاعدة الشاشة بالخصوصية نفسها (0,2,0) */
    .report-page { margin:0 auto; box-shadow:none; width:210mm; min-height:0; padding:0; border-radius:0; }
    .report-page .doc-footer { margin-bottom:0; }
    table.sig { margin:16px 0 8mm; }
    .attachment-page { page-break-before: always; break-before: page; }
    .attachment-title { margin:0 0 8px !important; page-break-after: avoid; }
    .doc-header, .doc-footer, table.sig, .program-card, .section-head { page-break-inside: avoid; }
    .section-head { page-break-after: avoid; }
    table.data thead { display: table-header-group; }
    table.data tr { page-break-inside: avoid; }

    /* === v10: ضغط طباعي للمرفق (صفحة واحدة لكل مرفق ما أمكن) — خاص بهذه الصفحة فقط === */
    .doc-body { padding: 10px 16px 6px; }
    .doc-header { padding: 12px 18px 10px; }
    .header-emblem { width: 46px; height: 46px; }
    .section { margin-bottom: 10px; }
    .section-head { margin: 10px 0 6px; padding-bottom: 5px; }
    .section-num { width: 24px; height: 24px; font-size: 11px; }
    .section-head h2 { font-size: 12px; }
    .info-cell, .program-cell { padding: 7px 10px; }
    .info-card, .criterion-card, .program-card { margin-bottom: 8px; }
    .criterion-card, .info-card, .evaluator-card { page-break-inside: avoid; }
    .criterion-head { padding: 7px 12px; }
    .criterion-head h3 { font-size: 11.5px; }
    .criterion-head .count { font-size: 10px; padding: 2px 8px; }
    .eval-table { font-size: 10.5px; }
    .eval-table thead th { padding: 6px 8px; font-size: 10px; }
    .eval-table tbody td { padding: 6px 10px; font-size: 10.5px; }
    .badge { font-size: 10px; min-width: 56px; padding: 4px 8px; }
    .evaluator-card { padding: 12px; gap: 8px; }
    .eval-person .val { font-size: 11.5px; }
    .signature-area { margin-top: 10px; gap: 8px; padding-top: 8px; }
    .sig-box { min-height: 52px; }
    .sig-line { margin: 10px auto 5px; }
    .doc-footer { padding: 6px 18px; }

    /* === v11: تثبيت صف التقدير العام مع جدوله + انفراجة علوية بسيطة عند أي امتداد === */
    table.data tr.footer-row { page-break-inside: avoid; page-break-before: avoid; }
    table.sig tr { page-break-inside: avoid; page-break-before: avoid; }
    .section-head { margin: 14px 0 6px; padding-bottom: 5px; page-break-inside: avoid; break-inside: avoid; page-break-after: avoid; break-after: avoid; }
    .section-count { page-break-after: avoid; break-after: avoid; }

    /* === v12: المرفقات الطويلة تُصفَّح بأمان (لا قص صامت) — نطاق صفحة المعاينة فقط === */
    .attachment-page .a4-page { overflow: visible; min-height: 0; height: auto; box-shadow: none; border: none; width: auto; }
    .attachment-page .criterion-card,
    .attachment-page .info-card,
    .attachment-page .program-card,
    .attachment-page .evaluator-card,
    .attachment-page .signature-area,
    .attachment-page .doc-header,
    .attachment-page .doc-footer,
    .attachment-page .program-grid,
    .attachment-page .info-grid,
    .attachment-page .program-cell { page-break-inside: avoid; break-inside: avoid; }
    .attachment-page .eval-table thead { display: table-header-group; }
    .attachment-page .eval-table tr { page-break-inside: avoid; break-inside: avoid; }
    .attachment-page .badge { page-break-inside: avoid; break-inside: avoid; }
    .attachment-page .section-head { page-break-inside: avoid; break-inside: avoid; page-break-after: avoid; break-after: avoid; }
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
        <a class="tb-btn tb-back" href="<?= APP_URL ?>/reports/archive"><i class="fas fa-undo"></i> السجل</a>
        <a class="tb-btn tb-back" href="<?= APP_URL ?>/employees/profile/<?= $employeeId ?>"><i class="fas fa-user"></i> الملف</a>
        <a class="tb-btn tb-pdf" href="<?= $reportUrl ?>/export/pdf"><i class="fas fa-file-pdf"></i> PDF</a>
        <a class="tb-btn tb-excel" href="<?= $reportUrl ?>/export/xlsx"><i class="fas fa-file-excel"></i> Excel</a>
        <a class="tb-btn tb-word" href="<?= $reportUrl ?>/export/docx"><i class="fas fa-file-word"></i> Word</a>
        <button class="tb-btn tb-print" onclick="window.print()"><i class="fas fa-print"></i> طباعة</button>
    </div>
</div>

<!-- === A4 Official Document Page === -->
<div class="a4-container report-wrap">
  <div class="report-page">
    <div class="doc-body report-content">

        <!-- Masthead: وثيقة رسمية مخصصة للتقرير -->
        <header class="doc-header">
            <div class="header-emblem"><i class="fas fa-file-signature"></i></div>
            <div class="header-titles">
                <p class="company">اسم الشركة — وحدة الموارد البشرية</p>
                <p class="basmala">بسم الله الرحمن الرحيم</p>
                <h1 class="doc-title">تقرير التقييم الشامل للموظف</h1>
                <div class="doc-employee"><?= $e($employee['full_name'] ?? '') ?></div>
            </div>
            <div class="header-meta">
                <p><strong>التاريخ:</strong> <?= $e($dates['gregorian']) ?></p>
                <p><strong>الموافق:</strong> <?= $e($dates['hijri']) ?></p>
                <p><strong>اليوم:</strong> <?= $e($dates['day']) ?></p>
                <div style="margin-top:4px; padding-top:4px; border-top:1px dashed #e2e8f0; font-size:10px; color:#64748b;"><i class="fas fa-hashtag" style="color:var(--gold);"></i> رقم التقرير: <?= $e($snapshot->report_number) ?></div>
            </div>
        </header>

        <!-- Employee Data -->
        <?php $_s = 0; ?>
        <div class="section-head"><div class="section-num"><?= ++$_s ?></div><h2>بيانات الموظف</h2><div class="line"></div></div>
        <div class="program-card emp-card">
            <div class="program-grid cols-4">
                <?php
                // بيانات الموظف الأساسية (بطاقة الوثيقة الذهبية — 4 خانات في صف واحد).
                $fields = [
                    'الاسم' => [$employee['full_name'] ?? '', 'fas fa-user'],
                    'الرقم الوظيفي' => [$employee['emp_code'] ?? '', 'fas fa-hashtag'],
                    'الفرع' => [$employee['branch_name'] ?? '', 'fas fa-building'],
                    'القسم' => [$employee['department_name'] ?? '', 'fas fa-sitemap'],
                ];
                foreach ($fields as $label => $spec):
                ?>
                <div class="program-cell"><label><i class="<?= $spec[1] ?>"></i> <?= $e($label) ?></label><span><?= $e($spec[0]) ?></span></div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Courses Section (Conditional) -->
        <?php if (!empty($courseRecords)): ?>
        <div class="section-head"><div class="section-num"><?= ++$_s ?></div><h2>الدورات التدريبية</h2><div class="line"></div></div>
        <p class="section-count">عدد الدورات: (<?= count($courseRecords) ?>)</p>
        <table class="data"><thead><tr><th>اسم الدورة</th><th>التاريخ</th><th>التقدير</th></tr></thead><tbody>
            <?php foreach ($courseRecords as $rec): ?>
            <tr><td><?= $e($getVal($rec, 'template_name')) ?></td><td><?= $e($getVal($rec, 'evaluation_date')) ?></td><td><?= $e($getVal($rec, 'grade_label')) ?></td></tr>
            <?php endforeach; ?>
            <tr class="footer-row"><td colspan="2">التقدير العام</td><td><?= $e($averages['course']['grade_label'] ?? '—') ?></td></tr>
        </tbody></table>
        <?php endif; ?>

        <!-- Workshops Section (Conditional) -->
        <?php if (!empty($workshopRecords)): ?>
        <div class="section-head"><div class="section-num"><?= ++$_s ?></div><h2>ورش العمل</h2><div class="line"></div></div>
        <p class="section-count">عدد الورش: (<?= count($workshopRecords) ?>)</p>
        <table class="data"><thead><tr><th>اسم الورشة</th><th>التاريخ</th><th>التقدير</th></tr></thead><tbody>
            <?php foreach ($workshopRecords as $rec): ?>
            <tr><td><?= $e($getVal($rec, 'template_name')) ?></td><td><?= $e($getVal($rec, 'evaluation_date')) ?></td><td><?= $e($getVal($rec, 'grade_label')) ?></td></tr>
            <?php endforeach; ?>
            <tr class="footer-row"><td colspan="2">التقدير العام</td><td><?= $e($averages['workshop']['grade_label'] ?? '—') ?></td></tr>
        </tbody></table>
        <?php endif; ?>

        <!-- Skill Evaluations Section (Conditional) -->
        <?php if (!empty($skillRecords)): ?>
        <div class="section-head"><div class="section-num"><?= ++$_s ?></div><h2>التقييمات المهارية</h2><div class="line"></div></div>
        <p class="section-count">عدد التقييمات المهارية: (<?= count($skillRecords) ?>)</p>
        <table class="data"><thead><tr><th>التاريخ</th><th>التقييم</th><th>الدرجة / 100</th><th>التقدير</th></tr></thead><tbody>
            <?php foreach ($skillRecords as $rec):
                $score = $getVal($rec, 'final_score') ?? $getVal($rec, 'grade_value');
            ?>
            <tr><td><?= $e($getVal($rec, 'evaluation_date')) ?></td><td><?= $e($getVal($rec, 'template_name')) ?></td><td><?= $score !== null && $score !== '' ? number_format((float)$score, 2) : '—' ?></td><td><?= $e($getVal($rec, 'grade_label')) ?></td></tr>
            <?php endforeach; ?>
            <tr class="footer-row"><td colspan="3">التقدير العام للتقييمات المهارية</td><td><?= $e($averages['skill']['grade_label'] ?? '—') ?></td></tr>
        </tbody></table>
        <?php endif; ?>

        <!-- Individual Evaluations Section (Conditional) -->
        <?php if (!empty($individualRecords)): ?>
        <div class="section-head"><div class="section-num"><?= ++$_s ?></div><h2>التقييمات الفردية</h2><div class="line"></div></div>
        <p class="section-count">عدد التقييمات الفردية: (<?= count($individualRecords) ?>)</p>
        <table class="data"><thead><tr><th>التاريخ</th><th>التقييم</th><th>الدرجة / 100</th><th>التقدير</th></tr></thead><tbody>
            <?php foreach ($individualRecords as $rec):
                $score = $getVal($rec, 'final_score') ?? $getVal($rec, 'grade_value');
            ?>
            <tr><td><?= $e($getVal($rec, 'evaluation_date')) ?></td><td><?= $e($getVal($rec, 'template_name')) ?></td><td><?= $score !== null && $score !== '' ? number_format((float)$score, 2) : '—' ?></td><td><?= $e($getVal($rec, 'grade_label')) ?></td></tr>
            <?php endforeach; ?>
            <tr class="footer-row"><td colspan="3">التقدير العام للتقييمات الفردية</td><td><?= $e($averages['employee']['grade_label'] ?? '—') ?></td></tr>
        </tbody></table>
        <?php endif; ?>

        <!-- Annual Evaluations Section (Conditional) -->
        <?php if (!empty($annualRecords)): ?>
        <div class="section-head"><div class="section-num"><?= ++$_s ?></div><h2>التقييمات السنوية</h2><div class="line"></div></div>
        <p class="section-count">عدد التقييمات: (<?= count($annualRecords) ?>)</p>
        <table class="data"><thead><tr><th>السنة</th><th>التقييم</th><th>الدرجة / 100</th><th>التقدير</th></tr></thead><tbody>
            <?php foreach ($annualRecords as $rec):
                $score = $getVal($rec, 'final_score') ?? $getVal($rec, 'grade_value');
                $year = $getVal($rec, 'evaluation_date') ? date('Y', strtotime($getVal($rec, 'evaluation_date'))) : '';
            ?>
            <tr><td><?= $e($year) ?></td><td><?= $e($getVal($rec, 'template_name')) ?></td><td><?= $score !== null && $score !== '' ? number_format((float)$score, 2) : '—' ?></td><td><?= $e($getVal($rec, 'grade_label')) ?></td></tr>
            <?php endforeach; ?>
            <tr class="footer-row"><td colspan="3">التقدير العام للتقييمات السنوية</td><td><?= $e($averages['annual']['grade_label'] ?? '—') ?></td></tr>
        </tbody></table>
        <?php endif; ?>

        <!-- Empty State -->
        <?php if (empty($courseRecords) && empty($workshopRecords) && empty($individualRecords) && empty($annualRecords) && empty($skillRecords)): ?>
        <div class="empty-state">لا توجد تقييمات مسجلة لهذا الموظف حتى الآن.</div>
        <?php endif; ?>

        <!-- Signatures -->
        <div class="section-head"><div class="section-num"><?= ++$_s ?></div><h2>التوقيعات والاعتماد</h2><div class="line"></div></div>
        <table class="sig"><thead><tr><th>الموظف</th><th>المدير المباشر</th><th>مدير الموارد البشرية</th></tr></thead><tbody>
            <tr><td>الاسم: ___________</td><td>الاسم: ___________</td><td>الاسم: ___________</td></tr>
            <tr><td>التوقيع: _________</td><td>التوقيع: _________</td><td>التوقيع: _________</td></tr>
            <tr><td>التاريخ: __/__/__</td><td>التاريخ: __/__/__</td><td>التاريخ: __/__/__</td></tr>
        </tbody></table>

    </div><!-- /.doc-body (المحتوى) -->

        <!-- Document Footer — ابن مباشر للوحة، يُثبّت في قاع الصفحة بتمدد .doc-body -->
        <footer class="doc-footer">
            <div class="foot-right"><span class="dot"></span> وثيقة رسمية — سرّي للاستخدام الداخلي فقط</div>
            <div class="foot-center">المستخدم: <strong><?= $e($snapshot->created_by_user_id ?? '') ?>#</strong></div>
            <div class="foot-left">رقم التقرير: <?= $e($snapshot->report_number) ?> · <?= $e($snapshot->created_at) ?></div>
        </footer>

  </div><!-- /.report-page -->
</div>

<!-- Attachments (صفحات منفصلة) -->
<?php
$attachments = [];
foreach ($records as $record) {
    $attachment = is_array($record) ? ($record['attachment'] ?? null) : ($record->attachment ?? null);
    if (!empty($attachment)) {
        $attachments[] = $attachment;
    }
}
?>
<?php if ($attachments !== []): ?>
    <?php // أنماط الوثيقة محمّلة من <head> (يتطلب مرة واحدة) — لا تكرر هنا. ?>
    <div class="a4-container">
        <?php foreach (array_values($attachments) as $idx => $attachment): ?>
            <?php
            $attachmentIndex = $idx + 1;
            $attachmentTotal = count($attachments);
            $evaluation = isset($attachment['evaluation']) && is_array($attachment['evaluation']) ? (object)$attachment['evaluation'] : null;
            $template = $attachment['template'] ?? [];
            $answersMap = [];
            foreach (($attachment['answers'] ?? []) as $answerRow) {
                if (is_array($answerRow) && isset($answerRow['section_id'])) {
                    $answersMap[$answerRow['section_id']] = (object)$answerRow;
                }
            }
            $answers = $answersMap;
            $program = !empty($attachment['program']) && is_array($attachment['program']) ? (object)$attachment['program'] : null;
            $employee = !empty($attachment['employee']) && is_array($attachment['employee']) ? (object)$attachment['employee'] : null;
            $evaluator = $evaluation ? (object)[
                'full_name' => $evaluation->evaluator_name ?? '',
                'position_title' => $evaluation->evaluator_title ?? '',
            ] : null;
            $hijriDate = null;
            ?>
            <div class="attachment-page">
                <div class="section-title attachment-title" id="attachment-section-title"<?= $attachmentIndex ?>">مرفق <?= $attachmentIndex ?> من <?= $attachmentTotal ?></div>
                <div class="attachment-doc" style="margin-bottom: 24px;">
                    <?php require APP_ROOT . '/app/Views/evaluations/_document.php'; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php elseif ($records !== []): ?>
    <div class="a4-container">
        <div class="a4-page">
            <div class="empty-state">لا توجد مرفقات (نسخة قديمة).</div>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
