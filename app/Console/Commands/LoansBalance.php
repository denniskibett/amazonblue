<?php

namespace App\Console\Commands;

use App\Services\LoanCalculator;
use App\Services\Loans\LoanBalancer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Owner, 22 Oct 2026 (App\Services\Loans\LoanBalancer): balance every loan; closed loans end at 0. */
class LoansBalance extends Command
{
    protected $signature = 'loans:balance {--dry-run} {--apply} {--confirm=} {--only= : Comma-separated loan ids}';

    protected $description = 'Payments and money sent to their cycles, cycles chained, no penalty days, closed loans end at 0 (dry-run + SHA)';

    private const COLS = ['loan_id', 'borrower', 'status', 'action', 'reason', 'cycles', 'penalty_before', 'penalty_after', 'owed_before', 'owed_after',
        'ledger_before', 'ledger_after', 'difference_before', 'difference_after', 'status_after', 'cash_sent', 'cycle1_lent', 'next', 'steps', 'changes', 'flags', 'cycles_before', 'cycles_after'];

    public function handle(LoanBalancer $svc): int
    {
        if (! LoanCalculator::v2() || (bool) $this->option('dry-run') === (bool) $this->option('apply')) {
            $this->error('Needs engine v2, and one of --dry-run or --apply --confirm=<sha>.');

            return self::FAILURE;
        }
        $only = $this->option('only') ? array_map('intval', explode(',', $this->option('only'))) : null;
        $path = 'reports/balance-' . now()->format('Y-m-d') . ($only ? '-only' : '') . '.csv';

        if ($this->option('apply')) {
            $signed = Storage::disk('local')->exists($path) ? hash('sha256', Storage::disk('local')->get($path)) : null;
            if (! $signed || $signed !== $this->option('confirm')) {
                $this->error('Refused: --confirm must be the SHA-256 of today\'s dry-run CSV (' . $path . ').');

                return self::FAILURE;
            }
        }

        $rows = [];
        foreach ($svc->candidates($only) as $id) {
            try {
                $rows[] = $svc->preview($id);
            } catch (\Throwable $e) {
                $rows[] = ['loan_id' => $id, 'action' => 'error', 'reason' => strtok($e->getMessage(), "\n")];
            }
        }
        $csv = $this->csv($rows);
        $sha = hash('sha256', $csv);
        $col = collect($rows);
        $this->line(sprintf('%d loan(s): %d to balance, %d re-booked only, %d skipped, %d errors. Penalty removed KES %s. Balanced after: %d. Still off: %d.',
            $col->count(), $col->where('action', 'balance')->count(), $col->where('action', 'rebook')->count(), $col->where('action', 'skip')->count(), $col->where('action', 'error')->count(),
            number_format($col->sum(fn ($r) => ($r['penalty_before'] ?? 0) - ($r['penalty_after'] ?? 0)), 2),
            $col->filter(fn ($r) => ($r['next'] ?? '') === 'balanced')->count(),
            $col->filter(fn ($r) => isset($r['next']) && $r['next'] !== 'balanced')->count()));

        if ($this->option('dry-run')) {
            Storage::disk('local')->put($path, $csv);
            $this->line('CSV: ' . Storage::disk('local')->path($path));
            $this->info('SHA-256: ' . $sha);

            return self::SUCCESS;
        }
        if ($sha !== $this->option('confirm')) {
            $this->error('Refused: the data changed since the dry run (SHA now ' . $sha . '). Run --dry-run again.');

            return self::FAILURE;
        }
        $done = 0;
        $failed = [];
        foreach ($col->whereIn('action', ['balance', 'rebook']) as $r) {
            try {
                $svc->apply($r['loan_id'], 'loans:balance ' . substr($sha, 0, 12));
                $done++;
            } catch (\Throwable $e) {
                $failed[] = $r['loan_id'];
                $this->error("  loan #{$r['loan_id']}: not applied: " . strtok($e->getMessage(), "\n"));
            }
        }
        $this->info("{$done} loan(s) balanced." . ($failed ? ' Failed: #' . implode(', #', $failed) : '') . ' Check: php artisan wallets:check');

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function csv(array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, self::COLS);
        foreach ($rows as $r) {
            fputcsv($fh, array_map(fn ($c) => $r[$c] ?? '', self::COLS));
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }
}
