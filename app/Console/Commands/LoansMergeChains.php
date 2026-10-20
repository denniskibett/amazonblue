<?php

namespace App\Console\Commands;

use App\Services\Ledger;
use App\Services\LoanCalculator;
use App\Services\Loans\LegacyChains;
use App\Services\Loans\LoanStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Merges legacy ROLL OVER chains into one loan each (owner, 21 Oct 2026; see Loans\LegacyChains).
 *
 *   --dry-run                 writes reports/merge-chains-<date>.csv (one row per link, then the links that
 *                             could not be made) and prints its SHA-256. Changes nothing.
 *   --apply --confirm=<sha>   only if today's CSV still has that SHA. Merges every chain, then (engine v2)
 *                             re-reads each root's status from its balance and, with the ledger on, re-posts.
 */
class LoansMergeChains extends Command
{
    protected $signature = 'loans:merge-chains
        {--dry-run : Write the chains CSV and its SHA-256; change nothing}
        {--apply : Merge (needs --confirm)}
        {--confirm= : SHA-256 of today\'s signed dry-run CSV}';

    protected $description = 'Merge legacy ROLL OVER chains into one loan with cycles: dry-run CSV, then --apply --confirm=<sha>';

    public function handle(LegacyChains $chains): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry === (bool) $this->option('apply')) {
            $this->error('Choose one: --dry-run or --apply --confirm=<sha>.');

            return self::FAILURE;
        }

        $found = $chains->detect();
        $csv = $this->csv($found);
        $sha = hash('sha256', $csv);
        $path = 'reports/merge-chains-' . now()->format('Y-m-d') . '.csv';

        if ($dry) {
            Storage::disk('local')->put($path, $csv);
            $links = array_sum(array_map(fn ($c) => count($c['links']), $found['chains']));
            $this->table(['', 'count'], [
                ['Chains (one loan each after the merge)', count($found['chains'])],
                ['Continuation loans merged into a chain', $links],
                ['Borrowers', count(array_unique(array_column($found['chains'], 'user')))],
                ['Longest chain (loans)', $found['chains'] ? max(array_map(fn ($c) => count($c['loans']), $found['chains'])) : 0],
                ['Not linked (listed in the CSV, left as they are)', count($found['unmatched'])],
            ]);
            $this->line('CSV: ' . Storage::disk('local')->path($path));
            $this->info('SHA-256: ' . $sha);
            $this->line('Apply after sign-off, the same day: php artisan loans:merge-chains --apply --confirm=' . $sha);

            return self::SUCCESS;
        }

        $signed = Storage::disk('local')->exists($path) ? hash('sha256', Storage::disk('local')->get($path)) : null;
        if (! $this->option('confirm') || $signed !== $this->option('confirm') || $sha !== $signed) {
            $this->error('Refused: --confirm must be the SHA-256 of today\'s dry-run CSV, and the chains must not have changed since. Run --dry-run again.');

            return self::FAILURE;
        }

        foreach ($found['chains'] as $chain) {
            DB::transaction(fn () => $chains->merge($chain));
        }
        if (LoanCalculator::v2()) {
            foreach ($found['chains'] as $chain) {
                $root = \App\Models\Loan::withTrashed()->find($chain['root']);
                if ($root && $root->status !== \App\Models\Loan::STATUS_REPAID) {
                    LoanStatus::apply($root);
                }
            }
        }
        if (Ledger::enabled()) {
            $this->call('ledger:backfill');
        }
        $this->info('Merged ' . count($found['chains']) . ' chain(s). Continuation loans are hidden (soft-deleted) and point at their chain\'s first loan.');

        return self::SUCCESS;
    }

    private function csv(array $found): string
    {
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['chain_root', 'user_id', 'chain', 'from_loan', 'to_loan', 'date', 'gap_days', 'amount_closed', 'amount_opened', 'note']);
        foreach ($found['chains'] as $c) {
            foreach ($c['links'] as $l) {
                fputcsv($fh, [$c['root'], $c['user'], implode('>', $c['loans']), $l['from'], $l['to'], $l['date'], $l['gap_days'],
                    $l['amount_closed'], $l['amount_opened'], abs($l['amount_closed'] - $l['amount_opened']) >= 0.01 ? 'amounts differ' : '']);
            }
        }
        foreach ($found['unmatched'] as $u) {
            fputcsv($fh, ['', $u['user'], '', '', $u['loan'], $u['date'], '', '', $u['amount'], 'NOT LINKED: ' . $u['why']]);
        }
        rewind($fh);
        $out = stream_get_contents($fh);
        fclose($fh);

        return $out;
    }
}
