# Amazonblue Capital — System Documentation (current state)

**Codebase:** `amazonblue` (git HEAD `457a703`, 34 commits)
**Live at:** loans.sharet.africa (CyberPanel)
**Documented:** 17 October 2026, read-only review of the uploaded zip. Nothing in the code was changed.
**Companion files:** `amazonblue_tree.txt` (full file tree + planned additions), `REVIEW_AND_RECOMMENDATIONS.md` (findings, suggestions, bank/MFI-grade comparison) and `WORKPLAN.md` (requests, open questions, change log). User journeys by role are in §12.

This document describes **what the system does today**, as the code is written. Where the code and the intent seem to differ, it says so and marks the module status. Recommendations are kept out of this file and go in the review document.

Status legend used below:

| Status | Meaning |
|---|---|
| ✅ Working | Code path is complete and wired to routes/UI |
| 🟡 Partial | Works, but with gaps or logic that may produce wrong numbers |
| 🔴 Broken | Route or call points to code that does not exist, or the logic cannot run |
| ⚪ Scaffold | Tables/models exist but nothing uses them |

---

## 1. What the system is

Amazonblue is a **short-term consumer lending and debt-recovery platform** for a Kenyan non-bank lender (brand: *Amazonblue Capital / Aimzone Ventures*, currency KES). It covers:

- a public marketing website with enquiry form
- borrower self-registration, a 4-step KYC flow, and self-service loan applications
- a staff back office: loan origination, disbursements, repayments, rollovers, payment plans, forbearance
- a broker/partner model where outside partners fund or introduce loans and share the interest
- house accounts and an "investments" book that tracks where lending money comes from
- a 4Cs credit-scoring engine that sets borrowing limits from repayment history
- non-performing loan (NPL) detection and a debt-recovery case module
- reports (portfolio, CRB-style, expected repayments, cash flow) and a staff dashboard
- PDF loan agreements with captured signatures

### Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12, PHP (lock file requires **PHP ≥ 8.4.1**; `composer.json` says `^8.2`) |
| DB | MySQL/MariaDB (live server reports MariaDB 10.3.39) |
| Front end | Blade, Alpine.js, Tailwind (Vite build committed to `public/build`) |
| Roles/permissions | `spatie/laravel-permission` v8 |
| Wallets | `bavix/laravel-wallet` v11 (balances in integer minor units) |
| PDF | `tecnickcom/tcpdf` |
| Backups | `spatie/laravel-backup` |
| Other | `moneyphp/money` and `league/iso3166`, `pragmarx/countries` (installed; Money is not used in app code) |
| Sessions/queue/cache | database driver |
| Mail | `log` driver in the shipped `.env` (no real e-mail sending) |

### Size

| Area | Count |
|---|---|
| Files (excl. vendor/node_modules/.git) | 821 files, 138 folders |
| Controllers | 14 main + 10 auth (LoanController alone is 2,042 lines) |
| Services | 12 (LoanCalculator 1,427 lines) |
| Models | 57 |
| Migrations | 79 (create 63 tables + 7 SQL views + 2 recent alterations) |
| Console commands | 13 |
| Blade views | ~265 files |
| Automated tests | Breeze defaults only (auth + profile); no lending tests |

---

## 2. Folder layout (what lives where)

```
app/
  Console/Commands/      13 artisan commands: NPL detection, status updates, cycle repair,
                         backfills for partner/house journals, wallet bootstrap/sync/check
  Console/Kernel.php     schedule definitions (NOT loaded by Laravel 12, see §9)
  Helpers/               Country, Phone, System helpers + global helpers.php
  Http/Controllers/      Loan, Disbursement, Repayment, Case (recovery), Partner, Investment,
                         Report, Dashboard, User, KycReview, Home (website + customer area + KYC),
                         Profile, Admin/SystemController, Auth/*
  Http/Middleware/       EnsureStaff, CheckRole, CheckMaintenanceMode
  Models/                57 Eloquent models
  Pdf/LoanPDF.php        TCPDF wrapper
  Services/              business logic (see §4)
  Support/Banks.php      list of Kenyan banks for KYC income step
config/lending.php       self-service product id, limits, risk caps
config/wallet.php        bavix wallet config
database/migrations/     schema (tables + reporting views)
database/seeders/        roles/permissions, lookup tables, house accounts, demo users (non-prod)
resources/views/         staff dashboard (TailAdmin template), customer area (home/account),
                         KYC (home/kyc), public site (home/*)
routes/web.php           all web routes (no API routes)
deploy.sh                CyberPanel deploy script
loans_main.sql           full phpMyAdmin dump of the live DB (in git)
storage/app/backups/     3 more full DB dumps + a copy of public storage
IdeaShare.pkg            35 MB macOS installer package (unrelated to the app, in git)
```

