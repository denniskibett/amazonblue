<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Loan;
use App\Models\Repayment;
use App\Services\AuditTrail;
use App\Services\BorrowingEligibility;
use App\Services\CustomerWallets;
use App\Services\LoanProgress;
use App\Support\CustomerNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Borrower repayment journey in /account (Stage 2, journey B): repay screen with M-Pesa instructions,
 * "I've paid" confirmation, receipts and the facility-closed screen.
 *
 * Read-only on money: the amount due, penalty and paid figures come from LoanProgress; repayments are
 * recorded by staff exactly as before (RepaymentController). Interim until Stage 4 (Daraja STK push,
 * C2B callbacks, payment-claims table): an "I've paid" claim is emailed to staff, written to the audit
 * trail and kept in the borrower's session only.
 */
trait BorrowerRepayment
{
    // statuses the borrower can pay against from the repay screen
    private static array $repayPayable = [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_FORBEARANCE];

    // handled by the recovery team: instructions + "contact the recovery team", no STK push
    private static array $repayRecovery = [Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY];

    // the demo flow states that can be opened directly (local only, ?demo=1&state=...)
    private static array $repayDemoStates = ['sending', 'waiting', 'received', 'failed', 'timeout'];

    /** /account/repay: the open loan's repay screen, or an empty state when there is nothing to repay. */
    public function accountRepayStart(Request $request, BorrowingEligibility $eligibility): View|RedirectResponse
    {
        $loan = $eligibility->openLoan($request->user());
        if ($loan) {
            return redirect()->route('account.repay', $loan);
        }

        return view('home.account.repay.none', ['user' => $request->user()]);
    }

    public function accountRepay(Request $request, Loan $loan, LoanProgress $progress): View|RedirectResponse
    {
        $this->ownRepayLoan($request, $loan);
        if ($loan->status === Loan::STATUS_REPAID) {
            return redirect()->route('account.closed', $loan);
        }

        $data = $progress->for($loan);
        $mode = match (true) {
            in_array($loan->status, self::$repayPayable, true) => 'pay',
            in_array($loan->status, self::$repayRecovery, true) => 'recovery',
            in_array($loan->status, [Loan::STATUS_PENDING, Loan::STATUS_APPROVED], true) => 'not_yet',
            default => 'closed_other', // rejected, written off
        };
        $demo = app()->environment('local') && $request->boolean('demo');

        return view('home.account.repay.show', [
            'user' => $request->user(),
            'progress' => $data,
            'mode' => $mode,
            'due' => $this->repayAmountDue($data),
            'mpesa' => $this->mpesaInstructions($loan),
            'claims' => self::claimsFor($loan),
            'checking' => session('repay_checking'),
            // design stub for the STK flow, never outside local
            'demo' => $demo,
            'demoState' => $demo && in_array($request->query('state'), self::$repayDemoStates, true) ? $request->query('state') : null,
        ]);
    }

    /** "I've paid": the borrower's M-Pesa confirmation code goes to staff (email + audit trail). */
    public function accountRepayConfirm(Request $request, Loan $loan, LoanProgress $progress): RedirectResponse
    {
        $this->ownRepayLoan($request, $loan);
        abort_unless(in_array($loan->status, array_merge(self::$repayPayable, self::$repayRecovery), true), 404);

        // own limiter key, so other throttled routes don't share the count: 5 claims per 10 minutes per borrower
        $limiterKey = 'repay-claim:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            throw ValidationException::withMessages(['code' => 'Too many tries. Please wait ' . ceil(RateLimiter::availableIn($limiterKey) / 60) . ' minutes, or message us on WhatsApp.']);
        }
        RateLimiter::hit($limiterKey, 600);

