#!/usr/bin/env bash
#
# Deploy / update the app on the live server (CyberPanel).
#
#   cd /home/<domain>/<app folder>   # the folder that contains artisan
#   bash deploy.sh                   # safe to run after every upload or git pull
#
# Before pushing or uploading from your machine:
#   npm run build                    # dashboard CSS/JS live in public/build (committed)
#   stop `npm run dev`               # it creates public/hot, which points the site at your laptop
#
set -euo pipefail
cd "$(dirname "$0")"

PHP="${PHP:-php}"   # e.g. PHP=/usr/local/lsws/lsphp83/bin/php bash deploy.sh
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

step "Database migrations"
$PHP artisan migrate --force

step "Storage link (uploaded IDs, avatars, signatures)"
[ -L public/storage ] || $PHP artisan storage:link

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

step "Maintenance mode off"
$PHP artisan up
trap - EXIT

printf '\n\033[1;32m✓ Deployed.\033[0m\n'

# ---------------------------------------------------------------------------
# DEPLOY notes: live .env must have at least
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=https://your-domain          (used for links, emails and storage URLs)
#   DB_DATABASE / DB_USERNAME / DB_PASSWORD = the CyberPanel database
# CyberPanel: point the website's document root at this app's public/ folder.
# ---------------------------------------------------------------------------
