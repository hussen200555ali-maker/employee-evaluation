# CD-025 — الوثيقة المعمارية الشاملة والتدقيق الجذري
## Comprehensive Architectural & Functional Audit

| الحقل | القيمة |
|---|---|
| **المرجع** | CD-025 |
| **تاريخ الإصدار** | 2026-11-08 |
| **المُعدّ** | كبير المطورين |
| **السرية** | سري للغاية |
| **الحالة** | تحليل وتدقيق — لا كود |

---

## الملخص التنفيذي (Executive Summary)

هذه الوثيقة تشريح هندسي شامل لست وحدات رئيسية في نظام تقييم الأداء. كل وحدة تُحلَّل عبر ثمانية أبعاد: نظرة عامة، مخطط البيانات، دورة الحياة، الشاشات والمسارات، نقاط التكامل، الأمان، الأداء، والفجوات.

**الحكم الإجمالي:** النظام **وظيفي ومستخدم في الإنتاج**، لكنه يحمل فجوات هيكلية وأمنية تتطلب تدخلاً قبل التوسع المؤسسي. أبرزها:

1. 🔴 **كلمات مرور بنص صريح في سجل التدقيق** (UserController L388).
2. 🔴 **ApiController بدون مصادقة** — أي شخص يعدّد الموظفين.
3. 🔴 **مسارات تدميرية عبر GET** (backup/restore/delete) — ثغرة CSRF.
4. 🔴 **غياب جدول `course_participants`** — لا يمكن ربط موظف بدورة بشكل موثوق.
5. 🔴 **خلط مقاييس في AveragesEngine** — متوسط رقمي (0-100) مع وصفي (1-5) = نتائج خاطئة.
6. 🔴 **صلاحيات مفقودة من البذور** (`evaluations.delete`, `training.evaluate`, `backup.manage`).

---

# الوحدة 1: الهيكل التنظيمي (Organization Structure)

## 1.1 نظرة عامة
**الهدف:** إدارة الفروع، الأقسام، الموظفين، المناصب، وعلاقات الإدارة.
**الإنجاز:** مكتمل وظيفياً (CRUD + فلترة + ترقيم صفحات + منع دورة المدير).
**الالتزام المؤسسي:** جزئي — لا يوجد سجل نقل (transfer log)، لا فحص اتساق فرع↔قسم.

## 1.2 مخطط البيانات
```
branches (id, name, location, manager_id→employees.id)
    │ 1:N
    ▼
departments (id, name, branch_id→branches.id)
    │ 1:N
    ▼
employees (id, emp_code[U], full_name, department_id→departments.id,
           branch_id→branches.id, manager_id→employees.id(自我),
           position_id→positions.id, is_active, join_date)
    ▲ N:1
positions (id, title, category ENUM(manager/deputy/staff/other))
```
**الفهارس:** `employees` مفهرس على `department_id, branch_id, manager_id, position_id` (install_schema.sql L277-280). `emp_code` و `personal_email` UNIQUE.
**FK方向:** جميع FKs على `employees` هي `ON DELETE SET NULL` — حذف قسم/فرع يُيتم الموظف صامتاً.

## 1.3 دورة حياة البيانات
- **المدخلات:** شاشات `employees/create`, `edit`؛ API بحث `/api/employees/search`؛ استيراد Excel.
- **المعالجة:** Controller يتحقق من `emp_code`/`full_name` غير فارغين (EmployeeController L147-161). **لا** يتحقق من اتساق `department_id` مع `branch_id`. منع دورة المدير فقط في `update()` (L327)، **لا** في `store()`.
- **المخرجات:** جدول `employees`؛ لقطات (snapshots) في `evaluations` عند إنشاء تقييم (`Employee::getDetailsForSnapshot` L291-313).

## 1.4 الشاشات والمسارات
| المسار | Controller@Method | الصلاحية |
|---|---|---|
| `GET /employees` | EmployeeController@index | `employees.view` |
| `GET /employees/create` | EmployeeController@create | `employees.create` |
| `POST /employees` | EmployeeController@store | `employees.create` |
| `GET /employees/:id/edit` | EmployeeController@edit | `employees.edit` |
| `POST /employees/update` | EmployeeController@update | `employees.edit` |
| `POST /employees/delete` | EmployeeController@destroy | `employees.delete` |
| `GET /employees/profile/:id` | EmployeeController@profile | `employees.profile` |
| `GET /branches` | BranchController@index | `branches.view` |
| `GET /departments` | DepartmentController@index | `departments.view` |
| `GET /employees/positions` | PositionsController@index | `positions.manage` |

**التحقق:** ترقيم صفحات (25/صفحة) في `index`؛ فلترة بـ `branch_id/department_id/search`؛ ترتيب whitelist (Employee.php L54).

## 1.5 نقاط التكامل
- **↔ التقييمات:** `Employee::getDetailsForSnapshot()` يُستدعى عند إنشاء كل تقييم لتجميد بيانات الموظف (الاسم/القسم/الفرع) في `evaluations` snapshot columns.
- **↔ لوحة التحكم:** `RoleDashboardService` يقيس عدد الموظفين حسب persona (admin/hr = الكل، manager = فريقه).
- **↔ التقارير:** `EmployeeReportService::getProfileReport` INNER JOIN employees مع `emp_code IS NOT NULL`.

## 1.6 الأمان والصلاحيات
- كل controller يستدعي `Auth::enforce()`. ✅
- **فجوة:** `branches.create/edit/delete` و `departments.create/edit/delete` مُستخدمة في الكود لكن **غير مُعرَّفة في `permissions` seed** — ستفشل دائماً بـ 403.
- **فجوة:** حذف موظف لا يتحقق إن كان `branches.manager_id` — FK هو SET NULL → الفرع يفقد مديره صامتاً.

