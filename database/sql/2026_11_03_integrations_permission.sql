-- =====================================================================================
-- Admins can open Integrations and API keys (3 November 2026)
-- New permission "manage integrations" for super-admin and admin, and "manage API tokens" for admin.
-- Same as database/seeders/RoleSeeder.php. Works on MariaDB 10.3 and MySQL 8+/9. Safe to run more than once.
-- After it: php artisan permission:cache-reset
-- =====================================================================================

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'manage integrations', 'web', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'manage integrations' AND guard_name = 'web');

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p JOIN roles r ON r.guard_name = 'web'
WHERE p.guard_name = 'web' AND (
      (p.name = 'manage integrations' AND r.name IN ('super-admin', 'admin'))
   OR (p.name = 'manage API tokens'   AND r.name = 'admin'))
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id);

SELECT CONCAT('Done: ', r.name, ' has ', p.name) AS result FROM role_has_permissions x
JOIN permissions p ON p.id = x.permission_id JOIN roles r ON r.id = x.role_id
WHERE p.name IN ('manage integrations', 'manage API tokens') AND r.name IN ('super-admin', 'admin');
