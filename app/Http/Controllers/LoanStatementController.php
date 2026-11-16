<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Repayment;
use App\Services\Loans\CycleBalance;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * The full loan statement (owner, 16 Nov 2026): one ledger from the first cycle to today, with the cycle as a column
 * and a running balance. Charges are money sent, each cycle's interest and penalty; payments are what the borrower
 * paid (and discounts or write-offs). Rollover rows only move the balance between cycles, so they are not money
 * lines. Each cycle starts on its principal and ends on the balance the loan engine works out for it; any
 * difference shows as an adjustment line, so the statement always agrees with the loan page.
 */
class LoanStatementController extends Controller
{
    private const KIND = ['discount' => 'Discount', 'bad_debt' => 'Written off', 'non_cash' => 'Non-cash credit'];

    public function show(int $user, int $loan): View
    {
        $loan = Loan::with(['user', 'loanType', 'cycles'])->where('user_id', $user)->findOrFail($loan);
        $cycles = $loan->cycles->sortBy('cycle_number')->values();
        $firstCycleId = $cycles->first()?->id;
        $byCycle = fn ($rows) => $rows->groupBy(fn ($r) => $r->loan_cycle_id ?: $firstCycleId);
        $disbursements = $byCycle(Disbursement::where('loan_id', $loan->id)->orderBy('disburse_date')->orderBy('id')->get());
        $repayments = $byCycle(Repayment::where('loan_id', $loan->id)->orderBy('repayment_date')->orderBy('id')->get());
        $isCash = fn ($x) => in_array($x->kind, ['cash', null], true);
        $ref = fn ($x) => trim(ucfirst(str_replace('_', ' ', (string) $x->mode)) . ($x->transaction ? ' · ' . $x->transaction : ''), ' ·') ?: null;

        $lines = [];
        $balance = 0.0;
        $totals = ['lent' => 0.0, 'interest' => 0.0, 'penalty' => 0.0, 'paid' => 0.0];
        $last = null;

        foreach ($cycles as $i => $c) {
            $b = CycleBalance::at($c);
            $n = (int) $c->cycle_number;
            $start = Carbon::parse($c->start_date)->toDateString();
            $due = Carbon::parse($c->due_date)->toDateString();
            $rate = rtrim(rtrim(number_format((float) $c->interest_rate, 2), '0'), '.');
            $rows = [];   // [date, order, text, ref, charge, payment, note]

            // how the cycle starts: the money sent (cycle 1) or the balance carried in, plus any top-up
            $sent = 0.0;
            if ($i > 0) {
                $rows[] = [$start, 0, 'Rolled over from #' . ($n - 1), null, 0.0, 0.0, true];
            }
            foreach ($disbursements->get($c->id, collect())->filter($isCash) as $d) {
                $rows[] = [Carbon::parse($d->disburse_date)->toDateString(), 1, $i === 0 ? 'Loan sent' : 'Top-up sent', $ref($d), (float) $d->amount, 0.0, false];
                $sent += (float) $d->amount;
            }
            $totals['lent'] += $sent;
            $gap = round((float) $b['principal'] - ($i === 0 ? 0 : $balance) - $sent, 2);
            if (abs($gap) >= 0.01) {
                $rows[] = [$start, 2, $i === 0 ? ($sent == 0.0 ? 'Loan principal' : 'Principal adjustment') : 'Carry-over adjustment', null, max(0, $gap), max(0, -$gap), false];
                if ($i === 0 && $sent == 0.0) {
                    $totals['lent'] += $gap;
                }
            }

            $rows[] = [$start, 3, 'Interest ' . $rate . '% to ' . Carbon::parse($due)->format('j M Y'), null, (float) $b['interest'], 0.0, false];
            $totals['interest'] += (float) $b['interest'];
            if ((float) $b['penalty'] > 0) {
                $rows[] = [$b['last_penalty_day'] ?? $due, 5, 'Late penalty' . ($b['days_subject_to_penalty'] ? ' · ' . $b['days_subject_to_penalty'] . ' days' : ''), null, (float) $b['penalty'], 0.0, false];
                $totals['penalty'] += (float) $b['penalty'];
            }
            foreach ($repayments->get($c->id, collect()) as $r) {
                if ($r->kind === 'rollover' && $b['carried_forward'] !== null) {
                    continue;   // the carry itself, not money paid
                }
                $rows[] = [Carbon::parse($r->repayment_date)->toDateString(), 4, $isCash($r) ? 'Payment received' : (self::KIND[$r->kind] ?? 'Credit'), $ref($r), 0.0, (float) $r->amount, false];
                if ($isCash($r)) {
                    $totals['paid'] += (float) $r->amount;
                }
            }

            usort($rows, fn ($a, $z) => [$a[0], $a[1]] <=> [$z[0], $z[1]]);
            foreach ($rows as [$date, , $text, $refText, $charge, $payment, $note]) {
                $balance = round($balance + $charge - $payment, 2);
                $lines[] = (object) ['date' => $date, 'n' => $n, 'text' => $text, 'ref' => $refText, 'charge' => $charge, 'payment' => $payment, 'balance' => $balance, 'note' => $note];
            }

            // end the cycle on the engine's figure (overpayments, allocation rounding)
            $owed = round((float) ($b['owed_on_close'] ?? $b['final_outstanding']), 2);
            if (abs($balance - $owed) >= 0.01) {
                $diff = round($owed - $balance, 2);
                $balance = $owed;
                $lines[] = (object) ['date' => $b['closed_on'] ?? now()->toDateString(), 'n' => $n, 'text' => 'Adjustment', 'ref' => null,
                    'charge' => max(0, $diff), 'payment' => max(0, -$diff), 'balance' => $balance, 'note' => false];
            }
            $last = $b;
        }

        return view('loans.statement', [
            'loan' => $loan,
            'lines' => $lines,
            'cycleCount' => $cycles->count(),
            'balance' => $last ? (float) $last['final_outstanding'] : 0.0,
            'dueDate' => $cycles->last()?->due_date,
            'totals' => $totals,
        ]);
    }
}
