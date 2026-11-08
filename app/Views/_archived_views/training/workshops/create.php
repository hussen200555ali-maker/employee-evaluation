<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<div class="container" style="max-width: 800px;">
    <div class="page-header">
        <h2><i class="fas fa-plus"></i> إضافة ورشة جديدة</h2>
        <a href="<?php echo APP_URL; ?>/training/workshops" class="btn-back">
            <i class="fas fa-arrow-right"></i> العودة للقائمة
        </a>
    </div>

    <div class="form-container">
        <form action="<?php echo APP_URL; ?>/training/workshops/store" method="POST">
                <?= csrf_field() ?>
          <div class="form-group mb-3">
    <label for="catalog_id">اختر الورشة من الكتالوج</label>
    <select name="catalog_id" id="catalog_id" class="form-select" required>
    <?php foreach ($data['catalog_workshops'] as $catalog_workshop): ?>
        <option value="<?php echo $catalog_workshop->id; ?>">
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
                            <option value="<?php echo $branch->id; ?>"><?php echo htmlspecialchars($branch->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department_id">القسم</label>
                    <select id="department_id" name="department_id" class="form-select" required>
                        <option value="">-- اختر قسماً --</option>
                    </select>
                </div>
            </div>

            <div class="form-row mt-3">
                <div class="form-group">
                    <label for="start_date">تاريخ البدء</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="end_date">تاريخ الانتهاء</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" required>
                </div>
            </div>

            <div class="form-group mt-3">
                <label for="status">حالة الورشة</label>
                <select name="status" id="status" class="form-select">
                    <option value="scheduled">مجدولة</option>
                    <option value="ongoing">جارية</option>
                    <option value="completed">مكتملة</option>
                    <option value="canceled">ملغاة</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit-form">
                    <i class="fas fa-save"></i> حفظ الورشة
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>

