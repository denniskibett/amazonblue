<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Services\LoanCalculator;
use App\Services\Loans\GraceFix;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Late-but-full payers get grace days instead of penalty and a write-off; pre-Phase-2 forbearance is restored.
 * See App\Services\Loans\GraceFix. The same proposals are on /staff/operations-review?section=grace.
 *
 *   --dry-run                         reports/grace-fix-<date>.csv + SHA-256
 *   --apply --confirm=<sha> [--only=] applies every grace and forbearance row (or only the listed loan ids)
 */
class LoansGraceFix extends Command
{
    protected $signature = 'loans:grace-fix
        {--dry-run : Write the CSV and its SHA-256; change nothing}
        {--apply : Apply the grace and forbearance rows (needs --confirm)}
        {--confirm= : SHA-256 of today\'s dry-run CSV}
        {--only= : Comma-separated loan ids to apply (default: all eligible rows)}';

    protected $description = 'Grace days for loans paid in full but late (removes their write-off); restore pre-Phase-2 forbearance';

    private const COLUMNS = ['action', 'loan_id', 'borrower', 'status_now', 'status_after', 'cycle_number', 'due', 'contract', 'paid',
        'paid_in_full_on', 'days_late', 'grace_now', 'grace_after', 'penalty_now', 'penalty_after', 'owed_now', 'owed_after', 'overpaid_after', 'waived_until_after',
        'write_off_id', 'write_off', 'reason'];

    public function handle(GraceFix $fix): int
    {
        if (! LoanCalculator::v2()) {
            $this->error('Needs lending.engine = v2 (set LENDING_ENGINE=v2, as on live).');

            return self::FAILURE;
        }
        $dry = (bool) $this->option('dry-run');
        if ($dry === (bool) $this->option('apply')) {
            $this->error('Choose one: --dry-run or --apply --confirm=<sha>.');

            return self::FAILURE;
        }

        $rows = $fix->proposals();
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, self::COLUMNS);
        foreach ($rows as $r) {
            fputcsv($fh, array_map(fn ($c) => $r[$c] ?? '', self::COLUMNS));
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        $sha = hash('sha256', $csv);
        $path = 'reports/grace-fix-' . now()->format('Y-m-d') . '.csv';

        $by = $rows->groupBy('action');
        foreach ([GraceFix::ACTION_GRACE, GraceFix::ACTION_FORBEARANCE, GraceFix::ACTION_SHORT] as $a) {
            $g = $by->get($a, collect());
            $this->line(sprintf('%-12s %3d loan(s); penalty removed KES %s; write-offs removed KES %s', $a, $g->count(),
                number_format($a === GraceFix::ACTION_SHORT ? 0 : $g->sum(fn ($r) => $r['penalty_now'] - ($r['penalty_after'] ?? 0)), 2),
                number_format($a === GraceFix::ACTION_GRACE ? $g->sum('write_off') : 0, 2)));
        }

        if ($dry) {
            Storage::disk('local')->put($path, $csv);
            $this->line('CSV: ' . Storage::disk('local')->path($path));
            $this->info('SHA-256: ' . $sha);

            return self::SUCCESS;
        }

        $signed = Storage::disk('local')->exists($path) ? hash('sha256', Storage::disk('local')->get($path)) : null;
        if (! $this->option('confirm') || $signed !== $this->option('confirm') || $sha !== $signed) {
            $this->error('Refused: --confirm must be the SHA-256 of today\'s dry-run CSV, and nothing may have changed since. Run --dry-run again.');

            return self::FAILURE;
        }

        $only = $this->option('only') ? array_map('intval', explode(',', $this->option('only'))) : null;
        $done = 0;
        $failed = [];
        foreach ($rows as $r) {
            if (! in_array($r['action'], [GraceFix::ACTION_GRACE, GraceFix::ACTION_FORBEARANCE], true) || ($only && ! in_array($r['loan_id'], $only, true))) {
                continue;
            }
            try {
                $fix->apply(Loan::findOrFail($r['loan_id']), null, 'loans:grace-fix --apply ' . substr($sha, 0, 12));
                $this->line("  loan #{$r['loan_id']}: {$r['action']} -> " . Loan::find($r['loan_id'])->status);
                $done++;
            } catch (\Throwable $e) {
                $failed[] = $r['loan_id'];
                $this->error("  loan #{$r['loan_id']}: not applied, nothing changed on it: " . strtok($e->getMessage(), "\n"));
            }
        }
        $this->info("{$done} loan(s) applied." . ($failed ? ' Failed: #' . implode(', #', $failed) . '.' : ''));

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
