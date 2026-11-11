<div dir="rtl">

# CD-042 (UI Enhance) — توجيه التطوير الموجّه إلى مطوّر PHP

**معرّف المهمة:** `CD-042-UI-ENHANCE`
**المستودع:** `https://github.com/hussen200555ali-maker/employee-evaluation.git`
**الفرع المقترح:** `feature/cd-042-ui-enhance` (متفرّع من `master` @ `8eeb72c`)
**التقنية المستهدفة:** PHP 8.2 + MySQL 8+ + MVC مخصّص (App\\) + phpoffice/phpspreadsheet
**اللغة/الاتجاه:** عربي / RTL
**تاريخ التوجيه:** 2026-11-11
**الحالة:** جاهز للتنفيذ

---

## 0. ملخّص تنفيذي

تحسين واجهة استيراد/تصدير Excel الموجودة (`app/Views/evaluations/excel_import.php`) بإضافة **لوحة معاينة بصرية لهيكل قالب Excel قبل التحميل** (الميزة الأساسية الجديدة)، ورفع حدود الصفوف إلى 2000، وإظهار حد حجم 20MB، وتحسين UX (منطقة رفع drag-and-drop + شريط حفظ لاصق + بطاقة «موظف جديد» رابعة). **البنية الخلفية الناضجة (`EvaluationExcelService` + جداول `evaluation_import_batches/rows/errors` + قيد التفرد `uq_eval_once`) تبقى كما هي ولا تُمَسّ.**

> ⚠️ هذا التوجيه يستهدف مشروع **PHP** قائمًا ومُنتاجًا. لا تُعدّل خادم Next.js في أي sandbox. المطوّر المستهدف: مطوّر PHP يعمل على بيئة XAMPP/Windows أو ما يعادلها.

---

## 1. السياق والمصالحة

المستودع `employee-evaluation` هو نظام تقييم أداء موظفين إنتاجي (PHP 8.2 / MySQL 8 / MVC مخصّص). وقد نفّذ مسبقًا:
- **محرك Excel كامل:** `app/Services/EvaluationExcelService.php` (102KB) يدعم 6 أنواع تقييم: `employee`, `employee_in_course`, `employee_in_workshop`, `course`, `workshop`, `unit`.
- **مسار استيراد كامل:** تنزيل قالب → رفع → معاينة → اعتماد atomic، عبر جداول `evaluation_import_batches`, `evaluation_import_rows`, `evaluation_import_row_errors`.
- **CD-042 (منع التكرار):** منجزة على `master` — قيد تفرد `uq_eval_once (evaluated_employee_id, template_id, evaluation_date, context_key)` حيث `context_key = IFNULL(context_id, 0)`. **لا تلمسها.**

الموك-أب المرجعي `mockups/cd-042/excel_templates_preview.html` (المُضاف بهذا الفرع) يعرض تصميمًا محسّنًا يضيف فوق الموجود دون إعادة بناء.

### جدول المصالحة (موجود vs. يُضاف)

| الجانب | موجود في المستودع | يضيفه هذا التوجيه |
|--------|------------------|------------------|
| تصدير القالب | `buildTemplate()` ناضج | — |
| المعاينة قبل الاعتماد | `previewUpload()` + جداول batches | — |
| الاعتماد atomic | `commitBatch()` + `uq_eval_once` | — |
| 6 أنواع تقييم | مدعومة كاملة | قد يُضاف «skill» (DQ-7) |
| لوحة معاينة **هيكل** القالب قبل التحميل | ❌ غير موجود | ✅ **الميزة الأساسية الجديدة** |
| حد الصفوف | `max=1000` | رفع إلى `2000` |
| حد حجم الملف | غير معروض | إظهار `20MB` |
| منطقة الرفع | `<input type=file>` بسيط | drag-and-drop منسّق |
| شريط حفظ | زر اعتماد سطري | sticky bar |
| بطاقات إحصائيات | 3 (إجمالي/صالحة/أخطاء) | 4 (+ موظف جديد) |

---

## 2. التقنية المستهدفة

