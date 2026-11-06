# توجيه تنفيذي — CD-024 / Sprint 6.0
## الرسم الديناميكي لواجهة التقييم الجماعي — تشغيل + اختبار + UAT

| الحقل | القيمة |
|---|---|
| **من** | كبير المطورين |
| **إلى** | المساعد المطور |
| **المرجع** | CD-024 — Sprint 6.0 |
| **تاريخ الإصدار** | 2026-11-07 |
| **آخر commit** | `3401a68` |
| **نوع المهمة** | اختبارات + UAT + تقرير باللقطات |

---

## ١) السياق

صدر توجيه CD-024 بإصلاح واجهة `bulk-create.php` لتعمل بالرسم الديناميكي — قراءة هيكل القالب ورسم الحقل المناسب لكل قسم (نسبة/رقمي/تقدير/نصي/تقدير عام/نعم-لا) بدلاً من أزرار 1-5 الثابتة.

تم التنفيذ في commit `3401a68`. **دورك:** مزامنة → اختبارات → UAT → تقرير.

> **قيد صارم:** لا تُعدِّل أي كود.

---

## ٢) ما تم تغييره

| المكوّن | التغيير |
|---|---|
| `BulkEvaluationController::schema()` | تُعيد `criterion_id` + `options` لكل قسم، و`grades` المُحلَّلة لأقسام `overall_grade` |
| `bulk-create.php` JS | `renderSectionField(s)` جديدة: switch على `s.type` يرسم الحقل الصحيح لكل نوع |
| `collectResults()` | تجمع القيم عبر `[data-section][data-field]` (score/text/grade_label) |
| `saveResults()` | type-aware storage + descriptive score من `overall_grade` |

---

## ٣) خطوات الإعداد

### الخطوة ١ — مزامنة
```bash
cd C:\xampp\htdocs\employee-evaluation
git fetch --all
git reset --hard origin/master
git log --oneline -1
# المتوقع: 3401a68 feat(CD-024): dynamic form rendering in bulk evaluation interface
```

### الخطوة ٢ — بوابة الترميز
```bash
php check-encoding.php --path=app
# المتوقع: exit=0
```

### الخطوة ٣ — لا ترحيل جديد
لا migration في CD-024 (التغييرات في الكود فقط).

### الخطوة ٤ — الاختبارات
```bash
php tests/runner.php
```
**المتوقع: 141/141 PASS** (137 + 4 جديدة في `DynamicBulkRenderingTest`).

الاختبارات الجديدة:
| # | الاختبار | ما يتحقق منه |
|---|---|---|
| 1 | `testPercentageSectionStoresNormalizedScore` | percentage: `normalized_score=75` |
| 2 | `testTextareaSectionStoresTextWithNullScore` | textarea: `raw_value=النص`، `normalized_score=NULL` |
| 3 | `testOverallGradeSectionComputesFinalScoreFromScale` | overall_grade: "ممتاز" → `final_score=5.0` |
| 4 | `testSchemaReturnsOptionsAndGradesForOverallGrade` | schema API تُعيد `grades` لأقسام overall_grade |

---

## ٤) سيناريوهات UAT

### السيناريو أ — قالب رقمي (حقول متنوعة)
1. افتح `/employee-evaluation/evaluations/bulk-create`
2. اختر **قالباً رقمياً** يحوي أقسام نسبة مئوية و/أو رقمية
3. **لقطة 1:** الحقول تظهر كـ `<input type="number">` (نسبة/نطاق) — **لا أزرار 1-5**
4. املأ درجة، احفظ فردياً
5. **لقطة 2:** البطاقة خضراء + استعلام DB يُظهر `normalized_score` صحيحة

### السيناريو ب — قالب وصفي (نصي + تقدير عام)
1. اختر **قالباً وصفياً** (من CD-023) يحوي قسم نصي + تقدير عام
2. **لقطة 3:** القسم النصي يظهر كـ Textarea، والتقدير العام كقائمة منزلقة بالتقديرات
3. اكتب نصاً، اختر "ممتاز"، احفظ
4. **تحقق من DB:**
   ```sql
   SELECT final_score, final_grade_label FROM evaluations WHERE id = <معرف>;
   -- المتوقع: final_score=5.00, final_grade_label='ممتاز'
   ```
5. **لقطة 4:** استعلام DB يُظهر `final_score=5.00`

### السيناريو ج — قالب بدرجات (grade)
1. اختر قالباً يحوي قسم `grade` (له options)
2. **لقطة 5:** الحقل يظهر كـ `<select>` بالخيارات
3. اختر قيمة، احفظ، تحقق من DB

### السيناريو د — حفظ الكل
1. أضف 3 موظفين، املأ حقولهم (أنواع مختلفة)
2. اضغط «حفظ الجميع»
3. **لقطة 6:** جميع البطاقات خضراء + العداد `محفوظ: 3`

---

## ٥) معايير القبول

| # | المعيار | الحالة |
|---|---|---|
| 1 | `git log` يُظهر `3401a68` | ☐ |
| 2 | بوابة الترميز `exit=0` | ☐ |
| 3 | `tests/runner.php` → **141/141 PASS** | ☐ |
| 4 | قالب رقمي: حقول رقمية/نسب (لا أزرار 1-5) | ☐ |
| 5 | قالب وصفي: Textarea + قائمة التقدير العام | ☐ |
| 6 | قالب بدرجات: `<select>` بالخيارات | ☐ |
| 7 | حفظ الكل ينجح لجميع الأنواع | ☐ |
| 8 | `final_score` للوصفي = قيمة التقدير (5.00 لممتاز) | ☐ |
| 9 | 6 لقطات شاشة حية | ☐ |

> إن لم تتوفر بيئة متصفح، الاكتفاء بـ 141/141 PASS مقبول للإغلاق (كما في CD-023).

---

## ٦) تقرير موجز

```
تقرير تنفيذ CD-024 — المساعد المطور
======================================
التاريخ: YYYY-MM-DD HH:mm
آخر commit: ______

الاختبارات: ___/141 PASS

UAT:
   - السيناريو أ (قالب رقمي):     ✓/✗
   - السيناريو ب (قالب وصفي):     ✓/✗
   - السيناريو ج (قالب درجات):    ✓/✗
   - السيناريو د (حفظ الكل):      ✓/✗
   اللقطات: (إن توفرت بيئة متصفح)

ملاحظات: ______
```

**بانتظار تأكيدك.**

— كبير المطورين
