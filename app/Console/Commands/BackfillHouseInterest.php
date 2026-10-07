<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Partner;
use App\Services\LoanCalculator;
use App\Services\PartnerLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillHouseInterest extends Command
{
    protected $signature = 'partners:backfill-house-interest
        {--apply : Persist. Default is dry-run.}';

    protected $description = 'Backfill historical house interest + penalty share.';

    private const EXCLUDED_TXNS = ['BAD DEBT', 'CREDIT DISCOUNT', 'ROLL OVER'];
    private const PENALTY_SHARE  = 0.50;

    public function handle(LoanCalculator $calculator, PartnerLedger $ledger): int
    {
        $apply = (bool) $this->option('apply');
        $house = Partner::where('is_house_account', 1)->first();

        if (! $house) {
            $this->error('House partner not found.');
            return self::FAILURE;
        }

        $this->info(($apply ? 'APPLY' : 'DRY RUN') . " — house #{$house->id} {$house->name}");

        // ---------- Pass A: pure house loans (100% interest) ----------
        $pureHouse = Loan::where('broker_status', 0)
            ->where('status', 'repaid')
            ->where('capitalized_interest', '>', 0)
            ->orderBy('id')
            ->get();

        // ---------- Pass B: brokered loans (house keeps non-broker portion) ----------
        $brokered = Loan::where('broker_status', 1)
            ->whereNotNull('partner_id')
            ->where('status', 'repaid')
            ->where('capitalized_interest', '>', 0)
            ->orderBy('id')
            ->get();

        // ---------- Pass C: house penalty share on brokered loans (50%) ----------
        // Pure house loans keep 100% of penalty, but per your scope we only do
        // the 50/50 split on brokered loans for now.

        $rowsA = []; $totalA = 0.0;
        foreach ($pureHouse as $loan) {
            $share = round((float) $loan->capitalized_interest, 4);
            $rowsA[] = [$loan->id, number_format((float) $loan->capitalized_interest, 2), '100%', number_format($share, 4)];
            $totalA += $share;
        }

        $rowsB = []; $totalB = 0.0;
        foreach ($brokered as $loan) {
            $rate  = (float) ($loan->broker_rate ?: 40.0);
            $share = round((float) $loan->capitalized_interest * (1 - $rate / 100), 4);
            $rowsB[] = [$loan->id, number_format((float) $loan->capitalized_interest, 2), $rate . '%', number_format($share, 4)];
            $totalB += $share;
        }

        $rowsC = []; $totalC = 0.0;
        foreach ($brokered as $loan) {
            $penaltyCollected = $this->computePenaltyCollected($loan, $calculator);
            $share = round($penaltyCollected * self::PENALTY_SHARE, 4);
            if ($share > 0) {
                $rowsC[] = [$loan->id, number_format($penaltyCollected, 2), '50%', number_format($share, 4)];
                $totalC += $share;
            }
        }

        $this->line('Pass A — pure house loans (100%): ' . count($rowsA));
        $this->table(['loan', 'interest', 'pct', 'house_share'], $rowsA);

        $this->newLine();
        $this->line('Pass B — brokered loans (non-broker portion): ' . count($rowsB));
        $this->table(['loan', 'interest', 'broker_rate', 'house_share'], $rowsB);

        $this->newLine();
        $this->line('Pass C — house penalty share on brokered loans (50%): ' . count($rowsC));
        if (! empty($rowsC)) {
            $this->table(['loan', 'penalty_collected', 'pct', 'house_share'], $rowsC);
        }

        $this->newLine();
        $this->table(['metric', 'value'], [
            ['Pass A total',  number_format($totalA, 4)],
            ['Pass B total',  number_format($totalB, 4)],
            ['Pass C total',  number_format($totalC, 4)],
            ['GRAND TOTAL',   number_format($totalA + $totalB + $totalC, 4)],
        ]);

        if (! $apply) {
            $this->warn('DRY RUN — re-run with --apply.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($pureHouse, $brokered, $house, $ledger, $calculator) {
            // Pass A
            foreach ($pureHouse as $loan) {
                $share = round((float) $loan->capitalized_interest, 4);
                if ($share <= 0) continue;

                $ledger->post(
                    partner: $house,
                    walletSlug: 'interest',
                    type: 'profit_distribution',
                    amount: $share,
                    loanId: $loan->id,
                    reference: "BACKFILL-HOUSE-INT-L{$loan->id}",
                    notes: "Backfilled house interest (100%) for loan #{$loan->id}",
                );
            }

            // Pass B
            foreach ($brokered as $loan) {
                $rate  = (float) ($loan->broker_rate ?: 40.0);
                $share = round((float) $loan->capitalized_interest * (1 - $rate / 100), 4);
                if ($share <= 0) continue;

                $ledger->post(
                    partner: $house,
                    walletSlug: 'interest',
                    type: 'profit_distribution',
                    amount: $share,
                    loanId: $loan->id,
                    reference: "BACKFILL-HOUSE-BRK-L{$loan->id}",
                    notes: "Backfilled house interest (non-broker portion) for loan #{$loan->id}",
                );
            }

            // Pass C
            foreach ($brokered as $loan) {
                $penaltyCollected = $this->computePenaltyCollected($loan, $calculator);
                $share = round($penaltyCollected * self::PENALTY_SHARE, 4);
                if ($share <= 0) continue;

                $ledger->post(
                    partner: $house,
                    walletSlug: 'interest',
                    type: 'penalty_share',
                    amount: $share,
                    loanId: $loan->id,
                    reference: "BACKFILL-HOUSE-PEN-L{$loan->id}",
                    notes: "Backfilled house penalty share (50%) for loan #{$loan->id}",
                );
            }
        });

        $wallet  = $house->getWallet('interest');
        $dp      = $wallet->decimal_places ?? 4;
        $balance = $wallet->balance / (10 ** $dp);

        $this->newLine();
        $this->info("Posted. house interest wallet balance: " . number_format($balance, $dp));

        return self::SUCCESS;
    }

    /**
     * Same last-cycle-only penalty formula used for Isiro.
     */
    private function computePenaltyCollected(Loan $loan, LoanCalculator $calculator): float
    {
        $cycle = LoanCycle::where('loan_id', $loan->id)
            ->orderByDesc('cycle_number')
            ->first();

        if (! $cycle) {
            return 0.0;
        }

        $calc        = $calculator->calculateCycleBalance($loan, $cycle);
        $fullBalance = (float) ($calc['full_balance'] ?? 0);

        $repaidInCycle = (float) DB::table('repayments')
            ->where('loan_id', $loan->id)
            ->where('loan_cycle_id', $cycle->id)
            ->whereNull('deleted_at')
            ->whereNotIn('transaction', self::EXCLUDED_TXNS)
            ->sum('amount');

        return round(max(0, $repaidInCycle - $fullBalance), 4);
    }
}