## 1.7 الأداء
- ✅ ترقيم صفحات في `index` (25/صفحة).
- 🔴 `Employee::getAll()` (L17-33) لا LIMIT — يحمّل كل الموظفين لقائمة المديرين في `create/edit`. عند 5000+ موظف: بطء + HTML ضخم.
- 🔴 `Employee::search()` (L338-352) يتجاهل `$limit/$offset` ويصلب `LIMIT 10`.
- 🔴 `findByDepartment()` و `getAllActiveEmployeeIds()` بلا LIMIT — خطر في التقييم الجماعي.
- 🟡 `getEmployeesWithEvaluationSummary` (L362-426) لا يفلتر `evaluations.status` — المسودات تضخّم المتوسط.
- 🟡 كشف دورة المدير (L127-151) يستعلم N مرة (مدير←مدير←…) — لا CTE متكرر.

## 1.8 الفجوات والتحديات
| # | الخطورة | الفجوة |
|---|---|---|
| G1 | 🔴 عالية | لا مسار نقل مخصص (`/transfer`)؛ النقل عبر `update()` العام بلا تسجيل القسم السابق ولا تاريخ نقل. |
| G2 | 🔴 عالية | `Evaluation::getAdminReports()` (L1346-1377) JOIN موظفين حيّ بدلاً من snapshot columns → تقارير تُنسب للقسم الجديد بعد النقل. |
| G3 | 🔴 عالية | `Employee::create()` يصلب `is_active=1` (L185) — لا يمكن إنشاء موظف غير نشط. |
| G4 | 🔴 عالية | `Employee::getAll()` بلا LIMIT لقائمة المديرين — لا يدعم 5000+ موظف. |
| G5 | 🟡 متوسطة | `store()` لا يفحص دورة المدير (فقط `update()`). |
| G6 | 🟡 متوسطة | `(int)$_POST['position_id']` يحوّل الفارغ إلى 0 بدل NULL (EmployeeController L186, L342). |
| G7 | 🟡 متوسطة | لا فحص أن `department_id` ينتمي لـ `branch_id` المختار. |
| G8 | 🟡 متوسطة | حذف فرع لا يتحقق من موظفين مرتبطين (BranchController L101). |

---

# الوحدة 2: باني القوالب (Form Builder — B1)

## 2.1 نظرة عامة
**الهدف:** تصميم قوالب التقييم (معايير + أقسام + أوزان + أنواع حقول) عبر واجهة سحب وإفلات.
**الإنجاز:** مكتمل — يدعم 10 أنواع أقسام (percentage/numeric/grade/boolean/textarea/text/overall_grade/formula/auto/helper)، تحقق أوزان حسب `evaluation_mode`، معاينة.
**الالتزام المؤسسي:** جيد — التحقق مزدوج (client + server)، idempotent migrations.

## 2.2 مخطط البيانات
```
form_templates (id, template_name, target_type ENUM(7),
    evaluation_mode ENUM(numeric/descriptive/mixed),
    evaluation_frequency ENUM(4), target_position_id→positions.id,
    unit_scope, is_active, version INT)

criteria (id, template_id→form_templates.id CASCADE,
    criterion_name, criterion_weight DECIMAL(5,2),
    order_index)

criteria_sections (id, criterion_id→criteria.id CASCADE,
    section_name, evaluation_type ENUM(10+2 CD-023),
    evaluation_options JSON, section_weight DECIMAL(5,2),
    min_value, max_value, allow_notes, notes_required,
    formula_config JSON, order_index)
```
**CD-023 أضاف:** `textarea`, `overall_grade` للـ ENUM.
**CD-023 أضاف:** `grading_scales.template_id` (nullable، للسلالم المخصصة).

## 2.3 دورة حياة البيانات
- **المدخلات:** واجهة الباني (`/builder/create`, `/builder/edit/:id`) — سحب عناصر، تعديل خصائص، حفظ.
- **المعالجة:** `BuilderController::save()` يجمع البيانات → `Template::buildStructureFromRequest()` → `validateWeights($structure, $evaluationMode)`.
  - **descriptive:** يُعفى من 100%، يُلزم بـ overall_grade.
  - **numeric/mixed:** 100% إلزامي، يستثني textarea/text/overall_grade.
  - يمنع أكثر من overall_grade واحد.
- **المخرجات:** `form_templates` + `criteria` + `criteria_sections` (transaction). التحديث = DELETE + re-INSERT (L219-221).

## 2.4 الشاشات والمسارات
| المسار | Controller@Method |
|---|---|
| `GET /builder/create` | BuilderController@index |
| `GET /builder/edit/:id` | BuilderController@index |
| `POST /builder/save` | BuilderController@save |
| `GET /builder/preview` | BuilderController@preview |
| `GET /templates` | TemplateController@index |
| `POST /templates/delete` | TemplateController@destroy |

**التحقق:** Client-side (builder.js L526-567) + Server-side (BuilderController L153-218). كلاهما يحترم `evaluation_mode`.

## 2.5 نقاط التكامل
- **↔ التقييمات:** `Template::getTemplateWithStructure($id)` يُستدعى في `Evaluation::calculateFinalScore`, `saveEvaluationResults`, `BulkEvaluationController::schema`, `EvaluationController::fill`.
- **↔ التقييم الجماعي:** `schema()` API تُعيد بنية القالب كـ JSON للواجهة.
- **↔ شاشة التعبئة:** `fill.php` تقرأ `evaluation_type` لكل قسم وترسم الحقل المناسب.

## 2.6 الأمان والصلاحيات
- `Auth::enforce('templates.manage')` في `BuilderController::__construct` (L23). ✅
- حذف قالب محمي: `Evaluation::isTemplateInUse()` يمنع الحذف إن استُخدم (TemplateController L57). ✅

