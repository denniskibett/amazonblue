<?php

namespace App\Services\Sharet;

use App\Models\Loan;
use App\Models\Repayment;
use App\Models\SharetPaymentRequest;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\Mpesa\PaymentRecorder;
use App\Support\StaffAlarm;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Borrower repayments through Sharet Pay (9 Nov 2026), the second rail beside the own M-Pesa paybill.
 *
 *  1. A pay link is made on our Sharet account for an amount the borrower owes (from their repay screen, or a staff
 *     "send pay link" on the loan). An open link for the same amount is reused; each create sends an Idempotency-Key.
 *  2. The borrower pays on Sharet (M-Pesa or Sharet wallet); the money lands in our Sharet wallet.
 *  3. The signed webhook (or sharet:sync, every 5 minutes) applies it: one cash repayment through
 *     Mpesa\PaymentRecorder::recordCash, exactly as an M-Pesa payment is recorded. The row lock and repayment_id make
 *     a repeated webhook or a later poll a no-op.
 */
class SharetCollections
{
    /** Sharet's smallest payment request (config pricing.min_amount there: KES 10.00). */
    public const MIN_AMOUNT = 10;

    /** Sharet switched on and a key saved. */
    public static function available(): bool
    {
        return (bool) config('sharet.enabled') && filled(config('sharet.api_key'));
    }

    /** Which rail the repay screen shows first: 'sharet' only when chosen and available. */
    public static function preferred(): string
    {
        return self::available() && config('sharet.repayment_rail') === 'sharet' ? 'sharet' : 'paybill';
    }

    /** An open pay link for this loan and amount (reused while still open). */
    public function payLink(Loan $loan, float $amount, ?User $by = null): SharetPaymentRequest
    {
        if (! self::available()) {
            throw new \DomainException('Sharet Pay is not switched on.');
        }
        if (! in_array($loan->status, PaymentRecorder::PAYABLE, true)) {
            throw new \DomainException('Loan #' . $loan->id . ' is ' . $loan->status . ', not open for payments.');
        }
        // Sharet takes whole shillings, at least KES 10 (it refuses less with 422 invalid_amount)
        $amount = (float) ceil(round($amount, 2));
        if ($amount < self::MIN_AMOUNT) {
            throw new \DomainException('Sharet Pay takes at least KES ' . self::MIN_AMOUNT . '.');
        }

        $existing = SharetPaymentRequest::where('loan_id', $loan->id)->where('status', 'open')->where('amount', $amount)->latest('id')->first();
        if ($existing?->isOpen()) {
            return $existing;
        }

        $client = SharetClient::make();
        $user = $loan->user;
        $reference = mb_substr('Loan ' . $loan->id . ($user?->name ? ' · ' . $user->name : ''), 0, 140);
        $body = array_filter([
            'amount' => number_format($amount, 2, '.', ''),
            'channels' => ['mpesa', 'sharet'],
            'reference' => $reference,
            'payer' => ($phone = \App\Services\Mpesa\Daraja::msisdn($user?->phone)) ? ['phone' => $phone] : null,
            'expires_in_minutes' => (int) config('sharet.pay_link_minutes'),
            'metadata' => ['loan_id' => (string) $loan->id],
        ]);
        // a new key for every new link: Sharet replays the first answer for a repeated key, so a key reused within the
        // minute after a link was paid, cancelled or expired would hand back that dead link
        $key = 'aimzone-loan-' . $loan->id . '-' . (int) $amount . '-' . now()->format('YmdHi') . '-' . SharetPaymentRequest::where('loan_id', $loan->id)->count();
        $res = $client->post('/payment-requests', $body, $key);
        if (! $res->successful() || ! $res->json('id')) {
            Log::error('Sharet pay link failed', ['loan_id' => $loan->id, 'status' => $res->status(), 'body' => mb_substr($res->body(), 0, 500)]);
            throw new \DomainException('Sharet could not make the pay link: ' . ($res->json('detail') ?? $res->json('message') ?? ('HTTP ' . $res->status())));
        }

        $row = SharetPaymentRequest::firstOrCreate(['sharet_id' => $res->json('id')], [
            'loan_id' => $loan->id, 'user_id' => $loan->user_id,
            'environment' => $res->json('environment') ?? $client->environment,
            'amount' => $amount, 'status' => $res->json('status') ?? 'open', 'pay_url' => $res->json('pay_url'),
            'reference' => $reference, 'expires_at' => $res->json('expires_at') ? Carbon::parse($res->json('expires_at'))->setTimezone(config('app.timezone')) : null,
            'created_by' => $by?->id, 'raw' => $res->json(),
        ]);
        AuditTrail::record('sharet_payment_requests', $row->id, 'create', null, ['event' => 'sharet_pay_link', 'loan_id' => $loan->id, 'amount' => $amount]);

        return $row;
    }

