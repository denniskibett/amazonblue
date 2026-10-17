<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanType;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Whether an individual borrower can apply through the dashboard, and how much.
 *
 * Limit: KES 5,000 for first-time borrowers; KES 50,000 once the most recent loan was repaid
 * on time in cash (no rollover or discount) and nothing has ever been written off or gone to
 * recovery. The 4Cs score and declared income can lower it (CreditAssessment). Amounts live in
 * config/lending.php.
 */
class BorrowingEligibility
{
    // a loan in any of these states blocks a new application
    public const OPEN_STATUSES = [
        Loan::STATUS_PENDING, Loan::STATUS_APPROVED, Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE,
        Loan::STATUS_OVERDUE, Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY, Loan::STATUS_FORBEARANCE,
    ];

    // any of these in the history keeps the borrower on the first-time limit
    public const BAD_HISTORY_STATUSES = [Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY, Loan::STATUS_WRITTEN_OFF];

    public function loanType(): ?LoanType
    {
        return LoanType::find(config('lending.borrower_loan_type_id'));
    }

    /**
     * Ladder limit (first-time / good standing), checked against today's 4Cs score and declared income.
     *
     * @return array{tier: string, limit: int, label: string, ladder: int, capped_by: ?string, score: int, category: ?string}
     */
    public function limit(User $user): array
    {
        $assessment = app(CreditAssessment::class)->current($user);

        return $assessment['limit'] + [
            'score' => $assessment['overall'],
            'category' => $assessment['category']?->name,
        ];
    }

    public function openLoan(User $user): ?Loan
    {
        return Loan::with('loanType')->where('user_id', $user->id)
            ->whereIn('status', self::OPEN_STATUSES)->latest('id')->first();
    }

    /**
     * Staff have approved the borrower's KYC after calling the next of kin and guarantor.
     * Everyone goes through it once, existing clients included.
     */
    public function isVerified(User $user): bool
    {
        return $user->kyc_status === 'approved';
    }

    /**
     * Has borrowed from us before (an existing client re-verifying, not a new sign-up).
     */
    public function isExistingClient(User $user): bool
    {
        return Loan::withTrashed()->where('user_id', $user->id)->whereNotIn('status', [Loan::STATUS_PENDING, Loan::STATUS_REJECTED])->exists();
    }

    /**
     * Reasons the borrower cannot apply right now (empty = can apply), as [title, message] pairs.
     */
    public function blockers(User $user): array
    {
        $reasons = [];

        if (! $user->borrower) {
            $reasons[] = ['Borrower profile missing', 'Your account is not set up as a borrower. Please contact us.'];
        } elseif (! $this->isVerified($user)) {
            $reasons[] = match ($user->kyc_status) {
                'submitted' => ['Verification in progress', 'Our team is verifying your details and will call your next of kin and guarantor. You can borrow once that is done.'],
                'rejected' => ['Verification needs attention', 'We could not verify some of your details. Please update your verification and submit it again.'],
                default => $this->isExistingClient($user)
                    ? ['Confirm your details once', 'We have a new verification process. Confirm your details (most are already filled in) and add two contacts we can call, then you can borrow again.']
                    : ['Verify your account first', 'Complete your verification (personal details, documents, income and contacts) before you borrow.'],
            };
        }

        if ($loan = $this->openLoan($user)) {
            $reasons[] = ['You already have a loan', 'Loan #' . str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT) . ' is ' . str_replace('_', ' ', $loan->status) . '. You can apply again once it is repaid.'];
        }

        if ($user->debtRecoveryCases()->open()->exists()) {
            $reasons[] = ['Account under recovery', 'Please settle your outstanding balance with our team before applying.'];
        }

        if (! $this->loanType()) {
            $reasons[] = ['Borrowing unavailable', 'Self-service borrowing is not available right now. Please contact us.'];
        }

        return $reasons;
    }

    /**
     * What the borrower would repay for $amount on the self-service product.
     *
     * @return array{amount: float, interest: float, total: float, rate: float, penalty_rate: float, due_date: Carbon, period: string}
     */
    public function quote(float $amount, ?Carbon $from = null): array
    {
        $type = $this->loanType();
        $rate = (float) $type->interest_rate;
        $from ??= now();
        $due = match ($type->unit) {
            'weeks' => $from->copy()->addWeeks($type->period),
            'months' => $from->copy()->addMonths($type->period),
            default => $from->copy()->addDays($type->period),
        };

        return [
            'amount' => $amount,
            'interest' => round($amount * $rate / 100, 2),
            'total' => round($amount * (1 + $rate / 100), 2),
            'rate' => $rate,
            'penalty_rate' => (float) $type->penalty_rate,
            'due_date' => $due,
            'period' => $type->period . ' ' . $type->unit,
        ];
    }
}
