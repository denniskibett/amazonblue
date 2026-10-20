<?php

namespace App\Support;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Branded emails: the 21 designs in resources/design/emails, as specs rendered by emails.branded (HTML) and
 * emails.branded-text (plain text). A notification's toMail() returns Emails::mail(Emails::kycApproved(...)).
 *
 * Each builder takes plain values (already formatted, e.g. 'KES 5,000.00') and returns a spec; see the view for keys.
 * Wired today: verify email, password reset, KYC submitted / approved / needs update, business submitted / reviewed,
 * loan application received, staff alerts. The rest (offer, funds sent, reminders, overdue, closed, partners,
 * trade finance) are ready for the Phase 6 notifications. Preview them all at /system/emails (super-admin).
 */
class Emails
{
    public static function mail(array $m): MailMessage
    {
        return (new MailMessage)->subject($m['subject'])->view(['html' => 'emails.branded', 'text' => 'emails.branded-text'], ['m' => $m]);
    }

    public static function firstName(?string $name): string
    {
        return strtok(trim((string) $name), ' ') ?: 'there';
    }

    // ---------------------------------------------------------------- account

    /** 01: welcome (individual). */
    public static function welcome(string $firstName): array
    {
        return [
            'subject' => 'Welcome to AIMZONE Ventures',
            'preheader' => 'Your account is ready. Verify your identity to start borrowing.',
            'badge' => ['Account created', 'info'],
            'heading' => 'Welcome, ' . $firstName,
            'paragraphs' => ['Your account is ready. One short step left: verify your identity so you can borrow. It takes about 5 minutes.'],
            'steps' => [['done', 'Create your account', 'Done'], ['now', 'Verify your identity', 'Your details, ID or passport, photo, selfies and signature'], ['todo', 'Borrow', 'We review every application by hand']],
            'buttons' => [['Verify my identity', route('kyc.personal'), true]],
        ];
    }

    /** 02: welcome (business). */
    public static function welcomeBusiness(string $firstName, string $businessName): array
    {
        return [
            'subject' => "Welcome — let's add your business",
            'preheader' => 'Verify yourself, then your business, to unlock trade finance.',
            'badge' => ['Business account', 'info'],
            'heading' => 'Welcome, ' . $firstName,
            'paragraphs' => ["You've started a business account for {$businessName}. Every business is linked to a verified individual, so you'll verify yourself first, then the business."],
            'steps' => [['done', 'Create your account', 'Done'], ['now', 'Verify yourself', 'About 5 minutes'], ['todo', 'Verify ' . $businessName, 'Company documents and directors']],
            'buttons' => [['Continue', route('account.business.index'), true]],
        ];
    }

    /** 03 (link form): confirm the email address after sign-up. The design's 6-digit code becomes a button. */
    public static function verifyEmail(string $firstName, string $url): array
    {
        return [
            'subject' => 'Confirm your email for AIMZONE Ventures',
            'preheader' => 'One click to confirm it is you. The link works for 60 minutes.',
            'badge' => ['Confirm email', 'info'],
            'heading' => "Confirm it's you, {$firstName}",
            'paragraphs' => ['Press the button to confirm your email address. You need this before you send your verification details. The link works for 60 minutes.'],
            'buttons' => [['Confirm my email', $url, true]],
            'note' => "Didn't create an account? You can ignore this email.",
        ];
    }

    /** 03: one-time code (for future OTP use). */
    public static function code(string $code, int $minutes = 5): array
    {
        return [
            'subject' => 'Your AIMZONE code: ' . $code,
            'preheader' => "Use this code to confirm it's you. It expires in {$minutes} minutes.",
            'badge' => ['Security code', 'info'],
            'heading' => "Confirm it's you",
            'paragraphs' => ["Enter this code to continue. It expires in {$minutes} minutes."],
            'code' => $code,
            'note' => "Didn't ask for this? You can ignore this email. Your account is safe.",
        ];
    }

    /** 04: password reset (link). */
    public static function passwordReset(string $url, int $minutes): array
    {
        return [
            'subject' => 'Reset your AIMZONE password',
            'preheader' => 'Use the button to set a new password.',
            'badge' => ['Password reset', 'warn'],
            'heading' => 'Reset your password',
            'paragraphs' => ["We received a request to reset your password. Use the button within {$minutes} minutes."],
            'buttons' => [['Set a new password', $url, true]],
            'note' => "If you didn't ask for this, ignore this email and your password won't change.",
        ];
    }

