# توجيه تنفيذي — CD-032 / Sprint 11.0
## سير العمل الكامل: إنشاء دورة → ربط موظفين → تقييم → ملف الموظف

| الحقل | القيمة |
|---|---|
| **من** | كبير المطورين |
| **إلى** | المساعد المطور |
| **المرجع** | CD-032 — Sprint 11.0 |
| **تاريخ الإصدار** | 2026-11-08 |
| **آخر commit** | `2e04ed8` |
| **نوع المهمة** | اختبارات + UAT + لقطات + استعلامات DB |

---

## ١) السياق

تم تحويل النماذج الأولية المعتمدة (CD-031، 10/10) إلى كود حي. هذا التوجيه يغطي **الرحلة الكاملة** من إنشاء الدورة حتى ظهور النتيجة في ملف الموظف.

**دورك:** مزامنة → اختبارات → UAT (5 سيناريوهات) → استعلامات DB → لقطات + تقرير.

> **قيد صارم:** لا تُعدِّل أي كود. تحقق ووثّق فقط.

---

## ٢) ما تم تنفيذه

| المكوّن | الجديد |
|---|---|
| **ربط الموظفين** | `enroll()` + `enrollStore()` (INSERT IGNORE) + `enrollRemove()` + view ببحث AJAX |
| **تفاصيل الدورة** | `show()` بـ 3 KPIs (إجمالي/مكتمل/بانتظار) + جدول + رابط تقييم |
| **API** | `pendingEmployees()` — JSON يحوي pending في دورة |
| **جماعي auto-load** | عند اختيار الدورة → سحب المسجلين تلقائياً كبطاقات |
| **مسارات** | 5 جديدة: enroll GET/POST + remove POST + show GET + API GET |

### المسارات الجديدة
| الطريقة | المسار | Controller |
|---|---|---|
| GET | `training/courses/enroll/:id` | `TrainingCourseController@enroll` |
| POST | `training/courses/enroll/store` | `TrainingCourseController@enrollStore` |
| POST | `training/courses/enroll/remove` | `TrainingCourseController@enrollRemove` |
| GET | `training/courses/:id` | `TrainingCourseController@show` |
| GET | `api/courses/:id/pending-employees` | `TrainingCourseController@pendingEmployees` |

---

## ٣) خطوات الإعداد

### الخطوة ١ — مزامنة
```bash
cd C:\xampp\htdocs\employee-evaluation
git fetch --all
git reset --hard origin/master
git log --oneline -1
# المتوقع: 2e04ed8 feat(CD-032): end-to-end workflow
```

### الخطوة ٢ — لا ترحيل جديد
لا migration في CD-032. الجداول `course_participants` و `training_courses` موجودة من CD-026.

### الخطوة ٣ — الاختبارات
```bash
php tests/runner.php
```
**المتوقع: 154/154 PASS** (151 + 3 جديدة في `CD032WorkflowTest`).

الاختبارات الجديدة:
| # | الاختبار | ما يتحقق منه |
|---|---|---|
| 1 | `testEnrollInsertsPendingAndPreventsDuplicates` | INSERT IGNORE يمنع التكرار؛ صفوف=2 |
| 2 | `testSyncUpdatesStatusToCompleted` | تحديث status→completed + completed_at |
| 3 | `testRoutesContainNewEndpoints` | 5 مسارات موجودة في routes/web.php |

### الخطوة ٤ — بوابة الترميز
```bash
php check-encoding.php --path=app  # exit=0
```

---

## ٤) سيناريوهات UAT (5 مسارات)

> **متطلبات مسبقة:** سجّل الدخول كـ Admin (صلاحية `training.manage` + `evaluations.create`).

### السيناريو أ — إنشاء دورة + ربط موظفين

**الخطوات:**
1. افتح `/employee-evaluation/training/courses/create`
2. أنشئ دورة جديدة (اختر catalog + فرع + قسم + تواريخ)
3. بعد الإنشاء، افتح `/employee-evaluation/training/courses/enroll/<course_id>`
4. **تحقق:** صفحة "ربط الموظفين بالدورة" تظهر + بطاقة معلومات الدورة (ذهبية)
5. اضغط "إضافة موظفين" → ابحث عن 3 موظفين → أضفهم
6. اضغط "تسجيل المحددين"
7. **المتوقع:** رسالة "تم تسجيل 3 موظف" + الجدول يُظهرهم بـ `status=بانتظار`

**تحقق من DB:**
```sql
SELECT cp.id, cp.employee_id, cp.status, e.full_name
FROM course_participants cp
JOIN employees e ON e.id = cp.employee_id
WHERE cp.course_id = <course_id>;
-- المتوقع: 3 صفوف، status='pending' لكل واحد
```

