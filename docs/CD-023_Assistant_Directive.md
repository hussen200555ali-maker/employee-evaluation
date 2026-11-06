# توجيه تنفيذي — CD-023 / Sprint 5.0
## تحديث باني القوالب لدعم التقييمات الوصفية — تشغيل + اختبار + UAT

| الحقل | القيمة |
|---|---|
| **من** | كبير المطورين (Lead Developer) |
| **إلى** | المساعد المطور (Assistant Developer) |
| **المرجع** | CD-023 — Sprint 5.0 |
| **تاريخ الإصدار** | 2026-11-06 |
| **آخر commit على `origin/master`** | `ac119dd` |
| **نوع المهمة** | ترحيل + اختبارات + UAT + تقرير باللقطات |

---

## ١) السياق

صدر توجيه كبير المستشارين CD-023 بتحديث باني القوالب (B1) لدعم **التقييمات الوصفية**: إضافة «قسم نصي» و«التقدير العام»، إلغاء شرط الـ 100% للقوالب الوصفية، وحساب الدرجة النهائية من التقدير العام.

تم تنفيذ الكود كاملاً ورفعه في commit `ac119dd`. **دورك:** مزامنة → ترحيل → اختبارات → UAT → تقرير باللقطات.

> **قيد صارم:** لا تُعدِّل أي كود. إن واجهت خطأ، أبلغني بالتفاصيل.

---

## ٢) الملفات المُسلَّمة

### ملفات جديدة
```
database/migrations/2026_11_06_CD023_descriptive_templates.sql   (ترحيل)
tests/cases/Controllers/DescriptiveTemplatesTest.php            (5 اختبارات)
docs/CD-023_Implementation.md                                   (توثيق)
```

### ملفات مُعدَّلة
```
app/Models/GradingScale.php        (+getByName, +getNumericValueForLabel, +getActiveScaleNames)
app/Controllers/BuilderController.php  (validateWeights معاد كتابتها + index يمرر scaleNames)
app/Views/builder/index.php        (+عنصرا قسم نصي + تقدير عام في المكتبة)
public/js/builder.js               (+textarea/overall_grade: addSection, properties, validation)
app/Models/Template.php            (buildStructureFromRequest يخزّن الخصائص في evaluation_options)
app/Views/evaluations/fill.php     (+textarea case, +overall_grade case)
app/Controllers/EvaluationController.php  (submit: extractOverallGradeAnswer + descriptive score)
app/Models/Evaluation.php          (calculateFinalScore descriptive + saveEvaluationResults textarea)
CHANGELOG.md                       (+إدخال CD-023)
```

---

## ٣) خطوات الإعداد (بالترتيب)

### الخطوة ١ — مزامنة
```bash
cd C:\xampp\htdocs\employee-evaluation
git fetch --all
git reset --hard origin/master
git log --oneline -1
# المتوقع: ac119dd feat(CD-023): template builder support for descriptive evaluations
```

### الخطوة ٢ — بوابة الترميز
```bash
php check-encoding.php --path=app
# المتوقع: exit=0 (لا BOM ولا CJK)
```

### الخطوة ٣ — الترحيل (إلزامي)
```bash
mysql -u root employee_evaluation < database/migrations/2026_11_06_CD023_descriptive_templates.sql
```
أو آليتك المعتادة. الترحيل Idempotent (آمن لإعادة التشغيل).

**تحقق:**
```sql
SHOW COLUMNS FROM criteria_sections LIKE 'evaluation_type';
-- المتوقع: enum يحوي 'textarea' و 'overall_grade'

SHOW COLUMNS FROM grading_scales LIKE 'template_id';
-- المتوقع: صف واحد بـ Default NULL
```

### الخطوة ٤ — الاختبارات (إلزامي)
```bash
php tests/runner.php
```
**المتوقع: 136/136 PASS** (132 السابقة + 4 جديدة في `DescriptiveTemplatesTest`؛ الاختبار الثالث guard للانحدار).

