# تقرير تنفيذ CD-040.1 — الاختبار المقبول للمستخدم (UAT)

**الالتزام المُختبر:** `297135e` — `feat(CD-040.1): full mockup parity — quick-add, cascade filters, program filter bar, preview`
**التاريخ:** 2026-11-09
**النطاق:** صفحة التقييم الموحّدة `/evaluations/create` (جديد) و `/evaluations/{id}/fill` (تعديل)، مع قائمة `/evaluations` وصفحة `/evaluations/bulk-create`.
**المرجع:** توجيه CD-040.1 (14 سيناريو UAT).

---

## 1. الملخص التنفيذي

| المؤشر | القيمة |
|---|---|
| إجمالي السيناريوهات | 14 |
| ناجح (PASS) | 8 |
| فاشل (FAIL) | 5 |
| جزئي (PARTIAL) | 1 |
| قرار الإطلاق | **غير جاهز** — توجد 4 عيوب عالية الخطورة تمسّ الفلاتر، حفظ المقيّم، الدقة الحسابية، وصلاحية منقّي البرامج |

**النتيجة المختصرة:** المسار الأساسي للصفحة الموحّدة يعمل (إنشاء، تعديل، معاينة، حفظ، حساب حي للقالب متساوي الأوزان، صلاحيات، تجاوب). لكن ثلاث وظائف محورية لا تعمل كما هو مطلوب: **فلترة الموظفين بالفرع/القسم**، **حفظ المقيّم المختار**، و**التعامل مع النوع المنطقي (boolean) والأوزان غير المتساوية**. إضافةً إلى خطأ JavaScript عام (`APP_URL`) وتحذيرات PHP في صفحة التقييم الجماعي.

---

## 2. بيئة التنفيذ وطريقة العمل

| العنصر | القيمة |
|---|---|
| المشروع | `C:\xampp\htdocs\employee-evaluation` عند `297135e` |
| الخادم | Apache 2.4.58 / PHP 8.2.12 (XAMPP) |
| قاعدة البيانات | MariaDB 10.4.32 |
| قاعدة اختبار معزولة | `employee_evaluation_cd040` (نسخة من `employee_evaluation_uat`) |
| الحساب الأساسي | `uat_manager` — مستخدم 4486، موظف 105، دور Manager (3) |
| حساب الصلاحيات المحدودة | `cd040_other` — مستخدم 7141، موظف 106، دور CD040 Restricted (689) |
| المتصفح | Chrome headless عبر CDP (منفذ 9333–9339)، بمقاسين 1440×1000 و390×844 |
| قوالب الاختبار | 104 «وزن ٥٠/٥٠» و105 «أنواع مختلطة» |

**ملاحظات منهجية:**
- تم أخذ نسخة احتياطية كاملة من حالة المستودع قبل الإصلاح إلى `297135e` (patch + نسخ ملفات + فرع `backup/cd040-pre-repair-20261109_201338`).
- لم تُعدّل أي ملفات مصدرية في المستودع؛ الاختبار جرى على قاعدة معزولة، وأُعيد `config/database.php` الأصلي بعد الانتهاء (تم التحقق من التطابق).
- صلاحية `training.view` مُنحت مؤقتًا لدور Manager لاختبار منقّي البرامج ثم **أُلغيت** (تم التحقق: العدد = 0).
- لقطات الشاشة التُقطت بـ Chrome headless لأن أداة لقطة الشاشة المدمجة تتطلب نافذة سطح مكتب مرئية. اللقطات حقيقية للصفحات.
- لا تتوفر نسخة نصية من توجيه CD-040.1 داخل المستودع؛ أُعيد بناء جدول السيناريوهات الـ14 من نطاق التوجيه المذكور، وتم اختبار كل بند وظيفي فيه.

---

## 3. جدول نتائج UAT

