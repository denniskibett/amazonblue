# B. Loan engine audit: products, origination, cycles, NPL, console commands

Stage 1 of WORKPLAN.md, audit only. Nothing in code, config or the database was changed. Data figures come from read-only SELECTs on the local mirror `loans_main` (date of the audit: 2026-10-17). Worked examples were computed by hand from the formulas in the code.

Labels: **Verified working** · **Implemented, not verified** · **Partial** · **Planned only** · **Broken** · **Unknown (not enough evidence)**

---

## 0. Verdicts on the confirmed business rules

| Rule | Verdict | One line |
|---|---|---|
| 5 Penalty | **Broken** | Penalty days run to *today*, not to the payment date. Late repayments only reduce the penalty and never the balance, so any excess is lost. |
| 6 Rollover | **Broken** (regression) | `executeRollover` carries `cycle.new_balance` (principal + interest). It ignores the cycle's repayments and penalty, and starts on the due date, not the last penalty day. The preview uses a different formula. |
| 7 amount / original_amount | **Partial** | `loans.amount` is set to the new cycle's principal **plus interest**, not the new principal. `original_amount` is NULL on 465/514 loans. Every dashboard and report reads `loans.amount`. |
| 8 Repayment ↔ cycle | **Partial** | The data is clean: 0 null `loan_cycle_id` and 0 cross-loan cycles. The code doesn't check that the cycle belongs to the loan, and the cycle and loan status don't come from the cycle balance. |

### Rule 5: penalty
**Required:** penalty accrues daily on the unpaid balance (not on penalty) only until the borrower pays. A late repayment clears penalty first, then balance.

**Current** (`app/Services/LoanCalculator.php:189-366`, `calculateCycleBalance`):
- `outstandingAtDue = principal + interest + fee − repayments dated ≤ due_date` (:252-258)
- `daysOverdue = due_date → today` (:268). The `$calculationDate` parameter is ignored (:195-197). Days never stop at the payment date.
- `penalty = outstandingAtDue × penalty_rate% × (daysOverdue − grace)` (:273). This is simple interest on the unpaid balance, which is correct.
- `outstandingAfterDue = max(0, penalty − repaymentsAfterDue)` (:286) and `final = outstandingAtDue + outstandingAfterDue` (:289). A late repayment can only reduce the penalty. Anything above the penalty disappears and the balance never goes down.
- The penalty is waived entirely when `loan.status = forbearance` or the cycle notes contain "PAYMENT PLAN" (:200-203). This applies to **all** cycles of the loan, including history. The check `$cycle->interest_rate === 0.0` is never true because of the `decimal:2` cast, which returns a string.
- A second, different penalty formula exists in `Loan::calculatePenalties` (`app/Models/Loan.php:923-947`). It uses `loans.amount − all repayments` and is capped at 100%. `NplDetectionService` (:131) and `loans/edit.blade.php:116` use it.

**Worked example: loan 529, cycle 606** (Emergency 10 Days: 20% interest, 10%/day penalty, grace 0). Previous balance 14,400. The cycle runs 2026-10-06 → 2026-10-16. 2,500 was repaid on 10-07.
- Code on 10-17: 14,400 + 2,880 = 17,280 − 2,500 = 14,780 at due, + 1 day × 10% × 14,780 = 1,478, giving **16,258**. That matches the rule on day 1.
- Scenario: the borrower pays 16,258 on 10-17 and staff view the loan on 10-25.
  - Rule: 14,780 + 1,478 − 16,258 = **0**.
  - Code: 9 penalty days = 13,302, then max(0, 13,302 − 16,258) = 0, so final = 14,780 + 0 = **14,780 still owed**. The cycle never closes, and the outstanding the code computes keeps the full pre-penalty balance.

Affected: `LoanCalculator.php:195-197, 252-289`; `Loan.php:923-947`; `NplDetectionService.php:131`. Tables: `loan_cycles`, `repayments`, `loan_types.penalty_rate`. Migration risk: **low** for the schema (no columns needed). It is **high** for figures, because every displayed outstanding, rollover preview, partner share (:291-298) and P&L (:325-329) changes once the fix lands.

### Rule 6: rollover
**Required:**
- New principal = the cycle's new outstanding (including unpaid penalty days, after repayments).
- The new cycle starts on the last penalty day (or the due date if there was no penalty) and is due one product period later.
- The old cycle keeps its history, and the same balance is never carried twice.

