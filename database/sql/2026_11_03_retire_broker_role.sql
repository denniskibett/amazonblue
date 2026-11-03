-- =====================================================================================
-- Retire the "broker" role (3 November 2026)
-- Same change as database/migrations/2026_11_03_000040_retire_broker_role.php.
-- A broker is now a short-term partner: anyone holding the broker role gets the partner role and a partner row,
-- users.role follows, and the role is deleted. Loans, brokers rates and money rows are not touched.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once.
--
-- Look first:      SELECT u.id, u.email FROM users u JOIN model_has_roles m ON m.model_id = u.id
--                  JOIN roles r ON r.id = m.role_id WHERE r.name = 'broker' AND m.model_type = 'App\\Models\\User';
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> | gzip > backup.sql.gz
-- Run:             mysql <database> < database/sql/2026_11_03_retire_broker_role.sql
-- After it:        php artisan permission:cache-reset
-- =====================================================================================

START TRANSACTION;

SET @broker = (SELECT id FROM roles WHERE name = 'broker' AND guard_name = 'web' LIMIT 1);
SET @partner = (SELECT id FROM roles WHERE name = 'partner' AND guard_name = 'web' LIMIT 1);

INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
SELECT @partner, m.model_type, m.model_id FROM model_has_roles m
WHERE m.role_id = @broker AND m.model_type = 'App\\Models\\User' AND @partner IS NOT NULL;

INSERT INTO partners (user_id, name, email, phone, type, status, profit_share_rate, risk_tolerance, notes, created_at, updated_at)
SELECT u.id, u.name, u.email, u.phone, 'individual', 'active', 0, 'moderate', 'Short-term partner (was the broker role, 3 Nov 2026).', NOW(), NOW()
FROM users u JOIN model_has_roles m ON m.model_id = u.id AND m.model_type = 'App\\Models\\User' AND m.role_id = @broker
WHERE NOT EXISTS (SELECT 1 FROM partners p WHERE p.user_id = u.id);

UPDATE users SET role = 'partner' WHERE role = 'broker';
DELETE FROM model_has_roles WHERE role_id = @broker;
DELETE FROM role_has_permissions WHERE role_id = @broker;
DELETE FROM roles WHERE id = @broker;

COMMIT;

-- (MAX() always returns one row, so the NOT EXISTS guard sits outside the aggregate)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_03_000040_retire_broker_role' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: broker role retired; its users are partners' AS result;
