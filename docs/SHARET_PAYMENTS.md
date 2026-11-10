# Sharet Pay: a second payment rail

*Added 9 November 2026 on `claude/sharet-pay`. Modelled on scharge's Sharet integration.*

The app now has two rails side by side:

1. **Own M-Pesa paybill**: C2B and STK. This is unchanged. `MpesaTransaction` becomes a `Repayment` through
   `Mpesa\PaymentRecorder`, and B2C payouts still go through second sign-offs.
2. **Sharet Pay**: borrowers pay through a Sharet pay link into our Sharet wallet. Approved disbursements and approved
   partner payouts can also be sent out through Sharet.

Everything is **off** until it is switched on in Integrations › Sharet Pay.

## Money in: repayments

1. The borrower presses **Pay with Sharet Pay** on `/account/loans/{loan}/repay`, for the amount they chose. Staff
   can also press **Send Sharet pay link** on the loan page; this texts the link through the SMS driver and shows it
   to staff.
2. `Sharet\SharetCollections::payLink` creates `POST /payment-requests` with an `Idempotency-Key` and the
   channels mpesa and sharet. An open link for the same loan and amount is reused. Each link is stored in
   `sharet_payment_requests`.
3. The borrower pays on Sharet, and the money lands in our Sharet wallet.
4. A signed `payment_request.paid` webhook (`POST /webhooks/sharet`, header `Sharet-Signature`, deduplicated on
   the event id), or the `sharet:sync` poll every 5 minutes, calls `SharetCollections::apply`. The payment is
   recorded under a row lock as **one cash repayment** by `PaymentRecorder::recordCash`. This is the same method
   the M-Pesa callback uses: same latest cycle, same Repayment events, so the allocation, split, ledger, status and
   money emails all work as they do for M-Pesa. The repayment has `mode = sharet`, and `transaction` holds
   Sharet's transaction ID (for example `SHD1CCFAF6`). Repeated webhooks and polls add nothing. If a teller has
   already typed that ID by hand, the payment is linked to that repayment instead of being recorded a second time.
5. If the loan stops taking payments before the borrower pays (repaid, written off and so on), the payment is
   **held, not recorded**. The pay link shows "Paid, not recorded" on `/staff/sharet`, and admins get a
   StaffAlarm email.

## Money out: disbursements and partner payouts (`SHARET_PAYOUTS_ENABLED`)

Only money the app has already approved is sent, and only for the amount the app approved.

- **Disbursement.** On an *approved* loan, staff with `disburse loans` use **Send through Sharet** (M-Pesa number
  and amount, from KES 10 up to the loan amount). When second sign-offs are on, a second person approves it on
  Second sign-offs (action `sharet_payout`). When they are off, the request itself is the approval, just as when
  that person records a disbursement by hand. A loan cannot have both a Sharet payout and a B2C payout.
- **Partner payout.** On Requests › Prepare payout, tick **Pay through Sharet** and enter the partner's M-Pesa
  number. The payout is sent only once the withdrawal's own approval has posted it (`partner_requests.status = done`).
  If the withdrawal is declined, or paid another way, the Sharet payout is cancelled.
- **Sending.** `payments:sweep` runs at **08:00, 12:00 and 16:00 Africa/Nairobi**. Each row is claimed under a lock
  and stamped with its slot before the call, so a second run in the same slot sends nothing. Each attempt sends
  `POST /payments` with `Idempotency-Key: aimzone-payout-{id}-{attempt}`.
  - When Sharet answers paid, or later sends `payment.paid` (webhook or poll), the payout is recorded once. A
    disbursement is recorded exactly as a B2C result records one: `mode = sharet`, loan marked disbursed, partner
    links. A partner payout gets `paid_on` and Sharet's reference in `bank_reference`. The ledger posting was
    already made when the withdrawal was approved.
  - If Sharet clearly refuses (a 4xx), nothing has moved. The payout goes back in the queue for the next slot and
    admins are alerted.
  - If there is no clear answer (5xx, timeout, 409), the payout stays "paying". `sharet:sync` asks again with the
    **same** key, so Sharet replays its first answer and never pays twice.
  - If Sharet reports `payment.failed`, the payout is marked failed. Staff can **Try again** (a new key) or
    **Cancel** it on `/staff/sharet`.

