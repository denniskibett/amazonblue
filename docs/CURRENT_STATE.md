# Current state of the system

**As of 3 November 2026, branch `stage-2-ui` (`7b6dd72`).** This is the one living description of what the code does.
Update it in the same commit as any change to a rule, switch, role or module. Earlier audits and plans are in
[archive/](archive/) and describe the system as it was then.

"State" in §4 describes the code on this branch. Whether the 1–3 November work is deployed is not recorded; check the
server's commit. Live values come from the last recorded server reads in the runbooks. Confirm them on the server with
`bash scripts/server-tidy.sh` (read-only) before relying on them.

---

## 1. What it is

One Laravel 12 app, three front doors:

| Door | Who | Layout | Where |
|---|---|---|---|
| Website | Visitors | Website CSS (`layouts/frontend`, `home/*`) | `/`, `/contact`, testimonials |
| Account | Borrowers, partners | Website CSS + `x-az.*` components (`home/account/*`, `home/kyc/*`, `home/business/*`) | `/account/*`, `/kyc/*` |
| Back office | Staff | TailAdmin + Tailwind + `x-staff.*` components | `/dashboard`, `/staff/*`, `/loans`, … |

Also: the M-Pesa callbacks under `/mpesa/{secret}/…`, director self-verification links (`/verify/director/{token}`),
and a small API (`/api/v1/ping`, Bearer API key).

## 2. Roles

Roles and their permissions are defined in `database/seeders/RoleSeeder.php` (`php artisan roles:sync` applies them).
Staff roles are in `User::STAFF_ROLES`; anyone else who signs in goes to `/account`.

| Role | Can do |
|---|---|
| borrower | KYC, borrow, repay (STK), top-up/extension/plan requests, statements, businesses, testimonial |
| partner | Partner portal: lines, funded loans, earnings (paid out / to be paid out / pending), add funds, withdraw, subscriptions. Two kinds, one role: **long-term** partners fund lines (fixed rate or loan share); **short-term** partners (the old "broker") introduce borrowers and can fund them loan by loan. A short-term partner has introducer terms in `brokers` (`User::isShortTermPartner()`); their loans carry `loans.partner_id` + `broker_status`, listed as "brokered" in the portal |
| teller | Create and disburse loans, take repayments, cash-up |
| loan-officer | Origination and loan changes |
| recovery-officer | Collections, recovery cases, notices |
| accountant | Ledger, wallets, partner money, reports |
| auditor | Read-only |
| manager | Nearly everything an admin has, including `edit users` and API tokens |
| admin | Adds Integrations (`manage integrations`) and API keys |
| super-admin | Everything (`*`). Only role with `/system` (settings, backups), `manage roles` and Preview ("view as"): a stand-in for a role, or any real account but another super-admin, staff included; every preview request is rolled back and nothing is sent |
| collector | Outside debt collection agency staff. Not staff: they sign in to `/collect` and see only the loans placed with their agency while the placement is open (forbearance included). Contact details unlock `LENDING_AGENCY_NOTICE_DAYS` (7) days after the borrower was told. No ID photos, selfies or meters |

Maker-checker (second sign-off) applies only when `LENDING_APPROVALS_ENABLED=true`. Admin and super-admin may disburse a
loan they approved themselves (`LENDING_SELF_DISBURSE_ROLES`); each such disbursement is audited as `self_disbursed`.

## 3. How a loan works (engine v2)

1. **Limit.** First loan up to `LENDING_FIRST_TIME_LIMIT` (5,000), then up to `LENDING_GOOD_STANDING_LIMIT` (50,000),
   capped by the 4Cs risk category (`CreditAssessment`) and by income × `LENDING_INCOME_MULTIPLE`.
2. **Apply → approve.** Borrowers apply on the product `LENDING_BORROWER_LOAN_TYPE_ID`; staff create loans on any
   product. Status goes pending → approved (by staff, or through `Approvals` when sign-offs are on).
