-- =====================================================================================
-- Broker KYC access (G1): 20 October 2026
-- KYC files (ID, selfie, signature, business documents) now need `edit users` or the new
-- `view kyc documents`. The broker loses `view users`. `view kyc documents` goes to the roles that
-- held `view users` before (admin, manager, recovery-officer, auditor); super-admin has every permission.
-- Same grants as database/seeders/RoleSeeder.php. No schema change, so no migration.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once.
-- Undo: re-grant `view users` to broker.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_20_broker_kyc_access.sql
-- Then:            php artisan permission:cache-reset   (deploy-live.sh also does this)
-- =====================================================================================

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'view kyc documents', 'web', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'view kyc documents' AND `guard_name` = 'web');

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
JOIN `roles` r ON r.guard_name = 'web' AND r.name IN ('super-admin', 'admin', 'manager', 'recovery-officer', 'auditor')
WHERE p.name = 'view kyc documents' AND p.guard_name = 'web'
  AND NOT EXISTS (SELECT 1 FROM `role_has_permissions` x WHERE x.permission_id = p.id AND x.role_id = r.id);

DELETE rhp FROM `role_has_permissions` rhp
JOIN `roles` r ON r.id = rhp.role_id AND r.name = 'broker' AND r.guard_name = 'web'
JOIN `permissions` p ON p.id = rhp.permission_id AND p.name = 'view users' AND p.guard_name = 'web';

SELECT r.name AS role,
       MAX(p.name = 'view users') AS has_view_users,
       MAX(p.name = 'view kyc documents') AS has_view_kyc_documents
FROM `roles` r
LEFT JOIN `role_has_permissions` rhp ON rhp.role_id = r.id
LEFT JOIN `permissions` p ON p.id = rhp.permission_id
WHERE r.guard_name = 'web'
GROUP BY r.name ORDER BY r.name;
