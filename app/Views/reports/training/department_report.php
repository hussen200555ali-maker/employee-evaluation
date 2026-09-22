<?php 
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="page-header">
    <h2><i class="fas fa-file-alt"></i> تقرير أداء التدريب - الأقسام</h2>
</div>

<!-- نموذج اختيار المعايير -->
<div class="form-container mb-4">
    <form action="<?php echo APP_URL; ?>/reports/training/department" method="POST">
                <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="department_id">اختر القسم</label>
                <select name="department_id" id="department_id" class="form-control" required>
                    <option value="">-- اختر --</option>
                    <?php foreach ($data['departments'] as $department): ?>
                        <option value="<?php echo $department->id; ?>" <?php echo (isset($data['selected_department']) && $data['selected_department'] == $department->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($department->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="start_date">من تاريخ</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $data['selected_start_date'] ?? ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">إلى تاريخ</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $data['selected_end_date'] ?? ''; ?>" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-submit-form"><i class="fas fa-cogs"></i> إنشاء التقرير</button>
        </div>
    </form>
</div>

<!-- عرض نتائج التقرير (يظهر فقط إذا كانت هناك بيانات) -->
<?php if (!empty($data['reportData']) && !empty($data['reportData']['details'])): ?>
    <div class="content-card">
        <div class="card-header">
            <h3>نتائج التقرير</h3>
            <!-- يمكن إضافة زر طباعة هنا لاحقاً -->
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>اسم الدورة</th>
                            <th>الحضور (المفترض/الفعلي)</th>
                            <th>مكون الحضور (من 50)</th>
                            <th>مكون الانتظام (من 30)</th>
                            <th>مكون الاستيعاب (من 20)</th>
                            <th>النتيجة النهائية (من 100)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['reportData']['details'] as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row->course_title); ?></td>
                                <td><?php echo $row->expected_attendees . ' / ' . $row->actual_attendees; ?></td>
                                <td><?php echo number_format($row->attendance_component, 2); ?></td>
                                <td><?php echo number_format($row->regularity_component, 2); ?></td>
                                <td><?php echo number_format($row->comprehension_component, 2); ?></td>
                                <td><strong><?php echo number_format($row->final_score, 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <!-- صف الإجماليات والمتوسطات -->
                    <tfoot class="table-summary">
                        <tr>
                            <td><strong>الإجمالي / المتوسط</strong></td>
                            <td><strong><?php echo $data['reportData']['summary']['total_expected'] . ' / ' . $data['reportData']['summary']['total_actual']; ?></strong></td>
                            <td><?php echo number_format($data['reportData']['summary']['avg_attendance'], 2); ?></td>
                            <td><?php echo number_format($data['reportData']['summary']['avg_regularity'], 2); ?></td>
                            <td><?php echo number_format($data['reportData']['summary']['avg_comprehension'], 2); ?></td>
                            <td><strong><?php echo number_format($data['reportData']['summary']['avg_final_score'], 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php elseif (isset($data['reportData'])): ?>
    <div class="alert alert-info">لا توجد دورات مقيمة لهذا القسم في الفترة الزمنية المحددة.</div>
<?php endif; ?>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>

