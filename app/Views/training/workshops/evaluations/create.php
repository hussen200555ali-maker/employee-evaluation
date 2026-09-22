<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<div class="container" style="max-width: 850px;">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-star-half-alt text-warning"></i> تقييم ورشة تدريبية</h2>
        <a href="<?php echo APP_URL; ?>/training/workshops" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> العودة للقائمة
        </a>
    </div>

    <!-- بطاقة معلومات الورشة (للتأكيد) -->
    <div class="card bg-light border-primary mb-4">
        <div class="card-body">
            <h5 class="card-title text-primary mb-3">
                <i class="fas fa-info-circle me-2"></i> معلومات الورشة قيد التقييم
            </h5>
            <div class="row">
                <div class="col-md-8">
                    <strong>العنوان:</strong> <?php echo htmlspecialchars($data['workshop']->workshop_title); ?>
                </div>
                <div class="col-md-4">
                    <strong>الفترة:</strong> <?php echo $data['workshop']->start_date; ?> إلى <?php echo $data['workshop']->end_date; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- حاوية النموذج الرئيسية -->
    <div class="form-container card shadow-sm">
        <div class="card-body p-4">
            <form action="<?php echo APP_URL; ?>/training/workshops/evaluations/store" method="POST" id="evaluationForm">
                <?= csrf_field() ?>
                <input type="hidden" name="workshop_id" value="<?php echo $data['workshop']->id; ?>">

                <!-- 1. قسم الحضور -->
                <fieldset class="mb-4">
                    <legend class="form-legend"><i class="fas fa-users me-2"></i> 1. تقييم الحضور (الوزن: 80%)</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="expected_attendees" class="form-label fw-bold">عدد الحضور المتوقع</label>
                            <input type="number" id="expected_attendees" name="expected_attendees" class="form-control form-control-lg" required min="1" placeholder="مثال: 50">
                        </div>
                        <div class="col-md-6">
                            <label for="actual_attendees" class="form-label fw-bold">عدد الحضور الفعلي</label>
                            <input type="number" id="actual_attendees" name="actual_attendees" class="form-control form-control-lg" required min="0" placeholder="مثال: 45">
                        </div>
                    </div>
                </fieldset>

                <!-- 2. قسم انتظام البرنامج -->
                <fieldset class="mb-4">
                    <legend class="form-legend"><i class="fas fa-tasks me-2"></i> 2. تقييم انتظام البرنامج (الوزن: 20%)</legend>
                    <div class="form-group">
                        <label for="program_regularity_weighted_score" class="form-label fw-bold">أدخل الدرجة الموزونة (من 0 إلى 20)</label>
                        <input type="number" id="program_regularity_weighted_score" name="program_regularity_weighted_score" class="form-control form-control-lg" required step="0.1" min="0" max="20" placeholder="مثال: 18.5">
                        <div class="form-text mt-2">
                            هذه هي الدرجة النهائية لهذا المعيار. مثال: إذا كان البرنامج منتظمًا بالكامل، أدخل 20.
                        </div>
                    </div>
                </fieldset>

                <!-- 3. قسم الملاحظات -->
                <fieldset class="mb-4">
                    <legend class="form-legend"><i class="fas fa-sticky-note me-2"></i> 3. ملاحظات وتوصيات المقيم</legend>
                    <div class="form-group">
                        <textarea name="notes" class="form-control" rows="4" placeholder="أضف أي ملاحظات أو توصيات لتحسين الورش المستقبلية هنا..."></textarea>
                    </div>
                </fieldset>

                <!-- زر الحفظ -->
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i> حفظ التقييم النهائي
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- إضافة بعض الأنماط المخصصة لتحسين المظهر -->
<style>
    .form-legend {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--primary-color);
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--light-gray);
        margin-bottom: 1.5rem;
    }
    .form-container.card {
        border-top: 4px solid var(--primary-color);
    }
</style>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>