The full tree with file sizes is in `amazonblue_tree.txt`.

---

## 3. Users, roles and access

### 3.1 Roles

Seeded by `RoleSeeder` (Spatie): `super-admin`, `admin`, `manager`, `loan-officer`, `accountant`, `recovery-officer`, `auditor`, `teller`, `broker`, `partner`, `borrower`.

There are **two role systems running side by side**:

1. **Spatie roles/permissions** (`model_has_roles`) — used by route middleware (`permission:view loans`, etc.) and `$user->can()`.
2. **A legacy `users.role` column** — enum `admin | borrower | broker | teller | partner`. Many controller checks still use it directly (e.g. `auth()->user()->role === 'admin'` in rollover, write-off, loan index, repayment index). Registration keeps both in step for borrowers.

Effect: a user with Spatie role `manager` or `super-admin` but legacy column `teller` can be blocked from actions that check the column, and vice versa.

### 3.2 Route zones

| Zone | Prefix | Guard | Who |
|---|---|---|---|
| Public website | `/`, `/{page}`, `/contact` | none (enquiry throttled 6/min) | anyone |
| Auth | `/login`, `/register`, password reset, email verify | Breeze | anyone |
| Google login | `/auth/google*` | — | 🔴 controller is empty, Socialite not installed |
| KYC | `/kyc/*` | `auth`, `role:borrower` | borrowers |
| Customer account | `/account/*` | `auth` (+ `role:borrower` / `role:partner`) | borrowers & partners |
| Staff dashboard | everything else | `auth`, `verified`, `staff` + per-route `permission:*` | staff roles |

`EnsureStaff` sends any user who is only a borrower/partner to `/account`. Per-route permissions are Spatie permissions (`view loans`, `create repayments`, `rollover loans`, `grant forbearance`, `edit recovery cases`, `access system settings`, …).

There are no Laravel Policies; record-level checks (e.g. "is this borrower one of this broker's clients") are hand-written in a few controller methods.

---

## 4. Modules

### 4.1 Public website — ✅ Working
`HomeController@index/page/robots/sitemap/storeEnquiry`, views in `resources/views/home/*`.
Pages: company, services, emergency-solutions, asset-finance, commodities, crb-solutions, careers, contact, privacy, terms, disclaimer, sitemap. Contact form saves an `Enquiry` (name, organisation, email, investor type, allocation, message) and is rate limited.

### 4.2 Registration & authentication — 🟡 Partial
Laravel Breeze. Registration creates a `borrower` (legacy column + Spatie role). Password rules: `Password::defaults()`. Login throttling is Breeze's default (5 attempts). Email verification is required for the staff dashboard. No two-factor authentication (a `two_factor_auth: false` flag exists in system settings JSON but nothing reads it). Google login routes exist but the controller has no methods. Session lifetime 120 minutes, sessions not encrypted.

### 4.3 Borrower KYC — ✅ Working (manual verification)
Routes `/kyc/personal → documents → income → contacts → review → submitted` (`HomeController`), staff review in `KycReviewController`.

| Step | Captures |
|---|---|
| Personal | first/last name, national ID number (unique), DOB, phone, address |
| Documents | ID front/back, selfie/photo, signature (drawn or uploaded image). Files: jpg/png/webp/heic/pdf up to 10 MB |
| Income | employment type, employer/business, net salary, years, bank + account |
| Contacts | next of kin and guarantor (name, phone, relationship) |
| Review/submit | sets `kyc_status = submitted` |

Staff review (`/kyc-reviews`, permission `edit users`): approve requires ticking "called next of kin" and "called guarantor"; records reviewer, time, notes, and activates the borrower. Reject sends the borrower back. Existing clients must go through it once. No automated ID check (IPRS), no phone/SIM check, no sanctions/PEP screening.

**Storage:** ID images, selfies, signatures and generated agreements are saved on the `public` disk (`storage/app/public/...`), which is web-served through the `/storage` symlink. Agreements use predictable names `agreements/loan_agreement_{id}.pdf`.

### 4.4 Customer account (borrowers & partners) — ✅ Working
`/account` (mobile-first website design):
- **Borrower:** home, Borrow page (limit, quote, blockers), My loans, loan detail "progress drawer" (`LoanProgress` service: Applied → Approved → Sent to M-Pesa → Repaid timeline, balance, cycles, repayments), agreement download, profile.
- **Partner:** statement page.

### 4.5 Self-service borrowing, limits & 4Cs scoring — ✅ Working
`LoanController@apply`, `BorrowingEligibility`, `CreditAssessment`, `config/lending.php`.

