-- =====================================================================================
-- KYC review by staff: 16 October 2026
-- Borrowers submit their KYC; staff approve or reject it after calling the next of kin and guarantor.
-- Same change as database/migrations/2026_10_16_000000_add_kyc_review_fields_to_users_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
-- Run AFTER 2026_10_15_live_upgrade.sql (it needs users.kyc_submitted_at).
--
-- Changes (all on users, all nullable):
--   kyc_status         null = not submitted / submitted / approved / rejected
--   kyc_reviewed_by    staff user who approved or rejected
--   kyc_reviewed_at    when
--   kyc_review_notes   what staff found / why it was rejected
--   kyc_checks         which verification calls were made, e.g. {"next_of_kin": true, "guarantor": true}
--   + marks anyone who already submitted KYC as 'submitted' (waiting for review)
--   + records the migration as run
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_16_kyc_review.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `kyc_status` VARCHAR(20) NULL DEFAULT NULL AFTER `kyc_submitted_at`',
  'SELECT ''users.kyc_status already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'kyc_status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `kyc_reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `kyc_status`',
  'SELECT ''users.kyc_reviewed_by already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'kyc_reviewed_by');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `kyc_reviewed_at` TIMESTAMP NULL DEFAULT NULL AFTER `kyc_reviewed_by`',
  'SELECT ''users.kyc_reviewed_at already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'kyc_reviewed_at');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `kyc_review_notes` TEXT NULL DEFAULT NULL AFTER `kyc_reviewed_at`',
  'SELECT ''users.kyc_review_notes already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'kyc_review_notes');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- JSON: MariaDB stores it as LONGTEXT with a validity check, MySQL as native JSON
SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `kyc_checks` JSON NULL DEFAULT NULL AFTER `kyc_review_notes`',
  'SELECT ''users.kyc_checks already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'kyc_checks');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- anyone who already submitted is waiting for review
UPDATE `users` SET `kyc_status` = 'submitted' WHERE `kyc_submitted_at` IS NOT NULL AND `kyc_status` IS NULL;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_16_000000_add_kyc_review_fields_to_users_table', COALESCE(MAX(batch), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = '2026_10_16_000000_add_kyc_review_fields_to_users_table');

SELECT 'Done: KYC review fields ready.' AS result;
