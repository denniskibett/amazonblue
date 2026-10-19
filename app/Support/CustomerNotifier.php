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
 * Emails for the customer journey (KYC submitted / approved / rejected, application received) and the
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
