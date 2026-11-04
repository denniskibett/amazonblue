# AIMZONE design file (imported 3 Nov 2026)

A copy of the design canvas "AIMZONE Ventures Logos" (claude.ai/artifact/2JAjByGcGyBxbALNTnFMX9, version 1793811062-3427): 267 artboards. It is the reference for how every screen should look. The app's tokens in `resources/design/tokens.cjs` already use its colours and fonts.

Each `.dc.html` file is one artboard: the markup is in `<x-dc>`, its data in the `<script type="text/x-dc">` block. `canvas.json` is the index (positions, titles, pages).

**Look used in the web-app boards:** Sora (titles, 26px 700, -0.035em), Figtree (body), Outfit (figures) · ink-blue `#1B2B6B` primary, ink `#151A2E` text, slate `#5A6178` secondary text, line `#DDE3EF`, cloud `#EEF2FA` ground, mist `#C9D6F0` selected · white cards, 24px corners, soft shadow, no border · pill buttons (999px, 48px high) · 14px inputs and menu items · status chips from the token pairs (sage, apricot, rose, sand).

Refresh this copy by reading the artifact again; don't edit these files by hand.

**Staff screens use the app's TailAdmin look** (white sidebar with MENU groups, top bar with search, grey page, white cards with a grey border and 16px corners, royal-blue `#2B4FD8` buttons, right-hand side sheets for forms). Borrower and partner screens use the customer look described above.

Web app journeys was rebuilt on 3 Nov 2026 to match the code's roles (loan officer, manager, KYC & compliance, teller, recovery officer, accountant, admin, super-admin, auditor, broker), with a row for the borrower's unhappy paths and a row of staff side sheets and dialogs. Trade finance and valuation moved to "Later · not in the app yet". Asset finance (any asset: bikes first, then cars, buses, land, buildings, aircraft, equipment) was designed there on 4 Nov 2026; partner returns and payouts, and the six-group sidebar with module tabs, are on "Gap screens".

## Page 1 (32)

- `Main.dc.html` · 1 · Stencil
- `Target.dc.html` · 2 · Lowercase Dot
- `Monogram.dc.html` · 3 · Block Stack
- `LowerStack.dc.html` · LowerStack.dc.html
- `Condensed.dc.html` · 4 · Slab
- `SplitWeight.dc.html` · 5 · Solid + Outline
- `Seal.dc.html` · 6 · Focus Frame
- `LowerDotI.dc.html` · 2a · Square i-dot
- `LowerDotI-1.dc.html` · 2a-1 · Diamond i-dot
- `LowerDotI-2.dc.html` · 2a-2 · Caps descriptor
- `LowerDotI-3.dc.html` · 2a-3 · Jakarta, left-aligned
- `LowerInline.dc.html` · 2b · One line
- `LowerInline-1.dc.html` · 2b-1 · Mid-height square
- `LowerInline-2.dc.html` · 2b-2 · Caps after square
- `LowerInline-3.dc.html` · 2b-3 · Boxed name
- `LowerRound.dc.html` · 2c · Round dot
- `LowerRound-1.dc.html` · 2c-1 · Round i-dot
- `LowerRound-2.dc.html` · 2c-2 · Ring dot
- `LowerRound-3.dc.html` · 2c-3 · All lowercase
- `Final.dc.html` · Final · aımzone ventures
- `WeightsBelow.dc.html` · Ventures below · weights
- `WeightsSide.dc.html` · Ventures beside · weights
- `Icons.dc.html` · Icon options
- `Lockups.dc.html` · Lockups · ventures below & beside
- `Shortlist.dc.html` · Shortlist · A / B / C
- `PairA1.dc.html` · A + a + square · ventures below, bold
- `PairA2.dc.html` · A + az corner · ventures below, bold
- `PairB1.dc.html` · B + a + square · ventures beside, light
- `PairB2.dc.html` · B + az corner · ventures beside, light
- `PairC1.dc.html` · C + a + square · full-width capitals
- `PairC2.dc.html` · C + az corner · full-width capitals
- `IconsMatch.dc.html` · Icons built from the wordmark

