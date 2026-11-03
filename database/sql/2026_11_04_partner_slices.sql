-- =====================================================================================
-- Partner money recorded the way it happens (4 November 2026)
-- Same change as database/migrations/2026_11_04_000010_partner_slices_and_share_rates.php:
--   investments.partner_share_rate / partner_penalty_rate   a line's own % of interest and penalty
--   loan_fundings.settled_on / settled_by / settled_interest / settled_transaction_id   a slice the house settled
--   partner_requests.paid_on / bank_reference   a payout recorded after it was made
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> | gzip > backup.sql.gz
-- Run:             mysql <database> < database/sql/2026_11_04_partner_slices.sql
-- =====================================================================================

DROP PROCEDURE IF EXISTS az_add_col;
DELIMITER //
CREATE PROCEDURE az_add_col(IN t VARCHAR(64), IN c VARCHAR(64), IN ddl TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = t AND column_name = c) THEN
    SET @s = CONCAT('ALTER TABLE `', t, '` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END //
DELIMITER ;

CALL az_add_col('investments', 'partner_share_rate', '`partner_share_rate` DECIMAL(5,2) NULL AFTER `return_type`');
CALL az_add_col('investments', 'partner_penalty_rate', '`partner_penalty_rate` DECIMAL(5,2) NULL AFTER `partner_share_rate`');
CALL az_add_col('loan_fundings', 'settled_on', '`settled_on` DATE NULL AFTER `source`');
CALL az_add_col('loan_fundings', 'settled_by', '`settled_by` VARCHAR(20) NULL AFTER `settled_on`');
CALL az_add_col('loan_fundings', 'settled_interest', '`settled_interest` DECIMAL(12,2) NULL AFTER `settled_by`');
CALL az_add_col('loan_fundings', 'settled_transaction_id', '`settled_transaction_id` BIGINT UNSIGNED NULL AFTER `settled_interest`');
CALL az_add_col('partner_requests', 'paid_on', '`paid_on` DATE NULL AFTER `status`');
CALL az_add_col('partner_requests', 'bank_reference', '`bank_reference` VARCHAR(60) NULL AFTER `paid_on`');
DROP PROCEDURE IF EXISTS az_add_col;

-- (MAX() always returns one row, so the NOT EXISTS guard sits outside the aggregate)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_04_000010_partner_slices_and_share_rates' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: partner share rates, settled slices, paid payouts' AS result;
