# Audit A: customer-facing modules (Stage 1, audit only)

Scope: public website, auth, borrower KYC, /account, borrowing and limits, credit scoring, agreements and signatures, error handling.
Evidence: code read (file:line), `php artisan route:list`, `php artisan migrate:status`, read-only SELECTs on local mirror `loans_main` (local data, not live).

Labels: **Verified working** · **Implemented, not verified** · **Partial** · **Planned only** · **Broken** · **Unknown (not enough evidence)**

Local data snapshot (mirror): 91 users (79 borrower, 1 partner, 4 admin, 2 teller, 5 with no role); `kyc_status` NULL for all 91; `email_verified_at` NULL for all 91; 0 self-service loans; 0 rows in `loan_risk_assessments`, `credit_bureau_reports`, `financial_assessments`, `enquiries`; 10 borrower-role users have no `borrowers` row; 49 loans in an open status. Migrations `2026_10_16_000000_add_kyc_review_fields_to_users_table` and `2026_10_17_000000_add_scoring_fields_to_loan_risk_assessments_table` are **Pending** locally (KYC columns exist via `database/sql/2026_10_16_kyc_review.sql`; scoring columns do not exist locally).

---

## 1. Public website

1. **Purpose / today**: marketing site (welcome + 11 static pages), robots.txt, sitemap.xml, confidential enquiry form on /contact. **Implemented, not verified** (routes resolve in route:list; pages not rendered in this audit).
2. **Code**: `routes/web.php:19-28`; `HomeController@index/page/robots/sitemap/storeEnquiry` (`app/Http/Controllers/HomeController.php:59-341`); `Enquiry` model; views `resources/views/welcome.blade.php`, `resources/views/home/*.blade.php`, `home/partials`, `home/layouts`.
3. **Tables**: `enquiries` (topic, name, email, phone, investor_type, allocation, message, route_to, consented_at, ip_address, user_agent, reference). Unique constraint per topic+contact (caught at HomeController.php:314).
4. **Rules**: `{page}` limited to `HomeController::PAGES` whitelist (web.php:22-23) **Implemented, not verified**; one enquiry per topic per email-or-phone, duplicate returns existing reference (HomeController.php:293-319) **Implemented, not verified**; `route_to` decided server-side (309); consent required.
5. **Roles**: public; enquiry POST throttled 6/min (web.php:26).
6. **Data/money**: no money. Enquiry stored with IP/user agent (personal data).
7. **Dependencies**: none beyond DB. No email/notification to the desk on new enquiry (no notify call in storeEnquiry) **Partial**.
8. **Issues**:
   - No staff notification or inbox evidence for enquiries in this module; `enquiries` is empty locally. **Unknown** whether staff see them (outside this module).
   - robots.txt does not disallow `/account` or `/storage` (HomeController.php:73-83). **Partial**.
   - `resources/views/welcome.blade copy*.php` (4 files) are dead copies (tech debt).
9. **Recommendations**: notify the routed desk on new enquiry; add `/account` and `/storage` to robots; delete copy views; add retention rule for enquiry IP/UA (owner decision).

## 2. Authentication

1. **Purpose / today**: Breeze-style login, register (individual borrowers only), password reset, email verification, Google login routes, logout, password change.
2. **Code**: `routes/auth.php`; `app/Http/Controllers/Auth/*`; `app/Http/Requests/Auth/LoginRequest.php`; `EnsureStaff` (`app/Http/Middleware/EnsureStaff.php`); `User::isCustomer/homeRoute/STAFF_ROLES` (`app/Models/User.php:142-158`); views `resources/views/auth/*`.
3. **Tables**: `users`, `password_reset_tokens`, `sessions`, Spatie `roles`/`model_has_roles`, `borrowers`.
4. **Rules**:
   - Register: `account_type` must be `individual` (business "coming soon"); unique email + unique Kenyan mobile `^(?:\+?254|0)[17]\d{8}$`; creates user (legacy `role='borrower'`, `status=0`), assigns Spatie `borrower`, creates `borrowers` row in one transaction, then logs in and redirects to `kyc.personal` (RegisteredUserController::store). **Implemented, not verified**.
   - Login throttled 5 attempts per key (LoginRequest.php:62); lands on `homeRoute()` = `account.home` for customers else `dashboard` (AuthenticatedSessionController.php:32). **Implemented, not verified**.
   - Customer = has borrower/partner and no staff role (User.php:147-150). EnsureStaff redirects customers from staff routes to /account (403 for JSON) (EnsureStaff.php:19-24). **Implemented, not verified**.
