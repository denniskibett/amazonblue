<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\User;
use App\Services\BorrowingEligibility;
use App\Services\LoanAgreementService;
use App\Services\LoanCalculator;
use App\Support\CustomerNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/** Quote and apply, with the same rules as the website's Borrow page (LoanController@apply). */
class BorrowController extends Controller
{
    public function quote(Request $request, BorrowingEligibility $eligibility): JsonResponse
    {
        abort_unless($eligibility->loanType(), 404, 'Borrowing unavailable');
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:1', 'max:10000000']]);
        $q = $eligibility->quote((float) $data['amount']);

        return response()->json(['amount' => $q['amount'], 'interest' => $q['interest'], 'total' => $q['total'], 'rate' => $q['rate'],
            'penalty_rate' => $q['penalty_rate'], 'due_date' => $q['due_date']->toDateString(), 'period' => $q['period']]);
    }

    public function apply(Request $request, BorrowingEligibility $eligibility, LoanCalculator $calculator, LoanAgreementService $agreements): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('borrower'), 403, 'Only borrowers can apply.');
        if ($blockers = $eligibility->blockers($user)) {
            return response()->json(['message' => $blockers[0][1], 'title' => $blockers[0][0]], 422);
        }
        $limit = $eligibility->limit($user)['limit'];
        $min = (int) config('lending.min_amount');
        $data = $request->validate([
            'amount' => ['required', 'numeric', "min:{$min}", "max:{$limit}"],
            'consent' => ['accepted'],
        ], [
            'amount.min' => 'The smallest loan is KES ' . number_format($min) . '.',
            'amount.max' => 'Your current limit is KES ' . number_format($limit) . '.',
            'consent.accepted' => 'Please accept the loan terms.',
        ]);

        try {
            $loan = DB::transaction(function () use ($user, $data, $request, $eligibility, $calculator, $agreements) {
                User::whereKey($user->id)->lockForUpdate()->first();
                if ($eligibility->openLoan($user)) {
                    return null;
                }
                $loan = Loan::create([
                    'user_id' => $user->id, 'loan_type_id' => config('lending.borrower_loan_type_id'),
                    'amount' => $data['amount'], 'original_amount' => $data['amount'], 'borrow_date' => now()->toDateString(),
                    'status' => Loan::STATUS_PENDING, 'broker_status' => 0, 'reason' => 'Self-service application from the mobile app.',
                    'consent' => true, 'consent_date' => now(), 'cycle' => 1,
                ]);
                $calculator->createInitialCycle($loan);
                $agreements->generateLoanAgreement($loan->fresh(), $request);

                return $loan;
            });
        } catch (\Throwable $e) {
            Log::error('Mobile application failed for user #' . $user->id, ['error' => $e->getMessage()]);

            return response()->json(['message' => 'We could not send your application. Nothing was saved; try again in a few minutes.'], 500);
        }
        if (! $loan) {
            throw ValidationException::withMessages(['amount' => 'Your earlier application is still open.']);
        }
        CustomerNotifier::applicationReceived($loan);
        // their guarantor is texted a link to sign before the money goes (owner, 21 Nov 2026)
        app(\App\Services\Loans\Guarantees::class)->open($loan, $user);
        DeviceController::sighting($request, 'applied');

        return response()->json(['loan' => LoansController::card($loan->fresh('loanType'))], 201);
    }
}
