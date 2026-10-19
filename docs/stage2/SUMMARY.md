# Stage 2 summary: UI journeys (19 October 2026)

All work is on branch `stage-2-ui` and is **not committed**. Loan calculation, allocation, the "repaid" rule and `loans.amount` are unchanged. The only schema change is the business tables, which the owner approved.

Final checks: `php artisan test` gives 31 passed, 0 failed. All 232 routes resolve to real methods, all views compile, and every permission the code uses exists on the live mirror.

## What was built

| Journey | Who | Walkthrough and screenshots | Module notes |
|---|---|---|---|
| Foundation: one set of design tokens (including loan-status colours), components `x-az.*` (customers) and `x-staff.*` (staff), `demo:seed`, screenshot harness | all | [README](README.md), [DESIGN-TOKENS](DESIGN-TOKENS.md) | — |
| A. Business onboarding: add a business (details, directors and owners, documents, financials, review), plus the staff business review | borrower with approved KYC; staff | [business.md](walkthroughs/business.md) | [E-business.md](../audit/modules/E-business.md) |
| B. M-Pesa repayment: repay screen, "I've paid" code, receipts, facility closed, edge cases | borrower | [repay.md](walkthroughs/repay.md) | A-customer.md, Stage 2 section |
| C. Partner self-service: portfolio, investments, funded loans, earnings, statement and CSV, add funds, withdrawal request, borrower/partner switcher | partner | [partner.md](walkthroughs/partner.md) | C-money.md, Stage 2 section |
| D1. Borrower journeys: every loan state, extension request with preview, payment-plan acceptance, statement of account, notifications, help, loan terms, 5-step borrow | borrower | [borrower.md](walkthroughs/borrower.md) | A-customer.md, Stage 2 section |
| D2. Staff desk: inbox, approval queue (4Cs), disbursement queue, receive payment (cycle picker), reversal with reason, collections worklist, teller end of day, roles matrix, loan products, wallet checks, partner payouts | staff roles | [staff.md](walkthroughs/staff.md) | D-staff-roles-journeys.md, Stage 2 section |

Screenshots (desktop 1440 px and phone 390 px) are in `docs/stage2/screens/<journey>/`. The demo logins are in [README](README.md); the password is `Demo12345!`. They work only on the clone `loans_wp_s2`, never on live.

## Mocked or interim: Stage 4 dependencies

| Area | Today | Needs in Stage 4 |
|---|---|---|
| M-Pesa | STK push button off; "I've paid" sends an email and writes an audit row; claim status kept in the session | Daraja STK push and callbacks, C2B, `payment_claims` table, reconciliation, staff claims queue, "payment received" email |
| Receipts | No "balance after" | Per-cycle ledger and running balance |
| Borrower requests (extension, payment-plan consent, help) | Email plus an audit-log row | Requests table with approval, stored consent |
| Extension preview | Indicative, from `getRolloverPreview` | Rollover maths rework (rules 5 and 6) |
| Partner add funds and withdrawal | Email plus an audit row; nothing posted | Funding and withdrawal request tables, maker-checker, PartnerLedger posting |
| Partner KYC and online subscription | Placeholder | Partner KYC, subscription agreement, invest flow |
| Business | Placeholders for business loans, trade finance, asset-backed lending, AML | Products, limits, valuation, AML screening, director self-verification |
| Staff | Placeholders for six-account split preview, close day, restructure, collector performance, product editing, ledger and bank reconciliation, second approver | Rule 3 split, cash-up table, products with audit and term snapshots, ledger, maker-checker |
| Reversal | Soft delete plus a reason in the audit log | True reversal entries |
| Notifications | Built from the borrower's own records | Notifications table, SMS gateway |
| Penalty display | "Owed today" hidden on written-off loans (compounding gives figures like 114,000 on a 5,000 loan) | Penalty rework (rule 5, cap decision) |
| Rejected loans | Reopened only by staff editing the details | A flag column so staff can authorise the customer to edit a rejected application, plus a customer edit screen |
| M-Pesa credentials | Sandbox shortcode 174379 in local `.env`, not shown to borrowers | Live paybill, production Daraja consumer key, secret and passkey |

