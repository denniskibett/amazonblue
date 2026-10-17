-- =====================================================================================
-- Partner journal fix: 16 October 2026
-- partner_transactions.wallet_slug was never saved (missing from the model's fillable fields), so every
-- journal row fell back to the column default 'principal'. Each row's real wallet is the one its bavix
-- posting (same reference) went to; this sets the journal to match. On live data: the house account's
-- 466 interest rows (KES 4,420,156.64) move from 'principal' to 'interest'. Amounts are not touched.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_16_partner_journal_wallet_slug.sql
-- Then (app):      php artisan wallets:check --repair-partners
-- =====================================================================================

-- what will change (before)
SELECT pt.partner_id, pt.wallet_slug AS journal_says, w.slug AS posted_to, COUNT(*) AS rows_to_fix, SUM(pt.amount) AS amount
FROM partner_transactions pt
JOIN transactions t ON JSON_UNQUOTE(JSON_EXTRACT(t.meta, '$.reference')) = pt.reference
JOIN wallets w ON w.id = t.wallet_id AND w.holder_type = 'App\\Models\\Partner' AND w.holder_id = pt.partner_id
WHERE pt.wallet_slug <> w.slug
GROUP BY pt.partner_id, pt.wallet_slug, w.slug;

UPDATE partner_transactions pt
JOIN transactions t ON JSON_UNQUOTE(JSON_EXTRACT(t.meta, '$.reference')) = pt.reference
JOIN wallets w ON w.id = t.wallet_id AND w.holder_type = 'App\\Models\\Partner' AND w.holder_id = pt.partner_id
SET pt.wallet_slug = w.slug
WHERE pt.wallet_slug <> w.slug;

SELECT CONCAT('Done. Journal rows corrected: ', ROW_COUNT()) AS result;
