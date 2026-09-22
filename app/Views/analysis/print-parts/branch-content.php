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

<!-- جدول توزيع الأداء للأقسام -->
<?php if (isset($hierarchicalData['entities']) && !empty($hierarchicalData['entities'])): ?>
<div class="table-title">توزيع الأداء لأقسام الفرع</div>
<table>
    <thead>
        <tr>
            <th width="40%">اسم القسم</th>
            <th width="20%">عدد الموظفين</th>
            <th width="20%">متوسط الأداء</th>
            <th width="20%">الحالة</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $totalPerformance = 0;
        $deptCount = 0;
        ?>
        <?php foreach ($hierarchicalData['entities'] as $department): ?>
            <tr>
                <td><?php echo $department['name']; ?></td>
                <td><?php echo $department['employee_count'] ?? 'غير معروف'; ?></td>
                <td>
                    <span class="performance-badge 
                        <?php 
                        if ($department['average_performance'] >= 80) echo 'badge-success';
                        elseif ($department['average_performance'] >= 60) echo 'badge-warning';
                        else echo 'badge-danger';
                        ?>
                    ">
                        <?php echo $department['average_performance']; ?>%
                    </span>
                </td>
                <td>
                    <?php 
                    if ($department['average_performance'] >= 80) echo 'متميز';
                    elseif ($department['average_performance'] >= 60) echo 'جيد';
                    else echo 'بحاجة تحسين';
                    ?>
                </td>
            </tr>
            <?php 
            $totalPerformance += $department['average_performance'];
            $deptCount++;
            ?>
        <?php endforeach; ?>
        
        <?php if ($deptCount > 0): ?>
            <tr style="background: #f8f9fa; font-weight: bold;">
                <td>المتوسط العام للأقسام</td>
                <td><?php echo $entityInfo['employee_count']; ?></td>
                <td>
                    <span class="performance-badge badge-info">
                        <?php echo round($totalPerformance / $deptCount, 2); ?>%
                    </span>
                </td>
                <td>إجمالي الفرع</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- جدول توزيع الأداء للكفاءات -->
<div class="table-title">توزيع الأداء للكفاءات في الفرع</div>

<?php foreach ($templatesData as $templateName => $criteria): ?>
    <table>
        <thead>
            <tr class="template-header">
                <th colspan="8"><?php echo $templateName; ?></th>
            </tr>
            <tr>
                <th width="20%">القسم</th>
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
                                <strong><?php echo $entityInfo['name']; ?></strong>
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