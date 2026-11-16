# CD-043 — قائمة نشر UAT (P5)

## 1. الترحيلات (Apply على UAT — بالترتيب)

1. `database/migrations/2026_11_16_CD043_permissions.sql` (M1) + تحقق
2. `database/migrations/2026_11_16_CD043_settings.sql` (M2) + تحقق
3. `database/migrations/2026_11_16_CD043_import_batches.sql` (M4) + تحقق
4. `database/migrations/2026_11_16_CD043_template_types.sql` (M3) — **فقط بعد تحقق precheck = 0 على UAT** (تم: 0 صف)
5. Rollback (اختياري) — `_down.sql` بترتيب عكسي

## 2. الملفات (Deploy)

- `app/Services/Excel/ExcelTemplateBuilder.php` (جديد)
- `app/Services/Excel/EmployeeMatcher.php`, `ProgramMatcher.php`, `ExcelScope.php`, `ExcelImportValidator.php`, `EvaluationCalculator.php` (جديد)
- `app/Services/EvaluationExcelService.php` (معدّل)
- `app/Services/EvaluationCalculator.php` (جديد)
- `app/Models/Evaluation.php` (معدّل — delegation)
- `app/Controllers/EvaluationExcelController.php` (معدّل)
- `app/Views/evaluations/excel_export.php` (جديد)
- `app/Views/evaluations/excel_import.php` (معدّل)
- `app/Views/layouts/sidebar.php` (معدّل)
- `routes/web.php` (معدّل)
- `cron/excel_retention.php` (جديد — معطّل افتراضياً)

## 3. التحقق بعد النشر

- `php -l` على جميع PHP المعدّلة.
- `php tests/run_isolated.php` → 5/5 + 6/6 + 4/4 + 3/3.
- HTTP: `GET /evaluations/excel/export` → 403 (غير مخوّل) / 200 (Admin).
- `SELECT target_type ... NOT IN (...)` = 0 صف على UAT.
- تصدير 100 صف ≤ 3s، 2000 صف ≤ 5s.
- استيراد template 2 (descriptive) → final_score = 100 (ممتاز).

## 4. التفعيل (Admin)

- retention: `INSERT INTO settings (setting_key, setting_value) VALUES ('excel.retention_enabled','1') ON DUPLICATE KEY UPDATE setting_value='1';` (اختياري، معطّل افتراضياً).
- cron: إضافة `cron/excel_retention.php` إلى crontab (يومياً) — **فقط بعد التفعيل**.

## 5. Rollback

- وسم العودة: `pre-cd043-20261116_034536` على master.
- `git checkout pre-cd043-20261116_034536`.
- الترحيلات: `_down.sql` بترتيب عكسي (M3 → M4 → M2 → M1).
- قاعدة UAT: استعادة من `backups/CD-043/pre_cd043_20261116_034536.sql`.
