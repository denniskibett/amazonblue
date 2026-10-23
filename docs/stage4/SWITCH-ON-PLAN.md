# Stage 4: switching on what is built but off (23 October 2026)

The code for everything below is live. Each item is a `.env` switch, so turning it on (or back off) needs no deploy.
Do **one item per day**. Watch the named screen for a day before you start the next one.

According to the runbooks, these are already **on** live: the ledger (Phase 1), `LENDING_ENGINE=v2` (Phase 2, 21 Oct 01:33) and
`LENDING_SPLIT_ENABLED` (Phase 3, 21 Oct 02:16). Step 0 confirms that.

All commands run on the server in a fresh shell:

```bash
cd /home/loans.sharet.africa/laravel && /bin/pwd     # must print /home/loans.sharet.africa/laravel
```

After every `.env` edit, run `php artisan config:cache`. To roll any item back, set its switch to `false` and run
`config:cache` again. Rows already stored are kept, and the screens go back to how they worked before.

## 0. Read where things stand (nothing changes)

```bash
grep -E '^(LENDING_ENGINE|LENDING_LEDGER_ENABLED|LENDING_REVERSALS_ENABLED|LENDING_SPLIT_ENABLED|LENDING_APPROVALS_ENABLED|LENDING_REQUESTS_ENABLED|NOTIFICATIONS_ENABLED|SMS_DRIVER|MPESA_ENV|MPESA_ENABLED|MPESA_CLAIMS_ENABLED|MPESA_STK_ENABLED)=' .env
crontab -l -u loans4537                 # must contain the schedule:run line (DEPLOY.md, open item 1)
php artisan schedule:list               # statuses 01:00, NPL 01:30, integrity 02:00, digest 08:00, reminders 09:00, queue every minute
php artisan wallets:check               # trial balance 0.0000; note the receivable count as the baseline
```

That grep prints switch names and plain values only; it never prints keys or passwords. Send the output back before
step 1. **If the cron line is missing, add it first.** Without it, the nightly jobs and the queue don't run, so every
notification in step 3 would sit unsent.

## Before you start

| Need | Why | Check |
|---|---|---|
| SPF + DKIM for `loans.sharet.africa` (DEPLOY.md, open item 2) | Notifications and verify-email links land in spam without them | Send yourself a password reset; Gmail › Show original › SPF and DKIM say PASS |
| Two people holding each sign-off permission | With maker-checker on, an item nobody else can approve waits forever | The tinker line in step 4 |
| A backup | Any rollback that goes beyond the switch | `mysqldump --single-transaction --routines --triggers loans_main \| gzip > ~/backups/pre_switch_$(date +%Y%m%d_%H%M%S).sql.gz` |

## 1. Reversals keep the original: `LENDING_REVERSALS_ENABLED=true`

- **What changes:** "Reverse" on a payment entered **before today** keeps the original row and adds a minus row on
  the same date. A same-day mistake is still deleted.
- **First:** tell the tellers. The payment list will show both rows.
- **Watch:** Receive payment › Recent payments, and End of day (a reversal shows as Reversed).
- **Skip this step** if step 0 already shows `true`.

## 2. Stored requests: `LENDING_REQUESTS_ENABLED=true`

- **What changes:**
  - Borrower extension and payment-plan requests, and partner funds-sent and withdrawal requests, are saved and
    decided at **Requests** (`/staff/requests`).
  - The person who asked sees the status on their account.
  - Emails still go out as before.
- **Watch:** `/staff/requests` for a day.
  - "Approve and extend…" books the rollover exactly as the loan page previews it.
  - "Decline" needs a note.

## 3. Notifications: `NOTIFICATIONS_ENABLED=true`

The texts are approved, money sent, payment received, due in 3 days, due today, overdue (day 1 and day 7), not
approved, request decided, partner funds confirmed, and withdrawal paid. They are service messages only, with no
marketing.

1. **Day 1:** set `NOTIFICATIONS_ENABLED=true` and leave `SMS_DRIVER=log`. Nothing reaches borrowers by SMS yet.
   Read the wording in `storage/logs/laravel-$(date +%F).log`.
2. **Day 2:** if the wording is right, set:
   ```
   SMS_DRIVER=africastalking
   AT_USERNAME=<username>
   AT_API_KEY=<key>
   AT_SENDER_ID=<approved sender ID, optional>
   ```
   - No SMS goes out between 20:00 and 08:00 Nairobi time.
   - Reminders go out at 09:00.
- **Watch:** the queue drains every minute. `php artisan queue:failed` must stay empty.

## 4. Second sign-offs (maker-checker): `LENDING_APPROVALS_ENABLED=true`

- **First:** check that at least two active staff hold each permission:
  ```bash
  php artisan tinker --execute='foreach (["approve loans","write off loan","delete repayments","edit partners"] as $p) echo $p, ": ", App\Models\User::permission($p)->whereNull("deleted_at")->count(), PHP_EOL;'
  ```
  Every line must say 2 or more.
