/**
 * =================================================================
 * الملف: public/js/evaluation-creator.js (النسخة النهائية المصححة )
 * =================================================================
 */
document.addEventListener('DOMContentLoaded', function () {
    // --- التحقق من أننا في الصفحة الصحيحة ---
    const startEvaluationForm = document.getElementById('start-evaluation-form');
    if (!startEvaluationForm) {
        // إذا لم يتم العثور على النموذج، أوقف تنفيذ هذا الملف بالكامل
        return; 
    }

    // --- 1. جلب المسار الأساسي من وسم body ---
    const APP_URL = document.body.dataset.appUrl;

    if (!APP_URL) {
        console.error("خطأ فادح: المسار الأساسي للتطبيق (APP_URL) غير مُعرَّف في <body data-app-url='...'>.");
        return;
    }

    // --- 2. تعريف عناصر الصفحة ---
    const evaluatedSearchInput = document.getElementById('evaluated_employee_search');
    const evaluatedIdInput = document.getElementById('evaluated_employee_id');
    const evaluatedResultsContainer = document.getElementById('evaluated-search-results');
    const evaluatorSearchInput = document.getElementById('evaluator_employee_search');
    const evaluatorIdInput = document.getElementById('evaluator_employee_id');
    const evaluatorResultsContainer = document.getElementById('evaluator-search-results');
    const isExternalCheck = document.getElementById('is_external_evaluator');
    const externalFields = document.getElementById('external-evaluator-fields');
    const evaluatorNameInput = document.getElementById('evaluator_name');
    const evaluatorTitleInput = document.getElementById('evaluator_title');
    const templateSelect = document.getElementById('template_id');
    const submitButton = startEvaluationForm.querySelector('button[type="submit"]');
    let searchTimeout;

    // --- 3. دالة البحث عن الموظفين ---
    async function searchEmployees(query, resultsContainer) {
        if (query.length < 2) {
            resultsContainer.innerHTML = '';
            resultsContainer.style.display = 'none';
            return;
        }

        const searchUrl = `${APP_URL}/api/employees/search?q=${encodeURIComponent(query)}`;
        
        try {
            const response = await fetch(searchUrl);
            const contentType = response.headers.get("content-type");
            if (!response.ok || !contentType || !contentType.includes("application/json")) {
                const errorText = await response.text();
                console.error(`فشل طلب البحث إلى ${searchUrl}.`, errorText);
                resultsContainer.innerHTML = `<div class="search-result-item error">خطأ في الخادم.</div>`;
                resultsContainer.style.display = 'block';
                return;
            }
            const employees = await response.json();
            displayResults(employees, resultsContainer);
        } catch (error) {
            console.error(`فشل الاتصال بالخادم عند طلب ${searchUrl}`, error);
            resultsContainer.innerHTML = `<div class="search-result-item error">فشل الاتصال بالخادم.</div>`;
            resultsContainer.style.display = 'block';
        }
    }

    // --- 4. دالة عرض نتائج البحث ---
    function displayResults(employees, container) {
        container.innerHTML = '';
        if (employees.length > 0) {
            employees.forEach(employee => {
                const item = document.createElement('div');
                item.className = 'search-result-item';
                item.textContent = `${employee.full_name} (${employee.emp_code})`;
                item.dataset.id = employee.id;
                item.dataset.positionId = employee.position_id;
                container.appendChild(item);
            });
        } else {
            container.innerHTML = '<div class="search-result-item disabled">لا توجد نتائج مطابقة.</div>';
        }
        container.style.display = 'block';
    }

    // --- 5. دالة جلب قوالب التقييم ---
    async function fetchTemplates(positionId) {
        templateSelect.disabled = true;
        templateSelect.innerHTML = '<option value="">جاري التحقق...</option>';

        if (!positionId || positionId === 'null' || positionId == 0) {
            templateSelect.innerHTML = '<option value="">خطأ: يجب تحديد منصب للموظف أولاً.</option>';
            validateForm();
            return;
        }

        templateSelect.innerHTML = '<option value="">جاري تحميل القوالب...</option>';
        try {
            const response = await fetch(`${APP_URL}/api/templates/by-position/${positionId}`);
            const templates = await response.json();

            templateSelect.innerHTML = '';
            if (templates.length > 0) {
                templateSelect.innerHTML = '<option value="">-- اختر قالب التقييم --</option>';
                templates.forEach(template => {
                    const option = new Option(template.template_name, template.id);
                    templateSelect.appendChild(option);
                });
                templateSelect.disabled = false;
            } else {
                templateSelect.innerHTML = '<option value="">لا توجد قوالب متاحة لهذا المنصب.</option>';
            }
        } catch (error) {
            console.error('Error fetching templates:', error);
            templateSelect.innerHTML = '<option value="">فشل تحميل القوالب.</option>';
        }
        validateForm();
    }

    // --- 6. دوال إدارة الأحداث ---
    function setupSearch(searchInput, resultsContainer) {
        if (searchInput) {
            searchInput.addEventListener('keyup', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value;
                searchTimeout = setTimeout(() => searchEmployees(query, resultsContainer), 300);
            });
        }
    }

    function handleResultSelection(resultsContainer, searchInput, idInput) {
        if (resultsContainer) {
            resultsContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('search-result-item') && e.target.dataset.id) {
                    idInput.value = e.target.dataset.id;
                    searchInput.value = e.target.textContent;
                    resultsContainer.style.display = 'none';
                    if (idInput === evaluatedIdInput) {
                        fetchTemplates(e.target.dataset.positionId);
                    }
                    validateForm();
                }
            });
        }
    }

    function validateForm() {
        if (submitButton) {
            const isExternal = isExternalCheck && isExternalCheck.checked;
            const evaluatorOk = isExternal ? ((evaluatorNameInput && evaluatorNameInput.value.trim()) || (evaluatorTitleInput && evaluatorTitleInput.value.trim())) : !!evaluatorIdInput.value;
            submitButton.disabled = !(evaluatedIdInput.value && evaluatorOk && templateSelect.value);
        }
    }
    function toggleExternal() {
        if (!isExternalCheck) return;
        const isExternal = isExternalCheck.checked;
        if (isExternal) {
            evaluatorSearchInput.value = '';
            evaluatorIdInput.value = '';
            evaluatorSearchInput.disabled = true;
            if (externalFields) externalFields.style.display = 'block';
        } else {
            if (externalFields) externalFields.style.display = 'none';
            if (evaluatorNameInput) evaluatorNameInput.value = '';
            if (evaluatorTitleInput) evaluatorTitleInput.value = '';
            evaluatorSearchInput.disabled = false;
        }
        validateForm();
    }
    if (isExternalCheck) {
        isExternalCheck.addEventListener('change', toggleExternal);
        if (evaluatorNameInput) evaluatorNameInput.addEventListener('input', validateForm);
        if (evaluatorTitleInput) evaluatorTitleInput.addEventListener('input', validateForm);
    }

    // --- 7. ربط الأحداث ---
    setupSearch(evaluatedSearchInput, evaluatedResultsContainer);
    setupSearch(evaluatorSearchInput, evaluatorResultsContainer);
    handleResultSelection(evaluatedResultsContainer, evaluatedSearchInput, evaluatedIdInput);
    handleResultSelection(evaluatorResultsContainer, evaluatorSearchInput, evaluatorIdInput);
    
    if (templateSelect) {
        templateSelect.addEventListener('change', validateForm);
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-wrapper')) {
            if (evaluatedResultsContainer) evaluatedResultsContainer.style.display = 'none';
            if (evaluatorResultsContainer) evaluatorResultsContainer.style.display = 'none';
        }
    });
});
