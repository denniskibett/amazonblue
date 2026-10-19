# Stage 3 review: Stage 2 UI against the audit (19 October 2026)

*Review only. No code, schema or data was changed. The sources are `docs/audit/AUDIT.md`, `docs/audit/modules/*.md`, `docs/audit/DEPLOY-2026-10-18.md`, `docs/stage2/SUMMARY.md` and `docs/stage2/walkthroughs/*`, on branch `stage-2-ui` at commit `42cab7d`. The codebase was not re-read, so every claim below rests on those documents. Where they don't settle a point, it is marked **Check**: something to confirm with one grep at the start of Stage 4.*

The Stage 4 plan is in [STAGE4-PLAN.md](STAGE4-PLAN.md).

**Verdicts used below**

- **Fixed:** done in the Stage 1 fixes (18 October).
- **UI:** addressed by a Stage 2 screen.
- **Stage 4:** deferred, and listed in the plan.
- **Owner:** waiting on an owner decision.
- **Rejected:** won't do, with the reason given.
- **Gap:** not addressed and not deferred anywhere. This is a new finding of this review.

---

## 0. Summary

1. **Every P0 and P1 audit finding is now fixed, deferred or waiting on a decision.** The exception is P1 #17, email verification. It is still not enforced, and no stage owns it. It is now Stage 4 Phase 0.
2. **The biggest risk is not missing UI.** It is that several screens show numbers the engine computes wrongly. "Owed today", the extension preview, the due date, "Facility closed" and the partner's "available" balance are all affected (§3). Ten of the screens Stage 2 built depend on rules 5, 6, 7 and 8, which were deliberately left alone.
3. **This review found eight new issues (G1–G8).** The three to fix first:
   - **G1:** brokers can open every borrower's KYC documents and every business's documents.
   - **G2:** staff can still set a loan to `disbursed` or `repaid` by editing it, with no money recorded.
   - **G3:** the cycle check on repayments exists only in the UI; the server doesn't enforce it.

   All three are small, and all three are in Phase 0 of the plan.
4. **The borrower copy promises something the engine doesn't do.** The pending-loan card says "the due date starts when the money is sent". In fact cycle 1 starts on the application date and is never moved (B §2).
5. **Live state is not confirmed.** The Stage 1 runbook (`DEPLOY-2026-10-18.md`) exists, and the failed super-admin tinker shows live has *some* new code. Nothing records which runbook steps ran. Stage 4 can't start money work until the owner confirms (plan §0.1).

---

## 1. Audit finding traceability

### 1.1 Business rules (AUDIT §1)

| # | Rule | Audit | Now | Verdict | Notes |
|---|---|---|---|---|---|
| 1 | AC owns company-funded loans | Partial | Unchanged. Disburse defaults to the house line (General Lending Book). | Stage 4 (Phase 3) | The partner "Funded loans" list also includes `loans.partner_id = partner`, which is the **broker**. A broker-partner sees brokered loans as "funded" (G6). |
| 2 | Investment → partner → disbursement, no over-allocation | Partial / Broken | Over-allocation guard added (`assertCanDisburse`); house lines uncapped by design; disburse screen shows the investment picker and the funders | Fixed (guard); Stage 4 (backfill of 619 legacy rows) | |
| 3 | Six-way split of every repayment | Planned only | Split preview is a placeholder on Receive payment | Stage 4 (Phase 3) | The partner earnings screen reads a one-off backfill (7 Oct). Every later repayment adds nothing to earnings (§3). |
| 4 | Restate history | Partial | Unchanged | Stage 4 (Phase 3) + Owner (broker-rate source) | |
| 5 | Penalty until payment; late payment clears penalty first | Broken | Unchanged. Screens show the current figure. | Stage 4 (Phase 2) | Affects every "owed today" (§3) |
| 6 | Rollover carries true outstanding from the last penalty day | Broken (regression) | Unchanged. The borrower extension preview uses `getRolloverPreview`, which differs from what `executeRollover` books. | Stage 4 (Phase 2) | The preview is labelled "Indicative", but on loan 529 it understates the booked amount by 1,226.40 (§3, S-B3) |
| 7 | `amount` = current principal; `original_amount` = first | Partial | Unchanged; disburse defaults to `original_amount`, else `amount` | Stage 4 (Phase 2) + Owner | The dashboard still sums `loans.amount` |
| 8 | Repayment belongs to a cycle of the same loan | Partial | Receive payment offers only the loan's own cycles | UI, but **Gap G3**: the server still validates only `exists:loan_cycles,id` | |
| 9 | Wallet handles | Planned only | `users.handle`, `partners.handle`, `businesses.handle`, backfill, shown in UI | Fixed | |

