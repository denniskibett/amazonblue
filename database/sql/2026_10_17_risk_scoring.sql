-- =====================================================================================
-- Automatic 4Cs risk scoring: 17 October 2026
-- The system scores every loan (character, capacity, capital, conditions) from the borrower's
-- repayment history and keeps it up to date; limits are checked against the score.
-- Same change as database/migrations/2026_10_17_000000_add_scoring_fields_to_loan_risk_assessments_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
--
-- Changes (all on loan_risk_assessments):
--   source             auto = scored by the system, manual = entered by staff (existing rows: manual)
--   risk_category_id   link to risk_categories (+ foreign key fk_risk_category)
--   limit_amount       the borrowing limit the assessment supported
--   factors            JSON: what the scores were built from
--   assessed_at        the date the assessment is "as at"
--   assessed_by        now nullable (system assessments have no staff assessor)
--   + records the migration as run
--
-- The scores themselves are calculated by the app, not SQL. After this script run:
--   php artisan risk:assess
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_17_risk_scoring.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_risk_assessments` ADD COLUMN `source` VARCHAR(20) NOT NULL DEFAULT ''manual'' AFTER `loan_id`',
  'SELECT ''loan_risk_assessments.source already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_risk_assessments' AND column_name = 'source');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_risk_assessments` ADD COLUMN `risk_category_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `risk_category`, ADD INDEX `fk_risk_category` (`risk_category_id`), ADD CONSTRAINT `fk_risk_category` FOREIGN KEY (`risk_category_id`) REFERENCES `risk_categories` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION',
  'SELECT ''loan_risk_assessments.risk_category_id already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_risk_assessments' AND column_name = 'risk_category_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_risk_assessments` ADD COLUMN `limit_amount` DECIMAL(15,2) NULL DEFAULT NULL AFTER `overall_score`',
  'SELECT ''loan_risk_assessments.limit_amount already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_risk_assessments' AND column_name = 'limit_amount');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- JSON: MariaDB stores it as LONGTEXT with a validity check, MySQL as native JSON
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_risk_assessments` ADD COLUMN `factors` JSON NULL DEFAULT NULL AFTER `recommendation`',
  'SELECT ''loan_risk_assessments.factors already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_risk_assessments' AND column_name = 'factors');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_risk_assessments` ADD COLUMN `assessed_at` TIMESTAMP NULL DEFAULT NULL AFTER `factors`',
  'SELECT ''loan_risk_assessments.assessed_at already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_risk_assessments' AND column_name = 'assessed_at');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 1,
  'ALTER TABLE `loan_risk_assessments` MODIFY `assessed_by` BIGINT UNSIGNED NULL DEFAULT NULL',
  'SELECT ''loan_risk_assessments.assessed_by is already nullable'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_risk_assessments' AND column_name = 'assessed_by' AND is_nullable = 'NO');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_17_000000_add_scoring_fields_to_loan_risk_assessments_table', COALESCE(MAX(batch), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = '2026_10_17_000000_add_scoring_fields_to_loan_risk_assessments_table');

SELECT 'Done: risk scoring fields ready. Now run: php artisan risk:assess' AS result;
