-- Which bank (owner, 16 Nov 2026): bank_code (id from resources/data/banks-kenya.txt) and bank_name (full name as
-- it read that day) on disbursements and repayments. Safe to run more than once; MariaDB 10.3 (live) and MySQL 9 (local).
-- Twin of database/migrations/2026_11_16_000010_add_bank_to_disbursements_table.php and 2026_11_16_000020_add_bank_to_repayments_table.php.

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'disbursements' AND COLUMN_NAME = 'bank_code');
SET @sql := IF(@c = 0, 'ALTER TABLE `disbursements` ADD COLUMN `bank_code` VARCHAR(20) COLLATE utf8mb4_unicode_ci NULL AFTER `mode`, ADD COLUMN `bank_name` VARCHAR(120) COLLATE utf8mb4_unicode_ci NULL AFTER `bank_code`, ADD INDEX `disbursements_bank_code_index` (`bank_code`)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repayments' AND COLUMN_NAME = 'bank_code');
SET @sql := IF(@c = 0, 'ALTER TABLE `repayments` ADD COLUMN `bank_code` VARCHAR(20) COLLATE utf8mb4_unicode_ci NULL AFTER `mode`, ADD COLUMN `bank_name` VARCHAR(120) COLLATE utf8mb4_unicode_ci NULL AFTER `bank_code`, ADD INDEX `repayments_bank_code_index` (`bank_code`)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_16_000010_add_bank_to_disbursements_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_16_000020_add_bank_to_repayments_table' AS migration, COALESCE(MAX(batch), 0) AS batch FROM `migrations`
) m WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);
