# تقرير شامل — وحدة تصدير/استيراد Excel (CD-043)
## جميع أنواع التقييم والقوالب — للعرض على المستشار

**المهمة:** CD-043 — وحدة تصدير/استيراد Excel
**المرجع:** APPR-EXCEL-IE-007 + SRS-EXCEL-IE-3.1-final
**البيئة:** UAT (`employee_evaluation_uat`)
**التاريخ:** 2026-11-16
**الحالة:** مسودة — بعد إصلاح BUG-001 (قوائم منسدلة) وبانتظار تحقق المدقّق

---

## 1. نظرة عامة

وحدة CD-043 توحّد **تصدير قوالب Excel** (4 أنواع تقييم) و **استيراد النتائج** (مع التحقق والمطابقة والالتزام) ضمن نظام التقييم. الهدف: 100% مطابقة manual-vs-import (9 مقارنات ذهبية)، مع 3 ضمانات غير قابلة للتفاوض:

1. **التقدير العام = final result** عبر `final_grade_label/value` (لا عمود `overall_grade` في `evaluations`).
2. **لا عمود `overall_grade`** في جدول `evaluations` (الاستخدام: `final_grade_label` + `final_grade_value`).
3. **حدود صلبة:** 2000 صف / 50MB / retention 12/30/365 (معطل افتراضياً).

---

## 2. أنواع التقييم (Target Types) — 4

| # | النوع (target_type) | الوصف | الوضع (evaluation_mode) | التقدير العام (overall_grade) |
|---|---|---|---|---|
| 1 | `employee` | تقييم موظف (سنوي) | numeric | ❌ لا |
| 2 | `employee_in_course` | موظف داخل دورة | descriptive / numeric | ✅ (descriptive فقط) |
| 3 | `employee_in_workshop` | موظف داخل ورشة | numeric | ❌ لا |
| 4 | `skill` | تقييم مهاري | numeric | ❌ لا |

---

## 3. كتالوج القوالب (UAT) — 6 قوالب

| ID | اسم القالب | target_type | evaluation_mode | is_active | version |
|---|---|---|---|---|---|
| 1 | التقييم السنوي للموظف | employee | numeric | 1 | 1 |
| 2 | التقييم الوصفي للدورة | employee_in_course | descriptive | 1 | 1 |
| 3 | التقييم الرقمي للدورة | employee_in_course | numeric | 1 | 1 |
| 4 | التقييم المهاري | skill | numeric | 1 | 1 |
| 104 | الاستفادة والأخلاق من الدورة | employee_in_course | descriptive | 1 | 1 |
| 206 | قالب ورشة (UAT) | employee_in_workshop | numeric | 1 | 1 |

---

## 4. بنية التصدير (Export Structure) — تفصيل لكل نوع

### 4.1 — `employee` (تقييم موظف سنوي)
**الأعمدة (A..N):**

| العمود | المفتاح | Kind | الملاحظات |
|---|---|---|---|
| A | employee_name | fixed | اسم الموظف * |
| B | employee_code | fixed | رقم الموظف |
| C | employee_branch | fixed | الفرع * (قائمة منسددة: BranchList) |
| D | employee_department | fixed | القسم * (قائمة منسددة: DepartmentList) |
| E..J | section_1..6 + notes | section/notes | 3 معايير (الأداء 40%، السلوك 30%، المبادرة 30%) — 6 أقسام |
| K..N | notes, evaluator_name, evaluator_title, evaluation_date | tail | ترتيب الذيل |

**المعايير والأوزان:** الأداء الوظيفي (40%)، السلوك والجدارة (30%)، المبادرة والتطوير (30%).
**التحقق:** نسبة 0–100 (whole) لحقول النسبة %؛ قائمة منسددة للفرع/القسم.
**لا يوجد:** عمود كنية، عمود التقدير العام.

### 4.2 — `employee_in_course` (descriptive) — التقييم الوصفي للدورة
**الأعمدة (A..U):**

| العمود | المفتاح | Kind |
|---|---|---|
| A | program_title | fixed (program header) |
| B | program_branch | fixed (program header) |
| C | program_department | fixed (program header) |
| D | program_start | fixed (program header) |
| E | program_end | fixed (program header) |
| F | program_responsible | fixed (program header) |
| G | employee_name | fixed |
| H | employee_code | fixed |
| I | employee_branch | fixed (BranchList) |
| J | employee_department | fixed (DepartmentList) |
| K/P | section_7..9 + notes | section/notes (3 textarea) |
| Q | overall_grade | overall_grade (GradeList — للدورة الوصفية فقط) |
| R..U | notes, evaluator_name, evaluator_title, evaluation_date | tail |