| # | السيناريو | المتوقع | النتيجة | الدليل |
|---|---|---|---|---|
| UAT-1 | إنشاء تقييم جديد من `/evaluations/create` | تحميل الصفحة، بحث موظف، اختيار مقيّم، تحميل بنية القالب ديناميكيًا، الحفظ ينشئ سجلًا وينتقل لصفحة العرض | ✅ PASS | `01_new_create_desktop.png`، `01_employee_search_results.png`، `01_employee_evaluator_selected.png`، `01_template104_criteria.png`، `01_saved_show_page.png`، `evidence_stageA.json` (evalId=191) |
| UAT-2 | فتح تقييم محفوظ وتعديله | `reopen` يعيد الحالة مسودة، `/fill` يعبّئ 85 و8، تعديل القيم والحفظ يحدّث السجل | ✅ PASS | `02_edit_prefill.png`، `02_edit_modified.png`، `02_edit_saved_show.png`، `evidence_stageB.json` (response `final_score: 90`) |
| UAT-3 | الفلاتر المتتالية (فرع ← قسم) في بحث الموظف/المقيّم | اختيار الفرع يحمّل أقسامه، والبحث يُصفّى حسب الفرع والقسم | ❌ FAIL | `03_cascade_filter_empty.png`، `evidence_stageA.json → uat03_*`، `Network_Evidence.txt` |
| UAT-4 | الإضافة السريعة لموظف | إنشاء موظف جديد (رقم/اسم/فرع/قسم/منصب) واختياره تلقائيًا | ⚠️ PARTIAL | `04_quick_add_form.png`، `04_quick_add_selected.png`، `DB_Evidence.txt` (الموظف 7606) |
| UAT-5 | حفظ بيانات المقيّم المختار | يُحفظ `evaluator_employee_id` كما اختاره المستخدم، ويتسق الاسم/الصفة معه | ❌ FAIL | `Network_Evidence.txt` (POST = 5)، `DB_Evidence.txt` (المحفوظ = 105) |
| UAT-6 | منقّي البرامج (قالب دورة/ورشة) | تحميل البرامج وفلترتها بالفرع/القسم/التاريخ وعرض بطاقة معلومات البرنامج | ❌ FAIL | `06_program_filter_403.png`، `06_program_filters_working.png`، `evidence_stageE.json` |
| UAT-7 | معاينة التقييم قبل الحفظ | تعرض الأطراف والقالب والبرنامج والتاريخ وكل الإجابات والدرجة المحسوبة | ✅ PASS | `07_preview_modal.png`، `evidence_stageA.json → uat07_preview` |
| UAT-8 | الحفظ ومزامنة قاعدة البيانات (قالب رقمي) | حفظ الإجابات مع `normalized_score`، وحساب `final_score` بشكل صحيح | ✅ PASS | `DB_Evidence.txt` (الأسطر 117/118 و119/120)، `Network_Evidence.txt` |
| UAT-9 | الحساب الحي: 85% + 8/10 | الناتج 82.5 والتقدير «جيد جدًا» | ✅ PASS | `09_live_score_825.png`، `evidence_stageA.json → uat09_live_score` |
| UAT-10 | الصلاحيات والأمان | مستخدم غير مسند إليه التقييم لا يفتح `/fill` ولا يحفظ، و`/evaluations` تتطلب `reports.view.all` | ✅ PASS | `10_fill_foreign_draft_redirect.png`، `10_evaluations_index_403.png`، `evidence_stageD.json`، `evidence_stageD2.json` |
| UAT-11 | الأنواع الديناميكية (percentage/numeric/grade/boolean/textarea/overall) | تُعرض وتُحفظ كل الأنواع، والحساب الحي يساوي 90.0 والنهائي 90.0 | ❌ FAIL | `11_boolean_criteria.png`، `11_boolean_live_score.png`، `11_boolean_preview.png`، `11_boolean_saved_show.png`، `DB_Evidence.txt` |
| UAT-12 | التجاوب (Responsive) | لا تجاوز أفقي على 390px، وتخطيط عمودين على 1440px | ✅ PASS | `12_mobile_390.png`، `12_desktop_1440.png`، `evidence_stageA.json → uat12_*` |
| UAT-13 | نظافة السجلات (Console/PHP) | لا أخطاء JavaScript أو تحذيرات PHP عند الاستخدام | ❌ FAIL | `PHP_Log_Evidence.txt`، `evidence_stageA/B/C/E.json → console_exceptions` |
| UAT-14 | قائمة `/evaluations` وصفحة `/evaluations/bulk-create` | الصفحتان تُحمّلان وتعرضان البيانات والنموذج | ✅ PASS | `14_evaluations_list.png`، `14_bulk_create.png`، `evidence_stageA.json → uat14_*` |

---

## 4. تفاصيل العيوب المكتشفة

