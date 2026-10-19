#!/usr/bin/env bash
# Restore test for the deploy backups (Stage 4 Phase 0.14): restores a dump into a throw-away database,
# compares row counts with the live database, then drops the copy. Reads live, never writes to it.
#
#   bash scripts/restore-test.sh                      # newest ~/backups/db_*.sql.gz against loans_main
#   bash scripts/restore-test.sh <dump.sql.gz> [db]   # a given dump, against another database
#
# On the server run it as root (same as deploy-live.sh). Counts can differ by rows written since the dump;
# the money tables should match exactly when nothing was entered in between.
# Env: MYSQL (default "mysql -uroot"), KEEP=1 keeps the restored copy for a look.
set -euo pipefail

MYSQL="${MYSQL:-mysql -uroot}"
DUMP="${1:-$(ls -1t ~/backups/db_*.sql.gz 2>/dev/null | head -1 || true)}"
LIVE="${2:-loans_main}"
[ -n "$DUMP" ] && [ -f "$DUMP" ] || { echo "No dump found (looked for ~/backups/db_*.sql.gz)." >&2; exit 1; }

COPY="restore_test_$(date +%Y%m%d_%H%M%S)"
TABLES="users loans loan_cycles repayments disbursements partners partner_transactions investment_fundings wallets transactions audit_logs migrations"

cleanup() { [ "${KEEP:-0}" = 1 ] || $MYSQL -e "DROP DATABASE IF EXISTS \`$COPY\`" || true; }
trap cleanup EXIT

echo "Dump:  $DUMP ($(du -h "$DUMP" | cut -f1))"
gzip -t "$DUMP"
echo "Copy:  $COPY"
$MYSQL -e "CREATE DATABASE \`$COPY\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
start=$(date +%s)
gunzip -c "$DUMP" | $MYSQL "$COPY"
echo "Restored in $(( $(date +%s) - start ))s"

printf '\n%-22s %12s %12s\n' table restored live
fail=0
for t in $TABLES; do
  r=$($MYSQL -N -e "SELECT COUNT(*) FROM \`$COPY\`.\`$t\`" 2>/dev/null || echo missing)
  l=$($MYSQL -N -e "SELECT COUNT(*) FROM \`$LIVE\`.\`$t\`" 2>/dev/null || echo missing)
  mark=""; [ "$r" = "$l" ] || mark="  <- differs"
  [ "$r" = missing ] && fail=1
  printf '%-22s %12s %12s%s\n' "$t" "$r" "$l" "$mark"
done

money=$($MYSQL -N -e "SELECT CONCAT(ROUND(SUM(amount),2)) FROM \`$COPY\`.repayments WHERE deleted_at IS NULL")
echo; echo "Repayments total in the copy: $money"
[ "$fail" = 0 ] && echo "Restore test passed: every table restored." || { echo "Restore test FAILED: a table is missing from the copy." >&2; exit 1; }
