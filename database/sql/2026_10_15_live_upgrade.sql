-- =====================================================================================
-- Live database upgrade: 15 October 2026
-- Brings a database that matches live (as of 15 Oct 2026) in line with the migrations in
-- database/migrations. Works on MariaDB 10.3 (live) and MySQL 8+/9 (local).
--
-- Safe to run more than once: every change checks first. No data is changed or deleted.
--
-- Changes:
--   1. users.kra_pin            new column (KYC self-service onboarding)
--   2. users.kyc_submitted_at   new column (KYC self-service onboarding)
--   3. enquiries.consented_at   keeps the moment consent was given (drops ON UPDATE CURRENT_TIMESTAMP,
--                               which overwrote it on every edit)
--   4. migrations               records the 86 rebuilt migrations as run, so
--                               `php artisan migrate` has nothing left to do afterwards
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_15_live_upgrade.sql
-- =====================================================================================

-- 1. users.kra_pin
SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `users` ADD COLUMN `kra_pin` VARCHAR(20) NULL DEFAULT NULL AFTER `id_number`',
    'SELECT ''users.kra_pin already exists'' AS note')
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'kra_pin'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. users.kyc_submitted_at
SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `users` ADD COLUMN `kyc_submitted_at` TIMESTAMP NULL DEFAULT NULL AFTER `id_back_path`',
    'SELECT ''users.kyc_submitted_at already exists'' AS note')
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'kyc_submitted_at'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. enquiries.consented_at: default to the current time, never auto-update (only if the table exists)
SET @sql = (
  SELECT IF(COUNT(*) = 1 AND MAX(LOWER(extra)) LIKE '%on update%',
    'ALTER TABLE `enquiries` MODIFY `consented_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
    'SELECT ''enquiries.consented_at already correct (or table not present)'' AS note')
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'enquiries' AND column_name = 'consented_at'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. record the rebuilt migrations as run (one new batch; names already recorded are skipped)
SET @batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT n.migration, @batch
FROM (
  SELECT 'x' AS migration FROM DUAL WHERE FALSE
  UNION ALL SELECT '0001_01_01_000010_create_users_table'
  UNION ALL SELECT '0001_01_01_000011_create_password_reset_tokens_table'
  UNION ALL SELECT '0001_01_01_000012_create_sessions_table'
  UNION ALL SELECT '0001_01_01_000013_create_cache_table'
  UNION ALL SELECT '0001_01_01_000014_create_cache_locks_table'
  UNION ALL SELECT '0001_01_01_000015_create_jobs_table'
  UNION ALL SELECT '0001_01_01_000016_create_job_batches_table'
  UNION ALL SELECT '0001_01_01_000017_create_failed_jobs_table'
  UNION ALL SELECT '0001_01_02_000000_create_wallets_table'
  UNION ALL SELECT '0001_01_02_000001_create_transactions_table'
  UNION ALL SELECT '0001_01_02_000002_create_transfers_table'
  UNION ALL SELECT '0001_01_02_000003_create_permissions_table'
  UNION ALL SELECT '0001_01_02_000004_create_roles_table'
  UNION ALL SELECT '0001_01_02_000005_create_model_has_permissions_table'
  UNION ALL SELECT '0001_01_02_000006_create_model_has_roles_table'
  UNION ALL SELECT '0001_01_02_000007_create_role_has_permissions_table'
  UNION ALL SELECT '2025_01_01_000001_create_action_types_table'
  UNION ALL SELECT '2025_01_01_000002_create_address_types_table'
  UNION ALL SELECT '2025_01_01_000003_create_addresses_table'
  UNION ALL SELECT '2025_01_01_000004_create_admins_table'
  UNION ALL SELECT '2025_01_01_000005_create_asset_types_table'
  UNION ALL SELECT '2025_01_01_000006_create_audit_logs_table'
  UNION ALL SELECT '2025_01_01_000007_create_bank_accounts_table'
  UNION ALL SELECT '2025_01_01_000008_create_brokers_table'
  UNION ALL SELECT '2025_01_01_000009_create_borrowers_table'
  UNION ALL SELECT '2025_01_01_000010_create_bureau_names_table'
  UNION ALL SELECT '2025_01_01_000011_create_categories_table'
  UNION ALL SELECT '2025_01_01_000012_create_communication_statuses_table'
  UNION ALL SELECT '2025_01_01_000013_create_communication_types_table'
  UNION ALL SELECT '2025_01_01_000014_create_contact_types_table'
  UNION ALL SELECT '2025_01_01_000015_create_contacts_table'
  UNION ALL SELECT '2025_01_01_000016_create_document_statuses_table'
  UNION ALL SELECT '2025_01_01_000017_create_document_types_table'
  UNION ALL SELECT '2025_01_01_000018_create_employment_types_table'
  UNION ALL SELECT '2025_01_01_000019_create_employments_table'
  UNION ALL SELECT '2025_01_01_000020_create_enquiries_table'
  UNION ALL SELECT '2025_01_01_000021_create_hardship_reasons_table'
  UNION ALL SELECT '2025_01_01_000022_create_investments_table'
  UNION ALL SELECT '2025_01_01_000023_create_legal_proceeding_types_table'
  UNION ALL SELECT '2025_01_01_000024_create_loan_agreement_templates_table'
  UNION ALL SELECT '2025_01_01_000025_create_loan_types_table'
  UNION ALL SELECT '2025_01_01_000026_create_partners_table'
  UNION ALL SELECT '2025_01_01_000027_create_loans_table'
  UNION ALL SELECT '2025_01_01_000028_create_assets_table'
  UNION ALL SELECT '2025_01_01_000029_create_loan_agreement_sections_table'
  UNION ALL SELECT '2025_01_01_000030_create_loan_cycles_table'
  UNION ALL SELECT '2025_01_01_000031_create_loan_ledger_table'
  UNION ALL SELECT '2025_01_01_000032_create_loan_risk_assessments_table'
  UNION ALL SELECT '2025_01_01_000033_create_partner_transactions_table'
  UNION ALL SELECT '2025_01_01_000034_create_disbursements_table'
  UNION ALL SELECT '2025_01_01_000035_create_investment_fundings_table'
  UNION ALL SELECT '2025_01_01_000036_create_payment_method_types_table'
  UNION ALL SELECT '2025_01_01_000037_create_payment_methods_table'
  UNION ALL SELECT '2025_01_01_000038_create_recovery_agencies_table'
  UNION ALL SELECT '2025_01_01_000039_create_agency_contacts_table'
  UNION ALL SELECT '2025_01_01_000040_create_recovery_performance_metrics_table'
  UNION ALL SELECT '2025_01_01_000041_create_recovery_priorities_table'
  UNION ALL SELECT '2025_01_01_000042_create_recovery_statuses_table'
  UNION ALL SELECT '2025_01_01_000043_create_debt_recovery_cases_table'
  UNION ALL SELECT '2025_01_01_000044_create_agency_case_assignments_table'
  UNION ALL SELECT '2025_01_01_000045_create_communications_table'
  UNION ALL SELECT '2025_01_01_000046_create_credit_bureau_reports_table'
  UNION ALL SELECT '2025_01_01_000047_create_financial_assessments_table'
  UNION ALL SELECT '2025_01_01_000048_create_legal_deadlines_table'
  UNION ALL SELECT '2025_01_01_000049_create_legal_proceedings_table'
  UNION ALL SELECT '2025_01_01_000050_create_court_hearings_table'
  UNION ALL SELECT '2025_01_01_000051_create_recovery_actions_table'
  UNION ALL SELECT '2025_01_01_000052_create_recovery_case_notes_table'
  UNION ALL SELECT '2025_01_01_000053_create_recovery_documents_table'
  UNION ALL SELECT '2025_01_01_000054_create_recovery_payment_plans_table'
  UNION ALL SELECT '2025_01_01_000055_create_recovery_installments_table'
  UNION ALL SELECT '2025_01_01_000056_create_recovery_templates_table'
  UNION ALL SELECT '2025_01_01_000057_create_repayments_table'
  UNION ALL SELECT '2025_01_01_000058_create_residence_types_table'
  UNION ALL SELECT '2025_01_01_000059_create_risk_categories_table'
  UNION ALL SELECT '2025_01_01_000060_create_skip_tracing_table'
  UNION ALL SELECT '2025_01_01_000061_create_system_table'
  UNION ALL SELECT '2025_01_01_000062_create_tellers_table'
  UNION ALL SELECT '2025_01_01_000063_create_user_profiles_table'
  UNION ALL SELECT '2025_01_02_000000_create_borrower_contact_network_view'
  UNION ALL SELECT '2025_01_02_000001_create_broker_performance_report_view'
  UNION ALL SELECT '2025_01_02_000002_create_customer_health_scorecard_view'
  UNION ALL SELECT '2025_01_02_000003_create_debt_recovery_summary_view'
  UNION ALL SELECT '2025_01_02_000004_create_loan_type_analysis_view'
  UNION ALL SELECT '2025_01_02_000005_create_portfolio_summary_view'
  UNION ALL SELECT '2025_01_02_000006_create_recovery_officer_workload_view'
) n
WHERE NOT EXISTS (SELECT 1 FROM `migrations` m WHERE m.migration = n.migration);

SELECT CONCAT('Done. Migrations recorded in this run: ', ROW_COUNT()) AS result;
