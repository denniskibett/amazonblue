# Walkthrough: borrower journeys (journey set D1)

Server: `DB_DATABASE=loans_wp_s2 php artisan serve --port=8794`. Screens: `BASE=http://127.0.0.1:8794 OUT=docs/stage2/screens/borrower scripts/walkthrough.sh <login> name=/path`. Each shot has a `-desktop.png` (1440 wide) and a `-phone.png` (390 wide). Routes, rules and Stage 4 dependencies: [A-customer.md › Stage 2: borrower journeys](../../audit/modules/A-customer.md#stage-2-borrower-journeys).

`demo:seed` covers active, overdue, rejected and new. The other states were made with `DB_DATABASE=loans_wp_s2 php scripts/borrower-states-demo.php` (DemoSeed's own helpers + the Loan model's state methods; skips logins that already have loans). It adds `pending@`, `approved@`, `inrecovery@`, `plan@` and `writtenoff@demo.aimzone.test` (password `Demo12345!`), loans 551-556 in the shots.

Every state card comes from `App\Support\BorrowerLoanState` and shows on the account home (short form) and on the loan page (full form, under the header badge).

## Under review (pending@, loan 551)

1. **Home**: the hero says "Under review"; the card below says what happens next (we may call you or your guarantor; the M-Pesa message is your signal; the due date starts when the money is sent). [desktop](../screens/borrower/pending/home-desktop.png) · [phone](../screens/borrower/pending/home-phone.png)
2. **Loan page**: "Under review" badge, the full card, timeline with "Approved" as the current step. [desktop](../screens/borrower/pending/loan-desktop.png) · [phone](../screens/borrower/pending/loan-phone.png)

## Approved, sending soon (approved@, loan 552)

[home](../screens/borrower/approved/home-desktop.png) · [home phone](../screens/borrower/approved/home-phone.png) · [loan](../screens/borrower/approved/loan-desktop.png) · [loan phone](../screens/borrower/approved/loan-phone.png)

## Rejected (rejected@, loan 547)

1. **Home**: no open loan, so the hero offers borrowing; the card says the application was not approved, with the reason staff wrote ("Income could not be confirmed with the employer.", taken from the `Rejected by … on …: <reason>` note; the staff name is not shown), what to do next, "View loan", "Apply again" and "Ask about this decision". Shown for 30 days after the decision. [desktop](../screens/borrower/rejected/home-desktop.png) · [phone](../screens/borrower/rejected/home-phone.png)
2. **Loan page**: same card with "Apply again" first. [desktop](../screens/borrower/rejected/loan-desktop.png) · [phone](../screens/borrower/rejected/loan-phone.png)
3. **Borrow in five steps** `/account/borrow`: Product → Amount → Payout & security (M-Pesa number masked, guarantor) → Offer (amount, 20% flat, total, one payment with a date tile) → Sign (consent tick + "Apply for KES …"). One form, the same POST to `LoanController@apply`; the summary and limit stay on the right. [desktop](../screens/borrower/rejected/borrow-desktop.png) · [phone](../screens/borrower/rejected/borrow-phone.png)

## Active, on time (active@, loan 545)

1. **Home**: unchanged hero (repay journey); Help in the quick actions now opens `/account/help`. [desktop](../screens/borrower/active/home-desktop.png) · [phone](../screens/borrower/active/home-phone.png)
2. **Loan page**: "Active" badge, a "Need more time to pay?" note linking to the extension screen, timeline (`x-az.timeline`), **Your loan terms** (accepted on, agreement version, SHA-256 fingerprint when frozen, "View signed agreement"), "Statement for this loan". [desktop](../screens/borrower/active/loan-desktop.png) · [phone](../screens/borrower/active/loan-phone.png)
3. **Ask for more time** `/account/loans/545/extension`: indicative cost from `LoanCalculator::getRolloverPreview` (read-only): owed today, interest for the new period, new due date, new amount, all labelled "Indicative" with a warning that pricing is being reworked; a reason box and an "I understand" tick; "Request extension". [desktop](../screens/borrower/active/extension-desktop.png) · [phone](../screens/borrower/active/extension-phone.png)
4. **Statement for this loan** `/account/loans/545/statement`: tiles (sent to you in cash, paid by you in cash, owed today), date filter, table with a Cash / Rollover / Discount / Write-off / Book entry tag on each line, "Print or save as PDF" (print hides the account chrome and adds a letterhead). [desktop](../screens/borrower/active/statement-desktop.png) · [phone](../screens/borrower/active/statement-phone.png)
5. **Notifications** `/account/notifications`: recent updates built from the borrower's own records; emails we send (on); SMS / in-app alerts as a Stage 4 placeholder. [desktop](../screens/borrower/active/notifications-desktop.png) · [phone](../screens/borrower/active/notifications-phone.png)
6. **Help** `/account/help`: WhatsApp, phone and email; a message form (topic, which loan, message); FAQ. [desktop](../screens/borrower/active/help-desktop.png) · [phone](../screens/borrower/active/help-phone.png)

## Overdue (overdue@, loan 546)

1. **Home**: red hero (repay journey) and a card "Overdue by 10 days": the late penalty rate from the loan type, the default threshold from `calculateCycleBalance` ("still unpaid 30 days after the due date … recovery team"), the limit effect, and "Ask for more time" / "Contact us about this loan". [desktop](../screens/borrower/overdue/home-desktop.png) · [phone](../screens/borrower/overdue/home-phone.png)
2. **Loan page**. [desktop](../screens/borrower/overdue/loan-desktop.png) · [phone](../screens/borrower/overdue/loan-phone.png)
3. **Extension after a request**: "You asked on 19 Oct 2026" (from the audit row). The preview's new due date counts from the original due date, so for an overdue loan it can be today; the page says the team will agree a workable date. [desktop](../screens/borrower/overdue/extension-desktop.png) · [phone](../screens/borrower/overdue/extension-phone.png)

Tested: request sent (302, "Request sent" dialog, audit row `borrower_requests` #546 `extension_request`, staff email in the mail log); empty reason gives both field errors; another borrower posting to loan 546 gets 404.

## Defaulted / in recovery (inrecovery@, loan 554)

1. **Home and loan page**: "This loan is with our recovery team", then the borrower's own case read-only: case number, case status (Negotiation), assigned officer, in default since, last contact, next step planned. No amounts from the case. "Call the recovery team" and "Contact us about this loan". [home](../screens/borrower/inrecovery/home-desktop.png) · [home phone](../screens/borrower/inrecovery/home-phone.png) · [loan](../screens/borrower/inrecovery/loan-desktop.png) · [loan phone](../screens/borrower/inrecovery/loan-phone.png)
2. **All-loans statement** `/account/statement-of-account`: two loans, loan picker, cash totals. [desktop](../screens/borrower/inrecovery/statement-desktop.png) · [phone](../screens/borrower/inrecovery/statement-phone.png)

## Payment plan (plan@, loan 555)

1. **Home and loan page**: "You're on a payment plan" with "See the plan". [home](../screens/borrower/plan/home-desktop.png) · [loan](../screens/borrower/plan/loan-desktop.png) · [loan phone](../screens/borrower/plan/loan-phone.png)
2. **Plan** `/account/loans/555/plan`: the plan from the current cycle (period, start, pay by, plan interest, amount from LoanProgress) and penalties paused until `forbearance_until`. "I accept this plan" opens an `x-az.confirm` dialog; after confirming the page shows "You accepted this plan on …". [desktop](../screens/borrower/plan/plan-desktop.png) · [phone](../screens/borrower/plan/plan-phone.png)

Tested: accept (302, "Thank you", audit row `payment_plan_accepted` with cycle id, balance and dates, staff email).

## Written off (writtenoff@, loan 556)

1. **Home**: card "This loan has been written off" (still counts on the history, talk to us about settling, limit stays at the starting limit). [desktop](../screens/borrower/writtenoff/home-desktop.png) · [phone](../screens/borrower/writtenoff/home-phone.png)
2. **Loan page**. [desktop](../screens/borrower/writtenoff/loan-desktop.png) · [phone](../screens/borrower/writtenoff/loan-phone.png)
3. **Statement**: cash lines plus a "ROLL OVER" and a "WRITE OFF" book entry shown in grey with their tags; no "owed today" tile for a written-off loan. [desktop](../screens/borrower/writtenoff/statement-desktop.png) · [phone](../screens/borrower/writtenoff/statement-phone.png)

## Not built / mocked

- Extension and plan acceptance are not stored in their own table: one `audit_logs` row each (`table_name = borrower_requests`, `record_id` = loan id, `new_values.type`) plus a staff email. Help messages use the same row (`record_id` 0 when no loan is named).
- No notifications table: "Recent updates" is derived; SMS and in-app alerts are placeholders.
- No running balance on the statement (needs a ledger of charges).
- Borrow has one product; "more products" is a placeholder. The steps are views only.