        $request->merge(['code' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('code')))]);
        $due = $this->repayAmountDue($progress->for($loan));
        $data = $request->validate([
            'code' => ['required', 'regex:/^[A-Z0-9]{10}$/'],
            'amount' => ['required', 'numeric', 'min:1', 'max:' . max(1, $due)],
            'paid_from' => ['nullable', 'string', 'max:20', 'regex:/^[0-9 +]+$/'],
        ], [
            'code.regex' => 'An M-Pesa code has 10 letters and numbers, e.g. QJK8X2LM4P.',
            'amount.max' => 'That is more than the amount due (KES ' . number_format($due, 2) . ').',
            'paid_from.regex' => 'Enter the phone number you paid from, digits only.',
        ]);

        $recorded = $loan->repayments()->where('transaction', $data['code'])->first();
        if ($recorded) {
            throw ValidationException::withMessages(['code' => 'We have already recorded this payment (receipt #' . $recorded->id . ').']);
        }

        $claims = session('repay_claims', []);
        $already = collect($claims[$loan->id] ?? [])->contains('code', $data['code']);
        if (! $already) {
            $amount = (float) $data['amount'];
            CustomerNotifier::paymentClaimed($loan, $amount, $data['code'], $data['paid_from'] ?? null);
            AuditTrail::record('payment_claims', $loan->id, 'create', null, [
                'event' => 'payment_claimed', 'loan_id' => $loan->id, 'amount' => $amount,
                'mpesa_code' => $data['code'], 'paid_from' => $data['paid_from'] ?? null, 'stage' => 'interim: email only',
            ]);
            $claims[$loan->id][] = ['code' => $data['code'], 'amount' => $amount, 'at' => now()->toIso8601String()];
            session(['repay_claims' => $claims]);
        }

        return redirect()->route('account.repay', $loan)->with('repay_checking', $data['code']);
    }

    /** Facility closed: shown once the loan's status is repaid. */
    public function accountLoanClosed(Request $request, Loan $loan, LoanProgress $progress, BorrowingEligibility $eligibility): View|RedirectResponse
    {
        $this->ownRepayLoan($request, $loan);
        if ($loan->status !== Loan::STATUS_REPAID) {
            return redirect()->route('account.repay', $loan);
        }
        $user = $request->user();
        // how this loan ended (on time, late, rolled over...), as the 4Cs scoring sees it
        $history = collect(app(\App\Services\CreditAssessment::class)->current($user)['history'] ?? []);

        return view('home.account.repay.closed', [
            'user' => $user,
            'progress' => $progress->for($loan),
            'outcome' => $history->firstWhere('loan_id', $loan->id),
            'limit' => $eligibility->limit($user)['limit'] ?? null,
            'hasOpenLoan' => (bool) $eligibility->openLoan($user),
        ]);
    }

    /** One repayment of the borrower's own loan, printable. Non-cash book entries are labelled, not receipts. */
    public function accountReceipt(Request $request, Loan $loan, Repayment $repayment): View
    {
        $this->ownRepayLoan($request, $loan);
        abort_unless((int) $repayment->loan_id === (int) $loan->id, 404);
        $loan->loadMissing('loanType');

        return view('home.account.repay.receipt', [
            'user' => $request->user(),
            'loan' => $loan,
            'repayment' => $repayment,
            'kind' => CustomerWallets::kindOf($repayment),
        ]);
    }

    /**
     * "I've paid" claims made in this session for a loan, each marked received once a repayment with the
     * same M-Pesa code has been recorded. Session only until Stage 4 stores claims.
     */
    public static function claimsFor(Loan $loan): array
    {
        $claims = session('repay_claims', [])[$loan->id] ?? [];
        if (! $claims) {
            return [];
        }
        $recorded = $loan->repayments->pluck('id', 'transaction')->mapWithKeys(fn ($id, $code) => [strtoupper((string) $code) => $id]);

        return collect($claims)->map(fn ($c) => $c + ['receipt' => $recorded[$c['code']] ?? null])->reverse()->values()->all();
    }

    private function ownRepayLoan(Request $request, Loan $loan): void
    {
        abort_unless((int) $loan->user_id === (int) $request->user()->id, 404);
    }

    /** Amount due now, straight from LoanProgress (never below zero for the picker). */
    private function repayAmountDue(array $progress): float
    {
        return max(0.0, (float) ($progress['balance']['due'] ?? 0));
    }

    /** Paybill and account number to show, from config/lending.php mpesa. */
    private function mpesaInstructions(Loan $loan): array
    {
        $config = config('lending.mpesa', []);
        $user = $loan->user;
        $format = $config['account_format'] ?? 'loan_number';
        $account = match ($format) {
            'phone' => $user?->phone,
            'id_number' => $user?->id_number,
            default => (string) $loan->id,
        };

        return [
            'paybill' => trim((string) ($config['paybill'] ?? '')) ?: null,
            'account' => $account,
            'account_label' => ['phone' => 'your registered phone number', 'id_number' => 'your ID number'][$format] ?? 'your loan number',
            'stk' => (bool) ($config['stk_enabled'] ?? false),
            'enabled' => (bool) ($config['enabled'] ?? false),
        ];
    }
}
