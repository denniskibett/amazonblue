<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\LoanCalculator;
use Carbon\Carbon;

class RepairLoanCycles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'loans:repair-cycles';

    /**
     * The console command description.
     */
    protected $description = 'Repairs loan cycles, due dates, balances and statuses';

    protected LoanCalculator $calculator;

    public function __construct(LoanCalculator $calculator)
    {
        parent::__construct();

        $this->calculator = $calculator;
    }

    public function handle()
    {
        $this->info('======================================');
        $this->info('Loan Cycle Repair');
        $this->info('======================================');

        $loans = Loan::with(['loanType', 'cycles'])->get();

        $this->info("Found {$loans->count()} loans.");
        $this->newLine();

        foreach ($loans as $loan) {

            if (!$loan->loanType) {
                $this->warn("Loan #{$loan->id} has no Loan Type. Skipping.");
                continue;
            }

            $period = (int) $loan->loanType->period;
            $unit   = strtolower($loan->loanType->unit);

            $this->line("Processing Loan #{$loan->id}");

            $cycles = $loan->cycles()
                ->orderBy('cycle_number')
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Create Initial Cycle if missing
            |--------------------------------------------------------------------------
            */

            if ($cycles->isEmpty()) {

                try {

                    $this->calculator->createInitialCycle($loan);

                    $this->info("   ✓ Initial cycle created");

                    $cycles = $loan->cycles()
                        ->orderBy('cycle_number')
                        ->get();

                } catch (\Throwable $e) {

                    $this->error($e->getMessage());

                    continue;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Fix Due Dates
            |--------------------------------------------------------------------------
            */

            $borrowDate = Carbon::parse($loan->borrow_date);

            foreach ($cycles as $index => $cycle) {

                if ($index == 0) {

                    $dueDate = $borrowDate->copy();

                } else {

                    $dueDate = Carbon::parse($cycles[$index - 1]->due_date);

                }

                switch ($unit) {

                    case 'days':
                        $dueDate->addDays($period);
                        break;

                    case 'weeks':
                        $dueDate->addWeeks($period);
                        break;

                    case 'months':
                        $dueDate->addMonths($period);
                        break;

                    case 'years':
                        $dueDate->addYears($period);
                        break;

                    default:
                        $dueDate->addDays($period);
                }

                $cycle->due_date = $dueDate;

                /*
                |--------------------------------------------------------------------------
                | Interest Calculations
                |--------------------------------------------------------------------------
                */

                if ($cycle->cycle_number == 1) {

                    $interest = ($loan->loanType->interest_rate / 100) * $loan->amount;

                    $cycle->previous_balance = 0;

                    $cycle->interest_capitalized = $interest;

                    $cycle->new_balance = $loan->amount + $interest;

                } else {

                    $previous = $cycles[$index - 1];

                    $interest = ($loan->loanType->interest_rate / 100) * $previous->new_balance;

                    $cycle->previous_balance = $previous->new_balance;

                    $cycle->interest_capitalized = $interest;

                    $cycle->new_balance = $previous->new_balance + $interest;
                }

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                if ($index == ($cycles->count() - 1)) {

                    $cycle->status = 'active';

                } else {

                    $cycle->status = 'completed';
                }

                $cycle->save();

                $this->line("   Cycle {$cycle->cycle_number} repaired.");
            }

            /*
            |--------------------------------------------------------------------------
            | Update Loan
            |--------------------------------------------------------------------------
            */

            $latest = $loan->cycles()
                ->orderByDesc('cycle_number')
                ->first();

            if ($latest) {

                $loan->cycle = $latest->cycle_number;

                $loan->due_date = $latest->due_date;

                $loan->calculated_due_date = $latest->due_date;

                $loan->amount = $latest->new_balance;

                $loan->capitalized_interest = $loan->cycles()->sum('interest_capitalized');

                $loan->save();
            }

            $this->info("✓ Loan #{$loan->id} repaired.");
            $this->newLine();
        }

        $this->info('======================================');
        $this->info('Loan repair complete.');
        $this->info('======================================');

        return self::SUCCESS;
    }
}