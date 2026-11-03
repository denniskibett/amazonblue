-- =====================================================================================
-- Outside debt collection agencies (3 November 2026)
-- Same change as database/migrations/2026_11_03_000050_create_collection_agencies_tables.php, plus the
-- "collector" role and the "manage collection agencies" permission from database/seeders/RoleSeeder.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> | gzip > backup.sql.gz
-- Run:             mysql <database> < database/sql/2026_11_03_collection_agencies.sql
-- After it:        php artisan permission:cache-reset
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `collection_agencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agreement_ref` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `coverage` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cross_border` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','paused') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `collection_agency_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_collection_agency_users_user` (`user_id`),
  KEY `ix_collection_agency_users_agency` (`agency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `collection_placements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` bigint unsigned NOT NULL,
  `loan_id` bigint unsigned NOT NULL,
  `placed_by` bigint unsigned DEFAULT NULL,
  `placed_at` datetime NOT NULL,
  `notice_sent_on` date DEFAULT NULL,
  `ends_on` date NOT NULL,
  `status` enum('active','returned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `returned_at` datetime DEFAULT NULL,
  `returned_by` bigint unsigned DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_collection_placements_loan` (`loan_id`,`status`),
  KEY `ix_collection_placements_agency` (`agency_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `collection_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `placement_id` bigint unsigned NOT NULL,
  `loan_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `channel` enum('call','sms','visit','whatsapp','email') COLLATE utf8mb4_unicode_ci NOT NULL,
  `outcome` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `promise_amount` decimal(12,2) DEFAULT NULL,
  `promise_date` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_collection_contacts_loan` (`loan_id`),
  KEY `ix_collection_contacts_placement` (`placement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- the collector role (no staff permissions) and who may manage agencies and placements
INSERT INTO roles (name, guard_name, created_at, updated_at)
SELECT 'collector', 'web', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'collector' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'manage collection agencies', 'web', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'manage collection agencies' AND guard_name = 'web');

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p JOIN roles r ON r.guard_name = 'web'
WHERE p.guard_name = 'web' AND p.name = 'manage collection agencies'
  AND r.name IN ('super-admin', 'admin', 'manager', 'recovery-officer')
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id);

-- (MAX() always returns one row, so the NOT EXISTS guard sits outside the aggregate)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_03_000050_create_collection_agencies_tables' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: collection agencies, placements and contacts; collector role' AS result;
