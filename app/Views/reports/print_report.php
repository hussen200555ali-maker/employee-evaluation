<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة: <?php echo htmlspecialchars($data['pageTitle']); ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/fonts.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/libs/bootstrap-5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/print-style.css">
</head>
<body>

    <div class="printable-area">
        <?php
            $employeeColsCount = count($data['config']['employee_columns']);
            $totalCols = 2 + $employeeColsCount + 4; 
        ?>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th colspan="<?php echo $totalCols; ?>" class="text-center">
                        <h1><?php echo htmlspecialchars($data['pageTitle']); ?></h1>
                        <?php if (!empty($data['config']['start_date']) && !empty($data['config']['end_date'])): ?>
                            <p class="mb-0">للفترة من <?php echo $data['config']['start_date']; ?> إلى <?php echo $data['config']['end_date']; ?></p>
                        <?php endif; ?>
                    </th>
                </tr>
                <tr>
                    <th rowspan="2">الفروع</th>
                    <th rowspan="2">الأقسام</th>
                    <th colspan="<?php echo $employeeColsCount; ?>">تقييمات الموظفين</th>
                    <th rowspan="2">الدورات (<?php echo $data['config']['courses_weight']; ?>%)</th>
                    <th rowspan="2">ورش العمل (<?php echo $data['config']['workshops_weight']; ?>%)</th>
                    <th rowspan="2">إجمالي تقييم القسم</th>
                    <th rowspan="2">إجمالي تقييم الفرع</th>
                </tr>
                <tr id="dynamic-headers-row">
                    <!-- JavaScript will inject dynamic headers here -->
                </tr>
            </thead>

            <tbody id="report-body-placeholder">
                <!-- JavaScript will inject data rows here -->
            </tbody>

            <tfoot class="table-group-divider">
                <tr class="table-primary">
                    <th colspan="<?php echo $totalCols - 1; ?>" class="text-center fs-5">المتوسط العام للشركة</th>
                    <th class="text-center fs-4" id="company-total-placeholder">0.00%</th>
                </tr>
                <tr>
                    <td colspan="<?php echo $totalCols; ?>" class="text-center small text-muted">
                        <?php echo APP_NAME; ?> &copy; <?php echo date('Y'); ?> | تاريخ الطباعة: <?php echo date('Y-m-d H:i'); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script>
        const reportPrintData = <?php echo json_encode($data); ?>;

        // بناء رؤوس الأعمدة الديناميكية
        const { config, templates, criteria } = reportPrintData;
        const employeeColumnHeaders = config.employee_columns.map(col => {
            const [type, id] = col.source.split('-');
            let sourceName = (type === 'template') ? `استمارة: ${templates[id] || '?'}` : `معيار: ${criteria[id] || '?'}`;
            return `<th>${sourceName} (${col.weight}%)</th>`;
        }).join('');
        document.getElementById('dynamic-headers-row').innerHTML = employeeColumnHeaders;

        // بناء صفوف البيانات
        const { reportData } = reportPrintData;
        let tableRows = '';
        if (!reportData.branches || reportData.branches.length === 0) {
            // ===> إصلاح بسيط هنا أيضاً في colspan <===
            tableRows = `<tr><td colspan="${2 + employeeColsCount + 4}">لا توجد بيانات.</td></tr>`;
        } else {
            reportData.branches.forEach(branch => {
                let firstRow = true;
                branch.departments.forEach(department => {
                    const dynamicScores = department.scores.map(score => `<td>${parseFloat(score).toFixed(2)}%</td>`).join('');
                    
                    // =================================================================
                    //  ▼▼▼ هذا هو السطر الحاسم الذي تم إصلاحه بالكامل ▼▼▼
                    // =================================================================
                    tableRows += `<tr>
                                    ${firstRow ? `<td rowspan="${branch.departments.length}">${branch.branch_name}</td>` : ''}
                                    <td>${department.department_name}</td>
                                    ${dynamicScores}
                                    <td>${parseFloat(department.courses_score).toFixed(2)}%</td>
                                    <td>${parseFloat(department.workshops_score).toFixed(2)}%</td>
                                    <td>${parseFloat(department.department_total).toFixed(2)}%</td>
                                    ${firstRow ? `<td rowspan="${branch.departments.length}" class="fw-bold fs-5">${parseFloat(branch.branch_total).toFixed(2)}%</td>` : ''}
                                  </tr>`;
                    // =================================================================
                    //  ▲▲▲ نهاية منطقة الإصلاح ▲▲▲
                    // =================================================================
                    firstRow = false;
                });
            });
        }
        document.getElementById('report-body-placeholder').innerHTML = tableRows;

        // تحديث الإجمالي العام للشركة
        document.getElementById('company-total-placeholder').textContent = `${parseFloat(reportData.company_total).toFixed(2)}%`;

        window.onload = () => window.print();
    </script>

</body>
</html>
