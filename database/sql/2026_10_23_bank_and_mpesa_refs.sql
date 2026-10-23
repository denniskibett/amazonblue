-- =====================================================================================
-- Two references per payment: bank Transaction Ref ID and M-Pesa code (23 October 2026)
-- Same change as database/migrations/2026_10_23_000005_add_bank_and_mpesa_refs.php.
-- Works on MariaDB 10.3 (live). Safe to run more than once. Adds empty columns; no data changed
-- (php artisan payments:refs fills them, dry run first).
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_23_bank_and_mpesa_refs.sql
-- =====================================================================================

ALTER TABLE `disbursements`
  ADD COLUMN IF NOT EXISTS `bank_ref` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `transaction`,
  ADD COLUMN IF NOT EXISTS `mpesa_ref` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `bank_ref`;
CREATE INDEX IF NOT EXISTS `disbursements_bank_ref_index` ON `disbursements` (`bank_ref`);
CREATE INDEX IF NOT EXISTS `disbursements_mpesa_ref_index` ON `disbursements` (`mpesa_ref`);

ALTER TABLE `repayments`
  ADD COLUMN IF NOT EXISTS `bank_ref` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `transaction`,
  ADD COLUMN IF NOT EXISTS `mpesa_ref` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `bank_ref`;
CREATE INDEX IF NOT EXISTS `repayments_bank_ref_index` ON `repayments` (`bank_ref`);
CREATE INDEX IF NOT EXISTS `repayments_mpesa_ref_index` ON `repayments` (`mpesa_ref`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_23_000005_add_bank_and_mpesa_refs' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);