- Single product for self-service: `lending.borrower_loan_type_id` (default 1, "Emergency 10 Days", 20% per 10 days).
- **Blockers:** no borrower profile, KYC not approved, an open loan (pending/approved/disbursed/overdue/defaulted/recovery/forbearance), an open recovery case, product missing.
- **Limit ladder:** first-time KES 5,000; good standing KES 50,000 (last loan repaid on time in cash, no rollover/discount, nothing ever written off or in recovery). Then capped by risk category (low 50k / medium 30k / high 10k / very high 5k) and declared income × `income_multiple` (1.0). Minimum KES 1,000.
- **4Cs score (0–100):** Character 40% (how past loans ended), Capacity 25% (income vs amount, largest loan repaid), Capital 15% (tenure, loans repaid, cash in vs out), Conditions 20% (open/overdue loans, rollover streaks, recovery). Every loan gets an "as-at borrow date" assessment; the borrower is re-scored after any loan/repayment/disbursement change (deferred to end of request).
- Application runs in a DB transaction with a row lock on the user to stop double submission; then creates cycle 1 and generates the agreement **using the signature already on file from KYC**.
- Application goes in as `pending`; staff approve and disburse manually.

### 4.6 Loan products (loan types) — ✅ Working
Table `loan_types`: name, period + unit (days/weeks/months/years), interest rate (flat % per period), penalty rate, grace days, default threshold days. Live data has 10 products, e.g. *Emergency 10 Days 20%*, *Quick Loans 1 month 20%*, *Quick Loans 14 days 20%*, *Quick Loans 3 months 95%*, *Emergency 2 weeks 35%*. Penalty rate is 10 on every product.

### 4.7 Staff loan origination — 🟡 Partial
`LoanController@store/update/edit/destroy/index/show`.
- Staff (admin/teller) choose borrower, product, amount, borrow date, **status** (`pending | approved | disbursed | repaid` on create; also `overdue | defaulted` on edit), broker flag, guarantor, loan officer, reason, consent, optional drawn signature.
- Duplicate guard: if the borrower has open loans the API returns 409 with a summary; the UI can resend with `X-Force-Create: true` to create anyway.
- Brokers can only create loans for their own clients.
- Loan creation, cycle creation and agreement generation are separate steps (not one transaction); a cycle failure is logged and the loan stays.
- Setting status to `defaulted` on edit opens a recovery case.
- There is no separate approve/reject action, no approval limits per role, and no maker-checker: the same user can create a loan as `disbursed` or edit it to `repaid`.

### 4.8 Loan cycles, interest, penalty, rollover, payment plans, forbearance — 🟡 Partial
`LoanCalculator`, `Loan` model, `LoanController@rollover/createPaymentPlan/grantForbearance/endForbearance`.

A loan is a chain of **cycles** (`loan_cycles`). Each cycle has `previous_balance`, `interest_capitalized`, `new_balance`, `interest_rate`, `start_date`, `due_date`, `status`.

**Balance formula** (`calculateCycleBalance`):

```
principal          = cycle.previous_balance > 0 ? cycle.previous_balance : loan.amount
interest           = principal × cycle.interest_rate %            (flat, once per cycle)
processing_fee     = principal × loanType.processing_fee_rate %   (0 on payment plans)
full_balance       = principal + interest + processing_fee
outstanding_at_due = full_balance − repayments on/before due date
days_overdue       = today − due_date
penalty            = outstanding_at_due × penalty_rate % × (days_overdue − grace_days)
                     (daily, simple, no upper limit; skipped on payment-plan/forbearance cycles)
final_outstanding  = outstanding_at_due + max(0, penalty − repayments after due date)
defaulted          = days_overdue ≥ NPL threshold (loan.npl_trigger_threshold or derived from product, default 30)
```

With a 10% penalty rate this is **10% of the overdue balance per day**.

**Rollover** (`executeRollover`, staff only, legacy role admin/teller):
- allowed when loan status is `active | overdue | disbursed` and not `defaulted`;
- new cycle principal = previous cycle's `new_balance` (principal + interest of that cycle); new interest = that × rate (product rate or a custom rate); so interest compounds each rollover;
- new start date = previous due date, or the last repayment date if the cycle was settled late, or a custom date; new due date = start + product period (or custom days/date);
- marks the old cycle `completed`, **overwrites `loans.amount` with the new balance**, resets overdue/NPL flags, sets status `disbursed` (or `forbearance` if penalties waived);
- not wrapped in a DB transaction.

**Payment plan** (`createPaymentPlan`): either a new cycle at a custom interest rate and period with penalties waived (`startPaymentPlan`), or a "manual" new balance set by staff (`manualBalanceAdjustment`). Edit mode updates any cycle by `cycle_id` from the request.

**Forbearance:** grant/end endpoints set `forbearance_until` and status.

