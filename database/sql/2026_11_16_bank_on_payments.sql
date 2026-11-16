-- Which bank (owner, 16 Nov 2026): bank_code (id from resources/data/banks-kenya.txt) and bank_name (full name as
-- it read that day) on disbursements and repayments. Safe to run more than once; MariaDB 10.3 (live) and MySQL 9 (local).
-- Twin of database/migrations/2026_11_16_000010_add_bank_to_disbursements_table.php and 2026_11_16_000020_add_bank_to_repayments_table.php.

ALTER TABLE `disbursements`
  ADD COLUMN IF NOT EXISTS `bank_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `mode`,
  ADD COLUMN IF NOT EXISTS `bank_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `bank_code`;
CREATE INDEX IF NOT EXISTS `disbursements_bank_code_index` ON `disbursements` (`bank_code`);

ALTER TABLE `repayments`
  ADD COLUMN IF NOT EXISTS `bank_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `mode`,
  ADD COLUMN IF NOT EXISTS `bank_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `bank_code`;
CREATE INDEX IF NOT EXISTS `repayments_bank_code_index` ON `repayments` (`bank_code`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_16_000010_add_bank_to_disbursements_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_16_000020_add_bank_to_repayments_table' AS migration, COALESCE(MAX(batch), 0) AS batch FROM `migrations`
) m WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);
