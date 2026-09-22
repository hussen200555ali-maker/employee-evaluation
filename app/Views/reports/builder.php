<?php 
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="container mt-4">
    <div class="page-header">
        <h2><i class="fas fa-chart-pie text-primary"></i> <?php echo $data['pageTitle']; ?></h2>
    </div>

    <form action="<?php echo APP_URL; ?>/reports/generate" method="GET" id="report-builder-form">
        
        <!-- القسم الأول: الإعدادات الأساسية للتقرير -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">1. الإعدادات الأساسية للتقرير</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="report_title" class="form-label fw-bold">عنوان التقرير</label>
                        <input type="text" class="form-control" id="report_title" name="report_title" value="تقرير الأداء الشامل" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="start_date" class="form-label fw-bold">من تاريخ</label>
                        <input type="date" class="form-control" id="start_date" name="start_date">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="end_date" class="form-label fw-bold">إلى تاريخ</label>
                        <input type="date" class="form-control" id="end_date" name="end_date">
                    </div>
                </div>
            </div>
        </div>

        <!-- القسم الثاني: الأعمدة الديناميكية -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">2. تحديد أعمدة التقرير وأوزانها</h5>
            </div>
            <div class="card-body">
                <!-- أعمدة تقييم الموظفين -->
                <h6><i class="fas fa-users"></i> تقييمات الموظفين (5 أعمدة كحد أقصى)</h6>
                <div id="employee-columns-container">
                    <!-- الصفوف ستضاف هنا بواسطة JavaScript -->
                </div>
                <button type="button" id="add-employee-column" class="btn btn-outline-primary btn-sm mt-2"><i class="fas fa-plus"></i> إضافة عمود تقييم موظفين</button>
                
                <hr class="my-4">

                <!-- أعمدة الدورات والورش -->
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-graduation-cap"></i> الدورات التدريبية</h6>
                        <div class="input-group">
                            <input type="number" class="form-control weight-input" name="courses_weight" placeholder="الوزن النسبي" min="0" max="100" step="1">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-tools"></i> ورش العمل</h6>
                        <div class="input-group">
                            <input type="number" class="form-control weight-input" name="workshops_weight" placeholder="الوزن النسبي" min="0" max="100" step="1">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- القسم الثالث: زر الإنشاء ومجموع الأوزان -->
        <div class="card shadow-sm">
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div>
                    <strong>مجموع الأوزان: </strong>
                    <span id="total-weight" class="badge bg-secondary fs-6">0%</span>
                </div>
                <button type="submit" id="generate-report-btn" class="btn btn-success btn-lg" disabled>
                    <i class="fas fa-cogs"></i> إنشاء التقرير
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const employeeColumnsContainer = document.getElementById('employee-columns-container');
    const addEmployeeColumnBtn = document.getElementById('add-employee-column');
    const totalWeightSpan = document.getElementById('total-weight');
    const generateReportBtn = document.getElementById('generate-report-btn');
    const form = document.getElementById('report-builder-form');
    let employeeColumnCount = 0;

    // بيانات من PHP
    const templates = <?php echo json_encode($data['templates']); ?>;
    const criteria = <?php echo json_encode($data['criteria']); ?>;

    function createEmployeeColumn() {
        if (employeeColumnCount >= 5) {
            showToast('warning', 'يمكنك إضافة 5 أعمدة لتقييم الموظفين كحد أقصى.');
            return;
        }
        employeeColumnCount++;

        const columnDiv = document.createElement('div');
        columnDiv.className = 'row g-3 align-items-center mb-3 dynamic-column';
        columnDiv.innerHTML = `
            <div class="col-md-8">
                <select class="form-select" name="employee_columns[${employeeColumnCount - 1}][source]" required>
                    <option value="" selected disabled>-- اختر مصدر بيانات العمود ${employeeColumnCount} --</option>
                    <optgroup label="استمارات تقييم كاملة">
                        ${templates.map(t => `<option value="template-${t.id}">${t.template_name}</option>`).join('')}
                    </optgroup>
                    <optgroup label="معايير محددة">
                        ${criteria.map(c => `<option value="criterion-${c.id}">${c.criterion_name} (من: ${c.template_name})</option>`).join('')}
                    </optgroup>
                </select>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <input type="number" class="form-control weight-input" name="employee_columns[${employeeColumnCount - 1}][weight]" placeholder="الوزن" min="0" max="100" step="1" required>
                    <span class="input-group-text">%</span>
                </div>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm remove-column-btn"><i class="fas fa-trash"></i></button>
            </div>
        `;
        employeeColumnsContainer.appendChild(columnDiv);
    }

    function updateTotalWeight() {
        let total = 0;
        document.querySelectorAll('.weight-input').forEach(input => {
            if (input.value) {
                total += parseFloat(input.value);
            }
        });

        totalWeightSpan.textContent = total + '%';
        totalWeightSpan.classList.remove('bg-success', 'bg-danger', 'bg-secondary');

        if (total === 100) {
            totalWeightSpan.classList.add('bg-success');
            generateReportBtn.disabled = false;
        } else if (total > 100) {
            totalWeightSpan.classList.add('bg-danger');
            generateReportBtn.disabled = true;
        } else {
            totalWeightSpan.classList.add('bg-secondary');
            generateReportBtn.disabled = true;
        }
    }

    addEmployeeColumnBtn.addEventListener('click', createEmployeeColumn);

    form.addEventListener('input', function(e) {
        if (e.target.classList.contains('weight-input')) {
            updateTotalWeight();
        }
    });

    form.addEventListener('click', function(e) {
        if (e.target.closest('.remove-column-btn')) {
            e.target.closest('.dynamic-column').remove();
            employeeColumnCount--;
            updateTotalWeight();
        }
    });
    
    // لإظهار رسائل التنبيه
    function showToast(type, message) {
        // يمكنك استخدام مكتبة مثل SweetAlert2 أو Toastr هنا
        alert(message);
    }

    // إضافة عمود واحد بشكل افتراضي عند تحميل الصفحة
    createEmployeeColumn();
    updateTotalWeight();
});
</script>

<?php 
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