**المعايير:** تقييم الاستفادة (100%) — 3 أقسام (textarea) + التقدير العام (overall_grade).
**التحقق:** قائمة منسددة للبرنامج/الفرع/القسم/التقدير؛ LEN>0 للتواريخ.
**الضمانة 1:** التقدير العام يُكتب في `final_grade_label` + `final_grade_value`.

### 4.3 — `employee_in_course` (numeric) — التقييم الرقمي للدورة
**الأعمدة (A..O):** program header (A..F) + employee (G..J) + section_11 (numeric 0–100) + tail (notes/evaluator_name/evaluator_title/evaluation_date).

**المعايير:** اختبار نهائي (100%) — 1 قسم numeric.
**لا يوجد:** overall_grade.

### 4.4 — `employee_in_course` (descriptive) — الاستفادة والأخلاق (id=104)
**الأعمدة:** program header (A..F) + employee (G..J) + 4 textarea (الفهم للدروس، التطبيق العملي، تعاملة مع الزملاء، تعاملة مع العاملين) + overall_grade (GradeList) + tail.

**المعايير:** الاستفادة (50%) + الأخلاق (50%) — 4 أقسام textarea + overall_grade.

### 4.5 — `employee_in_workshop` (numeric) — تقييم ورشة
**الأعمدة (A..O):** program header (A..F) + employee (G..J) + section (percentage 0–100) + tail (notes/evaluator_name/evaluator_title/evaluation_date).

**المعايير:** معيار (100%) — 1 قسم percentage.
**لا يوجد:** overall_grade.

### 4.6 — `skill` (تقييم مهاري)
**الأعمدة (A..N):**

| العمود | المفتاح | Kind |
|---|---|---|
| A | employee_name | fixed |
| B | employee_code | fixed |
| C | employee_branch | fixed (BranchList) |
| D | employee_department | fixed (DepartmentList) |
| E..J | section_12..14 + notes | section/notes (3 معايير) |
| K..N | notes, evaluator_name, evaluator_title, evaluation_date | tail |

**المعايير:** المهارات التقنية (50%)، مهارات التواصل (25%)، حل المشكلات (25%) — 3 أقسام percentage.
**لا يوجد:** program header، overall_grade.

---

## 5. شيت Lists (Hidden) — 5 أعمدة

| العمود | الاسم (Named Range) | المحتوى (UAT) | الصفوف |
|---|---|---|---|
| A | `BranchList` | الفروع | A2:A4 |
| B | `DepartmentList` | الأقسام | B2:B8 |
| C | `ProgramList` | البرامج | C2:C30 (course) |
| D | `GradeList` | سلم التقديرات (ممتاز..ضعيف) | D2:D6 |
| E | `SkillLevelList` | مستويات المهارة (متقدم/جيد/مقبول) | E2:E4 |

- **الحالة:** hidden (مخفية).
- **بدون معرّفات تقنية** (no `id` in headers).
- **ترتيب name→code** للفردي/المهاري.
- **بدون كلمة سر.**

---

## 6. حماية الورقة (Protection)

- `sheetProtection sheet="1"` — **بلا كلمة سر** (`password` attribute absent).
- `sort="0"`, `insertRows="0"`, `formatCells="0"` — منع الفرز/إدراج الصفوف/التنسيق.
- Freeze pane: `A5` (تجميد 4 صفوف رأس).
- Right-to-left: ✅.

---

## 7. حدود النظام (Limits)

| البند | القيمة | المصدر |
|---|---|---|
| min_rows | 50 | `settings.excel.min_rows` |
| max_rows | 2000 (سقف صلب) | `settings.excel.max_rows` (min(2000, ...)) |
| max_file_size_mb | 50 | `settings.excel.max_file_size_mb` |
| retention (audit/batches/rejected) | 12/365/30 شهر/يوم | `settings.excel.retention_*` |
| retention cron | معطل افتراضياً (`excel.retention_enabled` disabled) | P5 |

---

## 8. التحقق من البيانات (Data Validation)

