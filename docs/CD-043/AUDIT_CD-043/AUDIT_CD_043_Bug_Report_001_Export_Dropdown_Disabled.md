# تقرير خطأ — التدقيق المستقل (AUDIT-CD-043) — Bug Report No. 001

**المهمة:** AUDIT-CD-043 — تدقيق مستقل لمنتج CD-043 (تصدير/استيراد Excel)
**المعرف:** AUDIT-CD-043-001
**المرجع:** SRS-EXCEL-IE-3.1-final §2/P2 — APPR-EXCEL-IE-007
**الأصل:** ملف إكسل مصدّر فعلياً (XLSX) عبر مسار `ExcelTemplateBuilder` + `PhpSpreadsheet\Writer\Xlsx`
**التاريخ:** 2026-11-16
**الحالة:** **مكتوب ومسلّم — أول تقرير خطأ خلال 24 ساعة من صدور التكليف**

---

## 1. الملخص التنفيذي

تقرير **خطأ أول** ضمن سلسلة تدقيق AUDIT-CD-043.

عند فحص **محتوى ملف إكسل (XLSX) مُولَّداً فعلياً** عبر بانر القوالب (`ExcelTemplateBuilder`) وكتابة الملف عبر `PhpSpreadsheet\Writer\Xlsx`، وتأكيد ذلك على **ملف العينة المُرسلة** في `docs/CD-043/exports/`، تم اكتشاف تعطل جوهري في **قواعد التحقق من البيانات (Data Validation) من نوع القائمة المنسددة**:

> جميع قواعد أنواع `list` تُكتب في مستند الـXLSX بسمة `showDropDown="0"` — أي أن **القوائم المنسددة غير مفعّلة** في ملف الإكسل المصدّر.

وهذا يتعارض مباشرةً مع متطلب SRS (مطابقة قائمة منسددة من شيت `Lists` — اسم ثم رمز) ومع إعلان تقرير P2 بأن «قوائم منسدلة في مواضعها ✅».

---

## 2. التفاصيل

| البند | القيمة |
|---|---|
| نوع الخطأ | عدم تمكين القائمة المنسددة (Data Validation — Dropdown) في ملف الإكسل المصدر |
| العمق | ميداني (artifact-level) — فشل لمس تواتر كامل المسار |
| التأثير | عدم ظهور/اختيار القيم من القائمة في ملف الإكسل المصدّر |
| الأولوية | **عالية** (يؤثر على قابلية الاستخدام الفعلية لميزة القوائم المنسددة في المنتج) |
| النطاق | أنواع القوالب: `employee`، `employee_in_course`، `employee_in_workshop`، `skill` (مع البرامج/الفروع/الأقسام/التقدير/المستويات) |

## 3. الأدلة

### 3.1 — مستند مُولَّد خلال التدقيق (audit regeneration)

الملف: `C:\Users\hussen\AppData\Local\Temp\opencode/audit2_employee_in_course.xlsx` (ناتج `ExcelTemplateBuilder->build()` + كتابة Xlsx)

محتوى XML لقاعدة تحقق نموذج البرنامج:

```xml
<dataValidation type="list" errorStyle="stop" operator="between" allowBlank="1"
  showDropDown="0" showInputMessage="0" showErrorMessage="1"
  errorStyle="stop" errorTitle="برنامج غير صالح" error="اختر برنامجاً من القائمة."
  sqref="A5:A104">
  <formula1>ProgramList</formula1>
</dataValidation>
```

مشابهة للقوائم المتبقية: `B5:B104` → `BranchList`، `C5:C104` → `DepartmentList`، `Q5:Q104` → `GradeList` — **كلها بقيمة `showDropDown="0"`**.

### 3.2 — ملف العينة المُرسل ضمن `docs/CD-043/exports/`

- `employee.xlsx` → القوائم في الأعمدة ذات الصلة (`C`/`D`/`E`) مكتوبة بقيمة `showDropDown="0"`.
- ملف `employee_in_course.xlsx` (مصدّر عبر المسار نفسه) → `A5:A104 → ProgramList`، `B5:B104 → BranchList`، `C5:C104 → DepartmentList`، `Q5:Q104 → GradeList` بقيمة `showDropDown="0"`.

### 3.3 — قواعد إثبات إضافية (فحص سلامة المستند)

