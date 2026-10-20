<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\Ledger;
use App\Services\LoanCalculator;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\CycleOne;
use App\Services\Loans\LoanStatus;
use App\Services\Loans\LoanSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Restates every loan on the v2 engine (Stage 4 Phase 2, decision 2).
 *
 *   --dry-run   compares the old engine (v1) with v2 for every loan with cycles and writes
 *               storage/app/private/reports/restate-<date>.csv plus its SHA-256. Changes nothing.
 *   --apply --confirm=<sha>
 *               only with lending.engine = v2, and only if today's CSV still has that SHA (the data has not
 *               moved since it was signed). Then, per loan: records the product terms and principal on cycles
 *               that have none, writes each cycle's balance cache, updates amount/original_amount from the
 *               cycles, and sets the status from the balance. With the ledger on, posts each cycle's charges
 *               (the adjusting entries).
 *
 * Repayments and disbursements are never edited. A loan that is "repaid" but owes on v2 keeps its status
 * (listed as REPAID_BUT_OWES) unless named in --reopen=ids: the owner decides each one.
 */
class LoansRestate extends Command
{
    protected $signature = 'loans:restate
        {--dry-run : Write the comparison CSV and its SHA-256; change nothing}
        {--apply : Apply v2 (needs --confirm)}
        {--confirm= : SHA-256 of today\'s signed dry-run CSV}
        {--reopen= : Comma-separated loan ids listed REPAID_BUT_OWES that should go back to owing}';

    protected $description = 'Restate loans on the v2 engine: dry-run CSV for sign-off, then --apply --confirm=<sha>';

    private const HEADER = ['loan_id', 'user_id', 'status_old', 'status_new', 'amount_old', 'amount_new', 'original_old', 'original_new',
        'owed_old', 'owed_new', 'difference', 'penalty_new', 'penalty_without_cap', 'cap_removes', 'carry_differences',
        'cycles', 'legacy_chain', 'reasons'];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry === (bool) $this->option('apply')) {
            $this->error('Choose one: --dry-run or --apply --confirm=<sha>.');

            return self::FAILURE;
        }
        if (! $dry && ! LoanCalculator::v2()) {
            $this->error('Switch LENDING_ENGINE=v2 first (after the owner signs the dry-run CSV), so screens and data agree.');

            return self::FAILURE;
        }

        $rows = $this->compare();
        $csv = $this->toCsv($rows);
        $sha = hash('sha256', $csv);
        $path = 'reports/restate-' . now()->format('Y-m-d') . '.csv';

        if ($dry) {
            Storage::disk('local')->put($path, $csv);
            $this->summary($rows);
            $this->line('CSV: ' . Storage::disk('local')->path($path));
            $this->info('SHA-256: ' . $sha);
            $this->line('Apply after sign-off, the same day: php artisan loans:restate --apply --confirm=' . $sha);

            return self::SUCCESS;
        }

        $signed = Storage::disk('local')->exists($path) ? hash('sha256', Storage::disk('local')->get($path)) : null;
        if (! $this->option('confirm') || $signed !== $this->option('confirm') || $sha !== $signed) {
            $this->error('Refused: --confirm must be the SHA-256 of today\'s dry-run CSV, and the data must not have changed since. Run --dry-run again and get it signed.');

            return self::FAILURE;
        }

        $reopen = collect(explode(',', (string) $this->option('reopen')))->map(fn ($v) => (int) trim($v))->filter()->all();
        $applied = 0;
        foreach ($rows as $r) {
            DB::transaction(function () use ($r, $reopen) {
                $loan = Loan::withTrashed()->with('loanType')->findOrFail($r['loan_id']);
                foreach (LoanCycle::where('loan_id', $loan->id)->orderBy('cycle_number')->get() as $cycle) {
                    $fill = ['principal' => $cycle->principal ?? $cycle->lentPrincipal()];
                    if ($cycle->penalty_rate === null && $loan->loanType) {
                        $fill += CycleOne::terms($loan);
                    }
                    $cycle->fill($fill + LoanStatus::cache(CycleBalance::at($cycle)));
                    if ($cycle->isDirty()) {
                        $cycle->save();
                    }
                }
                LoanSync::fromCycles($loan);
                if (in_array('REPAID_BUT_OWES', $r['reasons'], true) && ! in_array($loan->id, $reopen, true)) {
                    return; // the owner decides each one
                }
                LoanStatus::apply($loan->refresh());
            });
            $applied++;
        }
        if (Ledger::enabled()) {
            $this->call('ledger:backfill');
        }
        $this->info("Applied to {$applied} loan(s). Repayments and disbursements were not changed.");

