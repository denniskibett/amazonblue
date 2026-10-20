-- =====================================================================================
-- M-Pesa tables: 21 October 2026 (Stage 4 Phase 4)
--   mpesa_transactions  every C2B confirmation / STK callback, raw; trans_id unique (no double recording)
--   payment_claims      a borrower's "I've paid", with a status visible on every device
-- Same change as database/migrations/2026_10_21_000301_create_mpesa_transactions_table.php and
-- 2026_10_21_000302_create_payment_claims_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_21_mpesa_tables.sql
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `mpesa_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('c2b','stk') NOT NULL,
  `trans_id` VARCHAR(32) NULL DEFAULT NULL COMMENT 'M-Pesa receipt, e.g. UJ7CQ9KQCR',
  `checkout_request_id` VARCHAR(100) NULL DEFAULT NULL,
  `msisdn` VARCHAR(64) NULL DEFAULT NULL COMMENT 'payer phone as sent (may be hashed by Safaricom)',
  `amount` DECIMAL(15,2) NULL DEFAULT NULL,
  `bill_ref` VARCHAR(64) NULL DEFAULT NULL COMMENT 'account number the payer typed',
  `shortcode` VARCHAR(20) NULL DEFAULT NULL,
  `trans_time` DATETIME NULL DEFAULT NULL,
  `loan_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `repayment_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `status` ENUM('pending','received','matched','unmatched','duplicate','failed','reversed') NOT NULL DEFAULT 'received',
  `note` VARCHAR(255) NULL DEFAULT NULL,
  `payload` LONGTEXT NULL DEFAULT NULL,
  `resolved_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mpesa_trans_id` (`trans_id`),
  UNIQUE KEY `uq_mpesa_checkout` (`checkout_request_id`),
  KEY `fk_mpesa_loan` (`loan_id`),
  KEY `fk_mpesa_repayment` (`repayment_id`),
  KEY `idx_mpesa_status` (`status`),
  CONSTRAINT `fk_mpesa_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_mpesa_repayment` FOREIGN KEY (`repayment_id`) REFERENCES `repayments` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_claims` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `loan_id` BIGINT UNSIGNED NOT NULL,
  `code` VARCHAR(32) NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `phone` VARCHAR(32) NULL DEFAULT NULL,
  `status` ENUM('checking','received','rejected') NOT NULL DEFAULT 'checking',
  `reason` VARCHAR(255) NULL DEFAULT NULL,
  `repayment_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `mpesa_transaction_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `decided_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_claims_loan_code` (`loan_id`, `code`),
  KEY `fk_claims_user` (`user_id`),
  KEY `fk_claims_loan` (`loan_id`),
  KEY `idx_claims_code` (`code`),
  KEY `idx_claims_status` (`status`),
  KEY `fk_claims_repayment` (`repayment_id`),
  KEY `fk_claims_mpesa` (`mpesa_transaction_id`),
  CONSTRAINT `fk_claims_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_claims_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_claims_repayment` FOREIGN KEY (`repayment_id`) REFERENCES `repayments` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_claims_mpesa` FOREIGN KEY (`mpesa_transaction_id`) REFERENCES `mpesa_transactions` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- guards outside the aggregate, so a second run inserts nothing
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000301_create_mpesa_transactions_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000302_create_payment_claims_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: mpesa_transactions, payment_claims ready' AS result;
