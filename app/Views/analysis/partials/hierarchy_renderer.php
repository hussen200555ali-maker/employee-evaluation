<?php
/**
 * دوال مساعدة لعرض الهيكل الهرمي
 */

function renderHierarchy($data, $level = 0) {
    if (empty($data['entities'])) {
        return '<div class="alert alert-info text-center">لا توجد بيانات للعرض</div>';
    }
    
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
    
    return '
    <div class="employee-card card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center">
                    <i class="fas fa-user me-2 text-success"></i>
                    <div>
                        <h6 class="mb-0">' . htmlspecialchars($entity['name']) . '</h6>
                        <small class="text-muted">' . htmlspecialchars($entity['emp_code']) . '</small>
                    </div>
                </div>
                <span class="badge ' . $performanceClass . '">المتوسط: ' . $entity['average_performance'] . '%</span>
            </div>
            
            ' . renderEmployeeCompetencies($entity['competencies']) . '
        </div>
    </div>';
}

function renderEmployeeCompetencies($competencies) {
    $html = '<div class="competencies-breakdown">';
    
    // نقاط القوة
    if (!empty($competencies['strong'])) {
        $html .= renderCompetencySection('نقاط القوة', $competencies['strong'], 'success', 'fa-star');
    }
    
    // الكفاءات المتوسطة
    if (!empty($competencies['medium'])) {
        $html .= renderCompetencySection('الكفاءات المتوسطة', $competencies['medium'], 'warning', 'fa-check-circle');
    }
    
    // يحتاج تطوير
    if (!empty($competencies['weak'])) {
        $html .= renderCompetencySection('يحتاج تطوير', $competencies['weak'], 'danger', 'fa-flag');
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
        $html .= '
            <div class="competency-item d-flex justify-content-between align-items-center p-1 border-bottom">
                <div>
                    <strong>' . htmlspecialchars($comp['section_name']) . '</strong>
                    <small class="text-muted ms-2">' . htmlspecialchars($comp['criterion_name']) . '</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-' . $type . ' me-2">' . $comp['score'] . '%</span>
                    <small class="text-muted">' . htmlspecialchars($comp['template_name']) . '</small>
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