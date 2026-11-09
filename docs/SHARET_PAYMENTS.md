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

## Settings (Integrations › Sharet Pay)

| Field | Default | Meaning |
|---|---|---|
| `SHARET_ENABLED` | off | The whole rail. While previewing ("view as") it is forced off |
| `SHARET_API_KEY` | none | `sk_test_…` or `sk_live_…`, with scopes collect, payout and read. Secret: masked and never sent to the browser |
| `SHARET_WEBHOOK_SECRET` | none | Saved by **Test connection**. Secret |
| `SHARET_BASE_URL` | `https://sharet.africa/api/v1` | |
| `SHARET_REPAYMENT_RAIL` | `paybill` | Which rail borrowers see first. Both are always shown while Sharet is on |
| `SHARET_PAYOUTS_ENABLED` | off | Disbursements and partner payouts through Sharet |
| `SHARET_PAY_LINK_MINUTES` | 4320 (3 days) | How long a pay link stays open. The amount owed changes with penalties, so keep it short |

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
4. On **Integrations › Sharet Pay**, paste the key and leave the switch, `SHARET_PAYOUTS_ENABLED` and the
   rail at their defaults. Press **Test connection**. This checks the key and registers the webhook on the public
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
