# CD-026 — توثيق التنفيذ
## التنفيذ الشامل: ربط الموظف بالدورات، توحيد التقييمات، إضافة تقييم المهارات، إغلاق الثغرات الأمنية

| الحقل | القيمة |
|---|---|
| **المرجع** | CD-026 — Sprint 7.0 |
| **تاريخ التنفيذ** | 2026-11-08 |
| **عدد الاختبارات** | 148 (141 + 7 جديدة) |

---

## ١) الإصلاحات الأمنية (P0)

### A1: إزالة كلمات المرور النصية من audit_logs
- **الملف:** `app/Controllers/Admin/UserController.php` (L388)
- **الإصلاح:** استبدال `$data` (المتضمن `password` plaintext) بـ `$auditData` يحوي `password_changed: bool` فقط.

### A2: تأمين ApiController
- **الملف:** `app/Controllers/ApiController.php`
- **الإصلاح:** أُضيف `__construct()` يفحص `Auth::check()` → 401 JSON إن لم يُسجّل الدخول.

### A3: تحويل مسارات backup التدميرية GET→POST
- **الملف:** `routes/web.php` (L254-285)
- **الإصلاح:** كل مسارات الحذف/الاستعادة/الجدولة/التشفير أصبحت `POST` (تتطلب CSRF).

### A4: الصلاحيات المفقودة
- **الملف:** `database/migrations/2026_11_08_CD026_missing_permissions.sql`
- **الإصلاح:** إضافة 10 صلاحيات (`evaluations.delete/edit`, `training.evaluate`, `backup.manage`, `branches/department create/edit/delete`) + منحها للأدوار.

---

## ٢) البنية التحتية للتدريب

### B1: جداول المشاركة
- **الملف:** `database/migrations/2026_11_08_CD026_training_infra.sql`
- **جديد:** `course_participants` + `workshop_participants` (id, course/workshop_id, employee_id, status ENUM, enrolled_at, completed_at, UNIQUE(course_id+employee_id), FKs CASCADE).

### B2: نوع القالب المهاري
- **نفس الملف:** إضافة `skill` إلى `form_templates.target_type` ENUM.

---

## ٣) محرك الحسابات (G25)

### C: إصلاح خلط المقاييس
- **الملف:** `app/Models/Evaluation.php` — `calculateDescriptiveScore()`
- **الإصلاح:** التقييم الوصفي يُحوّل إلى مقياس **0-100** (عبر `max_value` من `grading_scales`) بدل **1-5** (`numeric_value`). ممتاز→100، جيد جداً→89.99، إلخ.
- **ملفات إضافية:** `EvaluationController::submit` + `BulkEvaluationController::saveResults` تستخدم نفس التحويل.
- **AveragesEngine** يتجاهل `final_score=NULL` أصلاً (L10-13).

---

## ٤) الاختبارات الجديدة (7)
1. `testAveragesEngineIgnoresNullFinalScore` — NULL يُستثنى
2. `testCourseParticipantsTableExists`
3. `testWorkshopParticipantsTableExists`
4. `testSkillTypeExistsInEnum`
5. `testApiControllerRequiresAuth` — 401 بدون جلسة
6. `testMissingPermissionsExist` — 3 صلاحيات حرجة
7. `testDescriptiveScoreIsOn100Scale` — "ممتاز"→100.0

---

## ٥) الضوابط المُلتزَم بها
- ✅ Auth.php — لم يُلمس (إلا UserController لإزالة plaintext)
- ✅ باني القوالب الأساسي — لم يُلمس (إلا ENUM skill في migration)
- ✅ AveragesEngine — يتجاهل NULL أصلاً؛ الإصلاح في calculateDescriptiveScore
- ✅ بوابة الترميز exit=0

— كبير المطورين