الاختبارات الجديدة:
| # | الاختبار | ما يتحقق منه |
|---|---|---|
| 1 | `testValidateWeightsAllowsDescriptiveWithout100Percent` | القالب الوصفي يُعفى من شرط الـ 100% |
| 2 | `testValidateWeightsRequiresOverallGradeForDescriptive` | القالب الوصفي يُلزم بـ "التقدير العام" |
| 3 | `testValidateWeightsNumericStillEnforces100Percent` | القالب الرقمي يظل يُطبّق الـ 100% (حماية انحدار) |
| 4 | `testTextSectionDoesNotAffectFinalScore` | القسم النصي لا يؤثر على الدرجة (تبقى 80.0) |
| 5 | `testOverallGradeCalculatesCorrectlyForDescriptive` | "ممتاز" → final_score = 5.0 |

> **إن فشل أي اختبار:** الصق اسمه + رسالة الخطأ كاملة. لا تُصلح بنفسك.

---

## ٤) سيناريوهات UAT

سجّل الدخول بحساب **Admin** لديه صلاحية `templates.manage` و`evaluations.fill`.

### السيناريو أ — بناء قالب وصفي في الباني
1. افتح `/employee-evaluation/builder/create`
2. **لقطة 1:** المكتبة تُظهر مجموعة «التقييم الوصفي (CD-023)» بـ «قسم نصي» و«التقدير العام».
3. اضبط `evaluation_mode = descriptive`.
4. اسحب «معيار جديد» → اسحب داخله «قسم نصي» → اضغط عليه وعدّل «الحد الأقصى للأحرف» و«إجباري».
5. اسحب «التقدير العام» → اضغط عليه واختر سلم التقديرات من القائمة.
6. **لقطة 2:** لوحة خصائص «التقدير العام» تُظهر اختيار السلم.
7. اضغط «حفظ» → يجب أن ينجح **بدون** تنبيه الـ 100%.
8. **لقطة 3:** صفحة `/templates` تُظهر القالب الجديد.

### السيناريو ب — التحقق من رفض القالب الوصفي بدون تقدير عام
1. في الباني، اضبط `evaluation_mode = descriptive`.
2. أضف معياراً + قسم نصي **بدون** «التقدير العام».
3. اضغط «حفظ» → يجب أن يظهر تنبيه: «القالب الوصفي يجب أن يحتوي على التقدير العام».
4. **لقطة 4:** تنبيه الرفض.

### السيناريو ج — التحقق من حفظ القالب الرقمي (حماية انحدار)
1. في الباني، اضبط `evaluation_mode = numeric`.
2. أضف معياراً (وزن 100) + قسم نسبة مئوية (وزن 100).
3. اضغط «حفظ» → يجب أن ينجح (السلوك الرقمي محفوظ).
4. **لقطة 5:** القالب الرقمي محفوظ في `/templates`.

### السيناريو د — تعبئة تقييم وصفي
1. أنشئ تقييماً جديداً مرتبطاً بالقالب الوصفي من السيناريو أ.
2. افتح شاشة التعبئة `/evaluations/:id/fill`.
3. **لقطة 6:** الأقسام النصية تُرسم كـ Textarea، والتقدير العام كقائمة منزلقة.
4. اكتب نصاً في القسم النصي، واختر «ممتاز» في التقدير العام.
5. اضغط «حفظ وإرسال».
6. **تحقق من DB:**
   ```sql
   SELECT id, status, final_score, final_grade_label
   FROM evaluations WHERE id = <معرف التقييم>;
   -- المتوقع: status='completed', final_score=5.00, final_grade_label='ممتاز'
   ```
7. **لقطة 7:** استعلام DB يُظهر `final_score=5.00` و`final_grade_label='ممتاز'`.

### السيناريو هـ — القسم النصي لا يؤثر على الدرجة الرقمية
1. في قالب رقمي (`evaluation_mode = numeric`)، أضف معياراً يحوي: قسم نسبة مئوية (وزن 100) + قسم نصي (وزن 0).
2. احفظ القالب، أنشئ تقييماً، وعبّئه (النسبة = 75، النص = أي شيء).
3. **تحقق من DB:**
   ```sql
   SELECT final_score FROM evaluations WHERE id = <معرف>;
   -- المتوقع: final_score = 75.00 (القسم النصي لم يؤثر)
   ```