## Brand identity (20)

- `Brand01-Cover.dc.html` · 01 · Cover
- `BrandIntro.dc.html` · 02 · Introduction
- `BrandContents.dc.html` · 03 · Contents
- `BrandDiv01.dc.html` · 04 · Section 01 · Logo
- `Brand02-Logo.dc.html` · 05 · Logo suite
- `Brand03-Space.dc.html` · 06 · Clear space & size
- `Brand04-Usage.dc.html` · 07 · Usage
- `BrandDiv02.dc.html` · 08 · Section 02 · Colour
- `Brand05-Colour.dc.html` · 09 · Colour
- `BrandDiv03.dc.html` · 10 · Section 03 · Typography
- `Brand06-Type.dc.html` · 11 · Typography
- `BrandDiv04.dc.html` · 12 · Section 04 · Graphic elements
- `Brand07-Square.dc.html` · 13 · The square
- `Brand12-Patterns.dc.html` · 14 · Patterns
- `BrandDiv05.dc.html` · 15 · Section 05 · Applications
- `Brand08-Cards.dc.html` · 16 · Business cards
- `Brand09-Letter.dc.html` · 17 · Letterhead
- `Brand10-Social.dc.html` · 18 · Social media
- `Brand11-Digital.dc.html` · 19 · Web & email
- `BrandBack.dc.html` · 20 · Back cover

## Website (26)

- `Web-Landing.dc.html` · Website · Home
- `Web-Services.dc.html` · Website · Services
- `Web-Company.dc.html` · Website · Company
- `Web-Connect.dc.html` · Website · Contact
- `Web-Privacy.dc.html` · Website · Privacy policy
- `Web-Terms.dc.html` · Website · Terms of service
- `Web-Disclaimer.dc.html` · Website · Disclaimer
- `Web-Careers.dc.html` · Website · Careers
- `Auth-Login.dc.html` · Log in
- `Auth-Forgot.dc.html` · Forgot password
- `Auth-OTP.dc.html` · Verify code
- `Auth-Reset.dc.html` · Reset password
- `Auth-Register.dc.html` · Register · individual
- `KYC-1-Personal.dc.html` · KYC individual · 1 personal details
- `KYC-2-Documents.dc.html` · KYC individual · 2 identity documents
- `KYC-I3-Income.dc.html` · KYC individual · 3 income & payout
- `KYC-I4-Review.dc.html` · KYC individual · 4 review & submit
- `KYC-3-Review.dc.html` · KYC individual · submitted
- `KYB-R-Register.dc.html` · Register · business
- `KYB-0-Start.dc.html` · KYC business · start
- `KYB-1-Details.dc.html` · KYC business · 1 business details
- `KYB-2-Directors.dc.html` · KYC business · 2 directors & owners
- `KYB-3-Documents.dc.html` · KYC business · 3 company documents
- `KYB-4-Financials.dc.html` · KYC business · 4 financials
- `KYB-5-Review.dc.html` · KYC business · 5 review & submit
- `KYB-6-Submitted.dc.html` · KYC business · submitted

## Mobile app (23)

- `App2-A1.dc.html` · Borrower · 1 Welcome
- `App-Client-Request.dc.html` · Borrower · 2 Amount
- `App2-A3.dc.html` · Borrower · 3 Verify
- `App2-A4.dc.html` · Borrower · 4 Offer
- `App2-A5.dc.html` · Borrower · 5 Funds sent
- `App-Client-Home.dc.html` · Borrower · Home
- `App-Finance-Collections.dc.html` · Partner · Portfolio
- `App2-B2.dc.html` · Partner · Opportunities
- `App2-B3.dc.html` · Partner · Invest
- `App2-B4.dc.html` · Partner · Statements
- `App2-C1.dc.html` · Trade · 1 Type
- `App2-C2.dc.html` · Trade · 2 Deal
- `App2-C3.dc.html` · Trade · 3 Documents
- `App2-C4.dc.html` · Trade · 4 Term sheet
- `App2-C5.dc.html` · Trade · 5 Tracking
- `App2-D1.dc.html` · Asset-backed · 1 Asset
- `App2-D2.dc.html` · Asset-backed · 2 Valuation
- `App2-D3.dc.html` · Asset-backed · 3 LTV
- `App2-D4.dc.html` · Asset-backed · 4 Offer
- `App2-D5.dc.html` · Asset-backed · 5 Schedule
- `App-Officer-Pipeline.dc.html` · Team · Officer pipeline
- `App-Credit-Review.dc.html` · Team · Credit decision
- `App-Admin-Overview.dc.html` · Team · Admin overview

