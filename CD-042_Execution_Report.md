# CD-042 — منع تكرار التقييم (Execution Report)

**التاريخ:** 2026-11-10
**الحالة:** منفّذ ومختبر (المرحلة 1 + المرحلة 2: Hardening)
**الفرع:** master

## 1. المشكلة (مثبتة)
- duplicates 231–237 أُنشئت 2026-11-10 04:17:15–19 (Chrome 156)، `POST bulk-save → 200`.
- السبب: `findExistingForEmployeeTemplateDate` (b7a8d79) أضاف `AND context_type='general'` عندما كان `context_type` غير فارغ؛ الواجهة ترسل `context_type='general'` بدون برنامج، والسجلات `context_type=NULL` → لا تطابق → كل حفظ يُنشئ صفاً.
- لا قيد تفرد → race (نقر مزدوج/تبويبان) → تكرار.
- UI تُظهر «✅» حتى عند `saved:0`.

## 2. التغييرات
### أ. Model (`app/Models/Evaluation.php`)
- `findExistingForEmployeeTemplateDate`: `COALESCE(context_id,0) = :ctx`، بدون شرط `context_type`، بدون fallback متقاطع (برنامج مختلف لا يُمنع).

### ب. Controllers
- `BulkEvaluationController::bulkSave`: إزالة تكرار الموظف داخل الطلب · `SAVEPOINT` لكل موظف · التقاط `1062` → «موجود» · استجابة غنية (`saved_items`, `skipped` بأسماء، `progress`).
- `EvaluationController::saveUnified`: التقاط `1062` → 409 مع `existing_id`.
- `EvaluationController::saveSingle`: نفس المعالجة (كان بدون فحص).
- `ApiController::evaluationExists`: **لم يُعدّل** — كان يستدعي `findExistingForEmployeeTemplateDate` أصلاً، فورث الإصلاح تلقائياً.

### ج. Database
- cleanup: 31 صفاً زائداً في 7 مجموعات (الأكمل: إجابات ← final_score ← الأقدم). نسخة احتياطية: `storage/backups/CD-042/duplicates_20261110_200056.json`.
- قيد تفرد: `context_key INT AS (IFNULL(context_id,0)) STORED` + `UNIQUE (evaluated_employee_id, template_id, evaluation_date, context_key)`. migration + down.
- تحقق: `Duplicate entry '5-1-2026-11-10-0'` (1062) — القيد يعمل.