5. **Roles**: guest for register/login/reset; auth for verify/confirm/logout.
6. **Data**: no money. Phone is not verified (no OTP).
7. **Dependencies**: mail transport for reset/verification (not checked); Socialite is **not** in composer.json.
8. **Issues**:
   - **Broken**: Google login. `/auth/google` and `/auth/google/callback` route to an empty `GoogleController` (`app/Http/Controllers/Auth/GoogleController.php` body is `//`), no Socialite package. Not linked from any view (grep), so users don't hit it unless they type the URL (500 / BadMethodCall).
   - **Broken**: email verification is not enforced. `User` implements only `Wallet` (User.php:15), not `MustVerifyEmail`, so the `verified` middleware on staff routes (web.php:73, 270) is a no-op and no verification email is sent on `Registered`. All 91 local users have `email_verified_at` NULL.
   - **Partial**: `VerifyEmailController` and `EmailVerificationPromptController` redirect to `dashboard` (lines 18/25 and 18), not `homeRoute()`; a customer would bounce through EnsureStaff to /account. Only login uses `homeRoute()`.
   - **Partial**: `POST /register` has no throttle and no captcha; phone not verified.
   - 5 local users have no role at all: they pass EnsureStaff (not a customer) and reach staff routes, then rely on per-route `permission:` middleware. **Implemented, not verified**.
   - `/account` group has `auth` only (web.php:50); fine for customers, but staff hitting `/account/borrow` get a bare Spatie 403.
9. **Recommendations**: remove or implement Google routes; decide whether email (or phone OTP) verification is required and implement `MustVerifyEmail` or SMS OTP; throttle register; send verify redirects through `homeRoute()`.

## 3. Borrower KYC

1. **Purpose / today**: 4-step self-service KYC (personal, documents, income & payout, next of kin & guarantor), review + 3 declarations, submit; staff approve/reject after calling next of kin and guarantor. **Implemented, not verified** (no local user has ever submitted: `kyc_status` NULL for all 91).
2. **Code**: routes `web.php:30-43` (`auth`, `role:borrower`), staff `web.php:102-108` (`permission:edit users`); `HomeController.php:345-762` + helpers 769-964; `KycReviewController.php`; views `resources/views/home/kyc/*`, `resources/views/kyc-reviews/{index,show}`; migration `2026_10_16_000000_add_kyc_review_fields_to_users_table.php` (pending locally) and `database/sql/2026_10_16_kyc_review.sql` (applied locally).
3. **Tables**: `users` (id_number, dob, kra_pin, nationality, id_type, id_front_path, id_back_path, avatar, signature, kin_name/relation/phone, kyc_submitted_at, kyc_status, kyc_reviewed_by, kyc_reviewed_at, kyc_review_notes, kyc_checks JSON); `addresses` (type `current`); `borrowers` (income_type, employer_name, workplace, job_title, department, net_salary); `employments` (current; source of funds and registration no. stored as text in `notes`); `payment_methods` (M-Pesa primary `pending_verification`, optional bank); `contacts` (guarantor = `relationship_specific='Guarantor'`); lookups `address_types`, `employment_types`, `payment_method_types`, `contact_types`.
4. **Rules** (all **Implemented, not verified**):
   - Personal: age >= 18; KRA PIN `^[AP]\d{9}[A-Z]$`; ID/passport unique across users; KRA PIN **not** unique (HomeController.php:369-381).
   - While a loan is in the pipeline, first/last name, ID number and DOB cannot change (HomeController.php:384-396, `User::isProfileLocked` User.php:760).
   - Documents: national ID front+back or passport bio, photo, signature (drawn PNG or image upload), 10 MB, jpg/png/webp/heic/pdf (456-475).
   - Income: required fields per income type (`INCOME_FIELDS`, 521-526); Safaricom M-Pesa `^(?:\+?254|0)[17]\d{8}$`; years -> `start_date` = now minus years.
   - Contacts: kin and guarantor phones must differ from each other and from the borrower's phone (675-678).
   - Submit: 3 declarations (true info, not PEP, CRB consent) + all 4 sections complete -> `kyc_status='submitted'` (730-744).
   - Locking: `submitted` blocks all edits (903-911); `approved` locks every field that already has a value and re-merges saved values on POST so tampering can't change them (830-852, 439-451); `rejected` reopens everything.
   - Staff approve requires both "called" checkboxes; sets `approved`, reviewer, time, notes, `kyc_checks`, and `borrowers.status=1` (KycReviewController approve). Reject requires note >= 10 chars; borrower sees the note on the account home (borrower.blade.php:32-36).
