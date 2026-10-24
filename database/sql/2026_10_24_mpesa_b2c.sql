-- =====================================================================================
-- M-Pesa B2C payouts on mpesa_transactions: type b2c, conversation id, disbursement (24 October 2026)
-- Same change as database/migrations/2026_10_24_000004_mpesa_b2c.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+ (local). Safe to run more than once. No data changed or deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> | gzip > backup.sql.gz
-- Run:             mysql <database> < database/sql/2026_10_24_mpesa_b2c.sql
-- =====================================================================================

ALTER TABLE `mpesa_transactions` MODIFY `type` ENUM('c2b','stk','b2c') COLLATE utf8mb4_unicode_ci NOT NULL;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `mpesa_transactions` ADD COLUMN `conversation_id` VARCHAR(64) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `checkout_request_id`, ADD UNIQUE INDEX `uq_mpesa_transactions_conversation` (`conversation_id`)',
  'SELECT ''mpesa_transactions.conversation_id already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mpesa_transactions' AND column_name = 'conversation_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `mpesa_transactions` ADD COLUMN `disbursement_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `repayment_id`, ADD INDEX `fk_mpesa_transactions_disbursement` (`disbursement_id`), ADD CONSTRAINT `fk_mpesa_transactions_disbursement` FOREIGN KEY (`disbursement_id`) REFERENCES `disbursements` (`id`) ON DELETE SET NULL',
  'SELECT ''mpesa_transactions.disbursement_id already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mpesa_transactions' AND column_name = 'disbursement_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_24_000004_mpesa_b2c' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: M-Pesa B2C columns ready' AS result;
