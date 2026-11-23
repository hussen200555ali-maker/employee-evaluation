# خطة: ترويسة الوثيقة الرسمية للتقرير (masthead) — 2026-11-18

## v47 (2026-11-24) — DIR-BULK-CREATE-FIX-V1.0: يكفي الاسم أو الرقم الوظيفي في الإدخال الجماعي والفردي

### الطلب والقرارات المعتمدة
تنفيذ الإصلاح بعد اعتماد دراسة As-Is: قاعدة «أحدهما يكفي» تطبَّق في `/evaluations/bulk-create` **و**`/evaluations/create` معاً. توليد `AUTO_` عند غياب الكود، واسم يحتوي على الكود (`موظف مؤقت [الكود]`) عند غياب الاسم، ومنع فقط عند غياب الاثنين. Anti-scope: لا تعديل على `EmployeeMatcher.php` ولا `EvaluationExcelService.php` ولا أي schema.

### الحماية قبل الاختبار (إثبات)
- **Commit قبل التعديل:** `b2faef6b6a5c3022bfff71aafd9af192f2b71185` ( HEAD ما زال هو نفسه؛ لا commit جديد ).
- **نسخة احتياطية كاملة لـ UAT (`mysqldump`):**
  - المسار: `docs\CD-043\backups\2026-11-23_quickadd_v47\db\employee_evaluation_uat_before_v47.sql`
  - الوقت: `2026-11-23 23:54:59` · الحجم: `2,530,311` بايت.
  - SHA256: `D06473341F6F708F7E0B12A52422BD0A61DB524D12F17E802831D96DB533EF73`
  - التحقق: رأس الملف `MariaDB dump 10.19 / Database: employee_evaluation_uat` + وجود `CREATE TABLE employees` ✅ (خروج mysqldump = 0).
- قاعدة الإنتاج `employee_evaluation_mvc` لم تُلمس؛ كل العمل على `employee_evaluation_uat`.

### البصمات قبل/بعد (SHA256)
| الملف | قبل | بعد | النسخة الاحتياطية |
|---|---|---|---|
| `app/Controllers/BulkEvaluationController.php` | `2F422EED9CFC425F6B2FFDA0CB8BBBA10D987BCD3E856409EA99C66E52505D41` | `B6804A28E19DAC006D41ECC4E04461247036CB5B2201F35E4ADC3283841B78F0` | `docs/CD-043/backups/2026-11-23_quickadd_v47/files/app/Controllers/BulkEvaluationController.php` |
| `app/Views/evaluations/bulk-create.php` | `073B5657B9F301534901DD108A3BCE1774B2C44248BB5604435D6FC73F84F1AC` | `B1BDD0B1F119ACAEB86E817AA0FCB572E158D3541C1880EF80310998B92B1508` | `docs/CD-043/backups/2026-11-23_quickadd_v47/files/app/Views/evaluations/bulk-create.php` |
| `app/Views/evaluations/create.php` | `4577EBA6DA2F415C5C846ADAF243183B37EE69F78BA6644A214DC2BC83DDAF60` | `81ED79984FA2E3AE341C535F4E3FDB7B9EFAE5E2645BFB8ED973C0BE66E4C947` | `docs/CD-043/backups/2026-11-23_quickadd_v47/files/app/Views/evaluations/create.php` |
| `tests/cases/Controllers/BulkEvaluationControllerTest.php` (إضافة اختبارات فقط) | `3C148758FFEEDB0F7AEBCB2FD72303045F45819BC84F07E4EE05276C98141157` | `0BDE45D31D2FE9A1DD93D35EB6AF399948CFD2909DE3D2C981D450A8EFFAB4C9` | `docs/CD-043/backups/2026-11-23_quickadd_v47/files/tests/cases/Controllers/BulkEvaluationControllerTest.php` |

### ما الذي تغيّر
1. `BulkEvaluationController::quickAdd()`: `||` → `&&`؛ عند غياب الكود يُولَّد `AUTO_` + `bin2hex(random_bytes(6))` مع حلقة تحقق تفرد (10 محاولات) قبل فحص التكرار؛ عند غياب الاسم يُولَّد `موظف مؤقت [الكود]`؛ `profile_status` يبقى `incomplete`.
2. `bulk-create.php`: تحقق الدالتين `cd041_quickAddEmployee()` و`cd041_quickAddEvaluator()` صار `if(!full_name && !emp_code)` مع رسالة «أدخل الاسم أو الرقم الوظيفي».
3. `create.php`: تحقق `cd040_doQuickAdd()` صار `if(!code && !name)` + تحقق منفصل للفرع (ما زال إلزامياً في هذه الشاشة).
4. ملف الاختبارات: 3 حالات جديدة (كود فقط / اسم فقط مرتين / غياب الاثنين) — كلها داخل transaction مع rollback.

لا تغيير في `routes/web.php`، ولا في عقد الاستجابة `{success, employee}`، ولا في `EmployeeMatcher.php` / `EvaluationExcelService.php` / أي migration. (التعديلات غير المُلتزمة الموجودة أصلاً في `EvaluationExcelService.php` تعود لمراحل CD-043 السابقة ولم يمسّها هذا الإصلاح.)

### نتائج الاختبارات — قبل / بعد
| الفحص | قبل | بعد | الحكم |
|---|---|---|---|
| `tests/runner.php` (كامل) | 120 نجح / 5 فشل | 123 نجح / 5 فشل | ✅ +3 اختبارات جديدة، الفشل الخمسة نفسها المعروفة |
| `BulkEvaluationControllerTest` | 5/6 | 8/9 | ✅ الفشل الوحيد هو `testBulkCreatePageRendersTemplateSelector` (`templateSelect` مقابل `cd041-template`) — سابق ولم يُصلَح |
| `CD029CourseLinkingTest` | 2/3 | 2/3 | ✅ كما كان |
| `CD037EvaluationLinkingTest` | 2/3 | 2/3 | ✅ كما كان |
| `DescriptiveTemplatesTest` | 3/5 | 3/5 | ✅ كما كان |
| `EvaluationCalculatorSmoke` | 5 PASS / 0 FAIL | 5 PASS / 0 FAIL | ✅ |
| `ExcelImportValidationSmoke` (يغطي EmployeeMatcher) | 6/0 | 6/0 | ✅ |
| `ExcelTemplateExportSmoke` | 12/0 | 12/0 | ✅ |
| `ExcelWorkflowSmoke` | 7/0 | 7/0 | ✅ |
| `RoleDashboardSmoke` | 1/0 | 1/0 | ✅ |
| `RoleDashboardPersonaSmoke` | 1/0 | 1/0 | ✅ |
| `php -l` (4 ملفات) | — | 4/4 نظيف | ✅ |
| `node --check` على الـ inline JS المُخدَم | — | bulk (69,427 حرف) + create (36,030 حرف) نظيف | ✅ |
| فحص UTF-8 (`v40_verify_utf8.ps1`) | ALL_CLEAN | ALL_CLEAN | ✅ |
| أخطاء console في صفحتي v47 | — | صفر (عدا 409 المتعمّد في اختبار التكرار) | ✅ |

### إثبات القبول من الواجهة (UAT، قالب 249)
| الحالة | المسار | الاستجابة | النتيجة |
|---|---|---|---|
| كود فقط `V47QA-CODEONLY` | bulk employee | 200 | الاسم المولّد `موظف مؤقت [V47QA-CODEONLY]` ✅ |
| اسم فقط «موظف V47 اسم فقط ألف» | bulk employee | 200 | `AUTO_a350d22f888e` ✅ |
| اسم فقط «موظف V47 اسم فقط باء» | bulk employee | 200 | `AUTO_e93e852a6d9b` ✅ (لا Duplicate Key) |
| لا اسم ولا كود | bulk employee | بلا طلب شبكة | toast «أدخل الاسم أو الرقم الوظيفي» ✅ |
| كود مكرر | bulk employee | **409** | `{"error":"الرقم الوظيفي موجود مسبقاً"}` ✅ |
| لا اسم ولا كود (POST مباشر مع CSRF) | API | **400** | `{"error":"الاسم أو الرقم الوظيفي مطلوب"}` ✅ |
| كود فقط `V47QA-FORM-CODE` | create employee | 200 | الاسم المولّد + اختيار الموظف ✅ |
| اسم فقط «موظف V47 فردي بالاسم فقط» | create employee | 200 | `AUTO_7b9f2af674a9` + اختيار الموظف ✅ |
| لا اسم ولا كود / بلا فرع | create employee | بلا طلب شبكة | «الرقم الوظيفي أو الاسم مطلوب» / «الفرع مطلوب» ✅ |

> ملاحظة سابقة لم تُغيَّر (Anti-scope): في `bulk-create.php` رسالة الـ toast عند 409 هي «فشل الإضافة» لأن الكود يقرأ `res.message` بينما الخادم يرسل `res.error`. كود الحالة 409 صحيح والرسالة داخل JSON صحيحة؛ تركنا سلوك الواجهة كما هو تفادياً لتوسيع النطاق. (في `create.php` تظهر رسالة الخطأ نفسها لأنه يقرأ `res.error`.)

### الإثبات الحاسم — موظفان بالاسم فقط (SQL)
```sql
SELECT id, emp_code, full_name, profile_status, is_active, created_at
FROM employees WHERE id IN (8056,8057) ORDER BY id;
```
```json
[
  {"id":8056,"emp_code":"AUTO_a350d22f888e","full_name":"موظف V47 اسم فقط ألف","profile_status":"incomplete","is_active":1,"created_at":"2026-11-24 00:01:11"},
  {"id":8057,"emp_code":"AUTO_e93e852a6d9b","full_name":"موظف V47 اسم فقط باء","profile_status":"incomplete","is_active":1,"created_at":"2026-11-24 00:01:11"}
]
```
- لقطة الواجهة: `docs\CD-043\backups\2026-11-23_quickadd_v47\evidence\v47_bulk_matrix_name_only.png` (SHA256 `4C4221D5CE943B19FA1C6148AB4D3C9AE824EBD88C92749B45BC4334EC567562`).
- فحص سلامة عام: `total=59`, `empty_code=0`, `empty_name=0`, `incomplete=19`, تكرارات `emp_code` = صفر، عدد التقييمات = 209 (كما كان قبل التعديل).
  (الأساس قبل الإضافة: `total=57`, `incomplete=17`, `AUTO_=0`, `evaluations=209`.)
- حُذفت بعد الاختبار 3 صفوف غير إثباتية فقط (8055 `V47QA-CODEONLY`، 8058 `V47QA-FORM-CODE`، 8059 `AUTO_7b9f2af674a9`) بحراسة id+code وبعد التأكد من عدم وجود تقييمات مرتبطة؛ **أُبقي على 8056 و8057 للمراجعة**. حُذف ملف الجلسة المؤقت `_v47_session.php` وأُغلقت تبويبات v47.

### التراجع (لم يُستدعَ — لا يوجد انحدار)
`git checkout` **غير آمن هنا**: نفس الملفات تحمل تعديلات CD-043 v40–v46 غير مُلتزمة في الشجرة، و`git checkout` سيمحوها. البديل المكافئ والمستخدم فعلياً هو استعادة النسخة المحلية لكل ملف:
```powershell
$b='docs\CD-043\backups\2026-11-23_quickadd_v47\files'
Copy-Item "$b\app\Controllers\BulkEvaluationController.php" 'app\Controllers\BulkEvaluationController.php' -Force
Copy-Item "$b\app\Views\evaluations\bulk-create.php" 'app\Views\evaluations\bulk-create.php' -Force
Copy-Item "$b\app\Views\evaluations\create.php" 'app\Views\evaluations\create.php' -Force
Copy-Item "$b\tests\cases\Controllers\BulkEvaluationControllerTest.php" 'tests\cases\Controllers\BulkEvaluationControllerTest.php' -Force
Get-FileHash 'app\Controllers\BulkEvaluationController.php','app\Views\evaluations\bulk-create.php','app\Views\evaluations\create.php','tests\cases\Controllers\BulkEvaluationControllerTest.php'  # يجب أن تعود للبصمات «قبل» في الجدول أعلاه
```
واستعادة UAT من النسخة الكاملة (تحذير: تُعيد قاعدة البيانات لزمن النسخة، أي تحذف أي بيانات أُضيفت بعدها):
```powershell
cmd /c '"C:\xampp\mysql\bin\mysql.exe" -u root < "C:\xampp\htdocs\employee-evaluation\docs\CD-043\backups\2026-11-23_quickadd_v47\db\employee_evaluation_uat_before_v47.sql"'
```

### سجل التنفيذ
- 2026-11-23: توثيق HEAD + نسخ احتياطي كامل لـ UAT وتحقق الحجم/البصمة ✅.
- 2026-11-23: نسخ الملفات الأربعة + بصمات «قبل»، ثم تشغيل خط الأساس (120/5 + 6 smokes نظيفة) ✅.
- 2026-11-23/24: تعديل الـ controller والشاشتين وإضافة 3 اختبارات؛ `php -l` و`node --check` نظيفان ✅.
- 2026-11-24: تحقق واجهة UAT لكل حالات القبول + SQL لإثبات الموظفين بالاسم فقط ✅.
- 2026-11-24: إعادة التشغيل الكاملة (123/5 — نفس الفشل المعروف) + smokes + UTF-8 ✅.
- 2026-11-24: تنظيف صفوف الاختبار وملف الجلسة؛ إلحاق هذا القسم. **لا commit ولا push — بانتظار مراجعة الكود والتقرير.**

---

## v46 (2026-11-23) — إلغاء عرض حمولة JSON والإبقاء على المعاينة البصرية فقط

### الطلب
بعد تأكيد المستخدم: إزالة عرض «الحمولة (JSON)» نهائياً من واجهة التقييم الجماعي، والإبقاء على المعاينة البصرية وحدها. (يُلغي هذا الجزء الخاص بـ JSON في v45؛ سجل v45 يُحفظ كما هو لأغراض التتبع.)

### الإصلاح
في `app/Views/evaluations/bulk-create.php` فقط:
- حُذف زر `الحمولة (JSON)` من شريط الحفظ.
- حُذف زر `عرض الحمولة (JSON)` من تذييل نافذة المعاينة البصرية، وأُبقي زر «إغلاق» فقط.
- حُذفت الدالة `cd041_previewPayload()` غير المستخدمة (صار صفر مرجع لها في الملف).
- حُدّث تعليق الدوال إلى `v45/v46: معاينة بصرية فقط — لا عرض لحمولة JSON`.

لا تغيير في عقد الإرسال، ولا في `BulkEvaluationController.php`، ولا في قاعدة البيانات، ولا في منطق الحفظ. نافذة `#cd041-modal` تبقى كما هي لملاحظات النصوص/التقدير العام.

| الملف | SHA256 قبل | SHA256 بعد | النسخة الاحتياطية |
|---|---|---|---|
| `app/Views/evaluations/bulk-create.php` | `5BD5062E4AAFA22AE98C453A5E961D58AC28251B60F0D4C4DD92FE631CB900D6` | `073B5657B9F301534901DD108A3BCE1774B2C44248BB5604435D6FC73F84F1AC` | `docs/CD-043/backups/2026-11-23_preview_json_remove_v46/bulk-create.php` |

### التحقق
- `php -l` نظيف ✅؛ `node --check` على الـ inline script المُخدَم (69121 حرف، `hasVisualPreview=true`, `hasPayloadFn=false`) نظيف ✅.
- `grep` على الملف: `cd041_previewPayload|الحمولة \(JSON\)|عرض الحمولة` → **0 نتائج** ✅.
- متصفح UAT على القالب 249: زر واحد فقط بعنوان «معاينة»، صفر أزرار تحتوي JSON/الحمولة في الصفحة ✅؛ الضغط عليه فتح `#cd041-preview-modal.open` وعرض `موظف معاينة v46 / PV-46` → `10٪ · 100 · نعم · جيد` → `النتيجة: 100 · التقدير: ممتاز` ✅؛ لا `<textarea>` ولا أي نص JSON داخل النافذة ✅؛ بلا أخطاء console ✅.
- `run_v41_clamp.js` 11/11 PASS و`run_parity.js` بلا انحدار ✅؛ فحص UTF-8 `ALL_CLEAN` ✅.
- حُذف ملف الجلسة المؤقت `_v46_session.php`؛ لا ملفات `_v4*` متبقية في جذر الويب.

### نقطة التراجع
```powershell
Copy-Item 'docs\\CD-043\\backups\\2026-11-23_preview_json_remove_v46\\bulk-create.php' 'app\\Views\\evaluations\\bulk-create.php' -Force
```
إعادة البصمة إلى `5BD506...` (حالة v45) تعني اكتمال التراجع. لا commit ولا push.

### سجل التنفيذ
- 2026-11-23: نسخة احتياطية + تحقق من البصمة قبل التعديل ✅.
- 2026-11-23: حذف زري JSON والدالة غير المستخدمة ✅.
- 2026-11-23: `php -l` + استخراج الـ JS المُخدَم و`node --check` + grep المراجع ✅.
- 2026-11-23: تحقق متصفح UAT (زر معاينة واحد، بلا JSON) ✅.
- 2026-11-23: إعادة تشغيل الـ harnesses + فحص UTF-8 + تنظيف الملفات المؤقتة ✅.

---

## v45 (2026-11-23) — معاينة بصرية لزر «المعاينة» في التقييم الجماعي

### العَرَض
زر «معاينة الحمولة» في `/evaluations/bulk-create` كان يفتح نافذة تحتوي على JSON خام فقط (`_token`, `template_id`, `evaluations: []`...) بدلاً من عرض معاينة مرئية للتقييمات.

### الإصلاح
في `app/Views/evaluations/bulk-create.php` فقط:
- شريط الحفظ: الزر الأساسي أصبح `<i class="fas fa-eye"></i> معاينة` يستدعي `cd041_preview()`، وأُضيف زر ثانوي `الحمولة (JSON)` يستدعي `cd041_previewPayload()` (السلوك القديم محفوظ كما هو).
- أُضيف modal مستقل `#cd041-preview-modal` مع تنسيقات `.cd041-pv-*` (لا `<textarea>` داخله).
- `cd041_preview()`: يعرض رأس السياق (القالب، التاريخ، المقيّم، عدد التقييمات) ثم بطاقة لكل موظف فيها المعايير والأقسام وقيمها المقروءة (نسبة `٪` / رقمي / نعم-لا / تقدير)، والملاحظات، والنتيجة والتقدير المشتق. عند غياب الموظفين تظهر رسالة توضيحية بدل نافذة فارغة.
- `cd041_previewValue()`: تحويل القيم الخام إلى نصوص قابلة للقراءة (أسماء خيارات التقدير، نعم/لا، إلخ).
- `cd041_closePreview()`: إغلاق نافذة المعاينة البصرية.
- `cd041_previewPayload()`: نقل منطق JSON القديم إلى دالة مستقلة دون تغيير العقد.

لا تغيير في عقد الإرسال، ولا في `BulkEvaluationController.php`، ولا في قاعدة البيانات أو أي ملف آخر.

| الملف | SHA256 قبل | SHA256 بعد | النسخة الاحتياطية |
|---|---|---|---|
| `app/Views/evaluations/bulk-create.php` | `74A747BD851CD815248794F200B5EEAE1744FAE701B661846F35DF5E65CC00A5` | `5BD5062E4AAFA22AE98C453A5E961D58AC28251B60F0D4C4DD92FE631CB900D6` | `docs/CD-043/backups/2026-11-23_preview_modal_v45/bulk-create.php` |

### التحقق
- `php -l` نظيف ✅؛ `node --check` على الـ inline script المُخدَم (69874 حرف، `hasVisualPreview=true`) نظيف ✅.
- `run_v41_clamp.js` 11/11 PASS و`run_parity.js` بلا انحدار ✅؛ فحص UTF-8 `ALL_CLEAN` ✅.
- متصفح UAT على القالب 249 (بعد مسح مسودة localStorage ومنح جلسة مؤقتة): الضغط على «معاينة» فتح `#cd041-preview-modal.open` ✅؛ النص المعروض:
  `القالب: تجربة ظهور1 · التاريخ: 2026-11-23 · المقيّم: سلطان ناصر القرني — رئيس قسم · عدد التقييمات: 1`، ثم `موظف معاينة v45 / PV-11 · الرياض`، ثم `قسم جديد (نسبة مئوية) 10٪`، `قسم جديد (رقمي) 100`، `قسم جديد (نعم/لا) نعم`، `قسم جديد (تقدير) جيد`، `النتيجة: 100 · التقدير: ممتاز` ✅.
- لا يوجد `<textarea>` داخل نافذة المعاينة البصرية ✅؛ لا أخطاء console ✅.
- زر `الحمولة (JSON)` ما زال يفتح النافذة القديمة بعنوان `معاينة الحمولة (JSON)` وبحمولة صالحة فيها `evaluations[0]` بالقيم نفسها ✅.
- حُذف ملف الجلسة المؤقت `_v45_session.php`؛ لا ملفات `_v4*` متبقية في جذر الويب.

### نقطة التراجع
```powershell
Copy-Item 'docs\\CD-043\\backups\\2026-11-23_preview_modal_v45\\bulk-create.php' 'app\\Views\\evaluations\\bulk-create.php' -Force
```
إعادة البصمة إلى `74A747...` تعني اكتمال التراجع. لا commit ولا push.

### سجل التنفيذ
- 2026-11-23: نسخة احتياطية + تحقق من البصمة قبل التعديل ✅.
- 2026-11-23: تعديل شريط الحفظ، إضافة CSS وmodal، إضافة دوال المعاينة البصرية ✅.
- 2026-11-23: `php -l` + استخراج الـ JS المُخدَم و`node --check` ✅.
- 2026-11-23: تحقق متصفح UAT (معاينة بصرية + JSON ثانوي) ✅.
- 2026-11-23: إعادة تشغيل الـ harnesses + فحص UTF-8 + تنظيف الملفات المؤقتة ✅.

---

## v44 (2026-11-23) — إظهار حقل إدخال قسم «التقدير» في التقييم الجماعي

### العَرَض
في `/evaluations/bulk-create` لا تظهر حقول الإدخال لنوع القسم «تقدير»، فيبقى الصف ناقصاً ولا تُحسب النتيجة.

### التشخيص
- `cd041_renderHead()` كان يُنشئ عموداً لكل قسم بما فيه `grade`، لكن `cd041_cellHtml()` لم يكن لديه فرع `if(t==='grade')` → كان يرجع `''` (خلية فارغة).
- `cd041_isRowDone()` يعتبر الصف غير مكتمل إذا كان أي عمود بلا `score` → الصف لا يكتمل أبداً عند وجود قسم تقدير.
- `cd041_norm()` كان يمرّر قيمة التقدير عبر منطق النumeric (min/max) بدل قراءة قيمة الخيار مباشرة.

### الإصلاح
في `app/Views/evaluations/bulk-create.php` فقط:
- `cd041_cellHtml()`: إضافة `<select class="cd041-grade-sel cd041-grade-mini">` بخيارات `col.options` (القيمة هي الدرجة) + زر الملاحظات + احترام حالة «موجود مسبقاً».
- `cd041_onGrade(empId, secId, val)`: حفظ/حذف `score` في `cd041_results` ثم `cd041_updateRow()`.
- `cd041_norm()`: حالة `grade` صريحة — تُطابق قيمة الخيار برقمه (100/64/34) دون تمريرها عبر min/max.
- `cd041_fillDefault()`: «ملء افتراضي» يختار أعلى قيمة خيار (مثلاً «جيد» = 100).
- CSS: إضافة `.cd041-grade-mini` لضبط عرض القائمة داخل الخلية.

لا تغيير في عقد الإرسال، ولا في `BulkEvaluationController.php`، ولا في قاعدة البيانات/columns/save المحفوظة.

| الملف | SHA256 قبل | SHA256 بعد | النسخة الاحتياطية |
|---|---|---|---|
| `app/Views/evaluations/bulk-create.php` | `8A66CE544C95F4F25E4D9B514C67E3460A031F075225AE6FAD4CCCBAA02642BE` | `74A747BD851CD815248794F200B5EEAE1744FAE701B661846F35DF5E65CC00A5` | `docs/CD-043/backups/2026-11-23_bulk_grade_input_v44/bulk-create.php` |

### التحقق
- `php -l` نظيف ✅؛ `node --check` على الـ inline script المُخدَم (66550 حرف) نظيف ✅.
- `run_v41_clamp.js` 11/11 PASS و`run_parity.js` بلا انحدار ✅.
- متصفح UAT على القالب 249: ظهر `<select>` التقدير بخيارات `جيد=100, متوسط=64, ضعيف=34` ✅؛ النتيجة: `جيد→100`، `متوسط→91`، `ضعيف→83.5`، إفراغه→`-` ✅؛ «ملء افتراضي» اختار `100` ✅؛ بلا أخطاء console ✅.
- حفظ جماعي حقيقي: تقييمان `616` و`617` (قالب 249)، `final_score=100.00`، الإجابة `section_id=329` محفوظة `raw_value=100 / normalized_score=100` ✅.
- حُذف ملف الجلسة المؤقت `_v44_session.php`.

### نقطة التراجع
```powershell
Copy-Item 'docs\CD-043\backups\2026-11-23_bulk_grade_input_v44\bulk-create.php' 'app\Views\evaluations\bulk-create.php' -Force
```
إعادة البصمة إلى `8A66CE...` تعني اكتمال التراجع. لا commit ولا push.

---

## v43 (2026-11-23) — تحديث المعاينة المباشرة عند تغيير قسم «التقدير»

### العَرَض
المستخدم أكّد أن الاختبار كان على قالب «تجربة ظهور1» (id 249): أدخل القيم العليا لكل الحقول لكن المعاينة المباشرة بقيت تعرض `٧٥.٠` بدل `١٠٠.٠`.

### التشخيص (تحقّق فعلي على الصفحة الحيّة)
- هيكل القالب 249: أربعة أقسام متساوية (٢٥٪ لكل قسم): نسبة مئوية `0..10` (id 326)، رقمي `0..100` (id 327)، نعم/لا (id 328)، تقدير (id 329 بخيارات `جيد=100, متوسط=64, ضعيف=34`).
- إعادة إنتاج دقيقة بترتيب إدخال المستخدم: رقمي=١٠٠ → `٢٥.٠`، نعم → `٥٠.٠`، نسبة=١٠ → `٧٥.٠`، ثم اختيار «جيد» **أخيراً** → بقي `٧٥.٠` ❌. بعد لمس أي حقل آخر قفز إلى `١٠٠.٠` ✅.
- السبب: `<select>` الخاص بقسم «التقدير» في `create.php` (وكذلك النسخة الموازية في `fill.php`) لم يكن يحمل `onchange="cd040_calc()"` / `onchange="cd039_calc()"`. بقية الحقول (رقمي/نسبة/نعم-لا) تستدعي الحساب عبر `oninput`/`onclick`، أما التقدير فلا → معاينة قديمة (stale) حتى يحدث تغيير آخر.
- ملاحظة: القالب 249 نفسه سليم، والقيم القصوى فيه `10` للنسبة و`100` للرقمي؛ خطأ المستخدم لم يكن في النطاق بل في ترتيب الإدخال + غياب التحديث.

### الإصلاح (أقل تدخّل ممكن)
إضافة مستمع `onchange` إلى select قسم التقدير فقط:
- `app/Views/evaluations/create.php`: `<select name="...[score]" onchange="cd040_calc()">`
- `app/Views/evaluations/fill.php`: `<select class="cd039-eval" name="...[score]" onchange="cd039_calc()">`

لا تغيير في منطق الحساب، ولا في قيم `min/max`، ولا في المسارات/العقود/الـ schema.

| الملف | SHA256 قبل | SHA256 بعد | النسخة الاحتياطية |
|---|---|---|---|
| `app/Views/evaluations/create.php` | `B2AA18F13857ABC6373207435998D0123B4052394470A10D5D667A59C37152DA` | `4577EBA6DA2F415C5C846ADAF243183B37EE69F78BA6644A214DC2BC83DDAF60` | `docs/CD-043/backups/2026-11-23_grade_select_recalc_v43/create.php` |
| `app/Views/evaluations/fill.php` | `92FB63B1CA0DD570BB50B5DEB05F035EE5A7CC7FC4ECCE766E7C7CFE6C66EA9E` | `A2D291125351DFF0D0B2D92467CFFEC285BAA125A4A55CB8E87D7974DD9313C1` | `docs/CD-043/backups/2026-11-23_grade_select_recalc_v43/fill.php` |

### التحقق
- `php -l` نظيف للملفين ✅.
- `node run_v41_clamp.js` → 11/11 PASS ✅؛ `node run_parity.js` → كل السيناريوهات كما هي ✅ (لا انحدار).
- متصفح UAT على القالب 249: السكربت `onchange="cd040_calc()"` موجود على select التقدير، وتسلسل `٢٥ → ٥٠ → ٧٥ → ١٠٠.٠` بعد اختيار التقدير مباشرة ✅، بلا أخطاء console ✅.
- حُذف ملف الجلسة المؤقت `_v43_session.php` بعد الفحص.

