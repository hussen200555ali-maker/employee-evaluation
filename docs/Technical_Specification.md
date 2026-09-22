# المواصفة الفنية الذهبية - منصة التقييم الشاملة (4 أنواع)
**الإصدار 1.0 - المرجع الرسمي للتنفيذ**
**تاريخ: 2026-09-07 | البنية الوحيدة: form_templates → criteria → criteria_sections**

---

## 1. قاعدة البيانات - SQL كامل

### 1.1 تعديل form_templates
```sql
ALTER TABLE form_templates 
  ADD COLUMN target_type ENUM('employee','employee_in_course','employee_in_workshop','course','workshop','unit') NOT NULL DEFAULT 'employee' AFTER target_position_id,
  ADD COLUMN target_entity_id INT NULL AFTER target_type COMMENT 'يبقى NULL للعامية - لا يربط بكيان محدد',
  ADD COLUMN unit_scope ENUM('branch','department') NULL COMMENT 'عند target_type=unit: فرع أو قسم',
  ADD INDEX idx_target (target_type, target_entity_id);
-- إبقاء target_position_id للتوافق (position → employee عام)
-- الأنواع الستة العامة (Generic) - كلها NULL في target_entity_id
```

### 1.2 تعديل evaluations
```sql
ALTER TABLE evaluations
  MODIFY evaluated_employee_id INT NULL,
  ADD COLUMN entity_type ENUM('employee','course','workshop','branch','department') NOT NULL DEFAULT 'employee' AFTER template_id,
  ADD COLUMN entity_id INT NULL AFTER entity_type,
  ADD COLUMN context_type ENUM('course','workshop') NULL AFTER entity_id,
  ADD COLUMN context_id INT NULL AFTER context_type,
  ADD COLUMN schedule_id INT NULL AFTER context_id,
  ADD COLUMN entity_snapshot JSON NULL AFTER evaluator_employee_id,
  ADD INDEX idx_entity (entity_type, entity_id),
  ADD INDEX idx_context (context_type, context_id),
  ADD INDEX idx_schedule (schedule_id);
ALTER TABLE evaluations ADD CONSTRAINT fk_eval_schedule FOREIGN KEY (schedule_id) REFERENCES evaluation_schedules(id) ON DELETE SET NULL;
-- القيد الفريد تطبيقي (NULL-safe) في PHP
```

### 1.3 جدول evaluation_schedules
```sql
CREATE TABLE evaluation_schedules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_id INT NOT NULL,
  entity_type ENUM('branch','department') NOT NULL,
  unit_id INT NOT NULL,
  periodicity ENUM('daily','weekly','monthly','quarterly','semiannual','annual','custom') NOT NULL,
  title VARCHAR(255) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_schedule (template_id, entity_type, unit_id, periodicity, start_date),
  FOREIGN KEY (template_id) REFERENCES form_templates(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 1.4 evaluation_answers (بدون تغيير)
```sql
-- كما هو: id, evaluation_id FK CASCADE, section_id FK criteria_sections, raw_value TEXT, normalized_score DECIMAL(5,2), notes TEXT
```

### 1.5 صلاحيات جديدة
```sql
INSERT INTO permissions (name, description, category) VALUES
('units.view','عرض الوحدات','الوحدات'),
('units.create','إنشاء تقييم وحدة','الوحدات'),
('units.fill','تعبئة تقييم وحدة','الوحدات'),
('units.review','مراجعة واعتماد وحدة','الوحدات'),
('units.manage','إدارة قوالب الوحدات','الوحدات'),
('courses.manage','إدارة قوالب الدورات','التدريب'),
('workshops.manage','إدارة قوالب الورش','التدريب');
```

### ERD
```
form_templates (1) ──< criteria (N) ──< criteria_sections (N)
     │ 1                          │
     └──────< evaluations (N) >──┘ (template_id)
              │ 1 ──< evaluation_answers (N) >── criteria_sections
              └─ FK schedule_id >── evaluation_schedules (N) >── form_templates
              └─ entity_id → employees/branches/departments/training_courses
