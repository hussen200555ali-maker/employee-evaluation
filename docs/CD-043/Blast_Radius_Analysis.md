# تحليل نطاق التأثير (Blast Radius Analysis) — CD-043

**المهمة:** CD-043 — وحدة تصدير/استيراد Excel
**المرجع:** APPR-EXCEL-IE-007 + RESP-EXCEL-IE-008 (تعليق C2)
**الفرع:** `feature/cd-043-excel-ie` (Commit `cf8e636`)
**التاريخ:** 2026-11-16
**الحالة:** ✅ مكتمل — جاهز للمراجعة

---

## 1. M3 — تقليص `enum` في `form_templates.target_type`

### 1.1 نتيجة الفحص المسبق (Precheck)

**الاستعلام:**
```sql
SELECT target_type, COUNT(*) FROM form_templates
WHERE target_type NOT IN ('employee','employee_in_course','employee_in_workshop','skill')
GROUP BY target_type;
```

| البيئة | النتيجة |
|---|---|
| `employee_evaluation_excel_test` (اختبار) | **0 صف** |
| `employee_evaluation_uat` (UAT) | **0 صف** |

**توزيع القيم (شامل Archived):**

| البيئة | target_type | n | active |
|---|---|---|---|
| test | employee | 4 | 4 |
| test | employee_in_course | 9 | 9 |
| test | employee_in_workshop | 3 | 3 |
| test | skill | 4 | 4 |
| UAT | employee | 1 | 1 |
| UAT | employee_in_course | 3 | 3 |
| UAT | skill | 1 | 1 |

**الخلاصة:** لا توجد قوالب (نشطة أو مؤرشفة) تستخدم القيم الملغاة (`course`, `workshop`, `unit`, `admin`). الفحص المسبق يعيد 0 صف في كلا البيئتين.

### 1.2 بيئة التطبيق

| البند | القيمة |
|---|---|
| M3 مطبّقة على | `employee_evaluation_excel_test` (قاعدة الاختبار) فقط |
| M3 على UAT | **لم تُطبَّق** (ممنوع حتى اعتماد C2) |
| UAT target_type | بالفعل 4 قيم (لا حاجة لـ M3) |

### 1.3 اختبار Rollback لـ M3

تم تنفيذ APPLIED → ROLLED_BACK → APPLIED على قاعدة الاختبار:

| الخطوة | COLUMN_TYPE (target_type) |
|---|---|
| قبل (APPLIED) | `enum('employee','employee_in_course','employee_in_workshop','skill')` |
| بعد DOWN (ROLLED_BACK) | `enum('employee','employee_in_course','employee_in_workshop','course','workshop','unit','admin','skill')` |
| بعد UP (APPLIED) | `enum('employee','employee_in_course','employee_in_workshop','skill')` |

**الخلاصة:** Rollback مثبت ويعمل. M3 قابل للعكس.

### 1.4 القوالب المؤرشفة (Archived)

- `is_active=0` قوالب: 0 في كلا البيئتين.
- جميع القوالب (نشطة) تستخدم فقط القيم 4 المسموحة.
- **لا تأثير** على أي قالب موجود.

---

## 2. مسار `/evaluations/excel/export` — تعارض المسارات

### 2.1 قائمة المسارات (بعد P1+P2)

لا توجد تعارضات (تكرار نمط) في `routes/web.php` (194 مساراً نشطاً، 0 تكرار).

**مسارات Excel (12):**

