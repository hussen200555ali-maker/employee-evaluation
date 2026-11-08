# CD-032 — توثيق التنفيذ
## سير العمل الكامل: إنشاء دورة → ربط موظفين → تقييم → ملف الموظف

| الحقل | القيمة |
|---|---|
| **المرجع** | CD-032 |
| **تاريخ التنفيذ** | 2026-11-08 |
| **عدد الاختبارات** | 154 (151 + 3 جديدة) |

---

## ما تم تنفيذه

### A. قاعدة البيانات
- `training_courses` و `training_workshops` يحتويان بالفعل على `branch_id` + `department_id` — لا migration مطلوب.
- `course_participants.status` ENUM('pending','completed','absent') موجود من CD-026.

### B. إدارة الدورات
1. **إنشاء الدورة** (`/training/courses/create`): موجود بالفعل مع حقول الفرع/القسم.
2. **ربط الموظفين** (`/training/courses/enroll/:id`) — **جديد**:
   - `enroll()`: عرض جدول المسجلين + بحث AJAX
   - `enrollStore()`: `INSERT IGNORE` (يمنع التكرار) + `status='pending'`
   - `enrollRemove()`: حذف مشارك
3. **تفاصيل الدورة** (`/training/courses/:id`) — **جديد**:
   - `show()`: 3 KPIs (إجمالي/مكتمل/بانتظار) + جدول + رابط "تقييم المتبقين"
4. **API** (`/api/courses/:id/pending-employees`) — **جديد**:
   - `pendingEmployees()`: JSON يحوي الموظفين `pending` في دورة (للجماعي)

### C. التقييم الفردي (fill.php)
- حقل اختيار الدورة موجود من CD-026.2
- بطاقة المقيّم موجودة من CD-028
- `submit()` يحفظ `context_type` + `context_id` من CD-029
- `syncCourseParticipant()` يحدّث `status='completed'` من CD-029

### D. التقييم الجماعي (bulk-create.php)
- حقل `contextSelectWrap` موجود من CD-029
- **CD-032 جديد:** `loadPendingEmployees()` — عند اختيار الدورة، يستدعي `/api/courses/:id/pending-employees` ويضيف الموظفين تلقائياً كبطاقات
- `bulkSave()` يحفظ `context_id` من CD-029

### E. ملف الموظف (profile.php)
- موجود بالفعل مع تبويبات + متوسطات + جدول تقييمات

### F. الاختبارات (3 جديدة في CD032WorkflowTest)
1. `testEnrollInsertsPendingAndPreventsDuplicates` — INSERT IGNORE يمنع التكرار
2. `testSyncUpdatesStatusToCompleted` — تحديث الحالة لـ completed
3. `testRoutesContainNewEndpoints` — المسارات الجديدة موجودة

— كبير المطورين
