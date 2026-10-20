# Stage 4 Phase 2: cycle engine, what changed and how to deploy it (21 October 2026)

Branch `stage-2-ui`, after Phase 1 (`a08a43c`). There is one commit per item (2.1–2.11). **Nothing changes on any screen until `LENDING_ENGINE=v2` is set**, and that happens only after the owner signs the restatement CSV. Repayments and disbursements are never edited.

## What v2 does (owner decisions of 20 October 2026)

| Rule | v1 (today) | v2 |
|---|---|---|
| Penalty | 10% a day on what was owed at the due date, counted **to today** even after payment; a late payment only reduces the penalty | 10% a day, simple, on unpaid principal + interest, **only until paid**; each payment clears penalty, then interest, then principal |
| Cap | none (a 5,000 loan reached 114,000) | **in-duplum**: a cycle's penalty stops at the principal unpaid on the due date (`LENDING_PENALTY_CAP=none` turns it off; lawyer to confirm) |
| Rollover | preview ≠ booking; carries P + I, ignoring payments and penalty; starts on the due date | preview = booking; carries everything owed on the day; starts on the last penalty day; cycles 2–4 keep the previous rate; from cycle 5 staff set a rate or use a plan |
| Payment plan | 0% cycle; penalty dropped; waiver applied to all cycles | carries everything owed; waiver on the plan cycle only |
| `loans.amount` | principal + interest after a rollover | the latest cycle's principal; `original_amount` = cycle 1's |
| Cycle 1 | starts at application | starts when the money is sent (first cash disbursement) |
| Status | "repaid" when all repayments ≥ `loans.amount`; overdue from dates alone | from the latest cycle's balance: repaid only at 0 |
| Ledger | cash only | also each cycle's interest, penalty and carry difference, so a loan's receivable = what it owes |

**New and changed commands:**
- `loans:restate`;
- `ledger:accrue` (nightly at 01:15);
- `loans:check-integrity` (now reads v2);
- `loans:repair-cycles` (removed);
- `loans:fix-cycles` (refuses under v2).

## Rehearsal on the live mirror (21 October 2026, local)

| | |
|---|---|
| Loans compared | 514 |
| Owed today, v1 | **199,326,828.02**: penalty running to today on paid loans |
| Owed today, v2 | **10,166,737.83** |
| In-duplum cap removes | 58,070,504.66 on 89 loans |
| Penalty now stops at payment | 115 loans |
| Rollovers that carried a different amount | 15 loans, 562,490.03 net |
| **Repaid but still owes on v2** | **57 loans, 1,281,413.26**; 25 are legacy chains. They stay "repaid" unless named in `--reopen`. |
| `loans.amount` becomes current principal | 66 |
| `original_amount` filled from cycle 1 | 468 |
| Legacy ROLL OVER chains (flagged) | 311 |
| After `--apply` | trial balance 0.0000; status job changes nothing; `ledger:accrue` posts nothing twice |
| Ledger receivable ≠ owed | 350 loans, every one explained: 310 legacy chains (their ROLL OVER rows are not posted, decision 6), 20 with `non_cash` rows, 21 where cycle 1 lent a different amount from the cash sent |

The "repaid but owes" loans are real under the agreed rules, not a defect. Example: loan 23 paid exactly 6,000 on a 6,000 cycle, but late. The penalty for those days comes off first, so principal is left. The owner decides each one: reopen it, or record a discount or write-off.

## Before deploying (on the server, as root)

1. Back up:
   ```bash
   mysqldump --single-transaction --routines --triggers loans_main | gzip > ~/backups/pre_phase2.sql.gz
   ```
2. Run the SQL (safe to run twice):
   ```bash
   mysql loans_main < database/sql/2026_10_20_loan_cycle_engine_columns.sql
   ```
3. Deploy with `scripts/push-live.sh`. `LENDING_ENGINE` stays unset, which means v1.

## After deploying

Always start in a fresh shell in the live folder. The deploy moves the old folder out from under an open shell.

1. Move in:
   ```bash
   cd /home/loans.sharet.africa/laravel
   ```
   Then run `/bin/pwd`. It must print `/home/loans.sharet.africa/laravel`.
2. Dry run. It prints the summary and the SHA-256:
   ```bash
   php artisan loans:restate --dry-run
   ```
   Copy `storage/app/private/reports/restate-<date>.csv` to the owner.
