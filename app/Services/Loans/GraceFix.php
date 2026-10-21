<?php

namespace App\Services\Loans;

use App\Console\Commands\LoansWriteOffRepaid;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Repayment;
use App\Services\AuditTrail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Owner decision, 21 Oct 2026: loans from before the v2 engine that were paid in full but a few days late must not
 * owe penalty that nobody ever collected, and must not carry a write-off for it.
 *
 * For each such loan the latest cycle gets exactly as many grace days as the borrower was late (the day the cash
 * reached principal + interest, minus the due date). The due date itself is kept, so the history stays true.
 * Then the "kept repaid" write-off (loans:write-off-repaid) is soft-deleted and the status is worked out again.
 * Saving the cycle re-books its penalty in the ledger; deleting the write-off reverses its posting.
 *
 * Loans that were in forbearance before Phase 2 (backup pre_phase2_apply_20261021_003323) go back to forbearance
 * with an open-ended waiver; staff move the date when an extension is agreed.
 *
 * Anything that grace does not clear (the borrower genuinely paid less) is listed, never changed here.
 */
class GraceFix
{
    /** The day the v2 engine was switched on: only cycles due before it are old-model loans. */
    public const CUTOFF = '2026-10-21';

    /** Loans whose status was forbearance in the last backup before Phase 2 (merged ones resolve to their root). */
    // 513 (Erick Kiiya Mutuku) left out: owner, 21 Oct 2026, he rolls over as usual
    public const PRE_V2_FORBEARANCE = [210, 492];

    /** Open-ended forbearance (loans.forbearance_until is a TIMESTAMP, so it cannot pass 2038). */
    public const OPEN_ENDED = '2037-12-31';

    /**
     * Owner, 21 Oct 2026, by the loan forbearance is restored on: MOLUK AWAD (#209, root of 210) until 5 Dec 2026;
     * Shadrack Cheruiyot (#430, root of 492) open-ended, no rollover, while the way forward is discussed.
     */
    public const FORBEARANCE_UNTIL = [209 => '2026-12-05', 430 => self::OPEN_ENDED];

    public const ACTION_GRACE = 'grace';
    public const ACTION_SHORT = 'short_paid';
    public const ACTION_FORBEARANCE = 'forbearance';

    /** Every loan that needs a decision, in loan order. */
    public function proposals(): Collection
    {
        $writeOffs = Repayment::where('transaction', LoansWriteOffRepaid::REFERENCE)->get()->keyBy('loan_id');
        $forbearance = $this->forbearanceRoots();
        $ids = Loan::whereNotIn('status', [Loan::STATUS_REJECTED, Loan::STATUS_APPROVED])->pluck('id');

        return $ids->map(fn ($id) => $this->propose(Loan::with('user:id,name')->find($id), $writeOffs->get($id), $forbearance))
            ->filter()->values();
    }

    public function proposalFor(Loan $loan): ?array
    {
        $writeOff = Repayment::where('transaction', LoansWriteOffRepaid::REFERENCE)->where('loan_id', $loan->id)->first();

        return $this->propose($loan->loadMissing('user:id,name'), $writeOff, $this->forbearanceRoots());
    }