| العنصر | القيمة |
|--------|--------|
| اللغة | PHP 8.2 |
| قاعدة البيانات | MySQL 8+ / MariaDB 10.4+ |
| البنية | MVC مخصّص (App\\ namespace، PSR-4) |
| Excel | `phpoffice/phpspreadsheet ^1.29` |
| PDF | `dompdf/dompdf`, `tecnickcom/tcpdf`, `mpdf/mpdf` |
| عربي | `khaled.alshamaa/ar-php` |
| العرض | PHP views تقليدية (`app/Views/*.php`) + layouts |
| البيئة المرجعية | XAMPP (Windows) — أوامر PowerShell في تقارير التراجع السابقة |

---

## 3. المصادر المرجعية (اقرأها قبل البرمجة)

| الملف | الغرض |
|------|------|
| `app/Services/EvaluationExcelService.php` | خاص `buildTemplate()` (الأسطر 32–318) لفهم توليد الأعمدة/الأقسام/الأوزان لكل `target_type`. |
| `app/Services/EvaluationExcelService.php` | `previewUpload()`/`previewWorkbookPath()` (319–488) + `commitBatch()` (489+) لفهم مسار المعاينة/الاعتماد. |
| `app/Views/evaluations/excel_import.php` | الواجهة الحالية (ستُعدّل). |
| `database/migrations/20260824_excel_import.sql` | جداول الاستيراد الثلاث. |
| `database/migrations/2026_11_06_CD023_descriptive_templates.sql` | أنواع الأقسام (`textarea`, `overall_grade`). |
| `database/migrations/2026_11_03_CD016_evaluation_foundation.sql` | أساس التقييمات (للمراجعة). |
| `CD-042_Execution_Report.md` | سياق منع التكرار (منجز — لا تلمسه). |
| `mockups/cd-042/excel_templates_preview.html` | **المواصفة المرئية المعتمدة** — استنسخ تصميمها بدقة. |
| `mockups/cd-042/excel_import.html` | موك-أب تدفق الاستيراد. |
| `docs/cd-042/CD-042_Study.html` (أو `study.html`) | الدراسة الشاملة (مرجع متطلبات). |

---

## 4. القرارات المعتمدة

### القرارات الستة الأصلية (DQ-1..DQ-6) — منطبقة على الكود الموجود
| # | القرار | المعتمد |
|---|------|---------|
| DQ-1 | إنشاء الدورات من Excel (الخيار ب) | ✅ نعم مع تحقق صارم (موجود في `buildTemplate`) |
| DQ-2 | إنشاء الأقسام آليًا | ❌ لا — تسجيل مسبق فقط |
| DQ-3 | إنشاء المسميات آليًا | ❌ لا — فارغ + تحذير |
| DQ-4 | الحد الأقصى لحجم الملف | **20MB** |
| DQ-5 | صيغة `.xls` | ❌ `.xlsx` فقط |
| DQ-6 | فترات متعددة في نفس الملف | ✅ مسموح |

### قرار جديد (DQ-7) — نوع «skill» في الموك-أب
الموك-أب يعرض تبويب «تقييم مهاري (skill)» غير موجود في الـ 6 أنواع الحالية. خياران:

- **(أ) إضافة `skill` كـ `target_type` جديد:** `ALTER TABLE templates MODIFY target_type ENUM(...,'skill')` + معالجته في `buildTemplate()` و`previewWorkbookPath()` و`commitBatch()`.
- **(ب) معاملته كقالب `employee` بأقسام معايير مختلفة** (تقني/قيادي) دون نوع جديد — يُنشأ عبر منشئ النماذج (Form Builder) الموجود.

> **التوصية:** اعتمد **(ب)** أولًا لتقليل التغيير الخلفي، واعرض في تبويب «skill» أي قالب `employee` تكون معاييره تقني/قيادي. إن أردت لاحقًا تمييزه كنوع مستقل، انتقل إلى (أ) في مهمة لاحقة.

