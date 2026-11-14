-- =====================================================================================
-- Fixed-return lines (15 November 2026)
-- Same change as database/migrations/2026_11_15_000010_add_fixed_return_to_investments_return_type.php:
--   investments.return_type gains 'fixed_return' (a set % for a set number of days, e.g. 100,000 for 10 days at 8%).
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
-- =====================================================================================

SET @t := (SELECT COLUMN_TYPE FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'investments' AND COLUMN_NAME = 'return_type');
SET @sql := IF(@t IS NOT NULL AND @t NOT LIKE '%fixed_return%',
  'ALTER TABLE `investments` MODIFY `return_type` ENUM(''house'',''loan_share'',''fixed_rate'',''fixed_return'') NOT NULL DEFAULT ''house''',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_15_000010_add_fixed_return_to_investments_return_type' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.migration = m.migration);

SELECT 'Done: fixed-return lines' AS result;
