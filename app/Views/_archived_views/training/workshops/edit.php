edit.php<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<div class="container" style="max-width: 800px;">
    <div class="page-header">
        <h2><i class="fas fa-pencil-alt"></i> تعديل ورشة تدريبية</h2>
        <a href="<?php echo APP_URL; ?>/training/workshops" class="btn-back">
            <i class="fas fa-arrow-right"></i> العودة للقائمة
        </a>
    </div>

    <div class="form-container">
        <form action="<?php echo APP_URL; ?>/training/workshops/update" method="POST">
                <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?php echo $data['workshop']->id; ?>">

          <div class="form-group mb-3">
    <label for="catalog_id">اختر الورشة من الكتالوج</label>
    <select name="catalog_id" id="catalog_id" class="form-select" required>
        <option value="">-- اختر ورشة --</option>
        <?php foreach ($data['catalog_workshops'] as $catalog_workshop): ?>
            <option value="<?php echo $catalog_workshop->id; ?>"
                <?php if ($data['workshop']->catalog_id == $catalog_workshop->id) echo 'selected'; ?>>
                <?php echo htmlspecialchars($catalog_workshop->title); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="branch_id">الفرع</label>
                    <select id="branch_id" name="branch_id" class="form-select" required>
                        <option value="">-- اختر فرعاً --</option>
                        <?php foreach ($data['branches'] as $branch): ?>
                            <option value="<?php echo $branch->id; ?>" 
                                <?php if ($data['workshop']->branch_id == $branch->id) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($branch->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department_id">القسم</label>
                    <select id="department_id" name="department_id" class="form-select" required>
                        <option value="">-- اختر قسماً --</option>
                        <?php foreach ($data['departments'] as $department): ?>
                            <option value="<?php echo $department->id; ?>" 
                                <?php if ($data['workshop']->department_id == $department->id) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($department->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row mt-3">
                <div class="form-group">
                    <label for="start_date">تاريخ البدء</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" 
                           value="<?php echo $data['workshop']->start_date; ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date">تاريخ الانتهاء</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" 
                           value="<?php echo $data['workshop']->end_date; ?>" required>
                </div>
            </div>

            <div class="form-group mt-3">
                <label for="status">حالة الورشة</label>
                <select name="status" id="status" class="form-select" required>
                    <option value="scheduled" <?php if ($data['workshop']->status == 'scheduled') echo 'selected'; ?>>مجدولة</option>
                    <option value="ongoing" <?php if ($data['workshop']->status == 'ongoing') echo 'selected'; ?>>جارية</option>
                    <option value="completed" <?php if ($data['workshop']->status == 'completed') echo 'selected'; ?>>مكتملة</option>
                    <option value="canceled" <?php if ($data['workshop']->status == 'canceled') echo 'selected'; ?>>ملغاة</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit-form">
                    <i class="fas fa-save"></i> حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>

