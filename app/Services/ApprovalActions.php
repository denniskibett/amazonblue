<?php

namespace App\Services;

use App\Models\DebtRecoveryCase;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\Repayment;
use App\Models\User;
use App\Services\Loans\Reversal;
use App\Services\Loans\WriteOff;
use Illuminate\Support\Facades\DB;

/** What each maker-checker action does once the checker approves it (Stage 4 Phase 5). */
class ApprovalActions
{
    public function run(object $a, User $checker): mixed
    {
        $payload = json_decode((string) $a->payload, true) ?: [];
        $maker = User::find($a->requested_by);

        return match ($a->action) {
            'approve_loan' => $this->approveLoan(Loan::findOrFail($a->subject_id), $maker, $checker),
            'write_off' => app(WriteOff::class)->perform(DebtRecoveryCase::findOrFail($a->subject_id), (string) ($payload['reason'] ?? ''), $checker, $maker),
            'reversal' => app(Reversal::class)->reverse(Repayment::findOrFail($a->subject_id),
                trim(($payload['reason'] ?? '') . ' (asked by ' . ($maker->name ?? '#' . $a->requested_by) . ', approved by ' . $checker->name . ')')),
            'partner_withdrawal' => $this->payPartner((int) $a->subject_id, $checker),
            default => throw new \DomainException('Unknown action ' . $a->action),
        };
    }

    private function approveLoan(Loan $loan, ?User $maker, User $checker): Loan
    {
        if ($loan->status !== Loan::STATUS_PENDING) {
            throw new \DomainException('Loan #' . $loan->id . ' is ' . $loan->status . ', no longer pending.');
        }
        $loan->update(['status' => Loan::STATUS_APPROVED, 'recovery_notes' => ($loan->recovery_notes ? $loan->recovery_notes . "\n" : '')
            . 'Approved by ' . ($maker->name ?? '?') . ' and ' . $checker->name . ' (second sign-off) on ' . now()->format('Y-m-d H:i')]);

        return $loan;
    }

    private function payPartner(int $requestId, User $checker): object
    {
        $r = DB::table('partner_requests')->where('id', $requestId)->lockForUpdate()->first();
        if (! $r || $r->status !== 'prepared') {
            throw new \DomainException('This withdrawal is not waiting for approval.');
        }
        $partner = Partner::findOrFail($r->partner_id);
        $journal = $partner->withdrawPartnerBalance((float) $r->amount, 'PAYOUT-PR' . $r->id, 'Payout (request #' . $r->id . ')', $r->wallet_slug ?: 'principal');
        DB::table('partner_requests')->where('id', $r->id)->update(['status' => 'done', 'approved_by' => $checker->id,
            'partner_transaction_id' => $journal->id, 'updated_at' => now()]);
        $user = User::find($partner->user_id);
        DB::afterCommit(fn () => app(CustomerEvents::class)->partner('withdrawal_paid', $user, (float) $r->amount));

        return $journal;
    }
}