**Partner share inside the calculation:** for brokered loans, the broker's share = interest × broker interest rate % + penalty × broker penalty rate % (client vs broker rates depend on the borrower's `client_type`). Investment partners get `profit_share_rate` of interest and penalty.

### 4.9 Disbursements — 🟡 Partial
`DisbursementController`, `DisbursementService`.
- Staff **record** a disbursement after paying out (M-Pesa/bank done outside the system): amount, date, transaction reference (free text), mode, funding source (internal/partner/mixed), investment, partner transaction.
- If no investment is given, links to an auto-created "General Lending Book" investment and the House Principal partner.
- If the loan was `approved`, it becomes `disbursed`.
- Disbursements can also be recorded against an investment with no loan.
- No check of loan status (a pending, rejected or repaid loan can receive a disbursement), no check that total disbursed ≤ approved amount, no duplicate reference check.

### 4.10 Repayments — 🟡 Partial
`RepaymentController`, `RepaymentService` (the service is injected but `store()` does not use it).
- Staff record a repayment: loan, cycle, amount (min 0), date, transaction reference, mode.
- After saving, the cycle is marked `completed` if cycle repayments ≥ `new_balance` (penalty not included), and the **loan is marked `repaid` if there are no active cycles or if total repayments ≥ `loans.amount`**.
- Not in a DB transaction; no check that the cycle belongs to the loan; transaction reference is unique per loan only on **update**, not on create.
- **Non-cash entries are recorded as repayments too.** The type is inferred from the reference text (`CustomerWallets::NON_CASH`): a reference containing `ROLL`/`CARRY FORWARD` = rollover, `DISCOUNT`/`WAIVE` = discount, `BAD DEBT`/`WRITE OFF` = write-off; anything else counts as cash. Live data has 239 "ROLL OVER", 83 "CREDIT DISCOUNT", 13 "BAD DEBT" and 22 "BROKER FEE(S)" repayment rows; the broker-fee rows are counted as cash.
- No M-Pesa C2B/STK integration; no automatic receipting or SMS.

### 4.11 Customer wallets & reconciliation — ✅ Working (cash shadow ledger)
`CustomerWallets`, model observers in `AppServiceProvider`, commands `wallets:*`.
- Each borrower has one bavix wallet `borrower_loan`: cash disbursed is withdrawn, cash repaid is deposited, so balance = cash repaid − cash received. Non-cash entries (by the reference-text rule above) are excluded.
- Every save/delete/restore of a disbursement or repayment re-syncs the wallet after commit, idempotently (each posting carries its source id; edits post the difference).
- Partners have four wallets: `principal`, `interest`, `broker_fee`, `investment_tracker`, fed from `partner_transactions`.
- `wallets:check` compares wallets with the journal/source rows; `wallets:reconcile-from-journal` repairs partner wallets; `wallets:bootstrap` / `wallets:sync` create and backfill.
- Failures are logged and do not block staff.

### 4.12 Partners, brokers & house accounts — 🟡 Partial
`PartnerController`, `PartnerLedger`, `PartnerPortfolio`, `Partner`/`Broker` models, house-account commands.
- **Partners** (`partners` table, 4 rows live) are funders or introducers; some are house accounts (`is_house_account`, `account_type` principal/interest/...).
- **Brokers** (`brokers` table) hold broker share rates: `interest_client`, `interest_broker`, `penalty_client`, `penalty_broker`. Borrowers are linked to a broker; a loan is brokered when `broker_status = 1`.
- **Partner journal** (`partner_transactions`, decimal 19,4): every posting goes through `PartnerLedger::post()`, which writes the wallet and the journal row in one transaction and is idempotent on `(reference, wallet_slug)` (unique index).
- Staff actions: add contribution, withdraw, distribute profit.
- Interest and broker-fee postings are produced by **batch backfill commands** (`partners:backfill-house-interest`, `partners:backfill-broker-fees`, `loans:balance-house-accounts`), not at the moment a repayment is recorded. The real-time helpers in `LoanCalculator` (`distributeRepaymentToPartners`, `returnPrincipalToPartners`) are not called by any controller.

### 4.13 Investments — 🟡 Partial
`InvestmentController`, `Investment`, `InvestmentFunding`.
Tracks facilities that fund lending (type, facility type, initial amount, current value, expected return, status, funding partners) and per-partner funding commitments (`investment_fundings`: committed / disbursed / returned). List, stats, show, create, update, delete work. The routes for notes, milestones and adding funding point to methods that do not exist (🔴).

