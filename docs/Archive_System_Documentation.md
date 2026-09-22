# نظام الأرشيف - التوثيق الشامل

**الحالة: مكتمل | التاريخ: 2026-09-08 | الإصدار: 2.0**

---

## 1. نظرة عامة

### 1.1 الهدف
نظام أرشيف متكامل لعرض وتصفية وطباعة تقييمات الدورات التدريبية والورش العمل ب تصميم فاخر يدعم:
- عرض شجري: فروع → أقسام → سنوات → سجلات
- فلتر متعدد: تاريخ + حالة التقييم + بحث نصي
- طباعة احترافية مع بسم الله الرحمن الرحيم
- واجهة عربية RTL تصميم فاخر

### 1.2 النطاق
| المكون | العدد | الملفات |
|--------|-------|---------|
| نماذج (Models) | 1 | `ArchiveModel.php` |
| وحدات تحكم (Controllers) | 2 | `ArchiveCoursesController.php`, `ArchiveWorkshopsController.php` |
| عروض (Views) | 9 | `courses.php`, `workshops.php`, `*_departments.php`, `*_years.php`, `*_records.php`, `show_evaluation.php` |
| ملفات CSS | 1 | `archive.css` |
| مسارات (Routes) | 10 | `routes/web.php` |

---

## 2. هيكل قاعدة البيانات

### 2.1 الجداول المعتمدة
```
┌─────────────────────┐     ┌─────────────────────┐
│   training_courses  │     │  training_workshops  │
│   (الدورات)         │     │   (الورش)           │
├─────────────────────┤     ├─────────────────────┤
│ id                  │     │ id                  │
│ catalog_id → FK     │     │ catalog_id → FK     │
│ branch_id → FK      │     │ branch_id → FK      │
│ department_id → FK  │     │ department_id → FK  │
│ start_date          │     │ start_date          │
│ end_date            │     │ end_date            │
│ status              │     │ status              │
└─────────┬───────────┘     └─────────┬───────────┘
          │                           │
          └───────────┬───────────────┘
                      │
              ┌───────▼────────┐
              │  evaluations   │
              │  (التقييمات)   │
              ├────────────────┤
              │ entity_type    │  ← 'course' أو 'workshop'
              │ entity_id      │  ← training_courses.id أو training_workshops.id
              │ final_score    │
              │ status         │  ← 'draft','pending_review','approved','completed','expired','archived'
              │ evaluation_date│
              └────────────────┘
                      │
              ┌───────▼────────┐
              │ course_catalog │
              │ workshop_catalog│
              ├────────────────┤
              │ id             │
              │ title          │  ← اسم الدورة/الورشة (يبحث فيه المستخدم)
              └────────────────┘
```

### 2.2 العلاقات
- `training_courses.branch_id → branches.id`
- `training_courses.department_id → departments.id`
- `training_courses.catalog_id → course_catalog.id`
- `training_workshops.branch_id → branches.id`
- `training_workshops.department_id → departments.id`
- `training_workshops.catalog_id → workshop_catalog.id`
- `evaluations.entity_id → training_courses.id` (عند `entity_type='course'`)
- `evaluations.entity_id → training_workshops.id` (عند `entity_type='workshop'`)

---

## 3. المكونات البرمجية

### 3.1 ArchiveModel.php

#### الوظائف العامة
```php
// فلتر حالة التقييم (آمن - inline string)
private function evalStatusFilter(?string $status): string
// يرجع SQL: " AND e.status = 'approved'" أو " AND e.status IN ('approved','completed')"

// فلتر التاريخ
private function applyDateFilter(string $tableAlias, ?string $from, ?string $to): string
// يرجع SQL: " AND tc.end_date >= :date_from AND tc.end_date <= :date_to"

// فروع الدورات
public function getCourseBranchCards(?string $from, ?string $to, ?string $status, string $search = ''): array
// يرجع: branch_id, branch_name, total_courses, evaluated_count, avg_score

// أقسام الدورات
public function getCourseDepartmentCards(int $branchId, ?string $from, ?string $to, ?string $status, string $search = ''): array

// سنوات الدورات
public function getCourseYearCards(int $departmentId, ?string $from, ?string $to, ?string $status): array

// سجلات الدورات
public function getCourseRecords(int $departmentId, int $year, ?string $from, ?string $to, string $search, ?string $status): array

// إحصائيات عامة
public function getCourseOverallStats(?string $from, ?string $to, ?string $status): array
```

