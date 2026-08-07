<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\LoanCalculator;
use Carbon\Carbon;

class FixLoanCycles extends Command
{
    protected $signature = 'loans:fix-cycles';
    protected $description = 'Fix loan cycles to use loan_type period for due dates';

    protected $calculator;

    public function __construct(LoanCalculator $calculator)
    {
        parent::__construct();
        $this->calculator = $calculator;
    }

    public function handle()
    {
        $this->info('========================================');
        $this->info('Fixing Loan Cycles Using LoanType Period');
        $this->info('========================================');
        $this->info('');

        $loans = Loan::with(['loanType', 'cycles'])->get();
        $this->info('Found ' . $loans->count() . ' loans to process');
        $this->info('');

        $fixedCount = 0;
        $errorCount = 0;

        foreach ($loans as $loan) {
            if (!$loan->loanType) {
                $this->warn('Skipping loan #' . $loan->id . ' - no loan type');
                continue;
            }

            $this->info("Processing loan #{$loan->id}: {$loan->loanType->name}");
            $this->line("  Period: {$loan->loanType->period} {$loan->loanType->unit}");
            $this->line("  Current balance: KES " . number_format($loan->amount, 2));
            
            try {
                $cycles = $loan->cycles()->orderBy('cycle_number', 'asc')->get();
                
                if ($cycles->isEmpty()) {
                    $this->line("  No cycles found - creating initial cycle...");
                    $this->calculator->createInitialCycle($loan);
                    $fixedCount++;
                    $this->line("  ✅ Created initial cycle");
                    $this->info('');
                    continue;
                }

                // Update due dates using loan_type period
                $period = (int) $loan->loanType->period;
                $unit = $loan->loanType->unit;
                
                $borrowDate = Carbon::parse($loan->borrow_date);
                $previousDueDate = $borrowDate->copy();
                
                // Calculate first due date
                switch ($unit) {
                    case 'days': $previousDueDate->addDays($period); break;
                    case 'weeks': $previousDueDate->addWeeks($period); break;
                    case 'months': $previousDueDate->addMonths($period); break;
                    case 'years': $previousDueDate->addYears($period); break;
                    default: $previousDueDate->addDays($period);
                }

                foreach ($cycles as $cycle) {
                    if ($cycle->cycle_number === 1) {
                        $cycle->due_date = $previousDueDate;
                        $this->line("  Cycle #1 due date: " . $cycle->due_date->format('Y-m-d'));
                    } else {
                        // Calculate from previous due date
                        $newDueDate = $previousDueDate->copy();
                        switch ($unit) {
                            case 'days': $newDueDate->addDays($period); break;
                            case 'weeks': $newDueDate->addWeeks($period); break;
                            case 'months': $newDueDate->addMonths($period); break;
                            case 'years': $newDueDate->addYears($period); break;
                            default: $newDueDate->addDays($period);
                        }
                        $cycle->due_date = $newDueDate;
                        $previousDueDate = $newDueDate;
                        $this->line("  Cycle #{$cycle->cycle_number} due date: " . $cycle->due_date->format('Y-m-d'));
                    }
                    $cycle->save();
                }

                // Fix statuses - only one active
                foreach ($cycles as $index => $cycle) {
                    if ($index === $cycles->count() - 1) {
                        $cycle->status = 'active';
                        $this->line("  Cycle #{$cycle->cycle_number} status: ACTIVE");
                    } else {
                        $cycle->status = 'completed';
                        $this->line("  Cycle #{$cycle->cycle_number} status: COMPLETED");
                    }
                    $cycle->save();
                }

                // Update loan
                $lastCycle = $cycles->last();
                if ($lastCycle) {
                    $loan->cycle = $lastCycle->cycle_number;
                    $loan->due_date = $lastCycle->due_date;
                    $loan->calculated_due_date = $lastCycle->due_date;
                    $loan->amount = $lastCycle->new_balance;
                    $loan->capitalized_interest = $loan->cycles()->sum('interest_capitalized');
                    $loan->save();
                    $this->line("  Loan updated: cycle={$lastCycle->cycle_number}, balance=KES " . number_format($loan->amount, 2));
                    $this->line("  Due date: " . $loan->due_date->format('Y-m-d'));
                }

                $fixedCount++;
                $this->line("  ✅ Fixed successfully");
                $this->info('');

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("  ❌ Error: " . $e->getMessage());
                $this->info('');
            }
        }

        $this->info('========================================');
        $this->info('SUMMARY');
        $this->info('========================================');
        $this->info("✅ Fixed: {$fixedCount} loans");
        $this->info("❌ Errors: {$errorCount} loans");
        $this->info('');
        $this->info('Done!');
    }
}