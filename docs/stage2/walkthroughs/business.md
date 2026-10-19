# Walkthrough: business KYC (journey A)

**Setup**

- **Server:** `DB_DATABASE=loans_wp_s2 php artisan serve --port=8791`.
- **Screenshots:** the GET-only pages can be retaken with `BASE=http://127.0.0.1:8791 OUT=docs/stage2/screens/business scripts/walkthrough.sh <login> name=/path`. The shots below were taken in one logged-in curl session per person, using the same Chrome and `sips` steps as the harness. That keeps flashed validation errors, which a fresh login would lose.
- **Shots:** each has a `-desktop.png` (1440 wide) and a `-phone.png` (390 wide).
- **Reference:** rules, routes and Stage 4 dependencies are in [E-business.md](../../audit/modules/E-business.md).

**Data**

- `demo:seed` now adds **Rift Agro Traders Ltd** (KYB-000001) for active@ (Amina Wanjiru). It is submitted, with 3 people and 3 tiny demo PDFs.
- The other businesses in the shots were made through the screens on `loans_wp_s2`:
  - KYB-000002, Otieno Logistics (overdue@)
  - KYB-000003, Muthoni Fresh Produce (rejected@, Grace Muthoni, whose individual KYC is approved)
- Screens for the borrower are in `screens/business/borrower/`. They were all taken as **rejected@demo.aimzone.test**.

## Borrower, normal path (rejected@, business KYB-000003)

1. **Account home.** A new "Your business" card at the bottom shows "Add a business". [desktop](../screens/business/borrower/01-account-home-desktop.png) · [phone](../screens/business/borrower/01-account-home-phone.png)
2. **Start** (`/account/business/start`). It shows "You're verified" and what to have ready. [desktop](../screens/business/borrower/02-start-desktop.png) · [phone](../screens/business/borrower/02-start-phone.png)
3. **1 Business details** (`/account/business/new`). The fields are type chips, registered and trading name, registration number, KRA PIN, date, industry, your role, address and contact details. Saving creates the draft and adds the applicant as a verified director. [desktop](../screens/business/borrower/03-details-desktop.png) · [phone](../screens/business/borrower/03-details-phone.png)
4. **2 Directors & owners.** It starts with the applicant at 0%; the add form is open. [desktop](../screens/business/borrower/04-members-start-desktop.png) · [phone](../screens/business/borrower/04-members-start-phone.png)
   - After you set your own share to 55% and add Joseph Kariuki at 45%: [desktop](../screens/business/borrower/05b-members-done-desktop.png) · [phone](../screens/business/borrower/05b-members-done-phone.png)
5. **3 Company documents.** Upload rows for the certificate, CR12 (limited companies), KRA PIN certificate, and the optional statements, permit and other documents. The Ctrl+V paste and blur check work here. The shot was retaken after approval, so the rows show "Saved" and are locked. [desktop](../screens/business/borrower/07-documents-done-desktop.png) · [phone](../screens/business/borrower/07-documents-done-phone.png)
6. **4 Financials.** Revenue, employees and the bank (from the list, or "Other" with a name). [desktop](../screens/business/borrower/08-financials-desktop.png) · [phone](../screens/business/borrower/08-financials-phone.png)
7. **5 Review & submit.** Four sections, all ticked, and three declarations. [desktop](../screens/business/borrower/09-review-desktop.png) · [phone](../screens/business/borrower/09-review-phone.png)
8. **Submitted.** Shows reference KYB-000003. The borrower got the "We have your business details" email, and admins got "New business to review" (MAIL_MAILER=log). [desktop](../screens/business/borrower/10-submitted-desktop.png) · [phone](../screens/business/borrower/10-submitted-phone.png)
9. **Business page while under review.** Details, people, and the three Stage 4 placeholders (business loans, trade finance, asset-backed). [desktop](../screens/business/borrower/11-business-under-review-desktop.png) · [phone](../screens/business/borrower/11-business-under-review-phone.png)

## Staff (admin@demo.aimzone.test)

