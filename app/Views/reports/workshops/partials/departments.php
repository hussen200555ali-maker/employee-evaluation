<?php if (!empty($departments)): ?>
<div class="accordion" id="departmentsAccordion-branch-<?php echo htmlspecialchars($_GET['id'] ?? '0'); ?>">
    <?php foreach ($departments as $dept): ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-dept-<?php echo $dept->id; ?>">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                        data-bs-target="#collapse-dept-<?php echo $dept->id; ?>" onclick="loadWorkshops(<?php echo $dept->id; ?>)">
                    
                    <div class="d-flex justify-content-between align-items-center w-100 pe-3">
                        <span>
                            <i class="fas fa-sitemap text-muted me-2"></i>
                            <?php echo htmlspecialchars($dept->name); ?>
                        </span>
                        <div>
                            <span class="badge bg-primary rounded-pill me-2">
                                <?php echo $dept->workshop_count; ?> ورشة
                            </span>
                            <span class="badge bg-success rounded-pill">
                                متوسط التقييم: <?php echo round($dept->average_score ?? 0, 1); ?>%
                            </span>
                        </div>
                    </div>

                </button>
            </h2>
            <div id="collapse-dept-<?php echo $dept->id; ?>" class="accordion-collapse collapse" 
                 data-bs-parent="#departmentsAccordion-branch-<?php echo htmlspecialchars($_GET['id'] ?? '0'); ?>">
                <div class="accordion-body" id="workshops-dept-<?php echo $dept->id; ?>">
                    <!-- الورش ستحمل هنا عند الضغط -->
                    <div class="text-center p-3">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
