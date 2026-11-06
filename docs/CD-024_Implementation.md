# CD-024 — توثيق التنفيذ
## الرسم الديناميكي لواجهة التقييم الجماعي

| الحقل | القيمة |
|---|---|
| **المرجع** | CD-024 — Sprint 6.0 |
| **تاريخ التنفيذ** | 2026-11-07 |
| **الحالة** | مُنفَّذ، بانتظار UAT |
| **عدد الاختبارات** | 141 (137 + 4 جديدة) |

---

## ١) الهدف

إصلاح واجهة `bulk-create.php` لتعمل بمبدأ **الرسم الديناميكي**: قراءة هيكل القالب (Template Schema) ورسم الحقل المناسب لكل قسم حسب نوعه، بدلاً من رسم أزرار 1-5 ثابتة لكل الحقول.

---

## ٢) التغييرات

### `BulkEvaluationController::schema()` (API)
- كل قسم يُعيد الآن: `criterion_id`، `options` (parsed evaluation_options JSON).
- أقسام `overall_grade`: تُعيد `scale_name` + `grades` (صفوف السلم المُحلَّلة من `grading_scales`) — يحصل الـ frontend على كل البيانات في طلب واحد.

### `bulk-create.php` (واجهة + JS)
- **`renderCardBody()`**: أُعيدت كتابتها لاستدعاء `renderSectionField(s)` لكل قسم.
- **`renderSectionField(s)`** (دالة جديدة): switch على `s.type`:
  - `percentage` → `<input type="number" min=0 max=100>`
  - `numeric` → `<input type="number" min/max>`
  - `grade` → `<select>` من `s.options`
  - `textarea`/`text` → `<textarea>` مع `maxlength`
  - `overall_grade` → `<select>` من `s.grades` (السلم المُحلَّل)
  - `boolean` → radio نعم/لا
  - `helper`/`formula`/`auto` → readonly input
- **`collectResults()`**: أُعيدت كتابتها لجمع القيم عبر `[data-section][data-field]`:
  - `data-field="score"` → `{score: value}` (numeric/percentage/grade/boolean)
  - `data-field="text"` → `{text: value}` (textarea)
  - `data-field="grade_label"` → `{grade_label: value}` (overall_grade)
- **`saveSingle()` serialization**: تُرسل `results[sectionId][grade_label]` لأقسام overall_grade.

### `BulkEvaluationController::saveResults()` (Backend)
- **Type-aware storage**: تحمّل هيكل القالب، وبناء `sectionsMap`، ولكل إجابة:
  - `textarea`/`text` → `raw_value` = النص، `normalized_score` = NULL
  - `overall_grade` → `raw_value` = التسمية، `normalized_score` = NULL
  - `numeric` → `normalized_score` = تطبيع (min/max → 0-100)
  - `percentage`/`grade` → `normalized_score` = القيمة الخام
- **Descriptive score**: يبحث عن قسم `overall_grade`، يقرأ `grade_label`، يحوّله إلى `numeric_value` من `grading_scales` (بالـ `scale_name` المرتبط)، ويضبط `final_score`.
- **Legacy fallback**: `$results['grade_label']` و`$_POST['final_grade_label']` محفوظان للتوافق الخلفي.

---

## ٣) الضوابط المُلتزَم بها

| الضابط | الالتزام |
|---|---|
| عدم لمس `Auth.php` | ✅ |
| عدم لمس منطق باني القوالب (B1) | ✅ |
| عدم لمس `AveragesEngine` | ✅ |
| الحفاظ على 137 اختبار | ✅ + 4 = **141** |
| بوابة الترميز `exit=0` | ✅ |

---

## ٤) الاختبارات الجديدة — `DynamicBulkRenderingTest.php`

| # | الاختبار | ما يتحقق منه |
|---|---|---|
| 1 | `testPercentageSectionStoresNormalizedScore` | percentage: `normalized_score = 75` |
| 2 | `testTextareaSectionStoresTextWithNullScore` | textarea: `raw_value = النص`، `normalized_score = NULL` |
| 3 | `testOverallGradeSectionComputesFinalScoreFromScale` | overall_grade: `final_score = 5.0` من "ممتاز" |
| 4 | `testSchemaReturnsOptionsAndGradesForOverallGrade` | schema API تُعيد `grades` لأقسام overall_grade |

---

## ٥) كيف يعمل الرسم الديناميكي الآن

1. المستخدم يختار قالباً → JS يستدعي `GET /api/templates/{id}/schema`.
2. الـ schema تُعيد: لكل قسم `type` + `options` (للـ grade) + `grades` (لـ overall_grade).
3. `renderSectionField(s)` ترسم الحقل المناسب حسب `s.type`.
4. عند الحفظ، `collectResults` تجمع القيم بـ `data-field`، و`saveResults` تخزّنها بناءً على نوع القسم.

— كبير المطورين