**Current** (`LoanCalculator.php:634-865`, `executeRollover`):
- `newPrincipal = $activeCycle->new_balance` (:669). That is the stored principal + interest, with **no** repayments subtracted and **no** penalty added. Commit `cb16321` ("loan borrowers update", 2026-09-04) changed it from `final_outstanding` to `new_balance`. This is a regression against Rule 6.
- `getRolloverPreview` (:952) still uses `final_outstanding`, so **the preview shown to staff differs from what is booked**.
- Start date (:717-735): a custom date if given; otherwise the last repayment date if the cycle is fully settled and paid late; otherwise the **due date**. While penalty is running unpaid, the start date is the due date, not the last penalty day, and the penalty itself is dropped.
- Due date = start + one product period (:738-764). The custom `period_days` is wrongly combined with the product unit: a value meant as days becomes months for a monthly product.
- Old cycle → `completed` (:782). History is kept. **Verified working.**
- Double carry: `uk_loan_cycle_number (loan_id, cycle_number)` blocks a duplicate cycle number. But there is no `DB::transaction`: the old cycle is marked completed before the new one is created. `getActiveCycle` (:60-75) falls back to the **latest cycle even if completed**, so a loan with no active cycle can be rolled from an already carried-forward cycle. **Partial.**
- The interest rate on rollover defaults to the product rate, not the cycle's rate. `waive_penalty` sets the loan status to `forbearance`, which waives penalties on all cycles (see Rule 5).
- `Loan::rollover()` (`Loan.php:659-697`) calls `LoanCalculator::calculateCapitalizedInterest`, which **does not exist**. That is dead code and would fatal if called. **Broken.**
- `Loan::manualBalanceAdjustment` (:492-550) and `startPaymentPlan` (:966-1072) are two more rollover-like paths:
  - Both start "now" and set `amount = new principal + interest`.
  - The payment plan saves `interest_rate 0` but adds interest into `new_balance`. `calculateCycleBalance` then recomputes it at 0%, so the stored and computed balances disagree.
  - `startPaymentPlan` excludes penalty.

**Worked example: loan 529** (real data). Cycle 1: principal 12,000, interest 2,400 → 14,400, due 10-06. It was rolled on the due date with no repayment, giving cycle 2 with previous balance 14,400, start 10-06, due 10-16. That is correct by the rule.
If cycle 2 is rolled on 10-17:

| | Rule | Code (`executeRollover`) |
|---|---|---|
| New principal | 17,280 − 2,500 + 1,478 = **16,258** | **17,280** (2,500 repayment ignored, penalty ignored) |
| Start | 10-17 (last penalty day) | 10-16 (due date) |
| Due | 10-27 | 10-26 |
| New balance @20% | 19,509.60 | 20,736.00 |

The borrower is over-charged 1,226.40 on the new cycle, and the preview would have shown 16,258.
Data: on 19 rollover cycles, `previous_balance` ≠ the prior cycle's `new_balance`. Those were created by older code that subtracted repayments; for example, loan 461 cycle 2 is 36,960 = 136,960 − 100,000. History is therefore mixed between two formulas.

Affected: `LoanCalculator.php:60-75, 634-865, 934-1011`; `Loan.php:492-550, 659-697, 966-1072`; `LoanController.php:1078-1243`. Migration risk: **medium**. Re-deriving the 57 existing rollover cycles needs a decision on whether history is restated.

### Rule 7: `loans.amount` vs `original_amount`
**Current:**
- `executeRollover` sets `loans.amount = newBalance` (principal + new interest) (:813). So do `manualBalanceAdjustment` (:532), `startPaymentPlan` (:1060), `FixLoanCycles` (:86) and `Loan::rollover` (:671).
- `original_amount` is set by `createInitialCycle` (:607) and `apply` (`LoanController.php:98`). It is **NULL on 465 of 514 loans**, including 12 rolled loans.
- On the 29 rolled loans: `SUM(amount)` = 3,907,925 vs current principal 3,367,164 vs first principal 2,302,308.

**Additional bug:**
- `calculateCycleBalance` uses `principal = previous_balance > 0 ? previous_balance : loans.amount` (:227). Cycle 1 (previous_balance 0) is therefore recomputed from the *current* `loans.amount` after any rollover. For loan 494, cycle 1 would be computed on 69,120 instead of 40,000.
- 43 single-cycle loans already have `loans.amount` ≠ the cycle-1 principal. Example: loan 490 has amount 132,000, but the cycle principal is 124,000.

**Readers of `loans.amount`** (all treat it as "amount lent"; none read `original_amount` except two sidebar labels):

