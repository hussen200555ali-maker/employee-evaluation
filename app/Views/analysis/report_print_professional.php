<?php 
// ملف: app/Views/analysis/report_print_professional.php
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة: <?php echo htmlspecialchars($reportTitle); ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/print_professional.css">
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="logo">
                <!-- <img src="path/to/logo.png" alt="Logo"> -->
                <h4>اسم الشركة</h4>
            </div>
            <div class="title">
                <h1>تقرير تحليل الأداء</h1>
                <h2><?php echo htmlspecialchars($reportTitle); ?></h2>
            </div>
        </div>

        <div class="sub-header">
            <span>تاريخ التقرير: <?php echo date('Y-m-d'); ?></span>
            <span>تم تحليل <?php echo $analysis['analyzedEvaluationsCount']; ?> تقييم لـ <?php echo $analysis['totalEmployeesInScope']; ?> موظف/موظفين</span>
        </div>

        <div class="content">
            <h3>ملخص الأداء العام</h3>
            <table>
                <thead>
                    <tr>
                        <th>الكفاءة</th>
                        <th class="center">الحالة</th>
                        <th class="center">متوسط الأداء</th>
                        <th class="center">عدد المتميزين (>85%)</th>
                        <th class="center">عدد المحتاجين للتطوير (<60%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $all_sections = array_merge($analysis['classifiedPerformance']['strengths'], $analysis['classifiedPerformance']['good'], $analysis['classifiedPerformance']['weaknesses']);
                    uasort($all_sections, fn($a, $b) => $b['average'] <=> $a['average']);
                    ?>
                    <?php foreach ($all_sections as $name => $data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($name); ?></td>
                            <td class="center">
                                <?php 
                                if ($data['average'] >= 85) echo '<span class="status-badge status-strength">قوة</span>';
                                elseif ($data['average'] < 60) echo '<span class="status-badge status-weakness">ضعف</span>';
                                else echo '<span class="status-badge status-good">جيد</span>';
                                ?>
                            </td>
                            <td class="center"><?php echo $data['average']; ?>%</td>
                            <td class="center" title="<?php echo implode(', ', $data['strong_names']); ?>"><?php echo $data['strong_count']; ?></td>
                            <td class="center" title="<?php echo implode(', ', $data['weak_names']); ?>"><?php echo $data['weak_count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>هذا التقرير سري وخاص بإدارة الشركة</p>
            <div class="pagenum">صفحة <span></span></div>
        </div>
    </div>
</body>
</html>
