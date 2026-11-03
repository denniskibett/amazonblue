# Aimzone — Review, Suggestions & Bank/MFI-Grade Comparison

**Date:** 17 October 2026 · **Scope:** the uploaded `Aimzone.zip` (code, `.env`, SQL dumps, storage, logs) · **Code changes made:** none
**Read with:** `SYSTEM_DOCUMENTATION.md` (what the system does today) and `Aimzone_tree.txt`.

The suggestions below are options, not changes. Section 7 lists the decisions that need your call before any code is touched.

Severity used throughout:

| Tag | Meaning |
|---|---|
| **P0** | Exposure of customer data or money being mis-stated right now. Fix before anything else. |
| **P1** | Produces wrong balances/statuses or lets one person move money without control. |
| **P2** | Needed for a controlled, auditable lender (MFI-grade). |
| **P3** | Polish, scale, bank-grade extras. |

---

## 1. Summary

Aimzone is a working, well-used lending back office (≈514 loans, ≈KES 25 M booked since Feb 2025) with some genuinely good pieces: an idempotent partner journal, a cash-only wallet shadow that reconciles, an explainable 4Cs scoring engine, a careful KYC flow with referee calls, a locked self-service application, and a deploy script that backs up before migrating.

Its core weakness is that **there is no single source of truth for money**. Balances are recalculated from cycles on every view; repayments, rollovers, discounts and write-offs are all stored as "repayments" and told apart only by the text staff type in the reference box; statuses are set by hand or by code that does not run; and nothing records who changed what. That is normal for a system that grew fast, but it is the main gap between this and a microfinance- or bank-grade core.

Five things stand out:

1. **Customer data is exposed** (P0): a full production database dump (users, password hashes, sessions, reset tokens, 514 loans) is committed to git and sits in the zip three more times; national ID scans, selfies, signatures and loan agreements are on the public web disk, and agreement URLs are guessable (`/storage/agreements/loan_agreement_2.pdf`).
2. **Arrears are invisible** (P1): NPL detection calls a method that does not exist and is not scheduled anyway. Live data has 0 overdue and 0 defaulted loans, while 15 disbursed loans are past due, the oldest by 13 months.
3. **Loans can be closed without being paid** (P1): a repayment ≥ `loans.amount` (principal only) marks the loan repaid; a write-off sets the loan to `repaid`; staff can set status to `repaid` directly.
4. **No controls** (P1/P2): no audit trail, no maker-checker, no approval step; the same user can create a loan as disbursed, record its money and close it.
5. **Pricing may breach CBK digital-lending rules** (P1, confirm with counsel): penalty is 10% of the overdue balance **per day** with no cap, and rollovers compound interest on interest.

---

## 2. P0 — do these first, whatever else you decide

| # | Finding | Evidence | Suggested action |
|---|---|---|---|
| 0.1 | Full live DB dump in git history | `git ls-files` lists `loans_main.sql` (989 KB, 91 users incl. password hashes, 11 sessions, 6 reset tokens) | Remove from repo **and history** (`git filter-repo`), force-push, rotate `APP_KEY` (invalidates sessions), force password resets, expire reset tokens. Treat as a possible personal-data breach under the Data Protection Act 2019 and decide with counsel whether ODPC notification applies (72-hour rule). |
| 0.2 | More dumps inside the project | `storage/app/backups/*.sql` ×3, `public_storage_stale_copy_20261015/` | Move off the server/laptop project folder into encrypted, access-controlled backup storage. Never zip them with the code. |
| 0.3 | KYC documents and agreements are publicly reachable | `HomeController.php:457-468` store to `public` disk; `LoanAgreementService.php:38` `agreements/loan_agreement_{id}.pdf` | Move IDs, selfies, signatures and agreements to the private `local` disk; serve through an authorised controller (owner or staff with permission), optionally with signed, short-lived URLs. Migrate existing files and delete the public copies. |
| 0.4 | Unrelated 35 MB `IdeaShare.pkg` in git | repo root | Remove from repo and history. |
| 0.5 | Request bodies and secrets in logs | `LoanController.php:146` `Log::debug('Loan Store Request Data', $request->all())`; 247 MB `laravel.log` with password/signature fields in places | Remove the debug dump; set `LOG_LEVEL=warning` in production; rotate (`daily` channel, 14-day retention); purge old logs. |
| 0.6 | Production hygiene | shipped `.env`: `APP_DEBUG=true`, `APP_ENV=local`; TailAdmin demo pages live under the staff area | Confirm live `.env` has `APP_DEBUG=false`; remove demo routes and `* copy*.php` files. |

