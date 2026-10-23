-- =====================================================================================
-- Borrowers: pay frequency and pay day (borrower-entered) and a copy of repayment behaviour (nightly): 23 October 2026
-- Same change as database/migrations/2026_10_23_000004_add_pay_and_behaviour_to_borrowers.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. Adds empty columns; no data changed.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_23_borrowers_pay_and_behaviour.sql
-- =====================================================================================

ALTER TABLE `borrowers`
  ADD COLUMN IF NOT EXISTS `pay_frequency` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `net_salary`,
  ADD COLUMN IF NOT EXISTS `pay_day` tinyint unsigned DEFAULT NULL AFTER `pay_frequency`,
  ADD COLUMN IF NOT EXISTS `profile_completed_at` timestamp NULL DEFAULT NULL AFTER `department`,
  ADD COLUMN IF NOT EXISTS `behaviour_score` tinyint unsigned DEFAULT NULL AFTER `profile_completed_at`,
  ADD COLUMN IF NOT EXISTS `on_time_rate` decimal(5,1) DEFAULT NULL AFTER `behaviour_score`,
  ADD COLUMN IF NOT EXISTS `comfort_amount` decimal(15,2) DEFAULT NULL AFTER `on_time_rate`,
  ADD COLUMN IF NOT EXISTS `suggested_limit` int unsigned DEFAULT NULL AFTER `comfort_amount`,
  ADD COLUMN IF NOT EXISTS `behaviour_flags` text COLLATE utf8mb4_unicode_ci AFTER `suggested_limit`,
  ADD COLUMN IF NOT EXISTS `behaviour_at` timestamp NULL DEFAULT NULL AFTER `behaviour_flags`;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_23_000004_add_pay_and_behaviour_to_borrowers' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);
