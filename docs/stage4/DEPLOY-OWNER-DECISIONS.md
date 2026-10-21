# Owner decisions, 21 October 2026 (`loans:owner-decisions`)

Forbearance means "indefinitely, with a day to pay": the latest cycle's penalty is waived open-ended (31 Dec 2037),
its due date is the pay-by day, and staff move that day by editing the loan when an extension is asked for. The
waiver covers the whole cycle, so penalty already charged on it comes off. The nightly `loans:update-statuses` never
defaults a loan in forbearance.

| Loan | Borrower | Decision | Pay by | Owed before → after |
|---|---|---|---|---|
| #467 | Sharon Chemurgor | forbearance | 1 Oct 2026 | 62,208.00 → 62,208.00 |
| #389 | Emmanuel Ruwa Tsuma | forbearance | 8 Oct 2026 | 27,651.43 → 27,651.43 |
| #377 | Judy Kerebi | forbearance | 3 Oct 2026 | 2,749.60 → 2,749.60 |
| #212 | David Ihaji | forbearance (penalty waived) | 3 Oct 2026 | 1,876,219.22 → 1,023,392.30 |
| #458 | ISIRO Agencies | payment plan 1,000,000 at 0%, forbearance | 22 Oct 2026 | 1,854,749.95 → 1,000,000.00 |
| #156 | Faith Chepngetich | payment plan 12,000 at 0% (dated 30 Sep), forbearance | 1 Oct 2026 | 24,560.00 → 12,000.00 |
| #511 | Joseph (Trade Finance per year) | active: default flags cleared | 29 May 2027 | 149,999.00 |

A plan's amount below what was owed is booked as a rollover adjustment on the old cycle.

Rehearsed on a copy of live: all 7 applied; trial balance 0.0000; all wallets reconcile; the nightly run changes none.

## On live (fresh shell)

1. `mysqldump --single-transaction --routines --triggers loans_main | gzip > ~/backups/pre_owner_decisions_$(date +%Y%m%d_%H%M%S).sql.gz`
2. `php artisan loans:owner-decisions --dry-run` (the table must match the one above)
3. `php artisan loans:owner-decisions --apply --confirm=<sha>`
4. `php artisan ledger:accrue && php artisan wallets:check && php artisan loans:update-statuses --dry-run`

Rollback: restore `pre_owner_decisions_*`.
