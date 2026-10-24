-- =====================================================================================
-- Business loans: loan business, product audience, business limit, director links, change requests (24 October 2026)
-- Same change as database/migrations/2026_10_24_000002_business_lending.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+ (local). Safe to run more than once. No data changed or deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> | gzip > backup.sql.gz
-- Run:             mysql <database> < database/sql/2026_10_24_business_lending.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loans` ADD COLUMN `business_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `user_id`, ADD INDEX `fk_loans_business` (`business_id`), ADD CONSTRAINT `fk_loans_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE SET NULL',
  'SELECT ''loans.business_id already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loans' AND column_name = 'business_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loan_types` ADD COLUMN `audience` ENUM(''individual'',''business'') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''individual'' AFTER `name`',
  'SELECT ''loan_types.audience already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loan_types' AND column_name = 'audience');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `businesses` ADD COLUMN `credit_limit` DECIMAL(15,2) NULL DEFAULT NULL AFTER `monthly_revenue`',
  'SELECT ''businesses.credit_limit already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'businesses' AND column_name = 'credit_limit');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `business_members` ADD COLUMN `verify_token_hash` CHAR(64) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `verified_at`, ADD UNIQUE INDEX `uq_business_members_verify_token` (`verify_token_hash`), ADD COLUMN `verify_sent_at` TIMESTAMP NULL DEFAULT NULL AFTER `verify_token_hash`, ADD COLUMN `verify_expires_at` TIMESTAMP NULL DEFAULT NULL AFTER `verify_sent_at`',
  'SELECT ''business_members.verify_token_hash already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'business_members' AND column_name = 'verify_token_hash');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `business_documents` ADD COLUMN `business_member_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `business_id`, ADD INDEX `fk_business_documents_member` (`business_member_id`), ADD CONSTRAINT `fk_business_documents_member` FOREIGN KEY (`business_member_id`) REFERENCES `business_members` (`id`) ON DELETE CASCADE',
  'SELECT ''business_documents.business_member_id already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'business_documents' AND column_name = 'business_member_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `borrower_requests` ADD COLUMN `business_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `loan_id`, ADD INDEX `fk_borrower_requests_business` (`business_id`), ADD CONSTRAINT `fk_borrower_requests_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE SET NULL',
  'SELECT ''borrower_requests.business_id already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'borrower_requests' AND column_name = 'business_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `borrower_requests` MODIFY `type` ENUM('extension','plan','help','topup','business_change') COLLATE utf8mb4_unicode_ci NOT NULL;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_24_000002_business_lending' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: business lending ready' AS result;
