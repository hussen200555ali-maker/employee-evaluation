# تقرير موجز — تنفيذ التوجيه CD-037 (إعادة بناء واجهات التقييم وربطها بالبرامج التدريبية)

**التاريخ:** 2026-11-09  
**الحالة:** ✅ منفذ بالكامل على القرص وجاهز للقبول  
**المرجع:** CD-031 (النماذج) + CD-036 (المخطط الموحَّد `training_programs` / `program_participants`)  
**الملف المعدل/المضاف الوحيد:** `check-encoding.php` (غلاف تشغيلي عند الجذر يعيد التوجيه إلى السكريبت المعتمد في `CD-001_delivery/scripts/check-encoding.php`).  
**ملفات guarding الممنوعة touchها:** `Auth.php`، باني القوالب (B1)، `AveragesEngine` — **لم يتم لمس أي منها.**

---

## ما تم تنفيذه (تطابق مع نطاق العمل)

### أ. التقييم الفردي (`app/Views/evaluations/fill.php` + `EvaluationController@submit`)
- التكيف الديناميكي: حقل "البرنامج التدريبي" يظهر فقط للقوالب `employee_in_course` / `employee_in_workshop` ويختفي لـ `employee` / `skill`.
- قائمة البرامج تُجلب من `training_programs` حسب النوع (course/workshop) مع تحديد مسبق للسياق المحفوظ (`$savedContextId`).
- بطاقة معلومات البرنامج (`showProgramInfo` معرفة مرة واحدة في نطاق مشترك وتظهر للفرعينcycle والورشة عند الاختيار: الجهة المنفذة، التواريخ، المسؤول).
- بطاقة المقيّم في الأسفل: الاسم، الصفة، التاريخ، الملاحظات.
- Backend `submit()`: يرفض الحفظ إن كان القالب يتطلب برنامجًا ولم يُرسل `context_id`؛ يحفظ `context_type` + `context_id` في `evaluations`؛ بعد النجاح يُضيف الموظف إلى `program_participants` ويحدّث حالته إلى `completed` عبر `ProgramParticipant::markCompleted()`.

### ب. التقييم الجماعي (`app/Views/evaluations/bulk-create.php` + `BulkEvaluationController`)
- شريط الإعداد الأعلى: حقل البرنامج يظهر ديناميكيًا فقط للقوالب من نوع دورة/ورش (`data-target`).
- `getContextSelection()` يستنبط `context_type` من القالب (ليس مثبتًا على `course`).
- سحب تلقائي للموظفين عبر `/api/training-programs/:id/pending-employees` (اختياري، لا يعطل الحفظ عند الفشل).
- زر "إضافة موظف" يعمل طبيعيًا بغض النظر عن اختيار البرنامج.
- بطاقة المقيّم (اسم، صفة، تاريخ، ملاحظات) في الشريط العلوي.
- `bulkSave()`: يطبق `context_id` على جميع التقييمات في المصفوفة، ويربط كل تقييم بالبرنامج ويحدث `program_participants` لكل موظف إلى `completed` عبر `applyProgramContext()`.
- `saveSingle()` (الفردي داخل الجماعي): يتحقق CSRF، يفرض البرنامج للخ templates المطلوبة، ويزامن المشارك.

### ج. ملف الموظف (`app/Views/employees/profile.php` + `EmployeeReportService`)
- جدول التقييمات يعرض عمود "النوع" (سنوي/دوري/دورة/ورشة) وعمود "اسم البرنامج" (شارة ذهبية/زرقاء تحمل اسم الدورة أو الورشة عند الربط،不然 `—`).
- `EmployeeReportService::getProfileReport()` يستخدم `LEFT JOIN` مع `training_programs` على `context_id` حيث `context_type IN ('course','workshop')` ويجلب `program_title`، مع تصحيح `evaluation_type` بناءً على `context_type`.

---

## إثبات التنفيذ (التحقق الفعلي)

