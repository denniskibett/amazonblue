# Aimzone Capital loan system: Stage 1 audit

*Audit date: 17 October 2026. Evidence comes from the code, the migrations and read-only queries on the local mirror `loans_main`. No code or data was changed. Wherever the live state could differ from the mirror, this document says so.*

**Start here in later sessions**

| File | What it holds |
|---|---|
| [TREE.md](TREE.md) | The project tree, the 17 broken routes, the scheduler (there is none) and package versions |
| [modules/A-customer.md](modules/A-customer.md) | Website, login and sign-up, KYC, customer account, borrowing and limits, credit scoring, agreements and signatures, error handling, customer journeys |
| [modules/B-loan-engine.md](modules/B-loan-engine.md) | Loan products, origination and approval, cycles, interest, penalty, rollover, payment plans, forbearance, NPL detection, console commands |
| [modules/C-money.md](modules/C-money.md) | Disbursements, repayments, Bavix wallets, partners, brokers, investments, AC house accounts |
| [modules/D-staff-roles-journeys.md](modules/D-staff-roles-journeys.md) | Debt recovery, reports and dashboard, settings, roles and permissions, audit log, journey inventory, design mapping |
| [design-screens.md](design-screens.md) | Screens in the design artefact. They were mapped by screen title; the screens were not opened one by one. |

Each finding in the module files carries one label: **Verified working**, **Implemented, not verified**, **Partial**, **Planned only**, **Broken** or **Unknown**.

---

## 1. The confirmed business rules: where the code stands

| # | Rule | Status | What happens today | Main files |
|---|---|---|---|---|
| 1 | AC owns company-funded loans | **Partial** | `loans.partner_id` holds the **broker**, not the funder. AC exists only as Partner #6 "House Account", and no principal is attributed to any funder. | Loan, Partner, PartnerLedger |
| 2 | Investment → partner → disbursement | **Partial** (over-allocation control **Broken**) | Only 10 of 629 disbursements are linked to an investment, all to house investment #1. Its funding row shows 20,000 drawn against 186,000 actually disbursed. Nothing blocks over-allocation. | Investment.php:378-446, DisbursementController |
| 3 | Fixed split of every repayment | **Planned only** | Repayments are not split. Partner and AC amounts come only from a one-off backfill (497 rows, no repayment_id). `loan_ledger` has 0 rows. | RepaymentController, PartnerLedger |
| 4 | Restate history | **Partial** (data exists) | 29 loans are brokered (`broker_status=1`); 27 of them have broker fees booked. 20 of the 22 "BROKER FEE(S)" repayments sit on **non-brokered** loans. Broker rates come from three sources that conflict. | loans, brokers, partners |
| 5 | Penalty runs daily until payment; late payment clears penalty first | **Broken** | Penalty days run to **today**, not to the payment date. Late payments reduce only the penalty, never the balance. Loan 529: after a full payment on day 1, the code still shows 14,780 owed eight days later; the rule says 0. | LoanCalculator.php:195-197, 268, 286-289 |
| 6 | Rollover carries the true outstanding, starting on the last penalty day | **Broken** (regression from commit cb16321) | It carries `cycle.new_balance`, ignoring repayments and penalty, and starts on the due date. The preview shows a different figure from the one booked. Loan 529: the code books 17,280; the rule says 16,258. | LoanCalculator.php:669, 717-735, 952 |
| 7 | `loans.amount` = current principal; `original_amount` = first principal | **Partial** | `loans.amount` becomes principal + interest. `original_amount` is NULL on 465 of 514 loans. Every dashboard and report sums `loans.amount`. | LoanCalculator.php:813, Loan.php:532,1060, Dashboard/ReportController |
| 8 | Repayment belongs to a cycle of the same loan; status comes from the cycle | **Partial** | The data is clean (0 orphans), but the code never checks the cycle belongs to the loan. "Repaid" is decided from all repayments ≥ `loans.amount`, and 434 repaid loans still have an active cycle. | RepaymentController.php:67, 89-105 |
| 9 | Wallet handles (@firstlast) | **Planned only** | Nothing exists yet. One name clash today (`ianokoth` ×2); 7 users have one-word names. | — |

---

## 2. Findings by priority

### P0: fix before anything else (security, money, legal)

