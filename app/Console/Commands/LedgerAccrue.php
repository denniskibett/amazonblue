<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\LoanCalculator;
use App\Services\Ledger;
use Illuminate\Console\Command;

/**
 * Nightly (01:15): books each open cycle's charges up to today (engine v2 + ledger): the day's penalty, and
 * any interest or carry difference not yet booked. Posts only differences, so it is safe to run any time.
 */
class LedgerAccrue extends Command
{
    protected $signature = 'ledger:accrue {--dry-run : Show what would be posted; change nothing}';

    protected $description = 'Book open cycles\' interest and penalty to the ledger (engine v2)';

    public function handle(Ledger $ledger): int
    {
        if (! Ledger::enabled() || ! LoanCalculator::v2()) {
            $this->line('Nothing to do: needs lending.ledger.enabled and lending.engine = v2.');

            return self::SUCCESS;
        }

        $open = Loan::whereNotIn('status', [Loan::STATUS_PENDING, Loan::STATUS_APPROVED, Loan::STATUS_REJECTED, Loan::STATUS_REPAID, Loan::STATUS_WRITTEN_OFF])->pluck('id');
        $cycles = LoanCycle::whereIn('loan_id', $open)->where('status', 'active')->get();
        $minor = 0;
        $posted = 0;
        $failed = [];
        foreach ($cycles as $cycle) {
            // one bad cycle is reported and skipped; it doesn't stop the rest of the book
            try {
                $steps = $ledger->sync('loan_cycle', $cycle->id, $ledger->planLoanCycle($cycle)['legs'],
                    ['loan_id' => $cycle->loan_id, 'date' => now()->toDateString(), 'source' => 'ledger', 'reference' => 'Cycle ' . $cycle->cycle_number],
                    (bool) $this->option('dry-run'));
            } catch (\Throwable $e) {
                $failed[] = 'cycle #' . $cycle->id . ' (loan #' . $cycle->loan_id . '): ' . mb_substr($e->getMessage(), 0, 200);

                continue;
            }
            $posted += count($steps);
            $minor += array_sum(array_column($steps, 'minor'));
        }

        $this->line(sprintf('%d open cycle(s); %d posting(s) %s, net KES %s.', $cycles->count(), $posted,
            $this->option('dry-run') ? 'to make' : 'made', number_format($minor / (10 ** Ledger::dp()), 2)));

        if ($failed) {
            foreach ($failed as $line) {
                $this->error('Failed: ' . $line);
            }
            \App\Support\StaffAlarm::raise('ledger-accrue', 'Ledger accrual: ' . count($failed) . ' cycle(s) failed',
                'The nightly ledger:accrue skipped ' . count($failed) . ' cycle(s) it could not book; the rest were booked. First: ' . $failed[0] . '.',
                ['failed' => $failed]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
