/**
 * الملف الرئيسي لـ JavaScript في التطبيق
 */
/**
 * الملف الرئيسي لـ JavaScript في التطبيق
 */

// ==================================================
//           ===>  أضف الدالة التالية هنا  <===
// ==================================================
/**
 * دالة مساعدة لعرض رسائل Toast باستخدام SweetAlert2.
 * @param {string} type - نوع الرسالة ('success', 'error', 'warning', 'info').
 * @param {string} message - نص الرسالة الذي سيظهر.
 */
function getCsrfToken(){return document.querySelector('meta[name="csrf-token"]')?.content || '';}
function showToast(type, message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-start', // ستظهر في أعلى اليسار (لأن الصفحة عربية)
        showConfirmButton: false,
        timer: 3000, // مدة ظهور الرسالة بالمللي ثانية (3 ثواني)
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: type,
        title: message
    });
}



// ننتظر حتى يتم تحميل كامل محتوى الصفحة (DOM) قبل تنفيذ الكود.
document.addEventListener('DOMContentLoaded', function() {

    // ==================================================
    //           معالجات خاصة بصفحة الموظفين
    // ==================================================
    const deleteEmployeeButtons = document.querySelectorAll('.js-delete-employee');
    deleteEmployeeButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const employeeId = this.dataset.id;
            const employeeName = this.dataset.name;
            if (confirm("�� ��� ����� �� ��� ���� ��� ������ \"" + employeeName + "\"�")) {
                submitDeleteForm('/employees/delete', employeeId);
            }
        });
    });

// في ملف public/js/main.js داخل document.addEventListener('DOMContentLoaded', function() { ... });

// ==================================================
//        معالجات خاصة بصفحة إدارة المناصب
// ==================================================
const editPositionModalElement = document.getElementById('editPositionModal');
if (editPositionModalElement) {
    const editPositionModal = new bootstrap.Modal(editPositionModalElement);
    const editPositionForm = document.getElementById('editPositionForm');

    // معالج فتح نافذة التعديل
    document.querySelectorAll('.js-edit-position').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const title = this.dataset.title;
            const category = this.dataset.category;

            // ملء حقول النموذج في النافذة المنبثقة
            editPositionForm.querySelector('#edit-id').value = id;
            editPositionForm.querySelector('#edit-title').value = title;
            editPositionForm.querySelector('#edit-category').value = category;

            // فتح النافذة
            editPositionModal.show();
        });
    });
}

// معالج زر الحذف
document.querySelectorAll('.js-delete-position').forEach(button => {
    button.addEventListener('click', function() {
        const positionId = this.dataset.id;
        const positionTitle = this.dataset.title;

        // استخدام SweetAlert2 لرسالة تأكيد احترافية
        Swal.fire({
            title: "�� ��� ����Ͽ",
            text: "���� ��� ������ \"" + positionTitle + "\"! �� ���� ������� �� ��� �������.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '��� �����!',
            cancelButtonText: '�����'
        }).then((result) => {
            if (result.isConfirmed) {
                // إذا أكد المستخدم، قم بإنشاء فورم وإرساله
              submitDeleteForm('/employees/positions/delete', positionId);
            }
        });
    });
});

// ملاحظة: تأكد من أن دالة submitDeleteForm موجودة في ملف main.js
// إذا لم تكن موجودة، أضفها:
function submitDeleteForm(actionUrl, id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = actionUrl;

    const hiddenField = document.createElement('input');
    hiddenField.type = 'hidden';
    hiddenField.name = 'id';
    hiddenField.value = id;
    form.appendChild(hiddenField);
    const csrfTokenField = document.createElement('input'); csrfTokenField.type = 'hidden'; csrfTokenField.name = '_token'; csrfTokenField.value = getCsrfToken(); form.appendChild(csrfTokenField);
    document.body.appendChild(form);
    form.submit();
}

    // ==================================================
    //           معالجات خاصة بصفحة الفروع
    // ==================================================
    const editBranchModalElement = document.getElementById('editBranchModal');
    // ===> التحقق من وجود العنصر أولاً <===
    if (editBranchModalElement) {
        const editBranchModal = new bootstrap.Modal(editBranchModalElement);
        const editBranchButtons = document.querySelectorAll('.js-edit-branch');
        const editBranchForm = document.getElementById('editBranchForm');

        editBranchButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const location = this.dataset.location;
                const managerId = this.dataset.managerId;

                editBranchForm.querySelector('#edit-id').value = id;
                editBranchForm.querySelector('#edit-name').value = name;
                editBranchForm.querySelector('#edit-location').value = location;
                editBranchForm.querySelector('#edit-manager_id').value = managerId;

                editBranchModal.show();
            });
        });
    }

    const deleteBranchButtons = document.querySelectorAll('.js-delete-branch');
    deleteBranchButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const branchId = this.dataset.id;
            const branchName = this.dataset.name;
            if (confirm("�� ��� ����� �� ��� ���� ��� ����� \"" + branchName + "\"�")) {
                submitDeleteForm('/branches/delete', branchId);
            }
        });
    });


    // ==================================================
    //           معالجات خاصة بصفحة الأقسام
    // ==================================================
    const editDepartmentModalElement = document.getElementById('editDepartmentModal');
    // ===> التحقق من وجود العنصر أولاً <===
    if (editDepartmentModalElement) {
        const editDepartmentModal = new bootstrap.Modal(editDepartmentModalElement);
        const editDepartmentButtons = document.querySelectorAll('.js-edit-department');
        const editDepartmentForm = document.getElementById('editDepartmentForm');
        
        editDepartmentButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const branchId = this.dataset.branchId;

                editDepartmentForm.querySelector('#edit-id').value = id;
                editDepartmentForm.querySelector('#edit-name').value = name;
                editDepartmentForm.querySelector('#edit-branch_id').value = branchId;

                editDepartmentModal.show();
            });
        });
    }

    const deleteDepartmentButtons = document.querySelectorAll('.js-delete-department');
    deleteDepartmentButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const departmentId = this.dataset.id;
            const departmentName = this.dataset.name;
            if (confirm("�� ��� ����� �� ��� ���� ��� ����� \"" + departmentName + "\"�")) {
                submitDeleteForm('/departments/delete', departmentId);
            }
        });
    });


    /**
     * دالة مساعدة لإنشاء وإرسال نموذج الحذف.
     * هذا يمنع تكرار الكود.
     * @param {string} actionUrl - الرابط الذي سيتم إرسال الطلب إليه.
     * @param {number} id - معرف العنصر المراد حذفه.
     */
  // في ملف: public/js/main.js

