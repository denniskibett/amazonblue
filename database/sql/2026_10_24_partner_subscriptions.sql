-- =====================================================================================
-- Partner KYC and online subscription: request payload + line, partner business + source of funds (24 October 2026)
-- Same change as database/migrations/2026_10_24_000001_partner_subscriptions.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+ (local). Safe to run more than once. No data changed or deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> | gzip > backup.sql.gz
-- Run:             mysql <database> < database/sql/2026_10_24_partner_subscriptions.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `partner_requests` ADD COLUMN `payload` LONGTEXT COLLATE utf8mb4_bin NULL DEFAULT NULL AFTER `note`',
  'SELECT ''partner_requests.payload already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'partner_requests' AND column_name = 'payload');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `partner_requests` ADD COLUMN `investment_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `partner_transaction_id`, ADD INDEX `fk_partner_requests_investment` (`investment_id`), ADD CONSTRAINT `fk_partner_requests_investment` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`) ON DELETE SET NULL',
  'SELECT ''partner_requests.investment_id already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'partner_requests' AND column_name = 'investment_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `partners` ADD COLUMN `business_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `user_id`, ADD INDEX `fk_partners_business` (`business_id`), ADD CONSTRAINT `fk_partners_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE SET NULL',
  'SELECT ''partners.business_id already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'partners' AND column_name = 'business_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `partners` ADD COLUMN `source_of_funds` VARCHAR(60) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `tax_id`',
  'SELECT ''partners.source_of_funds already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'partners' AND column_name = 'source_of_funds');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_24_000001_partner_subscriptions' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: partner subscriptions ready' AS result;
