# Staff journeys (Stage 2, set D2)

These are new staff desk pages under `/staff/*`. They use the TailAdmin layout and the `x-staff.*` components, and appear in the sidebar under **Desk** and **Set-up & finance** (`partials/sidebar-staff-desk.blade.php`). Each link shows only when you hold the permission its route needs.

Every money or status action goes to an **existing** route and controller method, which is unchanged:

| Action | Existing route | Screen |
|---|---|---|
| Approve | `PUT loans.update` (status → approved; all other fields re-sent unchanged) | Loan approvals |
| Reject | `PATCH loans.reject` (reason required) | Loan approvals |
| Disburse | `POST disbursements.store` | Disburse |
| Receive payment | `POST repayments.store` | Receive payment |
| Reverse payment | `RepaymentController@destroy`, called by `staff.repayments.reverse`; the reason goes to `AuditTrail` | Receive payment |
| Work a case | `cases.show` / `cases.create` | Collections |

Those four controller methods answer JSON, so the forms submit with fetch (`staff/desk/_ajax`). The server's validation errors appear in the form, and on success the page moves on with a confirmation.

Screenshots are in `docs/stage2/screens/staff/<role>/<name>-{desktop,phone}.png`. They were taken on the `loans_wp_s2` clone with the demo logins (password `Demo12345!`).

## Teller (teller@demo.aimzone.test)

1. **Inbox:** shows approved loans that are not yet disbursed. [desktop](../screens/staff/teller/inbox-desktop.png)
2. **Disbursement queue:** lists approved loans, oldest first, plus today's totals. Click **Disburse**. [queue](../screens/staff/teller/disburse-queue-desktop.png)
3. **Disburse:** fill in the amount (defaults to the principal), date, mode and a required reference.
   - The investment picker defaults to the house line (General Lending Book).
   - The partners funding the chosen investment are shown under the picker.
   - An M-Pesa payout and maker-checker are placeholders.
   - [desktop](../screens/staff/teller/disburse-desktop.png) · [phone](../screens/staff/teller/disburse-phone.png)
4. **Receive payment:** search for the loan, select it, pick one of the loan's own cycles, then enter amount, date, mode and reference.
   - "Split preview" is a placeholder (Stage 4: six-account split).
   - So is "payment confirmations": they arrive by email and are not stored.
   - [search](../screens/staff/teller/receive-search-desktop.png) · [loan](../screens/staff/teller/receive-loan-desktop.png) · [phone](../screens/staff/teller/receive-loan-phone.png)
5. **End of day:** shows what this teller entered today: payments and disbursements, cash vs book entries, and cash in by mode. It can be printed. "Close the day" is a placeholder (Stage 4: cash-up table). [desktop](../screens/staff/teller/end-of-day-desktop.png)
6. **Edge cases:**
   - Loan approvals is read-only, with no Approve or Reject buttons: [approvals](../screens/staff/teller/approvals-readonly-desktop.png).
   - `/staff/roles` returns 403.

## Manager (manager@demo.aimzone.test)

1. **Inbox:** shows loans to approve, loans approved but not disbursed, KYC submissions, and recovery cases assigned to the manager. [desktop](../screens/staff/manager/inbox-desktop.png)
2. **Loan approvals:** each row shows the 4Cs score (overall, then C·C·C·C), the limit and the recommendation, all read-only. A loan above its limit is flagged.
   - **Approve** opens a dialog. If the loan has no purpose of 10 or more characters, the dialog asks for one, because the existing update requires it.
   - **Reject** opens a dialog with a reason (5 to 1000 characters).
   - [desktop](../screens/staff/manager/approvals-desktop.png)
3. **Wallets & reconciliation:** runs the same checks as `wallets:check`, with no repair button. [desktop](../screens/staff/manager/wallets-desktop.png)
4. **Edge cases:**
   - A search with no match shows an empty state: [no match](../screens/staff/manager/receive-none-desktop.png).
   - **Reverse** is refused for the manager, because the existing destroy also requires the admin role. The page explains this.

## Admin (admin@demo.aimzone.test)

