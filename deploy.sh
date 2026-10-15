#!/usr/bin/env bash
#
# Deploy / update the app on the live server (CyberPanel).
#
#   cd /home/<domain>/<app folder>   # the folder that contains artisan
#   bash deploy.sh                   # safe to run after every upload or git pull
#
# If the website's document root is NOT this app's public/ folder
# (e.g. app in /home/<domain>/laravel, site served from /home/<domain>/public_html),
# say where the web root is and the script copies public files there:
#   PUBLIC_DIR=/home/<domain>/public_html bash deploy.sh
#
# Before pushing or uploading from your machine:
#   npm run build                    # dashboard CSS/JS live in public/build (committed)
#   stop `npm run dev`               # it creates public/hot, which points the site at your laptop
#
set -euo pipefail
cd "$(dirname "$0")"

PHP="${PHP:-php}"               # e.g. PHP=/usr/local/lsws/lsphp83/bin/php bash deploy.sh
PUBLIC_DIR="${PUBLIC_DIR:-}"    # web root, when it isn't ./public (see top)
step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }
fail() { printf '\n\033[1;31m✗ %s\033[0m\n' "$1"; exit 1; }

step "Pre-flight checks"
[ -f .env ] || fail ".env is missing. Copy .env.example to .env and fill in the live values (see DEPLOY notes at the bottom of this file)."
grep -q '^APP_ENV=production' .env || echo "  ! APP_ENV is not 'production' in .env"
grep -q '^APP_DEBUG=false' .env || echo "  ! APP_DEBUG is not 'false' in .env (errors and secrets would show to visitors)"
grep -qE '^APP_URL=https?://(localhost|127\.0\.0\.1)' .env && echo "  ! APP_URL still points at localhost"
[ -f public/build/manifest.json ] || fail "public/build/manifest.json is missing. Run 'npm run build' locally and push/upload public/build."

step "Removing files that must never be live"
rm -f public/hot                                   # Vite dev-server pointer
find bootstrap/cache -name '*.php' -delete         # local config/route caches

step "Maintenance mode on"
$PHP artisan down --retry=15 || true
trap '$PHP artisan up >/dev/null 2>&1 || true' EXIT

if command -v composer >/dev/null 2>&1; then
  step "Installing PHP dependencies"
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
else
  [ -d vendor ] || fail "composer is not installed and vendor/ is missing."
  echo "  composer not found, using the uploaded vendor/ folder"
fi

step "Database backup before migrating"
# zipped dump in storage/app/private/<APP_NAME>/; the deploy stops here if the backup fails
if [ "${SKIP_BACKUP:-0}" = "1" ]; then
  echo "  skipped (SKIP_BACKUP=1)"
else
  $PHP artisan backup:run --only-db --disable-notifications || fail "Database backup failed, so nothing was migrated. Fix it, or rerun with SKIP_BACKUP=1 at your own risk."
fi

step "Database migrations"
# every migration checks for existing tables, keys and views first, so this only adds what's missing
$PHP artisan migrate --force

step "Storage link (uploaded IDs, avatars, signatures)"
# uploads live in storage/app/public; public/storage must be a link to it, never a copied folder
# (a copy goes stale: new uploads would save fine but never show)
[ -d public/storage ] && [ ! -L public/storage ] && fail "public/storage is a real folder, not a link. Move its files into storage/app/public, delete it, and rerun."
[ -L public/storage ] || $PHP artisan storage:link

if [ -n "$PUBLIC_DIR" ] && [ "$(cd "$PUBLIC_DIR" && pwd -P)" != "$(cd public && pwd -P)" ]; then
  step "Copying public files to the web root: $PUBLIC_DIR"
  [ -f "$PUBLIC_DIR/index.php" ] || fail "$PUBLIC_DIR/index.php not found. Is PUBLIC_DIR the website's document root?"
  rm -f "$PUBLIC_DIR/hot" "$PUBLIC_DIR/robots.txt"   # robots.txt is now served by Laravel (with the sitemap)
  # site assets and compiled dashboard assets are replaced wholesale so old files can't linger
  for dir in assets build; do
    rm -rf "${PUBLIC_DIR:?}/$dir"
    cp -R "public/$dir" "$PUBLIC_DIR/$dir"
  done
  # dashboard images/logos and root files; index.php and .htaccess are left alone (they hold the server's paths)
  mkdir -p "$PUBLIC_DIR/images"
  cp -R public/images/. "$PUBLIC_DIR/images/"
  for f in favicon.ico; do
    if [ -f "public/$f" ]; then cp "public/$f" "$PUBLIC_DIR/$f"; fi
  done
  # uploads are served from /storage
  if [ -d "$PUBLIC_DIR/storage" ] && [ ! -L "$PUBLIC_DIR/storage" ]; then
    fail "$PUBLIC_DIR/storage is a real folder, not a link. Move its files into storage/app/public, delete it, and rerun."
  fi
  [ -e "$PUBLIC_DIR/storage" ] || ln -s "$(pwd -P)/storage/app/public" "$PUBLIC_DIR/storage"
fi

step "Clearing old caches (including cached system settings and permissions)"
$PHP artisan optimize:clear
$PHP artisan permission:cache-reset

step "Building fresh caches"
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan event:cache

step "File permissions"
chmod -R ug+rwX storage bootstrap/cache
# CyberPanel's Terminal runs as root, but the site runs as its own user (the owner of artisan).
# Hand everything this script touched back to that user, or the site can't write its caches/logs.
if [ "$(id -u)" = "0" ]; then
  OWNER="$(stat -c '%U:%G' artisan)"
  if [ "${OWNER%%:*}" != "root" ]; then
    chown -R "$OWNER" storage bootstrap/cache vendor public
    if [ -n "$PUBLIC_DIR" ]; then chown -R "$OWNER" "$PUBLIC_DIR/assets" "$PUBLIC_DIR/build" "$PUBLIC_DIR/images"; fi
    echo "  ownership returned to $OWNER"
  fi
fi

step "Maintenance mode off"
$PHP artisan up
trap - EXIT

# OpenLiteSpeed (CyberPanel) keeps compiled PHP in OPcache; a graceful restart makes it load the new code
if [ "$(id -u)" = "0" ] && [ -x /usr/local/lsws/bin/lswsctrl ]; then
  step "Reloading OpenLiteSpeed so PHP picks up the new code"
  /usr/local/lsws/bin/lswsctrl restart >/dev/null && echo "  done"
fi

printf '\n\033[1;32m✓ Deployed.\033[0m\n'

# ---------------------------------------------------------------------------
# DEPLOY notes: live .env must have at least
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=https://your-domain          (used for links, emails and storage URLs)
#   DB_DATABASE / DB_USERNAME / DB_PASSWORD = the CyberPanel database
# CyberPanel: point the website's document root at this app's public/ folder.
# ---------------------------------------------------------------------------
