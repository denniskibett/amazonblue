<?php

namespace App\Services;

use App\Models\Loan;
use Illuminate\Support\Carbon;

/**
 * Everything a borrower sees about one of their loans in the progress drawer:
 * timeline (applied → approved → sent → repaid), money summary, cycles, repayments
 * and the signed agreement.
 */
class LoanProgress
{
    public function __construct(private LoanCalculator $calculator)
    {
    }

    public function for(Loan $loan): array
    {
        $loan->loadMissing(['loanType', 'cycles', 'repayments', 'disbursements']);

        $cycles = $loan->cycles->sortBy('cycle_number')->values();
        $current = $cycles->firstWhere('status', 'active') ?? $cycles->last();
        $balance = null;
        if ($current) {
            try {
                $calc = $this->calculator->calculateCycleBalance($loan, $current);
                $balance = [
                    'total' => (float) $calc['full_balance'],
                    'paid' => (float) $calc['total_repayments'],
                    'penalty' => (float) $calc['penalty'],
                    'due' => (float) $calc['final_outstanding'],
                ];
            } catch (\Throwable $e) {
                $balance = ['total' => (float) $current->new_balance, 'paid' => 0.0, 'penalty' => 0.0, 'due' => (float) $current->new_balance];
            }
        }

        $disbursement = $loan->disbursements->sortBy('id')->first();
        $repayments = $loan->repayments->sortByDesc('repayment_date')->values();
        $dueDate = $current?->due_date ?? $loan->due_date;
        $isLate = $dueDate && in_array($loan->status, ['disbursed', 'active', 'overdue'], true)
            && now()->startOfDay()->gt(Carbon::parse($dueDate)->startOfDay());

        $reached = fn (array $statuses) => in_array($loan->status, $statuses, true);
        $sentStatuses = ['disbursed', 'active', 'overdue', 'forbearance', 'defaulted', 'recovery', 'repaid', 'written_off'];

        $timeline = [
            ['label' => 'Applied', 'done' => true, 'date' => $loan->created_at,
                'detail' => 'KES ' . number_format($loan->amount) . ' · ' . ($loan->loanType->name ?? '')],
            $loan->status === 'rejected'
                ? ['label' => 'Not approved', 'done' => true, 'date' => $loan->updated_at, 'detail' => 'This application was declined.', 'tone' => 'red']
                : ['label' => 'Approved', 'done' => $reached(array_merge(['approved'], $sentStatuses)), 'date' => null,
                    'detail' => $loan->status === 'pending' ? 'Our team is reviewing your application.' : 'Approved by our team.'],
            ['label' => 'Sent to M-Pesa', 'done' => $reached($sentStatuses), 'date' => $disbursement?->disburse_date,
                'detail' => $disbursement ? 'KES ' . number_format((float) ($disbursement->net_amount ?: $disbursement->amount)) . ($disbursement->transaction ? ' · ref ' . $disbursement->transaction : '') : 'Sent once approved.'],
            ['label' => 'Repaid', 'done' => $loan->status === 'repaid', 'date' => $loan->status === 'repaid' ? $repayments->first()?->repayment_date : null,
                'detail' => $loan->status === 'repaid' ? 'Fully repaid. Thank you!'
                    : ($dueDate ? ($isLate ? 'Overdue since ' : 'Due by ') . Carbon::parse($dueDate)->format('j M Y') : 'Repay by the due date.'),
                'tone' => $isLate ? 'red' : null],
        ];
        if ($loan->status !== 'rejected') {
            // the first step not yet done is the one in progress
            foreach ($timeline as $i => $step) {
                if (! $step['done']) {
                    $timeline[$i]['current'] = true;
                    break;
                }
            }
        }

        return [
            'loan' => $loan,
            'reference' => 'Loan #' . str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT),
            'timeline' => $timeline,
            'balance' => $balance,
            'due_date' => $dueDate,
            'is_late' => $isLate,
            'progress' => $balance && $balance['total'] > 0 ? (int) min(100, round($balance['paid'] / $balance['total'] * 100)) : 0,
            'cycles' => $cycles,
            'repayments' => $repayments,
            // signed copy frozen on file, or consented (frozen on first view)
            'has_agreement' => (bool) ($loan->agreement_sha256 || $loan->consent),
        ];
    }
}
