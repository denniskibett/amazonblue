<?php

namespace App\Support;

use App\Models\User;
use App\Services\CustomerWallets;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Collected per officer per month (owner, 23 Oct 2026). Read-only.
 *
 * A case's loan counts for the officer it is assigned to now (cases are rarely moved; when they are, the new
 * officer takes the month's figures). Only cash repayments count as collected: rollovers, discounts and
 * write-offs are book entries, not money. A promise is kept when cash paid on the loan from the day of the
 * promise up to the promised date adds up to the promised amount.
 */
class CollectorPerformance
{
    /** @return Collection<int, object> one row per officer, plus "Not assigned" (id null) when it has anything */
    public static function forMonth(Carbon $month): Collection
    {
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $cases = DB::table('debt_recovery_cases')->whereNull('deleted_at')
            ->get(['id', 'loan_id', 'assigned_to', 'status_id']);
        $closed = DB::table('recovery_statuses')->whereIn('slug', \App\Http\Controllers\Staff\CollectionsController::CLOSED_CASE)->pluck('id')->all();
        $officerOfLoan = $cases->whereNotNull('loan_id')->pluck('assigned_to', 'loan_id');

        $rows = [];
        $row = function ($id) use (&$rows) {
            $key = $id ?? 0;

            return $rows[$key] ??= (object) ['id' => $id, 'open_cases' => 0, 'actions' => 0, 'contacts' => 0, 'promises' => 0,
                'promised' => 0.0, 'due_promises' => 0, 'kept' => 0, 'collected' => 0.0, 'payments' => 0];
        };

        foreach ($cases as $c) {
            if (! in_array((int) $c->status_id, array_map('intval', $closed), true)) {
                $row($c->assigned_to)->open_cases++;
            }
        }

        // cash repayments this month on loans with a case, credited to the case's officer
        if ($officerOfLoan->isNotEmpty()) {
            DB::table('repayments')->whereNull('deleted_at')->whereIn('loan_id', $officerOfLoan->keys())
                ->whereBetween('repayment_date', [$from->toDateString(), $to->toDateString()])
                ->get(['loan_id', 'amount', 'kind', 'transaction'])
                ->filter(fn ($r) => CustomerWallets::kindOf($r) === 'cash')
                ->each(function ($r) use ($row, $officerOfLoan) {
                    $o = $row($officerOfLoan[$r->loan_id] ?? null);
                    $o->collected += (float) $r->amount;
                    $o->payments++;
                });
        }

        // actions logged by each officer this month, and their promises
        $actions = DB::table('recovery_actions as a')->join('debt_recovery_cases as c', 'c.id', '=', 'a.case_id')
            ->whereNull('a.deleted_at')->whereBetween('a.action_date', [$from, $to])
            ->get(['a.performed_by', 'a.outcome', 'a.promised_amount', 'a.promised_date', 'a.action_date', 'c.loan_id', 'c.assigned_to']);
        foreach ($actions as $a) {
            $o = $row($a->performed_by ?? $a->assigned_to);
            $o->actions++;
            if (! in_array($a->outcome, ['no_answer', 'wrong_number', 'pending', null], true)) {
                $o->contacts++;
            }
            if ($a->outcome === 'promise_to_pay' && $a->promised_amount > 0) {
                $o->promises++;
                $o->promised += (float) $a->promised_amount;
                if ($a->promised_date && $a->promised_date <= now()->toDateString() && $a->loan_id) {
                    $o->due_promises++;
                    $paid = DB::table('repayments')->whereNull('deleted_at')->where('loan_id', $a->loan_id)
                        ->whereBetween('repayment_date', [Carbon::parse($a->action_date)->toDateString(), $a->promised_date])
                        ->get(['amount', 'kind', 'transaction'])->filter(fn ($r) => CustomerWallets::kindOf($r) === 'cash')->sum('amount');
                    if ($paid + 0.005 >= (float) $a->promised_amount) {
                        $o->kept++;
                    }
                }
            }
        }

        $names = User::whereIn('id', array_filter(array_map(fn ($r) => $r->id, $rows)))->pluck('name', 'id');

        return collect($rows)->map(function ($r) use ($names) {
            $r->name = $r->id ? ($names[$r->id] ?? 'User #' . $r->id) : 'Not assigned';
            $r->kept_rate = $r->due_promises ? round($r->kept / $r->due_promises * 100) : null;

            return $r;
        })->sortByDesc('collected')->values();
    }
}