        return self::SUCCESS;
    }

    /** One comparison row per loan with cycles: v1 vs v2. */
    private function compare(): array
    {
        $engine = config('lending.engine');
        $calc = app(LoanCalculator::class);
        $rows = [];

        foreach (Loan::withTrashed()->with('loanType')->whereHas('cycles')->orderBy('id')->get() as $loan) {
            $cycles = LoanCycle::where('loan_id', $loan->id)->orderBy('cycle_number')->get();
            $latest = $cycles->last();

            config(['lending.engine' => 'v1']);
            try {
                $old = $calc->calculateCycleBalance($loan, $calc->getActiveCycle($loan))['final_outstanding'];
            } catch (\Throwable) {
                $old = null;
            }
            config(['lending.engine' => 'v2']);

            $b = CycleBalance::at($latest);
            $uncapped = (function () use ($latest) {
                $cap = config('lending.penalty.cap');
                config(['lending.penalty.cap' => 'none']);
                $u = CycleBalance::at($latest);
                config(['lending.penalty.cap' => $cap]);

                return $u;
            })();
            $carry = round($cycles->slice(0, -1)->sum(fn ($c) => CycleBalance::at($c)['carry_difference'] ?? 0), 2);
            $decision = LoanStatus::decide($loan);
            $newStatus = $decision['status'] ?? $loan->status;
            $legacy = DB::table('repayments')->where('loan_id', $loan->id)->where('kind', 'rollover')->exists()
                || DB::table('disbursements')->where('loan_id', $loan->id)->where('kind', 'rollover')->exists();
            $original = $cycles->first()->lentPrincipal();
            $amount = $latest->lentPrincipal();

            $reasons = [];
            if ($loan->status === Loan::STATUS_REPAID && $b['final_outstanding'] > 0) {
                $reasons[] = 'REPAID_BUT_OWES';
            }
            if ($loan->status !== Loan::STATUS_REPAID && $newStatus === Loan::STATUS_REPAID) {
                $reasons[] = 'PAID_NOW_REPAID';
            }
            if ($old !== null && abs($old - $b['final_outstanding']) >= 0.01) {
                $reasons[] = $b['repayments_after_due'] > 0 ? 'PENALTY_STOPS_AT_PAYMENT' : 'BALANCE_RULE';
            }
            if ($uncapped['penalty'] - $b['penalty'] >= 0.01) {
                $reasons[] = 'IN_DUPLUM_CAP';
            }
            if (abs($carry) >= 0.01) {
                $reasons[] = 'ROLLOVER_CARRY_DIFFERS';
            }
            if (abs((float) $loan->amount - $amount) >= 0.01) {
                $reasons[] = 'AMOUNT_IS_CURRENT_PRINCIPAL';
            }
            if ($loan->original_amount === null || abs((float) $loan->original_amount - $original) >= 0.01) {
                $reasons[] = 'ORIGINAL_FROM_CYCLE_1';
            }
            if ($legacy) {
                $reasons[] = 'LEGACY_CHAIN';
            }

            $rows[] = [
                'loan_id' => $loan->id, 'user_id' => $loan->user_id,
                'status_old' => $loan->status, 'status_new' => $newStatus,
                'amount_old' => (float) $loan->amount, 'amount_new' => $amount,
                'original_old' => $loan->original_amount === null ? '' : (float) $loan->original_amount, 'original_new' => $original,
                'owed_old' => $old ?? '', 'owed_new' => $b['final_outstanding'],
                'difference' => $old === null ? '' : round($b['final_outstanding'] - $old, 2),
                'penalty_new' => $b['penalty'], 'penalty_without_cap' => $uncapped['penalty'],
                'cap_removes' => round($uncapped['penalty'] - $b['penalty'], 2), 'carry_differences' => $carry,
                'cycles' => $cycles->count(), 'legacy_chain' => $legacy ? 'yes' : '', 'reasons' => $reasons,
            ];
        }
        config(['lending.engine' => $engine]);

        return $rows;
    }

    private function toCsv(array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, self::HEADER);
        foreach ($rows as $r) {
            fputcsv($fh, array_map(fn ($k) => $k === 'reasons' ? implode(' ', $r[$k]) : $r[$k], self::HEADER));
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }

    private function summary(array $rows): void
    {
        $count = fn (string $reason) => count(array_filter($rows, fn ($r) => in_array($reason, $r['reasons'], true)));
        $sum = fn (string $key) => round(array_sum(array_map(fn ($r) => is_numeric($r[$key]) ? $r[$key] : 0, $rows)), 2);
        $this->table(['', 'loans', 'KES'], [
            ['Loans compared', count($rows), ''],
            ['Owed now, old engine', '', number_format($sum('owed_old'), 2)],
            ['Owed now, v2', '', number_format($sum('owed_new'), 2)],
            ['Penalty stops at payment', $count('PENALTY_STOPS_AT_PAYMENT'), ''],
            ['Other balance-rule changes', $count('BALANCE_RULE'), ''],
            ['In-duplum cap removes', $count('IN_DUPLUM_CAP'), number_format($sum('cap_removes'), 2)],
            ['Rollover carried a different amount', $count('ROLLOVER_CARRY_DIFFERS'), number_format($sum('carry_differences'), 2)],
            ['Repaid but owes (owner decides each)', $count('REPAID_BUT_OWES'), ''],
            ['Owes nothing, becomes repaid', $count('PAID_NOW_REPAID'), ''],
            ['loans.amount becomes current principal', $count('AMOUNT_IS_CURRENT_PRINCIPAL'), ''],
            ['original_amount from cycle 1', $count('ORIGINAL_FROM_CYCLE_1'), ''],
            ['Legacy ROLL OVER chain (flagged)', $count('LEGACY_CHAIN'), ''],
        ]);
    }
}
