# مولد Excel الديناميكي — التقرير الشهري وكل القوالب

## الهدف
توليد Excel مطابق للاستمارة الورقية، ديناميكي حسب القالب، حتى 100 موظف، مع تعبئة مسبقة و استيراد ذري.

## البنية — ورقة واحدة
- `A الرقم الوظيفي* | B الاسم الكامل* | C الصفة او الكنية | D الفرع* | E القسم* | F..F+n بنود القالب | +1 ملاحظة عامة | +2 تاريخ التقييم* | +3 مسؤول التقييم* | +4 الصفة`
- `colCount = 5 + n + 4` — `n = count(criteria_sections)` للقالب
- `A1` عنوان مدمج (معرف القالب + المقيّم) — `A2` تعليمات — `A3:W4` ترويسات مدمجة (معيار + قسم + ذيل) — `A5` تلميحات — `A6:A105` بيانات صفراء قابلة للتحرير — تجميد `A6` — `rightToLeft` — حماية ورقة

## التعبئة المسبقة
- `?branch_id=X` → `SELECT emp_code, full_name, nickname, branch, department FROM employees WHERE is_active=1 AND branch_id=X LIMIT 100`
- `?department_id=Y` → نفس مع `department_id`
- `evaluator_name/title` تُملأ من `employees` للمقيّم الحالي

## التحقق داخل Excel
- قوائم منسدلة للفروع/الاقسام — لكل `grade` قائمة `labels` — `numeric/percentage` حدود رقمية — تاريخ `DATE` — تلوين احمر للمكرر `A` و `B` عند غياب الرقم — تنسيق تاريخ `yyyy-mm-dd` — حماية

## قاعدة أو — الرقم أو الاسم
- يجب احدهما على الاقل — رسالة: يجب ادخال الرقم الوظيفي او الاسم الكامل على الاقل في الصف X
- اولية للرقم — اذا غاب الرقم بحث بالاسم + فرع/قسم — اذا التباس (اكثر من موظف بنفس الاسم) خطأ يطلب الرقم

## الاستيراد — معاينة و التزام ذري
- `previewWorkbookPath` — يقرأ `A1` لمعرف القالب — يفحص حتى 100 صف — حقن صيغ `^[=+@-]` — تاريخ ليس مستقبلا — وجود الفرع/القسم — مطابقة قيمة القسم لنوعه — تكرار `emp_code` و `full_name` — ينشئ `evaluation_import_batches/rows/row_errors`
- `commitBatch` — `FOR UPDATE` — يعيد التحقق — ينشئ موظف جديد اذا لزم (emp_code او full_name) — ينشئ/يحدث تقييم `draft` → `completed` — يحسب `final_score` — `audit_logs`

## الملفات المعدلة
- `app/Services/EvaluationExcelService.php` — اعادة بناء `buildTemplate`, `previewWorkbookPath`, `commitBatch`, `validateSingleRow`, `applyDropdowns`, `applyDuplicateFormatting`, `applyDateFormatting` + مساعدات `getPrefillEmployees`, `getEvaluatorInfo`, `findEmployeeByName`
- `app/Controllers/EvaluationExcelController.php` — تمرير `branch_id/department_id` و `branches`
- `app/Views/evaluations/excel_import.php` — واجهة الاختيارات الثلاثة (فارغ/فرع/قسم) مع تحميل متتالٍ
- `database` — `ALTER TABLE employees ADD nickname`, `ALTER TABLE evaluations ADD notes`

## الاختبار
- بناء قالب فارغ — بناء معبأ بفرع — معاينة 2 صالح + 2 مرفوض (فارغ/مكرر) — التزام ذري وحساب درجة 100 — تنظيف