    /** Apply a Sharet payment-request object (webhook or poll). Records the repayment at most once. */
    public function apply(array $object): ?SharetPaymentRequest
    {
        $row = SharetPaymentRequest::where('sharet_id', (string) ($object['id'] ?? ''))->first();
        if (! $row) {
            return null;
        }
        $status = (string) ($object['status'] ?? $row->status);
        if ($status !== 'paid') {
            if ($status !== $row->status && ! $row->repayment_id && $row->status !== 'paid') {
                $row->update(['status' => $status, 'raw' => $object]);
            }

            return $row;
        }

        $recorder = app(PaymentRecorder::class);

        return DB::transaction(function () use ($row, $object, $recorder) {
            $row = SharetPaymentRequest::whereKey($row->id)->lockForUpdate()->first();
            if ($row->repayment_id || $row->status === 'paid') {
                return $row; // already applied (repeated webhook, or the poll after the webhook)
            }
            $paidAt = isset($object['paid_at']) ? Carbon::parse($object['paid_at'])->setTimezone('Africa/Nairobi') : now('Africa/Nairobi');
            $txn = strtoupper((string) ($object['transaction_id'] ?? '')) ?: $row->sharet_id;
            $row->fill(['status' => 'paid', 'paid_via' => $object['paid_via'] ?? null, 'sharet_transaction_id' => $txn,
                'paid_at' => $paidAt, 'raw' => $object]);

            $loan = Loan::whereKey($row->loan_id)->lockForUpdate()->first();
            $typed = Repayment::where('transaction', $txn)->whereNull('reversal_of_id')->first();
            if ($typed) {
                // a teller typed Sharet's transaction ID first: link it, never record it twice
                $row->repayment_id = $typed->id;
                $row->note = 'Recorded by hand before Sharet reported it';
            } elseif ($loan && in_array($loan->status, PaymentRecorder::PAYABLE, true)) {
                $repayment = $recorder->recordCash($loan, (float) $row->amount, $paidAt->toDateString(), $txn, 'sharet', null,
                    'Sharet Pay' . ($row->paid_via ? ' (' . $row->paid_via . ')' : '') . ', pay link ' . $row->sharet_id);
                $row->repayment_id = $repayment->id;
            } else {
                // money is in our Sharet wallet but the loan no longer takes payments: staff decide (refund or another loan)
                $row->note = 'Paid, not recorded: loan #' . $row->loan_id . ' is ' . ($loan->status ?? 'missing');
                DB::afterCommit(fn () => StaffAlarm::raise('sharet-unrecorded', 'Sharet payment not recorded',
                    'A borrower paid KES ' . number_format((float) $row->amount, 2) . ' through Sharet for loan #' . $row->loan_id . ', which no longer takes payments. Decide on /staff/sharet.',
                    ['sharet_id' => $row->sharet_id]));
            }
            $row->save();
            AuditTrail::record('sharet_payment_requests', $row->id, 'update', ['status' => 'open'],
                ['status' => 'paid', 'repayment_id' => $row->repayment_id, 'transaction' => $txn]);

            return $row;
        });
    }

    /** Poll Sharet for links still open: a safety net for missed webhooks. Returns how many changed. */
    public function syncOpen(int $limit = 200): int
    {
        if (! self::available()) {
            return 0;
        }
        $client = SharetClient::make();
        $changed = 0;
        SharetPaymentRequest::where('status', 'open')->where('created_at', '>=', now()->subDays(30))
            ->orderBy('id')->limit($limit)->get()
            ->each(function (SharetPaymentRequest $row) use ($client, &$changed) {
                $res = $client->get('/payment-requests/' . $row->sharet_id);
                if ($res->successful() && ($res->json('status') ?? 'open') !== 'open') {
                    $this->apply($res->json());
                    $changed++;
                }
            });

        return $changed;
    }
}
