-- =====================================================================================
-- loans.merged_into_loan_id: 21 October 2026
-- A legacy ROLL OVER continuation loan merged into its chain's first loan points at it (and is soft-deleted
-- by `php artisan loans:merge-chains --apply`). Column only; nothing is merged by this script.
-- Same change as database/migrations/2026_10_21_000101_add_merged_into_loan_id_to_loans_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_21_loans_merged_into.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loans` ADD COLUMN `merged_into_loan_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`, ADD INDEX `fk_loans_merged_into` (`merged_into_loan_id`), ADD CONSTRAINT `fk_loans_merged_into` FOREIGN KEY (`merged_into_loan_id`) REFERENCES `loans` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION',
  'SELECT ''loans.merged_into_loan_id already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loans' AND column_name = 'merged_into_loan_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- guard outside the aggregate, so a second run inserts nothing
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000101_add_merged_into_loan_id_to_loans_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: loans.merged_into_loan_id ready' AS result;
