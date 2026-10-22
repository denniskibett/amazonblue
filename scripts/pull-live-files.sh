#!/usr/bin/env bash
# Copy the uploaded files (KYC IDs, selfies, signatures, agreements, business documents, logos) from LIVE to this
# computer, so pictures and documents show locally the way they do on live (owner, 22 Oct 2026).
#
# The database pull (scripts/pull-live-db.sh) brings the rows that point at these files, not the files themselves;
# that is why images showed on live but not here.
#
#   scripts/pull-live-files.sh          copy new and changed files (never deletes anything locally)
#   scripts/pull-live-files.sh --dry   list what would be copied
#
# Read-only on the server. Personal documents stay in storage/app/private, which git ignores.
set -euo pipefail

SERVER=root@185.113.249.146
REMOTE=/home/loans.sharet.africa/laravel/storage/app
DRY=""
[[ "${1:-}" == "--dry" ]] && DRY="--dry-run"

cd "$(dirname "$0")/.."
for dir in private public; do
  mkdir -p "storage/app/$dir"
  echo "== storage/app/$dir"
  rsync -az $DRY --info=stats1 --exclude 'db-pulls/' --exclude 'backups/' -e "ssh -o BatchMode=yes" "$SERVER:$REMOTE/$dir/" "storage/app/$dir/"
done
[ -e public/storage ] || php artisan storage:link >/dev/null
echo
echo "Done. Uploaded files now match live (nothing was deleted here)."
