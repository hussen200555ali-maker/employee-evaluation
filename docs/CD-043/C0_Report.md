# تقرير C0 — قبل بدء التنفيذ
**المهمة:** CD-043 — وحدة تصدير/استيراد Excel
**المرجع:** APPR-EXCEL-IE-006 (نافذ 2026-11-16) + SRS-EXCEL-IE-3.1-final
**الفرع:** `feature/cd-043-excel-ie`
**التاريخ:** 2026-11-16
**الحالة:** ✅ C0 مكتمل

---

## 1. الخطوات المنفذة

| # | الخطوة | النتيجة | الدليل |
|---|---|---|---|
| 1 | إرجاع `routes/web.php` من التعديل المحلي غير الملتزم | ✅ تم | `git diff` نظيف بعد الإرجاع |
| 2 | وسم العودة `pre-cd043-<timestamp>` | ✅ `pre-cd043-20261116_034536` على `master` (`77cbfbb`) | `git tag` |
| 3 | إنشاء فرع التنفيذ | ✅ `feature/cd-043-excel-ie` من `master` | `git branch --show-current` |
| 4 | نسخة DB كاملة (UAT) | ✅ `backups/CD-043/pre_cd043_20261116_034536.sql` (334,907 بايت) | `mysqldump --single-transaction --routines --triggers --events` |
| 5 | خط أساس الدخان | ✅ `ExcelWorkflowSmoke` 4/4 + `CD042DuplicateSmoke` 1/1 | مخرجات التشغيل أدناه |

---

## 2. حالة البيئة

| البند | القيمة |
|---|---|
| MySQL/MariaDB | يعمل (`mysqld is alive`) |
| PHP CLI | 8.2.12 — `C:\xampp\php\php.exe` |
| قاعدة التطبيق | `employee_evaluation_uat` (كما في `config/database.php`) |
| قاعدة الاختبار المعزولة | `employee_evaluation_excel_test` |
| حالة الفرع | `feature/cd-043-excel-ie` عند `77cbfbb` |
| ملفات غير متتبعة | مجلد وثائق SRS العربي فقط (لا يُحذف) |

---

## 3. معايرة قاعدة الاختبار (إلزام C0)

عند الفحص وُجدت `employee_evaluation_excel_test` **متأخرة عن UAT**:

- 37 جدولاً مقابل 46 جدولاً فعلياً في UAT (ناقص 12 جدولاً: `training_programs`, `training_catalogs`, `grading_scales`, `password_history`, `login_attempts`, `report_snapshots`, `report_snapshot_counters`, `admin_notifications`, `admin_report_evaluators`, `course_participants`, `program_participants`, `workshop_participants`).
- 6 جداول مشتركة ببنية مختلفة: `users`, `employees`, `form_templates`, `evaluations`, `evaluation_answers`, `criteria_sections`.
- 3 جداول قديمة غير مستخدمة في الكود (`password_resets`, `report_templates`, `report_template_columns`).

**الإجراء:** أُعيد بناء قاعدة الاختبار من **بنية UAT فقط** (بدون بيانات UAT)، ثم نُقلت بيانات الاختبار التاريخية (الموظفون/القوالب/التقييمات) والبيانات المرجعية (الأدوار/الصلاحيات/الإعدادات/سلالم التقدير) مع تجاهل الأعمدة الجديدة (تأخذ قيمها الافتراضية).

**النتيجة:**

- 46 جدولاً — **0 فروقات DDL** مع UAT (الفرق الوحيد: جدول أرشيف `evaluations_backup_cd0406_20261110` استُثني عمداً).
- النسخة القديمة محفوظة كـ `employee_evaluation_excel_test_old` + نسخة SQL في `backups/CD-043/excel_test_pre_rebuild_20261116_034707.sql`.

---

## 4. خط أساس الدخان

| الاختبار | النتيجة | الخروج |
|---|---|---|
| `tests/ExcelWorkflowSmoke.php` | **4/4** (employee, course, unit, employee_in_course) | 0 |
| `tests/cases/Controllers/CD042DuplicateSmokeTest.php` | **1/1** | 0 |

**أمر التشغيل:** `php tests/ExcelWorkflowSmoke.php` — مباشرة (يثبّت قاعدة الاختبار داخلياً).
اختبارات `tests/runner.php` تقرأ `config/database.php` (UAT)، لذا أُضيف مشغّل معزول `tests/run_isolated.php` يثبّت `DB_NAME=employee_evaluation_excel_test` دون تعديل أي ملف متعقَّب:
`php tests/run_isolated.php CD042Duplicate` → 1/1.

---

## 5. ملاحظات وحدود

1. `config/database.php` يشير إلى UAT. أي تشغيل لمشغّل الاختبارات يجب أن يوجَّه لقاعدة الاختبار (سيُعالج ضمن بنية الاختبارات في P1 دون تعديل إعدادات الإنتاج).
2. حزمة `tests/runner.php` الكاملة تحتوي اختبارات تعتمد على بيانات UAT (مثل `AuthTest` التي تبحث عن مستخدم `sysadmin`)؛ هذه **ليست ضمن خط أساس C0** المحدد (اختبارا الدخان فقط) وستُعاير عند C5 مع الاختبارات الـ29.
3. لا تغييرات إنتاجية على قاعدة UAT حتى الآن. ترحيلات P1 ستُطبَّق على قاعدة الاختبار أولاً.

---

## 6. الخلاصة

**C0 مكتمل:** الفرع والوسم والنسخة الاحتياطية وخط الأساس جاهزة، وقاعدة الاختبار مطابقة لبنية UAT. **الانتقال إلى P1.**
