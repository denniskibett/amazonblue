<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Services\Loans\LoanDecision;
use Illuminate\Console\Command;

/**
 * One owner decision on one or more loans (App\Services\Loans\LoanDecision). Without --apply it only shows what
 * would change.
 *
 *   php artisan loans:decide forbearance 467,389 --note="owner 21 Oct 2026"            (open-ended)
 *   php artisan loans:decide forbearance 209 --until=2026-12-05
 *   php artisan loans:decide active 511 --apply
 */
class LoansDecide extends Command
{
    protected $signature = 'loans:decide {action : forbearance | active} {loans : Comma-separated loan ids}
        {--until= : Forbearance end date (default open-ended)} {--note= : Recorded in the audit log} {--apply : Save it}';

    protected $description = 'Apply one owner decision (forbearance, active) to loans; preview unless --apply';

    public function handle(LoanDecision $decide): int
    {
        if (! in_array($this->argument('action'), LoanDecision::ACTIONS, true)) {
            $this->error('Action must be one of: ' . implode(', ', LoanDecision::ACTIONS));

            return self::FAILURE;
        }
        $failed = false;
        foreach (array_map('intval', explode(',', $this->argument('loans'))) as $id) {
            try {
                $loan = Loan::findOrFail($id);
                [$before, $after, $reason] = $this->option('apply')
                    ? $decide->apply($loan, $this->argument('action'), $this->option('until'), $this->option('note'))
                    : $decide->preview($loan, $this->argument('action'), $this->option('until'));
                $this->line(sprintf('loan #%d: %s -> %s; owed %s -> %s; %s%s', $id, $before['status'], $after['status'],
                    number_format($before['owed'], 2), number_format($after['owed'], 2), $reason, $this->option('apply') ? '' : '  (preview)'));
            } catch (\Throwable $e) {
                $failed = true;
                $this->error("loan #{$id}: " . strtok($e->getMessage(), "\n"));
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
