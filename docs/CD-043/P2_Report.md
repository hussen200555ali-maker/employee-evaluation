# تقرير P2 — باني القالب وشاشة التصدير

**المهمة:** CD-043 — وحدة تصدير/استيراد Excel
**المرجع:** APPR-EXCEL-IE-007 + SRS-EXCEL-IE-3.1-final
**الفرع:** `feature/cd-043-excel-ie`
**الفترة:** 2026-11-16 (P2)
**حالة P2:** ✅ مكتمل — **بانتظار اعتماد C2 قبل بدء P3**

---

## 1. ما أُنجز

| # | البند | الحالة |
|---|---|---|
| 1 | `app/Services/Excel/ExcelTemplateBuilder.php` — باني القوالب (4 أنواع) | ✅ |
| 2 | تفويض `EvaluationExcelService` (buildTemplate/describeTemplateStructure) للباني | ✅ |
| 3 | `app/Views/evaluations/excel_export.php` — شاشة التصدير (6 خطوات) | ✅ |
| 4 | `EvaluationExcelController::exportForm()` + `downloadExport()` | ✅ |
| 5 | مسارات `GET evaluations/excel/export` + `GET evaluations/excel/export/download` | ✅ |
| 6 | M3 — تقليص `target_type` إلى 4 قيم (ملف ترحيل + rollback) | ✅ |
| 7 | مُستورد جديد التخطيط (صف 5، أعمدة ديناميكية، رأس برنامج 6 حقول) | ✅ |
| 8 | `normalizeDateCell`, `findProgramId`, `findOrCreateProgram`, `lookupGradeValue` | ✅ |
| 9 | ضمانة 1 — `final_grade_label/value` للدورة الوصفية (التقدير العام) | ✅ |
| 10 | حماية الورقة بلا كلمة سر (`setHashValue('')`) | ✅ |
| 11 | `tests/ExcelWorkflowSmoke.php` — إعادة كتابة (5 سيناريوهات) | ✅ 5/5 |
| 12 | `tests/ExcelTemplateExportSmoke.php` — جديد (6 فحوصات) | ✅ 6/6 |
| 13 | 4 ملفات sample في `docs/CD-043/exports/` | ✅ |
| 14 | قياس الأداء (100/2000 صف) | ✅ |
| 15 | تحديث `tests/run_isolated.php` (تعليق، قائمة، تحذير) | ✅ |
| 16 | إضافة أقسام متابعة C1 إلى `P1_Report.md` | ✅ |

## 2. الملفات

**جديدة:**
- `app/Services/Excel/ExcelTemplateBuilder.php`
- `app/Views/evaluations/excel_export.php`
- `database/migrations/2026_11_16_CD043_template_types.sql` + `_down.sql`
- `tests/ExcelTemplateExportSmoke.php`
- `docs/CD-043/exports/employee.xlsx`
- `docs/CD-043/exports/employee_in_course.xlsx`
- `docs/CD-043/exports/employee_in_workshop.xlsx`
- `docs/CD-043/exports/skill.xlsx`

**معدّلة:**
- `app/Services/EvaluationExcelService.php` — تفويض + مُستورد جديد + helpers + commit patching
- `app/Controllers/EvaluationExcelController.php` — exportForm + downloadExport
- `routes/web.php` — مساران جديدا
- `app/Views/evaluations/excel_import.php` — رابط شاشة التصدير
- `tests/ExcelWorkflowSmoke.php` — إعادة كتابة
- `tests/run_isolated.php` — تحسين التعليق
- `docs/CD-043/P1_Report.md` — أقسام متابعة C1

## 3. التحقق من متطلبات SRS v3.1-final