3. **Disburse.** Staff record a disbursement (`DisbursementService`), or send it by M-Pesa B2C (request → second person
   approves → Safaricom's result records it). The first cash disbursement re-dates cycle 1.
4. **Funding.** The cash comes from the house, a partner line, or several of them at once (**part funding**,
   `loan_fundings`, `Services/Loans/LoanFundings`). Past loans had their single funder frozen by
   `loans:backfill-fundings`.
5. **Cycles** (`Services/Loans/CycleBalance`, in whole cents). Each cycle charges flat interest (rate × principal, or
   an exact `loan_cycles.interest_amount`). After the due date plus any **grace days** (`loans.grace`), a daily
   penalty runs on the unpaid principal + interest. Total penalty per cycle is capped at the principal unpaid on the
   due date (in duplum, `LENDING_PENALTY_CAP`).
6. **Repayment.** Each payment pays penalty, then interest, then principal; the rest is an overpayment. Repayments belong
   to a cycle (`repayment.loan_cycle_id`). Reversals keep the original row and net it on the same day.
7. **Short payment** (`ShortfallFit`, since 1 Nov). If the cash is short by up to `LENDING_SHORTFALL_MAX` (2,000), the
   cycle closes at the cash received: its interest is cut to fit and late cash gets grace days. Nothing extra is written
   to `repayments` (this replaced the "SHORTFALL DISCOUNT" rows of the first version).
8. **Changes to a running loan:**
   - **Rollover / extension** (`Rollover`): closes the cycle and opens the next one with everything owed as principal.
     The new cycle may use **another product** (period, rate, penalty), and staff choose the booking date.
   - **Payment plan:** a rollover to a set amount, optionally written down.
   - **Top-up** (`TopUp`): the new cycle's principal = what is owed + new cash, with a disbursement.
   - **Product change / Edit current cycle** (`ProductChange`): re-prices only the open cycle, with a before/after preview.
   - **Forbearance:** the latest cycle's penalty is waived open-ended; the due date is the pay-by day.
   - **Write-off** (`WriteOff`): a `bad_debt` row for what is owed; the loan becomes `written_off`.
9. **Status** (`LoanStatus`, nightly): repaid, forbearance, overdue, disbursed or defaulted, from the latest cycle.
   Non-performing loans open recovery cases.
10. **Splitting the money** (`RepaymentSplit`, `LENDING_SPLIT_ENABLED`). The interest and penalty of each cash
    repayment go to broker share, funder share and house remainder, posted by `PartnerLedger` (journal + bavix
    wallet). Principal goes back to the funding line. On a part-funded loan the split is pro rata to the funders.
    Finished lines close and queue payouts (`partners:lines`).
    **Partner shares (since 3 Nov 2026):** a partner line carries its own share of the interest
    (`investments.partner_share_rate`, default the partner's profit share, else 60%) and of the penalty
    (`partner_penalty_rate`, default 50%). Our money with a short-term partner's client: the partner gets their
    introducer share (40%) and the house the rest. Lines with no rate keep the partner's profit share and no penalty
    share, so older lines split as before.
    **Slices** (`PartnerSlices`, partner page › Money): which part of a loan a line paid for, tied to its
    disbursement. A slice earns nothing before its disbursement date. **House settles** pays a partner out of a
    slice before the borrower repays (their interest share on the day, `SETTLE-LF{id}`); later repayments of that
    slice are the house's. Both have Preview (journal rows, nothing saved). Changes re-split by difference: new
    journal rows, nothing edited.
    **Payouts already made:** Requests › prepare › "Already paid" records the day and the bank or M-Pesa reference;
    the payout posts on that day once signed off (`Requests::recordPaidPayout`).
11. **Ledger** (`LENDING_LEDGER_ENABLED`). Double-entry postings to `gl-*` wallets, posted after commit, accrued nightly
    (`ledger:accrue`). The chart of accounts is in `config/lending.php › ledger.accounts`.

Money emails (`Services/MoneyEmails`): money sent, each payment (with what is still due) and "paid in full", by email
only, once per event. Not sent for book entries, console entries or money dated more than a week back.

## 4. Modules

| Module | State | Main code |
|---|---|---|
| Website, enquiries, testimonials | On. Testimonials are approved by staff; the homepage section hides when there are none | `HomeController`, `TestimonialController` |
| Registration, login, sessions | On. 12-hour sessions; a 419 on sign-in returns to a fresh form | `Auth/*`, `bootstrap/app.php` |
| Borrower KYC (ID, selfies, income, contacts) | On, manual review by staff | `Kyc*`, `home/kyc/*` |
| Business KYB, director self-verification | KYB on; business loans behind `LENDING_BUSINESS_LOANS` | `routes/features/business-lending.php` |
| Borrowing, limits, 4Cs scoring | On | `CreditAssessment`, `BorrowingEligibility` |
| Loan engine v2 (cycles, grace, penalty, rollover, top-up, short payment, product change) | On live since 21 Oct (`LENDING_ENGINE=v2`) | `Services/Loans/*` |
| Disbursements (staff form) | On | `DisbursementController`, `DisbursementService` |
| M-Pesa C2B / STK repayments | Built, off (`MPESA_ENABLED`, `MPESA_STK_ENABLED`) | `Services/Mpesa/*`, `MpesaCallbackController` |
| M-Pesa B2C payouts | Built, off (`MPESA_B2C_ENABLED`; needs sign-offs on) | `routes/features/mpesa-b2c.php`, `B2cPayouts` |
| Part funding | On (since 2 Nov) | `LoanFundings`, `RepaymentSplit` |
| Partner portal and lines | On | `PartnerAccount`, `Partners/*` |
| Partner subscriptions (online KYC + subscription) | Built, off (`LENDING_PARTNER_SUBSCRIPTIONS`) | `routes/features/partner-subscriptions.php` |
| Ledger, wallets, reconciliation | On; `wallets:check` reconciles | `Ledger`, `PartnerLedger`, `CustomerWallets` |
| Requests (top-up, extension, plan, partner) | On live since 23 Oct | `Services/Requests` |
| Second sign-offs (maker-checker), approval limits | Built, off | `Approvals`, `ApprovalActions` |
| Notifications and SMS (KenyaSMS / Africa's Talking) | Built, off; SMS goes to the log | `Notifications/*`, `notifications:reminders` |
| Late-payment emails | Built, off (`NOTIFICATIONS_OVERDUE_EMAILS`) | `notifications:reminders` |
| AML / sanctions screening | Built, off (`LENDING_AML_SCREENING`) | `routes/features/aml.php` |
| Debt recovery, NPL | On | `CaseController`, `NplDetectionService` |
| Reports, dashboard, operations review | On | `Reports*`, `staff/operations-review` |
| Collection agencies (outside collectors) | On (since 3 Nov). Staff with `manage collection agencies` (admin, manager, recovery officer) add agencies and collector logins on `/staff/agencies` and place overdue, defaulted, recovery and forbearance loans until an end date. The old empty `recovery_agencies` / `agency_*` tables are not used | `Staff/CollectionAgenciesController`, `Collect/PortalController`, `Services/Collections/AgencyPlacements` |
| Cash and growth | On (since 3 Nov). `/staff/cash-forecast` (`view reports`): cash per the ledger, a 12-week cash forecast from the loan book (weighted by lateness, `lending.forecast`) with and without lending at the recent pace, and lending, collections and new-loan interest by month with a 3-month projection. Partner payouts and running costs are not in it yet | `Services/CashForecast` |
| Agreements and signature pad | On | `LoanAgreementService`, `SignatureService` |
| Integrations page | On (since 3 Nov). M-Pesa, SMS, SMTP/`MAIL_URL`, Google Analytics, S3, Slack. Values are encrypted in `integrations` and **override `.env` at boot** | `Support/Integrations`, `Staff/IntegrationsController` |
| API keys and `/api/v1` | On; only `GET /api/v1/ping` so far | `ApiKey`, `AuthenticateApiKey` |
| Site analytics (first-party) | On (`ANALYTICS_ENABLED`), kept 180 days | `SiteAnalytics`, `TrackPageView` |
| Header search | On: menu pages, loans by number, people by name/phone/ID (back office only) | `Support/NavMenu`, `Staff/SearchController` |
| Preview ("view as") | On, super-admin only; mail, SMS, STK and B2C payouts are blocked while previewing | `Middleware/PreviewAs` |

## 5. Switches

Each is a `.env` value (or, for the integration keys, the Integrations page). Turning one on or off needs no deploy:
change it, then `php artisan config:cache`. Switch order and checks: [stage4/SWITCH-ON-PLAN.md](stage4/SWITCH-ON-PLAN.md).

| Switch | Default | Live (last read) | What it does |
|---|---|---|---|
| `LENDING_ENGINE` | `v1` | `v2` (21 Oct) | Cycle engine. v1 is legacy; keep v2 |
| `LENDING_LEDGER_ENABLED` | false | on (23 Oct read) | Double-entry ledger postings |
| `LENDING_SPLIT_ENABLED` | false | on (21 Oct) | Broker / funder / house split of each repayment |
| `LENDING_REVERSALS_ENABLED` | false | on (23 Oct) | Reversals keep the original row |
| `LENDING_REQUESTS_ENABLED` | false | on (23 Oct) | Stored borrower and partner requests |
| `LENDING_APPROVALS_ENABLED` | false | off (23 Oct) | Second sign-offs |
| `LENDING_SELF_DISBURSE_ROLES` | `super-admin,admin` | not recorded | Roles exempt from "approver can't disburse"; empty = nobody exempt |
| `NOTIFICATIONS_ENABLED` | false | off (23 Oct) | Notifications and SMS |
| `NOTIFICATIONS_MONEY_EMAILS` | **true** | on by default (3 Nov) | Money sent / payment / paid-in-full emails |
| `NOTIFICATIONS_OVERDUE_EMAILS` | false | not recorded | Late-payment emails on `NOTIFICATIONS_OVERDUE_DAYS` |
| `SMS_DRIVER` | `log` | `log` (23 Oct) | `kenyasms`, `africastalking` or `log` |
| `MPESA_ENABLED`, `MPESA_STK_ENABLED` | false | off, sandbox (23 Oct) | C2B and STK repayments |
| `MPESA_B2C_ENABLED` | false | not recorded | M-Pesa payouts |
| `LENDING_PARTNER_SUBSCRIPTIONS` | false | not recorded | Partner online subscription |
| `LENDING_BUSINESS_LOANS` | false | not recorded | Loans to businesses |
| `LENDING_AML_SCREENING` | false | not recorded | Sanctions screening before approvals |
| `LENDING_BEHAVIOUR_LIMITS` | false | not recorded | Limits from borrower behaviour profiles |
| `LENDING_SHORTFALL_MAX` | 2000 | not recorded | Largest short payment accepted as paid |
| `ANALYTICS_ENABLED` | true | not recorded | First-party page views |
| `MPESA_ALLOWED_IPS` | empty (any address) | not recorded | Safaricom callback addresses; set before M-Pesa goes on |
| `WALLET_LOCK_DRIVER` | `array` | not recorded | Wallet balance locks; `database` or `redis` on live (see §9) |

"Not recorded" means no runbook has written down the live value since that switch shipped. Read it with
`scripts/server-tidy.sh` and update this table.

## 6. Scheduled jobs

Defined in `routes/console.php`, Africa/Nairobi time. The server runs `schedule:run` every minute from cron.

| Time | Command | What it does |
|---|---|---|
| every minute | `queue:work` | Drains the queue |
| 01:00 | `loans:update-statuses` | Loan statuses; defaults past the threshold |
| 01:15 | `ledger:accrue` | Books cycle interest and penalty to the ledger |
| 01:30 | `loans:detect-npl` | Non-performing loans; opens recovery cases |
| 02:00 | `loans:check-integrity` | Read-only integrity report (CSV in `storage/app/private/reports/`) |
| 02:15 | `analytics:profiles` | Borrower behaviour profiles |
| 02:30 | `partners:lines` | Closes finished partner lines, queues payouts |
| 02:45 | `partners:check --notify` | Partner money reconciles: each split shares out what was collected, lines add up, partner earnings payable equals the partners' earnings wallets, trial balance 0; alerts admins if not |
| 1st of month, 02:30 | `partners:accrue-fixed` | Monthly interest for fixed-rate partners |
| 03:30 | `analytics:prune-views` | Drops page views older than `ANALYTICS_KEEP_DAYS` |
| 1st of month, 03:00 | `audit:prune` | Audit log retention ([stage4/RETENTION.md](stage4/RETENTION.md)) |
| 08:00 | `approvals:digest` | Emails sign-offs waiting more than a day |
| 09:00 | `notifications:reminders` | Due and overdue reminders (`--dry-run` lists who would get one) |

## 7. Other commands

All in `app/Console/Commands`. Repairs and backfills dry-run by default and apply with `--apply` (most also want
`--confirm=<sha>` from the dry run).

| Group | Commands |
|---|---|
| Checks (read-only) | `loans:check-integrity`, `wallets:check` |
| Set-up | `roles:sync`, `users:super-admin`, `wallets:bootstrap`, `partners:link`, `risk:assess`, `mpesa:register-urls`, `integrations:import-env`, `demo:seed` (never on production) |
| One-off data repairs (done on live; keep for history) | `loans:restate`, `loans:merge-chains`, `loans:rollover-cleanup`, `loans:credit-cleanup`, `loans:grace-fix`, `loans:owner-decisions`, `loans:write-off-repaid`, `loans:backfill-fundings`, `allocations:restate`, `ledger:backfill`, `money:backfill-columns`, `payments:refs`, `borrowers:fill-gaps`, `files:make-private`, `handles:backfill`, `loans:backfill-house-account`, `loans:balance`, `loans:balance-house-accounts`, `partners:backfill-broker-fees`, `partners:backfill-house-interest`, `partners:record-october` (Buosti's and Obiri's October money; dry run without `--apply`), `partners:own-loans`, `investments:fix-house-funding`, `wallets:sync`, `wallets:fix-house`, `wallets:reconcile-from-journal` |
| Legacy (v1 only) | `loans:fix-cycles` refuses to run under engine v2; `loans:repair-cycles` was removed on 21 Oct |

Check each command's exact name with `php artisan list` before running it.

## 8. Database changes

Every schema change has a migration **and** a `database/sql/*.sql` twin with the same date. The twin is idempotent
(safe to run twice), works on MariaDB 10.3, and inserts its own row into `migrations`. On live: back up, run the twin,
then deploy ([DEPLOY.md](DEPLOY.md)). Permission-only and data-only fixes have a SQL file and no migration; permission
fixes are also mirrored in `RoleSeeder`.

Latest twins: `2026_11_01_cycle_interest_amount`, `2026_11_01_site_analytics`, `2026_11_01_testimonials`,
`2026_11_02_part_funding`, `2026_11_03_integrations_api_keys`, `2026_11_03_integrations_permission`,
`2026_11_03_users_utility_meters`, `2026_11_03_retire_broker_role`, `2026_11_03_collection_agencies`.

## 9. Known issues

From the code audit of 3 November 2026. The code fixes for the rest of that audit are in the change log (§11).
Remove each line when it is done.

**Needs action on live (not code)**
- A live database dump (`loans_main.sql`: users, password hashes, borrowers, loans) is tracked in git and the GitHub repo
  was public. Untrack it, purge it from history, and invalidate sessions and reset tokens. (The owner keeps the file
  locally to compare live and local.)
- Passwords were written to `storage/logs` by the old user-edit logging (removed 3 Nov). Clear the live logs.
- Old agreements and ID images sit in `storage/app/public` under guessable names. Run
  `php artisan files:make-private --dry-run`, then `--apply`, on live. Only after that remove the `public` fallback in
  `Support/PrivateFiles::absolute`.
- Wallet locks: `WALLET_LOCK_DRIVER` is still `array` (one process only), so two requests can race on a partner's
  balance. Set it to `database` (or `redis`) on live after a test on a copy of live.
- M-Pesa callbacks: set `MPESA_ALLOWED_IPS` to Safaricom's callback addresses before switching M-Pesa on.
- Eight M-Pesa receipts are on two repayments each in the copy of live (`repayments.mpesa_ref` TD14WWGMI4, TDA87V3XFY,
  TE89Y1GGWP, TJV4A8Z943, UB9PX665CY, UD76BBT9AX, UE4BM391CB, UELD84YB8S). Owner to check whether each is a double
  count or one payment split across cycles. A unique index on the receipt can follow once they are resolved; until then
  a per-receipt lock stops new doubles.

**Still open in code**
- No 2FA for staff.
- Penalty carried into a rollover's principal restarts the in-duplum cap: confirm with legal.
- `Investment::recordFundingDisbursement` checks a line's free capacity before taking its lock, so two draws at the same
  moment can exceed a partner line.
- `partners:accrue-fixed` can credit months after a fixed-rate partner left (it reads `updated_at`).

## 10. Open items (owner and server)

From the runbooks; tick them off here.

- [ ] SPF, DKIM and the `mail.loans` A record at Olitt (until then, Gmail quarantines mail, so keep notifications off).
- [ ] Rotate `DB_PASSWORD` (it was pasted in a chat) and the M-Pesa keys before production.
- [ ] sysadmin: strong password; roles = `["super-admin"]`.
- [ ] `.env`: drop the duplicate `LOG_STACK`, add `LOG_DAILY_DAYS=30` (`scripts/server-tidy.sh --apply`).
- [ ] Ubuntu 20.04 is past standard support; the server has 2 GB RAM.
- [ ] Record the live value of every "not recorded" switch in §5.

## 11. Change log

Newest first. One line per change that alters a rule, switch, role or module.

| Date | Change |
|---|---|
| 2026-11-03 | Collection agencies: `collector` role and `/collect` portal, `/staff/agencies`, permission `manage collection agencies`; tables `collection_agencies`, `collection_agency_users`, `collection_placements`, `collection_contacts` |
| 2026-11-03 | Cash and growth page (`/staff/cash-forecast`), `lending.forecast` assumptions |
| 2026-11-03 | Preview ("view as") opens any real account but a super-admin, staff included |
| 2026-11-03 | Broker role retired: brokers are short-term partners (partner role + `brokers` terms) on the partner portal, out of the staff app. The migration moves anyone with the role to partner (adding a partner row) and deletes the role; `brokers`, `borrowers.broker_id`, `loans.broker_*` and the `broker_fee*` ledger types are unchanged. User forms set introducer terms on a partner; labels say "introducer fee". Old `hasRole('broker')` branches in legacy loan/repayment/case screens are now dead code, still to be removed |
| 2026-11-03 | `users.utility_meters` (JSON): KPLC meter or account numbers a borrower gives as proof of address, with consent; not shared with collection agencies |
| 2026-11-03 | Audit fixes: no request bodies or passwords in logs; only a super-admin changes a super-admin (and only an admin an admin), no self role change or self delete; M-Pesa B2C sent only after the approval commits, at most once, result recorded under a lock, off while previewing; staff disbursement locks the loan, refuses pending/closed loans and a repeated reference; brokers see only their clients' loans (loan, PDF, agreement, cycles, previews, disbursement, repayment pages); partner edit writes only validated fields; M-Pesa callbacks answer non-zero when a payment can't be saved and alert admins; per-receipt lock between callback and teller; failed ledger/split postings and `ledger:accrue` cycles alert admins (`StaffAlarm`), accrual skips a bad cycle; per-address login limit and forgot-password throttle; root `deploy.sh` removed |
| 2026-11-03 | `manage integrations` permission for admin and super-admin; admin gets `manage API tokens` |
| 2026-11-03 | Integrations page, API keys + `/api/v1/ping`, money emails, admin self-disbursement, header search |
| 2026-11-02 | Part funding across house and partners (`loan_fundings`), backfill; partner portal shows paid out / to be paid out / pending; staff link a partner login |
| 2026-11-01 | Short payment closes the cycle at the cash received (`ShortfallFit`, `loan_cycles.interest_amount`) |
| 2026-11-01 | Rollover into another product; Edit current cycle; product change with preview; grace days per loan |
| 2026-11-01 | 12-hour sessions, 419 fix; testimonials; site analytics |
| 2026-10-25 | Late-payment emails with penalty breakdown; rollover booking-date choices; signature pad |
| 2026-10-24 | AML screening, partner subscriptions, business loans, M-Pesa B2C, KenyaSMS: all deployed off |
| 2026-10-23 | Reversals and requests switched on |
| 2026-10-21 | Engine v2 and split switched on; grace days; rollover chains; credit-discount clean-up; owner decisions |
| 2026-10-20 | Stage 4 Phase 0 and the ledger (Phase 1) |

Details of each October change are in the matching runbook in [stage4/](stage4/).