| # | Finding | Label | Evidence |
|---|---|---|---|
| 1 | **Anyone can download signed loan agreements without logging in.** They sit on the public disk under sequential ids (`/storage/agreements/loan_agreement_{id}.pdf`). | Broken | LoanAgreementService.php:38-39 |
| 2 | **KYC IDs, selfies and signatures are on the public disk.** Signature names can be guessed, and two users with the same name overwrite each other's file. | Broken | HomeController.php:480-491, SignatureService.php:22-23 |
| 3 | **Permission leak.** Two role systems disagree: Spatie roles and the `users.role` column. Auditor, broker and recovery-officer can create, edit and delete users and disbursements. Brokers can set loans to disbursed or repaid. 5 users with no role count as staff. | Broken | routes/web.php:94, 215-224; DisbursementController.php:46,193,225; User.php:147 |
| 4 | **The penalty and rollover maths break rules 5 and 6.** Balances shown and amounts booked are wrong on any late or rolled loan. | Broken | see §1 |
| 5 | **The House #6 principal wallet double-counts KES 4,420,156.64 of interest.** A `--repair-partners` run deposited it before the journal was corrected, and `wallets:check` can't see the error. **Don't run `--repair-partners` on live.** | Broken | CustomerWallets::checkPartners |
| 6 | **A signed agreement can change after signing.** It is rebuilt from current data whenever it's viewed, with no stored hash, signing time, IP or version. | Partial | LoanController.php:1680,1699 |
| 7 | **The penalty clause in the agreement is 10% per day, compounding.** It needs legal review against Kenya's in-duplum rule and the configured penalty rate. | Decision / legal | LoanAgreementService |

### P1: broken features that staff and customers hit

