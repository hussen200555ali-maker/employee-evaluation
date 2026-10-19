# OOS-12 — نسخة رجوع (Backup)

## الوصف
توسيع `scripts/check-encoding.php` لدعم وسيط النطاق `--path` (delivery/app/all)
بدلًا من نطاق التسليم الوحيد، تمهيدًا لفحص كود التطبيق `app/**/*.php` + `app/**/*.inc`
مع إبقاء allowlist مكتبة `app/Libraries/pChart/`.

**القرار المعماري (المستشار 2026-10-19):** نطاق `pre-commit` يبقى **منفصلًا لكل
مستودع**؛ لا مسارات مطلقة.
- خطاف التسليم: `php scripts/check-encoding.php --path=delivery`.
- خطاف التطبيق (إن وُجد): `php ../CD-001_delivery/scripts/check-encoding.php --path=app`.

## الملفات
| الملف | الوصف | SHA256 |
|---|---|---|
| `check-encoding.pre-item12.php` | الأداة قبل التوسيع | `EA0B534BE9686AAF3629AA231CC220D24FD4353872F810E933F8BC73D0C4F173` |
| `install-hooks.pre-item12.php` | مُثبِّت الخطافات قبل التعديل | `715A6B66C770DDF253A1BF2F86063D7A2740649328A68C557EC9977E4D99A473` |

## كيفية الاسترجاع
1. انسخ الملفين إلى مواقعهما الأصلية:
   - `check-encoding.pre-item12.php` → `CD-001_delivery/scripts/check-encoding.php`
   - `install-hooks.pre-item12.php` → `CD-001_delivery/scripts/install-hooks.php`
2. تحقّق من SHA256 بعد النسخ.
3. أنشئ commit عكسيًا في مستودع التسليم (وتطبيق خطاف التسليم إن تغيّر).

## قياس «قبل» (مُثبَّت)
- النطاق الافتراضي (delivery): 35 ملفًا / 6,936 سطرًا · `exit=0` · الزمن = **0.73s**.
- `--path=app`: غير مدعوم → يُعامل كمسار صريح → «مسار غير موجود» · `exit=2`.
