<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\AuditTrail;
use Illuminate\Support\Facades\DB;

/**
 * One owner decision on one loan, audited (Feature 2b; first used 21 Oct 2026). Every decision is re-checked against
 * today's data, saved in one transaction, and the status worked out again from the latest cycle.
 *
 *   forbearance  penalty waived on the latest cycle until a date (open-ended = GraceFix::OPEN_ENDED); the default
 *                flags are cleared and the nightly run leaves it alone while the waiver lasts
 *   active       a default that the cycle does not support (on time, or within its threshold) is cleared
 */
class LoanDecision
{
    public const ACTIONS = ['forbearance', 'active'];

    /** What the decision would do, without saving: [before, after, reason]. */
    public function preview(Loan $loan, string $action, ?string $until = null): array
    {
        $cycle = LoanCycle::where('loan_id', $loan->id)->orderByDesc('cycle_number')->first()
            ?? throw new \RuntimeException("Loan #{$loan->id} has no cycle.");
        $b = CycleBalance::at($cycle);
        $before = ['status' => $loan->status, 'owed' => $b['final_outstanding'], 'penalty' => $b['penalty'], 'due' => $cycle->due_date->toDateString(),
            'waived_until' => $cycle->penalty_waived_until?->toDateString(), 'default_triggered' => (bool) $loan->default_triggered];

        if ($action === 'forbearance') {
            abort_if($b['final_outstanding'] <= 0.004, 422, "Loan #{$loan->id} owes nothing.");
            $until = $until ?: GraceFix::OPEN_ENDED;
            $after = CycleBalance::compute(['waived_until' => $until] + CycleBalance::termsFor($cycle), CycleBalance::paymentsFor($cycle), now()->toDateString());

            return [$before, ['status' => Loan::STATUS_FORBEARANCE, 'owed' => $after['final_outstanding'], 'penalty' => $after['penalty'], 'waived_until' => $until, 'default_triggered' => false],
                'penalty waived ' . ($until === GraceFix::OPEN_ENDED ? 'open-ended' : 'until ' . $until)];
        }
        if ($action === 'active') {
            if ($b['is_defaulted']) {
                throw new \RuntimeException("Loan #{$loan->id} is {$b['days_overdue']} day(s) late, past its {$b['default_threshold_days']}-day threshold: the cycle supports the default.");
            }

            return [$before, ['status' => $b['final_outstanding'] <= 0.004 ? Loan::STATUS_REPAID : ($b['is_overdue'] ? Loan::STATUS_OVERDUE : Loan::STATUS_DISBURSED),
                'owed' => $b['final_outstanding'], 'default_triggered' => false], 'default cleared: the latest cycle is due ' . $cycle->due_date->toDateString()];
        }

        throw new \InvalidArgumentException("Unknown action {$action}.");
    }

    public function apply(Loan $loan, string $action, ?string $until = null, ?string $note = null): array
    {
        [$before, $after, $reason] = $this->preview($loan, $action, $until);

        DB::transaction(function () use ($loan, $action, $after, $before, $reason, $note) {
            $locked = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            $cycle = LoanCycle::where('loan_id', $locked->id)->orderByDesc('cycle_number')->lockForUpdate()->firstOrFail();
            $clear = ['default_triggered' => false, 'is_non_performing' => false, 'default_triggered_at' => null, 'default_date' => null, 'days_in_default' => 0];

            if ($action === 'forbearance') {
                $cycle->penalty_waived_until = $after['waived_until'];
                $cycle->notes = trim($cycle->notes . " | Forbearance until {$after['waived_until']} (owner decision)");
                $cycle->save();
                $locked->forceFill($clear + ['status' => Loan::STATUS_FORBEARANCE, 'forbearance_until' => $after['waived_until'] . ' 23:59:59'])->save();
            } else {
                // LoanStatus leaves a defaulted loan alone; start it from "disbursed" and let the cycle decide
                $locked->forceFill($clear + ['status' => Loan::STATUS_DISBURSED])->save();
            }
            $d = LoanStatus::apply($locked->fresh());
            AuditTrail::record('loans', $locked->id, 'update', $before, $after + ['decision' => $action, 'reason' => $reason, 'note' => $note, 'status_now' => $d['status'] ?? null]);
        });

        return [$before, $after, $reason];
    }
}
