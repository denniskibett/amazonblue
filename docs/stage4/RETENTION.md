# Retention and restore policy (Stage 4 Phase 0.14, 20 October 2026)

These are the owner's defaults for decision 23 (STAGE4-PLAN §9). Change them in `config/lending.php` › `retention`.

## Audit log (`audit_logs`)

| Group | Tables | Kept for |
|---|---|---|
| Money | loans, repayments, disbursements, partners, partner_transactions, investments, debt_recovery_cases, payment_claims, system | 7 years. A row about a loan, or about its repayments or disbursements, is kept until 7 years after the loan closed (repaid, written off or rejected). |
| KYC | users, businesses | While the person or business is a customer, then 7 years after the record was deleted |
| Access | roles, permissions, model_has_roles | 2 years |
| Anything else | — | Never pruned |

`php artisan audit:prune` runs at 03:00 Nairobi time on the 1st of each month and logs to `storage/logs/audit-prune.log`. Run `php artisan audit:prune --dry-run` to see the counts first.

The audit trail started on 18–19 October 2026, so the first rows become due in October 2028 (access) and October 2033 (money).

Not covered yet:
- Agreements and KYC files on the private disk.
- Deleting a customer's personal data at the end of retention.

Both need the Phase 5 consent table.

## Backups and restore test

Every deploy makes a backup at `~/backups/db_<time>.sql.gz` (root) and keeps the last 10. See [DEPLOY.md](../DEPLOY.md).

**Restore test:** `bash /home/loans.sharet.africa/laravel/scripts/restore-test.sh`, as root.
- It restores the newest dump into a throw-away database and compares row counts with `loans_main`.
- It then drops the copy.
- It never writes to live.

Run it once now. After that, run it every quarter and after any server change. Record the date and result here.

| Date | Dump | Result | By |
|---|---|---|---|
| 2026-10-20 | local clone (`loans_wp_s2`) | Script check only: 12/12 tables restored, counts equal | Claude (local) |
| | live `~/backups/db_*.sql.gz` | **To do (owner)** | |
