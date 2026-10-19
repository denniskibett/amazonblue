-- =====================================================================================
-- Signed loan agreements: tamper evidence, 18 October 2026
-- When a borrower consents, the agreement PDF is generated once, stored privately and never
-- regenerated; its fingerprint and signing details are kept on the loan.
-- Same change as database/migrations/2026_10_18_000001_add_agreement_fields_to_loans_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
--
-- Changes (all on loans, all nullable):
--   agreement_path        the frozen PDF, relative to storage/app/private
--   agreement_sha256      SHA-256 of that PDF
--   agreement_signed_at   when it was signed (consent)
--   agreement_signer_ip   IP of the consent request (null for older loans)
--   agreement_user_agent  browser of the consent request (null for older loans)
--   agreement_version     agreement template version ("legacy" for copies frozen from before)
--   + records the migration as run
--
-- After this script, move existing agreements and KYC files off the public disk:
--   php artisan files:make-private --dry-run
--   php artisan files:make-private
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_18_loan_agreement_fields.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loans` ADD COLUMN `agreement_path` VARCHAR(255) NULL DEFAULT NULL AFTER `consent_date`',
  'SELECT ''loans.agreement_path already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loans' AND column_name = 'agreement_path');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loans` ADD COLUMN `agreement_sha256` CHAR(64) NULL DEFAULT NULL AFTER `agreement_path`',
  'SELECT ''loans.agreement_sha256 already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loans' AND column_name = 'agreement_sha256');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loans` ADD COLUMN `agreement_signed_at` TIMESTAMP NULL DEFAULT NULL AFTER `agreement_sha256`',
  'SELECT ''loans.agreement_signed_at already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loans' AND column_name = 'agreement_signed_at');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loans` ADD COLUMN `agreement_signer_ip` VARCHAR(45) NULL DEFAULT NULL AFTER `agreement_signed_at`',
  'SELECT ''loans.agreement_signer_ip already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loans' AND column_name = 'agreement_signer_ip');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loans` ADD COLUMN `agreement_user_agent` VARCHAR(255) NULL DEFAULT NULL AFTER `agreement_signer_ip`',
  'SELECT ''loans.agreement_user_agent already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loans' AND column_name = 'agreement_user_agent');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `loans` ADD COLUMN `agreement_version` VARCHAR(30) NULL DEFAULT NULL AFTER `agreement_user_agent`',
  'SELECT ''loans.agreement_version already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'loans' AND column_name = 'agreement_version');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_18_000001_add_agreement_fields_to_loans_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = '2026_10_18_000001_add_agreement_fields_to_loans_table');

SELECT 'Done: loan agreement fields ready. Now run: php artisan files:make-private' AS result;
