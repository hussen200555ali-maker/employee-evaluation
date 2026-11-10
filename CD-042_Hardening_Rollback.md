# CD-042 (Hardening) — نقطة العودة والتراجع

**تاريخ الإنشاء:** 2026-11-10 22:10:41
**الغرض:** نقطة عودة آمنة قبل تحسينات ما بعد CD-042 (توحيد أخطاء الحفظ + ربط المسودة/الحالة + قفل صفوف الملاحظات).

## 1. معرّفات Git
| العنصر | القيمة |
|---|---|
| Tag | `pre-cd042-hardening-20261110_221041` |
| Branch احتياطي | `backup/pre-cd042-hardening-20261110_221041` |
| Commit الأساس | `24aa270` (feat(CD-042): prevent duplicate evaluations) |

```powershell
# عرض نقطة العودة
git show --stat pre-cd042-hardening-20261110_221041

# العودة الكاملة للكود (بعد commit التحسينات)
git checkout backup/pre-cd042-hardening-20261110_221041
# أو على master:
git revert <hardening_commit>
```

## 2. نسخة قاعدة البيانات
- الملف: `storage/backups/CD-042/pre_hardening_20261110_221041/db_evaluations.sql`
- المحتوى: جدولا `evaluations` و`evaluation_answers` فقط (بنية + بيانات).

```powershell
Get-Content storage\backups\CD-042\pre_hardening_20261110_221041\db_evaluations.sql |
  & "C:\xampp\mysql\bin\mysql.exe" -u root employee_evaluation_uat
```
> ملاحظة: الاستعادة تُعيد الجدولين لما كانت عليه قبل التحسينات. أي تقييم أُنشئ بعد نقطة العودة سيُفقد — خذ نسخة جديدة قبل الاستعادة إن لزم.

## 3. نسخة الملفات المعدّلة
داخل نفس المجلد `pre_hardening_20261110_221041/`:
- `Evaluation.php.bak` ← `app/Models/Evaluation.php`
- `BulkEvaluationController.php.bak` ← `app/Controllers/BulkEvaluationController.php`
- `EvaluationController.php.bak` ← `app/Controllers/EvaluationController.php`
- `bulk-create.php.bak` ← `app/Views/evaluations/bulk-create.php`

```powershell
$b = "storage\backups\CD-042\pre_hardening_20261110_221041"
Copy-Item "$b\Evaluation.php.bak"               app\Models\Evaluation.php -Force
Copy-Item "$b\BulkEvaluationController.php.bak" app\Controllers\BulkEvaluationController.php -Force
Copy-Item "$b\EvaluationController.php.bak"     app\Controllers\EvaluationController.php -Force
Copy-Item "$b\bulk-create.php.bak"              app\Views\evaluations\bulk-create.php -Force
```

## 4. ما الذي يغطيه التراجع؟
| السيناريو | الإجراء |
|---|---|
| خلل في واجهة التقييم الجماعي | استعادة `bulk-create.php.bak` أو `git revert` |
| خلل في شكل استجابة الحفظ | استعادة `BulkEvaluationController.php.bak` |
| خلل في منع التكرار (نموذج) | استعادة `Evaluation.php.bak` |
| تلف بيانات تقييمات | استعادة `db_evaluations.sql` |
| العودة الكاملة | `git checkout backup/pre-cd042-hardening-20261110_221041` |

## 5. قيد التفرد (لا يتأثر بالتحسينات)
- `uq_eval_once` + `context_key` باقيان كما هما. للتراجع عنهما (نادرًا):
  `database/migrations/2026_11_10_CD042_eval_uniqueness_down.sql`.

## 6. بعد الاستعادة
1. `C:\xampp\php\php.exe -l` للملفات المستعادة.
2. `C:\xampp\php\php.exe tests/runner.php CD042DuplicateSmoke` (يجب 1/1).
3. افتح `/evaluations/bulk-create` وتأكد من عدم وجود أخطاء console.
