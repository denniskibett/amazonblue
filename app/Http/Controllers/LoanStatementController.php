<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Repayment;
use App\Services\Loans\CycleBalance;
use Illuminate\View\View;

/**
 * The full loan statement (owner, 16 Nov 2026): every cycle from the first, each with its own numbers,
 * disbursements and repayments, for printing next to the one-cycle statement on the loan page.
 */
class LoanStatementController extends Controller
{
    public function show(int $user, int $loan): View
    {
        $loan = Loan::with(['user', 'loanType', 'cycles'])
            ->where('user_id', $user)->findOrFail($loan);
        $cycles = $loan->cycles->sortBy('cycle_number')->values();
        $firstCycleId = $cycles->first()?->id;
        $byCycle = fn ($rows) => $rows->groupBy(fn ($r) => $r->loan_cycle_id ?: $firstCycleId);
        $disbursements = $byCycle(Disbursement::where('loan_id', $loan->id)->orderBy('disburse_date')->orderBy('id')->get());
        $repayments = $byCycle(Repayment::where('loan_id', $loan->id)->orderBy('repayment_date')->orderBy('id')->get());

        $rows = $cycles->map(function ($c) use ($disbursements, $repayments) {
            $b = CycleBalance::at($c);

            return (object) [
                'cycle' => $c,
                'b' => $b,
                'owed' => (float) ($b['owed_on_close'] ?? $b['final_outstanding']),
                'carried' => $b['carried_forward'],
                'disbursements' => $disbursements->get($c->id, collect()),
                'repayments' => $repayments->get($c->id, collect()),
            ];
        });
        $last = $rows->last();

        return view('loans.statement', [
            'loan' => $loan,
            'rows' => $rows,
            'balance' => $last ? (float) $last->b['final_outstanding'] : 0.0,
            'dueDate' => $last?->cycle->due_date,
            // money that actually moved (cash, or older rows with no kind), not rollover carries
            'totalPaid' => (float) $rows->sum(fn ($r) => $r->repayments->filter(fn ($x) => in_array($x->kind, ['cash', null], true))->sum('amount')),
            'totalLent' => (float) $rows->sum(fn ($r) => $r->disbursements->filter(fn ($x) => in_array($x->kind, ['cash', null], true))->sum('amount')),
        ]);
    }
}