5. **Roles**: borrower (own KYC); staff with `edit users` permission review.
6. **Data**: identity documents, photo and signature saved on the **public** disk (`id_cards/`, `avatars/`, `images/signatures/`); `public/storage` symlink exists; staff view uses `Storage::disk('public')->url()` (kyc-reviews/show.blade.php:4).
7. **Dependencies**: SignatureService; lookup tables seeded with the expected slugs (not verified); BorrowingEligibility reads `kyc_status`.
8. **Issues**:
   - **Broken (security)**: KYC documents are publicly reachable at `/storage/id_cards/<hash>.jpg` etc. with no auth. Filenames are random hashes (not guessable), but any leaked URL exposes national IDs. Signatures use predictable names (see module 7).
   - **Partial**: no notification to the borrower on approve/reject, or to staff on submit (no notify calls in either controller).
   - **Partial**: no maker-checker on KYC approval; same reviewer can approve any user, including one they registered. No check that reviewer != applicant.
   - **Partial**: `blockers()` shows "Borrower profile missing, contact us" for users without a `borrowers` row (BorrowingEligibility blockers) even though finishing KYC income step creates it (HomeController.php:575). 10 local borrowers are in that state.
   - **Partial**: M-Pesa number stays `pending_verification`; nothing in this module verifies payout numbers or bank accounts.
   - Tech debt: source of funds / registration no. parsed out of `employments.notes` text (878-900, 936-941); docblock at 823-829 is duplicated/stale.
   - Migration pending locally while the SQL script was applied; confirm live has the same columns (**Unknown** for live).
9. **Recommendations**: move KYC files to a private disk served through an authorised controller; notify on submit/approve/reject; owner decides on KRA PIN uniqueness, maker-checker, document expiry, re-KYC frequency, and payout-number verification.

## 4. Customer account (/account)

1. **Purpose / today**: mobile-first customer area. Borrower: home (open loan progress, limit, KYC steps, recent activity, repayment journey, "how to repay"), borrow, loans list, loan detail, agreement viewer, profile (password change, logout). Partner: home (portfolio by wallet) and statement. **Implemented, not verified**.
2. **Code**: `web.php:50-70`; `HomeController.php:98-260`; services `BorrowingEligibility`, `LoanProgress`, `PartnerPortfolio` (summary 33, statement 69), `CustomerWallets::kind`, `CreditAssessment::current`; views `resources/views/home/account/{borrower,borrow,loans,loan,agreement,profile,partner,statement}.blade.php`, layout `home/layouts/account.blade.php`.
3. **Tables**: `loans`, `loan_cycles`, `loan_types`, `disbursements`, `repayments`, `partners`, wallet tables (Bavix) via PartnerPortfolio, `addresses`, `payment_methods`, `contacts`.
4. **Rules**:
   - Non-customers hitting `/account` go to `dashboard` (HomeController.php:101-103). **Implemented, not verified**.
   - Loan detail, agreement and PDF: `abort_unless($loan->user_id === user->id, 404)` (145, 175) **Implemented, not verified**.
   - Recent activity shows only cash movements (`CustomerWallets::kind === 'cash'`), last 5 (257-259).
   - Statement: partner only (`role:partner`), wallet filter whitelisted against `PartnerPortfolio::WALLETS` (195).
5. **Roles**: `auth` for home/profile; `role:borrower` for borrow/loans/agreement; `role:partner` for statement.
6. **Money**: read-only. Repayment is manual: "send by M-Pesa, then send the message on WhatsApp, staff record it" (borrower.blade.php:187-196). No paybill/till number shown, no STK push, no callback.
7. **Dependencies**: LoanProgress, PartnerPortfolio, CreditAssessment (run on every home view, heavy per request), Bavix wallets.
8. **Issues**:
   - **Partial**: no self-service repayment (manual WhatsApp). The page does not tell the borrower where to send money.
   - **Partial**: a user with both borrower and partner roles sees only the borrower home (`hasRole('borrower')` first, 105) and has no link to the partner statement.
   - **Partial**: profile is read-only except password; to fix details the borrower goes back through KYC (and after approval saved fields are locked) with no "request a change" flow.
   - **Partial**: partner account with no `partners` row shows an empty portfolio (121); statement 404s (`firstOrFail`, 189). Only 1 partner locally.
   - Performance: `CreditAssessment::current()` runs twice per borrower home view (limit + journey, 113 and 226). Not measured.
9. **Recommendations**: show official payment instructions (owner decision: paybill/till/account format) or integrate M-Pesa; partner/borrower dual-role switcher; a "request profile change" flow for locked fields.

## 5. Borrowing and limits

1. **Purpose / today**: verified borrowers apply for one fixed product ("Emergency 10 Days", loan_type 1) up to their limit; creates a pending loan, first cycle and agreement PDF. **Implemented, not verified** (0 self-service loans locally).
2. **Code**: `POST /account/borrow` -> `LoanController@apply` (`app/Http/Controllers/LoanController.php:59-133`, `role:borrower`, `throttle:6,1`); `app/Services/BorrowingEligibility.php`; `config/lending.php`; view `home/account/borrow.blade.php`.
3. **Tables**: `loans` (pending, consent, consent_date, cycle=1, reason), `loan_cycles` (via LoanCalculator::createInitialCycle), `loan_types`, `debt_recovery_cases`.
4. **Rules / formulas**:
   - Product (local DB): interest 20% flat, penalty 10%, 10 days.
   - Quote: `interest = amount x rate/100`; `total = amount x (1 + rate/100)`; due = today + period (BorrowingEligibility quote).
   - Min KES 1,000; first-time limit 5,000; good-standing 50,000 (config/lending.php).
   - Limit (CreditAssessment.php:212-244): ladder = 50,000 if last closed loan `on_time` and never written off and never in recovery, else 5,000; then capped by risk category cap (low 50k, medium 30k, high 10k, very high 5k); then capped by income: `floor(net_salary x income_multiple / (1 + rate/100) / 500) x 500`; then floored at first_time_limit (5,000).
   - Blockers: no borrower row; KYC not `approved`; any open loan (pending, approved, disbursed, active, overdue, defaulted, recovery, forbearance); open recovery case; product missing.
   - Double-submit guard: row lock on user + re-check open loan inside transaction (LoanController.php:87-91).
