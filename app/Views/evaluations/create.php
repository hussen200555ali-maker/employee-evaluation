<?php 
// المسار: app/Views/evaluations/create.php
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="container">
    <div class="page-header">
        <h2><i class="fas fa-play-circle text-primary"></i> بدء تقييم موظف جديد</h2>
    </div>

    <div class="form-container">
        <form action="<?php echo APP_URL; ?>/evaluations/store" method="POST" id="start-evaluation-form">
            <?= csrf_field() ?>

            <h4>1. تحديد أطراف التقييم</h4>
            <div class="row g-3 mb-3">
                <!-- حقل البحث عن الموظف المُراد تقييمه -->
                <div class="col-md-6">
                    <label for="evaluated_employee_search" class="form-label fw-bold">الموظف المُراد تقييمه</label>
                    <div class="search-wrapper">
                        <input type="text" class="form-control form-control-lg" id="evaluated_employee_search" placeholder="ابحث بالاسم أو الرقم الوظيفي..." autocomplete="off">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <input type="hidden" name="evaluated_employee_id" id="evaluated_employee_id" required>
                    <div id="evaluated-search-results" class="search-results"></div>
                </div>

                <!-- حقل البحث عن الموظف المُقيِّم -->
                <div class="col-md-6">
                    <label for="evaluator_employee_search" class="form-label fw-bold">الموظف القائم بالتقييم (المُقيِّم)</label>
                    <div class="search-wrapper">
                        <input type="text" class="form-control form-control-lg" id="evaluator_employee_search" placeholder="ابحث بالاسم أو الرقم الوظيفي..." autocomplete="off">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <input type="hidden" name="evaluator_employee_id" id="evaluator_employee_id">
                    <div id="evaluator-search-results" class="search-results"></div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="is_external_evaluator" name="is_external_evaluator" value="1">
                        <label class="form-check-label" for="is_external_evaluator">مُقيّم خارجي (ليس له حساب في النظام)</label>
                    </div>
                    <div id="external-evaluator-fields" style="display:none;" class="mt-2">
                        <input type="text" class="form-control" name="evaluator_name" id="evaluator_name" placeholder="اسم المقيّم الخارجي">
                        <input type="text" class="form-control mt-2" name="evaluator_title" id="evaluator_title" placeholder="صفة المقيّم (مثال: مستشار)">
                        <small class="text-muted">أدخل الاسم أو الصفة على الأقل</small>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <h4>2. تحديد الاستمارة والتاريخ</h4>
            <div class="row g-3">
                <!-- قائمة القوالب (سيتم ملؤها بواسطة الجافاسكريبت) -->
                <div class="col-md-8">
                    <label for="template_id" class="form-label fw-bold">قالب التقييم</label>
                    <select class="form-select form-select-lg" id="template_id" name="template_id" required disabled>
                        <option value="">اختر الموظف المُراد تقييمه أولاً...</option>
                    </select>
                </div>
                <!-- حقل تاريخ التقييم -->
                <div class="col-md-4">
                    <label for="evaluation_date" class="form-label fw-bold">تاريخ التقييم</label>
                    <input type="date" class="form-control form-control-lg" id="evaluation_date" name="evaluation_date" value="<?php echo date('Y-m-d'); ?>" required>
             <small class="form-text text-success fw-bold mt-1 d-block" id="hijri-date-display">
                        <?php 
                            $hijri_today = convertToHijri(date('Y-m-d'));
                            echo 'الموافق: ' . ($hijri_today ? $hijri_today->full_date : 'غير متاح');
                        ?>
                    </small>
                  </div>
            </div>

            <div class="form-actions mt-4">
                <button type="submit" class="btn-submit-form btn-lg" disabled>
                    <i class="fas fa-arrow-circle-right"></i> بدء التقييم وتعبئة الاستمارة
                </button>
            </div>

        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const evaluationDateInput = document.getElementById('evaluation_date');
    const hijriDateDisplay = document.getElementById('hijri-date-display');

    if (evaluationDateInput && hijriDateDisplay) {
        evaluationDateInput.addEventListener('change', function() {
            const selectedDate = this.value;

            if (!selectedDate) {
                hijriDateDisplay.textContent = '';
                return;
            }

            hijriDateDisplay.textContent = 'جارِ التحويل...';
            hijriDateDisplay.classList.remove('text-danger', 'text-success');
            hijriDateDisplay.classList.add('text-muted');

            fetch(`<?php echo APP_URL; ?>/api/to-hijri?date=${selectedDate}`)
                .then(response => response.json())
                .then(data => {
                    if (data.hijri) {
                        hijriDateDisplay.textContent = 'الموافق: ' + data.hijri;
                        hijriDateDisplay.classList.remove('text-muted');
                        hijriDateDisplay.classList.add('text-success');
                    } else {
                        hijriDateDisplay.textContent = 'خطأ في تحويل التاريخ.';
                        hijriDateDisplay.classList.add('text-danger');
                    }
                })
                .catch(error => {
                    console.error('Error fetching Hijri date:', error);
                    hijriDateDisplay.textContent = 'فشل الاتصال بالخادم.';
                    hijriDateDisplay.classList.add('text-danger');
                });
        });
    }
});
</script>
<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