/**
 * =================================================================
 * دالة مساعدة لإنشاء وإرسال نموذج الحذف (النسخة النهائية والمؤكدة)
 * =================================================================
 * @param {string} relativePath - المسار النسبي فقط (مثال: '/employees/delete').
 * @param {number} id - معرف العنصر المراد حذفه.
 */
function submitDeleteForm(relativePath, id) {
    const form = document.createElement('form');
    form.method = 'POST';
    
    // ===> الإصلاح الحاسم هنا <===
    // نستخدم متغير APP_URL العام لضمان بناء الرابط الصحيح دائمًا.
    form.action = APP_URL + relativePath;

    const hiddenField = document.createElement('input');
    hiddenField.type = 'hidden';
    hiddenField.name = 'id';
    hiddenField.value = id;
    form.appendChild(hiddenField);
    const csrfTokenField = document.createElement('input'); csrfTokenField.type = 'hidden'; csrfTokenField.name = '_token'; csrfTokenField.value = getCsrfToken(); form.appendChild(csrfTokenField);
    document.body.appendChild(form);
    form.submit();
}

// ==================================================
    //        معالجات خاصة بصفحة قوالب التقييم
    // ==================================================
    const editTemplateModalElement = document.getElementById('editTemplateModal');
    if (editTemplateModalElement) {
        const editTemplateModal = new bootstrap.Modal(editTemplateModalElement);
        const editTemplateButtons = document.querySelectorAll('.js-edit-template');
        const editTemplateForm = document.getElementById('editTemplateForm');

        editTemplateButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                
                const id = this.dataset.id;
                const name = this.dataset.name;
                const description = this.dataset.description;
                const status = this.dataset.status;

                editTemplateForm.querySelector('#edit-id').value = id;
                editTemplateForm.querySelector('#edit-name').value = name;
                editTemplateForm.querySelector('#edit-description').value = description;
                editTemplateForm.querySelector('#edit-status').value = status;

                editTemplateModal.show();
            });
        });
    }

  const deleteTemplateButtons = document.querySelectorAll('.js-delete-template');