| # | Finding | Label |
|---|---|---|
| 8 | NPL detection calls a method that doesn't exist, and there is no scheduler, so 0 loans are ever marked overdue or defaulted. | Broken |
| 9 | Code sets the loan status `active`, which isn't allowed for `loans.status`; with strict mode on, those saves throw. This includes deleting a repayment. | Broken |
| 10 | Rollovers and discounts count towards "repaid". Writing off a debt marks the loan as repaid. Recovery "amount collected" lowers the debt with no repayment entry. | Broken |
| 11 | Partner contributions, profit sharing and withdrawals write to the wrong table, so partner funding and withdrawals fail. | Broken |
| 12 | Disbursement paths that run from investments insert NULL into a `loan_id` column that doesn't allow it. | Broken |
| 13 | Staff can't reject a loan. Cycle 1 is created at application and never moved at disbursement. Editing the amount doesn't update the cycle. | Broken |
| 14 | Reports: export never runs (it's caught by the `reports/{reportType}` route), some figures are hard-coded or simulated, and cashflow has inflows and outflows reversed. Totals include KES 14.4M of disbursements and 18.9M of repayments that weren't cash. | Broken |
| 15 | 17 routes point to methods that don't exist. Case-page buttons do nothing. `cases/my` and `cases/stats` are caught by `cases/{case}`. | Broken |
| 16 | There are no branded error pages and the exception handler is empty. The maintenance toggle does nothing. Whether live shows stack traces is unknown; `.env.example` ships with debug on. | Broken / Unknown |
| 17 | Email verification is not enforced (`User` doesn't implement `MustVerifyEmail`). | Broken |
| 18 | The audit log has 0 rows; nothing writes to it. | Broken |

### P2: gaps and technical debt

- **Customers can't repay in the app.** The page tells them to pay by M-Pesa and send the message on WhatsApp, and shows no paybill number.
- **Nobody is notified** on KYC submission, approval or rejection, or on a new application.
- **Credit bureau and financial assessment tables are schema only.** KYC asks for consent to a credit bureau check that is never made.
- **Two scoring systems disagree.** The old Borrower 300-850 score helpers contradict `config/lending.php`.
- **The limit floor is always KES 5,000**, even for written-off or very-high-risk borrowers.
- **Recovery features exist only as schema:** agencies, legal proceedings, skip tracing and payment plans (16 empty tables). Six model relations point to missing classes.
- **Dead or duplicate code:** 9 `* copy*.php` files, `Loan::rollover`, `Loan::updateStatus`, the unused `app/Http/Kernel.php`, and 7 SQL views that nothing reads.
- **Commands that write data with no dry-run:** `loans:fix-cycles`, `repair-cycles` and `backfill-house-account`.
- **Local only:** the risk-scoring migration hasn't been applied to `loans_main`, so score saves fail and are logged.

---

## 3. Roles and journeys

- **The two major roles are admin (staff)** — 4 users, `layouts/app` (TailAdmin) — **and borrower (customer)** — 79 Spatie roles against 84 `users.role` values, `home/layouts/account` and `kyc`. Minor roles: teller (2) and partner (1). Seven seeded roles have no users, including super-admin, so `/system` returns 403 for everyone.
- **Journeys:** 29 in total. 4 exist, 15 exist with gaps and 10 are missing. Details are in module files A and D.
- **Design journeys with no backend at all:**
  - trade finance (LC, FX, supplier payments)
  - asset-backed lending and the valuation officer
  - compliance (AML, business review)
  - business registration and KYC
  - partner KYC and self-service investing
  - M-Pesa repayment
  - alerts and emails
  - accountant ledger and reconciliation
  - admin products, pricing and approval limits
  - system admin (health, security, audit, API)
  - teller end of day
  - mobile app

---

## 4. Benchmark: microfinance and bank practice

| Control | Category | Today |
|---|---|---|
| Double-entry ledger; every movement posted once, with reversals rather than edits | Mandatory financial integrity | Missing: `loan_ledger` is empty, repayments aren't split, edits overwrite records |
| Daily reconciliation (loans ↔ cycles ↔ repayments ↔ wallets ↔ bank/M-Pesa) | Mandatory | Partial: borrower wallets reconcile; nothing reconciles against M-Pesa or the bank |
| Maker-checker on approvals, disbursements and write-offs | Mandatory | Missing |
| Immutable audit trail | Mandatory | Missing: 0 rows |
| Correct arrears ageing and automated NPL classification | Mandatory | Broken: no scheduler, and the detection method is missing |
| Least-privilege, per-action permissions | Mandatory | Broken: permission leaks |
| Private storage for KYC documents and agreements | Mandatory (Kenya Data Protection Act 2019) | Broken: public disk |
| Tamper-evident signed agreements (hash, time, IP) | Mandatory | Missing |
| Loan products and pricing configured with approval | Microfinance operational | Partial: no screen to manage products |
| Collections workflow, restructuring and promise-to-pay | Microfinance operational | Partial: case pages are broken |
| Credit bureau checks and reporting (CRB) | Microfinance operational (CBK rules for digital lenders) | Planned only |
| Customer notifications by SMS or email | Microfinance operational | Missing |
| Interest and penalty caps (in-duplum) and disclosure of key facts | Mandatory, **needs legal confirmation** | Unknown: the clause says 10% per day, compounding |
| Data retention schedule; consent records | Mandatory (DPA), **needs legal confirmation** | Missing |
| Backups, restore tests, rollback plan | Mandatory | Partial: a backup route exists but its method is missing |
| Automated tests around money | Mandatory | Minimal: 26 tests, 4 failing, none on money maths |
| Core banking GL, regulatory returns, treasury | Bank-only | Out of scope |
| Payments API (M-Pesa STK push, B2C) | Optional, high value | Missing |

*The legal points above are flags for a lawyer, not legal advice.*

---

## 5. Decisions needed from the owner

**Money rules**
1. Is a penalty of 10% per day intended? Is there a cap, such as in-duplum?
2. Is `loans.amount` the new principal, or principal + interest? Should past reports be restated?
3. Which broker rate applies when restating history: `loans.broker_rate`, the brokers table, or `partners.broker_rate`?
4. How is the penalty split on brokered loans, and on other loans?
5. What are the `BROKER FEE(S)`, `TO BE PAID` and `SPLIT` entries? Is `TO BE PAID` cash?
6. Should old rollovers stored as chains of separate loans be merged into cycles, or left out of totals?
7. How should write-offs be accounted for? Must recovery collections be posted as repayments?
8. Does "partner" in rule 3 mean funding investors only? Do brokers stay as Partner records?
9. Should AC be one house account with separate wallets, or separate accounts? The seeder expects two; the data has one.
10. Should the processing fee on repayments stay?
11. Does a rollover use the product rate or the previous cycle's rate? Should payment plans and forbearance waive earlier penalties?
12. Does cycle 1 start on the application date or the disbursement date? Which NPL threshold applies?

**Access and process**

13. Should Spatie be the only role system? Which staff roles do we keep (the design's 11 roles, or the seeded ones)? What is the per-action permission matrix?
14. Who may approve, reject and disburse? Is a second sign-off (maker-checker) required, and above what amount?
15. Who is the live super-admin?
16. How are backups done?

**Customers**

17. Must email or phone be verified before KYC or borrowing?
18. Limit ladder: keep the KES 5,000 floor? Add steps between 5,000 and 50,000? What happens when income is zero?
19. Does the 4Cs score only advise staff, or does it block approval?
20. Are credit bureau checks required?
21. Which repayment channel do customers see? Is M-Pesa integration in scope?
22. KYC policy: should the KRA PIN be unique? Should a second person approve? Should payout numbers be checked? How often should borrowers re-verify?
23. How long should KYC documents, logs and IP data be kept?

**Scope**

24. Which design journeys are in scope for Stage 2? Trade finance, asset-backed lending and compliance have no backend at all.
25. Stage 2 design: confirm which role keeps TailAdmin (the workplan suggests staff) and which uses the design artefact (customers).