| File:line | Use | Mismatch |
|---|---|---|
| `DashboardController.php:49,54` | NPL total debt = SUM(loans.amount) | includes capitalised interest |
| `DashboardController.php:290` | monthly "loans" chart SUM(amount) by borrow_date | rolled loans counted at current balance in original month |
| `DashboardController.php:96-101,178-183` | total disbursements/repayments = SUM(all rows) | 14.42M of 24.24M disbursements and 18.94M of 29.52M repayments are non-cash book entries (ROLL OVER, CREDIT DISCOUNT, BAD DEBT, INTEREST FEES…) |
| `DashboardController.php:371,395` | interest = rate × loans.amount | interest on interest |
| `ReportController.php:321-323,734-737` | balance = SUM(loans.amount) − SUM(repayments) | wrong after any rollover |
| `ReportController.php:357,375,409,445,482,561,563` | portfolio/defaulted/cleared totals | current balance, not principal; :445 "total_amount_repaid" sums loans.amount |
| `ReportController.php:447,465,603` | interest = rate × loans.amount | as above |
| `ReportController.php:336,361,476` | "default_listings"/"non_performing" count `status='rejected'` | wrong status |
| `Loan.php:738` `outstanding_balance` | amount − all repayments | repayments of old cycles subtract from the new amount |
| `Loan.php:891` `calculateTotalDue` | amount + rate×amount + capitalized_interest − repayments | interest counted twice |
| `RepaymentController.php:97-104`, `Loan.php:346-357,1100-1107` | repaid when SUM(all repayments) ≥ loans.amount | mixes cycles |
| views: `loans/show.blade.php`, `partials/table/table-loans.blade.php`, `partials/card/users-card.blade.php`, `partials/table/table-users.blade.php` | show/sum `$loan->amount` | Unknown per column (not opened) |

Migration risk: **high**. Backfilling `original_amount` needs the cycle-1 principal (`new_balance − interest_capitalized`), not `loans.amount`. Changing the meaning of `amount` changes every report.

### Rule 8: repayments ↔ cycles
| Check | Result | Label |
|---|---|---|
| Repayments with null `loan_cycle_id` | 0 / 800 | **Verified working** (data) |
| Repayment cycle belongs to another loan | 0 | **Verified working** (data) |
| FK `repayments.loan_cycle_id → loan_cycles` | present | **Verified working** |
| `RepaymentController@store` validates the cycle is of the same loan | no (`exists:loan_cycles,id` only, :67) | **Partial** |
| Default to the active cycle | only in `RepaymentService::createRepayment` (:26-33), which **is never called**. The controller requires `loan_cycle_id` from the form | **Partial** |
| Cycle status from cycle balance | `new_balance − cycle repayments ≤ 0` → completed (:80-86). Ignores penalty and the late-payment rule | **Partial** |
| Loan status from cycle balance | `!hasActiveCycles OR SUM(all repayments) ≥ loans.amount` → repaid (:89-105). Not from the cycle balance | **Broken** |
| Data consistency | 434 repaid loans still have an `active` cycle (cycle status never synced). 3 repayments dated before their cycle start. 1 repayment (1,600) booked to an old cycle after the next cycle started | **Broken** |
| Disbursements not tied to cycles | `DisbursementService` sets `loan_cycle_id` (:34). 629/629 disbursements have a cycle. Balances do not use disbursements | **Partial** (owner to confirm the intent) |
| Deleting a repayment | can set status `active`, which is **not in the `loans.status` enum** → SQL error in strict mode (:211-216) | **Broken** |

Worked example, **loan 486**:
- Cycle 1 (31,651.43, due 08-26) received 3 × 1,000, one of them dated 09-03, the same day a payment plan opened cycle 2. Cycle 2's previous balance of 29,651.43 reflects only 2,000.
- Because the loan is `forbearance`, the penalty is waived on cycle 1 too. The 1,000 dated after due therefore reduces nothing: `max(0, 0 − 1,000)`.

---

## 1. Loan products (`loan_types`, `LoanType`)

1. **Purpose:** defines period, unit, interest %, penalty %/day, grace days and default threshold. There are 19 products. Product 1 "Emergency 10 Days" carries 355/514 loans and is the self-service product (`config/lending.php:9`).
2. **Code:**
   - `app/Models/LoanType.php` (fillable lacks `penalty_rate`, `grace_period_days`, `default_threshold_days`)
   - migration `2025_01_01_000025_create_loan_types_table.php`
   - view `2025_01_01_000004_create_loan_type_analysis_view.php`
   - **No controller, route or UI to manage products** (only `LoanType::all()` for dropdowns: `LoanController.php:848,934`, `UserController.php:76`). **Planned only** (management).