```

---

## 2. سير العمل (Workflow) - مخطط تدفق نصي

### الحالات (Statuses)
`draft → pending_review → approved → archived` + `expired` (تلقائي)

### القواعد
| انتقال | من | إلى | الفاعل | الحدث |
|---|---|---|---|---|
| إرسال | draft | pending_review | معبئ (مدير فرع) | `Audit: SUBMIT` + إشعار مراجع |
| اعتماد | pending_review | approved | `units.review` (الإدارة) | `final_score` يُحسب + يظهر في التقارير |
| إعادة | pending_review | draft | مراجع | ملاحظات |
| إغلاق تلقائي | draft | expired | cron يومي `end_date < CURDATE()` | يمنع `fill()` |

### التدفق لكل نوع
- **فردي حر:** `builder(create position) → evaluations/create(employee) → fill(draft→approved مباشر)`
- **موظف داخل دورة:** `course.create → evaluations/create?entity=employee&context=course → fill(draft→approved)`
- **دورة ككيان:** `builder(course) → evaluations/create?entity=course → fill(draft→approved)`
- **وحدة دورية:** `builder(branch) → schedules.create(monthly) → evaluations/create?entity=branch&schedule=5 → fill(draft→pending_review→approved)` - الإنشاء يدوي عند الضغط `بدء دورية`

---

## 3. الواجهات (Wireframes نصية)

### الباني بعد التعديل
```
[اسم القالب*] [نوع الهدف: ▼ موظف/دورة/ورشة/فرع/قسم/منصب] [الكيان: ▼ (يملأ api/scope/list)] [نشط ☑]
[وصف]
[لوحة السحب: معيار + أقسام numeric/percentage/grade] [كانفاس وزن 0% + توزيع تلقائي] [خصائص]
```

### إنشاء تقييم وحدة
```
[نوع: فرع/قسم ▼] [الوحدة: الرياض ▼ (Branch::getAllWithDepartments)] [القالب: وحدة-شهري ▼ (api/templates/by-scope)] [الدورية: شهري 01-2026 ▼ (schedules WHERE unit_id)] → [بدء]
```

### التعبئة (Fill)
```
شارة [مسودة/مراجعة/معتمد] + ترويسة (للموظف: اسم/منصب/فرع) أو (للوحدة: فرع الرياض | الفترة: شهري 01-2026)
Accordion معايير/مهام + live-scoreboard + شريط: [حفظ مسودة] [إرسال للمراجعة] أو [اعتماد] حسب الصلاحية
```

### لوحة التقارير الموحدة
```
فلتر: [نوع: الكل/أفراد/دورات/وحدات] [فترة] [فرع] → Radar(المهام) + Line(زمني) + جدول مقارنة + [طباعة A4][Excel]
```

---

## 4. المسارات والمتحكمات

| المسار | المتحكم@دالة | الصلاحية |
|---|---|---|
| GET builder/create, builder/edit/:id | BuilderController@index | templates.manage |
| POST builder/save | BuilderController@save | templates.manage + units.manage |
| GET evaluations/create?type=&entity_id=&context=&schedule= | EvaluationController@create | evaluations.create / units.create |
| POST evaluations/store | EvaluationController@store | نفس + فلتر نطاق |
| GET evaluations/:id/fill | EvaluationController@fill | evaluations.fill / units.fill |
| POST evaluations/submit | EvaluationController@submit | نفس |
| POST evaluations/review/:id (approve/reject) | EvaluationController@review | units.review |
| GET api/templates/by-scope/:type/:id | ApiController@getTemplatesByScope | auth |
| GET/POST evaluation_schedules/* | ScheduleController@index/store/update | units.manage |
| GET reports/unified | ReportsController@unified | reports.view.all (+ نطاق) |

---

## 5. الصلاحيات والنطاق (RBAC + SQL Scope)

### المصفوفة
| الدور | فردي | موظف بدورة | دورة ككيان | وحدة | اعتماد |
|---|---|---|---|---|---|
| إدارة/HR | الكل | الكل | الكل | الكل | نعم |
| مدير فرع | فرعه فقط | فرعه | دورات فرعه | فرعه+أقسامه | لا |
| مدير قسم | قسمه | قسمه | دورات قسمه | قسمه | لا |
| موظف | ذاتي | ذاتي بدورة شارك بها | مشاهدة | لا | لا |

### حقن WHERE التلقائي (أمان)
```php
// في كل Model::getFiltered($scopeBranchId,$scopeDeptId)
$where = "WHERE status='approved'";
if (!Auth::userHasPermission('reports.view.all')) {
  $where .= " AND entity_id IN (SELECT id FROM employees WHERE branch_id=:scope)";
  $bind[':scope']=$scopeBranchId;
}
// Evaluation::findById() يفحص: if (!isAdmin && entity_type='branch' && entity_id != scope) throw 403
// Api يفحص Auth::can() قبل JSON - تخمين /evaluations/show/999 → 403 حتى لو الرابط صحيح
```

---

## 6. التحليلات والتقارير

### استعلامات لوحة التحكم
```sql
-- Radar: متوسط كل مهمة
SELECT cs.section_name, AVG(ea.normalized_score) avg FROM evaluation_answers ea 
JOIN evaluations e ON ea.evaluation_id=e.id JOIN criteria_sections cs ON ea.section_id=cs.id
WHERE e.entity_type=:t AND e.entity_id IN (:ids) AND e.status='approved' AND e.evaluation_date BETWEEN :s AND :e
GROUP BY cs.id;

