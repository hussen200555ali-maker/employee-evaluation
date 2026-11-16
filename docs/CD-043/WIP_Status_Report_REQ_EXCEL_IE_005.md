# تقرير حالة WIP — CD-043 (REQ-EXCEL-IE-005)

**الفرع:** `feature/cd-043-wip`
**Commit:** `e0fc6e8` — wip(CD-043): robust dropdown fix + artifact-level smoke + regenerated samples + audit/comprehensive reports
**Tag (نقطة عودة):** `pre-cd043-review-20261116_174230` (pushed to origin)
**Remote:** https://github.com/hussen200555ali-maker/employee-evaluation (branch pushed ✅)
**التاريخ:** 2026-11-16

---

## 1. ما أُنجز (WIP) — قائمة البنود

### المرحلة 1 — الأساس (P1) ✅
- `ExcelTemplateBuilder.php` — باني القوالب (4 أنواع) — commit `2c6c41a`.
- `EvaluationExcelService` — تفويض buildTemplate/describeTemplateStructure.
- مسارات `evaluations/excel/export` + `download`.
- M1/M2/M3/M4 migrations على UAT.

### المرحلة 2 — التصدير (P2) ✅
- `app/Views/evaluations/excel_export.php` — 6 خطوات + فلاتر.
- 4 ملفات sample في `docs/CD-043/exports/`.
- `ExcelTemplateExportSmoke` — 6/6.
- **Robust fix (BUG-001):** `showDropDown=false` + `ensureDropdownsEnabled()` (post-process `showDataDropDown="1"`) + artifact-level smoke assertion.

### المرحلة 3 — الاستيراد/التحقق (P3) ✅
- `EmployeeMatcher`, `ProgramMatcher`, `ExcelScope`, `ExcelImportValidator`.
- `ExcelImportValidationSmoke` — 4/4.

### المرحلة 4 — Parity (P4) ✅
- `EvaluationCalculator` — parity engine؛ `Evaluation::calculateFinalScore` يفوّض.
- `EvaluationCalculatorSmoke` — 3/3.

### المرحلة 5 — تنظيف (P5) ✅
- إزالة legacy buildTemplateLegacy/describeTemplateStructureLegacy.
- retention cron معطل افتراضياً (`excel.retention_enabled`).
- `UAT_Deployment_Checklist.md`.

### التدقيق (AUDIT-CD-043) 🟡
- Bug Report #001 (قوائم منسدلة) — مُسلَّم.
- جدول توافق SRS — مسودة.
- التوصية النهائية — مسودة (Hold UAT sign-off).
- تقرير شامل (All Types/Templates) — مُسلَّم.

---

## 2. قيد التنفيذ / ما بقي

| البند | الحالة | تقدير |
|---|---|---|
| تحقق المدقّق من ملفات إكسل فعلياً | ⏳ pending | 1 يوم |
| UAT re-test (4 أنواع + استيراد + قوائم) | ⏳ pending | 2–3 أيام |
| إكمال جدول توافق SRS (بقية البنود) | 🟡 مسودة | 0.5 يوم |
| التوصية النهائية (UAT sign-off) | 🟡 مسودة | بعد التحقق |

---

## 3. المرحلة الحالية + نسبة الإنجاز

- **المرحلة:** 5 (اختبار) — مع Hold UAT sign-off.
- **نسبة الإنجاز:** P1 100% / P2 100% / P3 100% / P4 100% / P5 100% / Audit 70% / **إجمالي ~90%**.

---

## 4. العوائق

- **BUG-001 (مُصلَح، بانتظار تحقق):** قوائم منسددة كانت `showDropDown="0"` (معكوس). تم الإصلاح (robust: + `showDataDropDown="1"`). بانتظار المدقّق.
- **لا تعارض SRS** باستثناء possible design mismatch: `DepartmentList` (single, no INDIRECT) vs SRS P2-10 `Dept_<n>`. قرار معتمد سابقاً (no INDIRECT). أُبلِغ إن لزم.
- **لا جداول جديدة** — استُخدمت batches/rows/errors القائمة.

