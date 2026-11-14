<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\MpesaTransaction;
use App\Models\PaymentClaim;
use App\Services\AuditTrail;
use App\Services\LoanProgress;
use App\Services\Mpesa\Daraja;
use App\Services\Mpesa\PaymentRecorder;
use App\Services\Sharet\SharetCollections;
use App\Support\CustomerNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Repay from the app, on the same rails as the website's repay screen: M-Pesa prompt (STK), Sharet Pay link,
 * and "I already paid" with the M-Pesa code. Only the payment callbacks record money; these only ask for it.
 */
class RepayController extends Controller
{
    public function stk(Request $request, Loan $loan, LoanProgress $progress, Daraja $daraja): JsonResponse
    {
        LoansController::own($request, $loan);
        abort_unless(config('lending.mpesa.stk_enabled'), 409, 'The M-Pesa prompt is off. Pay by Paybill or Sharet Pay.');
        abort_unless(in_array($loan->status, LoansController::PAYABLE, true), 409, 'This loan cannot be paid from the app.');
        $due = $this->due($progress, $loan);
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:1', 'max:' . max(1, ceil($due))]]);
        $phone = Daraja::msisdn($request->user()->phone);
        if (! $phone || $daraja->problem()) {
            return response()->json(['message' => 'The M-Pesa prompt is not available. Pay by Paybill and send us the code.'], 503);
        }
        try {
            $r = $daraja->stkPush($phone, (int) ceil((float) $data['amount']), (string) $loan->id, 'Loan ' . $loan->id);
        } catch (\Throwable $e) {
            Log::error('Mobile STK push failed', ['loan' => $loan->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'We could not reach M-Pesa. Try again in a minute.'], 502);
        }
        $t = MpesaTransaction::create(['type' => 'stk', 'checkout_request_id' => $r['CheckoutRequestID'] ?? null, 'loan_id' => $loan->id,
            'amount' => (int) ceil((float) $data['amount']), 'msisdn' => $phone, 'status' => 'pending',
            'note' => mb_substr((string) ($r['CustomerMessage'] ?? ''), 0, 250)]);
        DeviceController::sighting($request, 'paid');

        return response()->json(['request_id' => $t->id, 'status' => 'pending'], 201);
    }

    public function stkStatus(Request $request, Loan $loan, int $requestId): JsonResponse
    {
        LoansController::own($request, $loan);
        $t = MpesaTransaction::where('id', $requestId)->where('loan_id', $loan->id)->where('type', 'stk')->firstOrFail();

        return response()->json(['status' => $t->status, 'receipt' => $t->trans_id, 'repayment' => $t->repayment_id,
            'message' => $t->status === 'failed' ? $t->note : null]);
    }

    public function sharet(Request $request, Loan $loan, LoanProgress $progress, SharetCollections $collections): JsonResponse
    {
        LoansController::own($request, $loan);
        abort_unless(SharetCollections::available(), 409, 'Sharet Pay is off.');
        abort_unless(in_array($loan->status, array_merge(LoansController::PAYABLE, LoansController::RECOVERY), true), 409, 'This loan cannot be paid from the app.');
        $min = SharetCollections::MIN_AMOUNT;
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:' . $min, 'max:' . max($min, ceil($this->due($progress, $loan)))]]);
        try {
            $link = $collections->payLink($loan, (float) $data['amount'], $request->user());
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Sharet Pay is not available right now. Try again in a minute.'], 502);
        }
        DeviceController::sighting($request, 'paid');

        return response()->json(['pay_url' => $link->pay_url, 'amount' => (float) $link->amount], 201);
    }

    public function claim(Request $request, Loan $loan, LoanProgress $progress): JsonResponse
    {
        LoansController::own($request, $loan);
        abort_unless(in_array($loan->status, array_merge(LoansController::PAYABLE, LoansController::RECOVERY), true), 409, 'This loan cannot be paid from the app.');
        $key = 'repay-claim:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => 'Too many tries. Please wait a few minutes.']);
        }
        RateLimiter::hit($key, 600);
        $request->merge(['code' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('code')))]);
        $due = $this->due($progress, $loan);
        $data = $request->validate([
            'code' => ['required', 'regex:/^[A-Z0-9]{10}$/'],
            'amount' => ['required', 'numeric', 'min:1', 'max:' . max(1, $due)],
            'paid_from' => ['nullable', 'string', 'max:20', 'regex:/^[0-9 +]+$/'],
        ], ['code.regex' => 'An M-Pesa code has 10 letters and numbers.']);
        if ($loan->repayments()->where('transaction', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'We have already recorded this payment.']);
        }
        if (! config('lending.mpesa.claims_enabled')) {
            CustomerNotifier::paymentClaimed($loan, (float) $data['amount'], $data['code'], $data['paid_from'] ?? null);
            AuditTrail::record('payment_claims', $loan->id, 'create', null, ['event' => 'payment_claimed', 'loan_id' => $loan->id,
                'amount' => (float) $data['amount'], 'mpesa_code' => $data['code'], 'stage' => 'mobile, email only']);

            return response()->json(['status' => 'checking', 'code' => $data['code']], 201);
        }
        $claim = PaymentClaim::firstOrCreate(['loan_id' => $loan->id, 'code' => $data['code']],
            ['user_id' => $request->user()->id, 'amount' => (float) $data['amount'], 'phone' => $data['paid_from'] ?? null, 'status' => 'checking']);
        if ($claim->wasRecentlyCreated) {
            CustomerNotifier::paymentClaimed($loan, (float) $data['amount'], $data['code'], $data['paid_from'] ?? null);
            AuditTrail::record('payment_claims', $claim->id, 'create', null, ['loan_id' => $loan->id, 'amount' => (float) $data['amount'], 'mpesa_code' => $data['code'], 'via' => 'mobile']);
        }
        $claim = app(PaymentRecorder::class)->matchClaim($claim);

        return response()->json(['status' => $claim->status, 'code' => $claim->code, 'receipt' => $claim->repayment_id], 201);
    }

    private function due(LoanProgress $progress, Loan $loan): float
    {
        return max(0.0, (float) ($progress->for($loan)['balance']['due'] ?? 0));
    }
}
