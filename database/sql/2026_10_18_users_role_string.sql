-- =====================================================================================
-- Roles and permissions: 18 October 2026
-- Spatie roles (model_has_roles) are now the single source of truth for who can do what.
-- users.role stays as a mirror of each user's primary role for older screens, but it was an
-- ENUM('admin','borrower','broker','teller','partner') and could not hold the newer staff roles
-- (manager, loan-officer, accountant, recovery-officer, auditor, super-admin). It becomes VARCHAR(50).
-- Same change as database/migrations/2026_10_18_000021_change_role_to_string_on_users_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
--
-- After this script run (backfills Spatie roles for users who only have users.role, reports conflicts):
--   php artisan roles:sync --dry-run
--   php artisan roles:sync
--   php artisan permission:cache-reset
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_18_users_role_string.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 1,
  'ALTER TABLE `users` MODIFY `role` VARCHAR(50) NULL DEFAULT NULL',
  'SELECT ''users.role is already a string (or column missing)'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'role'
    AND LOWER(column_type) LIKE 'enum%');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- (MAX() always returns one row, so the NOT EXISTS guard sits outside the aggregate)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_18_000021_change_role_to_string_on_users_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: users.role is VARCHAR(50)' AS result;
