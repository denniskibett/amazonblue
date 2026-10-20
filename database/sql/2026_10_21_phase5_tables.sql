-- =====================================================================================
-- Phase 5 tables: 21 October 2026 (Stage 4 Phase 5)
--   approvals           maker-checker requests (checker <> maker, enforced by CHECK)
--   approval_limits     most one person in a role may approve alone (approve_loan: 50,000 for super-admin/admin/manager)
--   borrower_requests   extension / plan / help requests with the decision the borrower sees
--   partner_requests    funding confirmations and withdrawals (prepared by one person, approved by another)
--   teller_day_closes   a teller's cash-up for a day (expected, counted, variance, approver)
--   loan_type_versions  the values a loan product had before each change
-- Same change as database/migrations/2026_10_21_000401..000406_*.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_21_phase5_tables.sql
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `requested_by` bigint unsigned NOT NULL,
  `decided_by` bigint unsigned DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_approvals_subject` (`subject_type`,`subject_id`),
  KEY `idx_approvals_action` (`action`),
  KEY `idx_approvals_status` (`status`),
  KEY `fk_approvals_requested_by` (`requested_by`),
  KEY `fk_approvals_decided_by` (`decided_by`),
  CONSTRAINT `fk_approvals_decided_by` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_approvals_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  CONSTRAINT `chk_approvals_checker` CHECK (((`decided_by` is null) or (`decided_by` <> `requested_by`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `approval_limits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_approval_limits` (`role`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `borrower_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('extension','plan','help') COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `loan_id` bigint unsigned DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `payload` json DEFAULT NULL,
  `status` enum('open','approved','declined','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `decision_note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decided_by` bigint unsigned DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_borrower_requests_user` (`user_id`),
  KEY `fk_borrower_requests_loan` (`loan_id`),
  KEY `idx_borrower_requests_status` (`status`),
  CONSTRAINT `fk_borrower_requests_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_borrower_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `partner_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('funding','withdrawal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `partner_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `wallet_slug` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','prepared','done','declined') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `requested_by` bigint unsigned DEFAULT NULL,
  `prepared_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `partner_transaction_id` bigint unsigned DEFAULT NULL,
  `decision_note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_partner_requests_partner` (`partner_id`),
  KEY `idx_partner_requests_status` (`status`),
  CONSTRAINT `fk_partner_requests_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `teller_day_closes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teller_id` bigint unsigned NOT NULL,
  `day` date NOT NULL,
  `expected` json NOT NULL,
  `counted` json NOT NULL,
  `variance` decimal(15,2) NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `status` enum('closed','approved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'closed',
  `closed_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_teller_day` (`teller_id`,`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loan_type_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_type_id` bigint unsigned NOT NULL,
  `version` int unsigned NOT NULL,
  `values` json NOT NULL,
  `changed_by` bigint unsigned DEFAULT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loan_type_version` (`loan_type_id`,`version`),
  KEY `fk_loan_type_versions_type` (`loan_type_id`),
  CONSTRAINT `fk_loan_type_versions_type` FOREIGN KEY (`loan_type_id`) REFERENCES `loan_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `approval_limits` (`role`, `action`, `max_amount`, `created_at`, `updated_at`) VALUES
  ('super-admin', 'approve_loan', 50000.00, NOW(), NOW()),
  ('admin', 'approve_loan', 50000.00, NOW(), NOW()),
  ('manager', 'approve_loan', 50000.00, NOW(), NOW());

-- guards outside the aggregate, so a second run inserts nothing
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000401_create_approvals_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000402_create_approval_limits_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000403_create_borrower_requests_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000404_create_partner_requests_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000405_create_teller_day_closes_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000406_create_loan_type_versions_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: Phase 5 tables ready' AS result;
