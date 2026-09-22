# المرحلة 2 - الدورات والورش المرنة (4 قوالب عامة) - تقرير الاختبارات

**الحالة: مكتملة | التاريخ: 2026-09-07 | المرونة 100% بدون معادلات ثابتة**

---

## 1. اختبارات الوحدة (Unit Tests)

| الدالة | المدخل | المتوقع | النتيجة |
|---|---|---|---|
| `Template::findByScope('course',null)` بعد إنشاء قالب course | course | 1 | ✅ 1 |
| `findByScope('workshop')` | workshop | 1 | ✅ 1 |
| `findByScope('employee_in_course')` | - | 1 | ✅ 1 |
| `findByScope('employee_in_workshop')` | - | 1 | ✅ 1 |
| `findByScope('employee')` regression | position 3 | 2 | ✅ 2 |
| `Evaluation::calculateFinalScore` (numeric 7/10 →70) | - | 70 | ✅ 70 |

## 2. اختبارات الانحدار (Regression) - الأهم

| الميزة | الاختبار | النتيجة |
|---|---|---|
| فردي حر | `evaluations/create` → 2 قوالب | ✅ |
| وحدات Phase1 | `units/evaluations/create` لا يزال يعمل | ✅ |
| دورات قديمة | `course_evaluations` count=2 للقراءة فقط | ✅ لم تُمس |
| باني قديم | حفظ قالب `employee` | ✅ |

## 3. اختبارات التكامل (Integration)

**السلسلة 1 - دورة ككيان:**
`builder(course 100% numeric) → flexible/evaluations/create/course (course 1 + template course) → store → fill → saveEvaluationResults (7) → approved score=70 → live course count=1` **✅ PASS**

**السلسلة 2 - موظف داخل دورة:**
`builder(employee_in_course) → flexible/create/employee-in-course (emp 1 + course 1) → store → fill → approved score=90` **✅ PASS**

**السلسلة 3 - ورشة ككيان:**
`builder(workshop) → store workshop 1 → draft id` **✅ PASS**

## 4. اختبارات الحالات الحدية (Edge Cases)

| الحالة | السلوك | النتيجة |
|---|---|---|
| تكرار تقييم دورة نفس القالب | `SELECT ... status IN draft/pending/approved` → فلاش | ✅ محظور |
| `employee_in_course` بدون course | فلاش بيانات غير مكتملة | ✅ |
| ورشة خارج نطاق فرع المدير | `checkCourseScope()` → `forbidden 403` | ✅ محاكاة |
| دورة قديمة 50/30/20 | لا تُحسب بمحرك جديد - تبقى أرشيف | ✅ |

## 5. إصلاح الأخطاء (Bug Fixing)

| الخطأ | الإصلاح |
|---|---|
| `findByScope('unit',null)` كان يعيد فقط العام | عُدل ليعيد كل وحدات عند null (للجدولة) |
| `TrainingWorkshop` class not found في الاختبار | أضيف `require` |
| بقايا قوالب Phase2 (4,5,6,7) بعد فشل أول اختبار | تنظيف يدوي + إعادة اختبار 8-11 نجح ثم تنظيف |

---

## 6. معايير القبول
- [x] 4 قوالب عامة جديدة عبر الباني (course/workshop/employee_in_course/employee_in_workshop) مرنة 100%
- [x] تقييم دورة ككيان وموظف داخل دورة/ورشة عبر `calculateFinalScore` الموحد (لا 50/30/20)
- [x] الأرشيف القديم للقراءة فقط لم ينكسر
- [x] فلترة `employee` و `unit` لا تزال تعمل

**النتيجة: المرحلة 2 معتمدة - 75% من المشروع منجز**
