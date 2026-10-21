<?php

namespace App\Console\Commands;

use App\Services\LoanCalculator;
use App\Services\Loans\OwnerDecisions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** The owner's per-loan decisions (App\Services\Loans\OwnerDecisions): --dry-run CSV + SHA, then --apply --confirm. */
class LoansOwnerDecisions extends Command
{
    protected $signature = 'loans:owner-decisions {--dry-run} {--apply} {--confirm=}';

    protected $description = 'Apply the owner\'s per-loan forbearance / payment-plan / status decisions (dry-run + SHA)';

    public function handle(OwnerDecisions $svc): int
    {
        if (! LoanCalculator::v2() || (bool) $this->option('dry-run') === (bool) $this->option('apply')) {
            $this->error('Needs engine v2, and one of --dry-run or --apply --confirm=<sha>.');

            return self::FAILURE;
        }
        $rows = $svc->preview();
        $cols = ['loan_id', 'who', 'action', 'status_now', 'status_after', 'cycle', 'due_now', 'pay_by', 'owed_now', 'penalty_now', 'owed_after', 'note'];
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, $cols);
        foreach ($rows as $r) {
            fputcsv($fh, array_map(fn ($c) => $r[$c] ?? '', $cols));
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        $sha = hash('sha256', $csv);
        $path = 'reports/owner-decisions-' . now()->format('Y-m-d') . '.csv';
        $this->table(['loan', 'who', 'action', 'status', 'pay by', 'owed now', 'owed after'], array_map(fn ($r) => [$r['loan_id'], $r['who'], $r['action'],
            ($r['status_now'] ?? '') . ' -> ' . ($r['status_after'] ?? ''), $r['pay_by'] ?? '', number_format($r['owed_now'] ?? 0, 2), number_format($r['owed_after'] ?? 0, 2)], $rows));

        if ($this->option('dry-run')) {
            Storage::disk('local')->put($path, $csv);
            $this->info('SHA-256: ' . $sha);

            return self::SUCCESS;
        }
        $signed = Storage::disk('local')->exists($path) ? hash('sha256', Storage::disk('local')->get($path)) : null;
        if ($signed !== $this->option('confirm') || $sha !== $signed) {
            $this->error('Refused: --confirm must be the SHA-256 of today\'s unchanged dry-run CSV.');

            return self::FAILURE;
        }
        foreach ($rows as $r) {
            if ($r['ok']) {
                $svc->apply($r['loan_id']);
                $this->line("  loan #{$r['loan_id']}: {$r['action']} -> " . \App\Models\Loan::find($r['loan_id'])->status);
            }
        }

        return self::SUCCESS;
    }
}
