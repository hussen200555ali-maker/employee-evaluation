<?php 
// في ملف /app/Views/training/courses/index.php
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>
<div class="container mt-4">

    <!-- العنوان -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus-circle"></i> إضافة دورة جديدة</h2>
        <a href="<?php echo APP_URL; ?>/training/courses/catalog" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> الرجوع
        </a>
    </div>

    <!-- النموذج -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="<?php echo APP_URL; ?>/training/courses/catalog/store" method="POST">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="title" class="form-label">اسم الدورة <span class="text-danger">*</span></label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="اكتب اسم الدورة" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">الوصف</label>
                    <textarea id="description" name="description" rows="4" class="form-control" placeholder="اكتب وصفاً مختصراً"></textarea>
                </div>

                <div class="mb-3">
                    <label for="category" class="form-label">التصنيف</label>
                    <input type="text" id="category" name="category" class="form-control" placeholder="مثال: إدارية، تقنية، سلامة">
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> حفظ الدورة
                </button>
            </form>
        </div>
    </div>

</div>


<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>