deleteTemplateButtons.forEach(button => {
    button.addEventListener('click', function(event) {
        event.preventDefault(); // منع السلوك الافتراضي

        const templateId = this.dataset.id;
        const templateName = this.dataset.name;
        const rootUrl = typeof APP_URL !== 'undefined' ? APP_URL : (typeof BASE_URL !== 'undefined' ? BASE_URL : '');

        if (confirm("�� ��� ����� �� ��� ���� ��� ������ \"" + templateName + "\"�")) {
            
            // 1. إنشاء نموذج جديد مخصص لهذه العملية فقط
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `${rootUrl}/templates/delete`; // استخدام الرابط الصحيح
            form.style.display = 'none';

            // 2. إنشاء حقل مخفي بالاسم الصحيح
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'template_id'; // <-- هذا هو الإصلاح الحاسم
            hiddenInput.value = templateId;
            form.appendChild(hiddenInput);
            const csrf2 = document.createElement('input'); csrf2.type='hidden'; csrf2.name='_token'; csrf2.value=getCsrfToken(); form.appendChild(csrf2);

            // 3. ����� ����� �������

            // 4. ����� ������� ������ �������
            document.body.appendChild(form);
            form.submit();
        }
    });
});
    
    // ==================================================
    //        معالجات خاصة بصفحة إدارة المعايير
    // ==================================================
    const criterionFormElement = document.getElementById('criterion-form');
    // ===> التحقق من وجود نموذج المعايير في الصفحة الحالية <===
    if (criterionFormElement) {
        const formTitle = document.getElementById('criterion-form-title');
        const submitBtnText = document.getElementById('criterion-submit-btn-text');
        const cancelBtn = document.getElementById('cancel-edit-btn');
        const criterionIdField = document.getElementById('criterion-id');
        const criterionNameField = document.getElementById('criterion-name');
        const criterionWeightField = document.getElementById('criterion-weight');

        /**
         * معالجة تعديل المعيار (Criterion Edit Handler)
         */
        const editCriterionButtons = document.querySelectorAll('.btn-edit-criterion');
        editCriterionButtons.forEach(button => {
            button.addEventListener('click', function() {
                // 1. جلب البيانات من الزر الذي تم الضغط عليه.
                const id = this.dataset.id;
                const name = this.dataset.name;
                const weight = this.dataset.weight;

                // 2. تغيير عنوان وزر النموذج ليعكس وضع "التعديل".
                formTitle.innerHTML = '<i class="fas fa-edit"></i> تعديل المعيار';
                submitBtnText.textContent = 'حفظ التعديلات';
                criterionFormElement.action = '/employee-evaluation/criteria/update'; // تغيير مسار الإرسال
                cancelBtn.style.display = 'inline-block'; // إظهار زر الإلغاء

                // 3. ملء حقول النموذج بالبيانات الحالية للمعيار.
                criterionIdField.value = id;
                criterionNameField.value = name;
                criterionWeightField.value = weight;

                // 4. نقل التركيز (focus) إلى أول حقل في النموذج.
                criterionNameField.focus();
            });
        });

        /**
         * معالجة إلغاء التعديل
         */
        cancelBtn.addEventListener('click', function() {
            // 1. إعادة النموذج إلى وضع "الإضافة".
            formTitle.innerHTML = '<i class="fas fa-plus-circle"></i> إضافة معيار جديد';
            submitBtnText.textContent = 'حفظ المعيار';
            criterionFormElement.action = '/employee-evaluation/criteria'; // إعادة مسار الإرسال
            cancelBtn.style.display = 'none'; // إخفاء زر الإلغاء

            // 2. تفريغ حقول النموذج.
            criterionFormElement.reset();
            criterionIdField.value = '';
        });

        /**
         * معالجة حذف المعيار (Criterion Deletion Handler)
         */
        const deleteCriterionButtons = document.querySelectorAll('.btn-delete-criterion');
        deleteCriterionButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const templateId = criterionFormElement.querySelector('input[name="template_id"]').value;

                if (confirm("�� ��� ����� �� ��� ���� ��� ������� \"" + name + "\"�")) {
                    // لطلب الحذف، سننشئ نموذجاً ونرسله.
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/employee-evaluation/criteria/delete';

                    // إضافة حقل ID المعيار.
                    const idField = document.createElement('input');
                    idField.type = 'hidden';
                    idField.name = 'id';
                    idField.value = id;
                    form.appendChild(idField);

                    // إضافة حقل ID القالب (مهم لإعادة التوجيه).
                    const templateIdField = document.createElement('input');
                    templateIdField.type = 'hidden';
                    templateIdField.name = 'template_id';
                    templateIdField.value = templateId;
                    form.appendChild(templateIdField);
                    const csrf3 = document.createElement('input'); csrf3.type='hidden'; csrf3.name='_token'; csrf3.value=getCsrfToken(); form.appendChild(csrf3);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    }
    
    // ==================================================
    //        معالجات خاصة بصفحة إدارة الأقسام
    // ==================================================
    const sectionForm = document.getElementById('section-form');
    if (sectionForm) {
        const inputTypeSelect = document.getElementById('input-type');
        const numericOptions = document.getElementById('numeric-options');
        const choiceOptions = document.getElementById('choice-options');

        function toggleSectionOptions() {
            const selectedType = inputTypeSelect.value;
            
            // إخفاء كل الحقول الديناميكية أولاً
            numericOptions.style.display = 'none';
            choiceOptions.style.display = 'none';

            // إظهار الحقل المناسب بناءً على الاختيار
            if (selectedType === 'numeric') {
                numericOptions.style.display = 'block';
            } else if (selectedType === 'single_choice' || selectedType === 'multi_choice') {
                choiceOptions.style.display = 'block';
            }
        }

        // استدعاء الدالة عند تحميل الصفحة لتحديد الحالة الأولية
        toggleSectionOptions();
        // استدعاء الدالة عند كل تغيير في القائمة المنسدلة
        inputTypeSelect.addEventListener('change', toggleSectionOptions);
    }

      // ==================================================
    //        معالجات خاصة بصفحة إدارة الأقسام
    // ==================================================
    const sectionFormElement = document.getElementById('section-form');
    if (sectionFormElement) {
        const formTitle = sectionFormElement.parentElement.previousElementSibling.querySelector('h3');
        const submitBtn = sectionFormElement.querySelector('button[type="submit"]');
        const cancelBtn = document.createElement('button'); // سننشئ زر الإلغاء ديناميكياً
        cancelBtn.type = 'button';
        cancelBtn.className = 'btn btn-secondary';
        cancelBtn.textContent = 'إلغاء التعديل';
        cancelBtn.style.display = 'none';
        submitBtn.parentElement.appendChild(cancelBtn);

        const sectionIdField = document.createElement('input');
        sectionIdField.type = 'hidden';
        sectionIdField.name = 'id';
        sectionFormElement.prepend(sectionIdField);

        const inputTypeSelect = document.getElementById('input-type');
        const numericOptions = document.getElementById('numeric-options');
        const choiceOptions = document.getElementById('choice-options');

        function toggleSectionOptions() { /* ... (الدالة تبقى كما هي) ... */ }
        toggleSectionOptions();
        inputTypeSelect.addEventListener('change', toggleSectionOptions);

        // ===> منطق التعديل الجديد هنا <===
        const editSectionButtons = document.querySelectorAll('.btn-edit-section');
        editSectionButtons.forEach(button => {
            button.addEventListener('click', function() {
                const data = this.dataset;

                // 1. تغيير النموذج إلى وضع "التعديل"
                formTitle.innerHTML = '<i class="fas fa-edit"></i> تعديل القسم';
                submitBtn.innerHTML = '<i class="fas fa-save"></i> حفظ التعديلات';
                sectionFormElement.action = '/employee-evaluation/sections/update';
                cancelBtn.style.display = 'inline-block';

                // 2. ملء حقول النموذج بالبيانات
                sectionIdField.value = data.id;
                sectionFormElement.querySelector('#section-name').value = data.name;
                sectionFormElement.querySelector('#input-type').value = data.inputType;
                sectionFormElement.querySelector('#max-value').value = data.maxValue;
                sectionFormElement.querySelector('#options-text').value = data.options;
                sectionFormElement.querySelector('#section-weight').value = data.weight;
                sectionFormElement.querySelector('#has_notes').checked = (data.hasNotes == '1');
                sectionFormElement.querySelector('#notes_required').checked = (data.notesRequired == '1');

                // 3. تحديث الحقول الديناميكية
                toggleSectionOptions();
                
                // 4. الانتقال إلى النموذج
                sectionFormElement.scrollIntoView({ behavior: 'smooth' });
            });
        });

        // ===> منطق إلغاء التعديل <===
        cancelBtn.addEventListener('click', function() {
            formTitle.innerHTML = '<i class="fas fa-plus-circle"></i> إضافة قسم جديد';
            submitBtn.innerHTML = '<i class="fas fa-save"></i> حفظ القسم';
            sectionFormElement.action = '/employee-evaluation/sections';
            cancelBtn.style.display = 'none';
            sectionFormElement.reset();
            sectionIdField.value = '';
            toggleSectionOptions();
        });
    }
        // ==================================================
    //        معالجات خاصة بصفحة إدارة المستخدمين
    // ==================================================
    const editUserRoleModalElement = document.getElementById('editUserRoleModal');
    // التحقق من وجود النافذة المنبثقة في الصفحة الحالية
    if (editUserRoleModalElement) {
        // إنشاء كائن Modal من Bootstrap للتحكم في النافذة
        const editUserRoleModal = new bootstrap.Modal(editUserRoleModalElement);
        // استهداف كل أزرار "تغيير الدور"
        const editUserRoleButtons = document.querySelectorAll('.js-edit-user-role');
        // استهداف نموذج التعديل
        const editUserRoleForm = document.getElementById('editUserRoleForm');

        // إضافة مستمع حدث لكل زر
        editUserRoleButtons.forEach(button => {
            button.addEventListener('click', function() {
                // 1. جلب البيانات من data attributes الخاصة بالزر الذي تم الضغط عليه
                const userId = this.dataset.userId;
                const username = this.dataset.username;
                const currentRoleId = this.dataset.currentRoleId;

                // 2. ملء حقول النموذج في النافذة المنبثقة بالبيانات
                editUserRoleForm.querySelector('#edit-user-id').value = userId;
                editUserRoleForm.querySelector('#username-placeholder').textContent = username;
                editUserRoleForm.querySelector('#edit-role-id').value = currentRoleId;

                // 3. فتح النافذة المنبثقة
                editUserRoleModal.show();
            });
        });
    }

    // ==================================================
    //        معالجات خاصة بصفحة إدارة الأدوار والصلاحيات
    // ==================================================
    
    // استهداف كل مربعات الاختيار التي لها الكلاس 'permission-checkbox'
    const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');

permissionCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const roleId = this.closest('.permission-form').dataset.roleId;
        const permissionId = this.value;
        const isChecked = this.checked;

        const label = this.nextElementSibling;
        const spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm ms-2';
        label.appendChild(spinner);
        this.disabled = true;

        fetch('/employee-evaluation/admin/roles/update-permission', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                role_id: roleId,
                permission_id: permissionId,
                is_checked: isChecked
            })
        })
        .then(response => {
            // دائماً قم بإزالة مؤشر التحميل وإعادة تفعيل المربع
            label.removeChild(spinner);
            this.disabled = false;
            
            // إذا لم تكن الاستجابة ناجحة، اعرض رسالة خطأ وأرجع المربع لحالته السابقة
            if (!response.ok) {
                this.checked = !isChecked; // إعادة المربع لحالته الأصلية
                // استدعاء دالة الـ Toast لعرض الخطأ
                showToast('error', 'فشل حفظ التغيير!');
                // إيقاف سلسلة الـ .then() لأننا لا نريد معالجة JSON غير موجود
                throw new Error('Server responded with an error.');
            }
            return response.json();
        })
        .then(data => {
            // إذا نجح الطلب، استدعاء دالة الـ Toast لعرض رسالة النجاح
            if (data.success) {
                showToast('success', 'تم حفظ التغيير');
            }
        })
        .catch(error => {
            // هذا الجزء سيعمل فقط في حالة فشل الشبكة أو إذا أوقفنا السلسلة يدوياً
            console.error('Fetch Error:', error);
            // نتأكد من أننا لا نعرض رسالة خطأ مرتين
            if (error.message !== 'Server responded with an error.') {
                showToast('error', 'فشل الاتصال بالخادم.');
            }
        });
    });
    });

    // في ملف /public/js/main.js داخل DOMContentLoaded

    // ... (بعد كود معالجة صلاحيات الأدوار)

    // ==================================================
    //        معالجات تعديل وحذف الأدوار
    // ==================================================
    const editRoleModalElement = document.getElementById('editRoleModal');
    if (editRoleModalElement) {
        const editRoleModal = new bootstrap.Modal(editRoleModalElement);
        const editRoleForm = document.getElementById('editRoleForm');

        // معالج فتح نافذة التعديل
        document.querySelectorAll('.js-edit-role').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const description = this.dataset.description;

                editRoleForm.querySelector('#edit-id').value = id;
                editRoleForm.querySelector('#edit-name').value = name;
                editRoleForm.querySelector('#edit-description').value = description;

                editRoleModal.show();
            });
        });
    }

    // معالج زر الحذف
    document.querySelectorAll('.js-delete-role').forEach(button => {
        button.addEventListener('click', function() {
            const roleId = this.dataset.id;
            const roleName = this.dataset.name;

            Swal.fire({
                title: "�� ��� ����Ͽ",
                text: "���� ��� ����� \"" + roleName + "\" ���� �����!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '��� �����!',
                cancelButtonText: '�����'
            }).then((result) => {
                if (result.isConfirmed) {
                    // إذا أكد المستخدم، قم بإنشاء فورم وإرساله
                    const form = document.createElement('form');
                    form.method = 'POST';
                  form.action = '/employee-evaluation/admin/roles/delete';
                    
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = 'id';
                    hiddenField.value = roleId;
                    form.appendChild(hiddenField);
                    const csrf4 = document.createElement('input'); csrf4.type='hidden'; csrf4.name='_token'; csrf4.value=getCsrfToken(); form.appendChild(csrf4);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
// ==================================================
//        معالج تغيير حالة المستخدم (تفعيل/تعليق)
// ==================================================
document.querySelectorAll('.js-toggle-status').forEach(button => {
    button.addEventListener('click', function() {
        const userId = this.dataset.userId;
        const username = this.dataset.username;
        const isActivating = this.classList.contains('btn-view');

        const actionText = isActivating ? '�����' : '�����';
        const confirmButtonText = isActivating ? '��� �� ��������' : '��� �� ��������';

        Swal.fire({
            title: "�� ��� ����Ͽ",
            text: "���� " + actionText + " ���� �������� \"" + username + "\".",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: isActivating ? '#28a745' : '#d33',
            confirmButtonText: confirmButtonText,
            cancelButtonText: '�����'
        }).then((result) => {
            if (result.isConfirmed) {
                // إرسال الطلب عبر AJAX
                fetch('/employee-evaluation/admin/users/toggle-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', data.message);
                        // إعادة تحميل الصفحة لعرض التغييرات (أبسط طريقة)
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('error', data.message || 'حدث خطأ ما.');
                    }
                })
                .catch(err => {
                    showToast('error', 'فشل الاتصال بالخادم.');
                    console.error(err);
                });
            }
        });
    });
});


// في ملف /public/js/main.js

// معالج زر حذف المستخدم
document.querySelectorAll('.js-delete-user').forEach(button => {
    button.addEventListener('click', function() {
        const userId = this.dataset.userId;
        const username = this.dataset.username;

        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: `سيتم حذف حساب المستخدم "${username}" نهائياً! لا يمكن التراجع عن هذا الإجراء.`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذفه!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/employee-evaluation/admin/users/delete';
                
                const hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = 'id';
                hiddenField.value = userId;
                form.appendChild(hiddenField);
                const csrf5 = document.createElement('input'); csrf5.type='hidden'; csrf5.name='_token'; csrf5.value=getCsrfToken(); form.appendChild(csrf5);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
// ==================================================
//        معالج إعادة تعيين كلمة المرور
// ==================================================
document.querySelectorAll('.js-reset-password').forEach(button => {
    button.addEventListener('click', function() {
        const userId = this.dataset.userId;
        const username = this.dataset.username;

        Swal.fire({
            title: 'إعادة تعيين كلمة المرور',
            text: `هل أنت متأكد من أنك تريد إنشاء كلمة مرور جديدة للمستخدم "${username}"؟`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'نعم، قم بالإنشاء',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                // إظهار مؤشر تحميل
                Swal.showLoading();

                fetch('/employee-evaluation/admin/users/reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // عرض كلمة المرور الجديدة في نافذة منبثقة
                        Swal.fire({
                            title: 'تم بنجاح!',
                            html: `
                                <p>تم إنشاء كلمة مرور جديدة للمستخدم <strong>${username}</strong>.</p>
                                <p>الرجاء نسخها وإعطائها للمستخدم. لن يتم عرضها مرة أخرى.</p>
                                <div style="background-color: #f0f0f0; border: 1px solid #ccc; border-radius: 5px; padding: 10px; margin-top: 15px; font-size: 1.2rem; font-weight: bold; letter-spacing: 2px;">
                                    ${data.newPassword}
                                </div>
                            `,
                            icon: 'success',
                            confirmButtonText: 'حسناً، فهمت'
                        });
                    } else {
                        showToast('error', data.message || 'حدث خطأ ما.');
                    }
                })
                .catch(err => {
                    Swal.close(); // إغلاق مؤشر التحميل
                    showToast('error', 'فشل الاتصال بالخادم.');
                    console.error(err);
                });
            }
        });
    });
});

// في ملف /public/js/main.js

function submitStatusForm(courseId, newStatus) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/employee-evaluation/training/courses/update-status';

    const idField = document.createElement('input');
    idField.type = 'hidden';
    idField.name = 'id';
    idField.value = courseId;
    form.appendChild(idField);

    const statusField = document.createElement('input');
    statusField.type = 'hidden';
    statusField.name = 'status';
    statusField.value = newStatus;
    form.appendChild(statusField);
    const csrf6 = document.createElement('input'); csrf6.type='hidden'; csrf6.name='_token'; csrf6.value=getCsrfToken(); form.appendChild(csrf6);
    document.body.appendChild(form);
    form.submit();
}
  /**
     * معالج القوائم المنسدلة المترابطة (الفروع والأقسام)
     * يعمل في صفحات إضافة/تعديل الدورات التدريبية.
     */
    const branchSelect = document.getElementById('branch_id');
    const departmentSelect = document.getElementById('department_id');

    // التحقق من وجود قائمة الفروع في الصفحة الحالية
    if (branchSelect && departmentSelect) {
        
        // إضافة مستمع حدث 'change' لقائمة الفروع
        branchSelect.addEventListener('change', function() {
            const branchId = this.value;

            // تعطيل قائمة الأقسام وإظهار رسالة "جاري التحميل..."
            departmentSelect.disabled = true;
            departmentSelect.innerHTML = '<option>جاري تحميل الأقسام...</option>';

            // إذا لم يتم اختيار فرع، أعد القائمة لوضعها الافتراضي
            if (!branchId) {
                departmentSelect.innerHTML = '<option value="">-- اختر قسماً (اختياري) --</option>';
                departmentSelect.disabled = false;
                return;
            }

            // إرسال طلب fetch إلى الـ API
            fetch(`/employee-evaluation/api/departments/by-branch/${branchId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('فشل الاتصال بالخادم');
                    }
                    return response.json();
                })
                .then(departments => {
                    // تفريغ القائمة الحالية
                    departmentSelect.innerHTML = '<option value="">-- اختر قسماً (اختياري) --</option>';

                    // ملء القائمة بالخيارات الجديدة
                    departments.forEach(department => {
                        const option = document.createElement('option');
                        option.value = department.id;
                        option.textContent = department.name;
                        departmentSelect.appendChild(option);
                    });

                    // إعادة تفعيل القائمة
                    departmentSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching departments:', error);
                    departmentSelect.innerHTML = '<option value="">-- حدث خطأ --</option>';
                    // لا تقم بإعادة تفعيل القائمة في حالة الخطأ
                });
        });
    }
    
// في ملف /public/js/main.js
   // =================================================================
    //        معالج لوحة معلومات أداء التدريب (مع وظيفة الطباعة)
    // =================================================================
    const branchesAccordion = document.getElementById('branchesAccordion');

    if (branchesAccordion) {
        // --- 1. إضافة زر الطباعة الشامل ---
        const pageHeader = document.querySelector('.page-header');
        if (pageHeader) {
            const printButtonHtml = `
                <div class="no-print mb-3 text-start">
                    <button id="print-global-report-btn" class="btn btn-primary">
                        <i class="fas fa-print me-2"></i>طباعة التقرير الشامل
                    </button>
                </div>`;
            pageHeader.insertAdjacentHTML('afterend', printButtonHtml);
        }

        // --- 2. التعامل مع فتح أكورديون الفروع (لجلب الأقسام) ---
        branchesAccordion.addEventListener('show.bs.collapse', function (event) {
            const accordionBody = event.target.querySelector('.accordion-body');
            const branchId = event.target.id.replace('collapse-', '');

            if (accordionBody.dataset.loaded === 'true') return;

            accordionBody.innerHTML = `<div class="text-center p-4"><div class="spinner-border text-primary"></div><p class="mt-2">جاري تحميل بيانات الأقسام...</p></div>`;
            
            fetch(`${BASE_URL}/api/reports/departments-by-branch/${branchId}`)
                .then(response => response.json())
                .then(departments => {
                    accordionBody.dataset.loaded = 'true';
                    if (departments.length === 0) {
                        accordionBody.innerHTML = '<div class="alert alert-secondary text-center">لا توجد أقسام مقيمة في هذا الفرع.</div>';
                        return;
                    }

                    let departmentsHtml = `
                        <div class="text-start mb-3 no-print">
                            <button class="btn btn-outline-secondary btn-sm print-branch-report-btn" data-branch-id="${branchId}">
                                <i class="fas fa-print me-2"></i>طباعة تقرير هذا الفرع
                            </button>
                        </div>
                        <div class="accordion" id="departmentsAccordion-${branchId}">`;

                    departments.forEach(dept => {
                        departmentsHtml += `
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dept-collapse-${dept.department_id}" onclick="loadCoursesForDashboard(${dept.department_id})">
                                        <div class="d-flex justify-content-between w-100 pe-4">
                                            <span><i class="fas fa-sitemap me-2"></i>${dept.department_name}</span>
                                            <span class="badge bg-success rounded-pill">متوسط الأداء: ${parseFloat(dept.avg_final_score || 0).toFixed(2)}%</span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="dept-collapse-${dept.department_id}" class="accordion-collapse collapse" data-bs-parent="#departmentsAccordion-${branchId}">
                                    <div class="accordion-body" id="courses-body-${dept.department_id}" data-department-id="${dept.department_id}">
                                        <div class="text-center p-2"><div class="spinner-border spinner-border-sm"></div></div>
                                    </div>
                                </div>
                            </div>`;
                    });
                    departmentsHtml += '</div>';
                    accordionBody.innerHTML = departmentsHtml;
                })
                .catch(error => {
                    console.error('Error fetching departments:', error);
                    accordionBody.innerHTML = '<div class="alert alert-danger">حدث خطأ أثناء تحميل بيانات الأقسام.</div>';
                });
        });

        // --- 3. التعامل مع جميع أزرار الطباعة باستخدام تفويض الأحداث ---
        document.body.addEventListener('click', function(event) {
            // التأكد من أن دالة الطباعة موجودة (تم تحميلها من الملف الآخر)
            if (typeof fetchAndPrintReport !== 'function') return;

            // زر الطباعة الشامل
            if (event.target.id === 'print-global-report-btn') {
                fetchAndPrintReport('company');
            }

            // زر طباعة الفرع
            const printBranchBtn = event.target.closest('.print-branch-report-btn');
            if (printBranchBtn) {
                fetchAndPrintReport('branch', printBranchBtn.dataset.branchId);
            }

            // زر طباعة القسم
            const printDeptBtn = event.target.closest('.print-department-report-btn');
            if (printDeptBtn) {
                fetchAndPrintReport('department', printDeptBtn.dataset.departmentId);
            }
        });
    }
    // =================================================================
//   معالج لوحة معلومات الورش (Workshops Dashboard Handler)
//   هذا الكود مخصص فقط لصفحة تقارير الورش
// =================================================================
// =================================================================
//   معالج لوحة معلومات الورش (النسخة النهائية مع أزرار الطباعة)
// =================================================================
const workshopsBranchesAccordion = document.getElementById('workshopsBranchesAccordion');

if (workshopsBranchesAccordion) {

    workshopsBranchesAccordion.addEventListener('show.bs.collapse', function (event) {
        const collapsePanel = event.target; 
        const accordionBody = collapsePanel.querySelector('.accordion-body');
        const button = document.querySelector(`[data-bs-target="#${collapsePanel.id}"]`);
        
        if (!button) return;
        const branchId = button.dataset.branchId;
        if (!branchId || accordionBody.dataset.loaded === 'true') return;

        accordionBody.innerHTML = `<div class="text-center p-4"><div class="spinner-border text-primary"></div><p class="mt-2">جاري تحميل بيانات الأقسام...</p></div>`;
        
        fetch(`${BASE_URL}/api/reports/workshops-by-branch/${branchId}`)
            .then(response => response.json())
            .then(departments => {
                accordionBody.dataset.loaded = 'true';

                if (!departments || departments.length === 0) {
                    accordionBody.innerHTML = '<div class="alert alert-secondary text-center">لا توجد أقسام لديها ورش عمل مقيّمة في هذا الفرع.</div>';
                    return;
                }

                // ===> إضافة زر طباعة الفرع هنا <===
                let departmentsHtml = `
                    <div class="text-start mb-3 no-print">
                        <button class="btn btn-outline-secondary btn-sm js-print-workshop-btn" data-level="branch" data-id="${branchId}">
                            <i class="fas fa-print me-2"></i>طباعة تقرير هذا الفرع
                        </button>
                    </div>
                    <div class="accordion" id="departmentsAccordion-${branchId}">`;

                departments.forEach(dept => {
                    const avgScore = parseFloat(dept.average_score || 0).toFixed(1);
                    departmentsHtml += `
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dept-collapse-${dept.id}" onclick="loadWorkshops(${dept.id})">
                                    <div class="d-flex justify-content-between w-100 pe-4">
                                        <span><i class="fas fa-sitemap me-2"></i>${dept.name}</span>
                                        <span class="badge bg-success rounded-pill">متوسط الأداء: ${avgScore}%</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="dept-collapse-${dept.id}" class="accordion-collapse collapse" data-bs-parent="#departmentsAccordion-${branchId}">
                                <div class="accordion-body" id="workshops-dept-${dept.id}">
                                    <div class="text-center p-2"><div class="spinner-border spinner-border-sm"></div></div>
                                </div>
                            </div>
                        </div>`;
                });

                departmentsHtml += '</div>';
                accordionBody.innerHTML = departmentsHtml;
            })
            .catch(error => {
                console.error('Error fetching workshop departments:', error);
                accordionBody.innerHTML = '<div class="alert alert-danger text-center">حدث خطأ فادح أثناء جلب البيانات.</div>';
            });
    });

    // ===> إضافة معالج الطباعة الخاص بالورش <===
    document.body.addEventListener('click', function(event) {
        // ابحث عن أقرب زر طباعة تم الضغط عليه
        const printBtn = event.target.closest('.js-print-workshop-btn');
        if (printBtn) {
            const level = printBtn.dataset.level;
            const id = printBtn.dataset.id;
            
            // بناء الرابط لصفحة الطباعة
            let url = `${BASE_URL}/reports/workshops/print?level=${level}`;
            if (id) {
                url += `&id=${id}`;
            }
            // فتح الرابط في نافذة جديدة
            window.open(url, '_blank');
        }
    });
}
// (الكود السابق في الملف يبقى كما هو)
// ...

// في نهاية الملف، استبدل الجزء الخاص بـ "معالجات خاصة بصفحة إضافة موظف جديد" بالكود التالي:

// ==================================================
//        معالجات خاصة بصفحة إضافة موظف جديد (النسخة النهائية المصححة)
// ==================================================
const addEmployeeForm = document.getElementById('add-employee-form');
if (addEmployeeForm) {
    const btnSaveAndEvaluate = document.getElementById('btn-save-and-evaluate');
    const evaluationSection = document.getElementById('evaluation-section');
    const submitAndEvaluateBtn = document.getElementById('submit-and-evaluate');
    const positionSelect = document.getElementById('position_id');
    const templateSelect = document.getElementById('template_id');
    const saveOnlyBtn = addEmployeeForm.querySelector('button[value="save"]');

    // 1. التعامل مع زر "حفظ وبدء التقييم"
    if (btnSaveAndEvaluate) {
        btnSaveAndEvaluate.addEventListener('click', function() {
            // إظهار قسم التقييم
            evaluationSection.style.display = 'block';
           // ===>  أضف الكلاس الخاص بالتنسيق <===
        submitAndEvaluateBtn.classList.add('btn-submit-form');
            // إخفاء الأزرار القديمة
            this.style.display = 'none';
            saveOnlyBtn.style.display = 'none';

            // إظهار زر الإرسال الفعلي لعملية "حفظ وتقييم"
            submitAndEvaluateBtn.style.display = '';
            submitAndEvaluateBtn.classList.add('btn-save');
            
            // جعل حقول التقييم مطلوبة
            document.getElementById('evaluator_employee_id').required = true;
            document.getElementById('evaluation_date').required = true;
            if (templateSelect) templateSelect.required = true;

            // جلب القوالب فوراً إذا كان المنصب محدداً
            if (positionSelect && positionSelect.value) {
                fetchTemplatesForPosition(positionSelect.value);
            }
        });
    }

    // 2. جلب القوالب عند تغيير المنصب الوظيفي
    if (positionSelect) {
        positionSelect.addEventListener('change', function() {
            const positionId = this.value;
            fetchTemplatesForPosition(positionId);
        });
    }

    // 3. دالة لجلب القوالب
    async function fetchTemplatesForPosition(positionId) {
        if (!templateSelect) return;
        templateSelect.disabled = true;
        if (!positionId) {
            templateSelect.innerHTML = '<option value="">اختر المنصب الوظيفي أولاً...</option>';
            return;
        }
        templateSelect.innerHTML = '<option value="">جاري التحميل...</option>';
        
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
    }

    // 4. إعادة استخدام منطق البحث عن الموظف (المُقيِّم)
    const evaluatorSearchInput = document.getElementById('evaluator_employee_search');
    const evaluatorIdInput = document.getElementById('evaluator_employee_id');
    const evaluatorResultsContainer = document.getElementById('evaluator-search-results');
    let searchTimeout;

    if (evaluatorSearchInput) {
        evaluatorSearchInput.addEventListener('keyup', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value;
            if (query.length < 2) {
                if (evaluatorResultsContainer) evaluatorResultsContainer.style.display = 'none';
                return;
            }
            searchTimeout = setTimeout(() => searchEmployees(query, evaluatorResultsContainer), 300);
        });

        if (evaluatorResultsContainer) {
            evaluatorResultsContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('search-result-item') && e.target.dataset.id) {
                    evaluatorIdInput.value = e.target.dataset.id;
                    evaluatorSearchInput.value = e.target.textContent;
                    evaluatorResultsContainer.style.display = 'none';
                }
            });
        }
    }

    // دالة البحث
    async function searchEmployees(query, container) {
        if (!container) return;
        const response = await fetch(`${APP_URL}/api/employees/search?q=${encodeURIComponent(query)}`);
        const employees = await response.json();
        container.innerHTML = '';
        if (employees.length > 0) {
            employees.forEach(employee => {
                const item = document.createElement('div');
                item.className = 'search-result-item';
                item.textContent = `${employee.full_name} (${employee.emp_code})`;
                item.dataset.id = employee.id;
                container.appendChild(item);
            });
        } else {
            container.innerHTML = '<div class="search-result-item disabled">لا توجد نتائج.</div>';
        }
        container.style.display = 'block';
    }
}




}); // نهاية DOMContentLoaded


/**
 * دالة لجلب وعرض الدورات لقسم معين في لوحة المعلومات.
 * (تبقى خارج DOMContentLoaded لتكون متاحة للاستدعاء من onclick)
 */
function loadCoursesForDashboard(departmentId) {
    const coursesBody = document.getElementById(`courses-body-${departmentId}`);
    if (coursesBody.dataset.loaded === 'true') return;

    fetch(`${BASE_URL}/api/reports/courses-by-department/${departmentId}`)
        .then(response => response.json())
        .then(courseGroups => {
            coursesBody.dataset.loaded = 'true';
            if (courseGroups.length === 0) {
                coursesBody.innerHTML = '<div class="alert alert-light p-2 text-center">لا توجد دورات مقيمة في هذا القسم.</div>';
                return;
            }

            let html = `
                <div class="text-start mb-3 no-print">
                    <button class="btn btn-outline-info btn-sm print-department-report-btn" data-department-id="${departmentId}">
                        <i class="fas fa-print me-2"></i>طباعة تقرير هذا القسم
                    </button>
                </div>`;

            courseGroups.forEach(group => {
                html += `<h6 class="mt-3">${group.course_title}</h6>`;
                html += '<ul class="list-group list-group-flush mb-3">';
                group.courses.forEach(course => {
                    html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-book me-2 text-muted"></i>
                                <a href="${BASE_URL}/training/evaluations/show/${course.evaluation_id}">دورة بتاريخ ${course.start_date}</a>
                            </div>
                            <span class="badge bg-info">النتيجة: ${parseFloat(course.final_score || 0).toFixed(2)}%</span>
                        </li>`;
                });
                html += '</ul>';
            });
            coursesBody.innerHTML = html;
        })
        .catch(error => {
            console.error('Error fetching courses:', error);
            coursesBody.innerHTML = '<div class="alert alert-danger">حدث خطأ أثناء تحميل الدورات.</div>';
        });
}

// public/js/main.js

function confirmDelete(event, itemName = 'هذا العنصر') {
    event.preventDefault(); // منع إرسال النموذج فوراً
    const form = event.target;

    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: `سيتم حذف ${itemName} بشكل نهائي! لا يمكن التراجع عن هذا الإجراء.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، قم بالحذف!',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit(); // إذا أكد المستخدم، قم بإرسال النموذج
        }
    });
}
function confirmReopen(event) {
    event.preventDefault();
    const form = event.target;

    Swal.fire({
        title: 'إعادة فتح التقييم؟',
        text: 'سيتم تغيير حالة التقييم إلى "مسودة" وسيتم توجيهك لصفحة التعديل. هل أنت متأكد؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'نعم، أعد الفتح!',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}

// public/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    // التحقق من أننا في صفحة التقييم
    const evaluationLayout = document.querySelector('.evaluation-layout');
    if (!evaluationLayout) return;

    const sidebarLinks = document.querySelectorAll('#criteria-navigation a');
    const criteriaSections = document.querySelectorAll('.criterion-accordion .accordion-item');

    // 1. التمرير السلس عند النقر على روابط الشريط الجانبي
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                // حساب إزاحة الهيدر الرئيسي إذا كان لديك هيدر ثابت
                const offsetTop = targetElement.getBoundingClientRect().top + window.pageYOffset - 80; // 80px كإزاحة
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    // 2. تحديث الرابط النشط في الشريط الجانبي أثناء التمرير
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const id = entry.target.getAttribute('id');
            const navLink = document.querySelector(`#criteria-navigation a[href="#${id}"]`);
            
            if (entry.isIntersecting) {
                // إزالة 'active' من جميع الروابط
                sidebarLinks.forEach(link => link.classList.remove('active'));
                // إضافة 'active' إلى الرابط الحالي
                if(navLink) navLink.classList.add('active');
            }
        });
    }, { rootMargin: "-40% 0px -60% 0px" }); // يحدد متى يعتبر العنصر "مرئياً"

    criteriaSections.forEach(section => {
        observer.observe(section);
    });
});