## 2.7 الأداء
- ✅ `getTemplateWithStructure` استعلام واحد للقالب + استعلام لكل معيار لأقسامه — قابل للتحسين بـ JOIN واحد لكنه مقبول (قوالب قليلة).
- 🟡 `updateTemplateWithStructure` = DELETE + re-INSERT كامل الهيكل — ثقيل للقوالب الكبيرة لكن نادر.

## 2.8 الفجوات والتحديات
| # | الخطورة | الفجوة |
|---|---|---|
| G9 | 🟡 متوسطة | `form_templates.version` يُزاد عند كل تحديث (Template.php L200) لكن لا فحص optimistic concurrency — تحديثان متزامنان يكتب أحدهما فوق الآخر. |
| G10 | 🟡 متوسطة | التحديث = DELETE + re-INSERT → التقييمات القديمة تشير لـ `criteria_sections.id` محذوفة. **لكن** snapshots في `evaluations` تحمي البيانات التاريخية. |
| G11 | 🟢 منخفضة | `builder1.js`/`builder1.css` موجودة لكن غير محمّلة (stale). |
| G12 | 🟢 منخفضة | `templates/show.php` موجود لكن بلا مسار (unrouted). |

---

# الوحدة 3: إدارة التقييمات (Evaluations)

## 3.1 نظرة عامة
**الهدف:** إنشاء وتعبئة وحساب التقييمات (فردي/جماعي/دوري/وصفي).
**الإنجاز:** مكتمل — 4 مسارات إنشاء (فردي، جماعي، دورة/ورشة، وحدة)، حساب آلي للدرجة، snapshots، تقدير يدوي للوصفي.
**الالتزام المؤسسي:** جيد — transaction في الحفظ الجماعي، snapshots لكل تقييم.

## 3.2 مخطط البيانات
```
evaluations (id, template_id→form_templates.id,
    status ENUM(draft/pending_review/approved/completed),
    evaluation_date, evaluation_type ENUM(annual/periodic/course/workshop),
    evaluated_employee_id→employees.id,
    evaluator_employee_id→employees.id,
    employee_name, employee_code, branch_name, department_name,  ← SNAPSHOT
    position_name, entity_snapshot JSON,
    final_score DECIMAL(5,2), final_grade_label, final_grade_value,
    entity_type ENUM(employee/course/workshop/branch/department/admin),
    entity_id, context_type ENUM(course/workshop), context_id,
    created_by_user_id→users.id)

evaluation_answers (id, evaluation_id→evaluations.id,
    section_id→criteria_sections.id,
    raw_value, normalized_score DECIMAL,
    notes)
```

## 3.3 دورة حياة البيانات
- **المدخلات:** شاشة `fill.php` (فردي)؛ `bulk-create.php` (جماعي)؛ `FlexibleEvaluationController` (دورة/ورشة)؛ Excel import.
- **المعالجة:**
  - `saveEvaluationResults`: لكل قسم، يحدد `raw_value` + `normalized_score` حسب `evaluation_type` (numeric→تطبيع، percentage→خام، textarea→NULL، overall_grade→NULL).
  - `calculateFinalScore`: numeric/mixed = Σ(normalized_score × weight)؛ descriptive = قيمة التقدير العام من `grading_scales`.
- **المخرجات:** `evaluations` + `evaluation_answers`؛ `final_score`/`final_grade_label`/`final_grade_value` محدّثة.

## 3.4 الشاشات والمسارات
| المسار | Controller@Method | الصلاحية |
|---|---|---|
| `GET /evaluations/create` | EvaluationController@create | `evaluations.create` |
| `POST /evaluations/store` | EvaluationController@store | `evaluations.create` |
| `GET /evaluations/:id/fill` | EvaluationController@fill | `evaluations.fill` |
| `POST /evaluations/submit` | EvaluationController@submit | `evaluations.fill` |
| `GET /evaluations/bulk-create` | BulkEvaluationController@create | `evaluations.create` |
| `POST /evaluations/save-single` | BulkEvaluationController@saveSingle | `evaluations.create` |
| `POST /evaluations/bulk-save` | BulkEvaluationController@bulkSave | `evaluations.create` |

## 3.5 نقاط التكامل
- **↔ القوالب:** يقرأ `evaluation_mode` لتحديد طريقة الحساب.
- **↔ السلالم:** `GradingScale::getNumericValueForLabel(scaleName, gradeLabel)` لتحويل التقدير لقيمة رقمية في الوصفي.
- **↔ التقارير:** `EmployeeReportService` يقرأ `evaluations` مع snapshots.
- **↔ الهيكل التنظيمي:** snapshots تُجمَّد من `Employee::getDetailsForSnapshot`.

## 3.6 الأمان والصلاحيات
- `evaluations.create`, `evaluations.fill` مُنفَّذة. ✅
- 🔴 `evaluations.delete` (EvaluationController L528) و `evaluations.edit` (L563) **غير مُعرَّفة في seed** — 403 دائماً.
- Manager scope: `reports.view.team` يُستخدم فقط في `RoleDashboardService` — **لا** في `EvaluationController`. المدير لا يصل لـ `/evaluations`.

## 3.7 الأداء
- ✅ `calculateFinalScore` استعلام واحد للإجابات + حلقة في PHP — مقبول.
- 🟡 `saveEvaluationResults` = DELETE + re-INSERT لكل الإجابات — ثقيل لكن نادر (عند الحفظ فقط).
- ✅ فهارس: `idx_eval_emp_status`, `idx_eval_status_date` على `evaluations`.

