<?php
// ملف: app/Views/reports/workshops/partials/workshops.php

if (!empty($data['groupedWorkshops'])): ?>
    <!-- زر طباعة تقرير هذا القسم -->
    <div class="text-start mb-3 no-print">
        <button class="btn btn-outline-info btn-sm js-print-workshop-btn" data-level="department" data-id="<?php echo $data['groupedWorkshops'][0]['instances'][0]->department_id; ?>">
            <i class="fas fa-print me-2"></i>طباعة تقرير هذا القسم
        </button>
    </div>

    <?php foreach ($data['groupedWorkshops'] as $group): ?>
        <!-- عنوان مجموعة الورش -->
        <h6 class="mt-3 fw-bold text-primary-emphasis"><?php echo htmlspecialchars($group['workshop_title']); ?></h6>
        
        <!-- قائمة بالورش المنفذة تحت هذا العنوان -->
        <ul class="list-group list-group-flush mb-3">
            <?php foreach ($group['instances'] as $instance): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-calendar-check me-2 text-muted"></i>
                        <a href="<?php echo APP_URL . '/training/workshops/evaluations/show/' . $instance->evaluation_id; ?>" target="_blank">
                            ورشة بتاريخ <?php echo $instance->start_date; ?>
                        </a>
                    </div>
                    <span class="badge bg-info rounded-pill">
                        النتيجة: <?php echo number_format($instance->final_score ?? 0, 1); ?>%
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>

<?php else: ?>
    <div class="alert alert-light p-2 text-center">لا توجد ورش عمل مقيّمة في هذا القسم.</div>
<?php endif; ?>
