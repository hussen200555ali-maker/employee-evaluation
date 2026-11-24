<?php
// المسار: app/Views/templates/index.php
// DIR-TEMPLATES-UI-REDESIGN-V1.1 — هوية مؤسسية معزولة (tp-*)
require_once APP_ROOT . '/app/Views/layouts/header.php';

$tpTemplates = $templates ?? [];
$tpCanManage = App\Core\Auth::userHasPermission('templates.manage');
$tpActiveCount = 0;
foreach ($tpTemplates as $tpItem) {
    if (isset($tpItem->is_active) && $tpItem->is_active == 1) {
        $tpActiveCount++;
    }
}
$tpTypeLabels = [
    'employee'            => 'موظف فردي',
    'employee_in_course'  => 'دورة',
    'employee_in_workshop'=> 'ورشة',
    'course'              => 'دورة ككيان',
    'workshop'            => 'ورشة ككيان',
    'unit'                => 'وحدة تنظيمية',
    'admin'               => 'إداري',
    'skill'               => 'مهاري',
];
$tpModeLabels = ['numeric' => 'عددي', 'descriptive' => 'وصفي', 'mixed' => 'مختلط'];
?>
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/templates-ui.css?v=1.0">

<div class="tp-page" id="tpPage">

    <!-- ===================== Header ===================== -->
    <section class="tp-hero">
        <div class="tp-hero__row">
            <div>
                <h2 class="tp-hero__title"><i class="fas fa-clipboard-list"></i> إدارة قوالب التقييم</h2>
                <p class="tp-hero__sub">صمّم معايير التقييم وأقسامها وأوزانها من واجهة واحدة متسقة مع هوية النظام.</p>
            </div>
            <div class="tp-hero__meta">
                <span class="tp-chip"><i class="fas fa-layer-group"></i> <?= count($tpTemplates) ?> قالب</span>
                <span class="tp-chip"><i class="fas fa-circle-check"></i> <?= $tpActiveCount ?> نشط</span>
                <?php if ($tpCanManage): ?>
                    <a href="<?= APP_URL ?>/builder/create" class="tp-btn tp-btn--gold">
                        <i class="fas fa-plus"></i> إنشاء قالب جديد
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===================== Toolbar ===================== -->
    <section class="tp-toolbar" aria-label="أدوات عرض القوالب">
        <div class="tp-toolbar__search">
            <i class="fas fa-search"></i>
            <input type="search" id="tpSearch" placeholder="ابحث باسم القالب أو الوصف..." autocomplete="off" aria-label="بحث">
        </div>
        <select id="tpStatusFilter" aria-label="فلترة حسب الحالة">
            <option value="">كل الحالات</option>
            <option value="active">نشط</option>
            <option value="draft">مسودة</option>
        </select>
        <select id="tpTypeFilter" aria-label="فلترة حسب نوع القالب">
            <option value="">كل الأنواع</option>
            <?php foreach ($tpTypeLabels as $tpKey => $tpLabel): ?>
                <option value="<?= htmlspecialchars($tpKey, ENT_QUOTES) ?>"><?= htmlspecialchars($tpLabel, ENT_QUOTES) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="tpSort" aria-label="ترتيب القوالب">
            <option value="recent">الأحدث أولاً</option>
            <option value="name">الاسم (أ→ي)</option>
            <option value="criteria">الأكثر معايير</option>
        </select>
        <span class="tp-count" id="tpResultCount"></span>
    </section>

    <!-- ===================== Cards ===================== -->
    <section class="tp-grid" id="tpGrid" aria-label="قائمة القوالب">
        <?php if (!empty($tpTemplates)): ?>
            <?php foreach ($tpTemplates as $template): ?>
                <?php
                $tpIsActive = isset($template->is_active) && $template->is_active == 1;
                $tpStatusClass = $tpIsActive ? 'active' : 'draft';
                $tpStatusText  = $tpIsActive ? 'نشط' : 'مسودة';
                $tpStatusIcon  = $tpIsActive ? 'circle-check' : 'hourglass-half';
                $tpType        = (string)($template->target_type ?? '');
                $tpMode        = (string)($template->evaluation_mode ?? '');
                $tpFrequency   = (string)($template->evaluation_frequency ?? '');
                $tpName        = (string)($template->template_name ?? 'قالب بدون اسم');
                $tpDescription = (string)($template->template_description ?: 'لا يوجد وصف لهذا القالب.');
                $tpCriteria    = (int)($template->criteria_count ?? 0);
                $tpCreator     = (string)($template->created_by_name ?? 'غير معروف');
                ?>
                <article class="tp-card"
                         data-search="<?= htmlspecialchars($tpName . ' ' . $tpDescription, ENT_QUOTES) ?>"
                         data-status="<?= $tpStatusClass ?>"
                         data-type="<?= htmlspecialchars($tpType, ENT_QUOTES) ?>"
                         data-criteria="<?= $tpCriteria ?>">
                    <div class="tp-card__head">
                        <span class="tp-status tp-status--<?= $tpStatusClass ?>">
                            <i class="fas fa-<?= $tpStatusIcon ?>"></i> <?= $tpStatusText ?>
                        </span>
                        <span class="tp-card__id">#<?= (int)$template->id ?></span>
                    </div>
                    <div class="tp-card__body">
                        <h3 class="tp-card__title"><?= htmlspecialchars($tpName, ENT_QUOTES) ?></h3>
                        <p class="tp-card__desc"><?= htmlspecialchars($tpDescription, ENT_QUOTES) ?></p>
                        <div class="tp-tags">
                            <span class="tp-tag tp-tag--type">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($tpTypeLabels[$tpType] ?? ($tpType ?: 'عام'), ENT_QUOTES) ?>
                            </span>
                            <span class="tp-tag"><?= htmlspecialchars($tpModeLabels[$tpMode] ?? ($tpMode ?: 'عددي'), ENT_QUOTES) ?></span>
                            <?php if ($tpFrequency !== ''): ?>
                                <span class="tp-tag"><?= htmlspecialchars($tpFrequency, ENT_QUOTES) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tp-card__foot">
                        <div class="tp-meta">
                            <span><i class="fas fa-list-ul"></i> <?= $tpCriteria ?> معايير</span>
                            <span><i class="fas fa-user"></i> <?= htmlspecialchars($tpCreator, ENT_QUOTES) ?></span>
                        </div>
                        <div class="tp-actions">
                            <a href="<?= APP_URL ?>/builder/edit/<?= (int)$template->id ?>" class="tp-btn tp-btn--primary tp-btn--sm">
                                <i class="fas fa-drafting-compass"></i> الباني
                            </a>
                            <?php if ($tpCanManage): ?>
                                <button type="button"
                                        class="tp-btn tp-btn--danger tp-btn--sm tp-delete"
                                        data-id="<?= (int)$template->id ?>"
                                        data-name="<?= htmlspecialchars($tpName, ENT_QUOTES) ?>">
                                    <i class="fas fa-trash-alt"></i> حذف
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="tp-empty" style="grid-column:1/-1;">
                <i class="fas fa-folder-open"></i>
                <h4>لا توجد قوالب لعرضها حالياً.</h4>
                <p>ابدأ بإنشاء قالب جديد لإعداد تقييماتك.</p>
                <?php if ($tpCanManage): ?>
                    <a href="<?= APP_URL ?>/builder/create" class="tp-btn tp-btn--primary">
                        <i class="fas fa-plus"></i> إنشاء أول قالب
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- نتيجة فارغة بعد الفلترة -->
    <div class="tp-empty tp-hidden" id="tpNoResults" style="margin-top:14px;">
        <i class="fas fa-filter"></i>
        <h4>لا توجد نتائج مطابقة.</h4>
        <p>جرّب تغيير كلمة البحث أو الفلاتر.</p>
    </div>

    <!-- ===================== Delete modal ===================== -->
    <div class="tp-modal" id="tpDeleteModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="tpDeleteTitle">
        <div class="tp-modal__dialog">
            <div class="tp-modal__header" id="tpDeleteTitle">
                <i class="fas fa-triangle-exclamation"></i> تأكيد حذف القالب
            </div>
            <div class="tp-modal__body">
                هل أنت متأكد من حذف القالب:
                <span class="tp-modal__name" id="tpDeleteName"></span>؟
                <div class="tp-callout"><i class="fas fa-info-circle"></i> سيُحذف القالب ولا يمكن التراجع عن هذه العملية.</div>
            </div>
            <div class="tp-modal__footer">
                <button type="button" class="tp-btn tp-btn--ghost" id="tpDeleteCancel">إلغاء</button>
                <button type="button" class="tp-btn tp-btn--danger" id="tpDeleteConfirm"><i class="fas fa-trash-alt"></i> تأكيد الحذف</button>
            </div>
        </div>
    </div>

    <!-- عقد الحذف: نفس المسار ونفس أسماء الحقول (template_id + _token) -->
    <form id="tpDeleteForm" method="POST" action="<?= APP_URL ?>/templates/delete" style="display:none;">
        <input type="hidden" name="template_id" id="tpDeleteId" value="">
        <input type="hidden" name="_token" id="tpDeleteToken" value="">
    </form>
</div>

<script src="<?= APP_URL ?>/public/js/templates-ui.js?v=1.0"></script>

<?php
require_once APP_ROOT . '/app/Views/layouts/footer.php';
