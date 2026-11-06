# توجيه متابعة نهائي — CD-022 / Sprint 4.0
## إعادة التحقق من السيناريوَين أ و ج بعد إصلاح العيبين المتبقيين

| الحقل | القيمة |
|---|---|
| **من** | كبير المطورين (Lead Developer) |
| **إلى** | المساعد المطور (Assistant Developer) |
| **المرجع** | CD-022 — Sprint 4.0 (Final follow-up) |
| **تاريخ الإصدار** | 2026-11-06 |
| **آخر commit على `origin/master`** | `24d0929` |
| **رداً على** | تقريرك المؤرخ 2026-11-06 22:16 |
| **الحالة الحالية** | 132/132 PASS ✅ — عيبان فقط في الواجهة |

---

## ١) تقدير

تقريرك الثاني دقيق جداً: 132/132 اختبار PASS، وحدّدت العيبين المتبقيين بسطرية دقيقة مع الدليل الحي من قاعدة البيانات (`normalized_score=0.00` مقابل `4.00` من الاختبار الآلي). كما أثبتّ فرضية الإصلاح يدوياً بحقن هيدر CSRF. هذا مستوى تشخيصي احترافي.

طبّقتُ العيبين في commit `24d0929` — كلاهما في ملف الواجهة فقط (سطران منطقيان).

---

## ٢) الإصلاحات المُطبَّقة (commit `24d0929`)

### العيب أ — الدرجة تُحفظ صفر (serialization)
**الملف:** `app/Views/evaluations/bulk-create.php` — دالة `saveSingle()`

**السبب:** كان السطر يرسل الكائن كاملاً `{score:4}` بدلاً من قيمته العددية:
```js
// قبل (مكسور):
for (var key in results) formData.append('results[' + key + '][score]', results[key]);
// results[key] = {score:4}  →  يُرسل "[object Object]"  →  PHP (float)0
```

**الإصلاح:** تفريع حسب نوع الإدخال:
```js
// بعد (صحيح):
for (var key in results) {
    var entry = results[key];
    if (entry && typeof entry === 'object') {
        if (entry.score !== undefined) formData.append('results[' + key + '][score]', entry.score);
        if (entry.text !== undefined)  formData.append('results[' + key + '][text]', entry.text);
    } else if (key === 'grade_label') {
        formData.append('final_grade_label', entry);
    }
}
```
يغطي: القوالب الرقمية (`score`)، الوصفية (`text`)، والدرجة اليدوية (`final_grade_label` كحقل مستقل).

### العيب ج — حفظ الكل يُعاد توجيهه (CSRF middleware)
**الملف:** نفس الملف — دالة `saveAll()`

**السبب:** الـ middleware العالمي `Auth::enforceCsrfToken()` في `public/index.php` يسبق الـ Controller، ويقرأ CSRF من `$_POST` أو هيدر `HTTP_X_CSRF_TOKEN` (انظر `app/Core/Auth.php:249`). جسم JSON لا يملأ `$_POST`، فرفض الطلب قبل وصوله للإصلاح #5 السابق.

**الإصلاح:** إضافة الهيدرات التي يدعمها الـ middleware أصلاً:
```js
fetch(appUrl + '/evaluations/bulk-save', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,     // ← الـ middleware يقرأ هذا (Auth.php:249)
        'Accept': 'application/json'   // ← يضمن رد JSON عند أي خطأ
    },
    body: payload
})
```

> **ملاحظة:** الإصلاح #5 السابق في `bulkSave()` (قراءة `_token` من JSON) يبقى كطبقة دفاع ثانية، لكن الهيدر هو ما يحل الموضوع عند الـ middleware.

---

## ٣) ملاحظة حول فجوة التغطية (تقريرك بند ٨-٢)