---

## 3. Findings and suggestions by module

### 3.1 Loan engine: cycles, interest, penalty, rollover (`LoanCalculator`, `Loan`)

| Sev | Finding | Evidence | Suggestion (options) |
|---|---|---|---|
| P1 | Penalty is uncapped daily simple penalty: `outstanding × 10% × days`. After 10 days late it equals the whole balance; after 30 days it is 3× the balance. | `LoanCalculator.php:274`; every product has `penalty_rate = 10` | (a) Treat `penalty_rate` as a one-off % or a monthly %; (b) keep daily but **cap total interest + penalties at the outstanding principal once in default**, which is how the CBK Digital Credit Providers Regulations are reported to work (confirm with counsel); (c) both. |
| P1 | Rollover compounds: new principal = previous `new_balance` (principal + interest), and cash paid in the cycle is not subtracted | `LoanCalculator.php:669, 692` | Decide the rollover rule (see §7). Typical MFI rule: borrower pays the interest due, principal carries forward, new interest on principal only. |
| P1 | Rollover overwrites `loans.amount` with the new balance, so "loan amount" no longer means what was lent | `LoanCalculator.php:813` | Keep `loans.amount` = approved principal, forever. Current balance belongs on the cycle/ledger. |
| P1 | Rollover, payment plan and loan creation are multi-step writes outside a DB transaction | `executeRollover`, `createPaymentPlan`, `store` | Wrap each in `DB::transaction` with a row lock on the loan. |
| P1 | Payment-plan edit updates any `cycle_id` from the request without checking it belongs to the loan | `LoanController.php:1101-1108` | Scope the lookup: `$loan->cycles()->findOrFail(...)`. |
| P2 | Interest is flat per period and booked up front; no daily accrual, no allocation waterfall | `calculateCycleBalance` | Introduce an explicit allocation order for each cash receipt (penalty → fees → interest → principal is common) and store the split on the repayment. |
| P2 | Floats for money in PHP | throughout | Use integer cents or `brick/math` (already installed via bavix) / `moneyphp` (installed, unused) in the calculator. |
| P2 | "Is this a payment plan?" is detected by searching cycle notes for text | `LoanCalculator.php:200-203` | Add a `cycle_type` column (`standard, rollover, payment_plan, manual_adjustment`). |
| P3 | Duplicate due-date switch logic in 4 places | `calculateDueDate`, `calculateRolloverDueDate`, `executeRollover`, `BorrowingEligibility::quote` | One `ProductSchedule` helper. |

### 3.2 Repayments (`RepaymentController`, `RepaymentService`)

| Sev | Finding | Evidence | Suggestion |
|---|---|---|---|
| P1 | Loan marked **repaid when repayments ≥ `loans.amount`** (principal only; interest and penalty ignored), or when no cycle is active | `RepaymentController.php:96-104` | Close a loan only when the ledger/cycle outstanding (incl. interest and penalty) is ≤ 0. Use the existing `RepaymentService` (and fix it: it compares repayments with disbursements only). |
| P1 | No duplicate check on the transaction reference at create (only on update); at least 4 M-Pesa codes appear on two different loans | `RepaymentController.php:71, 76`; dump | Unique index on `(source, external_ref)` for real cash; show a warning for possible split payments. |
| P1 | Cycle id is not checked against loan id; amount may be 0 | `RepaymentController.php:66-72` | Validate cycle belongs to loan; `min:0.01`. |
| P1 | Non-cash events (rollover, discount, bad debt) are stored as repayments and identified by words in the reference; "BROKER FEE(S)" (22 rows) and "TO BE PAID" count as **cash** | `CustomerWallets.php:36-40`; dump | Add a typed `entry_type` (`cash_receipt, rollover_transfer, discount, write_off, reversal, fee_adjustment`) and stop inferring from text. One-off migration to classify history. |
| P1 | Recording a repayment does not post the partner/broker share; that happens later via backfill commands | §4.12 of docs | Post partner shares in the same transaction as the receipt (via `PartnerLedger::post`, which is already idempotent). |
| P2 | Repayments can be edited and deleted | resource routes | Make cash entries immutable; corrections by reversal + re-entry, with reason and approver. |
| P2 | Broker filter queries `loans.broker_id`, a column that does not exist | `RepaymentController.php:28, 55, 149, 162, 179` | Use `partner_id` / borrower→broker relation. |