3. **Tables:** `loan_types` 1-n `loans`, 1-n `disbursements`.
4. **Rules:**

   | Rule | Formula | Label |
   |---|---|---|
   | Interest | flat `rate%` of principal per cycle | **Implemented, not verified** |
   | Penalty | `penalty_rate%` of the unpaid balance per day (see Rule 5) | **Broken** |
   | Grace days | `loan_types.grace_period_days` subtracted from penalty days. All products = 0 | **Implemented, not verified** |
   | Default threshold | `loan_types.default_threshold_days` is **never read**. `Loan::getNplThreshold` (:230-252) uses `period_days + clamp(2×period, 14, 60)`: 10-day = 30, 1-month = 90, 3-month = 150 | **Broken** |
   | Unit `year` (product 19) | `calculateDueDate` (`LoanCalculator.php:37-52`) and `getPeriodInDays` (`Loan.php:258-264`) only know `years`. A new loan would be due +1 day and the NPL threshold would use 30. The existing loan 511 has the correct date (set elsewhere) | **Broken** (latent) |
   | Processing fee | `LoanCalculator` reads `loanType->processing_fee_rate` (:218), a column that does not exist → always 0. `loans.processing_fee_rate` = 0 on all loans | **Unknown** |
5. **Roles:** none (DB only).
6. **Money flow:** rates are copied to `loan_cycles.interest_rate` at cycle creation. The penalty is always read live from the product, so changing a product changes the history of open loans.
7. **Dependencies:** `LoanCalculator`, `Loan::getNplThreshold`, `BorrowingEligibility` (quote), `CaseController.php:123-145`. That last one hard-codes `INTERVAL period DAY`, which is wrong for weeks/months.
8. **Issues:**
   - Penalty 10%/day on 17 of 19 products (a 30-day overdue loan accrues 300%).
   - Names disagree with rates: "Quick Loans 2 months 15% monthly" = 30% total, "… 20% monthly" = 20% total, and "Quick Loans 14" is 2 weeks.
9. **Recommendation:** add product admin with audit, snapshot `penalty_rate`/`grace` on the cycle, use `default_threshold_days`, and normalise `unit`.

## 2. Origination and approval (`LoanController`)

1. **Purpose:** borrower self-service `apply` (`LoanController.php:59-134`) and staff `store`/`update`/`edit`/`destroy`. "Approval" is just editing `status` through `update`.
2. **Routes:**
   - `POST /borrow` (apply)
   - `Route::resource('loans')` (`routes/web.php:138`, any one of view/create/edit/delete permissions)
   - `loans/{loan}/…` rollover, payment-plan, forbearance, recovery, agreement and signature (`web.php:141-205`)
   - Views: `loans/*`, `partials/*`.
3. **Tables:** `loans` (user, loan_type, guarantor_id→users, loan_officer_id→users, partner) plus a `loan_cycles` row created at application time.
4. **Rules:**

   | Rule | Where | Label |
   |---|---|---|
   | Self-service: limit/min, one open loan, row lock, consent required, status pending, original_amount set | `:59-134` | **Implemented, not verified** |
   | Staff create: admin/teller (by **`users.role` column**, not Spatie) may set status `pending/approved/disbursed/repaid` directly | `:152-179, 285` | **Partial** (no maker-checker) |
   | Duplicate-loan warning, overridable by the `X-Force-Create` header | `:216-279` | **Implemented, not verified** |
   | Cycle 1 is created at **application time**, starting at `borrow_date`, not the disbursement date. Approve/disburse never moves it | `:114, 297`; `LoanCalculator.php:562-622` | **Broken** |
   | `store` doesn't set `original_amount` (only `createInitialCycle` does, and its failure is swallowed `:309-314`) | `:290-315` | **Partial** |
   | Update: status `pending/approved/disbursed/repaid/overdue/defaulted`. **`rejected` is not allowed**, so there is no way to reject in the UI. Changing `amount`/`borrow_date`/type does **not** update the cycle | `:413-461` | **Broken** |
   | Disburse via `DisbursementController.php:181-182` approved→disbursed | | **Implemented, not verified** |
   | Guarantor: any borrower user, optional; relationship free text | `:181-182, 855-876` | **Implemented, not verified** |
   | Loan officer: any admin/teller user, optional | `:1757` | **Implemented, not verified** |
   | Consent: required on create. The `update` path **clears consent if the checkbox is absent** (:435-437). `giveConsent()` (:1736) calls the non-existent `Loan::giveConsent` and has no route | | **Broken** |
   | Duplicate private `createInitialCycle` in the controller (:373-411), unused | | tech debt |
   | `loans.status` enum lacks `active` and `written_off`, but code sets `active` (`Loan.php:376,400,1080`, `RepaymentController.php:216`, `UpdateLoanStatuses.php:216,232`). Strict mode is on (`config/database.php:58`), so those saves throw | | **Broken** |
5. **Roles (DB):**
   - `edit loans` is held by super-admin, admin, **broker**, teller, manager, loan-officer and **recovery-officer**. Any of them can set status to approved/disbursed/repaid (brokers are limited to their own clients, `:2034-2041`).
   - `rollover loans` is held by super-admin/admin/manager/loan-officer, but the controller also requires `users.role ∈ {admin, teller}` (`:1175`). Managers and loan officers are therefore blocked, and tellers lack the permission.
   - Payment plan only checks `edit loans`. Edit mode updates **any `cycle_id`**, without checking it belongs to the loan (`:1098-1113`).