3. **The owner signs the CSV.** Before signing:
   - read the summary and the `REPAID_BUT_OWES` rows;
   - choose any loans to reopen;
   - get the lawyer's view on the cap.
4. **The same day**, switch the engine and apply:
   1. Add the switch:
      ```bash
      echo 'LENDING_ENGINE=v2' >> /home/loans.sharet.africa/laravel/.env
      ```
   2. Clear the caches:
      ```bash
      php /home/loans.sharet.africa/laravel/artisan optimize:clear
      ```
   3. Apply with the signed SHA (add `--reopen=<ids>` if any loans should go back to owing):
      ```bash
      php /home/loans.sharet.africa/laravel/artisan loans:restate --apply --confirm=<sha>
      ```
   If it refuses because the data moved (a payment came in after signing), run `--dry-run` again and get the new CSV signed.
5. Check:
   - `php artisan wallets:check`: the trial balance must be 0.0000; receivable differences are reported, see above.
   - `php artisan loans:check-integrity`: `repaid_with_balance` must equal the `REPAID_BUT_OWES` loans not reopened.
   - `php artisan schedule:list` must show `ledger:accrue` at 01:15.

## Watch for

- **The nightly integrity email** lists the repaid-but-owing loans until the owner decides each one.
- **Borrowers whose balance changed.** The plan asks for a message to anyone whose balance changed by more than KES 100. SMS and email for this belong to Phase 6; until then, staff call the affected borrowers listed in the CSV.
- **Cycle 1 dating** only applies to disbursements made after the switch. Existing loans keep their dates.
- **Legacy chains** stay separate loans. Their ROLL OVER rows reduce balances as before, but they are not posted to the ledger.

## Rollback

- **Before `--apply`:** remove `LENDING_ENGINE` (or set it to `v1`), then run `optimize:clear`. Every figure returns to v1.
- **After `--apply`:** `--apply` rewrites `loans.amount`, `original_amount`, statuses and the cycle caches. v1 reads `loans.amount` for cycle 1, so switching back alone does **not** give the old figures. Either fix forward on v2 (recommended), or restore `~/backups/pre_phase2.sql.gz` (or the deploy's own backup) and re-enter any payments recorded since.
- **Ledger:** with the engine back on v1, `php artisan ledger:backfill` reverses every cycle charge it posted. Cash postings stay.
- **Code:** `deploy-live.sh --rollback`. The new columns are nullable, and the old code ignores them.
- Keep v1 selectable for 30 days after the switch (plan §2). Then delete `Loan::calculatePenalties` and the v1 branches.

## Done on live (21 October 2026, Nairobi time)

- **00:43:** loan_cycles columns added (backup `pre_phase2_columns_*`). Deployed `ff1c987` (engine still v1).
- **00:47:** disbursement #629 date corrected to 2026-10-12 (`database/sql/2026_10_21_fix_disbursement_629_date.sql`, audited).
- **00:54:** app on Nairobi time (`APP_TIMEZONE=Africa/Nairobi`). Deployed `fcefaf9`.
- **01:04:** disbursement #301: the owner set disburse_date to 2025-12-26 in CyberPanel; the payment date was set to match by `2026_10_21_fix_disbursement_301_date.sql`.
- **01:33:** after the nightly jobs:
  - backup `pre_phase2_apply_*`;
  - dry run SHA `9a96d7da…`;
  - `LENDING_ENGINE=v2`;
  - `loans:restate --apply`. No `--reopen`: the owner kept all 57 repaid-but-owing loans repaid.
  - Results: trial balance 0.0000, all wallets reconcile, the status job changes nothing.
- **01:37:**
  - `loans.merged_into_loan_id` added;
  - deployed `0b219fa` (real-cash dashboard cards, the chain merge tool);
  - `loans:merge-chains --dry-run`: 80 chains, 219 continuations, 16 not linked, SHA `0c558f44…`, identical to the rehearsal on a fresh copy.

## Next: merging legacy ROLL OVER chains (owner, 21 Oct 2026)

`loans:merge-chains --apply --confirm=<sha>`, after the owner signs `merge-chains-<date>.csv`. Rehearsal on a fresh copy of live:
- 514 loan rows become **295 loans**;
- trial balance 0.0000; all wallets reconcile;
- repaid-but-owing drops from 57 to **44**;
- loans whose ledger receivable differs from what they owe drop from 351 to **98**.

Re-run `loans:restate --dry-run` and `--apply` after the merge, so the merged loans get their statuses from their new cycles.
