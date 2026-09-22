<?php 
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="container-fluid mt-4">
    <div class="page-header mb-4">
        <h2 id="report-title-display"><i class="fas fa-chart-bar text-primary"></i> <span>...</span></h2>
        <p id="report-period-display" class="lead"></p>
    </div>

    <!-- ================================================== -->
    <!-- ▼▼▼ بداية لوحة التحكم الجديدة ▼▼▼ -->
    <!-- ================================================== -->
    <div class="card shadow-sm mb-4" id="report-controls">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-cogs"></i> لوحة تحكم التقرير</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-center">
                
                <div class="col-md-4">
                    <label for="report_title" class="form-label fw-bold">عنوان التقرير</label>
                    <input type="text" class="form-control" id="report_title" name="report_title">
                </div>

                <div class="col-md-2">
                    <label for="start_date" class="form-label fw-bold">من تاريخ</label>
                    <input type="date" class="form-control" id="start_date" name="start_date">
                </div>

                <div class="col-md-2">
                    <label for="end_date" class="form-label fw-bold">إلى تاريخ</label>
                    <input type="date" class="form-control" id="end_date" name="end_date">
                </div>

                <div class="col-md-2">
                    <label for="courses_weight" class="form-label fw-bold">وزن الدورات (%)</label>
                    <input type="number" class="form-control" id="courses_weight" name="courses_weight" min="0" max="100">
                </div>

                <div class="col-md-2">
                    <label for="workshops_weight" class="form-label fw-bold">وزن الورش (%)</label>
                    <input type="number" class="form-control" id="workshops_weight" name="workshops_weight" min="0" max="100">
                </div>

            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
    <div>
        <strong class="me-2">مجموع الأوزان:</strong>
        <span id="total-weight-badge" class="badge fs-6">0%</span>
    </div>
  
<div>
    <button id="print-report-btn" class="btn btn-info"><i class="fas fa-print"></i> طباعة التقرير</button>
    <button id="add-column-btn" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة عمود تقييم</button>
    <button id="save-report-btn" class="btn btn-success"><i class="fas fa-save"></i> حفظ التقرير</button>
</div>

</div>
          </div>
    </div>
    <!-- ================================================== -->
    <!-- ▲▲▲ نهاية لوحة التحكم الجديدة ▲▲▲ -->
    <!-- ================================================== -->

    <div id="report-container">
        <!-- سيتم حقن الجدول هنا بواسطة JavaScript -->
    </div>
</div>


<!-- ================================================== -->
<!-- ▼▼▼ بداية نافذة إضافة/تعديل العمود ▼▼▼ -->
<!-- ================================================== -->
<div class="modal fade" id="column-editor-modal" tabindex="-1" aria-labelledby="columnEditorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="columnEditorModalLabel">إضافة/تعديل عمود تقييم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="column-editor-form" novalidate>
                    <!-- حقل مخفي لتخزين مؤشر العمود عند التعديل -->
                    <input type="hidden" id="column-edit-index" value="-1">

                    <div class="mb-3">
                        <label for="column-source" class="form-label">مصدر البيانات</label>
                        <select class="form-select" id="column-source" required>
                            <option value="" selected disabled>-- اختر مصدر بيانات العمود --</option>
                            <optgroup label="استمارات تقييم كاملة">
                                <?php foreach ($data['templates'] as $template): ?>
                                    <option value="template-<?php echo $template->id; ?>"><?php echo htmlspecialchars($template->template_name); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="معايير محددة">
                                <?php foreach ($data['criteria'] as $criterion): ?>
                                    <option value="criterion-<?php echo $criterion->id; ?>"><?php echo htmlspecialchars($criterion->criterion_name); ?> (من: <?php echo htmlspecialchars($criterion->template_name); ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="column-weight" class="form-label">الوزن النسبي (%)</label>
                        <input type="number" class="form-control" id="column-weight" min="0" max="100" step="1" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" id="save-column-btn" class="btn btn-primary">حفظ التغييرات</button>
            </div>
        </div>
    </div>
</div>
<!-- ================================================== -->
<!-- ▲▲▲ نهاية نافذة إضافة/تعديل العمود ▲▲▲ -->
<!-- ================================================== -->


<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>

<script src="<?php echo APP_URL; ?>/public/js/report-builder.js"></script>