#### آليات البحث
```php
// البحث في بطاقات الفروع - EXISTS subquery
$searchFilter = " AND EXISTS (
    SELECT 1 FROM training_courses tc2 
    JOIN course_catalog cc2 ON tc2.catalog_id=cc2.id 
    WHERE tc2.branch_id=b.id 
    AND cc2.title LIKE :search_branch
)";
$params[':search_branch'] = "%{$search}%";
```

#### آلياتفلتر الحالة (آمنة)
```php
// القيم المسموح بها فقط
$allowedStatuses = ['draft','pending_review','approved','completed','expired','archived'];

// التحقق + escape
if (in_array($status, $allowedStatuses, true)) {
    $safe = addslashes($status);
    return " AND e.status = '{$safe}'";
}
// الافتراضي: فقط المعتمد والمكتمل
return " AND e.status IN ('approved','completed')";
```

---

### 3.2 ArchiveCoursesController.php

```php
class ArchiveCoursesController extends Controller {
    // الصفحة الرئيسية - بطاقات الفروع
    public function branches()
    // GET /archive/courses/branches
    // Parameters: from, to, status, search

    // صفحة الأقسام لفرع معين
    public function departments(int $branchId)
    // GET /archive/courses/branches/:id/departments

    // صفحة السنوات لقسم معين
    public function years(int $departmentId)
    // GET /archive/courses/departments/:id/years

    // صفحة السجلات لقسم وسنة
    public function records(int $departmentId, int $year)
    // GET /archive/courses/records/:deptId/:year
    // Parameters: from, to, status, search

    // عرض تقييم واحد (الطباعة)
    public function show(int $courseId)
    // GET /archive/courses/show/:id
}
```

### 3.3 ArchiveWorkshopsController.php
نفس هيكل `ArchiveCoursesController` مع `training_workshops` بدلاً من `training_courses`.

---

## 4. نظام البحث

### 4.1 النطاق
| الصفحة | حقل البحث | القاعدة |
|--------|-----------|---------|
| courses.php (الرئيسية) | `name="search"` | يبحث في `course_catalog.title` |
| workshops.php (الرئيسية) | `name="search"` | يبحث في `workshop_catalog.title` |
| courses_departments.php | `name="search"` | يبحث في `course_catalog.title` |
| workshops_departments.php | `name="search"` | يبحث في `workshop_catalog.title` |
| courses_years.php | `name="search"` | يبحث في `course_catalog.title` |
| workshops_years.php | `name="search"` | يبحث في `workshop_catalog.title` |
| courses_records.php | `name="search"` | يبحث في `course_catalog.title`, `branches.name`, `departments.name` |
| workshops_records.php | `name="search"` | يبحث في `workshop_catalog.title`, `branches.name`, `departments.name` |

### 4.2 التمرير عبر المستويات
```
المستخدم يكتب "JavaScript" في البحث
    ↓
الفرع يظهر فقط إذا كان به دورة match
    ↓
النقر على الفرع → الأقسام تظهر فقط إذا كان بها دورة match
    ↓
النقر على القسم → السنوات تظهر فقط إذا كان بها دورة match
    ↓
النقر على السنة → السجلات تظهر (مع تصفية إضافية)
```

### 4.3 الكود الأمني
```php
// 1. التحقق من القيم المسموح بها
$allowedStatuses = ['draft','pending_review','approved','completed','expired','archived'];

// 2. Escape من SQL Injection
$safe = addslashes($status);

// 3. استخدام parameterized query للبحث
$params[':search_branch'] = "%{$search}%";

// 4. عرض آمن في HTML
<?= htmlspecialchars($currentSearch) ?>
```

---

## 5. الروابط (Routes)

```php
// أرشيف الدورات
$router->get('/archive/courses/branches', [ArchiveCoursesController::class, 'branches']);
$router->get('/archive/courses/branches/(\d+)/departments', [ArchiveCoursesController::class, 'departments']);
$router->get('/archive/courses/departments/(\d+)/years', [ArchiveCoursesController::class, 'years']);
$router->get('/archive/courses/records/(\d+)/(\d+)', [ArchiveCoursesController::class, 'records']);
$router->get('/archive/courses/show/(\d+)', [ArchiveCoursesController::class, 'show']);

// أرشيف الورش
$router->get('/archive/workshops/branches', [ArchiveWorkshopsController::class, 'branches']);
$router->get('/archive/workshops/branches/(\d+)/departments', [ArchiveWorkshopsController::class, 'departments']);
$router->get('/archive/workshops/departments/(\d+)/years', [ArchiveWorkshopsController::class, 'years']);
$router->get('/archive/workshops/records/(\d+)/(\d+)', [ArchiveWorkshopsController::class, 'records']);
$router->get('/archive/workshops/show/(\d+)', [ArchiveWorkshopsController::class, 'show']);
```