أشيرتَ إلى أن `testSaveSingleCreatesCompletedEvaluationWithScore` يمر رغم عيب الواجهة لأنه يبني `$_POST` مباشرة من PHP. هذا تحليل صحيح. **الإصلاح المُطبَّق يعالج السبب الجذري في الواجهة**، أما اختبار JS serialization نفسه فيتطلب متصفحاً headless (خارج نطاق المشروع حالياً). طبقة الـ PHP coverage صحيحة وتغطي الـ Controller.

---

## ٤) خطوات إعادة التحقق (السيناريوَين أ و ج فقط)

### الخطوة ١ — مزامنة
```bash
cd C:\xampp\htdocs\employee-evaluation
git fetch --all
git reset --hard origin/master
git log --oneline -1
# المتوقع: 24d0929 fix(CD-022): two remaining UAT defects — score=0 and bulk-save CSRF
```

### الخطوة ٢ — لا ترحيل، لا اختبارات جديدة
- الترحيل: لا تغيير (العمود `profile_status` موجود).
- الاختبارات: `php tests/runner.php` يجب أن يبقى **132/132 PASS** (لا اختبارات جديدة، فقط إصلاح JS).
- شغّلها للتأكيد فقط إن أحببت.

### الخطوة ٣ — UAT للسيناريوَين الفاشلَين فقط

**السيناريو أ (تقييم فردي — التحقق من الدرجة):**
1. افتح `/employee-evaluation/evaluations/bulk-create`
2. اختر قالباً رقمياً → أضف موظفاً → اختر الدرجة **4**
3. اضغط «حفظ» على البطاقة → يجب أن تتحول خضراء
4. **تحقق من DB:**
   ```sql
   SELECT id, section_id, raw_value, normalized_score
   FROM evaluation_answers
   WHERE evaluation_id = (SELECT MAX(id) FROM evaluations)
   ORDER BY id DESC;
   -- المتوقع: normalized_score = 4.00 (وليس 0.00)
   ```
5. **لقطة 1:** البطاقة الخضراء + استعلام DB يُظهر `normalized_score = 4.00`.

**السيناريو ج (حفظ الكل — بدون حقن يدوي):**
1. أضف 3 موظفين واملأ درجاتهم (دون حفظ فردي)
2. اضغط «حفظ الجميع»
3. **المتوقع:** تنبيه `تم حفظ 3 تقييم` + جميع البطاقات خضراء + العداد `محفوظ: 3`
4. **تحقق من DB:**
   ```sql
   SELECT COUNT(*) AS saved_today FROM evaluations
   WHERE evaluation_date = CURDATE() AND status = 'completed';
   -- المتوقع: العدد زاد بـ 3 منذ آخر جولة
   ```
5. **لقطة 2:** جميع البطاقات خضراء + العداد `محفوظ: 3` + تنبيه النجاح.

---

## ٥) معايير القبول النهائية

| # | المعيار | الحالة |
|---|---|---|
| 1 | `git log` يُظهر `24d0929` كآخر commit | ☐ |
| 2 | `tests/runner.php` → 132/132 PASS | ☐ |
| 3 | السيناريو أ: `normalized_score = 4.00` (وليس 0) | ☐ |
| 4 | السيناريو ج: حفظ الكل ينجح من الواجهة بدون حقن هيدر يدوي | ☐ |
| 5 | لقطتان حيتان (البطاقة الخضراء + DB، وكل البطاقات خضراء + العداد) | ☐ |

---

## ٦) تقرير موجز (أرسله عند الانتهاء)

```
تقرير التحقق النهائي CD-022 — المساعد المطور
==============================================
التاريخ: YYYY-MM-DD HH:mm
آخر commit: ______

١. الاختبارات: ___/132 PASS

٢. السيناريو أ (الدرجة):
   - normalized_score = ______ (المتوقع 4.00)
   - ✓/✗

٣. السيناريو ج (حفظ الكل):
   - عدد التقييمات المحفوظة: ______
   - ✓/✗

٤. اللقطات: (2 مرفقة)

٥. ملاحظات: ______
```

---

**بانتظار تأكيدك النهائي لإغلاق CD-022.**

— كبير المطورين
