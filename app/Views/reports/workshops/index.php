<?php 
// ==================================================
//  صفحة تقارير الورش التدريبية (النسخة النهائية والمحسّنة)
// ==================================================
require_once APP_ROOT . '/app/Views/layouts/header.php';  
?>

<div class="container-fluid mt-4">
    <!-- 1. ترويسة الصفحة الرئيسية -->
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary-emphasis">
            <i class="fas fa-chart-pie me-2"></i> تقارير الورش التدريبية
        </h2>
        <a href="<?php echo APP_URL; ?>/reports/workshop-dashboard/print?level=all" target="_blank" class="btn btn-primary shadow-sm">
            <i class="fas fa-print me-2"></i> طباعة التقرير الشامل
        </a>
    </div>

    <!-- 2. بطاقات الإحصائيات العامة (بتصميم محسن) -->
 <div class="row g-3  ">
        <div class="col-md-6 col-lg-3">
            <div class="card text-center border-primary border-2 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title text-primary">إجمالي الورش</h5>
                    <p class="display-5 fw-bold text-primary-emphasis"><?php echo $data['total_workshops']; ?></p>
                </div>
            </div>
        </div>
      
        <div class="col-md-6 col-lg-3">
            <div class="card text-center border-dark border-2 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title text-dark">متوسط التقييم العام</h5>
                    <p class="display-5 fw-bold text-dark-emphasis"><?php echo round($data['average_score'], 1); ?><small class="fs-5">%</small></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. قسم الفروع (الأكورديون) -->
    <div class="mt-5">
        <h3 class="mb-3"><i class="fas fa-code-branch me-2"></i> تفاصيل الورش حسب الفروع</h3>
        
        <?php if (!empty($data['branches'])): ?>
            <!-- 
                ===> النقطة الأهم (1): استخدام ID فريد للأكورديون <===
                هذا الـ ID هو الذي يستمع إليه كود الجافاسكريبت الصحيح في main.js
            -->
            <div class="accordion shadow-sm" id="workshopsBranchesAccordion">
                <?php foreach ($data['branches'] as $branch): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <!-- 
                                ===> النقطة الأهم (2): إضافة data-branch-id <===
                                هذا يسمح للجافاسكريبت بمعرفة أي فرع تم الضغط عليه
                            -->
                            <button class="accordion-button collapsed fw-bold" type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#collapse-<?php echo $branch->id; ?>"
                                    data-branch-id="<?php echo $branch->id; ?>">
                                
                                <div class="d-flex justify-content-between w-100 align-items-center pe-3">
                                    <span>
                                        <i class="fas fa-building text-muted me-2"></i>
                                        <?php echo htmlspecialchars($branch->name); ?>
                                    </span>
                                    <span class="badge bg-secondary rounded-pill">
                                        <?php echo $branch->workshop_count; ?> ورشة
                                    </span>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse-<?php echo $branch->id; ?>" class="accordion-collapse collapse" data-bs-parent="#workshopsBranchesAccordion">
                            <div class="accordion-body bg-light">
                                <!-- 
                                    ===> النقطة الأهم (3): لا يوجد كود جافاسكريبت هنا <===
                                    هذا المكان سيتم ملؤه تلقائيًا بواسطة الكود الموجود في main.js
                                -->
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle fa-2x mb-3"></i>
                <p class="mb-0">لا توجد فروع لعرض تقاريرها حالياً.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 
    4. سكربت تحميل الورش داخل الأقسام
    هذا الكود ضروري ويعمل بشكل صحيح، لذلك يجب الإبقاء عليه.
    يتم استدعاؤه من الـ HTML الذي يتم بناؤه ديناميكيًا في main.js
-->
<script>
function loadWorkshops(deptId) {
    const container = document.getElementById("workshops-dept-" + deptId);
    // التأكد من أن الحاوية موجودة وأن البيانات لم يتم تحميلها من قبل
    if (container && !container.dataset.loaded) {
        container.innerHTML = `<div class="text-center p-3"><div class="spinner-border spinner-border-sm"></div></div>`;
        container.dataset.loaded = "true"; // ضع علامة التحميل فورًا لمنع الطلبات المتكررة

        fetch("<?php echo APP_URL; ?>/api/reports/workshops-by-department/" + deptId)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading workshops:', error);
                container.innerHTML = '<div class="alert alert-warning p-2">فشل تحميل الورش.</div>';
            });
    }
}
</script>

<?php 
// تضمين الفوتر
require_once APP_ROOT . '/app/Views/layouts/footer.php';  
?>
