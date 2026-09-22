<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/archive.css">

<style>
    .printable-area { padding: 40px; background: white; }
    .report-header { text-align: center; border-bottom: 3px double #0d5e52; padding-bottom: 20px; margin-bottom: 30px; }
    .report-title { font-size: 24px; font-weight: bold; color: #0d5e52; }
    .force-box { background: #f8f9fa; border: 2px solid #0d5e52; border-radius: 12px; padding: 20px; margin: 20px 0; }
    .force-item { text-align: center; }
    .force-value { font-size: 28px; font-weight: bold; color: #0d5e52; }
    .compliance-badge { font-size: 32px; padding: 10px 30px; border-radius: 50px; }
    .section-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
    .section-title { font-weight: bold; color: #0d5e52; border-bottom: 2px solid #c8943f; padding-bottom: 8px; margin-bottom: 10px; }
    .helper-row { background: #f8f9fa; opacity: 0.8; }
    .formula-row { background: #fff5f5; }
    @media print {
        body > *:not(.printable-area) { display: none !important; }
        .printable-area { padding: 20px; }
        .no-print { display: none !important; }
    }
</style>

<div class="printable-area">
    <div style="text-align:center;font-size:18px;margin-bottom:20px;color:#0d5e52;font-weight:bold;">
        بسم الله الرحمن الرحيم
    </div>

    <div class="report-header">
        <div class="report-title"><?= htmlspecialchars($report->report_title ?? 'تقرير إداري') ?></div>
        <div class="report-meta" style="margin-top:10px;color:#666;">
            <span><i class="fas fa-calendar"></i> <?= $report->period_start ?> - <?= $report->period_end ?></span>
            <span><i class="fas fa-clock"></i> <?= $report->period_type ?></span>
            <span><i class="fas fa-check-circle"></i> <?= $report->status === 'completed' ? 'مكتمل' : ($report->status === 'approved' ? 'معتمد' : 'مسودة') ?></span>
        </div>
    </div>

    <div class="force-box">
        <h5 style="color:#0d5e52;margin-bottom:15px;"><i class="fas fa-chart-bar"></i> بيانات القوة</h5>
        <div class="row">
            <div class="col-md-4 force-item">
                <div class="force-value"><?= $report->actual_force ?? 0 ?></div>
                <div style="color:#666;font-size:14px;">القوة الفعلية</div>
            </div>
            <div class="col-md-4 force-item">
                <div class="force-value"><?= $report->applied_force ?? 0 ?></div>
                <div style="color:#666;font-size:14px;">القوة المطبقه</div>
            </div>
            <div class="col-md-4 force-item">
                <?php
                $c = $report->compliance_rate ?? 0;
                $color = $c >= 90 ? '#22c55e' : ($c >= 70 ? '#f59e0b' : '#ef4444');
                ?>
                <div class="compliance-badge" style="background:<?= $color ?>;color:white;">
                    <?= round($c, 1) ?>%
                </div>
                <div style="color:#666;font-size:14px;">نسبة المطابقة</div>
            </div>
        </div>
    </div>

    <?php if(!empty($template['criteria'])): ?>
        <h5 style="color:#0d5e52;margin:20px 0 15px;"><i class="fas fa-list-ul"></i> تفاصيل التقييم</h5>
        <?php foreach($template['criteria'] as $criterion): ?>
        <div class="section-card">
            <div class="section-title"><?= htmlspecialchars($criterion['criterion_name']) ?></div>
            <?php foreach($criterion['sections'] as $section):
                $sectionType = $section['evaluation_type'] ?? '';
                $value = $answersMap[$section['id']] ?? '—';
                $rowClass = '';
                if ($sectionType === 'helper') $rowClass = 'helper-row';
                elseif ($sectionType === 'formula') $rowClass = 'formula-row';
            ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #eee;" class="<?= $rowClass ?>">
                    <span>
                        <?= htmlspecialchars($section['section_name']) ?>
                        <?php if($sectionType === 'helper'): ?>
                            <small class="text-muted">(مساعد)</small>
                        <?php elseif($sectionType === 'formula'): ?>
                            <small class="text-muted">(محسوب)</small>
                        <?php endif; ?>
                    </span>
                    <span class="badge" style="background:#0d5e52;">
                        <?php
                        if ($sectionType === 'percentage' && is_numeric($value)) {
                            echo round((float)$value, 1) . '%';
                        } elseif ($sectionType === 'formula' && is_numeric($value)) {
                            echo round((float)$value, 2);
                        } elseif ($sectionType === 'boolean') {
                            echo $value == '1' ? 'نعم' : 'لا';
                        } else {
                            echo htmlspecialchars($value);
                        }
                        ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if(!empty($evaluators)): ?>
        <h5 style="color:#0d5e52;margin:20px 0 15px;"><i class="fas fa-users"></i> المقيّمون</h5>
        <div class="row">
            <?php foreach($evaluators as $ev): ?>
                <div class="col-md-4">
                    <div class="d-flex align-items-center p-2 bg-light rounded mb-2">
                        <i class="fas fa-user-circle fa-2x text-primary me-2"></i>
                        <div>
                            <strong><?= htmlspecialchars($ev->full_name ?? '') ?></strong>
                            <small class="d-block text-muted"><?= $ev->assigned_at ?? '' ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($report->notes)): ?>
        <h5 style="color:#0d5e52;margin:20px 0 15px;"><i class="fas fa-sticky-note"></i> ملاحظات</h5>
        <div class="bg-light p-3 rounded"><?= nl2br(htmlspecialchars($report->notes)) ?></div>
    <?php endif; ?>

    <div class="no-print" style="margin-top:30px;text-align:center;">
        <a href="<?= APP_URL ?>/admin/reports" class="btn btn-secondary"><i class="fas fa-arrow-right"></i> العودة</a>
        <a href="<?= APP_URL ?>/admin/reports/edit/<?= $report->id ?>" class="btn btn-primary"><i class="fas fa-edit"></i> تعديل</a>
        <button onclick="window.print()" class="btn btn-success"><i class="fas fa-print"></i> طباعة</button>
    </div>
</div>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
