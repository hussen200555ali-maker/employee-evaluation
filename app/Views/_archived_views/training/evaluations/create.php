<?php 
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="page-header">
    <h2><?php echo $data['pageTitle']; ?></h2>
    <a href="<?php echo APP_URL; ?>/training/courses" class="btn-back">
        <i class="fas fa-arrow-right"></i> العودة إلى قائمة الدورات
    </a>
</div>

<div class="form-container">
    <form action="<?php echo APP_URL; ?>/training/evaluations/store" method="POST">
                <?= csrf_field() ?>
        <input type="hidden" name="course_id" value="<?php echo $data['course']->id; ?>">

        <h4><i class="fas fa-users"></i> 1. تقييم الحضور (الوزن: 50%)</h4>
        <div class="form-row">
            <div class="form-group">
                <label for="expected_attendees">عدد الحضور المفترض</label>
                <input type="number" id="expected_attendees" name="expected_attendees" class="form-control" required min="0">
            </div>
            <div class="form-group">
                <label for="actual_attendees">عدد الحضور الفعلي</label>
                <input type="number" id="actual_attendees" name="actual_attendees" class="form-control" required min="0">
            </div>
        </div>

        <h4><i class="fas fa-tasks"></i> 2. تقييم انتظام البرنامج (الوزن: 30%)</h4>
        <div class="form-group">
            <label for="program_regularity_weighted_score">أدخل الدرجة مباشرة (من 0 إلى 30)</label>
            <input type="number" id="program_regularity_weighted_score" name="program_regularity_weighted_score" class="form-control" required step="0.01" min="0" max="30">
            <small class="form-text text-muted">هذه هي الدرجة الموزونة مباشرة. مثال: إذا كانت النتيجة 25، فهذا يمثل 25% من إجمالي التقييم.</small>
        </div>

        <h4><i class="fas fa-lightbulb"></i> 3. تقييم استيعاب المشاركين (الوزن: 20%)</h4>
        <div class="form-group">
            <label for="participant_comprehension_weighted_score">أدخل الدرجة مباشرة (من 0 إلى 20)</label>
            <input type="number" id="participant_comprehension_weighted_score" name="participant_comprehension_weighted_score" class="form-control" required step="0.01" min="0" max="20">
            <small class="form-text text-muted">هذه هي الدرجة الموزونة مباشرة. مثال: إذا كانت النتيجة 18، فهذا يمثل 18% من إجمالي التقييم.</small>
        </div>

        <h4><i class="fas fa-sticky-note"></i> ملاحظات إضافية</h4>
        <div class="form-group">
            <textarea name="notes" class="form-control" rows="4" placeholder="أضف أي ملاحظات إضافية حول الدورة هنا..."></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit-form">
                <i class="fas fa-save"></i> حفظ التقييم
            </button>
        </div>
    </form>
</div>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>