### 1.2 P0 findings

| # | Finding | Verdict | Where it was dealt with | Still open |
|---|---|---|---|---|
| 1 | Agreements downloadable without login | Fixed | A fix 1: private disk, authorised routes, `files:make-private` | Live: confirm `files:make-private` ran and `/storage/agreements/…` returns 404 |
| 2 | KYC files public; signatures guessable | Fixed | A fix 2. Business documents (Stage 2) are private too. | Live: as above. **G1**: broker access to the private files. |
| 3 | Permission leak (two role systems, OR-list routes) | Fixed | D fixes (Spatie only, per-action routes, `roles:sync`); Stage 2 tightened reject to `approve loans` | **G1** (broker `view users`), **G2** (status by edit), `/system` temporarily open to `admin` (§4) |
| 4 | Penalty and rollover maths | Stage 4 | Plan Phase 2 | Screens show the wrong figures until then (§3) |
| 5 | House #6 double-count of 4,420,156.64 | Fixed | C fix: `wallets:fix-house`, `checkPartners` covers every wallet | Live: confirm `wallets:fix-house` ran (runbook §4) |
| 6 | Signed agreement can change | Fixed | A fix 3: frozen PDF, SHA-256, signed-at, IP, user agent, version; shown on "Your loan terms" | Rebuild-on-missing still happens (logged). Acceptable. |
| 7 | Penalty clause "10% per day compounding" | Owner (legal) | Not addressed | **The engine's penalty is simple, not compounding** (B rule 5: `outstandingAtDue × rate × days`). Stage 2 docs call it "compounding". So the agreement text, the engine and `Loan::calculatePenalties` (capped at 100%) give three different definitions. The 114,000 on a 5,000 loan is 6,000 × 10% × 180 days, with no cap. In-duplum review is still needed. |

### 1.3 P1 findings

| # | Finding | Verdict | Notes |
|---|---|---|---|
| 8 | NPL detection broken, no scheduler | Fixed (B fix 2, 3) | Live cron must exist (runbook §5). `loans:update-statuses` still decides from due dates and ignores repayments, so the collections worklist can show loans that were paid but left `disbursed` (§3, S-S6). Stage 4 Phase 2 derives status from the cycle balance. |
| 9 | `active` status throws | Fixed (B fix 1) | |
| 10 | Non-cash counts as repaid; write-off → repaid; collections bypass ledger | Partly fixed: write-off → `written_off`; collection → real repayment | **The "repaid" rule itself is unchanged.** A collection of 5,000 on a 188,609 case debt marked loan 461 `repaid` on the clone, because Σ repayments ≥ `loans.amount` (44,352). That borrower now sees "Facility closed" and "Borrow again". `markAsRecovered` still sets `repaid` with no money. Stage 4 Phase 2, and the highest-impact item in the plan. |
| 11 | Partner contributions wrote to the wrong table | Fixed | |
| 12 | NULL `loan_id` on investment paths | Fixed | |
| 13 | No reject; cycle 1 at application; edit doesn't update cycle | Reject: Fixed and tightened (Stage 2). Cycle 1 and edit: Stage 4 + Owner (decision 12). | **G4**: the borrower copy says the due date starts when money is sent; the engine starts it at application. **G5**: the Stage 2 "reopen a rejected loan by editing it" path can change `amount` without updating cycle 1's stored `new_balance`. |
| 14 | Reports wrong | Mostly fixed (D: export, simulated data removed, cashflow direction, real cash columns) | The dashboard `loans.amount` sums are unchanged. Portfolio reports still read `loans.amount` (rule 7). Stage 4 Phase 7. |
| 15 | 17 broken routes; case buttons dead | Fixed (D; Stage 2 check: 232 routes resolve) | |
| 16 | Error pages, handler, maintenance | Fixed (A fix 4) | Live: confirm `APP_DEBUG=false`, `LOG_LEVEL` above debug |
| 17 | Email verification not enforced | **Gap → Stage 4 Phase 0** + Owner (decision 17) | The `verified` middleware on staff routes is still a no-op. |
| 18 | Audit log empty | Fixed (D: `AuditObserver`, `/audit-log`) | Retention: Owner (decision 23). Stage 2 also uses `audit_logs` as interim storage for claims and requests (Phase 5 replaces this). |

