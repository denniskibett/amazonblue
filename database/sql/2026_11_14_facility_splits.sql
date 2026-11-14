-- =====================================================================================
-- Facility splits (14 November 2026)
-- Same change as database/migrations/2026_11_14_000010_create_facility_splits_table.php and
-- 2026_11_14_000020_create_facility_split_parties_table.php:
--   facility_splits          a facility's own split: principal plus interest first, then penalty, up to a total
--   facility_split_parties   who shares it: principal, entitlement, penalty %, what they had already received
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
-- The split for loan 458 is set afterwards with: php artisan loans:facility-split (see docs in that command).
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `facility_splits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `loan_id` BIGINT UNSIGNED NOT NULL,
  `pi_target` DECIMAL(15,2) NOT NULL COMMENT 'principal plus interest the parties share first',
  `total_cap` DECIMAL(15,2) NOT NULL COMMENT 'everything collected up to here counts; above pi_target it is penalty',
  `counted_from` DATE NOT NULL COMMENT 'payments on or after this day are split by this rule',
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `facility_splits_loan_id_unique` (`loan_id`),
  KEY `facility_splits_created_by_foreign` (`created_by`),
  CONSTRAINT `facility_splits_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `facility_splits_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facility_split_parties` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `facility_split_id` BIGINT UNSIGNED NOT NULL,
  `partner_id` BIGINT UNSIGNED NOT NULL,
  `principal` DECIMAL(15,2) NOT NULL,
  `pi_entitled` DECIMAL(15,2) NOT NULL COMMENT 'this party''s principal plus interest',
  `penalty_pct` DECIMAL(5,2) NOT NULL COMMENT 'this party''s % of the penalty',
  `received_before` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'already received before counted_from',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `facility_split_parties_facility_split_id_partner_id_unique` (`facility_split_id`, `partner_id`),
  KEY `facility_split_parties_partner_id_foreign` (`partner_id`),
  CONSTRAINT `facility_split_parties_facility_split_id_foreign` FOREIGN KEY (`facility_split_id`) REFERENCES `facility_splits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `facility_split_parties_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_14_000010_create_facility_splits_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
  UNION ALL
  SELECT '2026_11_14_000020_create_facility_split_parties_table', COALESCE(MAX(batch), 0) + 1 FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.migration = m.migration);

SELECT 'Done: facility split tables' AS result;
