-- =====================================================================================
-- Loan cycle engine columns: 20 October 2026 (Stage 4 Phase 2)
-- loan_cycles gains: principal; the product terms snapshot (penalty_rate, grace_days, default_threshold_days);
-- penalty_waived_until; the balance cache (closing_balance, penalty_accrued, principal_paid, interest_paid,
-- penalty_paid, balance_as_of); closed_on. All nullable; values are written by `php artisan loans:restate`.
-- Same change as database/migrations/2026_10_20_000201_add_engine_columns_to_loan_cycles_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_20_loan_cycle_engine_columns.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `principal` DECIMAL(15,2) NULL DEFAULT NULL AFTER `interest_rate`',
  'SELECT ''loan_cycles.principal already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'principal');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `penalty_rate` DECIMAL(6,3) NULL DEFAULT NULL AFTER `principal`',
  'SELECT ''loan_cycles.penalty_rate already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'penalty_rate');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `grace_days` INT UNSIGNED NULL DEFAULT NULL AFTER `penalty_rate`',
  'SELECT ''loan_cycles.grace_days already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'grace_days');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `default_threshold_days` INT UNSIGNED NULL DEFAULT NULL AFTER `grace_days`',
  'SELECT ''loan_cycles.default_threshold_days already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'default_threshold_days');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `penalty_waived_until` DATE NULL DEFAULT NULL AFTER `due_date`',
  'SELECT ''loan_cycles.penalty_waived_until already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'penalty_waived_until');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `closing_balance` DECIMAL(15,2) NULL DEFAULT NULL AFTER `penalty_waived_until`',
  'SELECT ''loan_cycles.closing_balance already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'closing_balance');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `penalty_accrued` DECIMAL(15,2) NULL DEFAULT NULL AFTER `closing_balance`',
  'SELECT ''loan_cycles.penalty_accrued already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'penalty_accrued');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `principal_paid` DECIMAL(15,2) NULL DEFAULT NULL AFTER `penalty_accrued`',
  'SELECT ''loan_cycles.principal_paid already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'principal_paid');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `interest_paid` DECIMAL(15,2) NULL DEFAULT NULL AFTER `principal_paid`',
  'SELECT ''loan_cycles.interest_paid already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'interest_paid');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `penalty_paid` DECIMAL(15,2) NULL DEFAULT NULL AFTER `interest_paid`',
  'SELECT ''loan_cycles.penalty_paid already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'penalty_paid');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `balance_as_of` DATE NULL DEFAULT NULL AFTER `penalty_paid`',
  'SELECT ''loan_cycles.balance_as_of already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'balance_as_of');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_cycles` ADD COLUMN `closed_on` DATE NULL DEFAULT NULL AFTER `balance_as_of`',
  'SELECT ''loan_cycles.closed_on already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_cycles' AND column_name = 'closed_on');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- guard outside the aggregate, so a second run inserts nothing
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_20_000201_add_engine_columns_to_loan_cycles_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: loan_cycles engine columns ready' AS result;
