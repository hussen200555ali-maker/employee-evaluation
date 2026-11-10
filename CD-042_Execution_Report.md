# CD-042 — منع تكرار التقييم (Execution Report)

**التاريخ:** 2026-11-10
**الحالة:** منفّذ ومختبر
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
- `ApiController::evaluationExists`: موحّد مع النموذج.

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
- حزمة الاختبارات: لا تراجعات جديدة (الفشل المتبقي: `testBulkCreatePageRendersTemplateSelector` (id قديم `templateSelect`)، CD029، CD037، DescriptiveTemplates — 모두 사전 실패).
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
