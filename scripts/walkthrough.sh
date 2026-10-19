#!/usr/bin/env bash
# Walkthrough harness: log in as a demo user, save each page's HTML and take desktop + phone screenshots.
#
#   scripts/walkthrough.sh <who> <name>=<path> [<name>=<path> ...]
#
#   <who>   a demo key (admin, teller, manager, recovery, auditor, broker, partner, active, overdue,
#           rejected, new; "borrower" = active) or any email. Password: $PASSWORD (default Demo12345!).
#   <path>  a URL path on the server, e.g. dashboard=/account  loan=/account/loans/545
#
# Output: docs/stage2/screens/<who>/<name>.html, <name>-desktop.png (1440x900), <name>-phone.png (390x844)
# Env:    BASE (default http://127.0.0.1:8790), PASSWORD, DESKTOP_H / PHONE_H (taller shots), OUT (output root)
# Needs:  the app running on the clone:  DB_DATABASE=loans_wp_s2 php artisan serve --port=8790
#         demo users:                    DB_DATABASE=loans_wp_s2 php artisan demo:seed
# How:    curl logs in through /login (CSRF token + session cookie) and fetches the HTML; the HTML is
#         written to public/_wt/ with <base href> pointing at the server, headless Chrome screenshots that
#         snapshot (it has no session of its own), then the snapshot is deleted.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BASE="${BASE:-http://127.0.0.1:8790}"
PASSWORD="${PASSWORD:-Demo12345!}"
CHROME="${CHROME:-/Applications/Google Chrome.app/Contents/MacOS/Google Chrome}"
DESKTOP_H="${DESKTOP_H:-900}"
PHONE_H="${PHONE_H:-844}"

if [ $# -lt 2 ]; then
  sed -n '2,12p' "$0" | sed 's/^# \{0,1\}//'
  exit 1
fi

who="$1"; shift
case "$who" in
  *@*) email="$who"; who="${who%%@*}" ;;
  borrower) email="active@demo.aimzone.test" ;;
  recovery-officer) email="recovery@demo.aimzone.test"; who="recovery" ;;
  *) email="$who@demo.aimzone.test" ;;
esac

OUT="${OUT:-$ROOT/docs/stage2/screens}/$who"
SNAP_DIR="$ROOT/public/_wt"
TMP="$(mktemp -d)"
mkdir -p "$OUT" "$SNAP_DIR"
cleanup() { rm -rf "$TMP"; rm -f "$SNAP_DIR"/wt-"$$"-*.html "$SNAP_DIR"/frame-wt-"$$"-*.html; rmdir "$SNAP_DIR" 2>/dev/null || true; }
trap cleanup EXIT

curl -fsS "$BASE/up" -o /dev/null 2>/dev/null || curl -fsS "$BASE/login" -o /dev/null || { echo "Server not reachable at $BASE" >&2; exit 1; }

# log in: CSRF token from the form, then post the credentials with the same cookie jar
jar="$TMP/cookies"
token="$(curl -fsS -c "$jar" -b "$jar" "$BASE/login" | sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' | head -1)"
[ -n "$token" ] || { echo "No CSRF token on $BASE/login" >&2; exit 1; }
landing="$(curl -sS -c "$jar" -b "$jar" -o /dev/null -w '%{redirect_url}' \
  --data-urlencode "_token=$token" --data-urlencode "email=$email" --data-urlencode "password=$PASSWORD" "$BASE/login")"
case "$landing" in
  */login*|"") echo "Login failed for $email (redirected to '${landing:-nowhere}')" >&2; exit 1 ;;
esac
echo "Logged in as $email -> $landing"

shot() { # url file width height
  "$CHROME" --headless=new --disable-gpu --hide-scrollbars --no-first-run --no-default-browser-check \
    --user-data-dir="$TMP/chrome" --window-size="$3,$4" --virtual-time-budget=4000 \
    --screenshot="$2" "$1" >/dev/null 2>&1
}

for pair in "$@"; do
  name="${pair%%=*}"; path="${pair#*=}"
  [ "$name" != "$pair" ] || { echo "Skip '$pair': use name=/path" >&2; continue; }
  html="$OUT/$name.html"
  code="$(curl -sS -b "$jar" -c "$jar" -o "$html" -w '%{http_code}' "$BASE$path")"
  if [ "$code" != "200" ]; then echo "  $name: HTTP $code for $path (HTML saved, no screenshots)" >&2; continue; fi

  snap="wt-$$-$name.html"
  # <base> so relative links/assets resolve against the server; the snapshot itself is served from public/_wt
  perl -0pe "s{<head([^>]*)>}{<head\$1><base href=\"$BASE$path\">}i" "$html" > "$SNAP_DIR/$snap"
  shot "$BASE/_wt/$snap" "$OUT/$name-desktop.png" 1440 "$DESKTOP_H"
  # headless Chrome won't make a window narrower than ~500px, so the phone shot renders the page in a
  # 390px iframe centred on a 600px window and crops the PNG back to the middle 390px (sips, built into macOS)
  printf '<!doctype html><body style="margin:0"><iframe src="%s" style="display:block;border:0;margin-left:105px;width:390px;height:%spx"></iframe>' \
    "$snap" "$PHONE_H" > "$SNAP_DIR/frame-$snap"
  shot "$BASE/_wt/frame-$snap" "$OUT/$name-phone.png" 600 "$PHONE_H"
  sips --cropToHeightWidth "$PHONE_H" 390 "$OUT/$name-phone.png" >/dev/null
  rm -f "$SNAP_DIR/$snap" "$SNAP_DIR/frame-$snap"
  echo "  $name: $path -> ${OUT#$ROOT/}/$name-{desktop,phone}.png"
done