## Pieces

| Piece | Where |
|---|---|
| Schema (you apply; no migration) | `database/sql/2026_11_09_sharet_pay.sql` |
| Settings | `config/sharet.php`, Integrations › Sharet Pay (`Support\Integrations::CATALOGUE['sharet']`) |
| API client, signature check | `app/Services/Sharet/SharetClient.php` |
| Pay links, applying payments, poll | `app/Services/Sharet/SharetCollections.php` |
| Payouts, sweep | `app/Services/Sharet/SharetPayouts.php`, `php artisan payments:sweep [--dry-run] [--slot=2026-11-09T08]` |
| Poll | `php artisan sharet:sync` (every 5 minutes) |
| Webhook | `POST /webhooks/sharet` (`Sharet\WebhookController`); CSRF exempt in `bootstrap/app.php` |
| Shared recording path with M-Pesa | `Mpesa\PaymentRecorder::recordCash` |
| Staff page | `/staff/sharet` (Payments menu, shown once Sharet is on) |
| Routes | `routes/features/sharet.php` |
| Tests | `tests/Feature/Features/SharetPayTest.php` (Sharet faked, real HMAC signatures) |

## Sharet's contract, as checked end to end (10 Nov 2026, against a local Sharet sandbox)

- Amounts are strings with two decimals (`"2500.00"`), whole shillings, at least **KES 10**, for pay links and payouts.
  The repay screen and the staff pay link refuse less.
- `Idempotency-Key` is 8 to 80 characters. Sharet replays the first answer for a repeated key, so each new pay link
  gets its own key.
- Webhook events we register: `payment_request.paid`, `payment_request.expired`, `payment.paid`, `payment.failed`,
  `payment.refunded`. Sharet refuses any other name, and the whole registration fails with it.
- A paid request's `transaction_id` is its own SH reference (`null` in the sandbox, where the request id is used). A
  payment object has no `transaction_id`: its id (`pay_…`) is the reference on the disbursement or partner payout.
- The sandbox **fail** button cancels the request with no event; `sharet:sync` closes it here.
- Sharet sends `payment.*` before it answers `POST /payments` (sandbox and instant payouts). The webhook then matches
  the one payout still being sent with that reference and amount, and the answer that follows adds nothing.
- Times come back in UTC and are kept as the same instant in Nairobi time.

## Settings (Integrations › Sharet Pay)

The Sharet Pay card on `/staff/integrations` is the one place Sharet is set up (10 Nov 2026). It shows the Sharet
icon, a status line (**Not connected**, **On · tested 5 minutes ago**, **On · last test failed**, **Off · …**), a
**Connect** / **Settings** button, **Details** and the on/off switch. Settings holds:

| Field | Default | Meaning |
|---|---|---|
| API key (`SHARET_API_KEY`) | none | `sk_test_…` or `sk_live_…`, scopes collect, payout and read. Encrypted, never shown again; blank keeps the saved one. Saved on the card only, never read from `.env` |
| Webhook signing secret (`SHARET_WEBHOOK_SECRET`) | none | Optional: **Test connection** registers our webhook and fills it in. Card only |
| API base URL (`SHARET_BASE_URL`) | `https://sharet.africa/api/v1` | Optional; blank means the default |
| Repayment rail (`SHARET_REPAYMENT_RAIL`) | `paybill` | Which rail borrowers see first. Both are shown while Sharet Pay is on |
| Payouts through Sharet (`SHARET_PAYOUTS_ENABLED`) | off | Disbursements and partner payouts through Sharet |
| Pay link open for (`SHARET_PAY_LINK_MINUTES`) | 4320 (3 days) | The amount owed changes with penalties, so keep it short |

- **The switch** is the only on/off (the old "Sharet Pay on" field is gone; an "off" saved there still counts until the
  switch is used). It cannot be switched on before a key is saved. While previewing ("view as") Sharet is forced off.
