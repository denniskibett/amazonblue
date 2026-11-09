<?php

namespace App\Services\Sharet;

use App\Models\Loan;
use App\Models\Partner;
use App\Models\SharetPayout;
use App\Models\User;
use App\Services\Approvals;
use App\Services\AuditTrail;
use App\Services\DisbursementService;
use App\Services\Mpesa\Daraja;
use App\Support\StaffAlarm;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Money out through Sharet (9 Nov 2026; switch sharet.payouts_enabled). Nothing is sent that the app has not
 * already approved, and the amounts are the app's own:
 *
 *  - Loan disbursement: staff with "disburse loans" ask on an approved loan. With second sign-offs on, a second
 *    person approves it on /staff/sign-offs (action sharet_payout); with them off, the ask is the approval, exactly
 *    as when that person records a disbursement by hand. When Sharet reports it paid, the disbursement is recorded
 *    as the Disburse form records one (mode sharet).
 *  - Partner payout: staff tick "Pay through Sharet" when preparing a withdrawal on Requests. It is sent only after
 *    the existing withdrawal approval has posted it (partner_requests.status = done); a declined request cancels it.
 *
 * payments:sweep sends what is ready at 08:00, 12:00 and 16:00 Africa/Nairobi. Each row is claimed under a lock and
 * stamped with the slot before the call, so a second run in the same slot sends nothing; every attempt has its own
 * Idempotency-Key, and a call with no clear answer stays "paying" and is only ever re-asked with the same key.
 */
class SharetPayouts
{
    public static function enabled(): bool
    {
        return SharetCollections::available() && (bool) config('sharet.payouts_enabled');
    }

    public static function slot(): string
    {
        return now('Africa/Nairobi')->format('Y-m-d\TH');
    }

    /** Staff ask for an approved loan's money to go to the borrower's M-Pesa through Sharet. */
    public function requestDisbursement(Loan $loan, float $amount, string $phone, User $maker): SharetPayout
    {
        if (! self::enabled()) {
            throw new \DomainException('Payouts through Sharet are off (Integrations › Sharet Pay).');
        }
        if ($loan->status !== Loan::STATUS_APPROVED) {
            throw new \DomainException('Only an approved loan can be paid out.');
        }
        $msisdn = Daraja::msisdn($phone) ?? throw new \DomainException('Enter a Kenyan M-Pesa number, e.g. 0712 345 678.');
        $amount = floor($amount);
        if ($amount < 10 || $amount > (float) $loan->amount) {
            throw new \DomainException('The amount must be between KES 10 and the loan amount (KES ' . number_format((float) $loan->amount, 2) . ').');
        }

        return DB::transaction(function () use ($loan, $amount, $msisdn, $maker) {
            Loan::whereKey($loan->id)->lockForUpdate()->first();
            if (SharetPayout::where('kind', 'disbursement')->where('loan_id', $loan->id)->whereIn('status', array_merge(SharetPayout::OPEN, ['paid']))->exists()
                || DB::table('mpesa_transactions')->where('type', 'b2c')->where('loan_id', $loan->id)->whereIn('status', ['pending', 'matched'])->exists()) {
                throw new \DomainException('This loan already has a payout waiting or paid.');
            }
            $second = Approvals::enabled();
            $row = SharetPayout::create(['kind' => 'disbursement', 'loan_id' => $loan->id, 'amount' => $amount,
                'destination' => ['type' => 'mpesa', 'phone' => $msisdn], 'status' => $second ? 'awaiting_approval' : 'queued',
                'requested_by' => $maker->id, 'approved_by' => $second ? null : $maker->id]);
            if ($second) {
                app(Approvals::class)->request($maker, 'sharet_payout', 'sharet_payouts', $row->id, $amount, ['loan_id' => $loan->id]);
            }
            AuditTrail::record('sharet_payouts', $row->id, 'create', null, ['event' => 'sharet_disbursement_requested', 'loan_id' => $loan->id, 'amount' => $amount]);

            return $row;
        });
    }

    /** The second person approved on /staff/sign-offs (ApprovalActions). Queues it for the next run; sends nothing now. */
    public function approve(int $id, User $checker): SharetPayout
    {
        $row = SharetPayout::whereKey($id)->lockForUpdate()->first();
        if (! $row || $row->status !== 'awaiting_approval' || $row->kind !== 'disbursement') {
            throw new \DomainException('This payout is not waiting for approval.');
        }
        $row->update(['status' => 'queued', 'approved_by' => $checker->id]);

        return $row;
    }

