-- =====================================================================================
-- KYC selfies: 20 October 2026
-- Adds three nullable paths to users for the KYC selfies (face straight, turned left, turned right),
-- stored on the private disk like the ID images. Existing customers are not changed; they are asked
-- for the selfies the next time they go through verification.
-- Same change as database/migrations/2026_10_20_000001_add_selfie_paths_to_users_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_20_kyc_selfies.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `selfie_center_path` VARCHAR(255) NULL DEFAULT NULL AFTER `id_back_path`',
  'SELECT ''users.selfie_center_path already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'selfie_center_path');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `selfie_left_path` VARCHAR(255) NULL DEFAULT NULL AFTER `selfie_center_path`',
  'SELECT ''users.selfie_left_path already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'selfie_left_path');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `selfie_right_path` VARCHAR(255) NULL DEFAULT NULL AFTER `selfie_left_path`',
  'SELECT ''users.selfie_right_path already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'selfie_right_path');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- guard outside the aggregate, so a second run inserts nothing
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_20_000001_add_selfie_paths_to_users_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: users.selfie_center_path, selfie_left_path, selfie_right_path ready' AS result;
