<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<?php
// محسوبات للعرض (لا تغيير على الكنترولر)
$positionsList    = is_array($positions ?? null) ? $positions : [];
$totalPositions   = count($positionsList);
$totalEmployees   = array_sum(array_map(static fn($p) => (int)($p->employee_count ?? 0), $positionsList));
$managerCount     = 0;
$deputyCount      = 0;
$staffCount       = 0;
$otherCount       = 0;
$leadCount        = 0;
foreach ($positionsList as $p) {
    switch ((string)($p->category ?? '')) {
        case 'manager': $managerCount++; $leadCount++; break;
        case 'deputy':  $deputyCount++;  $leadCount++; break;
        case 'staff':   $staffCount++; break;
        default:        $otherCount++;
    }
}
?>

<style>
/* ====== positions — brand redesign (teal/gold, aligned with product-ui + official documents) ====== */
.positions-page { padding: 1.1rem 1.1rem 2.4rem; }
.positions-page * { box-sizing: border-box; }

/* Hero */
.positions-hero {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    border-radius: 20px;
    padding: clamp(1.4rem, 2.4vw + 1rem, 2.2rem);
    margin-bottom: 1.1rem;
    color: #effaf5;
    background: linear-gradient(120deg, #093f37 0%, #0d6658 64%, #147b6a 100%);
    box-shadow: 0 18px 40px rgba(9,63,55,.22);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.4rem;
    flex-wrap: wrap;
}
.positions-hero::after {
    content: '';
    position: absolute; inset: 0; z-index: -1; opacity: .42;
    background-image: radial-gradient(circle at 92% 18%, rgba(255,255,255,.22) 0 2px, transparent 3px);
    background-size: 18px 18px;
}
.positions-hero::before {
    content: '';
    position: absolute; z-index: -1; top: -30%; left: -8%; width: 18rem; height: 18rem;
    border-radius: 50%; background: radial-gradient(circle, rgba(200,148,63,.22), transparent 65%); filter: blur(20px);
}
.positions-hero__left { flex: 1 1 22rem; min-width: 17rem; }
.positions-hero .eyebrow {
    display: inline-flex; align-items: center; gap: .5rem;
    color: #c6eadf; font-size: .74rem; font-weight: 800; letter-spacing: .08em;
    text-transform: uppercase; margin-bottom: .65rem;
}
.positions-hero .eyebrow i { color: #f3ca78; font-size: .95rem; }
.positions-hero h1 {
    color: #fff; font-weight: 900; letter-spacing: -.02em;
    font-size: clamp(1.55rem, 2.2vw + .8rem, 2.3rem);
    line-height: 1.25; margin: 0 0 .6rem;
}
.positions-hero h1 .accent { color: #f3ca78; }
.positions-hero p { color: rgba(255,255,255,.78); max-width: 36rem; font-size: .92rem; line-height: 1.7; margin: 0 0 1rem; }
.positions-hero__cta {
    display: inline-flex; align-items: center; gap: .55rem;
    background: linear-gradient(90deg, #f3ca78, #c8943f);
    color: #3a2a06; font-weight: 800; border-radius: 12px; padding: .72rem 1.1rem;
    text-decoration: none; border: 0; cursor: pointer; box-shadow: 0 12px 28px rgba(200,148,63,.32);
    transition: transform .18s ease;
    font-family: inherit; font-size: .9rem;
}
.positions-hero__cta:hover { transform: translateY(-2px); }
.positions-hero__cta i { font-size: 1.05rem; }

/* Medallions */
.positions-hero__stats {
    display: flex; gap: .65rem; flex-wrap: wrap; flex: 0 0 auto;
}
.ph-stat {
    min-width: 6.6rem; padding: .85rem .9rem; border-radius: 16px;
    border: 1px solid rgba(255,255,255,.18); background: rgba(255,255,255,.10);
    backdrop-filter: blur(10px);
}
.ph-stat i { color: #f3ca78; font-size: .8rem; display: block; margin-bottom: .35rem; }
.ph-stat strong { display: block; color: #fff; font-weight: 900; font-size: 1.55rem; line-height: 1; letter-spacing: -.02em; }
.ph-stat span { font-size: .7rem; color: rgba(255,255,255,.7); display: block; margin-top: .35rem; }
.ph-stat--gold i { color: #f3ca78; }
.ph-stat--mint i { color: #8ce3c1; }

/* Filter bar */
.positions-filter {
    display: flex; flex-wrap: wrap; gap: .65rem; align-items: center; justify-content: space-between;
    padding: .55rem; margin-bottom: .85rem;
    background: rgba(255,255,255,.85); border: 1px solid #e2eee8; border-radius: 16px;
    box-shadow: 0 4px 14px rgba(22,48,43,.06);
}
.positions-filter__search { position: relative; flex: 1 1 18rem; min-width: 14rem; }
.positions-filter__search i {
    position: absolute; top: 50%; right: .85rem; transform: translateY(-50%);
    color: #6f817b; font-size: .9rem; pointer-events: none;
}
.positions-filter__search input {
    width: 100%; padding: .65rem 2.35rem .65rem .9rem; border-radius: 12px;
    border: 1px solid #d6e3dd; background: #f8fbf9; color: #16302b;
    font-family: inherit; font-size: .88rem; transition: border-color .2s, box-shadow .2s;
}
.positions-filter__search input:focus {
    outline: none; border-color: #0d5e52; background: #fff;
    box-shadow: 0 0 0 .22rem rgba(13,94,82,.14);
}
.positions-filter__pills { display: flex; gap: .45rem; flex-wrap: wrap; }
.pill {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .45rem .8rem .45rem .95rem; border-radius: 999px;
    border: 1px solid #d6e3dd; background: #fff; color: #16302b;
    font-family: inherit; font-size: .78rem; font-weight: 800; cursor: pointer;
    transition: background .18s, color .18s, border-color .18s;
}
.pill i { font-size: .8rem; color: #c8943f; }
.pill span {
    display: inline-grid; place-items: center; min-width: 1.4rem; height: 1.4rem;
    padding: 0 .35rem; font-size: .68rem; font-weight: 900; border-radius: 999px;
    background: #e8f0ec; color: #3a534c;
}
.pill:hover { border-color: #c8943f; background: #fffaf0; }
.pill.active { background: linear-gradient(120deg, #093f37, #0d6658); border-color: transparent; color: #fff; }
.pill.active i { color: #f3ca78; }
.pill.active span { background: rgba(255,255,255,.2); color: #fff; }

/* Card */
.positions-card {
    background: #fff; border: 1px solid #e2eee8; border-radius: 18px;
    box-shadow: 0 6px 22px rgba(22,48,43,.06); overflow: hidden;
}
.positions-card__header {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: .8rem 1.1rem; border-bottom: 1px solid #eef4f0;
    background: linear-gradient(180deg, #fff, #fbfdfc);
}
.positions-card__header .table-title {
    color: #16302b; font-weight: 800; font-size: .98rem; letter-spacing: -.01em;
    display: flex; align-items: center; gap: .5rem;
}
.positions-card__header .table-title i { color: #c8943f; }
.positions-card__header .table-subtitle {
    font-size: .74rem; color: #6f817b; display: block; margin-top: .2rem; font-weight: 600;
}
.positions-counter {
    display: inline-flex; align-items: center; gap: .55rem;
    background: #f4f9f6; border: 1px solid #e2eee8; border-radius: 999px;
    padding: .35rem .75rem .35rem .95rem; font-size: .74rem; font-weight: 800; color: #0d5e52;
}
.positions-counter strong { color: #093f37; font-weight: 900; }

/* Table */
.positions-table { width: 100%; border-collapse: collapse; }
.positions-table thead th {
    background: #0c2823; color: #fff; padding: .75rem 1rem; font-size: .72rem;
    font-weight: 800; letter-spacing: .06em; text-transform: uppercase; text-align: right; border: 0;
    border-bottom: 3px solid #c8943f;
}
.positions-table tbody tr { border-bottom: 1px solid #eef4f0; transition: background .18s; }
.positions-table tbody tr:nth-child(even) { background: #fbfdfc; }
.positions-table tbody tr:hover { background: #f6fbf8; }
.positions-table tbody td { padding: .78rem .95rem; font-size: .84rem; vertical-align: middle; border: 0; color: #16302b; }
.positions-table tbody td .title-strong { font-weight: 800; }
.positions-table tbody td.amount { font-weight: 800; color: #093f37; }

/* Category pills */
.cat-pill {
    display: inline-flex; align-items: center; gap: .45rem;
    padding: .35rem .7rem .35rem .85rem; border-radius: 999px; font-size: .74rem; font-weight: 800;
    border: 1px solid; white-space: nowrap;
}
.cat-pill i { font-size: .76rem; }
.cat-pill--manager {
    background: linear-gradient(120deg, #093f37, #0d5e52);
    color: #f3ca78; border-color: #c8943f;
    box-shadow: 0 3px 8px rgba(9,63,55,.22);
}
.cat-pill--deputy { background: #fffaf0; color: #8d6825; border-color: #c8943f; }
.cat-pill--staff { background: #edf5f0; color: #0d5e52; border-color: #b6dccb; }
.cat-pill--other { background: #f4f4f4; color: #555; border-color: #e2e2e2; }

/* Employee count badges */
.emp-badge {
    display: inline-flex; align-items: center; gap: .35rem; padding: .32rem .65rem; border-radius: 10px;
    background: #f4f9f6; border: 1px solid #e2eee8; font-size: .76rem; font-weight: 800;
    color: #0d5e52; min-width: 2.8rem; justify-content: center;
}
.emp-badge i { font-size: .75rem; color: #c8943f; }
.emp-badge--zero { background: #f6f6f6; border-color: #e6e6e6; color: #888; }

/* Actions */
.action-buttons { display: inline-flex; gap: .4rem; }
.btn-action {
    width: 2rem; height: 2rem; display: grid; place-items: center;
    border: 1px solid #e2eee8; border-radius: 10px; background: #fff; color: #0d5e52; cursor: pointer;
    transition: background .18s, color .18s, transform .18s, box-shadow .18s;
}
.btn-action:hover { transform: translateY(-1px); background: #093f37; color: #f3ca78; box-shadow: 0 6px 13px rgba(9,63,55,.28); border-color: transparent; }
.btn-action.btn-delete { color: #c3534d; border-color: #f1cbc7; }
.btn-action.btn-delete:hover { background: #b03228; color: #fff; border-color: #b03228; box-shadow: 0 6px 13px rgba(176,50,40,.32); }

/* Empty */
.positions-empty { padding: 2.5rem 1rem; text-align: center; color: #6f8a80; }
.positions-empty i { font-size: 2.2rem; color: #c8943f; opacity: .7; display: block; margin-bottom: .6rem; }
.positions-empty h3 { font-size: .95rem; font-weight: 800; color: #16302b; margin: 0 0 .3rem; }
.positions-empty p { font-size: .82rem; margin: 0; }

/* Modals */
.modal-content { border: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 22px 55px rgba(22,48,43,.22); }
.modal-header {
    background: linear-gradient(135deg, #093f37, #0d6658);
    color: #fff; border: 0; padding: 1rem 1.25rem;
}
.modal-title { color: #fff !important; font-weight: 800; letter-spacing: -.01em; display: flex; align-items: center; gap: .55rem; }
.modal-title i { color: #f3ca78; }
.modal-header .btn-close { filter: invert(1) brightness(1.5); }
.modal-body { padding: 1.25rem 1.4rem; background: #fbfdfc; }
.modal-footer { background: #fbfdfc; border-top: 1px solid #eef4f0; padding: .9rem 1.25rem; gap: .5rem; }
.form-label { font-weight: 700; color: #16302b; font-size: .82rem; margin-bottom: .35rem; display: flex; align-items: center; gap: .35rem; }
.form-label i { color: #c8943f; font-size: .72rem; }
.form-control, .form-select { border-color: #d6e3dd; font-size: .88rem; padding: .55rem .75rem; }
.form-control:focus, .form-select:focus {
    outline: none; border-color: #0d5e52; box-shadow: 0 0 0 .22rem rgba(13,94,82,.14); background: #fff;
}
.modal-footer .btn-primary {
    background: linear-gradient(120deg, #093f37, #0d5e52); border-color: #0d5e52;
    font-weight: 800; padding: .55rem 1.1rem; border-radius: 10px;
}
.modal-footer .btn-primary:hover { background: linear-gradient(120deg, #071f1b, #093f37); border-color: #093f37; }
.modal-footer .btn-secondary { font-weight: 700; padding: .55rem 1.1rem; border-radius: 10px; background: #fff; color: #34443d; border-color: #dde6e1; }

@media (max-width: 720px) {
    .positions-hero { padding: 1.25rem; gap: 1rem; }
    .positions-hero__stats { gap: .45rem; }
    .ph-stat { min-width: 5.4rem; padding: .55rem .6rem; }
    .ph-stat strong { font-size: 1.2rem; }
    .positions-page { padding: .7rem .7rem 1.6rem; }
    .positions-card__header { flex-direction: column; align-items: flex-start; gap: .5rem; }
}
</style>

<div class="positions-page">

    <!-- ====== Hero Banner ====== -->
    <section class="positions-hero" aria-labelledby="positions-title">
        <div class="positions-hero__left">
            <span class="eyebrow"><i class="fas fa-id-badge"></i> إدارة الموارد البشرية</span>
            <h1 id="positions-title">المناصب <span class="accent">الوظيفية</span></h1>
            <p>هيكل الوظائف المؤسسية وتصنيفاتها — تعريف وسعر وتصنيف وتقييم ونقل — بواجهة واحدة تليق بجودة المشروع وهويته.</p>
            <button type="button" class="positions-hero__cta js-open-add-modal" data-bs-toggle="modal" data-bs-target="#addPositionModal">
                <i class="fas fa-circle-plus"></i> تعريف منصب جديد
            </button>
        </div>
        <div class="positions-hero__stats" aria-label="إحصائيات سريعة">
            <div class="ph-stat">
                <i class="fas fa-layer-group"></i>
                <strong><?= (int)$totalPositions ?></strong>
                <span>منصب</span>
            </div>
            <div class="ph-stat ph-stat--gold">
                <i class="fas fa-users"></i>
                <strong><?= (int)$totalEmployees ?></strong>
                <span>موظف مرتبط</span>
            </div>
            <div class="ph-stat ph-stat--mint">
                <i class="fas fa-user-tie"></i>
                <strong><?= (int)$leadCount ?></strong>
                <span>قيادة+إشراف</span>
            </div>
        </div>
    </section>

    <!-- ====== Filter bar ====== -->
    <div class="positions-filter" role="search">
        <label class="positions-filter__search" for="positionSearch">
            <i class="fas fa-magnifying-glass"></i>
            <input id="positionSearch" type="search" placeholder="ابحث عن منصب أو فئة…" autocomplete="off">
        </label>
        <div class="positions-filter__pills" id="positionsCatPills" role="tablist">
            <button type="button" class="pill active" data-cat="all" role="tab">الكل <span><?= (int)$totalPositions ?></span></button>
            <button type="button" class="pill" data-cat="manager" role="tab"><i class="fas fa-crown"></i> مدير <span><?= (int)$managerCount ?></span></button>
            <button type="button" class="pill" data-cat="deputy" role="tab"><i class="fas fa-user-tie"></i> نائب <span><?= (int)$deputyCount ?></span></button>
            <button type="button" class="pill" data-cat="staff" role="tab"><i class="fas fa-users"></i> موظف <span><?= (int)$staffCount ?></span></button>
            <button type="button" class="pill" data-cat="other" role="tab"><i class="fas fa-ellipsis"></i> أخرى <span><?= (int)$otherCount ?></span></button>
        </div>
    </div>

    <!-- ====== Card + Table ====== -->
    <section class="positions-card" aria-labelledby="positions-card-title">
        <div class="positions-card__header">
            <div>
                <h2 class="table-title" id="positions-card-title">
                    <i class="fas fa-table-list"></i> سجل المناصب
                </h2>
                <small class="table-subtitle">اضغط رمز التعديل لتحديث البيانات، أو رمز الحذف لإزالته — مع حماية الحذف للمرتبطين بالموظفين.</small>
            </div>
            <span class="positions-counter" aria-live="polite"><i class="fas fa-layer-group"></i> <strong><?= (int)$totalPositions ?></strong> سجل</span>
        </div>

        <div class="table-responsive">
            <table class="positions-table" id="positionsTable">
                <thead>
                    <tr>
                        <th style="width:42%">المنصب الوظيفي</th>
                        <th style="width:22%">الفئة</th>
                        <th style="width:16%">موظفون</th>
                        <th style="width:20%">الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="positionsTableBody">
                    <?php if (!empty($positions)): ?>
                        <?php foreach ($positions as $position):
                            $cnt  = (int)($position->employee_count ?? 0);
                            $cat  = (string)($position->category ?? 'other');
                            $cats = ['manager' => ['مدير', 'cat-pill--manager', 'fa-crown'],
                                     'deputy'  => ['نائب / وكيل', 'cat-pill--deputy', 'fa-user-tie'],
                                     'staff'   => ['موظف',       'cat-pill--staff',   'fa-users'],
                                     'other'   => ['أخرى',       'cat-pill--other',   'fa-ellipsis-h']];
                            [$catLabel, $catClass, $catIcon] = $cats[$cat] ?? ['غير معروف', 'cat-pill--other', 'fa-circle-question'];
                        ?>
                            <tr data-cat="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" data-title="<?= htmlspecialchars($position->title, ENT_QUOTES, 'UTF-8') ?>">
                                <td>
                                    <span class="title-strong"><?php echo htmlspecialchars($position->title); ?></span>
                                </td>
                                <td>
                                    <span class="cat-pill <?= htmlspecialchars($catClass) ?>">
                                        <i class="fas <?= htmlspecialchars($catIcon) ?>"></i>
                                        <?= htmlspecialchars($catLabel) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="emp-badge<?= $cnt === 0 ? ' emp-badge--zero' : '' ?>" title="إنذار: يحتوي على <?= (int)$cnt ?> موظف">
                                        <i class="fas fa-id-badge"></i>
                                        <?= (int)$cnt ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit js-edit-position"
                                                data-id="<?= (int)$position->id ?>"
                                                data-title="<?= htmlspecialchars($position->title, ENT_QUOTES, 'UTF-8') ?>"
                                                data-category="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"
                                                title="تعديل">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="btn-action btn-delete js-delete-position"
                                                data-id="<?= (int)$position->id ?>"
                                                data-title="<?= htmlspecialchars($position->title, ENT_QUOTES, 'UTF-8') ?>"
                                                title="حذف">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($positions)): ?>
            <div class="positions-empty" role="status">
                <i class="fas fa-id-card-clip"></i>
                <h3>لا توجد مناصب مُعرّفة بعد</h3>
                <p>ابدأ تعريف أول منصب بالنقر على «تعريف منصب جديد» أعلى الصفحة.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- ================================================== -->
<!--           ===> النوافذ المنبثقة (Modals) <===       -->
<!-- ================================================== -->

<!-- نافذة إضافة منصب جديد -->
<div class="modal fade" id="addPositionModal" tabindex="-1" aria-labelledby="addPositionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?php echo APP_URL; ?>/employees/positions/store" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="addPositionModalLabel"><i class="fas fa-circle-plus"></i> تعريف منصب جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label"><i class="fas fa-briefcase"></i> اسم المنصب</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="مثال: مدير مشروع، أخصائي تسويق…" required>
                    </div>
                    <div class="mb-1">
                        <label for="category" class="form-label"><i class="fas fa-layer-group"></i> الفئة الوظيفية</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="staff">موظف</option>
                            <option value="deputy">نائب / وكيل</option>
                            <option value="manager">مدير</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> حفظ المنصب</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نافذة تعديل المنصب -->
<div class="modal fade" id="editPositionModal" tabindex="-1" aria-labelledby="editPositionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editPositionForm" action="<?php echo APP_URL; ?>/employees/positions/update" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPositionModalLabel"><i class="fas fa-pen"></i> تعديل المنصب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-title" class="form-label"><i class="fas fa-briefcase"></i> اسم المنصب</label>
                        <input type="text" class="form-control" id="edit-title" name="title" required>
                    </div>
                    <div class="mb-1">
                        <label for="edit-category" class="form-label"><i class="fas fa-layer-group"></i> الفئة الوظيفية</label>
                        <select class="form-select" id="edit-category" name="category" required>
                            <option value="staff">موظف</option>
                            <option value="deputy">نائب / وكيل</option>
                            <option value="manager">مدير</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* positions — client-side filter + category pills (no backend changes) */
(function () {
    var searchInput = document.getElementById('positionSearch');
    var rows = Array.prototype.slice.call(document.querySelectorAll('#positionsTable tbody tr'));
    var pills = document.querySelectorAll('#positionsCatPills .pill');
    var activeCategory = 'all';

    function applyFilters() {
        var q = (searchInput ? searchInput.value : '').trim().toLowerCase();
        var shown = 0;
        rows.forEach(function (row) {
            var cat = row.getAttribute('data-cat') || 'other';
            var title = (row.getAttribute('data-title') || '').toLowerCase();
            var matchCat = activeCategory === 'all' || cat === activeCategory;
            var matchQuery = !q || title.indexOf(q) !== -1 || cat.toLowerCase().indexOf(q) !== -1;
            var visible = matchCat && matchQuery;
            row.style.display = visible ? '' : 'none';
            if (visible) shown++;
        });
        var counter = document.querySelector('.positions-counter strong');
        if (counter) counter.textContent = String(shown);
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);

    pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            activeCategory = pill.getAttribute('data-cat') || 'all';
            pills.forEach(function (p) { p.classList.toggle('active', p === pill); });
            applyFilters();
        });
    });
    applyFilters();
})();
</script>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