### 4.14 NPL detection & status updates — 🔴 Broken / not running
- `NplDetectionService::runDetection()` loops over loans and calls `$loan->updateNplStatus()`, **which does not exist on the Loan model**; each loan throws, the error is caught and logged, so no loan is ever flagged.
- The schedule (`loans:detect-npl` daily 02:00 and every six hours) is in `app/Console/Kernel.php`, which Laravel 12 does not load; `routes/console.php` schedules nothing. `deploy.sh` does not set up a cron for `schedule:run`.
- `loans:update-statuses` exists and can be run by hand.
- Live data confirms it: 514 loans, none in `overdue` or `defaulted`; 15 loans still `disbursed` past their due date, the oldest due 12 Sep 2025.

### 4.15 Debt recovery — 🟡 Partial
`CaseController` (1,453 lines), `DebtRecoveryCase` and ~20 recovery models.
- Working: case list/create/show/edit/update/delete, "my cases", add action, add note, mark recovered, mark written off, CSV export, stats; cases are opened from a loan (`startRecovery`) or when a loan is edited to `defaulted`.
- **Write-off sets the loan status to `repaid`** (`CaseController.php:799`), and is restricted by the legacy `role === 'admin'` column.
- Route ordering: `cases/my`, `cases/stats`, `cases/export/csv` are declared after `Route::resource('cases')`, so `GET /cases/my` is captured by `cases/{case}` (show). `getCaseData` and `getStats` routes point to missing methods.
- Scaffold only (tables + models, unused, 0 rows): recovery agencies & contacts, agency assignments, court hearings, legal proceedings & deadlines, recovery documents, tasks, templates, timeline, payment plans/installments, skip tracing, credit bureau reports, financial assessments, communications.

### 4.16 Reports & dashboard — 🟡 Partial
`ReportController` (1,076 lines), `DashboardController`, 7 SQL views.
- Reports: customer reports, CIC reports, loan portfolio, expected repayments, CRB report, active/completed/defaulted loans, group summary, journal entries, loan disbursement, customer balance summary, customer savings, cash flow, customer statistics. Filters: date range, status, loan type, amount range, gender, role.
- `/reports/export` is declared after `/reports/{reportType}`, so export is unreachable (it is treated as a report named "export").
- Dashboard: counts and amounts by status (pending, approved, disbursed, overdue, defaulted, recovery, forbearance, repaid, rejected), monthly disbursed vs repaid (cash only), broker split.
- SQL views: borrower contact network, broker performance, customer health scorecard, debt recovery summary, loan type analysis, portfolio summary, recovery officer workload.
- "CRB report" is a report layout; there is no API connection to Metropol, TransUnion or Creditinfo.

### 4.17 Loan agreements & signatures — ✅ Working
`LoanAgreementService` (784 lines), `SignatureService`, `LoanPDF`. Generates a PDF agreement per loan with borrower details, terms and signature image; borrower and staff can view/download. Signature is a base64 canvas image or uploaded image stored on the public disk. Consent = checkbox + timestamp. Template/section tables exist but are unused (the text is in code).

### 4.18 System settings & admin — 🟡 Partial
`Admin/SystemController`, `System` model: company name, logos, slogan, timezone (live: **UTC**), date/time format, currency (KES), colours, contacts, SEO, maintenance mode, pagination, custom CSS/JS, notification/security JSON. Clear cache and toggle maintenance work. The **backup** and **debug** routes point to methods that do not exist. Profile routes for delete avatar, update address, get data and confirm password also point to missing methods.

### 4.19 Template/demo pages — ⚪
The staff area still serves the TailAdmin demo pages (`/index`, `/invoice`, `/calendar`, `/buttons`, `/signin`, `/signup`, charts, …) and there are four `welcome.blade copy*.php` and a `ProfileController copy.php`.

---

## 5. Console commands

| Command | Purpose |
|---|---|
| `loans:detect-npl {--loan=}` | NPL detection (🔴 calls a missing method) |
| `loans:update-statuses` | Recalculate loan statuses from cycles |
| `loans:fix-cycles`, `loans:repair-cycles` | Repair/rebuild cycle rows |
| `loans:backfill-house-account` | Assign house account to loans |
| `loans:balance-house-accounts` | Balance house partner accounts |
| `partners:backfill-broker-fees` | Post broker fee journal entries from history |
| `partners:backfill-house-interest` | Post house interest/principal journal entries from history |
| `risk:assess` | Run 4Cs assessments |
| `wallets:bootstrap`, `wallets:sync`, `wallets:check`, `wallets:reconcile-from-journal` | Wallet setup, sync, integrity check, repair |

---

## 6. Data model (key tables)

