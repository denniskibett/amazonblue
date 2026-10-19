# Stage 4 plan (written 19 October 2026, Stage 3)

*This is a plan only; no code was written. It builds on [REVIEW.md](REVIEW.md) (findings S-*, G-*), [AUDIT.md](../audit/AUDIT.md) and [SUMMARY.md](../stage2/SUMMARY.md).*

**Standing rules for every phase**

- **One stage per session.** Each phase below is sized for one session.
- **Every schema change ships twice:**
  1. as a guarded migration: one table per file, FKs inline, ordered core → packages → app → views;
  2. as an idempotent SQL twin in `database/sql/`, which must run on MariaDB 10.3 (live) and MySQL 9 (local).

  MySQL 9 has no `ADD COLUMN IF NOT EXISTS`, so guards go through `information_schema` in a prepared statement. The `migrations` row insert must keep its guard outside the aggregate (D, "Still open").
- **Money changes run behind a switch and a dry-run.** Each has a switch in `config/lending.php` and a `--dry-run` that prints a before/after CSV.
- **History is corrected by adjusting entries,** never by editing or deleting repayments or disbursements.
- **Back up first.** Every live step starts with `mysqldump --single-transaction --routines --triggers`.

---

## 0. Phase 0: confirm live, close the leaks, stop the worst figures (small, low risk)

### 0.1 Confirm live state (owner, no code)

Owner runs and reports:

- `php artisan migrate:status`;
- `php artisan schedule:list`;
- `php artisan wallets:check`;
- `SELECT COUNT(*) FROM audit_logs`;
- `SELECT COUNT(*) FROM loan_risk_assessments`;
- `curl -I https://<site>/storage/agreements/loan_agreement_1.pdf`, which should return 404;
- the `APP_DEBUG` and `LOG_LEVEL` values.

Tick off each step of `DEPLOY-2026-10-18.md` and the Stage 2 deploy (SUMMARY "To deploy"). **No Phase 1+ work starts until this is done.**

### 0.2 Work items

