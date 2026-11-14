-- =====================================================================================
-- Mobile app test API (11 November 2026)
-- Same change as database/migrations/2026_11_11_000010_create_personal_access_tokens_table.php and
-- 2026_11_11_000020_create_mobile_devices_tables.php:
--   personal_access_tokens   Laravel Sanctum tokens, one per phone
--   mobile_devices           phones a user signs in on (model, system, app version, push token)
--   data_consents            what the borrower allowed the app to use
--   device_sightings         approximate area when signing in, applying and paying (never coordinates)
--   api_keys.client          what uses the key: mobile (the app) or system (other systems)
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` BIGINT UNSIGNED NOT NULL,
  `name` TEXT NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `abilities` TEXT NULL,
  `last_used_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mobile_devices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `install_id` VARCHAR(64) NOT NULL,
  `platform` VARCHAR(10) NOT NULL DEFAULT 'android',
  `model` VARCHAR(80) NULL,
  `os_version` VARCHAR(40) NULL,
  `app_version` VARCHAR(20) NULL,
  `push_token` VARCHAR(255) NULL,
  `first_seen_at` TIMESTAMP NULL,
  `last_seen_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile_devices_user_id_install_id_unique` (`user_id`, `install_id`),
  KEY `mobile_devices_install_id_index` (`install_id`),
  CONSTRAINT `mobile_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `data_consents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `item` VARCHAR(40) NOT NULL,
  `granted` TINYINT(1) NOT NULL,
  `version` VARCHAR(20) NOT NULL DEFAULT '1',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `data_consents_user_id_item_unique` (`user_id`, `item`),
  CONSTRAINT `data_consents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `device_sightings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mobile_device_id` BIGINT UNSIGNED NOT NULL,
  `event` VARCHAR(20) NOT NULL,
  `area` VARCHAR(120) NULL,
  `seen_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  KEY `device_sightings_mobile_device_id_seen_at_index` (`mobile_device_id`, `seen_at`),
  CONSTRAINT `device_sightings_mobile_device_id_foreign` FOREIGN KEY (`mobile_device_id`) REFERENCES `mobile_devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS az_add_client;
DELIMITER //
CREATE PROCEDURE az_add_client()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'api_keys' AND column_name = 'client') THEN
    ALTER TABLE `api_keys` ADD COLUMN `client` VARCHAR(20) NOT NULL DEFAULT 'system' AFTER `name`;
  END IF;
END //
DELIMITER ;
CALL az_add_client();
DROP PROCEDURE IF EXISTS az_add_client;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_11_000010_create_personal_access_tokens_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
  UNION ALL
  SELECT '2026_11_11_000020_create_mobile_devices_tables', COALESCE(MAX(batch), 0) + 1 FROM `migrations`
  UNION ALL
  SELECT '2026_11_11_000030_add_client_to_api_keys_table', COALESCE(MAX(batch), 0) + 1 FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.migration = m.migration);

SELECT 'Done: mobile API tables' AS result;
