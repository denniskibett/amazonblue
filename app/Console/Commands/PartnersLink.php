<?php

namespace App\Console\Commands;

use App\Models\Investment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Links money to its parties before repayments are split (Stage 4 Phase 3):
 *  - brokered loans record the broker's penalty share (brokers.penalty_broker for the broker's user, else
 *    lending.split.default_broker_penalty_rate);
 *  - a chain whose continuation was brokered but whose first loan was not takes the continuation's broker
 *    (the borrower is the same);
 *  - cash disbursements with no funding line are linked to the house line (General Lending Book).
 *
 *   --dry-run                 reports/partners-link-<date>.csv + SHA-256
 *   --apply --confirm=<sha>   same day, unchanged data
 */
class PartnersLink extends Command
{
    protected $signature = 'partners:link {--dry-run} {--apply} {--confirm=}';

    protected $description = 'Record broker penalty shares, carry brokers across merged chains, link unfunded cash disbursements to the house line';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry === (bool) $this->option('apply')) {
            $this->error('Choose one: --dry-run or --apply --confirm=<sha>.');

            return self::FAILURE;
        }
        $changes = $this->changes();
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['table', 'id', 'column', 'from', 'to', 'why']);
        foreach ($changes as $c) {
            fputcsv($fh, [$c['table'], $c['id'], $c['column'], $c['from'], $c['to'], $c['why']]);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        $sha = hash('sha256', $csv);
        $path = 'reports/partners-link-' . now()->format('Y-m-d') . '.csv';

        if ($dry) {
            Storage::disk('local')->put($path, $csv);
            $by = collect($changes)->groupBy(fn ($c) => $c['table'] . '.' . $c['column'])->map->count();
            $this->table(['change', 'rows'], $by->map(fn ($n, $k) => [$k, $n])->values()->all());
            $this->line('CSV: ' . Storage::disk('local')->path($path));
            $this->info('SHA-256: ' . $sha);

            return self::SUCCESS;
        }
        $signed = Storage::disk('local')->exists($path) ? hash('sha256', Storage::disk('local')->get($path)) : null;
        if (! $this->option('confirm') || $signed !== $this->option('confirm') || $sha !== $signed) {
            $this->error('Refused: --confirm must be the SHA-256 of today\'s unchanged dry-run CSV.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($changes) {
            foreach ($changes as $c) {
                DB::table($c['table'])->where('id', $c['id'])->update([$c['column'] => $c['to'], 'updated_at' => now()]);
            }
        });
        $this->info(count($changes) . ' change(s) applied.');

        return self::SUCCESS;
    }

    private function changes(): array
    {
        $out = [];
        $penalty = DB::table('partners as p')->join('brokers as b', 'b.user_id', '=', 'p.user_id')->pluck('b.penalty_broker', 'p.id');
        $default = (float) config('lending.split.default_broker_penalty_rate', 50);

        // a merged chain takes its continuation's broker
        $carry = DB::table('loans as c')->join('loans as r', 'r.id', '=', 'c.merged_into_loan_id')
            ->where('c.broker_status', 1)->whereNotNull('c.partner_id')->where('r.broker_status', 0)
            ->orderBy('r.id')->get(['r.id as root', 'c.id as cont', 'c.partner_id', 'c.broker_rate'])->unique('root');
        $carried = [];
        foreach ($carry as $c) {
            $carried[$c->root] = $c->partner_id;
            $why = 'merged continuation #' . $c->cont . ' was brokered; same borrower';
            $out[] = ['table' => 'loans', 'id' => $c->root, 'column' => 'broker_status', 'from' => 0, 'to' => 1, 'why' => $why];
            $out[] = ['table' => 'loans', 'id' => $c->root, 'column' => 'partner_id', 'from' => '', 'to' => $c->partner_id, 'why' => $why];
            $out[] = ['table' => 'loans', 'id' => $c->root, 'column' => 'broker_rate', 'from' => '', 'to' => $c->broker_rate, 'why' => $why];
        }

        $brokered = DB::table('loans')->whereNull('deleted_at')->where('broker_status', 1)->whereNotNull('partner_id')
            ->whereNull('broker_penalty_rate')->orderBy('id')->get(['id', 'partner_id']);
        foreach ($brokered as $l) {
            $out[] = ['table' => 'loans', 'id' => $l->id, 'column' => 'broker_penalty_rate', 'from' => '',
                'to' => $penalty[$l->partner_id] ?? $default,
                'why' => isset($penalty[$l->partner_id]) ? 'brokers.penalty_broker' : 'broker has no brokers row: default ' . $default];
        }
        foreach ($carried as $root => $partnerId) {
            $out[] = ['table' => 'loans', 'id' => $root, 'column' => 'broker_penalty_rate', 'from' => '',
                'to' => $penalty[$partnerId] ?? $default, 'why' => 'carried broker'];
        }

        $house = Investment::where('name', 'General Lending Book')->value('id');
        if ($house) {
            foreach (DB::table('disbursements')->whereNull('investment_id')->where('kind', 'cash')->whereNull('deleted_at')->orderBy('id')->pluck('id') as $id) {
                $out[] = ['table' => 'disbursements', 'id' => $id, 'column' => 'investment_id', 'from' => '', 'to' => $house,
                    'why' => 'cash with no funding line: AC\'s own money (house line)'];
            }
        }

        return $out;
    }
}