### قرارات حدود
- رفع `max_rows` من `1000` إلى **`2000`** (متوافق مع DQ-4).
- إظهار حد حجم `20MB` في منطقة الرفع.
- الحد الأدنى للصفوف يبقى `1` (أو `50` إن أردت توحيدًا مع الموك-أب).

---

## 5. نطاق التغييرات (ملفًا ملفًا)

### 5.1 `app/Views/evaluations/excel_import.php` (تعديل رئيسي)
أعدّل الواجهة لتطابق `mockups/cd-042/excel_templates_preview.html`:

1. **لوحة معاينة هيكل القالب** (الميزة الأساسية — القسم 6 للتذييل التقني):
   - تبويبات للأنواع المتاحة (employee / skill / course / workshop كحد أدنى؛ يمكن توسيعها للـ6).
   - كل تبويب يعرض جدولًا بصريًا لهيكل أعمدة قالب Excel: الترويسة المدمجة (`rowspan=2`)، الأقسام والأوزان، مؤشرات القوائم المنسدلة (▼)، أحرف الأعمدة (A–N)، صفوف بيانات نموذجية، تمييز «＋ موظف جديد» بخلفية `--gold-bg`.
   - وسيلة إيضاح (legend) لأنواع الخلايا: ترويسة / أقسام / وزن-تقدير / قائمة منسدلة / رقم / نصّي / موظف جديد.
   - التبديل بين التبويبات يحدّث قائمة «القالب» في لوحة التصدير.

2. **لوحة التصدير (①):** حدّث `max` من `1000` إلى `2000`، وأضف نص «الحد: ٥٠ صف كحد أدنى · ٢٠٠٠ صف كحد أقصى».

3. **منطقة الرفع (②):** استبدل `<input type="file">` بمنطقة drag-and-drop منسّقة (نفس `.upload-zone` في الموك-أب):
   - `<div class="upload-zone">` بحدود متقطّعة `2px dashed`، أيقونة 📁، نص «اسحب ملف XLSX هنا أو انقر للاختيار» + سطر فرعي «حد أقصى ٢٠ ميغابايت (يتّسع لـ 2000 موظف) · .xlsx فقط · استعمل القالب المُصدّر».
   - نقر المنطقة يُفتح نافذة اختيار الملف؛ السحب والإفلات يُفعّل الـ `<input type="file" name="workbook" accept=".xlsx" hidden>`.
   - عرض اسم الملف وحجمه بعد الاختيار في `<div class="upload-info">`.

4. **شريط حفظ لاصق (sticky bar):** في الأسفل، `position: sticky; bottom: 0` مع `backdrop-filter: blur(6px)`، يحوي:
   - نص جانبي: «القالب محمي: تعديل البيانات فقط — لا إضافة أعمدة أو تغيير أنواع الخلايا».
   - زر «↺ تفريغ» (إعادة ضبط).
   - زر «✅ تأكيد الاستيراد» معطّل حتى تمر المعاينة بنجاح (يُفعَّل عبر JS عند `preview.valid > 0 && preview.invalid == 0`).

5. **بطاقات الإحصائيات:** غيّرها من 3 (إجمالي/صالحة/أخطاء + القالب) إلى 4: **إجمالي الصفوف / صالحة / موظف جديد / أخطاء** (أضف بطاقة «موظف جديد» تُحسب من `count(rows where is_new_employee)`).

6. **الحفاظ على:** نظام الألوان الحالي (`#0c2823` / `#c8943f` / `#faefd9` — موجود أصلاً في ملف `excel_import.php`)، الفئات `.cd042-*`، التدفّق PHP الحالي (`$preview === null` ? تحميل+رفع : معاينة+اعتماد).

### 5.2 `app/Services/EvaluationExcelService.php` (إضافة method)
أضف method جديدًا يُرجع **بيانات وصفية لهيكل القالب** (بدل توليد ملف كامل) لتغذية لوحة المعاينة البصرية:

