# توجيه متابعة — CD-023 (إصلاح FK constraint)
## إعادة تشغيل الاختبارات بعد إضافة created_by_user_id

| الحقل | القيمة |
|---|---|
| **من** | كبير المطورين |
| **إلى** | المساعد المطور |
| **المرجع** | CD-023 — Sprint 5.0 (Follow-up #2: FK fix) |
| **آخر commit** | `160c7a6` |
| **رداً على** | تقرير إعادة الاختبار (135/136 PASS) |

---

## ١) الإصلاح

في تقريرك حدّدت الفشل بدقة: `SQLSTATE[23000]: 1452` على قيد FK `eval_fk_creator` (`created_by_user_id` → `users(id)`). إدراجات الاختبار الخام في `evaluations` لم تُزوِّد هذا العمود.

**الإصلاح في commit `160c7a6`:**
- أُضيفت دالة `fetchAnyUserId()` تُعيد أول `users.id` صالح.
- إدراجا `evaluations` في `makeNumericEvaluationWithSection` و`testOverallGradeCalculatesCorrectlyForDescriptive` أصبحا يُضيفان عمود `created_by_user_id` مربوطاً بمعرّف مستخدم صالح.

يؤثر فقط على الاختبارَين 4 و5.

---

## ٢) إعادة التشغيل

```bash
cd C:\xampp\htdocs\employee-evaluation
git fetch --all
git reset --hard origin/master
git log --oneline -1
# المتوقع: 160c7a6 fix(CD-023): add created_by_user_id to test evaluations INSERTs

php tests/runner.php
```

**المتوقع الآن: 136/136 PASS.**

---

## ٣) تقرير موجز

```
تقرير إعادة اختبار CD-023 (FK fix) — المساعد المطور
=====================================================
التاريخ: YYYY-MM-DD HH:mm
آخر commit: ______
النتيجة: ___/136 PASS
ملاحظات: ______
```

**بانتظار تأكيدك لـ 136/136 لإغلاق CD-023.**

— كبير المطورين
