# Walkthrough: partner self-service (journey C)

**Setup**

- Server: `DB_DATABASE=loans_wp_s2 php artisan serve --port=8793`.
- Screens: `BASE=http://127.0.0.1:8793 DESKTOP_H=1300 PHONE_H=2200 scripts/walkthrough.sh partner name=/path`. They are all in `docs/stage2/screens/partner/`. Each shot has a `-desktop.png` (1440 wide) and a `-phone.png` (390 wide).
- Routes, config and Stage 4 dependencies are in [C-money.md › Stage 2: partner self-service](../../audit/modules/C-money.md#stage-2-partner-self-service).

**Demo data, clone only**

`demo:seed` gives partner #9 "Lake Capital Partners" (partner@demo.aimzone.test) one KES 250,000 contribution and no linked loans. Before the "normal" shots, the following was added on `loans_wp_s2` with existing model methods:

- **Investment.** Investment #2 "Lake Capital SME Line" was created. `Investment::addPartnerFunding(9, 150000)` made funding row + contribution journal + principal wallet.
- **Milestones, update and note.** Added with `addMilestone`, `addUpdate` and `addNote`.
- **Loans.** Disbursements 638, 639 and 640 (demo loans 553 repaid, 554 recovery, 555 forbearance) got `investment_id = 2`, plus `recordFundingDisbursement` (drawn 26,000).
- **Earnings.** `PartnerLedger::post` added profit shares 1,200 (28 Sep, loan 553) and 860 (10 Oct, loan 555), and a penalty share of 300 (14 Oct, loan 554).

Edge-case logins were also made on the clone only (password `Demo12345!`):

- `nopartner@demo.aimzone.test`: partner role, no partners row.
- `zeropartner@demo.aimzone.test`: partner row with bank details, no wallets.
- `dual@demo.aimzone.test`: borrower + partner, zero balances.

`demo:seed --fresh` may remake the demo loans and drop the disbursement links.

The empty-state shots (`*-empty`) were taken **before** the data above was linked.

## Partner, normal path (partner@demo.aimzone.test)

1. **Home** `/account` (and `/account/partner`):
   - available balance KES 402,360.00, which is the sum of the Bavix wallets;
   - capital (principal wallet), interest earned and broker fees, from the journal;
   - shortcuts to investments, funded loans, earnings and statement;
   - latest entries, how you earn, your funding.

   [desktop](../screens/partner/home-desktop.png) · [phone](../screens/partner/home-phone.png)
2. **Investments** `/account/partner/investments`: one card per funding row showing committed 150,000, lent 26,000, returned 0, 3 loans and 17% lent out. It ends with a Stage 4 placeholder for online subscriptions. [desktop](../screens/partner/investments-desktop.png) · [phone](../screens/partner/investments-phone.png)
3. **Investment detail** `/account/partner/investments/2`:
   - the partner's own committed, drawn and returned amounts;
   - the loans drawn from the investment (initials + @handle);
   - the journal rows linked to it;
   - milestones (timeline), updates and notes from the JSON columns.

   [desktop](../screens/partner/investment-desktop.png) · [phone](../screens/partner/investment-phone.png)
4. **Funded loans** `/account/partner/loans`: status filter, borrower shown by initials and @handle, status badge and amount. [desktop](../screens/partner/loans-desktop.png) · [phone](../screens/partner/loans-phone.png)
5. **Funded loan detail** `/account/partner/loans/555`:
   - still to repay and % repaid (LoanProgress);
   - total and repaid;
   - about the loan (no ID, phone or name);
   - repayments;
   - what the partner earned on it.

   [desktop](../screens/partner/loan-desktop.png) · [phone](../screens/partner/loan-phone.png)
6. **Earnings** `/account/partner/earnings`: total, by type (profit share, broker fee, penalty share, interest) and by month as a split bar. There is a year filter when there is more than one year. [desktop](../screens/partner/earnings-desktop.png) · [phone](../screens/partner/earnings-phone.png)
7. **Statement** `/account/statement`:
   - wallet chips;
   - From/To dates;
   - Download CSV (`/account/statement.csv`, same filters, own rows only);
   - shared `x-az.table`. On phones, wallet and reference are hidden and the date moves under the entry.

   [desktop](../screens/partner/statement-desktop.png) · [phone](../screens/partner/statement-phone.png). Filtered (Interest, October): [desktop](../screens/partner/statement-filtered-desktop.png) · [phone](../screens/partner/statement-filtered-phone.png)
8. **Add funds** `/account/partner/add-funds`:
   - bank and M-Pesa instructions from `config('lending.partner_funding')`. These are empty here, so an info note gives the reference AZP-0009;
   - the third-party warning;
   - the "I've sent funds" form;
   - a Stage 4 placeholder.

   [desktop](../screens/partner/funds-desktop.png) · [phone](../screens/partner/funds-phone.png)
9. **Withdraw** `/account/partner/withdraw`:
   - wallet balances;
   - choose a wallet, amount (up to that wallet's balance, "Use all"), note;
   - payout method from the partner record (M-Pesa 0700 ••• 105);
   - "Request withdrawal" opens `x-az.confirm`;
   - a Stage 4 placeholder.

   [desktop](../screens/partner/withdraw-desktop.png) · [phone](../screens/partner/withdraw-phone.png)

**Checked by hand (curl) on the clone**

- **Withdrawal within balance.** KES 20,000 from Capital showed "Withdrawal request sent".
  - Audit row `partners #9 create {request: partner_withdrawal, …}`.
  - StaffAlert email logged.
  - Wallets unchanged (principal 400,000, interest 2,360).
- **Funds sent.** KES 50,000 by bank, code FT26TEST1, showed "Thanks, we have let our team know", with an audit row and an email.
- **CSV.** CSV for the interest wallet gave 3 rows with the wallet balance after each.
- **Access.**
  - Another partner's investment (`/investments/1`) or an unlinked loan (`/loans/545`) returns 404.
  - A borrower on `/account/partner` or `/account/statement` gets 403.
  - A partner on `/account/borrow` gets 403.

## Edge cases

| Case | Login | Shots |
|---|---|---|
| Withdrawal above balance: client message + disabled button. The server also refuses ("You can request up to KES 2,360.00 from Interest.", checked by curl) | partner@ (`?amount=999999`) | [desktop](../screens/partner/withdraw-over-balance-desktop.png) · [phone](../screens/partner/withdraw-over-balance-phone.png) |
| No investments / no funded loans / no earnings (before linking) | partner@ | investments [desktop](../screens/partner/investments-empty-desktop.png) · [phone](../screens/partner/investments-empty-phone.png); loans [desktop](../screens/partner/loans-empty-desktop.png) · [phone](../screens/partner/loans-empty-phone.png); earnings [desktop](../screens/partner/earnings-empty-desktop.png) · [phone](../screens/partner/earnings-empty-phone.png) |
| No partner record: "being set up" on every partner page; the statement no longer 404s; the CSV redirects | nopartner@ | home [desktop](../screens/partner/edge-no-partner-home-desktop.png) · [phone](../screens/partner/edge-no-partner-home-phone.png); statement [desktop](../screens/partner/edge-no-partner-statement-desktop.png) · [phone](../screens/partner/edge-no-partner-statement-phone.png) |
| Zero balance: home at KES 0.00; withdraw shows "Nothing to withdraw yet" | zeropartner@ | home [desktop](../screens/partner/edge-zero-balance-home-desktop.png) · [phone](../screens/partner/edge-zero-balance-home-phone.png); withdraw [desktop](../screens/partner/edge-zero-balance-withdraw-desktop.png) · [phone](../screens/partner/edge-zero-balance-withdraw-phone.png) |
| Borrower + partner: Borrowing / Partner switcher (sidebar and phone top bar); `/account` = borrower home, `/account/partner` = partner home with partner tabs | dual@ | borrower [desktop](../screens/partner/dual-borrower-home-desktop.png) · [phone](../screens/partner/dual-borrower-home-phone.png); partner [desktop](../screens/partner/dual-partner-home-desktop.png) · [phone](../screens/partner/dual-partner-home-phone.png) |
| No payout details on file: warning instead of the form; the server also refuses | (no demo login) | not shot |