    /** 05: new sign-in. */
    public static function newSignIn(string $device, string $location, string $time): array
    {
        return [
            'subject' => 'New sign-in to your account',
            'preheader' => 'We noticed a sign-in from a new device.',
            'badge' => ['New sign-in', 'warn'],
            'heading' => 'New sign-in to your account',
            'paragraphs' => ["We noticed a sign-in from a device we haven't seen before. If this was you, there's nothing to do."],
            'panel' => ['rows' => [['Device', $device], ['Location', $location], ['Time', $time]]],
            'buttons' => [['Secure my account', route('password.request'), true]],
            'note' => 'Not you? Reset your password now and call us on +254 701 607 959.',
        ];
    }

    // ---------------------------------------------------------------- KYC / KYB

    /** 06: KYC received. */
    public static function kycSubmitted(string $firstName, string $reference, string $date): array
    {
        return [
            'subject' => "We've received your details ({$reference})",
            'preheader' => 'Our team checks them and calls your next of kin and guarantor, usually within one working day.',
            'badge' => ['In review', 'review'],
            'heading' => "Thanks, {$firstName}. We're reviewing your details.",
            'paragraphs' => ['Our team checks your documents and calls your next of kin and guarantor, usually within one working day. We will email you as soon as it is done.'],
            'panel' => ['rows' => [['Reference', $reference], ['Submitted', $date], ['Status', 'In review', true]]],
            'buttons' => [['View my account', route('account.home'), false]],
        ];
    }

    /** 07: KYC approved. */
    public static function kycApproved(string $firstName): array
    {
        return [
            'subject' => "You're verified",
            'preheader' => 'You can now apply for a loan.',
            'badge' => ['Verified', 'ok'],
            'heading' => "You're verified, {$firstName}",
            'paragraphs' => ['Your identity is confirmed. You can now apply for a loan from your account.'],
            'panel' => ['rows' => [['Decisions', 'Reviewed by our team'], ['Paid to', 'M-Pesa']]],
            'buttons' => [['Apply for a loan', route('account.borrow'), true], ['Add a business', route('account.business.index'), false]],
        ];
    }

    /** 08: KYC needs an update (rejected with a note). */
    public static function kycActionNeeded(string $firstName, string $note, ?string $url = null, string $what = 'your details'): array
    {
        return [
            'subject' => 'Action needed: please update ' . $what,
            'preheader' => 'We could not finish your verification yet.',
            'badge' => ['Action needed', 'warn'],
            'heading' => "We need a quick update, {$firstName}",
            'paragraphs' => ["We could not verify {$what} yet. Here is what our team asked for:", '“' . $note . '”'],
            'buttons' => [['Update ' . $what, $url ?? route('kyc.review'), true]],
            'note' => 'Tips: use good light, keep all four corners of your ID in frame and avoid glare. Your passport photo should be on a plain light background.',
        ];
    }

    /** 09: business submitted. */
    public static function kybSubmitted(string $businessName, string $reference, string $url): array
    {
        return [
            'subject' => "{$businessName} is under review ({$reference})",
            'preheader' => 'We will email you as soon as the review is done.',
            'badge' => ['In review', 'review'],
            'heading' => "{$businessName} is under review",
            'paragraphs' => ['Thanks for sending your business details. Our team checks the documents and may call you to confirm, usually within two working days.'],
            'panel' => ['rows' => [['Reference', $reference], ['Status', 'In review', true]]],
            'buttons' => [['View my business', $url, false]],
        ];
    }

    /** 10: director invite. */
    public static function directorInvite(string $ownerName, string $role, string $businessName, string $url): array
    {
        return [
            'subject' => "{$ownerName} added you as a {$role} of {$businessName}",
            'preheader' => 'Please verify your identity. It takes about 5 minutes.',
            'badge' => ['Verification request', 'info'],
            'heading' => 'Please verify your identity',
            'paragraphs' => ["{$ownerName} added you as a {$role} of {$businessName} on AIMZONE Ventures. Kenyan law requires us to verify every director and owner of 10% or more."],
            'buttons' => [['Verify my identity', $url, true]],
            'note' => "You'll upload your own ID through a secure link. {$ownerName} won't see your documents.",
        ];
    }

    /** 11: business approved. */
    public static function kybApproved(string $businessName, string $url): array
    {
        return [
            'subject' => "{$businessName} is verified",
            'preheader' => 'Business loans and trade finance will appear in your account as they open.',
            'badge' => ['Business verified', 'ok'],
            'heading' => "{$businessName} is verified",
            'paragraphs' => ["Good news: {$businessName} is verified and linked to your account. Business loans and trade finance will appear in your account as soon as they open."],
            'buttons' => [['View my business', $url, true]],
        ];
    }

