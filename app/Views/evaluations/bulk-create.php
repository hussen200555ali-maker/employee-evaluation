<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';
// CD-024.1: منع التخزين المؤقت للصفحة لضمان تحميل أحدث نسخة من JS
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!-- CD-028 UX active — Enterprise polish + responsive + transitions -->
<style>
.bulk-setup { display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:16px; align-items:end; background:#fff; border-radius:12px; padding:20px 24px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:16px; }
.bulk-setup label { display:block; font-size:11px; color:#6c757d; margin-bottom:4px; font-weight:600; }
.bulk-setup select, .bulk-setup input { width:100%; padding:10px 14px; border:1px solid #e8e8e8; border-radius:8px; font-size:13px; font-family:'Cairo'; }
.bulk-setup .lock-badge { font-size:9px; color:#6c757d; margin-top:4px; }
.bulk-search { display:flex; gap:12px; align-items:center; margin-bottom:20px; }
.bulk-search input { flex:1; padding:12px 18px; border:2px solid #e8e8e8; border-radius:8px; font-size:13px; font-family:'Cairo'; }
.bulk-search input:focus { outline:none; border-color:#c8943f; }
.bulk-search .btn-add { padding:12px 20px; background:#0c2823; color:#c8943f; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
.search-results { display:none; position:absolute; background:#fff; border:1px solid #e8e8e8; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,0.1); max-height:300px; overflow-y:auto; z-index:50; min-width:400px; }
.search-results .item { padding:10px 16px; cursor:pointer; border-bottom:1px solid #f0f0f0; font-size:12px; }
.search-results .item:hover { background:#f5f0e8; }
.search-results .item .name { font-weight:600; color:#0c2823; }
.search-results .item .meta { font-size:10px; color:#6c757d; }
.emp-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:16px; overflow:hidden; border:1px solid #e8e8e8; transition:background 0.2s; }
.emp-card.saved { background:#e8f5f0; border-color:#0d5e52; }
.emp-card .card-header { padding:14px 20px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e8e8e8; background:#f5f0e8; }
.emp-card.saved .card-header { background:#e8f5f0; border-bottom-color:#0d5e52; }
.emp-card .card-header .emp-info { display:flex; align-items:center; gap:12px; }
.emp-card .avatar { width:42px; height:42px; border-radius:50%; background:#0c2823; color:#c8943f; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; }
.emp-card.saved .avatar { background:#0d5e52; color:#fff; }
.emp-card .emp-name { font-size:14px; font-weight:700; color:#0c2823; }
.emp-card .emp-meta { font-size:11px; color:#6c757d; }
.saved-badge { background:#0d5e52; color:#fff; padding:4px 14px; border-radius:20px; font-size:11px; font-weight:700; }
.emp-card .card-body { padding:16px 20px; }
.criteria-title { font-size:12px; font-weight:700; color:#0c2823; margin:10px 0 6px; padding-bottom:4px; border-bottom:1px solid #e8e8e8; }
.criteria-row { display:flex; align-items:center; justify-content:space-between; padding:5px 0; }
.criteria-row .lbl { font-size:12px; flex:1; }
.scale-group { display:flex; gap:4px; }
.scale-btn { width:32px; height:32px; border:1px solid #e8e8e8; border-radius:6px; background:#fff; font-size:13px; font-weight:600; cursor:pointer; font-family:'Cairo'; color:#6c757d; }
.scale-btn:hover { border-color:#c8943f; color:#0c2823; }
.scale-btn.selected { background:#0c2823; color:#c8943f; border-color:#0c2823; }
.emp-card.saved .scale-btn { opacity:0.5; }
.emp-card.saved .scale-btn.selected { opacity:1; }
.desc-textarea { width:100%; padding:10px 14px; border:1px solid #e8e8e8; border-radius:8px; font-size:12px; font-family:'Cairo'; min-height:50px; resize:vertical; }
.emp-card.saved .desc-textarea { background:#f0f0f0; color:#6c757d; }
.grade-select { padding:8px 14px; border:1px solid #e8e8e8; border-radius:8px; font-size:12px; font-family:'Cairo'; }
.notes-row { margin-top:10px; padding-top:8px; border-top:1px solid #e8e8e8; }
.notes-row label { font-size:11px; color:#6c757d; display:block; margin-bottom:3px; }
.notes-row textarea { width:100%; padding:8px 12px; border:1px solid #e8e8e8; border-radius:6px; font-size:11px; font-family:'Cairo'; min-height:30px; resize:vertical; }
.emp-card.saved .notes-row textarea { background:#f0f0f0; color:#6c757d; }
.card-actions { padding:10px 20px; display:flex; gap:8px; justify-content:flex-end; border-top:1px solid #e8e8e8; }
.btn-save-one { background:#0c2823; color:#c8943f; border:none; padding:8px 18px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; font-family:'Cairo'; }
.btn-add-next { background:#fff; color:#0c2823; border:1px solid #e8e8e8; padding:8px 16px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; font-family:'Cairo'; }
.emp-card.saved .btn-save-one { display:none; }
.quick-add-inline { background:#f5f0e8; padding:16px 20px; border:2px dashed #c8943f; border-radius:8px; margin:12px 0; }
.quick-add-inline h4 { font-size:13px; color:#0c2823; margin-bottom:10px; }
.inline-grid { display:grid; grid-template-columns:2fr 1fr 1fr 1fr auto; gap:8px; align-items:end; }
.inline-grid input, .inline-grid select { padding:8px 12px; border:1px solid #e8e8e8; border-radius:6px; font-size:12px; font-family:'Cairo'; }
.btn-inline-add { background:#c8943f; color:#0c2823; border:none; padding:8px 14px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; }
.btn-inline-cancel { background:transparent; color:#6c757d; border:none; padding:8px 10px; font-size:12px; cursor:pointer; }

/* CD-028: UX/UI Polish — Responsive + Transitions */
.emp-card { transition: transform .2s ease, box-shadow .3s ease, background .3s ease; }
.emp-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(12,40,35,.12); }
.emp-card.saved { background: linear-gradient(135deg, #e8f5f0 0%, #d4ebe4 100%) !important; border-color: #0d5e52 !important; transition: background .4s ease; }
.emp-card.saved .card-header { background: linear-gradient(90deg, #d4ebe4, #c4dfd7) !important; }
.saved-badge { animation: cd028-pop .3s ease; }
@keyframes cd028-pop { 0% { transform: scale(0.8); opacity: 0; } 50% { transform: scale(1.1); } 100% { transform: scale(1); opacity: 1; } }
.cd024-input:focus, .cd024-select:focus { border-color: #c8943f !important; box-shadow: 0 0 0 .15rem rgba(200,148,63,.12) !important; outline: none; }
.bulk-setup { transition: box-shadow .3s; }
.bulk-setup:focus-within { box-shadow: 0 4px 16px rgba(200,148,63,.1); }
.global-actions { backdrop-filter: blur(8px); }
.btn-save-all { transition: transform .15s, box-shadow .2s; }
.btn-save-all:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(12,40,35,.4); }
.btn-save-all:active { transform: translateY(0); }
@media (max-width: 768px) {
  .bulk-setup { grid-template-columns: 1fr !important; gap: 12px; }
  .inline-grid { grid-template-columns: 1fr !important; }
  .emp-card .card-header { flex-direction: column; align-items: flex-start; }
  .global-actions { position: fixed; bottom: 0; left: 0; right: 0; padding: 10px 16px; }
  .global-actions .btn-save-all { width: 100%; padding: 12px; }
  .global-actions .summary { font-size: 11px; }
}
.global-actions { position:fixed; bottom:0; left:0; right:0; background:#fff; border-top:2px solid #c8943f; padding:14px 24px; display:flex; justify-content:center; align-items:center; gap:16px; box-shadow:0 -4px 12px rgba(0,0,0,0.08); z-index:100; }
.global-actions .summary { font-size:12px; color:#6c757d; }
.global-actions .summary strong { color:#0c2823; }
.btn-save-all { background:#0c2823; color:#c8943f; border:none; padding:14px 40px; border-radius:8px; font-size:15px; font-weight:700; cursor:pointer; font-family:'Cairo'; box-shadow:0 2px 8px rgba(12,40,35,0.3); }
.empty-state { text-align:center; padding:40px; color:#999; }
.empty-state .icon { font-size:40px; margin-bottom:10px; }
</style>

<div class="container-fluid">
    <h2><?= htmlspecialchars($pageTitle ?? '') ?></h2>
    <hr>

    <!-- Setup Bar -->
    <div class="bulk-setup">
        <div>
            <label>قالب التقييم</label>
            <select id="templateSelect">
                <option value="0">— اختر القالب —</option>
                <?php foreach ($templates ?? [] as $tpl): ?>
                    <option value="<?= (int)$tpl->id ?>" data-mode="<?= htmlspecialchars($tpl->evaluation_mode ?? 'numeric') ?>" data-target="<?= htmlspecialchars($tpl->target_type ?? 'employee') ?>">
                        <?= htmlspecialchars($tpl->template_name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="lock-badge">🔒 يُقفل بعد أول تقييم</div>
        </div>
        <!-- CD-029: حقل اختيار الدورة/الورشة — يظهر ديناميكياً -->
        <div id="contextSelectWrap" style="display:none;">
            <label id="contextLabel">الدورة/الورشة</label>
            <select id="contextSelect"><option value="0">— اختر —</option></select>
            <span id="contextBadge" style="display:none;font-size:10px;background:#c8943f;color:#0c2823;padding:2px 8px;border-radius:10px;font-weight:700;"></span>
        </div>
        <div><label>التاريخ الموحد</label><input type="date" id="evalDate" value="<?= date('Y-m-d') ?>"></div>
        <div><label>اسم المقيّم</label><input type="text" id="evaluatorName" value="<?= htmlspecialchars($evaluatorName ?? '') ?>"></div>
        <div><label>صفة المقيّم</label><input type="text" id="evaluatorTitle" value="<?= htmlspecialchars($evaluatorTitle ?? '') ?>"></div>
    </div>

    <!-- Search + Quick Add -->
    <div class="bulk-search" style="position:relative;">
        <input type="text" id="empSearch" placeholder="🔍 بحث وإضافة موظف (الاسم أو الرقم الوظيفي)..." autocomplete="off">
        <button class="btn-add" onclick="toggleQuickAdd()">＋ إضافة موظف جديد</button>
        <div class="search-results" id="searchResults"></div>
    </div>

    <!-- Quick Add Inline -->
    <div id="quickAddForm" style="display:none;">
        <div class="quick-add-inline">
            <h4>＋ إضافة موظف جديد</h4>
            <div class="inline-grid">
                <input type="text" id="qaName" placeholder="الاسم الكامل">
                <input type="text" id="qaCode" placeholder="الرقم الوظيفي">
                <select id="qaBranch"><option value="0">الفرع</option><?php foreach ($branches ?? [] as $b): ?><option value="<?= (int)$b->id ?>"><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?></select>
                <select id="qaDept"><option value="0">القسم</option></select>
                <div style="display:flex;gap:4px;">
                    <button class="btn-inline-add" onclick="submitQuickAdd()">✓ إضافة</button>
                    <button class="btn-inline-cancel" onclick="toggleQuickAdd()">✕</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards Container -->
    <div id="cardsContainer" style="margin-bottom:80px;">
        <div class="empty-state" id="emptyState">
            <div class="icon">📋</div>
            ابحث عن موظف أو أضف واحداً للبدء في التقييم الجماعي.
        </div>
    </div>
</div>

<!-- Global Actions -->
<div class="global-actions">
    <div class="summary" id="summary"><strong>0</strong> موظفين · <strong>0</strong> محفوظ · <strong>0</strong> بانتظار الحفظ</div>
    <button class="btn-save-all" onclick="saveAll()" id="btnSaveAll">💾 حفظ الجميع</button>
</div>

<script>
// CD-024.1: علامة تشخيصية — تحقق من وحدة تحكم المتصفح (Console)
console.log('[CD-024] dynamic-render v3401a68 active — renderSectionField ready');
var appUrl = '<?= defined('APP_URL') ? htmlspecialchars(APP_URL, ENT_QUOTES) : '' ?>';
var csrfToken = '<?= htmlspecialchars($csrfToken ?? '') ?>';
var templateSchema = null;
var templateLocked = false;
var employeeCards = [];

// Template selection → load schema + context field
document.getElementById('templateSelect').addEventListener('change', function() {
    if (templateLocked) { alert('القالب مُقفل. لا يمكن تغييره بعد إدخال تقييمات.'); this.value = templateSchema ? templateSchema.id : 0; return; }
    var id = parseInt(this.value);
    if (id === 0) { templateSchema = null; document.getElementById('contextSelectWrap').style.display='none'; return; }

    // CD-029: إظهار/إخفاء حقل الدورة حسب target_type
    var selectedOpt = this.options[this.selectedIndex];
    var target = selectedOpt.getAttribute('data-target') || 'employee';
    var ctxWrap = document.getElementById('contextSelectWrap');
    var ctxLabel = document.getElementById('contextLabel');
    var ctxSelect = document.getElementById('contextSelect');
    var ctxBadge = document.getElementById('contextBadge');

    if (target === 'employee_in_course') {
        ctxLabel.textContent = '📚 البرنامج (دورة)';
        ctxWrap.style.display = '';
        ctxSelect.innerHTML = '<option value="0">— اختر البرنامج (دورة) —</option>';
        fetch(appUrl + '/api/training-programs/list/course').then(r => r.json()).then(function(list) {
            (Array.isArray(list) ? list : []).forEach(function(p) {
                var opt = document.createElement('option');
                opt.value = p.id; opt.textContent = p.title || ('برنامج #' + p.id);
                ctxSelect.appendChild(opt);
            });
        }).catch(function() { ctxSelect.innerHTML = '<option value="0">— لا توجد برامج —</option>'; });
        ctxBadge.style.display = 'none';
    } else if (target === 'employee_in_workshop') {
        ctxLabel.textContent = '🏆 البرنامج (ورشة)';
        ctxWrap.style.display = '';
        ctxSelect.innerHTML = '<option value="0">— اختر البرنامج (ورشة) —</option>';
        fetch(appUrl + '/api/training-programs/list/workshop').then(r => r.json()).then(function(list) {
            (Array.isArray(list) ? list : []).forEach(function(p) {
                var opt = document.createElement('option');
                opt.value = p.id; opt.textContent = p.title || ('برنامج #' + p.id);
                ctxSelect.appendChild(opt);
            });
        }).catch(function() { ctxSelect.innerHTML = '<option value="0">— لا توجد برامج —</option>'; });
        ctxBadge.style.display = 'none';
    } else {
        ctxWrap.style.display = 'none';
    }

    fetch(appUrl + '/api/templates/' + id + '/schema').then(r => r.json()).then(data => {
        templateSchema = data;
        // Re-render existing cards with new schema
        employeeCards.forEach(function(emp) { renderCardBody(emp); });
    });
});

// CD-029: شارة تأكيد الدورة المختارة
document.getElementById('contextSelect').addEventListener('change', function() {
    var badge = document.getElementById('contextBadge');
    if (this.value > 0) {
        var name = this.options[this.selectedIndex].text;
        badge.textContent = '✓ ' + name;
        badge.style.display = '';
        // CD-032: سحب الموظفين pending من الدورة تلقائياً
        loadPendingEmployees(parseInt(this.value));
    } else {
        badge.style.display = 'none';
    }
});

// CD-032: سحب الموظفين المسجلين (pending) من الدورة المختارة
function loadPendingEmployees(courseId) {
    if (!courseId || courseId < 1) return;
    fetch(appUrl + '/api/courses/' + courseId + '/pending-employees').then(r => r.json()).then(function(emps) {
        var list = Array.isArray(emps) ? emps : [];
        if (list.length === 0) { alert('لا يوجد موظفون بانتظار التقييم في هذه الدورة.'); return; }
        // إضافة كل موظف كبطاقة
        list.forEach(function(e) {
            if (!employeeCards.find(function(c) { return c.id === e.id; })) {
                addEmployeeCard({ id: e.id, full_name: e.full_name, emp_code: e.emp_code, department_name: e.department_name || '', branch_name: e.branch_name || '' });
            }
        });
    }).catch(function() { alert('تعذر جلب الموظفين.'); });
}

// Search autocomplete
var searchTimer;
document.getElementById('empSearch').addEventListener('input', function() {
    clearTimeout(searchTimer);
    var q = this.value.trim();
    if (q.length < 2) { document.getElementById('searchResults').style.display = 'none'; return; }
    searchTimer = setTimeout(function() {
        fetch(appUrl + '/api/employees/search?q=' + encodeURIComponent(q)).then(r => r.json()).then(data => {
            var box = document.getElementById('searchResults');
            box.innerHTML = '';
            // API may return a bare array OR an object wrapper {employees:[...]}
            var list = Array.isArray(data) ? data : (data.employees || []);
            if (list.length > 0) {
                list.forEach(function(e) {
                    var div = document.createElement('div');
                    div.className = 'item';
                    div.innerHTML = '<div class="name">' + e.full_name + '</div><div class="meta">' + e.emp_code + ' · ' + (e.department_name || '') + '</div>';
                    div.onclick = function() { addEmployeeCard(e); document.getElementById('empSearch').value = ''; box.style.display = 'none'; };
                    box.appendChild(div);
                });
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        });
    }, 300);
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.bulk-search')) document.getElementById('searchResults').style.display = 'none';
});

function toggleQuickAdd() {
    var f = document.getElementById('quickAddForm');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}

function submitQuickAdd() {
    var name = document.getElementById('qaName').value.trim();
    var code = document.getElementById('qaCode').value.trim();
    var branch = document.getElementById('qaBranch').value;
    if (!name || !code) { alert('الاسم والرقم الوظيفي مطلوبان'); return; }

    var formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('full_name', name);
    formData.append('emp_code', code);
    formData.append('branch_id', branch);
    formData.append('department_id', document.getElementById('qaDept').value);

    fetch(appUrl + '/api/employees/quick-add', { method: 'POST', body: formData })
    .then(r => r.json()).then(data => {
        if (data.success) {
            addEmployeeCard(data.employee);
            document.getElementById('qaName').value = '';
            document.getElementById('qaCode').value = '';
            toggleQuickAdd();
        } else { alert(data.error || 'فشل الإضافة'); }
    });
}

// Branch → Department cascade for quick add
document.getElementById('qaBranch').addEventListener('change', function() {
    var deptSelect = document.getElementById('qaDept');
    deptSelect.innerHTML = '<option value="0">القسم</option>';
    if (this.value > 0) {
        fetch(appUrl + '/api/departments/by-branch/' + this.value).then(r => r.json()).then(data => {
            var depts = Array.isArray(data) ? data : (data.departments || []);
            if (Array.isArray(depts)) depts.forEach(function(d) {
                var opt = document.createElement('option');
                opt.value = d.id; opt.textContent = d.name;
                deptSelect.appendChild(opt);
            });
        });
    }
});

function addEmployeeCard(emp) {
    if (employeeCards.find(e => e.id === emp.id)) { alert('هذا الموظف مضاف بالفعل'); return; }
    employeeCards.push(emp);
    renderCard(emp);
    updateSummary();
    document.getElementById('emptyState').style.display = 'none';
}

function renderCard(emp) {
    var container = document.getElementById('cardsContainer');
    var card = document.createElement('div');
    card.className = 'emp-card';
    card.id = 'card-' + emp.id;
    card.innerHTML = '<div class="card-header"><div class="emp-info"><div class="avatar">' + (emp.full_name[0] || '?') + '</div><div><div class="emp-name">' + emp.full_name + '</div><div class="emp-meta">' + (emp.emp_code || '') + ' · ' + (emp.department_name || '') + ' · ' + (emp.branch_name || '') + '</div></div></div></div><div class="card-body" id="body-' + emp.id + '"></div><div class="card-actions"><button class="btn-add-next" onclick="addNewCardSlot()">＋ إضافة موظف آخر</button><button class="btn-save-one" onclick="saveSingle(' + emp.id + ')">💾 حفظ فردي</button></div>';
    container.appendChild(card);
    renderCardBody(emp);
}

function renderCardBody(emp) {
    var body = document.getElementById('body-' + emp.id);
    if (!body || !templateSchema) { body.innerHTML = '<div style="color:#999;font-size:11px;">اختر قالباً لعرض حقول التقييم</div>'; return; }

    var html = '';
    var isDescriptive = templateSchema.evaluation_mode === 'descriptive';

    templateSchema.criteria.forEach(function(c) {
        html += '<div class="criteria-title">' + c.name + (isDescriptive ? '' : ' (وزن ' + c.weight + '%)') + '</div>';
        c.sections.forEach(function(s) {
            html += renderSectionField(s);
        });
    });

    html += '<div class="notes-row"><label>ملاحظات المقيّم</label><textarea data-notes="' + emp.id + '" placeholder="ملاحظات..."></textarea></div>';
    body.innerHTML = html;
}

// CD-024: render a single section field based on its type
function renderSectionField(s) {
    var type = s.type || 'percentage';
    var fieldId = 'sec-' + s.id;
    var label = '<div class="criteria-row"><div class="lbl">' + s.name + '</div>';

    switch (type) {
        case 'percentage':
            return label + '<div class="scale-group"><input type="number" class="cd024-input" data-section="' + s.id + '" data-field="score" min="0" max="100" step="1" placeholder="0-100%" style="width:90px;padding:6px 8px;border:1px solid #e8e8e8;border-radius:6px;font-family:Cairo;"></div></div>';
        case 'numeric':
            var mn = s.min_value != null ? s.min_value : 0;
            var mx = s.max_value != null ? s.max_value : 999999;
            return label + '<div class="scale-group"><input type="number" class="cd024-input" data-section="' + s.id + '" data-field="score" min="' + mn + '" max="' + mx + '" step="any" placeholder="' + mn + '-' + mx + '" style="width:110px;padding:6px 8px;border:1px solid #e8e8e8;border-radius:6px;font-family:Cairo;"></div></div>';
        case 'grade':
            var opts = s.options || [];
            var sel = '<select class="cd024-select grade-select" data-section="' + s.id + '" data-field="score" style="padding:6px 10px;border:1px solid #e8e8e8;border-radius:6px;font-family:Cairo;font-size:12px;"><option value="">— اختر —</option>';
            opts.forEach(function(o) { sel += '<option value="' + o.value + '">' + (o.label || o.value) + '</option>'; });
            sel += '</select>';
            return label + '<div class="scale-group">' + sel + '</div></div>';
        case 'textarea':
        case 'text':
            var mxChars = (s.options && s.options.max_chars) ? s.options.max_chars : 1000;
            return '<div class="criteria-row" style="flex-direction:column;align-items:stretch;"><div class="lbl" style="margin-bottom:4px;">' + s.name + '</div><textarea class="cd024-textarea desc-textarea" data-section="' + s.id + '" data-field="text" rows="3" maxlength="' + mxChars + '" placeholder="أدخل الإجابة الوصفية..." style="width:100%;padding:8px 12px;border:1px solid #e8e8e8;border-radius:8px;font-family:Cairo;font-size:12px;resize:vertical;"></textarea></div>';
        case 'overall_grade':
            var grades = s.grades || [];
            var sel = '<select class="cd024-select cd024-overall-grade" data-section="' + s.id + '" data-field="grade_label" style="padding:6px 10px;border:1px solid #e8e8e8;border-radius:6px;font-family:Cairo;font-size:12px;width:100%;"><option value="">— اختر التقدير —</option>';
            grades.forEach(function(g) { sel += '<option value="' + g.label + '">' + g.label + ' (' + g.value + ')</option>'; });
            sel += '</select>';
            return '<div class="criteria-row" style="border-top:2px solid #c8943f;padding-top:8px;margin-top:8px;"><div class="lbl" style="color:#c8943f;font-weight:700;">🏆 ' + s.name + '</div><div class="scale-group" style="flex:1;">' + sel + '</div></div>';
        case 'boolean':
            return label + '<div class="scale-group"><label style="font-size:12px;margin-left:12px;cursor:pointer;"><input type="radio" class="cd024-bool" data-section="' + s.id + '" data-field="score" name="bool-' + s.id + '" value="1"> نعم</label><label style="font-size:12px;margin-left:12px;cursor:pointer;"><input type="radio" class="cd024-bool" data-section="' + s.id + '" data-field="score" name="bool-' + s.id + '" value="0"> لا</label></div></div>';
        case 'helper':
            return label + '<div class="scale-group"><input type="number" class="cd024-input" data-section="' + s.id + '" data-field="score" min="' + (s.min_value||0) + '" max="' + (s.max_value||999999) + '" step="any" placeholder="قيمة مساعدة" style="width:110px;padding:6px 8px;border:1px solid #e8e8e8;border-radius:6px;font-family:Cairo;"></div><small style="color:#999;font-size:10px;">حقل مساعد</small></div>';
        case 'formula':
        case 'auto':
            return label + '<div class="scale-group"><input type="text" class="cd024-input" data-section="' + s.id + '" data-field="score" readonly placeholder="تلقائي" style="width:110px;padding:6px 8px;border:1px solid #e8e8e8;border-radius:6px;font-family:Cairo;background:#f5f5f5;"></div></div>';
        default:
            return label + '<div class="scale-group"><input type="text" class="cd024-input" data-section="' + s.id + '" data-field="score" placeholder="أدخل القيمة" style="width:110px;padding:6px 8px;border:1px solid #e8e8e8;border-radius:6px;font-family:Cairo;"></div></div>';
    }
}

function selectScale(btn) {
    var siblings = btn.parentElement.querySelectorAll('.scale-btn');
    siblings.forEach(function(s) { s.classList.remove('selected'); });
    btn.classList.add('selected');
    templateLocked = true;
}

function saveSingle(empId) {
    if (!templateSchema) { alert('اختر قالباً أولاً'); return; }
    var results = collectResults(empId);
    if (!results) return;

    var formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('employee_id', empId);
    formData.append('template_id', templateSchema.id);
    // CD-029: أضف context_id إن وُجد (دورة/ورشة)
    var ctxSelect = document.getElementById('contextSelect');
    if (ctxSelect && ctxSelect.value > 0) {
        formData.append('context_id', ctxSelect.value);
        formData.append('context_type', ctxSelect.closest('#contextSelectWrap') ? 'course' : 'course');
    }
    formData.append('evaluation_date', document.getElementById('evalDate').value);
    formData.append('evaluator_name', document.getElementById('evaluatorName').value);
    formData.append('evaluator_title', document.getElementById('evaluatorTitle').value);
    for (var key in results) {
        var entry = results[key];
        if (entry && typeof entry === 'object') {
            // numeric/percentage/grade/boolean: { score: N }
            if (entry.score !== undefined) formData.append('results[' + key + '][score]', entry.score);
            // textarea/text: { text: '...' }
            if (entry.text !== undefined) formData.append('results[' + key + '][text]', entry.text);
            // overall_grade section: { grade_label: 'ممتاز' }
            if (entry.grade_label !== undefined) formData.append('results[' + key + '][grade_label]', entry.grade_label);
        } else if (key === 'grade_label') {
            // legacy: top-level grade_label scalar
            formData.append('final_grade_label', entry);
        }
    }

    fetch(appUrl + '/evaluations/save-single', { method: 'POST', body: formData })
    .then(r => r.json()).then(data => {
        if (data.success) {
            var card = document.getElementById('card-' + empId);
            card.classList.add('saved');
            card.querySelectorAll('.scale-btn,.desc-textarea,.grade-select,textarea').forEach(function(el) { el.disabled = true; el.readOnly = true; });
            card.querySelector('.btn-save-one').style.display = 'none';
            var header = card.querySelector('.card-header');
            if (!header.querySelector('.saved-badge')) {
                var badge = document.createElement('div'); badge.className = 'saved-badge'; badge.innerHTML = '✓ تم الحفظ';
                header.appendChild(badge);
            }
            updateSummary();
        } else { alert(data.error || 'فشل الحفظ'); }
    });
}

function collectResults(empId) {
    var card = document.getElementById('card-' + empId);
    var results = {};
    var hasData = false;

    // CD-024: collect from all type-aware fields via data-section + data-field
    // numeric/percentage/grade/boolean → { score: value }
    // textarea/text → { text: value }
    // overall_grade → { grade_label: value }
    card.querySelectorAll('[data-section][data-field]').forEach(function(el) {
        var sid = el.dataset.section;
        var field = el.dataset.field;
        var val = '';
        if (el.type === 'radio') {
            if (!el.checked) return;
            val = el.value;
        } else if (el.tagName === 'SELECT') {
            val = el.value;
        } else {
            val = el.value;
        }
        if (val === '' || val === null) return;

        if (!results[sid]) results[sid] = {};
        if (field === 'score') {
            results[sid].score = parseFloat(val);
        } else if (field === 'text') {
            results[sid].text = val;
        } else if (field === 'grade_label') {
            results[sid].grade_label = val;
        }
        hasData = true;
    });

    var notes = card.querySelector('[data-notes="' + empId + '"]');
    if (notes && notes.value.trim()) results['notes'] = notes.value;
    if (!hasData) { alert('لم يتم إدخال أي تقييم'); return null; }
    return results;
}

function saveAll() {
    var unsaved = employeeCards.filter(function(e) {
        var card = document.getElementById('card-' + e.id);
        return card && !card.classList.contains('saved');
    });
    if (unsaved.length === 0) { alert('لا توجد بطاقات غير محفوظة'); return; }
    if (!templateSchema) { alert('اختر قالباً أولاً'); return; }

    var evaluations = [];
    unsaved.forEach(function(emp) {
        var results = collectResults(emp.id);
        if (results) evaluations.push({ employee_id: emp.id, results: results });
    });

    if (evaluations.length === 0) { alert('لا توجد بيانات صالحة للحفظ'); return; }

    // CD-029: أضف context_id للحمولة إن وُجد
    var ctxSel = document.getElementById('contextSelect');
    var ctxId = (ctxSel && ctxSel.value > 0) ? parseInt(ctxSel.value) : null;
    var ctxType = ctxId ? 'course' : null; // TODO: detect workshop

    var payload = JSON.stringify({
        _token: csrfToken,
        template_id: templateSchema.id,
        evaluation_date: document.getElementById('evalDate').value,
        evaluator_name: document.getElementById('evaluatorName').value,
        evaluator_title: document.getElementById('evaluatorTitle').value,
        context_id: ctxId,
        context_type: ctxType,
        evaluations: evaluations
    });

    fetch(appUrl + '/evaluations/bulk-save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
            'Accept': 'application/json'
        },
        body: payload
    }).then(r => r.json()).then(data => {
        if (data.success) {
            alert(data.message);
            unsaved.forEach(function(emp) {
                var card = document.getElementById('card-' + emp.id);
                card.classList.add('saved');
                card.querySelectorAll('.scale-btn,.desc-textarea,.grade-select,textarea').forEach(function(el) { el.disabled = true; el.readOnly = true; });
                var btn = card.querySelector('.btn-save-one'); if (btn) btn.style.display = 'none';
                var header = card.querySelector('.card-header');
                if (!header.querySelector('.saved-badge')) {
                    var badge = document.createElement('div'); badge.className = 'saved-badge'; badge.innerHTML = '✓ تم الحفظ';
                    header.appendChild(badge);
                }
            });
            updateSummary();
        } else { alert(data.error || 'فشل الحفظ الجماعي'); }
    });
}

function addNewCardSlot() {
    document.getElementById('empSearch').focus();
}

function updateSummary() {
    var total = employeeCards.length;
    var saved = employeeCards.filter(function(e) {
        var card = document.getElementById('card-' + e.id);
        return card && card.classList.contains('saved');
    }).length;
    document.getElementById('summary').innerHTML = '<strong>' + total + '</strong> موظفين · <strong>' + saved + '</strong> محفوظ · <strong>' + (total - saved) + '</strong> بانتظار الحفظ';
}
</script>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
