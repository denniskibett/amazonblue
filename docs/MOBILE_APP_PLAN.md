# Mobile app plan (Flutter)

Owner's brief, 11 Nov 2026: one Flutter app for every role, borrower first, Android first and iOS later, built on the
screens in the design file's **Mobile app** page (version 147: welcome, KYC, borrow, repay, borrow again, late and
recovery, partner, coming-soon trade and Spiro, team, phone tracking). No code until this plan is approved.

## 0. Read first: the stores' rules on short loans

Both stores restrict personal loans, and this decides what the app may offer.

| Rule | Google Play | Apple App Store |
|---|---|---|
| Minimum time to repay in full | More than 60 days; loans due in full in 60 days or less are not allowed | More than 60 days |
| Cost cap | 36% APR in the US only; Kenya needs the Personal Loan App Declaration for Kenya with proof of the CBK digital credit licence | Maximum 36% a year (APR), all costs and fees included (guideline 3.2.2(ix)) |
| Data the app may read | No external storage, photos and videos, contacts, precise location or call logs (since 31 May 2023) | Privacy labels; must disclose APR and due date |

Our 10-day Emergency loan (20% for 10 days) is due in full within 60 days and costs far more than 36% a year, so **it
cannot be offered inside a store app**. Options:

1. **Store app with long products only** (recommended): bike finance with Spiro (12–18 months), 3-month+ loans, trade
   finance, partner investing, repayments of any loan. Short loans stay on the website and USSD.
2. **Store app shows and repays existing short loans but does not sell them**: borrowers apply on the web; the app
   tracks and repays. Needs a careful read of each store's wording; Play has removed apps for less.
3. **APK outside the stores** (direct download): allowed by Android, not iOS; loses trust and updates; not advised.

Decide this before building the Borrow screens. The rest of the plan holds either way. The policy wording should be
re-checked on the day of submission; both stores change it.