4. **لقطة 8:** استعلام DB يُظهر `final_score=75.00`.

---

## ٥) معايير القبول

| # | المعيار | الحالة |
|---|---|---|
| 1 | `git log` يُظهر `ac119dd` كآخر commit | ☐ |
| 2 | بوابة الترميز `exit=0` | ☐ |
| 3 | الترحيل نُفِّذ: ENUM يحوي `textarea` و`overall_grade` + `template_id` موجود | ☐ |
| 4 | `tests/runner.php` → **136/136 PASS** | ☐ |
| 5 | السيناريو أ: بناء قالب وصفي ينجح بدون 100% | ☐ |
| 6 | السيناريو ب: رفض القالب الوصفي بدون تقدير عام | ☐ |
| 7 | السيناريو ج: القالب الرقمي يظل يتطلب 100% (حماية انحدار) | ☐ |
| 8 | السيناريو د: شاشة التعبئة ترسم Textarea + قائمة التقدير، و`final_score=5.00` | ☐ |
| 9 | السيناريو هـ: القسم النصي لا يؤثر على الدرجة الرقمية (`final_score=75.00`) | ☐ |
| 10 | 8 لقطات شاشة حية | ☐ |

---

## ٦) صيغة التقرير

```
تقرير تنفيذ CD-023 — المساعد المطور
======================================
التاريخ: YYYY-MM-DD HH:mm
بيئة الاختبار: XAMPP v؟؟؟ / PHP v؟؟؟ / MariaDB v؟؟؟

١. المزامنة:
   - آخر commit: ______
   - بوابة الترميز: exit=______

٢. الترحيل:
   - evaluation_type enum يحوي textarea+overall_grade: ✓/✗
   - grading_scales.template_id موجود: ✓/✗

٣. الاختبارات:
   - النتيجة: ___/136 PASS
   - (إن فشل أي اختبار، الصق اسمه + الخطأ)

٤. UAT:
   - السيناريو أ (بناء قالب وصفي):    ✓/✗  ملاحظات: ______
   - السيناريو ب (رفض بدون تقدير عام): ✓/✗  ملاحظات: ______
   - السيناريو ج (حماية انحدار رقمي):  ✓/✗  ملاحظات: ______
   - السيناريو د (تعبئة قالب وصفي):    ✓/✗  ملاحظات: ______
   - السيناريو هـ (قسم نصي لا يؤثر):   ✓/✗  ملاحظات: ______

٥. لقطات الشاشة: (8 مرفقة)

٦. أخطاء/ملاحظات إضافية:
   ______
```

---

## ٧) القواعد الصارمة

- 🚫 **لا تُعدِّل أي كود** إلا بأمر صريح مني.
- 🚫 **لا تتجاوز الترحيل** — الميزة تعتمد على `textarea` و`overall_grade` في الـ ENUM.
- 🚫 **لا تلمس** `Auth.php` أو واجهة `bulk-create` أو حساب أوزان القوالب الرقمية (ضوابط CD-023).
- ✅ أرفق **لقطات حية** (ليس تخطيطات).
- ✅ إن فشل سيناريو، أكمل الباقي وسجّل الملاحظات + رسالة الخطأ.

---

## ٨) ملاحظات تقنية سريعة

- **`evaluation_options` JSON** يخزّن خصائص الأنواع الجديدة:
  - textarea: `{"max_chars":1000,"required":false}`
  - overall_grade: `{"scale_name":"السلم الخماسي"}`
- **القوالب القديمة** غير متأثرة — السلوك الرقمي محفوظ تماماً.
- **آلية `final_grade_id` القديمة** في شاشة التعبئة محفوظة كـ fallback (توافق خلفي).
- **الباني** يمنع إضافة «التقدير العام» أكثر من مرة (تنبيه فوري عند السحب).

**بانتظار تقريرك مع اللقطات.**

— كبير المطورين