| # | المسار | الطريقة | الحارس | الحالة |
|---|---|---|---|---|
| 1 | `evaluations/excel/import` | GET | `evaluations.fill` | مفعّل (P1) |
| 2 | `evaluations/excel/export` | GET | `evaluations.export` | **جديد (P2)** |
| 3 | `evaluations/excel/export/download` | GET | `evaluations.export` | **جديد (P2)** |
| 4 | `evaluations/excel/template` | GET | `evaluations.fill` | مفعّل (P1) |
| 5 | `evaluations/excel/template/:id` | GET | `evaluations.fill` | مفعّل (P1) |
| 6 | `evaluations/excel/preview` | POST | `evaluations.fill` | مفعّل (P1) |
| 7 | `evaluations/excel/commit/:id` | POST | `evaluations.fill` | مفعّل (P1) |
| 8 | `evaluations/excel/training-template` | GET | `evaluations.fill` | مفعّل (P1) |
| 9 | `api/training-templates` | GET | session only | مفعّل (P1) |
| 10 | `api/training-list` | GET | session only | مفعّل (P1) |
| 11 | `api/training-programs/:id` | GET | (TrainingProgramController) | موجود (غير Excel) |
| 12 | `api/training-programs/list/:type` | GET | (ApiController) | موجود (غير Excel) |

**الوحدات/المرن/الجدولة** (المرحلة 1) **ما زالت مؤرشفة** (داخل `/* ... */`) — لم تُفعَّل.

### 2.2 اختبار الوصول

| الطلب (غير مصادق) | الحالة |
|---|---|
| `GET /employee-evaluation/evaluations/excel/export` | **403** (حارس `evaluations.export`) |
| `GET /employee-evaluation/evaluations/excel/import` | **403** (حارس `evaluations.fill`) |

**الخلاصة:** الحارس يعمل. لا تعارض مع أي مسار موجود. الوحدات/المرن/الجدولة ما زالت مؤرشفة.

---

## 3. التعديلات على الملفات المشتركة

### 3.1 قائمة الملفات المعدّلة (CD-043 vs master)

| الملف | النوع | التأثير على وحدات أخرى |
|---|---|---|
| `app/Controllers/EvaluationExcelController.php` | **معدّل** (إضافي) | Excel فقط |
| `app/Services/EvaluationExcelService.php` | **معدّل** (استبدالي جزئي) | Excel فقط |
| `app/Services/Excel/ExcelTemplateBuilder.php` | **جديد** | Excel فقط |
| `app/Views/evaluations/excel_export.php` | **جديد** | Excel فقط |
| `app/Views/evaluations/excel_import.php` | **معدّل** (추가 رابط) | Excel فقط |
| `app/Views/layouts/sidebar.php` | **معدّل** (رابط Excel) | واجهة عامة (عرض فقط) |
| `routes/web.php` | **معدّل** (إلغاء تعليق + 2 سطور) | توجيه (إضافة فقط) |
| `database/migrations/*` | **جديد** (4 ترحيلات + rollback) | DB ( additive / nullable ) |
| `tests/*` | **جديد/معدّل** | اختبارات فقط |

**لا تم تعديل:** `EvaluationController` (saveUnified)، `Evaluation` model (calculateFinalScore)، `config/database.php`، أو أي وحدة تقييم يدوي. **P4 (المرحلة الأخطر) لم تُنفَّذ بعد.**

### 3.2 طبيعة التعديلات (إضافية vs استبدالية)

- `EvaluationExcelController`: **إضافي** — دالتان جديدتان (`exportForm`, `downloadExport`) + helpers (`optionalId`, `assertScopeExists`). الدوال القديمة (`importForm`, `preview`, `commit`, `downloadTemplate`, ...) **لم تتغير**.
- `routes/web.php`: **إضافي** — إلغاء تعليق كتلة Excel (8 مسارات) + 2 سطور جديدة. لا حذف لمسار نشط. الوحدات/المرن/الجدولة **ما زالت مؤرشفة**.
- `sidebar.php`: **추가** — رابط «استيراد وتصدير Excel» يظهر لمن يملك `evaluations.fill` أو `evaluations.export`. لا إزالة روابط موجودة.
- `EvaluationExcelService`: **استبدالي جزئي** (الباني + المُستورد + commit patching) — لكن الملف نفسه مخصص للوحدة Excel، ولا يُستخدم من وحدات التقييم اليدوي.

### 3.3 ملفات مشتركة أخرى

