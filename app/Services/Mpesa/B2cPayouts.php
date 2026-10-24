<?php

namespace App\Services\Mpesa;

use App\Models\Loan;
use App\Models\User;
use App\Services\Approvals;
use App\Services\AuditTrail;
use App\Services\DisbursementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * M-Pesa B2C payouts (owner, 23 Oct 2026; switch lending.mpesa.b2c_enabled): staff send an approved loan's money
 * straight to the borrower's phone.
 *
 *   1. One person asks (request): an mpesa_transactions row of type b2c, status pending, waiting for approval.
 *   2. A second person approves on Second sign-offs (always; second sign-offs must be on): that sends the request
 *      to Daraja (send) and keeps its ConversationID.
 *   3. Safaricom's result callback (result): paid = the disbursement is recorded with the M-Pesa receipt, exactly as
 *      the Disburse form records one, and linked on the row; not paid = the row fails with Safaricom's reason.
 *      A timeout leaves it pending with a note: check the M-Pesa statement before sending again.
 * Nothing is ever sent twice from one row: a row with a conversation id is never sent again.
 */
class B2cPayouts
{
    public static function enabled(): bool
    {
        return (bool) config('lending.mpesa.b2c_enabled') && Approvals::enabled();
    }

    public function request(Loan $loan, float $amount, string $phone, User $maker): int
    {
        if (! self::enabled()) {
            throw new \DomainException('M-Pesa payouts are off. They need MPESA_B2C_ENABLED and second sign-offs on.');
        }
        if ($loan->status !== Loan::STATUS_APPROVED) {
            throw new \DomainException('Only an approved loan can be paid out.');
        }
        $msisdn = Daraja::msisdn($phone);
        if (! $msisdn) {
            throw new \DomainException('Enter a Kenyan M-Pesa number, e.g. 0712 345 678.');
        }
        $amount = round($amount, 0);
        if ($amount < 10 || $amount > (float) $loan->amount) {
            throw new \DomainException('The amount must be between KES 10 and the loan amount (KES ' . number_format((float) $loan->amount, 2) . ').');
        }
        if (DB::table('mpesa_transactions')->where('type', 'b2c')->where('loan_id', $loan->id)->whereIn('status', ['pending', 'matched'])->exists()) {
            throw new \DomainException('This loan already has an M-Pesa payout waiting or paid.');
        }

        return DB::transaction(function () use ($loan, $amount, $msisdn, $maker) {
            $id = DB::table('mpesa_transactions')->insertGetId([
                'type' => 'b2c', 'msisdn' => $msisdn, 'amount' => $amount, 'bill_ref' => 'LOAN-' . $loan->id, 'loan_id' => $loan->id,
                'status' => 'pending', 'note' => 'Waiting for a second sign-off',
                'payload' => json_encode(['requested_by' => $maker->id, 'requested_at' => now()->toDateTimeString()]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            app(Approvals::class)->request($maker, 'mpesa_b2c', 'mpesa_transactions', $id, $amount, ['loan_id' => $loan->id, 'phone' => $msisdn]);
            AuditTrail::record('mpesa_transactions', $id, 'create', null, ['event' => 'b2c_requested', 'loan_id' => $loan->id, 'amount' => $amount]);

            return $id;
        });
    }

    /** Runs when the second person approves: sends the request to Safaricom. */
    public function send(int $id, User $checker): object
    {
        $row = DB::table('mpesa_transactions')->where('id', $id)->where('type', 'b2c')->lockForUpdate()->first();
        if (! $row || $row->status !== 'pending' || $row->conversation_id) {
            throw new \DomainException('This payout has already been sent or closed.');
        }
        $loan = Loan::findOrFail($row->loan_id);
        if ($loan->status !== Loan::STATUS_APPROVED) {
            throw new \DomainException('Loan #' . $loan->id . ' is ' . $loan->status . ', no longer waiting for its money.');
        }
        $daraja = app(Daraja::class);
        if ($problem = $daraja->b2cProblem()) {
            throw new \DomainException('M-Pesa is not set up: ' . $problem);
        }
        try {
            $reply = $daraja->b2c($row->msisdn, (int) $row->amount, 'Loan ' . $loan->id, 'Loan disbursement');
        } catch (\Throwable $e) {
            Log::warning('B2C request failed', ['row' => $id, 'error' => mb_substr($e->getMessage(), 0, 300)]);
            throw new \DomainException('Safaricom did not accept the payout. Nothing was sent; try again later.');
        }
        if ((string) ($reply['ResponseCode'] ?? '') !== '0' || empty($reply['ConversationID'])) {
            throw new \DomainException('Safaricom refused the payout: ' . ($reply['ResponseDescription'] ?? 'no reason given') . '. Nothing was sent.');
        }
        $payload = (json_decode((string) $row->payload, true) ?: []) + ['approved_by' => $checker->id, 'sent_at' => now()->toDateTimeString(),
            'originator_conversation_id' => $reply['OriginatorConversationID'] ?? null];
        DB::table('mpesa_transactions')->where('id', $id)->update(['conversation_id' => $reply['ConversationID'], 'payload' => json_encode($payload),
            'note' => 'Sent to Safaricom, waiting for the result', 'updated_at' => now()]);
        AuditTrail::record('mpesa_transactions', $id, 'update', null, ['event' => 'b2c_sent', 'conversation_id' => $reply['ConversationID']]);

        return DB::table('mpesa_transactions')->find($id);
    }

    /** Safaricom's result callback. */
    public function result(array $body): void
    {
        $r = $body['Result'] ?? [];
        $conversation = (string) ($r['ConversationID'] ?? '');
        $row = $conversation !== '' ? DB::table('mpesa_transactions')->where('type', 'b2c')->where('conversation_id', $conversation)->first() : null;
        if (! $row) {
            Log::warning('B2C result for an unknown conversation', ['conversation' => $conversation]);

            return;
        }
        if ($row->status !== 'pending') {
            return; // already handled (Safaricom can repeat a callback)
        }
        $params = collect($r['ResultParameters']['ResultParameter'] ?? [])->mapWithKeys(fn ($p) => [$p['Key'] ?? '' => $p['Value'] ?? null]);
        $payload = (json_decode((string) $row->payload, true) ?: []) + ['result' => $r];

        if ((int) ($r['ResultCode'] ?? -1) !== 0) {
            DB::table('mpesa_transactions')->where('id', $row->id)->update(['status' => 'failed', 'payload' => json_encode($payload),
                'note' => mb_substr('Not paid: ' . ($r['ResultDesc'] ?? 'no reason'), 0, 255), 'updated_at' => now()]);
            AuditTrail::record('mpesa_transactions', $row->id, 'update', ['status' => 'pending'], ['status' => 'failed', 'reason' => $r['ResultDesc'] ?? null]);

            return;
        }

        $receipt = strtoupper((string) ($r['TransactionID'] ?? $params['TransactionReceipt'] ?? ''));
        DB::transaction(function () use ($row, $receipt, $payload) {
            $loan = Loan::lockForUpdate()->findOrFail($row->loan_id);
            $d = app(DisbursementService::class)->createDisbursement($loan, [
                'amount' => (float) $row->amount, 'transaction' => $receipt, 'mpesa_ref' => $receipt, 'mode' => 'mpesa',
                'disburse_date' => now()->toDateString(), 'payment_date' => now()->toDateString(), 'funding_source' => 'internal',
                'notes' => 'Sent by M-Pesa B2C to ' . $row->msisdn . ' (payout #' . $row->id . ')',
            ], $loan->getCurrentCycle());
            if ($loan->status === Loan::STATUS_APPROVED) {
                $loan->status = 'disbursed';
                $loan->save();
            }
            // a partner borrowing for themselves is their own broker (owner, 23 Oct 2026), as on the Disburse form
            $links = app(\App\Services\Partners\PartnerLinks::class);
            if (! $loan->broker_status && ($own = $links->ownPartner($loan))) {
                $links->apply($loan, $own->id, null, null, 'disbursement');
            }
            DB::table('mpesa_transactions')->where('id', $row->id)->update(['status' => 'matched', 'trans_id' => $receipt ?: null, 'disbursement_id' => $d->id,
                'trans_time' => now(), 'payload' => json_encode($payload), 'note' => 'Paid', 'updated_at' => now()]);
            AuditTrail::record('mpesa_transactions', $row->id, 'update', ['status' => 'pending'], ['status' => 'matched', 'receipt' => $receipt, 'disbursement_id' => $d->id]);
        });
    }

    /** Safaricom's queue timed out: we can't tell whether it was paid. */
    public function timeout(array $body): void
    {
        $conversation = (string) ($body['Result']['ConversationID'] ?? $body['ConversationID'] ?? '');
        DB::table('mpesa_transactions')->where('type', 'b2c')->where('conversation_id', $conversation)->where('status', 'pending')
            ->update(['note' => 'Timed out at Safaricom: check the M-Pesa statement before doing anything else', 'updated_at' => now()]);
    }
}