---

## 6. واجهة المستخدم

### 6.1 التصميم
| العنصر | اللون | الوصف |
|--------|-------|-------|
| `navbar-luxury` | `linear-gradient(135deg, #0d5e52, #0a4a42)` | شريط التنقل الرئيسي |
| حد ذهبي | `border-bottom: 3px solid #c8943f` | فاصل أنيق |
| بطاقات الفروع | خلفية بيضاء + ظلال ناعمة | `branch-card` |
| حلقة النتيجة | `score-ring-xs` | دائرة ملونة حسب التصنيف |
| شريط الفلتر | `filter-bar` | خلفية رمادية فاتحة |

### 6.2 التصنيف الديناميكي
```php
// من rating_settings في قاعدة البيانات
$ratings = (new RatingSetting())->getAll();

function ratingFor($score, $ratings) {
    foreach($ratings as $r) {
        if($score >= $r->min_score && $score <= $r->max_score) {
            return $r;
        }
    }
    return end($ratings) ?: (object)['label'=>'غير مصنف','color'=>'#64748b'];
}
```

### 6.3 شجرة العرض
```
الفرع الرئيسي (branches.php)
├── الرياض (branch_id=1)
│   └── [courses_departments.php]
│       ├── تقنية المعلومات (department_id=2)
│       │   └── [courses_years.php]
│       │       ├── 2026
│       │       │   └── [courses_records.php]
│       │       │       ├── دورة JavaScript
│       │       │       └── دورة PHP
│       │       └── 2025
│       └── الموارد البشرية (department_id=3)
├── جدة (branch_id=2)
└── الدمام (branch_id=3)
```

---

## 7. الطباعة

### 7.1 show_evaluation.php
- يخفي كل شيء ما عدا `.printable-area`
- يعرض بسم الله الرحمن الرحيم في الأعلى
- جدول تفاصيل التقييم مع الأقسام والأسئلة
- توقيع المدير + التاريخ

### 7.2 CSS الخاص بالطباعة
```css
/* في print-style.css */
body > *:not(.printable-area) {
    display: none !important;
}

/* في show_evaluation.php - override */
@media print {
    body > .main-content {
        display: block !important;
    }
}
```

---

## 8. الاختبارات

### 8.1 اختبارات الوحدة
| الدالة | المدخل | المتوقع | النتيجة |
|--------|--------|---------|---------|
| `getCourseBranchCards()` | from=2026-01-01 | فروع بها دورات 2026 | ✅ |
| `getCourseBranchCards()` | search='JavaScript' | فروع بها دورة JavaScript | ✅ |
| `getCourseBranchCards()` | status='completed' | فروع بها تقييمات مكتملة | ✅ |
| `evalStatusFilter()` | status='invalid' | الافتراضي approved+completed | ✅ |
| `evalStatusFilter()` | status='draft' | draft فقط | ✅ |

### 8.2 اختبارات التكامل
| السيناريو | النتيجة |
|-----------|---------|
| فتح /archive/courses/branches → 3 فروع | ✅ |
| فلتر تاريخ: from=2026-01-01 → دورات 2026 فقط | ✅ |
| فلتر حالة: status=completed → تقييمات مكتملة فقط | ✅ |
| بحث: search=JavaScript → فروع بها الدورة | ✅ |
| نقر فرع → أقسام هذا الفرع فقط | ✅ |
| نقر قسم → سنوات هذا القسم فقط | ✅ |
| نقر سنة → سجلات هذه السنة فقط | ✅ |
| عرض تقييم → بسم الله + جدول + توقيع | ✅ |
| طباعة → PDF احترافي | ✅ |

### 8.3 اختبارات الحالة الحدية
| الحالة | السلوك | النتيجة |
|--------|--------|---------|
| لا توجد دورات | رسالة "لا توجد فروع" | ✅ |
| بحث بدون نتائج | 0 فروع + رسالة فارغة | ✅ |
| حالة غير صالحة | افتراضي approved+completed | ✅ |
| تاريخ from > to | لا نتائج | ✅ |
| search مع from/to | كلا الفلترين يعملان | ✅ |