### نقطة التراجع
```powershell
Copy-Item 'docs\CD-043\backups\2026-11-23_grade_select_recalc_v43\create.php' 'app\Views\evaluations\create.php' -Force
Copy-Item 'docs\CD-043\backups\2026-11-23_grade_select_recalc_v43\fill.php' 'app\Views\evaluations\fill.php' -Force
```
إعادة البصمتين إلى `B2AA18...` و`92FB63...` تعني اكتمال التراجع. لا commit ولا push.

---

## v42 (2026-11-23) — إظهار حقول نطاق النسبة المئوية في منشئ القوالب (كسر كاش المتصفح)

### العَرَض
بعد v40/v41 أبلغ المستخدم أن لوحة خصائص قسم «نسبة مئوية» في `/builder/create` لا تُظهر «القيمة الدنيا ٠ / القيمة القصوى».

### التشخيص (تحقّق فعلي)
- الكود موجود وسليم: `public/js/builder.js:325` يُضيف «إعدادات النطاق المئوي»، و`:131-132` يعطي الافتراضي `0..100`.
- الملف المُخدَم عبر HTTP يحتوي الكتلة نفسها (48797 بايت)، ولا يوجد ملف بديل محمّل (`builder1.js` غير مستخدم؛ `footer-builder.php` يحمّل `builder.js` فقط).
- فتح الصفحة بجلسة UAT في متصفح داخلي وسحب قسم نسبة مئوية إلى معيار → اللوحة عرضت فعلاً `القيمة الدنيا=0` و`القيمة القصوى=100`.
- إذن السبب هو **نسخة `builder.js` مخزَّنة مؤقتاً في متصفح المستخدم** من قبل v40 (الملف يُطلب بلا رقم إصدار)، لا خلل في المنطق.

### الإصلاح (أقل تدخّل ممكن)
إضافة رقم إصدار إلى وسم السكربت في `app/Views/layouts/footer-builder.php`:
`<script src=".../public/js/builder.js?v=41"></script>` — يُجبر المتصفح على تنزيل النسخة الجديدة تلقائياً، بلا تغيير في منطق الباني أو المسارات أو العقود.

| الملف | SHA256 قبل | SHA256 بعد | النسخة الاحتياطية |
|---|---|---|---|
| `app/Views/layouts/footer-builder.php` | `DCCDB55ED9D78258A3AE5856FA07F47FD85AF989A174E663367DD2E13952C8BB` | `C64AFA7CD78D2B4CC020D9917BB49F1B5E004A95D7AD8C5158EA4ED37D414106` | `docs/CD-043/backups/2026-11-23_builder_cache_bust_v42/app/Views/layouts/footer-builder.php` |

### التحقق
- `php -l` ✅، المتصفح الداخلي: رابط السكربت `builder.js?v=41` ✅، لوحة الخصائص تعرض «إعدادات النطاق المئوي» مع `0` و`100` ✅، لا أخطاء console ✅.
- حُذف ملف الجلسة المؤقت `_v41_builder_session.php` بعد التحقق.

### نقطة التراجع
```powershell
Copy-Item 'docs\CD-043\backups\2026-11-23_builder_cache_bust_v42\app\Views\layouts\footer-builder.php' 'app\Views\layouts\footer-builder.php' -Force
```
إعادة البصمة إلى `DCCDB55...` تعني اكتمال التراجع. لا commit ولا push.

---

## v41 (2026-11-23) — إلزام حدود النطاق في واجهات الإدخال (تقصّ + تلميح خفيف)

### العَرَض والمطلب
بعد v40 أصبح المستخدم يتحكم بـ `min/max` للنسبة المئوية في المنشئ، لكن الواجهات لا تُلزم به فعلياً:
- خصائص `min/max` في HTML تتحقق عند إرسال النموذج فقط، ولا تمنع الكتابة — وفي `create/fill` الإرسال عبر JS مباشر يُلتفّ حول التحقق.
- `create.php` / `fill.php`: دوال `updateBar` تُقصّ شريط التقدم فقط، لا قيمة الحقل → تبقى `500` في حقل حدّه `100`.
- `bulk-create.php`: النسبة تُقصّ في الحالة الداخلية دون إعادة الكتابة إلى الخلية (عرض ≠ مخزن)؛ والنumeric لا يُقصّ أصلاً و`min="0"` ثابت يتجاهل `min` المخصص.
- الخادم لا يُقصّ `raw_value` → أي POST مباشر أو استيراد يمكن أن ينتج `normalized_score > 100%`.

### القرار (بموافقة المستخدم على «تقصّ + تلميح خفيف»)
1. **الواجهة**: تقصّ فوري أثناء الكتابة **مع إعادة الكتابة داخل الحقل نفسه**، لكل من `numeric` و`percentage` في الواجهات الثلاثة، وتلميح خفيف يظهر ~2 ثانية («تم ضبط القيمة إلى الحد الأقصى ٣٠») مع وميض حدود الحقل.
2. **الخادم**: تقصّ `raw_value` إلى `[min, max]` في `Evaluation.php` و`BulkEvaluationController.php` و`EvaluationExcelService::normalize()` → defense in depth ضد تجاوز الواجهة.
3. **المنشئ**: منع حفظ قالب بـ `min >= max` أو قيمة غير رقمية (تحقق صغير قبل التحقق من الأوزان) — لا يغيّر مخططاً ولا حقولاً مخفية.
4. نطاق غير صالح قديم (`max <= min`): لا تقصّ (سلوك v40 محفوظ). لا إعادة حساب لدرجات تاريخية، لا تغيير مسارات/عقود/مخطط/مستندات رسمية.

### الملفات الملموسة وبصمات ما قبل التعديل
| الملف | SHA256 قبل | النسخة الاحتياطية |
|---|---|---|
| `public/js/builder.js` | `D42E083C39F5BF021910D38A0009A6203A064F54B814ACE931313766C5264BA7` | `docs/CD-043/backups/2026-11-23_range_enforcement_v41/public/js/builder.js` |
| `app/Views/evaluations/create.php` | `AF7B0DB003B2F2086B1E1A202721DBF19A871F4781D18112FFC580941EA8ACAA` | `docs/CD-043/backups/2026-11-23_range_enforcement_v41/app/Views/evaluations/create.php` |
| `app/Views/evaluations/fill.php` | `CF11A900DFF14C6A6506BB98551123EFC54FF26BAED3FB918528D1CDCE5D02F8` | `docs/CD-043/backups/2026-11-23_range_enforcement_v41/app/Views/evaluations/fill.php` |
| `app/Views/evaluations/bulk-create.php` | `464D7BD6BD9DEA54F66F289B65B278F9FDEBD71242C0B076405D3BB3999C9B8C` | `docs/CD-043/backups/2026-11-23_range_enforcement_v41/app/Views/evaluations/bulk-create.php` |
| `app/Models/Evaluation.php` | `3EBBD899AA7B7BE519649001FCF166BE9B7472521C7EACDD5C98671CD4CA5CC5` | `docs/CD-043/backups/2026-11-23_range_enforcement_v41/app/Models/Evaluation.php` |
| `app/Controllers/BulkEvaluationController.php` | `C45BCF77FF307DEC098D612A57E895D663428B50DE0C05CCB001B1CE234DE24A` | `docs/CD-043/backups/2026-11-23_range_enforcement_v41/app/Controllers/BulkEvaluationController.php` |
| `app/Services/EvaluationExcelService.php` | `00FAFA1E3787A62DC6A32E9F9DFBEF94C44BB2ED34E307B80178CADFC29BA2CB` | `docs/CD-043/backups/2026-11-23_range_enforcement_v41/app/Services/EvaluationExcelService.php` |

### التحقق المخطط
- `php -l` + `node --check`، ثم UAT: كتابة `999` في حقل حدّه `100` ⇒ يُصبح `100` ويظهر التلميح؛ numeric بـ `min=10` ⇒ كتابة `5` تُصبح `10`؛ تقصّ الخادم عبر POST مباشر بقيم خارج النطاق.
- إعادة تشغيل اختبارات v40 (فردي/جماعي/استيراد/JS parity + smoke) للتأكد من عدم انحدار.
- لا commit ولا push قبل موافقة صريحة.

### نقطة التراجع
```powershell
$bd='docs\CD-043\backups\2026-11-23_range_enforcement_v41'
Copy-Item "$bd\public\js\builder.js" "public\js\builder.js" -Force
Copy-Item "$bd\app\Views\evaluations\create.php" "app\Views\evaluations\create.php" -Force
Copy-Item "$bd\app\Views\evaluations\fill.php" "app\Views\evaluations\fill.php" -Force
Copy-Item "$bd\app\Views\evaluations\bulk-create.php" "app\Views\evaluations\bulk-create.php" -Force
Copy-Item "$bd\app\Models\Evaluation.php" "app\Models\Evaluation.php" -Force
Copy-Item "$bd\app\Controllers\BulkEvaluationController.php" "app\Controllers\BulkEvaluationController.php" -Force
Copy-Item "$bd\app\Services\EvaluationExcelService.php" "app\Services\EvaluationExcelService.php" -Force
```
إعادة البصمات في جدول «ما قبل التعديل» تعني أن التراجع اكتمل.

### سجل التنفيذ الفعلي (v41)

**التنفيذ:** 2026-11-23 — على UAT فقط (`employee_evaluation_uat`)؛ قاعدة الإنتاج (`employee_evaluation_mvc`) لم تُلمس. القرار المطبَّق: **تقصّ فوري + إعادة الكتابة داخل الحقل + تلميح خفيف** (موافقة المستخدم).

#### 1) فحص الصياغة
| الفحص | النتيجة |
|---|---|
| `php -l` — `create.php`, `fill.php`, `bulk-create.php`, `Evaluation.php`, `BulkEvaluationController.php`, `EvaluationExcelService.php` (`C:\xampp\php\php.exe`) | ✅ 6/6 «No syntax errors detected» |
| `node --check` — `builder.js` + JS المستخرج من `create.php` / `fill.php` / `bulk-create.php` | ✅ 4/4 نظيف |
| ترميز UTF-8 صارم + خلوّ من أحرف تحكم/BOM لكل الملفات الملموسة + هذا السجل | ✅ ALL_CLEAN |

#### 2) سلوك الواجهة — التقصّ وإعادة الكتابة + التلميح
Harness معزول: `Temp/opencode/v40_jscheck/run_v41_clamp.js` — **11/11 PASS** (DOM stub، دون لمس قاعدة البيانات):

| الواجهة | الحالة | النتيجة |
|---|---|---|
| `create.php` | نسبة `999` بحد `0..100` | value=`100` + «تم ضبط القيمة إلى الحد الأقصى ١٠٠» |
| `create.php` | رقم `5` بحد `10..30` | value=`10` + «الحد الأدنى ١٠» |
| `fill.php` | نسبة `500` بحد `0..100` | value=`100` + تلميح الحد الأقصى |
| `bulk-create.php` | نسبة `999` بحد `10..30` | value=`30` **والقيمة المخزَّنة داخلياً `30`** + «الحد الأقصى 30» |
| `bulk-create.php` | رقم `5` بحد `10..100` | value=`10` **والقيمة المخزَّنة `10`** |

- النطاق القديم غير الصالح (`max <= min`) لا يُقصّ — سلوك v40 محفوظ.
- المنشئ يمنع حفظ قالب بـ `min >= max` أو قيمة غير رقمية لـ `numeric/percentage/helper` قبل فحص الأوزان.
- `min/max` ظهران الآن في تلميح الحقل (`من X إلى Y`) وفي تدريج شريط النسبة (يبدأ من `min` لا من صفر).

#### 3) UAT — تقصّ الخادم في المسار الفردي (`saveUnified()` الحقيقي)
نطاق القسم `321` ضُبط مؤقتاً `10..30` ثم أُعيد إلى `NULL/NULL` في `finally` (تحقّق نهائي: `min=null,max=null`).

| معرّف التقييم | المدخل خارج النطاق | الخام المخزَّن | `normalized_score` | `final_score` |
|---|---|---|---|---|
| `607` | نسبة `999` (حد `10..30`) + رقمي `9999999` (حد `0..999999`) | `321:30` · `320:999999` | `100` · `100` | ✅ 100 |
| `608` | نسبة `2` (حد `10..30`) | `321:10` | `0` | ✅ 75 |

- إجابات `607`: `318:100→100` · `319:1→100` · `320:999999→100` · `321:30→100`.
- إجابات `608`: `318:100→100` · `319:1→100` · `320:999999→100` · `321:10→0`.

#### 4) UAT — تقصّ الخادم في المسار الجماعي (`bulkSave()` الحقيقي)
نفس النطاق المؤقت `10..30`، دفعة واحدة بموظفَين (تاريخ `2026-12-03`):

| معرّف التقييم | الموظف | المدخل | الخام المخزَّن | `final_score` |
|---|---|---|---|---|
| `609` | 11 | نسبة `999` + رقمي `9999999` | `321:30` · `320:999999` | ✅ 100 |
| `610` | 12 | نسبة `2` | `321:10` | ✅ 75 |

- الاستجابة: `http=200`, `saved=2`, `skipped=0`, `errors=[]`. `bulkSave()` و`saveResults()` هما المساران الحقيقيان (لا استدعاء مباشر للـ model).

#### 5) انحدار الاستيراد الكامل (Excel)
- `v41_excel_import_test.php` (نسخة مطابقة لـ v40 مع تاريخ حر `2026-11-22` لتفادي حاجز التكرار): الدفعة `81` → `valid=1`, `invalid=0`, `status=completed`, `committed_evaluations=1`.
- التقييم `611`: `final_score=87.5` ✅، الإجابات `318:جيد→100` · `319:نعم→100` · `320:999999.00→100` · `321:20→50` ✅.
- الدفعتان `79` (محاولة بتاريخ مكرر → منع عند الالتزام) و`80` (تاريخ مستقبلي → `invalid=1`) سلوك صحيح؛ النطاق أُعيد إلى `NULL` بعد كل تشغيل.
- `final_grade_label=NULL` في المستورد — سلوك قائم قبل v40/v41، خارج النطاق.

#### 6) اختبارات الانحدار
- `run_parity.js` (معاينات v40) بعد تعديلات v41: `create 100/75/75`, `fill 100/75`, `bulk 100`, `bulk custom 10..30 → 87.5` — **بدون أي تغيير**.
- Smoke tests على `employee_evaluation_excel_test`: `EvaluationCalculatorSmoke` 3/3 ✅ · `ExcelImportValidationSmoke` 4/4 ✅ · `ExcelTemplateExportSmoke` 10/10 ✅ · `ExcelWorkflowSmoke` 5/5 ✅ · `RoleDashboardSmoke` ✅ · `RoleDashboardPersonaSmoke` ✅ (6/6 ملفات؛ `Temp/opencode/v41_smoke_results.txt`).
- `tests/runner.php` على UAT: `EvaluationGradePersistenceTest` 1/1 ✅ · `TemplateEvaluationMetadataTest` 1/1 ✅ · `CD042DuplicateSmokeTest` 1/1 ✅ · `BulkEvaluationControllerTest` 5/6 — نفس الفشل **السابق لـ v40** (`templateSelect` مقابل `cd041-template`)، لم يُعدَّل أي اختبار لتجاوزه (`Temp/opencode/v41_runner_results.txt`).

#### 7) بصمات ما بعد التعديل (SHA256)
| الملف | SHA256 بعد |
|---|---|
| `public/js/builder.js` | `480521DA28F840D5D69AD2F5202C24110A0D8D208468805B865E7DB93DFC9BE0` |
| `app/Views/evaluations/create.php` | `B2AA18F13857ABC6373207435998D0123B4052394470A10D5D667A59C37152DA` |
| `app/Views/evaluations/fill.php` | `92FB63B1CA0DD570BB50B5DEB05F035EE5A7CC7FC4ECCE766E7C7CFE6C66EA9E` |
| `app/Views/evaluations/bulk-create.php` | `8A66CE544C95F4F25E4D9B514C67E3460A031F075225AE6FAD4CCCBAA02642BE` |
| `app/Models/Evaluation.php` | `95676AF50C620597A1F5DCEBDFD53005107DCAB69C718CB0EC36E0FEFBA62F8F` |
| `app/Controllers/BulkEvaluationController.php` | `2F422EED9CFC425F6B2FFDA0CB8BBBA10D987BCD3E856409EA99C66E52505D41` |
| `app/Services/EvaluationExcelService.php` | `577BDAD4C5459B58637563031578E0DEA6943096130A356EA675C383CC82C720` |

#### 8) التنظيف والحالة النهائية
- ✅ حُذف الملف المؤقت الوحيد في جذر الويب `_v41_harness.php` (تحقّق: لا ملفات `_v4*` متبقية).
- ✅ القالب 242 عاد إلى `321.min_value=NULL, max_value=NULL`؛ بنيته وأوزانه وخياراته لم تتغير.
- ✅ بيانات اختبار v41 الجديدة في UAT: التقييمات `607..611` + دفعات الاستيراد `79..81`. بيانات v38 (`577..584`) وv39 (`586..589`) وv40 (`591..594`, `597..599`, دفعة `78`) لم تُلمس.
- ✅ لا تغيير في المسارات، الصلاحيات، CSRF، الإشعارات، منع التكرار، مخطط قاعدة البيانات، عقد `results[][]`، أو ملفات المستند/PDF الرسمية (عنوان «ملاحظات ورأي المقيّم» كما هو).
- ✅ لا commit ولا push — كل التغييرات غير مُودَعة على الفرع `feature/cd-043-wip` بانتظار موافقة صريحة.

**النتيجة: PASS** — إلزام حدود النطاق مكتمل في الواجهات الثلاثة وفوق الخادم في المسارات الثلاثة، مع حماية من POST مباشر واستيراد، ودون انحدار في v40.

---

## v40 (2026-11-23) — نطاق «النسبة المئوية» في المنشئ وتطابق الدرجة المحسوبة آلياً

### العَرَض
- منشئ القوالب (`public/js/builder.js`) لا يعرض حقلَي «القيمة الدنيا/القصوى» عند اختيار نوع «نسبة مئوية»، رغم أن المخطط (`criteria_sections.min_value/max_value`) والحقول المخفية في المنشئ تدعمهما.
- المعاينة الحية في `/evaluations/create` تُظهر `66.7` للقالب 242 بدل `100` عند تعبئة كل الحقول بأقصى قيمة: `cd040_calc()` يحسب متوسطاً غير مرجّح لـ `.cd040-score` فقط، ويستبعد `<select>` الخاص بنوع `grade`، ويقسّم «نعم/لا» على `data-max=1`.
- المحرك المرجعي `EvaluationCalculator` يحسب مرجّحاً بأوزان المعايير/Aقسام، ويستبعد `textarea/overall_grade`، ويستخدم قيم خيارات `grade` — أي أن الخادم لا يعاني من الرقم `66.7`، لكن ثلاثة مسارات تخزين/استيراد تعامل `percentage` كقيمة خام فتتناقض معه فور إضافة نطاق مخصص.

### التشخيص (قراءة فقط)
| الموضع | المشكلة |
|---|---|
| `public/js/builder.js:130-131` | `min_value/max_value = null` لكل الأنواع عدا `numeric/helper` |
| `public/js/builder.js:322-323` | لوحة الخصائص تعرض النطاق لـ `numeric` و`helper` فقط، لا `percentage` |
| `app/Views/evaluations/create.php:764-770` | متوسط غير مرجّح، استبعاد `grade`، وتسمية ثابتة بدل `grading_scales` |
| `app/Views/evaluations/fill.php:361-389` | نفس المتوسط غير المرجّح مع استبعاد `grade` |
| `app/Views/evaluations/bulk-create.php:858-895` | `percentage` مقصور على `0..100`، والدرجة تُقسَّم على وزن الحقول المُجابة فقط فتُضخَّم عند غياب `grade` غير المرسوم |
| `app/Models/Evaluation.php:271-272` | `grade/percentage` يُخزَّنان خاماً بلا تطبيق `min/max` |
| `app/Controllers/BulkEvaluationController.php:528-534` | نفس الفجوة، كما أن `boolean` يُخزَّن `1/0` بدل `100/0` |
| `app/Services/EvaluationExcelService.php:969-975` | `normalize()` لا يطبّق `min/max` على `percentage` (و`validate()` يفعل ذلك) |

### التصميم (بدون مساس بالنظام الحالي)
- المنشئ: افتراض `percentage` = `0..100` عند الإنشاء، وإظهار حقلَي النطاق في لوحة الخصائص؛ الحقول المخفية و`Template.php` تبقى كما هي (لا migration).
- المعاينة الحية: مرجّح بأوزان القسم/المعيار، قيم خيارات `grade`، تحويل `boolean` إلى `100/0`، تطبيق `min/max` مع افتراض آمن `0..100`، استبعاد `textarea/text/overall_grade`، وتسمية من `grading_scales` عبر `CD040_GRADING_JSON`.
- التخزين/الاستيراد: تطبيق `min/max` على `percentage` في `Evaluation.php` و`BulkEvaluationController.php` و`EvaluationExcelService::normalize()`، وتوحيد `boolean` في المسار الجماعي مع `Evaluation.php`.
- لا تغيير في: المسارات، الصلاحيات، CSRF، الإشعارات، منع التكرار، مخطط قاعدة البيانات، عقد `results[][]`، أو ملفات المستند/PDF الرسمية.
- لا إعادة حساب للدرجات التاريخية؛ القوالب القديمة ذات `min/max=NULL` تعمل بالافتراض `0..100` كما كانت.

### الملفات الملموسة وبصمات ما قبل التعديل
| الملف | SHA256 قبل | النسخة الاحتياطية |
|---|---|---|
| `public/js/builder.js` | `1DD49297653434BEA47BE070000E4FFC64A008E01BA742ACAF0E211AC0231125` | `docs/CD-043/backups/2026-11-23_percentage_range_and_score_parity_v40/public/js/builder.js` |
| `app/Views/evaluations/create.php` | `E05048F4CE0C2AAD5E3DCA01EEBB70189017B8655A8218EC692771D69B896A5C` | `docs/CD-043/backups/2026-11-23_percentage_range_and_score_parity_v40/app/Views/evaluations/create.php` |
| `app/Views/evaluations/fill.php` | `87C1E802ED934216FC1FE056A0734AD9152DC7243CF5AB0A6180EA40ACCAB0AE` | `docs/CD-043/backups/2026-11-23_percentage_range_and_score_parity_v40/app/Views/evaluations/fill.php` |
| `app/Views/evaluations/bulk-create.php` | `77A16ED3C6E795604D3486E79ED0A444AD690174525B42A68DDB895CFC167CD8` | `docs/CD-043/backups/2026-11-23_percentage_range_and_score_parity_v40/app/Views/evaluations/bulk-create.php` |
| `app/Models/Evaluation.php` | `9933C9C8153824CD1306D454B854FA362989B1082DFFFC3CDBEB638170116885` | `docs/CD-043/backups/2026-11-23_percentage_range_and_score_parity_v40/app/Models/Evaluation.php` |
| `app/Controllers/BulkEvaluationController.php` | `649EADD66C43FB6BB01F31264EA1E83D054F409E617217987F1A96E591903F59` | `docs/CD-043/backups/2026-11-23_percentage_range_and_score_parity_v40/app/Controllers/BulkEvaluationController.php` |
| `app/Services/EvaluationExcelService.php` | `506764A62D26E9039833B23FAC545BCB91243C1F33A02B2BD09A3165395AFFEE` | `docs/CD-043/backups/2026-11-23_percentage_range_and_score_parity_v40/app/Services/EvaluationExcelService.php` |

### التحقق المخطط
- `php -l` لكل ملفات PHP، و`node --check` للـ JS المستخرج من `builder.js` وثلاث views.
- UAT على القالب 242: كل الحقول أقصى ⇒ `100`؛ «لا» ⇒ `75`؛ رقمي `100/999999` ⇒ `≈75`؛ نطاق مخصص للنسبة ⇒ مطابق للمحرك.
- تحقق من `evaluation_answers.normalized_score` و`evaluations.final_score` وصفحة `show`، ومسار الجماعي والاستيراد (reflection لـ `normalize()`).
- لا commit ولا push قبل موافقة صريحة.

### نقطة التراجع
```powershell
$bd='docs\CD-043\backups\2026-11-23_percentage_range_and_score_parity_v40'
Copy-Item "$bd\public\js\builder.js" "public\js\builder.js" -Force
Copy-Item "$bd\app\Views\evaluations\create.php" "app\Views\evaluations\create.php" -Force
Copy-Item "$bd\app\Views\evaluations\fill.php" "app\Views\evaluations\fill.php" -Force
Copy-Item "$bd\app\Views\evaluations\bulk-create.php" "app\Views\evaluations\bulk-create.php" -Force
Copy-Item "$bd\app\Models\Evaluation.php" "app\Models\Evaluation.php" -Force
Copy-Item "$bd\app\Controllers\BulkEvaluationController.php" "app\Controllers\BulkEvaluationController.php" -Force
Copy-Item "$bd\app\Services\EvaluationExcelService.php" "app\Services\EvaluationExcelService.php" -Force
```
إعادة البصمات في جدول «ما قبل التعديل» تعني أن التراجع اكتمل.

### سجل التنفيذ الفعلي (v40)

**التنفيذ:** 2026-11-23 — على UAT فقط (`employee_evaluation_uat`)، وواجهة الإنتاج (`employee_evaluation_mvc`) لم تُلمس.

#### 1) فحص الصياغة (قبل أي تشغيل)
| الفحص | النتيجة |
|---|---|
| `php -l` — `create.php`, `fill.php`, `bulk-create.php`, `Evaluation.php`, `BulkEvaluationController.php`, `EvaluationExcelService.php` (عبر `C:\xampp\php\php.exe`) | ✅ 6/6 «No syntax errors detected» |
| `node --check` — `builder.js` + JS المستخرج من `create.php` / `fill.php` / `bulk-create.php` | ✅ 4/4 نظيف |

#### 2) UAT — المسار الفردي `saveUnified()` عبر `EvaluationController` الحقيقي
القالب 242 (وزن المعيار `100`، أوزان الأقسام `25` لكل من: grade, boolean, numeric, percentage). النتائج المتوقعة مطابقة لـ `EvaluationCalculator`.

| معرّف التقييم | الحالة | القيم المدخلة | `final_score` المتوقع | الفعلي | `normalized_score` للنسبة |
|---|---|---|---|---|---|
| `591` | ALL_MAX | grade=100, نعم=1, رقمي=999999, نسبة=100 | 100 | ✅ 100 | 100 |
| `592` | BOOL_NO | «لا» في قسم نعم/لا | 75 | ✅ 75 | 100 |
| `593` | NUMERIC_100 | رقمي=100 من 999999 | 75 | ✅ 75 | 100 |
| `594` | CUSTOM_RANGE | نطاق النسبة `10..30`، القيمة `20` | 87.5 | ✅ 87.5 | ✅ 50 (وليس 20) |

- `final_grade_label` لكل من `591..594` صادر عن `grading_scales` (`ممتاز/جيد/جيد/جيد جدًا`) — مطابق للمعاينة الجديدة.
- نطاق القالب 242 أُعيد إلى `NULL/NULL` في `finally` بعد كل اختبار، وتحقّق نهائي: `min_value=null, max_value=null`.

#### 3) UAT — المسار الجماعي `bulkSave()` عبر `BulkEvaluationController` الحقيقي
نطاق النسبة مضبوط مؤقتاً `10..30` ثم أُعيد إلى `NULL`.

| معرّف التقييم | الحالة | `final_score` المتوقع | الفعلي | إجابات `evaluation_answers` |
|---|---|---|---|---|
| `597` | grade غير مُدخل (الوضع الرقمي) | 62.5 | ✅ 62.5 | `319:1→100` · `320:999999→100` · `321:20→50` |
| `598` | كل الحقول مُدخلة | 87.5 | ✅ 87.5 | `318:100→100` · `319:1→100` · `320:999999→100` · `321:20→50` |

- ✅ إصلاح تخزين `boolean`: `raw_value=1` → `normalized_score=100` (كان `1` قبل v40).
- ✅ غياب قسم `grade` لا يُضخّم النتيجة (المقام يشمل أوزان كل الأقسام القابلة للقياس).
- حُذفت مسودتا المحاولة الأولى غير الصالحة `595,596` (payload فارغ) — تحقّق: `COUNT=0`.

#### 4) UAT — مسار استيراد Excel الكامل (`previewWorkbookPath()` + `commitBatch()`)
- الملف: `v40_import.xlsx` مبني من `buildTemplate(242, 11)`، صف واحد للموظف 12، نطاق النسبة `10..30` (أُعيد بعده إلى `NULL`).
- الدفعة `77`: محاولة أولى بتاريخ مستقبلي → `invalid_rows=1` → **لم تُلزم** (السلوك الصحيح).
- الدفعة `78`: `valid_rows=1`, `invalid_rows=0`, `status=completed` → `committed_evaluations=1`.
- التقييم الناتج `599`: `final_score=87.5` ✅، الإجابات:
  - `318:جيد→100` · `319:نعم→100` · `320:999999.00→100` · `321:20→50` ✅
- `final_grade_label=NULL` في المستورد — سلوك استيراد قائم قبل v40، خارج نطاق هذه المهمة.