### 3.3 Disbursements (`DisbursementController`, `DisbursementService`)

| Sev | Finding | Suggestion |
|---|---|---|
| P1 | Any loan in any status can receive a disbursement; no limit vs approved amount; no reference uniqueness | Allow only `approved` loans; enforce Σ disbursed ≤ approved; unique reference. |
| P1 | No approval between "approved" and "money sent" | Maker-checker: one user requests, a second releases (§3.8). |
| P2 | Payout happens outside the system; staff type the M-Pesa code | M-Pesa B2C (Daraja) payout from the system, with the callback writing the disbursement. |
| P3 | Investment-only disbursements live in the same table as loan disbursements | Separate "funding movements" from "customer disbursements" once a ledger exists. |

### 3.4 Arrears, NPL and statuses (`NplDetectionService`, commands)

| Sev | Finding | Suggestion |
|---|---|---|
| P1 | `updateNplStatus()` does not exist → detection never flags anything | Implement it (days past due from the active cycle, set `overdue`/`defaulted`, open case). |
| P1 | Schedule is in `app/Console/Kernel.php`, which Laravel 12 ignores; no cron | Move schedules to `routes/console.php` (`Schedule::command(...)`) and add the CyberPanel cron `* * * * * php artisan schedule:run`. Add `loans:update-statuses` daily. |
| P1 | `active` and `written_off` statuses are used in code but not in the DB enum | Add them to the enum (or switch to a string column + PHP enum). |
| P2 | No ageing buckets or provisioning | Daily snapshot: DPD, bucket (current, 1-30, 31-60, 61-90, 90+), CBK class (Normal / Watch / Substandard / Doubtful / Loss), provision %. Feeds PAR reports. |
| P2 | Statuses can be typed in by staff on create/edit | Status changes only through actions (approve, reject, disburse, roll over, write off) that check the current state. |

### 3.5 Debt recovery (`CaseController`)

| Sev | Finding | Suggestion |
|---|---|---|
| P1 | Write-off sets the loan to **`repaid`** — written-off borrowers look like good payers in reports and the eligibility rule `BAD_HISTORY_STATUSES` never sees them | `CaseController.php:799` → set `written_off`; keep the balance; track post-write-off recoveries. |
| P1 | Write-off allowed to legacy `role === 'admin'` alone, no second approver | Permission `write off loans` + maker-checker + amount threshold. |
| P2 | `cases/my`, `cases/stats`, `cases/export/csv` unreachable (route order); two routes point to missing methods | Declare static routes before `Route::resource('cases')`; rename/implement methods. |
| P2 | ~20 recovery tables are empty scaffolding (agencies, court, legal, skip tracing, templates…) | Decide which you will actually use (§7); drop or park the rest so the schema reflects reality. |
| P2 | Collections conduct | Build in the DCP conduct rules: contact only the borrower and consented referees, log every contact (who, when, channel, outcome), no messages to other contacts, scripted templates. |

### 3.6 KYC & onboarding