## 3.8 الفجوات والتحديات
| # | الخطورة | الفجوة |
|---|---|---|
| G13 | 🔴 عالية | `evaluations.delete`/`evaluations.edit` غير معرَّفة في `permissions` seed → 403 دائماً. |
| G14 | 🟡 متوسطة | لا team-scoping في `EvaluationController` — المدير محجوب عن `/evaluations` تماماً. |
| G15 | 🟡 متوسطة | `saveEvaluationResults` = full overwrite (DELETE + re-INSERT) — لا حفظ جزئي. |
| G16 | 🟢 منخفضة | `Evaluation::updateStatus()` مُستدعاة (EvaluationController L381) لكن **غير معرَّفة** في النموذج — خطأ كامن (غير معرَّض لأن المسار غير قابل للوصول). |

---

# الوحدة 4: وحدة التدريب (Training)

## 4.1 نظرة عامة
**الهدف:** إدارة الدورات، الورش، الكتالوجات، وتقييماتها.
**الإنجاز:** **جزئي (~70%)** — CRUD موجود لكن بفجوات هيكلية حرجة.
**الالتزام المؤسسي:** ضعيف — نظاما تقييم متوازيان بلا توحيد، جداول مفقودة، مسارات ميتة.

## 4.2 مخطط البيانات
```
course_catalog (id, title[U], description, category, created_by→users.id)
workshop_catalog (id, title[لاU], description, category, created_by→users.id)

training_courses (id, catalog_id→course_catalog.id CASCADE,
    branch_id→branches.id, department_id→departments.id,
    start_date, end_date, status ENUM(4), created_by→users.id)

training_workshops (id, branch_id→branches.id CASCADE,
    department_id→departments.id, catalog_id→workshop_catalog.id,
    status ENUM(4), created_by[لاFK!])

course_evaluations (id, course_id→training_courses.id[U] CASCADE,
    expected_attendees, actual_attendees,
    program_regularity_weighted_score, participant_comprehension_weighted_score,
    final_score DECIMAL(5,2), evaluated_by→users.id)

workshop_evaluations (id, workshop_id→training_workshops.id[لاU!] CASCADE,
    expected_attendees, actual_attendees, attendance_score,
    program_regularity_weighted_score, final_score DECIMAL(8,2),
    evaluated_by[لاFK!])
```

### 🔴 الجدول المفقود: `course_participants`
**لا يوجد جدول يربط الموظف بالدورة.** البحث الشامل (schema + migrations + models + controllers) أكد عدم وجود `course_participants` أو أي junction table. الربط الوحيد بين موظف ودورة يحدث فقط عند إنشاء تقييم (عبر `evaluations.evaluated_employee_id` + `context_type='course'` + `context_id`).

**العواقب:**
- لا يمكن تسجيل حضور موظف بدورة دون إنشاء تقييم.
- لا تاريخ تدريبي للموظف ("أي دورات حضر؟").
- لا شهادات/حضور/تغذية راجعة فردية.
- `expected_attendees`/`actual_attendees` أعداد مجردة — لا يمكن التحقق ممن حضر.

## 4.3 دورة حياة البيانات
- **المدخلات:** شاشات `/training/courses/*`, `/training/workshops/*`, الكتالوجات، التقييمات.
- **المعالجة:** نظامان متوازيان:
  - **النظام A (legacy):** `course_evaluations`/`workshop_evaluations` — تقييم مسطح (حضور + انتظام + فهم = درجة).
  - **النظام B (CD-016+):** `evaluations` table بـ `entity_type='course'` — تقييم بالقالب.
- **المخرجات:** جدولان منفصلان — خطر الازدواجية في التقارير.

## 4.4 الشاشات والمسارات
| المسار | Controller |
|---|---|
| `GET /training/courses` | TrainingCourseController@index |
| `GET /training/courses/create` | TrainingCourseController@create |
| `POST /training/courses` | TrainingCourseController@store |
| `GET /training/courses/edit/:id` | TrainingCourseController@edit |
| `GET /training/courses/catalog` | CourseCatalogController@index |
| `GET /training/evaluations/create/:course_id` | CourseEvaluationController@create |
| `GET /training/workshops` | TrainingWorkshopController@index |
| `GET /training/workshops/evaluations/create/:workshop_id` | WorkshopEvaluationController@create |

### 🔴 مسارات مفقودة
- `GET /training/workshops/catalog/create` و `/edit/:id` — الـ controller methods موجودة لكن **بلا مسار** (مقارن بـ course catalog الذي لهما).
- `GET /training/courses/:id` و `/workshops/:id` — لا شاشة تفاصيل.
- قسم `// تقارير التدريب` في routes (L236-240) — **فارغ**، ميثودات التقارير في النموذج غير قابلة للوصول.

## 4.5 نقاط التكامل
- **↔ القوالب:** `form_templates.target_type='course'/'workshop'` للنظام B.
- **↔ التقييمات:** `evaluations.context_type/context_id` يربط تقييم الموظف بالدورة.
- **↔ الموظفين:** **مقطوع** — لا جدول مشاركة يربط الموظف بالدورة مباشرة.

## 4.6 الأمان والصلاحيات
- 🔴 `CourseEvaluationController::__construct` (L15) يستدعي `Auth::enforce('training.evaluate')` — **الصلاحية غير معرَّفة في seed** → 403 دائماً.
- 🟡 `WorkshopEvaluationController` constructor معلَّق (L12-14)، الصلاحية inline per-method بـ `training.manage` — **عدم اتساق** مع CourseEvaluation.
- 🟡 `courses.manage`/`workshops.manage` معرَّفة في seed (perm 29/30) لكن **غير مستخدمة في الكود** — صلاحيات ميتة.

## 4.7 الأداء
- ✅ فهارس على `branch_id`, `department_id` في training_courses/workshops.
- 🟡 `Department::getAll()` (L20) JOIN departments × employees × training_workshops × workshop_evaluations — ثقيل لصفحة قائمة.

