<?php

namespace App\Console\Commands;

use App\Services\LoanCalculator;
use App\Services\Loans\RolloverCleanup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Feature 2a: rollovers live in loan_cycles only (App\Services\Loans\RolloverCleanup).
 *
 *   --dry-run                                  reports/rollover-cleanup-<date>.csv + SHA-256
 *   --apply --confirm=<sha> [--only=root ids]  never-sent charges off, chains merged, stray ROLL OVER rows removed,
 *                                              consolidation transfers posted in the ledger
 */
class LoansRolloverCleanup extends Command
{
    protected $signature = 'loans:rollover-cleanup
        {--dry-run : Write the CSV and its SHA-256; change nothing}
        {--apply : Apply (needs --confirm)}
        {--confirm= : SHA-256 of today\'s dry-run CSV}
        {--only= : Comma-separated chain root loan ids to merge (default: all); stray rows and never-sent are always done}';

    protected $description = 'Merge the remaining ROLL OVER chains, remove ROLL OVER rows the cycles hold, clear charges on never-sent loans';

    public function handle(RolloverCleanup $svc): int
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

        $found = $svc->chains();
        $stray = $svc->strayRows();
        $never = $svc->neverSent();
        $transfers = $svc->transfers()->where('posted', false)->values();

        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['section', 'root', 'from', 'to', 'borrower', 'from_cycle', 'from_due', 'to_start', 'days_late', 'owed_at_rollover',
            'continuation_principal', 'carry_difference', 'roll_over_repayment', 'roll_over_disbursement', 'note']);
        foreach ($found['chains'] as $ch) {
            foreach ($ch['links'] as $l) {
                fputcsv($fh, ['chain', $ch['root'], $l['from'], $l['to'], $ch['borrower'], $l['from_cycle'], $l['from_due'], $l['to_start'],
                    $l['days_late'], $l['owed_at_rollover'], $l['continuation_principal'], $l['carry_difference'],
                    $l['repayment_id'] . ':' . $l['amount_closed'], $l['disbursement_id'] . ':' . $l['amount_opened'], 'matched on ' . $l['matched_on'] . ($l['overlap'] ? '; cycles start the same day' : '')]);
            }
        }
        foreach ($found['unmatched'] as $u) {
            fputcsv($fh, ['unmatched', '', '', $u['loan'], '', '', '', $u['date'], '', '', '', '', '', '', $u['why']]);
        }
        foreach ($stray as $r) {
            fputcsv($fh, ['stray_' . $r['table'], '', '', $r['loan_id'], '', '', '', $r['date'], '', '', '', '', $r['id'] . ':' . $r['amount'], '', $r['why']]);
        }
        foreach ($transfers as $t) {
            fputcsv($fh, ['transfer', '', $t['from'], $t['to'], $t['borrower'], '', $t['closed'], $t['opened'], '', $t['amount'], '', '', $t['repayment_id'], '', $t['how'] . '; into cycle ' . $t['into_cycle'] . ' (' . $t['target'] . ')']);
        }
        foreach ($never as $n) {
            fputcsv($fh, ['never_sent', '', '', $n['loan_id'], $n['borrower'], '', '', '', '', '', '', '', '', '', $n['status']]);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        $sha = hash('sha256', $csv);
        $path = 'reports/rollover-cleanup-' . now()->format('Y-m-d') . '.csv';

        $this->line(sprintf('chains %d (%d loans folded in; carry difference KES %s); unmatched %d; stray ROLL OVER rows %d (%d repayments, %d disbursements); never-sent loans %d; consolidation transfers %d (KES %s)',
            $found['chains']->count(), $found['links']->count(), number_format($found['chains']->sum('carry_difference'), 2), $found['unmatched']->count(),
            $stray->count(), $stray->where('table', 'repayments')->count(), $stray->where('table', 'disbursements')->count(), $never->count(), $transfers->count(), number_format($transfers->sum('amount'), 2)));

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

        $this->line('never-sent: ' . $svc->clearNeverSent() . ' ledger posting(s) reversed');
        $only = $this->option('only') ? array_map('intval', explode(',', $this->option('only'))) : null;
        $failed = [];
        foreach ($found['chains'] as $ch) {
            if ($only && ! in_array($ch['root'], $only, true)) {
                continue;
            }
            try {
                $done = $svc->applyChain($ch['root'], 'loans:rollover-cleanup ' . substr($sha, 0, 12), $ch);
                $this->line("  chain #{$ch['root']} <- #" . implode(', #', array_slice($ch['loans'], 1)) . ": merged, {$done['rows_removed']} ROLL OVER row(s) removed");
            } catch (\Throwable $e) {
                $failed[] = $ch['root'];
                $this->error("  chain #{$ch['root']}: not merged: " . strtok($e->getMessage(), "\n"));
            }
        }
        $this->line('stray ROLL OVER rows removed: ' . $svc->removeStray(null, 'loans:rollover-cleanup ' . substr($sha, 0, 12)));
        $this->line('consolidation transfers posted: ' . $svc->postTransfers($transfers));
        $this->info('Done.' . ($failed ? ' Failed chains: #' . implode(', #', $failed) : ''));

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