5. **Roles**: borrower with `kyc_status='approved'`.
6. **Money**: no money moves at apply; loan is `pending` for staff approval/disbursement (other module).
7. **Dependencies**: LoanCalculator, LoanAgreementService, CreditAssessment, config.
8. **Issues**:
   - **Partial**: if `createInitialCycle` or agreement generation throws, the error is only logged and the borrower still sees "Application sent" (LoanController.php:112-122). A loan can exist with no cycle or no agreement.
   - **Partial**: the 5,000 floor (CreditAssessment.php:235) means a very-high-risk borrower, someone with a written-off history, or someone whose income cap is lower still gets 5,000. Owner decision.
   - **Partial**: income of 0 or missing skips the income cap entirely (228).
   - **Partial**: one on-time loan jumps the ladder from 5,000 straight to 50,000 (before caps). Owner decision on intermediate steps.
   - **Partial**: no staff notification of a new self-service application.
   - Quote (simple flat) and the agreement's interest (LoanCalculator metrics) are computed by different code; agreement could disagree with the quote. **Unknown**.
   - Legacy `LoanController@store` logs the full request (`Log::debug('Loan Store Request Data:', $request->all())`, ~line 143) - PII in logs.
9. **Recommendations**: fail the application (or mark it) if cycle/agreement creation fails; owner to confirm limit ladder, floor, zero-income handling; notify staff.

## 6. Credit scoring

1. **Purpose / today**: in-house 4Cs score 0-100 from our own repayment history, live score drives limit; one `auto` assessment per loan re-scored after loan/repayment/disbursement changes (`AppServiceProvider.php:63` -> `rescoreLater`). **Partial**: live calculation is wired into limits and views; persistence needs columns that do not exist locally.
2. **Code**: `app/Services/CreditAssessment.php`; `Borrower::calculate4csScore` and legacy helpers (`app/Models/Borrower.php:186-397`); migrations `2025_01_01_000032_create_loan_risk_assessments_table`, `..._000046_create_credit_bureau_reports_table`, `..._000047_create_financial_assessments_table`, `..._000059_create_risk_categories_table`, `2026_10_17_000000_add_scoring_fields_to_loan_risk_assessments_table` (pending locally), SQL `database/sql/2026_10_17_risk_scoring.sql`.
3. **Tables**: `loan_risk_assessments` (loan_id; scores; overall; risk_category; assessed_by NOT NULL locally; plus source/factors/limit_amount/risk_category_id/assessed_at from the pending migration); `risk_categories` (very_high 0-49, high 50-64, medium 65-79, low 80-100 - Verified by query); `credit_bureau_reports` and `financial_assessments` (0 rows, no code uses them - **Planned only**).
4. **Formulas** (CreditAssessment.php):
   - Overall = 0.40 Character + 0.25 Capacity + 0.15 Capital + 0.20 Conditions (line 32, 342).
   - Character: no history 60; else weighted average (last 5 closed loans count double) of on_time 100, late max(40, 85 - 3 x days_late), rolled 45, discounted 35, written off 0; capped at 35 if ever written off, 30 if ever in recovery (360-391).
   - Capacity: average of income part (repay/income <=0.3:100, <=0.5:85, <=0.8:65, <=1.0:50, else 30) and history part (amount/largest repaid within 3 days late <=1:90, <=1.5:70, <=2:55, else 40); 50 if neither (393-419).
   - Capital: 40 if no closed loans; else 40 + min(25, 5 x on_time) + min(15, months with us) + cash cover bonus (>=1.1:+20, >=1.0:+10, >=0.8:0, else -20) (421-439).
   - Conditions: 100 - min(40, 20 x open loans) - 30 if overdue - min(40, 10 x rollover streak) - 20 if ever non-performing - 40 if open recovery (441-451).
   - Only cash counts as repayment; rollovers/discounts/write-offs classified via `CustomerWallets::kind`.
   - Recommendation text: >=80 approve, >=65 approve within limit, >=50 refer, else decline (253-265). Advisory only.
