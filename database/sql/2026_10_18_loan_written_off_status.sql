-- =====================================================================================
-- Loan status 'written_off': 18 October 2026
-- A recovery write-off now marks the loan 'written_off' (it used to mark it 'repaid', so written-off
-- debt was reported as repaid). Existing loans are not changed.
-- Same change as database/migrations/2026_10_18_000051_add_written_off_status_to_loans_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_18_loan_written_off_status.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 1,
  'ALTER TABLE `loans` MODIFY `status` ENUM(''pending'',''approved'',''disbursed'',''rejected'',''repaid'',''overdue'',''defaulted'',''recovery'',''forbearance'',''written_off'') NOT NULL DEFAULT ''pending''',
  'SELECT ''loans.status already allows written_off (or column missing)'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loans' AND column_name = 'status'
    AND column_type NOT LIKE '%written_off%');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- guard outside the aggregate, so a second run inserts nothing
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_18_000051_add_written_off_status_to_loans_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: loans.status accepts written_off' AS result;
