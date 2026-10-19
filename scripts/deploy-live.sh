#!/usr/bin/env bash
# Deploy a branch to live (loans.sharet.africa on CyberPanel). Run ON THE SERVER as root:
#   bash /home/loans.sharet.africa/laravel/scripts/deploy-live.sh [branch]      # default: stage-2-ui
#   bash /home/loans.sharet.africa/laravel/scripts/deploy-live.sh --rollback    # back to the previous release
#
# Server layout (see docs/DEPLOY.md):
#   /home/loans.sharet.africa/laravel        the app (git clone; .env and storage live here)
#   /home/loans.sharet.africa/public_html    web root; index.php loads ../laravel; build/ and assets/ are copied here
#   /home/loans.sharet.africa/releases/      previous app folders, newest kept for rollback
#
# Steps: back up DB -> fresh clone beside the live app -> .env + storage -> composer (PHP 8.3) -> checks
# (artisan boots, no pending migrations: run the database/sql twins first) -> swap -> caches -> smoke test.
# If the smoke test fails, the previous release is put back automatically.
set -euo pipefail

SITE=/home/loans.sharet.africa
APP=$SITE/laravel
WEB=$SITE/public_html
NEXT=$SITE/laravel_next
RELEASES=$SITE/releases
OWNER=loans4537
REPO=https://github.com/denniskibett/amazonblue.git
URL=https://loans.sharet.africa
KEEP=3

say()  { printf '\n\033[1m== %s\033[0m\n' "$*"; }
fail() { printf '\n\033[31mSTOP: %s\033[0m\n' "$*" >&2; exit 1; }

smoke() {
  local ok=0 code
  for path in / /login; do
    code=$(curl -s -o /dev/null -w '%{http_code}' "$URL$path" || true)
    echo "  $code $path"
    [ "$code" = "200" ] || ok=1
  done
  return $ok
}

restore_previous() {
  local prev
  prev=$(ls -1dt "$RELEASES"/laravel_* 2>/dev/null | head -1 || true)
  [ -n "$prev" ] || fail "no previous release in $RELEASES"
  mv "$APP" "$RELEASES/failed_$(date +%Y%m%d_%H%M%S)"
  mv "$prev" "$APP"
  (cd "$APP" && php artisan optimize:clear >/dev/null && php artisan permission:cache-reset >/dev/null)
  echo "Restored $(basename "$prev")."
}

[ "$(id -u)" = 0 ] || fail "run as root"
mkdir -p "$RELEASES" ~/backups
git config --global --get-all safe.directory | grep -qx "$NEXT" || git config --global --add safe.directory "$NEXT"
git config --global --get-all safe.directory | grep -qx "$APP"  || git config --global --add safe.directory "$APP"

if [ "${1:-}" = "--rollback" ]; then
  say "Rolling back"
  restore_previous
  smoke || fail "site still not healthy after rollback: check $APP/storage/logs"
  exit 0
fi

BRANCH=${1:-stage-2-ui}
STAMP=$(date +%Y%m%d_%H%M%S)

say "1/7 Database backup"
DB=$(grep -E '^DB_DATABASE=' "$APP/.env" | cut -d= -f2-)
mysqldump -uroot --single-transaction --routines --triggers "$DB" | gzip > ~/backups/db_"$STAMP".sql.gz
ls -1t ~/backups/db_*.sql.gz | tail -n +11 | xargs -r rm --
echo "  ~/backups/db_$STAMP.sql.gz"

say "2/7 Fresh copy of $BRANCH"
rm -rf "$NEXT"
git clone --quiet --depth 1 --branch "$BRANCH" "$REPO" "$NEXT"
echo "  $(git -C "$NEXT" log --oneline -1)"
cp "$APP/.env" "$NEXT/.env"
rsync -a "$APP/storage/" "$NEXT/storage/"
ln -sfn ../storage/app/public "$NEXT/public/storage"

say "3/7 Composer (no dev packages)"
(cd "$NEXT" && composer install --no-dev --optimize-autoloader --no-interaction --quiet) || fail "composer install failed; live site untouched"
[ -f "$NEXT/vendor/autoload.php" ] || fail "vendor/autoload.php missing; live site untouched"

say "4/7 Checks"
(cd "$NEXT" && php artisan --version) || fail "the new code does not boot; live site untouched"
PENDING=$(cd "$NEXT" && php artisan migrate:status --pending --no-ansi 2>/dev/null | grep -c 'Pending' || true)
if [ "$PENDING" != "0" ]; then
  (cd "$NEXT" && php artisan migrate:status --pending --no-ansi) || true
  fail "$PENDING migration(s) pending. Run their database/sql/*.sql twins on live first, then deploy again. Live site untouched."
fi
echo "  boots; no pending migrations"

say "5/7 Swap"
rsync -a "$APP/storage/" "$NEXT/storage/"     # uploads made during the build
rsync -a --chown="$OWNER:$OWNER" --exclude=index.php --exclude=.htaccess --exclude=storage --exclude=hot \
  "$NEXT/public/" "$WEB/"
chown -R "$OWNER:$OWNER" "$NEXT"
mv "$APP" "$RELEASES/laravel_$STAMP"
mv "$NEXT" "$APP"

say "6/7 Caches"
(cd "$APP" && php artisan optimize:clear >/dev/null && php artisan permission:cache-reset >/dev/null)
echo "  cleared"

say "7/7 Smoke test"
if ! smoke; then
  echo "Smoke test failed: rolling back."
  restore_previous
  smoke || true
  fail "deploy rolled back. See $RELEASES/failed_* and its storage/logs."
fi

ls -1dt "$RELEASES"/laravel_* 2>/dev/null | tail -n +$((KEEP + 1)) | xargs -r rm -rf --
say "Done: $(git -C "$APP" log --oneline -1) is live. Previous release: $RELEASES/laravel_$STAMP"
echo "Rollback if needed: bash $APP/scripts/deploy-live.sh --rollback"
