# Claude (Anthropic) and the platform test buttons

Added 20 November 2026. Ported from scharge (`Assistant\Claude`, `Kyc\DocumentReader`).

## The key

- **Integrations › Claude (Anthropic)** is a sysadmin-only card (`super-admin`). The key is saved encrypted with `APP_KEY` in `integrations.settings`. While the card is on, it wins over `.env` (`Support\Integrations::apply`).
- `.env` fallback: `ANTHROPIC_ENABLED`, `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL` (default `claude-sonnet-5-5`), `CLAUDE_READ_DOCUMENTS`.
- Claude is off until a key is saved and the card is switched on. Saving a new key clears the last test.
- **Test connection** makes one small real call with the key and model in force, and keeps the result on the card.
- Code: `app/Services/Claude/Claude.php`. It uses the official PHP SDK (`anthropic-ai/sdk`), sent through Laravel's HTTP client (`HttpTransporter`), so tests fake it with `Http::fake()`. Every call asks for JSON that matches a schema, and declined requests use the server-side fallback.

## Read with Claude (business KYC)

- On a business review, each certificate, CR12, KRA PIN certificate, business permit and director ID front (PDF or photo) has a **Read with Claude** button (`edit users`).
- A document goes to Anthropic only when someone presses the button, and only while the card is on with "Read business KYC documents" on.
- Claude copies the fields as printed (`business_documents.extracted`). The app then compares them with what the applicant typed: name, registration number, KRA PIN, CR12 directors against the directors listed, and a director's ID name and number. Mismatches show in red.
- Claude can misread. The result helps the reviewer and never approves anything.
- Live: run `database/sql/2026_11_20_claude_document_reading.sql` before deploying.

## Platform SMS test

- **Integrations › Sharet Ent SMS (platform) › Details › Send test SMS** sends one SMS as the platform sender (SHARETENT) to the owner's phone, `0717492048` (`IntegrationTestController::TEST_PHONE`), and to no one else.

## Not ported from scharge, and why

| scharge integration | Why not here |
|---|---|
| Airtel Money | AimZone collects through its paybill and Sharet Pay. No Airtel account or keys yet. |
| WhatsApp Cloud API | Needs a Meta business app and approved templates. AimZone shares wa.me links today. |
| Equity Jenga, bank statement import | AimZone banks with I&M (no public API). Matching statement lines to loan cycles needs its own design. |
| eTIMS | Loan interest is VAT-exempt and AimZone issues no eTIMS invoices today. |
| QuickBooks, Xero, Zoho Books | AimZone keeps its own ledger. An accounting sync is a separate project. |
| Outgoing webhooks | No partner system consumes AimZone events yet. |
| Property-only cards (rent automation, smart meters, channel sync, company email) | Property management features. |