### 1.4 P2 findings

| Finding | Verdict | Notes |
|---|---|---|
| Customers can't repay in the app | UI (repay, "I've paid", receipts, closed) + Stage 4 Phase 4 (Daraja) | Claims live in the session and an email only; lost on sign-out |
| Nobody notified | Fixed for email (KYC, application, business); Stage 4 Phase 6 for approved, money sent, payment received, reminders, SMS | Claim and request emails go to `admin`/`super-admin` only. Tellers, who record the payments, get nothing (Owner). |
| CRB and financial assessment schema only | Owner (decision 20) | KYC still collects CRB consent for a check nobody makes. Either do the check or change the wording (Phase 0 copy fix). |
| Two scoring systems | Fixed (A fix 9) | |
| Limit floor always 5,000 | Owner (decision 18) | The written-off card says "limit stays at the starting limit". `written_off` is not in the "open loan" blocker list, so a written-off borrower with no open case can borrow 5,000 again (G7). |
| Recovery schema only (16 tables) | **Rejected for Stage 4**, except recovery payment plans | No backend, no owner demand, and the design has no agency, legal or skip-tracing screens in the Stage 2 scope. Keep the tables; revisit after Phase 5. |
| Dead or duplicate code | Mostly fixed | `app/Http/Kernel.php` and `admin/system/index.blade.php` remain. Delete both in Phase 0. |
| Write commands with no dry-run | Fixed | `loans:repair-cycles` still crashes. Phase 2 deletes it or rewrites it against the new cycle model. |
| Risk-scoring migration not applied locally | Live **Unknown** | The approval queue shows the 4Cs from `loan_risk_assessments`. If live never ran `2026_10_17_risk_scoring.sql` and `risk:assess`, the column is empty. Check. |

### 1.5 Module findings that AUDIT.md doesn't list

| Finding (module) | Verdict |
|---|---|
| Register has no throttle or captcha; phone not verified (A §2) | Stage 4 Phase 0 (throttle); OTP with Phase 6 (SMS) |
| `robots.txt` lacks `/account`, `/storage` (A §1) | Phase 0 |
| KRA PIN not unique; no KYC maker-checker; reviewer can review themselves (A §3) | Owner (decision 22) → Phase 5 |
| `PaymentMethod::methodType()` points at a missing class (A Stage 2) | Phase 0 |
| Product unit `year` miscomputed (B §1) | Phase 2 |
| `loan_types.processing_fee_rate` column missing → fee always 0 (B §1) | Phase 2 + Owner (decision 10) |
| Rollover custom `period_days` treated as the product unit (B rule 6) | Phase 2 |
| Forbearance moves `loans.due_date`, not the cycle's (B §3) | Phase 2 |
| `Loan::calculatePenalties`, a second penalty formula (B rule 5) | Phase 2 (delete; one formula) |
| `LoanCalculator::distributeRepaymentToPartners` uses Bavix `withdraw` (C) | Phase 3 (replace) |
| Editing investment fundings changes `amount_committed` with no journal (C) | Phase 3 |
| WalletsBootstrap decimals and role filter (C) | Phase 3 |
| Partner #5 broker-fee +116,519.99 repair, cause unknown (C) | Phase 3 restatement explains it, or Owner writes it off |
| Broker journey broken (no broker profile, `repayments.broker_id`) (D) | Owner: are brokers users at all? See plan §9. |
| RepaymentController edit/destroy also need role admin/broker (D) | Owner (open in SUMMARY). Replace with a permission in Phase 5 with maker-checker. |
| Many views decide buttons by `hasRole` (D) | Phase 0 sweep to `@can` (low risk) |
| `/clear-cache` is a GET (D S4) | Phase 0 (POST + CSRF) |
| `custom_css` / `custom_js` stored, never rendered (D S5) | **Rejected** (custom JS = stored XSS risk). Remove the fields in Phase 0. |
| Every older `database/sql/*.sql` has the `migrations` insert bug (D) | Phase 0 |
| SQL views unused (D P9) | **Rejected** as a reporting source. Phase 7 builds reports on the ledger. Drop the views then. |
| TailAdmin demo pages reachable by any staff (D U5) | **Check**; delete in Phase 0 if still routed |

---

## 2. Screens against the business rules and role permissions

