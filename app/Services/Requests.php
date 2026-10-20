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
    public function decideBorrower(int $id, User $by, bool $approve, ?string $note): void
    {
        DB::transaction(function () use ($id, $by, $approve, $note) {
            $r = DB::table('borrower_requests')->where('id', $id)->lockForUpdate()->first();
            if (! $r || $r->status !== 'open') {
                throw new \DomainException('This request is no longer open.');
            }
            if ($approve && $r->type === 'extension') {
                if (! $by->can('rollover loans')) {
                    throw new \DomainException('You need the "rollover loans" permission to extend a loan.');
                }
                app(LoanCalculator::class)->executeRollover(Loan::findOrFail($r->loan_id), ['notes' => 'Extension requested by the borrower (request #' . $id . ')']);
            }
            DB::table('borrower_requests')->where('id', $id)->update(['status' => $approve ? 'approved' : 'declined',
                'decision_note' => $note, 'decided_by' => $by->id, 'decided_at' => now(), 'updated_at' => now()]);
            AuditTrail::record('borrower_requests', $id, 'update', ['status' => 'open'], ['status' => $approve ? 'approved' : 'declined', 'note' => $note]);
        });
    }

    /** Funding: confirm the money arrived (adds the capital) or decline. */
    public function confirmFunding(int $id, User $by, bool $received, ?string $note): void
    {
        DB::transaction(function () use ($id, $by, $received, $note) {
            $r = DB::table('partner_requests')->where('id', $id)->where('type', 'funding')->lockForUpdate()->first();
            if (! $r || $r->status !== 'pending') {
                throw new \DomainException('This request is no longer pending.');
            }
            $journal = $received ? Partner::findOrFail($r->partner_id)->addContribution((float) $r->amount, 'FUND-PR' . $r->id . '-' . $r->reference,
                'Funds received (request #' . $r->id . ')') : null;
            DB::table('partner_requests')->where('id', $id)->update(['status' => $received ? 'done' : 'declined', 'prepared_by' => $by->id,
                'partner_transaction_id' => $journal?->id, 'decision_note' => $note, 'updated_at' => now()]);
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
