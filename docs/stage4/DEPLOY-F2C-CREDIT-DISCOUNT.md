# Feature 2c: CREDIT DISCOUNT was never money (21 October 2026)

Owner: CREDIT DISCOUNT rows are removed; grace days are used instead; where the cash does not cover principal +
interest the cycle's rate is lowered to fit; where it does not cover the principal, the unpaid principal is written off.
CREDIT DISCOUNT used to carry a balance from one loan to the next is a rollover.

## `loans:credit-cleanup` (App\Services\Loans\CreditCleanup)

- **Rollover** (18 rows, 400,540.00): a CREDIT DISCOUNT repayment and a CREDIT DISCOUNT disbursement of the same
  borrower for the same amount within 15 days; or a CREDIT DISCOUNT repayment matching the next loan's ROLL OVER
  disbursement (soft-deleted ones count) or a first cycle with no disbursement; or a CREDIT DISCOUNT disbursement alone.
  Both rows become ROLL OVER rows, so `loans:rollover-cleanup` merges or transfers them.
- **Forgiveness** (70 cycles, 1,872,129.50 removed): the row goes; then the fewest grace days that close the cycle on
  the cash paid (extra cash still pays its days of penalty); then, if still short, a lower interest rate (2 decimals,
  rounded down); if short of the principal itself: rate 0% and exactly the unpaid principal written off
  (`WRITE OFF: cash short of principal (was CREDIT DISCOUNT)`). 38 close by grace, 12 by a lower rate, 20 by a write-off
  (254,499.50). A carried cycle "closes" when it owed exactly what the next cycle took over.

## `loans:rollover-cleanup` (changed)

- A loan that got new cash when it opened (a top-up) is not merged; the consolidation transfer handles it (merging
  would book that cash twice).
- Transfers are worked out after the merges; a merged pair is never also a transfer.

## Rehearsal on a copy of live (pulled 20:34)

credit-cleanup: 88 rows applied. rollover-cleanup: 9 chains (12 loans), 2 transfers (44,660). Trial balance 0.0000; all
wallets reconcile; repaid_with_balance 0. Mismatches 74 → 58: 16 fixed to the cent, none new, none worse.
Owner: merge 8 chains; #30→#40 (dates only, +6,960) is left for 2b.

## On live (fresh shell)

1. `mysqldump --single-transaction --routines --triggers loans_main | gzip > ~/backups/pre_credit_cleanup_$(date +%Y%m%d_%H%M%S).sql.gz`
2. `php artisan loans:credit-cleanup --dry-run` → `--apply --confirm=<sha>`
3. `php artisan loans:rollover-cleanup --dry-run` → `--apply --confirm=<sha> --only=6,9,37,38,45,54,60,126`
4. `php artisan ledger:accrue && php artisan wallets:check && php artisan loans:check-integrity`

Rollback: restore `pre_credit_cleanup_*`.