    // ---------------------------------------------------------------- borrowing

    /** Loan application received (06 pattern). */
    public static function applicationReceived(string $firstName, string $amount, string $reference, string $url): array
    {
        return [
            'subject' => "Application received: {$reference}",
            'preheader' => 'Our team reviews it shortly.',
            'badge' => ['In review', 'review'],
            'heading' => "Thanks, {$firstName}. We have your application.",
            'paragraphs' => ['Our team will review it shortly. Once approved, the money is sent to your M-Pesa and we will let you know.'],
            'panel' => ['label' => 'Amount requested', 'amount' => $amount, 'rows' => [['Reference', $reference], ['Status', 'In review', true]]],
            'buttons' => [['View my loan', $url, false]],
        ];
    }

    /** 12: offer ready. */
    public static function offerReady(string $firstName, string $amount, string $product, string $rate, string $total, string $dueDate, string $expiry, string $url): array
    {
        return [
            'subject' => "Your offer is ready: {$amount}",
            'preheader' => "Valid until {$expiry}.",
            'badge' => ['Approved', 'ok'],
            'heading' => "Your offer is ready, {$firstName}",
            'paragraphs' => ['Your application was approved. Review and sign to receive your funds.'],
            'panel' => ['label' => 'Approved amount', 'amount' => $amount, 'rows' => [['Product', $product], ['Rate', $rate], ['Total repayable', $total, true], ['Repay by', $dueDate], ['Offer valid until', $expiry]]],
            'buttons' => [['Review & sign', $url, true]],
        ];
    }

    /** 13: funds sent. */
    public static function fundsSent(string $amount, string $payoutMethod, string $reference, string $time, string $dueDate, string $total, string $url): array
    {
        return [
            'subject' => "{$amount} is on its way",
            'preheader' => "Sent to your {$payoutMethod}.",
            'badge' => ['Funds sent', 'ok'],
            'heading' => 'Your funds are on the way',
            'paragraphs' => ["We've sent {$amount} to your {$payoutMethod}. It usually arrives within a minute."],
            'panel' => ['label' => 'Amount sent', 'amount' => $amount, 'rows' => [['Reference', $reference], ['Sent', $time], ['Repay by', $dueDate], ['Total repayable', $total, true]]],
            'buttons' => [['View my loan', $url, false]],
        ];
    }

    /** How to pay, for 14 and 16. */
    private static function payBy(string $reference): array
    {
        $paybill = config('lending.mpesa.paybill');

        return array_values(array_filter([
            $paybill ? ['Pay by M-Pesa:', "Paybill {$paybill}, account {$reference}"] : ['Pay by M-Pesa:', 'see the Repay page in your account'],
        ]));
    }

    /** 14: repayment reminder. */
    public static function repaymentReminder(string $firstName, string $amount, string $product, string $dueDate, string $reference, int $days, string $payUrl, string $loanUrl): array
    {
        return [
            'subject' => "Your repayment is due in {$days} " . ($days === 1 ? 'day' : 'days'),
            'preheader' => "{$amount} is due on {$dueDate}.",
            'badge' => ['Due in ' . $days . ' ' . ($days === 1 ? 'day' : 'days'), 'warn'],
            'heading' => "A friendly reminder, {$firstName}",
            'paragraphs' => ['Your next repayment is coming up. Paying on time keeps your loan in good standing and your limit ready for next time.'],
            'panel' => ['label' => 'Amount due', 'amount' => $amount, 'rows' => [['Loan', $product], ['Due date', $dueDate], ['Reference', $reference]]],
            'info' => [...self::payBy($reference), ['', "Need more time? Talk to us before the due date and we'll work it out together."]],
            'buttons' => [['Pay now', $payUrl, true], ['View my loan', $loanUrl, false]],
        ];
    }

    /** 15: payment received. */
    public static function paymentReceived(string $firstName, string $amount, string $date, string $method, string $transaction, string $url): array
    {
        return [
            'subject' => 'Payment received — thank you',
            'preheader' => "We've received {$amount}.",
            'badge' => ['Payment received', 'ok'],
            'heading' => "Thank you, {$firstName} — we've got it",
            'paragraphs' => ['Your payment has been received and applied to your loan. Here is your receipt.'],
            'panel' => ['label' => 'Amount received', 'amount' => $amount, 'rows' => [['Date', $date], ['Method', $method], ['Transaction ID', $transaction]]],
            'buttons' => [['View receipt', $url, false]],
        ];
    }