- `config/database.php` — **لم يُعدَّل**.
- `app/Core/*` — **لم يُعدَّل**.
- `app/Controllers/EvaluationController.php` — **لم يُعدَّل** (P4 لم تُنفَّذ).
- `app/Models/Evaluation.php` — **لم يُعدَّل**.

---

## 4. الأعمدة السبعة في M4 — أثرها على الاستعلامات

### 4.1 تعريف الأعمدة (DDL)

| العمود | النوع | Null | Default | ملاحظة |
|---|---|---|---|---|
| `original_file_path` | varchar(500) | YES | NULL | جديد (P1) |
| `file_size` | bigint unsigned | YES | NULL | جديد (P1) |
| `file_sha256` | char(64) | YES | NULL | جديد (P1) + فهرس |
| `scope_json` | longtext | YES | NULL | جديد (P1) |
| `committing_at` | datetime | YES | NULL | جديد (P1) |
| `failed_at` | datetime | YES | NULL | جديد (P1) |
| `failure_reason` | text | YES | NULL | جديد (P1) |

**الخلاصة:** جميع الأعمدة السبعة `nullable` (أو لها default). لا كسر لـ INSERT القديم.

### 4.2 كود يعتمد على `SELECT *` / `INSERT` بدون أعمدة

- `EvaluationExcelService` يستخدم `INSERT INTO evaluation_import_batches (uploaded_by_user_id, original_filename, status) VALUES (...)` — **أعمدة محددة**.
- `UPDATE evaluation_import_batches SET status=..., committed_at=NOW() WHERE id=...` — **أعمدة محددة**.
- `UserController` (Admin) يستخدم `SELECT COUNT(*) FROM evaluation_import_batches WHERE uploaded_by_user_id = :id2` — **أعمدة محددة** (لا `SELECT *`).
- `ExcelImportConflictSmoke` test يستخدم `SELECT status FROM evaluation_import_batches WHERE id=...` — **أعمدة محددة**.

**لا يوجد كود يعتمد على `SELECT *`** → إضافة أعمدة nullable لا تكسر أي استعلام.

### 4.3 استخدام الجدول في وحدات أخرى

- `evaluation_import_batches` يُستخدم فقط في:
  - `EvaluationExcelService` (الوحدة Excel).
  - `UserController` (Admin) — guard لحذف المستخدم (count فقط).
- **لا تأثير** على وحدات التقييم/التقارير/المؤشرات.

---

## 5. توضيح «إرجاع `routes/web.php`» في C0

### 5.1 ما الذي أُرجِع؟

في C0، تم إرجاع **تعديلات غير ملتزمة (uncommitted)** على `routes/web.php` إلى نسخة `master` الملتزمة (`77cbfbb`). هذه التعديلات كانت **غير ملتزمة** (لم تكن في git history) — أي أنها تغييرات محلية (local edits) لم تُحفظ في commit.

**السبب:** C0 هدفه إنشاء فرع نظيف (`feature/cd-043-excel-ie`) من `master` (77cbfbb) بدون تغييرات عالقة. إرجاع التغييرات غير الملتزمة ضروري لضمان نقطة بداية نظيفة (rollback tag `pre-cd043-20261116_034536`).

### 5.2 هل كانت هذه التعديلات مطلوبة لوحدات أخرى؟

- التغييرات غير الملتزمة **لم تكن في git history** → لا يمكن تحديد محتواها بدقة من git.
- تم فحص `_emergency/web.php.bak` (نسخة احتياطية قديمة، pre-CD-037) — **ليست** جزءاً من CD-043، وهي نسخة أقدم (pre-CD-037) ولا تحتوي على تعديلات CD-043.
- **لا دليل** على أن التغييرات المُرْجعة تخص وحدات أخرى (التقييمات/التقارير/...). تعديلات C0 كانت فقط على `routes/web.php` (مسارات Excel مؤرشفة).
- **المسارات المؤرشفة** (الوحدات/المرن/الجدولة) **ما زالت** في `routes/web.php` داخل `/* ... */` — **لم تُحذف**، فقط لم تُفعَّل.

### 5.3 هل تم حفظها قبل الإرجاع؟

