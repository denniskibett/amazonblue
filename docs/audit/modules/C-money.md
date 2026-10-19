# Audit C: money modules (disbursements, repayments, Bavix wallets, partners, brokers, investments, AC house accounts)

Stage 1, audit only. Evidence comes from code (file:line) and read-only SELECTs on local `loans_main` (taken 2026-10-17). `wallets:check` and `wallets:sync --dry-run` were run read-only. Labels: **Verified working** · **Implemented, not verified** · **Partial** · **Planned only** · **Broken** · **Unknown (not enough evidence)**.

Versions: bavix/laravel-wallet **11.5.3** (composer.lock:11). spatie/laravel-permission **8.3.0**.

---

## 0. Rule verdicts

| Rule | Current behaviour | Required | Evidence | Label |
|---|---|---|---|---|
| **1** AC owns company-funded loans; principal goes to a partner only through a partner-linked investment | `loans.partner_id` holds the **broker** (partner 5 ISIRO = broker user 3; partner 8 Obiri), not a funder. No loan principal is booked to any partner. Every partner `principal` wallet is 0 **except House #6, which holds 4,420,156.64. That money is interest, put into the principal wallet by mistake (see B1)**. AC exists only as Partner #6 "House Account" (`is_house_account=1, account_type=principal`). | AC holder plus a funding link per disbursement | loans: `broker_status=1` gives 29 loans, all with partner_id set (5: 28, 8: 1). `broker_status=0` gives 507 loans, all with partner_id NULL | **Partial**: the rule holds by accident, because principal is never attributed to anyone. The AC principal wallet is wrong. |
| **2** investment→partner; disbursement→investment; partial and multiple draws; block over-allocation | The `disbursements.investment_id` FK exists (`disbursements_ibfk_2`). Only **10 of 629** disbursements are linked, all to Investment #1 "General Lending Book" (auto-created house fallback, DisbursementController.php:86-107). 619 legacy rows are unlinked. The link to a partner goes through `investment_fundings` (1 row: inv 1 → partner 6, committed 0). Multiple draws per investment are allowed. **There is no over-allocation check.** With committed=0 the funding row becomes `settled` after the first draw (Investment.php:392), and `fundings()->active()` then skips the later 9 draws. `amount_disbursed` = 20,000 while actual draws total 186,000. | as stated | SQL counts above | **Partial**. Over-allocation control is **Broken**. |
| **3** each repayment split into principal, interest, penalty, broker, partner and AC, with all six recorded on every repayment (0 where nothing applies) and tracked in Bavix | Repayments store only `amount, processing_fee, net_amount` (repayments table). No allocation happens at create time (RepaymentController.php:63-113, which bypasses the injected RepaymentService). The borrower Bavix wallet gets one gross cash deposit. Partner and house splits exist only as a **one-off backfill** (BackfillBrokerFees / BackfillHouseInterest, 497 journal rows dated 2026-10-07, `repayment_id` NULL on all, per-loan rather than per-repayment). 6 repayments (83,760) recorded since then have no split. `loan_ledger` (entry_type interest/penalty/fee…) has 0 rows and nothing writes to it. `transfers` = 0. | as stated | | **Planned only**: no per-repayment split exists. |
| **4** restate history (principal back to funding investment or AC; broker fees only where broker_status=1 at that broker's rates) | Data that exists: `loans.broker_status`, `partner_id`, `broker_rate` (defaults to 40 on **all** 536 loans, including non-brokered), `capitalized_interest`, `repayments.loan_cycle_id` (800/800), loan_cycles, `brokers` (1 row: interest_client 20, interest_broker 40, penalty 50/50), `borrowers.broker_id/client_type` (mixed values '0','1','individual'). Brokered loans: 29. Loans with broker fees booked: **27** (partner 5, repaid). Not booked: 1 disbursed loan for partner 5 and 1 for partner 8. **22 repayments with text "BROKER FEE(S)" (74,300.80) fall on 22 loans, and only 2 of those loans have broker_status=1.** The rates disagree: the backfill uses `loans.broker_rate` (40%) and a hard-coded 50% penalty (BackfillBrokerFees.php), while the loan screen uses the brokers table by client_type (20% or 40%) (LoanController.php:609-625). 7 brokered loans have no `borrowers.broker_id`. | as stated | | **Partial**: the inputs exist, but the rate source is ambiguous and there is no investment link on 619 disbursements (all would default to AC). |
| **9** wallet handles `@firstlast`, numbered on collision | Nothing exists: no column, no code (only `social_usernames` in User.php:431). Collision check on first + last word of `users.name`: 85 wallet holders give 84 distinct handles, with **1 collision ("ianokoth" ×2)**. Across all 90 active users the same single collision. 7 users have one-word names. 4 partners (company names) need their own rule. | | | **Planned only** |

---

## 1. Disbursements

1. **Purpose:** records cash sent to a borrower (or, in theory, investment-only draws). It computes the processing fee and net amount, links the house investment by default, and moves an approved loan to `disbursed`.
2. **Files:** `routes/web.php:215` (resource, any-of `view|create|edit|delete disbursements`). `app/Http/Controllers/DisbursementController.php` (store 46-191, update 193-222, destroy 225-239). `app/Services/DisbursementService.php` (fee and cycle). `app/Models/Disbursement.php` (no SoftDeletes). Migration `2025_01_01_000034_create_disbursements_table.php`. Views `resources/views/disbursements/`.
3. **Tables:** `disbursements` (loan_id NOT NULL FK, loan_cycle_id, amount, processing_fee, net_amount, transaction, mode, partner_transaction_id FK, funding_source enum internal/partner/mixed, investment_id FK, deleted_at column but no trait). Data: 629 rows. funding_source internal 537, NULL 82, partner 10. net_amount>0 on 92. processing_fee>0 on 0. partner_transaction_id set on 0.
4. **Rules / formulas:**
   - fee = `processing_fee_rate/100 × amount`
   - net = amount − fee (DisbursementService.php:24-26)
   - cash sent = `COALESCE(NULLIF(net_amount,0), amount)` (CustomerWallets.php:43)
   - funding_source is inferred: partner_transaction_id → partner; investment with funding_partners → partner; else internal.
   - internal with no investment → "General Lending Book" (firstOrCreate by name) plus an auto house funding row.
5. **Permissions:** the route allows any one of the four permissions. **The controller has no per-action check**, so auditor, broker and loan-officer roles, which hold only `view disbursements`, can POST, PUT and DELETE. **Broken (security)**, DisbursementController.php:46,193,225. The `disburse loans` permission is never checked anywhere.
6. **Flow:** store → DisbursementService::createDisbursement → model `saved` → afterCommit CustomerWallets::syncDisbursement (borrower wallet withdraw) → Investment::recordFundingDisbursement.
7. **Depends on:** Loan, LoanCycle, LoanCalculator, Investment, InvestmentFunding, Partner, CustomerWallets (AppServiceProvider.php:50-53).
8. **Bugs / debt:**
   - The investment-only path (DisbursementController.php:132-157, Investment::fundDisbursement Investment.php:419) inserts `loan_id = null` into a NOT NULL column. **Broken**
   - update and destroy never adjust `investment_fundings.amount_disbursed`. destroy hard-deletes; the wallet reverses through the `deleted` event. **Partial**
   - Investment funding is tracked only for the first draw (see Rule 2). **Broken**
   - A future-dated row exists: disbursement #629 has disburse_date 2026-12-10 against created 2026-10-12 (a data-entry slip). **Unknown**
   - The default transaction text is `'DISBURSEMENT'`, which kind() treats as cash. OK.
9. **Risks / recs:** add per-action `can()` checks. Make `investment_id` mandatory, with AC house investment as the default. Over-allocation guard: `sum(draws) ≤ amount_committed`.

## 2. Repayments (allocation only)

1. **Purpose:** records money received (or non-cash movements typed as text) against a loan cycle, then sets cycle and loan status.
2. **Files:** routes `web.php:128-134, 219`. `RepaymentController.php` (store 63-113, update 115-138, destroy 173-224). `RepaymentService.php` is used only by the injection; store bypasses it. `Repayment.php` (SoftDeletes; `recordBavixTransaction()` writes a non-existent `bavix_transaction_id` column at Repayment.php:88, dead code). Migration `..._000057_create_repayments_table.php`.
3. **Tables:** `repayments` (800 rows; loan_cycle_id set on all; investment_id and partner_transaction_id set on 0; deleted 0).
4. **Rules:**
   - Store marks the cycle completed when `new_balance − Σcycle repayments ≤ 0`.
   - Store marks the loan **repaid when there is no active cycle OR Σrepayments ≥ loan.amount (principal only)** (RepaymentController.php:97-104). This counts ROLL OVER, DISCOUNT and BAD DEBT rows as repayment. **Broken**, unless penalty and rollover maths elsewhere override it; the other auditor should confirm.
   - RepaymentService::checkLoanRepaymentStatus has the same principal-only logic.
   - Repayment processing fee = rate × amount (RepaymentController.php:129). Charging a processing fee on a repayment is odd. **Unknown**, owner decision.
5. **Permissions:** per-action `can()` checks are present (create/edit/delete repayments). **Implemented, not verified**
6. **Allocation:** none (see Rule 3). **Planned only**
8. **Bugs:**
   - destroy sets `Loan::STATUS_ACTIVE = 'active'` (RepaymentController.php:212). `'active'` is **not in the loans.status enum**, so the write fails or truncates. **Broken**
   - Update validates `transaction` as unique per loan, but store does not. This is inconsistent. **Partial**

## 3. Bavix Wallet

| holder_type | slug | decimal_places | wallets | Σ balance |
|---|---|---|---|---|
| Partner | principal | 4 | 4 | 4,420,156.64 (all on House #6) |
| Partner | interest | 4 | 4 | 4,420,156.64 (all on House #6) |
| Partner | broker_fee | 4 | 4 | 125,296.96 (all on partner 5) |
| Partner | investment_tracker | 4 | 4 | 0 |
| User | borrower_loan | 2 | 85 | 1,199,440.80 |

`transactions` = 1,337. `transfers` = 0. Bavix `Transfer` / `pay` / `CanPay` are never used.

**Posting paths**

| Path | Wallet | meta | Label |
|---|---|---|---|
| Disbursement/Repayment saved/deleted/restored → `DB::afterCommit` → CustomerWallets::syncSource (AppServiceProvider.php:38-54) | User borrower_loan; withdraw for cash out, deposit for cash in | `source, source_id, loan_id, reference, date, kind (posting/adjustment/reversal)` | **Verified working** (373 disbursement and 465 repayment postings; 75 of 76 borrowers reconcile) |
| PartnerLedger::post (PartnerLedger.php:22-91), called only by BackfillBrokerFees and BackfillHouseInterest | Partner slug | `reference, description` (**no source/source_id**) | **Partial**: one-off only, with no live caller |
| CustomerWallets::repairPartnerWallet (`wallets:check --repair-partners`) | Partner slug | `source=reconcile` | ran 2026-10-15 (2 rows) |
| WalletsReconcileFromJournal `--apply` | Partner slug | description only | **Partial**: it skips the reset when balance<0 and never deposits a negative target |
| Partner::addContribution / withdrawPartnerBalance / recordRepayment / distributeProfit (Partner.php:170-238) | **none**. Uses `$this->transactions()`, which is **Bavix's** `Transaction` relation (confirmed via tinker), not partner_transactions | n/a | **Broken**: inserts an invalid type into the Bavix `transactions` table, so it errors |

**Idempotency:**
- Borrower: the net posted per `(source, source_id)` is compared to target under `lockForUpdate`, so the sync can run any number of times. **Verified working** (dry-run proposes only the 2 known corrections).
- Partner: unique `(reference, wallet_slug)` (`pt_reference_wallet_unique`) plus a lookup in PartnerLedger. **Implemented, not verified**

**Reversals:**
- Borrower: deleting a source posts the opposite amount (`kind=reversal`). Disbursement has no SoftDeletes, so a hard delete arrives as `!exists`. **Implemented, not verified** (0 deleted rows in data).
- Partner: no reversal path. **Planned only**

**Reconciliation (`php artisan wallets:check`, read-only):**
- Borrowers: 75 checked. **1 issue: user #2 expected 346,320.00, wallet 276,320.00.** The cause is disbursements #20 and #21 "CARRY FORWARD" (70,000), which were posted as cash before CARRY FORWARD joined `NON_CASH`. `wallets:sync --dry-run` would post 2 reversals for 70,000. Wallet integrity (balance vs log) shows no issues.
- Partners: "2 partner wallet(s) with journal entries checked", 0 issues. **This is misleading.** The check iterates journal rows only, so the House `interest` wallet (4,420,156.64 in the wallet, **0 journal rows**) is never compared.

**Bugs:**
- **B1 (Broken): House #6 is double-counted.**
  - The backfill posted 466 entries to the House **interest** wallet. Because `wallet_slug` was then missing from `PartnerTransaction::$fillable` (PartnerTransaction.php:14-17 comment), the journal rows saved as `principal`.
  - `--repair-partners` (2026-10-15) then deposited 4,420,156.64 into the House **principal** wallet to "match" the journal.
  - Result: AC principal shows 4.42 M of phantom capital, the interest wallet has no journal behind it, and journal rows 462 profit_distribution + 4 penalty_share on partner 6 carry the wrong slug.
  - Partner 5 broker_fee also needed a +116,519.99 repair deposit. Its original postings summed to 8,776.97 against a journal of 125,296.96. **Cause Unknown**.
- WalletsBootstrap creates partner wallets with Bavix default decimals (2) where everything else uses 4. It also filters `users.role` instead of Spatie roles (WalletsBootstrap.php). **Partial**
- `PartnerTransaction` casts amount and balance_after as `decimal:2` while the column is 4 dp. **Partial**
- CustomerWallets::kind treats unrecognised legacy text as cash:

| text in `transaction` | repayments (n / KES) | disbursements (n / KES) | kind() | correct? |
|---|---|---|---|---|
| ROLL OVER (marker ROLL) | 239 / 15,555,827.50 | 241 / 14,353,842.65 | rollover | yes |
| CARRY FORWARD | 0 | 2 / 70,000 | rollover | yes (wallet not yet re-synced) |
| CREDIT DISCOUNT | 83 / 2,217,149.50 | 15 / 338,540 | discount | yes |
| BAD DEBT | 13 / 999,145.25 | 0 | bad_debt | yes |
| BROKER FEE(S) | 22 / 74,300.80 | 0 | **cash** | **Unknown**: is it the borrower paying a fee, or an internal split row? 20 of 22 are on non-brokered loans |
| TO BE PAID | 2 / 80,000 | 0 | **cash** | **likely wrong**: the wording says not yet received |
| SPLIT | 1 / 1,920 | 1 / 1,600 | **cash** | **Unknown** |

**Gaps vs Rule 3:**
- No principal, interest or penalty wallets for loans or AC.
- No AC house *interest* holder separate from the principal holder (HouseAccountsSeeder expects 2 house partners, but live has 1).
- No transfers between borrower → AC → partner/broker.
- No per-repayment postings to the partner or broker side.
- Partner meta lacks `source/source_id`, so a re-run keyed on repayment id is impossible.

## 4. Partners

1. **Purpose:** external capital providers, brokers (they share the Partner model) and the house account. Includes the journal `partner_transactions` and the customer statement (PartnerPortfolio).
2. **Files:** routes `web.php:315-357` (`permission:view/create/edit/delete partners`; contribution, withdraw and profit need `edit partners`). `PartnerController.php`. `Partner.php` (HasWallets + CanPay). `PartnerLedger.php`. `PartnerPortfolio.php` (read-only). HomeController.php:98,187 (partner account). Migrations `..._000026_create_partners_table`, `..._000033_create_partner_transactions_table`.
3. **Tables:**
   - `partners`: 4 rows. 5 ISIRO (user 3), 6 House Account, 7 Imagine-Nation, 8 Obiri Victor. `broker_rate` = 40 on all. `current_balance` = 0 on all.
   - `partner_transactions`: 497 rows. All have loan_id. 0 have repayment_id or investment_id. Unique (reference, wallet_slug).
4. **Rules:** portfolio "earned" = Σ journal types profit_distribution, broker_fee_earned, penalty_share and interest (PartnerPortfolio.php:47-48). **Implemented, not verified**
5. **Permissions:** route-level only. **Implemented, not verified**
8. **Bugs:**
   - `PartnerController@withdraw` calls `$partner->withdraw($amount, $reference, $notes)` (PartnerController.php:330). That resolves to **Bavix HasWallet::withdraw** with a string as `meta`, which throws a TypeError or InsufficientFunds. **Broken**
   - addContribution and distributeProfit write to the Bavix `transactions` relation (see the posting table). **Broken**
   - `partners.current_balance` and `total_*` are legacy shadows of the wallets. **Partial**

## 5. Brokers

- **Data:**
  - `brokers` has 1 row (user 3, interest_client 20, interest_broker 40, penalty 50/50).
  - Brokers also exist as Partners (5 linked to user 3; partner 8 has no user and no brokers row).
  - Loans: `broker_status` and `partner_id` (the broker). `broker_rate` defaults to 40.
  - The `broker_performance_report` view exists.
- **Assignment:** broker-created loans → broker_status=1 (LoanController.php:162-163). Admin and teller can set it freely (LoanController.php:190).
- **Fee formulas:**
  - Backfill: `capitalized_interest × broker_rate%` plus 50% of last-cycle penalty collected (BackfillBrokerFees.php).
  - Loan screen: brokers-table rate by `borrowers.client_type` (LoanController.php:609-625).
  - **The two sources conflict. Partial**
- **Booked:** 27 fee entries (79,736.96) and 4 penalty shares (45,560) to partner 5. None for partner 8.
- **Permissions** `set broker rate`, `settle broker fees` and `view broker fees` exist but are **never checked**. **Planned only**

## 6. Investments

- **Files:** routes `web.php:270-313`. `InvestmentController.php` (store funding rows 255-312, update 517-556). `Investment.php` (addPartnerFunding 331, recordFundingDisbursement 378, recordFundingReturn 403, fundDisbursement 419, recordRepayment 446). `InvestmentFunding.php`. Migrations `..._000022`, `..._000035`.
- **Data:**
  - 1 investment ("General Lending Book", initial 0, auto-created 2026-10-08).
  - 1 funding (partner 6, committed 0, disbursed 20,000, settled).
  - `funding_partners` JSON and `total_funding_raised` are legacy duplicates.
- **Bugs:**
  - Store and update with partner funding call `Partner::addContribution`, which fails (Bavix relation). **Broken**
  - `fundDisbursement` and `recordRepayment` insert loan_id NULL. **Broken**
  - The auto-settle when committed=0 stops tracking. **Broken**
  - The routes addFunding, addMilestone and addNote are missing (TREE.md). **Broken**
- **Permissions:** `create/settle investment funding` are never checked. **Planned only**

## 7. AC house accounts

- **Live/local:** a single Partner #6 "House Account" (`is_house_account=1, account_type=principal`) holding all four slugs.
- **HouseAccountsSeeder:** expects separate House Principal and House Interest partners. The seeder reuses #6 for principal and would create a new Interest partner. Today, house interest sits in #6's `interest` slug. **Partial**
- **Accessors:** `is_house_principal` and `is_house_interest` (Partner.php:266-276).
- **AC balances:**
  - principal 4,420,156.64: **wrong** (B1, interest double-counted).
  - interest 4,420,156.64: no journal behind it.
  - No AC cash or operating wallet.
- **Coverage:** non-brokered penalty income was never booked to the house ("per your scope" comment, BackfillHouseInterest.php Pass C). 462 of 464 repaid loans with interest are covered.

---

## Risks and recommendations (migration risk in brackets)

1. Fix B1 before any restatement:
   - correct the slug on the 466 house journal rows (principal → interest);
   - reverse the 2026-10-15 reconcile deposit on the House principal wallet;
   - extend `checkPartners` to every wallet, not just the ones with journal rows.

   [Medium: live data edit. Ship as an idempotent SQL script per memory rule.]
2. Re-run `wallets:sync` (non-dry) for user #2 after the owner confirms CARRY FORWARD is non-cash. [Low]
3. Add per-action permission checks in DisbursementController. [Low]
4. Introduce an allocation table (repayment_id × account {principal, interest, penalty, broker, partner, ac}) and post each leg to Bavix with `meta.source=repayment_alloc, source_id`. Repurpose or drop the unused `loan_ledger` table. [High: new schema, backfill over 800 repayments]
5. Make `disbursements.investment_id` NOT NULL after backfilling the 619 legacy rows to the AC house investment, and enforce `Σ draws ≤ committed`. [Medium]
6. Replace Partner::addContribution and similar methods with PartnerLedger::post, rename away from `transactions()`, and add `source/source_id` meta. [Medium]
7. Choose a single broker-rate source. [High for restatement: changes historical fees]

## Owner decisions needed

1. Broker rate source for restatement: `loans.broker_rate` (40% on every loan), `brokers.interest_client/interest_broker` by client_type (20/40), or `partners.broker_rate`?
2. Penalty split: 50% broker / 50% AC on brokered loans, and 100% AC otherwise? (Currently non-brokered penalty is never booked to AC.)
3. What are "BROKER FEE(S)" repayments (22, mostly on non-brokered loans), "TO BE PAID" (2, 80,000) and "SPLIT" (2)? Cash or non-cash, and to which account?
4. Is "partner" in Rule 3 the funding investor (via investment) only, with brokers always being the "broker" account? Should brokers remain Partner rows?
5. AC structure: one house holder with slugs, or separate principal and interest holders as the seeder expects?
6. Repayment processing fee: keep or drop?
7. Handle format for one-word names, companies/partners, and collisions (e.g. `@ianokoth`, `@ianokoth2`): ordering by user id?

## Fixes applied (2026-10-18)

Tested on a clone of `loans_main` (dropped afterwards). Loan calculation, repayment allocation, the six-account split (rule 3) and history restatement (rule 4) were **not** touched.

**What changed**

| Finding | Fix | Files |
|---|---|---|
| B1 House #6 double count / check blind spot | `checkPartners` now compares **every** partner wallet (16) with its journal; a wallet with no journal must hold 0, a journal group with no wallet is reported missing. It also reports journal rows whose wallet posting (same reference) went to another wallet (`slug_mismatches`). New `wallets:fix-house {--partner=} {--dry-run}` posts one compensating entry per wallet that differs from its journal (`forceWithdraw`/`deposit`, meta `{source: correction, reason, reference: CORR-P{id}-{SLUG}-{ts}, journal_total}`; the reason names the reconcile transaction it reverses). Nothing deleted or edited. | app/Services/CustomerWallets.php, app/Console/Commands/WalletsFixHouse.php, app/Console/Commands/WalletsCheck.php |
| `--repair-partners` unsafe | Refuses (exit 1) while any journal row points at the wrong wallet, and uses the same compensating-entry method (`correctPartnerWallet`, meta `source=correction`). `repairPartnerWallet` (`source=reconcile`) is gone. | WalletsCheck.php, CustomerWallets.php |
| Partner::addContribution / distributeProfit / recordRepayment / withdrawPartnerBalance wrote to Bavix `transactions()` | All go through `PartnerLedger::post` (journal row + wallet posting, atomic, idempotent on reference+slug): contribution and repayment → `principal`, profit → `interest`, withdrawal → `-amount` from `principal` under a row lock, refused with "Insufficient balance: the principal wallet holds KES X" when short. `current_balance` (legacy) is no longer written. `total_invested/total_returned/available_balance` read the journal / wallet. PartnerLedger wallet meta now carries `source=partner_journal, type`; journal rows get `created_by`. `PartnerTransaction` casts now `decimal:4`. | app/Models/Partner.php, app/Services/PartnerLedger.php, app/Models/PartnerTransaction.php |
| PartnerController@withdraw called Bavix `withdraw()` | Calls `withdrawPartnerBalance` (400 with the message when short). Contribution/profit amounts cast to float. | app/Http/Controllers/PartnerController.php |
| Investment store/update partner funding failed | `Investment::addPartnerFunding(partnerId, amount, ?reference, ?notes)`: funding row, then the partner's contribution journal row (linked `investment_id` + `investment_funding_id`, reference `INV-{inv}-F{funding}`) and principal wallet deposit, then `partner_transaction_id` and the legacy `total_funding_raised` / `funding_partners` are synced. Store and update call it. | app/Models/Investment.php, app/Http/Controllers/InvestmentController.php |
| Missing routes addFunding / addMilestone / addNote | Implemented (JSON). Funding = `addPartnerFunding`; milestones/notes use the JSON columns; a note with `category=update` also goes to `updates`. | InvestmentController.php |
| loan_id NULL disbursements/repayments | DisbursementController@store: `loan_id` required, investment-only requests get 422 "Choose the loan this disbursement is for…" (the investment-only branch is removed). `Investment::fundDisbursement` / `recordRepayment` take a `$loanId` (last param) and throw a clear error without one. | DisbursementController.php, Investment.php |
| Over-allocation / auto-settle at committed=0 | `assertCanDisburse` refuses a draw above the remaining committed amount (422 before anything is written; disbursement + funding draw now run in one DB transaction). `recordFundingDisbursement` uses a row with room, else spreads over committed rows; only rows with committed>0 auto-settle. **House lines are not capped** (investment funded only by house partners, or not linked yet): AC lends its own money, so draws go to an open house funding row (committed 0) that never settles. `investments:fix-house-funding {--dry-run}` corrects house rows auto-linked with committed 0: funding #1 committed 0→186,000 and drawn 20,000→186,000 (the 10 disbursements on investment #1), stays settled, dated correction note appended. | Investment.php, DisbursementController.php, app/Console/Commands/InvestmentsFixHouseFunding.php |
| Rule 9 handles | `users.handle` and `partners.handle` (varchar 64, nullable, unique). People: first+last word of the name, lowercase letters/digits (Dennis Kibet → denniskibet); one word → that word; companies (partner type corporate/business/company) → whole name compacted; collisions get 2, 3… by id order; unique across users and partners, except a partner linked to a user shares that user's handle. Set on save when empty (skipped if the column isn't there yet), backfilled by `handles:backfill {--dry-run}`. Shown as @handle on the customer profile and the staff user page header. | app/Support/Handle.php, app/Models/User.php, Partner.php, app/Console/Commands/HandlesBackfill.php, database/migrations/2026_10_18_000041_add_handle_to_users_table.php, 2026_10_18_000042_add_handle_to_partners_table.php, database/sql/2026_10_18_wallet_handles.sql, resources/views/home/account/profile.blade.php, resources/views/users/show.blade.php |
| `kind()`: TO BE PAID treated as cash | **Unchanged** (owner decision 3). | — |

**Clone results**
- Before (journal fixed by the 2026-10-16 SQL, which the mirror did not have yet: 466 rows, 4,420,156.64): old check "2 partner wallets checked, 0 issues"; new check "16 checked", `partner #6 principal: journal 0.0000, wallet 4,420,156.6400`; borrower user #2 expected 346,320 vs 276,320.
- `wallets:fix-house` posted one withdrawal of 4,420,156.64 on wallet #5 (House principal), reversing reconcile deposit #2180. Re-run: nothing to correct. Partner section: 0 issues.
- `wallets:sync --dry-run`: 2 postings, net KES 70,000 (user #2 CARRY FORWARD #20/#21). Applied on the clone: `wallets:check` → "All customer wallets reconcile."
- With a journal row deliberately mis-slugged, both `wallets:fix-house` and `wallets:check --repair-partners` refused.
- Partner #8: contribution 50,000, withdrawal 20,000, withdrawal 1,000,000 refused, profit 1,500 → journal and wallets matched. Controllers: addFunding 201, addMilestone 200, addNote 200, withdraw too much 400, disbursement over commitment 422, disbursement without loan 422, within commitment 201, house default 201 (open house row). Partner check still 0 issues after all of it.
- `handles:backfill`: 95 handles (91 users + 4 partners), 1 collision: user #101 "IAN OTIENO  OKOTH" → @ianokoth2 (user #40 "Ian Otieno Okoth" keeps @ianokoth; probably the same person twice). Partner #5 ISIRO shares @isiroagencies with user #3. Re-run: nothing to do.
- `php artisan test`: 20 pass, 6 fail. The 4 known ProfileTest failures + ProfileTest "profile page is displayed" (302) and AuthenticationTest login redirect (/account instead of /dashboard) come from parallel role/redirect changes, not from these files.

**Live steps, in order** (back up first: `mysqldump --single-transaction --routines --triggers <db> > backup.sql`)
1. Deploy the code.
2. `mysql <db> < database/sql/2026_10_16_partner_journal_wallet_slug.sql` if not run yet: moves 466 House rows (4,420,156.64) principal → interest. Re-running reports 0 rows.
3. `php artisan wallets:check`: expect `partner #6 principal: journal 0.0000, wallet 4,420,156.6400` (and user #2's 70,000).
4. `php artisan wallets:fix-house --dry-run`, then `php artisan wallets:fix-house`: one withdrawal of 4,420,156.64 from the House principal wallet. House principal becomes 0, interest stays 4,420,156.64, partner #5 broker_fee stays 125,296.96.
5. `php artisan wallets:check`: partner section 0 issues. `--repair-partners` is now safe on live: it refuses while the journal is mis-slugged and otherwise posts compensating `source=correction` entries only.
6. `php artisan investments:fix-house-funding --dry-run`, then without: funding #1 committed/drawn → 186,000 (match the dry-run to the live disbursement total on investment #1).
7. `mysql <db> < database/sql/2026_10_18_wallet_handles.sql` (or `php artisan migrate`), then `php artisan handles:backfill --dry-run`, review collisions, then `php artisan handles:backfill`.
8. Only after the owner confirms CARRY FORWARD is non-cash: `php artisan wallets:sync --dry-run` (2 postings, 70,000 for user #2), then `wallets:sync`.

**Waiting for owner decisions / not done**
- Six-account repayment split (rule 3) and restatement (rule 4): not built. No per-repayment partner/broker/AC postings yet; `Partner::recordRepayment` exists (idempotent reference `REP-{repayment}-P{partner}`) but nothing calls it.
- `kind()` for TO BE PAID / BROKER FEE(S) / SPLIT still cash (decision 3).
- Whether the AC house book should have a commitment cap: house lines are uncapped today.
- AC structure (decision 5), and handle rules (decision 7: implemented as first+last word, numbered by id; a broker's partner record shares the user's handle).
- Partner #5 broker_fee repair deposit (+116,519.99) cause still unknown; the wallet now matches the journal.
- Not fixed here: `LoanCalculator::distributeRepaymentToPartners` (no caller) still calls `$broker->withdraw(...)` with Bavix's signature; editing investment fundings in `update` changes `amount_committed` without a journal adjustment, and removing a funding row leaves its contribution journal row; WalletsBootstrap decimals/role filter; `PartnerController@getStats` reads non-existent `total_contribution`.

## Stage 2: partner self-service

*2026-10-19, branch `stage-2-ui`. Walkthrough with screenshots: [docs/stage2/walkthroughs/partner.md](../../stage2/walkthroughs/partner.md). No schema change, no wallet posting from the partner side, no calculation change.*

**What partners can do now** (`auth` + `role:partner`, own data only; another partner's investment or an unlinked loan → 404)

| Screen | Route (name) | Data source |
|---|---|---|
| Home | `/account` for partner-only users, `/account/partner` (`account.partner.home`) | PartnerPortfolio::summary (Bavix wallet balances = available) + PartnerAccount::home (journal sums: interest earned = profit_distribution + interest + penalty_share; broker fees = broker_fee_earned; capital added = contribution) |
| Investments, detail | `/account/partner/investments[/{id}]` | `investment_fundings` rows of the partner (committed / drawn / returned), Investment `milestones` / `updates` / `notes` JSON (notes with category internal/staff hidden), journal rows with that `investment_id` |
| Funded loans, detail | `/account/partner/loans[/{loan}]` | loans whose disbursement has `investment_id` in the partner's investments, plus `loans.partner_id` = partner (brokers); LoanProgress for balance and % repaid; journal rows for that loan. Borrower shown by initials + @handle only |
| Earnings | `/account/partner/earnings` | journal rows of the four earning types, grouped by month and type in PHP |
| Statement | `/account/statement` (`account.statement`, now `partnerStatement`) + `/account/statement.csv` | journal, filter by wallet + from/to; CSV streams the same rows |
| Add funds | `/account/partner/add-funds` (GET/POST) | instructions from `config('lending.partner_funding')`; "I've sent funds" → `AuditTrail` row (`partners`, partner id, `create`, `{request: partner_funds_sent, …}`) + `CustomerNotifier::partnerRequest` (StaffAlert to admins, link to `partners.show`) |
| Withdraw | `/account/partner/withdraw` (GET/POST) | wallet balances (Bavix, read-only); amount ≤ chosen wallet's balance (checked server-side); payout = partner bank account, else M-Pesa on the partner phone; `x-az.confirm`; audit row `{request: partner_withdrawal, …}` + StaffAlert. Nothing stored or posted: staff pay out and record it with `Partner::withdrawPartnerBalance` |

A user with both borrower and partner roles gets a Borrowing / Partner switcher in the account layout. Partner pages pass `'area' => 'partner'` to the layout, which then shows the partner tabs: Home, Investments, Loans, Statement, Profile. A partner-role user with no `partners` row sees a "being set up" state on every partner page; the statement used to 404 for them.

**Files**
- `app/Http/Controllers/Concerns/PartnerSelfService.php` (trait, used by HomeController)
- `app/Services/PartnerAccount.php` (read-only queries)
- `app/Support/CustomerNotifier.php` (`partnerRequest`)
- views `resources/views/home/account/partner.blade.php`, `statement.blade.php`, `partner/{setup,investments,investment,loans,loan,earnings,funds,withdraw}.blade.php`
- layout `home/layouts/account.blade.php` (partner nav + switcher)
- `routes/web.php`
- styles.css ("Partner self-service" block)
- `config/lending.php` and `.env.example`

`HomeController@accountStatement` is no longer routed.

**Config** `lending.partner_funding`:

| Key | Env | Default |
|---|---|---|
| `bank_name` | `PARTNER_FUNDING_BANK_NAME` | empty: info note instead of details |
| `bank_account_name` | `PARTNER_FUNDING_BANK_ACCOUNT_NAME` | empty: info note instead of details |
| `bank_account_number` | `PARTNER_FUNDING_BANK_ACCOUNT_NUMBER` | empty: info note instead of details |
| `bank_branch` | `PARTNER_FUNDING_BANK_BRANCH` | empty: info note instead of details |
| `bank_swift` | `PARTNER_FUNDING_BANK_SWIFT` | empty: info note instead of details |
| `mpesa_paybill` | `PARTNER_FUNDING_MPESA_PAYBILL` | empty: info note instead of details |
| `reference_prefix` | `PARTNER_FUNDING_REFERENCE_PREFIX` | `AZP-`, so partner 9 is AZP-0009 |
| `min_amount` | `PARTNER_FUNDING_MIN_AMOUNT` | 1000 |

**Stage 4 dependencies**
- Funding requests table plus staff confirmation. Today the "I've sent funds" request is an email + audit row only.
- Withdrawal requests table, maker-checker approval and the PartnerLedger posting on approval.
- Online investing: fund overview → amount & tenor → subscription agreement (signed) → fund → confirmed. Today it is a placeholder on Investments.
- Partner KYC (investor type, identity, suitability, payout & tax), which would also let partners maintain their own payout details.
- Portfolio report PDF and tax certificate.
- Request status tracking in the account.

**Owner decisions**
1. Bank account and paybill partners pay into, and the reference format (`AZP-0009` for now).
2. Which investment notes partners may see. Today every note is shown except category `internal`/`staff`, and `update` notes are shown once, as updates.
3. Whether a partner sees every loan drawn from an investment it co-funds with others. Today it does, because the share per loan is not attributed.
4. Whether withdrawals may come from any wallet (capital, interest, broker fees), or only from earnings and matured capital.
5. Whether to show the borrower's @handle to partners or only the initials.
6. Minimum top-up and withdrawal amounts.