    /** 16: overdue. */
    public static function paymentOverdue(string $amount, string $product, string $dueDate, string $reference, int $days, string $payUrl): array
    {
        return [
            'subject' => 'Your repayment is overdue',
            'preheader' => "{$amount} was due on {$dueDate}.",
            'badge' => [$days . ' ' . ($days === 1 ? 'day' : 'days') . ' overdue', 'danger'],
            'heading' => "Let's get your account back on track",
            'paragraphs' => ["We haven't received your repayment that was due on {$dueDate}. We understand things happen — please pay as soon as you can, or reach out so we can find a way forward together."],
            'panel' => ['label' => 'Amount overdue', 'amount' => $amount, 'rows' => [['Loan', $product], ['Original due date', $dueDate], ['Reference', $reference]]],
            'info' => self::payBy($reference),
            'buttons' => [['Pay now', $payUrl, true], ['Request a call', 'tel:+254701607959', false]],
        ];
    }

    /** 17: loan closed. */
    public static function facilityClosed(string $firstName, string $reference, string $total, string $date, bool $onTime, string $url): array
    {
        return [
            'subject' => 'All paid — thank you',
            'preheader' => "Loan {$reference} is fully repaid.",
            'badge' => ['Repaid', 'ok'],
            'heading' => "All paid. Thank you, {$firstName}.",
            'paragraphs' => ["Your loan {$reference} is fully repaid and closed."],
            'panel' => ['rows' => [['Total repaid', $total, true], ['Closed', $date], ['On time', $onTime ? 'Yes' : 'No']]],
            'buttons' => [['Borrow again', route('account.borrow'), true], ['View my loan', $url, false]],
        ];
    }

    // ---------------------------------------------------------------- trade finance

    /** 18: LC issued. */
    public static function lcIssued(string $lcRef, string $supplier, string $bank, string $latestShipment, string $expiry, string $deposit, string $url): array
    {
        return [
            'subject' => "LC {$lcRef} issued to {$supplier}",
            'preheader' => "Your letter of credit has been sent to your supplier's bank.",
            'badge' => ['LC issued', 'ok'],
            'heading' => 'Your letter of credit is issued',
            'paragraphs' => ["We've issued LC {$lcRef} through {$bank} in favour of {$supplier}. Share the reference with your supplier so they can ship."],
            'panel' => ['rows' => [['Supplier', $supplier], ['Latest shipment', $latestShipment], ['Expiry', $expiry], ['Your deposit', $deposit]]],
            'buttons' => [['View deal', $url, true]],
        ];
    }

    /** 19: supplier paid. */
    public static function supplierPaid(string $amount, string $supplier, string $approvedAt, string $sentAt, string $url): array
    {
        return [
            'subject' => "{$amount} sent to {$supplier}",
            'preheader' => 'Your supplier payment is on its way.',
            'badge' => ['Supplier paid', 'ok'],
            'heading' => 'Your supplier payment is on its way',
            'paragraphs' => ["We've sent {$amount} to {$supplier} by SWIFT. You can share the payment confirmation (MT103) with them."],
            'steps' => [['done', 'Approved', $approvedAt], ['done', 'Sent via SWIFT', $sentAt], ['now', "Received by supplier's bank", 'Usually 1–2 business days'], ['todo', 'Credited to supplier', "We'll confirm"]],
            'buttons' => [['Download MT103', $url, false]],
        ];
    }

    // ---------------------------------------------------------------- partners

    /** 20: investment confirmed. */
    public static function investmentConfirmed(string $firstName, string $amount, string $reference, string $tenor, string $maturity, string $targetReturn): array
    {
        return [
            'subject' => "You're invested: {$reference}",
            'preheader' => "We received {$amount}.",
            'badge' => ['Invested', 'ok'],
            'heading' => "You're invested, {$firstName}",
            'paragraphs' => ["We've received your funds and your subscription is active."],
            'panel' => ['label' => 'Amount invested', 'amount' => $amount, 'rows' => [['Subscription', $reference], ['Tenor', $tenor], ['Matures', $maturity], ['Target return', $targetReturn]]],
            'buttons' => [['View portfolio', route('account.partner.home'), false]],
            'note' => 'Private credit carries risk, including loss of capital. Target returns are not guaranteed.',
        ];
    }