```php
/**
 * يُرجع بنية أعمدة قالب Excel لمعاينة بصرية قبل التحميل.
 * يجب أن تتطابق مع ما يُولّده buildTemplate() تمامًا.
 *
 * @param int $templateId
 * @return array{
 *   target_type: string,
 *   columns: array<int, array{
 *     letter: string, key: string, label: string,
 *     type: 'text'|'number'|'dropdown'|'date'|'notes'|'grade',
 *     required: bool, section: string, weight: ?float,
 *     dropdown_source: ?string, rowspan: int, colspan: int
 *   }>,
 *   sections: array<int, array{name: string, weight: float, columns: array}>,
 *   dropdowns: array{branches?: array, departments?: array, grade_scale?: array, skill_levels?: array},
 *   sample_rows: array<int, array>
 * }
 */
public function describeTemplateStructure(int $templateId): array
```

**التنفيذ:** استخرج منطق بناء الأعمدة من `buildTemplate()` (الأسطر 50–300) وأعد استخدامه هنا بدل تكراره. هذا يضمن أن المعاينة البصرية تطابق الملف المُحمَّل. للقوالب الديناميكية (التي معاييرها من DB)، اقرأ `criteria_sections` + معاييرها من قالب `$templateId`.

### 5.3 المتحكّم (EvaluationController أو المسؤول عن `/evaluations/excel/import`)
- مرّر `describeTemplateStructure()` لكل قالب متاح إلى الواجهة (`$templateStructures = array_map(fn($t) => $service->describeTemplateStructure($t->id), $templates)`).
- **أو** (مُفضّل للتبويبات الديناميكية): أضف endpoint AJAX:
  - `GET /evaluations/excel/template-structure?template_id={id}` → يُرجع JSON لـ `describeTemplateStructure($id)`.
  - المسار في `app/Core/router` أو ما يعادله (راجع كيف تُسجَّل المسارات الحالية مثل `/evaluations/excel/template`).
  - الواجهة تستدعيه عند تبديل التبويبات لتحديث لوحة المعاينة.

### 5.4 قاعدة البيانات
- **لا جداول جديدة** ولا هجرات إلزامية لهذا التوجيه.
- إن اعتُمد **DQ-7 خيار (أ)** (إضافة `skill` كنوع جديد): أنشئ هجرة `database/migrations/2026_11_11_CD042_skill_type.sql`:
  ```sql
  ALTER TABLE templates MODIFY COLUMN target_type
    ENUM('employee','employee_in_course','employee_in_workshop','course','workshop','unit','skill')
    NOT NULL;
  -- + تحديث buildTemplate/previewWorkbookPath/commitBatch لمعالجة 'skill'
  ```
- **لا تلمس** قيد `uq_eval_once` أو أي شيء يتعلق بـ CD-042 الأصلية.

---

## 6. لوحة معاينة هيكل القالب — التفاصيل التقنية

هذه هي الميزة الأساسية الجديدة. يجب أن تُعرض **قبل** أن يُحمّل المستخدم القالب، كي يرى هيكل الأعمدة والأوزان والقوائم المنسدلة بصريًا.

### البنية HTML (مبسّطة، مطابقة للموك-أب)
```html
<div class="preview-wrap">
  <div class="preview-tabs">
    <div class="ptab active" data-type="employee">① موظف فردي</div>
    <div class="ptab" data-type="skill">④ مهاري</div>
    <div class="ptab" data-type="course">② دورة (وصفي)</div>
    <div class="ptab" data-type="workshop">③ ورشة</div>
  </div>
  <div class="legend"><!-- وسيلة الإيضاح --></div>
  <div class="preview-scroll">
    <table class="xl"><!-- أعمدة مدمجة rowspan=2، أقسام colspan، أحرف A-N --></table>
  </div>
</div>
```

### التلوين (من الموك-أب، مطابق للواجهة الحالية)
- الترويسة: `background:#0c2823; color:#fff` (البراند)
- الوزن/التقدير: `#c8943f` (ذهبي)
- خلية قائمة منسدلة: `background:#fffbeb`
- خلية رقم: `background:#f0f7ff`
- خلية نصّية: `background:#fcfcfc`
- خلية ملاحظة: `background:#faf5ff`
- موظف جديد: `background:#faefd9` (gold-bg)