    private function propose(?Loan $loan, ?Repayment $writeOff, array $forbearance): ?array
    {
        if (! $loan) {
            return null;
        }
        $cycle = LoanCycle::where('loan_id', $loan->id)->orderByDesc('cycle_number')->first();
        if (! $cycle) {
            return null;
        }
        $today = now()->toDateString();
        $terms = CycleBalance::termsFor($cycle);
        $payments = array_values(array_filter(CycleBalance::paymentsFor($cycle), fn ($p) => ! $writeOff || $p['id'] !== $writeOff->id));
        $now = CycleBalance::compute($terms, $payments, $today);

        $row = [
            'loan_id' => $loan->id, 'borrower' => $loan->user?->name, 'user_id' => $loan->user_id,
            'status_now' => $loan->status, 'cycle_id' => $cycle->id, 'cycle_number' => $cycle->cycle_number,
            'start' => $terms['start'], 'due' => $terms['due'], 'contract' => $now['full_balance'],
            'paid' => $now['total_repayments'], 'owed_now' => $now['final_outstanding'], 'penalty_now' => $now['penalty'],
            'write_off_id' => $writeOff?->id, 'write_off' => $writeOff ? (float) $writeOff->amount : 0.0,
            'grace_now' => (int) $terms['grace_days'], 'paid_in_full_on' => null, 'days_late' => null,
            'grace_after' => null, 'overpaid_after' => null, 'owed_after' => null, 'penalty_after' => null, 'status_after' => null,
            'waived_until' => $cycle->penalty_waived_until?->toDateString(),
        ];

        if (in_array($loan->id, $forbearance, true)) {
            if ($loan->status === Loan::STATUS_FORBEARANCE || $now['final_outstanding'] <= 0.004) {
                return null;
            }
            $until = self::FORBEARANCE_UNTIL[$loan->id] ?? self::OPEN_ENDED;
            $after = CycleBalance::compute(['waived_until' => $until] + $terms, $payments, $today);

            return array_merge($row, [
                'action' => self::ACTION_FORBEARANCE, 'owed_after' => $after['final_outstanding'], 'penalty_after' => $after['penalty'],
                'status_after' => Loan::STATUS_FORBEARANCE, 'waived_until_after' => $until,
                'reason' => 'In forbearance before Phase 2; v2 moved it to ' . $loan->status . '. Penalty waived '
                    . ($until === self::OPEN_ENDED ? 'open-ended, no rollover.' : 'until ' . Carbon::parse($until)->format('j M Y') . '.'),
            ]);
        }

        if ($terms['due'] >= self::CUTOFF || (! $writeOff && $now['final_outstanding'] <= 0.004)) {
            return null;
        }

        // the day the money reached principal + interest
        $need = (int) round($now['full_balance'] * 100);
        $sum = 0;
        $fullOn = null;
        $byDay = collect($payments)->groupBy('date')->sortKeys();
        foreach ($byDay as $day => $rows) {
            $sum += (int) round($rows->sum('amount') * 100);
            if ($sum >= $need) {
                $fullOn = $day;
                break;
            }
        }
        $late = $fullOn ? max(0, (int) Carbon::parse($terms['due'])->diffInDays(Carbon::parse($fullOn), false)) : null;

        if ($fullOn && $late > 0) {
            // the fewest grace days that still clear the cycle: any cash above principal + interest pays the penalty
            // of the days it covers (owner, 21 Oct 2026), so only the days nobody paid for become grace
            for ($grace = (int) $terms['grace_days']; $grace <= $late; $grace++) {
                $after = CycleBalance::compute(['grace_days' => $grace] + $terms, $payments, $today);
                if ($after['final_outstanding'] <= 0.004) {
                    break;
                }
            }
            if ($after['final_outstanding'] <= 0.004 && $grace > (int) $terms['grace_days']) {
                $kept = $after['penalty'];

                return array_merge($row, [
                    'action' => self::ACTION_GRACE, 'paid_in_full_on' => $fullOn, 'days_late' => $late, 'grace_after' => $grace,
                    'owed_after' => $after['final_outstanding'], 'penalty_after' => $kept, 'overpaid_after' => $after['overpaid'],
                    'status_after' => Loan::STATUS_REPAID,
                    'reason' => "Paid {$this->kes($now['total_repayments'])} on a {$this->kes($now['full_balance'])} cycle, {$late} day(s) late. "
                        . ($kept > 0 ? "The extra {$this->kes($kept)} is " . ($late - $grace) . " day(s) of penalty, kept; " : '')
                        . "{$grace} grace day(s) for the rest"
                        . ($writeOff ? "; the {$this->kes($writeOff->amount)} write-off goes." : '.')
                        . ($after['overpaid'] > 0.004 ? " {$this->kes($after['overpaid'])} stays as overpayment." : ''),
                ]);
            }
        }

        if (! $writeOff) {
            return null;
        }

        return array_merge($row, [
            'action' => self::ACTION_SHORT, 'paid_in_full_on' => $fullOn, 'days_late' => $late,
            'reason' => $fullOn
                ? 'Grace days do not clear it: something else is owed. Owner to decide.'
                : "Paid {$this->kes($now['total_repayments'])} of {$this->kes($now['full_balance'])}: genuinely short. Keep the write-off, or reopen.",
        ]);
    }

