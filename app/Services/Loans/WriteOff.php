<?php

namespace App\Services\Loans;

use App\Models\ActionType;
use App\Models\DebtRecoveryCase;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\RecoveryAction;
use App\Models\RecoveryCaseNote;
use App\Models\RecoveryStatus;
use App\Models\Repayment;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\LoanCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Writing a recovery case off (moved from CaseController so a checker's approval can run it; Stage 4 Phase 5).
 * The case and loan become written_off. Engine v2 also records the balance as a bad_debt book entry dated today,
 * so the ledger moves it to bad debts and the books agree with the status (decision 7).
 */
class WriteOff
{
    public function perform(DebtRecoveryCase $case, string $reason, User $by, ?User $maker = null): void
    {
        $status = RecoveryStatus::where('slug', 'written_off')->first() ?? throw new \RuntimeException('Written off status not found.');

        DB::transaction(function () use ($case, $reason, $by, $maker, $status) {
            $amount = (float) $case->total_debt_amount;
            $loan = $case->loan;
            $oldLoanStatus = $loan->status ?? null;
            $oldCaseStatus = $case->status_id;
            $who = $maker && $maker->id !== $by->id ? $maker->name . ', approved by ' . $by->name : $by->name;
            $summary = 'WRITTEN OFF ' . number_format($amount, 2) . ' on ' . now()->toDateString() . ' by ' . $who . '. Reason: ' . $reason;

            $case->update(['status_id' => $status->id, 'notes' => ($case->notes ? $case->notes . "\n\n" : '') . $summary, 'updated_by' => $by->id]);

            if ($loan) {
                if (LoanCalculator::v2() && ($cycle = LoanCycle::where('loan_id', $loan->id)->orderByDesc('cycle_number')->first())) {
                    $owed = CycleBalance::at($cycle)['final_outstanding'];
                    if ($owed > 0.004) {
                        Repayment::create(['loan_id' => $loan->id, 'loan_cycle_id' => $cycle->id, 'amount' => $owed,
                            'repayment_date' => now()->toDateString(), 'transaction' => 'WRITE OFF: case #' . $case->id,
                            'kind' => 'bad_debt', 'mode' => 'book', 'created_by' => $by->id, 'notes' => $summary]);
                    }
                }
                $loan->update(['status' => Loan::STATUS_WRITTEN_OFF]);
            }

            RecoveryCaseNote::create(['case_id' => $case->id, 'note_type' => 'alert', 'note' => $summary, 'created_by' => $by->id]);
            if ($other = ActionType::where('slug', 'other')->value('id')) {
                RecoveryAction::create(['case_id' => $case->id, 'action_type_id' => $other, 'action_date' => now(), 'performed_by' => $by->id,
                    'outcome' => 'failed', 'amount_collected' => 0, 'notes' => $summary]);
            }
            AuditTrail::record('debt_recovery_cases', $case->id, 'update',
                ['status_id' => $oldCaseStatus, 'loan_status' => $oldLoanStatus],
                ['event' => 'write_off', 'status_id' => $status->id, 'loan_id' => $case->loan_id, 'loan_status' => Loan::STATUS_WRITTEN_OFF,
                    'written_off_amount' => $amount, 'reason' => $reason, 'maker' => $maker?->id, 'checker' => $by->id]);
        });
    }
}