1. **Business reviews list.** The sidebar item sits under KYC Reviews and shows a badge with the number waiting. Tabs: Waiting / Approved / Rejected / In progress. [desktop](../screens/business/admin/01-reviews-list-desktop.png) · [phone](../screens/business/admin/01-reviews-list-phone.png)
2. **Detail: Approve pressed with one check ticked.** Shows "Tick every check before approving." The page covers:
   - business details
   - directors and owners table, with KYC status and ownership bars
   - document previews
   - applicant card
   - four checks
   - AML screening and business limit placeholders

   [desktop](../screens/business/admin/02-review-detail-approve-error-desktop.png) · [phone](../screens/business/admin/02-review-detail-approve-error-phone.png)

   In the shots, image previews appear broken. Headless Chrome has no session, so the authorised `files.business` route sends it to login. A logged-in browser shows the image, and PDFs open in a new tab.
3. **Demo business with PDF documents** (KYB-000001). [desktop](../screens/business/admin/03-review-detail-demo-pdfs-desktop.png) · [phone](../screens/business/admin/03-review-detail-demo-pdfs-phone.png)
4. **Rejected with a note**, then the Rejected tab. [desktop](../screens/business/admin/04-reviews-rejected-tab-desktop.png) · [phone](../screens/business/admin/04-reviews-rejected-tab-phone.png)
5. **Resubmitted business, then approved.** [desktop](../screens/business/admin/05-review-detail-resubmitted-desktop.png) · [phone](../screens/business/admin/05-review-detail-resubmitted-phone.png)
   - Confirmation: [desktop](../screens/business/admin/06-approved-confirmation-desktop.png) · [phone](../screens/business/admin/06-approved-confirmation-phone.png)
   - Outcome card with the ticked checks, the reviewer and the new handle @muthonifreshproduceltd: [desktop](../screens/business/admin/07-review-approved-outcome-desktop.png) · [phone](../screens/business/admin/07-review-approved-outcome-phone.png)

The audit log has one row for each change: create, each step save, submit, reject, resubmit, approve, and the handle.

## Edge cases

| Case | Login | Shots |
|---|---|---|
| Individual KYC not approved. The start page explains why and links to "Verify myself"; `/account/business/new` redirects back to start. | new@ | [desktop](../screens/business/new/01-start-kyc-not-approved-desktop.png) · [phone](../screens/business/new/01-start-kyc-not-approved-phone.png) |
| Ownership over 100%. Adding Ann Njeri at 10% when 100% is already listed shows "Ownership would add up to 110%." | rejected@ | [desktop](../screens/business/borrower/05-members-over-100-desktop.png) · [phone](../screens/business/borrower/05-members-over-100-phone.png) |
| Missing documents. Only the certificate is uploaded: "2 documents are still missing", with an error on the CR12 and KRA PIN rows. The certificate is kept. | rejected@ | [desktop](../screens/business/borrower/06-documents-missing-desktop.png) · [phone](../screens/business/borrower/06-documents-missing-phone.png) |
| Rejected: account home. The business card shows "Needs attention". | rejected@ | [desktop](../screens/business/borrower/12-account-home-rejected-desktop.png) · [phone](../screens/business/borrower/12-account-home-rejected-phone.png) |
| Rejected: business page with the reviewer's note and "Update and resubmit". | rejected@ | [desktop](../screens/business/borrower/13-business-rejected-desktop.png) · [phone](../screens/business/borrower/13-business-rejected-phone.png) |
| Rejected: review page with "What our team asked for" and "Send again for review". | rejected@ | [desktop](../screens/business/borrower/14-review-after-reject-desktop.png) · [phone](../screens/business/borrower/14-review-after-reject-phone.png) |
| Approved: "Verified", the handle, and placeholders for business products. | rejected@ | [desktop](../screens/business/borrower/15-business-approved-desktop.png) · [phone](../screens/business/borrower/15-business-approved-phone.png) |
| Approved: details are locked (read-only, "Verified: details are locked"). | rejected@ | [desktop](../screens/business/borrower/16-details-locked-desktop.png) · [phone](../screens/business/borrower/16-details-locked-phone.png) |

**Checked by hand with curl, no screenshots:**

- An invalid KRA PIN shows field errors.
- "Other" bank with no name shows "Type the name of the bank."
- Saving while a business is submitted is refused ("with our team for review").
- The applicant can't be removed.
- Another borrower gets a 404 on both the business page and its files.
- A teller (no `view users`) gets a 404 on the files and a 403 on `/business-reviews`.
- The owner and the admin can download the files.