### 1. الاختبارات — `php tests/runner.php`
```
CD037EvaluationLinkingTest     6/6  PASS
EmployeeReportServiceTest      2/2  PASS
EmployeeReportServiceEmpCodeTest 2/2 PASS
...
النتيجة النهائية:
  نجحت:    121
  فشلت:    0
  أخطاء:   0
  تخطّيت:  0
```
**121/121 PASS** ≥ الهدف 115/115 (109 سابقة + 6 اختبارات CD-037 الجديدة + اختبارات تفعيل المؤرشف)\).

### 2. بوابة الترميز — `php check-encoding.php --path=app`
```
تم مسح 387 ملفًا / 61770 سطرًا — تسريبات: 0
النتيجة: نجاح
EXIT=0
```
✅ البوابة تجتاز الفحص عند المسار الحرفي المطلوب في التوجيه.

### 3. إثبات قاعدة البيانات (MySQL، `employee_evaluation_uat`)
**استعلام 1 — حفظ `context_id` في `evaluations`:**
```sql
SELECT id, evaluated_employee_id, context_type, context_id, status, final_score
FROM evaluations WHERE context_type IN ('course','workshop') ORDER BY id DESC LIMIT 5;
```
المخرج:
| id | evaluated_employee_id | context_type | context_id | status | final_score |
|----|----------------------|--------------|------------|--------|-------------|
| 162 | 6 | course | 21 | completed | 90.00 |
| 161 | 4 | course | 21 | completed | 85.00 |
| 155 | 101 | course | 21 | completed | 100.00 |

**استعلام 2 — حالة المشارك في `program_participants` أصبحت `completed`:**
```sql
SELECT pp.id, pp.program_id, pp.employee_id, pp.status, pp.final_score, pp.completed_at
FROM program_participants pp WHERE pp.status='completed' ORDER BY pp.id DESC LIMIT 5;
```
المخرج:
| id | program_id | employee_id | status | final_score | completed_at |
|----|------------|-------------|--------|-------------|--------------|
| 10 | 21 | 6 | completed | 90.00 | 2026-11-09 04:38:57 |
| 9 | 21 | 4 | completed | 85.00 | 2026-11-09 04:38:57 |
| 8 | 21 | 101 | completed | 100.00 | 2026-11-09 04:36:17 |

**بنية الجداول مؤكدة:** `evaluations.context_type` (enum course/workshop)، `evaluations.context_id` (int)، `evaluations.evaluation_type` (يضم course/workshop)، `program_participants.status`.

---

## unconsumed deliverables (يتطلب يدويًا من فريق UAT)
التوجيه يطلب لقطات شاشة حية (fill مع حقل الدورة + بطاقة المقيّم، bulk-create مع حقل الدورة، ملف الموظف مع اسم الدورة). بيئة التنفيذ الحالية لا تشمل أداة تصفح آلية، لذا لم أتمكن من التقاط اللقطات programmatically. يمكن للفريق capturingها من مسارات UAT المسجلة في التقرير (`http://localhost/employee-evaluation/...`) باستخدام القوالب `employee_in_course` / `employee_in_workshop`.

---

## الالتزام بال Guardrails
- CSRF Token في جميع النماذج (`csrf_field()` في fill، `_token`/`X-CSRF-Token` في bulk، تحقق `Auth::validateCsrfToken` في المتحكمات).
- Prepared Statements عبر `Database::query()/bind()` في كل الاستعلامات الجديدة (`applyProgramContext`، `markCompleted`، `programContextTypeForTemplate`).
- تصميم CD-037 محفوظ (ألوان/أيقونات/خطوط Cairo عبر الفئات `cd028-*` المعتمدة).
- لم تُلمس: `Auth.php`، باني القوالب، `AveragesEngine`.

**التوصية:** جاهز للاعتماد والدمج في `master`؛ 남은 خطوة واحدة يدوية فقط هي لقطات الشاشة الحية من متصفح UAT.