    /** A partner withdrawal on Requests, to be paid through Sharet once its own approval has posted it. */
    public function queuePartnerPayout(int $partnerRequestId, string $phone, User $by): SharetPayout
    {
        if (! self::enabled()) {
            throw new \DomainException('Payouts through Sharet are off (Integrations › Sharet Pay).');
        }
        $msisdn = Daraja::msisdn($phone) ?? throw new \DomainException('Enter the partner\'s Kenyan M-Pesa number.');
        $r = DB::table('partner_requests')->where('id', $partnerRequestId)->where('type', 'withdrawal')->first();
        if (! $r || in_array($r->status, ['declined'], true) || $r->bank_reference) {
            throw new \DomainException('That withdrawal can\'t be paid through Sharet.');
        }
        if (fmod((float) $r->amount, 1.0) !== 0.0) {
            throw new \DomainException('Sharet pays whole shillings; pay KES ' . number_format((float) $r->amount, 2) . ' another way.');
        }
        if (SharetPayout::where('partner_request_id', $r->id)->whereIn('status', array_merge(SharetPayout::OPEN, ['paid']))->exists()) {
            throw new \DomainException('That withdrawal is already set to be paid through Sharet.');
        }
        $row = SharetPayout::create(['kind' => 'partner_payout', 'partner_request_id' => $r->id, 'partner_id' => $r->partner_id,
            'amount' => floor((float) $r->amount), 'destination' => ['type' => 'mpesa', 'phone' => $msisdn], 'status' => 'awaiting_approval',
            'requested_by' => $by->id]);
        AuditTrail::record('sharet_payouts', $row->id, 'create', null, ['event' => 'sharet_partner_payout_requested', 'partner_request_id' => $r->id, 'amount' => $row->amount]);

        return $row;
    }

    public function cancel(SharetPayout $row, User $by): void
    {
        DB::transaction(function () use ($row, $by) {
            $row = SharetPayout::whereKey($row->id)->lockForUpdate()->first();
            if (! in_array($row->status, ['awaiting_approval', 'queued', 'failed'], true)) {
                throw new \DomainException('Only a payout that has not been sent can be cancelled.');
            }
            $row->update(['status' => 'cancelled', 'failure' => 'Cancelled by ' . $by->name]);
            DB::table('approvals')->where('action', 'sharet_payout')->where('subject_id', $row->id)->where('status', 'pending')
                ->update(['status' => 'cancelled', 'updated_at' => now()]);
            AuditTrail::record('sharet_payouts', $row->id, 'update', null, ['event' => 'sharet_payout_cancelled']);
        });
    }

    /** A payout Sharet reported failed (nothing left our wallet) goes back in the queue with a new Idempotency-Key. */
    public function retry(SharetPayout $row, User $by): void
    {
        DB::transaction(function () use ($row, $by) {
            $row = SharetPayout::whereKey($row->id)->lockForUpdate()->first();
            if ($row->status !== 'failed') {
                throw new \DomainException('Only a failed payout can be tried again.');
            }
            $row->update(['status' => 'queued', 'attempt' => $row->attempt + 1, 'idempotency_key' => null, 'request_body' => null,
                'sharet_payment_id' => null, 'slot' => null, 'failure' => null]);
            AuditTrail::record('sharet_payouts', $row->id, 'update', null, ['event' => 'sharet_payout_retry', 'by' => $by->id, 'attempt' => $row->attempt]);
        });
    }

