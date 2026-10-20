<?php

namespace App\Services\Mpesa;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\MpesaTransaction;
use App\Models\PaymentClaim;
use App\Models\Repayment;
use App\Models\User;
use App\Services\LoanCalculator;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\LoanStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The one path every M-Pesa payment takes (Stage 4 Phase 4; owner: automatic and manual recording both).
 *
 *  - C2B confirmation or STK success -> an mpesa_transactions row (receipt unique) -> matched to a loan and
 *    recorded as a repayment (mode mpesa, transaction = the receipt), or left in the staff queue as unmatched.
 *  - A teller typing the same receipt by hand is refused (Repayment::booted), and a payment typed first links
 *    to the callback when it arrives, so a payment is never counted twice.
 *  - A borrower's "I've paid" claim becomes "received" as soon as its code is recorded either way.
 */
class PaymentRecorder
{
    public const PAYABLE = [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_FORBEARANCE,
        Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY];

    /** A C2B confirmation body (TransID, TransAmount, BillRefNumber, MSISDN, TransTime, BusinessShortCode...). */
    public function receiveC2b(array $p): MpesaTransaction
    {
        $transId = strtoupper(trim((string) ($p['TransID'] ?? '')));
        if ($transId === '') {
            throw new \InvalidArgumentException('C2B confirmation without TransID.');
        }
        $existing = MpesaTransaction::where('trans_id', $transId)->first();
        if ($existing) {
            return $existing; // a repeated callback: already stored, never recorded twice
        }
        $t = MpesaTransaction::create([
            'type' => 'c2b', 'trans_id' => $transId,
            'msisdn' => (string) ($p['MSISDN'] ?? ''), 'amount' => (float) ($p['TransAmount'] ?? 0),
            'bill_ref' => trim((string) ($p['BillRefNumber'] ?? '')), 'shortcode' => (string) ($p['BusinessShortCode'] ?? ''),
            'trans_time' => $this->time($p['TransTime'] ?? null), 'status' => 'received', 'payload' => $this->mask($p),
        ]);

        return $this->match($t);
    }

    /** An STK callback (Body.stkCallback). The pending row was made when the push was sent. */
    public function receiveStk(array $body): ?MpesaTransaction
    {
        $cb = $body['Body']['stkCallback'] ?? [];
        $t = MpesaTransaction::where('checkout_request_id', $cb['CheckoutRequestID'] ?? '')->first();
        if (! $t) {
            Log::warning('M-Pesa STK callback for an unknown request', ['checkout' => $cb['CheckoutRequestID'] ?? null]);

            return null;
        }
        if ($t->status !== 'pending') {
            return $t; // repeated callback
        }
        if ((int) ($cb['ResultCode'] ?? -1) !== 0) {
            $t->update(['status' => 'failed', 'note' => mb_substr((string) ($cb['ResultDesc'] ?? 'Not completed'), 0, 250), 'payload' => $this->mask($body)]);

            return $t;
        }
        $items = collect($cb['CallbackMetadata']['Item'] ?? [])->mapWithKeys(fn ($i) => [$i['Name'] => $i['Value'] ?? null]);
        $receipt = strtoupper((string) $items['MpesaReceiptNumber']);
        if (MpesaTransaction::where('trans_id', $receipt)->where('id', '!=', $t->id)->exists()) {
            $t->update(['status' => 'duplicate', 'note' => 'Receipt ' . $receipt . ' already received through C2B']);

            return $t;
        }
        $t->update([
            'trans_id' => $receipt, 'amount' => (float) $items['Amount'], 'msisdn' => (string) ($items['PhoneNumber'] ?? $t->msisdn),
            'trans_time' => $this->time($items['TransactionDate'] ?? null), 'status' => 'received', 'payload' => $this->mask($body),
        ]);

        return $this->match($t->refresh());
    }

    /** Matches a received transaction to its loan and records the repayment, or leaves it for staff. */
    public function match(MpesaTransaction $t): MpesaTransaction
    {
        return DB::transaction(function () use ($t) {
            $t = MpesaTransaction::whereKey($t->id)->lockForUpdate()->firstOrFail();
            if (in_array($t->status, ['matched', 'duplicate', 'reversed'], true)) {
                return $t;
            }
            // typed in by a teller first: link, don't record again
            $typed = Repayment::where('transaction', $t->trans_id)->first();
            if ($typed) {
                $t->update(['status' => 'matched', 'repayment_id' => $typed->id, 'loan_id' => $typed->loan_id, 'note' => 'Recorded by hand before the callback']);
                $this->settleClaims($t->trans_id, $typed);

                return $t;
            }

            [$loan, $why] = $this->loanFor($t);
            if (! $loan) {
                $t->update(['status' => 'unmatched', 'note' => $why]);

                return $t;
            }
            $repayment = $this->record($t, $loan, null);
            $t->update(['status' => 'matched', 'loan_id' => $loan->id, 'repayment_id' => $repayment->id, 'note' => $why]);

            return $t;
        });
    }

    /** Staff assigns an unmatched transaction to a loan (the payer typed a wrong account number). */
    public function assign(MpesaTransaction $t, Loan $loan, User $by): Repayment
    {
        return DB::transaction(function () use ($t, $loan, $by) {
            $t = MpesaTransaction::whereKey($t->id)->lockForUpdate()->firstOrFail();
            if (! in_array($t->status, ['unmatched', 'received'], true)) {
                throw new \DomainException('This payment is already ' . $t->status . '.');
            }
            $repayment = $this->record($t, $loan, $by);
            $t->update(['status' => 'matched', 'loan_id' => $loan->id, 'repayment_id' => $repayment->id, 'resolved_by' => $by->id,
                'note' => 'Assigned by ' . $by->name]);

            return $repayment;
        });
    }

