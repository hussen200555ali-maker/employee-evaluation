# جدول توافق — التدقيق المستقل (AUDIT-CD-043) — SRS Compliance Table

**المهمة:** AUDIT-CD-043 — تدقيق مستقل لمنتج CD-043 (تصدير/استيراد Excel)
**المرجع:** SRS-EXCEL-IE-3.1-final — المادة/الإصدار v3.1-final
**التاريخ:** 2026-11-16
**حالة التقرير:** مسودة — مكتمل جزئياً (المتطلبات P2 وما قبلها)

> ملاحظة منهجية: النتائج مبنية على فحص ميداني (artifact-level) لمحتوى ملفات الإكسل (XLSX) مُولَّدة فعلياً عبر `ExcelTemplateBuilder` + `PhpSpreadsheet`, مقارنةً بإعلانات التسليم السابق (P2 Report §3) واختبارات القبول.

| # | المتطلب (من SRS / إعلان P2) | الوصف | النتيجة | الأدلة / الفحص | ملاحظات |
|---|---|---|---|---|---|
| P2-01 | ورقتان فقط | ورقة رئيسية + ورقة Lists خفية | ✅ | فحص ملف مُولَّد: sheets=2، Lists state=hidden | مؤكد |
| P2-02 | لا كلمة سر | حماية بلا كلمة سر | ✅ | `<sheetProtection sheet="1" .../>` بلا `password=` في مستند مُولَّد + عينات | مؤكد |
| P2-03 | لا معرّفات تقنية في Lists | رأس القوائم بلغة عربية/أسماء وظيفية | ✅ | رأس القائمة بدون id | مؤكد |
| P2-04 | صف تعليمات = سطر واحد (A1) | `معرف القالب: <id>` | ✅ | تحقق من البانر | مؤكد |
| P2-05 | ترتيب الأعمدة: الاسم ثم الرقم | employee/skill: employee_name ثم employee_code | ✅ | فحص layout + smoke T1 | مؤكد |
| P2-06 | لا عمود الكنية | nickname خارج الـlayout | ✅ | — | مؤكد |
| P2-07 | ترتيب الذيل: ملاحظات → اسم → صفة → تاريخ | tailKeys = [notes, evaluator_name, evaluator_title, evaluation_date] | ✅ | فحص layout | مؤكد |
| P2-08 | رأس برنامج كامل (6 حقول) | program_title..program_responsible | ✅ | فحص layout (A:F) | مؤكد |
| P2-09 | التقدير العام (قائمة منسددة) للدورة الوصفية فقط | عمود overall_grade + GradeList | ❌ (مكسور في الملف) | BUG-001: `Q5:Q104` → `GradeList` بقيمة `showDropDown="0"` | يلزم إصلاحاً |
| P2-10 | **قوائم منسدلة في مواضعها** | BranchList/DeptList/ProgramList/GradeList/SkillLevelList + dropdown | ❌ (مكسور في الملف) | **BUG-001: `showDropDown="0"` في جميع قواعد type=list** | **مكسور فعلياً في ملف الإكسل المصدر** |
| P2-11 | حد الصفوف 50–2000 | min/max من settings مع سقف 2000 | ✅ | `limits()` + UAT settings | مؤكد |
| P2-12 | حجم الملف 50MB من settings | `excel.max_file_size_mb=50` | ✅ | قاعدة قسمة UAT | مؤكد |
| P2-13 | شاشة تصدير بالخطوات الست | 6 steps + UI | ✅ | فحص UI سابق (P2 fix 7f3f7d6) | مؤكد |
| P2-14 | فلاتر البرنامج | اختيار البرنامج/الفرع/القسم منفذ | ✅ | فحص UI + controller | مؤكد |
| P2-15 | M3 — تقليص target_type إلى 4 قيم | enum في قاعدة التطبيق UAT = 4 قيم | ✅ | قالب UAT (206 workshop) | مؤكد |
| P2-16 | لا نظام موازٍ | بانر موحد + delegation | ✅ | buildTemplate/describeTemplateStructure يفوّضان البانر | مؤكد |

### متطلبات وأحكام أساسية (من إعلانات P1/P3/P4/P5 وضمانات CD-043)

| المعرّف | المتطلب | النتيجة | ملاحظات |
|---|---|---|---|
| ضمانة 1 | التقدير العام = final result عبر `final_grade_label/value` | ✅ (P4 calculator + DB) | `evaluations.final_grade_label/value` موجودة؛ لا عمود `overall_grade` في جدول UAT ✅ |
| ضمانة 2 | 100% manual-vs-import parity | ✅ (3 modes × 3 sizes golden) | golden numeric comparison PASSED |
| ضمانة 3 | حدود 2000 صف / 50MB / retention | ✅ | settings + retention disabled default |
| R-SCP-01 | فحوص نطاق (فرع/قسم) للاستيراد | ✅ | `ExcelScope` — `departmentBelongsToBranch`, `validateBranchDepartment` |

### نتائج فحص إضافية أثناء التدقيق (artifact-level)

| البند | النتيجة | ملاحظات |
|---|---|---|
| جداول البيانات (Sheets) | ✅ 2 sheets | |
| حماية | ✅ بلا كلمة سر + sheet=1 | |
| تعليقات/قوائم | ✅ | BUG-001 |
| ترحيل/تنظيف | ✅ P5 cleanup | |
| retention cron | ✅ disabled default | |

---

## 2. ملخص التوافق العام

- **المتطلبات المعلن عنها في P2 (16) وبنود CD-043 الأخرى:** نسبياً ✅ مع ثغرة واحدة **مكسورة فعلياً** (P2-09/P2-10 — قوائم المنسدلة).
- **النتيجة الحالية للتدقيق:** **غير مكتمل (❌)** عند مستوى ملف الإكسل المصدّر الفعلي، وسط التسليم السابق ✅.

---

## 3. الحالة المبدئية للتدقيق (مكتمل جزئياً — قيد الإكمال)

| البند من مهام AUDIT-CD-043 | الحالة | الملاحظات |
|---|---|---|
| ملف إكسل مصدّر فعلياً (descriptive course / individual / workshop) | 🟡 مكتمل جزئياً — فحص بنى + BUG-001 | |
| data validation | 🟡 مكتمل جزئياً — BUG-001 + allowBlank observations | |
| Lists | ✅ مكتمل | |
| program header | ✅ مكتمل | |
| protection | ✅ مكتمل | |
| UI checks | ✅ مكتمل (سابقاً) | |
| 12 employee-match cases | ⬜ غير منفذ ضمن هذا التقرير المبدئي | |
| R-SCP-01 / E-026 | 🟡 محدد | |
| DB checks (UAT) | ✅ مكتمل | |
