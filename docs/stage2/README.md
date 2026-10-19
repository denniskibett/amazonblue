# Stage 2 foundation

## Components

Use `x-az.*` on customer pages (website CSS) and `x-staff.*` on staff pages (TailAdmin/Tailwind). Both sides take the same props. Each file has a usage comment at the top.

| Component | Props |
|---|---|
| status-badge | `status`, `type` = loan\|payment, `label` (override) |
| money | `amount`, `currency` = KES (false hides it), `sign` (+/−), `tone` = in\|out\|muted\|auto |
| timeline | `items`: the LoanProgress shape `[label, detail, date, done, current, tone=red]` |
| table | `headers` (`'Label'` or `['Label','num']`), `caption`, or `<x-slot:head>`. The default slot holds the `<tr>` rows; use `<td class="num">` for amounts. It scrolls inside its box on phones. |
| empty | `title`, `message`; the slot holds actions |
| alert | `type` = info\|ok\|warn\|err (success/warning/error also accepted), `title`; the slot is the message |
| confirm | `id`, `action`, `method`, `title`, `trigger`, `confirm`, `cancel`, `tone`=danger, `trigger-class`; the slot is the message, and `<x-slot:fields>` holds extra inputs. It is a `<dialog>` with a CSRF form. |
| field | `name`, `label`, `type` (text…\|select\|textarea), `value`, `options`, `placeholder`, `required`, `hint`, `error` (defaults to the error bag), `id` |
| placeholder | `dependency`, `title`, `stage` = 4. It shows "Coming in Stage 4: <dependency>". |

**Galleries:** these exist only when `APP_ENV=local` and need a login. `/_components` shows the customer components and `/staff/_components` shows the staff ones (staff login required).

**Staff layout tip:** in a CSS grid, use `grid-cols-1` / `min-w-0` so a wide table scrolls inside its box instead of stretching the page.

Tokens are documented in [DESIGN-TOKENS.md](DESIGN-TOKENS.md).

## Demo data

```
DB_DATABASE=loans_wp_s2 php artisan demo:seed          # idempotent
DB_DATABASE=loans_wp_s2 php artisan demo:seed --fresh  # remake the demo borrowers' loans
```

The command refuses to run on production or on `loans_main`. Every login uses the password `Demo12345!`.

| Login | Role | What it shows |
|---|---|---|
| active@demo.aimzone.test | borrower | verified; disbursed loan with a cycle and 2 repayments |
| overdue@demo.aimzone.test | borrower | verified; overdue loan |
| rejected@demo.aimzone.test | borrower | verified; rejected application |
| new@demo.aimzone.test | borrower | KYC not started |
| partner@demo.aimzone.test | partner | Partner row with a KES 250,000.00 contribution (PartnerLedger) |
| broker@demo.aimzone.test | broker | |
| admin@, teller@, manager@, recovery@, auditor@demo.aimzone.test | staff | admin, teller, manager, recovery-officer, auditor |

How the data is made:

- Loans are made the way staff make them: `Loan::create` followed by `LoanCalculator::createInitialCycle`.
- Repayments follow `RepaymentController::store`.
- The disbursed status is set directly, so no wallet disbursement runs.

## Walkthrough screenshots

```
DB_DATABASE=loans_wp_s2 php artisan serve --port=8790     # in another terminal
scripts/walkthrough.sh <who> <name>=<path> [...]
scripts/walkthrough.sh active home=/account loan=/account/loans/545
scripts/walkthrough.sh admin components=/staff/_components
```

**`<who>`:** a demo key (`active`, `overdue`, `rejected`, `new`, `partner`, `broker`, `admin`, `teller`, `manager`, `recovery`, `auditor`; `borrower` is the same as `active`) or any email address.

**Output:** for each page, `docs/stage2/screens/<who>/` gets `<name>.html`, `<name>-desktop.png` (1440x900) and `<name>-phone.png` (390x844).

**Options:** for taller shots, set `DESKTOP_H=2000 PHONE_H=3800`. To write somewhere else, set `OUT=/some/dir`. To use another server, set `BASE=`.

**How it works:**

1. curl logs in through `/login` with the CSRF token.
2. Each page is saved to `public/_wt/` with a `<base href>` (the folder is gitignored and is deleted afterwards).
3. Headless Chrome takes the screenshots.

Headless Chrome won't open a window narrower than about 500px. So the phone shot renders the page in a 390px iframe and crops it with `sips`.
