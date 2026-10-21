-- =====================================================================================
-- Drop the 7 unused SQL views: 21 October 2026 (Stage 4 Phase 7)
-- borrower_contact_network, broker_performance_report, customer_health_scorecard, debt_recovery_summary,
-- loan_type_analysis, portfolio_summary, recovery_officer_workload. Nothing reads them; reports read the
-- loan cycles and the ledger. Views hold no data.
-- Same change as database/migrations/2026_10_21_000601..000607_drop_*_view.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_21_drop_unused_views.sql
-- =====================================================================================

DROP VIEW IF EXISTS `borrower_contact_network`;
DROP VIEW IF EXISTS `broker_performance_report`;
DROP VIEW IF EXISTS `customer_health_scorecard`;
DROP VIEW IF EXISTS `debt_recovery_summary`;
DROP VIEW IF EXISTS `loan_type_analysis`;
DROP VIEW IF EXISTS `portfolio_summary`;
DROP VIEW IF EXISTS `recovery_officer_workload`;

-- guards outside the aggregate, so a second run inserts nothing
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000601_drop_borrower_contact_network_view' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000602_drop_broker_performance_report_view' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000603_drop_customer_health_scorecard_view' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000604_drop_debt_recovery_summary_view' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000605_drop_loan_type_analysis_view' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000606_drop_portfolio_summary_view' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_21_000607_drop_recovery_officer_workload_view' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: unused views dropped' AS result;