## 4.8 الفجوات والتحديات
| # | الخطورة | الفجوة |
|---|---|---|
| G17 | 🔴 حرجة | **لا `course_participants` table** — لا يمكن ربط موظف بدورة بشكل موثوق. |
| G18 | 🔴 عالية | نظاما تقييم متوازيان (A: course_evaluations + B: evaluations) بلا خطة توحيد — خطر ازدواجية. |
| G19 | 🔴 عالية | `training.evaluate` غير معرَّفة في seed → CourseEvaluationController 403 دائماً. |
| G20 | 🔴 عالية | مسارات workshop catalog create/edit مفقودة — controller methods غير قابلة للوصول. |
| G21 | 🟡 متوسطة | تباين schema: course_evaluations.course_id UNIQUE، workshop_evaluations.workshop_id ليس UNIQUE. |
| G22 | 🟡 متوسطة | `training_workshops.created_by` بلا FK. |
| G23 | 🟡 متوسطة | ميثودات تقارير التدريب في النموذج بلا مسارات/شاشات — كود ميت. |
| G24 | 🟡 متوسطة | 3 ملفات عرض يتيمة + 2 صلاحيات ميتة + `TrainingCourseController::delete()` مكرر بـ `destroy()`. |

---

# الوحدة 5: محرك التقارير (Reporting Engine)

## 5.1 نظرة عامة
**الهدف:** حساب المتوسطات، أرشفة التقارير (snaphots غير قابلة للتعديل)، تصدير PDF/Excel/Word.
**الإنجاز:** مكتمل وظيفياً لكن بـ **خلل في خلط المقاييس**.
**الالتزام المؤسسي:** متوسط — snapshots ممتازة، لكن التقارير الإدارية تتجاوزها.

## 5.2 مخطط البيانات
```
report_snapshots (id, report_number[U] VARCHAR(24),
    employee_id→employees.id, created_by_user_id→users.id,
    snapshot_json LONGTEXT JSON, created_at)
    ← TRIGGER: BEFORE UPDATE/DELETE → SIGNAL SQLSTATE '45000' (immutable)

report_snapshot_counters (id TINYINT PK, last_number BIGINT)
    ← atomic sequence for report_number generation

grading_scales (id, scale_name, grade_label, min_value, max_value,
    numeric_value, sort_order, is_active, template_id[CD-023])
```

## 5.3 دورة حياة البيانات
- **المدخلات:** `ReportsController::create` (بحث موظف) → `generate` → `ReportSnapshotService::createForEmployee`.
- **المعالجة:**
  - `EmployeeReportService::getProfileReport`: استعلام واحد يجلب تقييمات الموظف + حساب متوسطات لـ 5 أنواع (overall/annual/periodic/course/workshop).
  - `AveragesEngine::summarize`: متوسط `final_score` فقط.
  - `ReportSnapshotService`: يجمّد كل البيانات في `snapshot_json` + يولّد `report_number` (ER-NNNNNNNN).
- **المخرجات:** `report_snapshots` (immutable)؛ تصدير PDF/Excel/Word من الـ snapshot.

## 5.4 الشاشات والمسارات
| المسار | Controller@Method |
|---|---|
| `GET /reports/create` | ReportsController@create |
| `GET /reports/archive` | ReportsController@archive |
| `POST /reports/generate` | ReportsController@generate |
| `GET /reports/print/:id` | ReportsController@printOnly |
| `GET /reports/export/:id/:format` | ReportsController@export |
| `GET /employees/profile/:id/reports` | EmployeeReportController@generate |
| `GET /employees/profile/:id/reports/:reportId` | EmployeeReportController@show |

### 🟡 تكرار معماري
`ReportsController` و `EmployeeReportController` كلاهما يطبّق "generate" + "export" بصلاحية `employees.profile` وأسماء حقول CSRF مختلفة.

## 5.5 نقاط التكامل
- **↔ التقييمات:** يقرأ `evaluations` + `final_score` + `final_grade_label`.
- **↔ السلالم:** `grading_scales` لتحويل الدرجة لتسمية تقدير.
- **↔ الموظفين:** `Employee::getDetailsForSnapshot` لبيانات الموظف.

## 5.6 الأمان والصلاحيات
- 🔴 `reports.generate` معرَّفة (migration CD-021) لكن **لا controller يستخدمها** — كلها تستخدم `employees.profile`. صلاحية يتيمة.
- 🔴 `ReportsController::printOnly` (L124-166) — PDF عابر **بلا حفظ snapshot ولا Audit::log** — أي شخص بـ `employees.profile` يطبع بلا أثر.
- ✅ snapshots غير قابلة للتعديل (triggers SQLSTATE 45000).

## 5.7 الأداء
- 🔴 **N+1 في بحث الموظفين:** `ReportsController::searchEmployees` (L213-256) يستدعي `buildEvalSummary` ← `getProfileReport` (استعلامان) لكل موظف → 100+ استعلام لـ 50 موظف.
- 🔴 لا فهرس على `report_snapshots.created_at` — فلتر السنة يفحص كامل الجدول.
- 🔴 بحث الأرشيف بـ `JSON_EXTRACT(snapshot_json, '$.employee.full_name') LIKE` — full table scan، لا فهرس functional.
- 🟡 `LIMIT 50` ثابت في بحث الموظفين — لا ترقيم صفحات.

