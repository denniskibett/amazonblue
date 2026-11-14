# Mobile API (`/api/mobile/v1`)

The test app (Flutter, `~/projects/aimzone_app`, APK only for now) talks to this API. Owner, 11 Nov 2026.

## Switching it on

1. Run `database/sql/2026_11_11_mobile_api.sql` (creates `personal_access_tokens`, `mobile_devices`, `data_consents`,
   `device_sightings` and `api_keys.client`).
2. Set `MOBILE_API_ENABLED=true` in `.env`, then `php artisan config:cache`.
3. On **Staff › API keys**, make a key with **Used by: Mobile app**. It goes into the APK build as `API_KEY`.
   A mobile key opens only `/api/mobile/v1`; an "Other system" key opens only `/api/v1`.
4. Build the APK with that key, then on **Staff › API keys › Mobile app (test APK)** press **Upload APK** and give
   its version and what changed. It goes up on the download page `/app`, and the website footer shows an
   "Android app" link while one is up. **Take down** removes it. Uploads and take-downs are in the audit log.
   The file sits at `storage/app/public/app/<MOBILE_APK_FILE>` (default `aimzone-test.apk`); `MOBILE_APK_VERSION`
   and `MOBILE_APK_NOTES` are only used until the first upload. PHP's `upload_max_filesize` and `post_max_size`
   must be above the APK's size (a release build is about 20 to 30 MB).

The key inside an APK can be read by anyone who has the APK, so it marks "this is our app"; it is not a password.
Each person still signs in. Switch a leaked key off on the API keys page and ship a new build.

## Every call

| Header | Value |
|---|---|
| `X-Api-Key` | the Mobile app key |
| `X-Install-Id` | random ID made by the app on first start |
| `Authorization` | `Bearer <token>` after sign-in |
| `Accept` | `application/json` |

Errors are JSON `{message}`; validation errors also carry `errors`. 401 means sign in again. 503 means the switch is off.

## Endpoints

| Method and path | What it does |
|---|---|
| `GET ping`, `GET app` | key check; latest APK version and download link |
| `POST auth/login` | `login` (phone or email), `password`, `install_id`, `device_name` → `token` (30 days, one per install) |
| `POST auth/logout` | ends this phone's token |
| `GET me` | user, verified, limit, blockers, product, open loan |
| `POST devices` | registers the phone and the consents `{device, area, push}`; model and OS are kept only with `device` on |
| `POST devices/sighting` | `event` (signed_in, applied, paid), `area` (town or estate, kept only with `area` on) |
| `POST quotes` | `amount` → interest, total, due date |
| `POST loans` | apply: `amount`, `consent` (same rules as the website's Borrow page) |
| `GET loans`, `GET loans/{id}` | own loans; balance, timeline, payments and how to pay |
| `POST loans/{id}/repay/stk`, `GET .../stk/{requestId}` | M-Pesa prompt (needs `MPESA_STK_ENABLED`) and its status |
| `POST loans/{id}/repay/sharet` | Sharet Pay link (`pay_url`) |
| `POST loans/{id}/repay/claim` | "I already paid": `code`, `amount` |
| `GET statement` | all payments on the borrower's loans |

Phone and area data are staff only and never shown to collection agencies. The app never reads contacts, photos,
call logs, SMS or precise location.

## Building the APK

```bash
cd ~/projects/aimzone_app
flutter build apk --release --dart-define=API_BASE=https://loans.sharet.africa/api/mobile/v1 --dart-define=API_KEY=<mobile key>
```

The file is `build/app/outputs/flutter-apk/app-release.apk`. Test builds are signed with the debug key; a real
release key is needed before any store.
