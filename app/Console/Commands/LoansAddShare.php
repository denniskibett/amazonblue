<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\LoanRepair;
use App\Services\Loans\LoanSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Adds cash a partner put into a loan that was never recorded, and rebuilds the cycles up to the agreed payment plan,
 * which stays as it is (owner, 14 Nov 2026; loan 458: ISIRO's 44,000 from 30 Jun 2026, then cycles 1-8 readjusted,
 * forbearance kept on cycle 9). Shows the cycles before and after; --apply saves.
 *
 *   php artisan loans:add-share 458 --amount=44000 --on=2026-06-30 --why="ISIRO's share of the facility" --apply
 */
class LoansAddShare extends Command
{
    protected $signature = 'loans:add-share {loan}
        {--amount= : cash added}
        {--on= : the day it went in (Y-m-d)}
        {--why= : what it was}
        {--apply : save it (otherwise worked out and rolled back)}';

    protected $description = 'Add unrecorded partner cash to a loan and rebuild its cycles up to the agreed plan';

    public function handle(LoanRepair $repair): int
    {
        $loan = Loan::find($this->argument('loan'));
        $amount = (float) $this->option('amount');
        $on = (string) $this->option('on');
        $why = trim((string) $this->option('why')) ?: null;
        if (! $loan || $amount <= 0 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $on) || ! $why) {
            $this->error('Give a loan, --amount above 0, --on=Y-m-d and --why.');

            return self::FAILURE;
        }

        $before = $this->cycles($loan->id);
        DB::beginTransaction();
        try {
            $this->line($repair->addMissingDisbursement($loan->id, $why, $amount, $on, $why . ' (owner, ' . now()->format('j M Y') . ')'));
            $this->line($repair->rebuildFromCash($loan->id, $why, keepPlan: true));
            LoanSync::fromCycles($loan->fresh());
            $after = $this->cycles($loan->id);
            $apply = (bool) $this->option('apply');
            $apply ? DB::commit() : DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $rows = [];
        foreach ($after as $n => $a) {
            $b = $before[$n] ?? null;
            $rows[] = [$n, $a['dates'], $b ? number_format($b['principal'], 2) : '', number_format($a['principal'], 2),
                number_format($a['interest'], 2), $b ? number_format($b['owed'], 2) : '', number_format($a['owed'], 2)];
        }
        $this->table(['Cycle', 'Dates', 'Principal before', 'Principal after', 'Interest after', 'Owed at close before', 'Owed at close after'], $rows);
        $last = end($after);
        $this->info('Balance due now: ' . number_format($last['due'], 2) . ' (was ' . number_format(end($before)['due'], 2) . ')');
        $this->comment($this->option('apply') ? 'Saved.' : 'Nothing saved. Add --apply to save it.');

        return self::SUCCESS;
    }

    /** [cycle_number => principal, interest, owed at close (or now), due now, dates]. */
    private function cycles(int $loanId): array
    {
        $out = [];
        foreach (LoanCycle::where('loan_id', $loanId)->orderBy('cycle_number')->get() as $c) {
            $b = CycleBalance::at($c);
            $out[$c->cycle_number] = [
                'principal' => $c->lentPrincipal(), 'interest' => (float) $c->interest_capitalized,
                'owed' => (float) ($b['owed_on_close'] ?? $b['final_outstanding'] ?? 0), 'due' => (float) ($b['final_outstanding'] ?? 0),
                'dates' => $c->start_date?->format('j M') . ' – ' . $c->due_date?->format('j M'),
            ];
        }

        return $out;
    }
}