5. **Roles**: system; staff see scores on `users/show` and `profile/show`.
6. **Data**: reads loans, repayments, disbursements, recovery cases, `borrowers.net_salary` (self-declared).
7. **Dependencies**: CustomerWallets, BorrowingEligibility, RiskCategory rows, `risk:assess` command (referenced in docblock line 61; existence not checked here).
8. **Issues**:
   - **Broken (locally)**: `syncUser` writes `source`, `factors`, `limit_amount`, `risk_category_id`, `assessed_at` and `assessed_by = null`, but locally those columns don't exist and `assessed_by` is NOT NULL. Every re-score fails silently into the log (caught at 74-76). 0 rows in `loan_risk_assessments`. Live state **Unknown** (depends on whether `2026_10_17_risk_scoring.sql` was run).
   - **Partial**: income is self-declared and unverified, but it drives Capacity and the income cap.
   - **Planned only**: credit bureau (CRB) reports and financial assessments: tables only, no code. The KYC asks for CRB consent but nothing pulls a CRB report.
   - **Broken (consistency)**: legacy Borrower helpers contradict the live rules: `getMaxLoanAmount` (2-12x salary), `getRecommendedLoanLimit` (40% x 12 months), `getSuggestedInterestRate` (8.5%...), hard-coded risk bands, and `getDebtToIncomeRatio` using status `processing` which is not a Loan status (Borrower.php:197-310). `profile/show.blade.php:607-612` still shows a 300-850 "credit score" built from them.
   - Re-scoring runs after the response in the same PHP process (`app()->terminating`), with no queue. No scheduler exists, so nothing re-scores when a loan simply becomes overdue with no record change.
9. **Recommendations**: run the pending scoring SQL/migration everywhere and confirm on live; delete or rewire legacy Borrower helpers; owner decides whether CRB checks are required and whether scores are advisory or block approval.

## 7. Agreements and signatures

1. **Purpose / today**: TCPDF loan agreement with the borrower's KYC signature, generated on apply, on first view in /account, and on every staff view/download. **Implemented, not verified** (20 PDFs exist in local `storage/app/public/agreements`).
2. **Code**: `app/Services/LoanAgreementService.php` (generate 21-48, repayment 278-330, penalty 393-415, signatures 555-690); `app/Services/SignatureService.php`; `HomeController@accountAgreement/accountAgreementFile/agreementPath` (153-185); staff `LoanController` 1680, 1699, 1717, 1742, 325; view `home/account/agreement.blade.php` (untracked new file).
3. **Tables**: `loans.consent`, `loans.consent_date`; `users.signature` (path); no agreements table, no hash, no version.
4. **Rules**:
   - Customer can view only own loan; PDF generated on first view only if `consent` is true (179).
   - Penalty clause text is hard-coded: "10% of the Total Repayment Amount ... per day ... compounding daily" (LoanAgreementService.php:405-412), independent of `loan_types.penalty_rate`.
   - Lender always witnessed by director; borrower witnessed by guarantor only if one exists (555-560 docblock).
5. **Roles**: borrower (own), staff with `view loans` / `edit loans`.
6. **Data**: PDF written to `storage/app/public/agreements/loan_agreement_{id}.pdf`.
7. **Dependencies**: TCPDF, LoanCalculator metrics, users.signature file.
8. **Issues**:
   - **Broken (security)**: agreements live on the public disk with sequential names, so `/storage/agreements/loan_agreement_1.pdf`, `_2.pdf`, ... are downloadable by anyone without login (symlink `public/storage` exists; files `loan_agreement_2.pdf`, `_227.pdf`... present). Each contains name, ID number, address, amounts and signature.
   - **Broken (security)**: drawn signatures are saved as `images/signatures/signature_{User_Name}.png` (SignatureService.php:22-23): guessable and public, and two users with the same name overwrite each other's signature file. No duplicate today (query), but nothing prevents it.
   - **Partial (legal evidence)**: the "signed" agreement is not immutable. Staff show/download regenerate it from current data and the current signature (LoanController.php:1680, 1699), so a later amount edit or signature change rewrites history. No signed-at timestamp, IP, document hash or version is stored.
   - **Partial**: /account generates only if missing, so the borrower may see an older version than staff.
   - SignatureService logs at debug level including a data preview and the user's name (14-19); LOG_LEVEL=debug in `.env.example`.
   - Penalty "10% per day compounding" text may conflict with Kenyan lending law (in duplum) and with the configured penalty rate. Owner/legal decision.
9. **Recommendations**: private disk + authorised download; signatures named by user id/UUID; freeze the agreement at consent (store hash, timestamp, IP, user agent, version) and never regenerate a signed copy; legal review of penalty clause.

## 8. Error handling

Evidence: `bootstrap/app.php:31-33` `withExceptions` is empty; no `resources/views/errors/` directory; `resources/views/404.blade.php` is a TailAdmin demo page served only as a static route (web.php:373), not as the error view; `config/app.php:43` `debug` defaults to `false` when APP_DEBUG is unset, but `.env.example` sets `APP_DEBUG=true` and `APP_ENV=local`; no Ignition, Sentry or Telescope in composer.json; `app/Http/Kernel.php` (with `CheckMaintenanceMode`, `ApplySystemSettings`, `TrustProxies`) is not referenced by `bootstrap/app.php`, so it is dead in Laravel 12.