```
users ─┬─ borrowers ── brokers (rates)          roles/permissions (Spatie)
       ├─ employments, addresses, contacts        wallets / transactions / transfers (bavix)
       ├─ loans ─┬─ loan_cycles ─┬─ repayments
       │         │               └─ disbursements ── investments ── investment_fundings ── partners
       │         ├─ loan_risk_assessments                                  └─ partner_transactions
       │         ├─ debt_recovery_cases ─ recovery_actions / case_notes / ...
       │         └─ loan_ledger (unused)
       └─ audit_logs (unused)
loan_types, risk_categories, recovery_statuses/priorities, payment_method(_type)s, lookup tables
system (settings, single row)
```

Notes:
- Money columns are `decimal(15,2)` for loans/cycles/repayments/disbursements and `decimal(19,4)` for the partner journal; PHP arithmetic is done in floats.
- `loans.status` DB enum: `pending, approved, disbursed, rejected, repaid, overdue, defaulted, recovery, forbearance`. The model also defines `active` and `written_off`, which the DB column cannot store.
- Foreign keys: `loans → users` and `loans → loan_types` are `ON DELETE CASCADE`, and `repayments/disbursements/loan_cycles → loans` are `ON DELETE CASCADE`. Deleting a user or a product deletes their loans and all money rows under them.
- `loan_ledger` and `audit_logs` exist but are never written.
- 87 tables in the live DB; 44 are empty.

---

## 7. Money flow — life of a loan today

```
Borrower registers → KYC (4 steps) → staff call next of kin + guarantor → KYC approved
        │
        ▼
Apply (self-service, product #1, within limit)  or  staff create loan (any product/status)
        │  creates loan (pending) + cycle 1 (principal + interest, due date) + PDF agreement
        ▼
Staff approve (edit status)  →  staff pay out on M-Pesa/bank outside the system
        │
        ▼
Staff record disbursement (reference typed in)  → loan becomes disbursed → borrower wallet −cash
        │
        ▼
Borrower pays on M-Pesa/bank outside the system
        │
        ▼
Staff record repayment (reference typed in) → borrower wallet +cash → cycle/loan status updated
        │
        ├── paid in full ─────────────────────────────→ repaid
        ├── staff roll over (new cycle, interest on principal+interest) → disbursed/forbearance
        ├── staff payment plan / manual balance ─────→ new cycle, penalties waived
        ├── staff discount / bad debt (non-cash "repayment") → may close the loan as repaid
        └── staff open recovery case → recovered or written off (loan set to repaid)

Later, by hand: backfill commands post partner interest/broker fees to the partner journal;
wallets:check reconciles.
```

---

## 8. Current data snapshot (from `loans_main.sql`, dump of 15 Oct 2026)

Aggregates only, no personal data.

| Item | Value |
|---|---|
| Users | 91 (84 borrowers, 4 admin, 2 teller, 1 partner by legacy column) |
| Loans | 514, borrow dates 26 Feb 2025 – 15 Oct 2026 |
| Loan status | repaid 465 · disbursed 29 · pending 15 · forbearance 5 · overdue 0 · defaulted 0 |
| Original principal booked | ≈ KES 25.4 M |
| Loans rolled over at least once | 30 |
| Disbursed loans past due | 15 (oldest due 12 Sep 2025) |
| Repayment rows | 800 (incl. 239 rollover, 83 discount, 13 bad debt, 22 broker-fee text entries) |
| Disbursement rows | 629 (72 loans have more than one) |
| M-Pesa-style codes recorded as repayments on two different loans | at least 4 (could be split payments or double posting; needs checking) |
| Recovery cases | 2 |
| Partners | 4 · Wallets 88 · Wallet transactions 497 |
| Audit log rows | 0 · Loan ledger rows 0 · Risk assessments 0 (dump predates the scoring migration) |

---

## 9. Deployment & operations

- `deploy.sh` (CyberPanel): pre-flight warnings (APP_ENV/APP_DEBUG/APP_URL), removes `public/hot`, maintenance mode on, `composer install --no-dev` (or uses uploaded `vendor/`), **DB backup via spatie/backup before migrating** (stops on failure), `migrate --force`, storage link, cache rebuild, maintenance off. Optional `PUBLIC_DIR` copy for split web roots.
- Front-end build is committed (`public/build`), so the server needs no Node.
- Scheduler: not wired (see §4.14). Queue: `database` driver; no worker setup in deploy.
- Shipped `.env` is local (`APP_ENV=local`, `APP_DEBUG=true`, `LOG_LEVEL=debug`, mail to log).
- `storage/logs/laravel.log` is 247 MB with ~14,800 ERROR entries (most from past dev issues: brick/math version mismatch until 6 Oct 2026, missing `jobs` table, missing views/variables). `LoanController@store` logs the full request body at debug level.
- Backups: `config/backup.php` (spatie). Three manual SQL dumps sit in `storage/app/backups/` and one in the repo root.

---

## 10. Routes that point at missing code