6. **Money flow:** application → pending loan + cycle 1 (P + I) → staff approve → disbursement row (tied to the cycle) → repayments.
7. **Dependencies:** BorrowingEligibility, LoanAgreementService, SignatureService, LoanCalculator.
8. **Issues:** see the table. Also, `store` logs the full request at debug level (`:146`), including signature data.
9. **Recommendations:** a dedicated approve/reject/disburse action with permission and audit; create/reset cycle 1 at disbursement; forbid setting `disbursed`/`repaid` by edit; use Spatie roles, not `users.role`.

## 3. Cycles, interest, penalty, rollover, payment plans, forbearance, grace

1. **Purpose:** each loan has `loan_cycles` (571 rows; 30 loans have >1 cycle, max 8). The current outstanding is computed live by `calculateCycleBalance`. Nothing is stored except `new_balance` = P + I.
2. **Code:**
   - `app/Services/LoanCalculator.php` (cycle math, rollover, partner returns :383-560, :1341+)
   - `app/Models/Loan.php`, `app/Models/LoanCycle.php`
   - `RepaymentController.php`
   - `RepaymentService.php` (unused)
   - `LoanController.php:1022-1322`
   - `LoanProgress.php:28`
   - Migrations `…000030_create_loan_cycles_table`, `…000031_create_loan_ledger_table`.
3. **Tables:**
   - `loan_cycles` (FK loan, unique (loan_id, cycle_number); status enum active/completed/defaulted/repaid. The code returns `overdue` but never saves it).
   - `repayments.loan_cycle_id` FK.
   - `disbursements.loan_cycle_id` (no FK).
   - **`loan_ledger` has 0 rows and is never written** (only the model exists). **Planned only.**
4. **Rules:**

   | Feature | Formula / behaviour | Label |
   |---|---|---|
   | Cycle interest | `principal × cycle.interest_rate%`. Principal = `previous_balance`, or `loans.amount` for cycle 1 (wrong after a rollover) | **Partial** |
   | Penalty / late repayment | see Rule 5 | **Broken** |
   | Rollover | see Rule 6 | **Broken** |
   | Payment plan (`startPaymentPlan`) | new principal = P + I − cycle repayments (no penalty); cycle saved at 0% but `new_balance` includes interest; due = now + days; loan → forbearance | **Partial** |
   | Manual balance adjustment | staff set any new principal, + product interest, due now + `period` **as days** (wrong for weekly/monthly products) (:507); no approval or reason requirement | **Partial** |
   | Forbearance (`grantForbearance`, `Loan.php:458-481`) | moves `loans.due_date` only, **not the cycle due date**; the forbearance status waives all penalties; `endForbearance` → `overdue` regardless of balance | **Partial** |
   | Grace days (loan level) | `grace_days_balance/earned/used`. Nothing ever **earns** grace days. `applyGracePeriod` is only called from the dead `Loan::rollover` | **Planned only** |
   | Repaid → partner principal return | only in `executeRollover` when `new_balance ≤ 0` (:672-676). Never true in practice, since `new_balance` is P + I | **Broken** |
