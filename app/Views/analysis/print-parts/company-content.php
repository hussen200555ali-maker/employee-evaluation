<?php
// تجميع البيانات
$templatesData = [];
foreach ($analysis['classified_performance'] as $category => $competencies) {
    foreach ($competencies as $comp) {
        $templateName = $comp['template_name'];
        $criterionName = $comp['criterion_name'];
        
        if (!isset($templatesData[$templateName])) {
            $templatesData[$templateName] = [];
        }
        if (!isset($templatesData[$templateName][$criterionName])) {
            $templatesData[$templateName][$criterionName] = [];
        }
        
        $templatesData[$templateName][$criterionName][] = $comp;
    }
}
?>

<!-- جدول توزيع الأداء للفروع -->
<?php if (isset($hierarchicalData['entities']) && !empty($hierarchicalData['entities'])): ?>
<div class="table-title">توزيع الأداء لفروع الشركة</div>
<table>
    <thead>
        <tr>
            <th width="35%">اسم الفرع</th>
            <th width="15%">عدد الأقسام</th>
            <th width="15%">عدد الموظفين</th>
            <th width="15%">متوسط الأداء</th>
            <th width="20%">الحالة</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $totalPerformance = 0;
        $branchCount = 0;
        $totalEmployees = 0;
        ?>
        <?php foreach ($hierarchicalData['entities'] as $branch): ?>
            <?php
            $deptCount = count($branch['entities'] ?? []);
            $branchEmployees = $branch['employee_count'] ?? 0;
            ?>
            <tr>
                <td><?php echo $branch['name']; ?></td>
                <td><?php echo $deptCount; ?></td>
                <td><?php echo $branchEmployees; ?></td>
                <td>
                    <span class="performance-badge 
                        <?php 
                        if ($branch['average_performance'] >= 80) echo 'badge-success';
                        elseif ($branch['average_performance'] >= 60) echo 'badge-warning';
                        else echo 'badge-danger';
                        ?>
                    ">
                        <?php echo $branch['average_performance']; ?>%
                    </span>
                </td>
                <td>
                    <?php 
                    if ($branch['average_performance'] >= 80) echo 'متميز';
                    elseif ($branch['average_performance'] >= 60) echo 'جيد';
                    else echo 'بحاجة تحسين';
                    ?>
                </td>
            </tr>
            <?php 
            $totalPerformance += $branch['average_performance'];
            $branchCount++;
            $totalEmployees += $branchEmployees;
            ?>
        <?php endforeach; ?>
        
        <?php if ($branchCount > 0): ?>
            <tr style="background: #f8f9fa; font-weight: bold;">
                <td>المتوسط العام للفروع</td>
                <td><?php echo $branchCount; ?></td>
                <td><?php echo $totalEmployees; ?></td>
                <td>
                    <span class="performance-badge badge-info">
                        <?php echo round($totalPerformance / $branchCount, 2); ?>%
                    </span>
                </td>
                <td>إجمالي الشركة</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- جدول توزيع الأداء للكفاءات -->
<div class="table-title">توزيع الأداء للكفاءات في الشركة</div>

<?php foreach ($templatesData as $templateName => $criteria): ?>
    <table>
        <thead>
            <tr class="template-header">
                <th colspan="8"><?php echo $templateName; ?></th>
            </tr>
            <tr>
                <th width="20%">الفرع</th>
                <th width="15%">المعيار</th>
                <th width="15%">الكفاءة الفرعية</th>
                <th width="8%">أداء قوي</th>
                <th width="8%">أداء متوسط</th>
                <th width="8%">بحاجة للتطوير</th>
                <th width="13%">متوسط أداء الكفاءة</th>
                <th width="13%">متوسط أداء المعيار</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($criteria as $criterionName => $sections): ?>
                <?php
                $criterionScores = array_column($sections, 'average_score');
                $criterionAverage = count($criterionScores) > 0 ? array_sum($criterionScores) / count($criterionScores) : 0;
                $criterionAverage = round($criterionAverage, 2);
                ?>
                
                <?php foreach ($sections as $index => $section): ?>
                    <tr class="<?php echo $index === 0 ? 'criterion-row' : 'section-row'; ?>">
                        <?php if ($index === 0): ?>
                            <td rowspan="<?php echo count($sections); ?>">
                                <strong>الشركة ككل</strong>
                            </td>
                            <td rowspan="<?php echo count($sections); ?>">
                                <strong><?php echo $criterionName; ?></strong>
                            </td>
                        <?php endif; ?>
                        
                        <td><?php echo $section['section_name']; ?></td>
                        <td class="strong-col"><?php echo $section['strong_count']; ?></td>
                        <td class="medium-col"><?php echo $section['medium_count']; ?></td>
                        <td class="weak-col"><?php echo $section['weak_count']; ?></td>
                        <td>
                            <span class="performance-badge 
                                <?php 
                                if ($section['average_score'] >= 80) echo 'badge-success';
                                elseif ($section['average_score'] >= 60) echo 'badge-warning';
                                else echo 'badge-danger';
                                ?>
                            ">
                                <?php echo $section['average_score']; ?>%
                            </span>
                        </td>
                        
                        <?php if ($index === 0): ?>
                            <td rowspan="<?php echo count($sections); ?>">
                                <span class="performance-badge badge-info">
                                    <?php echo $criterionAverage; ?>%
                                </span>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>