| Code | HTML request today | JSON request today | Label |
|---|---|---|---|
| 403 | Laravel's built-in unbranded "Forbidden" page (Spatie role/permission, `abort_unless(...,403)`) | `{"message": ...}` 403; EnsureStaff aborts 403 for JSON customers | Implemented, not verified |
| 404 | Built-in unbranded 404 (unknown URL, `{page}` outside whitelist, other user's loan via `abort_unless`) | JSON 404 | Implemented, not verified |
| 409 | Built-in generic page (KYC approve/reject on non-submitted user) | JSON 409 | Implemented, not verified |
| 419 | Built-in "Page Expired"; user loses the form (KYC upload forms are long) | JSON 419 | Implemented, not verified |
| 422 | Web forms redirect back with errors + old input (KYC, apply, enquiry, register); apply also flashes an alert | JSON 422 with `errors` | Implemented, not verified |
| 429 | Built-in "Too Many Requests" (contact, apply, verify, login lockout shows form error) | JSON 429 | Implemented, not verified |
| 500 | If APP_DEBUG is true: full Laravel debug page with stack trace, request data and environment details. If false: built-in unbranded 500 | JSON with exception/trace if debug, else `{"message":"Server Error"}` | Unknown (live APP_DEBUG not checked) |
| 503 | `php artisan down` gives built-in 503. Admin "toggle maintenance" (web.php:365) only flips a DB flag; the middleware that would act on it is in the unused Kernel | JSON 503 on `artisan down` | Broken (admin toggle) |

Issues: no branded error pages for the customer site; no friendly 419 handling; no error tracking; debug pages in production depend entirely on the live `.env` (example ships `true`); `/up` health route exists (bootstrap/app.php:12). Recommendations: branded `errors/{403,404,419,429,500,503}` in the website design; confirm `APP_DEBUG=false` and `LOG_LEVEL` above debug on live; remove or port `app/Http/Kernel.php`; add error reporting (owner choice of tool).

---

## Customer journeys

| Journey | Status | Entry point | Permission | Pages | Backend routes | Records created | Success / failure states |
|---|---|---|---|---|---|---|---|
| Visit website / enquire | Existing with gaps | `/`, `/{page}`, `/contact` | public | welcome, home/* | `enquiry.store` (throttle 6/min) | `enquiries` | Success flash with reference; duplicate shows existing reference; 422 back. Gap: no desk notification |
| Register (individual) | Existing with gaps | `/register` | guest | auth/register | `POST register` | `users`, `model_has_roles` (borrower), `borrowers` | Logged in -> `/kyc/personal`; 422 on duplicate email/phone. Gaps: no email/phone verification, no throttle, business accounts not offered |
| Register (business / partner) | Missing | none | - | - | - | - | "Business accounts coming soon"; partners must be created by staff |
| Login / logout | Existing | `/login` | guest | auth/login | `login`, `logout` | session | Customer -> /account, staff -> /dashboard; 5-attempt lockout |
| Google login | Missing (broken route) | `/auth/google` | guest | none | empty controller | - | Error page |
| Password reset | Existing, not verified | `/forgot-password` | guest | auth/forgot, reset | `password.email`, `password.store` | `password_reset_tokens` | Redirect to login with status; depends on mail config (Unknown) |
| Email verification | Missing in effect | `/verify-email` | auth | auth/verify-email | verify routes | - | Not enforced (User lacks MustVerifyEmail); redirects to dashboard |
| KYC (4 steps + submit) | Existing with gaps | `/kyc` or account prompts | auth + role:borrower | kyc/personal, documents, income, contacts, review, submitted | `kyc.*.store`, `kyc.submit` | users fields, `addresses`, `borrowers`, `employments`, `payment_methods`, `contacts`, files on public disk | `kyc_status=submitted`, ref `KYC-000123`; edits blocked while submitted. Gaps: no notifications, public files |
| KYC review (staff side of the journey) | Existing with gaps | `/kyc-reviews` | staff + `edit users` | kyc-reviews/index, show | approve / reject | users kyc_* , `borrowers.status=1` | Approved: borrower can borrow; rejected: note shown on account home, borrower resubmits. No notification |
| Borrow (self-service) | Existing, not verified | `/account/borrow` | role:borrower, KYC approved | account/borrow | `account.borrow.store` (throttle 6/min) | `loans` (pending, consent), `loan_cycles`, agreement PDF | "Application sent" alert; blockers shown as alerts; 422 on amount/consent. Gap: cycle/PDF failure hidden |
| Loan approval and disbursement visibility | Existing with gaps | account home / loan detail | role:borrower | account/borrower, loan | read-only | - | Status shown; no notification when approved or sent |
| Loans list / loan detail | Existing | `/account/loans` | role:borrower | account/loans, loan | `account.loans`, `account.loan` | - | Own loans only (404 otherwise) |
| Repay | Missing (manual) | "How to repay" card | role:borrower | account/borrower | none | none by customer; staff record repayment | Instructions: pay by M-Pesa, send SMS on WhatsApp; no paybill shown, no STK/callback, no receipt |
| View / download agreement | Existing with gaps | loan detail button | role:borrower, own loan, consent | account/agreement | `account.agreement`, `account.agreement.file` | PDF on first view if missing | Inline or download; 404 if not own/no consent. Gap: public enumerable file, not immutable |
| Profile | Existing with gaps | `/account/profile` | auth | account/profile | `password.update`, `logout` | - | Read-only details, change password; changes go back through KYC; no change-request flow |
| Rollover / extension request | Missing | - | - | - | - | - | Staff only |
| Statements / receipts for borrower | Missing | - | - | - | - | - | Only recent activity (5) and per-loan periods |
| Partner home | Existing with gaps | `/account` | auth, partner (customer) | account/partner | `account.home` | - | Portfolio by wallet; empty if no `partners` row |
| Partner statement | Existing, not verified | `/account/statement` | role:partner | account/statement | `account.statement?wallet=` | - | Paginated entries per wallet; 404 if no `partners` row |
| Partner deposit / withdrawal request | Missing | - | - | - | - | - | Staff only |

## Owner decisions needed

1. Must email and/or phone be verified before KYC or borrowing? (today neither is enforced)
2. Limit ladder: keep the 5,000 floor for very-high-risk / written-off / low-income borrowers? Intermediate steps between 5,000 and 50,000? Behaviour when income is 0?
3. Is the 4Cs recommendation advisory, or should it block approval? Are CRB (credit bureau) checks required?
4. Penalty clause wording (10% per day, compounding) vs `penalty_rate` and Kenyan in-duplum rules (legal review).
5. Official repayment channel to show customers (paybill/till/account format), and whether M-Pesa integration is in scope.
6. KYC policy: KRA PIN uniqueness, maker-checker for approval, re-KYC interval, verification of M-Pesa/bank payout details.
7. Retention policy for KYC documents, enquiry IP/user agent and debug logs.

## Fixes applied (2026-10-18)

| # | Finding | What changed | Files |
|---|---|---|---|
| 1 | Signed agreements public at `/storage/agreements/loan_agreement_{id}.pdf` | PDFs are written to the private disk (`storage/app/private/agreements/loan_agreement_{id}_{random}.pdf`) and served only by `account.agreement(.file)` (borrower who owns the loan) and `loans.agreement.show/download` (staff with `view loans`). `files:make-private` moves the old ones. | `app/Services/LoanAgreementService.php`, `app/Http/Controllers/HomeController.php` (agreementPath), `LoanController@showAgreement/downloadAgreement`, `app/Services/LoanProgress.php` |
| 2 | KYC ID images, passport photo, signatures public; signature named after the user (guessable, same-name overwrite) | New uploads go to the private disk as `{folder}/{user id}_{random}.{ext}` (KYC documents step, drawn signatures, staff user form). One authorised route `GET /files/users/{user}/{id-front\|id-back\|photo\|signature}` (`files.user`): owner, or staff role with `view users` / `edit users` / `view loans`; anyone else 404, guests to login. Every view now uses `$user->documentUrl(...)` (account avatar, profile, KYC review, users show/table, loans table/sidebar, header). Reads check the private disk first, then public (for files not yet moved). Debug logging of signature data removed. | `app/Support/PrivateFiles.php` (new), `app/Http/Controllers/PrivateFileController.php` (new), `app/Services/SignatureService.php`, `User::documentUrl/getAvatarUrl`, `HomeController@storeKycDocuments/replaceFile`, `UserController@handleFileUploads`, views listed |
| 3 | Agreement not immutable, no evidence | New nullable columns on `loans`: `agreement_path`, `agreement_sha256`, `agreement_signed_at`, `agreement_signer_ip`, `agreement_user_agent`, `agreement_version`. On consent the PDF is generated once (row-locked), hashed and frozen; every later view/download (borrower or staff) serves that file and never rebuilds it. Unconsented loans get a draft (`agreements/drafts/`), not frozen. Self-service apply records the borrower's IP and browser; older consented loans are frozen on first view (signed_at = consent date, no IP). If a frozen file ever goes missing it is rebuilt, logged as an error, signing details kept. Hash, signing time and template version are shown under the agreement in /account. Template version constant: `LoanAgreementService::TEMPLATE_VERSION`. | migration `2026_10_18_000001_add_agreement_fields_to_loans_table.php`, SQL `database/sql/2026_10_18_loan_agreement_fields.sql`, `home/account/agreement.blade.php`, `.agreement-proof` in styles.css |
| 4 | No branded error pages; JSON could leak traces; admin maintenance toggle did nothing | `resources/views/errors/{403,404,419,429,500,503}` + `errors/layout` in the website design (self-contained, works without DB/session), plain wording, way back by role (guest home, customer /account, staff /dashboard), nothing internal shown. `bootstrap/app.php` withExceptions: API/AJAX (`expectsJson` or `api/*`) get `{message, status}` only, standard text for 5xx and for converted exceptions (no model names), 422 validation unchanged. Maintenance: `CheckMaintenanceMode` rewritten and appended to the web group; when the System "maintenance mode" switch is on, visitors and customers get 503, staff roles carry on, login/logout/password pages and `/up` stay open. `app/Http/Kernel.php` is still unused (left in place). | `resources/views/errors/*`, `bootstrap/app.php`, `app/Http/Middleware/CheckMaintenanceMode.php` |
| 5 | Broken Google login | Routes `auth/google*` and the empty `GoogleController` removed. | `routes/web.php`, `app/Http/Controllers/Auth/GoogleController.php` (deleted) |
| 6 | Profile routes without methods | No view or script used them: routes `profile.delete-avatar`, `profile.address.update`, `profile.data`, `profile.password.confirm` removed (the auth `password.confirm` route is unaffected). | `routes/web.php` |
| 7 | Apply said "Application sent" even if the cycle/agreement failed | Loan, first cycle (`LoanCalculator::createInitialCycle`, unchanged) and the signed agreement are created in one transaction; any failure rolls everything back and the borrower sees "We could not send your application. Nothing was saved." | `LoanController@apply` |
| 8 | No notifications | Mail notifications: borrower on KYC submitted / approved / rejected (with the reviewer's note) and application received; admins (`admin`, `super-admin`) on new KYC submission and new application. Sent synchronously; each send is wrapped in try/catch + `Log::warning`, so mail problems never break the request. | `app/Notifications/{KycSubmitted,KycReviewed,LoanApplicationReceived,StaffAlert}.php`, `app/Support/CustomerNotifier.php`, `HomeController@submitKyc`, `KycReviewController@approve/reject`, `LoanController@apply` |
| 9 | Borrower 300-850 helpers contradicted config/lending.php | `getCreditScore`, `getCreditRating`, `getMaxLoanAmount`, `getSuggestedInterestRate` removed (only `profile/show` used the first two; it now shows the 4Cs score /100 and today's limit). `risk_category` = CreditAssessment category; `recommended_loan_limit` = the BorrowingEligibility/CreditAssessment limit; `monthly_debt_capacity` and `isOverLeveraged` use `lending.income_multiple`. One CreditAssessment per borrower per request. | `app/Models/Borrower.php`, `resources/views/profile/show.blade.php` |

New command: `php artisan files:make-private {--dry-run}` moves `agreements/*`, `id_cards/`, `id-documents/`, `avatars/`, `signatures/`, `images/signatures/` and any file a `users` document column points to from `storage/app/public` to `storage/app/private` (same relative path, so `users.*` paths stay valid), copies, verifies byte-for-byte, then removes the public copy; consented loans' existing PDFs are frozen on the loan (`agreement_version = legacy`); agreements of unconsented or unknown loans go to `agreements/legacy/`. Nothing is deleted without a verified private copy; re-running moves nothing. It also reports user document paths that point to files on neither disk (left unchanged; locally 102 because the local storage folder is only a partial copy of live).

Tested on a clone of loans_main (dropped afterwards): `/storage/...` no longer serves agreements or KYC files; the owner and staff get 200, another borrower 404, guests the login page; staff view/download return the frozen file (hash matches the DB); a forced agreement failure during apply left no loan or cycle and showed the error; a successful apply stored hash, IP and user agent and logged both emails; error pages return 403/404/419/429/500/503 with JSON `{message,status}` for `Accept: application/json`; maintenance switch on gives guests and borrowers 503 while staff can log in and work. `php artisan test`: no new failures from these changes (the remaining failures come from the parallel role change where a user with no role is now a customer: AuthenticationTest redirect and ProfileTest).

Live deploy, in order:
1. Back up: `mysqldump --single-transaction --routines --triggers <db> > backup.sql` and `tar czf storage-public.tgz storage/app/public`.
2. Deploy the code, then `mysql <db> < database/sql/2026_10_18_loan_agreement_fields.sql` (run it before anyone opens an agreement: the code reads the new columns).
3. `php artisan files:make-private --dry-run`, check the list, then `php artisan files:make-private`.
4. `php artisan optimize:clear` (routes, views and config changed).
5. Confirm `APP_DEBUG=false` and mail settings (`MAIL_MAILER`, `MAIL_FROM_ADDRESS`) on live; check `https://<site>/storage/agreements/` URLs now return 404.
6. Make sure `storage/app/private` is not web-reachable (it is outside `public/`; only `public/storage -> storage/app/public` is linked).
