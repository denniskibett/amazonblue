# Feature 2a: rollovers live in loan_cycles only (21 October 2026)

## What it does (`loans:rollover-cleanup`, Operations review → "Rollover chains")

1. **Chains the first merge missed.** A later loan of the same borrower that opened with a ROLL OVER becomes the
   earlier loan's next cycle(s) when the earlier loan still owed money and either its latest cycle was due within 3
   days of the new loan's start, or its ROLL OVER repayment is exactly what the new loan lent (within 15 days).
   Owner, 21 Oct 2026: merge #282←330, #138←456, #216←237, #389←479, #126←158, #112←209 (the 5,000 Moluk did not
   carry is booked as a rollover adjustment). #9→48 is left for 2b.
2. **ROLL OVER rows the cycles already hold** are soft-deleted (a ROLL OVER repayment on a cycle carried into a
   later one; a ROLL OVER disbursement where a later cycle starts within 3 days). They were never money: no wallet,
   ledger or split posting, so no balance changes. ROLL OVER rows on a loan's latest cycle are kept.
3. **Loans never sent money** (pending/approved/rejected, no disbursement) charge nothing: their posted interest and
   penalty come off the ledger (`Ledger::neverSent`).

Also: `Disbursement` uses `SoftDeletes` (the column existed; no row was deleted before this).

## Rehearsal on a copy of live (local, pulled 18:58)

6 chains merged; 456 ROLL OVER rows removed (81 in the chains, 375 elsewhere); 22 never-sent postings reversed.
Trial balance 0.0000; all wallets reconcile; repaid_with_balance 0; receivable mismatches 98 → 78 (net rises to
1,696,293.72 only because the never-sent loans' −103,845 left the list). No split change.

## On live (after `scripts/push-live.sh`, in a fresh shell)

1. `cd /home/loans.sharet.africa/laravel && php -r 'echo getcwd(),"\n";'` (must be the live folder)
2. `mysqldump --single-transaction --routines --triggers loans_main | gzip > ~/backups/pre_rollover_cleanup_$(date +%Y%m%d_%H%M%S).sql.gz`
3. `php artisan loans:rollover-cleanup --dry-run` (chains 7, stray 447, never-sent 11 unless data moved)
4. `php artisan loans:rollover-cleanup --apply --confirm=<sha> --only=282,138,216,389,126,112`
5. `php artisan ledger:accrue && php artisan wallets:check && php artisan loans:check-integrity`

## Rollback

Restore `pre_rollover_cleanup_*`. Per row: `deleted_at = NULL` on the repayment/disbursement (audit log lists them).
