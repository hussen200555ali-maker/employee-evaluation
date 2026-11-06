# توجيه تنفيذي — CD-022 / Sprint 4.0
## ميزة التقييم الجماعي (Bulk Evaluation) — تسليم الكود + التشغيل + UAT

| الحقل | القيمة |
|---|---|
| **من** | كبير المطورين (Lead Developer) |
| **إلى** | المساعد المطور (Assistant Developer) |
| **المرجع** | CD-022 — Sprint 4.0 |
| **تاريخ الإصدار** | 2026-11-06 |
| **آخر commit على `origin/master`** | `1a5427b` |
| **نوع المهمة** | تنفيذ + اختبار + UAT + تقرير باللقطات |

---

## ١) السياق

صدر توجيه كبير المستشارين CD-022 ببناء ميزة **التقييم الجماعي** التي تتيح للمدير تقييم عدة موظفين في جلسة واحدة، معتمدةً على الموك-أب المعتمد (`mockups/bulk-eval/bulk_eval.html` — تقييم 10/10).

تم تنفيذ الكود كاملاً ورفعه إلى GitHub على فرع `master` في commits:

| Commit | المحتوى |
|---|---|
| `f00c7d6` | التنفيذ الأساسي: Controller + View + Routes + Migration |
| `1a5427b` | widget لوحة التحكم + 6 اختبارات جديدة |

**دورك:** مزامنة المستودع → تشغيل الترحيل → تشغيل الاختبارات → تنفيذ UAT على المتصفح → إرسال تقرير مع 8 لقطات شاشة حية.

---

## ٢) الملفات المُسلَّمة

### ملفات جديدة
```
app/Controllers/BulkEvaluationController.php         (377 سطر)
app/Views/evaluations/bulk-create.php                (387 سطر)
database/migrations/2026_11_06_CD022_bulk_eval.sql   (20 سطر)
tests/cases/Controllers/BulkEvaluationControllerTest.php  (6 اختبارات)
```

### ملفات مُعدَّلة
```
routes/web.php                        (+5 مسارات)
app/Services/RoleDashboardService.php (+ميثود incompleteProfiles)
app/Views/dashboard/index.php         (+widget التنبيه الذهبي)
public/css/modern-ui.css              (+أنماط .cd022-incomplete-alert*)
```

### المسارات الـ5 الجديدة في `routes/web.php`
| الطريقة | المسار | الـ Controller@Method |
|---|---|---|
| GET | `evaluations/bulk-create` | `BulkEvaluationController@create` |
| GET | `api/templates/:id/schema` | `BulkEvaluationController@schema` |
| POST | `api/employees/quick-add` | `BulkEvaluationController@quickAdd` |
| POST | `evaluations/save-single` | `BulkEvaluationController@saveSingle` |
| POST | `evaluations/bulk-save` | `BulkEvaluationController@bulkSave` |

### موضوع الترحيل `2026_11_06_CD022_bulk_eval.sql`
- يضيف عمود `profile_status ENUM('complete','incomplete') NOT NULL DEFAULT 'complete'` على جدول `employees`.
- يجعل `position_id` و`hire_date` و`manager_id` قابلة لـ NULL (لدعم الإضافة السريعة لموظف ببيانات مختصرة).
- **Idempotent** (يستخدم `ADD COLUMN IF NOT EXISTS` و`MODIFY COLUMN`).

---

## ٣) خطوات الإعداد على XAMPP (بالترتيب — لا تتجاوز خطوة)

### الخطوة ١ — مزامنة المستودع
```bash
cd C:\xampp\htdocs\employee-evaluation
git fetch --all
git reset --hard origin/master
git log --oneline -3
```
**المتوقع:** أحدث سطر هو:
```
1a5427b feat(CD-022): dashboard incomplete-profiles widget + bulk evaluation tests
```

### الخطوة ٢ — بوابة الترميز (إلزامية قبل أي تشغيل)
```bash
php check-encoding.php
```
**المتوقع:** PASS — لا BOM ولا CJK في أي ملف.
> إن رفض البوابة ملفاً، **لا تُعدِّله يدوياً** — أبلغني فوراً مع لقطة الشاشة.

### الخطوة ٣ — تشغيل الترحيل
```bash
php database/migrate.php
```
أو آلية التشغيل المعتادة في المشروع إن كانت مختلفة.

**تحقق يدوي بعد الترحيل:**
```sql
SHOW COLUMNS FROM employees LIKE 'profile_status';
-- المتوقع: صف واحد بـ Type: enum('complete','incomplete') و Default: 'complete'
```

### الخطوة ٤ — تشغيل الاختبارات (إلزامي)
```bash
php tests/runner.php
```
**المتوقع:** جميع الاختبارات PASS، العدد الإجمالي **132** (126 السابقة + 6 جديدة).