| العمود/النطاق | Type | Formula | showDropDown (بعد الإصلاح) |
|---|---|---|---|
| program_title (A) | list | ProgramList | 1 ✅ |
| program_branch (B) | list | BranchList | 1 ✅ |
| program_department (C) | list | DepartmentList | 1 ✅ |
| program_start/end (D/E) | custom | LEN>0 | 1 ✅ |
| employee_branch (I) | list | BranchList | 1 ✅ |
| employee_department (J) | list | DepartmentList | 1 ✅ |
| overall_grade (Q) | list | GradeList | 1 ✅ |
| evaluation_date (U) | custom | LEN>0 | 1 ✅ |
| numeric sections | decimal | min..max | — |
| percentage sections | whole | 0..100 | — |
| grade/multiple_choice | list | options / SkillLevelList | 1 ✅ |
| boolean | list | "نعم,لا" | 1 ✅ |
| text/textarea | — | LEN (custom) | 1 ✅ |

**BUG-001 (مُصلَح):** قبل الإصلاح، قواعد `type="list"` كانت تُكتب بـ `showDropDown="0"` (معكوس). بعد الإصلاح: `showDropDown="1"` + السمة القياسية `showDataDropDown="1"` (post-process).

---

## 9. ملفات العينات (Samples)

| الملف | النوع | الوضع | التقدير العام | listDVs |
|---|---|---|---|---|
| `docs/CD-043/exports/employee.xlsx` | employee | numeric | لا | 2 |
| `docs/CD-043/exports/employee_in_course.xlsx` | employee_in_course | descriptive | نعم | 6 |
| `docs/CD-043/exports/employee_in_workshop.xlsx` | employee_in_workshop | numeric | لا | 5 |
| `docs/CD-043/exports/skill.xlsx` | skill | numeric | لا | 2 |

---

## 10. الاختبارات (Tests)

| الاختبار | النتيجة | ملاحظات |
|---|---|---|
| `ExcelWorkflowSmoke.php` | 5/5 ✅ | 4 أنواع + descriptive |
| `ExcelTemplateExportSmoke.php` | 6/6 ✅ | + artifact-level dropdown check (BUG-001) |
| `ExcelImportValidationSmoke.php` | 4/4 ✅ | EmployeeMatcher, ProgramMatcher, ExcelScope, Validator |
| `EvaluationCalculatorSmoke.php` | 3/3 ✅ | numeric + descriptive + missing |
| Golden comparison (numeric) | PASSED ✅ | 100% parity |

---

## 11. حالة التدقيق المستقل (AUDIT-CD-043)

| البند | الحالة |
|---|---|
| تقرير الخطأ #001 (قوائم منسدلة معطلة) | ✅ مُسلَّم (ضمن 24 ساعة) |
| جدول توافق SRS | 🟡 مسودة (P2-09/P2-10 مُصلَحان) |
| التوصية النهائية | 🟡 مسودة (Hold UAT sign-off pending verification) |
| 12 حالة مطابقة موظف | 🟡 منفذة (10/12 pass في فحص تجريبي) |
| فحص DB (UAT) | ✅ مكتمل |
| فحص UI | ✅ مكتمل (سابقاً) |
| التحقق من ملفات إكسل фعلياً | ⏳ pending ( auditor ) |

---

## 12. الأذونات والوصول (UAT DB)

| البند | القيمة |
|---|---|
| permission `evaluations.export` | id=54 ✅ |
| settings `excel.*` | min_rows=50, max_rows=2000, max_file_size_mb=50 ✅ |
| retention | audit=12mo, batches=365d, rejected=30d ✅ |
| `evaluations` schema | final_score, final_grade_label, final_grade_value ✅ (لا overall_grade) ✅ |

---

## 13. الخلاصة (للمستشار)

- **4 أنواع تقييم** + **6 قوالب** في UAT.
- **بنية التصدير** موحدة: 2 sheets، رأس برنامج 6 حقول، ترتيب ذيل ثابت، قوائم منسددة من شيت Lists.
- **BUG-001 (قوائم منسدلة معطلة) — مُصلَح** (showDropDown="1" + showDataDropDown="1" في الملف الفعلي).
- **اختبارات CD-043:** 18/18 (5+6+4+3) PASS.
- **التدقيق المستقل:** جاري (تقرير #001 مُسلَّم، جدول التوافق + التوصية نهائية pending).
- **UAT sign-off:** معلّق (Hold) بانتظار تحقق المدقّق من ملفات إكسل فعلياً.

---
**الحالة:** جاهز للعرض على المستشار (مع ملاحظة BUG-001 مُصلَح + بانتظار تحقق المدقّق).
