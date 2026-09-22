document.addEventListener('DOMContentLoaded', function() {

    // 1. تعريف الحالة الأولية والعناصر الرئيسية (لا تغيير هنا)
    let formStructure = { criteria: [] };
    let selectedElement = null;
    let criterionCounter = 0;
    let sectionCounter = 0;

    const elements = {
        mainDropZone: document.getElementById('mainDropZone'),
        structureContainer: document.getElementById('form-structure-container'),
        propertiesContent: document.getElementById('properties-content'),
        hiddenFieldsContainer: document.getElementById('structure-hidden-fields'),
        draggableElements: document.querySelectorAll('.draggable-element'),
        workspaceCanvas: document.querySelector('.workspace-canvas'),
        totalWeightIndicator: document.getElementById('total-weight-indicator'),
        autoDistributeToggle: document.getElementById('auto-distribute-weights'),
    };

    // 2. دوال إدارة الحالة (الدماغ)
    
    // ▼▼▼ بداية التعديل 1: إضافة دوال النسخ الجديدة ▼▼▼

    /**
     * دالة جديدة: لنسخ معيار موجود.
     * @param {string} sourceCriterionId - معرف المعيار الأصلي الذي سيتم نسخه.
     */
    function cloneCriterion(sourceCriterionId) {
        const sourceCriterion = formStructure.criteria.find(c => c.id === sourceCriterionId);
        if (!sourceCriterion) return;

        criterionCounter++;
        // 1. إنشاء نسخة عميقة (Deep Clone) من المعيار
        const newCriterion = JSON.parse(JSON.stringify(sourceCriterion));
        
        // 2. تحديث خصائص النسخة الجديدة لضمان تفردها
        newCriterion.id = `c_${criterionCounter}`;
        newCriterion.name = `${sourceCriterion.name} (نسخة)`;
        
        // 3. تحديث معرفات الأقسام الداخلية لضمان عدم تضاربها
        newCriterion.sections.forEach(section => {
            sectionCounter++;
            section.id = `s_${sectionCounter}`;
        });

        // 4. إضافة النسخة الجديدة إلى هيكل البيانات
        formStructure.criteria.push(newCriterion);
        
        // 5. تحديث الواجهة وتحديد العنصر الجديد
        handleAutomaticWeightDistribution();
        updateUI();
        selectElement(newCriterion);
    }

    /**
     * دالة جديدة: لنسخ قسم موجود إلى معيار معين.
     * @param {string} sourceSectionId - معرف القسم الأصلي.
     * @param {string} targetCriterionId - معرف المعيار الذي سيستقبل النسخة.
     */
    function cloneSection(sourceSectionId, targetCriterionId) {
        let sourceSection = null;
        // البحث عن القسم الأصلي في كل المعايير
        for (const criterion of formStructure.criteria) {
            sourceSection = criterion.sections.find(s => s.id === sourceSectionId);
            if (sourceSection) break;
        }
        
        const targetCriterion = formStructure.criteria.find(c => c.id === targetCriterionId);

        if (!sourceSection || !targetCriterion) return;

        sectionCounter++;
        // 1. إنشاء نسخة عميقة من القسم
        const newSection = JSON.parse(JSON.stringify(sourceSection));
        
        // 2. تحديث خصائص النسخة الجديدة
        newSection.id = `s_${sectionCounter}`;
        newSection.name = `${sourceSection.name} (نسخة)`;

        // 3. إضافة النسخة إلى المعيار المستهدف
        targetCriterion.sections.push(newSection);
        
        // 4. تحديث الواجهة وتحديد العنصر الجديد
        handleAutomaticWeightDistribution(targetCriterion);
        updateUI();
        selectElement(newSection);
    }

    // ▲▲▲ نهاية التعديل 1 ▲▲▲

    // (بقية دوال إدارة الحالة مثل addCriterion, addSection, delete... تبقى كما هي)
    function addCriterion() {
        criterionCounter++;
        const newCriterion = { id: `c_${criterionCounter}`, type: 'criterion', name: `معيار جديد ${criterionCounter}`, description: '', weight: 0, sections: [] };
        formStructure.criteria.push(newCriterion);
        handleAutomaticWeightDistribution();
        updateUI();
        selectElement(newCriterion);
    }

    function addSection(criterionId, sectionType) {
        const criterion = formStructure.criteria.find(c => c.id === criterionId);
        if (!criterion) return;
        sectionCounter++;
        const newSection = { 
            id: `s_${sectionCounter}`, 
            type: 'section', 
            sectionType: sectionType, 
            name: `قسم جديد (${getSectionTypeName(sectionType)})`, 
            description: '', 
            weight: (sectionType === 'helper') ? 0 : 0, 
            allowNotes: true, 
            notesRequired: false, 
            notesPlaceholder: 'اكتب ملاحظاتك هنا...', 
            options: (sectionType === 'grade') ? [{ label: 'جيد', value: 100 }, { label: 'متوسط', value: 64 }, { label: 'ضعيف', value: 34 }] : [], 
            min_value: (sectionType === 'numeric' || sectionType === 'helper') ? 0 : null, 
            max_value: (sectionType === 'numeric' || sectionType === 'helper') ? 999999 : null,
            formula: (sectionType === 'formula') ? '' : null,
            formulaDisplay: (sectionType === 'formula') ? '' : null,
            readonly: (sectionType === 'formula' || sectionType === 'auto') ? true : false,
            autoSource: (sectionType === 'auto') ? 'employee_count' : null,
            isHelper: (sectionType === 'helper') ? true : false
        };
        criterion.sections.push(newSection);
        handleAutomaticWeightDistribution(criterion);
        updateUI();
        selectElement(newSection);
    }

    function deleteCriterion(criterionId) {
        formStructure.criteria = formStructure.criteria.filter(c => c.id !== criterionId);
        if (selectedElement && selectedElement.id === criterionId) selectElement(null);
        handleAutomaticWeightDistribution();
        updateUI();
    }

    function deleteSection(sectionId) {
        let parentCriterion = null;
        formStructure.criteria.forEach(c => {
            const sectionIndex = c.sections.findIndex(s => s.id === sectionId);
            if (sectionIndex > -1) { c.sections.splice(sectionIndex, 1); parentCriterion = c; }
        });
        if (selectedElement && selectedElement.id === sectionId) selectElement(null);
        if (parentCriterion) handleAutomaticWeightDistribution(parentCriterion);
        updateUI();
    }

    function selectElement(elementData) {
        selectedElement = elementData;
        renderPropertiesPanel();
        document.querySelectorAll('.selected').forEach(el => el.classList.remove('selected'));
        if (elementData) {
            const el = document.querySelector(`[data-id="${elementData.id}"]`);
            if (el) el.classList.add('selected');
        }
    }

    function handleAutomaticWeightDistribution(criterion = null) {
        if (!elements.autoDistributeToggle.checked) return;
        const list = criterion ? criterion.sections : formStructure.criteria;
        if (criterion) {
            const nonHelpers = list.filter(s => s.sectionType !== 'helper');
            const count = nonHelpers.length;
            if (count === 0) return;
            const weight = 100 / count;
            list.forEach(item => { item.weight = (item.sectionType === 'helper') ? 0 : weight; });
        } else {
            const count = list.length;
            if (count === 0) return;
            const weight = 100 / count;
            list.forEach(item => { item.weight = weight; });
        }
    }

  /**
 * يعالج التغيير اليدوي لوزن عنصر ويضمن دقة القيمة.
 * @param {string} id - معرف العنصر.
 * @param {string|number} newWeight - الوزن الجديد من حقل الإدخال.
 * @param {boolean} isCriterion - هل العنصر معيار أم قسم.
 */
function handleManualWeightChange(id, newWeight, isCriterion) {
    // البحث عن القائمة الصحيحة (معايير أو أقسام)
    const list = isCriterion 
        ? formStructure.criteria 
        : formStructure.criteria.find(c => c.sections.some(s => s.id === id))?.sections;

    if (!list) return;

    // البحث عن العنصر المستهدف داخل القائمة
    const target = list.find(item => item.id === id);

    if (target) {
        // ▼▼▼ بداية الإصلاح ▼▼▼
        // 1. تحويل الوزن الجديد إلى رقم عشري.
        let weightValue = parseFloat(newWeight) || 0;
        
        // 2. تقريب الرقم إلى 7 خانات عشرية لتجنب أخطاء الدقة.
        //    استخدام toFixed() يعيد سلسلة نصية، لذا نعيد تحويلها إلى رقم.
        weightValue = parseFloat(weightValue.toFixed(7));

        // 3. تحديث وزن العنصر بالقيمة الدقيقة والمقربة.
        target.weight = weightValue;
        // ▲▲▲ نهاية الإصلاح ▲▲▲
    }

    // تحديث الواجهة بالكامل لتعكس التغييرات.
    updateUI();
}


    // 3. دوال العرض (تحديث واجهة المستخدم)

    function updateUI() {
        renderStructure();
        renderPropertiesPanel();
        updateHiddenFields();
    }

    // ▼▼▼ بداية التعديل 2: تعديل دالة renderStructure لتفعيل السحب ▼▼▼
    /**
     * يعيد بناء عرض هيكل الاستمارة مع تفعيل السحب للعناصر الموجودة.
     */
    function renderStructure() {
        elements.structureContainer.innerHTML = '';
        let totalWeight = 0;

        formStructure.criteria.forEach((criterion, criterionIndex) => {
            totalWeight += parseFloat(criterion.weight);
            const criterionEl = document.createElement('div');
            criterionEl.className = 'criterion-wrapper';
            criterionEl.dataset.id = criterion.id;
            criterionEl.draggable = true;

            if (selectedElement && selectedElement.id === criterion.id) {
                criterionEl.classList.add('selected');
            }

            let sectionsWeightSum = criterion.sections.reduce((sum, s) => sum + parseFloat(s.weight), 0);
            
            criterionEl.innerHTML = `
                <div class="criterion-header">
                    <h6><span class="section-ref-badge">${criterionIndex + 1}</span> ${criterion.name}</h6>
                    <div class="meta-info">
                        <span class="weight-badge ${Math.abs(sectionsWeightSum - 100) > 0.01 && criterion.sections.length > 0 ? 'invalid' : ''}"><i class="fas fa-sitemap"></i> ${sectionsWeightSum.toFixed(2)}%</span>
                        <span class="weight-badge"><i class="fas fa-weight-hanging"></i> ${parseFloat(criterion.weight).toFixed(2)}%</span>
                        <button class="btn-icon-sm delete-criterion" title="حذف المعيار"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <div class="criterion-body">
                    <div class="sections-container"></div>
                    <div class="drop-zone section-drop-zone" data-criterion-id="${criterion.id}"><span><i class="fas fa-plus fa-xs"></i> أضف قسماً هنا</span></div>
                </div>
            `;
            
            const sectionsContainer = criterionEl.querySelector('.sections-container');
            criterion.sections.forEach((section, sectionIndex) => {
                const sectionEl = document.createElement('div');
                sectionEl.className = 'section-item';
                sectionEl.dataset.id = section.id;
                sectionEl.draggable = true;

                if (selectedElement && selectedElement.id === section.id) {
                    sectionEl.classList.add('selected');
                }
                const refCode = String.fromCharCode(65 + sectionIndex) + (criterionIndex + 1);
                sectionEl.innerHTML = `
                    <span><span class="section-ref-badge">${refCode}</span> ${section.name}</span>
                    <div class="meta-info">
                        <span class="weight-badge">${section.sectionType === 'helper' ? '<i class="fas fa-database"></i> مساعد' : parseFloat(section.weight).toFixed(2) + '%'}</span>
                        <button class="btn-icon-sm delete-section" title="حذف القسم"><i class="fas fa-trash"></i></button>
                    </div>
                `;
                sectionsContainer.appendChild(sectionEl);
            });

            elements.structureContainer.appendChild(criterionEl);
        });

        elements.structureContainer.appendChild(elements.mainDropZone);
        elements.totalWeightIndicator.textContent = `${totalWeight.toFixed(2)}%`;
        elements.totalWeightIndicator.classList.toggle('invalid', Math.abs(totalWeight - 100) > 0.01 && formStructure.criteria.length > 0);
    }
    // ▲▲▲ نهاية التعديل 2 ▲▲▲

    // (دالة renderPropertiesPanel و updateHiddenFields تبقى كما هي)
    function renderPropertiesPanel() {
        if (!selectedElement) { elements.propertiesContent.innerHTML = `<div class="no-selection-message"><i class="fas fa-mouse-pointer"></i><p>اضغط على أي عنصر لعرض خصائصه.</p></div>`; return; }
        let propertiesHTML = '';
        if (selectedElement.type === 'criterion') {
            propertiesHTML = `<div class="form-group"><label>اسم المعيار</label><input type="text" class="form-control" data-prop="name" value="${selectedElement.name || ''}"></div><div class="form-group"><label>الوزن النسبي للمعيار (%)</label><input type="number" class="form-control" data-prop="weight" value="${parseFloat(selectedElement.weight || 0).toFixed(7)}" step="any"></div><div class="form-group"><label>الوصف</label><textarea class="form-control" data-prop="description">${selectedElement.description || ''}</textarea></div>`;
        } else if (selectedElement.type === 'section') {
            propertiesHTML = `<div class="form-group"><label>اسم القسم</label><input type="text" class="form-control" data-prop="name" value="${selectedElement.name || ''}"></div><div class="form-group"><label>نوع القسم</label><select class="form-select" data-prop="sectionType"><option value="percentage" ${selectedElement.sectionType === 'percentage' ? 'selected' : ''}>نسبة مئوية</option><option value="numeric" ${selectedElement.sectionType === 'numeric' ? 'selected' : ''}>رقمي</option><option value="grade" ${selectedElement.sectionType === 'grade' ? 'selected' : ''}>تقدير</option><option value="boolean" ${selectedElement.sectionType === 'boolean' ? 'selected' : ''}>نعم/لا</option><option value="formula" ${selectedElement.sectionType === 'formula' ? 'selected' : ''}>صيغة حسابية</option><option value="auto" ${selectedElement.sectionType === 'auto' ? 'selected' : ''}>تلقائي من النظام</option><option value="helper" ${selectedElement.sectionType === 'helper' ? 'selected' : ''}>حقل مساعد للحسابات</option></select></div>${selectedElement.sectionType !== 'helper' ? `<div class="form-group"><label>الوزن داخل المعيار (%)</label><input type="number" class="form-control" data-prop="weight" value="${parseFloat(selectedElement.weight || 0).toFixed(7)}" step="any"></div>` : `<div class="alert alert-info py-2 mb-2"><i class="fas fa-info-circle"></i> الحقل المساعد لا يُحتسب في وزن الاستمارة</div>`}`;
            if (selectedElement.sectionType === 'grade') {
                propertiesHTML += `<hr><h6><i class="fas fa-star text-warning"></i> إعدادات خيارات التقدير</h6><div id="grade-options-container">`;
                (selectedElement.options || []).forEach((option, index) => { propertiesHTML += `<div class="input-group input-group-sm mb-2 grade-option-row"><input type="text" class="form-control option-label" placeholder="التسمية" value="${option.label || ''}" data-index="${index}"><input type="number" class="form-control option-value" placeholder="القيمة" value="${option.value || ''}" data-index="${index}"><button class="btn btn-outline-danger remove-option-btn" title="حذف" data-index="${index}"><i class="fas fa-times"></i></button></div>`; });
                propertiesHTML += `</div><button class="btn btn-sm btn-outline-primary w-100" id="add-option-btn"><i class="fas fa-plus"></i> إضافة خيار</button>`;
            } else if (selectedElement.sectionType === 'numeric') {
                propertiesHTML += `<hr><h6><i class="fas fa-hashtag text-info"></i> إعدادات النطاق الرقمي</h6><div class="row g-2"><div class="col"><label class="form-label form-label-sm">القيمة الدنيا</label><input type="number" class="form-control form-control-sm" data-prop="min_value" value="${selectedElement.min_value ?? 0}"></div><div class="col"><label class="form-label form-label-sm">القيمة القصوى</label><input type="number" class="form-control form-control-sm" data-prop="max_value" value="${selectedElement.max_value ?? 5}"></div></div>`;
            } else if (selectedElement.sectionType === 'formula') {
                let refsList = '';
                formStructure.criteria.forEach((crit, ci) => {
                    crit.sections.forEach((sec, si) => {
                        if (sec.id !== selectedElement.id) {
                            const ref = String.fromCharCode(65 + si) + (ci + 1);
                            refsList += `<div class="formula-ref-item" data-ref="${ref}"><span class="section-ref-badge">${ref}</span> ${sec.name}</div>`;
                        }
                    });
                });
                propertiesHTML += `<hr><h6><i class="fas fa-calculator text-danger"></i> إعدادات الصيغة الحسابية</h6><div class="form-group"><label class="form-label">الصيغة</label><input type="text" class="form-control" data-prop="formula" id="formula-input" value="${selectedElement.formula || ''}" placeholder="مثال: =(B1+C1)/D1"><div id="refs-dropdown" class="refs-dropdown" style="display:none;"><div class="refs-dropdown-header"><i class="fas fa-list"></i> الأقسام المتاحة (اضغط للإدراج)</div>${refsList}</div></div><div class="form-group"><label class="form-label">الوصف</label><input type="text" class="form-control" data-prop="formulaDisplay" value="${selectedElement.formulaDisplay || ''}" placeholder="وصف الصيغة"></div><div class="form-check form-switch"><input class="form-check-input" type="checkbox" data-prop="readonly" ${selectedElement.readonly ? 'checked' : ''}><label class="form-check-label">للقراءة فقط (محسوب تلقائياً)</label></div><div class="formula-hint"><small class="text-muted"><strong>الرموز:</strong> حرف العمود + رقم المعيار (A1, B2, C3...)</small><br><small class="text-muted"><strong>الدوال:</strong> SUM, AVG, MIN, MAX, COUNT, IF, AND, OR, TOTAL, AVERAGE</small></div>`;
            } else if (selectedElement.sectionType === 'auto') {
                propertiesHTML += `<hr><h6><i class="fas fa-cog text-muted"></i> إعدادات الحقل التلقائي</h6><div class="form-group"><label class="form-label">المصدر</label><select class="form-select" data-prop="autoSource"><option value="employee_count" ${selectedElement.autoSource === 'employee_count' ? 'selected' : ''}>عدد الموظفين</option><option value="actual_force" ${selectedElement.autoSource === 'actual_force' ? 'selected' : ''}>القوة الفعلية</option><option value="total" ${selectedElement.autoSource === 'total' ? 'selected' : ''}>المجموع الكلي</option></select></div><div class="form-check form-switch"><input class="form-check-input" type="checkbox" data-prop="readonly" checked disabled><label class="form-check-label">للقراءة فقط (تلقائي)</label></div>`;
            } else if (selectedElement.sectionType === 'helper') {
                propertiesHTML += `<hr><h6><i class="fas fa-calculator text-primary"></i> حقل مساعد للحسابات</h6><div class="alert alert-info py-2"><small><i class="fas fa-info-circle"></i> هذا الحقل يُستخدم كمدخل لعمليات حسابية في أقسام الصيغ ولا يُحتسب في وزن الاستمارة.</small></div><div class="row g-2"><div class="col"><label class="form-label form-label-sm">القيمة الدنيا</label><input type="number" class="form-control form-control-sm" data-prop="min_value" value="${selectedElement.min_value ?? 0}"></div><div class="col"><label class="form-label form-label-sm">القيمة القصوى</label><input type="number" class="form-control form-control-sm" data-prop="max_value" value="${selectedElement.max_value ?? 999999}"></div></div>`;
            }
            propertiesHTML += `<hr><h6>إعدادات الملاحظات</h6><div class="form-check form-switch"><input class="form-check-input" type="checkbox" data-prop="allowNotes" ${selectedElement.allowNotes ? 'checked' : ''}><label class="form-check-label">السماح بإضافة ملاحظات</label></div><div class="form-check form-switch" style="display: ${selectedElement.allowNotes ? 'block' : 'none'}"><input class="form-check-input" type="checkbox" data-prop="notesRequired" ${selectedElement.notesRequired ? 'checked' : ''}><label class="form-check-label">الملاحظات إجبارية</label></div>`;
        }
        elements.propertiesContent.innerHTML = propertiesHTML;

        // ربط أحداث القائمة المنسدلة للصيغ
        const formulaInput = document.getElementById('formula-input');
        const refsDropdown = document.getElementById('refs-dropdown');
        if (formulaInput && refsDropdown) {
            formulaInput.addEventListener('focus', () => { refsDropdown.style.display = 'block'; });
            formulaInput.addEventListener('blur', () => { setTimeout(() => { refsDropdown.style.display = 'none'; }, 200); });
            refsDropdown.querySelectorAll('.formula-ref-item').forEach(item => {
                item.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    const ref = item.dataset.ref;
                    const start = formulaInput.selectionStart;
                    const end = formulaInput.selectionEnd;
                    const val = formulaInput.value;
                    formulaInput.value = val.substring(0, start) + ref + val.substring(end);
                    formulaInput.focus();
                    formulaInput.selectionStart = formulaInput.selectionEnd = start + ref.length;
                    // تحديث القيمة في البيانات
                    if (selectedElement) {
                        selectedElement.formula = formulaInput.value;
                        updateHiddenFields();
                    }
                });
            });
        }
    }

    function updateHiddenFields() {
        if (!elements.hiddenFieldsContainer) return;
        let html = '';
        formStructure.criteria.forEach((criterion, cIndex) => {
            html += `<input type="hidden" name="criteria[${cIndex}][name]" value="${encodeURIComponent(criterion.name)}"><input type="hidden" name="criteria[${cIndex}][description]" value="${encodeURIComponent(criterion.description)}"><input type="hidden" name="criteria[${cIndex}][weight]" value="${criterion.weight}">`;
            (criterion.sections || []).forEach((section, sIndex) => {
                html += `<input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][name]" value="${encodeURIComponent(section.name)}"><input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][description]" value="${encodeURIComponent(section.description)}"><input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][type]" value="${section.sectionType}"><input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][weight]" value="${section.weight}"><input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][min_value]" value="${section.min_value ?? ''}"><input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][max_value]" value="${section.max_value ?? ''}">`;
                const optionsJson = JSON.stringify(section.options || []);
                html += `<input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][options]" value='${encodeURIComponent(optionsJson)}'>`;
                html += `<input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][allow_notes]" value="${section.allowNotes ? 1 : 0}"><input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][notes_required]" value="${section.notesRequired ? 1 : 0}"><input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][notes_placeholder]" value="${encodeURIComponent(section.notesPlaceholder)}"><input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][formula]" value="${encodeURIComponent(section.formula || '')}"><input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][formula_display]" value="${encodeURIComponent(section.formulaDisplay || '')}"><input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][readonly]" value="${section.readonly ? 1 : 0}"><input type="hidden" name="criteria[${cIndex}][sections][${sIndex}][auto_source]" value="${section.autoSource || ''}">`;
            });
        });
        elements.hiddenFieldsContainer.innerHTML = html;
    }

    // 4. إعداد مستمعي الأحداث
    
    // ▼▼▼ بداية التعديل 3: تحديث دالة setupEventListeners بالكامل ▼▼▼
    function setupEventListeners() {
        // --- 1. إعداد السحب من مكتبة العناصر (لا تغيير هنا) ---
        elements.draggableElements.forEach(el => {
            el.addEventListener('dragstart', e => {
                e.dataTransfer.setData('source', 'library'); // مصدر السحب: المكتبة
                e.dataTransfer.setData('type', e.target.dataset.type);
                if (e.target.dataset.sectionType) {
                    e.dataTransfer.setData('section-type', e.target.dataset.sectionType);
                }
                e.dataTransfer.effectAllowed = 'copy';
            });
        });

        // --- 2. إعداد السحب من مساحة العمل (المنطق الجديد) ---
        // نستخدم تفويض الأحداث للتعامل مع العناصر التي يتم إنشاؤها ديناميكياً
        elements.workspaceCanvas.addEventListener('dragstart', e => {
            const criterionEl = e.target.closest('.criterion-wrapper');
            const sectionEl = e.target.closest('.section-item');

            if (criterionEl) {
                e.dataTransfer.setData('source', 'workspace'); // مصدر السحب: مساحة العمل
                e.dataTransfer.setData('type', 'criterion');
                e.dataTransfer.setData('elementId', criterionEl.dataset.id);
                e.dataTransfer.effectAllowed = 'copy';
            } else if (sectionEl) {
                e.dataTransfer.setData('source', 'workspace'); // مصدر السحب: مساحة العمل
                e.dataTransfer.setData('type', 'section');
                e.dataTransfer.setData('elementId', sectionEl.dataset.id);
                e.dataTransfer.effectAllowed = 'copy';
            }
        });

        // --- 3. إعداد الإفلات في مساحة العمل (المنطق المحدث) ---
        elements.workspaceCanvas.addEventListener('dragover', e => {
            e.preventDefault(); 
            const dropZone = e.target.closest('.drop-zone');
            if (dropZone) dropZone.classList.add('drag-over');
        });

        elements.workspaceCanvas.addEventListener('dragleave', e => {
            const dropZone = e.target.closest('.drop-zone');
            if (dropZone) dropZone.classList.remove('drag-over');
        });

        elements.workspaceCanvas.addEventListener('drop', e => {
            e.preventDefault();
            const dropZone = e.target.closest('.drop-zone');
            if (!dropZone) return;
            
            dropZone.classList.remove('drag-over');
            const source = e.dataTransfer.getData('source');
            const type = e.dataTransfer.getData('type');

            if (source === 'library') {
                // منطق الإنشاء من المكتبة (كما كان)
                if (dropZone.id === 'mainDropZone' && type === 'criterion') {
                    addCriterion();
                } else if (dropZone.classList.contains('section-drop-zone') && type === 'section') {
                    const criterionId = dropZone.dataset.criterionId;
                    const sectionType = e.dataTransfer.getData('section-type');
                    addSection(criterionId, sectionType);
                }
            } else if (source === 'workspace') {
                // منطق النسخ من مساحة العمل (الجديد)
                const elementId = e.dataTransfer.getData('elementId');
                if (dropZone.id === 'mainDropZone' && type === 'criterion') {
                    cloneCriterion(elementId);
                } else if (dropZone.classList.contains('section-drop-zone') && type === 'section') {
                    const targetCriterionId = dropZone.dataset.criterionId;
                    cloneSection(elementId, targetCriterionId);
                }
            }
        });

        // --- 4. بقية مستمعي الأحداث (تبقى كما هي) ---
        elements.workspaceCanvas.addEventListener('click', function(e) {
            const criterionWrapper = e.target.closest('.criterion-wrapper');
            const sectionItem = e.target.closest('.section-item');
            const deleteCriterionBtn = e.target.closest('.delete-criterion');
            const deleteSectionBtn = e.target.closest('.delete-section');

            if (deleteCriterionBtn) { e.stopPropagation(); const id = deleteCriterionBtn.closest('.criterion-wrapper').dataset.id; if (confirm('هل أنت متأكد من حذف هذا المعيار وكل أقسامه؟')) deleteCriterion(id); return; }
            if (deleteSectionBtn) { e.stopPropagation(); const id = deleteSectionBtn.closest('.section-item').dataset.id; if (confirm('هل أنت متأكد من حذف هذا القسم؟')) deleteSection(id); return; }
            if (sectionItem) { const id = sectionItem.dataset.id; const elementData = formStructure.criteria.flatMap(c => c.sections).find(s => s.id === id); selectElement(elementData); } 
            else if (criterionWrapper) { const id = criterionWrapper.dataset.id; const elementData = formStructure.criteria.find(c => c.id === id); selectElement(elementData); }
        });

        elements.propertiesContent.addEventListener('change', function(e) {
            if (!selectedElement) return;
            let originalElement = null;
            if (selectedElement.type === 'criterion') { originalElement = formStructure.criteria.find(c => c.id === selectedElement.id); } 
            else if (selectedElement.type === 'section') { for (const criterion of formStructure.criteria) { originalElement = criterion.sections.find(s => s.id === selectedElement.id); if (originalElement) break; } }
            if (!originalElement) return;

            const target = e.target;
            const prop = target.dataset.prop;
            const value = target.type === 'checkbox' ? target.checked : target.value;

            if (target.classList.contains('option-label') || target.classList.contains('option-value')) {
                const index = parseInt(target.dataset.index, 10);
                const optionProp = target.classList.contains('option-label') ? 'label' : 'value';
                if (originalElement.options && originalElement.options[index]) { originalElement.options[index][optionProp] = value; }
            } else if (prop) {
                originalElement[prop] = value;
                if (prop === 'weight') { handleManualWeightChange(originalElement.id, value, originalElement.type === 'criterion'); }
                if (prop === 'sectionType') { originalElement.name = `قسم جديد (${getSectionTypeName(value)})`; renderPropertiesPanel(); }
            }
            updateUI();
        });

        elements.propertiesContent.addEventListener('click', function(e) {
            if (!selectedElement || selectedElement.type !== 'section' || selectedElement.sectionType !== 'grade') return;
            if (e.target.id === 'add-option-btn') { e.preventDefault(); if (!selectedElement.options) selectedElement.options = []; selectedElement.options.push({ label: '', value: 0 }); renderPropertiesPanel(); }
            const removeBtn = e.target.closest('.remove-option-btn');
            if (removeBtn) { e.preventDefault(); const index = parseInt(removeBtn.dataset.index, 10); if (selectedElement.options && selectedElement.options[index] !== undefined) { selectedElement.options.splice(index, 1); renderPropertiesPanel(); } }
        });

        elements.autoDistributeToggle.addEventListener('change', () => {
            if (elements.autoDistributeToggle.checked) {
                handleAutomaticWeightDistribution();
                formStructure.criteria.forEach(criterion => { handleAutomaticWeightDistribution(criterion); });
                updateUI();
            }
        });

        const builderForm = document.getElementById('builderForm');
        if (builderForm) {
            builderForm.addEventListener('submit', function(e) {
                updateHiddenFields();
                const totalWeight = formStructure.criteria.reduce((sum, c) => sum + parseFloat(c.weight), 0);
                if (formStructure.criteria.length > 0 && Math.abs(totalWeight - 100) > 0.01) { e.preventDefault(); alert('لا يمكن حفظ الاستمارة. يجب أن يكون إجمالي وزن المعايير 100%. المجموع الحالي هو: ' + totalWeight.toFixed(2) + '%'); return; }
                for(const criterion of formStructure.criteria) {
                    const sectionsWeightSum = criterion.sections.reduce((sum, s) => sum + parseFloat(s.weight), 0);
                    if (criterion.sections.length > 0 && Math.abs(sectionsWeightSum - 100) > 0.01) { e.preventDefault(); alert(`خطأ في معيار "${criterion.name}": يجب أن يكون مجموع أوزان الأقسام 100%. المجموع الحالي هو: ${sectionsWeightSum.toFixed(2)}%`); return; }
                }
            });
        }
    }
    // ▲▲▲ نهاية التعديل 3 ▲▲▲

    // 5. التهيئة الأولية (لا تغيير هنا)
    function initializeBuilder() {
        if (isEditing && initialFormStructure) {
            const cleanStructure = { criteria: [] };
            if (initialFormStructure.criteria && Array.isArray(initialFormStructure.criteria)) {
                cleanStructure.criteria = initialFormStructure.criteria.map(serverCriterion => {
                    const cleanCriterion = { id: `c_${serverCriterion.id}`, type: 'criterion', name: serverCriterion.criterion_name, description: serverCriterion.criterion_description || '', weight: parseFloat(serverCriterion.criterion_weight), sections: [] };
                    if (serverCriterion.sections && Array.isArray(serverCriterion.sections)) {
                        cleanCriterion.sections = serverCriterion.sections.map(serverSection => {
                            let parsedOptions = [];
                            try { parsedOptions = JSON.parse(serverSection.evaluation_options || '[]') || []; } catch (e) { parsedOptions = []; }
                            parsedOptions = parsedOptions.map(opt => (typeof opt === 'string' ? { label: opt, value: '' } : opt));
                            let parsedFormula = {};
                            try { parsedFormula = JSON.parse(serverSection.formula_config || '{}') || {}; } catch (e) { parsedFormula = {}; }
                            return { id: `s_${serverSection.id}`, type: 'section', sectionType: serverSection.evaluation_type, name: serverSection.section_name, description: serverSection.section_description || '', weight: parseFloat(serverSection.section_weight), allowNotes: !!parseInt(serverSection.allow_notes), notesRequired: !!parseInt(serverSection.notes_required), notesPlaceholder: serverSection.notes_placeholder || 'اكتب ملاحظاتك هنا...', options: parsedOptions, min_value: serverSection.min_value, max_value: serverSection.max_value, formula: parsedFormula.formula || '', formulaDisplay: parsedFormula.display || '', readonly: !!parsedFormula.readonly, autoSource: parsedFormula.auto_source || null, isHelper: serverSection.evaluation_type === 'helper' };
                        });
                    }
                    return cleanCriterion;
                });
            }
            formStructure = cleanStructure;
            const criterionIds = (initialFormStructure.criteria || []).map(c => parseInt(c.id));
            if (criterionIds.length > 0) criterionCounter = Math.max(0, ...criterionIds);
            const sectionIds = (initialFormStructure.criteria || []).flatMap(c => (c.sections || []).map(s => parseInt(s.id)));
            if (sectionIds.length > 0) sectionCounter = Math.max(0, ...sectionIds);
        }
        updateUI();
        setupEventListeners();
    }

    function getSectionTypeName(type) {
        const names = { percentage: 'نسبة مئوية', numeric: 'رقمي', grade: 'تقدير', boolean: 'نعم/لا', formula: 'صيغة حسابية', auto: 'تلقائي', helper: 'حقل مساعد' };
        return names[type] || 'قسم';
    }

    initializeBuilder();
});