| Sev | Finding | Suggestion |
|---|---|---|
| P0 | ID images on public disk | §2, item 0.3 |
| P2 | Identity is verified by staff phone calls only | Add IPRS ID check (via an aggregator such as Smile ID / Metropol / IPRS partner), M-Pesa name match on the phone number, and CRB check before first loan. |
| P2 | No AML screening | Sanctions/PEP screening at onboarding; flag unusual repayment sources; record risk rating. |
| P2 | Agreement "signed" by re-using the KYC signature image | Capture a fresh acceptance per loan: OTP to the borrower's phone + IP/device + timestamp + agreement hash, stored with the PDF. |
| P3 | Data minimisation | Set retention periods and purge rules for rejected applicants and closed accounts. |

### 3.7 Scoring & limits (`CreditAssessment`, `BorrowingEligibility`)

This is one of the stronger modules. Suggestions are P2/P3:
- Feed CRB data in (positive and negative) once the CRB integration exists.
- Back-test: compare scores at application with outcomes (the as-at-borrow-date assessments make this possible).
- Store the model version and inputs with each assessment so a decision can be explained later.
- The `written_off` fix in §3.5 matters here, otherwise character scores are inflated.

### 3.8 Controls: audit trail, maker-checker, roles

| Sev | Finding | Suggestion |
|---|---|---|
| P1 | `audit_logs` table exists, never written | Model observers (or `spatie/laravel-activitylog`) on loans, cycles, repayments, disbursements, partners, users, roles, system settings: who, when, IP, before/after. Append-only. |
| P1 | No maker-checker anywhere | Pending-action table: approve loan, release disbursement, write-off, discount, manual balance, rollover with custom rate, repayment reversal. Maker ≠ checker, limits by role. |
| P2 | Two role systems (legacy `users.role` + Spatie) | Move every check to Spatie permissions; drop the column after migration. |
| P1 | `ON DELETE CASCADE` from users and loan types down to loans, cycles, repayments, disbursements | Change to `RESTRICT`; soft-delete only; financial rows never deleted. |
| P2 | No 2FA for staff | TOTP 2FA for all staff roles; shorter idle timeout (e.g. 15–30 min) for staff sessions; `SESSION_ENCRYPT=true`. |

### 3.9 Partners, brokers, investments

- Good: `PartnerLedger::post()` is atomic and idempotent with a DB unique key; wallets reconcile with `wallets:check`.
- P1: shares are posted in batch by backfill commands rather than at receipt → partner statements lag and depend on someone running commands. Post at receipt (see §3.2).
- P2: `distributeRepaymentToPartners()` and `returnPrincipalToPartners()` call `$broker->withdraw(amount, 'string', 'string')`, which does not match the bavix signature and is on a `Broker` model without wallets; they are dead code — remove or rewrite on `PartnerLedger`.
- P2: three investment routes point to missing methods.
- P3: partner statement PDF + monthly e-mail.

### 3.10 Reports & dashboard

- P1: every report that groups by status is currently wrong in the same direction (no overdue/defaulted, write-offs counted as repaid). Fix §3.4/§3.5 first, then re-check reports.
- P2: `/reports/export` unreachable (route order).
- P2: add the standard MFI metrics: PAR1/PAR30/PAR90, collection efficiency, portfolio yield, write-off ratio, rollover rate, vintage curves by month of disbursement, cost of risk.
- P2: CRB "report" is a layout; a real CRB submission needs the bureau data specification and monthly upload.

### 3.11 Platform & operations

| Sev | Finding | Suggestion |
|---|---|---|
| P1 | PHP mismatch: lock requires ≥ 8.4.1, `composer.json` says ^8.2 | Pin `"php": "^8.4"` and confirm the CyberPanel lsphp version. |
| P2 | Timezone UTC in settings | `Africa/Nairobi` for `config/app.php` and display; store UTC in DB. Due dates and "days overdue" around midnight depend on this. |
| P2 | 17 routes point to missing methods (docs §10) | Implement or remove. |
| P2 | No staging environment; dev uses copies of live data | A staging site with an anonymised dataset; never real IDs on laptops. |
| P2 | Backups: good pre-migration step; no evidence of off-site copies or restore tests | Daily encrypted off-site backups (S3/Backblaze) + monthly restore test. |
| P2 | No tests for money logic | Unit tests for calculator (interest, penalty, cap, rollover, allocation), repayment closing, eligibility, wallet sync. Run in CI (GitHub Actions) on every push. |
| P3 | Fat controllers (LoanController 2,042 lines, CaseController 1,453) | Move to action classes per use case (ApproveLoan, RecordRepayment, RollOverLoan…). |
| P3 | No SMS/e-mail notifications at all (no Notifications/Jobs) | Africa's Talking SMS: approval, disbursement, due-in-2-days, overdue, receipt, statement. |

