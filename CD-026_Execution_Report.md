# تقرير CD-026 — المساعد المطور
===============================
التاريخ: 2026-11-08 00:10 (Asia/Riyadh)
آخر commit: `0b8e3a9` (توجيه CD-026) فوق `497c967` (تنفيذ CD-026)
البيئة: XAMPP / PHP 8.2.12 / MariaDB 10.4.32

> **ملاحظة قاعدة البيانات:** التوجيه يذكر `employee_evaluation`، لكن هذه القاعدة **غير موجودة** على السيرفر. قاعدة التطبيق والاختبارات هي `employee_evaluation_uat` (حسب `config/database.php`)، وتم تطبيق الترحيلين عليها.

## ١. المزامنة والترميز
- `git fetch/reset`: ✓ HEAD = `0b8e3a9`، ويشمل `497c967`.
- بوابة الترميز: ✓ `app` = 371 ملفاً exit=0، `tests` = 35 ملفاً exit=0 (استُخدم `..\employee-evaluation-docs\scripts\check-encoding.php` لأن `check-encoding.php` غير موجود في الجذر).

## ٢. الترحيل
| البند | النتيجة |
|---|---|
| `2026_11_08_CD026_missing_permissions.sql` | ✓ exit=0 |
| `2026_11_08_CD026_training_infra.sql` | ✓ exit=0 |
| `course_participants` | ✓ موجود |
| `workshop_participants` | ✓ موجود |
| `form_templates.target_type` | ✓ `enum(...,'admin','skill')` |
| الصلاحيات العشر | ✓ كلها موجودة (إجمالي الصلاحيات 51) |

توزيع الصلاحيات الجديدة: Admin (role 1) = 10/10، HR (role 2) = 7، Manager (role 3) = 2 (`evaluations.delete/edit`).

## ٣. الاختبارات
**`php tests/runner.php` لم يُكمل ولم يُخرج 148/148.** التوقف الحاد:

```
BulkEvaluationControllerTest   6/6   نجح
{"error":"غير مصرح — تسجيل الدخول مطلوب"}
```

**السبب الجذري:** `CD026IntegrationTest::testApiControllerRequiresAuth` (السطر 87) ينشئ `new ApiController()`، والـ constructor يستدعي `exit()` عند غياب الجلسة. `exit()` يُنهي عملية PHP كاملة ولا يمكن التقاطه بـ `try/catch`، فيموت مشغّل الاختبارات قبل طباعة أي ملخص.

### تفصيل النتائج عند تشغيل الملفات منفردة
- **بقية الملفات (141 اختباراً): 139/141 نجح**، وفشل اختباران بسبب توقعات قديمة لم تُحدَّث مع تغيير CD-026:
  1. `DescriptiveTemplatesTest::testOverallGradeCalculatesCorrectlyForDescriptive`
     - `الدرجة يجب أن تكون 5.0 (قيمة "ممتاز" في السلم الخماسي). — متوقع: 5.0 وجد: 100.0`
     - `tests/cases/Controllers/DescriptiveTemplatesTest.php:274`
  2. `DynamicBulkRenderingTest::testOverallGradeSectionComputesFinalScoreFromScale`
     - `final_score يجب أن يكون 5.0 (قيمة ممتاز). — متوقع: 5.0 وجد: 100.0`
     - `tests/cases/Controllers/DynamicBulkRenderingTest.php:180`
  - هذان الفشلان يؤكدان أن السلوك الجديد (0-100) يعمل، لكن الاختبارين لم يُحدَّثا.
- **`CD026IntegrationTest` (7 اختبارات): 6/7 نجح عند تشغيلها منفردة**:
  - ✓ `testAveragesEngineIgnoresNullFinalScore`
  - ✓ `testCourseParticipantsTableExists`
  - ✓ `testWorkshopParticipantsTableExists`
  - ✓ `testSkillTypeExistsInEnum`
  - ✓ `testMissingPermissionsExist`
  - ✓ `testDescriptiveScoreIsOn100Scale` (ممتاز → 100.0)
  - ✗ `testApiControllerRequiresAuth` — يُنهي العملية بـ `exit()` (لا يمكن احتسابه).

**النتيجة العملية:** 145/148 إن أُصلح الاختبار القاتل، و148/148 بعد تحديث توقُّعَي الاختبارين القديمين.

## ٤. معايير القبول
| # | المعيار | الحالة |
|---|---|---|
| 1 | الثغرات الأمنية الأربعة (P0) مغلقة | ⚠️ جزئي — انظر أدناه |
| 2 | `course_participants` + `workshop_participants` | ✓ |
| 3 | `skill` في `target_type` ENUM | ✓ |
| 4 | الصلاحيات المفقودة موجودة | ✓ |
| 5 | الوصفي يُحوّل لـ 0-100 (ممتاز→100) | ✓ |
| 6 | AveragesEngine يتجاهل NULL | ✓ |
| 7 | ApiController يتطلب مصادقة (401) | ✓ (HTTP 401 عبر curl) |
| 8 | 148/148 PASS | ✗ (145/148 فعلياً + انظر الملاحظات) |
| 9 | بوابة الترميز exit=0 | ✓ |

### تفصيل المعيار ١ (P0)
- **A1 — audit_logs:** ✓ الكود لم يعد يكتب كلمة المرور؛ `$auditData` يحوي `password_changed: bool` فقط (`git show 497c967`). **لكن** توجد **3 صفوف قديمة** في `audit_logs.details` ما زالت تحوي كلمات مرور نصية:
  - `id=364` → `"password":"123456"`
  - `id=365` → `"password":"fzahrani"`
  - `id=366` → `"password":"fzahrani"`
  - لا يوجد ترحيل لتنظيفها. **تحتاج تنظيفاً عاجلاً (P0 متبقٍ).**
- **A2 — ApiController:** ✓ `Auth::check()` في الـ constructor؛ تحقق حي: `GET /api/employees/search?q=test` بلا جلسة → **HTTP 401** + JSON.
- **A3 — backup GET→POST:** ✓ `git show 497c967 -- routes/web.php` يحوّل مسارات `backup/full|incremental|optimized|smart|delete|cleanup|clean-backups|setup-scheduler|remove-scheduler|test-scheduler|encryption/generate-key|encryption/delete-key` إلى POST. تحقق حي: `GET /backup/full` → **404** (لم يعد ينفّذ نسخة).
- **A4 — الصلاحيات:** ✓ 10 صلاحيات مضافة وموزعة.

## ٥. ملاحظات إضافية
1. لا تعديلات على أي ملف متتبع: `git status` يعرض فقط ملفات تقارير/لقطات غير متتبعة سابقة.
2. لم يُنشأ أي commit أو tag.
3. لم تُصلَح الاختبارات الفاشلة ولا الثغرة المتبقية في `audit_logs`، التزاماً بقيد «لا تعدّل الكود».
4. الترحيلان Idempotent جزئياً (`INSERT IGNORE` و`CREATE TABLE IF NOT EXISTS`)، لكن `MODIFY COLUMN` قابل لإعادة التشغيل بأمان.
