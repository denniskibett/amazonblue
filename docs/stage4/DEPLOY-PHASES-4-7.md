# Stage 4 Phases 4–7: deployed state and switch-on runbook

Deployed 2026-10-21 (commit `32b7c7f`). Backup taken first: `~/backups/pre_phases4-7_20261021_0304.sql.gz`
(plus the deploy script's own `~/backups/db_20261021_020738.sql.gz`).

## What is live now

| Piece | State |
|---|---|
| Tables `mpesa_transactions`, `payment_claims`, `approvals`, `approval_limits`, `borrower_requests`, `partner_requests`, `teller_day_closes`, `loan_type_versions`, `notifications` | created (SQL twins in `database/sql/2026_10_21_*.sql`) |
| `approval_limits` | approve_loan 50,000 for super-admin, admin, manager |
| 7 unused report views | dropped |
| Portfolio (`/staff/portfolio`), dashboard cards, reports on v2 | **on** (read-only, no switch) |
| Product versions, close-the-day, investment `return_type` on the form | **on** (no switch) |
| Scheduler: `approvals:digest` 08:00, `notifications:reminders` 09:00, `queue:work` every minute | running (they do nothing while their switch is off) |
| `LENDING_APPROVALS_ENABLED` (maker-checker) | off |
| `LENDING_REQUESTS_ENABLED` (stored borrower/partner requests) | off |
| `NOTIFICATIONS_ENABLED` (`SMS_DRIVER=log`) | off |
| `MPESA_ENABLED`, `MPESA_STK_ENABLED`, `MPESA_CLAIMS_ENABLED` | off |
| `MPESA_ENV` | `sandbox`, shortcode 174379, Safaricom's public sandbox passkey |
| `MPESA_CONSUMER_KEY` / `MPESA_CONSUMER_SECRET` | **empty**: the keys in the local `.env` are rejected by the sandbox (HTTP 400) |
| `MPESA_CALLBACK_SECRET` | generated (32 characters), only in the live `.env` |

Callback URLs: `https://loans.sharet.africa/mpesa/<MPESA_CALLBACK_SECRET>/c2b/validation`, `.../c2b/confirmation`,
`.../stk`. A wrong secret answers 404.

All `.env` edits below are in `/home/loans.sharet.africa/laravel/.env`. After every edit:

```bash
cd /home/loans.sharet.africa/laravel && /bin/pwd && php artisan config:cache
```

(`/bin/pwd` must print `/home/loans.sharet.africa/laravel`; if a shell was open across a deploy, `cd` again.)

## Daraja: sandbox first, then production

1. **Sandbox check.** From the Daraja portal (developer.safaricom.co.ke → My Apps), copy a sandbox app's Consumer
   Key and Secret into `MPESA_CONSUMER_KEY` / `MPESA_CONSUMER_SECRET`. Then run:
   ```bash
   php artisan tinker --execute='echo strlen(app(App\Services\Mpesa\Daraja::class)->token());'
   php artisan mpesa:register-urls
   ```
   A number from the first command means the keys work. Leave `MPESA_ENABLED` off on sandbox: simulated payments
   would otherwise be recorded against real loans.
2. **Production.** When the go-live app is approved, set:
   ```
   MPESA_ENV=production
   MPESA_SHORTCODE=<paybill or till>
   MPESA_PAYBILL=<paybill shown to borrowers>
   MPESA_PASSKEY=<Lipa na M-Pesa Online passkey from Safaricom>
   MPESA_CONSUMER_KEY=<production key>
   MPESA_CONSUMER_SECRET=<production secret>
   MPESA_ALLOWED_IPS=<Safaricom callback IPs, comma-separated; optional>
   ```
   Keep `MPESA_CALLBACK_SECRET` as it is. Then run `php artisan config:cache` and `php artisan mpesa:register-urls`.
   The app refuses production with the sandbox shortcode 174379.
3. **Switch on, in this order**, one per day if possible, and watch `/staff/payments`:
   - `MPESA_ENABLED=true`: paybill payments are recorded automatically. Anything that doesn't match a loan waits in
     the payments queue.
   - `MPESA_CLAIMS_ENABLED=true`: borrowers can enter an M-Pesa code they paid with.
   - `MPESA_STK_ENABLED=true`: the "Pay with M-Pesa" prompt on the borrower's account.
4. Weekly: upload the M-Pesa statement CSV on the payments page; every receipt is checked both ways.

## The other switches

1. `LENDING_REQUESTS_ENABLED=true`: borrower extension/plan and partner funding/withdrawal requests are stored and
   decided at `/staff/requests`. Nothing changes for borrowers except that they can see the status.
2. `LENDING_APPROVALS_ENABLED=true`: maker-checker turns on. This needs **at least two staff** holding each
   permission (approve loans, write off loan, delete repayments, edit partners). Otherwise items wait forever at
   `/staff/sign-offs`. Loans above 50,000 need a second approver. Write-offs, reversals of repayments from before
   today, and partner payouts always do.
3. Notifications: first run with `NOTIFICATIONS_ENABLED=true` and `SMS_DRIVER=log` for a day, and read
   `storage/logs/laravel-*.log` to check the wording. Then set:
   ```
   SMS_DRIVER=africastalking
   AT_USERNAME=<username>
   AT_API_KEY=<key>
   AT_SENDER_ID=<approved sender ID, optional>
   ```
   No SMS goes out between 20:00 and 08:00 Nairobi time.

## Rollback

- Turn any switch off by setting it to `false` and running `config:cache`. Data already stored stays.
- Code: `bash /home/loans.sharet.africa/laravel/scripts/deploy-live.sh --rollback` (back to `f0f00f1`, Phase 3).
  The new tables are harmless to the old code.
- Database: restore `~/backups/pre_phases4-7_20261021_0304.sql.gz`. This is only for a real disaster, because it
  loses everything entered since.

## Known issue found during this deploy (not caused by it)

`wallets:check` reports 98 loans whose ledger receivable differs from what the cycles say is owed (net
KES 1,625,288.72). These are unchanged since the Phase 2 backfill. The pattern: a legacy ROLL OVER carried a balance
into a **separate** loan that the chain merge did not fold in (for example 282 → 330, KES 432,000; 138 → 456,
KES 126,277.30). The old loan keeps the receivable while the new one carries it too. Trial balance is 0 and the
customer wallets reconcile. Fixing it means merging those chains or posting adjusting entries, with a dry-run CSV
and the owner's confirmation.
