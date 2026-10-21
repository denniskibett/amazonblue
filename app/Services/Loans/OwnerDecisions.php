<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\AuditTrail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Per-loan decisions the owner gave on 21 Oct 2026, applied by loans:owner-decisions (dry-run + SHA).
 *
 *   forbearance  status forbearance with the penalty waived open-ended ("indefinitely, with a day to pay"): the
 *                latest cycle's due date becomes the pay-by day; staff move it by editing when an extension is asked.
 *                The waiver covers the whole latest cycle, so penalty already charged on it comes off.
 *   plan         a payment-plan cycle for an agreed amount at 0%, pay-by day as given, then forbearance as above;
 *                what was owed above the amount is booked as a rollover adjustment on the old cycle.
 *   active       a loan that is on time but reads defaulted: default flags cleared, status from its cycle.
 */
class OwnerDecisions
{
    public const DECISIONS = [
        467 => ['action' => 'forbearance', 'pay_by' => '2026-10-01', 'who' => 'Sharon Chemurgor'],
        389 => ['action' => 'forbearance', 'pay_by' => '2026-10-08', 'who' => 'Emmanuel Ruwa Tsuma'],
        377 => ['action' => 'forbearance', 'pay_by' => '2026-10-03', 'who' => 'Judy Kerebi'],
        212 => ['action' => 'forbearance', 'pay_by' => '2026-10-03', 'who' => 'David Ihaji'],
        458 => ['action' => 'plan', 'amount' => 1000000.00, 'pay_by' => '2026-10-22', 'who' => 'ISIRO Agencies'],
        511 => ['action' => 'active', 'who' => 'Joseph (trade finance, due 29 May 2027)'],
        156 => ['action' => 'plan', 'amount' => 12000.00, 'pay_by' => '2026-10-01', 'who' => 'Faith Chepngetich'],
    ];

    /** What each decision would do, from today's data. */
    public function preview(): array
    {
        $rows = [];
        foreach (self::DECISIONS as $id => $d) {
            $loan = Loan::find($id);
            $cycle = $loan ? LoanCycle::where('loan_id', $id)->orderByDesc('cycle_number')->first() : null;
            if (! $loan || ! $cycle) {
                $rows[] = ['loan_id' => $id, 'who' => $d['who'], 'action' => $d['action'], 'ok' => false, 'note' => 'loan or cycle not found'];
                continue;
            }
            $now = CycleBalance::at($cycle);
            $terms = CycleBalance::termsFor($cycle);
            $after = match ($d['action']) {
                'forbearance' => CycleBalance::compute(['waived_until' => GraceFix::OPEN_ENDED, 'due' => $d['pay_by']] + $terms, CycleBalance::paymentsFor($cycle), now()->toDateString())['final_outstanding'],
                'plan' => (float) $d['amount'],
                default => $now['final_outstanding'],
            };
            $rows[] = ['loan_id' => $id, 'who' => $d['who'], 'action' => $d['action'], 'ok' => true,
                'status_now' => $loan->status, 'status_after' => $d['action'] === 'active' ? 'disbursed' : 'forbearance',
                'cycle' => $cycle->cycle_number, 'due_now' => $cycle->due_date->toDateString(), 'pay_by' => $d['pay_by'] ?? $cycle->due_date->toDateString(),
                'owed_now' => $now['final_outstanding'], 'penalty_now' => $now['penalty'], 'owed_after' => round($after, 2),
                'note' => match ($d['action']) {
                    'plan' => 'payment plan: new cycle ' . ($cycle->cycle_number + 1) . ' for KES ' . number_format($d['amount'], 2) . ' at 0%; ' . number_format($now['final_outstanding'] - $d['amount'], 2) . ' written down',
                    'forbearance' => 'penalty on cycle ' . $cycle->cycle_number . ' waived open-ended',
                    default => 'default flags cleared; on time until ' . $cycle->due_date->toDateString(),
                }];
        }

        return $rows;
    }

    public function apply(int $loanId): void
    {
        $this->applyDecision($loanId, self::DECISIONS[$loanId] ?? throw new \RuntimeException("No decision for loan #{$loanId}."));
    }

    protected function applyDecision(int $loanId, array $d): void
    {
        DB::transaction(function () use ($loanId, $d) {
            $loan = Loan::whereKey($loanId)->lockForUpdate()->firstOrFail();
            $old = $loan->only(['status', 'default_triggered', 'is_non_performing', 'forbearance_until', 'due_date']);
            // collections decided by the owner: a defaulted loan is not left defaulted by LoanStatus
            $loan->forceFill(['default_triggered' => false, 'is_non_performing' => false,
                'status' => in_array($loan->status, [Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY], true) ? Loan::STATUS_OVERDUE : $loan->status])->save();

            if ($d['action'] === 'plan') {
                // a pay-by day already past: the plan starts the day before it, so the cycle never ends before it starts
                $payBy = Carbon::parse($d['pay_by'])->startOfDay();
                $start = $payBy->lte(today()) ? $payBy->copy()->subDay() : today();
                app(Rollover::class)->execute($loan->fresh(), ['plan' => true, 'interest_rate' => 0, 'principal' => $d['amount'],
                    'date' => $start->toDateString(), 'due_date' => $d['pay_by'], 'notes' => 'Payment plan agreed by the owner (21 Oct 2026): KES ' . number_format($d['amount'], 2)]);
            }
            $cycle = LoanCycle::where('loan_id', $loanId)->orderByDesc('cycle_number')->first();
            if (in_array($d['action'], ['forbearance', 'plan'], true)) {
                $cycle->forceFill(['due_date' => $d['pay_by'], 'penalty_waived_until' => GraceFix::OPEN_ENDED,
                    'notes' => trim($cycle->notes . ' | Forbearance, pay by ' . Carbon::parse($d['pay_by'])->format('j M Y') . ' (owner, 21 Oct 2026)')])->save();
                $loan->refresh()->forceFill(['forbearance_until' => GraceFix::OPEN_ENDED . ' 23:59:59'])->save();
            }
            if ($d['action'] === 'active') {
                $loan->forceFill(['status' => Loan::STATUS_DISBURSED])->save();
            }
            $result = LoanStatus::apply($loan->fresh());
            AuditTrail::record('loans', $loanId, 'update', $old, ['owner_decision' => $d, 'status' => $result['status'] ?? null]);
        });
    }
}
