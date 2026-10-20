-- =====================================================================================
-- Drop the empty loan_ledger table: 20 October 2026 (Stage 4 Phase 1, owner OK)
-- It never had a writer and has 0 rows; the ledger now lives in bavix wallets (App\Services\Ledger).
-- The DROP only runs when the table exists AND is empty; otherwise it prints a note and changes nothing.
-- Same change as database/migrations/2026_10_20_000101_drop_loan_ledger_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_20_drop_loan_ledger.sql
-- =====================================================================================

SET @exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'loan_ledger');
SET @sql = IF(@exists = 0, 'SELECT 0 INTO @rows', 'SELECT COUNT(*) INTO @rows FROM `loan_ledger`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(@exists = 0, 'SELECT ''loan_ledger already dropped'' AS note',
  IF(@rows > 0, 'SELECT ''loan_ledger has rows: NOT dropped, ask the owner'' AS note', 'DROP TABLE `loan_ledger`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- guard outside the aggregate, so a second run inserts nothing
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_20_000101_drop_loan_ledger_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: loan_ledger dropped (or left alone, see note)' AS result;
