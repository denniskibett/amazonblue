<?php

namespace App\Support;

use App\Models\Loan;
use App\Models\User;
use App\Notifications\KycReviewed;
use App\Notifications\KycSubmitted;
use App\Notifications\LoanApplicationReceived;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Log;

/**
 * Emails for the customer journey (KYC and business KYC submitted / approved / rejected, application received) and the
 * matching heads-up to admins. Sending never breaks the request: a mail failure is logged and ignored.
 */
class CustomerNotifier
{
    // who gets the staff heads-up
    private const STAFF_RECIPIENT_ROLES = ['super-admin', 'admin'];

    public static function kycSubmitted(User $user): void
    {
        $reference = 'KYC-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);
        self::send($user, new KycSubmitted($reference));
        self::toStaff(new StaffAlert(
            'New KYC to review: ' . $user->name . ' (' . $reference . ')',
            $user->name . ' has submitted their verification details. Review the documents and call the next of kin and guarantor.',
            'Review KYC',
            route('kyc-reviews.show', $user),
        ));
    }

    public static function kycReviewed(User $user, bool $approved, ?string $note = null): void
    {
        self::send($user, new KycReviewed($approved, $note));
    }

    public static function applicationReceived(Loan $loan): void
    {
        $user = $loan->user;
        if ($user) {
            self::send($user, new LoanApplicationReceived($loan));
        }
        self::toStaff(new StaffAlert(
            'New loan application #' . str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT),
            ($user?->name ?? 'A borrower') . ' applied for KES ' . number_format((float) $loan->amount, 2) . ' from their account. It is waiting for approval.',
            'Open the loan',
            route('loans.show', $loan),
        ));
    }

    /**
     * Interim (Stage 2): a borrower says they paid by M-Pesa and typed the confirmation code. Nothing is
     * stored yet (Stage 4: payment claims table + reconciliation), so staff get the details by email and
     * record the repayment the usual way after checking M-Pesa.
     */
    public static function paymentClaimed(Loan $loan, float $amount, string $code, ?string $paidFrom = null): void
    {
        $user = $loan->user;
        $reference = str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT);
        self::toStaff(new StaffAlert(
            'Payment to confirm: loan #' . $reference . ', KES ' . number_format($amount, 2) . ', code ' . $code,
            ($user?->name ?? 'A borrower') . ' says they paid KES ' . number_format($amount, 2) . ' by M-Pesa on loan #' . $reference
                . ' (loan id ' . $loan->id . '). M-Pesa code: ' . $code . '.'
                . ($paidFrom ? ' Paid from: ' . $paidFrom . '.' : '')
                . ' Claimed ' . now()->format('j M Y H:i') . '. Check the M-Pesa statement, then record the repayment.',
            'Record the repayment',
            \Illuminate\Support\Facades\Route::has('users.loans.repayments.create') && $loan->user_id
                ? route('users.loans.repayments.create', [$loan->user_id, $loan->id])
                : route('loans.show', $loan),
        ));
    }

    /**
     * Interim (Stage 2): a borrower asked for something from their account (extension, payment-plan
     * acceptance, a question about a loan). Nothing is stored apart from the audit row; staff get the
     * details by email and follow up. Stage 4: requests table with approval.
     */
    public static function borrowerRequest(User $user, string $subject, string $line, ?Loan $loan = null): void
    {
        self::toStaff(new StaffAlert(
            $subject,
            $user->name . ' (user id ' . $user->id . ', ' . ($user->phone ?: $user->email) . '): ' . $line
                . ' Sent ' . now()->format('j M Y H:i') . ' from their account.',
            $loan ? 'Open the loan' : 'Open the customer',
            $loan ? route('loans.show', $loan) : (\Illuminate\Support\Facades\Route::has('users.show') ? route('users.show', $user) : url('/dashboard')),
        ));
    }

    public static function businessSubmitted(\App\Models\Business $business): void
    {
        $owner = $business->owner;
        if ($owner) {
            self::send($owner, new \App\Notifications\BusinessSubmitted($business->id, $business->legal_name, $business->reference()));
        }
        self::toStaff(new StaffAlert(
            'New business to review: ' . $business->legal_name . ' (' . $business->reference() . ')',
            ($owner?->name ?? 'A borrower') . ' has submitted ' . $business->legal_name . ' for verification. Check the documents, directors and shareholders.',
            'Review business',
            route('business-reviews.show', $business),
        ));
    }

    public static function businessReviewed(\App\Models\Business $business, bool $approved, ?string $note = null): void
    {
        if ($business->owner) {
            self::send($business->owner, new \App\Notifications\BusinessReviewed($business->id, $business->legal_name, $approved, $note));
        }
    }

    /**
     * Partner self-service request (funds sent / withdrawal): heads-up to admins with a link to the partner.
     * Nothing is stored or posted; staff record the money from the partner page (Stage 4: requests table).
     */
    public static function partnerRequest(\App\Models\Partner $partner, string $subject, string $line): void
    {
        self::toStaff(new StaffAlert($subject, $line, 'Open the partner', route('partners.show', $partner)));
    }

    /**
     * The partner funding details (where partners send money) changed on /system: every admin hears about it,
     * with the old and new values, so a change nobody expected is spotted before a partner pays into it.
     */
    public static function partnerFundingChanged(User $by, array $old, array $new): void
    {
        $lines = collect($new)->map(fn ($value, $key) => str_replace('_', ' ', $key) . ': '
            . (filled($old[$key] ?? null) ? $old[$key] : '(default)') . ' → ' . (filled($value) ? $value : '(default)'))->implode('; ');
        self::toStaff(new StaffAlert(
            'Partner funding details changed by ' . $by->name,
            $by->name . ' (' . $by->email . ') changed where partners send money, at ' . now()->format('d M Y H:i') . '. '
                . $lines . '. If you did not expect this, sign in and check the System settings now.',
            'Open System settings', route('system.index') . '#partner-funding'
        ));
    }

    private static function send(User $user, $notification): void
    {
        if (! $user->email) {
            return;
        }
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::warning('Customer email failed: ' . class_basename($notification), ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    private static function toStaff($notification): void
    {
        try {
            $staff = User::whereHas('roles', fn ($q) => $q->whereIn('name', self::STAFF_RECIPIENT_ROLES))
                ->whereNotNull('email')->get();
        } catch (\Throwable $e) {
            Log::warning('Staff email failed: ' . class_basename($notification), ['error' => $e->getMessage()]);

            return;
        }
        // one at a time, so one bad address doesn't stop the others
        foreach ($staff as $member) {
            self::send($member, $notification);
        }
    }
}