    /** A claim's code is a receipt we hold but couldn't match: the borrower's own claim decides the loan. */
    public function matchClaim(PaymentClaim $claim): PaymentClaim
    {
        $recorded = Repayment::where('transaction', $claim->code)->first();
        if ($recorded) {
            $this->settleClaims($claim->code, $recorded);

            return $claim->refresh();
        }
        $t = MpesaTransaction::where('trans_id', $claim->code)->whereIn('status', ['unmatched', 'received'])->first();
        if ($t) {
            $loan = Loan::find($claim->loan_id);
            if ($loan && in_array($loan->status, self::PAYABLE, true) && abs((float) $t->amount - (float) $claim->amount) < 0.01) {
                $this->assign($t, $loan, $claim->user);
            }
        }

        return $claim->refresh();
    }

    /** Claims for a receipt become "received" once a repayment carries it. */
    public function settleClaims(string $code, Repayment $repayment): void
    {
        $mpesa = MpesaTransaction::where('trans_id', $code)->value('id');
        PaymentClaim::where('code', $code)->where('status', 'checking')->update([
            'status' => 'received', 'repayment_id' => $repayment->id, 'mpesa_transaction_id' => $mpesa, 'updated_at' => now(),
        ]);
    }

    /** [Loan|null, why]: the account number must be an open loan's number; see config lending.mpesa.match_other_payers. */
    private function loanFor(MpesaTransaction $t): array
    {
        $loan = $t->loan_id ? Loan::find($t->loan_id) : null; // STK: the loan the push was for
        if (! $loan) {
            $ref = preg_replace('/\D/', '', (string) $t->bill_ref);
            $loan = $ref !== '' ? Loan::find((int) $ref) : null;
            if (! $loan) {
                return [null, 'Account number "' . $t->bill_ref . '" is not a loan number'];
            }
        }
        if (! in_array($loan->status, self::PAYABLE, true)) {
            return [null, 'Loan #' . $loan->id . ' is ' . $loan->status . ', not open for payments'];
        }
        if ($t->type === 'stk') {
            return [$loan, 'STK push for loan #' . $loan->id];
        }
        $payer = Daraja::msisdn($t->msisdn);
        $owner = Daraja::msisdn(User::whereKey($loan->user_id)->value('phone'));
        if ($payer && $owner && $payer === $owner) {
            return [$loan, 'Account number and phone match loan #' . $loan->id];
        }
        if (config('lending.mpesa.match_other_payers')) {
            return [$loan, 'Account number matches loan #' . $loan->id . ' (other payer allowed)'];
        }
        if (! $payer) {
            // Safaricom hid the number: accept only up to what is owed, so a typo can't over-credit another loan
            $cycle = LoanCycle::where('loan_id', $loan->id)->orderByDesc('cycle_number')->first();
            $owed = $cycle && LoanCalculator::v2() ? CycleBalance::at($cycle)['final_outstanding'] : (float) $loan->amount;
            if ((float) $t->amount <= $owed + 0.01) {
                return [$loan, 'Account number matches loan #' . $loan->id . '; phone hidden; amount within what is owed'];
            }

            return [null, 'Phone hidden and KES ' . number_format((float) $t->amount, 2) . ' is more than loan #' . $loan->id . ' owes'];
        }

        return [null, 'Paid from another phone than loan #' . $loan->id . "'s borrower"];
    }

    private function record(MpesaTransaction $t, Loan $loan, ?User $by): Repayment
    {
        $cycle = LoanCycle::where('loan_id', $loan->id)->orderByDesc('cycle_number')->first();
        $repayment = Repayment::create([
            'loan_id' => $loan->id, 'loan_cycle_id' => $cycle?->id, 'amount' => (float) $t->amount,
            'repayment_date' => ($t->trans_time ?? now())->toDateString(), 'transaction' => $t->trans_id, 'kind' => 'cash',
            'mode' => 'mpesa', 'created_by' => $by?->id,
            'notes' => 'M-Pesa ' . strtoupper($t->type) . ($t->maskedMsisdn() ? ' from ' . $t->maskedMsisdn() : '') . ($t->bill_ref ? ', account ' . $t->bill_ref : ''),
        ]);
        if (LoanCalculator::v2()) {
            LoanStatus::apply($loan->refresh());
        }
        $this->settleClaims($t->trans_id, $repayment);

        return $repayment;
    }

    private function time($v): ?Carbon
    {
        $s = preg_replace('/\D/', '', (string) $v);

        return strlen($s) === 14 ? Carbon::createFromFormat('YmdHis', $s, 'Africa/Nairobi') : now();
    }

    /** The payload with phone numbers masked (only the last 3 digits kept). */
    private function mask(array $p): array
    {
        array_walk_recursive($p, function (&$v, $k) {
            if (in_array($k, ['MSISDN', 'PhoneNumber', 'Value'], true) && is_scalar($v) && preg_match('/^\+?254\d{9}$/', (string) $v)) {
                $v = str_repeat('•', strlen((string) $v) - 3) . substr((string) $v, -3);
            }
        });

        return $p;
    }
}
