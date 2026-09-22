<?php // ملف: app/Views/analysis/report_pdf_template.php ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'dejavusans', sans-serif; color: #333; }
        .header-table { font-size: 9pt; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .report-title { font-size: 14pt; font-weight: bold; text-align: center; }
        .info-table { font-size: 10pt; border-collapse: collapse; width: 100%; margin-top: 15px; }
        .info-table td { border: 1px solid #ddd; padding: 8px; }
        .info-table td.label { font-weight: bold; background-color: #f2f2f2; width: 20%; }
        .section-title { font-size: 12pt; font-weight: bold; background-color: #e9ecef; padding: 8px; margin-top: 20px; text-align: right; }
        .perf-table { font-size: 9pt; border-collapse: collapse; width: 100%; margin-top: 10px; }
        .perf-table th { background-color: #343a40; color: #fff; padding: 8px; border: 1px solid #343a40; }
        .perf-table td { border: 1px solid #ddd; padding: 6px; }
        .chart-container { text-align: center; page-break-inside: avoid; margin-top: 20px; }
        .chart-title { font-size: 12pt; font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>
    <!-- الترويسة -->
    <table class="header-table" width="100%">
        <tr>
            <td width="33%">الموظف: <?php echo htmlspecialchars($employee->full_name); ?></td>
            <td width="34%" class="report-title">تقرير الأداء الفردي</td>
            <td width="33%" style="text-align: left;">تاريخ: <?php echo date('Y-m-d'); ?></td>
        </tr>
    </table>

    <h2 class="section-title">بطاقة معلومات الموظف</h2>
    <table class="info-table">
        <tr>
            <td class="label">الرقم الوظيفي</td>
            <td><?php echo htmlspecialchars($employee->emp_code); ?></td>
            <td class="label">القسم</td>
            <td><?php echo htmlspecialchars($employee->department_name); ?></td>
        </tr>
        <tr>
            <td class="label">المنصب</td>
            <td><?php echo htmlspecialchars($employee->position_title); ?></td>
            <td class="label">متوسط الأداء</td>
            <td><strong><?php echo round($performanceStats->average_score ?? 0, 1); ?>%</strong></td>
        </tr>
    </table>

    <!-- جداول الأداء -->
    <?php
    function render_pdf_table($title, $data, $color) {
        if (empty($data)) return;
        echo '<h2 class="section-title" style="background-color: ' . $color . '; color: #fff;">' . $title . '</h2>';
        echo '<table class="perf-table">
                <tr>
                    <th width="50%">الكفاءة</th>
                    <th width="35%">المعيار الرئيسي</th>
                    <th width="15%" align="center">الدرجة</th>
                </tr>';
        foreach ($data as $item) {
            echo '<tr>
                    <td>' . htmlspecialchars($item['section_name']) . '</td>
                    <td>' . htmlspecialchars($item['criterion_name']) . '</td>
                    <td align="center"><strong>' . $item['score'] . '%</strong></td>
                  </tr>';
        }
        echo '</table>';
    }

    if (isset($analysis['classified_performance'])) {
        $classifiedData = $analysis['classified_performance'];
        render_pdf_table('نقاط القوة (أداء ≥ 85%)', $classifiedData['strong'] ?? [], '#28a745');
        render_pdf_table('الأداء الجيد (50% - 84.9%)', $classifiedData['medium'] ?? [], '#007bff');
        render_pdf_table('جوانب تحتاج إلى تطوير (أداء < 50%)', $classifiedData['weak'] ?? [], '#dc3545');
    }
    ?>

    <!-- فاصل صفحات قبل المخططات -->
    <br pagebreak="true" />

    <h1 style="text-align:center;">التحليل البياني للأداء</h1>

    <!-- المخطط الأول -->
    <?php if (isset($barChartPath) && file_exists($barChartPath)): ?>
        <div class="chart-container">
            <h3 class="chart-title">ملخص أداء الكفاءات</h3>
            <img src="<?php echo $barChartPath; ?>" width="500">
        </div>
    <?php endif; ?>

      
  


    <!-- المخطط الثاني -->
    <?php if (isset($trendChartPath) && file_exists($trendChartPath)): ?>
        <div class="chart-container">
            <h3 class="chart-title">تطور الأداء العام</h3>
            <img src="<?php echo $trendChartPath; ?>" width="500">
        </div>
    <?php endif; ?>

</body>
</html>
