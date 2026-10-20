<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanCycle;

/**
 * The loan row follows its cycles (owner, 20 Oct 2026; AUDIT rule 7), under engine v2:
 *  - original_amount = what cycle 1 lent;
 *  - amount = what the latest cycle lends (the current principal, never principal + interest);
 *  - cycle, due_date = the latest cycle's.
 * Called after every cycle change (LoanCycle saved) and by rollover and loans:restate.
 */
class LoanSync
{
    public static function fromCycles(Loan $loan): void
    {
        $cycles = LoanCycle::where('loan_id', $loan->id)->orderBy('cycle_number')->get();
        if ($cycles->isEmpty()) {
            return;
        }
        $first = $cycles->first();
        $latest = $cycles->last();

        $loan->forceFill([
            'original_amount' => $first->lentPrincipal(),
            'amount' => $latest->lentPrincipal(),
            'cycle' => $latest->cycle_number,
            'due_date' => $latest->due_date,
            'calculated_due_date' => $latest->due_date,
        ]);
        if ($loan->isDirty()) {
            $loan->save();
        }
    }
}