- **What changes:**
  - Loans above **KES 50,000** (the `approval_limits` row for super-admin, admin and manager) need a second approver.
  - Write-offs, reversals of payments from before today, and partner payouts always need one.
  - Nobody can sign off their own request.
  - A digest goes to approvers at 08:00.
- **Watch:** Second sign-offs (`/staff/sign-offs`) and the bell.

## 5. M-Pesa: when you are ready to test payments

Follow [DEPLOY-PHASES-4-7.md](DEPLOY-PHASES-4-7.md) › "Daraja: sandbox first, then production", one switch per day:

1. `MPESA_ENABLED`
2. `MPESA_CLAIMS_ENABLED`
3. `MPESA_STK_ENABLED`

Weekly, upload the statement CSV on M-Pesa payments.

## Not a switch: still open from the runbooks

- **Legacy ROLL OVER chains:** `loans:merge-chains --apply` waits for the owner's sign-off on `merge-chains-<date>.csv`
  (Phase 2 › "Next"). The receivable differences in `wallets:check` come mostly from these chains. Features 2a and 2c
  merged the owner-listed ones on 21 Oct. Compare step 0's count with the 98 recorded then.
- **Wallets & reconciliation text:** the page still says repayments since 7 Oct are not split to partners. That has
  been out of date since Phase 3 went live on 21 Oct. Update the text once step 0 confirms `LENDING_SPLIT_ENABLED=true`.

## This round (23 October 2026): four database changes first, then deploy

`push-live` refuses to deploy while a database change is pending, so run the four SQL twins on live first. They
create two new tables, add `topup` to the request types, and add pay-schedule and behaviour columns to `borrowers`. No data changes. Each is safe to run twice.

```bash
cd /home/loans.sharet.africa/laravel
mysqldump --single-transaction --routines --triggers loans_main | gzip > ~/backups/pre_20261023_$(date +%H%M).sql.gz
git pull --ff-only origin stage-2-ui
mysql loans_main < database/sql/2026_10_23_message_batches.sql
mysql loans_main < database/sql/2026_10_23_borrower_profiles.sql
mysql loans_main < database/sql/2026_10_23_borrower_requests_topup.sql
mysql loans_main < database/sql/2026_10_23_borrowers_pay_and_behaviour.sql
```

Then deploy from the Mac with `scripts/push-live.sh`. Once it's live:

```bash
php artisan analytics:profiles      # first run of the borrower profiles (nightly at 02:15 from then on)
php artisan schedule:list           # shows analytics:profiles 02:15
php artisan borrowers:fill-gaps     # dry run: counts + a CSV in storage/app/private/reports; nothing changes
```

Send the fill-gaps CSV to the owner. After sign-off, `php artisan borrowers:fill-gaps --apply` creates the missing
`borrowers` rows (plain individual borrowers; nothing a borrower entered is touched). Income, employer and pay
schedule are never guessed: borrowers fill them in on "Complete your profile", and Messages › Complete your profile
emails the verified ones who are missing something (you check and send the batch).

What arrives:

| Piece | Where | Switch |
|---|---|---|
| Email and SMS manager: reminders, overdue warnings and final notices, sent in batches you check first | Messages (managers, admins) | none: nothing goes out until someone presses Send. Email needs `MAIL_MAILER=smtp` and SPF/DKIM; SMS needs step 3 above |
| Lending analytics: amount heatmap, weekday, time of month and repeat borrowing, borrower behaviour | Overview › Lending analytics | none (read-only) |
| Behaviour on approvals and in the person panel: on-time rate, comfortable amount, flags such as weekend lending | Loan approvals, any name | none (read-only) |
| Behaviour can lower a borrower's limit (never raise it) | the borrower's limit | `LENDING_BEHAVIOUR_LIMITS=true`, after a week of reading the analytics |
| Top-ups: a borrower asks on their loan page; staff send it from Requests with the M-Pesa code | account › loan › Top up; Requests | needs `LENDING_REQUESTS_ENABLED=true` (step 2) |
| Borrowers list: income, employer, pay schedule, what is missing, score, on-time rate, comfortable amount, suggested limit, flags | Customers › Borrowers | none (read-only) |
| "Complete your profile": borrowers add missing income, employer and pay schedule; details already on file stay read-only | account home prompt; account › details | none |
| Sysadmin "View as": staff roles, a new borrower, or a customer's account; nothing is saved | user menu › View as another role | none (super-admin only) |

Top-up rules: the loan is running and not past its due date, at least half of the current period is paid in cash,
nothing unpaid in the borrower's history, and the amount fits under their limit less what they owe. The current period
closes today; the new period lends what was owed + the top-up, at the same rate, for one product period; the top-up
is a cash disbursement on the new period, from the same funding line as the loan's first payout.

## Partner money (23 October 2026): no database change

This round needs no SQL. It uses columns that already exist: `investments.facility_type`, `maturity_date`, `partner_requests`.

