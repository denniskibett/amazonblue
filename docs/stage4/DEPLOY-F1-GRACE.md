# Feature 1: grace days instead of penalty write-offs; forbearance restored (21 October 2026)

## Why

v2 (Phase 2) charges penalty for every day late, from the first payment. Old-model loans paid in full a few days late
(loan 23: 6,000 due 8 Mar 2025, paid 11 Mar) were left "owing" penalty that was never collected, and
`loans:write-off-repaid` then booked a write-off for it: 44 `bad_debt` rows, KES 919,338.94, shown as repayments.

## The rule (owner, 21 Oct 2026)

- The latest cycle of a loan due **before 21 Oct 2026** gets as many grace days as it was paid late, **minus** the
  days of penalty that cash above principal + interest actually paid for (loan 165: 2 days late, extra 2,592 = one
  day's penalty, so 1 grace day). The due date is not changed.
- Then its "kept repaid" write-off is soft-deleted and the status is worked out again (repaid).
- What grace does not clear (the borrower paid less than principal + interest) is listed only: 6 loans
  (#7, 70, 205, 344, 502, 517), write-offs kept, owner decides.
- Forbearance as before Phase 2: MOLUK AWAD #209 (root of 210) until 5 Dec 2026; Shadrack Cheruiyot #430
  (root of 492) open-ended (31 Dec 2037), no rollover. Erick #513 rolls over as usual from his loan page.
- A remainder smaller than one day's penalty stays as overpayment credit (13 loans, KES 13,271.53).

Also in this release: due loans on the dashboard include overdue and forbearance; the loan page shows what the
loan's cash repayments actually shared out (Phase 3 journal) instead of the old brokers-table estimate; the staff
**Operations review** page (`/staff/operations-review`, tab "Late payers & forbearance") with Apply / Apply ticked;
`DebtRecoveryCase::getRemainingBalance()` (it was called but missing).

## Rehearsal on a copy of live (pulled 17:47, local)

| | |
|---|---|
| Grace | 38 loans; penalty removed 627,404.87; write-offs removed 614,133.34; penalty kept (paid by extra cash) 76,635.20 |
| Forbearance | 2 loans (#209 to 5 Dec 2026, #430 open-ended) |
| Owner decides | 6 loans, write-offs kept |
| After apply | 40 applied, 0 failed; trial balance 0.0000; all wallets reconcile; repaid_with_balance 0; `ledger:accrue` posts nothing |
| Split journal | ISIRO AGENCIES penalty share −900.00; House Account penalty share −98,985.33 (that cash now pays principal) |

## On live (after `scripts/push-live.sh`)

Fresh shell, `cd /home/loans.sharet.africa/laravel && /bin/pwd`, then one command at a time:

1. `mysqldump --single-transaction --routines --triggers loans_main | gzip > ~/backups/pre_grace_fix_$(date +%Y%m%d_%H%M%S).sql.gz`
2. `php artisan loans:grace-fix --dry-run`: the counts must match the rehearsal (38 / 2 / 6) unless payments
   came in since; read the CSV if they differ.
3. `php artisan loans:grace-fix --apply --confirm=<sha from step 2>`
4. `php artisan ledger:accrue && php artisan wallets:check && php artisan loans:check-integrity`
5. `php artisan loans:grace-fix --dry-run`: grace 0, forbearance 0, short_paid 6.

## Rollback

Per loan: restore the write-off row (`deleted_at = NULL`) and set the cycle's `grace_days` back to the value in the
audit log (`audit_logs.old_values`); the observers re-post the ledger and split. Whole run: restore `pre_grace_fix_*`.
