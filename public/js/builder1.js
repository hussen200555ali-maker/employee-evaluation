/**
 * المحرك الرئيسي لواجهة باني الاستمارات
 * هذا الكود يدير كل التفاعلات في صفحة builder.php
 */
document.addEventListener('DOMContentLoaded', function() {

    // ==================================================
    // 1. تعريف الحالة الأولية والعناصر الرئيسية
    // ==================================================

    // `formStructure` هو كائن JavaScript الذي يمثل "الحقيقة المطلقة" لهيكل الاستمارة.
    // أي تغيير في الواجهة يجب أن ينعكس هنا أولاً، ثم يتم تحديث الواجهة بناءً عليه.
    let formStructure = {
        criteria: []
    };

    // `selectedElement` يحتفظ بالبيانات الخاصة بالعنصر (معيار أو قسم) الذي تم تحديده حالياً.
    let selectedElement = null;

    // عدادات لضمان أن كل عنصر جديد يحصل على ID فريد.
    let criterionCounter = 0;
    let sectionCounter = 0;

    // كائن لتخزين مراجع عناصر DOM الرئيسية لسهولة الوصول إليها.
    const elements = {
        mainDropZone: document.getElementById('mainDropZone'),
        structureContainer: document.getElementById('form-structure-container'),
        propertiesContent: document.getElementById('properties-content'),
        noSelectionMessage: document.querySelector('.no-selection-message'),
        hiddenFieldsContainer: document.getElementById('structure-hidden-fields'),
        draggableElements: document.querySelectorAll('.draggable-element'),
        workspaceCanvas: document.querySelector('.workspace-canvas')
    };

    // ==================================================
    // 2. دوال إدارة الحالة (State Management)
    // ==================================================
    // هذه الدوال هي المسؤولة عن تعديل كائن `formStructure`.

    /**
     * يضيف معياراً جديداً إلى هيكل البيانات.
     */
    function addCriterion() {
        criterionCounter++;
        const newCriterion = {
            id: `c_${criterionCounter}`,
            type: 'criterion', // نضيف نوعاً لتمييزه عن الأقسام
            name: `معيار جديد ${criterionCounter}`,
            weight: 0,
            sections: []
        };
        formStructure.criteria.push(newCriterion);
        updateUI(); // نطلب تحديث الواجهة لتعكس التغيير.
    }

    /**
     * يضيف قسماً جديداً إلى معيار محدد.
     * @param {string} criterionId - معرف المعيار الذي سيتم إضافة القسم إليه.
     * @param {string} sectionType - نوع القسم (percentage, numeric, etc.).
     */
  /**
 * يضيف قسماً جديداً إلى معيار محدد. (النسخة المحسّنة)
 */
function addSection(criterionId, sectionType) {
    const criterion = formStructure.criteria.find(c => c.id === criterionId);
    if (!criterion) return;

    sectionCounter++;
    const newSection = {
        id: `s_${sectionCounter}`,
        type: 'section',
        sectionType: sectionType,
        name: `قسم جديد (${sectionType})`,
        description: '',
        weight: 0,
        // إضافة الخصائص الجديدة مع قيم افتراضية
        min_value: 0,
        max_value: 5,
        options: (sectionType === 'grade') ? [
            { label: 'ممتاز', value: 100 },
            { label: 'جيد', value: 75 },
            { label: 'مقبول', value: 50 }
        ] : [],
        allowNotes: true,
        notesRequired: false,
        notesPlaceholder: 'اكتب ملاحظاتك هنا...'
    };
    criterion.sections.push(newSection);
    updateUI();
    selectElement(newSection); // تحديد القسم الجديد مباشرة لعرض خصائصه
}

    /**
     * يحدد عنصراً لعرض خصائصه.
     * @param {object|null} elementData - بيانات العنصر من `formStructure`, أو null لإلغاء التحديد.
     */
    function selectElement(elementData) {
        selectedElement = elementData;
        updateUI(); // تحديث الواجهة لإظهار الخصائص الصحيحة.
    }

    /**
     * يحدّث خاصية في العنصر المحدد حالياً.
     * @param {string} propertyName - اسم الخاصية المراد تحديثها (مثل 'name' أو 'weight').
     * @param {any} value - القيمة الجديدة للخاصية.
     */
    /**
 * يحدّث خاصية في العنصر المحدد حالياً. (النسخة المحسّنة)
 * @param {string} propertyName - اسم الخاصية.
 * @param {any} value - القيمة الجديدة.
 * @param {number|null} optionIndex - مؤشر الخيار (في حالة تعديل خيار).
 * @param {string|null} optionProperty - اسم خاصية الخيار ('label' أو 'value').
 */
function updateSelectedElementProperty(propertyName, value, optionIndex = null, optionProperty = null) {
    if (!selectedElement) return;

    if (optionIndex !== null && optionProperty) {
        // حالة خاصة: تحديث خاصية داخل أحد الخيارات (options)
        if (selectedElement.options && selectedElement.options[optionIndex]) {
            selectedElement.options[optionIndex][optionProperty] = value;
        }
    } else {
        // الحالة العامة: تحديث خاصية مباشرة في العنصر.
        selectedElement[propertyName] = value;
    }
    
    // إعادة عرض لوحة الخصائص لتحديث الواجهة (مهم جداً لحقول checkbox).
    renderPropertiesPanel();
    // تحديث هيكل الواجهة الرئيسي (لتحديث الأسماء والأوزان).
    renderStructure();
    // تحديث الحقول المخفية.
    updateHiddenFields();
}

    // ==================================================
    // 3. دوال العرض (Render Functions)
    // ==================================================
    // هذه الدوال مسؤولة عن قراءة `formStructure` وتحديث واجهة HTML.

    /**
     * الدالة الرئيسية لتحديث الواجهة بالكامل.
     */
    function updateUI() {
        renderStructure();
        renderPropertiesPanel();
        updateHiddenFields();
    }

    /**
     * يعيد بناء عرض هيكل الاستمارة (المعايير والأقسام).
     */
    function renderStructure() {
        elements.structureContainer.innerHTML = ''; // مسح المحتوى القديم.

        formStructure.criteria.forEach(criterion => {
            const criterionEl = document.createElement('div');
            criterionEl.className = 'criterion-wrapper';
            criterionEl.dataset.id = criterion.id;
            if (selectedElement && selectedElement.id === criterion.id) {
                criterionEl.classList.add('selected');
            }

            criterionEl.innerHTML = `
                <div class="criterion-header">
                    <h6>${criterion.name}</h6>
                    <span>الوزن: ${criterion.weight}%</span>
                </div>
                <div class="criterion-body">
                    <div class="sections-container">
                        ${criterion.sections.map(section => `
                            <div class="section-wrapper" data-id="${section.id}" data-parent-id="${criterion.id}">
                                ${section.name}
                            </div>
                        `).join('')}
                    </div>
                    <div class="drop-zone section-drop-zone" data-criterion-id="${criterion.id}">
                        <span><i class="fas fa-plus fa-xs"></i> أضف قسماً هنا</span>
                    </div>
                </div>
            `;
            elements.structureContainer.appendChild(criterionEl);
        });
    }

    /**
     * يعرض لوحة الخصائص بناءً على العنصر المحدد.
     */
  function renderPropertiesPanel() {
    // إذا لم يكن هناك عنصر محدد، أظهر الرسالة الافتراضية وأوقف التنفيذ.
    if (!selectedElement) {
        elements.propertiesContent.innerHTML = ''; // مسح أي محتوى قديم
        elements.noSelectionMessage.style.display = 'block';
        return;
    }

    // إخفاء الرسالة الافتراضية لأن لدينا عنصراً محدداً.
    elements.noSelectionMessage.style.display = 'none';
    let propertiesHTML = '';

    // التحقق من نوع العنصر المحدد (معيار أم قسم).
    if (selectedElement.type === 'criterion') {
        // --- بناء واجهة خصائص المعيار ---
        propertiesHTML = `
            <div class="mb-3">
                <label for="prop-name" class="form-label fw-bold">اسم المعيار</label>
                <input type="text" id="prop-name" class="form-control" value="${selectedElement.name}">
            </div>
            <div class="mb-3">
                <label for="prop-description" class="form-label fw-bold">وصف المعيار</label>
                <textarea id="prop-description" class="form-control" rows="3">${selectedElement.description || ''}</textarea>
            </div>
            <div class="mb-3">
                <label for="prop-weight" class="form-label fw-bold">الوزن النسبي للمعيار (%)</label>
                <input type="number" id="prop-weight" class="form-control" value="${selectedElement.weight}" min="0" max="100" step="0.1">
            </div>
        `;
    } else if (selectedElement.type === 'section') {
        // --- بناء واجهة خصائص القسم ---
        propertiesHTML = `
            <div class="mb-3">
                <label for="prop-name" class="form-label fw-bold">اسم القسم</label>
                <input type="text" id="prop-name" class="form-control" value="${selectedElement.name}">
            </div>
            <div class="mb-3">
                <label for="prop-description" class="form-label fw-bold">وصف القسم (إرشادات للمقيّم)</label>
                <textarea id="prop-description" class="form-control" rows="3">${selectedElement.description || ''}</textarea>
            </div>
            <div class="mb-3">
                <label for="prop-weight" class="form-label fw-bold">الوزن داخل المعيار (%)</label>
                <input type="number" id="prop-weight" class="form-control" value="${selectedElement.weight}" min="0" max="100" step="0.1">
            </div>
            <hr>
            <h6>إعدادات حقل الإدخال</h6>
        `;

        // إضافة حقول إضافية بناءً على نوع القسم (sectionType).
        switch (selectedElement.sectionType) {
            case 'numeric':
                propertiesHTML += `
                    <div class="row g-2">
                        <div class="col">
                            <label for="prop-min-value" class="form-label">أقل قيمة</label>
                            <input type="number" id="prop-min-value" class="form-control" value="${selectedElement.min_value || 0}">
                        </div>
                        <div class="col">
                            <label for="prop-max-value" class="form-label">أعلى قيمة</label>
                            <input type="number" id="prop-max-value" class="form-control" value="${selectedElement.max_value || 5}">
                        </div>
                    </div>
                `;
                break;
            case 'grade':
                propertiesHTML += `
                    <div>
                        <label class="form-label fw-bold">خيارات التقدير</label>
                        <div id="options-container">
                            ${(selectedElement.options || []).map((opt, index) => `
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" class="form-control option-label" value="${opt.label}" data-index="${index}" placeholder="التسمية (ممتاز)">
                                    <input type="number" class="form-control option-value" value="${opt.value}" data-index="${index}" placeholder="القيمة (100)">
                                    <button class="btn btn-outline-danger remove-option-btn" data-index="${index}"><i class="fas fa-times"></i></button>
                                </div>
                            `).join('')}
                        </div>
                        <button class="btn btn-sm btn-outline-primary" id="add-option-btn"><i class="fas fa-plus"></i> إضافة خيار</button>
                    </div>
                `;
                break;
            // يمكن إضافة case لأنواع أخرى مثل 'percentage', 'boolean' إذا احتجنا خصائص خاصة بها.
        }

        // إضافة خيارات الملاحظات (تظهر لكل أنواع الأقسام).
        propertiesHTML += `
            <hr>
            <h6>إعدادات الملاحظات</h6>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="prop-allow-notes" ${selectedElement.allowNotes ? 'checked' : ''}>
                <label class="form-check-label" for="prop-allow-notes">السماح بإضافة ملاحظات</label>
            </div>
            <div class="form-check form-switch" style="display: ${selectedElement.allowNotes ? 'block' : 'none'}">
                <input class="form-check-input" type="checkbox" id="prop-notes-required" ${selectedElement.notesRequired ? 'checked' : ''}>
                <label class="form-check-label" for="prop-notes-required">الملاحظات إجبارية</label>
            </div>
            <div class="mt-2" style="display: ${selectedElement.allowNotes ? 'block' : 'none'}">
                <label for="prop-notes-placeholder" class="form-label">النص التلميحي للملاحظات</label>
                <input type="text" id="prop-notes-placeholder" class="form-control" value="${selectedElement.notesPlaceholder || ''}">
            </div>
        `;
    }

    // وضع كود HTML المكتمل في لوحة الخصائص.
    elements.propertiesContent.innerHTML = propertiesHTML;
}

    /**
     * يحدّث الحقول المخفية التي سيتم إرسالها مع النموذج.
     */
    function updateHiddenFields() {
        // هذا الجزء مهم جداً لتحويل كائن JavaScript إلى حقول يمكن لـ PHP قراءتها.
        elements.hiddenFieldsContainer.innerHTML = '';
        formStructure.criteria.forEach((criterion, cIndex) => {
            elements.hiddenFieldsContainer.innerHTML += `<input type="hidden" name="criteria[${cIndex}][name]" value="${criterion.name}">`;
            elements.hiddenFieldsContainer.innerHTML += `<input type="hidden" name="criteria[${cIndex}][weight]" value="${criterion.weight}">`;
            // ... (سنضيف باقي الخصائص هنا لاحقاً)

            criterion.sections.forEach((section, sIndex) => {
                elements.hiddenFieldsContainer.innerHTML += `<input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][name]" value="${section.name}">`;
                elements.hiddenFieldsContainer.innerHTML += `<input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][weight]" value="${section.weight}">`;
                elements.hiddenFieldsContainer.innerHTML += `<input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][type]" value="${section.sectionType}">`;
                // ...
            });
        });
    }
/**
 * يولد كود HTML لمعاينة الاستمارة.
 * @param {object} structure - كائن formStructure الحالي.
 * @returns {string} - سلسلة HTML جاهزة للعرض.
 */
function generatePreviewHTML(structure) {
    let html = `<div class="preview-header"><h3>${document.getElementById('template_name').value || 'اسم الاستمارة'}</h3></div>`;

    structure.criteria.forEach(criterion => {
        html += `<div class="preview-criterion">
                    <h4>${criterion.name} <span class="badge bg-secondary">${criterion.weight}%</span></h4>
                    <p class="text-muted">${criterion.description || ''}</p>
                `;
        
        criterion.sections.forEach(section => {
            html += `<div class="preview-section">
                        <h5>${section.name} <span class="badge bg-light text-dark">الوزن: ${section.weight}%</span></h5>
                        <p class="text-muted small">${section.description || ''}</p>
                        <div class="preview-input-mockup">
                            <em>[نموذج حقل إدخال لنوع: ${section.sectionType}]</em>
                        </div>
                    </div>`;
        });

        html += `</div>`; // إغلاق .preview-criterion
    });

    return html;
}


    // ==================================================
    // 4. إعداد مستمعي الأحداث (Event Listeners)
    // ==================================================
  /**
 * الدالة المركزية لإعداد كل مستمعي الأحداث في واجهة الباني.
 * تستخدم هذه الدالة تقنية "تفويض الأحداث" بكثافة لضمان كفاءة الأداء
 * والتعامل مع العناصر التي يتم إنشاؤها ديناميكياً.
 */
function setupEventListeners() {

    // ==================================================
    // 1. أحداث السحب (Drag) من مكتبة العناصر
    // ==================================================
    elements.draggableElements.forEach(el => {
        // عند بدء سحب عنصر من المكتبة
        el.addEventListener('dragstart', e => {
            // تخزين نوع العنصر الرئيسي (criterion أو section)
            e.dataTransfer.setData('text/plain', e.target.dataset.type);
            // إذا كان العنصر قسماً، نخزن نوعه الفرعي أيضاً (percentage, numeric, etc.)
            if (e.target.dataset.sectionType) {
                e.dataTransfer.setData('section-type', e.target.dataset.sectionType);
            }
        });
    });

    // ==================================================
    // 2. أحداث الإفلات (Drop) في مساحة العمل
    // ==================================================

    // نراقب الأحداث على حاوية مساحة العمل بأكملها بدلاً من كل منطقة إفلات على حدة.
    elements.workspaceCanvas.addEventListener('dragover', e => {
        // منع السلوك الافتراضي للمتصفح (الذي يمنع الإفلات).
        e.preventDefault(); 
        // البحث عن أقرب منطقة إفلات لمؤشر الماوس.
        const dropZone = e.target.closest('.drop-zone');
        if (dropZone) {
            // إضافة كلاس مرئي للإشارة إلى أن الإفلات ممكن هنا.
            dropZone.classList.add('drag-over');
        }
    });

    // عند مغادرة مؤشر الماوس لمنطقة إفلات.
    elements.workspaceCanvas.addEventListener('dragleave', e => {
        const dropZone = e.target.closest('.drop-zone');
        if (dropZone) {
            // إزالة الكلاس المرئي.
            dropZone.classList.remove('drag-over');
        }
    });

    // عند حدوث عملية الإفلات النهائية.
    elements.workspaceCanvas.addEventListener('drop', e => {
        e.preventDefault();
        const dropZone = e.target.closest('.drop-zone');
        if (dropZone) {
            dropZone.classList.remove('drag-over');
            // قراءة البيانات التي تم تخزينها عند بدء السحب.
            const type = e.dataTransfer.getData('text/plain');

            // التحقق من مكان الإفلات ونوع العنصر.
            if (dropZone.id === 'mainDropZone' && type === 'criterion') {
                // إذا تم إفلات "معيار" في منطقة الإفلات الرئيسية.
                addCriterion();
            } else if (dropZone.classList.contains('section-drop-zone') && type === 'section') {
                // إذا تم إفلات "قسم" في منطقة إفلات داخل معيار.
                const criterionId = dropZone.dataset.criterionId;
                const sectionType = e.dataTransfer.getData('section-type');
                addSection(criterionId, sectionType);
            }
        }
    });

    // ==================================================
    // 3. حدث الضغط (Click) لتحديد العناصر
    // ==================================================
    elements.workspaceCanvas.addEventListener('click', e => {
        // البحث عن أقرب عنصر (معيار أو قسم) تم الضغط عليه.
        const criterionWrapper = e.target.closest('.criterion-wrapper');
        const sectionWrapper = e.target.closest('.section-wrapper');

        // إلغاء تحديد أي عنصر محدد سابقاً.
        document.querySelectorAll('.selected').forEach(el => el.classList.remove('selected'));

        if (sectionWrapper) {
            // إذا تم الضغط على قسم، حدده.
            sectionWrapper.classList.add('selected');
            // جلب بيانات القسم من `formStructure` وتمريرها لدالة التحديد.
            const elementData = formStructure.criteria
                .find(c => c.id === sectionWrapper.dataset.parentId)
                .sections.find(s => s.id === sectionWrapper.dataset.id);
            selectElement(elementData);
        } else if (criterionWrapper) {
            // إذا تم الضغط على معيار (وليس قسم بداخله)، حدده.
            criterionWrapper.classList.add('selected');
            // جلب بيانات المعيار من `formStructure` وتمريرها لدالة التحديد.
            const elementData = formStructure.criteria.find(c => c.id === criterionWrapper.dataset.id);
            selectElement(elementData);
        } else {
            // إذا تم الضغط في أي مكان آخر، قم بإلغاء التحديد.
            selectElement(null);
        }
    });

    // ==================================================
    // 4. أحداث لوحة الخصائص (Properties Panel)
    // ==================================================

    // حدث الإدخال (input): يتم تفعيله فوراً عند أي تغيير في حقول النص أو الأرقام.
    elements.propertiesContent.addEventListener('input', e => {
        if (!selectedElement) return;

        const target = e.target;
        // استخدام `type` لتحديد إذا كان الحقل checkbox أم لا.
        const value = target.type === 'checkbox' ? target.checked : target.value;
        // تحويل معرف الحقل (مثل 'prop-min-value') إلى اسم الخاصية ('min_value').
        const propName = target.id.replace('prop-', '').replace(/-/g, '_');

        // التحقق مما إذا كان الحقل يخص خياراً (option) أم خاصية عامة.
        if (target.classList.contains('option-label') || target.classList.contains('option-value')) {
            const index = target.dataset.index;
            const prop = target.classList.contains('option-label') ? 'label' : 'value';
            // استدعاء دالة التحديث مع تمرير معلومات الخيار.
            updateSelectedElementProperty(null, value, index, prop);
        } else {
            // استدعاء دالة التحديث للخاصية العامة.
            updateSelectedElementProperty(propName, value);
        }
    });

    // حدث الضغط (click): للتعامل مع الأزرار مثل "إضافة خيار" أو "حذف خيار".
    elements.propertiesContent.addEventListener('click', e => {
        if (!selectedElement) return;

        // إذا تم الضغط على زر "إضافة خيار".
        if (e.target.id === 'add-option-btn') {
            // التأكد من وجود مصفوفة الخيارات، وإنشائها إذا لم تكن موجودة.
            if (!selectedElement.options) {
                selectedElement.options = [];
            }
            // إضافة خيار جديد بقيم افتراضية.
            selectedElement.options.push({ label: 'خيار جديد', value: 0 });
            updateUI(); // إعادة بناء الواجهة بالكامل لإظهار الحقل الجديد.
        }

        // إذا تم الضغط على زر "حذف خيار".
        if (e.target.classList.contains('remove-option-btn')) {
            const index = e.target.dataset.index;
            if (selectedElement.options) {
                // حذف الخيار من المصفوفة.
                selectedElement.options.splice(index, 1);
                updateUI(); // إعادة بناء الواجهة بالكامل.
            }
        }
    });

// --- حدث الضغط على زر المعاينة ---
const previewButton = document.querySelector('.toolbar button[data-bs-toggle="modal"]'); // استهداف الزر بشكل أفضل
if (previewButton) {
    previewButton.addEventListener('click', () => {
        const previewContent = document.getElementById('preview-content');
        // توليد HTML بناءً على الحالة الحالية لـ formStructure.
        previewContent.innerHTML = generatePreviewHTML(formStructure);
    });
}}


    // ==================================================
    // 5. بدء تشغيل الباني
    // ==================================================
    function initializeBuilder() {
        if (isEditing && initialFormStructure) {
            formStructure = initialFormStructure;
            // تحديث العدادات لتجنب تضارب المعرفات
            criterionCounter = Math.max(0, ...formStructure.criteria.map(c => parseInt((c.id || 'c_0').split('_')[1])));
            const sectionIds = formStructure.criteria.flatMap(c => (c.sections || []).map(s => parseInt((s.id || 's_0').split('_')[1])));
            if (sectionIds.length > 0) {
                sectionCounter = Math.max(0, ...sectionIds);
            }
        }
        updateUI();
    }

    initializeBuilder();
    setupEventListeners();
});

