-- =====================================================================================
-- Funding CRM and the business finance journey (20 November 2026)
-- Same change as database/migrations/2026_11_20_000010_create_funding_crm_and_finance_applications.php, plus the
-- permissions in database/seeders/RoleSeeder.php:
--   funding_prospects, funding_campaigns, funding_emails, funding_email_events, funding_activities, funding_suppressions
--   finance_applications, finance_application_documents
--   "manage funding prospects"     super-admin, admin, manager, loan-officer
--   "review finance applications"  super-admin, admin, manager
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
-- After it: php artisan permission:cache-reset
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `funding_prospects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(20) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `segment` VARCHAR(30) NOT NULL,
  `need` VARCHAR(20) NULL,
  `county` VARCHAR(60) NULL,
  `town` VARCHAR(80) NULL,
  `website` VARCHAR(255) NULL,
  `email` VARCHAR(255) NULL,
  `phone` VARCHAR(40) NULL,
  `contact_name` VARCHAR(255) NULL,
  `contact_role` VARCHAR(255) NULL,
  `source` VARCHAR(120) NOT NULL,
  `source_url` VARCHAR(255) NULL,
  `est_need` DECIMAL(15,2) NULL,
  `stage` VARCHAR(20) NOT NULL DEFAULT 'new',
  `owner_id` BIGINT UNSIGNED NULL,
  `next_on` DATE NULL,
  `next_step` VARCHAR(255) NULL,
  `last_emailed_at` TIMESTAMP NULL,
  `opted_out_at` TIMESTAMP NULL,
  `application_id` BIGINT UNSIGNED NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `funding_prospects_reference_unique` (`reference`),
  KEY `funding_prospects_segment_index` (`segment`),
  KEY `funding_prospects_email_index` (`email`),
  KEY `funding_prospects_stage_index` (`stage`),
  KEY `funding_prospects_application_id_index` (`application_id`),
  CONSTRAINT `funding_prospects_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `funding_campaigns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(20) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `segment` VARCHAR(30) NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `funding_campaigns_reference_unique` (`reference`),
  CONSTRAINT `funding_campaigns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `funding_emails` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `prospect_id` BIGINT UNSIGNED NOT NULL,
  `campaign_id` BIGINT UNSIGNED NULL,
  `reference` VARCHAR(24) NOT NULL,
  `track` VARCHAR(32) NOT NULL,
  `to` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `sent_by` BIGINT UNSIGNED NULL,
  `message_id` VARCHAR(255) NULL,
  `sent_at` TIMESTAMP NULL,
  `opens` INT UNSIGNED NOT NULL DEFAULT 0,
  `opened_at` TIMESTAMP NULL,
  `clicked_at` TIMESTAMP NULL,
  `replied_at` TIMESTAMP NULL,
  `bounced_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `funding_emails_reference_unique` (`reference`),
  UNIQUE KEY `funding_emails_track_unique` (`track`),
  CONSTRAINT `funding_emails_prospect_id_foreign` FOREIGN KEY (`prospect_id`) REFERENCES `funding_prospects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `funding_emails_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `funding_campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `funding_emails_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `funding_email_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email_id` BIGINT UNSIGNED NOT NULL,
  `prospect_id` BIGINT UNSIGNED NOT NULL,
  `kind` VARCHAR(10) NOT NULL,
  `occurred_at` TIMESTAMP NULL,
  `via` VARCHAR(10) NOT NULL DEFAULT 'direct',
  `ip_prefix` VARCHAR(45) NULL,
  `country` CHAR(2) NULL,
  `region` VARCHAR(80) NULL,
  `city` VARCHAR(80) NULL,
  `device` VARCHAR(20) NULL,
  `os` VARCHAR(20) NULL,
  `client` VARCHAR(30) NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `funding_email_events_prospect_id_index` (`prospect_id`),
  KEY `funding_email_events_occurred_at_index` (`occurred_at`),
  CONSTRAINT `funding_email_events_email_id_foreign` FOREIGN KEY (`email_id`) REFERENCES `funding_emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `funding_activities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `prospect_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `type` VARCHAR(20) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NULL,
  `due_at` TIMESTAMP NULL,
  `done_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `funding_activities_due_at_index` (`due_at`),
  CONSTRAINT `funding_activities_prospect_id_foreign` FOREIGN KEY (`prospect_id`) REFERENCES `funding_prospects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `funding_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `funding_suppressions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `reason` VARCHAR(20) NOT NULL,
  `source` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `funding_suppressions_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `finance_applications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(24) NOT NULL,
  `product` VARCHAR(10) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `steps_done` JSON NULL,
  `token_hash` CHAR(64) NOT NULL,
  `last_seen_at` TIMESTAMP NULL,
  `contact_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(40) NOT NULL,
  `business` JSON NULL,
  `details` JSON NULL,
  `amount` DECIMAL(15,2) NULL,
  `term_months` SMALLINT UNSIGNED NULL,
  `estimate` JSON NULL,
  `consent_at` TIMESTAMP NULL,
  `consent_ip` VARCHAR(45) NULL,
  `submitted_at` TIMESTAMP NULL,
  `prospect_id` BIGINT UNSIGNED NULL,
  `loan_id` BIGINT UNSIGNED NULL,
  `reviewer_id` BIGINT UNSIGNED NULL,
  `reviewed_at` TIMESTAMP NULL,
  `staff_note` TEXT NULL,
  `history` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `finance_applications_reference_unique` (`reference`),
  UNIQUE KEY `finance_applications_token_hash_unique` (`token_hash`),
  KEY `finance_applications_product_index` (`product`),
  KEY `finance_applications_status_index` (`status`),
  CONSTRAINT `finance_applications_prospect_id_foreign` FOREIGN KEY (`prospect_id`) REFERENCES `funding_prospects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `finance_applications_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `finance_applications_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `finance_application_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `finance_application_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(30) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NULL,
  `mime` VARCHAR(100) NULL,
  `size` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `finance_application_documents_finance_application_id_foreign` FOREIGN KEY (`finance_application_id`) REFERENCES `finance_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT n.name, 'web', NOW(), NOW() FROM (SELECT 'manage funding prospects' AS name UNION ALL SELECT 'review finance applications') n
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.name = n.name AND p.guard_name = 'web');

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p JOIN roles r ON r.guard_name = 'web'
WHERE p.guard_name = 'web' AND (
      (p.name = 'manage funding prospects' AND r.name IN ('super-admin', 'admin', 'manager', 'loan-officer'))
   OR (p.name = 'review finance applications' AND r.name IN ('super-admin', 'admin', 'manager')))
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_20_000010_create_funding_crm_and_finance_applications' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: funding CRM, finance applications, two permissions' AS result;
