<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\Repayment;
use App\Services\Loans\RepaymentSplit;
use App\Services\PartnerLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Restates partner and broker earnings on the Phase 3 split (rule 4).
 *
 * Every cash repayment gets its own split rows (SPLIT-R<id>-...). The one-off backfill of 7 Oct 2026 (earning
 * rows with no SPLIT/RESTATE reference) is reversed by one row per partner and type (RESTATE-LEGACY-...);
 * the legacy rows themselves stay. The house account (AC) gets its share of each repayment too, so all
 * parties together equal the interest and penalty collected.
 *
 *   --dry-run                 reports/allocations-restate-<date>.csv (per partner, then per repayment) + SHA
 *   --apply --confirm=<sha>   needs lending.split.enabled and engine v2; same day, unchanged data
 */
class AllocationsRestate extends Command
{
    protected $signature = 'allocations:restate {--dry-run} {--apply} {--confirm=}';

    protected $description = 'Restate partner/broker earnings from every repayment (dry-run CSV per partner, then --apply --confirm=<sha>)';

    public const EARNINGS = ['broker_fee_earned', 'penalty_share', 'profit_distribution', 'interest'];

    public function handle(RepaymentSplit $split): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry === (bool) $this->option('apply')) {
            $this->error('Choose one: --dry-run or --apply --confirm=<sha>.');

            return self::FAILURE;
        }
        if (! $dry && ! RepaymentSplit::enabled()) {
            $this->error('Switch LENDING_SPLIT_ENABLED=true first (engine v2), so repayments recorded from now on are split too.');

            return self::FAILURE;
        }

        $target = [];
        $collected = 0.0;
        $detail = [];
        foreach (Repayment::withTrashed()->orderBy('id')->lazy() as $r) {
            $collected += $split->collected($r);
            foreach ($split->plan($r) as [$partner, $type, $wallet, $amount, $why]) {
                $target["{$partner}|{$type}"] = ($target["{$partner}|{$type}"] ?? 0) + $amount;
                $detail[] = ['repayment', $r->id, $r->loan_id, substr((string) $r->repayment_date, 0, 10), $partner, $type, $amount, $why];
            }
        }
        $current = PartnerTransaction::whereIn('type', self::EARNINGS)
            ->selectRaw("partner_id, type,
                sum(case when reference like 'SPLIT-R%' then amount else 0 end) as split,
                sum(case when reference like 'RESTATE-LEGACY-%' then amount else 0 end) as reversed,
                sum(case when reference not like 'SPLIT-R%' and reference not like 'RESTATE-LEGACY-%' then amount else 0 end) as legacy")
            ->groupBy('partner_id', 'type')->get()->keyBy(fn ($r) => "{$r->partner_id}|{$r->type}");

        $summary = [];
        foreach (array_unique(array_merge(array_keys($target), $current->keys()->all())) as $key) {
            [$partner, $type] = explode('|', $key);
            $c = $current[$key] ?? null;
            $legacy = round((float) ($c->legacy ?? 0), 2);
            $reversed = round((float) ($c->reversed ?? 0), 2);
            $now = round($legacy + $reversed + (float) ($c->split ?? 0), 2);
            $new = round($target[$key] ?? 0, 2);
            $summary[] = ['partner', (int) $partner, Partner::whereKey($partner)->value('name'), $type, $now, $new, round($new - $now, 2),
                $legacy, round(-$legacy - $reversed, 2)];
        }
        usort($summary, fn ($a, $b) => [$a[1], $a[3]] <=> [$b[1], $b[3]]);

        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['row', 'partner_id', 'partner', 'type', 'earned_now', 'earned_restated', 'difference', 'legacy_backfill', 'legacy_reversal']);
        foreach ($summary as $s) {
            fputcsv($fh, $s);
        }
        fputcsv($fh, ['row', 'repayment_id', 'loan_id', 'date', 'partner_id', 'type', 'amount', 'why']);
        foreach ($detail as $d) {
            fputcsv($fh, $d);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        $sha = hash('sha256', $csv);
        $path = 'reports/allocations-restate-' . now()->format('Y-m-d') . '.csv';

        if ($dry) {
            Storage::disk('local')->put($path, $csv);
            $this->table(['partner', 'type', 'earned now', 'restated', 'difference'],
                array_map(fn ($s) => ["#{$s[1]} {$s[2]}", $s[3], number_format($s[4], 2), number_format($s[5], 2), number_format($s[6], 2)], $summary));
            $shared = round(array_sum($target), 2);
            $this->line(count($detail) . ' split row(s) from ' . count(array_unique(array_column($detail, 1))) . ' repayment(s).');
            $this->line(sprintf('Interest + penalty collected in cash: KES %s; shared between all parties: KES %s; difference %s.',
                number_format($collected, 2), number_format($shared, 2), number_format($shared - $collected, 2)));
            $this->line('CSV: ' . Storage::disk('local')->path($path));
            $this->info('SHA-256: ' . $sha);

            return self::SUCCESS;
        }

        $signed = Storage::disk('local')->exists($path) ? hash('sha256', Storage::disk('local')->get($path)) : null;
        if (! $this->option('confirm') || $signed !== $this->option('confirm') || $sha !== $signed) {
            $this->error('Refused: --confirm must be the SHA-256 of today\'s unchanged dry-run CSV.');

            return self::FAILURE;
        }

        $cycles = Repayment::withTrashed()->whereNotNull('loan_cycle_id')->distinct()->pluck('loan_cycle_id');
        foreach ($cycles as $cycleId) {
            DB::transaction(fn () => $split->syncCycle((int) $cycleId));
        }
        foreach ($summary as $s) {
            [$_, $partnerId, $name, $type, , , , , $reversal] = $s;
            if (abs($reversal) >= 0.005) {
                $wallet = PartnerTransaction::where('partner_id', $partnerId)->where('type', $type)->value('wallet_slug') ?? 'broker_fee';
                app(PartnerLedger::class)->post(Partner::findOrFail($partnerId), $wallet, $type, $reversal,
                    reference: 'RESTATE-LEGACY-' . $partnerId . '-' . strtoupper($type) . '-' . now()->format('Ymd'),
                    notes: 'Restatement: replaces the 7 Oct 2026 backfill with the share of each repayment', force: true);
            }
        }
        $this->info('Restated. Partner journal and wallets now carry one split per repayment.');

        return self::SUCCESS;
    }
}