---

## 9. الأمان

### 9.1 SQL Injection Prevention
```php
// ❌ خطأ
$sql = "WHERE status = '$status'";

// ✅ صحيح - inline مع allowlist
$allowedStatuses = ['draft','pending_review','approved','completed','expired','archived'];
if (in_array($status, $allowedStatuses, true)) {
    $safe = addslashes($status);
    return " AND e.status = '{$safe}'";
}

// ✅ صحيح - parameterized query
$params[':search'] = "%{$search}%";
$sql = "WHERE title LIKE :search";
```

### 9.2 XSS Prevention
```php
// ✅ عرض آمن
<?= htmlspecialchars($currentSearch) ?>
<?= htmlspecialchars($b->branch_name) ?>
```

### 9.3 Auth Check
```php
// ✅ فحص الصلاحية
Auth::can('training.view');
// يتحقق من تسجيل الدخول + الصلاحية
// يعيد 403 إذا لم تكن الصلاحية موجودة
```

---

## 10. الأخطاء المعروفة والمُصلحة

### 10.1 Invalid parameter number
**المشكلة:** خطأ PDO عند تكرار `:eval_status` في الاستعلامات المعقدة
**السبب:** PDO لا يدعم تكرار نفس المعامل في استعلام واحد
**الحل:** استخدم inline safe strings بدلاً من PDO named params
```php
// ❌ قبل
$this->db->bind(':eval_status', $status);

// ✅ بعد
$safe = addslashes($status);
return " AND e.status = '{$safe}'";
```

### 10.2 print-style.css conflict
**المشكلة:** `print-style.css` يخفي كل شيء ما عدا `.printable-area`
**الحل:** إضافة override في `show_evaluation.php`
```css
@media print {
    body > .main-content {
        display: block !important;
    }
}
```

---

## 11. الملفات المعدلة

### الملفات الجديدة
| الملف | الوصف |
|-------|-------|
| `app/Models/ArchiveModel.php` | 14 طريقة + فلتر آمن |
| `app/Controllers/ArchiveCoursesController.php` | 5 إجراءات |
| `app/Controllers/ArchiveWorkshopsController.php` | 5 إجراءات |
| `app/Views/archive/courses.php` | الصفحة الرئيسية للدورات |
| `app/Views/archive/workshops.php` | الصفحة الرئيسية للورش |
| `app/Views/archive/courses_departments.php` | أقسام الدورات |
| `app/Views/archive/workshops_departments.php` | أقسام الورش |
| `app/Views/archive/courses_years.php` | سنوات الدورات |
| `app/Views/archive/workshops_years.php` | سنوات الورش |
| `app/Views/archive/courses_records.php` | سجلات الدورات |
| `app/Views/archive/workshops_records.php` | سجلات الورش |
| `app/Views/archive/show_evaluation.php` | عرض تقييم واحد |
| `public/css/archive.css` | CSS مشترك |
| `docs/Archive_System_Documentation.md` | هذا الملف |

### الملفات المعدلة
| الملف | التعديل |
|-------|---------|
| `routes/web.php` | 10 مسارات جديدة |
| `app/Views/layouts/header.php` | روابط الأرشيف + navbar-luxury |

---

## 12. التدفق المستقبلي

### المرحلة القادمة
- [ ] تحسين `getCourseOverallStats` لتجنب العد المزدوج
- [ ] إضافة تصدير PDF من صفحة السجلات
- [ ] إضافة رسم بياني للإحصائيات
- [ ] تحسين الأداء مع البيانات الكبيرة

---

## 13. ملاحظات التطوير

### للصيانة
1. عند إضافة status جديد، أضفه في `$allowedStatuses` في `evalStatusFilter()`
2. عند إضافة catalog جديد، أضف `EXISTS` subquery في `getCourseBranchCards()`
3. عند تعديل `archive.css`، تحقق من التأثير على 9 صفحات

### للاختبار
```bash
# اختبار سريع
php -r "require 'app/Models/ArchiveModel.php'; echo 'OK';"

# اختبار PDO
curl "http://localhost/employee-evaluation/archive/courses/branches?search=JavaScript&status=completed"
```

---

**آخر تحديث: 2026-09-08 | الإصدار: 2.0 | الملف: Archive_System_Documentation.md**
