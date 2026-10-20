-- CD-014 / OOS-18: grant Admin the two team-scoped permissions, then the
-- runtime isAdmin() bypass in Auth::userHasPermission() can be removed.
-- Migration: 2026_10_21_CD014_oos18_admin_full_permissions.sql
-- Idempotent: INSERT ... SELECT ... WHERE NOT EXISTS.
--
-- Motivation (OOS-18, see CD-006_OUT_OF_SCOPE.md):
--   Auth::userHasPermission() short-circuited to true for any user holding the
--   Admin role, regardless of role_permissions (Auth.php:88). This made RBAC a
--   function of chance rather than data, and hid that Admin actually held only
--   36 of 38 permissions. The missing two are the team-scoped pair:
--     - evaluations.view.team
--     - evaluations.create.team
--   Decision (advisor-approved, 2026-10-21): "data is the source of truth" —
--   grant Admin all permissions in role_permissions, then drop the bypass.
--   The trg_role_permissions_ai trigger bumps users.permissions_version
--   automatically, so no manual version update is needed here.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant every permission not yet held by the system Admin role.
-- Selects role_id and permission_id dynamically (no hard-coded ids).
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Admin'
  AND r.is_system_role = 1
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );
