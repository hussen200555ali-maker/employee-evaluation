# DIR-TEMPLATES-UI-REDESIGN-V1.1 — سجل التنفيذ

## الحالة المرجعية
- الفرع: `feature/cd-043-wip`
- HEAD قبل التنفيذ: `154202ffbc33457dbb250fcb664326eca099793a`
- working tree قبل التنفيذ: نظيف
- الوضع: Code mode — مخوّل بالتنفيذ وفق الحزمة المغلقة V1.1

## القرارات الإدارية المستوعبة
1. إضافة `skill` إلى `$allowedTypes` في `BuilderController::save()` (إصلاح Backendوحيد مسموح).
2. تعديل `header-builder.php` يقتصر على إضافة وسم `<link>` واحد لـ `builder-ui.css`.
3. لا commit ولا push قبل الضوء الأخضر النهائي.

## ما سيتم تغييره

### ملفات جديدة
| الملف | الغرض |
|---|---|
| `public/css/templates-ui.css` | هوية شاشة `/templates` (بطاقات، chips، هيدر، modal، empty state) |
| `public/css/builder-ui.css` | هوية الباني مع الحفاظ على كل المعرّفات والclasses الحالية |
| `public/js/templates-ui.js` | بحث/فلترة/ترتيب + modal الحذف (Vanilla JS فقط) |

### ملفات معدّلة (تعديل جراحي)
| الملف | التغيير |
|---|---|
| `app/Views/templates/index.php` | استبدال `.page-header` القديم بهيدر مؤسسي، بطاقات `tp-*`، أدوات بحث/فلترة، modal حذف |
| `app/Views/builder/index.php` | إضافة وسم CSS، callout للتحقق، شريط وزن لكل معيار، بطاقات قابلة للطي، دون مساس بالعقد الحساس |
| `app/Views/layouts/header-builder.php` | سطر `<link>` واحد فقط |
| `app/Controllers/BuilderController.php` | إضافة `'skill'` إلى `$allowedTypes` فقط |

## العقد الحساس (يُمنع كسره)
- المعرّفات: `mainDropZone`، `form-structure-container`، `properties-content`، `structure-hidden-fields`، `total-weight-indicator`، `auto-distribute-weights`، `target_type`، `pos-wrapper`، `unit-scope-wrapper`.
- الclasses/data: `.draggable-element`، `.criterion-wrapper`، `.section-item`، `.drop-zone[data-criterion-id]`، `data-id`، `data-prop`، `data-section-type`.
- أسماء الحقول المخفية وترتيبها في `builder.js`: `criteria[c][...]` و `criteria[c][sections][s][...]` — صفر تغيير.
- الحذف: `POST /templates/delete` مع `template_id` و `_token` كما هو، مع modal بدل `confirm()`.

## ممنوع منعاً باتاً
`product-ui.css`، `excel-flow.css`، `main.js`، `builder.js`، `EvaluationExcelService`، `routes/web.php`، `TemplateController` (إلا BuilderController لـ skill)، الجداول/الـ migrations، منطق حساب الأوزان.

## النسخ الاحتياطي
المجلد: `docs/CD-043/backups/2026-11-24_templates_ui/`
يحتوي على نسخ `.bak` للملفات الأربعة بأسماء صريحة (تصحيح إداري أثناء التنفيذ: كان اسم `index.php.bak` يتعارض بين ملفَي view، ففُصلا):

| نسخة الاحتياط | الملف الأصلي | SHA256 (قبل) |
|---|---|---|
| `templates_index.php.bak` | `app/Views/templates/index.php` | `791174928dc385bda42238890fc79b2d242a921fc851db98930e60f5945e8c75` |
| `builder_index.php.bak` | `app/Views/builder/index.php` | `441bd80bc622b410af401433fd497350dc9213ad55704738b3a6ddff1a9d150e` |
| `header-builder.php.bak` | `app/Views/layouts/header-builder.php` | `b2b1943c396fb0d78ce3f63a4494c043247a2bfddbadab3b6b20d47025d92c90` |
| `BuilderController.php.bak` | `app/Controllers/BuilderController.php` | `dd9686bd28e3ea92d4d711bb27e87983490850648d886a6943bb43bca060bc34` |

كما يحتوي على `SHA256SUMS-before.txt` و `SHA256SUMS-after.txt` ومجلد `screenshots/` باللقطات قبل/بعد.

## نتائج التنفيذ
- `php -l`: 4/4 ملفات PHP سليمة.
- UAT: 15/15 PASS (منها إثبات بقاء `skill` بعد الحفظ وعدم تحويله إلى `employee`).
- Smokes: ExcelTemplateExport 12/12، ExcelWorkflow 5/5، ExcelImportValidation 4/4، EvaluationCalculator 3/3.
- `tests/runner.php`: EXIT=255 ونفس الفشل المعروف تماماً (لا انحدار جديد).
- متصفح حي: بحث/فلترة/ترتيب/modal حذف يعمل، وحقول `criteria[0][sections][0][...]` محفوظة بالترتيب، وطيّ المعايير وأشرطة الأوزان تعمل بلا أخطاء console.

## خطة التراجع
عند فشل أي اختبار: استعادة الملفات الأربعة من `.bak`، حذف الملفات الثلاثة الجديدة، ثم التحقق من تطابق SHA256 مع `SHA256SUMS-before.txt`.

## الاختبارات المخططة
- `php -l` للملفات PHP المعدّلة.
- Smokes: `ExcelTemplateExportSmoke 12/12`، `ExcelWorkflowSmoke 5/5`، `ExcelImportValidationSmoke 4/4`.
- `tests/runner.php` بمقارنة الفشل المعروف بخط الأساس.
- UAT: 14 حالة (بحث، فلترة، ترتيب، حذف، إنشاء، تعديل، سحب، وزن، حفظ، صلاحية، استجابة).
- إثبات الحفظ: مقارنة HIDDEN inputs قبل/بعد.
