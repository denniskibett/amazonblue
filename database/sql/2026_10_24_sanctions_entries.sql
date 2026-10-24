-- =====================================================================================
-- AML screening: the uploaded sanctions list (the only new table this round) (24 October 2026)
-- Same change as database/migrations/2026_10_24_000003_create_sanctions_entries.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+ (local). Safe to run more than once. No data changed or deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> | gzip > backup.sql.gz
-- Run:             mysql <database> < database/sql/2026_10_24_sanctions_entries.sql
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `sanctions_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `list_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kind` enum('individual','entity') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dob` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sanctions_entries_name_key` (`name_key`),
  KEY `idx_sanctions_entries_list` (`list_name`),
  KEY `fk_sanctions_entries_user` (`uploaded_by`),
  CONSTRAINT `fk_sanctions_entries_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_24_000003_create_sanctions_entries' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: sanctions_entries ready' AS result;
