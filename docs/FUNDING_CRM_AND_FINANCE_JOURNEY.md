# Funding CRM and the business finance journey (20 Nov 2026, branch `staging`)

Two pieces, built in this app (AimZone's live backend at loans.sharet.africa). Neither is live.

## Stack decision

AimZone already runs on this Laravel 12 app. It holds borrowers, KYB, loans, Sharet Pay and staff roles. Both pieces
are built here, not as a new app:

- **No new hosting.** The CRM and the journey deploy with the rest of the app.
- **One set of roles and permissions** for staff.
- **Repayments use the existing Sharet Pay rail.** An application can link to a loan, and the applicant then pays it
  from their application page.

The website copy in `~/projects/aimzone` is a static snapshot. The pages that are live are this app's
`resources/views/home`.

Designs: the canvas's "Later · not in the app yet" page. Trade finance is WA-E1–E5; asset finance is B-1–B-3 and
AF-12; the relationship-manager and leads boards are RM-1–3 and R-LO-Leads. A local copy is in `docs/design/aimzone-canvas`.

The staff asset-finance flow on `park/asset-finance-app` (teller-led, for riders) is separate and stays parked.

## 1. Funding CRM: `/staff/funding` (permission `manage funding prospects`)

Modelled on the scharge sales CRM.

- **Targets:** businesses that need float, working capital, asset or trade finance; never individuals.
- **Segments:** distributors, SACCOs, fuel stations, logistics, construction, agribusiness, manufacturers, large
  retailers (`config/funding_crm.php`).
- **Prospects:** each has a segment, a source (required: one of the lawful public sources listed in config), an owner, a
  stage, the next step and notes. Reference `AIM-P-00001`.
- **Pipeline stages:** New → Contacted → Interested → Applied → Approved → Funded, or Lost. A stage moves on its own
  when an email is sent, the link is clicked, an application starts, or staff decide on it.
- **Follow-ups:** a call is booked 3 working days after each cold email. "Reading now" lists who opened in the last 3
  days or clicked in the last 7.
- **Playbook:** per segment, with the approach, what to offer, objections and a starter email. None of it promises
  approval, an amount or a rate.
- **Cold email:**
  - Sent from `sales@sharet.africa` as "<manager> · Aimzone Ventures".
  - The subject carries `[AIM-E-000001]`, so replies to the shared inbox never mix with scharge (SC-) or Breve (BREVE-).
  - Opt-out is one click (RFC 8058 header) plus a footer link.
- **Campaigns:** `AIM-C-001`. Each run sends up to `FUNDING_CRM_BATCH` (40) emails. They go only to prospects that are
  still New, never emailed, have a business email and are not suppressed. Command: `funding:send-campaign`.
- **Tracking** (`App\Services\Funding\EmailEngagement`, ported from scharge):
  - Records the time, place, device, OS and mail app of each open and click.
  - Gmail and Apple privacy proxies are flagged.
  - Only a shortened IP is kept, and events are pruned after 12 months.
  - **Town** needs the offline IP database (see "What still needs you"). Until then only the country is recorded.
- **Do not email:**
  - Opt-outs, bounces and manual additions go on `funding_suppressions` (Data Protection Act 2019).
  - The list is checked before every send.
  - New prospects with a suppressed address start out opted out.
- **Import:** CSV upload or `php artisan funding:import file.csv --dry-run`. Rows without a known segment or lawful
  source are refused, and duplicates are skipped.
- **Prospect count today: 0.** No list has been loaded yet; see "What still needs you".

## 2. Business finance journey: `/finance/apply` (switch `FINANCE_JOURNEY_ENABLED`)

The applicant needs no login. Their private link is the key: only its SHA-256 is stored, and it lapses 30 days after
the last visit. "Lost your link?" emails a fresh one and the old one stops working.

| # | Screen | Asset finance | Trade finance |
|---|---|---|---|
| 0 | Choose, then start | name, email, Kenyan mobile, consent to contact; the link is emailed | same |
| 1 | Business | registered name, reg. no., KRA PIN (checked), sector, county, years, directors, turnover | same |
| 2 | What you need | asset type, make/model/year, new or used, supplier, price, deposit | supplier payment, LPO, invoice or stock; supplier and country, goods, buyer (required for LPO and invoice), KES or USD, value |
| 3 | Amount and term | up to price less deposit (at least 10%); 6–48 months; **estimated** monthly instalment on a reducing balance | up to the order value; 1–6 months; **estimated** single repayment at the end |
| 4 | Documents | KRA PIN cert, CR12, 6 months bank or M-Pesa statements, directors' IDs, supplier quote | same, with the LPO, invoice or proforma in place of the quote |
| 5 | Consent and submit | true and complete; CRB check; data processing (DPA 2019) | same |
| — | Status page | what happens now: received → review → decision → final terms and agreement → repayments through Sharet Pay | same |

- **Saving:** every step is saved on Continue or "Save and exit". The status page shows progress and "Carry on".
- **Changing the asset or deal** sends the applicant back to the amount step.
- **Documents:** PDF, JPG or PNG; at most 10 MB each, 20 per application, and a set number per type (one KRA PIN
  certificate, up to six statements, and so on). Files sit on the private disk, and only staff can open them.
- **Copy:** says "estimate" and "approved in principle" and makes no promises. A test fails if the start pages say
  "guarantee", "pre-approved" or "Cloud9".
- **Staff queue:** `/staff/finance-applications` (permission `review finance applications`).
  - Decisions: start review, ask for more (sends the applicant back to a chosen step), approve in principle, or decline
    with a reason. Each decision emails the applicant a fresh link.
  - Once a facility is booked as a loan, staff enter its loan number. The applicant's page then shows "Pay with Sharet
    Pay", using the existing `SharetCollections::payLink`.

## Tests

- `tests/Feature/FinanceJourneyTest.php`, 8 tests: saving and resume, validation, document limits, lapsed and replaced
  links, the trade estimate, the staff review round trip, and the copy check.
- `tests/Feature/FundingCrmTest.php`, 7 tests: AIM- references, sales@ sender and headers, open and click tracking, the
  opt-out and suppression, campaign eligibility and batches, the import, and permissions.

Run with `php artisan test --filter='FinanceJourneyTest|FundingCrmTest'`.

## What still needs you

1. **A lender or credit partner, and licences.**
   - Lending to businesses at this scale may need a Central Bank of Kenya non-deposit-taking credit provider licence,
     or a licensed partner to book the credit.
   - Trade finance in USD and payments to foreign suppliers go through a bank.
   - Data Protection Act registration with the ODPC, as a data controller and processor for marketing.
   - Have counsel review the consent wording, the privacy notice and the cold-email footer.
2. **Prices.** `FINANCE_JOURNEY_ASSET_RATE` (3.5%/month) and `FINANCE_JOURNEY_TRADE_RATE` (3%/month) are placeholders
   for the estimate only. Set real indicative rates or remove the estimate.
3. **The prospect list.** Load businesses from lawful public sources (company websites, directories, tender award
   notices, association lists), each with its source. Run `funding:import --dry-run` first.
4. **Mail.**
   - Live sends through Postfix on the server. Check that `sales@sharet.africa` has SPF, DKIM and DMARC for this server
     before the first campaign.
   - Replies land in the shared inbox. Sort them by `AIM-`.
5. **Town for opens and clicks.** Add `maxmind-db/reader` and the DB-IP City Lite file at
   `storage/app/geo/dbip-city-lite.mmdb`, as scharge does. Until then only the country is recorded.
6. **Sharet Pay** is still switched off on live (`SHARET_PAYOUTS_ENABLED`, Integrations › Sharet Pay).

## How to go live (only when the owner says "go live")

1. Bring `main` level with what is live. It is behind: live was deployed from `stage-2-ui`. Read the live commit with
   `git -C /home/loans.sharet.africa/laravel log -1`, then fast-forward `main` to it.
2. Merge `staging` into `main`.
3. On the server, before deploying, run the SQL twin `database/sql/2026_11_20_funding_crm_and_finance_applications.sql`,
   then `php artisan permission:cache-reset`.
4. Deploy from `main` with `scripts/push-live.sh`, which runs the tests and builds. Smoke-test `/finance/apply` and
   `/staff/funding`.
5. Return to `staging` for the next piece of work.