## Social media (4)

- `Social-Profiles.dc.html` · LinkedIn · company page
- `Social-Posts.dc.html` · LinkedIn · posts
- `Social-Carousel.dc.html` · LinkedIn · document carousels
- `Social-Video.dc.html` · Short video storyboards

## Documents (23)

- `Doc-Agreement-Cover.dc.html` · Facility agreement · cover
- `Doc-Agreement-Terms.dc.html` · Facility agreement · key terms
- `Doc-Offer.dc.html` · Offer letter
- `Doc-Receipt.dc.html` · Payment receipt
- `Doc-Statement.dc.html` · Statement of account
- `Doc-Asset-Cover.dc.html` · Asset-backed agreement · cover
- `Doc-Asset-Terms.dc.html` · Asset-backed agreement · terms & security
- `Doc-Schedule.dc.html` · Borrower agreement · schedule & signatures
- `Doc-TF-Termsheet.dc.html` · Trade finance · term sheet
- `Doc-TF-Application.dc.html` · Trade finance · LC application
- `Doc-Portfolio-1.dc.html` · Partner portfolio report · summary
- `Doc-Portfolio-2.dc.html` · Partner portfolio report · holdings
- `Doc-Pitch-01.dc.html` · Investor pitch · 01 Cover
- `Doc-Pitch-02.dc.html` · Investor pitch · 02 Problem
- `Doc-Pitch-03.dc.html` · Investor pitch · 03 Solution
- `Doc-Pitch-04.dc.html` · Investor pitch · 04 Products
- `Doc-Pitch-05.dc.html` · Investor pitch · 05 Traction
- `Doc-Pitch-06.dc.html` · Investor pitch · 06 Model
- `Doc-Pitch-07.dc.html` · Investor pitch · 07 Market & team
- `Doc-Pitch-08.dc.html` · Investor pitch · 08 The ask
- `Doc-Profile-Cover.dc.html` · Company profile · cover
- `Doc-Profile-About.dc.html` · Company profile · who we are
- `Doc-Profile-Team.dc.html` · Company profile · leadership & partners

## Project branding (2)

- `Proj-Hoarding.dc.html` · Project site hoarding
- `Proj-Kit.dc.html` · Signboard & site kit

## Emails (3)

- `Email-reminder.dc.html` · Email · Repayment reminder
- `Email-received.dc.html` · Email · Payment received
- `Email-overdue.dc.html` · Email · Overdue notice

## Web app journeys (76)

