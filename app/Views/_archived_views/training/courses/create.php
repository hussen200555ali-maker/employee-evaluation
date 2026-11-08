<?php 
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>
<!-- CD-033.1: إنشاء دورة — مطابق لـ Screen 1 من CD-031 -->
<div class="container" style="max-width: 800px;">
    <div class="page-header">
        <h2><i class="fas fa-plus"></i> 📚 إضافة دورة تدريبية جديدة</h2>
        <a href="<?php echo APP_URL; ?>/training/courses" class="btn-back">
            <i class="fas fa-arrow-right"></i> العودة للقائمة
        </a>
    </div>

    <div class="form-container">
        <form action="<?php echo APP_URL; ?>/training/courses" method="POST">
                <?= csrf_field() ?>
            
            <div class="form-group mb-3">
                <label for="catalog_id">📂 التصنيف (Catalog)</label>
                <select name="catalog_id" id="catalog_id" class="form-select" required>
                    <option value="">-- اختر التصنيف --</option>
                    <?php foreach ($data['catalog_courses'] as $catalog_course): ?>
                        <option value="<?php echo $catalog_course->id; ?>"><?php echo htmlspecialchars($catalog_course->title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="branch_id">🏢 الفرع المنفذ</label>
                    <select id="branch_id" name="branch_id" class="form-select" required>
                        <option value="">-- اختر الفرع --</option>
                        <?php foreach ($data['branches'] as $branch): ?>
                            <option value="<?php echo $branch->id; ?>"><?php echo htmlspecialchars($branch->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department_id">📋 القسم (اختياري — "عام" لمستوى الفرع)</label>
                    <select id="department_id" name="department_id" class="form-select">
                        <option value="">عام (مستوى الفرع)</option>
                    </select>
                </div>
            </div>

            <div class="form-row mt-3">
                <div class="form-group">
                    <label for="start_date">📅 تاريخ البدء</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="end_date">📅 تاريخ الانتهاء</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" required>
                </div>
            </div>

            <div class="form-group mt-3">
                <label for="status">📊 حالة الدورة</label>
                <select name="status" id="status" class="form-select" required>
                    <option value="scheduled" selected>مجدولة</option>
                    <option value="ongoing">جارية</option>
                    <option value="completed">مكتملة</option>
                    <option value="canceled">ملغاة</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit-form">
                    <i class="fas fa-save"></i> 💾 إنشاء الدورة
                </button>
            </div>
        </form>
    </div>
</div>

<!-- CD-033.1: AJAX cascade — الفرع → القسم -->
<script>
document.getElementById('branch_id').addEventListener('change', function() {
    var branchId = this.value;
    var deptSelect = document.getElementById('department_id');
    deptSelect.innerHTML = '<option value="">عام (مستوى الفرع)</option>';
    if (!branchId) return;
    fetch('<?= APP_URL ?>/api/departments/by-branch/' + branchId)
        .then(r => r.json())
        .then(function(data) {
            var list = Array.isArray(data) ? data : (data.departments || []);
            list.forEach(function(d) {
                var opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.name;
                deptSelect.appendChild(opt);
            });
        })
        .catch(function() {});
});
</script>
<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
