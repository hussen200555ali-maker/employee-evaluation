-- CD-021: Add reports.generate permission
-- Idempotent: only inserts if not exists
INSERT INTO permissions (name, description, category)
SELECT 'reports.generate', 'إصدار تقارير رسمية للموظفين', 'التقارير'
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'reports.generate');

-- Grant to Admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'Admin' AND r.is_system_role = 1
  AND p.name = 'reports.generate'
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );
