-- =====================================================================================
-- Integrations page and API keys (3 November 2026)
-- Same change as database/migrations/2026_11_03_000010_create_integrations_table.php and
-- 2026_11_03_000020_create_api_keys_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
-- After it: php artisan integrations:import-env   (copies the current .env values in, encrypted)
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> | gzip > backup.sql.gz
-- Run:             mysql <database> < database/sql/2026_11_03_integrations_api_keys.sql
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `integrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `settings` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_integrations_slug` (`slug`),
  KEY `fk_integrations_updated_by` (`updated_by`),
  CONSTRAINT `fk_integrations_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_four` char(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','disabled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `last_used_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_keys_token_hash` (`token_hash`),
  KEY `fk_api_keys_created_by` (`created_by`),
  CONSTRAINT `fk_api_keys_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (migration, batch) SELECT m.migration, m.batch FROM (SELECT '2026_11_03_000010_create_integrations_table' AS migration, COALESCE(MAX(batch),0)+1 AS batch FROM migrations) m WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_11_03_000010_create_integrations_table');
INSERT INTO migrations (migration, batch) SELECT m.migration, m.batch FROM (SELECT '2026_11_03_000020_create_api_keys_table' AS migration, COALESCE(MAX(batch),0)+1 AS batch FROM migrations) m WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_11_03_000020_create_api_keys_table');

SELECT 'Done: integrations and api_keys ready' AS result;
