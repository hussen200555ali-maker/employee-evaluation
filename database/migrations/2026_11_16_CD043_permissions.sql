-- CD-043 M1: صلاحية تصدير Excel
-- يضيف evaluations.export ويمنحها Admin/HR/Manager.
-- المتطلب: CD-043 / SRS-EXCEL-IE-3.1-final §15.2 — صلاحيتان فقط:
--   evaluations.export (تصدير) + evaluations.fill (رفع/معاينة/التزام).

INSERT INTO permissions (name, description, category)
VALUES ('evaluations.export', 'تصدير قوالب ونتائج التقييمات عبر Excel', 'التقييمات');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.name = 'evaluations.export'
WHERE r.name IN ('Admin', 'HR', 'Manager');
