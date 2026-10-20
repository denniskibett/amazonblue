<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Repayment;
use App\Services\LoanCalculator;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\LoanStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Loans the owner kept "repaid" although they still owe under the v2 rules (decision of 21 Oct 2026) get the
 * balance written off: one bad_debt book entry per loan on its latest cycle, dated today, for exactly what is
 * owed today. Nothing else changes; the ledger posts it to bad debts and the loan reads repaid with a closed
 * cycle, so the books and the status agree.
 *
 *   --dry-run                 reports/write-off-repaid-<date>.csv + SHA-256
 *   --apply --confirm=<sha>   only with engine v2 and the SHA of today's unchanged CSV
 */
class LoansWriteOffRepaid extends Command
{
    protected $signature = 'loans:write-off-repaid
        {--dry-run : Write the CSV and its SHA-256; change nothing}
        {--apply : Write the balances off (needs --confirm)}
        {--confirm= : SHA-256 of today\'s dry-run CSV}';

    protected $description = 'Write off what loans kept as repaid still owe under the v2 rules (dry-run CSV, then --apply --confirm=<sha>)';

    public const REFERENCE = 'WRITE OFF: kept repaid at restatement';

    public function handle(): int
    {
        if (! LoanCalculator::v2()) {
            $this->error('Needs lending.engine = v2.');

            return self::FAILURE;
        }
        $dry = (bool) $this->option('dry-run');
        if ($dry === (bool) $this->option('apply')) {
            $this->error('Choose one: --dry-run or --apply --confirm=<sha>.');

            return self::FAILURE;
        }

        $rows = [];
        foreach (Loan::where('status', Loan::STATUS_REPAID)->orderBy('id')->get() as $loan) {
            $cycle = LoanCycle::where('loan_id', $loan->id)->orderByDesc('cycle_number')->first();
            $b = $cycle ? CycleBalance::at($cycle) : null;
            if ($b && $b['final_outstanding'] > 0.004) {
                $rows[] = ['loan_id' => $loan->id, 'user_id' => $loan->user_id, 'cycle' => $cycle->cycle_number, 'cycle_id' => $cycle->id,
                    'principal' => $b['unpaid_principal'], 'interest' => $b['unpaid_interest'], 'penalty' => $b['unpaid_penalty'],
                    'write_off' => $b['final_outstanding']];
            }
        }

        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['loan_id', 'user_id', 'cycle', 'unpaid_principal', 'unpaid_interest', 'unpaid_penalty', 'write_off']);
        foreach ($rows as $r) {
            fputcsv($fh, [$r['loan_id'], $r['user_id'], $r['cycle'], $r['principal'], $r['interest'], $r['penalty'], $r['write_off']]);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        $sha = hash('sha256', $csv);
        $path = 'reports/write-off-repaid-' . now()->format('Y-m-d') . '.csv';
        $total = round(array_sum(array_column($rows, 'write_off')), 2);

        if ($dry) {
            Storage::disk('local')->put($path, $csv);
            $this->line(count($rows) . ' loan(s); KES ' . number_format($total, 2) . ' to write off ('
                . 'principal ' . number_format(array_sum(array_column($rows, 'principal')), 2)
                . ', interest ' . number_format(array_sum(array_column($rows, 'interest')), 2)
                . ', penalty ' . number_format(array_sum(array_column($rows, 'penalty')), 2) . ').');
            $this->line('CSV: ' . Storage::disk('local')->path($path));
            $this->info('SHA-256: ' . $sha);

            return self::SUCCESS;
        }

        $signed = Storage::disk('local')->exists($path) ? hash('sha256', Storage::disk('local')->get($path)) : null;
        if (! $this->option('confirm') || $signed !== $this->option('confirm') || $sha !== $signed) {
            $this->error('Refused: --confirm must be the SHA-256 of today\'s dry-run CSV, and nothing may have changed since. Run --dry-run again.');

            return self::FAILURE;
        }

        foreach ($rows as $r) {
            DB::transaction(function () use ($r) {
                Repayment::create([
                    'loan_id' => $r['loan_id'], 'loan_cycle_id' => $r['cycle_id'], 'amount' => $r['write_off'],
                    'repayment_date' => now()->toDateString(), 'transaction' => self::REFERENCE, 'kind' => 'bad_debt', 'mode' => 'book',
                    'notes' => 'Owner decision 21 Oct 2026: kept as repaid; the balance under the v2 rules is written off.',
                ]);
                LoanStatus::apply(Loan::findOrFail($r['loan_id']));
            });
        }
        $this->info(count($rows) . ' loan(s) written off, KES ' . number_format($total, 2) . '.');

        return self::SUCCESS;
    }
}
