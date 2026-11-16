-- The client's bank (owner, 16 Nov 2026): client_bank_code / client_bank_name beside our bank on disbursements and repayments.
-- Safe to run more than once; MariaDB 10.3 (live) and MySQL 9 (local).
-- Twin of 2026_11_16_000030_add_client_bank_to_disbursements_table.php and 2026_11_16_000040_add_client_bank_to_repayments_table.php.

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'disbursements' AND COLUMN_NAME = 'client_bank_code');
SET @sql := IF(@c = 0, 'ALTER TABLE `disbursements` ADD COLUMN `client_bank_code` VARCHAR(20) COLLATE utf8mb4_unicode_ci NULL AFTER `bank_name`, ADD COLUMN `client_bank_name` VARCHAR(120) COLLATE utf8mb4_unicode_ci NULL AFTER `client_bank_code`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repayments' AND COLUMN_NAME = 'client_bank_code');
SET @sql := IF(@c = 0, 'ALTER TABLE `repayments` ADD COLUMN `client_bank_code` VARCHAR(20) COLLATE utf8mb4_unicode_ci NULL AFTER `bank_name`, ADD COLUMN `client_bank_name` VARCHAR(120) COLLATE utf8mb4_unicode_ci NULL AFTER `client_bank_code`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (SELECT '2026_11_16_000030_add_client_bank_to_disbursements_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (SELECT '2026_11_16_000040_add_client_bank_to_repayments_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);
