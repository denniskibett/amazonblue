-- =====================================================================================
-- Guarantor sign-off before a loan is paid out, and next of kin confirmation (21 November 2026)
-- Same change as database/migrations/2026_11_21_000010_create_loan_guarantees_table.php
-- and database/migrations/2026_11_21_000020_create_kin_confirmations_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `loan_guarantees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `loan_id` BIGINT UNSIGNED NOT NULL,
  `borrower_id` BIGINT UNSIGNED NOT NULL,
  `contact_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(12) NOT NULL DEFAULT 'waiting',
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `relationship` VARCHAR(60) NULL,
  `id_number` VARCHAR(20) NULL,
  `dob` DATE NULL,
  `email` VARCHAR(255) NULL,
  `residence` VARCHAR(120) NULL,
  `occupation` VARCHAR(120) NULL,
  `id_front_path` VARCHAR(255) NULL,
  `id_back_path` VARCHAR(255) NULL,
  `signature_path` VARCHAR(255) NULL,
  `terms` TEXT NULL,
  `terms_sha256` CHAR(64) NULL,
  `token_hash` CHAR(64) NULL,
  `link_sent_at` TIMESTAMP NULL,
  `link_expires_at` TIMESTAMP NULL,
  `signed_at` TIMESTAMP NULL,
  `signed_ip` VARCHAR(45) NULL,
  `signed_agent` VARCHAR(255) NULL,
  `waived_by` BIGINT UNSIGNED NULL,
  `waived_reason` VARCHAR(255) NULL,
  `waived_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_guarantees_loan_id_unique` (`loan_id`),
  UNIQUE KEY `loan_guarantees_token_hash_unique` (`token_hash`),
  KEY `loan_guarantees_borrower_id_index` (`borrower_id`),
  KEY `loan_guarantees_phone_index` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_21_000010_create_loan_guarantees_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

CREATE TABLE IF NOT EXISTS `kin_confirmations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(12) NOT NULL DEFAULT 'waiting',
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `relation` VARCHAR(60) NULL,
  `stated_relation` VARCHAR(60) NULL,
  `id_number` VARCHAR(20) NULL,
  `id_front_path` VARCHAR(255) NULL,
  `id_back_path` VARCHAR(255) NULL,
  `token_hash` CHAR(64) NULL,
  `link_sent_at` TIMESTAMP NULL,
  `link_expires_at` TIMESTAMP NULL,
  `answered_at` TIMESTAMP NULL,
  `answered_ip` VARCHAR(45) NULL,
  `answered_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kin_confirmations_token_hash_unique` (`token_hash`),
  KEY `kin_confirmations_user_id_index` (`user_id`),
  KEY `kin_confirmations_phone_index` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_21_000020_create_kin_confirmations_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: loan_guarantees and kin_confirmations tables' AS result;
