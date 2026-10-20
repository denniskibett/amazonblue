-- =====================================================================================
-- Money rows: kind, created_by, reversal_of_id: 20 October 2026 (Stage 4 Phase 1)
--   repayments.kind            VARCHAR(16) NULL   cash | rollover | discount | bad_debt | non_cash
--   repayments.created_by      BIGINT UNSIGNED NULL  -> users.id (ON DELETE SET NULL)
--   repayments.reversal_of_id  BIGINT UNSIGNED NULL UNIQUE -> repayments.id (a row is reversed once)
--   disbursements.kind         VARCHAR(16) NULL
--   disbursements.created_by   BIGINT UNSIGNED NULL  -> users.id (ON DELETE SET NULL)
-- Columns only; the values are filled by `php artisan money:backfill-columns` (dry-run first).
-- Same change as database/migrations/2026_10_20_000102_add_ledger_columns_to_repayments_table.php
-- and 2026_10_20_000103_add_ledger_columns_to_disbursements_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_20_money_row_columns.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `repayments` ADD COLUMN `kind` VARCHAR(16) NULL DEFAULT NULL AFTER `transaction`, ADD INDEX `idx_repayments_kind` (`kind`)',
  'SELECT ''repayments.kind already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'repayments' AND column_name = 'kind');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `repayments` ADD COLUMN `created_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `notes`, ADD INDEX `fk_repayments_created_by` (`created_by`), ADD CONSTRAINT `fk_repayments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION',
  'SELECT ''repayments.created_by already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'repayments' AND column_name = 'created_by');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `repayments` ADD COLUMN `reversal_of_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `created_by`, ADD UNIQUE INDEX `uq_repayments_reversal_of` (`reversal_of_id`), ADD CONSTRAINT `fk_repayments_reversal_of` FOREIGN KEY (`reversal_of_id`) REFERENCES `repayments` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION',
  'SELECT ''repayments.reversal_of_id already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'repayments' AND column_name = 'reversal_of_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `disbursements` ADD COLUMN `kind` VARCHAR(16) NULL DEFAULT NULL AFTER `transaction`, ADD INDEX `idx_disbursements_kind` (`kind`)',
  'SELECT ''disbursements.kind already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'disbursements' AND column_name = 'kind');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `disbursements` ADD COLUMN `created_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `notes`, ADD INDEX `fk_disbursements_created_by` (`created_by`), ADD CONSTRAINT `fk_disbursements_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION',
  'SELECT ''disbursements.created_by already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'disbursements' AND column_name = 'created_by');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- guard outside the aggregate, so a second run inserts nothing
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_20_000102_add_ledger_columns_to_repayments_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_20_000103_add_ledger_columns_to_disbursements_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: repayments.kind/created_by/reversal_of_id, disbursements.kind/created_by ready' AS result;
