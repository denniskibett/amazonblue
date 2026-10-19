# Module E: businesses (business KYC)

*Stage 2, 19 October 2026. Built on branch `stage-2-ui` and tested on the clone `loans_wp_s2`.*

## Purpose

A borrower whose own KYC is approved can add a business to their account. Staff then verify it, the same way they verify individual KYC. Later work builds on this record:

- business loans
- trade finance (LC, FX, supplier payments)
- asset-backed lending
- compliance (AML screening)

Those four are Stage 4. For now they show only as placeholders. Individual borrowers work exactly as before: no loan, limit or wallet code was changed.

## Tables

There are three new tables. Each has its own migration: `2026_10_19_000011` to `000013`. The SQL twin is `database/sql/2026_10_19_businesses.sql`.

| Table | Key columns | Notes |
|---|---|---|
| `businesses` | `owner_user_id` → users (cascade); `legal_name`, `trading_name`, `registration_number` (indexed, not unique), `registration_type` (sole_proprietorship / partnership / limited_company / other), `kra_pin`, `industry`, `date_registered`; address (`address_line_1/2`, `city`, `county`, `postal_code`, `country`); `phone`, `email`, `website`; `monthly_revenue`, `employees`, `bank_name`, `bank_account`; `status` (draft / submitted / approved / rejected, indexed); `submitted_at`, `reviewed_by` → users (set null), `reviewed_at`, `review_notes`, `checks` JSON; `handle` (unique, nullable) | Soft deletes. Audited by `AuditObserver`. |
| `business_members` | `business_id` (cascade), `user_id` → users (nullable, set null), `full_name`, `role` (owner / director / shareholder / signatory), `id_number`, `kra_pin`, `phone`, `email`, `ownership_pct` decimal(5,2), `is_signatory`, `verified_at` | Directors, owners, shareholders and signatories. |
| `business_documents` | `business_id` (cascade), `type` (certificate / cr12 / kra_pin_certificate / bank_statement / business_permit / other), `path`, `original_name`, `mime`, `size`, `uploaded_by` → users (set null) | Files sit on the private disk in `storage/app/private/business_documents/{user id}_{random}.{ext}`. |

Status, registration type, role and document type are stored as strings and checked in the code, not as enums. That way Stage 4 can add values (for example a cooperative, or a trade-finance document type) without changing the schema.

**Models:**

- `App\Models\Business`, with `owner`, `reviewer`, `members` and `documents`, plus these rule helpers:
  - `requiredDocumentTypes`
  - `missingDocumentTypes`
  - `ownershipTotal`
  - `isEditable`
  - `isVisibleTo`
- `BusinessMember`
- `BusinessDocument`
- `User::businesses()`

## Routes

| Route | Name | Guard |
|---|---|---|
| `GET /account/business` (list), `/start`, `/new` (+POST) | `account.business.index/start/create/store` | `auth`, `role:borrower` |
| `GET /account/business/{business}` | `account.business.show` | owner only (404 otherwise) |
| `/{business}/details`, `/members` (+POST, `/members/next`, DELETE `/members/{member}`), `/documents`, `/financials`, `/review` (POST = submit), `/submitted` | `account.business.*` | owner only |
| `GET /files/businesses/{business}/{document}` | `files.business` | `auth` + `Business::isVisibleTo`: the owner, a member with a linked user, or a staff role with `view users` (super-admin always). Anyone else gets a 404. |
| `GET /business-reviews`, `/business-reviews/{business}` | `business-reviews.index/show` | `staff` + `permission:view users` |
| `POST /business-reviews/{business}/approve`, `/reject` | `business-reviews.approve/reject` | `permission:edit users` |

**Code:**

- `app/Http/Controllers/Concerns/BusinessOnboarding.php`, a trait used by `HomeController`. Every method name starts with `business…`.
- `app/Http/Controllers/BusinessReviewController.php`
- `PrivateFileController@business`
- `CustomerNotifier::businessSubmitted` and `CustomerNotifier::businessReviewed`
- `App\Notifications\BusinessSubmitted` and `App\Notifications\BusinessReviewed`
- `Handle::forBusiness`

**Views:**

- `resources/views/home/business/*`: the KYC-style layout, the steps, the account list and the account detail page.
- `resources/views/business-reviews/*`: TailAdmin, using `x-staff.*`.
- `public/assets/css/business.css`: loaded only on the business pages.

## Rules

