-- =====================================================================================
-- Email/SMS batches approved before they go out: 23 October 2026 (owner)
-- Same change as database/migrations/2026_10_23_000001_create_message_batches_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_23_message_batches.sql
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `message_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kind` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channels` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipients` int unsigned NOT NULL DEFAULT 0,
  `sent` int unsigned NOT NULL DEFAULT 0,
  `failed` int unsigned NOT NULL DEFAULT 0,
  `created_by` bigint unsigned DEFAULT NULL,
  `sent_by` bigint unsigned DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_batches_kind_status_index` (`kind`,`status`),
  KEY `message_batches_created_by_foreign` (`created_by`),
  KEY `message_batches_sent_by_foreign` (`sent_by`),
  CONSTRAINT `message_batches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `message_batches_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `message_batch_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `loan_id` bigint unsigned DEFAULT NULL,
  `owed` decimal(15,2) NOT NULL DEFAULT 0.00,
  `days_late` int NOT NULL DEFAULT 0,
  `due_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_batch_items_batch_id_foreign` (`batch_id`),
  KEY `message_batch_items_user_id_status_index` (`user_id`,`status`),
  KEY `message_batch_items_loan_id_index` (`loan_id`),
  CONSTRAINT `message_batch_items_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `message_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_batch_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_23_000001_create_message_batches_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);
