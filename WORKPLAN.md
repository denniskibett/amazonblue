# Amazonblue — Work Plan & Context Tracker

Short context file. Read this first each session, instead of re-reading the code.
Full detail: `SYSTEM_DOCUMENTATION.md` (current state) · `REVIEW_AND_RECOMMENDATIONS.md` (findings) · `amazonblue_tree.txt` (files).
Last updated: 17 Oct 2026 · Code changed so far: **none**

---

## 1. Fast context (what to know without opening code)

- Laravel 12 + Blade + Alpine + Tailwind; Spatie roles; bavix wallets; TCPDF agreements. Needs PHP ≥ 8.4.1.
- Two UIs today:
  - **Staff** = TailAdmin template (`layouts/app.blade.php`, `resources/views/{loans,users,cases,…}`).
  - **Customers** (borrower, partner) = custom website design (`home/layouts/{account,kyc}.blade.php`, `home/account/*`, `home/kyc/*`). Auth pages (`auth/*`) use the website `home.partials.head`.
- Money core: `app/Services/LoanCalculator.php` (cycles, interest, penalty, rollover), `RepaymentController@store` (does NOT use `RepaymentService`), `DisbursementController@store` → `DisbursementService`, `PartnerLedger::post()` (idempotent journal + wallet), `CustomerWallets` (borrower cash wallet, auto-synced by observers in `AppServiceProvider`).
- Wallets now: borrower `borrower_loan`; partner `principal`, `interest`, `broker_fee`, `investment_tracker`.
- Repayment type today is guessed from reference text (`ROLL`, `DISCOUNT`, `BAD DEBT`…).
- `loans.amount` is already overwritten on rollover (`LoanCalculator.php:813`); `original_amount` keeps the first principal.
- Repayments already have `loan_cycle_id` (nullable); balance per cycle uses it; loan-level close logic ignores it.
- Scheduler not wired (Laravel 12 ignores `app/Console/Kernel.php`); NPL `updateNplStatus()` missing.
- No `resources/views/errors/` folder → default Laravel error pages.

## 2. Requests captured (17 Oct 2026) — status: awaiting confirmation

| # | Request (as understood) | Touches | Status |
|---|---|---|---|
| R1 | Use the shared artifact's design to streamline UI; keep TailAdmin for one side (see Q2) | layouts, views | ⏸ artifact not readable |
| R2 | Every disbursement must be tied to an investment, and every investment to a partner | `disbursements`, `investments`, `investment_fundings`, `DisbursementController` | ⏸ |
| R3 | Repayments always split into accounts by default: principal, interest, penalty, broker, partner (+ house remainder) | `repayments` migration, `RepaymentService`, `RepaymentController` | ⏸ |
| R4 | Track every split with bavix wallets (loan/house/broker/partner wallets) | `CustomerWallets`, `PartnerLedger`, new wallet slugs | ⏸ |
| R5 | Restate history from the first loan: principal back to the investment / main company; broker fees on brokered loans | new artisan command, dry-run first | ⏸ |
| R6 | Rollover: previous balance becomes the new principal; update due loans and the current views; payments tied to `loan_cycle_id` | `LoanCalculator::executeRollover`, loan show/index, account views | ⏸ |
| R7 | `loans.amount` = current principal after rollover (keep `original_amount` = first principal) | `LoanCalculator`, reports reading `amount` | ⏸ |
| R8 | Repayments must belong to a cycle (required, and the cycle must belong to the loan); closing is per cycle | `RepaymentController`, form, validation | ⏸ |
| R9 | Fallback pages for 404, 403, 500 and other errors (419, 429, 503) | `resources/views/errors/*` | ⏸ |
| R10 | One consistent UI across both major roles | shared components/tokens | ⏸ |
| R11 | Rebuild the UIs from the artifact's journeys and mock each role's journey end to end | views + seed/demo data or clickable mock | ⏸ |
| R12 | Keep this tree + tracker updated before each change | `WORKPLAN.md`, `amazonblue_tree.txt` | ✅ started |

## 3. Open questions (block the build)