    /** 21: payout sent. */
    public static function payoutSent(string $netAmount, string $reference, string $gross, string $wht, string $paidTo): array
    {
        return [
            'subject' => "Payout sent: {$netAmount}",
            'preheader' => "Paid to {$paidTo}.",
            'badge' => ['Payout sent', 'ok'],
            'heading' => 'Your payout is on its way',
            'paragraphs' => ["We've paid your distribution for {$reference} to {$paidTo}."],
            'panel' => ['label' => 'Net amount', 'amount' => $netAmount, 'rows' => [['Gross', $gross], ['Withholding tax', $wht], ['Paid to', $paidTo]]],
            'buttons' => [['View statement', route('account.statement'), false]],
        ];
    }

    // ---------------------------------------------------------------- staff

    /** Internal alert to staff (same look, no customer footer wording change). */
    public static function staffAlert(string $subject, string $line, string $actionText, string $actionUrl): array
    {
        return [
            'subject' => $subject,
            'preheader' => $line,
            'badge' => ['Staff alert', 'info'],
            'heading' => $subject,
            'paragraphs' => [$line],
            'buttons' => [[$actionText, $actionUrl, true]],
        ];
    }

    /** Every template with sample values, for the preview page and tests. */
    public static function samples(): array
    {
        $loan = url('/account/loans/1');

        return [
            '01 Welcome' => self::welcome('Mary'),
            '02 Welcome (business)' => self::welcomeBusiness('Mary', 'Wanjiru Traders Ltd'),
            '03 Confirm email' => self::verifyEmail('Mary', url('/verify-email/1/sample')),
            '03 Code' => self::code('481207'),
            '04 Password reset' => self::passwordReset(url('/reset-password/sample'), 60),
            '05 New sign-in' => self::newSignIn('Chrome on Android', 'Nairobi, Kenya', '20 Oct 2026, 14:10'),
            '06 KYC submitted' => self::kycSubmitted('Mary', 'KYC-00042', '20 Oct 2026'),
            '07 KYC approved' => self::kycApproved('Mary'),
            '08 KYC action needed' => self::kycActionNeeded('Mary', 'The back of your ID is blurry. Please retake it in good light.'),
            '09 Business submitted' => self::kybSubmitted('Wanjiru Traders Ltd', 'KYB-00007', url('/account/business/1')),
            '10 Director invite' => self::directorInvite('Mary Wanjiru', 'director', 'Wanjiru Traders Ltd', url('/register')),
            '11 Business approved' => self::kybApproved('Wanjiru Traders Ltd', url('/account/business/1')),
            'Application received' => self::applicationReceived('Mary', 'KES 5,000.00', 'Loan #00551', $loan),
            '12 Offer ready' => self::offerReady('Mary', 'KES 5,000.00', 'Emergency 10 Days', '20% for 10 days', 'KES 6,000.00', '30 Oct 2026', '21 Oct 2026', $loan),
            '13 Funds sent' => self::fundsSent('KES 5,000.00', 'M-Pesa 0712 ••• 678', 'Loan #00551', '20 Oct 2026, 14:10', '30 Oct 2026', 'KES 6,000.00', $loan),
            '14 Repayment reminder' => self::repaymentReminder('Mary', 'KES 6,000.00', 'Emergency 10 Days', '30 Oct 2026', 'Loan #00551', 3, url('/account/loans/1/repay'), $loan),
            '15 Payment received' => self::paymentReceived('Mary', 'KES 6,000.00', '29 Oct 2026', 'M-Pesa', 'UJ7CQ9KQCR', $loan),
            '16 Overdue' => self::paymentOverdue('KES 6,600.00', 'Emergency 10 Days', '30 Oct 2026', 'Loan #00551', 1, url('/account/loans/1/repay')),
            '17 Loan closed' => self::facilityClosed('Mary', 'Loan #00551', 'KES 6,000.00', '29 Oct 2026', true, $loan),
            '18 LC issued' => self::lcIssued('LC-2026-014', 'Shenzhen Tools Co.', 'I&M Bank', '30 Nov 2026', '31 Dec 2026', 'USD 4,000.00', url('/account')),
            '19 Supplier paid' => self::supplierPaid('USD 12,500.00', 'Shenzhen Tools Co.', '20 Oct 2026', '20 Oct 2026', url('/account')),
            '20 Investment confirmed' => self::investmentConfirmed('James', 'KES 400,000.00', 'AZP-0009', '12 months', '20 Oct 2027', 'KES 64,000.00'),
            '21 Payout sent' => self::payoutSent('KES 15,200.00', 'AZP-0009', 'KES 16,000.00', 'KES 800.00', 'I&M Bank •••• 6350'),
            'Staff alert' => self::staffAlert('Loan integrity check: 3 finding(s)', 'The nightly read-only check found loans whose status and money disagree.', 'Open loans', url('/loans')),
        ];
    }
}