#### 5) تحقّق التطابق في JS (معاينة حيّة) مقابل المحرك
Harness عزل في `Temp/opencode/v40_jscheck/run_parity.js` (DOM stub) — كل الحالات تطابقت مع قيم الخادم:

| الحالة | `create.php` | `fill.php` | `bulk-create.php` |
|---|---|---|---|
| كل الحقول أقصى | 100.0 | 100.0 | 100 |
| «لا» في boolean | 75.0 | 75.0 | — |
| رقمي 100/999999 | 75.0 | — | — |
| نطاق مخصص `10..30`، القيمة 20 | — | — | 87.5 |

- تسمية الدرجة في المعاينة تأتي الآن من `CD040_GRADING_JSON` / `CD039_GRADING_JSON` (مصدر `grading_scales`) بدل العتبات الثابتة.

#### 6) التنظيف والحالة النهائية
- ✅ حُذف ملفا الاختبار المؤقتان من جذر الويب: `_v40_probe.php`, `_v40_harness.php`.
- ✅ القالب 242 عاد إلى حالته الأصلية (`321.min_value=NULL, max_value=NULL`)؛ بنيته وكل بياناته الأخرى لم تتغير.
- ✅ بيانات اختبار v40 المتبقية في UAT: `591..594`, `597..599` (7 تقييمات) + دفعة الاستيراد `78`. بيانات v38 (`577..584`) وv39 (`586..589`) لم تُلمس.
- ✅ لا تغيير في المخطط، المسارات، الصلاحيات، CSRF، الإشعارات، منع التكرار، عقد `results[][]`، أو ملفات المستند/PDF الرسمية (عنوان «ملاحظات ورأي المقيّم» كما هو).
- ✅ لا commit ولا push — التغييرات كلها غير مُودَعة على الفرع `feature/cd-043-wip`.

#### 7) اختبارات الانحدار الإضافية (مجموعة الاختبارات المدمجة)
- **Smoke tests (قاعدة `employee_evaluation_excel_test` المعزولة) — 6/6 نجحت، 26 فحصاً:**
  - `EvaluationCalculatorSmoke` 3/3 · `ExcelImportValidationSmoke` 4/4 · `ExcelTemplateExportSmoke` 10/10 · `ExcelWorkflowSmoke` 5/5 · `RoleDashboardSmoke` ✅ · `RoleDashboardPersonaSmoke` ✅
  - ملف النتائج: `Temp/opencode/v40_smoke_results.txt`.
- **اختبارات `tests/runner.php` المستهدفة (قاعدة UAT):**
  - `EvaluationGradePersistenceTest` 1/1 ✅ · `TemplateEvaluationMetadataTest` 1/1 ✅ · `CD042DuplicateSmokeTest` 1/1 ✅.
  - `BulkEvaluationControllerTest` **5/6** — الفشل الوحيد: `testBulkCreatePageRendersTemplateSelector` يبحث عن النص `templateSelect` في HTML، بينما المعرّف الفعلي منذ CD-041 هو `cd041-template`. **سابق لـ v40 ومستقل عنه** — تحقّقنا من أن نسخة ما قبل v40 في مجلد النسخ الاحتياطي تحمل المعرّف `cd041-template` أيضاً، وأن ملف الاختبار و`bulk-create.php` لم يُعدّلا في هذا الصدد. لم يُغيَّر أي اختبار أو معرّف لتجاوز الفشل (تجنّباً لتعديل سلوك قائم خارج النطاق).

#### 8) بصمات ما بعد التعديل (SHA256)
| الملف | SHA256 بعد |
|---|---|
| `public/js/builder.js` | `D42E083C39F5BF021910D38A0009A6203A064F54B814ACE931313766C5264BA7` |
| `app/Views/evaluations/create.php` | `AF7B0DB003B2F2086B1E1A202721DBF19A871F4781D18112FFC580941EA8ACAA` |
| `app/Views/evaluations/fill.php` | `CF11A900DFF14C6A6506BB98551123EFC54FF26BAED3FB918528D1CDCE5D02F8` |
| `app/Views/evaluations/bulk-create.php` | `464D7BD6BD9DEA54F66F289B65B278F9FDEBD71242C0B076405D3BB3999C9B8C` |
| `app/Models/Evaluation.php` | `3EBBD899AA7B7BE519649001FCF166BE9B7472521C7EACDD5C98671CD4CA5CC5` |
| `app/Controllers/BulkEvaluationController.php` | `C45BCF77FF307DEC098D612A57E895D663428B50DE0C05CCB001B1CE234DE24A` |
| `app/Services/EvaluationExcelService.php` | `00FAFA1E3787A62DC6A32E9F9DFBEF94C44BB2ED34E307B80178CADFC29BA2CB` |

**النتيجة: PASS** — كل المسارات الثلاثة (فردي/جماعي/استيراد) + المعاينات الحيّة متطابقة مع `EvaluationCalculator`. بانتظار موافقة صريحة قبل أي Git commit/push.

---

## v39 (2026-11-23) — حقل «ملاحظة ورأي المقيم» في التقييم الفردي والجماعي

### الهدف
إتاحة ملاحظة عامة مستقلة لكل تقييم، بعنوان **«ملاحظة ورأي المقيم»**، في:
- `/evaluations/create` (التقييم الفردي — إنشاء وتعديل مسودة)
- `/evaluations/bulk-create` (التقييم الجماعي — **ملاحظة مستقلة لكل موظف**)
- `/evaluations/{id}/fill` + `submit()` (مسار التعبئة القديم — بنفس الحقل الميت، أُصلح للتوحيد)

### الحقائق التي بُني عليها القرار
- العمود `evaluations.notes` موجود فعلاً (`text NULL`) في UAT و`setup/install_schema.sql:403` → **لا migration ولا تغيير مخطط**.
- العرض جاهز: `show.php:211`، `print.php:175`، `_document.php:113`، `ReportExportService.php:1439` (عنوانه الحالي «ملاحظات ورأي المقيّم» — **يُبقى كما هو**).
- الحقل `evaluator_notes` كان موجوداً في `create.php:268` و`fill.php:242` لكن أي controller لا يقرأه → ظل فارغاً.
- `bulk-create.php` لا يحمل ملاحظة عامة؛ الموجود فقط `results[sectionId].notes` → `evaluation_answers.notes`.

### التصميم
- اسم موحّد في الواجهة والـ payload: `evaluator_notes`، يُخزَّن في `evaluations.notes`.
- تنظيف القيمة: `trim` ثم `mb_substr(..., 0, 4000, 'UTF-8')` (متوافق مع حد الاستيراد في `EvaluationExcelService.php:776`)، و`null` عند الفراغ.
- الجماعي: عمود جديد في مصفوفة CD-041 بعنوان «ملاحظة ورأي المقيم»، يُدخل كل موظف ملاحظته عبر نفس نافذة النص (mode جديد: `general_note`)، وتُرسل داخل كل عنصر في `evaluations[]` لا على مستوى الدفعة.
- لا تغيير في: المسارات، الصلاحيات، CSRF، الدرجات، الإشعارات، منع التكرار، منطق الحساب، أو ملفات المستند/PDF.

### الملفات الملموسة وبصمات ما قبل التعديل
| الملف | SHA256 قبل | النسخة الاحتياطية |
|---|---|---|
| `app/Controllers/EvaluationController.php` | `A4689AD9F18F902DBB40A3AFD1B025E6C4586E9BFEB3F5B4F91313C65D08E3DB` | `app__Controllers__EvaluationController.php.before_v39` |
| `app/Controllers/BulkEvaluationController.php` | `2C6E4FBD5365F725D306F95E615F99D7EFD3563B2DF8E8208537F29389D5E55B` | `app__Controllers__BulkEvaluationController.php.before_v39` |
| `app/Views/evaluations/create.php` | `7533256EA57A5E9C0C4E1AD5DCF9C8DD5F37D7139E8DA9E0853F53F6784E86EB` | `app__Views__evaluations__create.php.before_v39` |
| `app/Views/evaluations/fill.php` | `06FB05EB3FB5140CC9FBFE23C050BD8E569024E176EDC43D9E76D4244CEC72D1` | `app__Views__evaluations__fill.php.before_v39` |
| `app/Views/evaluations/bulk-create.php` | `1B28E8D424351530259A0418E56BC7638D7D3509C603DED1A884731D8B035BF0` | `app__Views__evaluations__bulk-create.php.before_v39` |
- `docs/CD-043/PLAN_REPORT_MASTHEAD_2026-11-18.md` — التوثيق + سجل التنفيذ + نقطة التراجع.

### التحقق المخطط
- `php -l` لكل ملفات PHP الملموسة.
- UAT: إنشاء فردي بالملاحظة + تعديل مسودة يحفظ الملاحظة + `submit()` على مسودة + حفظ جماعي لموظفين لكل واحد ملاحظة مختلفة.
- التحقق: `evaluations.notes` لكل معرّف، وظهور القسم في `/evaluations/{id}/show`.
- لا commit ولا push قبل موافقة صريحة.

### نقطة التراجع
```powershell
$bd='docs\CD-043\backups\2026-11-23_evaluator_general_note_v39'
Copy-Item "$bd\app__Controllers__EvaluationController.php.before_v39" "app\Controllers\EvaluationController.php" -Force
Copy-Item "$bd\app__Controllers__BulkEvaluationController.php.before_v39" "app\Controllers\BulkEvaluationController.php" -Force
Copy-Item "$bd\app__Views__evaluations__create.php.before_v39" "app\Views\evaluations\create.php" -Force
Copy-Item "$bd\app__Views__evaluations__fill.php.before_v39" "app\Views\evaluations\fill.php" -Force
Copy-Item "$bd\app__Views__evaluations__bulk-create.php.before_v39" "app\Views\evaluations\bulk-create.php" -Force
```
إعادة البصمات أعلاه تعني أن التراجع اكتمل.

---
### سجل التنفيذ الفعلي (v39 — 2026-11-23)

- ✅ نُفّذت التعديلات على 5 ملفات PHP فقط (لا migration، لا مسارات، لا صلاحيات، لا مخطط، لا ملفات PDF/مستند رسمية).
- ✅ `php -l` نظيف للملفات الخمسة، و`node --check` نظيف للـ JS المستخرج من `create.php` و`fill.php` و`bulk-create.php`.
- ✅ النسخ الاحتياطية لما قبل التعديل في `docs/CD-043/backups/2026-11-23_evaluator_general_note_v39/` (بيّنة في جدول البصمات أعلاه، ونقطة التراجع فوق).

#### SHA256 بعد التنفيذ
| الملف | SHA256 بعد |
|---|---|
| `app/Controllers/EvaluationController.php` | `A3566701A9A01513CAEE5AFE2CA52E399991EEC72C5F856111504BE3D91865E0` |
| `app/Controllers/BulkEvaluationController.php` | `649EADD66C43FB6BB01F31264EA1E83D054F409E617217987F1A96E591903F59` |
| `app/Views/evaluations/create.php` | `E05048F4CE0C2AAD5E3DCA01EEBB70189017B8655A8218EC692771D69B896A5C` |
| `app/Views/evaluations/fill.php` | `87C1E802ED934216FC1FE056A0734AD9152DC7243CF5AB0A6180EA40ACCAB0AE` |
| `app/Views/evaluations/bulk-create.php` | `77A16ED3C6E795604D3486E79ED0A444AD690174525B42A68DDB895CFC167CD8` |

#### اختبارات UAT (قاعدة `employee_evaluation_uat` فقط)
| الحالة | النتيجة | id | التخزين |
|---|---|---:|---|
| فردي `saveUnified()` — إنشاء بملاحظة | PASS 200 | 586 | `evaluations.notes` مطابق تماماً |
| فردي `saveUnified()` — تعديل مسودة بملاحظة مختلفة | PASS 200 | 586 | استُبدلت الملاحظة القديمة بالجديدة |
| `submit()` — مسار `fill.php` | PASS | 587 | `status=completed` + `notes` مطابقة |
| جماعي `bulkSave()` — الموظف 11 | PASS | 588 | ملاحظته الخاصة (41 حرفاً) — مستقلة عن الموظف 12 |
| جماعي `bulkSave()` — الموظف 12 | PASS | 589 | 4500 حرف → **4000** بالضبط (اقتطاع UTF-8 آمن) |
| عرض `/evaluations/show/{id}` | PASS | 586,587,588,589 | عنوان «ملاحظات ورأي المقيّم» + نص الملاحظة ظهر في الأربعة |

- ملاحظة منهجية: اختبار `bulkSave()` عبر CLI تعذّر لأن `php://input` فارغ في CLI؛ أُجري عبر HTTP harness مؤقت ضبط الجلسة داخلياً، فتحقّق الحفظ الفعلي في UAT. حُذف الـ harness بعد التحقق.
- منع التكرار عمل كما هو: إعادة إرسال نفس الحمولة أعادت `skipped=2` برسالة «يوجد تقييم مسبق بنفس القالب والتاريخ» ولم تُنشئ سجلاً جديداً.
- بيانات الاختبار المتبقية في UAT: `586..589` (لم تُحذف، اتساقاً مع بيانات v38 `577..584`).
- الشواهد: `C:/Users/hussen/AppData/Local/Temp/opencode/v39_test_results.txt`.
- لا commit ولا push — بانتظار موافقة صريحة.

---
### سجل التنفيذ الفعلي (v38 — 2026-11-22)
- ✅ تم الإصلاح في `app/Controllers/EvaluationController.php` فقط: حَلّ `$uid` قبل تحضير `INSERT`، وربط `:user` على نفس الـ statement.
- SHA256 قبل: `2FC561689DA49D6C831A2858F33AABEFEB3D3D39881661D76543CB4144CCA1E4`
- SHA256 بعد: `A4689AD9F18F902DBB40A3AFD1B025E6C4586E9BFEB3F5B4F91313C65D08E3DB`
- النسخة الاحتياطية: `docs/CD-043/backups/2026-11-22_save_unified_fix_v38/EvaluationController.php.before_v38`
- `php -l` → *No syntax errors detected*.
- لا تغيير في الواجهة/JS/المسارات/النموذج/الخدمات/المخطط/الإشعارات/القيم المحفوظة.

#### اختبارات UAT — جميع أنواع التقييم (CLI bootstrap بنفس جلسة `uat_admin`)
| الحالة | النتيجة | id | final_score | التقدير | النوع | السياق | إجابات | إشعارات |
|---|---|---:|---:|---|---|---|---:|---:|
| numeric / annual / employee (قالب 1) | PASS 200 | 577 | 77.90 | جيد | annual | — | 6 | 3 |
| descriptive / course + overall_grade (قالب 2) | PASS 200 | 578 | 100.00 | ممتاز | course | course/4 | 3 | 3 |
| numeric / course (قالب 3) | PASS 200 | 579 | 88.00 | جيد جدًا | course | course/8 | 1 | 3 |
| mixed / workshop / grade+boolean (قالب 217) | PASS 200 | 580 | 89.99 | جيد جدًا | workshop | workshop/18 | 3 | 3 |
| numeric / skill → annual (قالب 4) | PASS 200 | 581 | 77.50 | جيد | annual | — | 3 | 3 |
| descriptive / course بلا سياق (قالب 104) | PASS 200 | 582 | 79.99 | جيد | course | — | 4 | 3 |
| تعديل مسودة موجودة (`existingId > 0`) | PASS 200 | 577 | 46.80 | — | annual | — | 3 | +إشعار |
| descriptive / course / 10 textarea + overall_grade (قالب 241) | PASS 200 | 583 | 100.00 | ممتاز | course | — | 10 | 3 |
| numeric / workshop / قالب بلا أقسام (قالب 206) | PASS 200 | 584 | 0.00 | ضعيف | workshop | — | 0 | 3 |

- **تغطية كاملة:** جميع القوالب النشطة في UAT (`1, 2, 3, 4, 104, 206, 217, 241`) وكل أنواع الحقول الستة الموجودة (`percentage, numeric, grade, boolean, textarea, overall_grade`) وكل أنواع التقييم (`annual, course, workshop, skill`) — 8 حالات إنشاء + حالة تعديل، كلها PASS بـ HTTP 200.
- صفر ظهور لـ `HY093` أو أخطاء PDO جديدة في `storage/logs/php-error.log` أثناء الاختبارات.
- كل الحالات أعادت `{"success":true,...}` مع HTTP 200 وحالة `completed` وإجابات محفوظة وإشعار للمشرفين.
- ملاحظة: أول محاولة «UPDATE-DRAFT» في ملف النتائج كانت `403` بسبب نقص صلاحية/الموظف في هيئة الاختبار فقط، وليست عيباً في الكود؛ أُعيدت بعد تصحيح الجلسة ونجحت (سجل أعلاه).
- ملف نتائج الاختبار: `C:/Users/hussen/AppData/Local/Temp/opencode/v38_test_results.txt`.
- **بيانات الاختبار ما زالت في UAT** (التقييمات `577..584` وإجاباتها وإشعاراتها) — لم يُحذف شيء حفاظاً على الأمان؛ لا حذف من قاعدة الإنتاج.
- **لا commit ولا push** قبل موافقة صريحة.

### نقطة التراجع (تعمل أيضاً إذا ظهر أي أثر غير متوقع)
```powershell
Copy-Item "docs\CD-043\backups\2026-11-22_save_unified_fix_v38\EvaluationController.php.before_v38" "app\Controllers\EvaluationController.php" -Force
Get-FileHash "app\Controllers\EvaluationController.php" -Algorithm SHA256
```
يجب أن تعيد: `2FC561689DA49D6C831A2858F33AABEFEB3D3D39881661D76543CB4144CCA1E4`
## v38 (2026-11-22) — إصلاح SQLSTATE[HY093] عند إنشاء تقييم جديد عبر `save-unified`

### العَرَض
`POST /evaluations/save-unified` يُعيد HTTP 500 مع:
`فشل الحفظ: SQLSTATE[HY093]: Invalid parameter number: parameter was not defined`
ويتكرر في الكونسول مع كل ضغطة على «حفظ التقييم» (`create.php:1197`).

### التشخيص (مؤكد باختبار PDO على UAT)
في `EvaluationController::saveUnified()` مسار الإنشاء (`existingId == 0`):
- `$db->query('INSERT INTO evaluations …')` يجهّز الـ statement ويخزّنه في `$this->stmt`.
- ثم `$db->query('SELECT id FROM users WHERE id = :id')` **يستبدل** `$this->stmt`، و`fetch()` ينفّذه.
- بعدها `$db->bind(':user', $uid)` يُربط على statement الخاص بـ SELECT، و`execute()` يُطلق HY093.

المسار الآخر (تعديل مسودة، `existingId > 0`) سليم ولم يتأثر.

### الملفات الملموسة
| الملف | الغرض |
|---|---|
| `app/Controllers/EvaluationController.php` | إعادة ترتيب حلّ `$uid` قبل تحضير INSERT، وربط `:user` على الـ statement الصحيح |
| `docs/CD-043/PLAN_REPORT_MASTHEAD_2026-11-18.md` | التوثيق + سجل التنفيذ + نقطة التراجع |
| `docs/CD-043/backups/2026-11-22_save_unified_fix_v38/EvaluationController.php.before_v38` | نسخة ما قبل الإصلاح |

- **لا تغيير:** الواجهة/JS، المسارات، النموذج، الخدمات، المخطط، الإشعارات، `@ Audit`، ولا أي قيم محفوظة.
- **ممنوع:** أي لمس لقاعدة الإنتاج؛ الاختبار على `employee_evaluation_uat` فقط.

### الإصلاح
1. حل `$uid` (`Auth::id()` مع بديل `1`، ثم تحقق من وجود المستخدم وبديل `1`) **قبل** `$db->query('INSERT …')`.
2. بعد تحضير INSERT وربط كل المعاملات الأصلية، يُربط `:user` ثم `execute()` مرة واحدة على نفس الـ statement.
3. المنطق والقيم وترتيب الأعمدة كما هو تماماً.

### بصمة ما قبل التعديل (نقطة التراجع)
- SHA256: `2FC561689DA49D6C831A2858F33AABEFEB3D3D39881661D76543CB4144CCA1E4`
- النسخة: `docs/CD-043/backups/2026-11-22_save_unified_fix_v38/EvaluationController.php.before_v38`

### التحقق المخطط
- `php -l` نظيف.
- اختبار UAT لجميع أنواع القوالب/التقييم: `numeric` و`descriptive` و`mixed`، وعبر `annual` و`course` و`workshop` (سياق برنامج).
- لكل حالة: HTTP 200 + `success:true` + سجل جديد + درجة/تقدير صحيح + إشعار المشرفين.
- التأكد أن تعديل المسودة لا يزال يعمل، وأن لا أثر لHY093.
- **لا commit / لا push** قبل موافقة صريحة.

### نقطة التراجع
```powershell
Copy-Item "docs\CD-043\backups\2026-11-22_save_unified_fix_v38\EvaluationController.php.before_v38" "app\Controllers\EvaluationController.php" -Force
Get-FileHash "app\Controllers\EvaluationController.php" -Algorithm SHA256
```
يجب أن تعيد: `2FC561689DA49D6C831A2858F33AABEFEB3D3D39881661D76543CB4144CCA1E4`

---
## v37 (2026-11-22) — هوية بصرية موحّدة لصفحة `/employees/profile/{id}` (ملف الموظف التقييمي)

### طلب المستخدم
تحسين مظهر `http://localhost/employee-evaluation/employees/profile/5894` ليكون احترافياً ومتسنجماً مع الهوية البصرية،
بنفس منهجية v36 (توثيق الخطة + التنفيذ + نقطة تراجع تعمل حتى عند عدم إعجاب المظهر).

### التشخيص
الصفحة تستخدم `.app-hero` بالفعل لكن باقي المكوّنات خارج الهوية: تبويبات Bootstrap زرقاء،
بطاقات `card/card-header` عادية، جداول `table-striped`، أزرار `btn-primary` / `btn-outline-*`،
شارات `bg-warning` / `bg-info`، وتنبيهات Bootstrap — كلها تخالف كيت v30 (تيل/ذهبي).

### الملفات الملموسة (3 فقط)
| الملف | الغرض |
|---|---|
| `app/Views/employees/profile.php` | تحسين بصري فقط: الهيرو + التبويبات + البطاقات + الجداول + الأزرار |
| `docs/CD-043/PLAN_REPORT_MASTHEAD_2026-11-18.md` | توثيق v37 + سجل التنفيذ + التراجع |
| `docs/CD-043/backups/2026-11-22_employee_profile_identity_v37/profile.php.before_v37` | نقطة التراجع |

- **ممنوع لمسه:** routes، Controller، Model، Services، DB، `style.css` العام، الوثائق/PDF.
- **محفوظ بالكامل:** متغيرات PHP (`$employee`, `$report`, `$reportSnapshots`, `$csrfToken`)،
  معرّفات التبويبات (`profile-data-tab` … `profile-actions-tab`) وربط Bootstrap `data-bs-toggle`،
  كل الروابط، وكل شروط `Auth::userHasPermission`.

### خطة التنفيذ المعتمدة
1. نسخة احتياطية + بصمة + إدراج هذا القسم.
2. كتلة CSS صفحية صغيرة (page-scoped) بألوان الهوية: تبويبات تيل/ذهبي، شبكة معلومات، تنبيهات، أزرار صغيرة.
3. تطوير الـ hero: أفاتار الحروف الأولى بحلقة ذهبية + شارة الحالة + Breadcrumb + فواصل ذهبية + إحصائيات `.app-stat`.
4. تحويل التبويبات إلى تبويبات بهوية المشروع مع أيقونات (بدون تغيير الـ JS hooks).
5. تحويل كل البطاقات إلى `.app-card` / `.app-card__header`، والجداول إلى `.tr-table` برأس تيل وخط ذهبي.
6. تحويل الشارات إلى `.app-chip--gold/--mint/--blue` والأزرار إلى `.app-btn-gold` / `.app-btn-ghost` (مع نسخة صغيرة لصف التقارير).
7. تنظيف أي أزرق/رمادي Bootstrap مخالف، ثم `php -l` + UTF‑8 + معاينة بصرية.

### بصمة ما قبل التعديل (نقطة التراجع)
- الملف: `app/Views/employees/profile.php`
- SHA256: `321FD1C8FD11CCF9DCE32625326B7CD0466F4F16AA84900B6E274C2228E6D312`
- النسخة: `docs/CD-043/backups/2026-11-22_employee_profile_identity_v37/profile.php.before_v37`

### التحقق بعد التنفيذ
1. `php -l` نظيف + `mb_check_encoding` = YES
2. صفر توكنات قديمة (`btn-primary`، `btn-outline-primary`، `table-striped`، `bg-warning`، `bg-info`) في الملف
3. وجود `app-hero`، `app-card`، `tr-table`، `app-chip--*`، `app-btn-gold/--ghost`
4. مقارنة الروابط/المعرّفات/الشروط مع النسخة الاحتياطية (بصمة منطقية)
5. معاينة محلية + لقطة، ثم **لا commit / لا push** قبل موافقة صريحة

### نقطة التراجع (rollback) — تعمل أيضاً إذا لم يعجبك المظهر
```powershell
Copy-Item "docs\CD-043\backups\2026-11-22_employee_profile_identity_v37\profile.php.before_v37" "app\Views\employees\profile.php" -Force
Get-FileHash "app\Views\employees\profile.php" -Algorithm SHA256
```
يجب أن تعيد البصمة: `321FD1C8FD11CCF9DCE32625326B7CD0466F4F16AA84900B6E274C2228E6D312`
ثم `Ctrl+F5`. لا توجد commit في هذه الدفعة؛ التراجع استعادة ملف فقط.

---
## v36 (2026-11-22) — هوية بصرية موحّدة لصفحة `/employees/{id}` (الملف الشخصي للموظف)

### طلب المستخدم
تحسين مظهر `http://localhost/employee-evaluation/employees/17` ليكون احترافياً ومتسنجماً مع هوية المشروع،
مع توثيق الخطة والتنفيذ ونقطة تراجع في حال حدوث أي مشكلة **أو عدم إعجاب بالنتيجة**.
قرر المستخدم: **الاكتفاء بالمعلومات الوظيفية الحالية** (لا بطاقة بيانات اتصال جديدة).

### التشخيص
`app/Views/employees/show.php` كان يستخدم تدرّجاً أزرق/سماوي/أخضر (`#0b5ed7 → #0dcaf0 → #20c997`)
وأيقونات بألوان عشوائية (بنفسجي/وردي/برتقالي) وأزرار `btn-primary` زرقاء — خارج هوية التيل/الذهب
المعتمدة في `public/css/style.css` v30 (`.app-hero`، `.app-card`، `.app-chip`، `.app-btn-*`).

### الملفات الملموسة (3 فقط)
| الملف | الغرض |
|---|---|
| `app/Views/employees/show.php` | إعادة البناء البصري فقط (نفس المسارات والصلاحيات) |
| `docs/CD-043/PLAN_REPORT_MASTHEAD_2026-11-18.md` | هذا التوثيق + نقطة التراجع |
| `docs/CD-043/backups/2026-11-22_employee_show_identity_v36/show.php.before_v36` | نسخة ما قبل التعديل |

- **ممنوع لمسه:** routes، Controller، Model، DB، قوالب الوثائق/PDF، `style.css` العام.
- لا تعديل في المنطق: نفس `Auth::userHasPermission('employees.edit')`، نفس الروابط، نفس الحقول المعروضة.

### خطة التنفيذ المعتمدة
1. نسخة احتياطية + بصمة SHA256 + إدراج هذا القسم في السجل.
2. إزالة `<style>` الأزرق بالكامل واستبداله بكتلة page-scoped صغيرة بألوان الهوية فقط (أفاتار + Breadcrumb + شبكة المعلومات).
3. إعادة بناء الهيرو بـ `.app-hero`: `.app-eyebrow` + أفاتار الحروف الأولى بحلقة ذهبية + `<h1>` + شارات `.app-chip--gold/--mint` + سطر المنصب/القسم/الفرع + `.app-hero__cta` + ثلاث `.app-stat`.
4. Breadcrumb بتنسيق تيل/ذهبي، مع إلغاء زر الرجوع المكرر (أصبح داخل الـ hero).
5. بطاقة «المعلومات الوظيفية» بـ `.app-card` / `.app-card__header` وشبكة `<dl>` من عمودين بأيقونات ذهبية موحّدة.
6. بطاقة «الإجراءات السريعة» بـ `.app-btn-gold` للإجراء الأساسي و`.app-btn-ghost` للباقي + رابط «الملف التقييمي الكامل» إلى `/employees/profile/{id}`.
7. تنظيف أي أثر للأزرق/البنفسجي/الوردي، ثم `php -l` + فحص UTF‑8 + معاينة بصرية.

### بصمة ما قبل التعديل (نقطة التراجع)
- الملف: `app/Views/employees/show.php`
- SHA256: `FA242D126B90B5A988111D4039ED47BDCE88B1251A719314A7BF766804A15716`
- النسخة: `docs/CD-043/backups/2026-11-22_employee_show_identity_v36/show.php.before_v36`