### CD040-UAT-D01 — فلترة الموظفين بالفرع/القسم لا تعمل (عالية)
- **السيناريو:** UAT-3
- **الوصف:** الفلترة تتم على العميل داخل `cd040_renderEmpResults()` عبر `e.branch_id` و`e.department_id`، لكن واجهة البحث `GET /api/employees/search` لا تعيد هذين الحقلين أصلًا، ولا تعيد أسماء الفرع/القسم/المنصب.
- **الأثر:** أي اختيار للفرع أو القسم يُنتج «لا توجد نتائج ضمن الفلتر» رغم وجود موظفين مطابقين. القوائم المتتالية نفسها تعمل (تم تحميل 5 أقسام للفرع 1).
- **السبب الجذري:**
  - `app/Models/Employee.php:340` — `SELECT id, emp_code, full_name, position_id` فقط.
  - `app/Views/evaluations/create.php:493-496` — الفلترة تعتمد على حقول غير موجودة.
- **الإصلاح المقترح:** توسيع استعلام البحث ليعيد `branch_id, department_id` مع `branch_name, department_name, position_title` عبر joins، أو نقل الفلترة إلى الخادم.

### CD040-UAT-D02 — `evaluator_employee_id` المختار لا يُحفظ (عالية)
- **السيناريو:** UAT-5
- **الوصف:** النموذج أرسل `evaluator_employee_id=5` (خالد الغامدي) مع اسمه وصفت‌ه، لكن `saveUnified()` لا يقرأ هذا الحقل إطلاقًا، ويستنتج المقيّم من جلسة المستخدم (الموظف 105). النتيجة: السجل يحمل `evaluator_employee_id=105` بينما `evaluator_name/title` مأخوذان من الموظف 5 — تناقض صريح.
- **السبب الجذري:** `app/Controllers/EvaluationController.php:519-524` (لا قراءة للحقل) و`:569-571` (الاستنتاج من الجلسة). وفي مسار التحديث `:555-560` يُحدّث الاسم والصفة فقط دون المعرف.
- **الإصلاح المقترح:** قراءة `evaluator_employee_id` من الطلب، التحقق من وجود الموظف وصلاحية إسناده، واستخدامه في الإنشاء والتحديث مع جلب الاسم/الصفة من نفس السجل.

### CD040-UAT-D03 — الحساب الحي يتجاهل الأوزان ويحسب boolean خطأً (عالية)
- **السيناريو:** UAT-9 / UAT-11
- **الوصف:** `cd040_calc()` يجمع كل عناصر `.cd040-score` ويأخذ متوسطًا بسيطًا بعد تطبيع كل عنصر على `data-max`، دون أي اعتبار لأوزان الأقسام أو المعايير، ودون تضمين `grade` (لأنه `select` وليس `.cd040-score`)، مع تضمين حقل boolean المخفي الذي لا يحمل `data-max`.
- **الأثر:** قالب 105 أعطى 67.0 بدل 90.0: (100% + 100% + 1%) ÷ 3 = 67.0. ويعمل بالصدفة على القالب 104 لأن أوزانه متساوية (50/50) ولأن نوعيه رقميان.
- **السبب الجذري:** `app/Views/evaluations/create.php:680-684`، و`:656` (حقل boolean المخفي).
- **الإصلاح المقترح:** حساب مرجّح فعلي (قيمة/حد أعلى × وزن القسم داخل المعيار × وزن المعيار)، واستبعاد الأنواع غير الرقمية من المتوسط، وإعطاء boolean تطبيعًا صريحًا (نعم = 100).

### CD040-UAT-D04 — النوع المنطقي يُحفظ بـ `normalized_score = 0.00` (عالية)
- **السيناريو:** UAT-11
- **الوصف:** إجابة «نعم» تُحفظ `raw_value = 1` لكن `normalized_score = 0.00`؛ لذلك جاءت الدرجة النهائية 80.0 بدل 90.0 (وزن boolean = 10%).
- **السبب الجذري:** `app/Models/Evaluation.php` — فرع `boolean` يترك القيمة 0.0 («لم تُعالج بعد») في `saveEvaluationResults()` (حوالي السطر 280).
- **الإصلاح المقترح:** تطبيع boolean إلى 100/0 (أو القيمة المعرّفة في بنية القسم) قبل حساب الوزن، وإضافة اختبار وحدة يغطي القالب 105.

