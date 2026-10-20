# Stage 4 Phase 1: ledger foundation, what changed and how to deploy it (20 October 2026)

Branch `stage-2-ui`, on top of `aae729d`. There is one commit per item (1.1–1.7). **No figure on any screen changes.** The ledger is written only; nothing reads it for a balance yet.

**Do not deploy until the owner confirms.** Before the ledger is switched on, the owner signs the dry-run CSVs (plan §10).

## What changed

| Item | Change | Switch |
|---|---|---|
| 1.1 | `loan_ledger` dropped. It was empty and never written to. The migration and SQL refuse to drop it if it has rows. | — |
| 1.2 | `repayments.kind`, `repayments.created_by` and `repayments.reversal_of_id`; `disbursements.kind` and `disbursements.created_by`. A row stores what it is (cash, rollover, discount, bad_debt or non_cash) and who entered it. The old guess from the reference text (`CustomerWallets::kind`) is deprecated. It is still used for rows not yet backfilled, and for a new row whose form sends no kind. Every screen, report and wallet now reads the stored value. | — |
| 1.3 | The ledger is kept in **bavix wallets**; there are no new tables. Each account is a wallet with a `gl-` slug and 4 decimal places, held by the loan, the partner or the house partner (AC). Each movement is one bavix transfer, so every entry balances and all `gl-` wallets add up to 0. Each posting step gets a uuid derived from its source, so a retry or a second worker cannot post twice. | `LENDING_LEDGER_ENABLED` (off) |
| 1.4 | `ledger:backfill [--dry-run]` posts every existing row and writes a CSV, including every row it does not post and why. `wallets:check` now checks the trial balance and compares ledger cash with each borrower's wallet. | needs the switch on |
| 1.5 | Deleting a repayment entered before today posts a **reversal entry**: a new row for minus the amount, on the original's date, linked by `reversal_of_id`. The original is kept. A mistake made today is still deleted. | `LENDING_REVERSALS_ENABLED` (off) |
| 1.6 | The teller's end-of-day page reads `created_by`. Older rows fall back to the audit log. | — |

### Chart of accounts (config `lending.ledger.accounts`)

| Account | Held by | Type | Phase 1 posts |
|---|---|---|---|
| `gl-cash` | AC | asset | cash sent (disbursements), cash received (repayments), partner money in and out |
| `gl-receivable` | each loan | asset | disbursed (+), cash, discounts and bad debts received (−) |
| `gl-capital` | each partner | liability | contributions (+), principal withdrawals (−) |
| `gl-earnings` | each partner | liability | profit, broker fee and penalty shares (+), payouts from the interest or broker-fee wallet (−) |
| `gl-fee-income` | AC | income | processing fees (0 today) |
| `gl-partner-share` | AC | expense | shares paid to partners and brokers |
| `gl-discounts` | AC | expense | `discount` repayments |
| `gl-bad-debt` | AC | expense | `bad_debt` repayments |

Debit-side accounts (asset, expense) carry positive balances. Credit-side accounts (liability, income) carry negative ones.