-- Line: زمني
SELECT DATE_FORMAT(evaluation_date,'%Y-%m') m, AVG(final_score) avg FROM evaluations 
WHERE entity_type=:t AND status='approved' GROUP BY m;

-- تقاطع: وحدة vs متوسط أفرادها
branchAvg = SELECT AVG(final_score) WHERE entity_type='branch' AND entity_id=:branchId;
employeeAvg = SELECT AVG(final_score) WHERE entity_type='employee' AND entity_id IN (SELECT id FROM employees WHERE branch_id=:branchId);
```

**الظهور:** `reports/unified` جدول `الوحدة | متوسط الوحدة | متوسط الأفراد | الفرق` + `Radar` و `Line` بنفس `Chart.js` الحالي.

---

## 7. Excel

| النوع | الأعمدة المطلوبة | تحقق |
|---|---|---|
| موظف | emp_code, template_id, section_id:raw_value | `emp_code EXISTS, section_id IN template` |
| وحدة | unit_code (branch name), period (2026-01), template_id, section_id:raw_value | `unit EXISTS, period matches schedule, section IN template` |
| دورة | course_id, template_id, section_id:raw_value | `course EXISTS` |

نفس `import_batches` + `is_active` للورقة - لا كسر.

---

## 8. خطة التنفيذ ومعايير القبول

| المرحلة | المدة | معيار القبول |
|---|---|---|
| 0 تحضير | 3 أيام | هجرتان + قالبان + findByScope يعمل - فردي حر لا يتأثر |
| 1 وحدات | 7 أيام | إنشاء/تعبئة وحدة دورية `draft→review→approved` + تظهر في unified |
| 2 دورات مرنة | 7 أيام | تقييم موظف داخل دورة + تقييم دورة ككيان بقالب مرن - القديم للقراءة فقط |
| 3 تقارير وتحليل + Excel | 7 أيام | Radar/Line يقارن وحدة vs أفراد + Excel وحدات/دورات + طباعة A4 |

**المرجع الذهبي:** هذه الوثيقة تمنع الاجتهاد - أي انحراف عن `target_type` أو الحالات أو `WHERE` النطاق يعتبر خرقاً.

---

## 9. ملحق: منشئ القوالب - 6 أنواع عامة قابلة لإعادة الاستخدام (القلب النابض)

### 9.1 الأنواع الستة - حقول target_type

| # | نوع القالب | target_type | target_entity_id | unit_scope | الوصف | مثال |
|---|---|---|---|---|---|---|
| 1 | موظف فردي عام | `employee` | `NULL` | NULL | أي موظف - كما الآن + `target_position_id` اختياري للمنصب | "تقييم الأداء العام" |
| 2 | موظف داخل دورة | `employee_in_course` | `NULL` | NULL | أي موظف داخل أي دورة - لا يرتبط بدورة محددة | "تقييم مشارك في دورة" |
| 3 | موظف داخل ورشة | `employee_in_workshop` | `NULL` | NULL | أي موظف داخل أي ورشة | "تقييم مشارك في ورشة" |
| 4 | دورة ككيان | `course` | `NULL` | NULL | أي دورة ككيان مستقل | "جودة تنظيم الدورة" |
| 5 | ورشة ككيان | `workshop` | `NULL` | NULL | أي ورشة ككيان | "فاعلية الورشة" |
| 6 | وحدة تنظيمية | `unit` | `NULL` | `branch`/`department` | أي فرع أو قسم - يبقى NULL ليكون عاماً | "أداء الوحدة الشهري" |

**العامية:** `target_entity_id` يظل `NULL` دائماً في القالب - لا يمتلئ إلا عند **التقييم الفعلي** `evaluations(entity_type, entity_id, context_id)` حيث `entity_id=emp.id` أو `branch.id` المحدد

**قاعدة البيانات:**
```sql
-- لا حاجة لـ target_entity_id محدد - العامية هي الأساس
-- findByScope() يفلتر: WHERE target_type=:type AND is_active=1 (بدون entity_id)
-- للتوافق: target_position_id يعامل كـ employee عام لمنصب محدد
```

### 9.2 واجهة المنشئ (Builder) - اختيار النوع + تسميات ديناميكية

```
[اسم القالب*] [نوع القالب: ▼ 6 خيارات] [وصف]
─────────────────────────────────────────
نوع القالب:
○ موظف فردي        → تسميات: معيار / بند
○ موظف في دورة     → تسميات: معيار مشاركة / هدف تدريبي
○ موظف في ورشة     → تسميات: معيار مشاركة / هدف
○ دورة             → تسميات: محور / عنصر جودة
○ ورشة             → تسميات: محور / فقرة
○ وحدة تنظيمية     → تسميات: مهمة تشغيلية / هدف تشغيلي (بدل معيار/بند)

