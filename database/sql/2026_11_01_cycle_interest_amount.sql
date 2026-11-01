-- =====================================================================================
-- Exact interest on a loan cycle (1 November 2026): a short payment accepted as the cycle's full amount sets
-- the cycle's interest to an exact figure. Same change as
-- database/migrations/2026_11_01_000040_add_interest_amount_to_loan_cycles_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 9 (local). Safe to run more than once. Adds an empty column; no data changed.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_11_01_cycle_interest_amount.sql
-- =====================================================================================

SET @add := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `interest_amount` decimal(15,2) DEFAULT NULL COMMENT ''Exact interest for the cycle when set; NULL = rate x principal'' AFTER `interest_rate`',
  'SELECT 1')
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'interest_amount');
PREPARE stmt FROM @add;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_01_000040_add_interest_amount_to_loan_cycles_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);