## 5.8 الفجوات والتحديات
| # | الخطورة | الفجوة |
|---|---|---|
| G25 | 🔴 حرجة | **خلط مقاييس في AveragesEngine** — `summarize` يطبّق متوسط `final_score` عبر تقييمات رقمية (0-100) ووصفية (1-5) **بلا تطبيع** → متوسط خاطئ (85+5)/2=45 → "ضعيف". |
| G26 | 🔴 عالية | التقييمات الوصفية ذات تقدير يدوي تُعرض في عمود "الدرجة / 100" بدل تجميعها في bucket وصففي (`ReportExportService::groupRecords` L391-416). |
| G27 | 🔴 عالية | `reports.generate` يتيمة — لا controller يستخدمها. |
| G28 | 🔴 عالية | `printOnly` بلا audit log — طباعة بلا أثر. |
| G29 | 🔴 عالية | `getAdminReports()` (Evaluation.php L1346-1377) JOIN موظفين حيّ → يتجاوز snapshots بعد النقل. |
| G30 | 🟡 متوسطة | `EmployeeReportService::getProfileReport` يفلتر `emp_code IS NOT NULL` → موظف بلا emp_code يُفقد حتى لو له تقييمات. |
| G31 | 🟡 متوسطة | `summarizeByYear` يملأ الفجوات، لكن `getAnnualSummaryWithActualDate` (SQL GROUP BY) **لا** يملأها — نفس الموظف يرى قوائم سنوات مختلفة. |
| G32 | 🟡 متوسطة | كود ميت: `ReportPDF.php`, `PdfReportBuilder.php`, `pChart/` library، `AveragesEngine::nearestGradeLabel`. |
| G33 | 🟡 متوسطة | snapshot غير قابل للإصلاح — triggers تمنع UPDATE/DELETE بلا override. بيانات خاطئة = دائمة. |

---

# الوحدة 6: الأمان والصلاحيات (Auth & RBAC)

## 6.1 نظرة عامة
**الهدف:** إدارة المستخدمين، الأدوار، الصلاحيات، CSRF، سجل التدقيق.
**الإنجاز:** ناضج لكن بـ **فجوات أمنية حرجة**.
**الالتزام المؤسسي:** متوسط — RBAC مصمم جيداً، لكن التنفيذ به ثغرات.

## 6.2 مخطط البيانات
```
users (id, username[U], email[U], password_hash, status ENUM(3),
    employee_id→employees.id CASCADE, must_change_password,
    permissions_version INT, last_login_at)

roles (id, name[U], is_system_role) — seeded: Admin/HR/Manager/Staff

user_roles (user_id→users.id, role_id→roles.id) — composite PK

permissions (id, name[U], description, category) — 42 seeded

role_permissions (role_id→roles.id, permission_id→permissions.id) — composite PK

audit_logs (id, user_id→users.id SET NULL, action, entity_type,
    entity_id, details TEXT, ip_address, created_at)

login_attempts (id, username, ip_address, success, attempted_at)
password_history (id, user_id→users.id, password_hash, created_at)
csrf_tokens (id, user_id→users.id, token, used, expires_at)
notifications (id, user_id→users.id, title, message, is_read, created_at)
```

## 6.3 دورة حياة البيانات
- **المدخلات:** تسجيل دخول (`AuthController::login`)؛ إدارة مستخدمين (`Admin/UserController`).
- **المعالجة:**
  - المصادقة: `password_verify` + فحص status + قفل تلقائي (5 محاولات/15 دقيقة).
  - الصلاحيات: `refreshSessionPermissions()` يملأ `$_SESSION['user_permissions']` من DB.
  - `permissions_version`: DB triggers تزيده عند تغيير roles/permissions → `invalidateSessionIfPermissionsChanged` يلغي الجلسة.
- **المخرجات:** `audit_logs` (يدوي عبر `Audit::log` في كل controller).

## 6.4 الشاشات والمسارات
| المسار | Controller@Method |
|---|---|
| `GET /login` | AuthController@loginForm |
| `POST /login` | AuthController@login |
| `GET /logout` | AuthController@logout |
| `GET /admin/users` | Admin/UserController@index |
| `GET /admin/roles` | Admin/RoleController@index |
| `GET /grading-scales` | GradingScaleController@index |

## 6.5 نقاط التكامل
- **↔ كل الوحدات:** كل controller يستدعي `Auth::enforce('permission.name')`.
- **↔ لوحة التحكم:** `RoleDashboardService::resolvePersona` يحدد persona من الأدوار/الصلاحيات.
- **↔ التدقيق:** `Audit::log` يُستدعى في معظم العمليات الحساسة (إنشاء/تعديل/حذف).

## 6.6 الأمان والصلاحيات

### 🔴 ثغرات حرجة
1. **كلمات مرور بنص صريح في audit_logs** — `Admin/UserController::update` (L388) يمرر `$data` المتضمن `password` plaintext إلى `Audit::log`. يُخزَّن في `audit_logs.details` JSON. **أي شخص يقرأ audit_logs يسترد كلمات المرور.**
2. **ApiController بلا مصادقة** — `ApiController` (L19: `Auth::handle()` معلَّق). أي شخص يعدّد الموظفين عبر `/api/employees/search`.
3. **مسارات تدميرية عبر GET** — `backup/restore/:id`, `backup/delete/:id`, `backup/cleanup` كلها GET. ثغرة CSRF: `<img src="/backup/restore/3">`.

### 🟡 فجوات عالية
4. **صلاحيات مفقودة من seed:** `evaluations.delete`, `evaluations.edit`, `training.evaluate`, `backup.manage`, `branches.create/edit/delete`, `departments.create/edit/delete` — مُستخدمة في `Auth::enforce` لكن **غير معرَّفة** في `permissions` seed → 403 دائماً.
5. **`Auth::user()` يعيد كائن جلسة قديم** — لا يُعاد جلبه من DB. تعليق مستخدم mid-session لا يُلغي جلسته فوراً.
6. **`Auth::logout()` ناقص** — فقط `session_unset()`، لا `session_destroy()` ولا `session_regenerate_id()`.
7. **قفل تسجيل الدخول per-username فقط** — لا per-IP. مهاجم يبدّل usernames لتجاوز القفل.
8. **`api_keys` table غير معرَّفة في install_schema** — V1 API مكسور على تثبيت جديد.

