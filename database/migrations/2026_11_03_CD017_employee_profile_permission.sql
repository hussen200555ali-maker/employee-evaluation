-- CD-017 adds permission data only; it does not alter the database schema.
INSERT INTO permissions (name, description, category)
SELECT 'employees.profile', 'View employee profile and evaluation history', 'Employees'
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE name = 'employees.profile'
);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT existing_permission.role_id, profile_permission.id
FROM role_permissions existing_permission
JOIN permissions view_permission
    ON view_permission.id = existing_permission.permission_id
   AND view_permission.name = 'employees.view'
JOIN permissions profile_permission
    ON profile_permission.name = 'employees.profile';