---

## 4. Bank-grade / microfinance-grade comparison

Scoring 0–5 against what a **Kenyan credit-only MFI / licensed digital credit provider** would be expected to run, with the bank-grade bar noted where it differs. Scores are a judgement from the code and data reviewed.

| # | Capability | MFI / DCP-grade expectation | Bank-grade adds | Aimzone today | Score |
|---|---|---|---|---|---|
| 1 | **Accounting core** | Double-entry general ledger, chart of accounts, every money event = balanced journal; trial balance; month-end close | Real-time GL, multi-entity, IFRS reporting, automated EOD | No GL. Balances recomputed from cycles. Partner journal single-sided; borrower wallet = cash shadow. `loan_ledger` unused | **1** |
| 2 | **Transaction integrity** | Typed transaction codes, idempotency keys, no edit/delete (reversals only), atomic posting, decimal math | Dual control on reversals, hash-chained journals | Free-text references decide cash vs non-cash; edits/deletes allowed; cascade deletes; floats; some writes outside transactions. Wallet/partner sync is idempotent (good) | **1.5** |
| 3 | **Product & schedule engine** | Configurable products, schedule (bullet/instalment), interest method, fee/penalty rules with caps, allocation waterfall, total-cost-of-credit disclosure | Daily accrual, EIR/APR, amortisation variants, holiday calendars | Products table with period/rate/penalty; flat interest; uncapped daily penalty; compounding rollovers; no waterfall | **2** |
| 4 | **Loan lifecycle state machine** | Statuses change only via controlled actions with valid transitions | Same, with workflow engine | Status is a free field on create/edit; DB enum missing values; write-off → repaid | **1** |
| 5 | **Segregation of duties / maker-checker** | Approve ≠ disburse ≠ record; approval limits by role; dual control on write-offs and adjustments | 4-eyes on all money movement, limit matrices | None | **0.5** |
| 6 | **Audit trail** | Immutable who/what/when/before/after on all financial and master data | Tamper-evident, SIEM-fed, retained 7+ years | Table exists, never written | **0.5** |
| 7 | **Onboarding & KYC/AML** | ID verification (IPRS), CRB check, sanctions/PEP, risk rating, consent records | Biometric, continuous screening, transaction monitoring | 4-step KYC, documents, income, referees, staff calls before approval (good process); no automated checks; files public | **2.5** |
| 8 | **Credit scoring & limits** | Documented scorecard, CRB data, limit rules, monitoring | Statistical/ML models, validation, model governance | Explainable 4Cs scorecard, as-at-date assessments, limit ladder with risk caps (strong for this stage); no CRB input; not validated | **3** |
| 9 | **Payment channels** | M-Pesa B2C payout, C2B/Paybill + STK push collection, automatic matching | Bank rails (PesaLink/RTGS/EFT), card, multi-PSP failover | Manual payout and manual entry of codes | **1** |
| 10 | **Reconciliation** | Daily M-Pesa/bank statement reconciliation, suspense handling | Automated multi-source recon, breaks workflow | `wallets:check` reconciles wallets vs source rows (good internal check); no external statement recon | **2** |
| 11 | **Arrears, NPL & provisioning** | Daily DPD, ageing buckets, PAR, CBK classification, provisioning, write-off policy, recoveries after write-off | IFRS 9 ECL staging & models | Detection broken and not scheduled; 0 overdue in data; no buckets; no provisioning | **0.5** |
| 12 | **Collections & recovery** | Case workflow, contact log, promise-to-pay, payment plans, agency hand-off, conduct rules | Dialler, segmentation, legal workflow | Case module with actions/notes/recover/write-off; payment plans; big unused schema | **2.5** |
| 13 | **Customer communication** | SMS receipts, reminders, statements, complaints channel | Omni-channel, preferences | None automated | **0.5** |
| 14 | **Regulatory & bureau reporting** | Monthly CRB submission (positive + negative), CBK returns as licensed, ODPC registration | Full prudential returns, stress tests | CRB-style report view only | **1** |
| 15 | **Security** | MFA for staff, least privilege, private document storage, encryption of PII, secrets out of repo, logging without PII, patching | Pen tests, WAF, HSMs, SOC | No MFA; public KYC files; DB dumps in git; PII in logs; Spatie permissions (good base) | **1** |
| 16 | **Data protection (DPA 2019)** | Registered controller/processor, DPIA, consent, retention, breach process, data subject rights | DPO, privacy engineering | Consent checkbox; no retention or access controls on documents | **1** |
| 17 | **Operations & resilience** | Scheduler & queues monitored, staging, off-site backups with restore tests, uptime monitoring | DR site, RTO/RPO targets, change advisory | Thoughtful deploy script with pre-migration backup; no scheduler; no staging; backups in project folder | **2** |
| 18 | **Testing & change control** | Unit tests on money logic, CI, code review | Regression suites, UAT sign-off | Breeze scaffolding tests only | **0.5** |
| 19 | **MIS & reporting** | PAR, yield, collection efficiency, vintages, partner statements | Board packs, regulatory MIS | Many reports and a dashboard; accuracy limited by status issues | **2.5** |
| 20 | **Funding / investor accounting** | Investor ledgers, allocation of loans to funds, profit share, statements | Treasury, ALM | Idempotent partner journal + wallets, house accounts, fundings (good base); postings batch-run | **2.5** |
| 21 | **Customer self-service** | Apply, see balance/schedule, receipts, statements | Full digital banking | Mobile-first account with limit, quote, progress timeline, agreements (good) | **3.5** |