- **Inbox:** shows every card, including business submissions when the `businesses` table exists. [inbox](../screens/staff/admin/inbox-desktop.png)
- **Loan approvals:** [approvals](../screens/staff/admin/approvals-desktop.png) · [approved tab](../screens/staff/admin/approved-desktop.png)
- **Disbursement queue, empty:** [queue](../screens/staff/admin/disburse-queue-empty-desktop.png)
- **Receive payment, with Reverse:** [receive](../screens/staff/admin/receive-loan-desktop.png)
  - The confirm dialog requires a reason.
  - The repayment row is deleted through the existing destroy, which re-checks the cycle and loan status.
  - The audit log gets the delete row and a second row with `reversal_reason`.
- **Collections:** [collections](../screens/staff/admin/collections-desktop.png)
- **End of day for any staff member:** needs `view reports` and uses the staff picker. [end of day](../screens/staff/admin/end-of-day-desktop.png)
- **Roles & permissions:** a read-only matrix with people per role. [read-only](../screens/staff/admin/roles-desktop.png)
  - Only `manage roles` (super-admin) sees the "Change a permission" form: [with manage roles](../screens/staff/admin/roles-manage-desktop.png).
  - The form gives or removes one permission on one role, then runs `permission:cache-reset` and writes an audit row.
  - The super-admin role is refused.
- **Loan products:** read-only. Editing is a placeholder because open loans read the penalty rate live. [products](../screens/staff/admin/products-desktop.png)
- **Partner payouts:** read-only balances per wallet. Withdrawal requests and maker-checker are placeholders. [payouts](../screens/staff/admin/partner-payouts-desktop.png)

## Recovery officer (recovery@demo.aimzone.test)

- **Collections:** the page shows tiles for 1–7 days, 8–30 days, over 30 days, and open cases assigned to me.
  - The worklist covers overdue, defaulted and recovery loans plus every open case.
  - Each row shows days late, case number, status, officer and the last action.
  - Each row links to **Work case** (`cases.show`), or **Open case** when there is none.
  - Restructure and performance are placeholders.
  - [desktop](../screens/staff/recovery/collections-desktop.png) · [phone](../screens/staff/recovery/collections-phone.png)
- **Edge case:** "Assigned to me" is empty, because no demo case is assigned. [empty](../screens/staff/recovery/collections-mine-empty-desktop.png)

## Auditor (auditor@demo.aimzone.test)

These pages are read-only:

- [approvals](../screens/staff/auditor/approvals-readonly-desktop.png)
- [disbursement queue](../screens/staff/auditor/disburse-queue-desktop.png): the auditor cannot disburse.
- [roles](../screens/staff/auditor/roles-readonly-desktop.png)
- [end of day (empty)](../screens/staff/auditor/end-of-day-empty-desktop.png)
- Wallets
- Products
- Payouts

Receive payment returns 403.

## Proven on the clone (`loans_wp_s2`, 19 Oct 2026)

Each action below was submitted with the exact fields the screen renders, and the AJAX headers the screen sends:

- **Approve:** manager approved loan 218 through `PUT /loans/218`, and it is now `approved`. A teller sees no Approve button.
- **Reject:** manager rejected loan 304 through `PATCH /loans/304/reject`. The reason was appended to `recovery_notes`. A 2-character reason returned 422 and the message was shown.
- **Disburse:** teller disbursed loan 218 through `POST /disbursements`.
  - This created disbursement 642 (house investment 1, `funding_source` internal), and the loan became `disbursed`.
  - A missing reference returned 422.
- **Repayment:** teller recorded repayment 821 (KES 1,000) on cycle 203 through `POST /repayments`.
- **Reversal:** repayment 822 was refused for the manager. Admin then reversed it, and audit rows 366 and 367 hold the reason.
- **Roles:** teller was given `view reports`, then it was removed, with two audit rows. The same change on super-admin was refused.

## Notes

- **"Entered by"** on the end-of-day page comes from the audit trail's `create` rows. Disbursements and repayments have no `created_by` column, so entries made before the audit trail existed do not show.
- **True reversal entries are Stage 4.** These are a negative posting that keeps the original row. Today the reversal is the existing soft delete plus the reason in the audit log.
- **Reject button:** the reject route needs only `edit loans`, which tellers, brokers and recovery officers also hold. The screen shows Reject only to holders of both `edit loans` and `approve loans`. Tightening the route itself is an owner decision.
