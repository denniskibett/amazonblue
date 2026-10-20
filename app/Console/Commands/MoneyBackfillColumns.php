<?php

namespace App\Console\Commands;

use App\Services\CustomerWallets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Fills repayments.kind / created_by and disbursements.kind / created_by (Stage 4 Phase 1).
 *
 *   kind:       from the reference text, by today's rule (CustomerWallets::kind). A row that already has a
 *               kind keeps it; if it differs from the text rule it is listed as "differs" (should be none).
 *   created_by: the user on the row's audit_logs 'create' entry, where one exists (the audit trail started
 *               in Stage 1, so older rows stay empty).
 *
 * Writes with plain UPDATEs (no model events): filling these columns changes no amount, wallet or status.
 * Deleted rows are included so their history reads the same way.
 */
class MoneyBackfillColumns extends Command
{
    protected $signature = 'money:backfill-columns
        {--dry-run : List what would change and write the CSV; change nothing}';

    protected $description = 'Fill kind and created_by on repayments and disbursements (dry-run first; writes a CSV either way)';

    public const TABLES = ['repayments', 'disbursements'];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $rows = [];
        $counts = [];

        foreach (self::TABLES as $table) {
            $creators = DB::table('audit_logs')->where('table_name', $table)->where('action', 'create')
                ->whereNotNull('user_id')->orderBy('id')->pluck('user_id', 'record_id');
            $c = ['kind' => 0, 'created_by' => 0, 'differs' => 0];

            foreach (DB::table($table)->orderBy('id')->get(['id', 'transaction', 'kind', 'created_by']) as $r) {
                $rule = CustomerWallets::kind($r->transaction);
                $stored = in_array($r->kind, CustomerWallets::KINDS, true) ? $r->kind : null;
                $creator = $r->created_by ?? ($creators[$r->id] ?? null);
                $set = [];
                if ($stored === null) {
                    $set['kind'] = $rule;
                    $c['kind']++;
                } elseif ($stored !== $rule) {
                    $c['differs']++;
                }
                if ($r->created_by === null && $creator !== null) {
                    $set['created_by'] = (int) $creator;
                    $c['created_by']++;
                }
                if ($set || ($stored !== null && $stored !== $rule)) {
                    $rows[] = [$table, $r->id, $r->transaction, $r->kind, $set['kind'] ?? $stored, $r->created_by, $set['created_by'] ?? $r->created_by,
                        $stored !== null && $stored !== $rule ? 'differs (kept: stored ' . $stored . ', text says ' . $rule . ')' : 'fill'];
                }
                if ($set && ! $dry) {
                    DB::table($table)->where('id', $r->id)->update($set);
                }
            }
            $counts[$table] = $c;
        }

        $path = 'reports/money-columns-' . now()->format('Ymd-His') . ($dry ? '-dry-run' : '') . '.csv';
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['table', 'id', 'reference', 'kind_before', 'kind_after', 'created_by_before', 'created_by_after', 'action']);
        foreach ($rows as $row) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        Storage::disk('local')->put($path, stream_get_contents($fh));
        fclose($fh);

        $this->table(['table', 'kind filled', 'created_by filled', 'kind differs from text'],
            collect($counts)->map(fn ($c, $t) => [$t, $c['kind'], $c['created_by'], $c['differs']])->values()->all());
        $this->line(($dry ? 'Dry run, nothing changed. ' : 'Done. ') . 'CSV: ' . Storage::disk('local')->path($path));

        return self::SUCCESS;
    }
}