## To deploy (when you choose to)
1. Run `mysql <db> < database/sql/2026_10_19_businesses.sql`. It is safe to run twice and matches the migrations.
2. Upload the code and `public/build` (rebuilt), then run `php artisan optimize:clear && php artisan permission:cache-reset`.
3. Fill the new `.env` keys:
   - `MPESA_PAYBILL` and `MPESA_ACCOUNT_FORMAT`.
   - `PARTNER_FUNDING_*` (bank name, account name, account number, branch, SWIFT, paybill).

   Until they are set, screens show placeholders.
4. Non-cash rule for `TO BE PAID`, `BROKER FEE(S)` and `SPLIT`: run `php artisan wallets:sync --dry-run`. On live, expect about 26 postings, net KES −154,620.80. Then run `php artisan wallets:sync`.

## Owner decisions made (19 October 2026)
- **Partners pay into:** I&M Bank, IMAGINE-NATION AGENCY LIMITED, Wilson branch, A/c 01104493106350, or M-Pesa paybill 542542 with account no. 01104493106350.
  - These are the defaults in `config/lending.php` (`partner_funding`); env vars override them.
  - Because the paybill account no. is the bank account, partners send their `AZP-` reference through the "I've sent funds" form.
  - **Editable on `/system` (addendum, 20 October 2026).** A "Partner funding details" card holds the 9 values in `system.settings.partner_funding`; there is no schema change and no SQL script.
    - Read order (`App\Support\PartnerFunding`): the saved value, else `config/lending.php`/env. A blank field returns to the default.
    - Only the super-admin can save, and must enter their current password. Admins see the values read-only.
    - Every save writes an `audit_logs` row (`table_name = system`, `event = partner_funding_changed`, old and new values) and emails every admin and super-admin the old and new values.
    - A second-person approval for changes needs the Stage 4 Phase 5 approvals table.
    - Tests: `tests/Feature/PartnerFundingSettingsTest.php`.
- **Account-number format:** `MPESA_ACCOUNT_FORMAT=loan_number`.
- **Borrower paybill: not set yet.** The 174379 shortcode and passkey you supplied are Safaricom's public sandbox (test) credentials, but `.env` says `MPESA_ENV=production`. Borrowers keep seeing "paybill to be confirmed" until the live paybill and live Daraja credentials are confirmed.
- **Rejecting a loan** needs `approve loans` on both the reject route and the edit form; tellers and brokers can no longer reject.
  - A rejected loan goes back to pending only when staff update its details in the same save. A note "Reopened by … after updating: …" is added.
  - Letting staff authorise the customer to edit a rejected application needs a flag column, so it is a Stage 4 item.
  - Tests: `tests/Feature/LoanRejectionTest.php`.
- **Super-admin is "sysadmin".** The account doesn't exist on live yet. Create it with:
  `php artisan users:super-admin sysadmin@aimzoneventures.com --create --phone=07XXXXXXXX`
  It prints a one-time password. Without `--create`, the command lists similar emails.
- **The 4 old Breeze `ProfileTest` failures are fixed.** The tests now match this app: the profile updates non-KYC fields at `/profile/update`, name and email are managed by KYC, and accounts are soft-deleted at `/profile/destroy`. 31 tests pass.

## Owner decisions still open
**Payments**
- The live borrower paybill and production Daraja credentials.
- The partner reference format (`AZP-0009`).
- Minimum top-up and withdrawal amounts.
- Who receives payment-claim and request emails. Today: admins.

**Recovery and borrowers**
- A separate recovery-team contact. Today it uses the general WhatsApp number.
- Whether borrowers see their officer's name. Today: yes.
- Written-off wording: is the balance still owed?
- How long a rejection stays on screen (30 days), and any wait before re-applying.
- Extension policy text.

**Business**
- Unique registration number?
- Must shareholdings total exactly 100%?
- Are bank statements required?
- Must other directors complete their own KYC?
- Which role approves, and is a second approver needed?
- Can an approved business be edited?

**Partners**
- Which investment notes partners see.
- Whether co-funders see every loan.
- Withdrawals from any wallet, or earnings only?
- Show the borrower's @handle, or initials only?

**Staff permissions**
- Should managers and accountants be allowed to reverse repayments? Today only admin can.
