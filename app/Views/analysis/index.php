<?php 
// ملف: app/Views/analysis/index.php (النسخة النهائية والمصححة)
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="container mt-4">
    <div class="page-header">
        <h2><i class="fas fa-magic text-primary"></i> منصة التحليل التفاعلي للأداء</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">بناء تقرير تحليلي</h5>
        </div>
        <div class="card-body p-4">
            <!-- لاحظ أننا سنعتمد على الجافاسكريبت لتحديد الرابط، لذا يمكن ترك action فارغاً أو كما هو -->
            <form action="<?php echo APP_URL; ?>/analysis/report" method="GET" id="analysis-form">
                
                <!-- 1. مستوى التحليل -->
                <div class="mb-3">
                    <label for="level" class="form-label fw-bold">1. اختر مستوى التحليل:</label>
                    <select id="level" name="level" class="form-select form-select-lg" required>
                        <option value="" disabled selected>-- حدد المستوى --</option>
                        <option value="employee">تحليل موظف</option>
                        <option value="department">تحليل قسم</option>
                        <option value="branch">تحليل فرع</option>
                        <option value="company">تحليل الشركة ككل</option>
                    </select>
                </div>

                <!-- 2. الكيان المستهدف (يظهر بالجافاسكريبت) -->
                <div id="target-selection" class="mb-3" style="display: none;">
                    <label for="target_id" class="form-label fw-bold">2. حدد الهدف:</label>
                    <select id="target_id" name="target_id" class="form-select form-select-lg"></select>
                </div>

                <!-- 3. نطاق التحليل -->
                <div class="mb-3">
                    <label for="scope" class="form-label fw-bold">3. اختر نطاق التحليل:</label>
                    <select id="scope" name="scope" class="form-select form-select-lg" required>
                        <option value="overall">التحليل الشامل (جميع أنواع التقييمات)</option>
                        <option value="by_template">تحليل حسب نوع التقييم</option>
                    </select>
                </div>

                <!-- 4. نوع القالب (يظهر بالجافاسكريبت) -->
                <div id="template-selection" class="mb-3" style="display: none;">
                    <label for="template_id" class="form-label fw-bold">4. حدد نوع التقييم:</label>
                    <select id="template_id" name="template_id" class="form-select form-select-lg">
                        <option value="" disabled selected>-- اختر نوع القالب --</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template->id; ?>"><?php echo htmlspecialchars($template->template_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-cogs"></i> إنشاء التقرير التحليلي
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- تعريف العناصر ---
    const levelSelect = document.getElementById('level');
    const targetSelectionDiv = document.getElementById('target-selection');
    const targetIdSelect = document.getElementById('target_id');
    const scopeSelect = document.getElementById('scope');
    const templateSelectionDiv = document.getElementById('template-selection');
    const templateIdSelect = document.getElementById('template_id');
    const analysisForm = document.getElementById('analysis-form'); // <-- تعريف النموذج

    // --- تخزين البيانات القادمة من PHP في متغيرات جافاسكريبت ---
    const dataSources = {
        employee: <?php echo json_encode($employees); ?>,
        department: <?php echo json_encode($departments); ?>,
        branch: <?php echo json_encode($branches); ?>
    };

    // --- الأحداث (Events) ---

    // 1. عند تغيير مستوى التحليل (موظف، قسم، فرع)
    levelSelect.addEventListener('change', function () {
        const selectedLevel = this.value;
        
        targetIdSelect.innerHTML = '';
        targetSelectionDiv.style.display = 'none';
        targetIdSelect.required = false;

        if (selectedLevel === 'employee' || selectedLevel === 'department' || selectedLevel === 'branch') {
            const items = dataSources[selectedLevel];
            targetIdSelect.innerHTML = `<option value="" disabled selected>-- اختر ${getArabicName(selectedLevel)} --</option>`;
            items.forEach(item => {
                const itemName = item.full_name || item.name;
                const option = new Option(itemName, item.id);
                targetIdSelect.add(option);
            });
            targetSelectionDiv.style.display = 'block';
            targetIdSelect.required = true;
        }
    });

    // 2. عند تغيير نطاق التحليل (شامل، حسب النوع)
    scopeSelect.addEventListener('change', function () {
        if (this.value === 'by_template') {
            templateSelectionDiv.style.display = 'block';
            templateIdSelect.required = true;
        } else {
            templateSelectionDiv.style.display = 'none';
            templateIdSelect.required = false;
        }
    });

    // =================================================================
    //          ===>  هذا هو الكود الحاسم لحل المشكلة  <===
    // =================================================================
    analysisForm.addEventListener('submit', function(event) {
        const selectedLevel = levelSelect.value;
        const selectedTargetId = targetIdSelect.value;

        // التحقق إذا كان المستوى هو "تحليل موظف"
        if (selectedLevel === 'employee') {
            // 1. منع النموذج من الإرسال إلى الرابط القديم
            event.preventDefault();

            // التأكد من أن المستخدم اختار موظفاً
            if (!selectedTargetId) {
                alert('الرجاء اختيار موظف أولاً.');
                return;
            }

            // 2. بناء الرابط الصحيح للتحليل الفردي
            const baseUrl = `<?php echo APP_URL; ?>/analysis/employee/${selectedTargetId}`;
            
            // 3. إضافة بارامترات القالب إذا تم اختياره
            const urlParams = new URLSearchParams();
            if (scopeSelect.value === 'by_template' && templateIdSelect.value) {
                urlParams.append('scope', 'by_template');
                urlParams.append('template_id', templateIdSelect.value);
            }
            
            const finalUrl = urlParams.toString() ? `${baseUrl}?${urlParams.toString()}` : baseUrl;

            // 4. إعادة توجيه المستخدم إلى الرابط الصحيح
            window.location.href = finalUrl;
        }
        // إذا كان المستوى (قسم، فرع، شركة)، سيتم إرسال النموذج بشكل طبيعي إلى الرابط الموجود في action
        // وهو /analysis/report، وهذا هو السلوك المطلوب للتحليل الجماعي.
    });


    // --- دوال مساعدة ---
    function getArabicName(level) {
        if (level === 'employee') return 'الموظف';
        if (level === 'department') return 'القسم';
        if (level === 'branch') return 'الفرع';
        return '';
    }
});
</script>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
