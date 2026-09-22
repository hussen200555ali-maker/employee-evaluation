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

<!-- جميع استمارات الموظفين في القسم -->
<div class="page-break-before"></div>
<div class="table-title">جميع استمارات الموظفين في القسم</div>

<?php if (!empty($allEmployees)): ?>
    <?php foreach ($allEmployees as $index => $employee): ?>
        <div class="employee-section">
            <div class="employee-header">
                <h4>استمارة الموظف: <?php echo $employee['name'] ?? 'غير معروف'; ?> (<?php echo $employee['emp_code'] ?? 'غير معروف'; ?>)</h4>
                <p>متوسط الأداء: <?php echo $employee['average_performance'] ?? 0; ?>%</p>
            </div>
            
            <?php if (isset($employee['competencies']) && !empty($employee['competencies'])): ?>
                <?php foreach ($employee['competencies'] as $category => $competencies): ?>
                    <?php if (!empty($competencies)): ?>
                        <h5 style="color: #2c3e50; margin: 15px 0 10px 0;">
                            <?php 
                            if ($category === 'strong') echo 'نقاط القوة';
                            elseif ($category === 'medium') echo 'الكفاءات المتوسطة';
                            else echo 'مجالات التحسين';
                            ?>
                            (<?php echo count($competencies); ?>)
                        </h5>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th width="40%">الكفاءة</th>
                                    <th width="30%">المعيار</th>
                                    <th width="15%">الدرجة</th>
                                    <th width="15%">التصنيف</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($competencies as $comp): ?>
                                    <tr>
                                        <td><?php echo $comp['section_name'] ?? 'غير معروف'; ?></td>
                                        <td><?php echo $comp['criterion_name'] ?? 'غير معروف'; ?></td>
                                        <td>
                                            <span class="performance-badge 
                                                <?php 
                                                $score = $comp['score'] ?? 0;
                                                if ($score >= 80) echo 'badge-success';
                                                elseif ($score >= 60) echo 'badge-warning';
                                                else echo 'badge-danger';
                                                ?>
                                            ">
                                                <?php echo $score; ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $score = $comp['score'] ?? 0;
                                            if ($score >= 80) echo 'قوي';
                                            elseif ($score >= 60) echo 'متوسط';
                                            else echo 'بحاجة تطوير';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">
                    لا توجد بيانات كفاءات متاحة لهذا الموظف
                </p>
            <?php endif; ?>
        </div>
        
        <?php if ($index < count($allEmployees) - 1): ?>
            <div class="page-break"></div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align: center; color: #7f8c8d; padding: 20px;">
        لا توجد بيانات موظفين متاحة
    </p>
<?php endif; ?>