    /**
     * Applies one proposal, re-checked against the data now. Returns the proposal that was applied.
     *
     * @throws \RuntimeException when there is nothing to apply any more
     */
    public function apply(Loan $loan, ?string $until = null, ?string $note = null): array
    {
        $p = $this->proposalFor($loan);
        if (! $p || ! in_array($p['action'], [self::ACTION_GRACE, self::ACTION_FORBEARANCE], true)) {
            throw new \RuntimeException("Loan #{$loan->id}: nothing to apply (" . ($p['action'] ?? 'no longer flagged') . ').');
        }

        DB::transaction(function () use ($p, $until, $note) {
            $loan = Loan::whereKey($p['loan_id'])->lockForUpdate()->firstOrFail();
            $cycle = LoanCycle::whereKey($p['cycle_id'])->lockForUpdate()->firstOrFail();
            $old = ['status' => $loan->status, 'grace_days' => $cycle->grace_days, 'penalty_waived_until' => $cycle->penalty_waived_until?->toDateString()];

            if ($p['action'] === self::ACTION_GRACE) {
                $cycle->grace_days = $p['grace_after'];
                $cycle->notes = trim($cycle->notes . " | Grace {$p['grace_after']} day(s): paid in full on {$p['paid_in_full_on']} (owner, 21 Oct 2026)");
                $cycle->save();
                if ($p['write_off_id']) {
                    Repayment::whereKey($p['write_off_id'])->first()?->delete();
                }
            } else {
                $date = $until ?: ($p['waived_until_after'] ?? self::OPEN_ENDED);
                $cycle->penalty_waived_until = $date;
                $cycle->notes = trim($cycle->notes . " | Forbearance until {$date} (restored, owner 21 Oct 2026)");
                $cycle->save();
                $loan->forceFill(['forbearance_until' => $date . ' 23:59:59'])->save();
            }

            // LoanStatus leaves pending loans alone; a loan with a paid cycle was never pending
            if ($loan->status === Loan::STATUS_PENDING) {
                $loan->forceFill(['status' => Loan::STATUS_DISBURSED])->save();
            }
            $d = LoanStatus::apply($loan->fresh());

            AuditTrail::record('loans', $loan->id, 'update', $old, [
                'status' => $d['status'] ?? $loan->status, 'grace_days' => $cycle->grace_days,
                'penalty_waived_until' => $cycle->penalty_waived_until?->toDateString(),
                'write_off_removed' => $p['write_off_id'], 'grace_fix' => $p['action'], 'note' => $note,
            ]);
        });

        // the broker/funder split follows the new penalty (the write-off delete already triggers it; this covers the rest)
        if (RepaymentSplit::enabled()) {
            DB::afterCommit(function () use ($p) {
                try {
                    app(RepaymentSplit::class)->syncCycle((int) $p['cycle_id']);
                } catch (\Throwable $e) {
                    Log::error("Repayment split failed for cycle #{$p['cycle_id']}", ['error' => $e->getMessage()]);
                }
            });
        }

        return $p;
    }

    /** The loans to restore: each pre-Phase-2 forbearance loan, or the loan it was merged into. */
    public function forbearanceRoots(): array
    {
        $roots = [];
        foreach (self::PRE_V2_FORBEARANCE as $id) {
            $seen = [];
            $loan = Loan::withTrashed()->find($id);
            while ($loan && $loan->merged_into_loan_id && ! isset($seen[$loan->id])) {
                $seen[$loan->id] = true;
                $loan = Loan::withTrashed()->find($loan->merged_into_loan_id);
            }
            if ($loan && ! $loan->trashed()) {
                $roots[] = $loan->id;
            }
        }

        return array_values(array_unique($roots));
    }

    private function kes($amount): string
    {
        return 'KES ' . number_format((float) $amount, 2);
    }
}