Stage 2's own rule was that "no calculation, wallet or status rule changed". Each screen calls existing routes. So the screens obey the permissions exactly as well as the routes do, and they show the engine's numbers exactly as correctly as the engine computes them. The checks below follow from that.

| Screen | Rule check | Permission check | Verdict |
|---|---|---|---|
| Borrower repay, "I've paid" | Rule 5: amount due from LoanProgress (wrong after late payment). Claim amount capped at that due. | `role:borrower`, own loan, throttled | OK for on-time loans; wrong figure for late ones (S-B1) |
| Receipts | Book entries labelled "not a receipt"; no balance after | own loan | OK |
| Facility closed | "Repaid" comes from the broken rule (P1 #10) | own loan | **Wrong state possible** (S-B5) |
| Extension request | Rule 6 preview ≠ booking; new due date from the original due date | own loan, throttled | **Misleading figure** (S-B3) |
| Payment-plan accept | Plan stored at 0% but `new_balance` includes interest (B §3) | own loan | Figure may disagree with staff (S-B6) |
| Loan state cards | Pending copy contradicts the cycle 1 start (G4); overdue threshold from `calculateCycleBalance` while detection uses `getDefaultThresholdDays` (B fix 2) | own loan | Copy fix + Phase 2 |
| Statement of account | Cash only via `kind()`; "owed today" from LoanProgress | own loans | OK except "owed today" |
| Borrow (5 steps) | Quote and agreement interest come from different code (A §5, Unknown) | KYC approved | Phase 2 test: quote == agreement == cycle |
| Business KYC + review | Rules as E; approval `edit users` | **G1**: broker (`view users`) can list and open business reviews and files | Fix G1 |
| Partner home, withdraw, payouts | "Available" = Σ wallets including principal, but disbursements never move partner principal | `role:partner`, own data | **Overstated** (S-P1) |
| Partner funded loans | Includes brokered loans (`partner_id`) | own data | Mislabelled (G6) |
| Partner earnings | One-off backfill only | own data | Incomplete (S-P2) |
| Staff approvals | 4Cs advisory (decision 19 open) | Approve and Reject `approve loans` (route and screen) | OK. No maker-checker (Phase 5). |
| Staff disburse | Rule 2 guard; default amount rule 7 | `create disbursements` | OK. **G2**: the same people can skip this screen and set `disbursed` by edit. |
| Staff receive payment | Rule 8 in the UI only | `create repayments` | **G3** |
| Reversal | Soft delete + audit reason (not a true reversal) | `delete repayments` + role admin | OK as interim. Check: does end of day exclude soft-deleted rows? |
| Collections | Statuses from due dates only | `view recovery cases` | Stale-status noise (S-S6) |
| End of day | Cash rule via `kind()`; "entered by" from audit rows only | `create repayments`/`create disbursements`/`view reports` | OK, with a "since audit trail" caveat |
| Roles matrix | — | view: `manage roles`/`view audit log`; edit: `manage roles` (super-admin only, no user yet) | OK. `/system` still allows `role:admin` (§4). |
| Products, wallets | Read-only | as listed | OK. Note: the wallets check reconciling ≠ partner balances being right (S-S5). |

---

## 3. Screens showing a number the backend can't produce correctly

"Correct" means correct by the confirmed rules (AUDIT §1). Severity: **High** means a customer or partner could act on a wrong amount; **Medium** means staff could; **Low** is cosmetic.

| ID | Screen and figure | Why it is wrong | When it's wrong | Severity | Interim (Phase 0) | Fixed by |
|---|---|---|---|---|---|---|
| S-B1 | Borrower home / loan / repay / statement: **amount due now, owed today, penalty** | Rule 5: penalty runs to today, and a late payment reduces only the penalty | Any loan paid after its due date. Loan 529: shows 14,780 when 0 is owed | High | Repay screen: when a cash repayment is dated after the due date, show "We're confirming your balance" instead of a figure, plus a contact link | Phase 2 |
| S-B2 | Same figures, cycle 1 after a rollover | `calculateCycleBalance` uses the current `loans.amount` for cycle 1 | Rolled loans (30 loans). Loan 494 cycle 1 on 69,120, not 40,000. | Medium (history view) | none | Phase 2 |
| S-B3 | Extension: **new amount, new due date** | Preview uses `final_outstanding`; booking uses `new_balance` and starts on the due date | Every extension with a repayment or penalty. Loan 529: preview 16,258 vs booked 17,280 | High | Hide "new amount" and say "we'll confirm the cost before anything changes". Staff must check the rollover statement before executing. | Phase 2 |
| S-B4 | **Due date** on every borrower screen | Cycle 1 starts at application, not disbursement | Any loan approved after the application day | High (penalty starts early) | Copy fix (G4). Owner decision 12 first. | Phase 2 |
| S-B5 | **Facility closed**, "how it ended", Borrow again; limit ladder | "Repaid" = Σ all repayments ≥ `loans.amount`, or no active cycle | Loans with rollover, discount or bad-debt rows, or recovery collections (loan 461) | High | none safe without code. Phase 0 adds a nightly report of loans that are `repaid` with cycle balance > 0. | Phase 2 |
| S-B6 | Payment plan: **plan interest, amount** | Cycle saved at 0%, `new_balance` includes interest; computed vs stored differ | Every payment plan (5 forbearance loans) | Medium | Show the stored figure and label it "agreed amount" | Phase 2 |
| S-B7 | Overdue card: "**recovery after N days**" | Threshold from `calculateCycleBalance`; detection uses `getDefaultThresholdDays` | Loans with `npl_trigger_threshold` 0 and a product threshold set | Low | none | Phase 2 (one threshold) |
| S-P1 | Partner home, withdraw, partner payouts: **available balance** | Principal wallet holds contributions; lending it out never moves it | Any partner with lent-out capital. Demo: 400,000 withdrawable while 26,000 is lent. | High (could pay out lent capital) | Withdraw: cap at principal − drawn-not-returned (from `investment_fundings`) server-side, or relabel "Capital (including lent out)" | Phase 3 |
| S-P2 | Partner **interest earned, earnings, statement** | No per-repayment split since the 7 Oct backfill | Every repayment after 7 Oct (6 rows on the mirror, 83,760) | Medium | Label "Earnings up to 7 Oct 2026; newer earnings are being calculated" | Phase 3 |
| S-P3 | Partner investment **returned** | `recordFundingReturn` has no caller | Always 0 | Medium | Hide the column, or show "—" | Phase 3 |
| S-P4 | Partner **funded loans** count and amounts | Includes brokered loans | Broker-partners (ISIRO #5, Obiri #8) | Medium | Split into "Funded" and "Brokered" lists (G6) | Phase 3 |
| S-S1 | Staff dashboard: NPL total, monthly loans, interest | Sum `loans.amount` (P + I, current balance); interest = rate × amount | Always after any rollover | Medium | Label "recorded amount (includes capitalised interest)" | Phase 7 |
| S-S2 | Reports: portfolio, defaulted, cleared totals | `loans.amount` | After rollovers | Medium | Real-cash columns already added (D) | Phase 7 |
| S-S3 | Receive payment, approvals: **balance** shown for the loan | LoanProgress (S-B1) | Late loans | Medium | As S-B1 | Phase 2 |
| S-S4 | End of day: **entered by** | From audit `create` rows; no `created_by` column | Entries before 18 Oct | Low | Caption already says so | Phase 1 (`created_by`) |
| S-S5 | Wallets & reconciliation: "**0 issues**" | It checks wallets against the journal, and the journal has no per-repayment split | Always | Medium (false comfort) | Caption: "wallets match the journal; the journal is not yet complete" | Phase 3 |
| S-S6 | Collections tiles: **days late**, buckets | Status from due dates; paid-but-`disbursed` loans counted | Loans like #177, #200, #208, #217 | Medium | none | Phase 2 |

---

## 4. Role consistency

**Shared components.** Customer pages use `x-az.*` and staff pages use `x-staff.*`, with the same props. Status colours come from one token file (`resources/design/tokens.cjs`), and the status words from `config/az.php`. This is consistent. One gap: the staff `x-staff.status-badge` and the borrower badge use different words for the same status (disbursed shows as "Active" to customers). That is intended, but needs a sentence in DESIGN-TOKENS so later work doesn't "fix" it.

**Permission leaks and inconsistencies**

| ID | Leak | Who | Severity | Fix |
|---|---|---|---|---|
| G1 | `files.user` and `files.business` allow any staff role holding `view users` (or `view loans` for user files). The broker role holds both, so a broker can open every borrower's ID, selfie and signature, every business's documents, and the business-review list. | broker (also recovery-officer, auditor, which is probably acceptable) | **High** (Data Protection Act) | Remove `view users` from broker, or narrow file access to `edit users` + `view kyc documents`. Phase 0, with owner OK. |
| G2 | `LoanController` store/update let `approve loans` holders set `repaid` or `pending`, and `disburse loans` holders set `disbursed`, with no repayment or disbursement row | teller, manager, admin | **High** (money state without money) | Forbid `disbursed` and `repaid` via store/update. Only the disbursement and repayment paths change them. Phase 0. |
| G3 | `repayments.store` accepts any cycle id | anyone with `create repayments` | Medium | Validate cycle ∈ loan server-side. Phase 0. |
| — | `/system` allows `role:super-admin\|admin` until the super-admin exists | admin | Medium | After `users:super-admin` runs on live: back to `permission:access system settings`. Phase 0. |
| — | Reject route vs screen | — | Fixed in Stage 2 | — |
| — | Reversal needs role admin on top of the permission | manager, accountant blocked | Owner | Phase 5 |
| — | Views still use `hasRole('admin')` for buttons | inconsistent UI, not a leak (routes enforce) | Low | Phase 0 sweep |
| — | Claim and request emails go to admins only | tellers can't act on them | Low (operational) | Phase 4 staff claims queue |
| G8 | One person can approve and disburse the same loan (admin holds both) | admin | Medium (benchmark) | Phase 5 maker-checker |

**Mobile.** Every customer screen has a 390 px shot. On the staff side, only teller disburse and receive, and the recovery collections page, have phone shots. Approvals, inbox, end of day, roles, wallets, products and payouts were shot on desktop only. Wide tables use the scroll-in-box pattern (README). **Check** each staff page at 390 px in Phase 0. Tellers and recovery officers work on phones.

---

## 5. Benchmark re-check (AUDIT §4)

| Control | Audit (17 Oct) | Now (19 Oct) | Plan phase |
|---|---|---|---|
| Double-entry ledger, reversals not edits | Missing | Missing; reversal = soft delete + audit reason | 1 |
| Daily reconciliation incl. M-Pesa and bank | Partial | Partial. Borrower and partner wallets vs journal, on screen. No M-Pesa or bank. | 1, 4 |
| Maker-checker (approve, disburse, write-off) | Missing | Missing; placeholders on screens | 5 |
| Immutable audit trail | Missing | **Done** (observer, `/audit-log`); retention open | 0 (retention) |
| Arrears ageing, automated NPL | Broken | **Scheduled**; still ignores repayments | 2 |
| Least privilege, per action | Broken | **Mostly done**; G1, G2, G3 open | 0 |
| Private KYC and agreements (DPA 2019) | Broken | **Done** (confirm live) | 0 (confirm) |
| Tamper-evident agreements | Missing | **Done** | — |
| Products configured with approval | Partial | Read-only screen | 5 |
| Collections, restructure, promise-to-pay | Partial | Worklist done; restructure placeholder | 5 |
| CRB checks and reporting | Planned only | Unchanged | Owner → 8 |
| Customer notifications SMS/email | Missing | Email partly done | 6 |
| Interest and penalty caps, key-facts disclosure | Unknown | Unchanged; three penalty definitions (§1.2 P0-7) | 2 + legal |
| Data retention, consent records | Missing | Unchanged; payment-plan consent = an audit row | 0 (policy), 5 (consent table) |
| Backups, restore test, rollback | Partial | Backup button works; no restore test | 0 |
| Automated tests around money | Minimal (26, 4 failing) | 31 passing; **still none on money maths** | 0 (characterisation), 2 |
| Payments API (STK, C2B, B2C) | Missing | UI ready, flags off | 4 |

---

## 6. New findings from this review (G1–G8)

| ID | Finding | Phase |
|---|---|---|
| G1 | Broker can open all KYC and business documents | 0 |
| G2 | `disbursed` and `repaid` settable by edit with no money row | 0 |
| G3 | Repayment cycle ∈ loan enforced in the UI only | 0 |
| G4 | Borrower copy "due date starts when money is sent" contradicts the engine | 0 (copy), 2 (engine) |
| G5 | Reopening a rejected loan by editing the amount leaves cycle 1 stale | 0 (block amount edit on reopen, or rebuild cycle 1 while pending), 2 |
| G6 | Partner "funded loans" mixes in brokered loans | 0 (label), 3 |
| G7 | A written-off borrower can borrow again at 5,000 | Owner (decision 18), then 0 |
| G8 | One person can approve and disburse | 5 |
