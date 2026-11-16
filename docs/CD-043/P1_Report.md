# تقرير P1 — التأسيس وإعادة التفعيل
**المهمة:** CD-043 — وحدة تصدير/استيراد Excel
**المرجع:** APPR-EXCEL-IE-006 + SRS-EXCEL-IE-3.1-final
**الفرع:** `feature/cd-043-excel-ie`
**الفترة:** 2026-11-16
**حالة P1:** ✅ مكتمل — **بانتظار اعتماد C1 قبل بدء P2**

---

## 1. ما أُنجز

| # | البند | الحالة |
|---|---|---|
| 1 | إعادة تفعيل مسارات Excel في `routes/web.php` | ✅ |
| 2 | إضافة رابط الوحدة في الشريط الجانبي وفق الصلاحية | ✅ |
| 3 | ترحيل M1 — صلاحية `evaluations.export` ومنحها لـAdmin/HR/Manager | ✅ |
| 4 | ترحيل M2 — إعدادات Excel الستة في `settings` | ✅ |
| 5 | ترحيل M4 — أعمدة وفهارس `evaluation_import_batches` | ✅ |
| 6 | Rollback لكل ترحيل (M1/M2/M4) | ✅ |
| 7 | اختبار التطبيق ثم التراجع ثم إعادة التطبيق على قاعدة الاختبار | ✅ |
| 8 | مشغّل اختبارات معزول يمنع الكتابة في UAT | ✅ |
| 9 | خط أساس الدخان بعد التغييرات | ✅ 4/4 + 1/1 |

---

## 2. الملفات

**جديدة:**
- `database/migrations/2026_11_16_CD043_permissions.sql` + `_down.sql`
- `database/migrations/2026_11_16_CD043_settings.sql` + `_down.sql`
- `database/migrations/2026_11_16_CD043_import_batches.sql` + `_down.sql`
- `tests/run_isolated.php` (يثبّت `DB_NAME=employee_evaluation_excel_test` قبل تحميل bootstrap)
- `docs/CD-043/C0_Report.md`

**معدّلة:**
- `routes/web.php` — تفعيل 8 مسارات Excel، وإبقاء مسارات الوحدات/المرن/الجدولة مؤرشفة في تعليق مستقل.
- `app/Views/layouts/sidebar.php` — رابط «استيراد وتصدير Excel» يظهر لمن يملك `evaluations.fill` أو `evaluations.export`، مع ضبط تمييز الرابط النشط.

**لم يُعدَّل:** أي إعداد إنتاجي، أو `config/database.php`، أو قاعدة UAT.

---

## 3. تفاصيل الترحيلات

| # | المحتوى | التحقق |
|---|---|---|
| M1 | `permissions`: `evaluations.export` + `role_permissions` لـ Admin/HR/Manager | الصلاحية موجودة ومنحها الأدوار الثلاثة |
| M2 | `excel.max_file_size_mb=50`, `min_rows=50`, `max_rows=2000`, `retention_audit_months=12`, `retention_rejected_batches_days=30`, `retention_batches_days=365` | 6 مفاتيح موجودة بقيمها |
| M4 | 7 أعمدة: `scope_json`, `original_file_path`, `file_size`, `file_sha256`, `committing_at`, `failed_at`, `failure_reason` + فهرسان `(status, created_at)` و`(file_sha256)` | 0 أعمدة/فهارس ناقصة |

**دورة الاختبار على `employee_evaluation_excel_test`:**

| الخطوة | النتيجة |
|---|---|
| تطبيق M1 + M2 + M4 | `APPLIED` — الصلاحية ومنحها + 6 إعدادات + 7 أعمدة + فهرسان |
| تطبيق Rollback بترتيب عكسي (M4 ← M2 ← M1) | `ROLLED_BACK` — لا صلاحية، لا إعدادات، لا أعمدة/فهارس |
| إعادة التطبيق | `APPLIED` مجدداً |

> قاعدة UAT لم تُلمس. تطبيق الترحيلات على UAT يبقى خطوة نشر منفصلة عند الاعتماد.

---

## 4. التحقق من المسارات

بعد تحميل `routes/web.php` فعلياً (Reflection على جدول الموجه):

```
ACTIVE_EXCEL_ROUTES (10):
  GET  evaluations/excel/import
  GET  evaluations/excel/template
  GET  evaluations/excel/template/:id
  GET  evaluations/excel/training-template
  POST evaluations/excel/preview
  POST evaluations/excel/commit/:id
  GET  api/training-templates
  GET  api/training-list
  GET  api/training-programs/:id
  GET  api/training-programs/list/:type
ARCHIVED_ROUTES_PRESENT (0)
```

- طلب فعلي لـ `GET /employee-evaluation/evaluations/excel/import` أعاد **403 من حارس `evaluations.fill`** (أي أن المسار مفعّل ووصل إلى المتحكم؛ قبل التفعيل كان 404).
- `RouterTest`: **7/7**.

---

## 5. الاختبارات المنفذة

| الاختبار | النتيجة |
|---|---|
| `php tests/ExcelWorkflowSmoke.php` | **4/4** — employee / course / unit / employee_in_course |
| `php tests/run_isolated.php CD042Duplicate` | **1/1** |
| `php tests/run_isolated.php RouterTest` | **7/7** |
| `php -l` على `routes/web.php`, `sidebar.php`, `EvaluationExcelController.php`, `tests/run_isolated.php` | ✅ لا أخطاء |

---

## 6. ملاحظات مخاطر

1. **المسارات مفعّلة الآن على بيئة UAT المحلية** (الكود مشترك) بينما خدمة Excel ما زالت بالنسخة القديمة. هذا مطابق لخطة P1 (إعادة التفعيل)، لكن أي استخدام حقيقي قبل C5 سيمرّ بالمسار القديم. الحارس الحالي `evaluations.fill` قائم.
2. `evaluations.export` غير موجودة بعد في UAT (الترحيل لم يُطبَّق هناك)؛ لا يوجد اعتماد كودي عليها حتى P2.
3. حزمة `tests/runner.php` الكاملة تحتوي اختبارات تعتمد على بيانات UAT (مثل `AuthTest`)؛ تُعاير عند C5. خط أساس C0/C1 هو اختبارات Excel + CD042 + Router.
4. قاعدة الاختبار القديمة محفوظة (`employee_evaluation_excel_test_old`) للرجوع عند الحاجة.

---

## 7. قائمة اعتماد C1

- [x] مسارات Excel فعّالة ومحمية بالصلاحية.
- [x] رابط الشريط الجانبي وفق الصلاحية.
- [x] M1/M2/M4 مطبّقة على قاعدة الاختبار.
- [x] Rollback مثبت لكل ترحيل.
- [x] قاعدة الاختبار مطابقة لبنية UAT (0 فروقات DDL).
- [x] خط الأساس أخضر (4/4 + 1/1) + Router 7/7.

**التالي (P2 — 8 أيام):** باني القالب وشاشة التصدير (`ExcelTemplateBuilder`، فصل واجهة التصدير عن الاستيراد، إزالة كلمة سر الورقة والعمود المساعد).

**لا يبدأ P2 قبل اعتماد C1 رسمياً.**