| # | Item | Current → target | Files | Tables |
|---|---|---|---|---|
| 0.1 | G1 broker file access | broker holds `view users` → remove it from broker (owner OK), and gate `files.user`/`files.business` on `edit users` or a new `view kyc documents` | `RoleSeeder`, `PrivateFileController`, `Business::isVisibleTo`, SQL role-permission script | `role_has_permissions` |
| 0.2 | G2 status by edit | store/update can set `disbursed`/`repaid` → refused (422, "Use Disburse / Receive payment") | `LoanController` store/update | — |
| 0.3 | G3 cycle ∈ loan | `exists:loan_cycles,id` → `Rule::exists(...)->where('loan_id', $loan)` on store and update | `RepaymentController` | — |
| 0.4 | `/system` gate | `role:super-admin\|admin` → `permission:access system settings`, once sysadmin exists | `routes/web.php` | — |
| 0.5 | Interim figure guards | S-B3 hide extension "new amount"; S-P1 cap withdrawal at principal − lent-not-returned; S-P2/S-P3/S-S1/S-S5 captions; S-B6 label | extension, withdraw, partner, dashboard, wallets views; `PartnerSelfService` withdraw check | — |
| 0.6 | G4, G5 copy and reopen | pending-card copy → "your due date is set from the date we send the money" *only after* decision 12 (else "from your application date"); reopen-by-edit with a changed amount → rebuild cycle 1 while the loan is still pending (no money moved) | `BorrowerLoanState`, `LoanController@update` | `loan_cycles` |
| 0.7 | G6 partner lists | one list → "Funded" (investment link) and "Brokered" (`partner_id`) tabs | `PartnerAccount`, partner loans views | — |
| 0.8 | G7 written-off borrowers | can borrow 5,000 → blocked (if owner agrees) | `BorrowingEligibility` blockers | — |
| 0.9 | Email verification (P1 #17) | not enforced → `MustVerifyEmail` for customers before KYC submit (decision 17); staff keep `verified` | `User`, KYC routes, verify redirects → `homeRoute()` | — |
| 0.10 | Small fixes | register throttle; `robots.txt`; `/clear-cache` → POST; drop `custom_js`/`custom_css` fields; delete `app/Http/Kernel.php`, `admin/system/index.blade.php`, TailAdmin demo routes (**Check** they're still routed); fix `PaymentMethod::methodType`; `hasRole` → `@can` in buttons; fix older SQL `migrations` guards | various | — |
| 0.11 | Repaid-but-owing report | nothing → a nightly `loans:check-integrity` (read-only), which lists `repaid` loans with cycle balance > 0, loans with an active cycle after repaid, and repayments dated before their cycle start | new command, schedule | — |
| 0.12 | Characterisation tests | none on money → tests that pin **today's** engine output for loans 529, 486, 494 and 461 (fixtures built from the audit numbers) | `tests/Feature/Money/*` | — |
| 0.13 | Staff mobile check | desktop shots only → 390 px pass over approvals, inbox, end of day, roles, wallets, products, payouts; fix overflow | staff desk views | — |
| 0.14 | Policies | — → audit-log retention (decision 23, e.g. 7 years for money and 2 years for access), and restore-test the backup once | doc + scheduled prune | `audit_logs` |

**Migration and rollback.** Only 0.1 and 0.6 touch data. 0.1 is reversible by re-granting the permission. 0.6 touches only pending loans. Everything else is code: roll back by redeploying the previous build.

**Financial risk.** None. 0.2 and 0.5 *reduce* the chance of acting on a wrong figure.

**Tests.**

- A permission test per role for files, status edits and `/system`.
- A cycle-mismatch 422.
- Withdraw-cap tests.
- The characterisation tests (0.12) must pass before and after Phase 0.

**Acceptance.**

- A broker gets 404 on any `/files/users/…` and `/files/businesses/…`.
- Editing a loan to `repaid` returns 422.
- A repayment on another loan's cycle returns 422.
- A partner can't request more than uncommitted capital plus earnings.
- `loans:check-integrity` runs nightly, and its first output goes to the owner.

---

## 1. Phase 1: ledger foundation (no figures change yet)

**Goal:** a double-entry ledger that every later phase posts to. It runs **in parallel** with today's records; nothing reads it for balances yet.

| Current | Target |
|---|---|
| `loan_ledger` exists, 0 rows, no writer | Replaced by `ledger_accounts`, `ledger_journals`, `ledger_entries` (`loan_ledger` dropped after owner OK, since it is empty) |
| Cash vs book entry decided by `CustomerWallets::kind()` from free text | A stored `kind` column on `repayments` and `disbursements` (cash, rollover, discount, bad_debt, non_cash), backfilled from `kind()`; `kind()` reads the column first |
| No `created_by` on money rows | `created_by` on `repayments` and `disbursements`, backfilled from the `audit_logs` create rows where present |
| Reversal = soft delete | `repayments.reversal_of_id`. A reversal is a new negative row plus a reversing journal. The original stays. Soft delete is kept for data-entry mistakes on the same day only (owner). |
| Bavix wallets are the only balances | Bavix stays as the customer-facing balance. Each wallet posting carries `meta.source = ledger_journal, source_id`. `wallets:check` adds a ledger ↔ wallet comparison. |

**Tables (one migration each, plus SQL twins):**

- **`ledger_accounts`:**
  - `id`, `code` (unique), `name`, `type` (asset/liability/income/expense/equity);
  - `holder_type`/`holder_id` (nullable), for partner, broker, AC or borrower sub-ledgers;
  - `is_active`.
- **`ledger_journals`:**
  - `id`, `occurred_on`, `posted_at`;
  - `source_type`, `source_id`, `purpose`, unique (`source_type`, `source_id`, `purpose`): this makes posting idempotent;
  - `description`, `created_by` FK users, `reversal_of_id` FK self.
- **`ledger_entries`:**
  - `id`, `journal_id` FK cascade, `account_id` FK;
  - `loan_id` FK, `loan_cycle_id` FK, `partner_id` FK (all nullable);
  - `debit`, `credit` decimal(15,4) unsigned;
  - CHECK (one side > 0). MariaDB 10.3 enforces CHECK; MySQL 9 does too.
- Columns: `repayments.kind`, `repayments.created_by`, `repayments.reversal_of_id`, `disbursements.kind`, `disbursements.created_by`.

**Code.**

- `App\Services\Ledger` with `post(journal, lines)`. It refuses an unbalanced journal, wraps everything in a transaction and is idempotent on the unique key.
- `ledger:backfill --dry-run` posts journals for existing disbursements and repayments using **today's** meaning: cash vs book entry only, with no interest/penalty split yet. That split is Phase 3.
- Observers call `Ledger` after commit, alongside the existing wallet sync.

**Migration and rollback.**

- All changes are additive.
- Rollback = turn off `lending.ledger.enabled` and truncate the three ledger tables. No existing table changes meaning.
- `kind` backfill: the dry-run lists every row whose stored kind would differ from `kind()`. That list should be empty.

**Financial risk.** Low. The ledger is write-only this phase. The one risk is double posting on retries, which the unique `(source_type, source_id, purpose)` prevents.

**Security.** Ledger tables get no edit UI. Read access is `view ledger`, which already exists and is unchecked (D U8).

**Tests.**

- Every journal balances.
- Backfill twice = the same rows.
- A reversal journal nets to 0 against its original.
- `kind` column == `kind()` for all 800+ repayments.

**Acceptance.**

- The ledger trial balance sums to 0.
- Ledger cash in/out per borrower equals that borrower's Bavix `borrower_loan` wallet for all 76 borrowers.
- The end-of-day page reads `created_by`.

---

## 2. Phase 2: cycle engine (rules 5, 6, 7, 8): the core money fix

**Blocking owner decisions:** 1 (penalty rate and cap), 2 (meaning of `loans.amount`, restate?), 6 (legacy chains), 11 (rollover rate, waivers), 12 (cycle-1 start, NPL threshold). See §9.

| Current | Target |
|---|---|
| Penalty days run to today; a late payment reduces only the penalty (rule 5) | One pure function `CycleBalance::at(cycle, repayments, date)`. Penalty accrues daily on the unpaid balance (not on penalty), only until it is paid. Each payment clears penalty first, then interest, then principal. Optional cap (decision 1). Payment-day semantics per decision. |
| Two other penalty formulas (`Loan::calculatePenalties`, the NPL total) | Deleted; every caller uses `CycleBalance` |
| Rollover books `new_balance` and starts on the due date; preview differs (rule 6) | `Rollover::preview()` and `Rollover::execute()` call the same function. New principal = true outstanding at the last penalty day. The new cycle starts on that day and is due one period later. Runs in `DB::transaction` + `lockForUpdate`. A completed cycle is never rolled again. |
| Cycle 1 created at application, from `borrow_date` | Cycle 1 is (re)dated at disbursement (decision 12), inside the disbursement transaction |
| `loans.amount` = P + I after a rollover; `original_amount` NULL on 465 loans (rule 7) | `loans.amount` = current principal (decision 2). `original_amount` = cycle-1 principal for all loans. Cycle 1 never reads `loans.amount`. |
| "Repaid" = Σ all repayments ≥ `loans.amount`, or no active cycle (rule 8, P1 #10) | Loan status is derived from the **current cycle's** balance: repaid ⇔ the last cycle's balance ≤ 0 from cash or an allowed non-cash kind (per decision 7). `markAsRecovered` posts nothing and sets nothing: it requires the balance to be 0. |
| Penalty rate, grace and threshold read live from the product | Snapshotted onto the cycle at creation |
| Payment plan stored at 0% but `new_balance` includes interest | Plan = a cycle with an explicit `interest_rate` and `penalty_waived_until`; waiver applies to that cycle only (decision 11) |
| Status detection from due dates only | `loans:update-statuses` uses `CycleBalance`, so a paid loan is never marked overdue. One default threshold: `Loan::getDefaultThresholdDays()`. |

**Tables (one migration each, plus SQL twins).**

`loan_cycles` gains:

- `penalty_rate`, `grace_days`, `default_threshold_days` (snapshot);
- `penalty_waived_until`;
- `closing_balance`, `penalty_accrued`, `principal_paid`, `interest_paid`, `penalty_paid` (cache, rebuilt by command);
- `closed_on`.

`loans.original_amount` is backfilled. No new table.

**Code.**

- `app/Services/Loans/CycleBalance.php`, `Rollover.php`, `LoanStatus.php`.
- `LoanCalculator` keeps its public methods as thin wrappers so the 30+ callers don't change in one go.
- Delete `loans:repair-cycles` and `Loan::calculatePenalties`.

**Restatement (needs decision 2).** `loans:restate --dry-run` writes `storage/app/private/restate-YYYYMMDD.csv`, one row per loan:

- old vs new balance, status and amount;
- the difference;
- the reason code.

The owner signs off the CSV. `--apply` then:

- writes the cycle caches and `original_amount`;
- corrects statuses;
- posts **adjusting journals** for differences in what the borrower owes.

Repayments and disbursements are never edited. Legacy ROLL OVER chains (233 loans) follow decision 6. The recommended default is to leave them as separate loans, flag them `legacy_chain`, and exclude their book-entry rows from totals.

**Feature switch.** `lending.engine = v1|v2`. Screens read through `LoanProgress`, so the switch covers every borrower, partner and staff figure in §3 of REVIEW at once.

**Migration and rollback.**

- Columns are additive.
- Rollback = `engine=v1` plus a reversing journal per restatement batch, since each batch is one journal group.
- Keep v1 for 30 days after the switch-over.

**Financial risks.**

| Risk | Mitigation |
|---|---|
| Borrowers' balances change. Most go **down** (rule 5 late payers); some rollovers go down (rule 6 over-charge, e.g. 1,226.40 on loan 529). | Owner reviews the CSV. Customer message for anyone whose balance changes by more than KES 100. |
| Loans flip from `repaid` back to owing (P1 #10, e.g. loan 461) | List them separately; owner decides per loan; no automatic collection action for 30 days |
| Limits change (scoring reads statuses) | Re-run `risk:assess` after apply; report limit changes |
| Partner and broker shares computed on the old engine (backfills) | Recomputed in Phase 3, not here |
| Penalty cap reduces income | Owner decision 1; legal sign-off |

**Security.** `loans:restate --apply` refuses unless the dry-run CSV for the same day exists and its hash is passed (`--confirm=<sha>`).

**Tests.**

- The four audit worked examples become exact expectations. Loan 529: pays 16,258 on 10-17, viewed on 10-25 → 0; rollover on 10-17 → principal 16,258, start 10-17, due 10-27, new balance 19,509.60.
- Preview == execute for 200 generated cases.
- Payment-day semantics.
- Waiver scope.
- Product units (days, weeks, months, year).
- Loan status matches the cycle balance for every loan after restate.
- The Phase 0 characterisation tests are **updated on purpose**, with the diff reviewed.

**Acceptance.**

- `loans:check-integrity` reports 0 repaid-but-owing loans and 0 repaid loans with an active cycle.
- The restate CSV is signed off.
- S-B1 … S-B7, S-S3 and S-S6 give correct figures.
- The extension preview equals the booked rollover to the cent.

---

## 3. Phase 3: six-account split, partner and AC postings, restatement (rules 1–4)

**Blocking owner decisions:** 3 (broker-rate source), 4 (penalty split), 5 (non-cash text meaning: partly done in Stage 2), 8 (partner = funder only?), 9 (AC structure), 10 (repayment processing fee).

| Current | Target |
|---|---|
| Repayments not split; partner and house amounts come from a one-off backfill (497 rows, no `repayment_id`) | Every repayment gets six allocation legs (principal, interest, penalty, broker, partner, AC), with zeros recorded. Each leg posts one ledger line and the matching Bavix posting (`meta.source=repayment_alloc, source_id`). |
| Partner principal never moves on disbursement → "available" overstated (S-P1) | A disbursement funded by an investment moves partner principal "available → lent". The repayment's principal leg moves it back. `recordFundingReturn` is called by the allocation. |
| `loans.partner_id` = broker | `loans.broker_partner_id` (renamed meaning) and the funder via `disbursements.investment_id` only. 619 legacy disbursements are linked to the AC house investment. |
| One house Partner #6 with four slugs | Per decision 9 (recommended: keep one house holder with separate slugs, since that is what live has; delete the second house partner from the seeder) |
| Broker rate from three sources | One source (decision 3), snapshotted on the loan at creation |
| `distributeRepaymentToPartners` with Bavix `withdraw` | Deleted; the allocation service replaces it |

**Tables.**

- **`repayment_allocations`** (one migration):
  - `id`, `repayment_id` FK cascade, `leg` (principal/interest/penalty/broker/partner/ac);
  - `amount` decimal(15,4);
  - `partner_id`, `investment_id` (nullable FKs);
  - `ledger_journal_id` FK;
  - unique (`repayment_id`, `leg`, `partner_id`).
- Columns: `loans.broker_partner_id` (backfilled from `partner_id` where `broker_status=1`; the old column stays read-only until Phase 7), and `loans.broker_rate_snapshot`.

**Restatement of history (rule 4).**

- `allocations:restate --dry-run` computes the legs for all 800+ repayments on the Phase 2 engine.
- It compares them with the existing journal: the backfill rows, broker fees and the +116,519.99 repair.
- It writes a CSV per partner and broker.

On apply it posts **difference** journals per partner and wallet, which reconcile old to new, with a dated reason. The legacy journal rows are never deleted.

**Migration and rollback.** The table is additive. Rollback = reverse the restatement journal group and stop the allocation observer (`lending.split.enabled=false`).

**Financial risks.**

| Risk | Mitigation |
|---|---|
| Partner and broker balances go **down** after restatement (e.g. the backfill used 40% on loans where the brokers table says 20%) | Owner signs off per-partner CSVs before apply; partner-facing note explaining the change; nothing is withdrawn automatically |
| Partners have already been paid out more than restated earnings | Report negative available balances; owner decides recovery |
| Allocation rounding | 4 dp; the last leg takes the remainder; the sum of legs == repayment amount, enforced |

**Security.** Partner payouts read only the restated wallets. Withdrawal caps (Phase 0) switch to the real "available".

**Tests.**

- Σ legs = amount for every repayment.
- Brokered vs non-brokered loans.
- Penalty split per decision 4.
- Disbursement → principal moves to "lent", and repayment moves it back.
- Restate twice = no change.
- `wallets:check` = 0 issues across ledger, journal and wallets.

**Acceptance.**

- Every repayment has six legs.
- Partner home "available" = wallet principal − lent + earnings.
- S-P1 … S-P4 and S-S5 are correct.
- The house AC books reconcile to the ledger.
- The owner has signed the restatement CSVs.

---

## 4. Phase 4: M-Pesa payments (Daraja)

**Blocking owner decisions:**

- the live paybill and production Daraja credentials (SUMMARY: still open; 174379 is the sandbox);
- the account-number format (decided: `loan_number`);
- who receives unmatched payments.

| Current | Target |
|---|---|
| "I've paid" = email + audit row + session | `payment_claims` table; claim status visible on every device; staff claims queue |
| STK button disabled | STK push (`MPESA_STK_ENABLED`) with callback |
| No C2B | C2B validation and confirmation URLs. A confirmed payment with a matching loan number becomes a repayment automatically (mode `mpesa`, `transaction` = M-Pesa receipt, `created_by` = system); unmatched → queue. |
| No reconciliation against M-Pesa | Upload the M-Pesa statement CSV; match by receipt; report missing either way |
| No "payment received" email | Sent on repayment create (Phase 6 adds SMS) |

**Tables (one migration each).**

- **`mpesa_transactions`:**
  - raw callback: `trans_id` unique, type (c2b/stk), `msisdn`, `amount`, `bill_ref`, `trans_time`, `payload` JSON;
  - `repayment_id` FK nullable;
  - `status` (received/matched/unmatched/duplicate/reversed).
- **`payment_claims`:**
  - `user_id`, `loan_id`, `code`, `amount`, `phone`;
  - `status` (checking/received/rejected);
  - `repayment_id`, `mpesa_transaction_id`.

**Code.** `app/Services/Mpesa/{Daraja,C2B,Stk}.php`, `MpesaCallbackController` (outside the web group, no CSRF), the staff claims queue under `/staff/payments`, and the repay views behind the flags. A queue worker is needed (see Phase 6).

**Migration and rollback.** Flags off = today's behaviour. Turning a flag off after go-live leaves matched repayments in place, which is correct because the money arrived.

**Financial risks.**

| Risk | Mitigation |
|---|---|
| Duplicate callback → double repayment | Unique `trans_id`; repayment creation keyed on it |
| Wrong loan matched (typo in account no.) | Match only an exact loan number of an open loan of the same MSISDN owner; else the queue |
| Overpayment | Allocation (Phase 3) puts any excess in a borrower credit leg; owner decides refunds |
| Sandbox credentials in production (current `.env`) | Startup check: `MPESA_ENV=production` with shortcode 174379 → refuse to enable |

**Security.**

- Callback URL with a secret path segment plus a Safaricom IP allowlist.
- Never trust the amount from the client or the STK request; only the confirmation counts.
- Credentials only in `.env`.
- Log payloads with the MSISDN masked.

**Tests.**

- Callback fixtures: valid, duplicate, unmatched, amount mismatch, reversal.
- STK success, cancel and timeout.
- Claim → match → "Received".

**Acceptance.**

- A paybill payment to the live shortcode with a loan number appears as a repayment within a minute, with six legs.
- The borrower sees "Received".
- The reconciliation of a day's M-Pesa CSV shows 0 unexplained rows.

---

## 5. Phase 5: requests, maker-checker, approval limits, cash-up, products

**Blocking owner decisions:** 14 (who approves, rejects and disburses; second sign-off above what amount), reversal by managers and accountants (SUMMARY open), KYC and business maker-checker (decision 22, E5), extension policy.

| Current | Target |
|---|---|
| Borrower extension, plan acceptance and help = audit row + email | `borrower_requests` with status, staff decision and borrower-visible outcome. Plan consent stores version, IP, user agent and hash. |
| Partner funds-sent and withdrawal = audit row + email | `partner_requests` (funding, withdrawal). Approval posts through `PartnerLedger`. Maker-checker. |
| One person can approve and disburse (G8) | `approvals` (maker-checker): approve above a limit, disburse, write-off, reversal and partner withdrawal need a second user with the permission. Maker ≠ checker is enforced. |
| No approval limits | `approval_limits` (role, action, max_amount) |
| Rejected loan reopened only by staff edit | `loans.customer_edit_allowed` flag + customer edit screen (SUMMARY Stage 4 item) |
| "Close the day" placeholder | `teller_day_closes` (teller, date, expected cash by mode, counted, variance, closed_by, approved_by) |
| Products read-only (penalty read live) | Product editing with `loan_type_versions`. Safe after Phase 2, which snapshots onto cycles. |
| Reversal requires role admin | `delete repayments` + checker approval; role check removed |
| Restructure placeholder | Restructure = a payment-plan cycle (Phase 2) created through an approval |

**Tables (one migration each):** `borrower_requests`, `partner_requests`, `approvals`, `approval_limits`, `teller_day_closes`, `loan_type_versions`, and the column `loans.customer_edit_allowed`.

**Rollback.** All additive. Rollback = flags off; screens fall back to the Stage 2 interim (email + audit row).

**Financial risk.** Low; this phase adds controls. Watch for approvals that never complete: a daily digest of pending approvals older than 24 h.

**Security.** Checker ≠ maker (also enforced in the DB). Approval decisions go to the audit log. Limits are read server-side only.

**Tests.** Per-action, per-role matrix; maker-checker refusal; limit boundaries; cash-up variance.

**Acceptance.**

- No money action above its limit completes with one user.
- Borrowers see the status of their requests.
- A teller's day closes with a recorded variance.

---

## 6. Phase 6: notifications and SMS (can run in parallel after Phase 0)

**Blocking owner decisions:** SMS provider; which events; quiet hours; sender ID.

- **Current:** synchronous mail for KYC, application and business; no queue.
- **Target:**
  - Laravel `notifications` table (database channel) for the borrower notifications centre;
  - SMS channel;
  - events: approved, money sent, payment received, due in 3 days, due today, overdue day 1 / 7, rejected, request decided, partner funds confirmed, withdrawal paid;
  - phone OTP at registration (A §2).
- **Infrastructure:** `QUEUE_CONNECTION=database` plus a cron `queue:work --stop-when-empty` every minute (CyberPanel has no supervisor).
- **Tables:** `notifications`, `jobs`, `failed_jobs` (each with a guarded migration and SQL twin), `notification_preferences`.
- **Rollback:** channel flags off.
- **Financial risk:** none. **Legal:** consent and opt-out for marketing vs service messages (DPA).
- **Tests:** each event → the right channel and copy; failed SMS retried and logged; no SMS for staff-only events.
- **Acceptance:** a borrower gets an SMS within 2 minutes of money sent and of payment received; the notifications centre reads the table.

---

## 7. Phase 7: reports and dashboard on the ledger

- **Current:** the dashboard sums `loans.amount`; reports mix `loans.amount` and real-cash columns; 7 unused SQL views.
- **Target:**
  - every KPI and report reads the ledger and cycle caches: portfolio at principal, interest and penalty income, PAR 1/30/90, NPL, partner distributions, trial balance;
  - CSV and PDF export;
  - drop the SQL views (one migration each, with SQL twins);
  - drop `loans.partner_id` after `broker_partner_id` has been in use for one month.
- **Blocking owner decision:** the statutory report set (CRB, CIC) and its format.
- **Rollback:** the old report methods stay behind `reports.source=legacy` for one release.
- **Acceptance:**
  - dashboard and reports agree with the ledger trial balance;
  - S-S1 and S-S2 are correct;
  - no report reads `loans.amount` as "lent".

---

## 8. Phase 8: scope-gated products (each needs a go/no-go)

| Item | Needs | Recommendation |
|---|---|---|
| Partner KYC + online subscription (fund overview → amount → signed subscription → fund) | Phase 3 (real partner balances), Phase 4 (payment in), Phase 5 (approval) | First of this group |
| Business loans (products, limits for businesses) | Phases 2, 3; E decisions 1–6 | Second |
| Director self-verification links | Phase 6 (SMS/email) | With business loans |
| AML / sanctions / PEP screening | A provider contract | Only with business loans |
| CRB checks | Owner decision 20; a CRB contract | When the owner decides |
| Trade finance (LC, FX, supplier payments) | No backend at all | **Out of Stage 4.** Needs its own audit. |
| Asset-backed lending + valuation | No backend; `Asset` model only | **Out of Stage 4** |
| M-Pesa B2C payouts (disbursement) | Phase 4 + maker-checker | After Phase 5 |
| Mobile app API | — | **Out of Stage 4** |

---

## 9. Owner decisions, by the phase they block

**Decided 20 October 2026 (Phase 0 can start):**

- **G1:** remove `view users` from broker.
- **G2:** block setting `repaid` and `disbursed` by edit.
- **G3:** validate cycle ∈ loan on the server.
- **G7:** block new loans after a write-off.
- **Decision 12:** cycle 1 starts at disbursement, so the G4 copy fix ships with Phase 0 and the engine change with Phase 2.
- **Super-admin:** sysadmin only.

Also: partner funding details are editable on `/system`, super-admin only, with a password check, audit row and admin email (SUMMARY addendum). Maker-checker on that change waits for Phase 5.

Each decision has a recommended default. If the owner accepts the default, the phase proceeds on it.

| # | Decision | Blocks | Recommended default |
|---|---|---|---|
| 1 | Penalty: is 10% per day intended? Cap? Does the agreement wording match? | 2 | Simple daily penalty on the unpaid balance; total interest + penalty capped at the outstanding principal when the loan went non-performing (in-duplum); **legal sign-off required**; change the agreement wording from "compounding" to match |
| 2 | `loans.amount` = current principal? Restate past reports? | 2 | Yes, current principal; restate with adjusting journals and the signed CSV |
| 3 | Broker rate source | 3 | Brokers table by client_type, snapshotted on the loan |
| 4 | Penalty split | 3 | Brokered: 50/50 broker/AC; other loans: 100% AC |
| 5 | Meaning of the remaining non-cash texts | 1 | As Stage 2 (`TO BE PAID`, `BROKER FEE(S)`, `SPLIT` = non-cash) |
| 6 | Legacy ROLL OVER chains | 2 | Leave them as separate loans; flag them; exclude book entries from totals |
| 7 | Write-offs and recovery collections | 2 | Write-off = journal to a bad-debt expense; collections after write-off = recovery income; status stays `written_off` |
| 8 | Partner = funding investor only; do brokers stay Partner rows? | 3 | Yes, funder only; brokers keep Partner rows for their wallet but are linked by `broker_partner_id` |
| 9 | AC structure | 3 | One house holder with slugs (matches live) |
| 10 | Repayment processing fee | 3 | Drop (it is 0 everywhere) |
| 11 | Rollover rate; do waivers apply to past cycles? | 2 | Previous cycle's rate; waiver applies to the current cycle only |
| 12 | Does cycle 1 start at application or at disbursement? NPL threshold? | 0, 2 | Disbursement; `loan_types.default_threshold_days` |
| 14 | Approve/reject/disburse roles; second sign-off amount | 5 | Approve: manager/admin. Disburse: teller. Maker ≠ checker. Second sign-off above KES 50,000. |
| 17 | Email/phone verification before KYC | 0, 6 | Email before KYC submit now; phone OTP in Phase 6 |
| 18 | Limit floor; written-off borrowers | 0 | Block borrowing after a write-off; keep the 5,000 floor otherwise |
| 19 | 4Cs advisory or blocking | 5 | Advisory, but approval below "refer" needs a second approver |
| 20 | CRB checks | 8 | Not now; stop asking for CRB consent until they're used |
| 21 | Live paybill + Daraja production credentials | 4 | — (needed from the owner) |
| 22 | KYC maker-checker, KRA PIN unique, re-KYC | 5 | KRA PIN unique; maker-checker not required for individual KYC; re-KYC every 24 months |
| 23 | Retention | 0 | Money and agreements 7 years after closure; KYC 7 years after the relationship ends; access logs 2 years |
| — | Who receives claim and request emails | 4 | Tellers + admins until the Phase 4 queue exists |
| — | Managers and accountants may reverse? | 5 | Yes, with checker approval |
| — | Remove `view users` from broker (G1) | 0 | Yes |
| — | Business: registration number unique? 100% shareholding? Bank statements? Director KYC? Approver? Edit after approval? | 8 | Unique; 100% for limited companies; 3 months of statements; director KYC before approval; `edit users` + second approver; edits via a change request |
| — | Partner: notes shown, co-funder visibility, withdrawal wallets, @handle vs initials, minimums | 3 | As built in Stage 2; withdrawals from earnings + un-lent capital only |
| — | Written-off wording; rejection display days; re-apply wait; recovery contact; officer name shown | 0 | Balance still owed; 30 days; no wait; a separate recovery line if one exists; show the officer's first name only |

---

## 10. Order and dependencies

```
Phase 0 ──► Phase 1 ──► Phase 2 ──► Phase 3 ──► Phase 7
   │            │                      │
   │            └──► Phase 5 ◄─────────┘ (product editing needs 2; withdrawals need 3)
   │                                   └──► Phase 4 (auto-posting needs 3's allocation; claims table can start after 1)
   └──► Phase 6 (any time after 0)
Phase 8 items: after their listed needs, each with an owner go/no-go.
```

**Before each money phase goes live (1, 2, 3, 4):**

- a clone of live with the phase applied;
- the dry-run CSVs signed by the owner;
- a backup;
- switch-over and rollback steps written as a `docs/stage4/DEPLOY-<phase>.md` runbook, in the same form as `DEPLOY-2026-10-18.md`.
