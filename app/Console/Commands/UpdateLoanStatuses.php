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

        // Exclude final statuses (repaid, written_off, rejected)
        if (!$specificLoan) {
            $query->whereNotIn('status', [
                Loan::STATUS_REPAID,
                Loan::STATUS_WRITTEN_OFF,
                Loan::STATUS_REJECTED,
            ]);
        }

        // If not force, exclude forbearance and recovery
        if (!$force) {
            $query->whereNotIn('status', [
                Loan::STATUS_FORBEARANCE,
                Loan::STATUS_RECOVERY,
            ]);
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

        // Get due date
        $dueDate = $loan->getDueDate();
        if (!$dueDate) {
            return ['updated' => false, 'skipped' => true, 'reason' => 'No due date'];
        }

        // Calculate days overdue
        $daysOverdue = $loan->calculateDaysOverdue();
        $threshold = $loan->getNplThreshold();

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
                $newStatus = Loan::STATUS_ACTIVE;
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
                $newStatus = Loan::STATUS_ACTIVE;
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
        // CASE 4: Overdue (but not defaulted yet)
        elseif ($daysOverdue > 0 && $oldStatus !== Loan::STATUS_OVERDUE) {
            $newStatus = Loan::STATUS_OVERDUE;
            $shouldUpdate = true;
            $reason = "Overdue: {$daysOverdue} days";
        }

        // Apply changes
        if ($shouldUpdate && !$dryRun) {
            $loan->status = $newStatus;
            $loan->save();
        }

        return [
            'updated' => $shouldUpdate,
            'old_status' => $oldStatus,
            'new_status' => $shouldUpdate ? $newStatus : $oldStatus,
            'reason' => $reason,
            'skipped' => !$shouldUpdate && !$skipped,
        ];
    }
}