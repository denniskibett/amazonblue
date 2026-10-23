-- =====================================================================================
-- One behaviour profile per borrower, refreshed nightly by analytics:profiles: 23 October 2026 (owner)
-- Same change as database/migrations/2026_10_23_000002_create_borrower_profiles_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. Holds derived data only.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_23_borrower_profiles.sql
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `borrower_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `loans` int unsigned NOT NULL DEFAULT 0,
  `on_time_rate` decimal(5,1) DEFAULT NULL,
  `avg_days_late` decimal(7,1) NOT NULL DEFAULT 0.0,
  `max_days_late` int unsigned NOT NULL DEFAULT 0,
  `bad_loans` int unsigned NOT NULL DEFAULT 0,
  `rolled` int unsigned NOT NULL DEFAULT 0,
  `comfort_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `largest` decimal(15,2) NOT NULL DEFAULT 0.00,
  `weekend_loans` int unsigned NOT NULL DEFAULT 0,
  `weekend_bad` int unsigned NOT NULL DEFAULT 0,
  `score` tinyint unsigned DEFAULT NULL,
  `suggested_limit` int unsigned NOT NULL DEFAULT 0,
  `flags` text COLLATE utf8mb4_unicode_ci,
  `last_outcome` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `computed_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `borrower_profiles_user_id_unique` (`user_id`),
  KEY `borrower_profiles_score_index` (`score`),
  CONSTRAINT `borrower_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_23_000002_create_borrower_profiles_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);
