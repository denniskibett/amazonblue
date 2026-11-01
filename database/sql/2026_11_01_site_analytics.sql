-- =====================================================================================
-- Site analytics: one row per page view, first-party, no IPs and no raw session ids (1 November 2026)
-- Same change as database/migrations/2026_11_01_000010_create_page_views_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> | gzip > backup.sql.gz
-- Run:             mysql <database> < database/sql/2026_11_01_site_analytics.sql
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `page_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `occurred_at` datetime NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `role` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'visitor',
  `session_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` smallint unsigned NOT NULL DEFAULT 200,
  `referrer_host` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_medium` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_campaign` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'desktop',
  `response_ms` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_page_views_occurred` (`occurred_at`),
  KEY `idx_page_views_user` (`user_id`, `occurred_at`),
  KEY `idx_page_views_session` (`session_hash`, `occurred_at`),
  KEY `idx_page_views_route` (`route_name`, `occurred_at`),
  CONSTRAINT `fk_page_views_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_01_000010_create_page_views_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = '2026_11_01_000010_create_page_views_table');

SELECT 'Done: page_views ready' AS result;