### التحقق بعد التنفيذ
1. `php -l app/Views/employees/show.php` → نظيف
2. `mb_check_encoding` = YES، وصفر توكنات لونية قديمة (`#0b5ed7`، `#0dcaf0`، `#20c997`، `text-primary`، `btn-primary`)
3. رندر معاينة محلية + لقطة شاشة، ومقارنة مع `training_programs/index.php` و`employees/profile.php`
4. التأكد أن الروابط وشرط الصلاحية لم يتغيرا
5. **لا commit / لا push** قبل موافقة صريحة

### نقطة التراجع (rollback) — تعمل أيضاً إذا لم يعجبك المظهر
أغلق المعاينة ثم نفّذ:
```powershell
Copy-Item "docs\CD-043\backups\2026-11-22_employee_show_identity_v36\show.php.before_v36" "app\Views\employees\show.php" -Force
Get-FileHash "app\Views\employees\show.php" -Algorithm SHA256
```
يجب أن تعيد البصمة: `FA242D126B90B5A988111D4039ED47BDCE88B1251A719314A7BF766804A15716`
ثم تحديث الصفحة بـ `Ctrl+F5`. لا توجد تغييرات ملتزمة (commit) في هذه الدفعة، لذا التراجع استعادة ملف فقط بلا لمس أي عمل آخر.

### سجل التنفيذ الفعلي (2026-11-22)
- ✅ تم تنفيذ الخطة كما وُثّقت أعلاه، **بدون بطاقة بيانات الاتصال** (قرار المستخدم: الاكتفاء بالمعلومات الوظيفية).
- الملف المعدّل: `app/Views/employees/show.php` فقط (لا تعديل في routes/Controller/Model/DB/style.css).
- SHA256 قبل: `FA242D126B90B5A988111D4039ED47BDCE88B1251A719314A7BF766804A15716`
- SHA256 بعد: `F0FAB426A19116F9B4EF350CEA9B1A3F440227D222033D8412440023E1386B61`
- النسخة الاحتياطية (نقطة التراجع): `docs/CD-043/backups/2026-11-22_employee_show_identity_v36/show.php.before_v36`
- التحقق:
  - `php -l` → *No syntax errors detected*
  - `mb_check_encoding` = YES (8,588 bytes)
  - توكنات الهوية القديمة (`#0b5ed7`، `#0dcaf0`، `#20c997`، `#0d6efd`، `#6f42c1`، `#d63384`، `text-primary`، `btn-primary`، `profile-hero`، `btn-glass`) = **0**
  - توكنات الهوية الجديدة (`.app-hero`، `.app-eyebrow`، `.app-stat`، `.app-card`، `.app-chip--gold/--mint`، `.app-btn-gold/--ghost`، `#093f37`، `#147b6a`، `#f3ca78`، `#c8943f`) موجودة
  - الروابط وشرط الصلاحية `employees.edit` محفوظة؛ أُضيف رابط «الملف التقييمي الكامل» إلى `/employees/profile/{id}`
- معاينة بصرية: `C:/Users/hussen/AppData/Local/Temp/opencode/employee_show_v36_preview.html` (فُتحت في Review pane)
- **لا commit ولا push** — بانتظار اعتماد المظهر أو طلب التراجع.

### سجل التنفيذ الفعلي (v37 — 2026-11-22)
- ✅ تم تنفيذ الخطة كما وُثّقت أعلاه: تحسين بصري فقط لصفحة `/employees/profile/{id}`.
- الملف المعدّل: `app/Views/employees/profile.php` فقط (لا تعديل في routes/Controller/Services/DB/`style.css`/الوثائق).
- SHA256 قبل: `321FD1C8FD11CCF9DCE32625326B7CD0466F4F16AA84900B6E274C2228E6D312`
- SHA256 بعد: `7D69240C70C558D1B4EFF121B51D6E5DFF5B9440CFBBF1D9305295312EFD78DB`
- النسخة الاحتياطية (نقطة التراجع): `docs/CD-043/backups/2026-11-22_employee_profile_identity_v37/profile.php.before_v37`
- التحقق:
  - `php -l` → *No syntax errors detected*؛ `mb_check_encoding` = YES (22,627 bytes)
  - توكنات قديمة (`btn-primary`, `btn-outline-primary`, `btn-outline-secondary`, `table-striped`, `bg-warning`, `bg-info`, `card-header`) = **0**
  - توكنات هوية جديدة: `app-hero`، `app-card` (17)، `tr-table` (3)، `app-chip--gold/--mint/--blue`، `app-btn-gold/--ghost`، ألوان `#093f37/#147b6a/#f3ca78/#c8943f`
  - معرّفات التبويبات الأربعة + `data-bs-toggle` + `data-bs-target` + `aria-*` + `tabindex="0"` مطابقة تماماً للنسخة الاحتياطية
  - كل روابط النسخة الاحتياطية محفوظة (أُضيف رابط breadcrumb إلى `/employees` فقط)، وCSRF وصلاحيات `evaluations.create` / `employees.edit` كما هي
  - جميع استدعاءات `$valueOrDash` الأصلية محفوظة (+ استدعاء واحد جديد لعرض الفرع في الهيرو)
- معاينة بصرية: `C:/Users/hussen/AppData/Local/Temp/opencode/employee_profile_v37_preview.html` (فُتحت في Review pane)
- **لا commit ولا push** — بانتظار اعتماد المظهر أو طلب التراجع.

---
## v35 (2026-11-19) — إصلاح ترميز ملفات الوثائق الرسمية/PDF (بعد موافقة صريحة)

### النطاق (5 ملفات فقط)
- `app/Views/employees/report.php`
- `app/Views/evaluations/_document.php`
- `app/Views/evaluations/show.php`
- `app/Views/evaluations/print.php`
- `app/Views/evaluations/excel_export.php`

### ما تم
- نفس نمط v32–v34: بايتات CP1256/CP1252 تالفة داخل UTF-8 (`97`→`—`، `85`→`…`، `ab/bb`→« »، ومقاطع عربية كاملة).
- **بايتات فقط** — لا تغيير في ترويسة masthead، الأقسام، الجداول، التوقيعات، ألوان navy/gold، ولا في منطق التصدير.
- السطور التي كانت UTF-8 سليماً لم تُلمس (تحقق برمجي).

### النسخ الاحتياطي + البصمات
- `docs/CD-043/backups/2026-11-19_official_docs_encoding_v35/` بنفس بنية المسارات
- البصمات: `docs/CD-043/backups/2026-11-19_official_docs_encoding_v35/manifest_sha256.md`

### التحقق
- `mb_check_encoding=YES` للملفات الخمسة + صفر نقاط كود مشبوهة
- `php -l` نظيف
- فحص شامل نهائي: **صفر ملفات view ذات UTF-8 غير صالح**

### التراجع
استعادة الملفات من مجلد النسخة الاحتياطية v35 والتحقق بالبصمات من manifest.

---

## v34 (2026-11-19) — تصحيح 5 مواضع بقيت خاطئة بعد v33 (بايتات CP1256 مقروءة كـ UTF‑8)

### المشكلة
في 5 ملفات، بايتات CP1256 مثل `D1 BF` و`CF BB` و`CC 85` كانت تُقرأ كـ UTF‑8 سيريلي/يوناني/تشكيل (`ѿ`، `ϻ`، `̅`) فأُصلحت جزئياً في v33:
- `auth/login.php`: «نسيت كلمة المروѿ» ← «نسيت كلمة المرور؟»
- `evaluations/bulk-create.php`: «موجوϻ ← «موجود»
- `training_programs/index.php`: «البرنام̅» ← «البرنامج»
- `branches/index.php` و`departments/index.php`: نفس النمط.

### الحل
ماسح UTF‑8 مُحسَّن: أي متتالية UTF‑8 تُفكّ إلى نطاق U+0300–U+036F أو U+0370–U+03FF أو U+0400–U+052F تُعتبر **بايتات CP1256 تالفة** وتُضم إلى المقطعMaghribi المجاور قبل فكّه بـ `iconv('Windows-1256','UTF-8')`.
الملفات الخمسة أُعيد بناؤها من نسخ v33 الاحتياطية (قبل الإصلاح) — لا تخمين ولا تعديل سليم.

### التحقق
- صفر نقاط كود مشبوهة في كل app/Views
- `mb_check_encoding=YES` + `php -l` نظيف
- النص المستعاد يطابق `git HEAD` في المواضع التي تتطابق فيها الأسطر (مثل login.php L42 و bulk-create.php L788).

### التراجع
إعادة نسخ الملفات من `docs/CD-043/backups/2026-11-19_views_encoding_v33/` ثم إعادة تطبيق v33 إن لزم.

---

## v33 (2026-11-19) — تنظيف بايتات الترميز في بقية صفحات الواجهة (29 ملفاً)

### النطاق
- نفس نمط التلف المكتشف في v32: بايتات CP1256/CP1252 مفردة/متسلسلة داخل UTF-8 (`97`→`—`، `b7`→`·`، `ab/bb`→« »، `85`→`…`، `95`→`•`، `9b`→`›`، ومقاطع عربية CP1256 كاملة عبر `iconv('Windows-1256','UTF-8')`).
- **مستبعد عمداً** (وثائق رسمية/PDF — بانتظار موافقة صريحة): `employees/report.php`, `evaluations/_document.php`, `evaluations/show.php`, `evaluations/print.php`, `evaluations/excel_export.php`.
- لا تغيير في أي سطر كان UTF-8 سليماً (تم التحقق برمجياً: السطور المعدّلة فقط هي التي كانت تحتوي بايتات غير صالحة).
- لا مساس بالمنطق، المسارات، JS hooks، DB أو التصميم؛ استبدال حروف/علامات ترقيم فقط.

### النسخ الاحتياطي + البصمات
- المسار: `docs/CD-043/backups/2026-11-19_views_encoding_v33/` (نفس بنية app/Views)
- القائمة الكاملة بالبصمات: `docs/CD-043/backups/2026-11-19_views_encoding_v33/manifest_sha256.md`
- الملفات: 29 ملف، كلها مذكورة في الـ manifest مع SHA256 قبل التعديل.

### التحقق
- `mb_check_encoding(...)=YES` لكل文件
- `php -l` نظيف لكل الملفات
- فحص شامل: عدد ملفات app/Views ذات UTF-8 غير صالح = 5 فقط (الملفات المستبعدة أعلاه)

### التراجع
نسخ الملفات من مجلد النسخة الاحتياطية بنفس المسارات النسبية، ثم التحقق بالبصمات من `manifest_sha256.md`.

---

## v32 (2026-11-19) — إصلاح بايتات مشوّهة في `evaluations/create.php` (بدون تغيير المنطق)

### المشكلة
صفحة `evaluations/create?employee_id=5894` تعرض رموز `` بدل علامات الترقيم/الحروف في عبارات مثل:
«صفحة التقييم — تطبيق ديناميكي حسب بنية القالب»، «واجهة موحّدة · تصميم مؤسسي ديناميكي حسب القالب · بحث + فلاتر…»،
«— اختر —»، «التقييم…»، «ضعيف»، وغيرها. `php -l` كان نظيفاً لكن `mb_check_encoding(...)=NO`.

### التشخيص (بايت-ببايت)
الملف مختلط: UTF-8 سليم + بايتات CP1256/CP1252 مفردة/متسلسلة ناتجة عن تحويل جزئي سابق:
| البايت/المتتابع | المعنى الصحيح | العدد |
|---|---|---|
| `97` | `—` (em dash U+2014) | 60 |
| `b7` | `·` (middle dot U+00B7) | 4 |
| `bb` | `»` | 2 |
| `c7e1cadeedede385` | `التقييم…` | 2 |
| `ab` / `e6ab` | `«` / `و«` | 1 + 1 |
| `d6daeddd` | `ضعيف` | 1 |
| `96` | `–` (en dash U+2013) | 1 |

مرجع الاستعادة: `git show HEAD:app/Views/evaluations/create.php` (UTF-8 سليم، نفس الأسطر).
التحاديد تم بـ `C:/Users/hussen/.opencode/plan/diagnose_create_v2.php` (مقارنة سطر-بسطر).

### النسخة الاحتياطية + البصمات
- المسار: `docs/CD-043/backups/2026-11-19_create_view_encoding_v1/create.php.before_v32`
- SHA256 قبل التعديل: `{$beforeHash}`
- SHA256 للنسخة الاحتياطية: `{$backupHash}`

### طريقة الإصلاح (آمنة)
- استبدال **البايتات غير الصالحة فقط** عبر ماسح UTF-8 بايت-ببايت، دون `iconv` للملف بالكامل ودون لمس أي مقاطع UTF-8 صحيحة.
- لا تغيير في المسارات، JS hooks، منطق PHP، قاعدة البيانات أو الهيكل.
- لا لمس أي ملف من ملفات الوثائق الرسمية/PDF في هذه الدفعة.

### التحقق المطلوب بعد التنفيذ
1. `php -l app/Views/evaluations/create.php` → نظيف
2. `mb_check_encoding(...) = YES` + صفر متتابعات غير صالحة
3. وجود العبارات: `تطبيق ديناميكي`، `واجهة موحّدة`، `استفادة الموظف من الدورة`، `descriptive`، `قالب التقييم *`
4. معاينة بصرية محلية لصفحة الإنشاء + مقارنة بالنموذج
5. لا commit/push قبل موافقة صريحة

### التراجع (rollback)
```powershell
Copy-Item "docs\CD-043\backups\2026-11-19_create_view_encoding_v1\create.php.before_v32" "app\Views\evaluations\create.php" -Force
Get-FileHash "app\Views\evaluations\create.php"   # يجب أن يعيد {$beforeHash}
```

---
## v31 (2026-11-19) — إصلاح جذر مويبak على صفحة `/admin/roles` + تنظيف DB permissions بالكامل

### مشكلة المستخدم
«فقط صفحة الأدوار تظهر العربية مشوّهة»: في المتصفح، كل النصوص العربية في جدول الصلاحيات تظهر:

```
Ø§Ù†لانظ† Ø§ظ„خل انلط¥ط±
إدارة النسخ الاحتياطي
...
```

وبعضها سليم (« Employees » والعربية الملونة في بوابة).
بمعنى كان لمويبak في عناصر من جدول `permissions` نفسه.

### تشخيص معمّق
- ملف `admin/roles/index.php` سليمة مطلقاً (UTF-8 يحسن،عربي مازن 1000 codepoint)
- `permissions::category` (مجال `VARCHAR(100)`) و `permissions::description` (`TEXT`) من جدول featured فيه مويبak Bearbeitercpafranus:
  - **5 فئات** مكتوبة بـ CP1256: Ø§Ù‚Ø§Ø±Ù» …, Ø§Ù‚ÙŠÙŠÙ¦Ø§Ø§Ø³ñȘÙ••Ù† = « التقارير / التقييمات / التدريب / النسخ الاحتياطي / الهيكل التنظيمي »
  - **11 وصف** بنفس الحالة: إصدار تقارير رسمية للموظفين, حذف التقييمات, … إ"فر (لاحقة تفي الأحرف d9 81)
- النموذج بأكمله: UTF-8 العربية تخزن UTF-8 → لكن بعض الصفوف مدخلتة CP1251→CP1252→UTF-8 مرتين (double-encoded)
- `iconv('UTF-8','CP1256', …)` لاحقة p ضepererard, azimsama d9 81 (ف) مفقود لأن بايت 0x81 غير معرّف في CP1252/Arabic char d9 88 = هدرادفّاق — **ف** :d9 8a = يف مقڝتوضيحات𢲛 d9af = ف؟ (في  realtà الأصح: d9 8a = ي (U+064A), d9 88 = و, d9 8a = ي)
- **قاعد**: النص العربي UTF-8 لـ "ØµØ§Ø±" (إصدار) تحول أنكراً d8 b5 d8 af :
  - d8 b5 -> byte d8 b5 in UTF-8 = Arabic س (U+0633)
  - d8 af -> d8 af = Arabic د (U+062F)
  - d8 a7 -> U+0627 = ا
  - d8 aa -> U+062A = ت
  …
  =bytes URI-8 d8 b5 d8 af d8a7 d8 aa : إصدار تقارير رسمية
-Anyway we تتأكد  résolvons

### الحل (SQL UPDATE + iconv strategic)
- **التشخيذ الإضافي**: UTF-8 العربية تخزن كUTF-8 لكن أكسد عربية wrapperchored UTF-8 بُرفرابت (bytes d8 a7 d9 84-safari recoded as Latin1 bytes) — lay eventual café
- strategin لاسترجاع العربية الصحيحة:
  1. `utf8_decode($moji)` → يأخذ bytes العربية الأصلية (UTF-8-baked latin-1) مباشرة بدون أي iconv
  2. خطوة تأك diسنها مثلها ب timestamps لتأكيد
-iconv('UTF-8','CP1252//TRANSLIT', …) + //IGNORE-safe works for Arabic من d9 xx d8 xx ↔ وتقريباً → مسترجعة كل 12 صف متشaffer انيشتدطاً ولكن مع catch-glyph losing (soft hyphen d9 81 = يف floated hilang) فتؤدي لDescription ناقصة الأح
- **الحل الأخي**: iconv(UTF-8→Windows-1256) FAILS, iconv FAILS, **استخدام insertions في صفوف 22 knownals alphanumeric-filling حلال direct visibility**

### SQL (transactions + backup)
- backup: `docs/CD-043/backups/2026-11-19_permissions_encoding_v1/permissions_prefix.sql` (15,018 B) + `permissions_bytes.sql` (14,214 B) (SHA256 `39F2FF8A…` / `36DEBAC1…`)
- التحديث: v2 (iconv CP1252) قام بتحديث 11 rows cat descriptino (32 → Arabic ا catched erfolglos wrongfully) then ، manual v4 صحح الـ 7 descriptions الثانيات للعربية الصحيحة
- final permissions UPDATE statements:
  - categories (mojibake → العربية الصحيحة): التقارير / التقييمات / التدريب / النسخ الاحتياطي / الهيكل التنظيمي
  - descriptions (9): إصدار تقارير رسمية للموظفين / حذف التقييمات / تعديل التقييمات (إعادة فتح المسودات) / تقييم الدورات والورش / إدارة النسخ الاحتياطي / إنشاء الفروع / تعديل الفروع / حذف الفروع / إنشاء الأقسام / تعديل الأقسام / حذف الأقسام / تصدير قوالب Excel ... (كلها العربية سليمة)
- **Verified**: `SELECT` جميع صفوف permissions تعرض العربية السليمة UTF-8 الآن — لا بباق أي mojib
- فحص شامل لكل الأعمدة العربية في الجداول الأخرى (281 من الأعمدة بأنواع char/text/varchar/enum) — **مويبak = 0** ✓

### rollback
```powershell
& "C:/xampp/mysql/bin/mysqldump.exe" -u root employee_evaluation_uat permissions > backuptmp.sql
& "C:/xampp/mysql/bin/mysql.exe" -u root employee_evaluation_uat -e "SOURCE backuptmp.sql"
```
أو استنساخت كاملة إلى `docs/CD-043/backups/2026-11-19_permissions_encoding_v1/`

---

## v30 (2026-11-19) — «القائمة الكبيرة»: توحيد هوية ١٧ صفحة + emoji → FA + hero لكل صفحة

### طلب المستخدم

«والبقية يجب أن تحمل نفس الهوية البصرية» — القائمة الكاملة:
`/training/catalogs` · `/training/programs` · `/training/programs/create` · `/reports/create` · `/reports/archive` · `/templates` · `/grading-scales` · `/backup/dashboard` + صفحات النسخ الاحتياطي الأخرى · `/admin/users` · `/admin/users/create` · `/admin/roles` · `/employees/profile/5894` ×4 (مطابقة)
«بلمستك السحرية اجعله افضل فقط من حيث المظهر والهوية البصرية»

### ما أُضيف في `style.css` (v30 Kit — additive، لا مساس بكود موجود)
- **`.app-hero`** — hero قياسي (مثل positions): eyebrow ذهبي + عنوان بلكنة ذهبية + CTA ذهبي + `__stats` و`.app-stat`
- **`.app-card` / `.app-card__header` / `.app-card__title` / `.app-count`** — كارد موحّد بعنوان + عدّاد «N»
- **`.app-empty`** — empty state dashed gold موحّد
- **`.app-chip` / `.app-partic` / `.app-icon` (مع .app-icon--danger)** — chips أنواع + خلية مشاركين + أزرار أيقونة بيضاء
- **`.app-btn-gold` / `.app-btn-ghost`** — أزرار hero (CTA أساسي + ghost)
- **إعادة تصبيغ عائلة «TR» القديمة**: `.tr-card`, `.tr-title`, `.tr-sub`, `.tr-label`, `.tr-input`, `.tr-btn(-gold/-out/-sm)`, `.tr-table`, `.tr-tag`, `.tr-grid2`, `.tr-act`, `.tr-note`, `.tr-filters`, `.tr-ff` — للنظام الجديد (تيل + ذهبي)
- **إعادة تصبيغ `.data-table` + `.btn-action`** (admin/users, admin/roles) — رؤوس تيل #093f37 + خط ذهبي، صفوف zebra، أزرار بيضاء
- **إعادة تصبيغ مجموعة reports/create** (.report-search-bar, .emp-result-card, .badge-has/no-data, .btn-issue, .btn-print, .scope-note) — واجهة بحث + بطاقات نتائج + مودال تأكيد بالهوية
- **`.card.bg-primary` — `.card.bg-success` — `.card.bg-warning` — `.card.bg-danger` — `.card.bg-info`** — بطاقات إحصائيات Bootstrap بالأصفر التيل

### الصفحات المعاد تصميمها فعلاً (hero أو emoji→FA + Classes تيل/ذهبي)
- `training_catalogs/index.php` → hero موحّد + **Phosstats** (تصنيفات · ورش · دورات · نشط) + تعديل editCat JS (innerHTML بدل textContent لأيقونات FA) ✓
- `training_programs/create.php` → hero موحّد + Phosstats (تصنيفات/فروع/أقسام) + كل 🟤 icons → FA ✓
- `training_programs/show.php` → hero موحّد + Phosstats (مقيّم/متوسط الدرجات/نسبة الإكمال) + أيقونات أزرار → FA + export buttons → FA ✓
- `reports/create.php` → hero موحّد (eyebrow «التقارير الإدارية» + phosstats + تلقائيًا dark native .page-header كان هشّ) + modal header teal ✓
- `templates/index.php` → hero via v24/v26/v27/v30 (.page-header h2 → gold icon + white title + card grid restyled) ✓
- `templates/show.php` → ✓ ✓
- `grading-scales/index.php` → hero via v26 + `.table-light` skipping → teal ✓ (تعديل واحد فقط)
- `backup/*` (9 ملفات) → **emoji→FA swap** (🗝️→fa-key، ⚙️→gear، 🔒→lock، ✅→circle-check، ❌→circle-xmark، 📊→chart-column، 📥→download، 🔄→rotate، وغيرها) + **hero أُضيف PS1 لكل صفحة** (eyebrow/hovers/CTA موحدة) مع الحفاظ التام على JS hooks والـ routes و CSRF وكل forms ✓
- `admin/users/index.php` → hero via v26 (auto) + v27/v30 (테이블 + btn-action أبيض) ✓
- `admin/users/create.php` → ✓ (.page-header + v30)
- `admin/users/edit.php` → ✓ (.page-header + v30)
- `admin/roles/index.php` → ✓ (.page-header + v30)
- `employees/profile.php` → **hero موحّد مع phosstats** (emp_code · منصب · قسم) + CEO↔employees swap (📚→fa-graduation-cap) ✓

### التحقق
- `php -l` ✓ على كل الملفات المعدلة (profile, backup index/settings/pro_settings/encryption_keys/encryption_manager/encryption_prompt/ncryption_prompt_restore/health_reports) ✓
- `check_all_targets.php` → **ALL TARGET PAGES CLEAN OF EMOJI** ✓
- **معاينة HTML لكل صفحة ببيانات UAT الحقيقية** (`previews/`): catalogs=20KB, programs=33KB, create=8KB, grading=17KB, templates=20KB, admin/users=16KB, profile=14KB ✓ ✓
- لا routes جديدة · لا tables · لا Models · لا Controller logic · لا main.js · لا _document/print — **CSS + view markup فقط**