**Overall:** roughly **1.7 / 5** against an MFI/DCP bar. The customer-facing and scoring layers are ahead of the financial core and controls. The biggest single lever is #1–#6: a ledger with typed, immutable entries and controlled status transitions. Most other gaps (PAR, provisioning, CRB, partner statements) become straightforward once that exists.

### What already meets or beats the MFI bar
- Partner journal posting: atomic, idempotent, unique-keyed.
- Cash-only wallet shadow with idempotent sync and a reconciliation command.
- 4Cs scoring with as-at-date assessments and explainable limits.
- KYC process design (structured steps + referee calls before activation).
- Double-submit protection on applications (row lock).
- Deploy script: pre-flight checks, maintenance mode, backup-before-migrate.

---

## 5. Kenyan regulatory points to check (not legal advice)

These come from public summaries of the CBK (Digital Credit Providers) Regulations 2022 and related law; confirm each with your advocate and against the gazetted text.

| Area | Reported rule | Where Aimzone stands |
|---|---|---|
| Licensing | Non-deposit-taking lenders offering credit through digital channels must be licensed by CBK as DCPs; new products/pricing changes need CBK approval | Confirm licence status and that all 10 live products and the rollover/penalty rules are the ones approved |
| Pricing on default | Interest/charges after default reportedly cannot exceed the principal owing | Penalty is uncapped (10%/day) and rollovers compound → **likely non-compliant** |
| Disclosure | Disclose all fees, interest rate and total cost of credit before the borrower accepts | Quote shows interest and total for the self-service product; penalty disclosed as a rate; no total cost of credit with penalties/rollover illustrated |
| Collections conduct | No threats, shaming, abusive language, posting borrower data, or messaging the borrower's contacts | No tooling either way; build contact logging and templates (§3.5) |
| Contacts | DCPs may not access the borrower's phone book | Compliant: only two consented referees are captured |
| CRB | Defaults ≤ KES 1,000 are not listed; 30-day notice before negative listing | No CRB integration yet; build these rules in when adding it |
| Data protection | Register with ODPC, lawful basis, security of personal data, breach notification | P0 items in §2 |
| AML (POCAMLA) | Customer due diligence and record keeping | Manual KYC only |