- `WA-A1-ThankYou.dc.html` · Thank you · verified
- `WA-A2-Dashboard.dc.html` · Borrower dashboard · new
- `WA-B1-Product.dc.html` · Borrow · 1 product
- `WA-B2-Amount.dc.html` · Borrow · 2 amount
- `WA-B3-Payout.dc.html` · Borrow · 3 payout & security
- `WA-B4-Offer.dc.html` · Borrow · 4 offer
- `WA-B5-Sign.dc.html` · Borrow · 5 sign
- `WA-B6-Funded.dc.html` · Borrow · funds sent
- `WA-B7-Active.dc.html` · Borrower dashboard · active loan
- `WA-B8-Repay.dc.html` · Repay · M-Pesa prompt
- `WA-B9-Repaid.dc.html` · Repaid · facility closed
- `WA-H1-Rejected.dc.html` · Borrower · application not approved
- `WA-H2-Overdue.dc.html` · Borrower · payment late
- `WA-H3-Extension.dc.html` · Borrower · ask for more time (side sheet)
- `WA-H4-TopUp.dc.html` · Borrower · top-up (side sheet)
- `WA-H5-Plan.dc.html` · Borrower · payment plan offered
- `WA-H6-Recovery.dc.html` · Borrower · in recovery
- `WA-C1-Type.dc.html` · Partner KYC · 1 investor type
- `WA-C2-Identity.dc.html` · Partner KYC · 2 identity
- `WA-C3-Suitability.dc.html` · Partner KYC · 3 suitability
- `WA-C4-Payout.dc.html` · Partner KYC · 4 payout & tax
- `WA-C5-ThankYou.dc.html` · Partner KYC · thank you
- `WA-D1-Home.dc.html` · Partner dashboard · new
- `WA-D2-Fund.dc.html` · Invest · fund overview
- `WA-D3-Amount.dc.html` · Invest · 1 amount & tenor
- `WA-D4-Agreement.dc.html` · Invest · 2 subscription agreement
- `WA-D5-Fund.dc.html` · Invest · 3 fund your investment
- `WA-D6-Confirmed.dc.html` · Invest · confirmed
- `WA-D7-Portfolio.dc.html` · Partner dashboard · active
- `WA-D8-Withdraw.dc.html` · Partner · withdraw (side sheet)
- `WA-R-RO1.dc.html` · Loan officer · pipeline
- `WA-R-RO2.dc.html` · Loan officer · borrower 360
- `WA-R-RO3.dc.html` · Loan officer · new loan (side sheet)
- `WA-R-RO4.dc.html` · Loan officer · requests
- `WA-R-CR1.dc.html` · Manager · loan approvals
- `WA-R-CR2.dc.html` · Manager · credit assessment
- `WA-R-CR3.dc.html` · Manager · approve (side sheet)
- `WA-R-CR4.dc.html` · Manager · portfolio risk
- `WA-R-CO1.dc.html` · KYC & compliance · KYC queue
- `WA-R-CO2.dc.html` · KYC & compliance · document review
- `WA-R-CO3.dc.html` · KYC & compliance · business review
- `WA-R-CO4.dc.html` · KYC & compliance · AML screening
- `WA-R-TL1.dc.html` · Teller · disbursement queue
- `WA-R-TL2.dc.html` · Teller · disburse (side sheet)
- `WA-R-TL3.dc.html` · Teller · receive payment (side sheet)
- `WA-R-TL4.dc.html` · Teller · end of day
- `WA-R-CL1.dc.html` · Recovery officer · collections queue
- `WA-R-CL2.dc.html` · Recovery officer · case and contact
- `WA-R-CL3.dc.html` · Recovery officer · restructure (side sheet)
- `WA-R-CL4.dc.html` · Recovery officer · performance
- `WA-R-AC1.dc.html` · Accountant · reconciliation
- `WA-R-AC2.dc.html` · Accountant · ledger
- `WA-R-AC3.dc.html` · Accountant · reports
- `WA-R-AC4.dc.html` · Accountant · partner payouts
- `WA-R-AD1.dc.html` · Admin · overview
- `WA-R-AD2.dc.html` · Admin · users and roles
- `WA-R-AD3.dc.html` · Admin · loan products
- `WA-R-AD4.dc.html` · Admin · approval limits
- `WA-R-SA1.dc.html` · Super-admin · system health
- `WA-R-SA2.dc.html` · Super-admin · audit log
- `WA-R-SA3.dc.html` · Super-admin · security
- `WA-R-SA4.dc.html` · Super-admin · integrations and API keys
- `WA-R-AU1.dc.html` · Auditor · audit log (read-only)
- `WA-R-AU2.dc.html` · Auditor · end-of-day closes (read-only)
- `WA-R-BR1.dc.html` · Broker · clients and fees
- `WA-R-BR2.dc.html` · Broker · refer a borrower (side sheet)
- `WA-M1.dc.html` · Side sheet · reject loan
- `WA-M2.dc.html` · Side sheet · extend loan (rollover)
- `WA-M3.dc.html` · Side sheet · ask to write off
- `WA-M4.dc.html` · Side sheet · second sign-off
- `WA-M5.dc.html` · Side sheet · prepare partner payout
- `WA-M6.dc.html` · Side sheet · approve a teller's close
- `WA-M7.dc.html` · Dialog · reverse a repayment
- `WA-M8.dc.html` · Side sheet · edit current cycle
- `WA-G1-Alerts.dc.html` · Alerts · by journey
- `WA-G2-Channels.dc.html` · Alerts · channels

