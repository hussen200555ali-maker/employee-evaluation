# توجيه تنفيذي — CD-026 / Sprint 7.0
## التنفيذ الشامل: أمن + تدريب + مهارات + محرك — تشغيل + اختبار + UAT

| الحقل | القيمة |
|---|---|
| **من** | كبير المطورين |
| **إلى** | المساعد المطور |
| **المرجع** | CD-026 — Sprint 7.0 |
| **آخر commit** | `497c967` |
| **نوع المهمة** | ترحيل + اختبارات + UAT |

---

## ١) ما تم تنفيذه

| القسم | التغيير |
|---|---|
| **أمن P0** | إزالة plaintext من audit_logs؛ ApiController بمصادقة؛ backup GET→POST؛ 10 صلاحيات مفقودة |
| **تدريب** | `course_participants` + `workshop_participants` (junction tables)؛ `skill` في ENUM |
| **محرك** | الوصفي يُحوّل لـ 0-100 (max_value) بدل 1-5 (numeric_value) |

---

## ٢) خطوات الإعداد

### الخطوة ١ — مزامنة
```bash
cd C:\xampp\htdocs\employee-evaluation
git fetch --all
git reset --hard origin/master
git log --oneline -1
# المتوقع: 497c967 feat(CD-026)...
```

### الخطوة ٢ — بوابة الترميز
```bash
php check-encoding.php --path=app
# exit=0
```

### الخطوة ٣ — الترحيل (إلزامي، ترحيلان)
```bash
mysql -u root employee_evaluation < database/migrations/2026_11_08_CD026_missing_permissions.sql
mysql -u root employee_evaluation < database/migrations/2026_11_08_CD026_training_infra.sql
```

**تحقق:**
```sql
SHOW TABLES LIKE '%participants';
-- المتوقع: course_participants + workshop_participants
SHOW COLUMNS FROM form_templates LIKE 'target_type';
-- المتوقع: enum يحوي 'skill'
SELECT name FROM permissions WHERE name IN ('evaluations.delete','training.evaluate','backup.manage');
-- المتوقع: 3 صفوف
```

### الخطوة ٤ — الاختبارات
```bash
php tests/runner.php
```
**المتوقع: 148/148 PASS** (141 + 7 جديدة في `CD026IntegrationTest`).

---

## ٣) معايير القبول

| # | المعيار | الحالة |
|---|---|---|
| 1 | الثغرات الأمنية الأربعة (P0) مغلقة | ☐ |
| 2 | `course_participants` + `workshop_participants` موجودان | ☐ |
| 3 | `skill` في `target_type` ENUM | ☐ |
| 4 | الصلاحيات المفقودة موجودة | ☐ |
| 5 | الوصفي يُحوّل لـ 0-100 (ممتاز→100) | ☐ |
| 6 | AveragesEngine يتجاهل NULL | ☐ |
| 7 | ApiController يتطلب مصادقة (401) | ☐ |
| 8 | 148/148 PASS | ☐ |
| 9 | بوابة الترميز exit=0 | ☐ |

---

## ٤) تقرير موجز

```
تقرير CD-026 — المساعد المطور
===============================
التاريخ: YYYY-MM-DD HH:mm
آخر commit: ______

الترحيل:
   - participants tables: ✓/✗
   - skill in ENUM: ✓/✗
   - permissions: ✓/✗

الاختبارات: ___/148 PASS

ملاحظات: ______
```

**بانتظار تأكيدك.**

— كبير المطورين
