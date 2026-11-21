# Changelog

Every build that leaves this Mac has a section here and a tag `mobile-v<version>`.

## 0.2.1+4 (21 Nov 2026)

- Signed with the Aimzone release key instead of the build Mac's debug key, so every future update installs over it.
- One-time step: phones with 0.2.0 or older must uninstall the old app first (Android will not replace an app signed
  with a different key). Nothing is lost; sign in again.

## 0.2.0+3 (21 Nov 2026)

- Each account sees its own screens (roles decided by the server): borrowers Home, Loans, Statement; partners
  Portfolio (read only); loan staff the Desk queue counts. Accounts the app has nothing for cannot sign in.
- Account on hold until verification (KYC) is finished: what is done, what is still needed, and a link to finish.
- Published on loans.sharet.africa/app (arm64, sha256 d40c6c28…2f08).

## 0.1.1+2 (15 Nov 2026)

- TEST ribbon on test builds.
- With a loan out, home is that loan: balance, due day, how much is paid, Pay now.
- Your own photo on home and Account.

## 0.1.0+1 (11 Nov 2026)

- First test build: sign in, home, borrow, repay (M-Pesa prompt, Sharet Pay, I already paid), statement.
