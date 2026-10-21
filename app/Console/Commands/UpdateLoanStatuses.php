<?php

namespace App\Console\Commands;

use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateLoanStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:update-statuses 
                            {--dry-run : Show what would change without saving}
                            {--loan= : Update only a specific loan ID}
                            {--status= : Only update loans with this status}
                            {--force : Force update even if in forbearance or recovery}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update loan statuses based on overdue days and NPL thresholds';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificLoan = $this->option('loan');
        $statusFilter = $this->option('status');
        $force = $this->option('force');

        $this->info('🚀 Starting loan status update...');
        $this->newLine();

        // Build query
        $query = Loan::query();

        // Filter by specific loan
        if ($specificLoan) {
            $query->where('id', $specificLoan);
            $this->line("🔍 Processing specific loan #{$specificLoan}");
        }

        // Filter by status
        if ($statusFilter) {
            $query->where('status', $statusFilter);
            $this->line("🔍 Filtering by status: {$statusFilter}");
        }

        // Only loans that have been disbursed can become overdue: never touch
        // pending / approved / rejected, and skip final statuses (repaid, written_off).
        $query->whereNotIn('status', [
            Loan::STATUS_PENDING,
            Loan::STATUS_APPROVED,
            Loan::STATUS_REJECTED,
            Loan::STATUS_REPAID,
            Loan::STATUS_WRITTEN_OFF,
        ]);

        // If not force, exclude recovery and forbearance that is still running
        // (expired forbearance is picked up so it can move to overdue)
        if (!$force) {
            $query->where('status', '!=', Loan::STATUS_RECOVERY)
                ->where(function ($q) {
                    $q->where('status', '!=', Loan::STATUS_FORBEARANCE)
                        ->orWhereNull('forbearance_until')
                        ->orWhere('forbearance_until', '<', now());
                });
        }

        $loans = $query->get();
        $total = $loans->count();

        if ($total === 0) {
            $this->warn('⚠️ No loans found to process.');
            return 0;
        }

        $this->info("📊 Processing {$total} loans...");
        $this->newLine();

        $stats = [
            'processed' => 0,
            'updated' => 0,
            'defaulted' => 0,
            'recovered' => 0,
            'errors' => 0,
            'skipped' => 0,
        ];

        // Progress bar
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($loans as $loan) {
            try {
                $result = $this->processLoan($loan, $dryRun, $force);
                $stats['processed']++;

                if ($result['updated']) {
                    $stats['updated']++;
                    if ($result['new_status'] === Loan::STATUS_DEFAULTED) {
                        $stats['defaulted']++;
                    }
                    if ($result['new_status'] === Loan::STATUS_REPAID) {
                        $stats['recovered']++;
                    }
                    
                    $this->line("\n  📝 Loan #{$loan->id}: {$result['old_status']} → {$result['new_status']}");
                    if ($result['reason']) {
                        $this->line("     Reason: {$result['reason']}");
                    }
                }

                if ($result['skipped']) {
                    $stats['skipped']++;
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $this->error("\n  ❌ Error processing loan #{$loan->id}: " . $e->getMessage());
                Log::error('Loan status update error', [
                    'loan_id' => $loan->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total loans processed', $stats['processed']],
                ['Statuses updated', $stats['updated']],
                ['Newly defaulted', $stats['defaulted']],
                ['Newly recovered/repaid', $stats['recovered']],
                ['Skipped (no change needed)', $stats['skipped']],
                ['Errors', $stats['errors']],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  DRY RUN completed - no changes were saved to the database.');
            $this->warn('   Remove --dry-run to apply changes.');
        }

        // Log summary
        Log::info('Loan status update completed', [
            'processed' => $stats['processed'],
            'updated' => $stats['updated'],
            'defaulted' => $stats['defaulted'],
            'recovered' => $stats['recovered'],
            'errors' => $stats['errors'],
            'dry_run' => $dryRun,
        ]);

        return 0;
    }

    /**
     * Process a single loan
     */
    private function processLoan(Loan $loan, bool $dryRun, bool $force): array
    {
        $oldStatus = $loan->status;
        $reason = null;
        $skipped = false;

        // Check if loan is in a state that shouldn't be auto-updated
        if (!$force) {
            if ($loan->isForbearanceActive()) {
                return ['updated' => false, 'skipped' => true, 'reason' => 'In forbearance'];
            }
            if ($loan->isInRecovery()) {
                return ['updated' => false, 'skipped' => true, 'reason' => 'In recovery'];
            }
            if ($loan->isFinal()) {
                return ['updated' => false, 'skipped' => true, 'reason' => 'Final status'];
            }
        }

        if (\App\Services\LoanCalculator::v2()) {
            return $this->processLoanV2($loan, $dryRun);
        }

        // Get due date
        $dueDate = $loan->getDueDate();
        if (!$dueDate) {
            return ['updated' => false, 'skipped' => true, 'reason' => 'No due date'];
        }

        // Calculate days overdue
        $daysOverdue = $loan->calculateDaysOverdue();
        $threshold = $loan->getDefaultThresholdDays();

        // Update days overdue field
        if (!$dryRun) {
            $loan->days_overdue = $daysOverdue;
            $loan->last_overdue_check = now();
        }

        // Determine new status
        $newStatus = $oldStatus;
        $shouldUpdate = false;

        // CASE 1: Not overdue
        if ($daysOverdue <= 0) {
            if (in_array($oldStatus, [Loan::STATUS_OVERDUE, Loan::STATUS_DEFAULTED])) {
                $newStatus = Loan::STATUS_DISBURSED;
                $shouldUpdate = true;
                $reason = 'Loan is no longer overdue';
                
                // Reset NPL flags
                if (!$dryRun && $loan->is_non_performing) {
                    $loan->is_non_performing = false;
                }
            }
        }
        // CASE 2: Check grace days
        elseif ($loan->grace_days_balance > 0 && $loan->grace_days_balance >= $daysOverdue) {
            if (!$dryRun) {
                $loan->useGraceDays((int) $daysOverdue);
            }
            if (in_array($oldStatus, [Loan::STATUS_OVERDUE, Loan::STATUS_DEFAULTED])) {
                $newStatus = Loan::STATUS_DISBURSED;
                $shouldUpdate = true;
                $reason = "Grace days used: {$daysOverdue} days";
            }
        }
        // CASE 3: Defaulted
        elseif ($daysOverdue >= $threshold && !$loan->default_triggered) {
            $newStatus = Loan::STATUS_DEFAULTED;
            $shouldUpdate = true;
            $reason = "Default triggered: {$daysOverdue} days overdue (threshold: {$threshold} days)";
            
            if (!$dryRun) {
                $loan->markAsDefaulted($reason);
            }
        }
        // CASE 4: Overdue (but not defaulted yet). A loan already defaulted stays
        // defaulted - without this guard every later run flipped defaulted -> overdue.
        elseif ($daysOverdue > 0 && !in_array($oldStatus, [Loan::STATUS_OVERDUE, Loan::STATUS_DEFAULTED], true)) {
            $newStatus = Loan::STATUS_OVERDUE;
            $shouldUpdate = true;
            $reason = "Overdue: {$daysOverdue} days";
        }

        // Apply changes (also persists days_overdue / last_overdue_check when the status is unchanged)
        if (!$dryRun) {
            if ($shouldUpdate) {
                $loan->status = $newStatus;
            }
            if ($loan->isDirty()) {
                $loan->save();
            }
        }

        return [
            'updated' => $shouldUpdate,
            'old_status' => $oldStatus,
            'new_status' => $shouldUpdate ? $newStatus : $oldStatus,
            'reason' => $reason,
            'skipped' => !$shouldUpdate && !$skipped,
        ];
    }

    /**
     * v2 (Stage 4 Phase 2): the status follows the latest cycle's balance (Loans\LoanStatus), so a loan that has
     * been paid is never marked overdue, and one that owes nothing becomes repaid. Default (with its recovery
     * case) still happens here, once the cycle is past its threshold.
     */
    private function processLoanV2(Loan $loan, bool $dryRun): array
    {
        $old = $loan->status;
        $d = \App\Services\Loans\LoanStatus::decide($loan);
        if (! $d) {
            return ['updated' => false, 'skipped' => true, 'old_status' => $old, 'new_status' => $old, 'reason' => 'Left alone'];
        }
        // a loan in forbearance (penalty waived today) is never defaulted by the nightly run
        $defaults = ! in_array($d['status'], [Loan::STATUS_REPAID, Loan::STATUS_FORBEARANCE], true) && $d['balance']['is_defaulted'] && ! $loan->default_triggered;
        $new = $defaults ? Loan::STATUS_DEFAULTED : $d['status'];
        $reason = $defaults
            ? "Default triggered: {$d['balance']['days_overdue']} days overdue (threshold: {$d['balance']['default_threshold_days']} days)"
            : $d['reason'];

        if (! $dryRun) {
            \App\Services\Loans\LoanStatus::apply($loan);
            if ($defaults) {
                $loan->refresh()->markAsDefaulted($reason);
            }
        }

        return ['updated' => $new !== $old, 'skipped' => $new === $old, 'old_status' => $old, 'new_status' => $new, 'reason' => $reason];
    }
}