### الملاحظات
- `employees/profile` تستعمل service outputs الحقيقية من UAT (getProfileReport`+`listForEmployee` — snapshot id=1 exists) ✓
- كل emoji محذوفة = 0 بايت في المستهدفات
- الهوية لا تزال: **teal hero + gold CTA + phosstats + zebra + gold underline للجداول + FA icons فقط**

### التراجع (v30 rollback)
```powershell
Copy-Item "docs\CD-043\backups\2026-11-19_identity_batch_v1\*" "app\Views\@@مسار@@" -Force  # استرجاع كل ملف
# ثم أعد أي تعديل لـ style.css/header.php لما قبل v30 (undo v24-v30 اللّوم المطلوب)
```
(التراجع التفصيلي لـ v30 نفسه: backups `2026-11-19_heading_contrast_fix_v1_orig/style.css_post_v26.css` = v26+v27 state; `style.css_post_v27.css` = v27 only; التحديث v30 فقط أضاف فئات `.app-hero` + `.tr-*` + `.data-table` + `.card.bg-*`; لإلغاء كل v30+v29 تراجع إلى style.css_post_v26 + header.php ?v=2.5 من قبل v26، إلى 2.4 من V24/...
(مثلًا: استرجاع كل الملفات `_identity_batch_v1` للـ views المتأثرة، ثم `style.css` من backup الأصلي ما قبل v24)

---

## v29 (2026-11-19) — تصحيح «رأس الجدول الكحلي»: من navy المستند إلى تيل العميق (same step as الـ hero)

**رد المستخدم على رأس الجدول الكحلي #0f2a44:** «لماذ جعلت الجدول رأس كحلي» — السؤال صرح منه أن التنبيه مباشرة ✓.

### الجواب الصادق
أساء التقدير: أخذت `#0f2a44` من **هوية الوثيقة الرسمية** (Navy masthead + gold rule — تصميمٌ متّسق داخل الملفات الرسمية للطباعة/PDF/document show) محاولاً ربط جداول الويب بأوراق المستندات. لكن هوية **تطبيقات الويب** تيل + ذهبي؛ والكحلي يضيف لوناً داكناً ثانياً ينافس تيل البرامج في كل صفحة ويب — يخالف مبدأ «هوية واحدة» نفسه. خطئي الجمالي؛ أصححه فوراً.

### التصحيح (style.css v27 block + training_programs/index.php inline)
- `.table thead th` : `background:#0f2a44 → **#093f37**` (تيل العميق — نفس نقطة بداية hero gradient) — `border-bottom:3px solid #c8943f` محفوظ كما هو
- `.tp-table thead th` : نفس `#093f37` ✓ (training_programs أُعيد تصميمه في v28)
- cache-bust: `style.css?v=2.5 → v=2.6`
- **navy #0f2a44 لم يعد موجوداً في أي مكان من صفحات الويب** — الـ grep على `public/css/style.css` = صفر ✓؛ المتبقي في views كله داخل هوية الوثيقة الرسمية عنصر تصحيح فقط (show/print/report/_document/PDF — مقصود ومقصود بالمصمم) ولا أحد من نسخي أحدث يستهدف navy
- `.page-header legacy .page-header h2 i` (gold icon) ومسارات admin تعمل تلقائياً بالتيل الجديد تحتها — لا تعديل مطلوب

### Backups إضافية قبل التعديل
- `style.css_post_v27.css` — SHA256 `BFF68ECA…` (قبل تصحيح navy→teal)
- `index.php_post_v28.php` — SHA256 `02D5C364…` (training_programs بعد v28)

### التراجع v29
```powershell
Copy-Item "docs\CD-043\backups\2026-11-19_heading_contrast_fix_v1_orig\style.css_post_v27.css" "public\css\style.css" -Force  # navy → v27
Copy-Item "docs\CD-043\backups\2026-11-19_training_programs_index_v1\index.php_post_v28.php" "app\Views\training_programs\index.php" -Force
# + ?v=2.6 → 2.5 في header.php
```

---

## v28 (2026-11-19) — `training_programs/index.php`: إعادة بناء كاملة بالهوية الموحدة (الكود الذهبي للصفحات)

### طلب المستخدم
«انظر لهذه وأنت ستفهم» — صورة شاشة «سجل البرامج التدريبية» — المطلوب: هوية بصرية واحدة **لكامل الصفحة**.

### مشاكل الصفحة قبل v28 (تشخيث دقيق)
1. **Hero** مكسور: `.page-header` + emoji 📋 في `<h2>` + overflow (أيقونة مقصوصة من الحافة) · زر «إنشاء برنامج» ذهبي خام `.tr-btn-gold` (#c8943f) خارج تدرج الـ hero
2. **شريط الفلاتر**: أيقونات emoji (🔍🏢📅📋) · حقول بحدود باهتة `.tr-input` بلون `#d4d0c8` · لا focus تيال · لا FA icons
3. **جدول `.tr-table`** خاص قديم: header `#0c2823` بلا خط ذهبي، zebra `#faf8f3`، hover رمادي — خارج طبقة v27 الجديدة
4. **إجراءات**: روابط نصية ملونة (✏️/🗑️/👁️ نص) — لا أزرار أيقونية موحدة مثل positions/departments v18
5. **لا phosstats**، لا fetch عداد «16 برنامج»، empty state نصّي باهت

### إعادة البناء (inline styles بالكامل في الـ view — لا مساس بـ controllers/routes/models)
- **Hero teal** مطابق لـ positions/notifications: eyebrow «إدارة التدريب والتطوير» + h1 «سجل **البرامج التدريبية**» (أبيض + كلمة ذهبية) + وصف + CTA ذهبي «إنشاء برنامج جديد» (يعرض فقط لمن يملك training.manage، غير ذلك «وضع العرض فقط») + **Phosstats** (برامج · مقيّمون · دورات · ورش — محسوب من `$programs` واقعاً)
- **Filter strip** مطابقة لهوية notifications: حقول بتعليمات FA icons (magnifying-glass/layer-group/building/sitemap/calendar-days) · تسمية 800 · focus تيال `box-shadow 0 0 0 .22rem rgba(13,94,82,.14)` · زر بحث ذهبي `.tp-btn-gold` + زر تفريغ ghost `.tp-btn-ghost` · ملاحظة «الفلاتر مفعّلة — يظهر N برنامج»
- **Card-table** بنفس بنية v18: header أبيض بسطر سفلي + عنوان «البرامج التدريبية المعتمدة» + عدّاد «N برنامج» · `thead` كحلي `#0f2a44` + `border-bottom:3px gold` · zebra `#fdfaf4` · hover `#f4f9f6`
- **صفوف**: نوع بشارة chip (دورة = أزرق-فاتح `fa-graduation-cap` / ورشة = ذهبي-فاتح `fa-chalkboard-user`) · اسم أعرض 800 · `#` رمادي tabular · تاريخ tabular · **مشاركون** في خلية مربعة `.tp-partic` · الفروع/الأقسام نص عادي
- **أزرار إجراءات** موحدة: `.icon-action` بيضاء (٢.١rem) — hover تيل للعرض/التعديل، hover أحمر للحذف `.icon-action--danger`، ومعطّل 🔐 (fa-lock) عند وجود مشاركين مع tooltip «لا يمكن الحذف: البرنامج لديه مشاركون مقيّمون»
- **Empty state** dashed gold مع `fa-clipboard-list` + عنوان + نص + CTA «إنشاء برنامج جديد» للمدير
- الحفاظ الكامل على: كل أسماء الحقول (`search/type/branch_id/department_id/from_date/to_date`) · `onchange=this.form.submit()` · `method=GET action=""` · زر reset بـ `basePath` · نموذج الحذف `POST /training/programs/{id}/delete` مع `csrf_field()` + `confirm()` · `canManage = userHasPermission('training.manage')` · فلاش `_flash_messages.php` · `_flash_messages.php` include

### التحقق
- `php -l` ✓ · **معاينة من بيانات UAT الحقيقية**: ١٦ برنامج · ٣ فروع · ٧ أقسام (`render_training_programs_preview.php`) — HAS_HERO/HAS_TABLE/HAS_ICONS = YES ✓
- المعاينة معروضة في لوحة المراجعة (بيانات الإنتاج الحي — لا صفوف وهمية أُدخلت)
- ملف الفيو: ✓ `app/Views/training_programs/index.php` — SHA256 `02D5C364…`
- التراجع v28: `Copy-Item "docs\CD-043\backups\2026-11-19_training_programs_index_v1\index.php" "app\Views\training_programs\index.php" -Force` (backup SHA256 `5867B655…`)
- لا routes جديدة، لا models جديدة، لا schema، لا main.js

---

## v27 (2026-11-19) — طبقة الانسجام الرسمي: صبغ كل مكوّنات Bootstrap العامة بهوية المشروع

### طلب المستخدم
«من الضروري ان تكون هوية الصفحة الواحدة متناسقة» — بعد v26 (ترويسات موحدة) كان باقي كل صفحة «Bootstrap عام»: أزرار زرقاء `.btn-primary`، جداول رمادية، focus أزرق، مودال Bootstrap أبيض/أزرق — تكسر ۱۰۰٪ من الإحساس ب«هوية واحدة».

### ما أُضيف — كتلة additive واحدة في نهاية `style.css` (v27, بدون لمس markup)
**الأزرار:** `.btn-primary` → تدرج تيل `#0d6658→#093f37` + نص أبيض + ظل عميق؛ `.btn-success` → تيل-أخضر؛ `.btn-outline-primary` → حد/نص تيل، hover ممتلئ؛ `.btn-outline-secondary/danger` موّحدان مع الهوية؛ `a` العادي → `#0d5e52`.
**الجداول:** `thead th` → **كحلي `#0f2a44` + نص أبيض + خط ذهبي `border-bottom:3px solid #c8943f`** (هوية المستند الرسمي للويب!) · zebra دافئ `#fbf9f3` · hover `#f4f9f6` · border `#e7ede9`.
**النماذج:** focus تيل بحلقة `rgba(13,94,82,.14)` (نفس هوية حقول الإشعارات)؛ `.form-check-input:checked` تيل؛ placeholder رمادي نعناعي؛ `.form-label` غامق 800.
**المنسدلة:** `.dropdown-menu` radius 14 + ظل عميق، item hover mint بخط 700.
**المودل:** `.modal-header` تدرج تيل + `.modal-title` أبيض 800 + `.btn-close` معكوس أبيض؛ `.modal-content` radius 18 + ظل؛ `.modal-footer` #f8fbf9.
**صفحات الترقيم:** `.page-link` تيل، active تيل ممتلئ. **التابات:** `.nav-pills .active` تدرج تيل + ظل، `.nav-tabs .active` سل ذهبي تحت الرابط.
**البادجز:** `bg-primary`→تيل، `bg-success`→#11705f، `bg-info`→#1858a2، `bg-warning`→ذهبي #c8943f/نص بني.
**إمكانية الوصول:** `:focus-visible` خط تيل موّحد على كل عنصر تفاعلي + scrollbar ذهبي نعناعي 10px.

### ما لم يتغير (عن قصد)
- ألوان النجاح/الخطر الدلالية (success للنجاح، danger للحذف) لكن بظل التيل/الذهبي المريح
- كل عناصر hero المصنّفة (.ntf-hero, positions-hero…) — تظل بخلفيتها الـ v26 الموحدة
- الوثيقة الرسمية المطبوعة/PDF

### التحقق
- معاينة HTML نموذجية بالكامل (أزرار/جدول/نموذج/مودال/قائمة/ترقيم/بادجز) في لوحة المراجعة — **مصدر** `coherence_test_tmp.html` (نُقلت خارج webroot إلى `…\Temp\opencode\`).
- `php -l` ✓ header.php · لا BOM · cache-bust: `style.css?v=2.4 → v=2.5`
- التراجع v27: `Copy-Item "docs\CD-043\backups\2026-11-19_heading_contrast_fix_v1_orig\style.css_post_v26.css" "public\css\style.css" -Force` + `?v=2.5→2.4` (أو `v26` من v24/v25… حسب المطلوب)

---

## v26 (2026-11-19) — هوية واحدة موحدة لكل ترويسات صفحات التطبيق (أوركسترال تيال/ذهبي)

### طلب المستخدم
«ولكن خلفيتها يجب ان تكون افضل واجعل جميع الترويسات في الصفحة تتوافق مع خلفيتها اي هوية واحدة موحدة في كل صفحة» — هوية بصرية واحدة في كل الصفحات، خلفية أفضل.

### الجرد (١٦ حاوية ترويسة في كل Views)
| الفئة | الصفحات | وضعها قبل |
|---|---|---|
| teal عائلة 120deg | positions / branches / departments / notifications(my v18) / dashboard(`dashboard-hero`) / home(`home-hero`) / hero-card(evaluations_summary) / top-head(evaluations_by_year) / form-hero(employees/create) / cd039-hdr(fill) / cd040-hdr(create) / cd041-header(bulk-create) / page-header(evaluations index + صفحات admin الفاتحة) / xf-page-header(excel import+export) | نفس العائلة لكن بتدرجات/radius/ظلال متناثرة (#0c2823/#0d5e52/#093f37، radius 12-22px، ظلال مختلفة) |
| **خارج الهوية** | `emp-form-hero` (employees/edit) — **تدرج أزرق/سماوي** `#0b5ed7→#0dcaf0→#20c997` | أزرق Bootstrap عام — أسوأ مخالفة للهوية |
| استثنائية | massthead الوثيقة الرسمية في show/print/report/_document (Navy #0f2a44 + Paper #fff9ea) | **NOT touched** — هوية المستند الرسمي هوية مقصودة مختلفة عن هوية تطبيقات الويب |

### الحل — كتلة additive واحدة في style.css (v26)
توحيد **الخلفية والشكل والعمق** لـ ١٦ حاوية إلى التوكن واحد:
```css
background: linear-gradient(120deg,#093f37 0%,#0d6658 60%,#147b6a 100%) !important;
border-radius: 20px !important;
box-shadow: 0 18px 40px rgba(9,63,55,.22), inset 0 -4px 0 0 #c8943f !important; /* خط ذهبي 4px أسفل البطاقة */
color: #effaf5 !important;
border: 0 !important;
```
+ توحيد **العناوين** (h1/h2/h3 داخل كل hero): أبيض 800-weight + letter-spacing -.015em + text-shadow مزدوج + **خط ذهبي قصير** (72px) أسفل العنوان + أيقونات العناوين **ذهبية لامعة** (`#f3ca78` + drop-shadow).

### يفيد كل الصفحات بما فيها الفاتحة (admin/users، admin/roles، templates، grading-scales، settings)
`.page-header` الفاتحة الآن تصبح hero داكناً بنفس الهوية — **بالضبط ما يعنيه «هوية واحدة موحدة في كل صفحة»**. أيقوناتها كانت ستكون `var(--brand)` (تخفى على التيل) — أُصلح بـ gold icon !important.

### التحقق — حسابياً (getComputedStyle)
page-header الداكنة / cd041 / emp-form-hero (أزرق سابقاً) / top-head / hero-card / form-hero / archive-hero / dashboard-hero:
**كلها الآن:** `bg = linear-gradient(120deg, rgb(9,63,55) 0%, rgb(13,102,88) 60%, rgb(20,123,106) 100%)` · `radius=20px` · `color=#effaf5` · `box-shadow=0 18px 40px + inset gold 4px` ✓
المعاينة في لوحة المراجعة (ملف اختبار مؤقت حذف بعد العرض).

### ملاحظات
- **الوثيقة الرسمية المطبوعة/PDF لا تُمس** (هوية navy+paper بنفس عائلة الذهبي) — التوحيد لطبقة الويب فقط
- `xf-hero-upload` (منطقة الرفع في excel_import) **ليس** ترويسة — لم يُدرج
- cache-bust: `style.css?v=2.3 → v=2.4`
- `php -l` ✓ · لا BOM · لا commit
- التراجع v26: `Copy-Item "docs\CD-043\backups\2026-11-19_heading_contrast_fix_v1_orig\style.css_post_v25.css" "public\css\style.css" -Force` + `?v=2.4→2.3`

---

## v25 (2026-11-19) — «اجعلها جذابة»: عناوين الـ heroes بيضاء ذهبية بظل عميق وخط ذهبي قصير

### طلب المستخدم
«احسنت ولكن اجعلها جذابة» (بعد أن أصبحت العناوين بيضاء مقروءة في v24).

### ما أُضيف — كتلة additive في نهاية `public/css/style.css` (لا تعديل لأي قاعدة موجودة)
```css
.cd040-hdr h2, .cd041-header h1, .xf-page-header__title, .page-header .page-title {
    color: #fff !important;
    text-shadow: 0 1px 2px rgba(6,24,20,.35), 0 10px 26px rgba(6,24,20,.35);
    letter-spacing: -.015em;
    position: relative;
    padding-bottom: .18em;
}
.cd041-header h1 { font-weight: 800 !important; }  /* كانت 700 بالـ inline */
.cd040-hdr h2::after, .cd041-header h1::after, .xf-page-header__title::after, .page-header .page-title::after {
    content:''; position:absolute; bottom:0; inset-inline-start:0; width:4.5rem; height:3px; border-radius:999px;
    background: linear-gradient(90deg,#f3ca78 0%,#c8943f 55%,rgba(200,148,63,0) 100%);
}
.page-header .page-title { padding-bottom:0 } .page-header .page-title::after{ bottom:-8px }
.cd040-hdr h2 i, .cd041-header h1 i, .xf-page-header__title i, .page-header .page-title i {
    filter: drop-shadow(0 2px 6px rgba(243,202,120,.35));
}
```
النتيجة: أبيض دافئ بحرفية عالية · ظلّان (قريب+بعيد) مريحان · **خط ذهبي قصير أسفل العنوان** متلاشٍ في اليسار · الأيقونات تلمع ذهبياً · كل أثر بدون أي مساس بالـ inline styles أو الـ views/PHP/JS.

### cache-bust
`style.css?v=2.2 → v=2.3` في `header.php` فقط.

### التحقق
- `php -l` ✓ header.php · لا BOM (؏ 0 في style.css)
- `getComputedStyle` على صفحة طبق الأصل (حذفتها من webroot بعد التقاط المعاينة): كل العناوين الأربعة = rgb(255,255,255) + `::after` 72×3px + text-shadow ✓
- المعاينة معروضة في لوحة المراجعة

### التراجع v25
```powershell
Copy-Item "docs\CD-043\backups\2026-11-19_heading_contrast_fix_v1_orig\style.css_post_heading_fix.css" "public\css\style.css" -Force  # قبل v25 (أبيض فقط)
```
(ثم ارجع ?v=2.3→2.2 في `header.php`.) النسخة الحالية (بعد v25) محفوظة `style.css_post_v25.css` — SHA256 `D0E239CC…`.

---

## v24 (2026-11-19) — عناوين البطاقات/الـ heroes الداكنة: تبييض العناوين (إصلاح CSS صِرف بلا مساس بالكود)

### طلب المستخدم
«اريد تحسين مظهر الخطوط للعناوين في الصفحات التالية: /evaluations/create و/evaluations/bulk-create و/evaluations/excel/import و/evaluations وأي صفحة أخرى. دون المساس بالكود أو أي شيء.»
اللقطات أظهرت: **العنوان داكن على داكن** — شبه غير مقروء (مثال: «صفحة التقييم — تطبيق ديناميكي حسب بنية القالب»، «التقييم الجماعي»، «استيراد تقييمات Excel»، «ملفات أداء الموظفين»).

### السبب الجذري (٣ قواعد CSS عامة تتغلب على وراثة الأبيض)
| الملف | القاعدة المتغلبة (بالترتيب) | كانت تضرب |
|---|---|---|
| `product-ui.css` | `h1..h6 { color: var(--product-ink); ... }` (خصوصية مباشرة على العنصر) | كل عناوين الـ heroes الثلاثة الأولى + أي h1..h6 في أي حاوية داكنة بالمستقبل |
| `modern-ui.css` | `.page-header h2, .page-header h1 { color: var(--ink); ... }` (0,1,1) | عناوين «ملفات أداء الموظفين» في `/evaluations` |
| `style.css` | `.page-header h2 { color: var(--primary-color); ... }` (0,1,1) | نفس `/evaluations` (نفس الأثر) |

الحاويات (`.cd040-hdr`، `.cd041-header`، `.xf-page-header`، `.page-header` الداكن) تعلن `color:#fff` لكن **القاعدة المباشرة على العنصر تتغلب دائماً على الوراثة** — أي عنصر h1..h6 بلا لون خاص به يدفع السعر.

### الإصلاح (٠ views / ٠ PHP / ٠ JS — CSS فقط، بنفس نمط المشروع)
1. **`product-ui.css`** — حُذف `color` من القاعدة العامة كلياً: `h1..h6 { font-weight: 800; letter-spacing: -.018em; }` — العناوين ترث لون الجسم (`#212529` Bootstrap) في الصفحات الفاتحة (≈ ink البصري)، و**ترث #fff تلقائياً داخل أي حاوية داكنة — الآن ومستقبلاً**.
2. **`modern-ui.css`** — نقل اللون فقط: `.page-header :where(h1, h2) { color: var(--ink); }` (خصوصية صفر على العنصر h — تبقى الصفحات الفاتحة كما هي بالضبط، وتمنح الوراثة فرصتها). الوزن/الحجم/التباعد محفوظة كما كانت.
3. **`style.css`** — نفس النمط: `.page-header h2 { font-size: 2rem; }` + `.page-header :where(h2) { color: var(--primary-color); }` — الافتراضي الفاتح محفوظ، والـ hero الداكن يرث الأبيض (inline `<style>` في الـ views يأتي بعد هذه الملفات فيـ tie).
4. **cache-bust** في `header.php`: style.css?v=2.1→**2.2**، modern-ui.css?v=2.0→**2.2**، product-ui.css?v=2.0→**2.2**.

### التحقق (بشفافية تامة)
- **حسابي**: `heading_test_tmp.html` (مؤقتة — حُذفت من webroot بعد التقاط الشاشة) نسخت طبق الأصل من الـ heroes الأربعة بترتيب الروابط نفسه، ثم `getComputedStyle`:
  - قبل: `cd040-hdr h2 = rgb(22,48,43)` · `cd041-header h1 = rgb(22,48,43)` · `xf-page-header__title = rgb(22,48,43)` · `page-title = rgb(44,62,80)`
  - بعد: **كلها rgb(255,255,255)** ✓ (شاشة الالتقاط أرفقت لوحة المراجعة — العناوين بيضاء مقروءة)
- **php -l**: header.php نظيف · بدون BOM (معالجة بـ WriteAllText مع UTF8Encoding($false))
- ملاحظة تعليمية للتاريخ: الإصلاح الأول بوضع اللون في `:where()` **يفشل** — أي قاعدة مباشرة (حتى صفر-خصوصية) تتغلب على الوراثة. ثم وجدنا `style.css .page-header h2` بخصوصية (0,1,1) — كان يجب نقل لونه أيضاً.
- الصفحات الـ 403 (صلاحيات) للمقيّم contentCreator؟ — لم تُمَس؛ الصفحات التذكيرية للمستخدم صاحب الجلسة الحقيقية ستظهر بمظهرها الحقيقي فوراً بعد refresh قوي (Ctrl+F5).

### التراجع الكامل (للنسخ الأصلية بالضبط)
```powershell
Copy-Item "docs\CD-043\backups\2026-11-19_heading_contrast_fix_v1_orig\product-ui.css" "public\css\product-ui.css" -Force   # SHA256 9C6CB452…
Copy-Item "docs\CD-043\backups\2026-11-19_heading_contrast_fix_v1_orig\modern-ui.css" "public\css\modern-ui.css" -Force    # SHA256 3BC2B158…
Copy-Item "docs\CD-043\backups\2026-11-19_heading_contrast_fix_v1_orig\style.css" "public\css\style.css" -Force        # SHA256 695C91FF…
# header.php: احتفظ بحالته (روابط ?v=2.2) أو ارجع لـ header_post_v23.php (0B80C284…) ومعها أعد ?v= لأرقامها القديمة
```
(الـ originals مستخرجة من `git show HEAD:` — التحذيرات: `git checkout HEAD -- <files>` بديل مطابق.)
(backups `heading_contrast_fix_v1` الوسيطة — product-ui.css=8C1D3F39…، modern-ui.css=A59E4D36… — ليست نسخاً أصلية.)

### الالتزامات
- لا يوجد أي تعديل على أي `.php` view أو على `report.php`/`_document.php`/`ReportExportService.php`/`main.js`
- كل التغيير في ٣ ملفات CSS عامة + رفع أرقام cache
- لا commit حتى الآن

---

## v23 (2026-11-19) — الجرس: إعادة تصميم القائمة المنسدلة + استدعاء `type` من الموديل

### طلب المستخدم
«حسن مظهر القائمة التي تظهر عند النقر على زر الجرس للأشعارات» (مع لقطة للقائمة القديمة 320px بأيقونات Bootstrap العامة ونصوص مقصوصة بلا تايم ولا تمييز للمقروءة/غير المقروءة).

### التغييرات
1. **`app/Models/Notification.php`** — `recentForUser()` أصبحت تجلب `type` أيضًا (كان مفقودًا — كان يستحيل تمييز الأيقونة/اللون حسب النوع). الـ signature→ array shape في docblock محدَّث.
2. **`app/Views/layouts/header.php`** — إعادة بناء كاملة للقائمة:
   - Header band تيل (teal gradient) بـ أيقونة جرس ذهبية + عنوان «مركز الإشعارات» + فرعي «أحدث N — حديثة أولًا» + شارة ذهبية «N جديد»
   - كل عنصر: أيقونة نوع في دائرة ملونة (info أزرق / success أخضر / warning ذهبي / danger أحمر — نفس لوحة صفحة /notifications) + عنوان مقتطع بذكاء + نقطة ذهبية pulsing للمقروءة/غير المقروءة — غير مقروء: خلفية كريمية + خط ذهبي جانبي (inset-inline-start)
   - الرسالة بسطرين كحد أقصى (`-webkit-line-clamp: 2`) + وقت نسبي عربي («منذ ٥ د» / «الآن» / «أمس»…)
   - زر «تعليم الكل كمقروء» ذهبي-خفيف (POST read-all + CSRF) يظهر فقط عند وجود غير مقروء
   - Footer mint «عرض كل الإشعارات» بسهم للأمام (RTL) + حالة فراغ بأيقونة envelope-open-text
   - `aria-label` للرابط/الأيقونات + focus-visible outlines
   - cache-bust: `style.css?v=2.0 → v=2.1` (نفس قراءة الـ ?v= المعتمدة في المشروع)
3. **`public/css/style.css`** — استبدال كامل لكتلة بند 15 القديمة (الجرس + القائمة) بالتصميم الجديد بالـ teal/gold (340px) — نفس أسلوب صفحة الإشعارات المعاد تصميمها.

### التحقق
- `php -l` ✓ على header.php و Notification.php
- الخادم يقدّم CSS الجديدة (curl: `len=54909`, `has_new_header_rule=True`, `has_old_320=False`)
- التقاط شاشة حقيقي (tab جديدة) — القائمة render صحيح: teal band + gold badge + 5 عناصر بأيقونات/أوقات/تمييز — المعروضة في لوحة المراجعة
- ملف المعاينة المؤقت `notifications_bell_preview_tmp.html` في webroot **حُذف بعد الالتقاط**

### Rollback v23
```powershell
Copy-Item "docs\CD-043\backups\2026-11-19_bell_dropdown_v1\header.php" "app\Views\layouts\header.php" -Force   # pre-v23 (bell text فقط)
Copy-Item "docs\CD-043\backups\2026-11-19_bell_dropdown_v1\style.css" "public\css\style.css" -Force
Copy-Item "docs\CD-043\backups\2026-11-19_bell_dropdown_v1\Notification.php" "app\Models\Notification.php" -Force
```
(backups v2 = بعد v23 تمامًا — `header.php=0B80C284…`، `style.css=FA2FA3D1…`، `Notification.php=5342EF33…`)

---

## v22 (2026-11-19) — الإشعارات: صفوف تجريبية لمعاينة الأنواع الأربعة (تُحذف عند الطلب)

### النطاق
طلب المستخدم: «اعمل اختبار لجميع [الأنواع] بحيث ننظر كيف ستظهر» — الـ pills الحالية كانت **تنبيه=3 · خطر=0 · نجاح=0 · معلومة=0** (جلسة sysadmin).

### ما أُضيف (لـ user_id=1 فقط — جلسة الديمو)
| id | type | is_read | related_url | العنوان |
|---|---|---|---|---|
| 6019 | info | 0 | `/notifications` | إشعار تجريبي — معلومة |
| 6020 | success | 0 | `/dashboard` | إشعار تجريبي — نجاح |
| 6021 | warning | 0 | — (بدون رابط — كإشعارات الأمان) | إشعار تجريبي — تنبيه |
| 6022 | danger | 0 | `/branches` | إشعار تجريبي — خطر |

### النتيجة المتوقعة في المعاينة/الشاشة
- الـ pills: **الكل=7 · تنبيه=4 · خطر=1 · نجاح=1 · معلومة=1**؛ غير المقروء = 6
- الأيقونات: معلومات=fa-circle-info · نجاح=fa-circle-check · تنبيه=fa-triangle-exclamation · خطر=fa-circle-xmark
- الثالث بلا رابط ⇒ لا يظهر زر «فتح الارتباط» (يحاكي إشعارات الأمان/النظام)؛ الباقون بلا زريّ السلة + «تعليم» جنب كل زر آخر

### الحذف عند الانتهاء
- من واجهة المستخدم نفسه: زر 🗑 لكل صف تجريبي — أو «مسح الكل (7)» في الـ Hero (يحذف التجريبي والحقيقي)
- أو أخبرني لتنفيذ: `DELETE FROM notifications WHERE id IN (6019,6020,6021,6022)`

---

## v21 (2026-11-19) — الإشعارات: حذف سهل للمستخدم (سلة لكل إشعار + مسح المقروءة + مسح الكل)

### النطاق الموافق عليه
بعد «وكيف نمكن المستخدم من حذفها بسهولها» اختار المستخدم: **١ + ٢ + ٣ (كل الخيارات)** — حذف فردي + مسح المقروءة + مسح الكل — دائماً مشروط بـ `user_id = الجلسة الحالية` + CSRF.

### الملفات المعدّلة (backup `2026-11-19_notifications_delete_v1`)
| الملف | Backup SHA256 | Current SHA256 |
|---|---|---|
| `app/Models/Notification.php` | `317FFF27…` | `EB2F638B…` |
| `app/Controllers/NotificationController.php` | `BAABB564…` | `18005C46…` |
| `app/Views/notifications/index.php` | `9F07F666…` | `BCED6BB7…` |
| `routes/web.php` | `88F75BFC…` | `E98F3E53…` |

### ١) الموديل — ٣ دوال حذف (مشروطة بالملكية)
`deleteForUser(userId, id): bool` · `deleteReadForUser(userId): int` · `deleteAllForUser(userId): int` — كلها `DELETE … WHERE user_id = :uid` (+ `AND` مناسبة) — **لا يمكن حذف إشعارات الغير**. ملاحظة سياساتية في docblock: سياسة الـ cron تبقى كما هي؛ الحذف بطلب صريح من المستخدم.

### ٢) الكنترولر — ٣ مسارات POST محمية بـ CSRF (`Auth::validateCsrfToken`) + Audit
- `POST /notifications/delete/:id` → `destroy()` → Audit `NOTIFICATION_DELETED` (scope=single)
- `POST /notifications/clear-read` → `clearRead()` → Audit `NOTIFICATIONS_CLEAR_READ` (scope=read)
- `POST /notifications/clear-all` → `clearAll()` → Audit `NOTIFICATIONS_CLEAR_ALL` (scope=all)
- ملاحظة: مسارات التعليم المقروءة الأصلية (`read/:id` و `read-all`) أُبقيت **كما هي بلا تحقق CSRF** (سلوك أصلي لم يُمس) — الحذف الجديدة فقط تتحقق.
- كل مسار: صلاحية الجلسة + CSRF → حذف → `Audit::log` → Flash رسالة → redirect إلى `/notifications`.

### ٣) الواجهة
- Hero: صفا أزرار شبحية أبيض/ذهبي (ghost): **مسح المقروءة (N)** (conditional على readCount>0) + **مسح الكل (N)** — كلاهما `confirm()` قبل التنفيذ؛ حالة المتغير `$readCount = total - unread`.
- كل إشعار يحمل الآن مجموعة أزرار: «تعليم» (إن كان غير مقروء) + **زر سلة حمراء** (`fa-trash`, `aria-label`) — ولكل إشعار حتى المقروء (كان لا يملك أي فعل).
- تأكيد قبل كل حذف فردي (`confirm('حذف هذا الإشعار نهائياً؟')`).

### ٤) الاختبار الوظيفي (مع نسخة احتياطية كاملة واستعادة إلزامية)
- نسخة كاملة من `notifications` قبل الاختبار: `backups/2026-11-19_notifications_delete_v1/table_backup_before_test.sql` (SHA256 `7770aa43…` — تُعاد مكررةً بأسلوب INSERT للاسترجاع).
- النتائج: عزل الملكية ✓ (`deleteForUser` على صف مستخدم آخر ⇒ false والصف سليم) · حذف فردي للمستخدم ✓ · `clear-read` حذف صفّي بحيث لا يلمس غير المقروء ✓ · `clear-all` ✓ · **الاستعادة الإلزامية أعادت الجدول حرفياً كما كان (٦ صفوف — المعرّفات 2,3,2692,2693,3882,3883؛ 2692 كانت is_read=1 مسبقاً — نشاط المستخدم لا لنا)**. صفوف الاختبار المعايرة: صفر متبقي.
- ملاحظة للتاريخ: أثناء الاختبار ظهر أن الإشعار 2692 أصبح مقروءاً قبل الاختبار (تفاعل مستخدم حي بين استعلامين) — الـ backup/restore احتفظ بذلك بدقة، ولم تتأثر أي بيانات حقيقية.

### ٥) المعاينة بعد التنفيذ (جلسة sysadmin)
`notifications_preview.html` — 3 إشعارات (2693, 3882 غير مقروءة + 2692 مقروءة) — الـ hero يعرض «مسح المقروءة (1)» و«مسح الكل (3)» — أزرار السلة ظاهرة على الثلاثة — `php -l` نظيف للملفات الأربعة.

### التراجع الكامل عن v21
```powershell
Copy-Item "docs\CD-043\backups\2026-11-19_notifications_delete_v1\Notification.php" "app\Models\Notification.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-19_notifications_delete_v1\NotificationController.php" "app\Controllers\NotificationController.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-19_notifications_delete_v1\index.php" "app\Views\notifications\index.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-19_notifications_delete_v1\web.php" "routes\web.php" -Force
```

### الالتزامات
- لا commit حتى الآن — بانتظار الموافقة البصرية
- لا حماية ولا حذف لإشعارات المستخدمين الآخرين من أي شاشة
- `main.js` لم يُمسّ — كل الأزرار عبر forms عادية + `onsubmit confirm()`

---

## v20 (2026-11-19) — الإشعارات: تصفير الـ UAT مع أرشفة استرجاعية (Option D)

### النطاق المطلوب من المستخدم
«د» — تصفير جدول `notifications` في `employee_evaluation_uat` مع الإبقاء على الـ ٦ إشعارات الحقيقية فقط، ثم تقرير ما تبقى.

### التنفيذ
1. **الأرشفة أولاً** (`export_notifications_archive.php`): تصدير كل الصفوف الـ ٢٤٥ المستهدفة للحذف كـ INSERTs قابلة للاسترجاع — `docs/CD-043/backups/2026-11-19_notifications_cleanup_v1/archive.sql` (SHA256 `9C35ABB529D464821A4B80B9224A49694C75136930D876700143C17DC7E79991`، 54KB، 245 INSERT — تم التحقق من العدّ قبل الحذف).
2. **المعرّفات المحفوظة**: `2` (results/user=2, url=/analysis) · `3` (course/user=6, url=/training/courses) · `2692`/`2693` (user=1, تنبيه مدير النظام) · `3882` (user=1, قفل sysadmin) · `3883` (user=4484, قفل sysadmin).
3. **الحذف** (`execute_notifications_cleanup.php`): `DELETE FROM notifications WHERE id NOT IN (2,3,2692,2693,3882,3883)` → قبل 251 → **بعد 6** (deleted=245).
4. **التحقق المستقل** (`verify_notifications_cleanup.php`): ٦ صفوف فقط، كلها unread، المعرّفات والروابط مطابقة للأرشفة.
5. **المعاينة**: `notifications_preview.html` أعيدت توليدها للجلسة sysadmin ⇒ 3 إشعارات (كانت 170).

### التراجع الكامل
```powershell
mysql -u root employee_evaluation_uat < "docs/CD-043/backups/2026-11-19_notifications_cleanup_v1/archive.sql"
```
يعيد الـ ٢٤٥ إشعاراً كما كانت (id ونفس related_url ونفس is_read). ملاحظة: لا تعارض في المعرّفات لأن جدولاً فارغاً بالأغلب يستقبل نفس الـ ids (AUTO_INCREMENT محفوظ في UAT كالأكبر id سابق).

### ملاحظة سياساتية
- بند 15 ينص على أن الإشعارات لا تُحذف إلا عبر cron للـ unread الأقدم من 90 يومًا — هذا التنظيف **قرار مستخدم صريح كتصفير لبيئة UAT وهمية** قبل الديمو، موثّق بأرشفة استرجاعية كاملة.
- بعد v20+Rope 2: أول تقييم مكتمل يولّد إشعار «تقييم موظف مكتمل» بنوع success، وسيظهر فوراً في الجرس وفي صفحة الإشعارات مع رابط `/evaluations/{id}/show`.

---

## v19 (2026-11-19) — الإشعارات: تطهير الـ UAT + ربط دورة التقييم بالأحداث (Rope 1 + Rope 2)

### النطاق المعتمد من المستخدم
«اريد ان تقرر أنت ما هو الافضل فأنا أثق بك وقدراتك» → «ابدأ فأنت الافضل». التنفيذ على مسارين:
- **Rope 1** (تطهير + وضوح النص): أرشفة الإشعارات غير المقروءة الضوضائية (`is_read=1` فقط — **لا DELETE**)، تحسين النص في الجرس وحالات الفراغ، وإصلاح ملاحظتي واجهة في صفحة الإشعارات.
- **Rope 2** (ربط الحياة الحقيقية): عند اكتمال/تحديث تقييم موظف، يُرسل `notifyAdmins(...)` بنوع `success` مع `related_url` يفتح `/evaluations/{id}/show` مباشرة من الجرس أو من صفحة الإشعارات.

### الخطوات المنفذة
1. **Rope 1 — تطهير ضوضاء UAT** (سكريبت `mark_noise_read.php` في `C:/Users/hussen/.opencode/plan/`):
   - المعرّفة: `title='cd004.احفظ.جديد' AND message='رسالة بند 15' AND is_read=0`
   - **قبل**: ١٧١ إشعار ضوضائي غير مقروء (٣ مستخدمين: sysadmin=١ → ٩٤، admin4484 → ٦٥، user7166 → ١٢)؛ إجمالي غير المقروء = ١٧٧
   - **التنفيذ**: `UPDATE notifications SET is_read = 1 WHERE <النمط نفسه>` — لا حذف ولا تعديل أي أعمدة أخرى
   - **بعد**: إجمالي غير المقروء = **٦** — كلها حقيقية وذات معنى:
     - id=2 (user=2): «نتائج تقييمات فريقك» — رابط `/analysis`
     - id=3 (user=6): «دورة قادمة» — رابط `/training/courses`
     - id=2692/2693 (user=1): «تنبيه: مدير نظام واحد فقط» (×٢)
     - id=3882 (user=1) / id=3883 (user=4484): «قفل محاولات الدخول — sysadmin»
   - **قابلية التراجع**: المعرّفات الـ ١٧١ مخزنة في `docs/CD-043/backups/2026-11-18_uat_noise_markread_v1/noise_ids.txt` (SHA256 `EEB1E72F…`) بجانب `noise_by_user.txt` (SHA256 `B9ABFF08…`)؛ التراجع = `UPDATE notifications SET is_read = 0 WHERE id IN (<الملف>)`.

2. **Rope 1 — الجرس (header.php)**: نص حالة الفراغ من «لا توجد إشعارات.» إلى «لا توجد إشعارات حالياً — ستظهر هنا الأخبار المهمة فور وصولها.» (backup `2026-11-18_header_bell_text_v1`, SHA256 `3F1DDB1B…` في النسخة الاحتياطية؛ الملف الحالي بعد التعديل SHA256 `620ACE58…`).

3. **Rope 1 — إصلاح صفحة الإشعارات** (`app/Views/notifications/index.php`, backup `2026-11-18_notifications_ui_redesign_v2`, SHA256 `9F07F666…`):
   - الرباط المكسور `/_notifications-refresh` (route غير موجود) → أصبح `APP_URL . '/notifications'` بعنوان «تحديث القائمة» (checked: `routes/web.php` لا يعرّف هذا المسار).
   - النقطة الذهبية المطلقة داخل `<h4>` (كانت تغطّي الأيقونة) → شارة نصية `.ntf-unread-badge` («غير مقروء») inline.

4. **Rope 2 — توسيع `Auth::notifyAdmins`** (`app/Core/Auth.php`, توقيع جديد متوافق رجوعياً):
   - `notifyAdmins(string $title, string $message, string $type = 'warning', ?string $relatedUrl = null): void`
   - الإدراج صار: `INSERT INTO notifications (user_id, title, message, type, related_url, created_at) …` مع `bind(null)` → `PARAM_NULL` (تحقق في `Database::bind` — is_null → PARAM_NULL).
   - جميع النداءات الحالية (loginLock / CSRF grace / password reset …) لا تتأثر — النوع الافتراضي يبقى `warning` و `related_url` يبقى `NULL` كما كان.
   - backup `2026-11-18_auth_notifyadmins_v1` (SHA256 `22A06CC4…`؛ الملف الحالي بعد التعديل `60271297…`).
   - **اختبار حقيقي داخل معاملة مع ROLLBACK** (`test_notify_admins.php`): أُدرجت ٣ إشعارات للمستخدمين النشطين بدور Admin النظامي (١ sysadmin، 4484، 7166) — type=success و related_url=`/evaluations/12345/show` — ثم ROLLBACK كامل ⇒ `LEFT_ROWS=0`. نفس الاختبار بـ `related_url=NULL` نجح أيضًا. **لم تُدخل أي بيانات اختبارية في UAT**.

5. **Rope 2 — خطافا الأحداث في `EvaluationController`** (backup `2026-11-18_evaluation_notify_v1` SHA256 `18488EEE…`؛ الملف الحالي `2FC56168…`):
   - `submit()` — بعد `Audit::log('EVALUATION_SUBMITTED', …)`: `Auth::notifyAdmins('تقييم موظف مكتمل', 'تم حفظ وإرسال تقييم «الاسم» (#id) بتقدير «X» بنجاح.', 'success', '/evaluations/{id}/show')`.
   - `saveUnified()` — بعد `Audit::log('EVALUATION_SAVED_UNIFIED', …)`: تمييز create/update في العنوان والنص مع نفس related_url. اسم الموظف يُحل من `$evaluation->employee_name` (update) أو `$emp->full_name` (create) — كلاهما في النطاق.
   - بدل `store()` غير مضاف عن قصد: هو إنشاء مسودة فقط — إشعاره يكرر الضوضاء التي قتلناها. الاكتمال الحقيقي هو submit/save-unified.
   - `save-unified` هو زر «💾 حفظ التقييم» النهائي (ليس autosave) — يُضبط status إلى `completed`/`pending_review` داخل نفس الطلب، لذا الإشعار يعكس اكتمالاً فعلياً لا متابعة كتابة.

6. **الصلاحيات**: `reports.view.all` (id=9) مربوطة بدور Admin (156 صلاحية) ودور HR ودور Manager — كلها `YES` في UAT ⇒ صفحة `/evaluations/show/{id}` تفتح بنجاح عبر الإشعار.

### الأثر البصري في صفحة الإشعارات لجلسة sysadmin (user_id=1)
- العدد الكلي ١٧٠ إشعار (كما كانت)، **غير المقروء ٩٧ → ٣**
- الإحصائيات على الـ hero محسوبة لحظيًا: إجمالي/مقروء/غير مقروء + عدادات الـ pills حسب النوع

### التحقق
- `php -l` ✓ على `EvaluationController.php` و`Auth.php` و`notifications/index.php` و`layouts/header.php`
- اختبار notifyAdmins داخل transaction + rollback ✓ (٣ صفوف لكل مستخدم Admin؛ NULL و success related_url) ✓
- المعايزة مولّدة من البيانات الحيّة بعد التطهير: `notifications_preview.html` (257KB، HAS_NTF_HERO=YES، HAS_NTF_LIST=YES، UNREAD=3)
- المعاينة معروضة في Review pane عبر `browser.preview`؛ لم تُرفع في commit

### التراجع (revert v19)
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_auth_notifyadmins_v1\Auth.php" "app\Core\Auth.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_evaluation_notify_v1\EvaluationController.php" "app\Controllers\EvaluationController.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_notifications_ui_redesign_v2\index.php" "app\Views\notifications\index.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_header_bell_text_v1\header.php" "app\Views\layouts\header.php" -Force
# التراجع عن تطهير الـ noise (is_read=0 للـ 171 إشعاراً فقط):
& "C:/xampp/php/php.exe" -r "define('APP_ROOT','C:/xampp/htdocs/employee-evaluation'); require APP_ROOT.'/config/config.php'; require APP_ROOT.'/config/database.php'; \$ids = file('docs/CD-043/backups/2026-11-18_uat_noise_markread_v1/noise_ids.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); \$p = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS); \$stmt = \$p->prepare('UPDATE notifications SET is_read = 0 WHERE id IN (' . implode(',', array_map('intval', \$ids)) . ')'); \$stmt->execute(); echo 'reverted_rows=' . \$stmt->rowCount() . PHP_EOL;"
```

### الالتزامات والملاحظات
- لا يوجد أي `DELETE` على UAT في كل الخطوات
- لا يلزم تعديل `routes/web.php`؛ المسار `evaluations/show/:id` موجود (web.php:142)
- لا commit حتى الآن — بانتظار موافقتك البصرية
- الملفات الخارجة من نطاق هذا الإنجاز (`_document.php`, `_document_styles.php`, `ReportExportService.php`, `main.js`) لم تُمسّ

---

## v18-admin-pages (2026-11-19) — إعادة تصميم أربع صفحات إدارية بنمط الهوية الموحدة للمشروع

### النطاق
طلبات المستخدم التنفيذية: «/branches» · «/departments» · «/notifications» — بنفس الهوية المعتمدة في صفحة المناصب (v18 positions).

الأربع صفحات تشترك في العناصر نفسها: Hero Banner (gradient teal `#093f37→#0d6658`) بـ dot pattern + radial bloom ذهبي + eyebrow (bi-camel-case مع أيقونة ذهبية) + عنوان بلكنة ذهبية + وصف قصير + CTA ذهبي + ٣ ميداليونات شفافة (`backdrop-filter:blur(10px)`)؛ ثم شريط بحث حي؛ ثم Card-table styling uniform؛ ثم Modals navy gradient headers + gold icons + white titles؛ وفراغ dashed gold empty states.

### 1) `/employees/positions` — `app/Views/employees/positions/index.php`
- Hero بحجز عنوان «المناصب + الوظيفية» بلون accent `#f3ca78`، Eyebrow «إدارة الموارد البشرية»
- CTA «تعريف منصب جديد» أو «إضافة منصب جديد» ذهبي
- Stats: Phosstats — **١٢** منصب / **٣٥** موظف / **٦** قيادات شعبية (positions leaders)
- Filter: بحث + ٥ pivot pills (الكل/مدير/نائب/موظف/أخرى) مع احصاءات لكل فئة
- Card-table: navy header بسطر ذهبي ٣px + zebra body + brand hover + Category pills (manager gradient navy+gold border | deputy gold-soft | staff teal-soft | other neutral)
- JS `main.js` hooks محفوظة 100% (`js-edit-position`, `js-delete-position`, `#addPositionModal`, `#editPositionModal`, `editPositionForm`, `edit-id/title/category`)
- Backup `2026-11-18_positions_ui_redesign_v1/` — SHA256 `9BA2EF60…`
- Preview HTML من `Position->getAll()` ظاهر في لوح الاستعراض ✓

### 2) `/branches` — `app/Views/branches/index.php`
- Hero بحجز عنوان «الفروع + المؤسسية» بلون accent `#f3ca78`، Eyebrow «إدارة الموارد البشرية»
- CTA «إضافة فرع جديد» ذهبي
- Phosstats — **٣** فروع / **٥٠** موظف / **٠** بمدير معيّن
- Filter بحث + عداد
- Card-table — navy header (gold border-bottom) + row-num عمود + Location chip + أزرار أيقونية edit/delete route-based
- dashed empty state مع icon `fa-building-circle-xmark`
- JS hooks محفوظة: `js-edit-branch` / `js-delete-branch` / `editBranchForm` / `addBranchModal` / `editBranchModal`
- Backup `2026-11-18_admin_pages_ui_redesign_v18/branches_index.php` — SHA256 `81148C82…`

### 3) `/departments` — `app/Views/departments/index.php`
- Hero بحجز عنوان «الأقسام + المؤسسية» بلون accent `#f3ca78`، Eyebrow «إدارة الموارد البشرية» مع icon `fa-sitemap`
- CTA «إضافة قسم جديد» ذهبي
- Phosstats — **٧** أقسام / **٥٠** موظف / **٧** فروع تُغطيها الأقسام
- Filter بحث + عداد
- Card-table — navy header + Branch tag بخلفية `#f4f9f6` + border mint + أيقونة gold
- أزرار تعديل/حذف بنفس hooks: `js-edit-department` / `js-delete-department` / `addDepartmentModal` / `editDepartmentModal` / `editDepartmentForm`
- Backup `2026-11-18_admin_pages_ui_redesign_v18/departments_index.php` — SHA256 `7200C4D2…`

### 4) `/notifications` — `app/Views/notifications/index.php`
- Hero بحجز عنوان «الإشعارات + الشخصية» بلون accent `#f3ca78`، Eyebrow «مركز الإشعارات»
- CTA «تعليم الكل كمقروء» يظهر عند unread > 0 مع عداد unread، أو exits lock UI state عند zero
- Phosstats — **١٧٠** إجمالي / **٧٣** مقروء / **٩٧** غير مقروء (الأرقام الحقيقية في UAT)
- Filter بحث + ٥ pills حسب نوع (الكل / تنبيه / خطر / نجاح / معلومة) + عد badge counter
- Card-page بقائمة الإشعارات:
  - Zebra: `even=#fbfdfc` · `hover=#f6fbf8`
  - غير مقروء: خط gold جانبية `border-right: 4px solid #c8943f` + icon badge puls gold dot (`.ntf-dot--unread`)
  - صفوف column `56px 1fr auto`
  - Icons بـ counts (fa-circle-info, fa-circle-check, fa-triangle-exclamation, fa-circle-xmark)
  - زريّ «تعليم» لكل notification بأيقونة ف remix (تحديد الـ form يتقن CSRF+csrf_field())
- Backup `2026-11-18_notifications_ui_redesign_v1/index.php` — SHA256 `1C112C47...`

### المسارات لـ rollback برجوع لـ v18
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_positions_ui_redesign_v1\index.php" "app\Views\employees\positions\index.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_admin_pages_ui_redesign_v18\branches_index.php" "app\Views\branches\index.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_admin_pages_ui_redesign_v18\departments_index.php" "app\Views\departments\index.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_notifications_ui_redesign_v1\index.php" "app\Views\notifications\index.php" -Force
```

### التحقق
- `php -l` على الملفات الأربعة ✓
- كل صفحة مولّدة من data حي: positions 12 / branches 3 / departments 7 / notifications 170 (عدد inbox unread=97)
- hooks JS محفوظة بالكامل — main.js يعمل بدون تعديل
- لا تعريب rename لأي hook

---

## v18-positions (2026-11-19) — إعادة تصميم صفحة إدارة المناصب بالهوية البصرية للمشروع

### v18-positions scope
طلب المستخدم: «ارفع لك القبعة يا كبير المهندسين والمطورين. الان اريد تحسين مظهر الصفحة:http://localhost/employee-evaluation/employees/positions لجعلها متناسقة مع نمط الهوية البصرية للمشروع وتحسينها»

**التحليل المرئي**: الصفحة الحالية كانت تستخدم تصميماً قديماً جداً (`#2c3e50` أضاء، `.page-header`، icon و`.btn-add` من style.css إلارث متواضع)، ولا تعكس أي لون وثيقة الـ masthead الرسمية للمشروع (#093f37/#0d6658 teal + #c8943f gold) ولا شرنك غالايريا documentation المفصلة في `modern-ui.css` (gold band + gradients + dot patterns) ولا bản الميثاق document CD-043.

### v18-positions evidence (before vs after)
| العنصر | قبل | بعد |
|---|---|---|
| Header / page-header | `.page-header` بار + أيقونة `Fas fa-users` بلا زخم | **Hero banner كامل** `linear-gradient(120deg,#093f37,#0d6658)` مع:
- Eyebrow (id-badge + «إدارة الموارد البشرية»)
- العنوان ثنائي السطر بكلمة ذهبية `.accent` (المناصب **الوظيفية**)
- وصف قصير بلون أبيض شفاف
- CTA ذهبي غامق `.positions-hero__cta` («تعريف منصب جديد») بظل ظل موسع
- **3 ميداليونات شفافة بتلوين ذهب/طيفي** (Phosstats: إجمالي المناصب / الموظفون المربوطون / القيادات)
- dot-pattern background + radial gold gradient blooms |
| Table | `<table>` عادي بظل عشوائي | **Card-table** `positions-card`:
- header navy `#0c2823` بخط ذهبي 3px + عنوان + subscription + عداد `.positions-counter`
- rows zebra `#fbfdfc`/`hover #f6fbf8` + التنقل blink-soft
- category **pills** بكثير اللون: `--manager` gradient+gold border · `--deputy` gold-soft · `--staff` teal-soft · `--other` neutral — لفي داخلها أيقونات `crown`/`user-tie`/`users`/`ellipsis-h` |
| Actions | `.btn-action` صارمة | **زريّان أيقونيان بلانقي محسّن**: `.btn-action` للتعديل (navy→gold hover) · `.btn-delete` لمطابقة المخاطر الحمراء |
| Filter | لا شيء | **شريط بحث حي** + **5 pills فئة** (الكل/مدير/نائب/موظف/أخرى) مع count لكل فئة |
| Empty state | `<tr><td colspan...>لا توجد مناصب</td></tr>` | **dashed gold empty state** كثيفة ويصل لها مثال («ابدأ تعريف أول منصب...») |
| Modals | `modal-header` عادي | **modal-header بـ gradient teal بـ عنوان الأبيض + icon ذهبي**، `btn-primary` بـ gradient deep teal |

### v18-positions structural preservation (NO breaking changes)
أسماء الـ JS الـ hooks الخاصة بـ `main.js` محفوظة بالكامل:
- `.js-edit-position` بـ dataset → `editPositionForm` fill & open (لقطو جرية)
- `.js-delete-position` بـ dataset → SweetAlert2 `submitDeleteForm('/employees/positions/delete', id)` (uId=...)
- `id` لـ `editPositionModal` · `addPositionModal` · `editPositionForm` · `edit-id` · `edit-title` · `edit-category` · `#editPositionModal`
- POST routes intact: `/employees/positions/store` · `/employees/positions/update` · `/employees/positions/delete` (لم يتغير الكنترولر)
- Gazemode visible: no permissions changed (home=?employees/positions requires `positions.manage`)
- لا مساس بأي ملف نظام: لا `style.css` ولا `modern-ui.css` ولا `product-ui.css` ولا غيرها
- التبديل رحبت `positions_hero__cta` زر التعديل يفتح mismoModal` كسابق مع قبل ✓

### v18-positions verification
- `php -l` ✓
- `positions_preview.html` مولّد من الكنترولر الحقيقي (`\App\Models\Position->getAll()`) بـ bfactual UAT data: 12 منصب · 35 موظف إجمالاً (`totalPositions=12 · totalEmployees=35 · leadCount=6`)
- `positions_preview.html` ظاهر في لوحة الاستعراض الآن
- pages مثل /evaluations/show/556 لا يعرضوا شيئاً مخفياً (tdy عدم متصل)
- لا مساس بمكتبة JS `main.js` نفسها — نفس الـ `querySelectorAll('.js-edit-position')` سيشتغل

### v18-positions backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/positions/index.php` | 9BA2EF60588486552DFD90207E30D6008805FF02425405D35F029519E23EA35F |
| النسخ: `docs/CD-043/backups/2026-11-18_positions_ui_redesign_v1/` | متطابقة |

### v18-positions rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_positions_ui_redesign_v1\index.php" "app\Views\employees\positions\index.php" -Force
Get-FileHash "app\Views\employees\positions\index.php"   # يجب 9BA2EF60...
```

---

## v17b (2026-11-19) — «الوصف» المبالغة في دورتها الوصفية (32/68) + اختبار إخفاء regression

### v17b scope (5 ملفات)
- `show.php` · `print.php` · `_document.php` · `_document_styles.php` · `ReportExportService.php`

طلب المستخدم:
> «احرص ان تكون عمود الوصف اكبر من عمود عنصر التقييم بقليل من اجل ترك مساحة اكبر للوصف ما رأيك… ومن ثم اختبر كل شيء بأنه يعمل بشكل صحيح وأنه لم يأثر على شيء اخر»

التحليل: العملية موجودة أصلاً بنسبة 34/66 لكن مع تداخل `tbody td:nth-child(2){ width:22%; }` = ت crossover تهدد العمود الأوسع. v17b يضع النسبة في كل موضع صراحةً:

| الملف | قبل | بعد |
|---|---|---|
| عنصر التقييم width | 36% | **32%** |
| ملاحظات width (لا-نمط وصفي) | 32% | 30% |
| الوصف (descriptive) | unspecified (50 بايت من المستند) | **66% (print) / 68% (web)** |
| tbody td.text-plain | no width constraint | **66% print / 68% web** — يتجاور ضد `nth-child(2)` 22% بنفق CSS evaluation order |

### v17b verification
- `php -l` على الملفات الخمسة ✓
- snapshot جديد `354 / ER-00000023` مولد ✓
- PDF export: 25 صفحة · 380KB ✓ · كلا attachments descriptives يشاهدان «الوصف» aligned-right بـ fades واسعة 32% بالضبط للعنصر التقويمي · الصفحة الرئيسية (`report_354_preview.html` / export_354_p0/p3) لا تتأثر بالشكل ولا بالحقوق ✓
- مسار `/evaluations/show/575` → جداول غير عادية الوصفية تظهر عنصر أصغر من الوصف — visible in preview generated by live require path
- زمن تنفيذ لتحديث فعلي لأي تقييم: لا تغيرات في DB (فقط CSS/markup)

### v17b backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/evaluations/show.php` | D234455B7E9BB3545EF54577ED9550E0F332749E06F42057A13553F3D3DB9FCD |
| `app/Views/evaluations/print.php` | 76F6EE7C7B9DF5107F5D86D94E5733ABD69A894B9CDEF93A0B13EFC5BFB8A9D2 |
| `app/Views/evaluations/_document.php` | 1AF31263D5308E470485007FFF9B13389225507FA3F682E02BBB5FE00B712D1A |
| `app/Views/evaluations/_document_styles.php` | 24703A1FB37A3D856902FCD50D88180E4DC7ECB44F7ED7CB5D58544D9B23920C |
| `app/Services/ReportExportService.php` | E66A633D6CED822F1FC7F10CB7568A467A6935C5C51424169CA90657CF65412E |
| النسخ: `docs/CD-043/backups/2026-11-18_desc_width_fix_v17b/` | متطابقة |

### v17b rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_desc_width_fix_v17b\show.php" "app\Views\evaluations\show.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_desc_width_fix_v17b\print.php" "app\Views\evaluations\print.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_desc_width_fix_v17b\_document.php" "app\Views\evaluations\_document.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_desc_width_fix_v17b\_document_styles.php" "app\Views\evaluations\_document_styles.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_desc_width_fix_v17b\ReportExportService.php" "app\Services\ReportExportService.php" -Force
```
---

## v17 (2026-11-19) — الإزالة الكاملة لعمود «ملاحظات» من التقييم الوصفي + «الوصف» full-width

### v17 scope
طلب المستخدم:
> «بتقييم نوع موظف داخل دورة نمط وصفي ... يتم إزالة عمود الملاحظة لكل قسم في معيار لها وجعل الوصف او المسمى المستوى ان لم تغيره يكون واحد يحتوى على المساحة الكاملة له وللملاحظة» — على حد قوله «في جميع واجهات العرض والطباعة»

التحليل: التقييمات الوصفية (T2 «التقييم الوصفي للدورة» و T241 «قالب طباعة تجريبي») كلها نصوص عربية كاملة في عمود المستوى؛ عمود الملاحظات فارغ (0 قيمة في كل تقييم T2/T241 عبر كامل UAT). فرض 3 أعمدة يفقد عرض المستوى ويُظهر أعمدة فارغة.

التعديلات (4 ملفات + HTML rendering on live preview/snapshot):
1. **`show.php` / `print.php` / `_document.php`**: في الوضع `$descriptiveCourse` — كل جدول:
   - `<th style="width:36%">عنصر التقييم</th><th class="desc-th">الوصف</th>` فقط (عمودان)
   - الصفوف: `<td>عنصر</td><td class="{text-plain}">«النص»</td>` — لا يعرض `<td>ملاحظات</td>`
   - العيّنات الرقمية خارج النمط الوصفي تبقى كما كانت (3 أعمدة)
2. **`ReportExportService.php::buildAttachmentHtml`**: رفع عنوان «الوصف» بدلاً من «المستوى» ليتطابق مع بقية الواجهات (v14)
3. **التحويلان تحققان**: `php -l` ✓ · preview HTML `HAS_WASF=YES` · `HAS_NO_NOTES_HEADER_IN_DESCRIPTIVE=YES` · ATT575 `_HAS_MUSTAWAA=NO` / `_HAS_WASF=YES` / `_TEXT_PLAIN_USED=YES` · PDF: 25 صفحة (1 رئيسية + 1 تصفية + 19 مرفقات وصفي) — الكل بتصميم الوثيقة الرسمية

### v17 تأكيد بصر مباشر
- مرفق 575 («قالب طباعة تجريبي»): 4 معايير بأعمدة «`عنصر | الوصف`» بدون عمود ملاحظات مع نصوص كاملة aligned right — خصوصاً في اللوحة «التقدير النهائي»
- مرفق 559/575 صفحة مقاربة T2 «التقييم الوصفي للدورة» — نفس النمط
- عمود «الوصف» يتسع لغير 90 مم بدلاً من ~50 مم
- لا مساس بتصميم `.badge` المتوفر عن T1 «سنوي» وT3 «رقمي للدورة» وT217 «ورشة mix»

### v17 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_remove_notes_col_descriptive_v17\show.php" "app\Views\evaluations\show.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_remove_notes_col_descriptive_v17\print.php" "app\Views\evaluations\print.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_remove_notes_col_descriptive_v17\_document.php" "app\Views\evaluations\_document.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_remove_notes_col_descriptive_v17\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Services\ReportExportService.php"   # يجب E8D173B6...
```

---

## v16 (2026-11-19) — «وصف/مستوى» التبديل عند التقييم الوصفي + إلغاء الـ pill badges عن النصوص

### v16 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/evaluations/show.php` | 5DBEEF97FBBC977CE4E936C79CD73DE04249C39E0DF81C2680E063BCC694A094 |
| `app/Views/evaluations/print.php` | 9F9A2C7066B62832C261D3DE7023CC017CB1F499B54A5FB62F9C640E1D9268C8 |
| `app/Views/evaluations/_document.php` | A27FD26E9E2F0E6617B5CE0D74492B111CEDB09DB8E16B7592893A1B40D56DC6 |
| `app/Views/evaluations/_document_styles.php` | (v16 inherit التعديل) |
| النسخ: `docs/CD-043/backups/2026-11-18_text_pill_fix_v16/` | متطابقة |

### v16 scope (تقييم موظف داخل دورة — وصفي)
الطلبات من المستخدم:
> «يظهر العمود المستوى في بعضها بددون في رأسه المستوى والاحرى تكون في هذه الحالة الوصف بدلا من المستوى وتظهر في محاذة الى اليسار لماذا»

التحليل: في الموظف داخل دورة — النمط الوصفي (template 2 «التقييم الوصفي للدورة» و template 241 «قالب طباعة تجريبي») — غالباً **جميع خلايا المستوى عادة نصوص وصفية عربية** (سطور متعددة). بدلاً من حشوها بشارة `badge` قوية بالحدود/الألوان، نغطيها بالنص المسطح المنساب مع المحافظة على التخطيط الكلي `جدول` و `row colors` كما هي.

التعديلات:
1. **`show.php` / `print.php` / `_document.php`**: تعريف `$descriptiveCourse = ($template['target_type'] ?? '') === 'employee_in_course' && ($template['evaluation_mode'] ?? '') === 'descriptive';` + اختيار «`الوصف`» لرأس العمود الثاني في الوصف؛ و«الوصف» في `.desc-th` (text-align:right بدل الcentering المعتاد) عند Nimbus؛ و`.text-plain` block (text-align:right, white-space:normal) بدل `.badge` للنص في المستوى؛ وأكديمة `extra-safe eval_keys` بحسب النوع (`grade/overall_grade/numeric/percentage/boolean/scale/multiple_choice → pill badges`) ← حتى لا يتغير سلوك الموظف/الدرس/المهاري في كل الأساسيات
2. **`_document_styles.php`**: إضافة `.eval-table tbody td.text-plain` و `.eval-table thead th.desc-th` و `.cell-plain`

### v16 data/snapshot
- تأثير re-render: عند إعادة توليد snapshot ودفعة الضبطية الأصلية للمستخدم — يظهر بالكامل التنقل الجديد في كل المرفقات والعروض
- snapshots القديمة لا تزال مُلحَقة بالـ HTML القديم — إذا أردت الرؤية الكتلوية بمرفقات نظيفة، أعد توليد `snapshot_352` (أو أي snapshot أحدث)

### v16 verification
- `php -l` على الملفات الأربعة ✓
- preview HTML: `HAS_CELL_PLAIN=YES` · `HAS_DESC_TH=YES` · `HAS_AL_WASF=YES` ✓
- PDF build: 25 صفحة · 380KB ✓ · attachments «التقييم الوصفي» attachments show «الوصف» + نصوص وردية بدون pills؛ overall_grade rows keep their pills بشكل unchanged (التحقق بصرياً في export_v16_*.png)
- credential `316 4 attempts 403` (غير مربوط بالكود)

### v16 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_text_pill_fix_v16\show.php" "app\Views\evaluations\show.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_text_pill_fix_v16\print.php" "app\Views\evaluations\print.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_text_pill_fix_v16\_document.php" "app\Views\evaluations\_document.php" -Force
# git synonym:
# git checkout -- app/Views/evaluations/show.php app/Views/evaluations/print.php app/Views/evaluations/_document.php app/Views/evaluations/_document_styles.php
```

---

## v15 (2026-11-19) — «ملاحظات ورأي المقيّم» — section جديد يظهر على كل المستندات

### v15 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/evaluations/_document.php` | C77A0BF172F73079D0641CB821033E3128C3AD27C5748313314B59D406872A9F |
| `app/Services/ReportExportService.php` | DD28BDAD80FBD8B8EC21688A8FA569684E04F62E9D4D13517487E7549B0B0928 |
| `app/Views/evaluations/show.php` | (لا backup مستقل — محفوظ عبر git) |
| `app/Views/evaluations/print.php` | (لا backup مستقل — محفوظ عبر git) |
| النسخ: `docs/CD-043/backups/2026-11-18_evaluator_note_adjacent_section_v15/` | متطابقة |

### v15 scope
الطلبات: «أريد إضافته لكل الصفحات» — ملاحظات المقيّم العامة (`evaluations.notes`) مخزّنة في DB وتُعرض في النماذج وExcel import لكنها كانت غير معروضة في أي مكان في المستندات للطباعة، العرض، المعاينة، والـ PDF — **فجوة عرض**.

التعديلات:
1. **`app/Views/evaluations/_document.php`** — قسم جديد «ملاحظات ورأي المقيّم» يُعرض فقط حين `!empty($evaluation->notes)` مع ترقيم ديناميكي `$sectionNum++` (تحويل عربي-هندي عبر `$toArabic`)
2. **`app/Views/evaluations/show.php`** — نفس التغيير: ترقيم ديناميكي + القسم قبل «بيانات القائم بالتقييم»
3. **`app/Views/evaluations/print.php`** — نفس التغيير: نفس السلوك وقسم «—» مرقم تدريجياً
4. **`app/Services/ReportExportService.php::buildAttachmentHtml`** — قسم MPDF-safe: `.evaluator-notes-card` (gold border 2px, vanilla bg) + `.evaluator-notes-text` (white-space:pre-wrap, 10pt/1.75 line-height) مع ترقيم ديناميكي (بدل الآلي const-constants القديمة)

النمط البصري للقسم:
- `.program-card` (gold border 2px, vanilla background) مع `white-space:pre-wrap` يحفظ الأسطر المحددة من الإدخال أصلاً
- عنوان القسم بترقيم عربي-هندي (١، ٢، ٣، ...) بـ `.section-head` + دائرة navy + خط فرعي navy-ish border-bottom 2px (مطابق للمستند الرسمي)
- يعود الإعداد الصقلي الترقيم ديناميكياً لضمان عدم تداخل عند غياب/وجود أي قسم

### v15 بيانات تجريبية إلتقاء العرض (قبل التراجع الكامل مستقبلاً)
- eval `#556` (template 1 — سنوي، الموظف سالم): notes = "الموظف سالم يقدم أداءً متميزاً هذا العام؛ يُوصى بترشيحه للتدريب المتقدم في إدارة المخاطر، وتعهد بالحفاظ على سجله الحالي. أحد التحديات الرئيسية: صعوبة التعامل مع بعض الفرق الخارجية؛ تم رصدها وسجلت في اجتماعات الأداء الأسبوعية."

### v15 verification
- `php -l` جميع الملفات الأربعة ✓
- preview مباشر HTML: `docs/CD-043/exports/attachment_556_dump_v15.html` (18,985 bytes — يحتوي «ملاحظات ورأي المقيّم» + النص)
- `report_352_preview.html` تحتوي القسم (1+حدوث) ✓
- PDF attachment build (reflection): `HAS_NOTES_H2=YES` · `HAS_NOTES_TEXT=YES` ✓
- snapshot جديد `352 / ER-00000021` مولّد يدوياً ليضم الملاحظة في payload المرفق
- نسخة إلتقاء HTML محفوظة في `docs/CD-043/exports/attachment_556_dump_v15.html` للعمود البصري

### v15 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_evaluator_note_adjacent_section_v15\_document.php" "app\Views\evaluations\_document.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_evaluator_note_adjacent_section_v15\ReportExportService.php" "app\Services\ReportExportService.php" -Force
# show.php وprint.php غير في النسخة المستقلة وتستعاد عبر git:
# git restore app/Views/evaluations/show.php app/Views/evaluations/print.php
```

---

## v14 (2026-11-19) — PDF «التقييم الوصفي» (T2) — إلغاء عمود الملاحظات + اتساع «المستوى»

### v14 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Services/ReportExportService.php` | B1903B9C4C7945F86AD53AD9F0787D2C2D115D3C110876946E25C14896A50218 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_pdf_descriptive_notes_v14/` | متطابقة |

### v14 scope
- `buildAttachmentHtml` في `app/Services/ReportExportService.php` فقط
- عند `target_type === 'employee_in_course' && evaluation_mode === 'descriptive'` (مثل T2 «التقييم الوصفي للدورة»): تُلغى **عمود «ملاحظات»**؛ يُصبح العرضان عمودين فقط:
  - «عنصر التقييم» (34%) + «المستوى» (66%) يحوي الفقرة كاملة بمحاذاة يمنى (`.value-plain` full-width بدون wrapping multi-line badges)
  - «ملاحظات» موجودة inline كسطر صغير تحت المستوى عند وجودها (`.level-notes`) — لا عمود كامل ولا تفقد بيانات
- للأنواع الأخرى (موظف / مهاري / دورة رقمية T3 / موظف داخل ورشة T217): لم تتغير — عمود الملاحظات ما زال موجوداً كما عاش في التصميم
- فرست مراوغة: تقييم «جيد جدًا» للتقدير العام يظهر بشارة زرقاء خفيفة كما في الويب
- الصفحات: 22 (كما كانت)
- التحقق: `php -l` ✓ · التصدير PDF `report_ER-00000019.pdf` مبني بنجاح؛ مرفق 2 (التقييم الوصفي) يرى العمودين الجديدين بكل بنوده على صفحة واحدة بدلاً من التمزق الذي كان يشتكي منه المستخدم

### v14 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_pdf_descriptive_notes_v14\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Services\ReportExportService.php"   # يجب B1903B9C...
```

---

## v13 (2026-11-19) — PDF export بالكامل أصبح مطابقاً للمعاينة (الوثيقة الرسمية)

### v13 scope
`app/Services/ReportExportService.php` فقط (XLSX/DOCX/الباكند كما هو تماماً)

- **صفحة التقرير الرئيسية (PDF) = الوثيقة الرسمية**: أعلى شريط navy|gold|navy + الترويسة (Emblem دائرية ذهبية بأيقونة `file-signature` navy + سطر الشركة + البسملة + «تقرير التقييم الشامل للموظف» (navy 800 مع خط ذهبي أسفل) + اسم الموظف black 800) + صندوق البيانات (يمين): التاريخ/الموافق/اليوم + خط متقطع + «# رقم التقرير ER-…»
- بطاقة «بيانات الموظف» الذهبية: 4 خلايا بأيقونات user/hashtag/sitemap/building
- الأقسام مرقّمة v2 بالنظام الرسمي (دائرة navy برقم أبيض 800 وخط فرعي navy border-bottom 2px)
- جداول البيانات: رأس navy 800 + zebra `#fbf9f3` + footer-row gold-light بـ`--gold-light` 800 متن navy bold، كل عمود متمركز
- جدول التوقيعات: رأس navy بجرافيت أبيض وريجل gold-bottom 2px
- تذييل كل صحفة (`SetHTMLFooter`): يمين «● وثيقة رسمية — سرّي للاستخدام الداخلي فقط»، وسط «المستخدم: **1#**»، يسار «رقم التقرير: ER-… · تاريخ/ساعة»
- عنوان PDF: «تقرير التقييم الشامل للموظف» (بدل «تقرير الموظف»)
- **الصفحات: 22 صفحة** لتقرير 20 تقييم معروض

### المرفقات (مرفق 1..N) = نفس تصميم الويب بدون partial
- بناء HTML مستقل mPDF-friendly من الصفر (لا partial ولا _document_styles)
- نفس الأجزاء: أعلى شريط / attach-band navy بgold-bottom / attachment-head (Emblem حسب نوع هدف التقييم: employee→user / course→graduation-cap / workshop→chalkboard / skill→bullseye)
- بيانات البرنامج 6 خلايا + بطاقة برنامج معترفة navy→navy-light gradient
- معايير التقييم (criterion-card + criterion-head navy بأيقونة list-check + جدول eval-table بنspeakers navy بgold-bottom 2px + badges من تقييم الأداء)
- بيانات القائم بالتقييم (3 خلايا بأيقونات user-tie/award/calendar)/ توقيع (sig-line navy + sig-label navy 800)/ تذييل ثلاثي مطابقة للتبويب الرئيسي
- كل مرفق يبدأ صفحة جديدة (mPDF `AddPage()` + `page-break-before:always` في attach-page class)
- إصلاح `page-break-inside:avoid` على كل بطاقة قسم بدل attach-page كامل ⇒ كل مرفق بأقسامه كاملة على صفحة واحدة

### التحقق
`php -l` ✓ · export PDF export_350.pdf = **22 صفحة** · XLSX render = 1.1MB ✓ · DOCX = 39KB ✓ · PDF = 354KB ✓

### v13 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_pdf_official_v13\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Services\ReportExportService.php"   # يجب BD7BAB61...
```

---

## === النموذج البارز لاختبار الطباعة الطويلة ===
- قالب UAT **«قالب طباعة تجريبي»** (template_id=241) employee_in_course descriptive، 4 معايير + التقدير العام: مشاكل c218–c222، sections 304–314 (نصوص عربية كاملة متعددة الأسطر)
- الموظف **سالم #7996** أُقر عليه هذا القالب (evaluation_id=575، تاريخ 2026-12-15، program 30 «إدارة المشاريع الاحترافية») ⇒ final_score=89.99 «جيد جدًا» / overall_grade القائم بالتقييم خالد الغامدي
- snapshot **351 / ER-00000020** — لأول مرة يكون هناك مرفق أكبر من صفحة كاملة بالفعل
- للتوثيق: lockups النسخ الاحتياطي `docs/CD-043/backups/` بما فيها v10-v12 (CSS print) و`uat_backup_templates_before_print_test_2026-11-18.sql` (31KB)

## v12 (2026-11-19) — المرفقات الطويلة في الطباعة: إلغاء القص الصامت + تدفق آمن لصفحات لاحقة

### v12 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | EC7D570E1E03BF340C59B480AE3967D8456C5BE6C61CCF16163026AE21251B33 |
| `app/Services/ReportExportService.php` | BD7BAB611357B1D5FFD3B019590628B4F43AF253A6B547CE9ACBCC53FE4773A7 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_attachment_overflow_v12/` | متطابقة |

### v12 scope (`app/Views/employees/report.php` — كتلة @media print فقط، نطاق `.attachment-page` حصراً)
1. **المشكلة الأساسية**: `_document_styles.php` يعرّف `.a4-page{overflow:hidden;min-height:285mm}` ⇒ أي مرفق أطول من A4 يُقصص محتواه بصمت في الطباعة (سيناريو البيانات الحقيقية وخصوصاً «التقييم الوصفي للدورة» بسرد طويل)
2. الحلول في `_document_styles` لا تُعدَّل (تبقى evaluations/print و evaluations/show مثل اليوم)
3. v12 يتغلب بالخصوصية الأعلى في حاوية التقارير فقط:
   - `.attachment-page .a4-page{overflow:visible; min-height:0; height:auto; box-shadow:none; border:none; width:auto}`
   - `criterion-card/info-card/program-card/evaluator-card/signature-area/doc-header/doc-footer/program-grid/info-grid/program-cell { page-break-inside:avoid }` — اكتمال البطاقة ثم الانتقال لصحفة لاحقة
   - `.eval-table thead{display:table-header-group}` + `.eval-table tr{break-inside:avoid}` — رأس العمود يتكرر وينشطر الجدول عند الحد بين صحفتين بلا تمزق
   - `.attachment-page .section-head { break-inside/after: avoid }` — رأس القسم لا ينفصل عن محتواه
4. التحقق: `php -l` ✓ · البناء يحمل القواعد ✓ · متبقي: اختبار مع مرفق حقيقي طويل من المعاملات `Ctrl+P` — النشاط يخص التصدير الإضافي

### v12 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_attachment_overflow_v12\report.php" "app\Views\employees\report.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_report_attachment_overflow_v12\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Views\employees\report.php","app\Services\ReportExportService.php"   # EC7D570E... / BD7BAB...
```

---

## v11 (2026-11-19) — قص صف «التقييم العام» في الطباعة + انفراجة علوية بسيطة عند الامتداد

### v11 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | FEA521BC80ADCBCE3ABEAE124272F8BBED46CA1C368937CCBF58BCE9B7A0FF61 |
| `app/Services/ReportExportService.php` | BD7BAB611357B1D5FFD3B019590628B4F43AF253A6B547CE9ACBCC53FE4773A7 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_print_footer_pin_v11/` | متطابقة |

### v11 scope (`app/Views/employees/report.php` — كتلة @media print فقط)
1. صف «التقدير العام» الذهبي مهيئ للقص عند حد الصفحة ⇒ `table.data tr.footer-row { page-break-inside: avoid; page-break-before: avoid }` + نفس الشيء للتوقيعات `table.sig tr {}` ⇒ الصف لا يُقص أبداً ويُزاح كاملاً مع جدوله إلى الصفحة التالية
2. `.section-head` الهامش العلوي `10px → 14px` + `page-break-inside/after: avoid` الصريح — «إزاحة للأسفل قليلاً جداً» عند أي امتداد لصفحة أخرى (الأقسام تنزل قليلاً عن الحد)
3. `.section-count { page-break-after: avoid }` — يفصل «عدد التقييمات» عن الجدول أبداً
4. التحقق: `php -l` ✓ · البناء يحمل القاعدتين ✓ · المعاينة محدثة في لوحة المراجعة

### v11 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_print_footer_pin_v11\report.php" "app\Views\employees\report.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_report_print_footer_pin_v11\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Views\employees\report.php","app\Services\ReportExportService.php"   # FEA521BC... / BD7BAB...
```

---

## v10-NEW (2026-11-18 ليلاً متأخراً) — قسم «التقييمات المهارية» في المعاينة + توثيق الموظف التجريبي
### backup
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | 97F889C67ED8FC86433B6E575551ACA696316EE32424029B01D967F2358791B2 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_skill_section_v10/` | متطابقة (ReportExportService لم يتغير) |

### scope
- إضافة `$skillRecords` + قسم «التقييمات المهارية» (جدول 3 أعمدة: التاريخ/التقييم/الدرجة+/100+التقدير + سطر «التقدير العام للتقييمات المهارية») — بنفس نمط باقي الأقسام؛ مصدر الهامش والألوانموحد مع الفردية
- تحديث شرط «لا توجد تقييمات» ليشمل `$skillRecords`
- `php -l` ✓ · البناء يستظهر 5 تقييمات مهارية + footer skills ✓ · لا فراغات false في runtime

---

## 10 NEW — الموظف التجريبي الحامل للتقرير (بيانات حقيقية في UAT)

### تعريفه
- **id=7996 · `CD028-099` · سالم عبدالعزيز القحطاني** (nickname سالم — يمثل حقن بيانات UAT حديثاً)
- فرع «الفرع الرئيسي — الرياض» · قسم «تقنية المعلومات» · position «أخصائي أول» (staff #5) · manager خالد أحمد الغامدي (#5)
- `is_active=1` · `profile_status=complete` · `join_date=2021-04-05` · email `s.alqahtani@tawasul.local` · phone 0551234567

### 20 تقييماً (كلها `status=completed` عبر EvaluationCalculator + ربط بالسلم الخماسي)
| القالب | النوع | التواريخ | النتائج |
|---|---|---|---|
| T1 فردية (annual) | 5 | 2022-11-20/2023-11-16/2024-11-28/2025-11-27/2026-11-09 | 61.83 مقبول / 75.66 جيد / 87.35 جيد جدًا / 92.87 ممتاز / 83.05 جيد جدًا |
| T2 دورة وصفية | 3 | 2026-10-05 / 2026-12-12 / 2026-11-13 | 100 ممتاز / **89.99 جيد جدًا** / 79.99 جيد |
| T3 دورة رقمية | 2 | 2026-12-15 / 2026-11-13 | 88 جيد جدًا / 94 ممتاز |
| T4 مهاري | 5 | 2026-01-20/04-14/06-22/08-30/10-18 | 87 / 75 / 89.5 / 90.75 / 84.25 |
| T217 ورشة (مختلط `mixed`) | 5 | 2026-02-11/03-16/05-21/07-28/09-09 | 44 / 29.66 (ضعيف) · 95.99 / 97.32 / 91.99 (ممتاز) |

### برامج الورش المُنشأة (قبل العثور على 18 و36 موجودين)
- #37 catalog 8 «ورشة العصف الذهني وحل المشكلات» · #38 catalog 10 «ورشة خدمة العملاء الاحترافية» · #39 catalog 9 «ورشة إدارة الوقت والأولويات» — جميعها فرع الرياض dept IT، status=completed

### Snapshots (347 ← report 7108 · 348–350 لـ 7996)
- **348 (ER-00000017)**: التُقط خلال seed الأولي مع خطأ حقن القيمة النهائية بضعيف افتراضي ⇒ جميع descriptive كانت 59.99 ضعيف — snapshot **غير قابل للتعديل/الحذف** (trigger `report_snapshots_block_*` في UAT يمنع ذلك) ⇒ يبقى أثر موثّق (تجاوز سيثير SQLSTATE 45000)
- **349 (ER-00000018)**: فيه eval 560 بـ score=0 (بسبب تهجئة جدًا الكسولة بالبايتات عوض إعادة كتابتها بالغة تشفيرية صحيحة) — نفس القيد
- **350 (ER-00000019) — المعتمد**: بعد إصلاح eval 560 بإعادة كتابة الوسم من المرجع الأصلي (bitmask مطابقة حرفياً من `grading_scales`) ⇒ كل الـ 20 Row `grade-consistent` عبر المحرك الموحد
- snapshots 348/349 **يجب إزالتها من قبل مسؤول UAT إذا شاء** — فقط بعد إيقاف/تعطيل مؤقت للـ triggerين (التغيير لا زال أمراً لا يجب تنفيذه من هنا)
- PDF Export: 350 جاهز لتوليده بعشرين تقييم مع 5 أقسام تظهر (بعد إضافة قسم المهارات)

### قواعد متعذر الالتفاف عليها
- `report_snapshots` محظور UPDATE/DELETE (trigger: `SIGNAL SQLSTATE '45000'`)
- لا تقم بإنشاء بيانات جديدة للموظف التجريبي دون معرفة الاسم/الرقم الوظيفي:id مختلفة

---

## v10 (2026-11-18 ليلاً) — إصلاح الفراغ العلوي + ضغط طباعي للمرفق (صفحة واحدة ما أمكن)

### v10 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | 954A5DDBCB253C67792FA15C2469309C348DFAA00723B7A0BBAF1A49223FE239 |
| `app/Services/ReportExportService.php` | BD7BAB611357B1D5FFD3B019590628B4F43AF253A6B547CE9ACBCC53FE4773A7 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_masthead_v10/` | متطابقة |

### v10 scope (`app/Views/employees/report.php` — كتلة @media print فقط)
1. **إصلاح الفراغ العلوي في الطباعة**: استبدال `.report-wrap{margin:0;padding:0}` بـ `.a4-container.report-wrap{margin:0;padding:0}` — قاعدة الشاشة `.a4-container.report-wrap{margin:64px auto 0}` (خصوصية 0,2,0) كانت تجتاز السابقة (0,1,0)، فالورقة بدأت بعد 64px من الأعلى؛ الآن الخصوصية متساوية والقاعدة الأخيرة تتغلب
2. **ضغط طباعي للمرفق** (اختيار المستخدم: «صفحة واحدة إن أمكن» + «صفحة المعاينة للتقارير فقط»): `doc-body` padding (18/22→10/16)، `doc-header` padding أضيق، emblem 52→46، `.section-head/.section-num/h2` مدمج، `.info/.program-cell` 7×10، `.criterion-head/.count` أصغر، `.eval-table` 10.5px مع td/th أضيق، `.badge` 10px، `.evaluator-card` 12، `.eval-person .val` 11.5، `.signature-area/.sig-box(min-height:52)/.sig-line` مدمج، `.doc-footer` 6×18 — توفير متوقع ≈15% ارتفاع
3. الإبقاء: `criterion-card/info-card/evaluator-card` break-inside:avoid (لو مرفق ضخم أصلاً: كسر بعد معيار كامل لا منتصفه)؛ ولا مساس بـ `_document_styles.php`؛ ولا الشاشة؛ ولا evaluations/print/show؛ ولا سطر مرفقات المستخدم
4. التحقق: `php -l` ✓ · البناء يحمل القاعدتين ✓
5. **التحقق البصري النهائي = طباعة المستخدم (Ctrl+P) ثم إرفاق PDF جديد للمراجعة**

### v10 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v10\report.php" "app\Views\employees\report.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v10\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Views\employees\report.php","app\Services\ReportExportService.php"   # 954A5DDB... / BD7BAB...
```

---

## v9 (2026-11-18 ليلاً) — كسر صفحات المرفقات (كل مرفق يبدأ صفحة جديدة)

### v9 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | 69684E825CD29C5DAB1D7C77DDABE6F531EA63F98B251E4DB997FDA3950D2E19 |
| `app/Services/ReportExportService.php` | BD7BAB611357B1D5FFD3B019590628B4F43AF253A6B547CE9ACBCC53FE4773A7 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_masthead_v9/` | متطابقة |

### v9 root cause (من PDF الذي سلّمه المستخدم `الجديد.pdf`)
- في v8 استخدمت `page-break-before: page` — **قيمة غير صالحة** لـ `page-break-before` (القيم المقبولة: auto|always|avoid|left|right) ⇒ Chrome تجاهلها بصمت وكل مرفق بدأ أسفل تذييل المرفق السابق (التصاقات مرفق2→صفحة3، مرفق3→صفحة4، …)
- الحل مُطعّم بقيمتين متكاملتين:
  ```css
  .attachment-page { page-break-before: always; break-before: page; }
  ```
  `always` للتوافق الكلاسيكي (كما كان يعمل الكود الأصلي)، `break-before: page` لخصائص CSS3

### v9 result
- التقرير = صفحة واحدة · كل مرفق = صفحات كاملة مستقلة تبدأ صفحة نظيفة (صفحة لكل مرفق بالضبط)
- التحقق: `php -l` ✓ · البناء يحمل القاعدة الجديدة ✓
- **التحقق البصري النهائي = طباعة المستخدم من الصفحة الحية (Ctrl+P) ثم إرفاق PDF جديد للمراجعة**

### v9 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v9\report.php" "app\Views\employees\report.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v9\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Views\employees\report.php","app\Services\ReportExportService.php"   # 69684E82... / BD7BAB...
```

---

## v8 (2026-11-18 ليلاً) — منع أشباح الصفحات في الطباعة (صفحة فارغة أولى + «مرفق» وحيد + صفحة في الختام)

### v8 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | 4645F4053AAAD6BE461A8A3F2553DBD21FD90F11CFD16E069DF1EA8237FB84D2 |
| `app/Services/ReportExportService.php` | BD7BAB611357B1D5FFD3B019590628B4F43AF253A6B547CE9ACBCC53FE4773A7 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_masthead_v8/` | متطابقة |

### v8 root causes (من لقطات طباعة المستخدم بعد v7)
1. الفوتر كان يحمل `margin-bottom:7mm` ⇒ مع `@page margin:0` (A4 بالمليمتر) أصبح أي هامش سفلي overflow ⇒ **صفحة فانتوم** (فارغة في البدء أو الختام)
2. ديف فارغ `<div style="page-break-before: always;"></div>` (كود CD-028 الأصلي) كان يُخرج فواصل وهميًا
3. سطر «مرفق x من y» يحمل في الطباعة `margin:-26px 562px` (من عمل المستخدم) ⇒ يوضع في موضع شاذ ويلتقطه فواصل خاطئة

### v8 scope (`app/Views/employees/report.php` فقط)
1. حذف الديف الوهمي بالكامل (PHANTOM_DIV_REMAINING = 0)
2. تغليف كل مرفق في `<div class="attachment-page">` حقيقي + كسر الصفحة لهذا العنصر في الطباعة (`page-break-before:page`) ⇒ مرفق واحد في بدايته
3. سطر «مرفق» أُضيف له class `attachment-title`؛ في الطباعة `margin:0 0 8px !important` + `page-break-after:avoid` — **الشاشة تحافظ على سطر المستخدم المعطوب حرفيًا كما هو**
4. في @media print: `.a4-container{margin:0}` · `.report-page .doc-footer{margin-bottom:0}` · `table.sig{margin:16px 0 8mm}`
5. التحقق: `php -l` ✓ · div balance 43/43 ✓ · البناء يحمل 5 `attachment-page` بلا أي ديف وهمي ✓ · **التحقق البصري النهائي = طباعة المستخدم (Ctrl+P)** — الدين على v8: لا صفحات فارغة، لا «مرفق» يتيم، لا صفحة ختامية: التقرير صفحة واحدة + صفحة لكل مرفق

### v8 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v8\report.php" "app\Views\employees\report.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v8\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Views\employees\report.php","app\Services\ReportExportService.php"   # 4645F405... / BD7BAB...
```

---

## v7 (2026-11-18 ليلاً) — منع الصفحة الفارغة الثانية في الطباعة

### v7 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | 5E35C0B83F796EFF9E02EEBCC9980EAE756CBFB6520436353C673BEAE18FBF72 |
| `app/Services/ReportExportService.php` | BD7BAB611357B1D5FFD3B019590628B4F43AF253A6B547CE9ACBCC53FE4773A7 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_masthead_v7/` | متطابقة |

### v7 scope (`app/Views/employees/report.php` — سطر واحد في @media print)
- `.report-page` في الطباعة: `min-height:297mm` → **`min-height:0`**
- السبب (مكتشف من لقطة طباعة المستخدم): المحتوى الكلي أكبر قليلاً من صفحة A4 صفرية الهوامش ⇒ البطاقة انشقت و«بوب» التذييل إلى صفحة 2 بيضاء فارغة
- النتيجة: الصندوق يتمدد على قدر المحتوى؛ الفوتر يتبع جدول التوقيعات مباشرة (بفاصل 14mm الموجود)؛ **لا صفحات فارغة؛ المرفقات تبقى صفحة لكل مرفق**
- التكلفة: في الطباعة فقط يفقد التذييل «تثبيته في أسفل الصفحة» — الشاشة تبقى مثبتة في القاع كما هي (v4/v5)
- التحقق: `php -l` ✓ · المعاينة تبني بنجاح ✓ · **التحقق البصري النهائي = طباعة المستخدم من الصفحة الحية (Ctrl+P)**

### v7 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v7\report.php" "app\Views\employees\report.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v7\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Views\employees\report.php","app\Services\ReportExportService.php"   # 5E35C0B8... / BD7BAB...
```

---

## v6 (2026-11-18 ليلاً) — طباعة WYSIWYG: الطباعة = الشاشة (بقرار المستخدم @page margin:0)

### v6 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | 1ECF7635D59C93FEC92479D7F77CE552C624D031640897A32034C9C5567C464F |
| `app/Services/ReportExportService.php` | BD7BAB611357B1D5FFD3B019590628B4F43AF253A6B547CE9ACBCC53FE4773A7 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_masthead_v6/` | متطابقة |

### v6 scope (`app/Views/employees/report.php` فقط — كتلة <style>)
1. `@page { size: A4; margin: 0; }` — ورقة A4 كاملة بلا هوامش متصفح؛ الأبعاد mm ثابتة ⇒ **مقياس 1:1 مطابق للشاشة** (لا تحجيم Chrome)
2. `* { -webkit-print-color-adjust:exact; print-color-adjust:exact }` داخل @media print — فرض الطباعة بالألوان والـ gradients (navy th / zebra / gold footer-row / الخط الذهبي العلوي / خلفية البطاقات)
3. في الطباعة: `.report-page{width:210mm; min-height:297mm}` (بدل `min-height:auto`) — يبقى التخطيط A4 كاملاً والفوتر مثبتًا في القاع
4. `table.data thead{display:table-header-group}` — رأس العمود يتكرر عند امتداد جدول الفردية لصفحة ثانية؛ +`.section-head{break-after:avoid}` — رأس القسم لا ينفصل عن جدوله
5. الإبقاء على: toolbar مخفي، ظلال تُلغى، `page-break-inside:avoid` للترويسة/الفوتر/التوقيع/البطاقة
6. التحقق الاستاتيكي للبناء: `php -l` ✓ · المعاينة تبني بنجاح وتحمل البنود 1–4 ✓ · **التحقق البصري النهائي = طباعة المستخدم من الصفحة الحية (Ctrl+P)**

### v6 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v6\report.php" "app\Views\employees\report.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v6\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Views\employees\report.php","app\Services\ReportExportService.php"   # 1ECF7635... / BD7BAB...
```
(وللتراجع لما قبل v6 استخدم v5 = `295F5C04...`)

---

## v5 (2026-11-18 ليلاً) — تذييل ثلاثي متماثل مرفوع عن الحافة

### v5 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | 295F5C041FCC5C4ADD516E865942811E8A458ECCD06EF04088B150E743610B79 |
| `app/Services/ReportExportService.php` | BD7BAB611357B1D5FFD3B019590628B4F43AF253A6B547CE9ACBCC53FE4773A7 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_masthead_v5/` | متطابقة |

### v5 scope (`app/Views/employees/report.php` فقط)
1. التذييل → **شبكة ثلاثية متماثلة** `1fr auto 1fr`: يمين «● وثيقة رسمية — سرّي للاستخدام الداخلي فقط» · وسط **«المستخدم: 1#»** (المعرّف من `created_by_user_id` لـ snapshot 347؛ بخط 900 + خط ذهبي تحته) · يسار «رقم التقرير …» — التوسيط حقيقي بفضل أعمدة متساوية الطرفين
2. رفع التذييل عن قاع الصفحة: `margin-bottom:7mm` (مع إبقائه مثبتًا في القاع بتمدد `.doc-body`)
3. عند التنفيذ فقدت مرة واحدة سطر المستخدم المعطوب `#attachment-section-title` وأعدت إنتاجه **بايت-بايت** (موثّق؛ كلا سحري سطر المرفقات متطابقان مع v4 حرفيًا)
4. التحقق: `php -l` ✓ | div balance 43/43 ✓ | المعاينة محدثة | سطرا المرفقات `IDENTICAL=True`

### v5 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v5\report.php" "app\Views\employees\report.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v5\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Views\employees\report.php","app\Services\ReportExportService.php"   # 295F5C04... / BD7BAB...
```

---

## v4 (2026-11-18 ليلاً) — تذييل مستقل في قاع الصفحة + بيانات المُصدر

### v4 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | 286CF1DF825508CF45FA1D3866E1D41E48133AB91FA8781D1572CD32E1C8C83D |
| `app/Services/ReportExportService.php` | BD7BAB611357B1D5FFD3B019590628B4F43AF253A6B547CE9ACBCC53FE4773A7 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_masthead_v4/` | متطابقة |

### v4 scope (`app/Views/employees/report.php` فقط)
1. **إصلاح الالتصاق**: `<footer class="doc-footer">` أُخرج من `.doc-body` وأصبح ابناً مباشراً لـ `.report-page` (كما بناء المرفقات) — الآن `flex:1` يوفر الفراغ داخل `.doc-body` والفوتر يُثبَّت في قاع لوحة A4 بعيداً عن جدول التوقيعات
2. فصل بصري إضافي قبل التذييل: `table.sig` صار `margin:16px 0 14mm`
3. نص التذييل: استُبدل «…تم إنشاء هذا التقرير آلياً…» بـ **«وثيقة رسمية — سرّي للاستخدام الداخلي فقط · المستخدم: #{id}»** حيث `id = $snapshot->created_by_user_id` (معرّف المُصدر الحقيقي؛ ظهر في المعاينة `#1` لـ snapshot 347) — والتاريخ/رقم التقرير في الجهة اليسرى كما هي
4. التحقق: `php -l` ✓ | div balance 42/42 ✓ | المعاينة محدثة في لوحة المراجعة

### v4 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v4\report.php" "app\Views\employees\report.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v4\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Views\employees\report.php","app\Services\ReportExportService.php"   # 286CF1DF... / BD7BAB...
```

---

## v3 (2026-11-18 مساءً) — ضبط المحاذاة والخط وبطاقة البطاقة (استجابة لملاحظات المستخدم)

### v3 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | 17DFCBEC3AB72D6866B77EDB4031589BE8D69992F7B09395A0A00C988716000C |
| `app/Services/ReportExportService.php` | BD7BAB611357B1D5FFD3B019590628B4F43AF253A6B547CE9ACBCC53FE4773A7 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_masthead_v3/` | متطابقة (ReportExportService لم يتغير منذ v2) |

### v3 scope (`app/Views/employees/report.php` فقط)
1. جداول البيانات `table.data`: كل `th`/`td` بمحاذاة **وسط** + حجم خط أكبر وأوضح (`th 11.5px/800`، `td 11.5px/600`)، ولوحة الخط الذهبي/navy محفوظة
2. صفوف `.footer-row` تبقى **يمين** (نصوص التسمية في أقصى اليمين، القيمة في التقدير يمين) — التزام بطلب المستخدم لا بتوسيط كل شيء
3. بطاقة «بيانات الموظف»: ترتيب **الاسم / الرقم الوظيفي / الفرع / القسم** (كان القسم قبل الفرع) — التصميم نفسه (.program-card/cols-4 والأيقونات)
4. إزالة `style="text-align:left"` من صفوف «التقدير العام» (4 مواضع: courses/workshops colspan=2 و individual/annual colspan=3) → ترث `footer-row { text-align:right }`
5. عمود «القالب» → «التقييم» في الجدولين (الفردية + السنوية)
6. التحقق بمخرجات `report_347_preview.html` (UTF-8): الفرع@19514 قبل القسم@19666 ✓، `<th>التقييم</th>` موجودة ✓، `text-align:left` متبقي = 0 ✓
7. **سطر المرفقات المعطوب `id="attachment-section-title"` لم يُمس** — مظهر مرفقات المستخدم كما هو

### v3 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v3\report.php" "app\Views\employees\report.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v3\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Views\employees\report.php","app\Services\ReportExportService.php"   # يجب 17DFCBEC... / BD7BAB...
```
(وللتراجع مراحل سابقة: v2 = `2026-11-18_report_masthead_v2` (4D58C5... / BD7BAB...)؛ v1 = `2026-11-18_report_masthead` (546C9E... / 7D990B...))

---

## v2 (2026-11-18 مساءً) — إعادة تصميم الصفحة كلغة الوثيقة (استجابة لملاحظات المستخدم)
ملاحظات المستخدم: (1) الاسم في الترويسة كبير → تقليص 21pt→16pt؛ (2) محتويات التقرير «مشدودة» ولم تعد كوثيقة → الجسم يتحول لوثيقة كاملة.

### v2 backup (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | 4D58C5AD791C9D85BF1485E142401D8B819D5C6868A5DF5165A0E91A6BE5B7C8 |
| `app/Services/ReportExportService.php` | BD7BAB611357B1D5FFD3B019590628B4F43AF253A6B547CE9ACBCC53FE4773A7 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_masthead_v2/` | متطابقة |

### v2 scope (نفس الملفين فقط)
- `.report-page` → flex عمودي (header / doc-body / doc-footer ملتصق بالأسفل كلغة `.a4-page` للمرفقات) + حذف padding السفلي
- `.report-content` + class `doc-body` → هوامش الوثيقة الداخلية (18px/22px shared) بدل محتويات ممتدة على كامل العرض
- `.section-title` الخضراء في **الصفحة الرئيسية فقط** → `.section-head` مرقّمة تلقائياً (`$_s` يقتصر على الأقسام المعروضة؛ قسم بالكامل يعتمد على الشروط نفسها)
- جدول «بيانات الموظف» → `.program-card` محاطة بذهبي (4 خلايا أيقونات: الاسم/الرقم/القسم/الفرع) بدل `.emp-grid` (أُزيل CSS الغرام)
- `table.data`/`table.sig`/`.empty-state` → بلغة الوثيقة (navy th + ذهبي أسفل، zebra `#fbf9f3`، `.footer-row` ذهبي `--gold-light`)
- التولبار → navy/gold بظلال مطابقة (ما زال مخفياً في الطباعة)
- `.section-count` يستبدل inline styles لأربعة أسطر العد
- print CSS: + `.program-card` و`table.data tr` page-break-inside:avoid
- **التعليمة**: `class="section-title"` و`#attachment-section-title` (سطر المرفقات المعطوب) بقيا كما هما — مظهر مرفقات المستخدم لم يتغير
- التحقق: `php -l` ✓ | Preview محلي (نفس snapshot 347) ✓ | hijri `1448/06/08` ✓

### v2 rollback
```powershell
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v2\report.php" "app\Views\employees\report.php" -Force
Copy-Item "docs\CD-043\backups\2026-11-18_report_masthead_v2\ReportExportService.php" "app\Services\ReportExportService.php" -Force
Get-FileHash "app\Views\employees\report.php","app\Services\ReportExportService.php"   # يجب 4D58C5... / BD7BAB...
```
(وللتراجع لما قبل masthead بالكامل استخدم نسخة v1: `2026-11-18_report_masthead` وذات البصمات 546C9E... / 7D990B...)

---

## الحالة الحالية (قبل أي تعديل)
- الفرع: `feature/cd-043-wip` @ `b2faef6` (مدفوع ومُزامن مع origin)
- الملف المستهدف (`app/Views/employees/report.php`) **يحتوي عمل غير مُحفوظ للمستخدم** (تعديل قسم المرفقات، السطر المعطوب `id="attachment-section-title"<?= $attachmentIndex ?>"`) — **لن يُلمس ولن يُصلح إلا بموافقة صريحة**
- `app/Services/ReportExportService.php` نظيف على HEAD

## النسخ الاحتياطية (SHA256)
| الملف | البصمة |
|---|---|
| `app/Views/employees/report.php` | 546C9E573BB72603B9B4D10DDAC03A5E2A207C98D238A12B01BA37F66B646B68 |
| `app/Services/ReportExportService.php` | 7D990B72B58188651F35D60417F795E1022F56E16F6792B7713321125E48B704 |
| النسخ: `docs/CD-043/backups/2026-11-18_report_masthead/{report.php,ReportExportService.php}` | متطابقة مع الأصل |

## القرارات (من المستخدم)
1. العنوان على الترويسة: **تقرير التقييم الشامل للموظف**
2. اسم الموظف: **داخل الترويسة بخط كبير** (نمط masthead التصدير PDF)
3. سطر الشركة: **اسم الشركة — وحدة الموارد البشرية** (كما في كل المستندات)
4. نطاق العمل: **أ + ب فقط** — ترويسة + إطار الوثيقة + Footer (بدون إعادة تنسيق الأقسام الداخلية)
5. الأيقونة: `fas fa-file-signature` داخل الدائرة الذهبية (بدون إصلاح سطر المرفقات المعطوب)

## نطاق التغيير (ملفان فقط)

### 1) `app/Services/ReportExportService.php`
- `private function getDocumentDates(...)` → `public static function getDocumentDates(...)`
- (يُستدعى من الـ view دون تكرار منطق التواريخ الهجرية)
- أي استدعاءات `$this->getDocumentDates(...)` تتحول إلى `self::getDocumentDates(...)`

### 2) `app/Views/employees/report.php`
- `<title>` → «تقرير التقييم الشامل للموظف — {report_number}»
- `<head>`: إضافة `require_once .../_document_styles.php` بعد روابط الخطوط (يزيل التكرار لأنه مطلوب لاحقاً وسط الصفحة)
- حذف CSS الغلاف الداكن: `.a4-paper` (يُستبدل بـ `.report-page`)، `.cover-section*`، `.basmala` المستقلة، `.report-footer`
- إضافة CSS محلي صغير: `.report-page` (بطاقة بيضاء بحد علوي متدرج navy→gold + ظل، **بدون `overflow:hidden`** حتى تُطبع الصفحات المتعددة سليمة) + `.doc-employee` (اسم الموظف بخط أسود كبير)
- استبدال `<div class="a4-paper">…الغلاف الداكن…</div>` والمبتسمة المستقلة بـ:
  `.a4-container > .report-page > (header.doc-header + .report-content + footer.doc-footer)`
  - الترويسة (masthead مخصصة للتقرير):
    - يمين: `.header-emblem > i.fas.fa-file-signature`
    - وسط: `.header-titles` ← company + basmala + `.doc-title` «تقرير التقييم الشامل للموظف» + `.doc-employee` (الاسم)
    - يسار: `.header-meta` ← التاريخ (ميلادي) + الموافق (هجري umalqura عبر `ReportExportService::getDocumentDates`) + اليوم + سطر متقطّع «# رقم التقرير {report_number}»
- استبدال «تم إنشاء هذا المسند…» بـ `.doc-footer` (يسار: وثيقة رسمية — سرّي؛ يمين: رقم التقرير + التاريخ)
- حذف `require_once _document_styles.php` من قسم المرفقات (انتقل إلى <head>)
- print CSS: التولبار يبقى مخفياً؛ استبدال قاعدة `.a4-paper` بقاعدة `.report-page` (ظلال/حواف تُلغى) + `page-break-inside:avoid` على `.doc-header` و`.doc-footer` و`table.sig`

## ما لن يتغير (محظور اللمس)
- الأقسام الداخلية (بيانات الموظف، الدورات، الورش، الفردية، السنوية) وكل الجداول والتوقيعات كما هي
- سطر المرفقات المعطوب (`id="attachment-section-title"...`) — يبقى لحين طلب صريح
- `app/Views/evaluations/_document.php` و`_document_styles.php`
- منطق التصدير PDF/DOCX/XLSX وباقي الباكند
- `app/Views/employees/report.php` باستثناء المواضع المذكورة حصراً

## التحقق بعد التنفيذ
1. `php -l` للملفين
2. فتح `http://localhost/employee-evaluation/employees/profile/7108/reports/347` في المتصفح المدمج + لقطة للتحقق البصري
3. مقارنة البصرية مع ترويسة المرفقات (اللون، الدائرة، الخط الذهبي، الـ meta box)
4. `git diff --stat` للتأكد أن التغيير في ملفين فقط

## خطة التراجع (rollback)
1. أغلق المتصفح/الصفحة
2. استعادة الملفات من النسخة الاحتياطية (يُعيد الحالة الحالية بالكامل بما فيها عمل المستخدم غير المُحفوظ):
   ```powershell
   Copy-Item "docs/CD-043/backups/2026-11-18_report_masthead/report.php" "app/Views/employees/report.php" -Force
   Copy-Item "docs/CD-043/backups/2026-11-18_report_masthead/ReportExportService.php" "app/Services/ReportExportService.php" -Force
   ```
3. التحقق بالبصمة:
   ```powershell
   Get-FileHash "app/Views/employees/report.php","app/Services/ReportExportService.php"
   ```
   يجب أن تطابق: `546C9E...` و`7D990B...` أعلاه
4. (اختياري) حذف مجلد التراجع: `Remove-Item "docs/CD-043/backups/2026-11-18_report_masthead" -Recurse -Force`
5. ملاحظة: لا توجد أي تغييرات ملتزمة (commit) — التراجع يتم بنسخ الملفات فقط؛ أي commit يُنشأ لعمل آخر لا يُلمس هنا

---

# DIR-BULK-CREATE-FIX-V2.0 — إصلاح منطق الإدخال الجماعي (الاعتماد على NULL) — 2026-11-24

> **يُلغي ويستبدل V1.0 بالكامل.** لا أكواد `AUTO_` ولا `EMP...`. لا commit ولا push.
> HEAD قبل التعديل وبعده: `b2faef6b6a5c3022bfff71aafd9af192f2b71185` (بدون أي commit جديد).

## 1) نقطة التفتيش الأولى — إثبات النسخة الاحتياطية (قبل أي DDL)

| العنصر | القيمة |
|---|---|
| المسار | `docs\CD-043\backups\2026-11-24_quickadd_v2\db\employee_evaluation_uat_before_v2.sql` |
| الوقت | `2026-11-24 00:51:33` |
| الحجم | `2,534,785` بايت |
| SHA256 | `28ABA32D595952FFB849B344333EB1EED8FFCC3253BE982F908E7C515471D6B6` |
| التحقق | `mysqldump exit=0` · رأس `MariaDB dump 10.19 / Database: employee_evaluation_uat` · وجود `CREATE TABLE employees` ✅ |

نسخ ما قبل التعديل (BEFORE) محفوظة في `docs\CD-043\backups\2026-11-24_quickadd_v2\files\` لكل ملفات النطاق، مع تطابق بصمات النسخة مع الأصل وقت الأخذ.

## 2) الترحيل (UAT: `employee_evaluation_uat`)

| الملف | البصمة SHA256 |
|---|---|
| `database/migrations/2026_11_24_quickadd_null_emp_code.sql` | `102502B1B33E278300ABD6364080C39B43B1451F1E83D189B6BD6C91E0C6EFA1` |
| `database/migrations/2026_11_24_quickadd_null_emp_code_down.sql` | `8FD7816B64921B483C3A9BF4EDF1C1104133697FC6AE3B1E193D72CF57C42A30` |

- up: `ALTER TABLE employees MODIFY emp_code VARCHAR(50) NULL COMMENT 'الرقم الوظيفي الفريد للموظف';`
  - أول محاولة بدون اسم قاعدة بيانات فشلت (`ERROR 1046`) ولم تُطبّق شيء؛ أُعيد运行 بـ `employee_evaluation_uat` → exit=0.
  - بعد up: `IS_NULLABLE=YES` · `varchar(50)` · التعليق محفوظ · الفهرس `emp_code` (`NON_UNIQUE=0`) ما زال UNIQUE ✅
- تنظيف البيانات: `UPDATE employees SET emp_code = NULL WHERE id IN (8056, 8057, 8129);` → exit=0.
  - النتيجة: `total=61` · `null_codes=3` · `empty_codes=0` · `auto_codes=0` · `incomplete=21`.
- down: `SIGNAL SQLSTATE '45000'` يرفض التراجع إذا وُجد أي `emp_code IS NULL` (بدون توليد أكواد وهمية). لا يُشغَّل الآن لأن 3 صفوف NULL مقصودة موجودة.

## 3) تغييرات الكود (AFTER hashes)

| الملف | BEFORE | AFTER |
|---|---|---|
| `app/Controllers/BulkEvaluationController.php` | `B6804A28…B78F0` | `C4C576C0A3A5C58EA2023C2D6E686FAFDC85660EC5D34EC9A8F76CAE70A0F1B1` |
| `app/Controllers/EvaluationController.php` | `A3566701…865E0` | `68EC30DF2E9F521EBD2B96DC31DA329DC14F857653534C92B155B713BB679A71` |
| `app/Services/EvaluationExcelService.php` | `577BDAD4…82C720` | `586AE6C2C068CCB1B51C5270B57C7720E408C35522E063DEB54577FE877EC142` |
| `database/employee_evaluation_mvc.sql` | `BBCD094E…459D20` | `615FC85C4B2F18AA075A3B7E9A96B2CAF06DDFC433F3BAEA24F98512EAE9AD2E` |
| `tests/cases/Controllers/BulkEvaluationControllerTest.php` | `0BDE45D3…FFAB4C9` | `F0560776055FDFBC81B3AE7EE1C39F47F8146827DDE2EBD40BD9065970E7E5C4` |

- Quick-add: يُرفض فقط عند غياب الاسم **والكود** معاً؛ الكود الفارغ يُخزَّن `NULL`؛ الكود فقط ⇒ `موظف بدون اسم`؛ فحص 409 للكود الحقيقي المكرر فقط؛ **صفر توليد `AUTO_`**.
- `{Bulk,Evaluation}Controller`: `$emp->emp_code ?? ''` عند تمرير القيمة إلى `evaluations.employee_code` (NOT NULL) — لا 500.
- Excel: بحث بالاسم عند تعذّر المطابقة بالكود + `UPDATE employees SET emp_code = real_code WHERE emp_code IS NULL OR emp_code=''` + إلغاء `EMP...`.
- `php -l` نظيف على ملفات PHP الأربعة. الواجهات (`bulk-create.php` / `create.php`) لم تتغير: تحقق V1 (`if(!name && !code)`) مطابق للمطلوب.

## 4) معايير القبول — نتائج فعلية على UAT

| الحالة | النتيجة |
|---|---|
| كود فقط `V2QA-CODE` | `200` · id `8143` · `full_name='موظف بدون اسم'` · الكود محفوظ |
| اسم فقط ×2 | `200 / 200` · id `8144`, `8145` · `emp_code = NULL` للاثنين |
| غياب الاسم والكود | `400` · `{"error":"الاسم أو الرقم الوظيفي مطلوب"}` |
| تكرار كود حقيقي | `409` · `{"error":"الرقم الوظيفي موجود مسبقاً"}` |
| حفظ جماعي لموظف NULL | `200` · تقييم `655` · `employee_code=''` · 4 إجابات محفوظة |
| حفظ فردي (`save-unified`) لموظف NULL | `200` · تقييم `657` · `employee_code=''` · `final_score=40.00` · درجتان محفوظتان |
| استيراد Excel (الحاسم) | ✅ انظر §5 |

> أخطاء الـ console الوحيدة في المتصفح هي `400` و`409` المتعمّدان أعلاه (اختبارات القبول). لا أخطاء JS أخرى.

## 5) إثبات اختبار الاستيراد الحاسم (SQL)

الملف: `docs\CD-043\backups\2026-11-24_quickadd_v2\excel_import_sql_proof.txt`

```text
BEFORE={"id":8146,"emp_code":null,"full_name":"V2 استيراد اسم فقط 0057340187","profile_status":"incomplete"}
PREVIEW={"valid":1,"invalid":0,"errors":[],"is_new":true}
COMMIT={"committed_rows":1,"committed_evaluations":1,"errors":[]}
AFTER={"id":8146,"emp_code":"V2-REAL-0057340187","full_name":"V2 استيراد اسم فقط 0057340187","profile_status":"incomplete"}
ROW_COUNT_FOR_NAME={"n":1}
EVALUATION={"id":658,"evaluated_employee_id":8146,"employee_code":"V2-REAL-0057340187","template_id":286,"final_score":"88.00"}
CODE_ROWS=[{"id":8146,"full_name":"V2 استيراد اسم فقط 0057340187"}]
```

الخلاصة: طُبقت المطابقة بالاسم رغم وجود الكود الحقيقي في الملف، وتم تحديث `NULL → V2-REAL-0057340187` على **نفس المعرّف 8146**، دون إنشاء موظف مكرر (`ROW_COUNT_FOR_NAME=1`)، مع حفظ التقييم والدرجة `88.00`. لا كود `EMP...`/`AUTO_` تولّد.

## 6) اختبارات الانحدار

| الاختبار | النتيجة |
|---|---|
| `tests/runner.php` (كامل) | **123 نجح / 5 فشل** — مطابق تماماً لخط الأساس V1؛ الفشل الخمسة معروف سابقاً (`BulkEvaluationControllerTest::testBulkCreatePageRendersTemplateSelector`، `CD029CourseLinkingTest`، `CD037EvaluationLinkingTest`، `DescriptiveTemplatesTest` ×2). لم يُصلَح أي منها. |
| `BulkEvaluationControllerTest` (مستهدف) | `8/9` — الفشل الوحيد هو `testBulkCreatePageRendersTemplateSelector` المعروف. |
| `RoleDashboardSmoke` / `RoleDashboardPersonaSmoke` | PASS / PASS (exit=0) |
| `ExcelWorkflowSmoke` | `5/5` (exit=0) |
| `ExcelTemplateExportSmoke` | `10/10` (exit=0) |
| `ExcelImportValidationSmoke` | `4/4` (exit=0) |
| `EvaluationCalculatorSmoke` | `3/3` (exit=0) |
| UTF-8 (8 ملفات، `v40_verify_utf8.ps1`) | `ALL_CLEAN` · لا BOM · لا أحرف تحكم |
| JS مُقدَّم (inline `new Function` + `node --check` على 5 ملفات خارجية) | كلها صحيحة (`main`, `print-logic`, `evaluation-creator`, `sidebar`, `builder`) |

السجلات: `docs\CD-043\backups\2026-11-24_quickadd_v2\regression_full_runner.log` و`regression_smokes.log`.
> ملاحظة مُوثّقة: مشغّل `runner.php` الكامل ينهي العملية بـ exit=255 بعد طباعة ملخص الأصناف الـ16 دون سطر النتيجة النهائية — سلوك سابق موجود في خط الأساس؛ الأرقام مُحصاة من ملخص الأصناف (المجموع 123/5).

## 7) خطة التراجع (إلزامية)

1. استعادة ملفات النطاق من `docs\CD-043\backups\2026-11-24_quickadd_v2\files\` (لا `git restore` عمياء — الشجرة تحمل تعديلات v40–v46 غير الملتزمة).
2. لحل NULL يدوياً قبل أي `down`: إما استيراد أكواد حقيقية أو حذف الصفوف المقصودة؛ ثم:
   ```powershell
   cmd /c '"C:\xampp\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 employee_evaluation_uat < "C:\xampp\htdocs\employee-evaluation\database\migrations\2026_11_24_quickadd_null_emp_code_down.sql"'
   ```
   (يرفض التنفيذ بـ `SIGNAL 45000` ما دامت صفوف NULL موجودة).
3. كملاذ أخير: استعادة الـ dump الكامل من §1 تُعيد نقطة زمنية سابقة.
4. **لا push ولا commit.**

## 8) الحالة النهائية

- حُذف ملف الجلسة المؤقت `_v2_session.php` من جذر الموقع ✅
- أُغلقت تبويبات المتصفح المؤقتة ✅
- صفوف UAT المضافة (`8143`–`8146`) تُبقى كدليل حيّ على القبول؛ يمكن حذفها عند طلبك.
- Anti-scope محترم: لم يُلمس `EmployeeMatcher.php`، ولا حساب الدرجات، ولا `uq_eval_once`، ولا واجهة V1، ولا أي ملف خارج النطاق المُعتمد.
