# تقرير التحقق - استيراد وتصدير Excel للأنواع الستة

**التاريخ: 2026-09-07 | الحالة: أساس فردي متين - 5 أنواع تحتاج توسعة**

## 1. الاستيراد (Import) - الوضع الحالي

| النوع | قالب (target_type) | الأعمدة الحالية `A-H` | هل يدعم؟ | السلوك الحالي (Smoke Test) |
|---|---|---|---|---|
| **موظف فردي** `employee` | `employee` | `emp_code, full_name, branch_name, department_name + sections` | ✅ نعم | ينشئ/يجد `evaluated_employee_id` + `status=completed` → تصحيح 100% |
| **موظف داخل دورة** `employee_in_course` | `employee_in_course` | نفس الأعمدة - لا `course_id` | ❌ لا | يُخزن كـ `employee` بدون `context_type=course` → يظهر خطأً في `all` بدل `employee_in_course` - مفقود `course EXISTS` |
| **موظف داخل ورشة** `employee_in_workshop` | `employee_in_workshop` | نفس - لا `workshop_id` | ❌ لا | كالسابق - يضيع السياق |
| **دورة ككيان** `course` | `course` | لا `course_id` | ❌ لا | يحاول إنشاء موظف `EMPxxx` بـ `full_name=عنوان الدورة` - خطأ تصنيف |
| **ورشة ككيان** `workshop` | `workshop` | لا `workshop_id` | ❌ لا | نفس |
| **وحدة دورية** `unit` | `unit` (branch/department) | `branch_name` مخصص للموظف - لا `unit_code` مستقل ولا `period/schedule_id` | ❌ لا | ينشئ موظفاً بدل وحدة + `status=completed` يتجاوز `pending_review` + لا تحقق `evaluation_schedules` |

**الدليل:** `EvaluationExcelService.php:1-851` لا يقرأ `entity_type/context_id/schedule_id` (grep 0) - `commitBatch` يُدخل `entity_type='employee'` افتراضياً (`Evaluation::create` الفرع الوحدوي لا يُستدعى).

## 2. التصدير (Export) - عدم الخلط

| المصدّر | عمود `entity_type` | يمنع الخلط؟ |
|---|---|---|
| `UnifiedReportController::export` | ✅ `SELECT entity_type ... WHERE entity_type=:et` + CSV `النوع` | نعم - عند التصفية |
| `StatisticalReportController::exportExcel` | ✅ `buildWhere` يميز `employee vs employee_in_course (context_type)` + CSV `النوع` | نعم - الوحيد الذي يميز السياق |
| `ExportService` (Analysis) | ❌ 9 أعمدة بدون `entity_type` | يخلط - `getAllForApi()` بلا فلتر |
| `PerformanceFiles` | منفصل لكل نوع (لا CSV) | لا خلط |

**الخلاصة:** التصدير الموحد والإحصائي يضمن التمييز عند التصفية، لكن الاستيراد لا ينتج البيانات المميزة أصلاً.

## 3. اختبارات الدخان (Smoke Tests) - 4 أنواع رئيسية

**البيئة:** `template 18: Stat Test Unit`, `template 19: Stat Test Course`, `branch 1: الفرع الرئيسي`, `course 1`, `employee 1` - `status IN approved/completed` فقط

```
[1] فردي: buildTemplate(employee, branch 1) → عمود D branch dropdown + F sections → preview emp_code=E001 → commit → entity_type=employee PASS
[2] موظف في دورة: buildTemplate(employee_in_course) → لا عمود course_id → preview emp_code=E001, course_id=1 → commit يخزن employee بدون context → FAIL (يتطلب context_type)
[3] دورة ككيان: buildTemplate(course) → preview course_id=1 → commit يحاول emp_code → FAIL
[4] وحدة دورية: buildTemplate(unit) → preview branch_name=الرياض, period=2026-01 → commit يخزن employee → FAIL + يتجاوز pending_review
```

**النتيجة:** 1/4 ناجح، 3/4 تحتاج أعمدة `entity_type, context_id, unit_code, schedule_id` + تحقق `template.target_type` + `status` سير.

## 4. التوصية قبل بناء التصنيف الديناميكي

الأساس الفردي متين للبناء فوقه. المطلوب قبل المرحلة التالية:
- إضافة `entity_type` hidden + أعمدة `course_id/workshop_id/unit_code/period` إلى `buildTemplate()` حسب `target_type`
- توسيع `payload_json` + `validateSingleRow()` بـ `COURSE_NOT_FOUND, SCHEDULE_MISMATCH`
- تعديل `commitBatch()` ليستدعي `Evaluation::create()` الوحدوي مع `entity_type/context/schedule_id` ويحترم `pending_review` للوحدات

بعدها يُبنى التصنيف الديناميكي والتقارير بأمان.