### د. UI (`app/Views/evaluations/bulk-create.php`)
- حالات صفوف: `pending/saving/saved/exists/error` (في JS state).
- بطاقة نتيجة حفظ ثابتة (عدّادات + أسماء محفوظين/متخطّين + #id + [عرض])، `aria-live="polite"`.
- حفظ مجزّأ كل 25 + شريط تقدّم.
- علم `cd041_saving` + منع Enter + تعطيل «حفظ الكل» عند عدم وجود صفوف.
- «بدء دفعة جديدة» + «تصفير» مع تراجع 10 ثوانٍ.
- مسودة تلقائية في `localStorage` + استعادة + `beforeunload`.

## 3. الاختبارات
- `CD042DuplicateSmokeTest`: 1/1 نجح (findExisting يعيد id).
- End-to-end HTTP: first save `saved:1` (#300)، second save `saved:0` + skipped «عبدالله محمد العتيبي: يوجد تقييم مسبق (#300)».
- حزمة الاختبارات: لا تراجعات جديدة (الفشل المتبقي قديم: `testBulkCreatePageRendersTemplateSelector` يتوقع id قديم `templateSelect`، CD029، CD037، DescriptiveTemplates).
- تحقق قاعدة البيانات: `dup_groups = 0`، `total_evals = 23`.

## 4. التراجع (Rollback)
1. `git revert` commits CD-042 (كل مرحلة commit منفصل).
2. `database/migrations/2026_11_10_CD042_eval_uniqueness_down.sql`.
3. استرجاع من `storage/backups/CD-042/*.json`.

## 5. ملفات متأثرة
- app/Models/Evaluation.php
- app/Controllers/BulkEvaluationController.php
- app/Controllers/EvaluationController.php
- app/Views/evaluations/bulk-create.php
- database/migrations/2026_11_10_CD042_cleanup_duplicates.php
- database/migrations/2026_11_10_CD042_eval_uniqueness.sql
- database/migrations/2026_11_10_CD042_eval_uniqueness_down.sql
- tests/cases/Controllers/CD042DuplicateSmokeTest.php
- CD-042_Execution_Report.md

## 6. ملاحظات
- كلمة مرور sysadmin (id=1) أُعيدت إلى قيمتها الأصلية بعد UAT.
- بيانات UAT (eval 300) حُذفت بعد الاختبار.
- `context_type='general'` من الواجهة (بدون برنامج) يُعامل كـ 0 (بدون سياق) في النموذج.

---

# المرحلة 2 — Hardening (2026-11-10 22:10)

## 7. نقطة العودة (Rollback Point)
| العنصر | القيمة |
|---|---|
| Git tag | `pre-cd042-hardening-20261110_221041` |
| Branch احتياطي | `backup/pre-cd042-hardening-20261110_221041` |
| Commit الأساس | `24aa270` |
| DB dump | `storage/backups/CD-042/pre_hardening_20261110_221041/db_evaluations.sql` |
| نسخ الملفات | نفس المجلد (`*.bak`) |

الدليل الكامل للتراجع: **`CD-042_Hardening_Rollback.md`**.

## 8. ما تم إصلاحه في هذه المرحلة
### 8.1 أخطاء الحفظ أصبحت مُهيكلة (كانت تُفقد)
- الخادم كان يرسل `errors` كنصوص، والواجهة تتعامل معها ككائنات (`er.employee_id`) → الصفوف الفاشلة لا تُوسم ولا تُلوّن.
- الآن `bulkSave` يُرجع `error_items: [{employee_id, name, message}]` إضافةً إلى `errors` (توافق خلفي)، ولا تُدرج حالات «موجود/مكرر» داخل `error_items` (لها `skipped`).
- الواجهة: `Array.isArray(res.error_items)` → تُطبّق الحالة على الصفوف، مع حماية من الكتابة فوق `saved/exists`.

### 8.2 حالة الصف بعد الحفظ
- كان الصف يتحول إلى «موجود» الأصفر بعد الحفظ. الآن الأولوية: `saved` (أخضر) ← `error` (أحمر) ← `existing` (أصفر).
- الصفوف المحفوظة تبقى مقفلة (كل حقول الإدخال `disabled`).

### 8.3 المسودة (dirty / localStorage)
- `cd041_markDirty` كان غير موصول بأي حدث → `dirty` يبقى `false` دائماً. الآن: مستمعا `input`/`change` (capture) + استدعاءات صريحة عند إضافة/حذف موظف، «تطبيق على الكل»، «تفريغ»، التراجع، وحفظ نافذة الملاحظات.
- كتابة المسودة أصبحت **debounced 800ms** بدل مؤقّت كل 4 ثوانٍ، وتُلغى المسودات القديمة (تاريخ/قالب سابق).
- لا تُكتب مسودة إذا لم يتبقَّ صف `pending/error` → لا مطالبة «استعادة مسودة» لدفعة محفوظة.
- `beforeunload` يعمل عند وجود صفوف معلّقة أو تغيير غير محفوظ.

### 8.4 قفل الملاحظات في الصفوف المحفوظة
- نافذة الملاحظات للصف المحفوظ/الموجود تصبح **عرض فقط**: `readOnly` + إخفاء زر الحفظ وخيار «تطبيق على الكل» (كان ممكنًا التعديل ثم يُفقد عند الحفظ التالي).

## 9. الاختبارات (المرحلة 2 — متصفح حقيقي)
| # | السيناريو | النتيجة |
|---|---|---|
| 1 | تحميل الصفحة | لا أخطاء console، العناصر الجديدة موجودة |
| 2 | كتابة في حقل المقيّم | `dirty=true` فورًا + مسودة بعد 800ms (موظفان + اسم المقيّم) |
| 3 | «تصفير الكل» | المصفوفة تُفرّغ + شريط تراجع ظاهر + المسودة تُحذف |
| 4 | «تراجع» | استعادة الموظفين + إخفاء الشريط + `dirty=true` |
| 5 | خطأ حفظ (استجابة مُحاكاة) | `state=error` + صف `cd041-row-err` + بطاقة حمراء «موظف خطأ — فشل اختباري» |
| 6 | حفظ 26 موظفًا (تجزئتان 25+1) | `محفوظ 26`، 26 صفًا في DB، 156 إجابة، 1.3 ثانية، لا صفوف معلّقة |
| 7 | سباق (طلبان متزامنان لنفس الموظف/القالب/التاريخ) | واحد `saved:1 (#339)` وآخر `saved:0` + `skipped #339`، صف واحد في DB |
| 8 | صف محفوظ | `cd041-row-saved`، شارة «تم الحفظ #»، حقول معطّلة، زر حفظ معطّل |
| 9 | ملاحظات صف محفوظ | `readOnly=true`، زر الحفظ مخفي، خيار «الكل» مخفي |
| 10 | ملاحظات صف غير محفوظ | قابلة للتعديل وزر الحفظ ظاهر |
| 11 | مسودة ثم استعادة | مسودة بموظفين 2 → استعادة 2 |
| 12 | بعد اكتمال الحفظ | `draftKeys=0`، `dirty=false`، لا حقول قابلة للتعديل |

**تنظيف بيانات الاختبار:** حُذفت كل تقييمات `2099-12-23..31` (30 صفًا + إجاباتها). النتيجة: `dup_groups=0`، `total_evals=24` (منها 342 أُنشئ يدويًا خارج الاختبارات: موظف 6816، قالب 104، سياق دورة 21 — لم يُمَس).

## 10. الاختبارات الآلية بعد التحسينات
- `CD042DuplicateSmokeTest`: 1/1 نجح.
- الحزمة الكاملة: نفس النتائج السابقة، **لا تراجعات جديدة** (4 مجموعات فاشلة قديمة).
- `php -l` نظيف للملفين المعدّلين.

## 11. الملفات المعدّلة في المرحلة 2
- `app/Controllers/BulkEvaluationController.php` (error_items)
- `app/Views/evaluations/bulk-create.php` (أخطاء، حالة الصف، dirty/draft، قفل الملاحظات، beforeunload)
- `CD-042_Execution_Report.md`, `CD-042_Hardening_Rollback.md`