### ✅ نقاط القوة
- `password_hash(PASSWORD_DEFAULT)` — bcrypt. ✅
- CSRF: token 128-bit + `hash_equals` + session + DB single-use. ✅
- `permissions_version` mechanism لـ invalidation. ✅
- `session.cookie_samesite=Strict`. ✅
- `ATTR_EMULATE_PREPARES=false` — prepared statements حقيقية، لا SQLi. ✅
- Password policy (طول 8، 3 من 4 فئات، 100 كلمة شائعة مرفوضة، history=3). ✅

## 6.7 الأداء
- 🟡 لا فهارس على `audit_logs(action)`, `(entity_type, entity_id)`, `(created_at)` — استعلامات التدقيق full scan.
- ✅ `permissions_version` caching (60s) — يقلل استعلامات الصلاحيات.
- ✅ `adminRoleIds()` per-request cache.

## 6.8 الفجوات والتحديات
| # | الخطورة | الفجوة |
|---|---|---|
| G34 | 🔴 حرجة | كلمات مرور plaintext في audit_logs (UserController L388). |
| G35 | 🔴 حرجة | ApiController بلا مصادقة. |
| G36 | 🔴 حرجة | مسارات backup تدميرية عبر GET. |
| G37 | 🔴 عالية | 8 صلاحيات مفقودة من seed → 403 دائماً في مسارات متعددة. |
| G38 | 🔴 عالية | `api_keys` table غير معرَّفة في installer. |
| G39 | 🟡 متوسطة | `Auth::user()` قديم (stale) — لا invalidation فورية للحالة suspended. |
| G40 | 🟡 متوسطة | لا team-scoping خارج `RoleDashboardService`. |
| G41 | 🟡 متوسطة | لا audit لـ: branch CRUD، grading scales، settings، backup operations، report exports. |
| G42 | 🟢 منخفضة | `Auth::is()` و `Auth::hasRole()` مكرران. |

---

# مصفوفة الترابط (Integration Matrix)

| الوحدة ↓ \ تتطلب ← | الهيكل التنظيمي | باني القوالب | التقييمات | التدريب | التقارير | الأمان |
|---|---|---|---|---|---|---|
| **الهيكل التنظيمي** | — | — | snapshots | — | employee lookup | Auth::enforce |
| **باني القوالب** | positions | — | template structure | target_type | — | templates.manage |
| **التقييمات** | employee snapshot | schema + mode | — | context (course/workshop) | final_score → reports | evaluations.create |
| **التدريب** | branch/dept | target_type='course' | evaluations (System B) | — | — | training.manage |
| **التقارير** | employee search | — | evaluations + averages | — | — | employees.profile |
| **الأمان** | user↔employee link | — | — | — | — | — |

### نقاط التقاطع الحرجة
1. **التدريب ↔ التقييمات:** نظامان متوازيان (course_evaluations + evaluations) — خطر ازدواجية.
2. **الهيكل ↔ التقارير:** snapshots تحمي التاريخ، لكن `getAdminReports` يتجاوزها.
3. **القوالب ↔ التقييمات:** `evaluation_mode` يحدد طريقة الحساب، لكن `AveragesEngine` لا يطبع المقاييس.

---

# قائمة الأولويات (Priorities List)

## 🔴 P0 — تدخل عاجل (قبل أي توسع)
| # | البند | الوحدة |
|---|---|---|
| 1 | إزالة كلمات المرور plaintext من audit_logs | أمان |
| 2 | إضافة مصادقة لـ ApiController | أمان |
| 3 | تحويل مسارات backup التدميرية إلى POST | أمان |
| 4 | إضافة الصلاحيات المفقودة لـ seed (`evaluations.delete`, `training.evaluate`, `backup.manage`, `branches.create/edit/delete`, `departments.create/edit/delete`) | أمان |
| 5 | إنشاء جدول `course_participants` | تدريب |
| 6 | إصلاح خلط المقاييس في AveragesEngine (تطبيع 1-5 إلى 0-100 أو bucket منفصل) | تقارير |

## 🟠 P1 — عالية (خلال السبرنت القادم)
| # | البند | الوحدة |
|---|---|---|
| 7 | إصلاح `getAdminReports` لاستخدام snapshots بدل JOIN حي | هيكل + تقارير |
| 8 | توحيد نظامَي التقييم في التدريب (System A → B) أو توثيق الفصل | تدريب |
| 9 | ربط `reports.generate` بـ controller أو حذفها | تقارير |
| 10 | إضافة audit لـ `printOnly` | تقارير |
| 11 | مسارات workshop catalog create/edit المفقودة | تدريب |
| 12 | استبدال `Employee::getAll()` بـ autocomplete للمانجر picker | هيكل |
| 13 | `api_keys` table في installer | أمان |

## 🟡 P2 — متوسطة (خلال شهر)
| # | البند | الوحدة |
|---|---|---|
| 14 | team-scoping في EvaluationController للمدير | تقييمات |
| 15 | مسار نقل موظف مخصص + سجل نقل | هيكل |
| 16 | فهارس على `audit_logs` و `report_snapshots.created_at` | أمان + تقارير |
| 17 | إصلاح تباين schema بين course/workshop evaluations | تدريب |
| 18 | حذف الكود الميت (`ReportPDF`, `PdfReportBuilder`, `pChart`, `builder1.js`, orphaned views) | عام |
| 19 | إصلاح `summarizeByYear` gap inconsistency | تقارير |
| 20 | `Auth::logout()` كامل + invalidation فورية للحالة suspended | أمان |

---

# إجابات الاستفسارات المحددة