**📸 لقطة 1:** صفحة enroll تُظهر جدول الموظفين المسجلين (3، pending).

---

### السيناريو ب — تفاصيل الدورة + KPIs

**الخطوات:**
1. افتح `/employee-evaluation/training/courses/<course_id>`
2. **تحقق:** 3 بطاقات KPI:
   - إجمالي المسجلين: 3
   - تم التقييم: 0
   - بانتظار التقييم: 3
3. الجدول يُظهر الموظفين الثلاثة بدرجة "—"
4. رابط "تقييم جميع الموظفين المتبقين" يظهر (لأن pending > 0)

**📸 لقطة 2:** صفحة تفاصيل الدورة بـ 3 KPIs + جدول.

---

### السيناريو ج — تقييم جماعي تلقائي (Auto-Load)

**الخطوات:**
1. افتح `/employee-evaluation/evaluations/bulk-create`
2. اختر قالباً بـ `target_type = employee_in_course` (مثل "التقييم الرقمي للدورة")
3. **تحقق:** حقل "📚 الدورة التدريبية" يظهر في شريط الإعداد
4. اختر الدورة التي أنشأتها في السيناريو أ
5. **المتوقع:** الموظفون المسجلون (pending) يُحمَّلون تلقائياً كبطاقات!
6. املأ درجاتهم (مثل 85، 90، 78)
7. اضغط "حفظ الجميع"
8. **المتوقع:** جميع البطاقات خضراء + عدّاد "محفوظ: 3"

**تحقق من DB:**
```sql
-- context_id محفوظ في كل تقييم
SELECT id, evaluated_employee_id, context_type, context_id, status, final_score
FROM evaluations
WHERE template_id = <template_id>
ORDER BY id DESC LIMIT 3;
-- المتوقع: context_type='course', context_id=<course_id>, status='completed'

-- course_participants محدَّث إلى completed
SELECT cp.employee_id, cp.status, cp.completed_at
FROM course_participants cp
WHERE cp.course_id = <course_id>;
-- المتوقع: status='completed' + completed_at ليس NULL لكل الـ3
```

**📸 لقطة 3:** شاشة bulk-create تُظهر الدورة المختارة + الموظفين المحمَّلين تلقائياً + البطاقات الخضراء.

---

### السيناريو د — تقييم فردي مرتبط بدورة

**الخطوات:**
1. أنشئ تقييماً فردياً لقالب `employee_in_course` (إن لم يكن موجوداً)
2. افتح `/employee-evaluation/evaluations/<id>/fill`
3. **تحقق:** بطاقة "الدورة التدريبية" تظهر في الأعلى (حدود ذهبية) + إلزامية
4. اختر الدورة
5. املأ التقييم
6. اضغط "حفظ وإرسال"
7. **المتوقع:** رسالة نجاح

**تحقق من DB:**
```sql
SELECT context_type, context_id, status, final_score
FROM evaluations WHERE id = <evaluation_id>;
-- المتوقع: context_type='course', context_id>0, status='completed'
```

**📸 لقطة 4:** شاشة fill.php تُظهر بطاقة الدورة + بطاقة المقيّم + زر الحفظ.

---

### السيناريو هـ — ملف الموظف النهائي

**الخطوات:**
1. افتح `/employee-evaluation/employees/profile/<employee_id>` لأحد الموظفين الذين قيّمتهم
2. **تحقق:** تبويب التقييمات يُظهر:
   - نوع التقييم (سنوي / دورة / مهاري)
   - الدرجة
   - التقدير
   - اسم الدورة (إن وُجد)
3. بطاقات KPI تُظهر المتوسطات

**📸 لقطة 5:** ملف الموظف يُظهر التقييمات مع ربط الدورة.

---

## ٥) جدول التحقق الشامل

| # | البند | أداة التحقق | المتوقع |
|---|---|---|---|
| 1 | `git log` عند `2e04ed8` | `git log --oneline -1` | `2e04ed8` |
| 2 | 154/154 PASS | `php tests/runner.php` | exit=0 |
| 3 | بوابة الترميز | `php check-encoding.php --path=app` | exit=0 |
| 4 | enroll تضيف pending | استعلام DB | 3 صفوف، status='pending' |
| 5 | enroll يمنع التكرار | محاولة إضافة مكرر | صفوف لا تزيد |
| 6 | show يعرض KPIs | فتح `/training/courses/:id` | 3/0/3 |
| 7 | API pending-employees | `curl` أو Network tab | JSON array |
| 8 | bulk auto-load | اختيار دورة في bulk-create | بطاقات تظهر تلقائياً |
| 9 | context_id محفوظ | استعلام evaluations | context_type='course' |
| 10 | course_participants → completed | استعلام بعد الحفظ | status='completed' |

---

