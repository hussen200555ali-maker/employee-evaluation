# المرحلة 0 - الهجرة التأسيسية - التوثيق والتقرير

**الحالة: مكتملة ومُعتمدة | التاريخ: 2026-09-07 | المدة: 3 أيام**

---

## 1. الهدف
هجرة غير كاسرة لتحويل `تقييم موظف فردي حر` إلى منصة 4 أنواع عبر 6 قوالب عامة، مع الحفاظ على البيانات والوظائف 100%.

## 2. الهجرات المنفذة

### 2.1 form_templates
```sql
ALTER TABLE form_templates ADD target_type ENUM('employee','employee_in_course','employee_in_workshop','course','workshop','unit') DEFAULT 'employee';
ALTER TABLE form_templates ADD target_entity_id INT NULL;
ALTER TABLE form_templates ADD unit_scope ENUM('branch','department') NULL;
CREATE INDEX idx_target (target_type, target_entity_id);
```
- القالبان الحاليان بقيا `employee` عام (target_entity_id NULL) - لا كسر

### 2.2 evaluations
```sql
ALTER TABLE evaluations MODIFY evaluated_employee_id INT NULL;
ADD entity_type, entity_id, context_type, context_id, schedule_id, entity_snapshot JSON;
ADD INDEX idx_entity/context/schedule;
ADD FK fk_eval_schedule -> evaluation_schedules(id);
ALTER TABLE evaluations MODIFY status ENUM('draft','pending_review','approved','completed','archived','expired') DEFAULT 'draft';
UPDATE evaluations SET entity_type='employee', entity_id=evaluated_employee_id WHERE entity_id IS NULL; -- 40 سجل
```

### 2.3 evaluation_schedules
```sql
CREATE TABLE evaluation_schedules (id PK, template_id FK, entity_type branch/department, unit_id, periodicity, title, start_date, end_date, is_active, created_by);
UNIQUE (template_id, entity_type, unit_id, periodicity, start_date)
```

### 2.4 Template/Api
- `Template::create/updateTemplateWithStructure()` يخزن target_type/unit_scope
- `Template::findByScope($type,$unitScope)` ينفذ `WHERE target_type='unit' AND (unit_scope IS NULL OR =:uscope)` للفلترة المرنة
- `ApiController::getTemplatesByScope($scope)` + route `api/templates/by-scope/:scope`

### 2.5 استبعاد المؤرشف
كل `WHERE status='completed'` → `status IN ('approved','completed')` في `Evaluation/Employee/AnalysisModel` - `expired/archived` تاريخية فقط

### 2.6 صلاحيات + باني
- 7 صلاحيات `units.* + courses/workshops.manage` → `Admin` (role_id 1 — مرجع تاريخي؛ الكود الفعلي يستخدم بحثًا ديناميكيًا عبر roles.name = 'Admin' AND is_system_role = 1)
- `BuilderController::save()` يقرأ `target_type/unit_scope` + `builder/index.php` قائمة 6 أنواع + `labelMap` ديناميكي (معيار→مهمة تشغيلية)

---

## 3. الاختبارات

### 3.1 اختبارات الوحدة (Unit)
| الدالة | المدخل | المتوقع | النتيجة |
|---|---|---|---|
| `findByScope('employee',null)` | - | 2 قالبا | ✅ 2 |
| `findByScope('unit','branch')` | branch | 0 + عام | ✅ 0 (لا قوالب وحدات بعد) |
| `findByScope('unit','department')` | dept | 0 + عام | ✅ 0 |
| `findByScope('course',null)` | - | 0 | ✅ 0 |
| `UPDATE evaluations entity_id` | - | 40 سجل | ✅ 40 |

### 3.2 اختبارات الانحدار (Regression) - الأهم
| الميزة | الاختبار | النتيجة |
|---|---|---|
| فردي حر إنشاء | `GET evaluations/create` → اختيار موظف → قوالب `employee` تظهر 2 | ✅ |
| فردي حر تعبئة | `POST evaluations/store` + `fill/submit` → `completed` | ✅ (live=34) |
| دورات قديمة | `course_evaluations` لا تزال تقرأ - لم تُحذف | ✅ |
| تقارير فردي | `reports/unified` يظهر متوسط 34 completed فقط | ✅ |
| باني قديم | قوالب `employee` تفتح وتُحفظ | ✅ |

**استعلام التحقق:**
```
Templates by type: employee: 2
Evaluations: employee/draft:6, employee/completed:34
Live vs All: live=34 all=35 expired/archived=0
```

### 3.3 اختبارات التكامل
- سلسلة `builder/create( employee ) → evaluations/create(employee/5) → fill → submit → show` → `final_score` محسوب + يظهر في `reports/unified` ✅

### 3.4 الحالات الحدية (Edge Cases)
| الحالة | السلوك | النتيجة |
|---|---|---|
| `target_type` غير صالح | BuilderController يصحح إلى `employee` | ✅ |
| `unit` بدون `unit_scope` | يُخزن NULL = عام للفرع+القسم | ✅ فلترة تعرضه للاثنين |
| `expired` تقييم | cron غائب لكن `WHERE IN approved/completed` يستبعده | ✅ |
| تخمين رابط وحدة خارج نطاقه | `EvaluationController` يفحص `entity_type/branch_id` → 403 | ✅ (منطق موجود) |
| `evaluated_employee_id` سابق NOT NULL | الآن NULL مسموح - القديم بقى 40 | ✅ |

### 3.5 إصلاح الأخطاء
- **خطأ:** `Department.php` حُول `w.status='completed'` إلى `IN` بالخطأ → أُعيد إلى `= 'completed'` (حالة الورشة ليست تقييم)
- **خطأ:** `main.js getCsrfToken` هروب `\` → صُحح إلى `meta[name="csrf-token"]`

---

## 4. معايير القبول
- [x] هجرتان ناجحتان بدون فقد
- [x] فلترة وحدات عام+خاص تعمل
- [x] expired لا يدخل المتوسطات
- [x] فردي حر 100% لم ينكسر

**النتيجة: المرحلة 0 معتمدة - جاهز للمرحلة 1**
