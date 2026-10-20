-- =====================================================================================
-- Stage 4 Phase 3 columns: 21 October 2026
--   investments.return_type   ENUM('house','loan_share','fixed_rate') NOT NULL DEFAULT 'house'
--   loans.broker_penalty_rate DECIMAL(5,2) NULL  (broker's share of penalty, percent)
-- Columns only; values are set by `php artisan partners:link` (dry-run first).
-- Same change as database/migrations/2026_10_21_000201_add_return_type_to_investments_table.php and
-- 2026_10_21_000202_add_broker_penalty_rate_to_loans_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_21_phase3_columns.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `investments` ADD COLUMN `return_type` ENUM(''house'',''loan_share'',''fixed_rate'') NOT NULL DEFAULT ''house'' AFTER `facility_type`',
  'SELECT ''investments.return_type already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'investments' AND column_name = 'return_type');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loans` ADD COLUMN `broker_penalty_rate` DECIMAL(5,2) NULL DEFAULT NULL COMMENT ''Broker share of penalty for this loan, percent'' AFTER `broker_rate`',
  'SELECT ''loans.broker_penalty_rate already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loans' AND column_name = 'broker_penalty_rate');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- guards outside the aggregate, so a second run inserts nothing
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000201_add_return_type_to_investments_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000202_add_broker_penalty_rate_to_loans_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: investments.return_type, loans.broker_penalty_rate ready' AS result;