### التفاعل (JS)
- نقر تبويب → `fetch('/evaluations/excel/template-structure?template_id=' + tplIdForType)` → يُعيد `{columns, sections, dropdowns, sample_rows}` → يُعيد رسم الجدول.
- أو إن مرّرت كل الهياكل من PHP مسبقًا (`$templateStructures`)، بدّل بينها بدون AJAX (أبسط، أسرع).
- تبديل التبويب يحدّث أيضًا `<select id="cd042-tpl">` لاختيار أول قالب من النوع المختار.

### التوافق مع `buildTemplate()`
**إلزامي:** عدد الأعمدة وترتيبها وأسماؤها في المعاينة = تمامًا ما يُولّده `buildTemplate($templateId, ...)` عند التحميل. اختبر هذا يدويًا: حمّل القالب، افتحه في Excel، قارن الأعمدة بالمعاينة البصرية — يجب التطابق 100%.

---

## 7. قواعد التحقق القائمة (للمراجعة فقط — لا تُعاد كتابتها)

القواعد موجودة في `EvaluationExcelService::previewWorkbookPath()` و`commitBatch()`. هذه قائمة للتحقق من أنها تغطي ما يلي (إن نقص شيء، أضِفه):

| الفئة | القواعد المتوقّعة |
|------|------------------|
| VR-X (أمن/ملف) | امتداد .xlsx فقط · حجم ≤ 20MB · بنية داخلية صالحة · صفوف ≤ 2000 · خلية لا تبدأ بـ `= + @` · تنظيف CR/LF/NULL · سجل تدقيق |
| VR-E (موظف) | emp_code أو full_name إجباري · ربط بـ DB · فرع ضمن القائمة · قسم تابع للفرع (DQ-2) · مسمى غير معروف → فارغ+تحذير (DQ-3) · كود جديد فريد |
| VR-C (دورة/ورشة) | (خيار ب) عنوان إجباري · تواريخ إجبارية و start<end · ربط/دمج عند تكرار العنوان · فرع/قسم البرنامج يخضعان لـ VR-E-05/06 |
| VR-S (درجات) | نسبة ∈ [0,100] · رقمي ≤ max_value · boolean ∈ {نعم,لا,1,0,true,false} · textarea ≤ max_chars (اقتطاع+تحذير) · أرقام صالحة · إلزامية غير فارغة |
| VR-G (تقدير) | descriptive إجباري · numeric اختياري · ضمن القائمة · percentage يُحسب · boolean يُحسب من الغالبية |

> ملاحظة: قاعدة **VR-X-04 (حجم ≤ 20MB)** و**VR-X-06 (صفوف ≤ 2000)** يجب أن تكون محدّثة في الكود إلى القيم المعتمدة (إن كانت 10MB/1000، حدّثها).

---

## 8. معايير القبول

1. ✅ لوحة معاينة هيكل القالب تُعرض قبل التحميل، بتبويبات لكل نوع، والأعمدة المدمجة والأوزان والقوائم▼ مطابقة **تمامًا** لما يُولّده `buildTemplate()` فعليًا (تحقق يدوي بمقارنة المعاينة بالملف المُحمَّل).
2. ✅ `max_rows` = `2000` + إظهار «20MB» في منطقة الرفع + تحديث `VR-X-04`/`VR-X-06` في الخدمة إن لزم.
3. ✅ منطقة رفع drag-and-drop تعمل (نقر + سحب وإفلات) + عرض اسم/حجم الملف.
4. ✅ شريط حفظ لاصق (sticky) + زر «تأكيد الاستيراد» معطّل حتى تمر المعاينة.
5. ✅ 4 بطاقات إحصائيات (إجمالي/صالحة/موظف جديد/أخطاء).
6. ✅ الواجهة مطابقة بصريًا لـ `mockups/cd-042/excel_templates_preview.html` (نفس الألوان والتخطيط).
7. ✅ `php -l` نظيف لكل ملف معدّل (`app/Views/evaluations/excel_import.php`, `app/Services/EvaluationExcelService.php`, المتحكّم).
8. ✅ اختبارات الدخان الموجودة تمرّ دون تراجع:
   - `php tests/runner.php CD042DuplicateSmoke` → 1/1
   - `php tests/ExcelWorkflowSmoke.php` → 4/4