[لوحة السحب: معيار/مهمة + أقسام] [كانفاس] [خصائص]
```

**المنطق البرمجي `builder/index.php + public/js/builder.js`:**
```js
const labelMap = {
  employee: {criterion:'معيار', section:'بند'},
  employee_in_course: {criterion:'معيار مشاركة', section:'هدف تدريبي'},
  employee_in_workshop: {criterion:'معيار مشاركة', section:'هدف'},
  course: {criterion:'محور', section:'عنصر'},
  workshop: {criterion:'محور', section:'فقرة'},
  unit: {criterion:'مهمة تشغيلية', section:'هدف تشغيلي'}
};
target_type.addEventListener('change', e=>{ 
  const l=labelMap[e.target.value]; 
  document.querySelectorAll('[data-label-criterion]').forEach(el=>el.textContent=l.criterion);
  // لا تغيير في saveStructure() - نفس criteria/section_weight
});
```
- `POST builder/save` يرسل `target_type` فقط - `BuilderController:save()` يخزن `target_type` و `target_entity_id=NULL`

### 9.3 المنطق في التعبئة (Fill) - فلترة تلقائية

| عند بدء تقييم | الكود يفلتر | القوالب المعروضة |
|---|---|---|
| موظف فردي `employee/5` | `WHERE target_type='employee' AND (target_position_id IS NULL OR =emp.position_id)` (`Template::findByScope`) | قوالب النوع 1 فقط |
| موظف داخل دورة `employee/5 + course/10` | `WHERE target_type='employee_in_course'` | قوالب النوع 2 فقط |
| موظف داخل ورشة | `WHERE target_type='employee_in_workshop'` | النوع 3 |
| دورة `course/10` | `WHERE target_type='course'` | النوع 4 |
| ورشة `workshop/3` | `WHERE target_type='workshop'` | النوع 5 |
| وحدة `branch/2 + schedule/7` | `WHERE target_type='unit' AND (unit_scope IS NULL OR =branch)` | النوع 6 |

```php
// ApiController::getTemplatesByScope($entityType, $contextType)
if ($contextType==='course') $type='employee_in_course';
elseif ($contextType==='workshop') $type='employee_in_workshop';
else $type=$entityType; // employee/course/workshop/unit
return $templateModel->findByScope($type);
```

### 9.4 بناء واحد يخدم ستة سيناريوهات بلا ازدواجية
- منشئ واحد، 6 خيارات، `criteria_sections` واحد - لا جدول مهام منفصل
- لا حاجة لقالب لكل فرع/دورة - `target_entity_id NULL` يجعله عاماً - التحديد يحدث عند `evaluations/entity_id` فقط
- التقارير `WHERE template.target_type` تقارن تلقائياً

**هذا الملحق يجعل المنشئ القلب النابض المرن - اعتماده مع الوثيقة يطلق المرحلة 0.**