    /**
     * Send everything ready. @return array<int, array{id:int, kind:string, status:string, amount?:float, message?:string}>
     */
    public function sweep(?string $slot = null, bool $dryRun = false): array
    {
        if (! self::enabled()) {
            return [['id' => 0, 'kind' => '-', 'status' => 'skipped', 'message' => 'Payouts through Sharet are off']];
        }
        $slot ??= self::slot();
        $client = SharetClient::make();
        $results = [];

        foreach (SharetPayout::whereIn('status', ['awaiting_approval', 'queued'])->orderBy('id')->get() as $row) {
            [$ready, $why] = $this->ready($row);
            if ($ready === null) {
                $results[] = ['id' => $row->id, 'kind' => $row->kind, 'status' => 'waiting', 'message' => $why];

                continue;
            }
            if ($ready === false) {
                $dryRun ?: $row->update(['status' => 'cancelled', 'failure' => $why]);
                $results[] = ['id' => $row->id, 'kind' => $row->kind, 'status' => 'cancelled', 'message' => $why];

                continue;
            }
            if ($dryRun) {
                $results[] = ['id' => $row->id, 'kind' => $row->kind, 'status' => 'would_pay', 'amount' => (float) $row->amount, 'message' => $row->destinationLabel()];

                continue;
            }

            $claimed = DB::transaction(function () use ($row, $slot, $client) {
                $r = SharetPayout::whereKey($row->id)->lockForUpdate()->first();
                if (! in_array($r->status, ['awaiting_approval', 'queued'], true) || $r->slot === $slot) {
                    return null; // another run took it, or this slot already tried it
                }
                $key = 'aimzone-payout-' . $r->id . '-' . $r->attempt;
                $body = ['amount' => number_format((float) $r->amount, 2, '.', ''), 'to' => $r->destination,
                    'reference' => $r->kind === 'disbursement' ? 'Loan ' . $r->loan_id . ' disbursement' : 'Partner payout PR' . $r->partner_request_id];
                $r->update(['status' => 'paying', 'slot' => $slot, 'idempotency_key' => $key, 'request_body' => $body, 'environment' => $client->environment]);

                return $r;
            });
            if (! $claimed) {
                $results[] = ['id' => $row->id, 'kind' => $row->kind, 'status' => 'skipped', 'message' => "Slot {$slot} already ran it"];

                continue;
            }
            $this->send($claimed, $client);
            $claimed->refresh();
            $results[] = ['id' => $claimed->id, 'kind' => $claimed->kind, 'status' => $claimed->status, 'amount' => (float) $claimed->amount, 'message' => $claimed->failure];
        }

        return $results;
    }

    /** true = send, false = cancel (with why), null = not yet (with why). */
    private function ready(SharetPayout $row): array
    {
        if ($row->kind === 'disbursement') {
            $loan = Loan::find($row->loan_id);
            if (! $loan || $loan->status !== Loan::STATUS_APPROVED) {
                return [false, 'Loan #' . $row->loan_id . ' is ' . ($loan->status ?? 'missing') . ', no longer waiting for its money'];
            }

            return $row->status === 'queued' ? [true, null] : [null, 'Waiting for a second sign-off'];
        }
        $r = DB::table('partner_requests')->where('id', $row->partner_request_id)->first();

        return match (true) {
            ! $r || $r->status === 'declined' => [false, 'The withdrawal was declined'],
            (bool) $r->bank_reference => [false, 'The withdrawal was paid another way (' . $r->bank_reference . ')'],
            $r->status === 'done' => [true, null],
            default => [null, 'Waiting for the withdrawal\'s approval'],
        };
    }

    /** One call to Sharet with the row's key and body (a repeat replays Sharet's first answer). */
    public function send(SharetPayout $row, ?SharetClient $client = null): void
    {
        $client ??= SharetClient::make();
        try {
            $res = $client->post('/payments', $row->request_body, $row->idempotency_key);
        } catch (\Throwable $e) {
            $this->unknown($row, $e->getMessage());

            return;
        }
        $this->answer($row, $res);
    }

    private function answer(SharetPayout $row, Response $res): void
    {
        if ($res->successful()) {
            $this->record($row, (array) $res->json());

            return;
        }
        if ($res->status() === 409 || $res->serverError()) {
            $this->unknown($row, 'HTTP ' . $res->status());

            return;
        }
        // a clear refusal: nothing moved and Sharet forgot the key, so the next run may send it again
        $why = mb_substr('Refused by Sharet: ' . ($res->json('detail') ?? $res->json('message') ?? $res->json('title') ?? ('HTTP ' . $res->status())), 0, 250);
        $row->update(['status' => 'queued', 'failure' => $why]);
        StaffAlarm::raise('sharet-payout-refused', 'Sharet refused a payout', $why . ' It will be tried again at the next run; check the Sharet balance.', ['payout' => $row->id]);
    }

    /** No clear answer: it may have gone. Never sent with a new key; sharet:sync asks again with the same one. */
    private function unknown(SharetPayout $row, string $why): void
    {
        $row->update(['failure' => mb_substr('No clear answer from Sharet yet (' . $why . '); sharet:sync asks again with the same key', 0, 250)]);
        Log::warning('Sharet payout with no clear answer', ['payout' => $row->id, 'why' => $why]);
    }

