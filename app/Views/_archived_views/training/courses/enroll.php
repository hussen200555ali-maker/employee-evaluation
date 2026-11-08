<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
$csrf = $csrfToken ?? '';
$course = $course ?? null;
$participants = $participants ?? [];
?>
<!-- CD-032: شاشة ربط الموظفين بالدورة -->
<div class="container mt-4">
    <div class="card shadow-sm mb-4" style="border:2px solid #c8943f;border-radius:12px;">
        <div class="card-header" style="background:linear-gradient(90deg,#fbf3e2,#f6e8cf);border-bottom:2px solid #c8943f;">
            <strong style="color:#c8943f;"><i class="fas fa-book"></i> معلومات الدورة</strong>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><small class="text-muted">اسم الدورة</small><br><strong><?= htmlspecialchars($course->title ?? $course->description ?? '') ?></strong></div>
                <div class="col-md-3"><small class="text-muted">الجهة المنفذة</small><br><strong><?= htmlspecialchars($course->branch_name ?? '') ?></strong></div>
                <div class="col-md-3"><small class="text-muted">التواريخ</small><br><strong><?= htmlspecialchars($course->start_date ?? '') ?> → <?= htmlspecialchars($course->end_date ?? '') ?></strong></div>
                <div class="col-md-3"><small class="text-muted">الحالة</small><br><strong><?= htmlspecialchars($course->status ?? '') ?></strong></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>الموظفون المسجلون (<?= count($participants) ?>)</h5>
        <button class="btn btn-primary" onclick="toggleAddPanel()"><i class="fas fa-plus"></i> إضافة موظفين</button>
    </div>

    <?php if (empty($participants)): ?>
        <div class="alert alert-info text-center">لا يوجد موظفون مسجلون بعد.</div>
    <?php else: ?>
    <table class="table table-hover">
        <thead><tr><th>الاسم</th><th>الرقم الوظيفي</th><th>القسم</th><th>الفرع</th><th>حالة التقييم</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($participants as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p->full_name) ?></td>
                <td><?= htmlspecialchars($p->emp_code) ?></td>
                <td><?= htmlspecialchars($p->dept_name ?? '—') ?></td>
                <td><?= htmlspecialchars($p->branch_name ?? '—') ?></td>
                <td>
                    <?php if ($p->status === 'completed'): ?>
                        <span class="badge bg-success">مكتمل</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">بانتظار</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="<?= APP_URL ?>/training/courses/enroll/remove" style="display:inline;" onsubmit="return confirm('تأكيد الإزالة؟')">
                        <input type="hidden" name="_token" value="<?= $csrf ?>">
                        <input type="hidden" name="participant_id" value="<?= (int)$p->id ?>">
                        <input type="hidden" name="course_id" value="<?= (int)$course->id ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- بحث وإضافة موظفين -->
    <div id="addPanel" style="display:none;border:2px dashed #c8943f;border-radius:10px;padding:16px;background:#fffbf3;margin-top:16px;">
        <strong style="color:#c8943f;font-size:14px;"><i class="fas fa-search"></i> بحث وإضافة موظفين (من جميع الفروع)</strong>
        <div class="input-group mt-2">
            <input type="text" id="empSearchInput" class="form-control" placeholder="ابحث بالاسم أو الرقم الوظيفي...">
            <button class="btn btn-primary" onclick="searchEmployees()"><i class="fas fa-search"></i> بحث</button>
        </div>
        <div id="searchResults" style="margin-top:10px;"></div>
        <form method="POST" action="<?= APP_URL ?>/training/courses/enroll/store" id="enrollForm">
            <input type="hidden" name="_token" value="<?= $csrf ?>">
            <input type="hidden" name="course_id" value="<?= (int)$course->id ?>">
            <div id="selectedList" style="margin-top:10px;"></div>
            <button type="submit" class="btn btn-success mt-2" id="submitBtn" style="display:none;"><i class="fas fa-check"></i> تسجيل المحددين</button>
        </form>
        <small class="text-muted d-block mt-2">💡 يمكن إضافة موظفين من أي فرع — التقييم مرتبط بالدورة لا بالفرع</small>
    </div>
</div>

<script>
var appUrl = '<?= defined('APP_URL') ? htmlspecialchars(APP_URL, ENT_QUOTES) : '' ?>';
var selectedIds = [];

function toggleAddPanel() { var p = document.getElementById('addPanel'); p.style.display = p.style.display === 'none' ? 'block' : 'none'; }

function searchEmployees() {
    var q = document.getElementById('empSearchInput').value.trim();
    if (q.length < 2) return;
    fetch(appUrl + '/api/employees/search?q=' + encodeURIComponent(q)).then(r => r.json()).then(function(data) {
        var list = Array.isArray(data) ? data : (data.employees || []);
        var html = '';
        list.forEach(function(e) {
            if (selectedIds.indexOf(e.id) !== -1) return;
            html += '<div class="d-flex justify-content-between align-items-center p-2 border-bottom"><span>' + e.full_name + ' (' + e.emp_code + ')</span><button type="button" class="btn btn-sm btn-outline-primary" onclick="addEmployee(' + e.id + ', \'' + e.full_name.replace(/'/g, "\\'") + '\', \'' + e.emp_code + '\')">＋ إضافة</button></div>';
        });
        document.getElementById('searchResults').innerHTML = html || '<small class="text-muted">لا نتائج</small>';
    });
}

function addEmployee(id, name, code) {
    if (selectedIds.indexOf(id) !== -1) return;
    selectedIds.push(id);
    var div = document.createElement('div');
    div.className = 'd-flex justify-content-between align-items-center p-2 border-bottom';
    div.id = 'sel-' + id;
    div.innerHTML = '<span>' + name + ' (' + code + ')</span><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEmployee(' + id + ')">إزالة</button><input type="hidden" name="employee_ids[]" value="' + id + '">';
    document.getElementById('selectedList').appendChild(div);
    document.getElementById('submitBtn').style.display = '';
}

function removeEmployee(id) {
    selectedIds = selectedIds.filter(function(x) { return x !== id; });
    var el = document.getElementById('sel-' + id);
    if (el) el.remove();
    if (selectedIds.length === 0) document.getElementById('submitBtn').style.display = 'none';
}
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