### CD040-UAT-D05 — منقّي البرامج محجوب بصلاحية `training.view` + فلتر القسم يُصفَّر (عالية)
- **السيناريو:** UAT-6
- **الوصف:** قالب الدورة يعرض بطاقة البرنامج، لكن الطلب `GET /api/training-programs/list/course` يعيد **403** لحساب Manager، فتظهر «عرض 0 من 0 برنامج». بعد منح `training.view` مؤقتًا ظهرت 3 برامج ونجح فلتر الفرع، لكن فلتر القسم عاد إلى «كل الأقسام» بعد كل استدعاء.
- **السبب الجذري:**
  - `app/Controllers/TrainingProgramController.php:22` — `Auth::enforce('training.view')` على كل الوحدة، ودور Manager لا يملكها.
  - `app/Views/evaluations/create.php:705` — `cd040_filterPrograms()` يعيد تحميل أقسام الفرع في كل استدعاء، فيُصفّر `progDeptFilter` قبل قراءته.
- **الإصلاح المقترح:** استخدام صلاحية أنسب لقراءة البرامج داخل التقييم (أو إضافة `training.view` لدور المقيّم)، وإعادة تحميل الأقسام فقط عند تغيّر الفرع مع الحفاظ على الاختيار، أو فلترة الخادم.

### CD040-UAT-D06 — تكرار تعريف `const APP_URL` (متوسطة)
- **السيناريو:** UAT-13
- **الوصف:** كل صفحة تُصدر `SyntaxError: Identifier 'APP_URL' has already been declared`، ما يعطّل كتلة JavaScript في التذييل (أزرار/سلوكيات التذييل) دون تعطيل سكربت الصفحة المضمّن.
- **السبب الجذري:** `app/Views/layouts/footer.php:45` و`app/Views/evaluations/create.php:441` يعرّفان الثابت نفسه عالميًا.
- **الإصلاح المقترح:** إزالة أحد التعريفين، أو استخدام `window.APP_URL = ...` مع حارس `if (typeof APP_URL === 'undefined')`.

### CD040-UAT-D07 — تحذيرات PHP في صفحة التقييم الجماعي (متوسطة)
- **السيناريو:** UAT-13 / UAT-14
- **الوصف:** عند فتح `/evaluations/bulk-create` تُسجّل 3 تحذيرات «Cannot modify header information — headers already sent» مصدرها `sidebar.php:83`، بسبب استدعاء `header()` في `bulk-create.php:4-6` بعد بدء الإخراج.
- **الإصلاح المقترح:** نقل ضبط الترويسات إلى وحدة تحكم/أعلى الصفحة قبل أي إخراج، أو إزالتها إن لم تكن لازمة.

### CD040-UAT-D08 — الإضافة السريعة تتجاهل المنصب (منخفضة/متوسطة)
- **السيناريو:** UAT-4
- **الوصف:** أنشأت الإضافة السريعة الموظف 7606 واختارته بنجاح، لكن `position_id = NULL` وحالة الملف `incomplete`. الواجهة لا ترسل المنصب أصلًا، والخادم لا يقرأه.
- **السبب الجذري:** `app/Views/evaluations/create.php:585-594` (المعامل `posId` غير مستخدم)، و`app/Controllers/BulkEvaluationController.php:138-166` (لا يوجد `position_id` في INSERT).
- **الإصلاح المقترح:** إرسال `position_id` (أو اسم المنصب مع مطابقة/إنشاء) وحفظه، وتحديث رسالة النجاح لتوضيح أن الملف غير مكتمل.

---

## 5. الأدلة التفصيلية

### 5.1 لقطات الشاشة (مجلد `samples/cd040/`)

