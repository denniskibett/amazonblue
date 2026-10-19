# Audit D: staff roles, recovery, reports, settings, audit log, journeys

Stage 1 (audit only). Evidence: code read by grep and line range, `php artisan route:list`, read-only SELECTs on the local mirror `loans_main` (2026-10-17). Live (loans.sharet.africa) data was not inspected. Routes are **not cached** (`bootstrap/cache` holds no routes file), so routes match in registration order.

Labels: **Verified working** · **Implemented, not verified** · **Partial** · **Planned only** · **Broken** · **Unknown (not enough evidence)**.

---

## 0. The two major roles

| | Role 1: **admin** (staff) | Role 2: **borrower** (customer) |
|---|---|---|
| Spatie users (mirror) | 4 (`model_has_roles`) | 79 (+5 users with `users.role='borrower'` but no Spatie role) |
| `users.role` column | `admin` (4) | `borrower` (84) |
| Gate | `auth, verified, staff` group, routes/web.php:73; `EnsureStaff` sends customers to `account.home` | `role:borrower` on `/kyc/*` and `/account/borrow`, routes/web.php:30,55 |
| Layout | `resources/views/layouts/app.blade.php` (TailAdmin) + `partials/sidebar.blade.php` + `partials/header.blade.php` | `resources/views/home/layouts/account.blade.php` (website design) and `home/layouts/kyc.blade.php` |
| Navigation | sidebar sections keyed on the **legacy column** `Auth::user()->role === 'admin'` (sidebar.blade.php:94, 461) | top nav built in account.blade.php:19 (Home, Borrow, Loans, Profile; partner gets Home, Statement, Profile) |

Other roles in the data: teller 2, partner 1. Seeded with no users: super-admin, manager, loan-officer, accountant, recovery-officer, auditor, broker (11 roles, 78 permissions, 320 role-permission links, 0 direct user permissions).

---

## 1. Users, roles and permissions

1. **Purpose today.** Two parallel role systems: Spatie roles/permissions (route middleware, `EnsureStaff`, dashboard branching) and the legacy `users.role` enum `admin|borrower|broker|teller|partner` (sidebar, CaseController, many views, UserController).
2. **Code.** bootstrap/app.php:16-25 (aliases `role`, `permission`, `role_or_permission`, `check.role`, `staff`); app/Http/Middleware/EnsureStaff.php; CheckRole.php (alias registered, no route uses it); app/Models/User.php:80-160 (`isAdmin`, `STAFF_ROLES`, `isCustomer`, `homeRoute`); UserController (657 lines); database/seeders/RoleSeeder.php; app/Http/Kernel.php (legacy, not loaded by Laravel 12).
3. **Tables.** `roles`, `permissions`, `role_has_permissions`, `model_has_roles`, `model_has_permissions` (empty), `users.role` (enum).
4. **Rules enforced.** Customer = has borrower/partner and none of STAFF_ROLES (User.php:147). Staff area needs verified email.
5. **Role-permission highlights (mirror).** `access system settings`: super-admin only. `view recovery cases`: admin, auditor, manager, recovery-officer, super-admin (not teller). `view reports`: accountant, admin, auditor, manager, super-admin (not teller). `view users`: admin, auditor, broker, manager, recovery-officer, super-admin.

| # | Finding | Label | Where |
|---|---|---|---|
| U1 | Two role systems disagree. UserController writes only `users.role`, never `assignRole/syncRoles`; staff-created or role-changed users get no or stale Spatie role and so no permissions | **Broken** | UserController.php:131, 262; only `assignRole` in app is RegisteredUserController.php:57 |
| U2 | UserController validation only allows `admin,broker,borrower,teller`: partner and all 7 newer staff roles cannot be assigned from the UI; the column enum cannot hold them either | **Broken** | UserController.php:424 |
| U3 | Resource routes use an OR list (`permission:view X\|create X\|edit X\|delete X`): any one permission opens every action. Auditor, broker, recovery-officer (`view users`) can create/edit/delete users; broker (`view disbursements`) can create/delete disbursements; teller/broker/recovery-officer (`view repayments`) can edit/delete repayments. Controllers mostly do not re-check | **Broken** (permission leak) | routes/web.php:94-95, 215-224 |
| U4 | 5 users have `users.role='borrower'` but no Spatie role. `isCustomer()` is false for them, so after verifying email they pass `staff` and reach the dashboard, profile and the TailAdmin demo pages | **Broken** (leak, latent) | User.php:147; routes/web.php:73-76, 365-398 |
| U5 | `/dashboard`, `/profile/*` and 20 TailAdmin demo pages (`/index`, `/invoice`, `/calendar`, `/signin`, …) have no permission check, only `staff` | **Implemented, not verified** (leak) | routes/web.php:76, 365-398 |
| U6 | No super-admin user on the mirror, so nobody holds `access system settings`: `/system` is 403 for everyone. Live unknown | **Unknown (not enough evidence)** for live; **Broken** on mirror | routes/web.php:360 |
| U7 | Sidebar and recovery screens use the legacy column; Spatie-only roles (manager, accountant, recovery-officer, auditor, loan-officer, super-admin) would see only "Dashboard". Teller sees a Reports group (sidebar.blade.php:461) whose `reports.*` routes 403 for teller | **Broken** | partials/sidebar.blade.php:94, 271, 388, 461 |
| U8 | Permissions defined and seeded but never checked anywhere: `manage roles`, `view audit log`, `write off loan`, `close recovery case`, `assign recovery case`, `log recovery action`, `impersonate users`, ledger/house/wallet permissions, `manage API tokens`, `manage feature flags` | **Planned only** | RoleSeeder.php; grep app/routes/views |
| U9 | No role/permission management UI, no Policies, no `Gate::before` for super-admin | **Planned only** (missing) | app/Policies absent |
| U10 | `DashboardController` branches on `hasRole('admin'\|'broker'\|'teller')`; super-admin and the other staff roles get no role data | **Partial** | DashboardController.php:64-69 |
| U11 | `CheckMaintenanceMode` / `ApplySystemSettings` only registered in app/Http/Kernel.php, which Laravel 12 ignores | **Broken** (dead) | bootstrap/app.php:30; app/Http/Kernel.php:28 |

