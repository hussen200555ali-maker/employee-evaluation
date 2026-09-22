<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<style>
    /* أنماط البطاقات العلوية */
    .kpi-card {
        border: none;
        border-radius: 15px;
        color: white;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }
    .kpi-card .card-body {
        position: relative;
        z-index: 2;
    }
    .kpi-card .icon-bg {
        position: absolute;
        top: 50%;
        left: -20px;
        transform: translateY(-50%);
        font-size: 6rem;
        opacity: 0.15;
        z-index: 1;
        transition: transform 0.4s ease;
    }
    .kpi-card:hover .icon-bg {
        transform: translateY(-50%) scale(1.1) rotate(-5deg);
    }
    .kpi-card .display-4 {
        font-weight: 700;
    }
    .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0dcaf0); }
    .bg-gradient-success { background: linear-gradient(45deg, #198754, #20c997); }
    .bg-gradient-danger { background: linear-gradient(45deg, #dc3545, #fd7e14); }

    /* أنماط نوافذ الشرح */
    .help-icon {
        cursor: pointer;
        opacity: 0.6;
        transition: opacity 0.2s;
    }
    .help-icon:hover {
        opacity: 1;
    }
    .explanation-card {
        background-color: #f8f9fa;
        border-left: 5px solid;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .explanation-card.step-1 { border-color: #fd7e14; }
    .explanation-card.step-2 { border-color: #198754; }
    .explanation-card.step-3 { border-color: #0d6efd; }
    .explanation-card h5 {
        font-weight: 700;
        color: #343a40;
    }
    .explanation-card .icon-wrapper {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }
    .quadrant-explanation-card {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        color: white;
    }
    .quadrant-explanation-card h6 { font-weight: 700; }

    /* الأنماط الجديدة للهيكل الشجري */
    .hierarchy-tree {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .entity-header {
        transition: all 0.3s ease;
        border-radius: 8px;
    }
    .entity-header:hover {
        background-color: #f8f9fa;
    }
    .branch-header {
        border-left: 4px solid #0d6efd;
    }
    .department-header {
        border-left: 4px solid #20c997;
    }
    .employee-card {
        border-left: 4px solid #198754;
    }
    .competency-section {
        border-radius: 6px;
        padding: 10px;
        background-color: #f8f9fa;
    }
    .competency-item {
        transition: background-color 0.2s;
    }
    .competency-item:hover {
        background-color: #e9ecef;
    }
    .toggle-icon {
        transition: transform 0.3s ease;
        width: 16px;
        text-align: center;
    }
    /* تحسينات إضافية للتصنيف الجديد */
.progress {
    border-radius: 10px;
    overflow: hidden;
}
.progress-bar {
    transition: width 0.3s ease;
}
.badge.bg-warning {
    color: #000 !important;
}
.analysis-section h4 {
    border-right: 4px solid;
    padding-right: 15px;
    margin-bottom: 20px;
}
.analysis-section h4.text-success { border-color: #198754; }
.analysis-section h4.text-warning { border-color: #ffc107; }
.analysis-section h4.text-danger { border-color: #dc3545; }

/* ================================================== */
/* تحسينات لنافذة تفاصيل الكفاءة - النسخة المحسنة */
/* ================================================== */
.details-modal {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.performance-summary-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    color: white;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.performance-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    backdrop-filter: blur(10px);
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
}

.employee-category-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.employee-category-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.category-header {
    border: none;
    padding: 1.25rem 1.5rem;
    font-weight: 600;
}

.employee-list {
    max-height: 300px;
    overflow-y: auto;
}

.employee-item {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s ease;
}

.employee-item:hover {
    background-color: #f8f9fa;
}

.employee-item:last-child {
    border-bottom: none;
}

.employee-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(45deg, #0d6efd, #0dcaf0);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    margin-right: 1rem;
    flex-shrink: 0;
}

.employee-info {
    flex: 1;
}

.employee-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.employee-position {
    font-size: 0.85rem;
    color: #6c757d;
}

.employee-score {
    text-align: right;
    flex-shrink: 0;
}

.score-badge {
    font-size: 1rem;
    font-weight: 700;
    padding: 0.5rem 0.75rem;
    border-radius: 25px;
}

.performance-bar {
    height: 6px;
    background-color: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.performance-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}

/* ألوان التصنيفات */
.category-strong .category-header { background: linear-gradient(135deg, #198754, #20c997); color: white; }
.category-medium .category-header { background: linear-gradient(135deg, #ffc107, #fd7e14); color: white; }
.category-weak .category-header { background: linear-gradient(135deg, #dc3545, #e83e8c); color: white; }

.category-strong .performance-fill { background: linear-gradient(90deg, #198754, #20c997); }
.category-medium .performance-fill { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.category-weak .performance-fill { background: linear-gradient(90deg, #dc3545, #e83e8c); }

/* شريط التمرير المخصص */
.employee-list::-webkit-scrollbar {
    width: 6px;
}

.employee-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.employee-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.employee-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* رأس النافذة المحسن */
.modal-header-custom {
    background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    color: white;
    border-bottom: none;
    padding: 1.5rem;
}

.modal-header-custom .modal-title {
    font-weight: 700;
    font-size: 1.3rem;
}

.modal-header-custom .btn-close {
    filter: invert(1);
    opacity: 0.8;
}

.modal-header-custom .btn-close:hover {
    opacity: 1;
}

/* مخطط التوزيع الدائري */
.distribution-chart {
    position: relative;
    height: 120px;
}

.distribution-progress {
    height: 120px;
}

.distribution-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

/* تحسينات للبطاقات */
.competency-breakdown-card {
    border-left: 4px solid;
    transition: all 0.3s ease;
}

.competency-breakdown-card:hover {
    transform: translateX(5px);
}

.breakdown-strong { border-left-color: #198754; }
.breakdown-medium { border-left-color: #ffc107; }
.breakdown-weak { border-left-color: #dc3545; }
/* تحسينات توزيع أداء الموظفين في الجدول */
.distribution-container {
    position: relative;
    padding: 10px 0;
}

.distribution-visual {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.distribution-bars {
    flex: 1;
    display: flex;
    height: 24px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 0 15px;
}

.distribution-bar {
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
}

.distribution-bar:hover {
    transform: scaleY(1.1);
    z-index: 2;
}

.distribution-bar.strong { background: linear-gradient(135deg, #198754, #20c997); }
.distribution-bar.medium { background: linear-gradient(135deg, #ffc107, #fd7e14); }
.distribution-bar.weak { background: linear-gradient(135deg, #dc3545, #e83e8c); }

.distribution-counts {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 5px;
}

.distribution-count-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 5px 8px;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
    min-width: 70px;
}

.distribution-count-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.distribution-count-item.strong:hover { background: rgba(25, 135, 84, 0.1); }
.distribution-count-item.medium:hover { background: rgba(255, 193, 7, 0.1); }
.distribution-count-item.weak:hover { background: rgba(220, 53, 69, 0.1); }

.count-value {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 2px;
}

.count-label {
    font-size: 0.75rem;
    opacity: 0.8;
}

.distribution-percentages {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    font-size: 0.8rem;
    color: #6c757d;
}

.percentage-item {
    text-align: center;
    flex: 1;
}

/* تحسينات إضافية للجدول */
.competency-row:hover .distribution-bar {
    opacity: 0.9;
}

.competency-row:hover .distribution-count-item {
    background: rgba(0,0,0,0.02);
}

/* تلميحات الأداء */
.distribution-tooltip {
    position: absolute;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    z-index: 1000;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s ease;
    transform: translateY(-10px);
}

.distribution-bar:hover .distribution-tooltip {
    opacity: 1;
    transform: translateY(-15px);
}

/* مخطط دائري صغير */
.mini-pie-chart {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    position: relative;
    background: conic-gradient(
        #198754 0% var(--strong-percent, 0%),
        #ffc107 var(--strong-percent, 0%) var(--medium-percent, 0%),
        #dc3545 var(--medium-percent, 0%) 100%
    );
    margin: 0 10px;
    flex-shrink: 0;
}

.pie-chart-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 600;
    color: #495057;
}

/* تحسينات للعرض على الشاشات الصغيرة */
@media (max-width: 768px) {
    .distribution-visual {
        flex-direction: column;
        align-items: center;
    }
    
    .distribution-bars {
        margin: 10px 0;
        width: 100%;
    }
    
    .mini-pie-chart {
        margin: 10px 0;
    }
}
/* تحسينات عمود الكفاءة والإجراءات */
.competency-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 8px 0;
}

.competency-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.competency-info {
    flex: 1;
    min-width: 0;
}

.competency-name {
    font-weight: 700;
    font-size: 1rem;
    color: #2c3e50;
    margin-bottom: 4px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.competency-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}

.competency-criterion {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: #6c757d;
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 6px;
}

.competency-template {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #8a8a8a;
    background: #f0f0f0;
    padding: 3px 6px;
    border-radius: 4px;
}

.competency-stats {
    display: flex;
    gap: 8px;
    margin-top: 6px;
}

.stat-badge {
    font-size: 0.75rem;
    padding: 2px 6px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* تحسينات زر الإجراءات */
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.btn-details {
    background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.2);
    position: relative;
    overflow: hidden;
}

.btn-details::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-details:hover::before {
    left: 100%;
}

.btn-details:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
}

.btn-details:active {
    transform: translateY(0);
}

.btn-details i {
    font-size: 0.9rem;
}

/* تحسينات إضافية للصفوف */
.competency-row {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.competency-row:hover {
    background: linear-gradient(135deg, #f8f9ff, #f0f8ff) !important;
    border-left-color: #0d6efd;
    transform: translateX(4px);
}

.competency-row:hover .competency-icon {
    transform: scale(1.1) rotate(5deg);
}

/* ألوان مختلفة لأنواع الكفاءات */
.competency-strong .competency-icon { background: linear-gradient(135deg, #198754, #20c997); color: white; }
.competency-medium .competency-icon { background: linear-gradient(135deg, #ffc107, #fd7e14); color: white; }
.competency-weak .competency-icon { background: linear-gradient(135deg, #dc3545, #e83e8c); color: white; }

.competency-strong .competency-name { color: #198754; }
.competency-medium .competency-name { color: #e67700; }
.competency-weak .competency-name { color: #dc3545; }

/* تحسينات للعرض على الشاشات الصغيرة */
@media (max-width: 768px) {
    .competency-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .competency-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: stretch;
    }
    
    .btn-details {
        justify-content: center;
    }
}

/* تلميحات إضافية */
.competency-tooltip {
    position: relative;
    cursor: help;
}

.competency-tooltip:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    white-space: nowrap;
    z-index: 1000;
    margin-bottom: 5px;
}

/* مؤشرات الأداء */
.performance-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 6px;
}

.indicator-strong { background: #198754; }
.indicator-medium { background: #ffc107; }
.indicator-weak { background: #dc3545; }
</style>

<div class="container mt-4">
    <!-- ================================================== -->
    <!-- الجزء العلوي: الملخص الاستراتيجي وبطاقة المعلومات -->
    <!-- ================================================== -->
    <div class="row mb-4">
        <div class="col-lg-3 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column text-center p-4">
                    <i class="fas <?php echo htmlspecialchars($entityInfo['icon'] ?? 'fa-question-circle'); ?> fa-2x text-primary mb-3"></i>
                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($entityInfo['name']); ?></h5>
                    <p class="text-muted small"><?php echo htmlspecialchars($entityInfo['type']); ?></p>
                    <div class="mt-auto border-top pt-3">
                        <p class="mb-1 small"><strong>المدير:</strong> <?php echo htmlspecialchars($entityInfo['manager']); ?></p>
                        <p class="mb-0 small"><strong>الموظفين:</strong> <?php echo $entityInfo['employee_count']; ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="row h-100">
                <div class="col-md-4 mb-4">
                    <div class="card kpi-card bg-gradient-primary h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <i class="fas fa-rocket icon-bg"></i>
                            <h2 class="display-4"><?php echo $analysis['stats']['average_performance']; ?>%</h2>
                            <p class="lead mb-0">متوسط الأداء العام</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card kpi-card bg-gradient-success h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <i class="fas fa-star icon-bg"></i>
                            <h2 class="display-4"><?php echo $analysis['stats']['strong_competencies_count']; ?></h2>
                            <p class="lead mb-0">كفاءات قوية</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card kpi-card bg-gradient-danger h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <i class="fas fa-flag icon-bg"></i>
                            <h2 class="display-4"><?php echo $analysis['stats']['weak_competencies_count']; ?></h2>
                            <p class="lead mb-0">كفاءات للتطوير</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================== -->
    <!-- الجزء الأوسط: التحليل المرئي (التبويبات والمخططات) -->
    <!-- ================================================== -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="analysisTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="competency-analysis-tab" data-bs-toggle="tab" data-bs-target="#competency-analysis" type="button" role="tab">
                        تحليل الكفاءات والمعايير
                        <i class="fas fa-question-circle text-muted ms-2 help-icon" data-bs-toggle="modal" data-bs-target="#matrixExplanationModal" title="كيف يعمل هذا التحليل؟"></i>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="employee-analysis-tab" data-bs-toggle="tab" data-bs-target="#employee-analysis" type="button" role="tab">
                        تحليل الموظفين
                        <i class="fas fa-question-circle text-muted ms-2 help-icon" data-bs-toggle="modal" data-bs-target="#employeeExplanationModal" title="كيف يعمل هذا التحليل؟"></i>
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="analysisTabsContent">
                <div class="tab-pane fade show active" id="competency-analysis" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-7 mb-4 mb-lg-0">
                            <h5><i class="fas fa-chart-bar text-info"></i> متوسط أداء المعايير الرئيسية</h5>
                            <div style="height: 350px; position: relative;"><canvas id="criteriaBarChart"></canvas></div>
                        </div>
                        <div class="col-lg-5">
                            <h5><i class="fas fa-th text-warning"></i> مصفوفة الأهمية والأداء</h5>
                            <div style="height: 350px; position: relative;"><canvas id="quadrantChart"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="employee-analysis" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="fas fa-users me-2"></i>التحليل التفصيلي للموظفين</h5>
                        <div>
                            <button class="btn btn-outline-secondary btn-sm" id="expand-all">
                                <i class="fas fa-expand"></i> فتح الكل
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" id="collapse-all">
                                <i class="fas fa-compress"></i> طي الكل
                            </button>
                        </div>
                    </div>
                    
                    <!-- الهيكل الشجري -->
                    <div class="hierarchy-tree" id="employee-hierarchy">
                        <?php 
                        // تضمين دوال العرض للهيكل الشجري
                        echo renderHierarchicalData($hierarchicalData ?? []); 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================== -->
    <!-- الجزء السفلي: التفاصيل الجدولية والفلاتر -->
    <!-- ================================================== -->
    <h3 class="mt-5">التفاصيل التحليلية للكفاءات</h3>
    <hr>
    <div class="card mb-4 no-print">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6"><label for="search-competency" class="form-label">ابحث عن كفاءة:</label><input type="text" id="search-competency" class="form-control" placeholder="اكتب اسم كفاءة..."></div>
                <div class="col-lg-3 col-md-6"><label for="filter-classification" class="form-label">عرض حسب التصنيف:</label><select id="filter-classification" class="form-select"><option value="all" selected>عرض الكل</option><option value="strong">نقاط القوة فقط</option><option value="medium">الأداء المتوسط فقط</option><option value="weak">جوانب التطوير فقط</option></select></div>
                <div class="col-lg-3 col-md-6"><label for="filter-position" class="form-label">فلترة حسب المنصب:</label><select id="filter-position" class="form-select"><option value="all" selected>كل المناصب</option><?php foreach ($analysis['available_positions'] as $position): ?><option value="<?php echo htmlspecialchars($position); ?>"><?php echo htmlspecialchars($position); ?></option><?php endforeach; ?></select></div>
                <div class="col-lg-3 col-md-6"><label for="filter-year" class="form-label">فلترة حسب السنة:</label><select id="filter-year" class="form-select"><option value="all" selected>كل السنوات</option><?php $years = $analysis['available_years']; sort($years, SORT_NUMERIC); foreach (array_reverse($years) as $year): ?><option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option><?php endforeach; ?></select></div>
                   <!-- ▼▼▼ هذا هو الزر الجديد الذي أضفناه ▼▼▼ -->
            <div class="col-lg-3 col-md-12">
                <div class="d-grid">
                  <a href="<?php echo APP_URL; ?>/analysis/print/level?level=<?php echo $filters['level'] ?? 'company'; ?>&target_id=<?php echo $filters['target_id'] ?? ''; ?>" 
   id="print-report-btn" 
   class="btn btn-secondary btn-lg" 
   target="_blank">
    <i class="fas fa-print me-2"></i> طباعة تقرير احترافي
</a>
                </div>
            </div>
            </div>
        </div>
    </div>
    <div id="analysis-results">
    <?php
  function render_group_table_v3($title, $data, $classification, $icon, $color) {
    echo "<div class='analysis-section' data-classification='{$classification}'>";
    echo "<h4 class='mt-4 text-{$color}'><i class='fas {$icon}'></i> {$title}</h4>";
    if (empty($data)) {
        echo "<p class='text-muted'>لا توجد كفاءات ضمن هذا النطاق.</p>";
    } else {
        echo "<div class='table-responsive'><table class='table table-hover table-bordered'>";
        echo "<thead class='table-light'><tr>
                <th style='width: 30%'>الكفاءة</th>
                <th class='text-center' style='width: 15%'>متوسط الأداء</th>
                <th class='text-center' style='width: 40%'>توزيع أداء الموظفين</th>
                <th class='text-center' style='width: 15%'>الإجراءات</th>
              </tr></thead><tbody>";
        
        foreach ($data as $item) {
            $details_json = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
            $performance_icon = getPerformanceIcon($item['average_score']);
            $indicator_class = getPerformanceIndicatorClass($item['average_score']);
            
            echo "<tr class='competency-row competency-{$classification}' data-name='" . strtolower(htmlspecialchars($item['section_name'])) . "'>";
            
            // === عمود الكفاءة المحسن ===
            echo "<td>
                    <div class='competency-header'>
                        <div class='competency-icon'>
                            <i class='fas {$performance_icon}'></i>
                        </div>
                        <div class='competency-info'>
                            <div class='competency-name' title='{$item['section_name']}'>
                                <span class='performance-indicator {$indicator_class}'></span>
                                {$item['section_name']}
                            </div>
                            <div class='competency-meta'>
                                <div class='competency-criterion competency-tooltip' data-tooltip='المعيار الرئيسي'>
                                    <i class='fas fa-project-diagram text-info'></i>
                                    <span>" . truncateText($item['criterion_name'], 25) . "</span>
                                </div>
                                <div class='competency-template competency-tooltip' data-tooltip='نموذج التقييم'>
                                    <i class='fas fa-clipboard-list text-secondary'></i>
                                    <span>" . truncateText($item['template_name'], 20) . "</span>
                                </div>
                            </div>
                            <div class='competency-stats'>
                                <span class='stat-badge bg-light text-dark'>
                                    <i class='fas fa-chart-line me-1'></i>
                                    {$item['average_score']}% متوسط
                                </span>
                                <span class='stat-badge bg-light text-dark'>
                                    <i class='fas fa-users me-1'></i>
                                    {$item['total_employees']} موظف
                                </span>
                            </div>
                        </div>
                    </div>
                  </td>";
            
            // === عمود متوسط الأداء ===
            echo "<td class='text-center align-middle'>
                    <div class='d-flex flex-column align-items-center'>
                        <span class='badge bg-{$color} fs-6 px-3 py-2 mb-1' style='min-width: 70px;'>
                            {$item['average_score']}%
                        </span>
                        <small class='text-muted'>
                            " . getPerformanceLabel($item['average_score']) . "
                        </small>
                    </div>
                  </td>";
            
            // === عمود توزيع الأداء (المحسن سابقاً) ===
            echo "<td class='text-center'>
                    <div class='distribution-container'>
                        <div class='distribution-visual'>
                            <div class='mini-pie-chart' 
                                 style='--strong-percent: {$item['strong_percentage']}%; 
                                        --medium-percent: " . ($item['strong_percentage'] + $item['medium_percentage']) . "%;'>
                                <div class='pie-chart-center'>
                                    " . round($item['average_score']) . "%
                                </div>
                            </div>
                            
                            <div class='distribution-bars'>
                                <div class='distribution-bar strong' 
                                     style='width: {$item['strong_percentage']}%'
                                     title='أداء قوي: {$item['strong_count']} موظف ({$item['strong_percentage']}%)'>
                                    <div class='distribution-tooltip'>
                                        أداء قوي: {$item['strong_count']} موظف ({$item['strong_percentage']}%)
                                    </div>
                                </div>
                                <div class='distribution-bar medium' 
                                     style='width: {$item['medium_percentage']}%'
                                     title='أداء متوسط: {$item['medium_count']} موظف ({$item['medium_percentage']}%)'>
                                    <div class='distribution-tooltip'>
                                        أداء متوسط: {$item['medium_count']} موظف ({$item['medium_percentage']}%)
                                    </div>
                                </div>
                                <div class='distribution-bar weak' 
                                     style='width: {$item['weak_percentage']}%'
                                     title='بحاجة تطوير: {$item['weak_count']} موظف ({$item['weak_percentage']}%)'>
                                    <div class='distribution-tooltip'>
                                        بحاجة تطوير: {$item['weak_count']} موظف ({$item['weak_percentage']}%)
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class='distribution-counts'>
                            <div class='distribution-count-item strong' title='{$item['strong_count']} موظف بأداء قوي'>
                                <span class='count-value text-success'>{$item['strong_count']}</span>
                                <span class='count-label'>قوي</span>
                            </div>
                            <div class='distribution-count-item medium' title='{$item['medium_count']} موظف بأداء متوسط'>
                                <span class='count-value text-warning'>{$item['medium_count']}</span>
                                <span class='count-label'>متوسط</span>
                            </div>
                            <div class='distribution-count-item weak' title='{$item['weak_count']} موظف بحاجة تطوير'>
                                <span class='count-value text-danger'>{$item['weak_count']}</span>
                                <span class='count-label'>بحاجة تطوير</span>
                            </div>
                        </div>
                    </div>
                  </td>";
            
            // === عمود الإجراءات المحسن ===
            echo "<td class='text-center align-middle'>
                    <div class='action-buttons'>
                        <button class='btn btn-details' 
                                data-bs-toggle='modal' 
                                data-bs-target='#detailsModal' 
                                data-details='{$details_json}'
                                title='عرض التحليل التفصيلي لهذه الكفاءة'>
                            <i class='fas fa-chart-bar'></i>
                            <span>تحليل مفصل</span>
                        </button>
                    </div>
                  </td>";
            
            echo "</tr>";
        }
        echo "</tbody></table></div>";
    }
    echo "</div>";
}

// دوال مساعدة جديدة
function getPerformanceIcon($score) {
    if ($score >= 80) return 'fa-star';
    if ($score >= 60) return 'fa-check-circle';
    return 'fa-flag';
}

function getPerformanceIndicatorClass($score) {
    if ($score >= 80) return 'indicator-strong';
    if ($score >= 60) return 'indicator-medium';
    return 'indicator-weak';
}

function getPerformanceLabel($score) {
    if ($score >= 80) return 'متميز';
    if ($score >= 60) return 'جيد';
    if ($score >= 40) return 'مقبول';
    return 'بحاجة تحسين';
}

function truncateText($text, $length) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}
    // تطبيق التصنيف الجديد مع النطاقات المحدثة
    $performance = $analysis['classified_performance'];
    render_group_table_v3('نقاط القوة الجماعية (متوسط أداء 80% - 100%)', $performance['strong'], 'strong', 'fa-star', 'success');
    render_group_table_v3('كفاءات ذات أداء متوسط (60% - 79.9%)', $performance['medium'], 'medium', 'fa-check-circle', 'warning');
    render_group_table_v3('مجالات التحسين الجماعية (متوسط أداء 0% - 59.9%)', $performance['weak'], 'weak', 'fa-flag', 'danger');
    ?>
</div>

<!-- ================================================== -->
<!-- نوافذ Modal -->
<!-- ================================================== -->

<!-- نافذة Modal المحسنة لعرض تفاصيل الموظفين في كفاءة معينة -->
<div class="modal fade details-modal" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="detailsModalLabel"><i class="fas fa-chart-line me-2"></i>تفاصيل أداء الكفاءة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- المحتوى سيتم حقنه هنا عبر JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- نافذة Modal لشرح تحليل الموظفين -->
<div class="modal fade" id="employeeExplanationModal" tabindex="-1" aria-labelledby="employeeExplanationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h4 class="modal-title w-100 text-center" id="employeeExplanationModalLabel"><i class="fas fa-microscope text-primary me-2"></i> كيف يعمل تبويب "تحليل الموظفين"؟</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <p class="text-center text-muted mb-4">يقوم هذا التحليل الذكي بفحص أداء كل موظف على حدة ليقدم لك رؤية شاملة عن نقاط قوته وضعفه. إليك كيف تتم العملية ببساطة:</p>
                <div class="explanation-card step-1">
                    <div class="icon-wrapper text-warning"><i class="fas fa-box"></i></div>
                    <h5>الخطوة الأولى: تجميع كل التقييمات</h5>
                    <p>يقوم النظام بجمع كل الدرجات التي حصل عليها الموظف في <strong>كل الكفاءات</strong> من <strong>كل استمارات التقييم</strong> التي خضع لها ضمن النطاق المحدد.</p>
                </div>
                <div class="explanation-card step-2">
                    <div class="icon-wrapper text-success"><i class="fas fa-star"></i> <span class="mx-2 text-muted">و</span> <i class="fas fa-flag text-danger"></i></div>
                    <h5>الخطوة الثانية: تحديد نقاط الأداء المتطرفة</h5>
                    <p>لكل موظف، يبحث النظام عن:</p>
                    <ul>
                        <li><strong>أقوى كفاءة:</strong> وهي الكفاءة (أو الكفاءات) التي حقق فيها <strong>أعلى درجة</strong>. هذه تمثل موهبته الحقيقية.</li>
                        <li><strong>أضعف كفاءة:</strong> وهي الكفاءة (أو الكفاءات) التي حقق فيها <strong>أدنى درجة</strong>. هذه تمثل فجوة الأداء التي تحتاج إلى تطوير.</li>
                    </ul>
                </div>
                <div class="explanation-card step-3 mb-0">
                    <div class="icon-wrapper text-primary"><i class="fas fa-id-card"></i></div>
                    <h5>الخطوة الثالثة: عرض البطاقة التحليلية</h5>
                    <p>أخيرًا، يتم عرض كل هذه المعلومات في "بطاقة تحليلية" لكل موظف، توضح لك بلمحة بصر أين يبرع الموظف وأين يحتاج إلى المساعدة، مما يساعدك على اتخاذ قرارات دقيقة وموجهة.</p>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">فهمت، شكرًا!</button>
            </div>
        </div>
    </div>
</div>

<!-- نافذة Modal لشرح مصفوفة الأهمية والأداء -->
<div class="modal fade" id="matrixExplanationModal" tabindex="-1" aria-labelledby="matrixExplanationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h4 class="modal-title w-100 text-center" id="matrixExplanationModalLabel"><i class="fas fa-th text-warning me-2"></i> ما هي مصفوفة الأهمية والأداء؟</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <p class="text-center text-muted mb-4">هي أداة تحليل استراتيجية تساعد على تحديد أولويات التطوير والتركيز من خلال مقارنة بُعدين رئيسيين لكل معيار من معايير التقييم:</p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="quadrant-explanation-card bg-success">
                            <h6><i class="fas fa-star"></i> نقاط القوة الرئيسية</h6>
                            <p class="small"><strong>(أداء عالي، أهمية عالية)</strong>  
هذه هي المجالات التي تتفوق فيها المؤسسة وهي مهمة جدًا. يجب الحفاظ على هذا المستوى من التميز والاستثمار فيه.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="quadrant-explanation-card bg-primary">
                            <h6><i class="fas fa-plus-circle"></i> فرص محتملة</h6>
                            <p class="small"><strong>(أداء منخفض، أهمية عالية)</strong>  
هذه هي أهم الأولويات للتطوير. تحسين الأداء في هذه المجالات سيحقق أكبر تأثير إيجابي على الأداء العام.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="quadrant-explanation-card bg-secondary">
                            <h6><i class="fas fa-thumbs-up"></i> أداء جيد</h6>
                            <p class="small"><strong>(أداء عالي، أهمية منخفضة)</strong>  
أداء جيد في مجالات أقل أهمية. لا تتطلب تركيزًا كبيرًا، ولكن يجب مراقبتها للحفاظ على المستوى الحالي.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="quadrant-explanation-card bg-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> أقل الأولويات</h6>
                            <p class="small"><strong>(أداء منخفض، أهمية منخفضة)</strong>  
هذه المجالات لا تتطلب تركيزًا فوريًا. يمكن وضع خطط تطوير لها على المدى الطويل بعد معالجة الفرص المحتملة.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">فهمت، شكرًا!</button>
            </div>
        </div>
    </div>
</div>
<!-- قسم التوصيات الذكية -->
<div class="card shadow-sm mt-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">
            <i class="fas fa-robot me-2"></i>التوصيات الذكية
            <small class="float-end">مدعومة بالذكاء الاصطناعي</small>
        </h5>
    </div>
    <div class="card-body">
        <?php
        $recommendationModel = new \App\Models\RecommendationModel();
        $recommendations = $recommendationModel->generateSmartRecommendations(
            $analysis, 
            $entityInfo['type'], 
            $entityInfo['employee_count']
        );
        
        if (!empty($recommendations)): 
            foreach ($recommendations as $index => $rec): 
                $badgeClass = [
                    'critical' => 'danger',
                    'high' => 'warning',
                    'medium' => 'info',
                    'low' => 'secondary'
                ][$rec['priority']] ?? 'secondary';
        ?>
        <div class="recommendation-item mb-3 p-3 border rounded">
            <div class="d-flex justify-content-between align-items-start">
                <h6 class="text-primary"><?php echo $rec['title']; ?></h6>
                <span class="badge bg-<?php echo $badgeClass; ?>">أولوية: <?php echo $rec['priority']; ?></span>
            </div>
            <p class="text-muted mb-2"><?php echo $rec['description']; ?></p>
            <div class="row">
                <div class="col-md-6">
                    <small><strong>الإجراء المقترح:</strong> <?php echo $rec['action']; ?></small>
                </div>
                <div class="col-md-3">
                    <small><strong>التأثير:</strong> <span class="text-success"><?php echo $rec['impact']; ?></span></small>
                </div>
                <div class="col-md-3">
                    <small><strong>الجهد:</strong> <span class="text-warning"><?php echo $rec['effort']; ?></span></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p class="text-center text-muted">لا توجد توصيات حالياً. مستوى الأداء جيد.</p>
        <?php endif; ?>
    </div>
</div>
<!-- ================================================== -->
<!-- تضمين مكتبات JavaScript -->
<!-- ================================================== -->
<script src="<?php echo APP_URL; ?>/public/libs/chartjs/chart.umd.min.js"></script>
<script src="<?php echo APP_URL; ?>/public/libs/chartjs/chartjs-plugin-datalabels.min.js"></script>
<script src="<?php echo APP_URL; ?>/public/libs/chartjs/chartjs-plugin-annotation.min.js"></script>

<!-- ================================================== -->
<!-- كود JavaScript للتفاعلية الكاملة (النسخة المصححة والمحسنة) -->
<!-- ================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ==================================================
    // 1. الإعدادات العامة والبيانات الأولية
    // ==================================================
    const analysisData = <?php echo json_encode($analysis); ?>;
    const allCompetencies = Object.values(analysisData.classified_performance).flat();
    let criteriaChart, quadrantChart;

    // التحقق من وجود العناصر قبل استخدامها
    const searchInput = document.getElementById('search-competency');
    const classificationFilter = document.getElementById('filter-classification');
    const positionFilter = document.getElementById('filter-position');
    const yearFilter = document.getElementById('filter-year');
    const printReportBtn = document.getElementById('print-report-btn');

    // ==================================================
    // 2. دوال إنشاء المخططات البيانية
    // ==================================================
    function createCriteriaBarChart() {
        const ctx = document.getElementById('criteriaBarChart');
        if (!ctx) return;
        
        const data = analysisData.criteria_performance;
        const labels = data.map(c => c.criterion_name);
        const scores = data.map(c => c.average_score);

        if (criteriaChart) criteriaChart.destroy();
        criteriaChart = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: { 
                labels, 
                datasets: [{ 
                    label: 'متوسط الأداء', 
                    data: scores, 
                    backgroundColor: 'rgba(13, 110, 253, 0.7)' 
                }] 
            },
            options: {
                responsive: true, 
                maintainAspectRatio: false, 
                indexAxis: 'y',
                plugins: { 
                    legend: { display: false }, 
                    datalabels: { 
                        anchor: 'end', 
                        align: 'end', 
                        formatter: val => val + '%' 
                    } 
                }
            }
        });
    }

    function createQuadrantChart() {
        const ctx = document.getElementById('quadrantChart');
        if (!ctx) return;
        
        const data = analysisData.criteria_performance;
        const averagePerformance = analysisData.stats.average_performance;
        const averageImportance = data.length > 0 ? data.reduce((sum, item) => sum + item.average_score, 0) / data.length : 50;

        const datasets = [{
            label: 'المعايير',
            data: data.map(item => ({
                x: item.average_score,
                y: item.average_score,
                label: item.criterion_name
            })),
            backgroundColor: 'rgba(255, 193, 7, 0.8)',
            pointRadius: 8,
            pointHoverRadius: 12
        }];

        if (quadrantChart) quadrantChart.destroy();
        quadrantChart = new Chart(ctx.getContext('2d'), {
            type: 'scatter',
            data: { datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.raw.label}: (الأداء: ${context.raw.x}%, الأهمية: ${context.raw.y}%)`
                        }
                    },
                    annotation: {
                        annotations: {
                            lineX: { 
                                type: 'line', 
                                xMin: averagePerformance, 
                                xMax: averagePerformance, 
                                borderColor: 'rgba(220, 53, 69, 0.5)', 
                                borderWidth: 2, 
                                borderDash: [6, 6], 
                                label: { 
                                    content: 'متوسط الأداء', 
                                    enabled: true, 
                                    position: 'start', 
                                    font: {size: 10} 
                                } 
                            },
                            lineY: { 
                                type: 'line', 
                                yMin: averageImportance, 
                                yMax: averageImportance, 
                                borderColor: 'rgba(220, 53, 69, 0.5)', 
                                borderWidth: 2, 
                                borderDash: [6, 6], 
                                label: { 
                                    content: 'متوسط الأهمية', 
                                    enabled: true, 
                                    position: 'start', 
                                    font: {size: 10} 
                                } 
                            }
                        }
                    }
                },
                scales: {
                    x: { 
                        min: 0, 
                        max: 100, 
                        title: { display: true, text: 'الأداء' } 
                    },
                    y: { 
                        min: 0, 
                        max: 100, 
                        title: { display: true, text: 'الأهمية (الوزن النسبي)' } 
                    }
                }
            }
        });
    }
// ==================================================
// 6. تفاعلية توزيع أداء الموظفين
// ==================================================
function setupDistributionInteractions() {
    // تفاعلية أشرطة التوزيع
    document.addEventListener('mouseover', function(e) {
        const distributionBar = e.target.closest('.distribution-bar');
        if (distributionBar) {
            const tooltip = distributionBar.querySelector('.distribution-tooltip');
            if (tooltip) {
                const rect = distributionBar.getBoundingClientRect();
                tooltip.style.left = '50%';
                tooltip.style.top = '-40px';
            }
        }
    });

    // تفاعلية بطاقات العد
    document.addEventListener('click', function(e) {
        const countItem = e.target.closest('.distribution-count-item');
        if (countItem) {
            // إضافة تأثير النقر
            countItem.style.transform = 'scale(0.95)';
            setTimeout(() => {
                countItem.style.transform = '';
            }, 150);
            
            // يمكن إضافة وظائف إضافية هنا مثل التصفية
            const type = Array.from(countItem.classList).find(cls => 
                cls.includes('strong') || cls.includes('medium') || cls.includes('weak')
            );
            if (type) {
                console.log(`تم النقر على ${type}`);
                // يمكن تنفيذ تصفية حسب النوع هنا
            }
        }
    });

    // تحريك المخططات الدائرية عند التمرير
    function animatePieCharts() {
        const pieCharts = document.querySelectorAll('.mini-pie-chart');
        pieCharts.forEach((chart, index) => {
            setTimeout(() => {
                chart.style.transform = 'scale(1)';
                chart.style.opacity = '1';
            }, index * 100);
        });
    }

    // تهيئة التحريك
    setTimeout(animatePieCharts, 500);
}

// استدعاء الدالة في التنفيذ الأولي
setupDistributionInteractions();
    // ==================================================
    // 3. دوال الطي والفتح للهيكل الشجري
    // ==================================================
    function setupHierarchyInteractions() {
        // النقر على رأس الفرع/القسم
        document.addEventListener('click', function(e) {
            const header = e.target.closest('.entity-header');
            if (header && header.style.cursor === 'pointer') {
                const entity = header.closest('.hierarchy-entity');
                const content = entity.querySelector('.entity-content');
                const icon = header.querySelector('.toggle-icon');
                
                if (content) {
                    if (content.style.display === 'none') {
                        content.style.display = 'block';
                        if (icon) icon.className = 'toggle-icon fas fa-minus';
                    } else {
                        content.style.display = 'none';
                        if (icon) icon.className = 'toggle-icon fas fa-plus';
                    }
                }
            }
        });
        
        // أزرار فتح/طي الكل
        const expandAllBtn = document.getElementById('expand-all');
        const collapseAllBtn = document.getElementById('collapse-all');
        
        if (expandAllBtn) {
            expandAllBtn.addEventListener('click', function() {
                document.querySelectorAll('.entity-content').forEach(content => {
                    content.style.display = 'block';
                });
                document.querySelectorAll('.toggle-icon').forEach(icon => {
                    icon.className = 'toggle-icon fas fa-minus';
                });
            });
        }
        
        if (collapseAllBtn) {
            collapseAllBtn.addEventListener('click', function() {
                document.querySelectorAll('.entity-content').forEach(content => {
                    content.style.display = 'none';
                });
                document.querySelectorAll('.toggle-icon').forEach(icon => {
                    icon.className = 'toggle-icon fas fa-plus';
                });
            });
        }
    }

    // ==================================================
    // 4. معالجات الأحداث (الفلاتر، النوافذ المنبثقة، والتصدير)
    // ==================================================
    
    const detailsModalElement = document.getElementById('detailsModal');
    if (detailsModalElement) {
        if (detailsModalElement.parentElement !== document.body) document.body.appendChild(detailsModalElement);
        if (!bootstrap.Modal.getInstance(detailsModalElement)) new bootstrap.Modal(detailsModalElement);
        let _pendingDetails = null;
        const forceCloseDetailsModal = () => {
            const inst = bootstrap.Modal.getInstance(detailsModalElement);
            if (inst) { try { inst.hide(); } catch(e) {} }
            detailsModalElement.classList.remove('show');
            detailsModalElement.style.display = 'none';
            detailsModalElement.setAttribute('aria-hidden','true');
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.body.style.removeProperty('overflow');
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        };
        detailsModalElement.addEventListener('hidden.bs.modal', function () {
            const mb = this.querySelector('.modal-body');
            if (mb) mb.innerHTML = '';
            _pendingDetails = null;
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.body.style.removeProperty('overflow');
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        });
        detailsModalElement.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn=>{
            btn.addEventListener('click', (e)=>{ e.preventDefault(); forceCloseDetailsModal(); });
        });
        detailsModalElement.addEventListener('click', (e)=>{
            if (e.target === detailsModalElement) forceCloseDetailsModal();
        });
        document.addEventListener('keydown', (e)=>{
            if (e.key === 'Escape' && detailsModalElement.classList.contains('show')) forceCloseDetailsModal();
        });
        detailsModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;
            const raw = button.getAttribute('data-details');
            if (!raw) return;
            try { _pendingDetails = JSON.parse(raw); } catch (e) {
                _pendingDetails = null;
                const mb = this.querySelector('.modal-body');
                if (mb) mb.innerHTML = '<div class="alert alert-danger text-center m-4">فشل تحميل التفاصيل</div>';
                return;
            }
            const mb = this.querySelector('.modal-body');
            if (mb) mb.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">جاري تحميل التفاصيل...</p></div>';
        });
        detailsModalElement.addEventListener('shown.bs.modal', function () {
            if (!_pendingDetails) return;
            let details = _pendingDetails;
            _pendingDetails = null;
            const modalTitle = this.querySelector('.modal-title');
            const modalBody = this.querySelector('.modal-body');
            
            // تحديث عنوان النافذة
            modalTitle.innerHTML = `<i class="fas fa-chart-line me-2"></i>تحليل أداء: ${details.section_name}`;
            
            let bodyHtml = `
                <!-- بطاقة الملخص الرئيسية -->
                <div class="performance-summary-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h4 class="mb-2"><i class="fas fa-bullseye me-2"></i>${details.section_name}</h4>
                            <p class="mb-0 opacity-75"><i class="fas fa-project-diagram me-2"></i>تابع لمعيار: ${details.criterion_name}</p>
                        </div>
                        <div class="text-end">
                            <div class="display-4 fw-bold">${details.average_score}%</div>
                            <small>متوسط الأداء</small>
                        </div>
                    </div>
                    
                    <div class="performance-stats">
                        <div class="stat-item">
                            <div class="stat-value">${details.total_employees}</div>
                            <div class="stat-label"><i class="fas fa-users me-1"></i>إجمالي الموظفين</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${details.strong_count}</div>
                            <div class="stat-label"><i class="fas fa-star me-1"></i>أداء قوي</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${details.medium_count}</div>
                            <div class="stat-label"><i class="fas fa-check-circle me-1"></i>أداء متوسط</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${details.weak_count}</div>
                            <div class="stat-label"><i class="fas fa-flag me-1"></i>بحاجة تطوير</div>
                        </div>
                    </div>
                </div>

                <!-- مخطط التوزيع -->
                <div class="card mb-4">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>توزيع أداء الموظفين</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="distribution-chart">
                                    <div class="progress distribution-progress">
                                        <div class="progress-bar bg-success" style="width: ${details.strong_percentage}%"></div>
                                        <div class="progress-bar bg-warning" style="width: ${details.medium_percentage}%"></div>
                                        <div class="progress-bar bg-danger" style="width: ${details.weak_percentage}%"></div>
                                    </div>
                                    <div class="distribution-center">
                                        <h4 class="mb-0">100%</h4>
                                        <small>التوزيع الكلي</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-4 text-center">
                                        <div class="mb-3">
                                            <span class="badge bg-success p-3 mb-2 d-inline-block" style="width: 30px; height: 30px;"></span>
                                            <h5 class="text-success">${Number(details.strong_percentage).toFixed(3)}%</h5>
                                            <small>أداء قوي<br>(${details.strong_count} موظف)</small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="mb-3">
                                            <span class="badge bg-warning p-3 mb-2 d-inline-block" style="width: 30px; height: 30px;"></span>
                                            <h5 class="text-warning">${Number(details.medium_percentage).toFixed(3)}%</h5>
                                            <small>أداء متوسط<br>(${details.medium_count} موظف)</small>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="mb-3">
                                            <span class="badge bg-danger p-3 mb-2 d-inline-block" style="width: 30px; height: 30px;"></span>
                                            <h5 class="text-danger">${Number(details.weak_percentage).toFixed(3)}%</h5>
                                            <small>بحاجة تطوير<br>(${details.weak_count} موظف)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;

            // دالة محسنة لعرض قائمة الموظفين
            const renderEmployeeList = (title, employees, category, icon, colorClass) => {
                let html = `
                <div class="employee-category-card category-${category}">
                    <div class="card-header category-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas ${icon} me-2"></i>
                            <strong>${title}</strong>
                            <span class="badge bg-light text-dark ms-2">${employees.length} موظف</span>
                        </div>
                        <span class="badge ${colorClass}">${employees.length > 0 ? Math.round(employees.reduce((sum, emp) => sum + emp.score, 0) / employees.length) : 0}% متوسط</span>
                    </div>
                    <div class="employee-list">
                `;

                if (employees.length > 0) {
                    employees.sort((a, b) => b.score - a.score).forEach((emp, index) => {
                        const initials = emp.name.split(' ').map(n => n[0]).join('').toUpperCase();
                        html += `
                        <div class="employee-item">
                            <div class="employee-avatar">
                                ${initials}
                            </div>
                            <div class="employee-info">
                                <div class="employee-name">${emp.name}</div>
                                ${emp.position ? `<div class="employee-position"><i class="fas fa-briefcase me-1"></i>${emp.position}</div>` : ''}
                                <div class="performance-bar">
                                    <div class="performance-fill" style="width: ${emp.score}%"></div>
                                </div>
                            </div>
                            <div class="employee-score">
                                <span class="score-badge badge ${colorClass}">${emp.score}%</span>
                            </div>
                        </div>`;
                    });
                } else {
                    html += `
                    <div class="text-center py-5">
                        <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">لا يوجد موظفون في هذا التصنيف</p>
                    </div>`;
                }

                html += `
                    </div>
                </div>`;

                return html;
            };

            bodyHtml += renderEmployeeList('📊 أداء قوي (80% - 100%)', details.strong_employees, 'strong', 'fa-star', 'bg-success');
            bodyHtml += renderEmployeeList('⚡ أداء متوسط (60% - 79.9%)', details.medium_employees, 'medium', 'fa-check-circle', 'bg-warning');
            bodyHtml += renderEmployeeList('🎯 بحاجة للتطوير (0% - 59.9%)', details.weak_employees, 'weak', 'fa-flag', 'bg-danger');
            requestAnimationFrame(()=>{ setTimeout(()=>{ if(modalBody) modalBody.innerHTML = bodyHtml; }, 16); });
        });
    }

    // دالة applyFilters المصححة
    function applyFilters() {
        const position = positionFilter ? positionFilter.value : 'all';
        const year = yearFilter ? yearFilter.value : 'all';
        const currentUrl = new URL(window.location);

        if (position === 'all') {
            currentUrl.searchParams.delete('position');
        } else {
            currentUrl.searchParams.set('position', position);
        }

        if (year === 'all') {
            currentUrl.searchParams.delete('year');
        } else {
            currentUrl.searchParams.set('year', year);
        }
        
        window.location.href = currentUrl.href;
    }

    // إضافة أحداث الفلاتر فقط إذا كانت العناصر موجودة
    if (positionFilter) {
        positionFilter.addEventListener('change', applyFilters);
    }

    if (yearFilter) {
        yearFilter.addEventListener('change', applyFilters);
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', () => {
            const searchTerm = searchInput.value.toLowerCase();
            document.querySelectorAll('.competency-row').forEach(row => {
                const name = row.dataset.name;
                row.style.display = name.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    if (classificationFilter) {
        classificationFilter.addEventListener('change', () => {
            const classification = classificationFilter.value;
            document.querySelectorAll('.analysis-section').forEach(section => {
                section.style.display = (classification === 'all' || section.dataset.classification === classification) ? '' : 'none';
            });
        });
    }

    // التحقق من وجود زر الطباعة قبل إضافة الأحداث
    if (printReportBtn) {
        printReportBtn.addEventListener('click', function(event) {
            event.preventDefault();
            const printUrl = new URL('<?php echo APP_URL; ?>/analysis/print/level');
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.forEach((value, key) => {
                printUrl.searchParams.set(key, value);
            });
            window.open(printUrl.href, '_blank');
        });
    }

    // ==================================================
    // 5. التنفيذ الأولي
    // ==================================================

    // تسجيل plugins إذا كانت مكتبة Chart.js محملة
    if (typeof Chart !== 'undefined') {
        Chart.register(ChartDataLabels);
        if (window.ChartAnnotation) {
            Chart.register(window.ChartAnnotation);
        }
        
        // إنشاء المخططات فقط إذا كانت العناصر موجودة
        if (document.getElementById('criteriaBarChart')) {
            createCriteriaBarChart();
        }
        if (document.getElementById('quadrantChart')) {
            createQuadrantChart();
        }
    }

    // إعداد التفاعلية للهيكل الشجري
    setupHierarchyInteractions();
// ==================================================
// 6. تفاعلية توزيع أداء الموظفين
// ==================================================
function setupDistributionInteractions() {
    // تفاعلية أشرطة التوزيع
    document.addEventListener('mouseover', function(e) {
        if (!e.target || !e.target.closest) return;
        const distributionBar = e.target.closest('.distribution-bar');
        if (distributionBar) {
            const tooltip = distributionBar.querySelector('.distribution-tooltip');
            if (tooltip) {
                const rect = distributionBar.getBoundingClientRect();
                tooltip.style.left = '50%';
                tooltip.style.top = '-40px';
            }
        }
    });

    // تفاعلية بطاقات العد
    document.addEventListener('click', function(e) {
        if (!e.target || !e.target.closest) return;
        const countItem = e.target.closest('.distribution-count-item');
        if (countItem) {
            // إضافة تأثير النقر
            countItem.style.transform = 'scale(0.95)';
            setTimeout(() => {
                countItem.style.transform = '';
            }, 150);
            
            // يمكن إضافة وظائف إضافية هنا مثل التصفية
            const type = Array.from(countItem.classList).find(cls => 
                cls.includes('strong') || cls.includes('medium') || cls.includes('weak')
            );
            if (type) {
                console.log(`تم النقر على ${type}`);
                // يمكن تنفيذ تصفية حسب النوع هنا
            }
        }
    });

    // تحريك المخططات الدائرية عند التمرير
    function animatePieCharts() {
        const pieCharts = document.querySelectorAll('.mini-pie-chart');
        pieCharts.forEach((chart, index) => {
            setTimeout(() => {
                chart.style.transform = 'scale(1)';
                chart.style.opacity = '1';
            }, index * 100);
        });
    }

    // تهيئة التحريك
    setTimeout(animatePieCharts, 500);
}

// ==================================================
// 7. تفاعلية الكفاءات والإجراءات المحسنة (النسخة المصححة)
// ==================================================
function setupCompetencyInteractions() {
    // تفاعلية أزرار التفاصيل
    document.addEventListener('mouseenter', function(e) {
        if (!e.target || !e.target.closest) return;
        const btn = e.target.closest('.btn-details');
        if (btn) {
            btn.style.background = 'linear-gradient(135deg, #0b5ed7, #0ba8e0)';
        }
    }, true);

    document.addEventListener('mouseleave', function(e) {
        if (!e.target || !e.target.closest) return;
        const btn = e.target.closest('.btn-details');
        if (btn) {
            btn.style.background = 'linear-gradient(135deg, #0d6efd, #0dcaf0)';
        }
    }, true);

    // تفاعلية بطاقات الكفاءة
    document.addEventListener('click', function(e) {
        if (!e.target || !e.target.closest) return;
        const competencyHeader = e.target.closest('.competency-header');
        if (competencyHeader) {
            const row = competencyHeader.closest('.competency-row');
            if (row) {
                // تأثير النقر على الكفاءة
                row.style.transform = 'scale(0.995)';
                setTimeout(() => {
                    row.style.transform = '';
                }, 150);
            }
        }
    });

    // تحميل سريع للنافذة المنبثقة
    document.addEventListener('click', function(e) {
        if (!e.target || !e.target.closest) return;
        const detailsBtn = e.target.closest('.btn-details');
        if (detailsBtn) {
            // إضافة تأثير تحميل
            const originalContent = detailsBtn.innerHTML;
            detailsBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>جاري التحميل...</span>';
            detailsBtn.disabled = true;
            
            setTimeout(() => {
                detailsBtn.innerHTML = originalContent;
                detailsBtn.disabled = false;
            }, 800);
        }
    });

    // تفاعلية التلميحات
    document.addEventListener('mouseover', function(e) {
        if (!e.target || !e.target.closest) return;
        const tooltipElement = e.target.closest('.competency-tooltip');
        if (tooltipElement) {
            const tooltip = tooltipElement.getAttribute('data-tooltip');
            if (tooltip) {
                // يمكن إضافة تلميحات مخصصة هنا
            }
        }
    });
}

// ==================================================
// 8. تحسينات إضافية للجدول (النسخة المصححة)
// ==================================================
function enhanceTableFunctionality() {
    // فرز الجدول عند النقر على العناوين
    const tableHeaders = document.querySelectorAll('th[data-sort]');
    tableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const table = this.closest('table');
            if (!table) return;
            
            const columnIndex = Array.from(this.parentNode.children).indexOf(this);
            const sortOrder = this.getAttribute('data-sort-order') || 'asc';
            
            sortTable(table, columnIndex, sortOrder);
            
            // تبديل اتجاه الفرز
            this.setAttribute('data-sort-order', sortOrder === 'asc' ? 'desc' : 'asc');
        });
    });

    // بحث متقدم في الكفاءات
    const searchInput = document.getElementById('search-competency');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.competency-row').forEach(row => {
                const competencyNameElem = row.querySelector('.competency-name');
                const criterionElem = row.querySelector('.competency-criterion span');
                
                if (!competencyNameElem || !criterionElem) return;
                
                const competencyName = competencyNameElem.textContent.toLowerCase();
                const criterionName = criterionElem.textContent.toLowerCase();
                const isVisible = competencyName.includes(searchTerm) || criterionName.includes(searchTerm);
                row.style.display = isVisible ? '' : 'none';
            });
        });
    }
}

// دالة مساعدة للفرز (النسخة المصححة)
function sortTable(table, column, order) {
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        let aValue, bValue;
        
        if (column === 0) { // عمود الكفاءة
            const aNameElem = a.querySelector('.competency-name');
            const bNameElem = b.querySelector('.competency-name');
            aValue = aNameElem ? aNameElem.textContent : '';
            bValue = bNameElem ? bNameElem.textContent : '';
        } else if (column === 1) { // عمود متوسط الأداء
            const aBadge = a.querySelector('.badge');
            const bBadge = b.querySelector('.badge');
            aValue = aBadge ? parseFloat(aBadge.textContent) || 0 : 0;
            bValue = bBadge ? parseFloat(bBadge.textContent) || 0 : 0;
        } else if (column === 2) { // عمود التوزيع
            const aPieCenter = a.querySelector('.pie-chart-center');
            const bPieCenter = b.querySelector('.pie-chart-center');
            aValue = aPieCenter ? parseFloat(aPieCenter.textContent) || 0 : 0;
            bValue = bPieCenter ? parseFloat(bPieCenter.textContent) || 0 : 0;
        } else {
            return 0;
        }
        
        if (order === 'asc') {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
    
    // إعادة ترتيب الصفوف
    rows.forEach(row => tbody.appendChild(row));
}

// استدعاء الدوال الجديدة مع معالجة الأخطاء
try {
    setupDistributionInteractions();
    setupCompetencyInteractions();
    enhanceTableFunctionality();
} catch (error) {
    console.error('حدث خطأ في تحميل التفاعلية:', error);
}
    // تطبيق الفلاتر من الرابط إذا وجدت
    const urlParams = new URLSearchParams(window.location.search);
    const positionParam = urlParams.get('position');
    const yearParam = urlParams.get('year');
    if (positionFilter && positionParam) positionFilter.value = positionParam;
    if (yearFilter && yearParam) yearFilter.value = yearParam;
});

</script>

<?php

// ==================================================
// دوال العرض للهيكل الشجري (يجب وضعها في نهاية الملف قبل footer)
// ==================================================

function renderHierarchicalData($data) {
    if (empty($data['entities'])) {
        return '<div class="alert alert-info text-center">لا توجد بيانات للعرض</div>';
    }
    
    return renderHierarchy($data, 0);
}

function renderHierarchy($data, $level = 0) {
    $html = '';
    foreach($data['entities'] as $entity) {
        $html .= renderEntity($entity, $level);
    }
    return $html;
}

function renderEntity($entity, $level = 0) {
    $margin = $level * 30;
    $hasChildren = !empty($entity['entities']);
    
    $html = '<div class="hierarchy-entity mb-2" style="margin-left: ' . $margin . 'px">';
    
    switch($entity['type']) {
        case 'branch':
            $html .= renderBranch($entity, $level, $hasChildren);
            break;
        case 'department':
            $html .= renderDepartment($entity, $level, $hasChildren);
            break;
        case 'employee':
            $html .= renderEmployee($entity, $level);
            break;
        default:
            // إذا كان نوع غير معروف، نستخدم renderEmployee كافتراضي
            $html .= renderEmployee($entity, $level);
    }
    
    if ($hasChildren) {
        $html .= '<div class="entity-content" style="display: block;">';
        $html .= renderHierarchy($entity, $level + 1);
        $html .= '</div>';
    }
    
    $html .= '</div>';
    return $html;
}

function renderBranch($entity, $level, $hasChildren) {
    $performanceClass = getPerformanceClass($entity['average_performance']);
    $icon = $hasChildren ? 'fa-minus' : 'fa-building';
    
    return '
    <div class="entity-header branch-header card card-body bg-light p-3" style="cursor: ' . ($hasChildren ? 'pointer' : 'default') . '">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                ' . ($hasChildren ? '<i class="toggle-icon fas fa-minus me-2"></i>' : '<i class="fas ' . $icon . ' me-2"></i>') . '
                <i class="fas fa-building me-2 text-primary"></i>
                <h6 class="mb-0">' . htmlspecialchars($entity['name']) . '</h6>
            </div>
            <div class="d-flex align-items-center">
                <span class="badge ' . $performanceClass . ' me-2">المتوسط: ' . $entity['average_performance'] . '%</span>
                <small class="text-muted">فرع</small>
            </div>
        </div>
    </div>';
}

function renderDepartment($entity, $level, $hasChildren) {
    $performanceClass = getPerformanceClass($entity['average_performance']);
    $icon = $hasChildren ? 'fa-minus' : 'fa-sitemap';
    
    return '
    <div class="entity-header department-header card card-body p-2" style="cursor: ' . ($hasChildren ? 'pointer' : 'default') . '">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                ' . ($hasChildren ? '<i class="toggle-icon fas fa-minus me-2"></i>' : '<i class="fas ' . $icon . ' me-2"></i>') . '
                <i class="fas fa-sitemap me-2 text-info"></i>
                <h6 class="mb-0">' . htmlspecialchars($entity['name']) . '</h6>
            </div>
            <div class="d-flex align-items-center">
                <span class="badge ' . $performanceClass . ' me-2">المتوسط: ' . $entity['average_performance'] . '%</span>
                <small class="text-muted">قسم</small>
            </div>
        </div>
    </div>';
}

function renderEmployee($entity, $level) {
    $performanceClass = getPerformanceClass($entity['average_performance']);
    
    // التحقق من وجود البيانات الأساسية
    $employeeName = isset($entity['name']) ? htmlspecialchars($entity['name']) : 'غير معروف';
    $empCode = isset($entity['emp_code']) ? htmlspecialchars($entity['emp_code']) : '';
    $averagePerformance = isset($entity['average_performance']) ? $entity['average_performance'] : 0;
    
    $html = '
    <div class="employee-card card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center">
                    <i class="fas fa-user me-2 text-success"></i>
                    <div>
                        <h6 class="mb-0">' . $employeeName . '</h6>';
    
    if ($empCode) {
        $html .= '<small class="text-muted">' . $empCode . '</small>';
    }
    
    $html .= '
                    </div>
                </div>
                <span class="badge ' . $performanceClass . '">المتوسط: ' . $averagePerformance . '%</span>
            </div>';
    
    // إضافة الكفاءات إذا كانت موجودة
    if (isset($entity['competencies']) && is_array($entity['competencies'])) {
        $html .= renderEmployeeCompetencies($entity['competencies']);
    } else {
        $html .= '<p class="text-muted text-center">لا توجد بيانات كفاءات متاحة</p>';
    }
    
    $html .= '
        </div>
    </div>';
    
    return $html;
}

function renderEmployeeCompetencies($competencies) {
    $html = '<div class="competencies-breakdown">';
    
    // نقاط القوة (80-100)
    if (!empty($competencies['strong'])) {
        $html .= renderCompetencySection('نقاط القوة', $competencies['strong'], 'success', 'fa-star');
    }
    
    // الكفاءات المتوسطة (60-79.9)
    if (!empty($competencies['medium'])) {
        $html .= renderCompetencySection('الكفاءات المتوسطة', $competencies['medium'], 'warning', 'fa-check-circle');
    }
    
    // يحتاج تطوير (أقل من 60)
    if (!empty($competencies['weak'])) {
        $html .= renderCompetencySection('يحتاج تطوير', $competencies['weak'], 'danger', 'fa-flag');
    }
    
    // إذا لم توجد أي كفاءات
    if (empty($competencies['strong']) && empty($competencies['medium']) && empty($competencies['weak'])) {
        $html .= '<p class="text-muted text-center">لا توجد كفاءات مسجلة</p>';
    }
    
    $html .= '</div>';
    return $html;
}

function renderCompetencySection($title, $competencies, $type, $icon) {
    $html = '
    <div class="competency-section mt-2">
        <h6 class="text-' . $type . '">
            <i class="fas ' . $icon . ' me-1"></i>
            ' . $title . ' (' . count($competencies) . ' كفاءة)
        </h6>
        <div class="competency-list">';
    
    foreach ($competencies as $comp) {
        $sectionName = isset($comp['section_name']) ? htmlspecialchars($comp['section_name']) : 'غير معروف';
        $criterionName = isset($comp['criterion_name']) ? htmlspecialchars($comp['criterion_name']) : '';
        $templateName = isset($comp['template_name']) ? htmlspecialchars($comp['template_name']) : '';
        $score = isset($comp['score']) ? $comp['score'] : 0;
        
        $html .= '
            <div class="competency-item d-flex justify-content-between align-items-center p-1 border-bottom">
                <div>
                    <strong>' . $sectionName . '</strong>';
        
        if ($criterionName) {
            $html .= '<small class="text-muted ms-2">' . $criterionName . '</small>';
        }
        
        $html .= '
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-' . $type . ' me-2">' . $score . '%</span>';
        
        if ($templateName) {
            $html .= '<small class="text-muted">' . $templateName . '</small>';
        }
        
        $html .= '
                </div>
            </div>';
    }
    
    $html .= '
        </div>
    </div>';
    return $html;
}

function getPerformanceClass($score) {
    if ($score >= 80) return 'bg-success';
    if ($score >= 60) return 'bg-warning';
    return 'bg-danger';
}

?>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>