#### الاختبارات الجديدة داخل `BulkEvaluationControllerTest.php`
| # | اسم الاختبار | ما يتحقق منه |
|---|---|---|
| 1 | `testBulkCreatePageRendersTemplateSelector` | الصفحة ترسم وتحتوي منتقي القوالب |
| 2 | `testSchemaReturnsTemplateStructureAsJson` | واجهة schema تُعيد JSON صحيح بالمعايير |
| 3 | `testQuickAddCreatesIncompleteEmployee` | الموظف المختصر حالته `incomplete` |
| 4 | `testQuickAddRejectsDuplicateCode` | رفض الرقم الوظيفي المكرر (مسار 409) |
| 5 | `testSaveSingleCreatesCompletedEvaluationWithScore` | التقييم يكتمل والدرجة النهائية = 85 |
| 6 | `testDashboardShowsIncompleteProfilesWidget` | widget لوحة التحكم يظهر في الخدمة والعرض |

> **إن فشل أي اختبار:** الصق اسمه + رسالة الخطأ كاملة. لا تُصلح بنفسك.

---

## ٤) سيناريوهات UAT على المتصفح (Golden Path)

سجّل الدخول بحساب **Admin** أو **Manager** لديه صلاحية `evaluations.create`.

### السيناريو أ — التقييم الجماعي الفردي (المسار الكامل)
1. افتح: `http://localhost/employee-evaluation/evaluations/bulk-create`
2. **لقطة 1:** صفحة "التقييم الجماعي" كاملة (شريط الإعداد + حقل البحث + زر "إضافة موظف جديد").
3. اختر **قالب تقييم رقمي** من القائمة المنسدلة.
4. ابحث عن موظف موجود (اكتب أول حرفين من اسمه) → تظهر قائمة autocomplete.
5. اختر موظفاً → تظهر بطاقة تقييم بالمعايير والأزرار (1-5 أو نسبة مئوية حسب نوع القالب).
6. **لقطة 2:** بطاقة موظف بعد اختيار الدرجات (قبل الحفظ).
7. اضغط **"حفظ"** على البطاقة → تتحول البطاقة للون الأخضر + شارة "محفوظ" + تعطيل الحقول.
8. **لقطة 3:** البطاقة بعد الحفظ (اللون الأخضر + الشارة).

### السيناريو ب — الإضافة السريعة (Quick Add)
1. على نفس الصفحة، اضغط **"＋ إضافة موظف جديد"**.
2. تظهر form إضافة مختصرة inline (الاسم + الرقم الوظيفي + الفرع + القسم — تتابع فرع→قسم).
3. أدخل موظفاً جديداً برقم وظيفي غير مستخدم → اضغط **"إضافة"**.
4. **لقطة 4:** بطاقة الموظف الجديد تظهر فوراً مع بياناته.
5. أكمل تقييمه واحفظه.

### السيناريو ج — حفظ الكل (Save All)
1. أضف 3 موظفين على الأقل واملأ درجاتهم **دون** الضغط على "حفظ" الفردي.
2. لاحظ شريط الملخص السفلي الثابت: "الإجمالي: 3 · المحفوظ: 0 · المتبقي: 3".
3. **لقطة 5:** شريط الملخص السفلي قبل الحفظ الكلي.
4. اضغط **"حفظ الكل"** → تتحول جميع البطاقات للون الأخضر + يتحديث العداد.
5. **لقطة 6:** جميع البطاقات بعد "حفظ الكل" + العداد يُظهر "المحفوظ: 3".

### السيناريو د — widget لوحة التحكم (CD-022 widget)
1. اذهب إلى: `http://localhost/employee-evaluation/dashboard`.
2. **لقطة 7:** لوحة التحكم — يجب أن يظهر **تنبيه ذهبي** تحت قسم الـ hero بعنوان:
   "عدد ملف موظف غير مكتمل" + قائمة بأسماء الموظفين الذين أضفتهم سريعاً + زر "فتح التقييم الجماعي".
3. اضغط زر "فتح التقييم الجماعي" → يُعيدك إلى صفحة التقييم الجماعي.

### السيناريو هـ — التحقق من سلامة البيانات في قاعدة البيانات
```sql
-- الموظفون المُضافون سريعاً يجب أن تكون حالتهم incomplete:
SELECT id, emp_code, full_name, profile_status
FROM employees
WHERE profile_status = 'incomplete'
ORDER BY id DESC LIMIT 10;

-- التقييمات المحفوظة اليوم يجب أن تكون مكتملة ولها درجة:
SELECT id, employee_name, status, final_score, evaluation_date
FROM evaluations
WHERE evaluation_date = CURDATE()
ORDER BY id DESC LIMIT 10;
```
**لقطة 8:** نتيجة الاستعلامين من phpMyAdmin أو CLI.

