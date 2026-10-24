<?php

namespace App\Services\Business;

use App\Models\Business;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\User;
use App\Services\Aml\Screening;
use App\Services\AuditTrail;
use App\Services\BorrowingEligibility;
use App\Services\LoanAgreementService;
use App\Services\LoanCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Business loans (owner, 23 Oct 2026; switch lending.business_loans.enabled; plan defaults).
 *
 * A business borrows once it is approved with a limit (BusinessApproval), every director has verified themselves
 * (DirectorVerification) and, with screening on, AML is clear. The owner applies from the business page for a
 * product meant for businesses (loan_types.audience = business) and signs as the borrower of record; the loan
 * carries the business (loans.business_id) and then runs through the same approval, disbursement, cycle and
 * repayment path as every other loan. One open loan per business; the amount fits the limit less what the
 * business still owes.
 */
class BusinessLending
{
    public const OPEN = ['pending', 'approved', 'disbursed', 'active', 'overdue', 'forbearance', 'defaulted', 'recovery'];

    public static function enabled(): bool
    {
        return (bool) config('lending.business_loans.enabled');
    }

    /** Products for businesses. */
    public function products(): Collection
    {
        return LoanType::where('audience', 'business')->orderBy('name')->get();
    }

    /** What the business still owes on its loans (principal of the open loans). */
    public function owed(Business $business): float
    {
        return round((float) Loan::where('business_id', $business->id)->whereIn('status', self::OPEN)->sum('amount'), 2);
    }

    public function available(Business $business): float
    {
        return max(0, round((float) $business->credit_limit - $this->owed($business), 2));
    }

    /** Why this business can't borrow right now: list of [title, message]. Empty = it can. */
    public function blockers(Business $business, User $user): array
    {
        $out = [];
        if (! self::enabled()) {
            return [['Not open yet', 'Business loans are not open yet.']];
        }
        if ($business->owner_user_id !== $user->id) {
            $out[] = ['Only the owner applies', 'The person who added the business applies for its loans.'];
        }
        if ($business->status !== Business::STATUS_APPROVED) {
            $out[] = ['Business not verified', 'Our team verifies the business first.'];
        }
        if ((float) $business->credit_limit <= 0) {
            $out[] = ['No limit yet', 'Our team sets the business limit when they approve it.'];
        }
        if (! app(BorrowingEligibility::class)->isVerified($user)) {
            $out[] = ['Verify yourself', 'Your own identity check must be approved.'];
        }
        if ($names = app(DirectorVerification::class)->unverified($business)->pluck('full_name')->all()) {
            $out[] = ['Directors to verify', 'Still to verify themselves: ' . implode(', ', $names) . '.'];
        }
        if ($b = app(Screening::class)->blocker($business)) {
            $out[] = ['Checks not finished', 'Our team is still finishing the business checks.'];
        }
        if (Loan::where('business_id', $business->id)->whereIn('status', ['pending', 'approved'])->exists()) {
            $out[] = ['Application already open', 'The business has an application waiting. One at a time.'];
        }
        if ($this->products()->isEmpty()) {
            $out[] = ['No business products', 'Our team has not set up a business product yet.'];
        }

        return $out;
    }

    /** Apply: the loan, its first cycle and the signed agreement are saved together. */
    public function apply(Business $business, User $user, LoanType $type, float $amount, string $purpose, Request $request): Loan
    {
        if ($b = $this->blockers($business, $user)) {
            throw new \DomainException($b[0][1]);
        }
        if ($type->audience !== 'business') {
            throw new \DomainException('Choose a business product.');
        }
        $amount = round($amount, 2);
        $min = (float) config('lending.min_amount');
        if ($amount < $min) {
            throw new \DomainException('The smallest loan is KES ' . number_format($min, 2) . '.');
        }
        if ($amount > $this->available($business)) {
            throw new \DomainException('The business can borrow up to KES ' . number_format($this->available($business), 2) . ' right now.');
        }

        $loan = DB::transaction(function () use ($business, $user, $type, $amount, $purpose, $request) {
            Business::whereKey($business->id)->lockForUpdate()->first();
            if (Loan::where('business_id', $business->id)->whereIn('status', ['pending', 'approved'])->exists()) {
                throw new \DomainException('The business has an application waiting. One at a time.');
            }
            $loan = Loan::create([
                'user_id' => $user->id,
                'business_id' => $business->id,
                'loan_type_id' => $type->id,
                'amount' => $amount,
                'original_amount' => $amount,
                'borrow_date' => now()->toDateString(),
                'status' => Loan::STATUS_PENDING,
                'broker_status' => 0,
                'reason' => 'Business loan for ' . $business->legal_name . ': ' . $purpose,
                'consent' => true,
                'consent_date' => now(),
                'cycle' => 1,
            ]);
            app(LoanCalculator::class)->createInitialCycle($loan);
            app(LoanAgreementService::class)->generateLoanAgreement($loan->fresh(), $request);

            return $loan;
        });
        AuditTrail::record('loans', $loan->id, 'create', null, ['event' => 'business_loan_applied', 'business_id' => $business->id, 'amount' => $amount, 'loan_type_id' => $type->id]);
        \App\Support\CustomerNotifier::applicationReceived($loan);

        return $loan;
    }
}
