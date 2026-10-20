# Stage 4 Phase 3: splitting repayments, what changed and how to switch it on (21 October 2026)

Deployed as `4136aee` with `LENDING_SPLIT_ENABLED` **off**: nothing is split until the owner signs the two CSVs below.

## The rules (owner, 20–21 October 2026)

Each **cash** repayment is first split by the cycle's waterfall into what it paid of penalty, interest and principal. Same-day rows apply in the order they were recorded. Then:

| Loan | Who earns what |
|---|---|
| Brokered, AC's money (the broker introduced the client) | broker: `loans.broker_rate` = **40%** of the interest paid, `loans.broker_penalty_rate` = **50%** of the penalty paid |
| Brokered, funded by the broker's own `loan_share` line (the broker tops up AC's loan) | broker: **100 − `brokers.interest_broker` = 60%** of the interest; the principal paid returns to the broker's line; AC guarantees that principal |
| Funded by a non-broker partner's `loan_share` line | partner: `partners.profit_share_rate` % of the interest; the principal returns to the line |
| `fixed_rate` line (e.g. 3% a month) | nothing per repayment; `partners:accrue-fixed` credits simple interest on the committed amount for each whole calendar month, from the 1st of the month after funding. A line that has left earns nothing for its last part month. |
| Not brokered, AC's money | nobody; AC keeps it (already its income in the ledger) |

Book entries (rollover, discount, write-off) earn nobody anything. A reversed or edited repayment moves the shares by exactly the change, as new journal rows.

## On live today (house money only)

- One funding line exists: General Lending Book (house). No partner line has been funded yet, so `loan_share` and `fixed_rate` are built and tested but have no live data.
- Brokers: **ISIRO AGENCIES** (#5, 23 loans) and **Obiri Victor** (#8, 1 loan). Obiri has no `brokers` row; the owner confirmed his 50% penalty share (21 Oct 2026).
- A non-broker partner funding a `loan_share` line earns `partners.profit_share_rate` % of the interest (owner confirmed, 21 Oct 2026).

## The two sign-offs

1. **`partners:link`** (live dry run, SHA `6d00a7dc…`, `partners-link-2026-10-21.csv`):
   - 25 brokered loans record the broker's penalty share;
   - loan #19 takes ISIRO as broker, from its merged continuation #21 (same borrower);
   - 360 cash disbursements with no funding line are linked to the house line.
2. **`allocations:restate`** (rehearsed on a copy of live, `allocations-restate-2026-10-21-rehearsal.csv`):

   | Partner | Share | Earned now (7 Oct backfill) | Restated | Difference |
   |---|---|---|---|---|
   | ISIRO AGENCIES | interest | 79,736.96 | 76,616.96 | −3,120.00 |
   | ISIRO AGENCIES | penalty | 45,560.00 | 118,107.92 | +72,547.92 |
   | Obiri Victor | interest | 0.00 | 1,600.00 | +1,600.00 |

   ISIRO's penalty share rises because a late payment now clears penalty first, so more of what was collected is penalty, and the broker takes half. The backfill rows stay in the journal; one reversal per partner and type replaces them, and 45 per-repayment rows are added. The house account is not touched.

## Switching it on (after both are signed, the same day)

Run each command separately; check the output before the next.

1. Back up:
   ```bash
   mysqldump --single-transaction --routines --triggers loans_main | gzip > ~/backups/pre_phase3_$(date +%Y%m%d_%H%M%S).sql.gz
   ```
2. Apply the links with the signed SHA:
   ```bash
   php /home/loans.sharet.africa/laravel/artisan partners:link --apply --confirm=<sha>
   ```
3. Add the split switch to the live `.env`:
   ```bash
   echo 'LENDING_SPLIT_ENABLED=true' >> /home/loans.sharet.africa/laravel/.env
   ```
4. Clear the caches:
   ```bash
   php /home/loans.sharet.africa/laravel/artisan optimize:clear
   ```
5. Dry-run the restatement and compare its table with the one above:
   ```bash
   php /home/loans.sharet.africa/laravel/artisan allocations:restate --dry-run
   ```
6. Apply with the SHA that step 5 printed:
   ```bash
   php /home/loans.sharet.africa/laravel/artisan allocations:restate --apply --confirm=<sha>
   ```
7. Check: partner wallets must equal the journal, and the trial balance must be 0.0000:
   ```bash
   php /home/loans.sharet.africa/laravel/artisan wallets:check
   ```

## Rollback

- **Before step 6:** remove `LENDING_SPLIT_ENABLED`, then run `optimize:clear`.
- **After step 6:** the journal is append-only. Either restore `pre_phase3_*`, or post reversing rows for the `SPLIT-R%` and `RESTATE-LEGACY-%` references.

## Done on live (21 October 2026, 02:16 Nairobi)

- The owner asked that AC's share be recorded on the house account too, so that all parties add up to what was collected (`f0f00f1`).
- Steps:
  - backup `pre_phase3_*`;
  - `partners:link` applied (SHA `6d00a7dc…`, 388 changes);
  - `LENDING_SPLIT_ENABLED=true`;
  - `allocations:restate` applied (SHA `bcae6630…`, identical to the rehearsal on a fresh copy).
- Results: 492 split rows from 368 repayments. **Interest + penalty collected in cash: KES 3,329,122.52, all of it shared out** (difference 0.00).

  | Party | Interest | Penalty |
  |---|---|---|
  | ISIRO AGENCIES (#5) | 76,616.96 | 118,107.92 |
  | Obiri Victor (#8) | 1,600.00 | 0.00 |
  | House Account (#6), AC | 2,065,501.03 | 1,067,296.61 |

- The 7 Oct backfill rows stay in the journal, each reversed by one `RESTATE-LEGACY-…` row.
- Partner wallets equal the journal; the ledger trial balance is 0.0000; all wallets reconcile.
- From now on, every repayment recorded, edited or reversed is split as it is saved.
