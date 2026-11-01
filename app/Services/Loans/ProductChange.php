<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\LoanType;
use App\Services\AuditTrail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * A sent loan moved to another product on Edit loan (owner, 1 Nov 2026), e.g. "Emergency 10 Days" → "Emergency 5 Days".
 * Only the open cycle changes: it keeps its start and principal, is due one new product period after its start, and
 * charges the new product's interest and penalty rates. Earlier cycles, disbursements and repayments stay as booked;
 * the repayments on the open cycle are re-allocated by CycleBalance, and the ledger and partner split follow the save.
 * A loan that has moved no money is rebuilt from scratch instead (LoanCalculator::rebuildInitialCycle).
 *
 * "Edit current cycle" on the loan page (owner, 1 Nov 2026) uses the same path with $set: a due date and/or interest
 * rate typed by staff, with or without a new product ($type null = keep the cycle's own terms).
 */
class ProductChange
{
    /** The open cycle (or the latest one) of a loan that has moved money; null when there is nothing to re-price. */
    public static function cycleFor(Loan $loan): ?LoanCycle
    {
        if (! $loan->disbursements()->exists() && ! $loan->cycles()->where('cycle_number', '>', 1)->exists()) {
            return null;
        }

        return $loan->getCurrentCycle() ?? $loan->cycles()->orderByDesc('cycle_number')->first();
    }

    /** Before and after for the open cycle, worked out today; null when the change does not touch a cycle. */
    public static function preview(Loan $loan, ?LoanType $type, array $set = []): ?array
    {
        $cycle = self::cycleFor($loan);
        if (! $cycle) {
            return null;
        }
        $today = now()->toDateString();
        $terms = CycleBalance::termsFor($cycle);
        $payments = CycleBalance::paymentsFor($cycle);
        $new = self::newTerms($cycle, $type, $set);
        $before = CycleBalance::compute($terms, $payments, $today);
        $after = CycleBalance::compute(array_merge($terms, [
            'due' => $new['due_date'], 'interest_rate' => $new['interest_rate'], 'penalty_rate' => $new['penalty_rate'],
        ]), $payments, $today);
        $side = fn (array $t, array $b) => [
            'product' => $t['product'], 'due_date' => $t['due_date'], 'interest_rate' => $t['interest_rate'], 'penalty_rate' => $t['penalty_rate'],
            'interest' => $b['interest'], 'full_balance' => $b['full_balance'], 'penalty' => $b['penalty'],
            'paid' => $b['total_repayments'], 'owed_today' => $b['final_outstanding'], 'days_overdue' => $b['days_overdue'],
        ];

        return [
            'cycle_number' => (int) $cycle->cycle_number,
            'start_date' => Carbon::parse($cycle->start_date)->toDateString(),
            'principal' => $cycle->lentPrincipal(),
            'before' => $side(['product' => $loan->loanType->name ?? '', 'due_date' => $terms['due'],
                'interest_rate' => (float) $terms['interest_rate'], 'penalty_rate' => (float) $terms['penalty_rate']], $before),
            'after' => $side(['product' => ($type ?? $loan->loanType)->name ?? ''] + $new, $after),
        ];
    }

    /** Re-price the open cycle with the loan's (already saved) product. Returns the preview it applied, or null. */
    public static function apply(Loan $loan, ?array $old = null, array $set = [], bool $product = true): ?array
    {
        $loan->load('loanType');
        $cycle = self::cycleFor($loan);
        if (! $cycle || ! $loan->loanType) {
            return null;
        }
        $type = $product ? $loan->loanType : null;
        $preview = self::preview($loan, $type, $set);
        $new = self::newTerms($cycle, $type, $set);

        DB::transaction(function () use ($loan, $cycle, $new, $old, $preview, $type) {
            $cycle = LoanCycle::whereKey($cycle->id)->lockForUpdate()->firstOrFail();
            $principal = $cycle->lentPrincipal();
            $interest = round($principal * $new['interest_rate'] / 100, 2);
            $cycle->fill([
                'principal' => $principal,
                'interest_rate' => $new['interest_rate'],
                'penalty_rate' => $new['penalty_rate'],
                'interest_capitalized' => $interest,
                'new_balance' => round($principal + $interest, 2),
                'due_date' => $new['due_date'],
            ]);
            $what = $type ? 'Product changed to ' . $type->name : 'Cycle terms changed';
            $cycle->notes = trim($cycle->notes . ' | ' . $what . ' (due ' . $new['due_date'] . ', ' . $new['interest_rate'] . '%) on ' . now()->toDateString(), ' |');
            $cycle->save();

            $loan->forceFill(['due_date' => $new['due_date']])->save();
            $d = LoanStatus::apply($loan->fresh());
            AuditTrail::record('loans', $loan->id, 'update', $old, [
                'product_change' => $preview, 'status' => $d['status'] ?? $loan->status,
            ]);
        });

        if (RepaymentSplit::enabled()) {
            try {
                app(RepaymentSplit::class)->syncCycle((int) $cycle->id);
            } catch (\Throwable $e) {
                Log::error("Repayment split failed for cycle #{$cycle->id}", ['error' => $e->getMessage()]);
            }
        }

        return $preview;
    }

    /** The open cycle's new due date and rates: the product's (or the cycle's own), then what staff typed. */
    private static function newTerms(LoanCycle $cycle, ?LoanType $type, array $set = []): array
    {
        $start = Carbon::parse($cycle->start_date)->startOfDay();
        $now = CycleBalance::termsFor($cycle);
        $t = $type ? [
            'due_date' => Period::add($start, (int) $type->period, (string) $type->unit)->toDateString(),
            'interest_rate' => (float) $type->interest_rate,
            'penalty_rate' => (float) ($type->penalty_rate ?? 0),
        ] : ['due_date' => $now['due'], 'interest_rate' => (float) $now['interest_rate'], 'penalty_rate' => (float) $now['penalty_rate']];
        if (! empty($set['due_date'])) {
            $t['due_date'] = Carbon::parse($set['due_date'])->toDateString();
        }
        if (isset($set['interest_rate']) && $set['interest_rate'] !== '') {
            $t['interest_rate'] = (float) $set['interest_rate'];
        }
        if ($t['due_date'] <= $start->toDateString()) {
            throw new \DomainException('The due date must be after the cycle start (' . $start->format('j M Y') . ').');
        }

        return $t;
    }
}
