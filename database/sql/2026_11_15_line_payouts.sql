-- =====================================================================================
-- Investor payouts from a line (15 November 2026)
-- Same change as database/migrations/2026_11_15_000020_add_payouts_to_investment_fundings_table.php:
--   investment_fundings.amount_paid_out          this line's money used to pay other investors back
--   investment_fundings.paid_from_investment_id  on a paid-back line, the line that paid it (NULL: our own money)
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
-- =====================================================================================

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'investment_fundings' AND COLUMN_NAME = 'amount_paid_out');
SET @sql := IF(@c = 0, 'ALTER TABLE `investment_fundings` ADD COLUMN `amount_paid_out` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT ''this line''''s money used to pay other investors back'' AFTER `amount_returned`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'investment_fundings' AND COLUMN_NAME = 'paid_from_investment_id');
SET @sql := IF(@c = 0, 'ALTER TABLE `investment_fundings` ADD COLUMN `paid_from_investment_id` BIGINT UNSIGNED NULL COMMENT ''the line whose money paid this one back (null: our own money)'' AFTER `amount_paid_out`, ADD CONSTRAINT `investment_fundings_paid_from_investment_id_foreign` FOREIGN KEY (`paid_from_investment_id`) REFERENCES `investments` (`id`) ON DELETE SET NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_15_000020_add_payouts_to_investment_fundings_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.migration = m.migration);

SELECT 'Done: line payouts' AS result;