    /** A Sharet payment object (the call's answer, a payment.* webhook or a poll). Records the money once. */
    public function record(SharetPayout $row, array $payment): void
    {
        DB::transaction(function () use ($row, $payment) {
            $row = SharetPayout::whereKey($row->id)->lockForUpdate()->first();
            if (in_array($row->status, ['paid', 'cancelled'], true)) {
                return;
            }
            $status = (string) ($payment['status'] ?? '');
            if ($row->status === 'failed' && $status !== 'paid' && ($payment['id'] ?? null) === $row->sharet_payment_id) {
                return; // the same failure again (the webhook, then Sharet's answer): one record and one alarm
            }
            $fill = ['sharet_payment_id' => $payment['id'] ?? $row->sharet_payment_id];
            if ($status === 'paying' || $status === '') {
                $row->update($fill + ['status' => 'paying', 'failure' => null]);

                return;
            }
            if ($status !== 'paid') {
                $why = mb_substr('Sharet: ' . (is_array($payment['failure'] ?? null) ? json_encode($payment['failure']) : ($payment['failure'] ?? $status)), 0, 250);
                $row->update($fill + ['status' => 'failed', 'failure' => $why]);
                AuditTrail::record('sharet_payouts', $row->id, 'update', ['status' => 'paying'], ['status' => 'failed', 'reason' => $why]);
                DB::afterCommit(fn () => StaffAlarm::raise('sharet-payout-failed', 'Sharet payout failed', $why . ' Retry or cancel it on /staff/sharet.', ['payout' => $row->id]));

                return;
            }

            $ref = strtoupper((string) ($payment['transaction_id'] ?? '')) ?: (string) ($payment['id'] ?? ('SHARET-' . $row->id));
            $fill += ['status' => 'paid', 'sharet_transaction_id' => $ref, 'paid_at' => now(), 'failure' => null];
            if ($row->kind === 'disbursement') {
                $fill['disbursement_id'] = $this->disburse($row, $ref)?->id;
            } else {
                $this->partnerPaid($row, $ref);
            }
            $row->update($fill);
            AuditTrail::record('sharet_payouts', $row->id, 'update', ['status' => 'paying'], ['status' => 'paid', 'reference' => $ref]);
        });
    }

    /** As the B2C result and the Disburse form record a disbursement. */
    private function disburse(SharetPayout $row, string $ref): ?\App\Models\Disbursement
    {
        $loan = Loan::lockForUpdate()->find($row->loan_id);
        if (! $loan) {
            return null;
        }
        $d = app(DisbursementService::class)->createDisbursement($loan, [
            'amount' => (float) $row->amount, 'transaction' => $ref, 'mode' => 'sharet',
            'disburse_date' => now()->toDateString(), 'payment_date' => now()->toDateString(), 'funding_source' => 'internal',
            'notes' => 'Sent through Sharet to ' . $row->destinationLabel() . ' (payout #' . $row->id . ')',
        ], $loan->getCurrentCycle());
        if ($loan->status === Loan::STATUS_APPROVED) {
            $loan->status = 'disbursed';
            $loan->save();
        }
        $links = app(\App\Services\Partners\PartnerLinks::class);
        if (! $loan->broker_status && ($own = $links->ownPartner($loan))) {
            $links->apply($loan, $own->id, null, null, 'disbursement');
        }

        return $d;
    }

    /** The withdrawal was already posted by its approval; this adds the day and Sharet's reference. */
    private function partnerPaid(SharetPayout $row, string $ref): void
    {
        $r = DB::table('partner_requests')->where('id', $row->partner_request_id)->first();
        if (! $r) {
            return;
        }
        DB::table('partner_requests')->where('id', $r->id)->update(['paid_on' => now()->toDateString(), 'bank_reference' => mb_substr($ref, 0, 60),
            'decision_note' => mb_substr(trim(($r->decision_note ?? '') . ' Sent through Sharet ' . now()->format('Y-m-d') . ' · ' . $ref), 0, 500), 'updated_at' => now()]);
    }

    /** sharet:sync: rows still "paying" are asked about (by id, or by re-sending with the same key). */
    public function syncPaying(): int
    {
        if (! SharetCollections::available()) {
            return 0;
        }
        $client = SharetClient::make();
        $n = 0;
        foreach (SharetPayout::where('status', 'paying')->orderBy('id')->limit(100)->get() as $row) {
            if ($row->sharet_payment_id) {
                $res = $client->get('/payments/' . $row->sharet_payment_id);
                if ($res->successful()) {
                    $this->record($row, (array) $res->json());
                }
            } elseif ($row->idempotency_key && $row->request_body) {
                $this->send($row, $client);
            }
            $n++;
        }

        return $n;
    }
}
