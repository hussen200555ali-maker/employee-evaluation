<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<?php
// محسوبات للعرض (لا تغيير على الكنترولر الحالي)
$branchesList    = is_array($branches ?? null) ? $branches : [];
$totalBranches   = count($branchesList);
$totalEmployees  = array_sum(array_map(static fn($b) => (int)($b->employee_count ?? 0), $branchesList));
$withManagerCount = 0;
foreach ($branchesList as $b) {
    if (!empty($b->manager_name)) { $withManagerCount++; }
}
?>

<style>
/* ====== branches — brand redesign (teal/gold identity) ====== */
.branches-page { padding: 1.1rem 1.1rem 2.4rem; }
.branches-page * { box-sizing: border-box; }

.branches-hero {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    border-radius: 20px;
    padding: clamp(1.4rem, 2.4vw + 1rem, 2.2rem);
    margin-bottom: 1.1rem;
    color: #effaf5;
    background: linear-gradient(120deg, #093f37 0%, #0d6658 64%, #147b6a 100%);
    box-shadow: 0 18px 40px rgba(9,63,55,.22);
    display: flex; align-items: center; justify-content: space-between; gap: 1.4rem; flex-wrap: wrap;
}
.branches-hero::after {
    content: ''; position: absolute; inset: 0; z-index: -1; opacity: .42;
    background-image: radial-gradient(circle at 92% 18%, rgba(255,255,255,.22) 0 2px, transparent 3px);
    background-size: 18px 18px;
}
.branches-hero::before {
    content: ''; position: absolute; z-index: -1; top: -30%; left: -8%; width: 18rem; height: 18rem;
    border-radius: 50%; background: radial-gradient(circle, rgba(200,148,63,.22), transparent 65%); filter: blur(20px);
}
.branches-hero__left { flex: 1 1 22rem; min-width: 17rem; }
.branches-hero .eyebrow {
    display: inline-flex; align-items: center; gap: .5rem;
    color: #c6eadf; font-size: .74rem; font-weight: 800; letter-spacing: .08em;
    text-transform: uppercase; margin-bottom: .65rem;
}
.branches-hero .eyebrow i { color: #f3ca78; font-size: .95rem; }
.branches-hero h1 {
    color: #fff; font-weight: 900; letter-spacing: -.02em;
    font-size: clamp(1.55rem, 2.2vw + .8rem, 2.3rem);
    line-height: 1.25; margin: 0 0 .6rem;
}
.branches-hero h1 .accent { color: #f3ca78; }
.branches-hero p { color: rgba(255,255,255,.78); max-width: 36rem; font-size: .92rem; line-height: 1.7; margin: 0 0 1rem; }
.branches-hero__cta {
    display: inline-flex; align-items: center; gap: .55rem;
    background: linear-gradient(90deg, #f3ca78, #c8943f);
    color: #3a2a06; font-weight: 800; border-radius: 12px; padding: .72rem 1.1rem;
    text-decoration: none; border: 0; cursor: pointer; box-shadow: 0 12px 28px rgba(200,148,63,.32);
    transition: transform .18s ease; font-family: inherit; font-size: .9rem;
}
.branches-hero__cta:hover { transform: translateY(-2px); }
.branches-hero__cta i { font-size: 1.05rem; }

.branches-hero__stats {
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

.branches-filter {
    display: flex; flex-wrap: wrap; gap: .65rem; align-items: center; justify-content: space-between;
    padding: .55rem; margin-bottom: .85rem;
    background: rgba(255,255,255,.85); border: 1px solid #e2eee8; border-radius: 16px;
    box-shadow: 0 4px 14px rgba(22,48,43,.06);
}
.branches-filter__search { position: relative; flex: 1 1 18rem; min-width: 14rem; }
.branches-filter__search i {
    position: absolute; top: 50%; right: .85rem; transform: translateY(-50%);
    color: #6f817b; font-size: .9rem; pointer-events: none;
}
.branches-filter__search input {
    width: 100%; padding: .65rem 2.35rem .65rem .9rem; border-radius: 12px;
    border: 1px solid #d6e3dd; background: #f8fbf9; color: #16302b;
    font-family: inherit; font-size: .88rem; transition: border-color .2s, box-shadow .2s;
}
.branches-filter__search input:focus {
    outline: none; border-color: #0d5e52; background: #fff;
    box-shadow: 0 0 0 .22rem rgba(13,94,82,.14);
}

.branches-card {
    background: #fff; border: 1px solid #e2eee8; border-radius: 18px;
    box-shadow: 0 6px 22px rgba(22,48,43,.06); overflow: hidden;
}
.branches-card__header {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: .8rem 1.1rem; border-bottom: 1px solid #eef4f0;
    background: linear-gradient(180deg, #fff, #fbfdfc);
}
.branches-card__header .table-title {
    color: #16302b; font-weight: 800; font-size: .98rem; letter-spacing: -.01em;
    display: flex; align-items: center; gap: .5rem;
}
.branches-card__header .table-title i { color: #c8943f; }
.branches-card__header .table-subtitle {
    font-size: .74rem; color: #6f817b; display: block; margin-top: .2rem; font-weight: 600;
}
.branches-counter {
    display: inline-flex; align-items: center; gap: .55rem;
    background: #f4f9f6; border: 1px solid #e2eee8; border-radius: 999px;
    padding: .35rem .75rem .35rem .95rem; font-size: .74rem; font-weight: 800; color: #0d5e52;
}
.branches-counter strong { color: #093f37; font-weight: 900; }

.branches-table { width: 100%; border-collapse: collapse; }
.branches-table thead th {
    background: #0c2823; color: #fff; padding: .75rem 1rem; font-size: .72rem;
    font-weight: 800; letter-spacing: .06em; text-transform: uppercase; text-align: right; border: 0;
    border-bottom: 3px solid #c8943f;
}
.branches-table tbody tr { border-bottom: 1px solid #eef4f0; transition: background .18s; }
.branches-table tbody tr:nth-child(even) { background: #fbfdfc; }
.branches-table tbody tr:hover { background: #f6fbf8; }
.branches-table tbody td { padding: .78rem .95rem; font-size: .84rem; vertical-align: middle; border: 0; color: #16302b; }
.branches-table tbody td .title-strong { font-weight: 800; }
.row-num { font-weight: 800; color: #c8943f; width: 3rem; text-align: center; }

.branch-name-cell {
    display: inline-flex; align-items: center; gap: .55rem;
}
.branch-name-cell__icon {
    width: 1.9rem; height: 1.9rem; border-radius: 10px; display: grid; place-items: center;
    background: linear-gradient(135deg, #093f37, #0d6658); color: #f3ca78; font-size: .8rem; flex: 0 0 auto;
}
.branch-location {
    display: inline-flex; align-items: center; gap: .4rem;
    font-size: .82rem; color: #34443d; font-weight: 600;
}
.branch-location i { color: #c8943f; }

.emp-badge {
    display: inline-flex; align-items: center; gap: .35rem; padding: .32rem .65rem; border-radius: 10px;
    background: #f4f9f6; border: 1px solid #e2eee8; font-size: .76rem; font-weight: 800;
    color: #0d5e52; min-width: 2.8rem; justify-content: center;
}
.emp-badge i { font-size: .75rem; color: #c8943f; }
.emp-badge--zero { background: #f6f6f6; border-color: #e6e6e6; color: #888; }

.action-buttons { display: inline-flex; gap: .4rem; }
.btn-action {
    width: 2rem; height: 2rem; display: grid; place-items: center;
    border: 1px solid #e2eee8; border-radius: 10px; background: #fff; color: #0d5e52; cursor: pointer;
    transition: background .18s, color .18s, transform .18s, box-shadow .18s;
}
.btn-action:hover { transform: translateY(-1px); background: #093f37; color: #f3ca78; box-shadow: 0 6px 13px rgba(9,63,55,.28); border-color: transparent; }
.btn-action.btn-delete { color: #c3534d; border-color: #f1cbc7; }
.btn-action.btn-delete:hover { background: #b03228; color: #fff; border-color: #b03228; box-shadow: 0 6px 13px rgba(176,50,40,.32); }

.branches-empty { padding: 2.5rem 1rem; text-align: center; color: #6f8a80; }
.branches-empty i { font-size: 2.2rem; color: #c8943f; opacity: .7; display: block; margin-bottom: .6rem; }
.branches-empty h3 { font-size: .95rem; font-weight: 800; color: #16302b; margin: 0 0 .3rem; }
.branches-empty p { font-size: .82rem; margin: 0; }

.modal-content { border: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 22px 55px rgba(22,48,43,.22); }
.modal-header {
    background: linear-gradient(135deg, #093f37, #0d6658); color: #fff; border: 0; padding: 1rem 1.25rem;
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
    .branches-hero { padding: 1.25rem; gap: 1rem; }
    .branches-page { padding: .7rem .7rem 1.6rem; }
    .branches-card__header { flex-direction: column; align-items: flex-start; gap: .5rem; }
}
</style>

<div class="branches-page">

    <!-- ====== Hero Banner ====== -->
    <section class="branches-hero" aria-labelledby="branches-title">
        <div class="branches-hero__left">
            <span class="eyebrow"><i class="fas fa-building"></i> إدارة الموارد البشرية</span>
            <h1 id="branches-title">الفروع <span class="accent">المؤسسية</span></h1>
            <p>تعريف فروع المؤسسة ومواقعها ومديريها وتوزيع الموظفين عليها — نظرة واحدة، إجراءات مرتبة، هوية متّسقة مع بقية الشاشت.</p>
            <?php if (App\Core\Auth::userHasPermission('branches.create')): ?>
                <button type="button" class="branches-hero__cta" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                    <i class="fas fa-circle-plus"></i> إضافة فرع جديد
                </button>
            <?php endif; ?>
        </div>
        <div class="branches-hero__stats" aria-label="إحصائيات سريعة">
            <div class="ph-stat">
                <i class="fas fa-building"></i>
                <strong><?= (int)$totalBranches ?></strong>
                <span>فرع مُسجّل</span>
            </div>
            <div class="ph-stat ph-stat--gold">
                <i class="fas fa-users"></i>
                <strong><?= (int)$totalEmployees ?></strong>
                <span>موظف موزّع</span>
            </div>
            <div class="ph-stat ph-stat--mint">
                <i class="fas fa-user-tie"></i>
                <strong><?= (int)$withManagerCount ?></strong>
                <span>بمدير معيّن</span>
            </div>
        </div>
    </section>

    <!-- ====== Filter bar ====== -->
    <div class="branches-filter" role="search">
        <label class="branches-filter__search" for="branchSearch">
            <i class="fas fa-magnifying-glass"></i>
            <input id="branchSearch" type="search" placeholder="ابحث عن فرع أو موقع أو مدير…" autocomplete="off">
        </label>
        <span class="branches-counter" aria-live="polite"><i class="fas fa-layer-group"></i> <strong><?= (int)$totalBranches ?></strong> فرع</span>
    </div>

    <!-- ====== Card + Table ====== -->
    <section class="branches-card" aria-labelledby="branches-card-title">
        <div class="branches-card__header">
            <div>
                <h2 class="table-title" id="branches-card-title">
                    <i class="fas fa-table-list"></i> سجل الفروع
                </h2>
                <small class="table-subtitle">اضغط زر التعديل لتحديث البيانات، أو زر الحذف لإزالته — مع حماية الحذف للمرتبطين بالموظفين.</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="branches-table" id="branchesTable">
                <thead>
                    <tr>
                        <th style="width:4%">#</th>
                        <th style="width:22%">اسم الفرع</th>
                        <th style="width:14%">الموقع</th>
                        <th style="width:22%">مدير الفرع</th>
                        <th style="width:10%">موظفون</th>
                        <th style="width:14%">تاريخ الإنشاء</th>
                        <th style="width:14%">الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="branchesTableBody">
                    <?php if (!empty($branches)): ?>
                        <?php foreach ($branches as $index => $branch):
                            $cnt = (int)($branch->employee_count ?? 0);
                            $loc = trim((string)($branch->location ?? ''));
                        ?>
                            <tr data-name="<?= htmlspecialchars($branch->name, ENT_QUOTES, 'UTF-8') ?>" data-location="<?= htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') ?>" data-manager="<?= htmlspecialchars((string)($branch->manager_name ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <td class="row-num"><?= (int)$index + 1 ?></td>
                                <td>
                                    <span class="branch-name-cell">
                                        <span class="branch-name-cell__icon"><i class="fas fa-building"></i></span>
                                        <strong><?= htmlspecialchars($branch->name) ?></strong>
                                    </span>
                                </td>
                                <td><span class="branch-location"><i class="fas fa-location-dot"></i><?= htmlspecialchars($loc !== '' ? $loc : '—') ?></span></td>
                                <td><?= htmlspecialchars($branch->manager_name ?? '—') ?></td>
                                <td>
                                    <span class="emp-badge<?= $cnt === 0 ? ' emp-badge--zero' : '' ?>" title="عدد الموظفين في هذا الفرع">
                                        <i class="fas fa-users"></i><?= (int)$cnt ?>
                                    </span>
                                </td>
                                <td><?= !empty($branch->created_at) ? date('Y-m-d', strtotime($branch->created_at)) : '—' ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (App\Core\Auth::userHasPermission('branches.edit')): ?>
                                            <button class="btn-action btn-edit js-edit-branch"
                                                    data-id="<?= (int)$branch->id ?>"
                                                    data-name="<?= htmlspecialchars($branch->name, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-location="<?= htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-manager-id="<?= (int)($branch->manager_id ?? 0) ?>"
                                                    title="تعديل">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (App\Core\Auth::userHasPermission('branches.delete')): ?>
                                            <button class="btn-action btn-delete js-delete-branch"
                                                    data-id="<?= (int)$branch->id ?>"
                                                    data-name="<?= htmlspecialchars($branch->name, ENT_QUOTES, 'UTF-8') ?>"
                                                    title="حذف">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($branches)): ?>
            <div class="branches-empty" role="status">
                <i class="fas fa-building-circle-xmark"></i>
                <h3>لا توجد فروع مُسجّلة بعد</h3>
                <p>ابدأ تعريف أول فرع بالنقر على «إضافة فرع جديد» أعلى الصفحة.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- نافذة إضافة فرع جديد -->
<div class="modal fade" id="addBranchModal" tabindex="-1" aria-labelledby="addBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?php echo APP_URL; ?>/branches" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="addBranchModalLabel"><i class="fas fa-circle-plus"></i> إضافة فرع جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add-name" class="form-label"><i class="fas fa-building"></i> اسم الفرع</label>
                        <input type="text" class="form-control" id="add-name" name="name" placeholder="مثال: فرع الرياض الرئيسي" required>
                    </div>
                    <div class="mb-3">
                        <label for="add-location" class="form-label"><i class="fas fa-location-dot"></i> الموقع</label>
                        <textarea class="form-control" id="add-location" name="location" rows="2" placeholder="حي / مدينة / شارع" required></textarea>
                    </div>
                    <?php if (!empty($managers)): ?>
                        <div class="mb-1">
                            <label for="add-manager_id" class="form-label"><i class="fas fa-user-tie"></i> مدير الفرع (اختياري)</label>
                            <select class="form-select" id="add-manager_id" name="manager_id">
                                <option value="">-- اختر مديراً --</option>
                                <?php foreach ($managers as $manager): ?>
                                    <option value="<?= (int)$manager->id ?>"><?= htmlspecialchars($manager->full_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> حفظ الفرع</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نافذة تعديل فرع -->
<div class="modal fade" id="editBranchModal" tabindex="-1" aria-labelledby="editBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?php echo APP_URL; ?>/branches/update" method="POST" id="editBranchForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBranchModalLabel"><i class="fas fa-pen"></i> تعديل الفرع</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-name" class="form-label"><i class="fas fa-building"></i> اسم الفرع</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-location" class="form-label"><i class="fas fa-location-dot"></i> الموقع</label>
                        <textarea class="form-control" id="edit-location" name="location" rows="2" required></textarea>
                    </div>
                    <?php if (!empty($managers)): ?>
                        <div class="mb-1">
                            <label for="edit-manager_id" class="form-label"><i class="fas fa-user-tie"></i> مدير الفرع (اختياري)</label>
                            <select class="form-select" id="edit-manager_id" name="manager_id">
                                <option value="">-- اختر مديراً --</option>
                                <?php foreach ($managers as $manager): ?>
                                    <option value="<?= (int)$manager->id ?>"><?= htmlspecialchars($manager->full_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
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
/* branches — client-side filter (no backend changes) */
(function () {
    var searchInput = document.getElementById('branchSearch');
    var rows = Array.prototype.slice.call(document.querySelectorAll('#branchesTable tbody tr'));
    var counter = document.querySelector('.branches-counter strong');

    function applyFilter() {
        var q = (searchInput ? searchInput.value : '').trim().toLowerCase();
        var shown = 0;
        rows.forEach(function (row) {
            var haystack = [
                row.getAttribute('data-name') || '',
                row.getAttribute('data-location') || '',
                row.getAttribute('data-manager') || ''
            ].join(' ').toLowerCase();
            var visible = !q || haystack.indexOf(q) !== -1;
            row.style.display = visible ? '' : 'none';
            if (visible) shown++;
        });
        if (counter) counter.textContent = String(shown);
    }

    if (searchInput) searchInput.addEventListener('input', applyFilter);
    applyFilter();
})();
</script>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
