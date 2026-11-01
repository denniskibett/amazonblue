<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\Partner;
use App\Services\Loans\LoanFundings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Freezes the funding of past loans (owner, 1 Nov 2026): for every loan with cash sent and no loan_fundings rows,
 * adds ONE row with the funder the split reads today (House or the one partner line) at 100% of the cash sent,
 * marked source 'backfill 2026-11-02'. Everyone else is 0 (no row). Only adds rows: no existing row of any table is
 * changed, and re-running adds nothing. Dry run unless --apply.
 */
class LoansBackfillFundings extends Command
{
    protected $signature = 'loans:backfill-fundings {--dry-run : Show counts and a sample, write nothing (the default)} {--apply : Add the rows}';

    protected $description = 'Freeze each past loan\'s single funder in loan_fundings (dry run unless --apply)';

    public function handle(LoanFundings $fundings): int
    {
        $apply = $this->option('apply') && ! $this->option('dry-run');
        if (! LoanFundings::ready()) {
            if ($apply) {
                $this->error('Table loan_fundings is missing: run database/sql/2026_11_02_part_funding.sql (or migrate) first.');

                return self::FAILURE;
            }
            $this->warn('Table loan_fundings is missing (run database/sql/2026_11_02_part_funding.sql before --apply); counting anyway.');
        }
        $done = LoanFundings::ready() ? DB::table('loan_fundings')->distinct()->pluck('loan_id')->flip() : collect();
        $houseIds = Partner::where('is_house_account', true)->pluck('id')->all();
        $counts = ['house' => 0, 'partner' => 0, 'no_cash' => 0, 'already' => 0];
        $sample = [];

        Loan::withTrashed()->orderBy('id')->select('id')->chunk(500, function ($loans) use ($fundings, $apply, $done, $houseIds, &$counts, &$sample) {
            foreach ($loans as $loan) {
                if ($done->has($loan->id)) {
                    $counts['already']++;
                    continue;
                }
                $now = $fundings->current($loan);
                if (! $now) {
                    $counts['no_cash']++;
                    continue;
                }
                [$lineId, $partnerId, $amount, $disbursementId] = $now;
                $counts[$partnerId && ! in_array($partnerId, $houseIds, true) ? 'partner' : 'house']++;
                if (count($sample) < 10) {
                    $sample[] = [$loan->id, $lineId ?? '-', $partnerId ? Partner::whereKey($partnerId)->value('name') : 'House', number_format($amount, 2)];
                }
                if ($apply) {
                    DB::table('loan_fundings')->insert(['loan_id' => $loan->id, 'investment_id' => $lineId, 'partner_id' => $partnerId,
                        'amount' => $amount, 'share' => 1, 'disbursement_id' => $disbursementId, 'source' => LoanFundings::BACKFILL,
                        'notes' => 'Single funder at 100% as the split read it before part funding', 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        });

        $this->table(['loan', 'line', 'funder', 'cash sent'], $sample);
        $this->info(sprintf('%d loan(s) %s: %d house, %d partner line. Skipped: %d already recorded, %d with no cash sent.',
            $counts['house'] + $counts['partner'], $apply ? 'frozen' : 'would be frozen', $counts['house'], $counts['partner'], $counts['already'], $counts['no_cash']));
        if (! $apply) {
            $this->warn('Dry run: nothing written. Run with --apply to add the rows.');
        }

        return self::SUCCESS;
    }
}
