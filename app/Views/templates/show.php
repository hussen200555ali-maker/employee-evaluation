<?php 
// تضمين الهيدر المشترك.
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="page-header">
    <div>
        <!-- عنوان الصفحة الرئيسي -->
        <h2><i class="fas fa-tasks"></i> إدارة معايير التقييم</h2>
        <!-- عنوان فرعي يوضح اسم القالب الحالي -->
        <p class="page-subtitle">أنت تقوم بتعديل القالب: "<?php echo htmlspecialchars($template->name); ?>"</p>
    </div>
    <a href="<?php echo APP_URL; ?>/templates" class="btn-back">
        <i class="fas fa-arrow-right"></i> العودة إلى قائمة القوالب
    </a>
</div>

<!-- حاوية رئيسية للصفحة مقسمة إلى عمودين -->
<div class="two-column-layout">
    <!-- العمود الأيسر: قائمة المعايير الحالية -->
    <div class="main-column">
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fas fa-stream"></i> المعايير الحالية</h3>
                <!-- سنضيف هنا إجمالي الأوزان لاحقاً -->
                <span class="badge-count" id="total-weight-badge">
                    إجمالي الأوزان: <?php 
                        $totalWeight = 0;
                        foreach ($criteria as $criterion) {
                            $totalWeight += $criterion->weight_percent;
                        }
                        echo $totalWeight;
                    ?>%
                </span>
            </div>
            <div class="card-body">
                <div id="criteria-list">
                    <?php if (!empty($criteria)): ?>
                        <?php foreach ($criteria as $criterion): ?>
                            <!-- بطاقة لكل معيار -->
                          <div class="criterion-item" data-id="<?php echo $criterion->id; ?>">
                            <div class="criterion-info">
                                <span class="criterion-name"><?php echo htmlspecialchars($criterion->name); ?></span>
                                <span class="criterion-weight"><?php echo htmlspecialchars($criterion->weight_percent); ?>%</span>
                            </div>
                            <div class="criterion-actions">
                                <!-- ===> التحديث هنا: إضافة data attributes <=== -->
                               <a href="<?php echo APP_URL . '/templates/' . $template->id . '/criteria/' . $criterion->id; ?>" class="btn-action-text" title="إدارة الأقسام التفصيلية">
                                  <i class="fas fa-list-ul"></i> إدارة الأقسام
                               </a>
                                <button class="btn-icon-sm btn-edit-criterion" 
                                        data-id="<?php echo $criterion->id; ?>"
                                        data-name="<?php echo htmlspecialchars($criterion->name); ?>"
                                        data-weight="<?php echo htmlspecialchars($criterion->weight_percent); ?>"
                                        title="تعديل">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn-icon-sm btn-delete-criterion" 
                                        data-id="<?php echo $criterion->id; ?>"
                                        data-name="<?php echo htmlspecialchars($criterion->name); ?>"
                                        title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data-sm">لم يتم إضافة أي معايير بعد. ابدأ بإضافة معيار من النموذج المجاور.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- العمود الأيمن: نموذج إضافة/تعديل معيار -->
    <div class="sidebar-column">
        <div class="content-card">
            <div class="card-header">
                <h3 id="criterion-form-title"><i class="fas fa-plus-circle"></i> إضافة معيار جديد</h3>
            </div>
            <div class="card-body">
                <!-- نموذج إضافة معيار جديد -->
                <form action="<?php echo APP_URL; ?>/criteria" method="POST" id="criterion-form">
                <?= csrf_field() ?>
                    <!-- حقل مخفي لمعرف القالب -->
                    <input type="hidden" name="template_id" value="<?php echo $template->id; ?>">
                    <!-- حقل مخفي لمعرف المعيار (سنستخدمه ظپظٹ التعديل) -->
                    <input type="hidden" name="id" id="criterion-id">

                    <div class="form-group mb-3">
                        <label for="criterion-name" class="form-label">اسم المعيار</label>
                        <input type="text" class="form-control" id="criterion-name" name="name" placeholder="مثال: المهارات الفنية" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="criterion-weight" class="form-label">الوزن النسبي (%)</label>
                        <input type="number" class="form-control" id="criterion-weight" name="weight_percent" min="1" max="100" placeholder="مثال: 40" required>
                        <div class="form-text">يجب أن يكون مجموع أوزان كل المعايير 100%.</div>
                    </div>
                    <div class="form-actions-split">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <span id="criterion-submit-btn-text">حفظ المعيار</span>
                        </button>
                        <button type="button" class="btn btn-secondary" id="cancel-edit-btn" style="display: none;">
                            إلغاء التعديل
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
// تضمين الفوتر المشترك.
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>