| # | المتطلب | الحالة | الدليل |
|---|---|---|---|
| 1 | ورقتان فقط (رئيسية + Lists) | ✅ | ExcelTemplateExportSmoke: 2 sheets |
| 2 | لا كلمة سر (لا `password=` في XML) | ✅ | `<sheetProtection sheet="1" .../>` |
| 3 | لا معرّفات تقنية في Lists | ✅ | فحص headers (no "id") |
| 4 | صف تعليمات = سطر واحد (A1) | ✅ | يحوي `معرف القالب: <id>` |
| 5 | ترتيب الأعمدة: الاسم ثم الرقم (فردي/مهاري) | ✅ | keys[0]=employee_name, keys[1]=employee_code |
| 6 | لا عمود الكنية | ✅ | nickname غير موجود في layout |
| 7 | ترتيب الذيل: ملاحظات → اسم المقيم → صفته → التاريخ | ✅ | tailKeys ثابت |
| 8 | رأس برنامج كامل (6 حقول) | ✅ | program_title..program_responsible |
| 9 | التقدير العام (dropdown من grading_scales) للدورة الوصفية فقط | ✅ | has_overall_grade=true فقط لـ employee_in_course + descriptive |
| 10 | قوائم منسدلة في مواضعها | ✅ | BranchList/GradeList/SkillLevelList/ProgramList/Dept_<n> |
| 11 | حد الصفوف 50–2000 | ✅ | limits() + max(1,min(2000,...)) |
| 12 | حجم الملف 50MB من settings | ✅ | excel.max_file_size_mb |
| 13 | شاشة تصدير بالخطوات الست | ✅ | excel_export.php (6 steps) |
| 14 | فلاتر البرنامج (اسم/فرع/قسم منفذ) | ✅ | JS + controller |
| 15 | M3 — تقليص enum إلى 4 قيم | ✅ | migration + rollback |
| 16 | لا نظام موازٍ | ✅ | buildTemplate/describeTemplateStructure يفوّضان للباني |

## 4. النماذج المصدَّرة (C2 review)

| الملف | النوع | الوضع | التقدير العام |
|---|---|---|---|
| `employee.xlsx` | فردي | numeric | لا |
| `employee_in_course.xlsx` | موظف داخل دورة | descriptive | نعم |
| `employee_in_workshop.xlsx` | موظف داخل ورشة | numeric | لا |
| `skill.xlsx` | مهاري | numeric | لا |

## 5. الاختبارات

| الاختبار | النتيجة |
|---|---|
| `php tests/ExcelWorkflowSmoke.php` | **5/5** |
| `php tests/ExcelTemplateExportSmoke.php` | **6/6** |
| `php -l` على الملفات المعدّلة | ✅ |

## 6. الأداء (UAT hardware)

| الصفوف | الوقت | الحد (UAT) | الحالة |
|---|---|---|---|
| 100 | 0.39s | ≤3s | ✅ |
| 2000 | 4.66s | ≤5s | ✅ (ضمن الحد، هامش 0.34s) |

> ملاحظة: 2000 صف within UAT threshold (≤5s) but close; production target (≤3s) may require optimization in P4/P5. The bottleneck is PhpSpreadsheet writer styling 2000 rows.

## 7. ضمانة 1 (overall_grade)

- عمود "التقدير العام *" يظهر فقط في (employee_in_course + descriptive).
- قائمة منسدلة من `grading_scales` (سلم "السلم الخماسي").
- عند الالتزام، يُكتب `final_grade_label` + `final_grade_value` (numeric_value من السلم).
- Smoke T5 يثبت: label="ممتاز", value=5.0.
- **ملاحظة:** parity كاملة (EvaluationCalculator) مؤجلة إلى P4 (C1 follow-up، غير حاجز C2).

## 8. ملاحظات مخاطر

1. M3 مطبّقة على قاعدة الاختبار فقط؛ تطبيق UAT عند النشر (بعد C2).
2. `EvaluationExcelService::buildTemplateLegacy()` / `describeTemplateStructureLegacy()` ما زالت موجودة للرجوع (P5 cleanup).
3. فحص `overall_grade` في `normalize()`/`calculateFinalScore()` لا يزال غير parity-كامل (P4).
4. الأداء 2000 صف (4.66s) ضمن حد UAT (≤5s) but margin small; monitor.

## 9. قائمة اعتماد C2

- [x] ExcelTemplateBuilder ينتج 4 أنواع من القوالب.
- [x] 2 أوراق، Lists مخفية، بلا كلمة سر، بلا معرّفات تقنية.
- [x] صف تعليمات واحد (مع معرف القالب).
- [x] الاسم ثم الرقم (فردي/مهاري).
- [x] لا عمود كنية.
- [x] ذيل: ملاحظات → اسم → صفة → تاريخ.
- [x] رأس برنامج 6 حقول (دورة/ورشة).
- [x] التقدير العام (dropdown) للدورة الوصفية فقط.
- [x] شاشة التصدير 6 خطوات + فلاتر.
- [x] M3 migration + rollback.
- [x] 4 ملفات sample.
- [x] اختبارات 5/5 + 6/6.
- [x] قياس الأداء 100≤3s / 2000≤5s (UAT).

**لا يبدأ P3 قبل اعتماد C2 رسمياً.**