## أ. الهيكل التنظيمي

**س: إذا نُقل موظف من قسم لآخر، كيف تتعامل التقييمات القديمة؟**
**ج:** التقييمات القديمة **محمية** عبر snapshot columns في `evaluations` (employee_name, branch_name, department_name, position_name). تُجمَّد عند إنشاء التقييم عبر `Employee::getDetailsForSnapshot()`. **لكن** `Evaluation::getAdminReports()` (L1346-1377) يتجاوز snapshots و JOIN موظفين حيّ — تقارير تُنسب للقسم الجديد. **هذه فجوة (G2/G29).**

**س: مشاكل أداء عند 5000 موظف؟**
**ج:** نعم — `Employee::getAll()` بلا LIMIT لقائمة المديرين (G4)؛ `findByDepartment()` و `getAllActiveEmployeeIds()` بلا LIMIT (G6). ترقيم صفحات موجود فقط في `index`. البحث autocomplete يصلب `LIMIT 10`.

## ب. باني القوالب

**س: ما هي القوالب الـ 7 الموجودة؟**
**ج:** في الإنتاج: قوالب numeric (employee)، descriptive (CD-023 demo)، grade-based. القوالب "الميتة" هي تلك بـ `is_active=0`. لا يوجد آلية لتتبع القوالب غير المستخدمة — يُحتاج استعلام `SELECT * FROM form_templates WHERE id NOT IN (SELECT DISTINCT template_id FROM evaluations)`.

**س: عند تعديل قالب مستخدم مسبقاً، كيف نحافظ على ثبات التقارير؟**
**ج:** عبر **snapshot pattern**: التقييمات تخزن بيانات الموظف مجمَّدة. `updateTemplateWithStructure` = DELETE + re-INSERT لـ criteria/sections → الـ IDs تتغير، لكن `evaluation_answers` تشير لـ `section_id` قديم قد لا يعود موجوداً. **الحماية الفعلية** هي أن `calculateFinalScore` و `getProfileReport` تقرأ من `evaluation_answers` (البيانات المخزنة) لا من القالب الحي. لكن `getTemplateWithStructure` للقالب المُحدَّث قد لا يطابق أقسام التقييم القديم. `form_templates.version` يُزاد لكن لا optimistic concurrency check.

## ج. التقييمات

**س: في التقييم الجماعي، كيف يُضمن Transaction واحد؟**
**ج:** `BulkEvaluationController::bulkSave()` (L208-274) يفتح `$db->beginTransaction()` (L262)، ثم يكرّر `createEvaluation + saveResults` لكل موظف. عند فشل أي واحد: `$db->rollBack()` (L273) → كل التقييمات السابقة في نفس الـ transaction تُلغى. ✅. **لكن** `createEvaluation` و `saveResults` كل منها تستدعي استعلامات منفصلة بلا nested transaction — إذا فشل `saveResults` بعد نجاح `createEvaluation`، الـ rollback الخارجي يلغي الاثنين. ✅ سليم.

## د. التدريب

**س: هل يوجد جدول `course_participants`؟**
**ج:** **لا.** هذا أكبر فجوة في الوحدة (G17). لا جدول يربط الموظف بالدورة. الربط الوحيد ضمني عبر `evaluations` بـ `context_type='course'` + `context_id`. بدون هذا الجدول، لا يمكن ربط تقييمات الدورات بملف الموظف بشكل موثوق (لا تاريخ تدريبي، لا شهادات، لا حضور فردي).

**س: كيف تُدار التقييمات القديمة للدورات؟ هل ستُنقل إلى `evaluations`؟**
**ج:** نظامان متوازيان: System A (`course_evaluations`/`workshop_evaluations` — legacy) و System B (`evaluations` table — CD-016+). **لا خطة توحيد موثَّقة** في أي ملف في `docs/`. خطر الازدواجية في التقارير. **التوصية:** توحيد في System B مع migration للبيعات القديمة، أو توثيق فصل رسمي.

## هـ. التقارير

**س: كيف يتعامل النظام مع موظف بلا تقييمات؟**
**ج:** يظهر في بحث التقارير (`searchEmployees`) ويُصدر له snapshot فارغ (`records=[]`, `averages=null`). يُعرض "لا توجد تقييمات مسجلة لهذا الموظف حتى الآن." في PDF/HTML. **لكن** موظف بلا `emp_code` **يُفقد** من `getProfileReport` (INNER JOIN + `emp_code IS NOT NULL` filter, G30).

**س: كيف يُحسب المتوسط مع تقييمات رقمية ووصفية معاً؟**
**ج:** **خطأ:** `AveragesEngine::summarize` (L9-15) يطبّق `AVG(final_score)` عبر الكل بلا تطبيع. رقمي=0-100، وصفي=1-5. المتوسط يصبح بلا معنى (مثال: (85+5)/2=45 → "ضعيف"). **التوصية:** إما تطبيع الوصفي لـ 0-100 قبل المتوسط، أو bucket منفصل لا يُدمج في `overall`.

---

## خاتمة

هذا التدقيق كشف أن النظام **وظيفي ومستخدم** لكنه يحمل **6 فجوات حرجة (P0)** و **7 فجوات عالية (P1)** تتطلب تدخلاً قبل التوسع المؤسسي أو التكامل مع وحدة التدريب. أبرزها:

1. **الأمان:** كلمات مرور في audit_logs + API بلا مصادقة + GET تدميري.
2. **التدريب:** لا `course_participants` — علاقة الموظف بالدورة مقطوعة.
3. **التقارير:** خلط مقاييس ينتج متوسطات خاطئة.

**التوصية:** معالجة P0 قبل أي تطوير جديد. ثم P1 في السبرنت القادم. ثم P2 خلال شهر.

— كبير المطورين
