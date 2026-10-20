<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanCycle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Legacy ROLL OVER chains (owner, 21 Oct 2026). Before cycles existed a rollover closed the old loan with a
 * ROLL OVER repayment and opened a new loan row with a ROLL OVER disbursement. Borrowers rarely start a new
 * loan without clearing the old one, so each continuation can be traced to the loan it continued:
 *
 *   continuation C = a loan with a rollover-kind disbursement on date D
 *   predecessor  P = the same borrower's loan with a rollover-kind repayment within 3 days of D
 *                    (same day first, then the closest amount), not already used by another continuation
 *
 * Following predecessors back gives the chain's first loan (the root). merge() makes every continuation's
 * cycles further cycles of the root, moves its rows (repayments, disbursements, recovery cases, partner
 * journal rows, risk assessments, collateral) to the root, and soft-deletes the continuation with
 * merged_into_loan_id. Nothing is deleted for good and no amount changes.
 *
 * A link that can't be made safely (no candidate, or two equally good ones) is reported, never guessed.
 */
class LegacyChains
{
    public const WINDOW_DAYS = 3;

    /** Tables whose loan_id moves to the root (agreements stay with the loan they were signed for). */
    public const MOVE = ['repayments', 'disbursements', 'debt_recovery_cases', 'partner_transactions', 'loan_risk_assessments'];

    /** @return array{chains: array<int, array>, unmatched: array<int, array>} */
    public function detect(): array
    {
        $opens = DB::table('disbursements as d')->join('loans as l', 'l.id', '=', 'd.loan_id')
            ->where('d.kind', 'rollover')->whereNull('d.deleted_at')->whereNull('l.deleted_at')
            ->orderBy('d.disburse_date')->orderBy('d.id')
            ->get(['d.id as disbursement_id', 'd.loan_id', 'l.user_id', 'd.disburse_date as date', 'd.amount']);
        $closes = DB::table('repayments as r')->join('loans as l', 'l.id', '=', 'r.loan_id')
            ->where('r.kind', 'rollover')->whereNull('r.deleted_at')->whereNull('l.deleted_at')
            ->get(['r.id as repayment_id', 'r.loan_id', 'l.user_id', 'r.repayment_date as date', 'r.amount'])
            ->groupBy('user_id');

        $pred = [];      // continuation loan id => link
        $used = [];      // predecessor loan id => continuation loan id
        $unmatched = [];
        foreach ($opens->unique('loan_id') as $o) {
            $day = Carbon::parse($o->date)->startOfDay();
            $candidates = collect($closes[$o->user_id] ?? [])
                ->filter(fn ($c) => (int) $c->loan_id !== (int) $o->loan_id && ! isset($used[$c->loan_id])
                    && abs(Carbon::parse($c->date)->startOfDay()->diffInDays($day, false)) <= self::WINDOW_DAYS)
                ->map(function ($c) use ($day, $o) {
                    $c->gap = abs((int) Carbon::parse($c->date)->startOfDay()->diffInDays($day, false));
                    $c->diff = abs((float) $c->amount - (float) $o->amount);

                    return $c;
                })
                ->sortBy([['gap', 'asc'], ['diff', 'asc']])->values();

            if ($candidates->isEmpty()) {
                $unmatched[] = ['loan' => (int) $o->loan_id, 'user' => (int) $o->user_id, 'date' => substr((string) $o->date, 0, 10),
                    'amount' => (float) $o->amount, 'why' => 'no earlier loan of this borrower was closed by a ROLL OVER within ' . self::WINDOW_DAYS . ' days'];
                continue;
            }
            $best = $candidates[0];
            if (isset($candidates[1]) && $candidates[1]->gap === $best->gap && abs($candidates[1]->diff - $best->diff) < 0.01) {
                $unmatched[] = ['loan' => (int) $o->loan_id, 'user' => (int) $o->user_id, 'date' => substr((string) $o->date, 0, 10),
                    'amount' => (float) $o->amount, 'why' => 'two earlier loans (#' . $best->loan_id . ', #' . $candidates[1]->loan_id . ') fit equally'];
                continue;
            }
            $used[$best->loan_id] = (int) $o->loan_id;
            $pred[(int) $o->loan_id] = ['from' => (int) $best->loan_id, 'to' => (int) $o->loan_id, 'user' => (int) $o->user_id,
                'gap_days' => $best->gap, 'amount_closed' => (float) $best->amount, 'amount_opened' => (float) $o->amount,
                'repayment_id' => (int) $best->repayment_id, 'disbursement_id' => (int) $o->disbursement_id,
                'date' => substr((string) $o->date, 0, 10)];
        }

        // walk each continuation back to its root, then group
        $chains = [];
        foreach ($pred as $loanId => $link) {
            $root = $loanId;
            $seen = [];
            while (isset($pred[$root]) && ! isset($seen[$root])) {
                $seen[$root] = true;
                $root = $pred[$root]['from'];
            }
            $chains[$root]['root'] = $root;
            $chains[$root]['user'] = $link['user'];
            $chains[$root]['links'][] = $link;
        }
        foreach ($chains as &$c) {
            usort($c['links'], fn ($a, $b) => [$a['date'], $a['to']] <=> [$b['date'], $b['to']]);
            $c['loans'] = array_merge([$c['root']], array_column($c['links'], 'to'));
        }
        unset($c);
        ksort($chains);

        return ['chains' => array_values($chains), 'unmatched' => $unmatched];
    }

    /** Merges one chain into its root. Run inside a transaction. */
    public function merge(array $chain): void
    {
        $root = Loan::withTrashed()->lockForUpdate()->findOrFail($chain['root']);
        $last = null;
        foreach (array_slice($chain['loans'], 1) as $loanId) {
            $c = Loan::withTrashed()->lockForUpdate()->findOrFail($loanId);
            $next = (int) LoanCycle::where('loan_id', $root->id)->max('cycle_number');
            $cycles = LoanCycle::where('loan_id', $c->id)->orderBy('cycle_number')->get();

            // the root's open cycle was carried into the continuation's first cycle
            $first = $cycles->first();
            if ($first) {
                LoanCycle::where('loan_id', $root->id)->where('cycle_number', $next)
                    ->update(['status' => 'completed', 'closed_on' => Carbon::parse($first->start_date)->toDateString()]);
            }
            foreach ($cycles as $i => $cycle) {
                $cycle->forceFill(['loan_id' => $root->id, 'cycle_number' => $next + $i + 1,
                    'notes' => trim(($cycle->notes ? $cycle->notes . ' | ' : '') . 'Legacy loan #' . $c->id . ' (ROLL OVER), merged 21 Oct 2026')])->saveQuietly();
            }
            foreach (self::MOVE as $table) {
                DB::table($table)->where('loan_id', $c->id)->update(['loan_id' => $root->id]);
            }
            DB::table('assets')->where('collateral_for_loan_id', $c->id)->update(['collateral_for_loan_id' => $root->id]);

            $c->forceFill(['merged_into_loan_id' => $root->id])->saveQuietly();
            $c->deleteQuietly();
            $last = $c;
        }

        // the chain now carries the latest continuation's standing
        if ($last) {
            $root->forceFill(['status' => $last->status, 'default_triggered' => (bool) $last->default_triggered,
                'is_non_performing' => (bool) $last->is_non_performing, 'forbearance_until' => $last->forbearance_until])->saveQuietly();
        }
        LoanSync::fromCycles($root->refresh());
    }
}
