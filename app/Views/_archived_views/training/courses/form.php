<?php 
// في ملف /app/Views/training/courses/form.php
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="container" style="max-width: 800px;">
    <div class="page-header">
        <h2><?php echo isset($data['course']) ? 'تعديل دورة تدريبية' : 'إضافة دورة تدريبية جديدة'; ?></h2>
        <a href="<?php echo APP_URL; ?>/training/courses" class="btn-back">
            <i class="fas fa-arrow-right"></i> العودة إلى القائمة
        </a>
    </div>

    <div class="form-container">
        <form action="<?php echo isset($data['course']) ? APP_URL . '/training/courses/update' : APP_URL . '/training/courses/store'; ?>" method="POST">
                <?= csrf_field() ?>
            <?php if (isset($data['course'])): ?>
                <input type="hidden" name="id" value="<?php echo $data['course']->id; ?>">
            <?php endif; ?>

            <div class="form-row">
                <!-- ===> هذا هو التعديل الأهم <=== -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="catalog_id">اختر الدورة من الكتالوج</label>
                    <select name="catalog_id" id="catalog_id" class="form-select" required>
                        <option value="">-- الرجاء اختيار دورة --</option>
                        <?php foreach ($data['catalog_courses'] as $catalog_course): ?>
                            <option value="<?php echo $catalog_course->id; ?>" <?php echo (isset($data['course']->catalog_id) && $data['course']->catalog_id == $catalog_course->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($catalog_course->title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="branch_id">الفرع</label>
                    <select name="branch_id" id="branch_id" class="form-select" required>
                        <option value="">-- اختر فرعاً --</option>
                        <?php foreach ($data['branches'] as $branch): ?>
                            <option value="<?php echo $branch->id; ?>" <?php echo (isset($data['course']->branch_id) && $data['course']->branch_id == $branch->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="department_id">القسم (اختياري)</label>
                    <select name="department_id" id="department_id" class="form-select">
                        <option value="">-- اختر قسماً --</option>
                        <?php if (!empty($data['departments'])): ?>
                            <?php foreach ($data['departments'] as $department): ?>
                                <option value="<?php echo $department->id; ?>" <?php echo (isset($data['course']->department_id) && $data['course']->department_id == $department->id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($department->name); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="start_date">تاريخ البدء</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($data['course']->start_date ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="end_date">تاريخ الانتهاء</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($data['course']->end_date ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit-form">
                    <i class="fas fa-save"></i> <?php echo isset($data['course']) ? 'حفظ التعديلات' : 'حفظ الدورة'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>