---

## ٥) معايير القبول (Acceptance Criteria)

| # | المعيار | الحالة |
|---|---|---|
| 1 | `git log` يُظهر commit `1a5427b` كآخر commit | ☐ |
| 2 | بوابة الترميز PASS | ☐ |
| 3 | الترحيل نُفِّذ وعمود `profile_status` موجود بـ Default = `complete` | ☐ |
| 4 | `tests/runner.php` → **132/132 PASS** | ☐ |
| 5 | السيناريو أ (التقييم الجماعي الفردي) يعمل | ☐ |
| 6 | السيناريو ب (الإضافة السريعة) يعمل | ☐ |
| 7 | السيناريو ج (حفظ الكل) يعمل | ☐ |
| 8 | السيناريو د (widget لوحة التحكم) يظهر | ☐ |
| 9 | السيناريو هـ (البيانات سليمة في DB) | ☐ |
| 10 | 8 لقطات شاشة حية مرفقة بالتقرير | ☐ |

---

## ٦) صيغة التقرير المطلوب منك (أرسله عند الانتهاء)

انسخ هذا القالب واملأه:

```
تقرير تنفيذ CD-022 — المساعد المطور
======================================
التاريخ: YYYY-MM-DD HH:mm
بيئة الاختبار: XAMPP v؟؟؟ / PHP v؟؟؟ / MariaDB v؟؟؟

١. المزامنة:
   - آخر commit: ______
   - بوابة الترميز: PASS / FAIL

٢. الترحيل:
   - عمود profile_status: موجود / غير موجود
   - Default: ______
   - ملاحظات: ______

٣. الاختبارات:
   - النتيجة: ___/132 PASS
   - (إن فشل أي اختبار، الصق اسمه + رسالة الخطأ هنا)

٤. UAT:
   - السيناريو أ (تقييم فردي):    ✓/✗  ملاحظات: ______
   - السيناريو ب (إضافة سريعة):    ✓/✗  ملاحظات: ______
   - السيناريو ج (حفظ الكل):       ✓/✗  ملاحظات: ______
   - السيناريو د (widget لوحة):    ✓/✗  ملاحظات: ______
   - السيناريو هـ (سلامة DB):      ✓/✗  ملاحظات: ______

٥. لقطات الشاشة: (مرفقة — 8 لقطات مرقّمة)

٦. أخطاء/ملاحظات إضافية:
   ______
```

---

## ٧) قواعد صارمة

- 🚫 **لا تُعدِّل أي كود** إلا بأمر صريح مني. إن واجهت خطأ، أبلغني بالتفاصيل وسأرسل الإصلاح.
- 🚫 **لا تتجاوز بوابة الترميز** — إن رفضت ملفاً، أبلغني.
- 🚫 **لا تتجاوز الترحيل** — الميزة تعتمد على عمود `profile_status` وبدونه تنهار.
- ✅ أرفق **لقطات شاشة حية** (ليس تخطيطات) — كل لقطة للصفحة الفعلية في المتصفح.
- ✅ إن فشل سيناريو، **لا تُعلِّق الباقي** — أكمل بقية السيناريوهات وسجِّل الملاحظات لكل واحد.
- ✅ إن ظهر خطأ 500 في أي صفحة، الصق رسالة الخطأ من `app/storage/logs/` أو من عرض PHP.

---

## ٨) ملاحظات تقنية للمطور (مرجع سريع)

- **الـ Controller** يستدعي ميثودات موجودة مسبقاً في النماذج (`Evaluation::calculateFinalScore`, `Evaluation::updateScoreAndStatus`, `Template::getTemplateWithStructure`, `Employee::findById`) — لا حاجة لأي تعديل في النماذج.
- **حساب الدرجة:** `calculateFinalScore` يأخذ `normalized_score` مباشرة (0-100) ويطبّق أوزان الأقسام والمعايير. الدرجة النهائية على مقياس 0-100.
- **القوالب الوصفية (descriptive/mixed):** لا تُحتسب درجتها آلياً؛ تستخدم درجة يدوية من `grading_scales`.
- **CSRF:** الحقول تقبل `_token` أو `_csrf` (توحيد تم في Sprint سابق).
- **widget لوحة التحكم** دفاعي: إن لم يُنفَّذ الترحيل بعد، تُرجع الخدمة مصفوفة فارغة (try/catch) ولا تنهار اللوحة.

---

**بانتظار تقريرك مع اللقطات.**

— كبير المطورين
