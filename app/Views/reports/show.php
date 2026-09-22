<?php 
require_once APP_ROOT . '/app/Views/layouts/header.php'; 

// استخراج البيانات لتسهيل الوصول إليها
$reportData = $data['reportData'];
$config = $data['config'];

// إعداد رؤوس الأعمدة الديناميكية
$employeeColumnHeaders = [];
foreach ($config['employee_columns'] as $col) {
    list($type, $id) = explode('-', $col['source']);
    $sourceName = '';
    if ($type === 'template') {
        // البحث عن اسم القالب
        $sourceName = 'استمارة: ' . ($data['templates'][$id] ?? 'غير معروف');
    } elseif ($type === 'criterion') {
        // البحث عن اسم المعيار
        $sourceName = 'معيار: ' . ($data['criteria'][$id] ?? 'غير معروف');
    }
    $employeeColumnHeaders[] = $sourceName . ' (' . $col['weight'] . '%)';
}
?>

<div class="container-fluid mt-4"> <!-- استخدام container-fluid لعرض أفضل للجدول العريض -->
    <div class="page-header">
        <div>
            <h2><i class="fas fa-file-invoice text-success"></i> <?php echo htmlspecialchars($config['report_title']); ?></h2>
            <?php if ($config['start_date'] && $config['end_date']): ?>
                <p class="lead">للفترة من <?php echo $config['start_date']; ?> إلى <?php echo $config['end_date']; ?></p>
            <?php endif; ?>
        </div>
        <a href="<?php echo APP_URL; ?>/reports/builder" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> العودة إلى بناء التقرير</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover text-center align-middle">
            <thead class="table-dark">
                <tr>
                    <th rowspan="2">الفروع</th>
                    <th rowspan="2">الأقسام</th>
                    <th colspan="<?php echo count($employeeColumnHeaders); ?>">تقييمات الموظفين</th>
                    <th rowspan="2">الدورات (<?php echo $config['courses_weight']; ?>%)</th>
                    <th rowspan="2">ورش العمل (<?php echo $config['workshops_weight']; ?>%)</th>
                    <th rowspan="2">إجمالي تقييم القسم</th>
                    <th rowspan="2">إجمالي تقييم الفرع</th>
                </tr>
                <tr>
                    <?php foreach ($employeeColumnHeaders as $header): ?>
                        <th><?php echo $header; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reportData['branches'])): ?>
                    <tr>
                        <td colspan="<?php echo 7 + count($employeeColumnHeaders); ?>" class="text-center p-5">
                            <h4><i class="fas fa-exclamation-triangle"></i> لا توجد بيانات لعرضها</h4>
                            <p>قد يكون السبب عدم وجود أقسام في الفروع أو عدم وجود تقييمات خلال الفترة المحددة.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reportData['branches'] as $branch): ?>
                        <?php $firstRow = true; ?>
                        <?php foreach ($branch['departments'] as $department): ?>
                            <tr>
                                <?php if ($firstRow): ?>
                                    <td rowspan="<?php echo count($branch['departments']); ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></td>
                                <?php endif; ?>
                                
                                <td><?php echo htmlspecialchars($department['department_name']); ?></td>
                                
                                <?php // عرض نتائج الأعمدة الديناميكية ?>
                                <?php foreach ($department['scores'] as $score): ?>
                                    <td><?php echo number_format($score, 2); ?>%</td>
                                <?php endforeach; ?>

                                <td><?php echo number_format($department['department_total'], 2); ?>%</td>

                                <?php if ($firstRow): ?>
                                    <td rowspan="<?php echo count($branch['departments']); ?>" class="fw-bold fs-5">
                                        <?php echo number_format($branch['branch_total'], 2); ?>%
                                    </td>
                                    <?php $firstRow = false; ?>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-group-divider">
                <tr class="table-primary">
                    <th colspan="<?php echo 2 + count($employeeColumnHeaders) + 2; ?>" class="text-center fs-5">
                        المتوسط العام للشركة
                    </th>
                    <th colspan="2" class="text-center fs-4">
                        <?php echo number_format($reportData['company_total'], 2); ?>%
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
