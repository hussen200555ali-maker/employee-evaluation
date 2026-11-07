# توجيه متابعة — CD-026.1
## إعادة الاختبارات بعد إصلاح العيوب الثلاثة + تنظيف audit_logs

| الحقل | القيمة |
|---|---|
| **من** | كبير المطورين |
| **إلى** | المساعد المطور |
| **المرجع** | CD-026.1 (Follow-up) |
| **آخر commit** | `c2e2caf` |
| **رداً على** | تقريرك المؤرخ 2026-11-08 00:10 |

---

## ١) الإصلاحات الثلاثة (commit `c2e2caf`)

### العيب ١ — الاختبار القاتل (`exit()` يقتل المشغّل)
**السبب:** `testApiControllerRequiresAuth` ينشئ `new ApiController()` فيستدعي الـ constructor `exit()`.
**الإصلاح:** أُعيدت كتابته بـ **subprocess** (`runCli`): يكتب سكربت مؤقت بلا جلسة، يشغّله، ويتحقق أن المخرج يحوي "غير مصرح".

### العيب ٢ — توقّعان قديمان (`5.0` بدل `100.0`)
**السبب:** CD-026 غيّر المقياس الوصفي من `numeric_value` (1-5) إلى `max_value` (0-100).
**الإصلاح:** تحديث التوقّع في `DescriptiveTemplatesTest` و`DynamicBulkRenderingTest` من `5.0` → `100.0`.

### العيب ٣ — كلمات مرور قديمة في audit_logs
**السبب:** 3 صفوف (id 364-366) ما زالت تحوي `"password":"..."` في details JSON.
**الإصلاح:** ترحيل جديد `2026_11_08_CD026_1_clean_audit_logs.sql` يستخدم `JSON_REMOVE` لإزالة مفتاح `$.password` نهائياً.

---

## ٢) خطوات إعادة التحقق

### الخطوة ١ — مزامنة
```bash
cd C:\xampp\htdocs\employee-evaluation
git fetch --all
git reset --hard origin/master
git log --oneline -1
# المتوقع: c2e2caf fix(CD-026.1): three test defects + audit_logs cleanup
```

### الخطوة ٢ — ترحيل تنظيف audit_logs (إلزامي)
```bash
mysql -u root employee_evaluation_uat < database/migrations/2026_11_08_CD026_1_clean_audit_logs.sql
```

**تحقق:**
```sql
SELECT id, details FROM audit_logs
WHERE details LIKE '%password%' AND details NOT LIKE '%password_changed%';
-- المتوقع: 0 صفوف (نظيف)
```

### الخطوة ٣ — الاختبارات
```bash
php tests/runner.php
```
**المتوقع الآن: 148/148 PASS.**

---

## ٣) تقرير موجز

```
تقرير إعادة اختبار CD-026.1 — المساعد المطور
==============================================
التاريخ: YYYY-MM-DD HH:mm
آخر commit: ______

١. audit_logs تنظيف:
   - صفوف تحوي password: ______ (المتوقع 0)

٢. الاختبارات:
   - النتيجة: ___/148 PASS

ملاحظات: ______
```

**بانتظار تأكيدك لـ 148/148 لإغلاق CD-026.**

— كبير المطورين
