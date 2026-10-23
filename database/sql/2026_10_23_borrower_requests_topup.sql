-- =====================================================================================
-- Borrowers can ask for a top-up: 23 October 2026 (owner)
-- Same change as database/migrations/2026_10_23_000003_add_topup_to_borrower_requests.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_23_borrower_requests_topup.sql
-- =====================================================================================

ALTER TABLE `borrower_requests` MODIFY `type` ENUM('extension','plan','help','topup') COLLATE utf8mb4_unicode_ci NOT NULL;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_23_000003_add_topup_to_borrower_requests' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);