| الملف | الوصف |
|---|---|
| `01_new_create_desktop.png` | صفحة الإنشاء الموحّدة كاملة على 1440px |
| `01_employee_search_results.png` | نتائج بحث الموظف/المقيّم |
| `01_employee_evaluator_selected.png` | الموظف والمقيّم بعد الاختيار |
| `01_template104_criteria.png` | معايير القالب 104 الديناميكية |
| `01_saved_show_page.png` | صفحة عرض التقييم بعد الحفظ (191) |
| `02_edit_prefill.png` | صفحة `/fill` مع القيم المحفوظة 85/8 |
| `02_edit_modified.png` | بعد التعديل إلى 90/9 |
| `02_edit_saved_show.png` | بعد حفظ التعديل |
| `03_cascade_filter_empty.png` | فشل الفلترة: «لا توجد نتائج ضمن الفلتر» |
| `04_quick_add_form.png` / `04_quick_add_selected.png` | نموذج الإضافة السريعة ثم الموظف المختار |
| `06_program_filter_403.png` | منقّي البرامج فارغ بسبب 403 |
| `06_program_filters_working.png` | البرامج بعد منح الصلاحية مؤقتًا |
| `07_preview_modal.png` | المعاينة الكاملة قبل الحفظ |
| `09_live_score_825.png` | الدرجة الحية ٨٢٫٥ |
| `10_fill_foreign_draft_redirect.png` | منع مستخدم غير مسند إليه من فتح `/fill` |
| `10_evaluations_index_403.png` | صفحة 403 لقائمة التقييمات |
| `11_boolean_criteria.png` | الأنواع الستة للقالب 105 |
| `11_boolean_live_score.png` | الدرجة الحية الخاطئة ٦٧٫٠ |
| `11_boolean_preview.png` / `11_boolean_saved_show.png` | المعاينة والحفظ للقالب 105 |
| `12_mobile_390.png` / `12_desktop_1440.png` | التجاوب |
| `14_evaluations_list.png` / `14_bulk_create.png` | القائمة والتقييم الجماعي |

### 5.2 مقتطف الشبكة (POST `/evaluations/save-unified` — إنشاء 191)

```
_token                     = <csrf>
evaluation_id               = 0
employee_id                 = 1
template_id                 = 104
evaluator_employee_id       = 5          ← المرسل من النموذج
results[87][117][score]     = 85
results[87][118][score]     = 8
→ HTTP 302 إلى /evaluations/show/191
```

والحفظ في قاعدة البيانات أعطى:

```
id  | evaluator_employee_id | evaluator_name        | final_score
191 | 105                   | خالد أحمد الغامدي      | 82.50 → 90.00 بعد التعديل
```

### 5.3 مقتطف قاعدة البيانات (`DB_Evidence.txt`)

```
evaluation_answers (191)
section_id | raw_value | normalized_score
117        | 85        | 85.00      ← نسبة الإنجاز
118        | 8         | 80.00      ← 8/10 بعد التطبيع

evaluation_answers (192 — boolean)
section_id | raw_value | normalized_score
119        | 100       | 100.00
120        | 20        | 100.00
121        | 100       | 100.00
122        | 1         | 0.00       ← الخطأ: «نعم» تساوي صفرًا
123        | نص        | NULL
124        | ممتاز     | NULL

employees: 7606 | CD040-QA-001 | position_id = NULL | profile_status = incomplete
```

### 5.4 السجلات (`PHP_Log_Evidence.txt` + `evidence_stage*.json`)

```js
// Console — كل صفحات الوحدة
SyntaxError: Identifier 'APP_URL' has already been declared
```

```text
[09-Nov-2026 20:24:02 Asia/Riyadh] PHP Warning: Cannot modify header information
- headers already sent by (...layouts\sidebar.php:83)
in ...app\Views\evaluations\bulk-create.php on line 4
```

### 5.5 نتائج الفلاتر والبرامج

```text
GET /api/employees/search?q=محمد            → 200
الحقول المعادة: id, emp_code, full_name, position_id   (لا branch_id/department_id)
عمود الفرع المحمّل: 5 أقسام للفرع 1                     (المتتالية تعمل)
نتيجة الفلترة: "لا توجد نتائج ضمن الفلتر"

GET /api/training-programs/list/course       → 403 (قبل المنح)
بعد منح training.view: "عرض 3 من 3 برنامج"، فلتر الفرع: "عرض 1 من 3"
فلتر القسم: القيمة بعد الاستدعاء = "" (يُصفَّر) → لا يعمل
```

### 5.6 الصلاحيات (UAT-10)

```text
GET  /evaluations/193/fill (غير مسند)   → redirect إلى /dashboard
                                          + "هذا التقييم غير مسند إلى حسابك."
POST /evaluations/save-unified (193)    → 403 {"error":"هذا التقييم غير مسند إليك"}
POST /evaluations/save-unified (192)    → 400 {"error":"لا يمكن تعديل تقييم غير مسودة"}
GET  /evaluations                        → 403 — الصلاحية المطلوبة: reports.view.all
```

---

## 6. ما يعمل بشكل صحيح (تأكيد إيجابي)