**Recommendations.** Pick Spatie as the single source of truth; make UserController assign Spatie roles; replace OR-list resource middleware with per-action permissions (or Policies); move sidebar to `@can`; gate demo pages or delete them; seed one super-admin on live; backfill Spatie roles for the 5 orphan users.

---

## 2. Debt recovery

1. **Purpose today.** Staff list, view, create (modal, JSON), update, add action, mark recovered, write off and delete debt recovery cases on defaulted loans. 2 cases on the mirror (`DR-2026-0001/0002`), 1 note, 0 actions.
2. **Code.** CaseController.php (1453 lines); app/Models/DebtRecoveryCase.php (705) + 18 recovery/legal models; app/Services/NplDetectionService.php; app/Console/Commands/DetectNplLoans.php (`loans:detect-npl`, not scheduled); Loan.php:442/571 and LoanController.php:465/1413 also create cases; views cases/index.blade.php, cases/show.blade.php, cases/create.blade.php (0 bytes), partials/modal/cases-create-modal.blade.php, partials/table/table-recovery-cases.blade.php, partials/recovery/*; migrations 2025_01_01_000038…000060.
3. **Tables (rows on mirror).** debt_recovery_cases 2, recovery_statuses 7, recovery_priorities 4, recovery_case_notes 1, legal_proceeding_types 9, communication_types 5, communication_statuses 5. Empty: recovery_actions, recovery_payment_plans, recovery_installments, recovery_documents, recovery_templates, recovery_agencies, agency_contacts, agency_case_assignments, recovery_performance_metrics, legal_proceedings, legal_deadlines, court_hearings, communications, skip_tracing, credit_bureau_reports, financial_assessments. Case → user, loan, status, priority, assigned_to (users).
4. **Rules enforced.** One open case per user (CaseController.php:251); write-off and delete admin-only (column); recovered status sets loan to repaid; collected amount reduces `total_debt_amount`.
5. **Roles.** Route: Spatie `view/edit recovery cases`. Controller: legacy column `admin|teller` (CaseController.php:168, 216, 565, 583, 706, 915, 1391), admin only for write-off/delete (768, 841). Net effect: only `admin` users can work cases; teller blocked at route, recovery-officer/manager blocked in controller.
6. **Data flow.** Loan default (manual status change, `Loan::markAsDefaulted`, or `loans:detect-npl`) → case created with `DR-YYYY-NNNN` → staff opens case → actions/notes → recovered/written-off → loan status updated.

| # | Finding | Label | Where |
|---|---|---|---|
| R1 | List and detail pages render, case creation via modal posts JSON | **Implemented, not verified** | CaseController.php:27, 214, 401 |
| R2 | Write-off sets the loan to `STATUS_REPAID` and `is_non_performing=false`: written-off debt is reported as repaid | **Broken** | CaseController.php:797-801 |
| R3 | "Amount collected" on an action lowers `total_debt_amount` and can close the loan as repaid, with no Repayment, wallet or ledger entry | **Broken** (money outside the ledger) | CaseController.php:984-1004 |
| R4 | "Add action" and "Write off" buttons dispatch `open-add-action` / `open-write-off`; no view listens, so nothing opens | **Broken** | cases/show.blade.php (script ~468, ~498) |
| R5 | `cases/my` and `cases/stats` are registered after `Route::resource('cases')`, so `cases/{case}` (show) catches them; also `myCases` requires a borrower, who is redirected away by `staff`, and `view cases.my` does not exist | **Broken** | routes/web.php:223-252; CaseController.php:883 |
| R6 | `cases.stats` → `getStats`, `cases.data` → `getCaseData` do not exist (controller has `stats`, no getCaseData); `cases.edit` returns `view('cases.edit')`, which does not exist; `create.blade.php` is empty; `addNote`, `updateAction` have no routes | **Broken** | CaseController.php:575, 1038, 1099, 1188 |
| R7 | Four separate case-creation paths (CaseController `RC…` numbers, Loan model, LoanController, NplDetectionService `DR-…`); numbering by `count()+1` collides with soft deletes and concurrent runs | **Partial** | CaseController.php:1271; NplDetectionService.php:138; Loan.php:588; LoanController.php:1446 |
| R8 | `recovery_officer` written on create/update but not a column and not fillable (silently dropped) | **Broken** (dead code) | CaseController.php:299, 643 |
| R9 | Model relations `timelines`, `tasks`, `agencyAssignments`, `skipTracingRecords`, `creditBureauReports`, `financialAssessments` point to tables or classes that do not exist (`recovery_timelines`, `recovery_tasks`, models AgencyCaseAssignment, SkipTracing, CreditBureauReport, FinancialAssessment) | **Broken** (any use fails) | DebtRecoveryCase.php:149-176 |
| R10 | Agencies, legal proceedings, court hearings, skip tracing, communications, recovery payment plans/installments, templates, performance metrics: schema and some models only; no routes, controllers or views | **Planned only** | migrations 000038-000060 |
| R11 | Loan-level "payment plan" exists but writes loan cycles, not `recovery_payment_plans` | **Partial** | LoanController.php:1078 |
| R12 | NPL detection command exists but no scheduler | **Implemented, not verified** (manual only) | DetectNplLoans.php; routes/console.php |
| R13 | Case export CSV route reachable (static segment) | **Implemented, not verified** | routes/web.php:246 |

**Owner decisions.** What write-off means for the loan (new `written_off` status? ledger write-off entry?); whether recovery collections must go through Repayment/wallet; who owns cases (recovery-officer vs teller vs admin); single numbering scheme; NPL threshold and how often detection runs; scope of agencies/legal/skip tracing for v1.

---

## 3. Reports and staff dashboard

1. **Purpose.** `/reports` lists 15 report types; `/reports/{type}` renders summary and charts; export to CSV. `/dashboard` shows staff KPIs and charts.
2. **Code.** ReportController.php (1076), views reports/index, reports/show; DashboardController.php (509), view dashboard.blade.php, service CustomerWallets; SQL views migrations 2025_01_02_000000…000006.
3. **SQL views (rows on mirror).** borrower_contact_network 85, broker_performance_report 1, customer_health_scorecard 76, debt_recovery_summary 2, loan_type_analysis 19, portfolio_summary 1, recovery_officer_workload 6. **No code reads any of them.**
4. **Rules.** `view reports` / `export reports` permissions; dashboard by Spatie role.

| # | Finding | Label | Where |
|---|---|---|---|
| P1 | `GET reports/export` registered after `reports/{reportType}`, so it is caught by `show('export')` → "Report not found"; export never runs | **Broken** | routes/web.php:257-267 |
| P2 | Excel and PDF export just return CSV; CSV header taken from first nested item | **Partial** | ReportController.php:1033-1076 |
| P3 | Hard-coded or simulated figures shown as real: CIC compliance 98.5 %, disputes 28/22, credit score distribution, bureau submissions (TransUnion, Creditinfo), "customer savings" = 10 % of loan amount with random last-activity date | **Broken** (misleading) | ReportController.php:330-347, 765-793, 981-1000 |
| P4 | Cashflow treats disbursements as inflows and repayments as outflows (inverted) | **Broken** | ReportController.php:800-812 |
| P5 | CIC "default listings" counts `rejected` loans | **Broken** | ReportController.php:337 |
| P6 | Report filters ignored: `show()` never passes request filters | **Partial** | ReportController.php:34-37 |
| P7 | Loan portfolio, active/completed/defaulted, disbursement, expected repayments: real Eloquent queries | **Implemented, not verified** | ReportController.php:350-545, 692 |
| P8 | Dashboard admin/teller KPIs are real queries; cash figures via CustomerWallets | **Implemented, not verified** | DashboardController.php:84-210 |
| P9 | SQL views are unused (dead schema) | **Planned only** | 2025_01_02_* |
| P10 | No regulatory, ledger, trial balance, partner distribution or audit reports, though permissions exist (`view regulatory reports`, `view ledger`) | **Planned only** | RoleSeeder.php |

**Owner decisions.** Which reports are statutory (CRB/CIC format, frequency); remove or label simulated sections; cashflow definition; whether the SQL views become the reporting source.

---

## 4. Settings / system

1. **Purpose.** Edit branding/locale settings in single-row `system` table; clear cache; toggle maintenance flag.
2. **Code.** Admin/SystemController.php (179); app/Models/System.php (`settings()` firstOrCreate, line 283); app/Helpers/SystemHelper.php (400 lines, 38 static getters, used by layouts/app.blade.php and AppServiceProvider); views admin/system/simple.blade.php (used), admin/system/index.blade.php (unused, ~1000+ lines); migration 2025_01_01_000061.

| # | Finding | Label | Where |
|---|---|---|---|
| S1 | Settings update with validation and file uploads | **Implemented, not verified** (unreachable on mirror, see U6) | SystemController.php:76-157 |
| S2 | `system.backup` → `backupDatabase` and `system.debug` → `debug` methods missing | **Broken** | routes/web.php:364,366 |
| S3 | Maintenance toggle stores a flag but no registered middleware reads it | **Broken** (no effect) | SystemController.php:169; U11 |
| S4 | Clear cache is a GET that runs `Cache::flush()` (CSRF-free, prefetchable) | **Implemented, not verified** (risk) | routes/web.php:363; SystemController.php:162 |
| S5 | `custom_css` / `custom_js` stored but never rendered | **Partial** | admin/system/simple.blade.php:895-904 |
| S6 | No settings for loan products/pricing, approval limits, notification channels, API tokens, feature flags | **Planned only** | permissions only |

**Owner decisions.** Who is system admin on live; whether custom JS should exist at all (XSS risk); backup approach (host-level vs app).

---

## 5. Audit log

| # | Finding | Label | Where |
|---|---|---|---|
| A1 | `audit_logs` table (user_id, action, table_name, record_id, old/new values, ip, user agent) and `AuditLog` model exist; `User::auditLogs()` relation | **Planned only** | migration 2025_01_01_000006; app/Models/AuditLog.php; User.php:334 |
| A2 | Nothing writes to it: 0 rows; no observer, trait or middleware references AuditLog | **Broken** (no audit trail) | grep app/ resources/ |
| A3 | No UI, though `view audit log` permission is seeded | **Planned only** | RoleSeeder.php |

**Owner decision.** Which events must be audited (loan status, disbursement, repayment edit/delete, write-off, role change, settings) and retention.

---

## 6. Journey inventory

Status: E = existing, G = existing with gaps, M = missing. Borrower/partner rows brief (covered by the customer-pages audit).

| # | Role | Journey | Status | Entry point | Permission | Pages / actions | Backend routes | Records created | Success / failure |
|---|---|---|---|---|---|---|---|---|---|
| 1 | admin | Log in, land on staff dashboard | G | /login → /dashboard | staff + verified | dashboard KPIs, charts | dashboard | none | KPIs shown; super-admin/other staff get no role data |
| 2 | admin | Manage users (create/edit/delete, borrower/broker profile) | G | sidebar Users | view/create/edit/delete users (OR) | users.index/create/edit/show | users.* | users, borrowers, brokers | Spatie role not assigned (U1) |
| 3 | admin | Assign roles / manage permissions | M | none | manage roles (unused) | none | none | none | n/a |
| 4 | admin | KYC review approve/reject | E (other auditor) | sidebar KYC Reviews | edit users | kyc-reviews index/show | kyc-reviews.* | user profile status | flash messages |
| 5 | admin/teller | Create loan for borrower, approve, edit, rollover, forbearance | G (other auditor) | sidebar Loans | create/edit loans, rollover, grant forbearance | loans.* | loans.*, users.loans.* | loans, cycles | `users.loans.create`, `admin.loans.edit` broken |
| 6 | teller | Disburse loan | G | dashboard / disbursements | create disbursements | disbursements.* | disbursements.* | disbursements, wallet tx | teller sidebar has no Disbursements link |
| 7 | teller | Receive repayment | G | loan page | create repayments | repayments.create | users.loans.repayments.*, repayments.* | repayments | no "receive payment" queue |
| 8 | teller | End-of-day cash-up | M | none | none | none | none | none | n/a |
| 9 | admin | Work recovery case (list, view, create, update) | G | sidebar Debt Recovery | view/edit recovery cases + column admin/teller | cases.index, cases.show, create modal | cases.* | debt_recovery_cases, notes, actions | add-action/write-off buttons dead (R4); edit view missing (R6) |
| 10 | admin | Record collection, mark recovered, write off | G | case page | edit recovery cases; admin column | buttons on show | cases.recover, cases.write-off, cases.action.add | case status, loan status | write-off marks loan repaid (R2); collections bypass ledger (R3) |
| 11 | recovery-officer | Own case queue, contact, restructure, performance | M | none (sidebar hidden, controller blocks) | view/edit recovery cases | none usable | none | none | n/a |
| 12 | admin | Agencies, legal proceedings, skip tracing, recovery payment plans | M | none | none | none | none | none | schema only (R10) |
| 13 | system | Automatic NPL detection | G | `php artisan loans:detect-npl` | n/a | none | console | cases | not scheduled |
| 14 | admin/manager | View and export reports | G | sidebar Reports | view/export reports | reports.index, reports.show | reports.* | none | export broken (P1); simulated data (P3) |
| 15 | super-admin | System settings | G | `/system` (no sidebar link) | access system settings | admin/system/simple | system.* | system row | backup/debug broken; maintenance no effect |
| 16 | admin/auditor | View audit log | M | none | view audit log | none | none | none | nothing logged |
| 17 | admin | Partners and investments administration | G (other auditor) | sidebar Partners/Investments | view/create/edit partners, investments | partners.*, investments.* | partners.*, investments.* | partners, investments, transactions | 3 investments routes broken |
| 18 | accountant | Reconciliation, ledger, partner distributions | M (partner profit distribute exists) | none | view ledger etc. (unused) | none | partners.profit.distribute only | partner tx | n/a |
| 19 | manager | Approve loans within limits | M (approval is a plain status edit) | none | approve loans (unused) | none | loans.update | loan status | no limits |
| 20 | auditor | Read-only oversight | G | dashboard only | view-only perms but OR-list grants edits (U3) | none in sidebar | n/a | n/a | n/a |
| 21 | broker | View own borrowers' loans, repayments, earnings | G | /dashboard (broker branch) | view loans, view repayments, view broker fees | sidebar broker section | loans.index, repayments.index | none | 0 broker users; `users.role` must be broker for sidebar |
| 22 | borrower | Register, verify email, KYC (personal, documents, income, contacts, review) | E | /register → /kyc | role:borrower | kyc/* | kyc.* | profile, documents, contacts | submitted page |
| 23 | borrower | Apply for loan | E | /account/borrow | role:borrower | account.borrow | account.borrow.store | loans | throttled 6/min |
| 24 | borrower | View loans, agreement, sign | G | /account/loans | role:borrower | account.loan, agreement | account.agreement(.file), loans.signature.save (staff group) | signature | signing route sits in staff group |
| 25 | borrower | Repay (M-Pesa prompt) | M | none | none | none | none | none | repayment is staff-entered |
| 26 | borrower | See own recovery status | M | none | none | cases.my is broken (R5) | none | none | n/a |
| 27 | partner | View account and statement | E | /account, /account/statement | role:partner | account home, statement | account.statement | none | flash |
| 28 | partner | Partner KYC, invest online, fund, subscription agreement | M | none | none | none | none | none | staff enter investments |
| 29 | all staff | Profile, password, signature | G | header menu | staff | profile.* | profile.* | user profile | 4 profile routes broken |

**Counts (29 journeys):** existing 4 (#4, 22, 23, 27) · existing with gaps 15 (#1, 2, 5, 6, 7, 9, 10, 13, 14, 15, 17, 20, 21, 24, 29) · missing 10 (#3, 8, 11, 12, 16, 18, 19, 25, 26, 28).

---

## 7. Design mapping (by screen title only)

The design artefact index was mapped **by screen title only**; board contents were not opened, so "built" means a page with a matching purpose exists, not that it matches the design.

| Design group (screens) | Status | Maps to journey |
|---|---|---|
| Website, Log in, Register individual, Forgot/Reset password, Verify code | Built (other auditor) | 22 |
| KYC individual 1-4 + submitted | Built (steps differ: app has personal, documents, income, contacts, review) | 22 |
| Register business, KYC business start/1-5/submitted | Not built | none |
| Borrower dashboard new/active, Borrow 1-5, funds sent, Thank you verified | Partly built (single borrow form, agreement + sign) | 23, 24 |
| Repay · M-Pesa prompt, Repaid · facility closed | Not built (no customer payment) | 25 |
| Partner KYC 1-4 + thank you | Not built | 28 |
| Partner dashboard new/active | Partly built (account home + statement) | 27 |
| Invest · fund overview / 1-3 / confirmed | Not built (staff-side investments only) | 17, 28 |
| Trade 1-4, Trade dashboard, Pay supplier 1-3, Supplier payment tracking, Trade repay facility | **Not built, no backend** | none |
| Alerts by journey / channels; Emails (reminder, payment received, overdue) | Not built (no scheduler, no notification settings) | none |
| Relationship officer pipeline / client 360 / new application / tasks | Partly built (users.show, loans create on behalf) | 2, 5 |
| Credit analyst queue / assessment / decision / portfolio risk | Partly built (CreditAssessment, LoanRiskAssessment services/models; no queue screen) | 5, 19 |
| Compliance officer KYC queue / document review | Partly built (kyc-reviews) | 4 |
| Compliance officer business review / AML alerts | **Not built, no backend** | none |
| Trade finance officer desk / document check / issue LC / FX & settlements | **Not built, no backend** | none |
| Valuation officer requests / report / collateral register / insurance | **Not built, no backend** (an `Asset` model exists, no flow) | none |
| Teller disbursement queue / disburse / receive payment | Partly built | 6, 7 |
| Teller end of day | Not built | 8 |
| Collections officer queue / contact / restructure / performance | Partly built (admin-only case screens, half broken) | 9-12 |
| Accountant reconciliation / ledger / reports / partner distributions | Partly built at most (reports, partner profit distribute); **ledger/reconciliation no UI** | 14, 18 |
| Admin overview | Partly built (dashboard) | 1 |
| Admin users & roles | Partly built (users only, no roles) | 2, 3 |
| Admin products & pricing, approval limits | **Not built, no backend UI** (loan_types table, no admin routes) | none |
| System admin health / audit log / security / API & webhooks | **Not built, no backend** (settings page only; audit log empty) | 15, 16 |
| Documents: facility agreement, borrower agreement, offer letter | Partly built (LoanAgreementService, agreement PDF) | 24 |
| Documents: receipt, statement of account, partner portfolio report | Partly built (partner statement); receipt not found | 27 |
| Documents: asset-backed agreement, trade finance term sheet / LC application | **Not built, no backend** | none |
| Mobile app (23 screens) | Not built (no API routes) | none |
| Company profile, Investor pitch | Marketing material, out of app scope | none |

**Design journeys with no backend at all:** trade finance (customer and officer, LC, FX, supplier payments), asset-backed lending and valuation officer (collateral register, LTV, insurance), compliance AML alerts and business KYC review, business registration/KYC, partner KYC and self-service investing, customer M-Pesa repayment, alerts/notification channels and emails, accountant ledger/reconciliation UI, admin products & pricing and approval limits, system admin health/security/audit log/API & webhooks, teller end of day, mobile app API.

---

## 8. Owner decisions (collected)

1. Which role system is authoritative (recommend Spatie) and whether the `users.role` column is dropped.
2. Staff role list to keep (the design has relationship officer, credit analyst, compliance, trade finance, valuation, collections, accountant, system admin; seeded roles differ: loan-officer, manager, recovery-officer, auditor).
3. Per-action permission matrix (who may edit/delete repayments, disbursements, users).
4. Write-off accounting treatment and loan status; whether recovery collections must post a Repayment.
5. Who works recovery cases; NPL threshold and detection schedule.
6. Statutory report set (CRB/CIC) and removal of simulated figures.
7. Audit events and retention.
8. Live super-admin owner; backup and maintenance-mode approach; whether custom JS is allowed.
9. Which design journeys are in scope for the next phase (trade finance, asset-backed, compliance AML, partner self-service, M-Pesa).

## Fixes applied (2026-10-18): roles and permissions

Spatie roles/permissions are now the single source of truth. `users.role` is kept only as a mirror of each user's primary role (for older screens and exports).

| Finding | Change | Files |
|---|---|---|
| U1, U2 | UserController create/update assign the Spatie role (the chosen role replaces the user's primary role; other roles such as partner are kept) and mirror it into `users.role`. Validation accepts any role in the `roles` table; only a super-admin can grant `super-admin`, only admin/super-admin can grant `admin`. `users.role` changed from ENUM to VARCHAR(50) so it can hold manager/auditor/etc. | app/Http/Controllers/UserController.php; database/migrations/2026_10_18_000021_change_role_to_string_on_users_table.php; database/sql/2026_10_18_users_role_string.sql |
| U1 | `User::primaryRole()` (by `ROLE_PRIORITY`), `User::syncLegacyRole()`, and a `saving` hook keep `users.role` equal to the primary Spatie role. PartnerController now `assignRole('partner')` instead of overwriting the column. | app/Models/User.php; app/Http/Controllers/PartnerController.php |
| U1 | Every `->role ===`, `in_array(->role, ...)`, `where('role', ...)` check in app/ and resources/views/ (copy files excluded) now uses `hasRole` / `hasAnyRole` / `can` / the Spatie `role()` scope. New `User::BACK_OFFICE_ROLES` (super-admin, admin, manager, loan-officer, accountant, recovery-officer, auditor) gets the "see everything" branches that used to be `admin`-only (loan index/show/PDF, disbursements index, users table, dashboard metric cards). | Loan, Repayment, Disbursement, Profile, Case, Report, Investment controllers; LoanAgreementService, NplDetectionService, WalletsBootstrap; loans/*, cases/*, users/*, profile/*, disbursements/index, repayments/index, partials (header, tables, modals, metric groups) |
| U4 | `isCustomer()` = not staff; `isStaff()` = holds any `STAFF_ROLES` role. A user with no role is a customer: `EnsureStaff` and `homeRoute()` send them to `/account`, never the dashboard. | app/Models/User.php |
| U5 / per-action | Resource routes use per-action permissions via `middlewareFor`: index/show = `view X`, create/store = `create X`, edit/update = `edit X`, destroy = `delete X` for users, loans, disbursements, repayments, recovery cases. KYC reviews: list/show = `view users`, approve/reject = `edit users`. Reports, partners, investments were already per-action. | routes/web.php |
| status gating | `LoanController` store/update: changing a loan's status needs `approve loans` (any status other than disbursed, including back to pending), or `disburse loans`/`approve loans` for `disbursed`. `edit loans` alone (broker, loan-officer, recovery-officer) can no longer set approved/disbursed/repaid. Holders today: approve = super-admin, admin, manager; disburse = also teller. | app/Http/Controllers/LoanController.php (authorisation lines only) |
| U7 | Sidebar: staff menu shown to back-office roles + teller, each item behind `@can('view …')`; borrower/broker blocks use `hasRole`; Reports group behind `@canany(['view reports','view partners','view investments'])` with per-item `@can` (teller no longer sees Reports links that 403). | resources/views/partials/sidebar.blade.php |
| recovery | CaseController: create/store = `create recovery cases`; edit/update/recover/actions = `edit recovery cases`; write-off = `write off loan`; delete = `delete recovery cases`; stats/export = `view recovery cases`. Recovery officers and managers can now work cases; officer pick-lists include manager and recovery-officer. | app/Http/Controllers/CaseController.php |
| rollover | Rollover = `rollover loans` (admin, manager, loan-officer, super-admin); rollover preview/statement/cycles = `view loans`. | app/Http/Controllers/LoanController.php |
| /system | `/system` now allows `role:super-admin|admin` (was `access system settings`, which only the unassigned super-admin held). Two view bugs that surfaced once the page was reachable fixed (`SystemHelper` not imported in the view; missing `content` key). | routes/web.php; resources/views/admin/system/simple.blade.php |
| backfill | New `php artisan roles:sync {--dry-run}`: users with `users.role` but no Spatie role get that role; conflicts (both set, different) and unknown/no-role users are reported, never changed. | app/Console/Commands/RolesSync.php |
| tests | AuthenticationTest: staff (admin role) land on the dashboard; new test that a no-role user lands on `/account`. ProfileTest page test gives its user a staff role. 23 pass, 4 pre-existing ProfileTest failures. | tests/Feature/Auth/AuthenticationTest.php, tests/Feature/ProfileTest.php |

Verified on a clone (`loans_wp_b`): borrower and no-role user are redirected to `/account` from every staff URL; auditor can view users, loans, disbursements, repayments, cases, reports, KYC reviews but gets 403 on create/edit/delete of users and disbursements and on `/system`; teller is 403 on users, cases, reports, `/system`; admin reaches everything including `/system`; loan-officer and teller get 403 setting a loan to disbursed/repaid/approved, admin succeeds. Dry run on the live copy: 5 borrowers backfilled, 0 conflicts.

**Live steps, in order**
1. Back up: `mysqldump --single-transaction --routines --triggers <db> > backup.sql`
2. `mysql <db> < database/sql/2026_10_18_users_role_string.sql` (or `php artisan migrate --force`)
3. Deploy code, then `php artisan permission:cache-reset && php artisan optimize:clear`
4. `php artisan roles:sync --dry-run`, check the conflicts list, then `php artisan roles:sync`

**Still open**
- Broker journey is broken independently of roles (no broker profile -> 403 "Broker profile not found" on dashboard; `repayments.broker_id` does not exist; disbursements view expects `$disbursements`). No broker users exist yet.
- RepaymentController edit/destroy still also require role admin or broker after the `edit/delete repayments` permission check, so manager/accountant (who hold the permission) are still blocked there. Left as-is, because removing an extra check counts as loosening access and needs the owner's OK.
- Brokers hold `view users`, so they can open the users list and KYC review list. Remove that permission from the broker role if that's not wanted.
- Many views still decide UI buttons by role (`hasRole('admin')`) rather than `@can`; routes and controllers enforce permissions regardless. No Policies, no role-management UI, `manage roles` and other seeded permissions are still unchecked (U8, U9).
- Owner to name a super-admin; once one exists, `/system` can go back to `permission:access system settings`.
- Every other `database/sql/*.sql` script has the same `migrations` insert bug that this script fixes. `INSERT … SELECT COALESCE(MAX(batch),0)+1 … WHERE NOT EXISTS` always returns one row, so a second run inserts a duplicate row with batch 1. This script puts the guard outside the aggregate.

---

## Fixes applied (2026-10-18): recovery, reports, audit log

| Finding | Change | Files |
|---|---|---|
| R2 | Write-off sets the loan to `written_off` (new `loans.status` value), not `repaid`; `is_non_performing` is left as is. No repayment is created. The outstanding case debt is the written-off amount and is recorded in the case notes, a case note, a recovery action ("Other", outcome failed) and an audit row (`event: write_off`, amount, reason). The case edit form can no longer set the written-off status (must use Write off). | CaseController@markAsWrittenOff, @update; migration 2026_10_18_000051; database/sql/2026_10_18_loan_written_off_status.sql |
| R3 | "Amount collected" on an action is posted as a real Repayment through the staff path (`RepaymentController@store`, unchanged): mode `recovery`, transaction = the payment reference (now required with an amount), current active cycle (or latest). The borrower wallet syncs through the existing repayment hook. The action is linked by the new `recovery_actions.repayment_id`; case debt drops by the posted amount; case closes as recovered at 0. The loan status is decided by the repayment path only (the old direct `repaid` write was removed). `updateAction` no longer changes amounts. | CaseController@addAction, postCollectionAsRepayment; RecoveryAction model; migration 2026_10_18_000052; database/sql/2026_10_18_recovery_action_repayment.sql |
| R4 | Add action and Write off buttons open real dialogs (new partial listens for `open-add-action` / `open-write-off`). Root cause found: the staff layout had no `@stack('scripts')`, so every `@push('scripts')` (cases, reports, partners, investments, users show) never rendered; added before `</body>`. The case page also crashed (memory) because `@js($caseData)` serialised user.borrower accessors that run credit scoring; it now passes a plain array of the fields the script reads. | resources/views/partials/recovery/case-action-modals.blade.php; cases/show.blade.php; layouts/app.blade.php |
| R5, R6 | `cases/my` and `cases/stats` registered before the resource; `reports/export` before `reports/{reportType}`. `getStats` (fixed relation names `recoveryCases`) and `getCaseData` (JSON) implemented; staff `cases/my` = cases assigned to me. New `cases/edit.blade.php` and `cases/create.blade.php` (TailAdmin form style). | routes/web.php; CaseController; cases/edit, cases/create |
| R9 | Minimal models for existing tables: AgencyCaseAssignment, SkipTracing, CreditBureauReport, FinancialAssessment. `timelines()`/`tasks()` removed from DebtRecoveryCase (no tables). | app/Models/*.php |
| P1, P3, P4, P5, P6 | Export works (route order; CSV streamed with headers from all rows; unknown report types 404). Removed simulated figures: CIC compliance/disputes, bureau submissions (now "Not available yet"), credit score distribution (now from loan_risk_assessments, empty until assessed), customer savings (no savings product: "Not available yet"). Cashflow: inflows = repayments, outflows = disbursements, by day (the old per-day matching never matched). CIC default listings and portfolio NPL count defaulted/recovery/written_off, not rejected. `start_date`/`end_date` query filters reach the report. | ReportController; reports/show.blade.php; partials/table/table-reports-cic-reports.blade.php |
| real cash | Cashflow, loan disbursement and customer balance summary show real cash next to recorded totals (`CustomerWallets::whereCash` + `DISBURSED_CASH_SQL`; rollovers, discounts, write-offs excluded). Dashboard `loans.amount` sums untouched. | ReportController |
| A1-A3 | `AuditObserver` on Loan, Repayment, Disbursement, Partner, PartnerTransaction, Investment, DebtRecoveryCase, User (role, status, KYC fields only): create/update (changed fields only, old and new)/delete/restore, user, IP, user agent. Spatie role attach/detach logged (`permission.events_enabled` = true). Write-offs, collections and backups add explicit rows. Failures are caught and logged, never block. `AuditLog` had no `updated_at` column: `UPDATED_AT = null`. Read-only page `/audit-log` (permission `view audit log`: admin, auditor, super-admin), filters by record type, record #, user, action; sidebar link in the Reports group. | app/Services/AuditTrail.php; app/Observers/AuditObserver.php; AppServiceProvider; config/permission.php; AuditLogController; audit-log/index.blade.php; sidebar |
| S2 | `system.backup`: mysqldump (single-transaction, routines, triggers, no-tablespaces) to `storage/app/private/backups/backup-YYYYmmdd-His.sql.gz` (0640, git-ignored), streamed to the admin. Credentials go through a temporary 0600 option file, never the command line; errors shown to the user are generic. `system.debug` route removed (no method; only the unused admin/system/index.blade.php called it). | Admin/SystemController; routes/web.php |
| copies | Deleted the 9 `[COPY]` files (ProfileController copy, welcome.blade copy x4, loans/show.blade copy x2 + "new cycles", monthly-loans.blade copy); nothing referenced them; they remain in git history. | |

Verified on a clone (`loans_wp_e`): write-off set loan 461 to `written_off` with notes/action/audit rows and no repayment; a 5,000 collection created repayment #807 (mode recovery, ref) linked to the action, case debt fell by 5,000, wallet +5,000; a second collection on case 1 moved that borrower's wallet +1,200; case index/show/create/edit/data/stats/my/export, all 15 reports and their CSV export, and `/audit-log` return 200; editing a repayment, loan status, role attach/detach and KYC status wrote audit rows. Backup download was a valid gzip dump with no password in it. Migrations and SQL twins both ran twice cleanly. `php artisan test`: 23 pass, 4 pre-existing ProfileTest failures.

**Live steps, in order**
1. Back up: `mysqldump --single-transaction --routines --triggers <db> > backup.sql`
2. `mysql <db> < database/sql/2026_10_18_loan_written_off_status.sql`
3. `mysql <db> < database/sql/2026_10_18_recovery_action_repayment.sql` (or `php artisan migrate --force` for both)
4. Deploy code, then `php artisan permission:cache-reset && php artisan optimize:clear` (config change: `permission.events_enabled`)
5. Check `mysqldump` is on the server PATH (or `/usr/bin`) and `proc_open` is allowed in PHP for the backup button; otherwise use CyberPanel backups.

**Still open**
- Collections use the staff repayment rule unchanged: `RepaymentController@store` marks the loan `repaid` when total repayments reach `loans.amount` or no cycle is active. On the clone, loan 461 (amount 44,352, case debt 188,609) became `repaid` after a 5,000 collection. This is the existing rule for every staff repayment; it needs an owner decision (rule 7 / loan balance definition), not changed here.
- `markAsRecovered` and the edit form's "recovered" status still set the loan `repaid` directly with no money posted (unchanged; owner decision).
- Excel/PDF export still return CSV (Excel opens it); no PDF report yet.
- Unused `admin/system/index.blade.php` still calls `route('system.debug')`; it would fail if anyone renders it. Delete it or keep it unused.
- `User::recoveryTasks()`, `EventType::timelines()`, `RecoveryPriority::tasks()` still point to missing tables (left alone; User is being edited by the roles work).
- Audit retention not set; rows are never pruned.
