# CD-023 — توثيق التنفيذ
## تحديث باني القوالب لدعم التقييمات الوصفية

| الحقل | القيمة |
|---|---|
| **المرجع** | CD-023 — Sprint 5.0 |
| **تاريخ التنفيذ** | 2026-11-06 |
| **الحالة** | مُنفَّذ، بانتظار UAT من المساعد المطور |
| **عدد الاختبارات** | 136 (132 سابقة + 4 جديدة) |

---

## ١) الهدف

تحديث باني القوالب (B1) وقاعدة البيانات وشاشة التعبئة الفردية لدعم **التقييمات الوصفية** عبر:
- إضافة عنصر «قسم نصي» (Textarea) للمكتبة.
- إضافة عنصر «التقدير العام» (Overall Grade) للمكتبة.
- إلغاء شرط "مجموع الأوزان = 100%" للقوالب الوصفية، وإلزامه بوجود "التقدير العام".
- حساب الدرجة النهائية للقالب الوصفي من "التقدير العام" فقط.

---

## ٢) التغييرات حسب الملف

### قاعدة البيانات — `database/migrations/2026_11_06_CD023_descriptive_templates.sql`
- توسيع ENUM لـ `criteria_sections.evaluation_type` بإضافة `textarea` و`overall_grade` (قيمة `text` كانت موجودة سابقاً وغير مستخدمة).
- إضافة عمود `template_id INT DEFAULT NULL` لجدول `grading_scales` (للسلالم المخصصة داخل الباني — اختياري).
- Idempotent: `ADD COLUMN IF NOT EXISTS` + `CREATE INDEX IF NOT EXISTS`.

### النموذج — `app/Models/GradingScale.php`
ميثودات جديدة:
- `getByName(string $scaleName): array` — جلب كل تقديرات سلمٍ مُسماة.
- `getNumericValueForLabel(string $scaleName, string $gradeLabel): ?float` — تحويل مسمى التقدير إلى قيمته الرقمية.
- `getActiveScaleNames(): array` — قائمة أسماء السلالم النشطة (للباني).

### المتحكم — `app/Controllers/BuilderController.php`
- `index()`: تمرير `gradingScaleNames` للواجهة.
- `validateWeights(array, string $evaluationMode): ?string` — مُعاد كتابتها:
  - **descriptive**: إلغاء شرط الـ 100%؛ إلزام وجود قسم `overall_grade` واحد على الأقل.
  - **numeric/mixed**: شرط الـ 100% مع استثناء الأنواع `textarea`/`text`/`overall_grade` من مجموع الأوزان.
  - منع إضافة أكثر من قسم `overall_grade` واحد.

### الواجهة — `app/Views/builder/index.php`
- إضافة مجموعة عناصر جديدة «التقييم الوصفي (CD-023)» في المكتبة تحوي:
  - «قسم نصي» (`data-section-type="textarea"`).
  - «التقدير العام» (`data-section-type="overall_grade"`).
- تمرير `gradingScaleNames` كمتغير JS.

### JavaScript — `public/js/builder.js`
- `addSection()`: معالجة `textarea` (weight=0، maxChars، required) و`overall_grade` (weight=0، scaleName)؛ منع تكرار `overall_grade`.
- `handleAutomaticWeightDistribution()`: استثناء `textarea`/`text`/`overall_grade` من توزيع الأوزان.
- `renderPropertiesPanel()`: لوحة خصائص للقسم النصي (max_chars، required) وللتقدير العام (اختيار سلم التقديرات).
- `updateHiddenFields()`: إرسال حقول `max_chars` و`required` و`scale_name`.
- التحقق من الإرسال: يعتمد على `evaluation_mode` (إلغاء 100% للوصفي + إلزام التقدير العام).
- `getSectionTypeName()`: إضافة `textarea` و`overall_grade`.
- تهيئة وضع التعديل: ترطيب `maxChars` و`required` و`scaleName` من بيانات الخادم.

### النموذج — `app/Models/Template.php`
- `buildStructureFromRequest()`: تخزين خصائص `textarea` (max_chars، required) و`overall_grade` (scale_name) داخل `evaluation_options` JSON.

