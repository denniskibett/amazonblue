<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Borrower and partner requests with a status the requester sees (Stage 4 Phase 5; switch
 * lending.requests.enabled). The Stage 2 email to staff and audit row still happen; this adds the stored request,
 * the staff decision, and what the decision does:
 *   borrower extension  approve = the rollover is booked exactly as previewed (Loans\Rollover); decline = note
 *   borrower plan       acceptance stored with its version, IP, user agent and a hash of the plan terms
 *   partner funding     confirm = the capital is added to the partner's journal (contribution)
 *   partner withdrawal  prepare (checks what is available) -> a second person approves -> paid from the wallet
 */
class Requests
{
    public static function enabled(): bool
    {
        return (bool) config('lending.requests.enabled');
    }

    public function borrower(string $type, User $user, ?Loan $loan, ?string $message, array $payload = []): ?int
    {
        if (! self::enabled()) {
            return null;
        }
        $request = request();

        return DB::table('borrower_requests')->insertGetId([
            'type' => $type, 'user_id' => $user->id, 'loan_id' => $loan?->id, 'message' => $message,
            'payload' => json_encode($payload + ['ip' => $request?->ip(), 'user_agent' => mb_substr((string) $request?->userAgent(), 0, 300)]),
            'status' => $type === 'plan' ? 'done' : 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** The borrower's latest request of a type on a loan, shaped like the audit row the views already read. */
    public static function latestBorrower(Loan $loan, string $type): ?object
    {
        $r = DB::table('borrower_requests')->where('loan_id', $loan->id)->where('user_id', $loan->user_id)->where('type', $type)->latest('id')->first();
        if ($r) {
            $r->created_at = Carbon::parse($r->created_at);
        }

        return $r;
    }

    public function partner(string $type, Partner $partner, float $amount, ?string $wallet, ?string $reference, ?string $note, ?User $by): ?int
    {
        if (! self::enabled()) {
            return null;
        }

        return DB::table('partner_requests')->insertGetId([
            'type' => $type, 'partner_id' => $partner->id, 'amount' => $amount, 'wallet_slug' => $wallet, 'reference' => $reference,
            'note' => $note, 'status' => 'pending', 'requested_by' => $by?->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** Staff decide a borrower request. Approving an extension books the rollover. */
    /**
     * @param  array{date?: ?string, interest_rate?: ?float}  $options  the day the extension is booked from (default: the
     *         cycle's due date, so the new cycle starts where the old one ended; a later day for a late loan) and,
     *         from cycle 5, the rate staff set
     */
    public function decideBorrower(int $id, User $by, bool $approve, ?string $note, array $options = []): void
    {
        DB::transaction(function () use ($id, $by, $approve, $note, $options) {
            $r = DB::table('borrower_requests')->where('id', $id)->lockForUpdate()->first();
            if (! $r || $r->status !== 'open') {
                throw new \DomainException('This request is no longer open.');
            }
            if ($approve && $r->type === 'extension') {
                if (! $by->can('rollover loans')) {
                    throw new \DomainException('You need the "rollover loans" permission to extend a loan.');
                }
                $loan = Loan::findOrFail($r->loan_id);
                $opts = $this->extensionOptions($loan, $options);
                app(LoanCalculator::class)->executeRollover($loan, $opts + ['notes' => 'Extension requested by the borrower (request #' . $id . ')'
                    . ' from ' . \Illuminate\Support\Carbon::parse($opts['date'])->format('j M Y')]);
            }
            if ($approve && $r->type === 'business_change') {
                // reopen the verified business so the owner can change it; it is checked again (owner, 24 Oct 2026)
                if (! $by->can('edit users')) {
                    throw new \DomainException('You need the "edit users" permission to reopen a business.');
                }
                $business = \App\Models\Business::findOrFail($r->business_id);
                $business->update(['status' => \App\Models\Business::STATUS_REJECTED, 'reviewed_by' => $by->id, 'reviewed_at' => now(),
                    'review_notes' => 'Reopened for your changes: ' . $r->message . ($note ? ' — ' . $note : '')]);
            }
            DB::table('borrower_requests')->where('id', $id)->update(['status' => $approve ? 'approved' : 'declined',
                'decision_note' => $note, 'decided_by' => $by->id, 'decided_at' => now(), 'updated_at' => now()]);
            AuditTrail::record('borrower_requests', $id, 'update', ['status' => 'open'], ['status' => $approve ? 'approved' : 'declined', 'note' => $note]);
            if ($r->loan_id && ($loan = Loan::find($r->loan_id))) {
                $text = $approve ? 'We agreed your ' . $r->type . ' request on loan ' . $loan->id . '. Your account shows the new terms.'
                    : 'We couldn\'t agree your ' . $r->type . ' request on loan ' . $loan->id . '. ' . ($note ?: 'Talk to us about other options.');
                DB::afterCommit(fn () => app(CustomerEvents::class)->loan('request_decided', $loan, ['approved' => $approve, 'text' => $text]));
            }
        });
    }


    /**
     * The options an extension is booked with: from the cycle's due date unless another day is chosen. A day before
     * the borrower's last payment on the cycle is refused (that payment would not count towards what is carried).
     */
    public function extensionOptions(Loan $loan, array $options = []): array
    {
        $cycle = $loan->cycles()->orderByDesc('cycle_number')->firstOrFail();
        $default = \Illuminate\Support\Carbon::parse($cycle->due_date)->min(now());   // not due yet: today (the new cycle still starts on the due date)
        $day = \Illuminate\Support\Carbon::parse(! empty($options['date']) ? $options['date'] : $default)->startOfDay();
        if ($day->lt(\Illuminate\Support\Carbon::parse($cycle->start_date)->addDay()) || $day->gt(now()->startOfDay())) {
            throw new \DomainException('Pick a day after cycle ' . $cycle->cycle_number . ' started (' . $cycle->start_date->format('j M Y') . ') and no later than today.');
        }
        $lastPaid = \App\Models\Repayment::where('loan_cycle_id', $cycle->id)->max('repayment_date');
        if ($lastPaid && \Illuminate\Support\Carbon::parse($lastPaid)->startOfDay()->gt($day)) {
            throw new \DomainException('The borrower paid on ' . \Illuminate\Support\Carbon::parse($lastPaid)->format('j M Y')
                . ', after that day. Pick ' . \Illuminate\Support\Carbon::parse($lastPaid)->format('j M Y') . ' or later so the payment counts.');
        }

        return array_filter(['date' => $day->toDateString(),
            'interest_rate' => isset($options['interest_rate']) && $options['interest_rate'] !== '' ? (float) $options['interest_rate'] : null], fn ($v) => $v !== null);
    }

    /** Funding: confirm the money arrived (adds the capital) or decline. */
    public function confirmFunding(int $id, User $by, bool $received, ?string $note): void
    {
        DB::transaction(function () use ($id, $by, $received, $note) {
            $r = DB::table('partner_requests')->where('id', $id)->where('type', 'funding')->lockForUpdate()->first();
            if (! $r || $r->status !== 'pending') {
                throw new \DomainException('This request is no longer pending.');
            }
            $payload = json_decode((string) ($r->payload ?? ''), true) ?: [];
            $lineId = null;
            if ($received && ($payload['kind'] ?? null) === 'subscription') {
                // an online subscription (Partners\Subscription): the money opens the line the partner signed for
                if (! $r->reference) {
                    throw new \DomainException('The partner has not said they sent the money yet (no transaction code).');
                }
                $line = app(\App\Services\Partners\PartnerLines::class)->open(Partner::findOrFail($r->partner_id), (float) $r->amount,
                    $payload['term'], $payload['maturity'] ?? null, $r->reference, $payload['sent']['on'] ?? null, $by,
                    'Online subscription (request #' . $r->id . ')');
                $lineId = $line->id;
                $journal = \App\Models\PartnerTransaction::where('investment_id', $line->id)->where('type', 'contribution')->latest('id')->first();
            } else {
                $journal = $received ? Partner::findOrFail($r->partner_id)->addContribution((float) $r->amount, 'FUND-PR' . $r->id . '-' . $r->reference,
                    'Funds received (request #' . $r->id . ')') : null;
            }
            DB::table('partner_requests')->where('id', $id)->update(['status' => $received ? 'done' : 'declined', 'prepared_by' => $by->id,
                'partner_transaction_id' => $journal?->id, 'investment_id' => $lineId, 'decision_note' => $note, 'updated_at' => now()]);
            if ($received) {
                $user = User::find(Partner::whereKey($r->partner_id)->value('user_id'));
                DB::afterCommit(fn () => app(CustomerEvents::class)->partner('funds_confirmed', $user, (float) $r->amount));
            }
        });
    }

    /**
     * Withdrawal: one person prepares it (the amount must still be available); with maker-checker on, a second
     * person approves on /staff/sign-offs and the payout is posted then; with it off, it is paid now.
     */
    public function prepareWithdrawal(int $id, User $by, bool $pay, ?string $note): void
    {
        DB::transaction(function () use ($id, $by, $pay, $note) {
            $r = DB::table('partner_requests')->where('id', $id)->where('type', 'withdrawal')->lockForUpdate()->first();
            if (! $r || $r->status !== 'pending') {
                throw new \DomainException('This request is no longer pending.');
            }
            if (! $pay) {
                DB::table('partner_requests')->where('id', $id)->update(['status' => 'declined', 'prepared_by' => $by->id, 'decision_note' => $note, 'updated_at' => now()]);

                return;
            }
            $wallet = Partner::findOrFail($r->partner_id)->getWallet($r->wallet_slug ?: 'principal');
            if (! $wallet || (float) $wallet->balanceFloat + 0.0001 < (float) $r->amount) {
                throw new \DomainException('The partner no longer has KES ' . number_format((float) $r->amount, 2) . ' available in that wallet.');
            }
            DB::table('partner_requests')->where('id', $id)->update(['status' => 'prepared', 'prepared_by' => $by->id, 'decision_note' => $note, 'updated_at' => now()]);
            $approvals = app(Approvals::class);
            if ($approvals->needsSecond($by, 'partner_withdrawal', (float) $r->amount)) {
                $approvals->request($by, 'partner_withdrawal', 'partner_requests', $id, (float) $r->amount, ['reason' => $note]);
            } else {
                app(ApprovalActions::class)->run((object) ['action' => 'partner_withdrawal', 'subject_id' => $id, 'requested_by' => $by->id, 'payload' => '{}'], $by);
            }
        });
    }
}
