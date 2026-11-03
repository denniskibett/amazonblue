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

## The live database is the source of truth

The CyberPanel database on the server (`loans_main`) is the only real data. Every correction is made **there**: a `database/sql/*.sql` twin, an artisan command run on the server, or the app itself. Never fix data on the local copy and never push local data up.

The local database is a throwaway copy. Refresh it from live before any work that needs current figures (dry runs, rehearsals, investigations):

```bash
scripts/pull-live-db.sh
```

It backs up the local database, takes one read-only `mysqldump --single-transaction` from the server, and replaces the local database with it. Copies are kept in `storage/app/private/db-pulls/`, which git ignores.

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
- `loans:repair-cycles` was removed (21 Oct); its successor `loans:fix-cycles` refuses to run under engine v2.
- Use `scripts/deploy-live.sh` only. The older `deploy.sh` at the repo root runs `migrate --force` and should not be used.
- Keep `APP_DEBUG=false` on live.
- Secrets live only in the server `.env`. Never paste them into chats or commits.

## Live status

Current switches, modules and open server items: [CURRENT_STATE.md](CURRENT_STATE.md) (§5 and §10). Add a line to the
deploy log below after every deploy.

| Date | Commit | What | Runbook |
|---|---|---|---|
| 2026-10-21 | `32b7c7f` | Stage 4 Phases 4–7 (deployed off) | [stage4/DEPLOY-PHASES-4-7.md](stage4/DEPLOY-PHASES-4-7.md) |
| 2026-10-21 | `4136aee` | Phase 3 split; switched on 02:16 | [stage4/DEPLOY-PHASE3.md](stage4/DEPLOY-PHASE3.md) |
| 2026-10-21 | | Phase 2 engine v2; switched on 01:33 | [stage4/DEPLOY-PHASE2.md](stage4/DEPLOY-PHASE2.md) |
| 2026-10-20 | `f7a5ef4` | Stage 4 Phase 0 | [stage4/PHASE0.md](stage4/PHASE0.md) |

Deploys from 23 October to 3 November are described in [stage4/SWITCH-ON-PLAN.md](stage4/SWITCH-ON-PLAN.md) (23–25 Oct)
and the git log (1–3 Nov), but their commits were not logged here. Fill them in from the server
(`git -C /home/loans.sharet.africa/laravel log -1 --oneline`).

The status recorded on 20 October (first integrity findings, restore test, the first open items) is kept in
[archive/DEPLOY-live-status-2026-10-20.md](archive/DEPLOY-live-status-2026-10-20.md). The cron for `loans4537` was present
at the 23 October server read.
