<?php
// تجميع البيانات حسب القوالب والمعايير
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

<!-- جدول توزيع الأداء للقسم -->
<div class="table-title">توزيع الأداء للكفاءات في القسم</div>

<?php foreach ($templatesData as $templateName => $criteria): ?>
    <table>
        <thead>
            <tr class="template-header">
                <th colspan="7"><?php echo $templateName; ?></th>
            </tr>
            <tr>
                <th width="25%">المعيار</th>
                <th width="25%">الكفاءة الفرعية</th>
                <th width="10%">أداء قوي</th>
                <th width="10%">أداء متوسط</th>
                <th width="10%">بحاجة للتطوير</th>
                <th width="10%">متوسط أداء الكفاءة</th>
                <th width="10%">متوسط أداء المعيار</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($criteria as $criterionName => $sections): ?>
                <?php
                // حساب متوسط أداء المعيار
                $criterionScores = array_column($sections, 'average_score');
                $criterionAverage = count($criterionScores) > 0 ? array_sum($criterionScores) / count($criterionScores) : 0;
                $criterionAverage = round($criterionAverage, 2);
                ?>
                
                <?php foreach ($sections as $index => $section): ?>
                    <tr class="<?php echo $index === 0 ? 'criterion-row' : 'section-row'; ?>">
                        <?php if ($index === 0): ?>
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