9. ✅ تدفّق end-to-end: اختيار نوع → معاينة الهيكل بصريًا → تحميل القالب → تعبئة → رفع → معاينة الصفوف (ملوّنة) → اعتماد → تحقق من عدم تكرار.
10. ✅ التبويب «skill» يعرض قالبًا تقني/قيادي (وفق DQ-7 خيار ب).

---

## 9. ترتيب التنفيذ المقترح

1. **فرع جديد:** `git checkout -b feature/cd-042-ui-enhance master`.
2. **الخدمة أولًا:** أضف `describeTemplateStructure()` إلى `EvaluationExcelService.php` + اختبرها على قالب `employee` موجود.
3. **المتحكّم:** مرّر البيانات (أو أضف endpoint AJAX).
4. **الواجهة:** أعدّل `excel_import.php` — لوحة المعاينة أولًا، ثم لوحة التصدير (max=2000)، ثم منطقة الرفع drag-drop، ثم شريط الحفظ اللاصق، ثم بطاقة «موظف جديد».
5. **تحديث حدود VR-X-04/06** في الخدمة إن كانت أقل.
6. **(إن DQ-7 أ):** هجرة `skill` + معالجة في الخدمة.
7. **اختبارات الدخان** + `php -l` على كل ملف.
8. **تحقق يدوي:** حمّل قالب `employee`، افتحه في Excel، قارن الأعمدة بالمعاينة البصرية — يجب التطابق.
9. **تعهد منفصل لكل مرحلة** (الخدمة، الواجهة، الحدود) لتسهيل التراجع.
10. **دمج إلى `master`** بعد المراجعة + UAT.

---

## 10. الاختبارات

- **لا تكتب اختبارات جديدة** ما لم يطلب كبير المستشارين — يكفي اختبارات الدخان الموجودة + التحقق اليدوي.
- اختبر يدويًا التدفق الكامل (الخطوة 9 أعلاه) على بيئة UAT (`employee_evaluation_uat`).
- اختبر على متصفّحين (Chrome + Edge) لأن CD-042 الأصلية رصدت تكرارًا من Chrome 156.

---

## 11. التراجع

أنشئ نقطة عودة قبل البدء (كما في الممارسة الموجودة):

```powershell
$tag = "pre-cd042-ui-enhance-$(Get-Date -Format yyyyMMdd_HHmmss)"
git tag $tag
git branch "backup/$tag" $tag
# DB dump:
& "C:\xampp\mysql\bin\mysqldump.exe" -u root employee_evaluation_uat evaluations evaluation_answers evaluation_import_batches evaluation_import_rows evaluation_import_row_errors > "storage/backups/CD-042/pre_ui_enhance/db.sql"
```

للتراجع:
- `git checkout backup/<tag>` أو `git revert <commits>`.
- الملفات المتأثرة قليلة (واجهة + method جديد + احتماليًا هجرة)، فالتراجع مباشر.

---

## 12. ملاحظات ختامية

- هذا التوجيه **لا يمسّ** عمل CD-042 الأصلية (منع التكرار) — هو طبقة UI فوقها.
- البنية الخلفية ناضجة — لا تُعدّها. أضِف `describeTemplateStructure()` فقط.
- إن واجهت تعارضًا بين الموك-أب والكود القائم، **الكود القائم هو المرجع** (الموك-أب تصميم إرشادي).
- الموك-أب والدراسة في هذا الفرع (`mockups/cd-042/` + `docs/cd-042/`) كمراجع تصميمية.
- بعد الدمج، حدّث `CHANGELOG.md` بإدخال `CD-042 (UI Enhance)`.

---

**نهاية التوجيه — CD-042-UI-ENHANCE**

</div>