- التحقق من نطاق المستند: `dimension ref="A1:U104"`.
- الحماية: `<sheetProtection sheet="1" .../>` — بلا كلمة سر (SRS متفق).
- قوائم التخزين (Named Ranges) سليمة: `BranchList='Lists'!A2:A4`، `DepartmentList='Lists'!B2:B8`، `ProgramList='Lists'!C2:C30`، `GradeList='Lists'!D2:D6`، `SkillLevelList='Lists'!E2:E4`.
- صيغة القائمة = اسم القائمة (اسم مال — وليس معرف تقني) ✅.

---

## 4. السبب الجذري (Root Cause)

المسار البرمجي:

1. `ExcelTemplateBuilder::addListValidation()` تضع `DataValidation::setShowDropDown(true)`.
2. كتابة الملف تتم عبر `PhpOffice\PhpSpreadsheet\Writer\Xlsx\Worksheet::writeDataValidations` التي تنفّذ:
   ```php
   $objWriter->writeAttribute('showDropDown', (!$dv->getShowDropDown() ? '1' : '0'));
   ```
   أي أن القيمة المطلوبة (`true`) تُحوَّل إلى `"0"`.
3. النتيجة: القيمة المُسجَّلة في مستند الـXLSX هي `showDropDown="0"`، وقاعدة التحقق تعمل كتحقق عادي (type=list) لكن **القائمة المنسددة معطلة**.

### 4.1 — الفجوة الأمنية/التطويرية في سلسلة الاختبار

- اختبار `tests/ExcelTemplateExportSmoke.php` يفحص **حالة الداخلية للبانر في الذاكرة** (`getDataValidationCollection()` → قوائم بأسماء القوائم) ويفحص مباشرة ملف XML للتحقق من غياب `password=` فقط.
- لا يفحص **القيمة الفعلية المُسجَّلة (`showDropDown`)** في ملف الـXLSX المُكتب.
- لا يوجد فحص «قيمة الـattribute بعد الكتابة».

لذلك **نجحت** مجموعة اختبارات CD-043 (6/6) رغم أن ملف الإكسل المصدّر الفعلي يحمل `showDropDown="0"`.

---

## 5. التأثير

- مستخدمو UAT (الذين يملؤون نماذج Excel يدوياً) لن يستطيعون الاعتماد على القوائم المنسددة الموصولة بقوائم `Lists`.
- الحاجة إلى إدخال بيانات يدوية خارج القائمة (أو تعديل القيم يدوياً) بدل الاختيار.
- خلل في الالتزام بمتطلب SRS حول القوائم المنسددة.
- لا يؤثر على منطق الاستيراد (الاستيراد يعتمد على بناء القوالب/القوائم نفسها)، لكنه يؤثر على جودة التطبيق العملي.

---

## 6. الحل المقترح (خارج نطاق تدقيق — يُرجى تنفيذه من المطوّر)

1. تعديل كتابة بيانات التحقق في `PhpSpreadsheet\Writer\Xlsx\Worksheet::writeDataValidations` (أو ضبط `showDropDown` بعد الكتابة) بحيث تُكتب القيمة الصحيحة `showDropDown="1"`.
2. إعادة توليد ملفات العينات في `docs/CD-043/exports/`.
3. توسيع فحص `ExcelTemplateExportSmoke` ليتحقق من القيمة بعد الكتابة (`strpos($xml, 'showDropDown="1"')`) — بحيث لا تتجاوز هذه الثغرة فحص الاختبار القائم على الذاكرة.

---

## 7. ملخص الحكم على هذه الملاحظة

| البند | النتيجة |
|---|---|
| Bug Report AUDIT-CD-043-001 | **مُثبت** (evidence: raw XML + delivered sample) |
| الاختبار الحالي (`ExcelTemplateExportSmoke`) | **فشل في اكتشاف الثغرة** — يفحص الذاكرة/الـattribute نادراً |
| الحكم | يستوجب إصلاحاً قبل اعتماد UAT النهائي |

---

## 8. إقرار

هذا التقرير مبني على فحص ميداني (artifact-level) لمحتوى ملفات إكسل حقيقية (مُولَّدة + عينات مُرسلة)، بلا أي تعديل على الكود، وبمنطق التدقيق المستقل.