5. **Roles:** create repayments: super-admin/admin/teller/manager/recovery-officer. edit: + accountant. delete: super-admin/admin/manager. `edit`/`destroy` also gate on `users.role ∈ {admin, broker}` and `$loan->broker_id` (a column that doesn't exist on loans).
6. **Money flow:** disbursement (cash or "ROLL OVER" book entry) → cycle P + I → repayments (cash plus non-cash: ROLL OVER, CREDIT DISCOUNT, BAD DEBT, INTEREST FEES, BROKER FEE(S)), which are **all treated as cash** in balances and dashboards → rollover creates the next cycle.
7. **Dependencies:** partner/investment ledgers (`calculatePartnerReturns`, `distributeRepaymentToPartners`), the Bavix wallets commands, and reports.
8. **Issues:** see the tables above. Also `Investment::recordRepayment` creates a repayment with `loan_id = null`, but the column is NOT NULL (`app/Models/Investment.php:448`). **Broken.**
9. **Recommendation:** one source of truth for cycle balance (stored penalty, payments and closing balance per cycle, or the ledger); fix Rule 5 → 6 → 7 in that order; a transaction plus `lockForUpdate` on rollover.

### Legacy rollover representation in data
Before cycles existed, a rollover was a **chain of separate loan rows**: the old loan was closed by a repayment with `transaction='ROLL OVER'`, and a new loan was opened by a disbursement `'ROLL OVER'` (always on cycle 1).

| Text | repayments n / KES | disbursements n / KES |
|---|---|---|
| ROLL OVER | 239 / 15,555,827.50 | 241 / 14,353,842.65 (233 loans) |
| CREDIT DISCOUNT | 83 / 2,217,149.50 | 15 / 338,540.00 |
| BAD DEBT | 13 / 999,145.25 | 0 |
| BROKER FEE(S) | 22 / 74,300.80 | 0 |
| TO BE PAID | 2 / 80,000.00 | 0 |
| SPLIT | 1 / 1,920.00 | 1 / 1,600.00 |
| CARRY FORWARD | 0 | 2 / 70,000.00 |

- 165/239 ROLL OVER repayments match a ROLL OVER disbursement on another loan of the same borrower on the same day.
- 162 loans both received and closed by rollover, so they are mid-chain.
- 72 loans have more than one disbursement.

The new engine (cycles) has 30 loans / 57 rollover cycles. Any total of disbursements or repayments double-counts legacy chains. Migration risk: **high** if chains are ever merged into cycles.

## 4. NPL detection and status updates

| Item | Behaviour | Label |
|---|---|---|
| `NplDetectionService::runDetection` / `checkLoan` | calls `$loan->updateNplStatus()`, which **does not exist** (`NplDetectionService.php:46,187`). Each loan throws, the error is caught and logged, and nothing is updated. Total debt uses disbursements + rate×amount + `calculatePenalties` − repayments (:128-133) | **Broken** |
| `Loan::updateStatus` (`Loan.php:336-415`) | never called anywhere. Would mark repaid when `!hasActiveCycles` and set `active` (not in the enum) | **Broken** / dead |
| `loans:update-statuses` (`UpdateLoanStatuses.php`) | status from `loans.calculated_due_date`/`due_date` and days overdue only, **ignoring repayments and cycle balance**. Includes **pending/approved** loans (only final, forbearance and recovery are excluded, :56-73). A loan paid on time but left `disbursed` becomes overdue/defaulted. Sets `defaulted` without `default_triggered` or a recovery case (:237-257). Has `--dry-run` | **Partial** |
| `markAsDefaulted` → recovery case | `Loan.php:421-445, 571-606`. Case number is count+1 (race), principal = loans.amount | **Implemented, not verified** |
| Trigger | **no scheduler**. All of these run only manually | **Broken** (operationally) |
| Current data | 0 loans `defaulted`/`overdue`/`recovery` (465 repaid, 29 disbursed, 15 pending, 5 forbearance), even though many disbursed loans are past due. For example, loan 331 was due 2026-03-12 and is still `disbursed` | evidence that nothing runs |

## 5. Console commands (`app/Console/Commands`)

| Command | Purpose | Writes? |
|---|---|---|
| `loans:detect-npl {--loan}` | runs NplDetectionService | Intended yes; in practice **no** (method missing) |
| `loans:update-statuses {--dry-run --loan --status --force}` | overdue/default flags from due dates | **Yes** (status, days_overdue, grace days); dry-run available |
| `loans:fix-cycles` | rewrites all cycle due dates chained from cycle 1, sets `loans.amount` = last cycle `new_balance` | **Yes, no dry-run**. Dangerous (overwrites custom dates and amounts) |
| `loans:repair-cycles` | creates missing cycle 1, flips cycle status active/completed | **Yes, no dry-run** |
| `loans:backfill-house-account` | creates InvestmentFunding rows for the House Principal account | **Yes, no dry-run** |
| `loans:balance-house-accounts {--apply}` | computes House Principal/Interest balances | only with `--apply` |
| `partners:backfill-broker-fees {--apply}` | historical broker fee + penalty share → broker wallet (uses `calculateCycleBalance`, so it inherits the Rule 5 bug) | only with `--apply` |
| `partners:backfill-house-interest {--apply}` | historical house interest + penalty share (same dependency) | only with `--apply` |
| `risk:assess {--dry-run}` | fills `loan_risk_assessments` (4Cs) | **Yes** unless `--dry-run` |
| `wallets:bootstrap {--apply}` | creates Bavix wallets, seeds from journal | only with `--apply` |
| `wallets:check` | reconciles wallets vs loans/journal | No |
| `wallets:reconcile-from-journal {--apply}` | compares the journal to wallets; `--apply` resets balances | only with `--apply` |
| `wallets:sync {--dry-run}` | creates customer wallets, posts borrower cash (idempotent by source) | **Yes** unless `--dry-run` |

## 6. Risks and recommendations (priority)
1. Fix the Rule 5 late-repayment math before anything reads balances for money (rollover, partner shares, broker/house backfills, wallets).
2. Restore Rule 6 (`final_outstanding`, start on the last penalty day), make preview == execute, and wrap it in a transaction with `lockForUpdate`.
3. Decide the meaning of `loans.amount` (Rule 7). Backfill `original_amount` from the cycle-1 principal. Stop cycle 1 from reading `loans.amount`.
4. Derive cycle and loan status only from the cycle balance. Validate cycle ↔ loan on repayment. Resync 434 repaid loans with active cycles.
5. Add `active`/`written_off` to the enum or stop using them. Remove the dead `Loan::rollover`, `updateStatus`, `giveConsent` and `updateNplStatus` calls.
6. Separate cash from non-cash transactions (type column) so dashboards stop counting book entries as cash.
7. Add a scheduler only after 1-4 are done. Give `fix-cycles`, `repair-cycles` and `backfill-house-account` a dry-run mode.

## 7. Owner decisions needed
- Is a penalty rate of **10% per day** (most products) intended? Is there a penalty cap? `Loan::calculatePenalties` caps at 100%, `LoanCalculator` has no cap.
- Rule 5 detail: does a payment on the due-plus-N day stop the penalty for that day? (Due 10th, paid 11th = 1 day, so the payment day counts.) Confirm for partial late payments: does the penalty keep running on the remainder?
- Rollover: on a forbearance/payment plan, are penalties waived for past cycles too (current behaviour) or only for the new cycle?
- Rollover interest rate: the product rate or the previous cycle's (custom) rate?
- Should custom `period_days` on rollover be days regardless of the product unit?
- `loans.amount`: the new principal (Rule 7) or principal + interest (current)? Should historical reports be restated?
- Should non-cash entries (CREDIT DISCOUNT, BAD DEBT, BROKER FEE(S), INTEREST FEES, TO BE PAID, SPLIT) reduce the balance as repayments, or be write-offs/fees?
- Legacy ROLL OVER loan chains: merge them into cycles, or leave them as separate loans and exclude them from totals?
- Disbursements: should `loan_cycle_id` stay populated (it is on all 629), or be removed (Rule 8 says "not tied")?
- Default/NPL threshold per product: use `loan_types.default_threshold_days` (30/45) or the current heuristic (30-150 days)?
- Who may approve, reject and disburse? Should brokers and recovery officers keep `edit loans`?
- Should cycle 1 start on the application date or the disbursement date?

## Fixes applied (2026-10-18)

Scope: status labels, detection wiring, scheduling, routes, missing non-calc methods. **No calculation logic was changed** (see "Left for the calc revamp" below). No schema change, no SQL twin needed.

### What changed
| # | Fix | Files |
|---|-----|-------|
| 1 | Invalid status `'active'` is no longer written to `loans.status` (not in the enum, strict mode threw). Running loans are written as `disbursed`; overdue stays `overdue`. Changed: `Loan::endPaymentPlan`, repayment delete path, both "no longer overdue / grace used" branches of `loans:update-statuses`. `STATUS_ACTIVE` stays only in read-side `whereIn` lists / labels. | `app/Models/Loan.php`, `app/Http/Controllers/RepaymentController.php`, `app/Console/Commands/UpdateLoanStatuses.php` |
| 1b | `RepaymentController` used `Carbon` without importing it, so **every repayment delete crashed** after the row was deleted (status never reverted). Added `use Carbon\Carbon;`. | `RepaymentController.php` |
| 2 | `Loan::updateNplStatus()` implemented (was called by `NplDetectionService` but missing). Acts on `disbursed` / `overdue` / expired `forbearance` only; writes `days_overdue`, `last_overdue_check`, `disbursed→overdue` (or back when the due date moved out), `is_non_performing` + `default_date` once days overdue ≥ threshold. Does not set `default_triggered` (still `markAsDefaulted`). New `Loan::getDefaultThresholdDays()`: `npl_trigger_threshold` > 0 → `loan_types.default_threshold_days` > 0 → old `getNplThreshold()` heuristic. Used by both detection commands. | `Loan.php`, `app/Services/NplDetectionService.php` |
| 2b | `Loan::calculateDaysOverdue()` returned a **negative** number under Carbon 3 (`now->diffInDays(past)` is signed), so nothing was ever detected as overdue. Now diffs forward from the due date. Only used by status/NPL detection and `getRecoveryStage` (not by penalty maths). | `Loan.php` |
| 2c | `NplDetectionService::runDetection` now checks only disbursed/overdue/forbearance (was `approved`/`active`), counts new vs already-NPL, stopped double-counting `loans_checked`, supports dry-run (rolls back its transaction). | `NplDetectionService.php` |
| 3 | Scheduler added: `loans:update-statuses` daily 01:00 and `loans:detect-npl` daily 01:30, Africa/Nairobi, `withoutOverlapping`, output appended to `storage/logs/loan-statuses.log` / `loan-npl.log`. `loans:update-statuses` now never touches pending/approved/rejected (even with `--loan`), picks up expired forbearance, persists `days_overdue` each run, and **no longer flips `defaulted` back to `overdue` on the next run** (it did: second run downgraded all 11 defaults). `loans:detect-npl --dry-run` added (`--dry-run` already existed on update-statuses). | `routes/console.php`, `UpdateLoanStatuses.php`, `DetectNplLoans.php` |
| 4 | Staff can reject: `PATCH /loans/{loan}/reject` (`loans.reject`, `rejection_reason` required) and `rejected` accepted by `loans.update` (with `rejection_reason`, required_if). Only from pending/approved; gated by `edit loans` (the permission approval uses today; `approve loans` exists in the seeder but nothing checks it). Reason is appended to `loans.recovery_notes` (`loans.reason` is the loan purpose). Edit form shows a "Rejected" option + reason box for pending/approved loans. | `LoanController.php` (`reject`, `update`), `routes/web.php`, `resources/views/loans/edit.blade.php` |
| 5 | Missing route methods: `createForUser` implemented (create form with borrower preselected). `adminEdit`, `chartData`, `getUserLoans` routes **removed** (no view/JS referenced `admin.loans.edit`, `loans.chart-data`, `loans.user-loans`; use `loans.edit`). `giveConsent` (unrouted) no longer calls the missing `Loan::giveConsent()`; it sets `consent`/`consent_date`. | `LoanController.php`, `routes/web.php` |
| 6 | Dead code removed: `Loan::rollover` (called missing `calculateCapitalizedInterest`/`calculateRolloverDueDate`; no callers) and `Loan::updateStatus` (no callers, wrote `'active'`). `LoanCalculator` rollover untouched. | `Loan.php` |
| 7 | `--dry-run` on `loans:fix-cycles`, `loans:repair-cycles`, `loans:backfill-house-account`: the whole run is wrapped in a transaction, rolled back on dry-run (committed otherwise), and prints insert/update/delete counts. What they compute is unchanged. | the three command files |

### Test results (clone of `loans_main`, 2026-10-17)
- `loans:update-statuses` first real run: **19 status changes** — 11 → `defaulted` (+ NPL flag, 11 recovery cases created by `markAsDefaulted`), 8 → `overdue` (incl. expired forbearance 465, 486). Second run: 0 changes. `loans:detect-npl` afterwards: 23 checked, 0 new.
- Run alone first, `loans:detect-npl --dry-run` would flag the same 11 NPL + 8 overdue.
- Repayment delete + re-save through the controller: past-due loan → `overdue` → `repaid`; future-due loan → `disbursed` → `repaid`; no enum error.
- Reject: pending loan → `rejected` with note; disbursed loan refused (422).
- `fix-cycles --dry-run`: would make 674 updates; `backfill-house-account --dry-run`: 619 updates; `repair-cycles` **crashes** (`Undefined array key -1` at `RepairLoanCycles.php:183`, cycles not numbered from 1) — pre-existing calc bug; the transaction now rolls back cleanly. Do not run it live.
- `php artisan test`: 20 pass / 6 fail — 5 ProfileTest + 1 AuthenticationTest (login redirects to `/account` instead of `/dashboard`), none in loan code.

### Live steps (in order)
1. Deploy code; `php artisan optimize:clear`.
2. `php artisan loans:update-statuses --dry-run` and `php artisan loans:detect-npl --dry-run`; review the list. On the mirror the first real run changes **19 loans (11 defaulted, 8 overdue)** — several defaulted loans are from 2025 (e.g. #177, #200, #208, #217) and may actually be repaid with a stale status; fix those by hand first, or every one gets a recovery case.
3. `php artisan loans:update-statuses` then `php artisan loans:detect-npl`.
4. CyberPanel → Cron Jobs (site user): `* * * * * cd /home/<site>/public_html && php artisan schedule:run >> /dev/null 2>&1`. Verify with `php artisan schedule:list`.

### Deliberately left for the calc revamp
- `LoanCalculator` due date for unit `year` (≈ lines 37-52) is not handled on first calculation; same for `Loan::getPeriodInDays()` (matches `years`, the product uses `year` → falls back to 30). Not touched.
- `getNplThreshold()` heuristic unchanged because `LoanCalculator` uses it for the penalty threshold when `npl_trigger_threshold` = 0 (12 disbursed loans). So penalties and status detection can use different thresholds for those loans until the revamp.
- `Loan::getDaysOverdue()` and `calculatePenalties()` have the same Carbon 3 signed-diff issue — penalty maths, not touched.
- How "repaid" is decided, cycle balances, `loans.amount`, rollover/payment-plan maths, grace-day consumption, the `repair-cycles` crash, and `NplDetectionService::createRecoveryCase` debt figures.
- `recovery` loans are still excluded from automatic updates; `default_triggered` loans are skipped by NPL detection.
