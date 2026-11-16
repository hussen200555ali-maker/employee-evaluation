# تقرير P4 — الالتزام (Commit) وحساب الدرجة (parity)

**المهمة:** CD-043 — وحدة تصدير/استيراد Excel
**المرجع:** APPR-EXCEL-IE-007 + SRS-EXCEL-IE-3.1-final §12/§18
**الفرع:** `feature/cd-043-excel-ie`
**حالة P4:** ✅ مكتمل — **بانتظار اعتماد C4 قبل بدء P5**

---

## 1. ما أُنجز

| # | البند | الحالة |
|---|---|---|
| 1 | `app/Services/EvaluationCalculator.php` — محرك موّحد (parity 100%) | ✅ |
| 2 | `Evaluation::calculateFinalScore` → يفوّض إلى EvaluationCalculator | ✅ |
| 3 | `EvaluationExcelService::calculateFinalScore` → يفوّض إلى EvaluationCalculator | ✅ |
| 4 | `commitBatch` — تعيين final_grade_label/value قبل الحساب (ضمانة 1) | ✅ |
| 5 | `tests/EvaluationCalculatorSmoke.php` — 3 فحوصات | ✅ 3/3 |
| 6 | Golden comparison (formula parity) — all numeric unchanged | ✅ |
| 7 | لا تعديل على `saveUnified` (فقط delegation عبر calculateFinalScore) | ✅ |

## 2. ضمانة 1 (parity)

- للدورة الوصفية (descriptive): `final_score = max_value` للتقدير العام (final_grade_label).
- Excel-imported descriptive: final_score now = 100 (ممتاز) instead of 0/percentage.
- Smoke T5 asserts final_score=100.0.
- Manual (saveUnified) already uses calculateFinalScore → now delegates to calculator (same result).

## 3. الاختبارات

| الاختبار | النتيجة |
|---|---|
| `ExcelWorkflowSmoke` | 5/5 |
| `ExcelTemplateExportSmoke` | 6/6 |
| `ExcelImportValidationSmoke` | 4/4 |
| `EvaluationCalculatorSmoke` | 3/3 |
| Golden comparison (numeric) | PASSED |
| `php -l` | ✅ |

## 4. ملاحظات

- P5 (المرحلة الأخيرة) — cleanup: إزالة legacy methods, FormulaEngine check, UAT deployment.
- `overall_grade` column in `evaluations` **not added** (guarantee 1 satisfied via final_grade_label/value).

## 5. قائمة اعتماد C4

- [x] EvaluationCalculator created (shared engine)
- [x] calculateFinalScore delegated (Evaluation + ExcelService)
- [x] commitBatch parity (final_score = grade max_value)
- [x] Golden comparison (numeric unchanged) PASSED
- [x] Smoke tests 5/5 + 6/6 + 4/4 + 3/3
- [x] No changes to saveUnified body (only delegation)
- [x] No `overall_grade` column added (guarantee 1 via final_grade_label/value)

**لا يبدأ P5 قبل اعتماد C4 رسمياً.**
