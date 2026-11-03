# Amazonblue Capital: loan system

Short-term lending for a Kenyan non-bank lender (Amazonblue Capital / Aimzone Ventures, KES), live at
**loans.sharet.africa**. One Laravel app serves the public website, the borrower and partner accounts, and the staff back
office.

- **Borrowers** register, pass KYC, borrow up to a scored limit, repay by M-Pesa, and ask for top-ups, extensions and
  payment plans. A borrower can add a business (KYB) and borrow through it.
- **Partners** fund lines, see the loans they funded and their earnings, add funds and withdraw.
- **Staff** (teller, loan officer, recovery, accountant, auditor, manager, admin, super-admin, broker) run origination,
  disbursements, repayments, rollovers, recovery, approvals, reports and settings.

## Start here

| Read | For |
|---|---|
| [docs/CURRENT_STATE.md](docs/CURRENT_STATE.md) | What the system does today: modules, money rules, roles, switches, open items. **Read first.** |
| [docs/DEPLOY.md](docs/DEPLOY.md) | How to deploy, server layout, rules for live |
| [docs/stage4/SWITCH-ON-PLAN.md](docs/stage4/SWITCH-ON-PLAN.md) | Features that are built but off, and how to switch each one on |
| [docs/stage4/](docs/stage4/) | One runbook per deployed change (what changed, rehearsal, rollback) |
| [docs/stage2/](docs/stage2/) | UI components, design tokens, demo data, journey walkthroughs |
| [docs/archive/](docs/archive/) | Earlier audits, reviews and plans (17–21 Oct 2026). History only, not current |

## Stack

Laravel 12 · PHP 8.3 (the lock file is pinned to 8.3.14, as on the server) · MariaDB 10.3 · Blade + Alpine + Tailwind
(Vite) · spatie/laravel-permission · bavix/laravel-wallet · TCPDF for agreements · M-Pesa Daraja (C2B, STK, B2C).

## Local setup

```bash
composer install
npm install && npm run build        # public/build is committed: rebuild before pushing
cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # roles, lookups, house accounts; demo users outside production
php artisan serve
```

Most features sit behind `.env` switches (`LENDING_*`, `MPESA_*`, `NOTIFICATIONS_*`); see
[CURRENT_STATE.md › Switches](docs/CURRENT_STATE.md#5-switches). For current figures, copy live into the local database
with `scripts/pull-live-db.sh` (it replaces the local database; see DEPLOY.md).

## Tests

```bash
php artisan test
```

Tests run against a MySQL database named `loans_testing` (`phpunit.xml`); `tests/TestCase.php` refuses any other name.
Create that database first. `scripts/push-live.sh` runs the suite before every deploy.

## Rules that matter

- The live database is the source of truth. Data fixes happen there, never on a local copy pushed up.
- Every schema change ships twice: a migration and a `database/sql/*.sql` twin, which is run on live **before** the
  deploy. The deploy script refuses to run while a migration is pending.
- Money repairs are commands that dry-run first and apply with `--apply --confirm=<sha>`.
- Secrets live only in the server `.env` (or the encrypted Integrations page). Never in commits, chats or docs.