- تحميل بنية القالب ديناميكيًا من `/api/templates/{id}/schema` وعرض الأنواع الستة (percentage, numeric, grade, boolean, textarea, overall_grade) بأسماء الحقول الصحيحة `results[cid][sid][...]`.
- المعاينة تعرض الموظف والمقيّم والقالب والبرنامج والتاريخ وكل الإجابات والدرجة.
- الحساب الحي للقالب 104 دقيق: 85% + 8/10 = 82.5 («جيد جدًا»).
- الحفظ والإنشاء والتحديث يعملان، مع تطبيع صحيح للأنواع الرقمية (85→85، 8/10→80).
- حماية الصلاحيات سليمة: لا وصول لتقييم غير مسند، ولا لقائمة التقييمات دون `reports.view.all`.
- التجاوب سليم: لا تجاوز أفقي عند 390px، وتخطيط عمودين عند 1440px.
- صفحتا `/evaluations` و`/evaluations/bulk-create` تُحمّلان (15 صفًا في القائمة، و13 خيار قالب في النموذج).

---

## 7. التوصيات وخطة الإصلاح المقترحة

1. **قبل أي إطلاق:** إصلاح D01 (الفلاتر) وD02 (المقيّم) وD03/D04 (الحساب وboolean) — فهي تمسّ صحة النتيجة وهوية المقيّم.
2. إصلاح D05 بحيث لا تعتمد قراءة البرامج على `training.view` غير الممنوحة لدور المقيّم، ومعالجة تصفير فلتر القسم.
3. إزالة تكرار `APP_URL` (D06) ونقل ترويسات `bulk-create` (D07) لتحقيق «سجلات نظيفة».
4. تمرير `position_id` في الإضافة السريعة (D08).
5. إضافة اختبارات انحدار: (أ) قالب بأوزان 40/30/10/10 مع boolean = 90.0، (ب) POST مقيّم مختلف يُحفظ كما هو، (ج) فلترة فرع/قسم تعيد نتائج، (د) فحص Console خالٍ من الأخطاء.

---

## 8. حالة المستودع وقاعدة البيانات بعد التنفيذ

| العنصر | الحالة |
|---|---|
| `config/database.php` | أُعيد إلى النسخة الأصلية (`employee_evaluation_uat`) — تم التحقق من SHA-256 |
| ملفات المصدر | بدون تعديل — `git status` نظيف للملفات المتتبعة |
| قاعدة الاختبار | `employee_evaluation_cd040` منفصلة، ولم تُمسّ `employee_evaluation_uat` |
| صلاحية `training.view` المؤقتة | أُلغيت — عدد المنح = 0 |
| فرع النسخة الاحتياطية | `backup/cd040-pre-repair-20261109_201338` |
| سجلات التقييمات المُنشأة | 191 (قالب 104، مكتمل)، 192 (قالب 105، مكتمل)، 193 (مسودة لاختبار الصلاحيات) |

---

## 9. الملفات المرجعية

| الملف | المحتوى |
|---|---|
| `samples/cd040/evidence_stageA.json` | الإنشاء، البحث، الفلاتر، الإضافة السريعة، المعاينة، الحساب الحي، القائمة، التجاوب، أخطاء Console |
| `samples/cd040/evidence_stageB.json` | إعادة الفتح، التعبئة المسبقة، تعديل وحفظ 191 |
| `samples/cd040/evidence_stageC.json` | القالب 105 والأنواع الديناميكية وboolean |
| `samples/cd040/evidence_stageD.json` / `D2.json` | اختبار الصلاحيات وحساب محدود الصلاحيات |
| `samples/cd040/evidence_stageE.json` | منقّي البرامج قبل/بعد منح `training.view` والفلاتر |
| `samples/cd040/DB_Evidence.txt` | مخرجات SQL للتقييمات والإجابات والموظف والصلاحيات |
| `samples/cd040/Network_Evidence.txt` | ملخص طلبات/استجابات API والحفظ |
| `samples/cd040/PHP_Log_Evidence.txt` | تحذيرات PHP المسجّلة أثناء الاختبار |

---

**الخلاصة:** الصفحة الموحّدة تنفّذ معظم الوظائف المطلوبة، لكنها **لا تجتاز UAT** حتى تُعالج العيوب D01–D05. العيوب موثّقة بأدلة قابلة لإعادة الإنتاج، ومسارات الإصلاح محددة أعلاه.
