# Stage 4 Phase 0: what changed and how to deploy it (20 October 2026)

Branch `stage-2-ui`. There is one commit per item (0.1–0.14). There are no schema changes and no new migrations. Two data-only SQL scripts change role permissions.

## Before deploying (on the server, as root)

1. Back up: `mysqldump --single-transaction --routines --triggers loans_main | gzip > ~/backups/pre_phase0.sql.gz`
2. Run the SQL scripts. Both are safe to run twice.
   - `mysql loans_main < database/sql/2026_10_20_broker_kyc_access.sql`: the broker loses `view users`, and `view kyc documents` is added.
   - `mysql loans_main < database/sql/2026_10_20_system_settings_gate.sql`: super-admin holds `access system settings`. The last query must list **sysadmin**. If it lists nobody, `/system` is closed until `php artisan users:super-admin` runs.
3. Deploy with `scripts/push-live.sh`. The deploy resets the permission cache.

## After deploying

- Run `php artisan loans:check-integrity --csv`, and send the output and CSV to the owner. From then on it runs nightly at 02:00 and emails admins when it finds anything.
- Run `bash scripts/restore-test.sh` once, and record the result in [RETENTION.md](RETENTION.md).
- Run `php artisan schedule:list`. It should show four jobs: statuses 01:00, NPL 01:30, integrity 02:00, audit prune (monthly).
- These still need the **cron for `loans4537`** ([DEPLOY.md](../DEPLOY.md), open item 1).

## Watch for

- **Email verification.** New and re-verifying customers must confirm their email before they submit KYC. Gmail quarantines mail from the site until SPF and DKIM are set ([DEPLOY.md](../DEPLOY.md), open item 2), and the verify link goes by email. Fix DNS first, or expect "I didn't get the email" calls. Staff are not affected.
- **`/system`** is now super-admin only. Admins get 403.
- **Brokers** can no longer open borrower or business documents, or the business-review list.
- **Written-off borrowers** can't apply for a loan.

## Rollback

- Redeploy the previous release (`deploy-live.sh --rollback`).
- To give brokers their old access back, re-grant `view users` to broker.
