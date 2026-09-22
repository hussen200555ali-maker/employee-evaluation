<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<div class="container" style="max-width: 900px;">
    <div class="page-header">
        <h2><i class="fas fa-chart-bar"></i> عرض تقييم ورشة تدريبية</h2>
        <a href="<?php echo APP_URL; ?>/training/workshops" class="btn-back">
            <i class="fas fa-arrow-right"></i> العودة للقائمة
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="card-title mb-3">
                <i class="fas fa-tools"></i> <?php echo htmlspecialchars($data['evaluation']->workshop_title); ?>
            </h4>

            <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item">
                    <strong>الفرع:</strong> <?php echo htmlspecialchars($data['evaluation']->branch_name ?? 'غير محدد'); ?>
                </li>
                <li class="list-group-item">
                    <strong>القسم:</strong> <?php echo htmlspecialchars($data['evaluation']->department_name ?? 'غير محدد'); ?>
                </li>
                <li class="list-group-item">
                    <strong>الفترة:</strong> 
                    <?php echo $data['evaluation']->start_date; ?> - <?php echo $data['evaluation']->end_date; ?>
                </li>
            </ul>

            <div class="row text-center">
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light">
                        <h5><i class="fas fa-users"></i> الحضور</h5>
                        <p class="fs-4 text-primary"><?php echo $data['evaluation']->attendance_score; ?> / 80</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light">
                        <h5><i class="fas fa-clock"></i> انتظام البرنامج</h5>
                        <p class="fs-4 text-success"><?php echo $data['evaluation']->program_regularity_weighted_score; ?> / 20</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light">
                        <h5><i class="fas fa-star"></i> النتيجة النهائية</h5>
                        <p class="fs-4 text-dark fw-bold"><?php echo $data['evaluation']->final_score; ?> / 100</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($data['evaluation']->notes)): ?>
            <div class="mt-4">
                <h5><i class="fas fa-sticky-note"></i> ملاحظات</h5>
                <p class="bg-light p-3 rounded"><?php echo nl2br(htmlspecialchars($data['evaluation']->notes)); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