- **Q1** Artifact access: share it with this account, or attach it as HTML/PDF/screenshots.
- **Q2** "For non-staff maintain TailAdmin": did you mean **staff keep TailAdmin** and customers get the artifact design? (Today it is the other way round from the sentence.)
- **Q3** "AC should handle…": what is "AC"? (Rollover? Account? Accounts module?)
- **Q4** R5 scope: recompute and re-post all historical loans (514), or only open ones? Run on a DB copy first, then live?
- **Q5** Split rules: broker share from `brokers` rates (client vs broker), partner share from `partners.profit_share_rate`, remainder to house interest? Penalty split the same way? Allocation order penalty → interest → principal?
- **Q6** "Both major roles": staff vs customers, or borrower vs partner?
- **Q7** Penalty cap and P0 data items (from review §2/§7): in scope now, or later?

## 4. UI journey inventory (from code)

### Exists
| Role | Journey | Screens |
|---|---|---|
| Visitor | Website, enquiry | `home/*`, contact |
| Borrower | Register → KYC (personal, documents, income, contacts, review) → submitted | `auth/register`, `home/kyc/*` |
| Borrower | Account home → Borrow (limit, quote, blockers) → apply | `home/account/borrower`, `borrow` |
| Borrower | My loans → loan detail (timeline, balance, cycles, repayments) → agreement | `loans`, `loan` |
| Borrower/Partner | Profile | `home/account/profile` |
| Partner | Statement | `home/account/statement`, `partner` |
| Staff | Dashboard | `dashboard` |
| Staff | Users CRUD, borrower detail | `users/*` |
| Staff | KYC review → approve/reject | `kyc-reviews/*` |
| Staff | Loans list/create/edit/show, rollover, payment plan, forbearance, recovery start, agreement | `loans/*` (modals in show) |
| Staff | Disbursements CRUD | `disbursements/*` |
| Staff | Repayments CRUD | `repayments/*` |
| Staff | Recovery cases list/create/show, actions, notes, recover, write-off | `cases/*` |
| Staff | Partners list/show, contribution, withdraw, profit | `partners/*` |
| Staff | Investments list/show | `investments/*` |
| Staff | Reports | `reports/*` |
| Staff | System settings | `admin/system/*` |
| All | Login, forgot/reset password, verify email | `auth/*` |

### Missing (candidates)
| Role | Missing journey |
|---|---|
| All | Error pages (404/403/419/429/500/503); session-expired; maintenance page in brand |
| Borrower | **Repay** (how to pay / M-Pesa paybill or STK, submit code, receipt) |
| Borrower | Rollover request with cost preview; accept payment plan |
| Borrower | Overdue / in-recovery view with what is owed and options |
| Borrower | Rejected application view + reason; KYC rejected → fix and resubmit (route exists, check UI) |
| Borrower | Receipts & statement download; notifications inbox |
| Borrower | Accept terms per loan (fresh acceptance, not re-used KYC signature) |
| Borrower | Support / complaint |
| Partner | Onboarding/KYC for partners |
| Partner | Portfolio: my investments → loans they funded → earnings (interest, penalty share), principal returned |
| Partner | Contribute funds / request withdrawal (with approval) |
| Broker | **No broker portal**: my clients, refer a borrower, loans I introduced, broker fee statement |
| Staff | Approval queue (approve/reject pending loans) — today it is a status edit |
| Staff | Disbursement queue (approved → paid) with investment selection |
| Staff | Repayment entry with cycle picker + split preview; reversal flow |
| Staff | Collections worklist (due today, 1–7, 8–30, 30+ days) |
| Staff | Write-off / discount approval (maker-checker) |
| Staff | Loan products (loan types) management |
| Staff | Roles & permissions management |
| Staff | Audit log viewer |
| Staff | Wallet/reconciliation screen (`wallets:check` results) |
| Staff | Partner payout approval; investment ↔ partner ↔ disbursement view |

## 5. Change log
| Date | Change | Files |
|---|---|---|
| 2026-10-17 | Review docs + tree + this tracker created | `docs/*` |
