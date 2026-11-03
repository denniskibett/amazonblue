<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Maker-checker (Stage 4 Phase 5, decision 14; switch lending.approvals.enabled).
 *
 *   approve_loan        approving a loan above the approver's approval_limits row (50,000) needs a second approver
 *   write_off           always needs a second person
 *   reversal            reversing a repayment entered before today always needs a second person
 *   partner_withdrawal  paying a partner out: prepared by one person, approved by another
 *
 * The checker must hold the action's permission, can never be the maker (also a CHECK in the database), and
 * the action runs only when the checker approves. Every decision is in the audit log.
 */
class Approvals
{
    public const ACTIONS = [
        'approve_loan' => ['permission' => 'approve loans', 'label' => 'Approve loan'],
        'write_off' => ['permission' => 'write off loan', 'label' => 'Write off'],
        'reversal' => ['permission' => 'delete repayments', 'label' => 'Reverse repayment'],
        'partner_withdrawal' => ['permission' => 'edit partners', 'label' => 'Pay a partner out'],
        // owner, 24 Oct 2026
        'approve_business' => ['permission' => 'edit users', 'label' => 'Approve a business and its limit'],
        'mpesa_b2c' => ['permission' => 'disburse loans', 'label' => 'Send a disbursement by M-Pesa'],
    ];

    public static function enabled(): bool
    {
        return (bool) config('lending.approvals.enabled');
    }

    /** The most this user may do alone for an action (the highest of their roles), or null for no limit row. */
    public function limitFor(User $user, string $action): ?float
    {
        $max = DB::table('approval_limits')->where('action', $action)->whereIn('role', $user->getRoleNames()->all())->max('max_amount');

        return $max === null ? null : (float) $max;
    }

    /** Whether a second person is needed for this user doing this action for this amount. */
    public function needsSecond(User $user, string $action, ?float $amount = null): bool
    {
        if (! self::enabled()) {
            return false;
        }
        if ($action !== 'approve_loan') {
            return true;
        }
        $limit = $this->limitFor($user, $action);

        return $limit !== null && (float) $amount > $limit;
    }

    /** Opens (or returns the open) request for a second person. */
    public function request(User $maker, string $action, string $subjectType, int $subjectId, ?float $amount, array $payload = []): object
    {
        $open = DB::table('approvals')->where('action', $action)->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)->where('status', 'pending')->first();
        if ($open) {
            return $open;
        }
        $id = DB::table('approvals')->insertGetId([
            'action' => $action, 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'amount' => $amount,
            'payload' => json_encode($payload), 'status' => 'pending', 'requested_by' => $maker->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        AuditTrail::record('approvals', $id, 'create', null, ['action' => $action, 'subject' => "{$subjectType}#{$subjectId}", 'amount' => $amount]);

        return DB::table('approvals')->find($id);
    }

    /**
     * The checker decides. On approval the action's executor runs in the same transaction; if it fails, the
     * request stays pending. Returns the executor's result (or null when rejected).
     */
    public function decide(int $approvalId, User $checker, bool $approve, ?string $reason = null): mixed
    {
        return DB::transaction(function () use ($approvalId, $checker, $approve, $reason) {
            $a = DB::table('approvals')->where('id', $approvalId)->lockForUpdate()->first();
            if (! $a || $a->status !== 'pending') {
                throw new \DomainException('This request is no longer waiting for a decision.');
            }
            if ((int) $a->requested_by === (int) $checker->id) {
                throw new \DomainException('You asked for this; a different person must decide it.');
            }
            $permission = self::ACTIONS[$a->action]['permission'] ?? null;
            if (! $permission || ! $checker->can($permission)) {
                throw new \DomainException('You need the "' . $permission . '" permission to decide this.');
            }

            $result = null;
            if ($approve) {
                $result = app(ApprovalActions::class)->run($a, $checker);
            }
            DB::table('approvals')->where('id', $a->id)->update([
                'status' => $approve ? 'approved' : 'rejected', 'decided_by' => $checker->id, 'decided_at' => now(),
                'reason' => $reason, 'updated_at' => now(),
            ]);
            AuditTrail::record('approvals', $a->id, 'update', ['status' => 'pending'],
                ['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $checker->id, 'reason' => $reason]);

            return $result;
        });
    }
}
