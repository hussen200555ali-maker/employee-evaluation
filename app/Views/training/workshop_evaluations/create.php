<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<div class="container" style="max-width: 800px;">
    <div class="page-header">
        <h2><i class="fas fa-star"></i> تقييم ورشة تدريبية</h2>
        <a href="<?php echo APP_URL; ?>/training/workshops" class="btn-back">
            <i class="fas fa-arrow-right"></i> العودة للقائمة
        </a>
    </div>

    <div class="form-container">
        <form action="<?php echo APP_URL; ?>/training/workshops/evaluations/store" method="POST">
                <?= csrf_field() ?>
            <input type="hidden" name="workshop_id" value="<?php echo $data['workshop']->id; ?>">

            <div class="mb-3">
                <label class="form-label">عنوان الورشة</label>
                <input type="text" class="form-control" 
                       value="<?php echo htmlspecialchars($data['workshop']->workshop_title); ?>" disabled>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="expected_attendees" class="form-label">الحضور المتوقع</label>
                    <input type="number" id="expected_attendees" name="expected_attendees" 
                           class="form-control" min="0" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="actual_attendees" class="form-label">الحضور الفعلي</label>
                    <input type="number" id="actual_attendees" name="actual_attendees" 
                           class="form-control" min="0" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="program_regularity_weighted_score" class="form-label">انتظام البرنامج (0 - 20)</label>
                <input type="number" id="program_regularity_weighted_score" 
                       name="program_regularity_weighted_score" class="form-control" min="0" max="20" required>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">ملاحظات</label>
                <textarea id="notes" name="notes" class="form-control" rows="3"
                          placeholder="أدخل أي ملاحظات إضافية..."></textarea>
            </div>

            <div class="form-actions mt-3">
                <button type="submit" class="btn-submit-form">
                    <i class="fas fa-save"></i> حفظ التقييم
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>