### شاشة التعبئة — `app/Views/evaluations/fill.php`
- فرع `textarea`: رسم `<textarea>` مع `maxlength` و`required`؛ الحقل باسم `results[..][..][text]`.
- فرع `overall_grade`: رسم `<select>` من `grading_scales` المُصفّاة بـ `scale_name`؛ الحقل باسم `results[..][..][grade_label]`.
- إخفاء عرض الدرجة وعمود الملاحظات لهذه الأنواع.

### المتحكم — `app/Controllers/EvaluationController.php`
- `submit()`: استخراج إجابة "التقدير العام" من النتائج (`extractOverallGradeAnswer`)؛ للقالب الوصفي، `final_score` = القيمة الرقمية للتقدير.
- `extractOverallGradeAnswer()` (ميثود خاصة جديدة): تبحث في هيكل القالب عن قسم `overall_grade` وتقرأ `grade_label`.

### النموذج — `app/Models/Evaluation.php`
- `saveEvaluationResults()`: للقسم النصي، تخزين `raw_value` = النص و`normalized_score` = NULL؛ للتقدير العام، `raw_value` = التسمية و`normalized_score` = NULL.
- `calculateFinalScore()`:
  - **descriptive**: استدعاء `calculateDescriptiveScore()` — يجد قسم `overall_grade`، يقرأ التسمية، يحوّلها لقيمة رقمية.
  - **numeric/mixed**: تخطّي الأقسام النصية والتقدير العام في المجموع.
- `calculateDescriptiveScore()` (ميثود خاصة جديدة): منطق حساب الدرجة الوصفية.

---

## ٣) الضوابط المُلتزَم بها

| الضابط | الالتزام |
|---|---|
| عدم لمس `Auth.php` | ✅ لم يُلمس |
| عدم لمس واجهة `bulk-create` | ✅ لم تُلمس |
| عدم لمس حساب أوزان القوالب الرقمية الحالية | ✅ السلوك الرقمي محفوظ (شرط 100% + الأوزان كما هي)؛ الاستثناء يطال فقط الأنواع الجديدة |
| الحفاظ على 132 اختبار | ✅ + 4 جديدة = **136** |
| بوابة الترميز `exit=0` | ✅ لا BOM ولا CJK |

---

## ٤) الاختبارات الجديدة — `tests/cases/Controllers/DescriptiveTemplatesTest.php`

| # | الاختبار | ما يتحقق منه |
|---|---|---|
| 1 | `testValidateWeightsAllowsDescriptiveWithout100Percent` | القالب الوصفي يُعفى من شرط الـ 100% |
| 2 | `testValidateWeightsRequiresOverallGradeForDescriptive` | القالب الوصفي يُلزم بوجود "التقدير العام" |
| 3 | `testValidateWeightsNumericStillEnforces100Percent` | القالب الرقمي يظل يُطبّق شرط الـ 100% |
| 4 | `testTextSectionDoesNotAffectFinalScore` | القسم النصي لا يؤثر على الدرجة النهائية (يبقى 80.0) |
| 5 | `testOverallGradeCalculatesCorrectlyForDescriptive` | التقدير العام "ممتاز" → `final_score` = 5.0 |

---

## ٥) كيفية استخدام الميزة

### في الباني:
1. اختر `evaluation_mode = descriptive`.
2. اسحب «قسم نصي» للأغراض الوصفية (ملاحظات، شواهد، وصف أداء).
3. اسحب «التقدير العام» — يُفتح لوحة خصائص لاختيار سلم التقديرات.
4. احفظ — لا حاجة لضبط الأوزان للـ 100%.

### في شاشة التعبئة:
1. تُرسم الأقسام النصية كـ Textarea.
2. يُرسم التقدير العام كقائمة منزلقة من السلم المختار.
3. عند الحفظ: تُحفظ الإجابات النصية كما هي، والدرجة النهائية = قيمة التقدير المختار.

---

## ٦) ملاحظات تقنية

- **`evaluation_options` JSON** يخزّن خصائص الأنواع الجديدة:
  - textarea: `{"max_chars":1000,"required":false}`
  - overall_grade: `{"scale_name":"السلم الخماسي"}`
- **القوالب القديمة** (بدون أقسام نصية أو تقدير عام) غير متأثرة — السلوك الرقمي محفوظ تماماً.
- **آلية `final_grade_id` القديمة** في `submit()` محفوظة كـ fallback للقوالب التي لا تحتوي قسم `overall_grade` (توافق خلفي).

— كبير المطورين
