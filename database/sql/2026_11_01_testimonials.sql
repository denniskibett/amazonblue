-- =====================================================================================
-- Testimonials from real borrowers, shown on the website once staff approve them (1 November 2026)
-- Same change as database/migrations/2026_11_01_000020_create_testimonials_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> | gzip > backup.sql.gz
-- Run:             mysql <database> < database/sql/2026_11_01_testimonials.sql
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `loan_id` bigint unsigned DEFAULT NULL,
  `rating` tinyint unsigned NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_role` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_testimonials_user` (`user_id`),
  KEY `idx_testimonials_status` (`status`, `approved_at`),
  KEY `fk_testimonials_loan` (`loan_id`),
  KEY `fk_testimonials_approver` (`approved_by`),
  CONSTRAINT `fk_testimonials_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_testimonials_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_testimonials_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (migration, batch) SELECT m.migration, m.batch FROM (SELECT '2026_11_01_000020_create_testimonials_table' AS migration, COALESCE(MAX(batch),0)+1 AS batch FROM migrations) m WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_11_01_000020_create_testimonials_table');

SELECT 'Done: testimonials ready' AS result;