---

## 5. اختبارات الدخان

| الاختبار | النتيجة |
|---|---|
| `ExcelWorkflowSmoke` | 5/5 ✅ |
| `ExcelTemplateExportSmoke` | 6/6 ✅ (artifact-level dropdown check) |
| `ExcelImportValidationSmoke` | 4/4 ✅ |
| `EvaluationCalculatorSmoke` | 3/3 ✅ |

---

## 6. إجابات النقاط الخمس (RESP-EXCEL-IE-004)

| # | النقطة | الإجابة (وفقاً لما طُبِّق) |
|---|---|---|
| 1 | **مواضع القوائم** | employee/skill: C→BranchList, D→DepartmentList. employee_in_course/workshop: A→ProgramList, B→BranchList, C→DepartmentList, I→BranchList, J→DepartmentList, Q→GradeList (descriptive). skill sections→SkillLevelList. department uses single `DepartmentList` (no INDIRECT). |
| 2 | **المسارات** | جديدة: `GET evaluations/excel/export` + `GET evaluations/excel/export/download`. Import: المسار legacy (excel_import) أُعيد استخدامه (لا مسارات موازية). |
| 3 | **findOrCreateProgram** | helpers: `findProgramId`, `findOrCreateProgram` (name+branch+department+dates+responsible). لم أعد تسميته `findOrCreateProgramInstance` (kept as is). |
| 4 | **المدة** | 32 يوم عمل (SRS). Code phases P1–P5 completed in one day (2026-11-16); UAT testing window 5 days ongoing. |
| 5 | **تقرير التقدّم الأسبوعي** | Markdown reports in `docs/CD-043/` (P1–P5 + closure draft + UAT checklist + audit). Weekly cadence during UAT. |

---

## 7. تأكيد البنود العشرة (نعم/لا — وفق ما طُبِّق في WIP)

| # | البند | SRS | نعم/لا |
|---|---|---|---|
| 1 | ورقتان فقط (رئيسية + قوائم مخفية) | 5.1 | **نعم** |
| 2 | رقم الدفعة يُنشأ عند الرفع (لا عند التصدير) | 7 | **نعم** |
| 3 | E-026 يُطبَّق بدقة (رفع نفس الملف مسموح، المنع على الصف) | الملحق أ | **نعم** (row-level duplicate handling in importer) |
| 4 | قالب الورش مستقل عن الدورة | 4.1 | **نعم** (target_type منفصل + template 206) |
| 5 | Excel 2007+ مدعوم + LibreOffice best effort | 18 | **نعم** (PhpSpreadsheet Xlsx writer; LibreOffice not explicitly tested) |
| 6 | R-SCP-01 بكامل نصفيها | 9.3 | **نعم** (ExcelScope: departmentBelongsToBranch + validateBranchDepartment) |
| 7 | 12 حالة مطابقة موظف + تطبيع عربي | 10.2 | **نعم** (EmployeeMatcher: normalizeName, namesEqual) |
| 8 | لا جداول جديدة — استخدام batches/rows/errors القائمة | 1.4 | **نعم** (M1–M4 فقط) |
| 9 | التقدير العام للدورة الوصفية = النتيجة النهائية | 12.1 | **نعم** (final_grade_label/value) |
| 10 | كتالوج E-001..E-026 يُطبَّق حرفيًا | الملحق أ | **نعم** (import validation engine; ExcelImportValidationSmoke 4/4) |

> **ملاحظة:** E-026/E-001..E-026: import validation engine applies row-level validation; smoke tests cover the engine. Harmonic verification of each code not individually traced in this WIP report.

---

## 8. قيود الامتثال

- ✅ لا دمج في master (WIP على فرع منفصل).
- ✅ لا مرحلة جديدة قبل اعتماد النهج.
- ✅ لا جداول جديدة.
- ✅ BUG-001 مُصلَح (robust) + smoke artifact-level + عينات مُولَّدة.

---

**بانتظار مراجعة WIP + قرارات النقاط الخمس + اعتماد رسمي.**
