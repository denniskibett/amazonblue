<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\DebtRecoveryCase;
use App\Models\RecoveryStatus;
use App\Models\RecoveryPriority;
use App\Models\RecoveryCaseNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NplDetectionService
{
    /**
     * Run full NPL detection
     */
    public function runDetection()
    {
        Log::info('NPL Detection started');

        try {
            DB::beginTransaction();

            $results = [
                'loans_checked' => 0,
                'new_npl' => 0,
                'new_overdue' => 0,
                'recovery_cases_created' => 0,
                'errors' => [],
            ];

            // Find loans to check
            $loansToCheck = Loan::with(['loanType', 'user', 'repayments', 'disbursements'])
                ->whereIn('status', ['disbursed', 'approved', 'active', 'overdue'])
                ->where('default_triggered', 0)
                ->get();

            $results['loans_checked'] = $loansToCheck->count();

            foreach ($loansToCheck as $loan) {
                try {
                    // Update NPL status
                    $loan->updateNplStatus();
                    $results['loans_checked']++;

                    if ($loan->is_non_performing) {
                        $results['new_npl']++;
                        
                        // Create recovery case if not exists
                        $caseCreated = $this->createRecoveryCase($loan);
                        if ($caseCreated) {
                            $results['recovery_cases_created']++;
                        }
                    } elseif ($loan->isOverdue()) {
                        $results['new_overdue']++;
                    }

                } catch (\Exception $e) {
                    $results['errors'][] = "Loan #{$loan->id}: " . $e->getMessage();
                    Log::error('Error processing loan #' . $loan->id . ': ' . $e->getMessage());
                }
            }

            DB::commit();

            Log::info('NPL Detection completed', $results);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('NPL Detection failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create recovery case for an NPL loan
     */
    public function createRecoveryCase($loan)
    {
        // Check if recovery case already exists
        $existingCase = DebtRecoveryCase::where('loan_id', $loan->id)
            ->whereHas('status', function($q) {
                $q->whereIn('slug', ['open', 'in_progress', 'negotiation', 'legal']);
            })
            ->first();

        if ($existingCase) {
            Log::info("Recovery case already exists for loan #{$loan->id}");
            return false;
        }

        // Determine status and priority based on days overdue
        $daysOverdue = $loan->days_overdue;
        $period = $loan->loanType->period ?? 30;
        $ratio = $daysOverdue / max(1, $period);

        if ($ratio > 4) {
            $statusSlug = 'legal';
            $prioritySlug = 'urgent';
        } elseif ($ratio > 3) {
            $statusSlug = 'legal';
            $prioritySlug = 'high';
        } elseif ($ratio > 2) {
            $statusSlug = 'negotiation';
            $prioritySlug = 'high';
        } elseif ($ratio > 1.5) {
            $statusSlug = 'in_progress';
            $prioritySlug = 'medium';
        } else {
            $statusSlug = 'open';
            $prioritySlug = 'medium';
        }

        $status = RecoveryStatus::where('slug', $statusSlug)->first();
        $priority = RecoveryPriority::where('slug', $prioritySlug)->first();

        if (!$status || !$priority) {
            Log::error("Could not find status or priority for loan #{$loan->id}");
            return false;
        }

        // Calculate total debt
        $totalDisbursed = $loan->disbursements->sum('amount') ?? $loan->amount;
        $totalRepaid = $loan->repayments->sum('amount') ?? 0;
        $interest = ($loan->loanType->interest_rate / 100) * $loan->amount;
        $penalty = $loan->calculatePenalties();

        $totalDebt = max(0, $totalDisbursed + $interest + $penalty - $totalRepaid);

        // Generate case number
        $year = now()->format('Y');
        $count = DebtRecoveryCase::whereYear('created_at', $year)->count() + 1;
        $caseNumber = 'DR-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        // Find default assignee
        $assignedTo = User::whereIn('role', ['admin', 'teller'])->first();

        // Create the recovery case
        $case = DebtRecoveryCase::create([
            'user_id' => $loan->user_id,
            'loan_id' => $loan->id,
            'case_number' => $caseNumber,
            'total_debt_amount' => $totalDebt,
            'principal_outstanding' => max(0, $loan->amount - min($totalRepaid, $loan->amount)),
            'interest_outstanding' => max(0, $interest - max(0, $totalRepaid - $loan->amount)),
            'penalty_outstanding' => $penalty,
            'fees_outstanding' => 0,
            'default_date' => $loan->default_date ?? now(),
            'days_in_default' => $loan->days_overdue,
            'status_id' => $status->id,
            'priority_id' => $priority->id,
            'assigned_to' => $assignedTo ? $assignedTo->id : null,
            'recovery_officer' => $assignedTo ? $assignedTo->name : null,
            'notes' => "Automatically created from NPL detection. Loan is {$loan->days_overdue} days overdue (Period: {$loan->loanType->period} {$loan->loanType->unit}).",
            'created_by' => 1, // System user
        ]);

        // Create initial note
        RecoveryCaseNote::create([
            'case_id' => $case->id,
            'note_type' => 'alert',
            'note' => "Case automatically created by NPL detection system. Loan is {$loan->days_overdue} days overdue. Total debt: KES " . number_format($totalDebt, 2),
            'created_by' => 1,
        ]);

        Log::info("Recovery case created: {$caseNumber} for loan #{$loan->id}");

        return $case;
    }

    /**
     * Check a specific loan for NPL status
     */
    public function checkLoan($loanId)
    {
        $loan = Loan::with(['loanType', 'user', 'repayments'])->find($loanId);
        
        if (!$loan) {
            return ['error' => 'Loan not found'];
        }

        $loan->updateNplStatus();
        
        return [
            'loan_id' => $loan->id,
            'is_npl' => $loan->is_non_performing,
            'is_overdue' => $loan->isOverdue(),
            'days_overdue' => $loan->days_overdue,
            'threshold' => $loan->npl_trigger_threshold,
            'status' => $loan->status,
        ];
    }

    /**
     * Get NPL statistics
     */
    public function getNplStats()
    {
        return [
            'total_loans' => Loan::count(),
            'npl_count' => Loan::where('is_non_performing', true)->count(),
            'overdue_count' => Loan::where('status', 'overdue')->count(),
            'defaulted_count' => Loan::where('status', 'defaulted')->count(),
            'npl_total_debt' => Loan::where('is_non_performing', true)->sum('amount'),
            'overdue_total_debt' => Loan::where('status', 'overdue')->sum('amount'),
            'npl_by_stage' => [
                'early_overdue' => Loan::where('days_overdue', '>', 0)
                    ->where('days_overdue', '<=', function($query) {
                        // This is a simplified version
                        return 30;
                    })
                    ->count(),
                'overdue' => Loan::where('status', 'overdue')->count(),
                'serious_overdue' => Loan::where('status', 'defaulted')
                    ->where('is_non_performing', false)
                    ->count(),
                'npl' => Loan::where('is_non_performing', true)->count(),
            ],
        ];
    }
}