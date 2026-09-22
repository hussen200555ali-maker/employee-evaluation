<?php 
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h2><i class="fas fa-chart-pie"></i> لوحة معلومات أداء التدريب</h2>
    <!-- زر الطباعة الشامل -->
  
</div>

<div class="accordion" id="branchesAccordion">
    <?php if (!empty($data['reportData'])): ?>
        <?php foreach ($data['reportData'] as $branch): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-<?php echo $branch->branch_id; ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $branch->branch_id; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $branch->branch_id; ?>">
                        <div class="d-flex justify-content-between w-100 pe-4">
                            <span>
                                <i class="fas fa-building me-2"></i>
                                <strong><?php echo htmlspecialchars($branch->branch_name); ?></strong>
                            </span>
                            <span class="badge bg-primary rounded-pill">
                                متوسط الأداء: <?php echo number_format($branch->avg_final_score ?? 0, 2); ?>%
                            </span>
                        </div>
                    </button>
                </h2>
                <div id="collapse-<?php echo $branch->branch_id; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $branch->branch_id; ?>" data-bs-parent="#branchesAccordion">
                    <div class="accordion-body">
                        <!-- سيتم تحميل الأقسام هنا ديناميكياً -->
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">جاري تحميل بيانات الأقسام...</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">لا توجد بيانات لعرضها.</div>
    <?php endif; ?>
</div>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
