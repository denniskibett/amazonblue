-- =====================================================================================
-- Data correction: disbursement #301 (loan 272) date, 21 October 2026
-- Entered as 2026-12-26 (payment date 2026-12-05), both in the future. The owner confirmed 2025-12-26, the
-- loan's borrow date. Only the two date columns change. Recorded in audit_logs. Runs only while the row
-- still has the wrong dates. Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_21_fix_disbursement_301_date.sql
-- =====================================================================================

SET @wrong = (SELECT COUNT(*) FROM disbursements WHERE id = 301 AND disburse_date = '2026-12-26' AND payment_date = '2026-12-05');

INSERT INTO audit_logs (table_name, record_id, action, old_values, new_values, user_id, ip_address, user_agent, created_at)
SELECT 'disbursements', 301, 'update',
  '{"disburse_date":"2026-12-26","payment_date":"2026-12-05"}',
  '{"disburse_date":"2025-12-26","payment_date":"2025-12-26","reason":"wrong year; owner confirmed 21 Oct 2026"}',
  NULL, NULL, 'database/sql/2026_10_21_fix_disbursement_301_date.sql', NOW()
FROM DUAL WHERE @wrong = 1;

UPDATE disbursements SET disburse_date = '2025-12-26', payment_date = '2025-12-26', updated_at = NOW()
WHERE id = 301 AND disburse_date = '2026-12-26' AND payment_date = '2026-12-05';

SELECT id, loan_id, disburse_date, payment_date, amount FROM disbursements WHERE id = 301;
