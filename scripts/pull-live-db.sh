#!/usr/bin/env bash
# Replace the LOCAL database with a fresh copy of LIVE (the CyberPanel database, loans_main).
#
# Live is the only source of truth. Corrections are made on live (SQL twins, artisan commands, the app),
# never on the local copy; the local copy is thrown away and pulled again whenever work needs current data.
#
#   scripts/pull-live-db.sh            backs up the local database, then replaces it with live
#   scripts/pull-live-db.sh --keep N   keep the last N local backups (default 5)
#
# Read-only on the server: one mysqldump --single-transaction (no locks on InnoDB tables).
set -euo pipefail

SERVER=root@185.113.249.146
REMOTE_DB=loans_main
KEEP=5
[[ "${1:-}" == "--keep" ]] && KEEP="${2:-5}"

cd "$(dirname "$0")/.."
LOCAL_DB=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2-)
DIR="storage/app/private/db-pulls"
STAMP=$(date +%Y%m%d_%H%M%S)
mkdir -p "$DIR"

say() { printf '\n== %s\n' "$*"; }

say "1/4 Back up the local database ($LOCAL_DB)"
mysqldump --single-transaction --routines --triggers "$LOCAL_DB" | gzip > "$DIR/local_before_${STAMP}.sql.gz"
echo "  $DIR/local_before_${STAMP}.sql.gz"

say "2/4 Dump live ($REMOTE_DB on $SERVER)"
ssh -o BatchMode=yes "$SERVER" "mysqldump --single-transaction --routines --triggers $REMOTE_DB | gzip" > "$DIR/live_${STAMP}.sql.gz"
gzip -t "$DIR/live_${STAMP}.sql.gz"
echo "  $DIR/live_${STAMP}.sql.gz ($(du -h "$DIR/live_${STAMP}.sql.gz" | cut -f1))"

say "3/4 Replace the local database with live"
mysql -e "DROP DATABASE IF EXISTS \`$LOCAL_DB\`; CREATE DATABASE \`$LOCAL_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
gunzip -c "$DIR/live_${STAMP}.sql.gz" | mysql "$LOCAL_DB"

say "4/4 Check"
mysql -N "$LOCAL_DB" -e "SELECT CONCAT('  loans ', COUNT(*)) FROM loans; SELECT CONCAT('  repayments ', COUNT(*)) FROM repayments; SELECT CONCAT('  last migration ', MAX(migration)) FROM migrations"
php artisan optimize:clear >/dev/null

# keep the newest $KEEP of each kind
for kind in local_before live; do
  ls -1t "$DIR"/${kind}_*.sql.gz 2>/dev/null | tail -n +$((KEEP + 1)) | while read -r f; do rm -f -- "$f"; done
done
echo
echo "Local now matches live as of $STAMP. Make corrections on live, then pull again."