| Route | Target | Effect |
|---|---|---|
| `GET /auth/google`, `/auth/google/callback` | `GoogleController@redirect/callback` | 500 |
| `GET users/{user}/loans/create` | `LoanController@createForUser` | 500 |
| `GET admin/loans/{loan}/edit` | `LoanController@adminEdit` | 500 |
| `GET loans/{loan}/user-loans` | `LoanController@getUserLoans` | 500 |
| `GET loans/{loan}/chart-data` | `LoanController@chartData` | 500 |
| `GET cases/{case}/data` | `CaseController@getCaseData` | 500 |
| `GET cases/stats` | `CaseController@getStats` (method is `stats`) | caught by `cases/{case}` |
| `GET cases/my`, `cases/export/csv` | declared after resource | caught by `cases/{case}` |
| `GET reports/export` | declared after `reports/{reportType}` | treated as a report type |
| `POST investments/{id}/note`, `/milestone`, `/funding` | `InvestmentController@addNote/addMilestone/addFunding` | 500 |
| `GET system/backup`, `POST system/debug` | `SystemController@backupDatabase/debug` | 500 |
| `DELETE profile/avatar`, `PUT profile/address`, `GET profile/data`, `POST profile/password/confirm` | `ProfileController@…` | 500 |

---

## 11. Tests

Only the Breeze scaffold tests (authentication, registration, password reset/update/confirm, email verification, profile) and two example tests. `phpunit.xml` points at a MySQL `loans_testing` DB. There are no tests for interest, penalty, rollover, repayment allocation, limits, scoring, wallets or partner postings. (The suite could not be run in this review: the locked dependencies need PHP 8.4.1+.)

---

## 12. User journeys by role

IDs are used across `WORKPLAN.md`, `REVIEW_AND_RECOMMENDATIONS.md` and the planned section of `amazonblue_tree.txt`.
✅ exists today · 🟡 exists with gaps · 🆕 planned (not built). The UI each journey runs in: **Site** = customer website design (`home/*`), **Admin** = TailAdmin staff dashboard.

The journeys worked on in the shared design artifact could not be read (not shared with this account). Planned journeys below come from the code review and the 17 Oct requests; they will be checked against the artifact once it is shared.

### 12.1 Everyone

| ID | Journey | Steps | UI | Status |
|---|---|---|---|---|
| J-A1 | Error pages | Any 403 / 404 / 419 (session expired) / 429 (too many tries) / 500 / 503 (maintenance) → branded page with what happened, a way back (home, account or dashboard by role), and a support link | Site for guests & customers, Admin for staff | 🆕 (Laravel defaults today) |
| J-A2 | Sign in & password | Login → lands on `/account` (customers) or `/dashboard` (staff); forgot → e-mail link → reset; verify e-mail (staff only) | Site | ✅ (mail goes to log in shipped `.env`) |
| J-A3 | Google sign-in | Button → Google → back signed in | Site | 🔴 routes exist, controller empty |

### 12.2 Borrower

| ID | Journey | Steps | Status |
|---|---|---|---|
| J-B1 | Register | Register (name, e-mail, phone, password) → borrower role → KYC start | ✅ |
| J-B2 | Verify (KYC) | Personal → Documents (ID front/back, photo, signature) → Income (employment, salary, bank) → Contacts (next of kin, guarantor) → Review → Submitted ("we will call your contacts") | ✅ |
| J-B3 | KYC rejected → fix | Account shows "needs attention" with staff note → reopen the step that failed → resubmit | 🟡 status + message exist; no per-step reason shown |
| J-B4 | Borrow | Account → Borrow: limit, blockers, amount, quote (interest, total, due date) → accept terms → submitted (pending) | ✅ |
| J-B13 | Track a loan | My loans → loan: timeline (applied, approved, sent, repaid), balance, cycles, repayments → agreement PDF | ✅ |
| J-B5 | Repay | Loan → Repay → how to pay (paybill/till + account number, or STK push later) → enter M-Pesa code + amount → "received, being confirmed" → staff confirm → receipt with the split (penalty, interest, principal) | 🆕 |
| J-B6 | Roll over | Loan (near or past due) → Roll over → preview (amount to pay now, new principal, new due date, new interest) → request → staff approve → new cycle shown | 🆕 (staff-only today) |
| J-B7 | Payment plan | Offer from staff → borrower sees terms (rate, period, penalties waived) → accept → plan cycle | 🆕 (staff-only today) |
| J-B8 | Loan states | Pending ("under review"), Rejected (reason, when to reapply), Overdue (days late, penalty so far, pay now), In recovery (officer contact, settle options) | 🟡 timeline only; no rejected reason, overdue or recovery screens |
| J-B9 | Statements & receipts | Download statement (all loans, date range) and individual receipts | 🆕 |
| J-B10 | Notifications | In-app list (approved, sent, due in 2 days, overdue, received) + SMS | 🆕 |
| J-B11 | Accept terms per loan | Each new loan: show agreement → OTP to phone → accepted (IP, device, time stored) | 🆕 (re-uses the KYC signature today) |
| J-B12 | Support | Help page + raise an issue (category, message) → ticket reference | 🆕 |
| J-B14 | Profile | View/edit contact details, change password | ✅ |

