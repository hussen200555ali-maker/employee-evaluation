document.addEventListener('DOMContentLoaded', function() {

    console.log('Report Builder script loaded successfully!');

    // ===================================================================
    //  1. تعريف عناصر واجهة المستخدم
    // ===================================================================
    const reportContainer = document.getElementById('report-container');
    const reportTitleDisplay = document.querySelector('#report-title-display span');
    const reportPeriodDisplay = document.getElementById('report-period-display');
    
    const controls = {
        title: document.getElementById('report_title'),
        startDate: document.getElementById('start_date'),
        endDate: document.getElementById('end_date'),
        coursesWeight: document.getElementById('courses_weight'),
        workshopsWeight: document.getElementById('workshops_weight'),
        addColumnBtn: document.getElementById('add-column-btn'),
        saveReportBtn: document.getElementById('save-report-btn'),
        totalWeightBadge: document.getElementById('total-weight-badge'),
        printReportBtn: document.getElementById('print-report-btn'),
    };

    const columnEditorModalEl = document.getElementById('column-editor-modal');
    const columnEditorModal = new bootstrap.Modal(columnEditorModalEl);
    const columnForm = {
        form: document.getElementById('column-editor-form'),
        editIndex: document.getElementById('column-edit-index'),
        source: document.getElementById('column-source'),
        weight: document.getElementById('column-weight'),
        saveBtn: document.getElementById('save-column-btn'),
        title: document.getElementById('columnEditorModalLabel')
    };

    // ===================================================================
    //  2. حالة التقرير (State Management)
    // ===================================================================
    let reportState = {
        report_title: "تقرير الأداء الشامل (اختبار)",
        start_date: "2025-09-01",
        end_date: "2025-11-30",
        employee_columns: [
            { source: "template-1", weight: 50 },
            { source: "criterion-1", weight: 30 }
        ],
        courses_weight: 10,
        workshops_weight: 10
    };

    // ===================================================================
    //  3. الدوال الرئيسية
    // ===================================================================
    
    async function fetchReportData(config) {
        console.log("Sending config to API:", config);
        reportContainer.innerHTML = `<div class="text-center p-5"><div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3 fs-5">جاري توليد التقرير...</p></div>`;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            if (!csrfToken) {
                reportContainer.innerHTML = `<div class="alert alert-warning"><b>انتهت جلسة الدخول.</b> يرجى تسجيل الدخول مجددًا لعرض التقرير وحفظه. <a href="${BASE_URL}/login" class="alert-link">تسجيل الدخول</a></div>`;
                return;
            }
            const response = await fetch(`${BASE_URL}/reports/generate`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken},
                body: JSON.stringify(config)
            });
            const responseText = await response.text();
            if (!response.ok) {
                try {
                    const errorResult = JSON.parse(responseText);
                    const errorMessage = errorResult.message || errorResult.error || errorResult.errors || 'خطأ غير معروف';
                    reportContainer.innerHTML = `<div class="alert alert-danger"><b>خطأ في الخادم:</b> ${errorMessage}</div>`;
                } catch (e) {
                    reportContainer.innerHTML = `<div class="alert alert-danger"><b>استجابة غير متوقعة من الخادم:</b></div>${responseText}`;
                }
                return;
            }
            const result = JSON.parse(responseText);
            if (result.success) {
                renderReport(result.data);
            } else {
                reportContainer.innerHTML = `<div class="alert alert-warning">${result.message}</div>`;
            }
        } catch (error) {
            reportContainer.innerHTML = `<div class="alert alert-danger">حدث خطأ فني أثناء الاتصال بالخادم.</div>`;
        }
    }

    function renderReport(data) {
        const { reportData, config, templates, criteria } = data;
        const formatScore = value => Number.isFinite(Number(value)) ? Number(value).toFixed(2) : '0.00';
        reportTitleDisplay.textContent = config.report_title;
        reportPeriodDisplay.textContent = (config.start_date && config.end_date) ? `للفترة من ${config.start_date} إلى ${config.end_date}` : '';
        
        const employeeColumnHeaders = config.employee_columns.map((col, index) => {
            const [type, id] = col.source.split('-');
            let sourceName = (type === 'template') ? `استمارة: ${templates[id] || 'غير معروف'}` : `معيار: ${criteria[id] || 'غير معروف'}`;
            return `<th><div class="d-flex justify-content-center align-items-center"><span>${sourceName} (${col.weight}%)</span><div class="ms-2"><button class="btn btn-sm btn-outline-light py-0 px-1 edit-column-btn" data-index="${index}" title="تعديل العمود"><i class="fas fa-pencil-alt fa-xs"></i></button><button class="btn btn-sm btn-outline-danger py-0 px-1 delete-column-btn" data-index="${index}" title="حذف العمود"><i class="fas fa-trash-alt fa-xs"></i></button></div></div></th>`;
        }).join('');

        let tableRows = '';
        if (!reportData.branches || reportData.branches.length === 0) {
            tableRows = `<tr><td colspan="${7 + config.employee_columns.length}" class="text-center p-5"><h4>لا توجد بيانات لعرضها.</h4></td></tr>`;
        } else {
            reportData.branches.forEach(branch => {
                let firstRow = true;
                branch.departments.forEach((department, deptIndex) => {
                    const dynamicScores = department.scores.map(score => `<td>${parseFloat(score).toFixed(2)}%</td>`).join('');
                    
                    // =================================================================
                    //  ▼▼▼ هذا هو السطر الحاسم الذي تم إصلاحه بالكامل ▼▼▼
                    // =================================================================
                    tableRows += `
                        <tr>
                            ${firstRow ? `<td rowspan="${branch.departments.length}">${branch.branch_name}</td>` : ''}
                            <td>${department.department_name}</td>
                            ${dynamicScores}
                            <td>${formatScore(department.courses_score)}%</td>
                            <td>${formatScore(department.workshops_score)}%</td>
                            <td>${formatScore(department.department_total)}%</td>
                            ${firstRow ? `<td rowspan="${branch.departments.length}" class="fw-bold fs-5">${formatScore(branch.branch_total)}%</td>` : ''}
                        </tr>
                    `;
                    // =================================================================
                    //  ▲▲▲ نهاية منطقة الإصلاح ▲▲▲
                    // =================================================================
                    firstRow = false;
                });
            });
        }

        const tableHTML = `<div class="table-responsive"><table class="table table-bordered table-hover text-center align-middle"><thead class="table-dark"><tr><th rowspan="2">الفروع</th><th rowspan="2">الأقسام</th><th colspan="${config.employee_columns.length}">تقييمات الموظفين</th><th rowspan="2">الدورات (${config.courses_weight}%)</th><th rowspan="2">ورش العمل (${config.workshops_weight}%)</th><th rowspan="2">إجمالي تقييم القسم</th><th rowspan="2">إجمالي تقييم الفرع</th></tr><tr>${employeeColumnHeaders}</tr></thead><tbody>${tableRows}</tbody><tfoot class="table-group-divider"><tr class="table-primary"><th colspan="${5 + config.employee_columns.length}" class="text-center fs-5">المتوسط العام للشركة</th><th colspan="2" class="text-center fs-4">${parseFloat(reportData.company_total).toFixed(2)}%</th></tr></tfoot></table></div>`;
        reportContainer.innerHTML = tableHTML;
    }

    function updateWeightDisplay() {
        let totalWeight = (parseFloat(reportState.courses_weight) || 0) + (parseFloat(reportState.workshops_weight) || 0);
        reportState.employee_columns.forEach(col => {
            totalWeight += parseFloat(col.weight) || 0;
        });
        controls.totalWeightBadge.textContent = `${totalWeight.toFixed(0)}%`;
        controls.totalWeightBadge.classList.remove('bg-success', 'bg-danger');
        if (totalWeight === 100) {
            controls.totalWeightBadge.classList.add('bg-success');
            controls.saveReportBtn.disabled = false;
            controls.saveReportBtn.title = "حفظ قالب التقرير";
        } else {
            controls.totalWeightBadge.classList.add('bg-danger');
            controls.saveReportBtn.disabled = true;
            controls.saveReportBtn.title = "لا يمكن الحفظ، يجب أن يكون مجموع الأوزان 100%";
        }
    }

    function syncUIWithState() {
        controls.title.value = reportState.report_title;
        controls.startDate.value = reportState.start_date;
        controls.endDate.value = reportState.end_date;
        controls.coursesWeight.value = reportState.courses_weight;
        controls.workshopsWeight.value = reportState.workshops_weight;
        updateWeightDisplay();
        fetchReportData(reportState);
    }

    // ===================================================================
    //  4. ربط الأحداث (Event Listeners)
    // ===================================================================
    function initializeEventListeners() {
        let debounceTimer;
        const debounceFetch = () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                updateWeightDisplay();
                fetchReportData(reportState);
            }, 500);
        };

        controls.title.addEventListener('input', e => {
            reportState.report_title = e.target.value;
            reportTitleDisplay.textContent = e.target.value;
        });
        controls.startDate.addEventListener('change', e => {
            reportState.start_date = e.target.value;
            debounceFetch();
        });
        controls.endDate.addEventListener('change', e => {
            reportState.end_date = e.target.value;
            debounceFetch();
        });
        controls.coursesWeight.addEventListener('input', e => {
            reportState.courses_weight = parseFloat(e.target.value) || 0;
            debounceFetch();
        });
        controls.workshopsWeight.addEventListener('input', e => {
            reportState.workshops_weight = parseFloat(e.target.value) || 0;
            debounceFetch();
        });

        controls.addColumnBtn.addEventListener('click', () => {
            columnForm.form.reset();
            columnForm.editIndex.value = -1;
            columnForm.title.textContent = 'إضافة عمود تقييم جديد';
            columnEditorModal.show();
        });

        columnForm.saveBtn.addEventListener('click', () => {
            if (!columnForm.form.checkValidity()) {
                columnForm.form.reportValidity();
                return;
            }
            const newWeight = parseFloat(columnForm.weight.value);
            const editIndex = parseInt(columnForm.editIndex.value);
            let currentTotalWeight = (parseFloat(reportState.courses_weight) || 0) + (parseFloat(reportState.workshops_weight) || 0);
            reportState.employee_columns.forEach((col, index) => {
                if (index !== editIndex) {
                    currentTotalWeight += parseFloat(col.weight) || 0;
                }
            });
            if (currentTotalWeight + newWeight > 100) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ في الوزن',
                    text: `لا يمكن إضافة هذا الوزن. المجموع الحالي هو ${currentTotalWeight.toFixed(0)}%، وإضافة ${newWeight}% ستجعل المجموع يتجاوز 100%.`,
                });
                return;
            }
            const newColumnData = { source: columnForm.source.value, weight: newWeight };
            if (editIndex === -1) {
                reportState.employee_columns.push(newColumnData);
            } else {
                reportState.employee_columns[editIndex] = newColumnData;
            }
            columnEditorModal.hide();
            syncUIWithState();
        });

        reportContainer.addEventListener('click', e => {
            const editBtn = e.target.closest('.edit-column-btn');
            const deleteBtn = e.target.closest('.delete-column-btn');
            if (editBtn) {
                const columnIndex = parseInt(editBtn.dataset.index);
                const columnToEdit = reportState.employee_columns[columnIndex];
                columnForm.form.reset();
                columnForm.source.value = columnToEdit.source;
                columnForm.weight.value = columnToEdit.weight;
                columnForm.editIndex.value = columnIndex;
                columnForm.title.textContent = 'تعديل عمود تقييم';
                columnEditorModal.show();
            }
            if (deleteBtn) {
                const columnIndex = parseInt(deleteBtn.dataset.index);
                Swal.fire({
                    title: 'هل أنت متأكد؟', text: "سيتم حذف هذا العمود من التقرير!", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                    confirmButtonText: 'نعم، احذفه!', cancelButtonText: 'إلغاء'
                }).then(result => {
                    if (result.isConfirmed) {
                        reportState.employee_columns.splice(columnIndex, 1);
                        syncUIWithState();
                        Swal.fire('تم الحذف!', 'تم حذف العمود بنجاح.', 'success');
                    }
                });
            }
        });

        controls.saveReportBtn.addEventListener('click', async () => {
            const { value: reportName } = await Swal.fire({
                title: 'حفظ قالب التقرير', input: 'text', inputValue: reportState.report_title,
                inputLabel: 'أدخل اسماً مميزاً لهذا القالب', inputPlaceholder: 'مثال: تقرير الأداء الشهري للفروع',
                showCancelButton: true, confirmButtonText: 'حفظ', cancelButtonText: 'إلغاء',
                inputValidator: (value) => !value && 'يجب إدخال اسم للتقرير!'
            });
            if (reportName) {
                reportState.report_title = reportName;
                controls.title.value = reportName;
                reportTitleDisplay.textContent = reportName;
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    if (!csrfToken) {
                        Swal.fire('انتهت الجلسة', 'يرجى تسجيل الدخول مجددًا قبل حفظ التقرير.', 'warning').then(() => {
                            window.location.href = `${BASE_URL}/login`;
                        });
                        return;
                    }
                    const response = await fetch(`${BASE_URL}/reports/save-template`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify(reportState)
                    });
                    const result = await response.json();
                    if (result.success) {
                        Swal.fire('تم الحفظ!', result.message, 'success');
                    } else {
                        throw new Error(result.message || result.error || 'تعذر حفظ قالب التقرير.');
                    }
                } catch (error) {
                    Swal.fire('خطأ!', `فشل حفظ التقرير: ${error.message}`, 'error');
                }
            }
        });

        controls.printReportBtn.addEventListener('click', () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `${BASE_URL}/reports/print`;
            form.target = '_blank';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrf;
            form.appendChild(csrfInput);
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'json';
            input.value = JSON.stringify(reportState);
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });
    }

    // ===================================================================
    //  5. نقطة انطلاق التطبيق
    // ===================================================================
    function initializeApp() {
        console.log("Initializing Report App...");
        initializeEventListeners();
        syncUIWithState();
    }

    initializeApp();
});
