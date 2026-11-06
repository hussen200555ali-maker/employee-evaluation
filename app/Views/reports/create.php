<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
?>
<style>
.report-search-bar { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
.report-search-bar select, .report-search-bar input {
    padding:10px 14px; border:1px solid #ddd; border-radius:8px; font-family:'Cairo'; font-size:13px;
}
.report-search-bar input { flex:1; min-width:200px; }
.report-search-bar .btn-search { background:#0c2823; color:#c8943f; border:none; padding:10px 24px; border-radius:8px; cursor:pointer; font-weight:700; }
.emp-result-card {
    background:#fff; border:1px solid #e8e8e8; border-radius:12px; padding:16px 20px; margin-bottom:12px;
    display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;
    box-shadow:0 1px 4px rgba(0,0,0,0.04);
}
.emp-result-card .emp-info { display:flex; align-items:center; gap:14px; }
.emp-result-card .avatar {
    width:48px; height:48px; border-radius:50%; background:#0c2823; color:#c8943f;
    display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:700;
    flex-shrink:0;
}
.emp-result-card .emp-name { font-size:15px; font-weight:700; color:#0c2823; }
.emp-result-card .emp-meta { font-size:11px; color:#6c757d; margin-top:2px; }
.emp-result-card .eval-summary { font-size:11px; color:#6c757d; margin-top:4px; background:#f5f0e8; padding:3px 10px; border-radius:6px; display:inline-block; }
.badge-has-data { background:rgba(13,94,82,0.1); color:#0d5e52; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; border:1px solid rgba(13,94,82,0.15); }
.badge-no-data { background:rgba(200,148,63,0.1); color:#c8943f; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; border:1px solid rgba(200,148,63,0.15); }
.btn-issue { background:#0c2823; color:#c8943f; border:none; padding:8px 18px; border-radius:8px; cursor:pointer; font-weight:700; font-size:12px; font-family:'Cairo'; }
.btn-print { background:#f5f0e8; color:#c8943f; border:1px solid #c8943f; padding:8px 18px; border-radius:8px; cursor:pointer; font-weight:700; font-size:12px; font-family:'Cairo'; text-decoration:none; display:inline-block; }
.cascade-hint { font-size:11px; color:#c8943f; }
#confirmModal { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(12,40,35,0.5); z-index:9999; align-items:center; justify-content:center; }
#confirmModal.show { display:flex; }
.modal-box { background:#fff; border-radius:12px; max-width:460px; width:90%; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,0.15); }
.modal-header { background:#8b2222; color:#fff; padding:14px 20px; font-weight:700; font-size:15px; }
.modal-body { padding:20px; }
.modal-body p { font-size:13px; line-height:1.7; margin-bottom:8px; }
.modal-body .warn-box { background:rgba(139,34,34,0.05); border:1px solid rgba(139,34,34,0.1); border-radius:8px; padding:10px 14px; margin:12px 0; font-size:12px; color:#8b2222; }
.modal-footer { padding:14px 20px; display:flex; gap:10px; border-top:1px solid #e8e8e8; }
.modal-footer form { margin:0; }
.modal-footer .btn-confirm { background:#0c2823; color:#c8943f; border:none; padding:8px 18px; border-radius:8px; cursor:pointer; font-weight:700; font-size:13px; }
.modal-footer .btn-cancel { background:#e8e8e8; color:#6c757d; border:none; padding:8px 18px; border-radius:8px; cursor:pointer; font-size:13px; }
</style>

<div class="container-fluid">
    <h2><?= htmlspecialchars($pageTitle ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
    <hr>

    <!-- Search + Cascading Filters -->
    <form method="GET" action="<?= APP_URL ?>/reports/create" class="report-search-bar">
        <select name="branch_id" id="branchFilter" aria-label="الفرع">
            <option value="0">كل الفروع</option>
            <?php foreach ($branches ?? [] as $b): ?>
                <option value="<?= (int)$b->id ?>" <?= ($branchId ?? 0) === (int)$b->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b->name, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="department_id" id="deptFilter" aria-label="القسم">
            <option value="0">كل الأقسام</option>
            <?php foreach ($departments ?? [] as $d): ?>
                <option value="<?= (int)$d->id ?>" <?= ($deptId ?? 0) === (int)$d->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d->name, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="q" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="ابحث بالاسم أو الرقم الوظيفي...">
        <button type="submit" class="btn-search">🔍 بحث</button>
    </form>

    <?php if (!empty($results)): ?>
        <?php foreach ($results as $emp): ?>
            <?php $hasEvaluations = ($emp->eval_summary ?? '') !== 'لا توجد تقييمات'; ?>
            <div class="emp-result-card">
                <div class="emp-info">
                    <div class="avatar"><?= htmlspecialchars(mb_substr((string)$emp->full_name, 0, 1), ENT_QUOTES, 'UTF-8') ?></div>
                    <div>
                        <div class="emp-name"><?= htmlspecialchars($emp->full_name, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="emp-meta">
                            <?= htmlspecialchars($emp->emp_code, ENT_QUOTES, 'UTF-8') ?>
                            · <?= htmlspecialchars($emp->department_name ?? '', ENT_QUOTES, 'UTF-8') ?>
                            · <?= htmlspecialchars($emp->branch_name ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="eval-summary"><?= htmlspecialchars($emp->eval_summary ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <?php if ($hasEvaluations): ?>
                        <span class="badge-has-data">لديه تقييمات</span>
                        <button type="button" class="btn-issue"
                                data-emp-id="<?= (int)$emp->id ?>"
                                data-emp-name="<?= htmlspecialchars($emp->full_name, ENT_QUOTES, 'UTF-8') ?>"
                                data-emp-code="<?= htmlspecialchars($emp->emp_code, ENT_QUOTES, 'UTF-8') ?>">
                            📄 إصدار تقرير
                        </button>
                    <?php else: ?>
                        <span class="badge-no-data">لا توجد تقييمات</span>
                        <a class="btn-print" href="<?= APP_URL ?>/reports/print/<?= (int)$emp->id ?>">📄⬇ طباعة إفادة PDF</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php elseif (($search ?? '') !== '' || ($branchId ?? 0) > 0 || ($deptId ?? 0) > 0): ?>
        <div style="text-align:center; padding:40px; color:#999;">
            <div style="font-size:40px; margin-bottom:12px;">🔍</div>
            لا توجد نتائج مطابقة للبحث.
        </div>
    <?php else: ?>
        <div style="text-align:center; padding:40px; color:#999;">
            <div style="font-size:40px; margin-bottom:12px;">📋</div>
            ابدأ بالبحث عن موظف لإصدار تقرير.
        </div>
    <?php endif; ?>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-box">
        <div class="modal-header" id="modalTitle">⚠ تنبيه — إصدار تقرير رسمي</div>
        <div class="modal-body">
            <p>أنت على وشك إصدار تقرير رسمي للموظف: <strong id="modalEmpName"></strong></p>
            <div class="warn-box">⚠ إصدار التقرير يعني حفظ نسخة ثابتة لا يمكن تعديلها أو حذفها. سيُحفظ برقم تسلسلي في السجل المؤرشف.</div>
        </div>
        <div class="modal-footer">
            <form method="POST" action="<?= APP_URL ?>/reports/generate" id="modalForm">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="employee_id" id="modalEmpId" value="0">
                <button type="submit" class="btn-confirm">✓ تأكيد الإصدار</button>
            </form>
            <button type="button" class="btn-cancel" onclick="closeModal()">✕ إلغاء</button>
        </div>
    </div>
</div>

<script>
(function () {
    var APP_URL = <?= json_encode(APP_URL, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var selectedDepartment = <?= (int)($deptId ?? 0) ?>;
    var branchFilter = document.getElementById('branchFilter');
    var deptFilter = document.getElementById('deptFilter');

    // Cascading dropdowns: when branch changes, fetch departments via AJAX.
    branchFilter.addEventListener('change', function () {
        var branchId = this.value;
        deptFilter.innerHTML = '<option value="0">كل الأقسام</option>';
        if (branchId <= 0) {
            return;
        }
        fetch(APP_URL + '/api/departments/by-branch/' + encodeURIComponent(branchId), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                // الواجهة البرمجية تُرجع مصفوفة مباشرة، ونتسامح مع الغلاف أيضاً.
                var departments = Array.isArray(data) ? data : (data.departments || data.data || []);
                departments.forEach(function (department) {
                    var option = document.createElement('option');
                    option.value = department.id;
                    option.textContent = department.name;
                    if (String(department.id) === String(selectedDepartment)) {
                        option.selected = true;
                    }
                    deptFilter.appendChild(option);
                });
            })
            .catch(function () {});
    });

    // Modal: read employee data from data-* attributes (safe with quotes).
    document.querySelectorAll('.btn-issue').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(
                button.dataset.empId,
                button.dataset.empName,
                button.dataset.empCode
            );
        });
    });

    window.openModal = function (empId, empName, empCode) {
        document.getElementById('modalEmpId').value = empId;
        document.getElementById('modalEmpName').textContent = empName + ' (' + empCode + ')';
        document.getElementById('confirmModal').classList.add('show');
    };

    window.closeModal = function () {
        document.getElementById('confirmModal').classList.remove('show');
    };

    document.getElementById('confirmModal').addEventListener('click', function (event) {
        if (event.target === this) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
})();
</script>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
