# CD-042 (Import Fix) — نقطة العودة والتراجع

**تاريخ الإنشاء:** 2026-11-10 22:25:14
**الغرض:** نقطة عودة قبل إصلاح فحص التكرار في مسار استيراد Excel (`EvaluationExcelService::commitBatch`).

## 1. معرّفات Git
| العنصر | القيمة |
|---|---|
| Tag | `pre-cd042-importfix-20261110_222514` |
| Branch احتياطي | `backup/pre-cd042-importfix-20261110_222514` |
| Commit الأساس | `4fb4976` (fix(CD-042): structured save errors, …) |

```powershell
# عرض نقطة العودة
git show --stat pre-cd042-importfix-20261110_222514

# العودة الكاملة للكود (بعد commit الإصلاح)
git checkout backup/pre-cd042-importfix-20261110_222514
# أو على master:
git revert <importfix_commit>
```

## 2. نسخة قاعدة البيانات
- الملف: `storage/backups/CD-042/pre_importfix_20261110_222514/db_evaluations_import.sql`
- المحتوى: جداول `evaluations`, `evaluation_answers`, `evaluation_import_batches`, `evaluation_import_rows` (بنية + بيانات).

```powershell
Get-Content storage\backups\CD-042\pre_importfix_20261110_222514\db_evaluations_import.sql |
  & "C:\xampp\mysql\bin\mysql.exe" -u root employee_evaluation_uat
```
> الاستعادة تُعيد هذه الجداول إلى ما قبل إصلاح الاستيراد. خذ نسخة جديدة قبل الاستعادة إن أُنشئت تقييمات لاحقة.

## 3. نسخة الملفات المعدّلة
داخل نفس المجلد:
- `EvaluationExcelService.php.bak` ← `app/Services/EvaluationExcelService.php`
- `excel_import.php.bak` ← `app/Views/evaluations/excel_import.php`

```powershell
$b = "storage\backups\CD-042\pre_importfix_20261110_222514"
Copy-Item "$b\EvaluationExcelService.php.bak" app\Services\EvaluationExcelService.php -Force
Copy-Item "$b\excel_import.php.bak"         app\Views\evaluations\excel_import.php -Force
```

## 4. ما الذي يغطيه التراجع؟
| السيناريو | الإجراء |
|---|---|
| خطأ في فحص تكرار الاستيراد | استعادة `EvaluationExcelService.php.bak` أو `git revert` |
| خطأ في واجهة الاستيراد | استعادة `excel_import.php.bak` |
| تلف بيانات الاستيراد | استعادة `db_evaluations_import.sql` |
| العودة الكاملة | `git checkout backup/pre-cd042-importfix-20261110_222514` |

## 5. قيد التفرد (لا يتأثر)
- `uq_eval_once` + `context_key` باقيان. للتراجع عنهما (نادرًا):
  `database/migrations/2026_11_10_CD042_eval_uniqueness_down.sql`.

## 6. بعد الاستعادة
1. `C:\xampp\php\php.exe -l app\Services\EvaluationExcelService.php`.
2. `C:\xampp\php\php.exe tests\runner.php CD042DuplicateSmoke` (يجب 1/1).
3. `C:\xampp\php\php.exe tests\ExcelWorkflowSmoke.php` (يجب 4/4).
4. ملاحظة: `tests/ExcelImportConflictSmoke.php` يتطلب قيد `uq_eval_once` في `employee_evaluation_excel_test`، وقد لا يمر على نقطة عودة قديمة.
