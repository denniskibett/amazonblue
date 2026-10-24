#!/usr/bin/env bash
# Server housekeeping (owner, 24 Oct 2026). Run ON THE SERVER from the live folder:
#   cd /home/loans.sharet.africa/laravel && bash scripts/server-tidy.sh          # report only, changes nothing
#   cd /home/loans.sharet.africa/laravel && bash scripts/server-tidy.sh --apply  # tidy .env (a backup is kept first)
# It never prints keys or passwords and never changes one. Rotating secrets is done by hand (docs/stage4/SWITCH-ON-PLAN.md).
set -euo pipefail
cd "$(dirname "$0")/.."
[ -f artisan ] && [ -f .env ] || { echo "STOP: run this from the live Laravel folder"; exit 1; }
APPLY=${1:-}

echo "== .env logging"
stack=$(grep -c '^LOG_STACK=' .env || true)
days=$(grep -c '^LOG_DAILY_DAYS=' .env || true)
echo "LOG_STACK lines: $stack (should be 1) · LOG_DAILY_DAYS set: $days (should be 1)"
if [ "$APPLY" = "--apply" ] && { [ "$stack" -gt 1 ] || [ "$days" -eq 0 ]; }; then
  cp -p .env ".env.bak.$(date +%Y%m%d_%H%M%S)"
  # keep the first LOG_STACK line, drop the rest; add LOG_DAILY_DAYS=30 when missing
  awk '/^LOG_STACK=/{ if (seen++) next } { print }' .env > .env.tmp && cat .env.tmp > .env && rm -f .env.tmp
  grep -q '^LOG_DAILY_DAYS=' .env || printf '\nLOG_DAILY_DAYS=30\n' >> .env
  php artisan config:cache >/dev/null
  echo "tidied (backup .env.bak.*), config cached"
fi

echo "== switches (names and plain values only)"
grep -E '^(LENDING_[A-Z_]+|NOTIFICATIONS_ENABLED|SMS_DRIVER|MPESA_ENV|MPESA_ENABLED|MPESA_CLAIMS_ENABLED|MPESA_STK_ENABLED|MPESA_B2C_ENABLED|MAIL_MAILER)=' .env || true

echo "== sysadmin roles (must be [\"super-admin\"] only)"
php artisan tinker --execute='$u = App\Models\User::where("email", "like", "sysadmin%")->first(); echo $u ? $u->email . " " . json_encode($u->getRoleNames()) : "no sysadmin user found", PHP_EOL;' 2>/dev/null || true

echo "== two people per sign-off permission (needed before LENDING_APPROVALS_ENABLED=true)"
php artisan tinker --execute='foreach (["approve loans","write off loan","delete repayments","edit partners","edit users","disburse loans"] as $p) echo str_pad($p, 20), App\Models\User::permission($p)->whereNull("deleted_at")->count(), PHP_EOL;' 2>/dev/null || true

echo "== machine"
. /etc/os-release 2>/dev/null && echo "OS: ${PRETTY_NAME:-unknown}"
free -m | awk 'NR<=3'
echo "== mail domain (SPF / DKIM records for loans.sharet.africa)"
command -v dig >/dev/null && { dig +short TXT loans.sharet.africa | grep -i spf || echo "no SPF record found"; dig +short TXT default._domainkey.loans.sharet.africa | head -1 || true; } || echo "dig not installed: check SPF/DKIM with a password-reset email (Gmail › Show original)"
