-- =====================================================================================
-- Part funding (2 November 2026): who put in what of a loan's cash. Several rows = part funded (each repayment's
-- interest and principal split pro rata); one row = a single funder. Same change as
-- database/migrations/2026_11_02_000010_create_loan_fundings_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 9 (local). Safe to run more than once. Creates an empty table; no data changed.
-- The frozen funding of past loans is NOT written here: after deploy run
--   php artisan loans:backfill-fundings            (dry run: counts and a sample, writes nothing)
--   php artisan loans:backfill-fundings --apply    (adds one row per past loan; re-running adds nothing)
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_11_02_part_funding.sql
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `loan_fundings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint unsigned NOT NULL,
  `investment_id` bigint unsigned DEFAULT NULL COMMENT 'The line the money came from; NULL = house with no line',
  `partner_id` bigint unsigned DEFAULT NULL COMMENT 'The partner behind the line; NULL = the house',
  `amount` decimal(15,2) NOT NULL COMMENT 'What this funder put in of the amount sent',
  `share` decimal(9,6) NOT NULL COMMENT 'amount / total, for display; the split uses the amounts',
  `disbursement_id` bigint unsigned DEFAULT NULL,
  `source` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disbursement' COMMENT 'disbursement | backfill 2026-11-02 | partner_links',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_loan_fundings_loan` (`loan_id`),
  KEY `idx_loan_fundings_line` (`investment_id`),
  KEY `fk_loan_fundings_partner` (`partner_id`),
  KEY `fk_loan_fundings_disbursement` (`disbursement_id`),
  CONSTRAINT `fk_loan_fundings_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_loan_fundings_line` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_loan_fundings_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_loan_fundings_disbursement` FOREIGN KEY (`disbursement_id`) REFERENCES `disbursements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_02_000010_create_loan_fundings_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: loan_fundings ready' AS result;