**Not posted in Phase 1** (listed in the backfill CSV):
- rollover and `non_cash` book entries (they wait for Phase 2's engine and decision 6);
- partner journal types that move money between a partner's own buckets (Phase 3).

**The house account's own share is not posted** (added after the live dry run on 20 Oct). The 7 Oct backfill put 4,420,156.64 into the house account's "earnings": 462 `profit_distribution` rows and 4 `penalty_share` rows. That is AC's own share of interest, not a cost AC owes itself. It becomes income when Phase 3 splits repayments. Partner #5's 125,296.96 (broker fees and penalty share) is posted.

`gl-receivable` goes **negative** on most loans in Phase 1. That's expected: cash repaid includes interest and penalty, and Phase 1 doesn't book what was charged. Phase 2 books the charges.

### Local dry run (live mirror)

| | Rows | Note |
|---|---|---|
| `money:backfill-columns` | 800 repayments, 629 disbursements (live: 802 and 631, 0 differ) | **0** rows where the stored kind differs from the text rule; `created_by` filled 0 (the mirror has no audit create rows) |
| `ledger:backfill` | 1,926 read, 937 posted, 989 not posted | not posted: 482 rollovers, 26 `non_cash`, 15 `discount` disbursements, 466 house-account shares |
| Trial balance | 0.0000 | a second run posts 0 transfers |
| Ledger cash vs borrower wallet | 75 of 75 equal | after `wallets:sync` on the mirror, whose wallets were 28 postings behind |

## Before deploying (on the server, as root)

1. Back up:
   ```bash
   mysqldump --single-transaction --routines --triggers loans_main | gzip > ~/backups/pre_phase1.sql.gz
   ```
2. Check the two things the ledger needs. Both must return one row:
   ```bash
   mysql loans_main -e "SHOW COLUMNS FROM transfers LIKE 'extra'"
   mysql loans_main -e "SELECT id, name FROM partners WHERE is_house_account = 1 ORDER BY account_type = 'principal' DESC, id LIMIT 1"
   ```
3. Run the SQL scripts. Both are safe to run twice.
   ```bash
   mysql loans_main < database/sql/2026_10_20_drop_loan_ledger.sql
   mysql loans_main < database/sql/2026_10_20_money_row_columns.sql
   ```
   If the first one prints `loan_ledger has rows: NOT dropped`, stop and tell the owner. Nothing else depends on it.
4. Deploy with `scripts/push-live.sh`. Leave both switches **off** in `.env`.

## After deploying

1. Fill the new columns:
   ```bash
   php artisan money:backfill-columns --dry-run
   ```
   The "kind differs" count must be 0. Then run it without `--dry-run`.
2. Get a clean baseline:
   ```bash
   php artisan wallets:check
   ```
   - Borrower issues must be 0 before the ledger starts. If there are any, run `php artisan wallets:sync --dry-run`, then `php artisan wallets:sync`.
   - If the House #6 principal issue (4,420,156.64) is still there, run `wallets:fix-house` ([DEPLOY-2026-10-18.md](../audit/DEPLOY-2026-10-18.md) §4).
3. Dry-run the ledger:
   ```bash
   php artisan ledger:backfill --dry-run
   ```
   Send the CSV (`storage/app/private/reports/ledger-backfill-…-dry-run.csv`) to the owner. **Wait for sign-off.**
4. Switch the ledger on and backfill:
   - Set `LENDING_LEDGER_ENABLED=true` in `.env`, then run `php artisan optimize:clear`.
   - Run `php artisan ledger:backfill`.
   - Run it again: it must post 0 transfers.
5. Check:
   ```bash
   php artisan wallets:check
   ```
   The trial balance must be 0.0000, with 0 issues between the ledger and the borrower wallets.
6. Reversals, once staff have been told: set `LENDING_REVERSALS_ENABLED=true`, then run `php artisan optimize:clear`. From then on, "Reverse" on a payment entered before today keeps the original and adds a minus row.

## Watch for

- **Time.** The backfill took about 40 s on the mirror. Each posting reads the source's earlier postings through JSON in `transactions.meta`. That is fine at today's size; Phase 3 adds an index before volumes grow.
- **The receive-payment and disburse forms don't offer a kind yet.** Without one, the kind is read from the reference as before. The routes already accept `kind`, so the forms can add a picker without a backend change.
- **Negative rows.** A reversal shows as a minus row in payment lists, and that is intended.
- **MariaDB 10.3.** The SQL was tested twice on MySQL 9.4 (local), and the resulting schema matches `php artisan migrate` exactly. It uses the same guard pattern as the earlier twins that ran on live (an `information_schema` check, then a prepared `ALTER`). It has not been run on MariaDB 10.3 itself; a clone of live is the place to do that.

## Rollback

- **Ledger:** set `LENDING_LEDGER_ENABLED=false`, then run `php artisan optimize:clear`. Nothing reads the ledger, so no screen changes. To remove the postings as well, do it **only with the owner's OK and after a backup**:
  ```sql
  DELETE tr FROM transfers tr JOIN wallets w ON w.id = tr.to_id WHERE w.slug LIKE 'gl-%';
  DELETE t FROM transactions t JOIN wallets w ON w.id = t.wallet_id WHERE w.slug LIKE 'gl-%';
  DELETE FROM wallets WHERE slug LIKE 'gl-%';
  ```
  Ledger transfers only ever run between `gl-` wallets, so this touches no customer or partner wallet.
- **Reversals:** set `LENDING_REVERSALS_ENABLED=false`. Reversal rows already made stay. They are correct entries.
- **Code:** run `deploy-live.sh --rollback`. The old code ignores the new columns. `loan_ledger` stays dropped; nothing used it.

## Owner decisions recorded on 20 October 2026 (plan §9)

| Topic | Decision | Used in |
|---|---|---|
| Ledger storage | Keep it in bavix (wallets and transfers), not new tables. Use the existing tables for everything. | Phase 1 |
| Decision 3: broker rate | `brokers.interest_broker` is the single source. **AC funds the broker's client:** the broker gets `interest_broker` (40%). **The broker funds AC's client (tops up our loan):** the broker gets `100 − interest_broker` (60%) and AC keeps 40%. `brokers.interest_client` is no longer used for the split. | Phase 3 |
| Broker-funded loan defaults | AC guarantees the broker's **principal** only. The broker loses the interest share, and AC pays the principal back. | Phase 3 (write-off posting) |
| Fixed-return partners (e.g. 3% a month) | Charged on **committed** money, not only what is lent out. It is an AC cost that builds up daily. | Phase 3 |
| Decision 5: non-cash words | Deprecated. Rows store their kind; the old rows were converted by the backfill. | Phase 1 |
| Reversals | Soft delete for same-day mistakes only; anything older gets a reversal entry. | Phase 1 |
| M-Pesa recording | Automatic (C2B/STK callbacks) and manual entry both go through one path. The receipt number is unique, so a payment recorded both ways counts once. | Phase 4 |
| `loan_ledger` | Drop it. | Phase 1 |

**Still open for Phase 3:**
- whether the 3% fixed return is paid out monthly or added to the partner's balance;
- whether `investments.interest_rate` holds that monthly rate.
