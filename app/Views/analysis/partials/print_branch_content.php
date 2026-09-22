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
<!-- جدول توزيع الأداء للأقسام - النسخة المحسنة -->
<?php if (!empty($departmentStats)): ?>
<div class="table-title">توزيع الأداء لأقسام <?php echo $entityInfo['name'] ?? 'الفرع'; ?></div>
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
        $totalDeptEmployees = 0;
        $totalDeptPerformance = 0;
        $deptCount = count($departmentStats);
        ?>
        <?php foreach ($departmentStats as $department): ?>
            <?php
            $empCount = $department->employee_count ?? 0;
            $performance = $department->average_performance ?? 0;
            
            $totalDeptEmployees += $empCount;
            $totalDeptPerformance += $performance;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($department->department_name ?? 'غير معروف'); ?></td>
                <td><?php echo $empCount > 0 ? $empCount : '0'; ?></td>
                <td>
                    <span class="performance-badge 
                        <?php 
                        if ($performance >= 80) echo 'badge-success';
                        elseif ($performance >= 60) echo 'badge-warning';
                        else echo 'badge-danger';
                        ?>
                    ">
                        <?php echo number_format($performance, 2); ?>%
                    </span>
                </td>
                <td>
                    <?php 
                    if ($performance >= 80) echo 'متميز';
                    elseif ($performance >= 60) echo 'جيد';
                    else echo 'بحاجة تحسين';
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        
        <?php if ($deptCount > 0): ?>
            <tr style="background: #f8f9fa; font-weight: bold;">
                <td>المتوسط العام للأقسام</td>
                <td><?php echo $totalDeptEmployees; ?></td>
                <td>
                    <span class="performance-badge badge-info">
                        <?php echo number_format($deptCount > 0 ? $totalDeptPerformance / $deptCount : 0, 2); ?>%
                    </span>
                </td>
                <td>إجمالي <?php echo $entityInfo['type'] ?? 'الفرع'; ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- جدول توزيع الأداء للكفاءات -->
<div class="page-break-before"></div>
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
                                <strong><?php echo $entityInfo['name'] ?? 'غير معروف'; ?></strong>
                            </td>
                            <td rowspan="<?php echo count($sections); ?>">
                                <strong><?php echo $criterionName; ?></strong>
                            </td>
                        <?php endif; ?>
                        
                        <td><?php echo $section['section_name']; ?></td>
                        <td class="strong-col"><?php echo $section['strong_count'] ?? 0; ?></td>
                        <td class="medium-col"><?php echo $section['medium_count'] ?? 0; ?></td>
                        <td class="weak-col"><?php echo $section['weak_count'] ?? 0; ?></td>
                        <td>
                            <span class="performance-badge 
                                <?php 
                                $sectionScore = $section['average_score'] ?? 0;
                                if ($sectionScore >= 80) echo 'badge-success';
                                elseif ($sectionScore >= 60) echo 'badge-warning';
                                else echo 'badge-danger';
                                ?>
                            ">
                                <?php echo $sectionScore; ?>%
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

<!-- جميع استمارات الموظفين في الفرع -->
<div class="page-break-before"></div>
<div class="table-title">جميع استمارات الموظفين في الفرع</div>

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