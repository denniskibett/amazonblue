#!/usr/bin/env bash
# From the Mac: test, build, push, then run the server deploy (scripts/deploy-live.sh) over SSH.
#   scripts/push-live.sh            # current branch
#   scripts/push-live.sh --no-ssh   # stop after the push and print the server command (e.g. CyberPanel web terminal)
# Needs: a clean working tree, SSH access as root to the server (asks for the password if no key is set up).
set -euo pipefail

SERVER=root@185.113.249.146
REMOTE_SCRIPT=/home/loans.sharet.africa/laravel/scripts/deploy-live.sh
BRANCH=$(git rev-parse --abbrev-ref HEAD)

say()  { printf '\n\033[1m== %s\033[0m\n' "$*"; }
fail() { printf '\n\033[31mSTOP: %s\033[0m\n' "$*" >&2; exit 1; }

cd "$(git rev-parse --show-toplevel)"
[ -z "$(git status --porcelain)" ] || fail "uncommitted changes; commit or stash them first"

say "Tests"
php artisan test --compact || fail "tests failed; nothing pushed"

say "Assets"
npm run build --silent >/dev/null
[ -z "$(git status --porcelain public/build)" ] || fail "public/build changed after npm run build; commit it (git add public/build) and run again"

say "Push $BRANCH"
git push origin "$BRANCH"

if [ "${1:-}" = "--no-ssh" ]; then
  say "Now run on the server:"
  echo "  cd /home/loans.sharet.africa/laravel && git pull --ff-only origin $BRANCH && bash $REMOTE_SCRIPT $BRANCH"
  exit 0
fi

say "Deploy on the server"
# pull first so the server runs the newest copy of the deploy script itself
ssh -t "$SERVER" "cd /home/loans.sharet.africa/laravel && git pull --quiet --ff-only origin $BRANCH && bash $REMOTE_SCRIPT $BRANCH"
