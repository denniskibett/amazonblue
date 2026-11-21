#!/usr/bin/env bash
# Release a build of this app (owner, 21 Nov 2026: every mobile app on its own branch, every build versioned).
#
#   scripts/release.sh            check, build the APKs, tag mobile-v<version>, push the branch and the tag
#   scripts/release.sh --no-push  same, but keep it local
#
# The version is the one in pubspec.yaml (X.Y.Z+N). Before running:
#   1. raise it: X.Y.Z for what people notice, +N by one for every build that leaves this Mac;
#   2. add a "## X.Y.Z+N (date)" section to CHANGELOG.md saying what changed;
#   3. commit.
# Apps with a dart_defines.example.json build with dart_defines.live.json (API address and key; never in git).
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

fail() { printf '\n\033[31mSTOP: %s\033[0m\n' "$*" >&2; exit 1; }

VERSION=$(grep -m1 '^version:' pubspec.yaml | awk '{print $2}')
TAG="mobile-v$VERSION"
[ -n "$VERSION" ] || fail "no version in pubspec.yaml"
[ -z "$(git status --porcelain)" ] || fail "uncommitted changes: commit them first"
git rev-parse -q --verify "refs/tags/$TAG" >/dev/null && fail "$TAG already exists: raise the version in pubspec.yaml (at least +N)"
grep -q "^## $VERSION " CHANGELOG.md || fail "CHANGELOG.md has no '## $VERSION (date)' section"
DEFINES=()
if [ -f dart_defines.example.json ]; then
  [ -f dart_defines.live.json ] || fail "dart_defines.live.json missing (copy dart_defines.example.json and fill it in)"
  DEFINES=(--dart-define-from-file=dart_defines.live.json)
fi

flutter analyze --no-fatal-infos
flutter build apk --release --split-per-abi ${DEFINES[@]+"${DEFINES[@]}"}

NOTES=$(awk -v v="## $VERSION " 'index($0, v) == 1 {on=1; next} /^## / {on=0} on' CHANGELOG.md | sed '/^\s*$/d')
git tag -a "$TAG" -m "$TAG" -m "$NOTES"
echo "Tagged $TAG on $(git rev-parse --short HEAD)."
ls -lh build/app/outputs/flutter-apk/*-release.apk

if [ "${1:-}" != "--no-push" ] && git remote get-url origin >/dev/null 2>&1; then
  git push origin "HEAD:$(git branch --show-current)" "$TAG"
fi
echo "Next: publish the arm64 APK the way this app's README says, with version $VERSION."