## Later · not in the app yet (33)

- `WA-E1-KYB.dc.html` · Trade · 1 business verification
- `WA-E2-Deal.dc.html` · Trade · 2 deal details
- `WA-E3-Docs.dc.html` · Trade · 3 trade documents
- `WA-E4-Terms.dc.html` · Trade · 4 term sheet
- `WA-E5-Overview.dc.html` · Trade dashboard
- `WA-F1-Supplier.dc.html` · Pay supplier · 1 add supplier
- `WA-F2-Payment.dc.html` · Pay supplier · 2 payment & FX
- `WA-F3-Authorise.dc.html` · Pay supplier · 3 authorise
- `WA-F4-Tracking.dc.html` · Supplier payment tracking
- `WA-F5-Repay.dc.html` · Trade · repay facility
- `WA-R-TF1.dc.html` · Trade finance officer · desk
- `WA-R-TF2.dc.html` · Trade finance officer · document check
- `WA-R-TF3.dc.html` · Trade finance officer · issue LC
- `WA-R-TF4.dc.html` · Trade finance officer · FX & settlements
- `WA-R-VA1.dc.html` · Valuation officer · requests
- `WA-R-VA2.dc.html` · Valuation officer · report
- `WA-R-VA3.dc.html` · Valuation officer · collateral register
- `WA-R-VA4.dc.html` · Valuation officer · insurance & tracking
- `AF-0-Checks.dc.html` · Asset finance · every check, by stage
- `AF-1-Desk.dc.html` · Asset finance · desk (teller)
- `AF-2-New.dc.html` · Asset finance · new application (side sheet)
- `AF-3-Checks.dc.html` · Asset finance · 1–3 KYC, IPRS, CRB (teller)
- `AF-4-Asset.dc.html` · Asset finance · 4 asset details (teller)
- `AF-4b-AssetChecks.dc.html` · Asset finance · 4b asset checks: NTSA, registry, valuation (teller)
- `AF-5-Cover.dc.html` · Asset finance · 5–6 insurance and tracker (teller)
- `AF-6-Calculator.dc.html` · Asset finance · 7 loan calculator (teller)
- `AF-6b-Amortization.dc.html` · Asset finance · 7b amortization table (teller)
- `AF-7-Release.dc.html` · Asset finance · 8 release form (teller)
- `AF-8-Review.dc.html` · Asset finance · 9 branch review (branch manager)
- `AF-9-Approve.dc.html` · Asset finance · 10 final approval (general manager / admin)
- `AF-10-Fleet.dc.html` · Asset finance · asset register and tracker map
- `AF-11-Borrower.dc.html` · Borrower · my bike (asset finance)
- `AF-12-Apply.dc.html` · Borrower · bike application status

## Gap screens (14)

- `Gap-ApprovalLimits.dc.html` · Admin · approval limits
- `Gap-EndOfDay.dc.html` · Manager · end-of-day approvals
- `Gap-Ledger.dc.html` · Accountant · ledger
- `Gap-PartnerMoney.dc.html` · Accountant · partner payouts
- `Gap-CaseDesk.dc.html` · Loan or recovery officer · case desk
- `Gap-BrokerFees.dc.html` · Broker · clients and fees
- `Gap-Alerts.dc.html` · Borrower · How we contact you
- `Gap-Health.dc.html` · Super-admin · system health
- `P-1-Portfolio.dc.html` · Partner · portfolio and returns (individual)
- `P-2-Payouts.dc.html` · Partner · payouts and distributions
- `P-3-Treasury.dc.html` · Partner · treasury overview (corporate)
- `S-1-Collections.dc.html` · Staff · six-group sidebar · Collections & Recovery tabs
- `S-2-FinOps.dc.html` · Staff · Financial Operations tabs
- `S-3-Map.dc.html` · Staff · sidebar consolidation map
