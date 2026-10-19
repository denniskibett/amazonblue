# Walkthrough: borrower M-Pesa repayment (journey B)

Server: `DB_DATABASE=loans_wp_s2 php artisan serve --port=8792`. Screens: `BASE=http://127.0.0.1:8792 OUT=docs/stage2/screens/repay DESKTOP_H=1500 PHONE_H=2900 scripts/walkthrough.sh <login> name=/path`. Every shot has a `-desktop.png` (1440 wide) and a `-phone.png` (390 wide). Details of routes, config and Stage 4 dependencies: [A-customer.md › Stage 2: repayment journey](../../audit/modules/A-customer.md#stage-2-repayment-journey).

Edge-case borrowers (repaid, recovery, pending) are not part of `demo:seed`. They were made for the shots with `scripts/repay-edge-demo.php` (see its header) and removed again; loan ids 548-550 in the shots no longer exist.

## Borrower, normal path (active@demo.aimzone.test, loan 545)

1. **Home**: the hero has "Repay"; the old "How to repay / WhatsApp" card is now a "Repay by M-Pesa" card with the amount due. [desktop](../screens/repay/active/home-desktop.png) · [phone](../screens/repay/active/home-phone.png)
2. **Loan detail**: "Repay" in the hero; each payment is a link to its receipt with a "Received" badge. [desktop](../screens/repay/active/loan-desktop.png) · [phone](../screens/repay/active/loan-phone.png)
3. **Repay screen** `/account/loans/545/repay`: amount due now (KES 7,000.00 from LoanProgress), full or other amount (1 … 7,000), "Pay with M-Pesa (STK push)" disabled with the Stage 4 placeholder, Paybill card (placeholder until the owner confirms the number; account no. = loan number), "I've paid" form, payments received. [desktop](../screens/repay/active/repay-desktop.png) · [phone](../screens/repay/active/repay-phone.png)
4. **STK flow design stub** (local only, `?demo=1&state=…`): [sending](../screens/repay/active/stk-sending-phone.png) · [waiting for PIN](../screens/repay/active/stk-waiting-phone.png) · [received](../screens/repay/active/stk-received-phone.png) · [failed](../screens/repay/active/stk-failed-phone.png) · [timed out](../screens/repay/active/stk-timeout-phone.png) (desktop: same names with `-desktop`).
5. **Receipt** `/account/loans/545/receipts/811`: RCPT-000811, amount, date, method, M-Pesa code, loan; "Print or save receipt" prints only the receipt. [desktop](../screens/repay/active/receipt-desktop.png) · [phone](../screens/repay/active/receipt-phone.png)

## Borrower, overdue + "I've paid" (overdue@demo.aimzone.test, loan 546)

1. **Repay screen, overdue**: warning with due date, penalty included (KES 9,600.00 from LoanProgress) and the loan type's 10% penalty rate; red hero. [desktop](../screens/repay/overdue/repay-desktop.png) · [phone](../screens/repay/overdue/repay-phone.png)
2. **After "I've paid"** (code QKA7B8C9D0, KES 2,000): "We're checking your payment" and a "Payments you told us about" card with "Checking". Staff got the "Payment to confirm" email; an audit row was written. [desktop](../screens/repay/overdue/checking-desktop.png) · [phone](../screens/repay/overdue/checking-phone.png)
3. **Home and loan detail** show the same claim (session only). [home](../screens/repay/overdue/home-phone.png) · [loan](../screens/repay/overdue/loan-phone.png)

Checked by hand (curl): a bad code shows "An M-Pesa code has 10 letters and numbers"; an amount above the due shows "That is more than the amount due"; a code already recorded on the loan shows "We have already recorded this payment (receipt #811)"; the sixth claim within 10 minutes shows "Too many tries"; another borrower's loan or receipt → 404.

## Edge cases

| Case | Login | Shots |
|---|---|---|
| No open loan (`/account/repay`) | rejected@ | [desktop](../screens/repay/rejected/repay-none-desktop.png) · [phone](../screens/repay/rejected/repay-none-phone.png) |
| Rejected application (`/account/loans/547/repay`) | rejected@ | [desktop](../screens/repay/rejected/repay-rejected-desktop.png) · [phone](../screens/repay/rejected/repay-rejected-phone.png); home: [phone](../screens/repay/rejected/home-phone.png) |
| Pending application: cannot repay yet | repay-pending@ (temporary) | [desktop](../screens/repay/repay-pending/repay-desktop.png) · [phone](../screens/repay/repay-pending/repay-phone.png) |
| Loan in recovery: "Contact the recovery team", no STK | repay-recovery@ (temporary) | [desktop](../screens/repay/repay-recovery/repay-desktop.png) · [phone](../screens/repay/repay-recovery/repay-phone.png); home: [phone](../screens/repay/repay-recovery/home-phone.png) |
| Repaid: facility closed (`/repay` redirects here) | repay-repaid@ (temporary) | [desktop](../screens/repay/repay-repaid/closed-desktop.png) · [phone](../screens/repay/repay-repaid/closed-phone.png); loan: [phone](../screens/repay/repay-repaid/loan-phone.png) |
| Book entry (not cash, "BROKER FEE ADJ"): labelled "Book entry", not a receipt | repay-repaid@ (temporary) | [desktop](../screens/repay/repay-repaid/book-entry-desktop.png) · [phone](../screens/repay/repay-repaid/book-entry-phone.png) |

## Staff

Nothing new on screen. Each "I've paid" claim emails admins (`admin`, `super-admin`): subject "Payment to confirm: loan #00546, KES 2,000.00, code QKA7B8C9D0", body with borrower, loan id, amount, code, phone and time, button "Record the repayment" → `/users/{user}/loans/{loan}/repayments/create` (`users.loans.repayments.create`). Staff check M-Pesa and record the repayment as before; the borrower's claim then shows "Received" with the receipt. Interim until Stage 4 (payment claims table + reconciliation, staff queue).
