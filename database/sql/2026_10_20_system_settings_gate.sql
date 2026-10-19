-- =====================================================================================
-- /system gate: 20 October 2026
-- /system now needs `access system settings` (was role super-admin|admin). Only super-admin holds it
-- (owner: sysadmin is the only super-admin). This makes sure the role has it and lists who can open /system.
-- Same grant as database/seeders/RoleSeeder.php (super-admin => '*'). No schema change, so no migration.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_20_system_settings_gate.sql
-- Then:            php artisan permission:cache-reset   (deploy-live.sh also does this)
-- Expect:          one row, sysadmin. If none, /system is closed to everyone until `users:super-admin` runs.
-- =====================================================================================

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'access system settings', 'web', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'access system settings' AND `guard_name` = 'web');

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
JOIN `roles` r ON r.guard_name = 'web' AND r.name = 'super-admin'
WHERE p.name = 'access system settings' AND p.guard_name = 'web'
  AND NOT EXISTS (SELECT 1 FROM `role_has_permissions` x WHERE x.permission_id = p.id AND x.role_id = r.id);

-- who can open /system (through a role or a direct grant)
SELECT DISTINCT u.id, u.email, COALESCE(r.name, '(direct grant)') AS via
FROM `permissions` p
LEFT JOIN `role_has_permissions` rhp ON rhp.permission_id = p.id
LEFT JOIN `roles` r ON r.id = rhp.role_id
JOIN `model_has_roles` mhr ON mhr.role_id = r.id AND mhr.model_type = 'App\\Models\\User'
JOIN `users` u ON u.id = mhr.model_id
WHERE p.name = 'access system settings' AND p.guard_name = 'web'
UNION
SELECT u.id, u.email, '(direct grant)'
FROM `permissions` p
JOIN `model_has_permissions` mhp ON mhp.permission_id = p.id AND mhp.model_type = 'App\\Models\\User'
JOIN `users` u ON u.id = mhp.model_id
WHERE p.name = 'access system settings' AND p.guard_name = 'web';
