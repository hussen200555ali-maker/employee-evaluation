# تحسين عرض تفصيلي أداء الموظف — enterprise

## الملف
- `app/Views/employees/evaluations_by_year.php` — اعادة بناء كاملة بتصميم شركة كبرى
- `app/Models/Evaluation.php` — تصحيح `getComparisonStatsForYear` بفلتر `status='completed'`
- `.opencode/plans/enterprise-performance-view-20260902.md` — الخطة

## التصميم
- ترويسة `navy #0f2a44` مع شعار HR و عنوان `تقييمات عام 2026م / 1447هـ` و `breadcrumb`
- بطاقة موظف مع افاتار و KPIs اربعة: متوسط السنة (محسوب بـ array_filter numeric)، اعلى درجة، متوسط القسم، متوسط الشركة
- شبكة `3 اعمدة` لكروت التقييم: رأس navy مع تاريخ، حلقة درجة conic-gradient، شريط تقدم، مقيّم، هجري
- الوان متناغمة مع الموقع: primary #0f766e، gold 4% فقط للـ badge

## القيمة
- متوسط سنوي صحيح (يفلتر NULL) — يتطابق مع DB AVG
- مقارنة قسم/شركة صحيحة (تفلتر completed فقط)
- توصية تلقائية ضمنية عبر عرض اعلى/ادنى درجة

## الاختبار
- `/employees/14/evaluations/2026` يعرض 4 KPIs و grid 3 اعمدة — progress مقيد 0-100 — هجري من convertToHijri