Sources (read 11 Nov 2026): [Google Play Financial Services policy](https://support.google.com/googleplay/android-developer/answer/9876821),
[Apple App Review Guidelines update](https://developer.apple.com/news?id=ey6d8onl),
[Google restricts loan apps' data access (Business Today Kenya)](https://businesstoday.co.ke/google-bars-loan-apps-accessing-contacts-photos/).

## 1. What ships, in order

| Release | Who | Screens (design file › Mobile app) |
|---|---|---|
| 1.0 Borrower | Borrowers | Welcome 1–5, KYC 1–10, home, products (coming-soon cards), borrow (per the store decision), confirm with PIN, funds sent, loan details, repay (M-Pesa prompt, Sharet Pay, paybill, "I already paid"), paid, limit grew, late, more time, in recovery, payment plan, statement, account |
| 1.1 Partner | Partners | Portfolio, opportunities, invest, statements, payouts |
| 1.2 Field team | Recovery officer, manager, owner | Calls list, case and log a call, approvals, today, phone and areas, repayment by phone model |
| Later | Borrowers | Spiro bike finance and trade finance (now "coming soon") |

One app, one login: the role decides the tabs after sign-in. A borrower who is also a partner switches in Account.

## 2. The app (Flutter)

- **Flutter stable**, Dart 3. Android 8.0+ (API 26), target the latest API Play requires; iOS 15+ later.
- **Structure:** `lib/features/<feature>/` (welcome, auth, kyc, borrow, loans, repay, statement, account, partner,
  team), each with `data/` (API calls), `state/` and `ui/`; `lib/core/` (API client, theme, router, storage).
- **State:** Riverpod. **Routing:** go_router with a guard per role. **HTTP:** dio with retry and a token interceptor.
- **Look:** a theme from `resources/design/tokens.cjs` (ink blue #1B2B6B, cloud #EEF2FA, Sora/Figtree/Outfit, 20–24px
  cards, pill buttons), the same tokens as the website, so app and web stay in step.
- **Security:** PIN plus fingerprint or face (`local_auth`); token in the Keystore/Keychain (`flutter_secure_storage`);
  no screenshots on money screens (Android `FLAG_SECURE`); certificate pinning to `loans.sharet.africa`; sign-out on a
  new phone until it is confirmed by SMS code.
- **KYC capture:** `camera` with an on-screen ID frame; selfie with a left/right turn; a signature pad. Photos go
  straight to the API, never to the gallery.
- **Payments:** M-Pesa prompt and "I already paid" call our API; Sharet Pay opens the pay link in an in-app browser
  and the app polls the loan until the webhook lands.
- **Push:** Firebase Cloud Messaging (due soon, due today, late, money sent, payment received, request decided).
- **Languages:** English and Kiswahili from day one.
- **Phone tracking (with consent, screen KYC 3):** phone model, OS and app version (`device_info_plus`), an app
  install ID, approximate area (`geolocator` with coarse location only) at application and payment. Never contacts,
  photos, call logs, SMS or precise location. Each item can be switched off in Account.

## 3. What the Laravel app must add

A mobile API beside the web app, reusing the services the website already runs on, so the money rules stay in one
place.

- **Auth:** Laravel Sanctum tokens, one per phone (`personal_access_tokens` with a device name). Sign-in with phone +
  PIN; SMS code on a new phone. A PIN column (hashed) on users, separate from the web password.
- **New tables** (each with a migration and a `database/sql` twin):
  `mobile_devices` (user, install ID, model, OS, app version, push token, first/last seen),
  `device_sightings` (device, approximate area, event: applied / paid / signed in, time),
  `data_consents` (user, item, on/off, version, time).
- **Endpoints, `/api/mobile/v1`** (JSON, versioned):

| Area | Endpoints | Reuses |
|---|---|---|
| Auth | `POST auth/register`, `auth/otp`, `auth/login`, `auth/logout`, `auth/pin` | `RegisteredUserController` rules |
| Device | `POST devices` (register, push token, consent), `POST devices/sighting` | new |
| KYC | `GET kyc`, `POST kyc/personal`, `kyc/documents` (multipart), `kyc/income`, `kyc/contacts`, `kyc/submit` | `HomeController` KYC steps, `SignatureService` |
| Home | `GET me` (role, limit, blockers, current loan card) | `BorrowingEligibility`, `BorrowerLoanState`, `LoanProgress` |
| Borrow | `GET products`, `POST quotes`, `POST loans` (apply, signed with PIN) | `LoanController@apply`, `LoanCalculator` |
| Loans | `GET loans`, `GET loans/{id}` (timeline, cycle, schedule), `GET loans/{id}/agreement.pdf` | existing account pages |
| Repay | `POST loans/{id}/repay/stk`, `GET .../stk/{id}`, `POST .../repay/sharet`, `POST .../repay/claim` | `Daraja`, `SharetController`, `PaymentRecorder` |
| Requests | `POST loans/{id}/extension`, `topup`, `plan/accept` | `Requests` |
| Statement | `GET statement?from&to`, `GET receipts/{id}` | `BorrowerJourneys` |
| Partner | `GET partner/portfolio`, `partner/tranches`, `partner/payouts`, `POST partner/withdrawals` | `PartnerReturns`, `PartnerAccount` |
| Team | `GET team/calls`, `GET cases/{id}`, `POST cases/{id}/actions`, `GET approvals`, `POST approvals/{id}` | `CollectionsController`, `CaseController`, `Approvals` |
| Owner | `GET owner/today`, `GET owner/phone-models` | `BusinessInsights`, `PeopleInsights` |

- **Push sender:** the existing `CustomerEvents` notifications gain an FCM channel.
- **Staff screens:** borrower page gains "Phones and areas"; People and places gains "By phone model" and
  "Shared phones" (a fraud flag), staff only, never shown to collection agencies.
- **Rate limits** per phone and per number; audit rows for sign-in, PIN change and new phone.

## 4. Build order and rough time

| Step | Work | About |
|---|---|---|
| 1 | Store decision (section 0); Play developer account; CBK licence and ODPC registration documents ready | owner |
| 2 | Laravel: Sanctum, devices, consents, auth + KYC + home endpoints, tests | 2 weeks |
| 3 | Flutter: project, theme, router, auth, welcome, KYC (camera, signature), home | 3 weeks |
| 4 | Laravel + Flutter: borrow, loans, repay (M-Pesa prompt, Sharet Pay, claim), statement, push | 3 weeks |
| 5 | Late, more time, recovery, plan, account, Kiswahili, phone tracking | 2 weeks |
| 6 | Closed test on Play (20 testers for 14 days, required for new personal developer accounts), fixes, Play review | 2–3 weeks |
| 7 | 1.1 Partner, then 1.2 field team | 2–3 weeks each |
| 8 | iOS build, App Store review (36% APR rule applies) | 2 weeks |

## 5. Decisions needed from the owner

1. Which store route (section 0), and which products the app sells at launch.
2. App name and package ID (suggest `africa.aimzone.app`, name "Aimzone").
3. Whether partners and staff use the same app (recommended) or a separate staff app.
4. Production M-Pesa paybill and Daraja keys for the app's M-Pesa prompt.
5. Who holds the Play and Apple developer accounts (company accounts, not personal).