| Piece | Where | Notes |
|---|---|---|
| **Brought in by** on the disburse form: sets `broker_status = 1`, `partner_id`, `broker_rate` (the partner's broker rate) and `broker_penalty_rate` (their brokers row, else 50%) | Disbursement queue › Disburse | Defaults to the loan's current broker. A partner's own loan defaults to that partner, so they get their broker share back. |
| **Funded by**: our money, a partner line with money free, or new partner money from a pasted M-Pesa SMS (code, amount, date and sender are read; a code can be used once) | Disburse form; partner page › "Record money … sent" | **Short term** closes when its loans are closed. **Long term** lends again what comes back, until its pay-back date. |
| **Line payouts**: when a line closes, its capital (less anything written off) and what it earned wait on Requests as "Line payout" rows | Requests › Partner requests | Staff send them with the M-Pesa code as with any withdrawal. A shortfall is written on the request; deciding whether AC covers it stays with you. |
| **Partner links per loan**: change the broker or the line on any loan. The preview shows how every share moves; the apply step posts only the difference | Loan page › "Partners: broker and funding" | This is the backfill for past loans. Nothing is saved without ticking "I checked the preview". |
| Nightly `partners:lines` at 02:30 closes matured lines that no payment touched | schedule | `php artisan partners:lines --dry-run` lists them |

**Backfill of partners' own loans** (dry run first, then sign-off):

```bash
php artisan partners:own-loans                 # dry run: per loan, what moves from AC to the partner; CSV in storage/app/private/reports
php artisan partners:own-loans --apply         # after the owner signs the CSV
php artisan wallets:check                      # partner wallets equal the journal; trial balance 0.0000
```

On the local copy of live, this covers ISIRO's 19 unbrokered own loans, and **KES 92,780.80** moves from AC's share to ISIRO (40% of the interest and 50% of the penalty already collected). Check whether those 19 loans really were ISIRO's own borrowing and not its clients booked under ISIRO's login.

## Bank ↔ M-Pesa references, modals and no reloads (23 October 2026): one database change

Run this on live **before** `push-live` (push-live refuses to deploy while a migration is pending):

```bash
mysql loans_main < database/sql/2026_10_23_bank_and_mpesa_refs.sql   # adds bank_ref and mpesa_ref to disbursements and repayments; no data changed
```

Once it is live:

```bash
php artisan payments:refs            # dry run: fills both references from the old ones and finds disbursements really sent bank → M-Pesa; CSV in storage/app/private/reports
php artisan payments:refs --apply    # after the owner checks the CSV
```

On the local copy of live, it finds 377 disbursements and 395 repayments with a reference it can read. **75 disbursements marked "M-Pesa" were really sent bank → M-Pesa** (their reference is the bank's Transaction Ref ID). The M-Pesa code of those 75 was never recorded. Add it on the payment when it's known.

| Piece | Where |
|---|---|
| New ways the money moved: **Bank → M-Pesa** and **M-Pesa → bank** ask for both references (bank Transaction Ref ID and M-Pesa code). Both are kept and searchable. | Disbursement and repayment modals, Disburse, Receive payment |
| Pasting a bank or M-Pesa message fills the mode, both references, the amount and the date. It warns when that money is already recorded on any loan. | Same screens |
| The same reference can't go on the same loan twice | Server check on every save |
| **No full reloads**: after a modal saves, the cards, tables and counts refresh in the background. Disburse and "Partners: broker and funding" open in a side drawer over the page. | Every staff page |
| Disbursement modal: **Who is behind this loan** (broker, our money, a partner line, or new partner money from its SMS, short or long term) | Loan page › Add disbursement |

## Modals, date pickers and a lighter app (23 October 2026, evening): no database change

- **One modal creates and edits.** This covers users (the separate edit modal is gone) and disbursements. On a disbursement, editing changes the broker and the funding line straight away while the loan has no payments. Once payments have been split, it sends you to "Partners: broker and funding", which shows the preview first.
- **Every date field is a flatpickr picker.** One date, date and time, or a range (from/to on Operations review, Disbursements, Reports and the borrower statements). Picking a date updates the form and Alpine straight away. flatpickr is in the app bundle and isn't loaded twice any more. Borrower pages fetch it only when they have a date field.
- **Every modal, drawer and dialog has the same backdrop**: a black gradient at about 50%. Modal fields and buttons are softer and use the brand colour.
- **Lighter**: the template's demo charts, world map, calendar, image pane, Dropzone, axios and the second flatpickr no longer load on every page. Debug console logging is gone. Modal event listeners no longer stack up after background refreshes.
- **Make partner**: the user page has a "Make partner" button. It opens the partner form filled in from their account and business, and can rename the login to the person behind it.

**Run `npm run build` before `push-live`.** The scripts changed (`resources/js/index.js`, `vite.config.js`).

**BUOSTI AGENCIES.** On the copy of live, user #111 is "BUOSTI AGENCY" (stephinebuluma@gmail.com, 0726330934). They are a borrower with no loans and no business record. Once deployed, open this link, check the details and press Save:

    https://loans.sharet.africa/partners?from_user=111&name=BUOSTI%20AGENCIES&person=Stephine%20Buluma

It creates the partner "BUOSTI AGENCIES" (type Business) linked to that login, gives the login the partner role, and renames it to "Stephine Buluma". Change any field before saving if needed.