- **Saving a new key** switches Sharet Pay off and drops the old webhook secret and test result: press Test connection,
  then switch it back on. A key saved before this change keeps working; nothing needs to be typed again.
- **Test connection** checks the saved key (also while switched off) and makes sure exactly one webhook of ours is
  registered. With a secret saved it registers nothing; otherwise, under a lock against double clicks, any webhook
  Sharet already has for `/webhooks/sharet` is deleted and one new one registered, its secret saved. The button is
  disabled while it runs, and the result shows on the card.
- **Remove** deletes the key, webhook secret and options and switches Sharet Pay off. The own paybill keeps working;
  open pay links and queued Sharet payouts wait until Sharet is connected again.
- The three options may also come from `.env` (same names), only as a fallback for an option never saved on the card.

Only staff with `manage integrations` or `access system settings` (admin, super-admin) can open the page or
**Test connection**. Borrowers and partners are sent back to their account.

## Decisions for the owner (conservative defaults are in place)

1. **Rail order.** Default: own paybill first, with Sharet Pay offered beside it. Change this only once Sharet
   collections are trusted.
2. **Fees.** No fee is added or deducted by this app. Whatever Sharet charges on collections and payouts is between
   us and Sharet; borrowers repay the amount they owe. Decide whether Sharet's charges come out of the house or are
   passed on. That would be a separate change.
3. **Payouts through Sharet.** Off. When on, decide whether Sharet disbursements must have second sign-offs. Today
   they follow the app's own switch: with sign-offs off, the staffer who asks approves it, just as a hand
   disbursement works. B2C, by contrast, always needs sign-offs. A stricter rule ("Sharet payouts always need
   sign-offs") is a one-line change.
4. **Payout timing.** Payouts go only at 08:00, 12:00 and 16:00, never immediately. Borrowers may wait up to about 4
   hours (overnight for anything after 16:00).
5. **Partner payouts.** These go to M-Pesa only, in whole shillings (a request with cents must be paid another way).
   Bank payouts through Sharet need Sharet's bank codes and stay manual for now.
6. **Payment for a closed loan.** It is held, not recorded. Staff decide whether to refund it or put it on another
   loan. There is no assign button yet: record it by hand with Sharet's transaction ID, and the link then reads as
   linked.
7. **Sweeping the Sharet wallet.** Money collected stays in our Sharet wallet. Moving it to the I&M account is a
   manual Sharet withdrawal for now. It also funds Sharet payouts, so keep enough there for the day's disbursements.

## Going live, in order

1. **Back up, then apply the schema:**
   `mysql <database> < database/sql/2026_11_09_sharet_pay.sql`. It is safe to re-run and changes no existing data.
2. **Deploy** this branch, then run `php artisan config:cache` and `php artisan route:cache` as usual.
3. **Create a Sharet key** in Sharet › Developers › API keys with scopes collect, payout and read. Start with
   `sk_test_`. For live payouts, add the server's IP to the key.
4. On **Integrations › Sharet Pay**, press **Connect**, paste the key and leave payouts and the rail at their
   defaults (Sharet Pay stays off). Press **Test connection**. This checks the key and registers the webhook on the public
   HTTPS address (`/webhooks/sharet`), saving its signing secret.
5. **Test with the sandbox key.** Switch Sharet Pay on, pay a test loan's link with Sharet's sandbox, and check that
   one repayment appears (mode Sharet Pay) and that `/staff/sharet` shows it paid.
6. **Swap to the live key.** Saving a new key drops the old webhook secret, so press Test connection again to
   register the live webhook, then switch Sharet Pay on.
7. **Payouts:** only after the above, switch `SHARET_PAYOUTS_ENABLED` on. Run `php artisan payments:sweep --dry-run`
   to see what would go.
8. **Cron:** the server's existing `schedule:run` every minute already runs `sharet:sync` (every 5 minutes) and
   `payments:sweep` (08:00, 12:00 and 16:00). Their output is in `storage/logs/sharet.log`.

Never point a test copy at the live key. A test copy's sweep would pay real money.