1. **Eligibility.** Only a borrower with `kyc_status = approved` can create a business. Anyone else sees the start page, which explains why and links to KYC (or to the account page while their own KYC is under review).
2. **Owner auto-added.** At step 1 the applicant becomes a member:
   - `user_id` is set and the member is verified.
   - Name, ID, KRA PIN, phone and email come from their KYC.
   - Their role is chosen on the form and can be edited later.
   - Ownership is 100% for a sole proprietorship and 0% otherwise.
   - They are a signatory.
   - They can't be removed.
3. **Members.** Each person needs a name, an ID or passport number (unique within the business), a role and an ownership %. KRA PIN, phone and email are optional.
   - Total ownership must not exceed 100%. This is checked on every save.
   - To finish the step, ownership must total more than 0%, at least one person must be a signatory, and a limited company needs a director.
   - A member whose ID number matches a user with approved KYC is linked (`user_id`, `verified_at`). That user can then see the business files.
4. **Documents.** Files can be JPG, PNG, WEBP, HEIC or PDF, up to 10 MB each. There is one file per type, and a new upload replaces the old file and deletes it.
   - Required for every business: the registration certificate and the KRA PIN certificate.
   - Required for a limited company: the CR12.
   - Optional: bank statements, the business permit and other documents.
   - Uploads are saved first. Any missing required types are then reported as errors.
   - Pasting with Ctrl+V and the blur, dark and small-photo check work the same way as on the individual KYC documents step.
5. **Financials.** Monthly revenue, employees, bank (from the `Banks` list or "Other") and account number are all required.
6. **Submitting** needs 3 declarations and every step complete. It sets `status = submitted` and `submitted_at`, and clears any previous decision. The previous decision stays in the audit log. Emails go to the borrower and to the admins.
7. **Locking** follows the same pattern as individual KYC:
   - While a business is `submitted` or `approved`, nothing can be changed. Pages show read-only and POST requests are refused with an error.
   - A `rejected` business reopens until it is submitted again.
8. **Approving** needs all 4 checks in `Business::CHECKS` (registration/BRS, KRA PIN, members against the CR12, applicant called) and no missing required documents. It also:
   - sets `reviewed_by` and `reviewed_at`, the notes and `checks`
   - gives the business a wallet `handle` (from the trading or legal name, unique across users, partners and businesses)
   - marks linked members as verified
   - emails the borrower
9. **Rejecting** needs a note of at least 10 characters, which the borrower sees. The ticked checks are stored, and the borrower gets an email.
10. **Duplicates.** If another business has the same registration number, the review page warns about it. Approval is not blocked.

## Roles

| Role | Can |
|---|---|
| Borrower (KYC approved) | Add businesses, fill in and submit them, see their own businesses and files |
| Borrower (not approved) | Only see the start page with the explanation |
| Member with a linked account | Open the business documents through `files.business`. They have no account pages for the business yet. |
| Staff with `view users` | See the list, the details and the documents |
| Staff with `edit users` | Approve or reject |

## Flow

```
/account (business card) -> start -> 1 details (creates draft) -> 2 directors & owners -> 3 documents -> 4 financials
  -> 5 review + declarations -> submitted (borrower + admin emails)
staff: Business reviews (sidebar, badge = submitted count) -> detail -> approve (4 checks) | reject (note)
rejected -> borrower sees note on /account and the business page -> fixes -> submits again
```

## Stage 4 dependencies (placeholders in place)

| Placeholder | Where | Depends on |
|---|---|---|
| Business loans | `/account/business/{id}` | Business loan products, limits and pricing |
| Trade finance | `/account/business/{id}` | Trade finance: LC, FX, supplier payments |
| Asset-backed loans | `/account/business/{id}` | Asset-backed lending and the valuation officer |
| AML screening | Staff review detail | An AML, sanctions and PEP screening provider |
| Business limit | Staff review detail | Business loan products, limits and trade finance |

Not built yet:

- Inviting directors to verify themselves through a secure link.
- Business wallets: the handle is reserved, but no wallet is created.
- Maker-checker approval.
- Automatic BRS or KRA lookups.

## Open owner decisions

1. Should a registration number be unique across businesses? Today two people can add the same company; staff get a warning.
2. Ownership threshold: should every holder of 10% or more be required, and must shareholders total exactly 100% for limited companies?
3. Are bank statements required (today optional), and how many months?
4. Do non-applicant directors and shareholders have to complete their own KYC before approval? The design shows "Can't approve yet: 2 people still need to finish KYC".
5. Who may approve: is `edit users` enough, or should there be a compliance-officer role or a second approver?
6. Can an approved business be edited? Today the borrower has to contact support.
