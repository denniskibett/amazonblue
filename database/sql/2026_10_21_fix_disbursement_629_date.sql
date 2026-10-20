-- =====================================================================================
-- Data correction: disbursement #629 (loan 510) date, 21 October 2026
-- Entered as 2026-12-10 (a date in the future); day and month were swapped. The owner confirmed 2026-10-12,
-- the day the row was entered. Only the two date columns change; the amount, the loan and the cycle do not.
-- Recorded in audit_logs (old and new values). Runs only while the row still has the wrong date.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_21_fix_disbursement_629_date.sql
-- =====================================================================================

SET @wrong = (SELECT COUNT(*) FROM disbursements WHERE id = 629 AND disburse_date = '2026-12-10' AND payment_date = '2026-12-10');

INSERT INTO audit_logs (table_name, record_id, action, old_values, new_values, user_id, ip_address, user_agent, created_at)
SELECT 'disbursements', 629, 'update',
  '{"disburse_date":"2026-12-10","payment_date":"2026-12-10"}',
  '{"disburse_date":"2026-10-12","payment_date":"2026-10-12","reason":"day and month swapped; owner confirmed 21 Oct 2026"}',
  NULL, NULL, 'database/sql/2026_10_21_fix_disbursement_629_date.sql', NOW()
FROM DUAL WHERE @wrong = 1;

UPDATE disbursements SET disburse_date = '2026-10-12', payment_date = '2026-10-12', updated_at = NOW()
WHERE id = 629 AND disburse_date = '2026-12-10' AND payment_date = '2026-12-10';

SELECT id, loan_id, disburse_date, payment_date, amount FROM disbursements WHERE id = 629;
