-- =====================================================================================
-- Utility meters on users (3 November 2026)
-- Same change as database/migrations/2026_11_03_000030_add_utility_meters_to_users_table.php.
-- Adds users.utility_meters (JSON list of KPLC meter/account numbers given as proof of address).
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> | gzip > backup.sql.gz
-- Run:             mysql <database> < database/sql/2026_11_03_users_utility_meters.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `utility_meters` JSON NULL AFTER `kyc_checks`',
  'SELECT ''users.utility_meters already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'utility_meters');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- (MAX() always returns one row, so the NOT EXISTS guard sits outside the aggregate)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_03_000030_add_utility_meters_to_users_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: users.utility_meters added' AS result;
