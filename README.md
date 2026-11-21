# Aimzone app (Android)

Flutter app for Aimzone Ventures clients and loan staff. It talks only to the Aimzone API
(`https://loans.sharet.africa/api/mobile/v1`; server code and docs in the `amazonblue` repo, `docs/MOBILE_API.md`).
What each account sees follows its role (borrower, partner, loan staff), decided by the server.

## Where the code lives

This app is the `mobile` branch of github.com/denniskibett/amazonblue: its own history, never merged into the
website branches (`staging`, `main`). Work on `mobile`; every build given to anyone is a tag.

## Versions

- `pubspec.yaml` holds the one version: `X.Y.Z+N`. `X.Y.Z` changes with what people notice; `+N` goes up by one for
  every build that leaves this Mac, even a re-build.
- Every release has a `## X.Y.Z+N (date)` section in `CHANGELOG.md` and a git tag `mobile-vX.Y.Z+N`.
- `scripts/release.sh` checks all of that, builds the APKs and tags (it refuses a version that is already tagged).

## Building

```bash
cp dart_defines.example.json dart_defines.live.json   # then put the real Mobile app key in it (never committed)
scripts/release.sh
```

## Publishing a build

The download page (`loans.sharet.africa/app`) serves `storage/app/public/app/aimzone-test.apk`, with its version and
notes from `storage/app/mobile-apk.json` on the server. Keep the old APK in `/root/amazonblue-backups/` before
replacing it, upload `build/app/outputs/flutter-apk/app-arm64-v8a-release.apk`, check its sha256, `chown loans4537`.