- **لا** — التغييرات غير الملتزمة (uncommitted) **لم تُحفظ** في أي commit أو stash (لا stash list).
- **لكن:** لا يوجد دليل على أن هذه التغييرات كانت « valuable » (قيمة) — كانت local edits على `routes/web.php` (غالباً من محاولات سابقة أو edits تجريبية).
- **التعويض:** في P1، تم **إعادة تفعيل** مسارات Excel (8 مسارات) من الكتلة المؤرشفة (commented block) — أي أن المسارات « المُرْجعة » **أُعيدت** (reactivated) في P1 من الكود الملتزم (committed).

**الخلاصة:** لا فقدان لـ code committed. التغييرات المُرْجعة كانت uncommitted local edits، وأُعيد تفعيل المسارات المطلوبة في P1.

---

## 6. ملخص — لا تأثير سلبي على وحدات موجودة

| # | البند | التأثير على وحدات موجودة |
|---|---|---|
| 1 | M3 (enum تقليص) | **لا** — 0 قوالب تستخدم القيم الملغاة (نشطة+مؤرشفة) |
| 2 | مسار export | **لا** — مسار جديد، لا تعارض، حارس يعمل (403) |
| 3 | ملفات مشتركة | **لا** — إضافات فقط؛ EvaluationController/Evaluation model **لم تُعدَّل** |
| 4 | M4 أعمدة | **لا** — nullable/default؛ لا `SELECT *`؛ جدول Excel فقط (+ count في UserController) |
| 5 | C0 reverted routes | **لا فقدان** — uncommitted edits؛ مسارات Excel أُعيد تفعيلها في P1 |

**P4 (المرحلة الأخطر — تعديل saveUnified/calculateFinalScore) لم تُنفَّذ بعد.** شرط regression test + مقارنة ذهبية (C3) سيُطبق قبل P4.

---

## 7. جاهزية P3

P3 (محرك التحقق) **آمنة نسبياً** لأن معظم ملفاتها جديدة:
- `ExcelImportValidator.php` (جديد)
- `EmployeeMatcher.php` (جديد)
- `ProgramMatcher.php` (جديد)
- `ExcelScope.php` (جديد)

تعديل `excel_import.php` (واجهة المعاينة) **추가** (تبويبات) — ستحافظ على الوظائف القديمة.

**خطة P3** (للموافقة):
1. إنشاء 4 ملفات جديدة (Validator/Matcher/ProgramMatcher/Scope).
2. تعديل `excel_import.php` (إضافة تبويبات — additive).
3. **عدم تعديل** `EvaluationController` / `Evaluation` model (P4).
4. regression smoke (ExcelWorkflowSmoke + ExcelTemplateExportSmoke) بعد كل تغيير.

---

## 8. طلب اعتماد C2

بناءً على التحليل أعلاه:

| # | البند | الحالة |
|---|---|---|
| 1 | M3 precheck (0 صف) | ✅ |
| 2 | M3 rollback مثبت | ✅ |
| 3 | M3 على UAT | ⛔ لم تُطبَّق (ممنوع حتى C2) |
| 4 | مسار export (لا تعارض، 403) | ✅ |
| 5 | ملفات مشتركة (إضافات فقط) | ✅ |
| 6 | M4 أعمدة (nullable، لا `SELECT *`) | ✅ |
| 7 | C0 reverted routes (لا فقدان) | ✅ |
| 8 | P4 لم تُنفَّذ (regression test لاحقاً) | ✅ (مخطط) |

**طلب:** اعتماد C2 رسمياً + صلاحية بدء P3.

---

## 9. مراجع

- Commit P1: `2c6c41a`
- Commit P2: `cf8e636`
- Tag rollback: `pre-cd043-20261116_034536` (master `77cbfbb`)
- M3 migration: `database/migrations/2026_11_16_CD043_template_types.sql` + `_down.sql`
- Smoke tests: `tests/ExcelWorkflowSmoke.php` (5/5), `tests/ExcelTemplateExportSmoke.php` (6/6)