Sources: [The Standard – CBK rules on digital lenders](https://www.standardmedia.co.ke/evewoman/article/2001440939/central-bank-rescues-borrowers-from-fangs-of-digital-loan-sharks), [Capital FM – lenders barred from customers' contacts](https://test.capitalfm.africa/digital-lenders-barred-from-accessing-customers-contacts-under-new-law/), [Business Daily – ODPC asks CBK to revoke permits for two digital lenders](https://www.businessdailyafrica.com/bd/economy/dpo-asks-cbk-to-revoke-permits-for-two-digital-lenders-5162924).

---

## 6. Suggested roadmap (for discussion)

| Phase | Goal | Contents | Rough effort* |
|---|---|---|---|
| **0 · Contain** (days) | Stop exposure | §2 items 0.1–0.6; rotate keys; private disk for documents | 2–4 days |
| **1 · Stop wrong numbers** (1–2 weeks) | Statuses and balances you can trust | Fix repayment close rule, write-off status, DB enum, NPL method + scheduler + cron, missing routes, route order, transactions around rollover/plan/create, cascade → restrict, cycle/loan ownership checks; tests for each | 1.5–2 weeks |
| **2 · Ledger & controls** (3–6 weeks) | MFI-grade core | Typed immutable entries (or a proper double-entry GL), allocation waterfall, penalty cap, rollover rule, status state machine, audit log, maker-checker, staff 2FA, Spatie-only roles; one-off classification of historical entries | 3–6 weeks |
| **3 · Channels & bureau** (3–5 weeks) | Less manual work, compliance | M-Pesa B2C/C2B/STK with callbacks and statement recon, SMS (Africa's Talking), CRB submission + checks, IPRS/KYC provider | 3–5 weeks |
| **4 · Risk & MIS** (2–4 weeks) | Run the book like an MFI | Daily DPD snapshots, PAR, CBK classification, provisioning, vintages, partner statements, staging env, off-site backups + restore tests, CI | 2–4 weeks |

*Single experienced Laravel developer; ranges, not quotes.

---

## 7. Decisions for you (needed before any update)

1. **Data incident handling (P0):** rewrite git history and rotate secrets now? Involve counsel on whether the exposure needs ODPC notification?
2. **Ledger approach:** (a) add a proper double-entry general ledger inside the app, (b) keep cycles but add a typed, immutable `loan_entries` sub-ledger (lighter), or (c) move the core to an off-the-shelf loan system (e.g. Mifos/Fineract, Musoni, or a Kenyan SaaS) and keep this app as the customer/partner front end?
3. **Penalty rule:** one-off late fee, monthly %, or daily % with a cap? What cap (e.g. total charges ≤ outstanding principal)?
4. **Rollover rule:** must the borrower pay interest to roll over? Is interest on the new cycle charged on principal only, or on principal + unpaid interest? Maximum number of rollovers?
5. **Write-off policy:** at how many days past due; who approves; do write-offs keep accruing; how are later recoveries recorded?
6. **Approval matrix:** who can approve loans and up to what amount; who can release disbursements; who can discount/write off; must they be different people?
7. **Historical data:** reclassify the 239 rollover / 83 discount / 13 bad-debt / 22 broker-fee / "TO BE PAID" repayment rows into typed entries, and restate the 13 written-off loans from `repaid`? Re-age the 15 past-due loans now?
8. **Recovery scope:** which of the empty recovery features (agencies, legal/court, skip tracing, templates, instalment plans) will you actually use? Drop the rest?
9. **Integrations and order:** M-Pesa (B2C, C2B, STK), SMS provider, CRB bureau(s), ID verification provider — which first?
10. **Licensing:** is the business licensed as a DCP (or under another regime)? Are the 10 live products the approved ones?
11. **Delivery format:** should fixes come as full replacement files per module (your usual preference) in phase order, with tests included?
