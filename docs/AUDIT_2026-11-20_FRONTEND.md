# Frontend audit, 20–21 Nov 2026 (branch `staging`)

## How it was done

- **Page crawl:** every GET page was requested as each role, locally, against a copy seeded with the demo data
  (`demo:seed`). The roles: super-admin, admin, manager, teller, loan officer, recovery officer, auditor,
  accountant, investment manager, borrower, partner and collector.
  - 2,020 page loads were clean.
  - 21 server errors and 11 raw-template flags were checked by hand. The raw-template flags on `/cases` and the loan
    page are false: the text is a developer comment inside inline JavaScript.
  - Nothing was crawled on live, because the server has only 2 GB of RAM.
- **Form audit:** every data-entry form was read against the controller and model that save it. Each finding was
  checked on both sides before it was fixed.
- **Public site:** all 19 public pages return 200, none of the 37 internal links or assets is broken, and the
  browser console shows no errors.
- **Data:** read-only queries on the local copy of live (last pulled 16 Nov). There are no orphaned loans,
  repayments, disbursements, cycles or borrowers.

## Bugs found and fixed

| # | What the user saw | Fix |
|---|---|---|
| 1 | Borrower statement of account (`/account/statement-of-account`): 500 | `Payment@if`, with a directive glued to a word, is not compiled while its `@endif` is. Spaced out. |
| 2 | Partner home: 500 for a partner login not yet linked to a partner record | The view defaults its chart data. |
| 3 | Wallets page showed the raw text `journal@if (…)` | Same glued-directive bug, written as one expression |
| 4 | `/loans/{id}/rollover-statement`: 500 (the view never existed) | Opens the full loan statement, which has every cycle |
| 5 | `/users/{id}/loans/{id}/edit`: 500 (the user id was passed as the loan) | Redirects to the loan's edit page |
| 6 | Editing an investment from the list wiped its maturity date, exit date, risk rating and similar fields | Only filled fields are saved |
| 7 | Fixed-return partner lines could not be edited (422), or were turned into another type | `fixed_return` accepted and offered |
| 8 | Each investment save added the whole notes history again as a new note | The notes box starts empty |
| 9 | Choosing "Recovered" on the case edit form marked the loan repaid while money was still owed | Refused there; "Mark as recovered" checks the balance |
| 10 | Editing a partner, manager or other staff member silently turned them into a borrower | Role options come from the real roles, with the current one selected |
| 11 | The old repayment edit page cleared the payment mode and references; the loan page's Edit link opened the wrong repayment | Fields not sent are kept; the link uses the repayment id |
| 12 | Rejecting a KYC deleted the AML screening, so the resubmitted KYC could not be approved | The AML result is kept |
| 13 | The "stop broker fee" tick was lost after Preview, so the fee kept being charged | The tick is remembered |
| 14 | KYC "not your own number" never matched, and kin and guarantor numbers were stored raw | Normalised to +2547… before comparing |
| 15 | `users/create` and `users/edit` could never save (client type sent 0/1) | Options are individual and non_individual |
| 16 | `loans/edit` could never save (no `reason`) and would have cleared the signed consent | Reason field added; a missing consent leaves it unchanged |
| 17 | `disbursements/edit` could never save (no reference field) | Field added, prefilled |
| 18 | Profile photo uploaded but never shown | Linked to the user |
| 19 | System settings: company, security, notifications, integrations and backup were never saved, and the location was stored double-encoded | Saved where the page reads them; location stored once |
| 20 | Business review: Reject blocked by the browser until a limit was typed | The Reject button skips browser validation; the server still needs the reason |
| 21 | Business and director phones: "0712 345 678", the form's own example, was refused | Normalised before validation |
| 22 | A borrower's "other" income type was blanked by the staff edit modal | The saved type stays an option |
| 23 | The add-partner introducer (broker) rate was never saved | Saved (default 40%) |
| 24 | Public asset finance and trade pages: "Apply now" opened individual sign-up | Opens the business finance application |
| 25 | `partials/chart/portfolio-performance.blade.php` did not compile (it was not used anywhere) | Removed |

**Regression tests:** `tests/Feature/AuditFixesTest.php`. One test compiles every Blade view and checks it parses,
which catches the whole glued-directive class of bug.

## Data clean-up: `php artisan data:repair-phones`

Dry run by default; `--apply` writes. Before writing, it saves every old value to
`storage/app/private/repairs/phones-<time>.json`.

- **Changes only what is certain:** a Kenyan mobile becomes +2547…, the app's one stored format.
- **On the local copy of live:**
  - 38 would change: 25 `users.kin_phone`, 9 `contacts.phone`, 3 `partners.phone`, 1 `employments.employer_phone`.
  - 3 are listed for a person (below).

## Listed for a person (not changed)

- **Users #1 and #3** share a phone number with another account: duplicate accounts. Decide which to keep.
- **User #93's phone is 8 digits** and not a readable number. Ask them.
- **Profile page fields:** name, phone and ID are shown but are not editable there by design; they change through
  KYC. Decide whether to hide them or make them read-only labels.
- **Settings not enforced:** two-factor, login attempts, session timeout, password expiry, the notification
  switches and auto-backup are now saved, but nothing in the app applies them yet. Decide whether to build them or
  remove them from the page.
- **`/staff/sharet`** needs the Sharet Pay tables. They ship only as `database/sql/2026_11_09_sharet_pay.sql`, with no
  migration. Confirm it has been run on live.

## Going live (only on the owner's own word)

The server has 2 GB of RAM, so run one heavy job at a time.

1. Check `free -m` shows at least 500 MB available. Back up the database.
2. Merge `staging` into `main` and deploy from `main` with `scripts/deploy-live.sh`. It backs up, checks out cleanly,
   smoke-tests and rolls back on failure. Then clear the caches and check the main pages return 200 and that the
   Laravel log has nothing new.
3. Run the phone clean-up:
   - Back up the tables it touches:
     `mysqldump loans_main users contacts partners businesses business_members employments enquiries | gzip > /root/amazonblue-backups/phones-$(date +%F).sql.gz`
   - Dry run: `flock /root/heavy-job.lock php -d memory_limit=512M artisan data:repair-phones`
   - Read the list, then run the same command with `--apply`.
4. Return to `staging`.
