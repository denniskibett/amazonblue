<?php

namespace App\Console\Commands;

use App\Services\LoanCalculator;
use App\Services\Loans\CreditCleanup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Feature 2c: CREDIT DISCOUNT rows (App\Services\Loans\CreditCleanup). --dry-run CSV + SHA, then --apply --confirm. */
class LoansCreditCleanup extends Command
{
    protected $signature = 'loans:credit-cleanup {--dry-run} {--apply} {--confirm=} {--only= : Comma-separated loan ids}';

    protected $description = 'CREDIT DISCOUNT: pairs become ROLL OVER rows; lone rows go, with grace days and a fitted rate (dry-run + SHA)';

    private const COLS = ['action', 'loan_id', 'borrower', 'to_loan', 'cycle_number', 'carried', 'amount', 'date', 'due', 'days_late', 'grace_before', 'grace_after',
        'rate_before', 'rate_after', 'owed_before', 'owed_without_credit', 'target', 'owed_after', 'write_off', 'write_off_date', 'repayment_ids', 'disbursement_id', 'reason'];

    public function handle(CreditCleanup $svc): int
    {
        if (! LoanCalculator::v2() || (bool) $this->option('dry-run') === (bool) $this->option('apply')) {
            $this->error('Needs engine v2, and one of --dry-run or --apply --confirm=<sha>.');

            return self::FAILURE;
        }
        $rows = $svc->proposals();
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, self::COLS);
        foreach ($rows as $r) {
            fputcsv($fh, array_map(fn ($c) => is_array($r[$c] ?? null) ? implode(' ', $r[$c]) : (is_bool($r[$c] ?? null) ? (int) $r[$c] : ($r[$c] ?? '')), self::COLS));
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        $sha = hash('sha256', $csv);
        $path = 'reports/credit-cleanup-' . now()->format('Y-m-d') . '.csv';

        $f = $rows->where('action', 'forgive');
        $this->line(sprintf('rollover rows %d (KES %s); forgiveness rows removed on %d cycle(s) (KES %s): %d close by grace, %d need a lower rate, %d short of principal (KES %s written off)',
            $rows->where('action', 'rollover')->count(), number_format($rows->where('action', 'rollover')->sum('amount'), 2),
            $f->count(), number_format($f->sum('amount'), 2),
            $f->filter(fn ($r) => $r['rate_after'] == $r['rate_before'] && $r['write_off'] == 0)->count(),
            $f->filter(fn ($r) => $r['rate_after'] != $r['rate_before'] && $r['write_off'] == 0)->count(),
            $f->filter(fn ($r) => $r['write_off'] > 0)->count(), number_format($f->sum('write_off'), 2)));

        if ($this->option('dry-run')) {
            Storage::disk('local')->put($path, $csv);
            $this->line('CSV: ' . Storage::disk('local')->path($path));
            $this->info('SHA-256: ' . $sha);

            return self::SUCCESS;
        }
        $signed = Storage::disk('local')->exists($path) ? hash('sha256', Storage::disk('local')->get($path)) : null;
        if ($signed !== $this->option('confirm') || $sha !== $signed) {
            $this->error('Refused: --confirm must be the SHA-256 of today\'s unchanged dry-run CSV.');

            return self::FAILURE;
        }
        $only = $this->option('only') ? array_map('intval', explode(',', $this->option('only'))) : null;
        $done = 0;
        $failed = [];
        foreach ($rows as $r) {
            if ($only && ! in_array($r['loan_id'], $only, true)) {
                continue;
            }
            try {
                $svc->apply($r, 'loans:credit-cleanup ' . substr($sha, 0, 12));
                $done++;
            } catch (\Throwable $e) {
                $failed[] = $r['loan_id'];
                $this->error("  loan #{$r['loan_id']}: not applied: " . strtok($e->getMessage(), "\n"));
            }
        }
        $this->info("{$done} row(s) applied." . ($failed ? ' Failed: #' . implode(', #', $failed) : '') . ' Next: loans:rollover-cleanup --dry-run picks up the new ROLL OVER rows.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
