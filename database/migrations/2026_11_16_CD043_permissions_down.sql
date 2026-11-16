-- CD-043 M1 rollback: إزالة صلاحية تصدير Excel
-- الترتيب إلزامي: role_permissions أولاً (FK) ثم permissions.

DELETE rp
FROM role_permissions rp
JOIN permissions p ON p.id = rp.permission_id
WHERE p.name = 'evaluations.export';

DELETE FROM permissions WHERE name = 'evaluations.export';
