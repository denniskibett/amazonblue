-- =====================================================================================
-- Businesses (business KYC), 19 October 2026
-- A verified borrower adds a business from /account; staff review it in Business reviews.
-- Three new tables, nothing else changes:
--   businesses          the company (owner_user_id = the borrower who added it), review status and checks
--   business_members    directors, owners, shareholders and signatories (ownership %, signatory flag)
--   business_documents  certificate, CR12, KRA PIN certificate, bank statement, permit, other
--                       (files on the private disk, storage/app/private/business_documents)
--   + records the three migrations as run
-- Same change as database/migrations/2026_10_19_00001{1,2,3}_create_business*_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_19_businesses.sql
-- Then:            php artisan optimize:clear
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `businesses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_user_id` BIGINT UNSIGNED NOT NULL,
  `legal_name` VARCHAR(191) NOT NULL,
  `trading_name` VARCHAR(191) NULL DEFAULT NULL,
  `registration_number` VARCHAR(50) NULL DEFAULT NULL,
  `registration_type` VARCHAR(30) NULL DEFAULT NULL,
  `kra_pin` VARCHAR(20) NULL DEFAULT NULL,
  `industry` VARCHAR(100) NULL DEFAULT NULL,
  `date_registered` DATE NULL DEFAULT NULL,
  `address_line_1` VARCHAR(191) NULL DEFAULT NULL,
  `address_line_2` VARCHAR(191) NULL DEFAULT NULL,
  `city` VARCHAR(100) NULL DEFAULT NULL,
  `county` VARCHAR(100) NULL DEFAULT NULL,
  `postal_code` VARCHAR(20) NULL DEFAULT NULL,
  `country` VARCHAR(100) NULL DEFAULT 'Kenya',
  `phone` VARCHAR(30) NULL DEFAULT NULL,
  `email` VARCHAR(191) NULL DEFAULT NULL,
  `website` VARCHAR(191) NULL DEFAULT NULL,
  `monthly_revenue` DECIMAL(15,2) NULL DEFAULT NULL,
  `employees` INT UNSIGNED NULL DEFAULT NULL,
  `bank_name` VARCHAR(100) NULL DEFAULT NULL,
  `bank_account` VARCHAR(50) NULL DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `submitted_at` TIMESTAMP NULL DEFAULT NULL,
  `reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `review_notes` TEXT NULL,
  `checks` JSON NULL DEFAULT NULL,
  `handle` VARCHAR(64) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `businesses` ADD UNIQUE KEY `businesses_handle_unique` (`handle`)',
  'SELECT ''businesses.businesses_handle_unique already exists'' AS note')
  FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'businesses' AND index_name = 'businesses_handle_unique');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `businesses` ADD KEY `businesses_owner_user_id_foreign` (`owner_user_id`)',
  'SELECT ''businesses.businesses_owner_user_id_foreign already exists'' AS note')
  FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'businesses' AND index_name = 'businesses_owner_user_id_foreign');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `businesses` ADD KEY `businesses_registration_number_index` (`registration_number`)',
  'SELECT ''businesses.businesses_registration_number_index already exists'' AS note')
  FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'businesses' AND index_name = 'businesses_registration_number_index');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `businesses` ADD KEY `businesses_status_index` (`status`)',
  'SELECT ''businesses.businesses_status_index already exists'' AS note')
  FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'businesses' AND index_name = 'businesses_status_index');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `businesses` ADD KEY `businesses_reviewed_by_foreign` (`reviewed_by`)',
  'SELECT ''businesses.businesses_reviewed_by_foreign already exists'' AS note')
  FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'businesses' AND index_name = 'businesses_reviewed_by_foreign');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `businesses` ADD CONSTRAINT `businesses_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE',
  'SELECT ''businesses.businesses_owner_user_id_foreign already exists'' AS note')
  FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'businesses' AND constraint_name = 'businesses_owner_user_id_foreign' AND constraint_type = 'FOREIGN KEY');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `businesses` ADD CONSTRAINT `businesses_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL',
  'SELECT ''businesses.businesses_reviewed_by_foreign already exists'' AS note')
  FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'businesses' AND constraint_name = 'businesses_reviewed_by_foreign' AND constraint_type = 'FOREIGN KEY');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `business_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `full_name` VARCHAR(191) NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'director',
  `id_number` VARCHAR(50) NULL DEFAULT NULL,
  `kra_pin` VARCHAR(20) NULL DEFAULT NULL,
  `phone` VARCHAR(30) NULL DEFAULT NULL,
  `email` VARCHAR(191) NULL DEFAULT NULL,
  `ownership_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `is_signatory` TINYINT(1) NOT NULL DEFAULT 0,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `business_members` ADD KEY `business_members_business_id_foreign` (`business_id`)',
  'SELECT ''business_members.business_members_business_id_foreign already exists'' AS note')
  FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'business_members' AND index_name = 'business_members_business_id_foreign');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `business_members` ADD KEY `business_members_user_id_foreign` (`user_id`)',
  'SELECT ''business_members.business_members_user_id_foreign already exists'' AS note')
  FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'business_members' AND index_name = 'business_members_user_id_foreign');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `business_members` ADD CONSTRAINT `business_members_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE',
  'SELECT ''business_members.business_members_business_id_foreign already exists'' AS note')
  FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'business_members' AND constraint_name = 'business_members_business_id_foreign' AND constraint_type = 'FOREIGN KEY');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `business_members` ADD CONSTRAINT `business_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL',
  'SELECT ''business_members.business_members_user_id_foreign already exists'' AS note')
  FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'business_members' AND constraint_name = 'business_members_user_id_foreign' AND constraint_type = 'FOREIGN KEY');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `business_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(30) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NULL DEFAULT NULL,
  `mime` VARCHAR(100) NULL DEFAULT NULL,
  `size` BIGINT UNSIGNED NULL DEFAULT NULL,
  `uploaded_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `business_documents` ADD KEY `business_documents_business_id_foreign` (`business_id`)',
  'SELECT ''business_documents.business_documents_business_id_foreign already exists'' AS note')
  FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'business_documents' AND index_name = 'business_documents_business_id_foreign');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `business_documents` ADD KEY `business_documents_uploaded_by_foreign` (`uploaded_by`)',
  'SELECT ''business_documents.business_documents_uploaded_by_foreign already exists'' AS note')
  FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'business_documents' AND index_name = 'business_documents_uploaded_by_foreign');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `business_documents` ADD CONSTRAINT `business_documents_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE',
  'SELECT ''business_documents.business_documents_business_id_foreign already exists'' AS note')
  FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'business_documents' AND constraint_name = 'business_documents_business_id_foreign' AND constraint_type = 'FOREIGN KEY');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `business_documents` ADD CONSTRAINT `business_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL',
  'SELECT ''business_documents.business_documents_uploaded_by_foreign already exists'' AS note')
  FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'business_documents' AND constraint_name = 'business_documents_uploaded_by_foreign' AND constraint_type = 'FOREIGN KEY');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- record the migrations as run, so `php artisan migrate` has nothing left to do
INSERT INTO migrations (migration, batch) SELECT m.migration, m.batch FROM (SELECT '2026_10_19_000011_create_businesses_table' AS migration, COALESCE(MAX(batch),0)+1 AS batch FROM migrations) m WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration='2026_10_19_000011_create_businesses_table');
INSERT INTO migrations (migration, batch) SELECT m.migration, m.batch FROM (SELECT '2026_10_19_000012_create_business_members_table' AS migration, COALESCE(MAX(batch),0)+1 AS batch FROM migrations) m WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration='2026_10_19_000012_create_business_members_table');
INSERT INTO migrations (migration, batch) SELECT m.migration, m.batch FROM (SELECT '2026_10_19_000013_create_business_documents_table' AS migration, COALESCE(MAX(batch),0)+1 AS batch FROM migrations) m WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration='2026_10_19_000013_create_business_documents_table');