### 12.3 Partner (investor / funder)

| ID | Journey | Steps | Status |
|---|---|---|---|
| J-P6 | Statement | Account → Statement → filter by wallet (principal, interest, broker fee, investment tracker) | ✅ |
| J-P1 | Onboarding | Invited or signs up → partner KYC (company/individual, ID/registration, bank, agreement) → staff approve | 🆕 (partners are created by staff) |
| J-P2 | Portfolio | My investments → each investment: committed, disbursed, returned, current value → loans it funded | 🆕 |
| J-P3 | Earnings | Interest and penalty share per period, per investment, per loan; principal returned | 🆕 |
| J-P4 | Add funds | Top-up request (amount, method, reference) → staff confirm → principal wallet credited | 🆕 |
| J-P5 | Withdraw | Request (amount, wallet, bank/M-Pesa) → staff approve (maker-checker) → paid → statement entry | 🆕 |

### 12.4 Broker

| ID | Journey | Steps | Status |
|---|---|---|---|
| J-K1 | Broker home | Clients count, active loans, arrears, fees earned this month | 🆕 (no broker portal; brokers use parts of the staff area) |
| J-K2 | Refer a borrower | Enter client details → invite link/SMS → client does J-B1/J-B2 → linked to broker | 🆕 |
| J-K3 | My clients' loans | List with status, due date, arrears; open a loan (read-only) | 🆕 |
| J-K4 | Fee statement | Broker fee per loan (interest share + penalty share), paid vs pending | 🆕 |

### 12.5 Staff (admin, manager, loan officer, teller, accountant, recovery officer, auditor)

| ID | Journey | Steps | Status |
|---|---|---|---|
| J-S1 | Dashboard | KPIs by status, monthly disbursed vs repaid, broker split | 🟡 numbers depend on statuses (see §4.14) |
| J-S2 | KYC review | Queue → borrower → documents, income, contacts → tick "called next of kin" + "called guarantor" → approve / reject with note | ✅ |
| J-S17 | Users | List, create, edit, view borrower with loans | ✅ |
| J-S3 | Loan approval | Pending queue → loan + score + limit + KYC → approve / reject with reason (second approver above a limit) | 🆕 (status edit today) |
| J-S4 | Disbursement queue | Approved loans → pick investment (and so partner) → pay out → enter reference → loan disbursed, wallets posted | 🆕 (form exists, no queue; investment optional) |
| J-S5 | Record repayment | Find loan → pick cycle (default: active) → amount, date, M-Pesa code → split preview (penalty, interest, principal → broker, partner, house) → save → wallets posted | 🟡 form exists; no cycle rule, no split |
| J-S6 | Reverse a repayment | Repayment → reverse with reason → second approver → reversal entries in all wallets | 🆕 (edit/delete today) |
| J-S7 | Rollover / payment plan / forbearance | Loan → preview → confirm → new cycle; loan amount = new principal | 🟡 exists; principal rule changing (R6/R7) |
| J-S8 | Collections worklist | Due today, 1–7, 8–30, 31–90, 90+ days → call/SMS log → promise to pay → open case | 🆕 |
| J-S15 | Recovery cases | Case list → case: actions, notes → recovered / written off | 🟡 write-off sets loan to repaid; some routes broken |
| J-S9 | Approvals (maker-checker) | Inbox of pending actions: write-off, discount, manual balance, custom-rate rollover, reversal, payout → approve / reject | 🆕 |
| J-S10 | Loan products | List, create, edit loan types (period, rate, penalty, cap, grace, threshold) | 🆕 (DB only today) |
| J-S11 | Roles & permissions | Roles list → permissions matrix → assign users | 🆕 (seeder only today) |
| J-S12 | Audit log | Filter by user, record, date → before/after | 🆕 |
| J-S13 | Wallets & reconciliation | Balances per wallet type → run check → list of gaps → repair (approved) | 🆕 (artisan only today) |
| J-S14 | Partner payouts | Withdrawal requests → approve → paid | 🆕 |
| J-S18 | Partners & investments | Partners list/show, contribution, withdraw, profit; investments list/show | 🟡 three investment actions missing |
| J-S16 | Reports | Pick report → filters → view; export | 🟡 export unreachable |
| J-S19 | System settings | Branding, contact, timezone, maintenance, cache | 🟡 backup/debug actions missing |
