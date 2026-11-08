# CD-029 — توثيق التنفيذ
## إكمال الربط بين التقييمات والدورات/الورش

| الحقل | القيمة |
|---|---|
| **المرجع** | CD-029 |
| **تاريخ التنفيذ** | 2026-11-08 |
| **عدد الاختبارات** | 151 (148 + 3 جديدة) |

---

## ما تم تنفيذه

### 1. الواجهة الفردية (EvaluationController::submit)
- قراءة `$_POST['context_course_id']` / `context_workshop_id`
- حفظ في `evaluations.context_type` + `context_id` عبر `saveEvaluationContext()`
- **إلزامي:** إن كان `target_type = employee_in_course/workshop` ولم يُرسل context_id → رفض الحفظ + Flash error
- بعد الحفظ الناجح: `syncCourseParticipant()` / `syncWorkshopParticipant()` تُحدّث/تُدرج سجل مشاركة → `status='completed'`

### 2. الواجهة الجماعية (bulk-create.php + BulkEvaluationController)
- **UI:** حقل `contextSelectWrap` ديناميكي يظهر عند `target_type = employee_in_course/workshop`
  - يجلب قائمة الدورات عبر `/api/training/courses/list`
  - شارة ذهبية `contextBadge` تُظهر اسم الدورة المختارة
- **Backend:** `bulkSave()` يقرأ `context_id` + `context_type` من JSON payload
  - يحفظهما في كل تقييم
  - `syncParticipant()` تُحدّث `course_participants` / `workshop_participants`
- **saveSingle:** يرسل `context_id` + `context_type` في FormData

### 3. course_participants
- عند إكمال تقييم دورة (فردي أو جماعي):
  - إن وُجد سجل → UPDATE status='completed', completed_at=NOW()
  - إن لم يوجد → INSERT (status='completed')
- نفس المنطق لـ `workshop_participants`

### 4. الاختبارات (3 جديدة في CD029CourseLinkingTest)
1. `testSaveSingleWithContextIdSavesToEvaluation` — context_type/context_id محفوظة
2. `testSyncParticipantInsertsNewRow` — course_participants سجل جديد
3. `testBulkCreateHasContextSelectElement` — HTML element موجود + context_id في payload

— كبير المطورين
