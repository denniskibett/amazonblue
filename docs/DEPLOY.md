# Deploying to live (loans.sharet.africa)

## The usual way: one command from the Mac

```bash
scripts/push-live.sh            # tests, builds assets, pushes the current branch, then deploys over SSH
scripts/push-live.sh --no-ssh   # stops after the push and prints the command to run in the server terminal
```

The server side is [`scripts/deploy-live.sh`](../scripts/deploy-live.sh). It runs as root on the server. **Run the `database/sql/*.sql` twin of any new migration on live before deploying.** The script refuses to deploy while a migration is pending.

What the script does, in order:

1. Backs up the database to `~/backups/db_<time>.sql.gz` and keeps the last 10.
2. Makes a fresh clone of the branch in `laravel_next`. Copies in `.env` and `storage`.
3. Runs `composer install --no-dev`. The lock file is pinned to PHP 8.3.14 (`composer.json` › `config.platform`).
4. Checks that the new code boots and that no migrations are pending.
5. Copies `public/` into `public_html` (except `index.php`, `.htaccess` and `storage`). Sets the owner to `loans4537`. Moves the live folder to `releases/laravel_<time>` and puts the new one in its place.
6. Clears the caches and resets the permission cache.
7. Smoke-tests `/` and `/login`. If either is not 200, the previous release goes back **automatically**.

Up to step 5, a failure stops the script and the live site is untouched. It keeps 3 old releases.

**Rollback by hand:** `bash /home/loans.sharet.africa/laravel/scripts/deploy-live.sh --rollback`

## Server layout

| Path | What |
|---|---|
| `/home/loans.sharet.africa/laravel` | The app: a git clone of this repo, plus `.env`, `storage` and `vendor` |
| `/home/loans.sharet.africa/public_html` | Web root. Its own `index.php` loads `../laravel`. `build/` and `assets/` are copies of `public/`. `storage` → `laravel/storage/app/public`. |
| `/home/loans.sharet.africa/releases/` | Previous app folders (rollback) |
| `~/backups/` (root) | Database dumps |

Other details:

- **Site user:** `loans4537`.
- **PHP:** 8.3.14.
- **Database:** MariaDB, `loans_main`.
- **Mail:** Postfix on the same machine, via `MAIL_URL=smtp://127.0.0.1:25?verify_peer=0`.
- **DNS:** Olitt (`ns1.olitt.net`).
- **Scheduler:** a cron runs `schedule:run` every minute. The jobs are at 01:00 and 01:30 Nairobi time; the server clock is UTC.

## Rules

- **Never** run `git stash -u`, `git clean`, `git reset --hard` or `git checkout -f` in the live folder.
- **Never** run `loans:repair-cycles` on live.
- Keep `APP_DEBUG=false` on live.
- Secrets live only in the server `.env`. Never paste them into chats or commits.

## Live status (20 October 2026)

**Live:** `stage-2-ui` (`a2c9732`) was deployed with `scripts/push-live.sh`. Smoke test 200/200; old agreement URLs return 404.

**Confirmed on live:**

- `APP_DEBUG=false`.
- All SQL twins applied.
- Stage 1 data repairs done.
- `wallets:check` reconciles.
- SSH key login from the Mac works.

**Open (owner, then close Stage 2):**

1. **Cron:** `crontab -u loans4537` is empty, so the nightly status/NPL jobs don't run. Add `* * * * * cd /home/loans.sharet.africa/laravel && php artisan schedule:run >> /dev/null 2>&1` for `loans4537`.
2. **Email:** sent from `noreply@loans.sharet.africa`. Gmail accepts it but quarantines it (DMARC), because there is no SPF/DKIM. Add SPF, DKIM (CyberPanel › Email › DKIM Manager) and the `mail.loans` A record **at Olitt**, not CyberPanel DNS.
3. **`.env`:** remove the duplicate `LOG_STACK=single` (the first value wins) and add `LOG_DAILY_DAYS=30`.
4. **Secrets:** rotate `DB_PASSWORD`, which was pasted in chat.
5. **sysadmin:** set a strong password and confirm `getRoleNames()` = `["super-admin"]`.
6. **Browser checks:** `/system` funding card (sysadmin), Business Reviews in the staff sidebar, Repay for a borrower.
7. **Later:** Ubuntu 20.04 is past standard support (enable Ubuntu Pro or upgrade); the server has 2 GB RAM with swap half used.