## ٦) معايير القبول

| # | المعيار | الحالة |
|---|---|---|
| 1 | `git log` يُظهر `2e04ed8` | ☐ |
| 2 | 154/154 PASS | ☐ |
| 3 | بوابة الترميز exit=0 | ☐ |
| 4 | إنشاء دورة يحفظ الفرع/القسم | ☐ |
| 5 | enroll يضيف pending + يمنع التكرار | ☐ |
| 6 | show يعرض KPIs صحيحة | ☐ |
| 7 | bulk auto-load يسحب pending تلقائياً | ☐ |
| 8 | context_id محفوظ في التقييمات | ☐ |
| 9 | course_participants → completed بعد التقييم | ☐ |
| 10 | 5 لقطات شاشة حية | ☐ |

---

## ٧) استعلامات SQL للتقرير

انسخ هذه واعملها بعد كل سيناريو وأرفق النتائج:

```sql
-- 1) المسجلون في الدورة
SELECT cp.employee_id, e.full_name, cp.status, cp.completed_at
FROM course_participants cp JOIN employees e ON e.id = cp.employee_id
WHERE cp.course_id = <COURSE_ID>;

-- 2) التقييمات المرتبطة بالدورة
SELECT id, evaluated_employee_id, context_type, context_id, status, final_score
FROM evaluations WHERE context_id = <COURSE_ID> AND context_type = 'course';

-- 3) حالة المشاركين بعد التقييم الجماعي
SELECT cp.employee_id, cp.status, cp.completed_at, ev.final_score
FROM course_participants cp
LEFT JOIN evaluations ev ON ev.evaluated_employee_id = cp.employee_id AND ev.context_id = cp.course_id
WHERE cp.course_id = <COURSE_ID>;
```

---

## ٨) تقرير موجز

```
تقرير CD-032 — المساعد المطور
================================
التاريخ: YYYY-MM-DD HH:mm
آخر commit: ______

الاختبارات: ___/154 PASS
بوابة الترميز: exit=______

السيناريو أ (إنشاء + ربط):
   - دورة أنشئت: ✓/✗ (ID: ______)
   - موظفون مسجلون: ___/3 (pending)
   - التكرار ممنوع: ✓/✗

السيناريو ب (تفاصيل):
   - KPIs: إجمالي=___ مكتمل=___ بانتظار=___

السيناريو ج (جماعي auto-load):
   - الدورة المختارة: ______
   - موظفون محمَّلون تلقائياً: ___
   - بطاقات خضراء بعد الحفظ: ✓/✗
   - context_id محفوظ: ✓/✗ (قيمة: ______)
   - course_participants → completed: ✓/✗

السيناريو د (فردي):
   - بطاقة الدورة تظهر: ✓/✗
   - context_id محفوظ: ✓/✗

السيناريو هـ (ملف الموظف):
   - جدول التقييمات يُظهر الدورة: ✓/✗

اللقطات: (5 مرفقة)
استعلامات SQL: (مرفقة)
ملاحظات: ______
```

---

## ٩) القواعد الصارمة

- 🚫 **لا تُعدِّل أي كود** — تحقق ووثّق فقط.
- ✅ Ctrl+F5 إلزامي لكل شاشة.
- ✅ استخدم `<COURSE_ID>` الفعلي الذي أنشأته في السيناريو أ.
- ✅ إن لم تظهر قائمة الدورات في الجماعي، تحقق:
  ```sql
  SELECT id, template_name, target_type FROM form_templates WHERE target_type = 'employee_in_course';
  ```
- ✅ إن لم تظهر بطاقات تلقائياً عند اختيار الدورة، تحقق Console (F12)是否存在 errors.
- ✅ إن فشل `pendingEmployees` API، تحقق:
  ```bash
  curl http://localhost/employee-evaluation/api/courses/<id>/pending-employees
  ```

---

## ١٠) ملاحظات تقنية

- **`course_participants`** يستخدم UNIQUE(course_id, employee_id) — التكرار ممنوع على مستوى DB.
- **`INSERT IGNORE`** في `enrollStore` — الصف المكرر يُتجاهل بصمت.
- **API `pending-employees`** يتطلب جلسة (ApiController محمي بـ `Auth::check()` من CD-026).
- **`show()`** LEFT JOINs `evaluations` بـ `context_id` + `context_type='course'` لجلب الدرجة.
- **`loadPendingEmployees()`** في bulk-create.js يستدعي الـ API ثم يضيف كل موظف عبر `addEmployeeCard()`.
- **بطاقة المقيّم** موجودة في fill.php من CD-028 (4 حقول: اسم، صفة، تاريخ، ملاحظات).

**بانتظار تقريرك باللقطات الخمس واستعلامات DB.**

— كبير المطورين
