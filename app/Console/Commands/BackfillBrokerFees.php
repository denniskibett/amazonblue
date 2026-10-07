<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Partner;
use App\Services\LoanCalculator;
use App\Services\PartnerLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillBrokerFees extends Command
{
    protected $signature = 'partners:backfill-broker-fees
        {--partner= : Partner id (required)}
        {--apply   : Persist. Default is dry-run.}';

    protected $description = 'Backfill historical broker fee + penalty share into a partner\'s broker_fee wallet.';

    private const EXCLUDED_TXNS = ['BAD DEBT', 'CREDIT DISCOUNT', 'ROLL OVER'];
    private const PENALTY_SHARE = 0.50;

    public function handle(LoanCalculator $calculator, PartnerLedger $ledger): int
    {
        $apply   = (bool) $this->option('apply');
        $partner = Partner::find((int) $this->option('partner'));

        if (! $partner) {
            $this->error('Partner not found.');
            return self::FAILURE;
        }

        $this->info(($apply ? 'APPLY' : 'DRY RUN') . " — partner #{$partner->id} {$partner->name}");

        $loans = Loan::query()
            ->where('partner_id', $partner->id)
            ->where('broker_status', 1)
            ->where('status', 'repaid')
            ->orderBy('id')
            ->get();

        $this->line("Loans: {$loans->count()}");
        $this->newLine();

        $rows = [];
        $totalInterest = 0.0;
        $totalPenalty  = 0.0;

        foreach ($loans as $loan) {
            $interest = (float) $loan->capitalized_interest;
            $rate     = (float) ($loan->broker_rate ?: $partner->broker_rate);
            $interestShare = round($interest * ($rate / 100), 4);

            $penaltyCollected = $this->computePenaltyCollected($loan, $calculator);
            $penaltyShare     = round($penaltyCollected * self::PENALTY_SHARE, 4);

            $rows[] = [
                $loan->id,
                number_format($interest, 2),
                $rate,
                number_format($interestShare, 4),
                number_format($penaltyCollected, 2),
                number_format($penaltyShare, 4),
            ];

            $totalInterest += $interestShare;
            $totalPenalty  += $penaltyShare;
        }

        $this->table(
            ['loan', 'interest', 'rate%', 'broker_interest', 'penalty_collected', 'broker_penalty'],
            $rows
        );

        $this->newLine();
        $this->table(
            ['metric', 'value'],
            [
                ['total broker interest share', number_format($totalInterest, 4)],
                ['total broker penalty share',  number_format($totalPenalty, 4)],
                ['TOTAL',                        number_format($totalInterest + $totalPenalty, 4)],
            ]
        );

        if (! $apply) {
            $this->warn('DRY RUN — re-run with --apply to persist.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($loans, $partner, $ledger, $calculator) {
            foreach ($loans as $loan) {
                $interest = (float) $loan->capitalized_interest;
                $rate     = (float) ($loan->broker_rate ?: $partner->broker_rate);
                $interestShare = round($interest * ($rate / 100), 4);

                $penaltyCollected = $this->computePenaltyCollected($loan, $calculator);
                $penaltyShare     = round($penaltyCollected * self::PENALTY_SHARE, 4);

                if ($interestShare > 0) {
                    $ledger->post(
                        partner: $partner,
                        walletSlug: 'broker_fee',
                        type: 'broker_fee_earned',
                        amount: $interestShare,
                        loanId: $loan->id,
                        reference: "BACKFILL-FEE-L{$loan->id}",
                        notes: "Backfilled broker interest share for loan #{$loan->id}",
                    );
                }

                if ($penaltyShare > 0) {
                    $ledger->post(
                        partner: $partner,
                        walletSlug: 'broker_fee',
                        type: 'penalty_share',
                        amount: $penaltyShare,
                        loanId: $loan->id,
                        reference: "BACKFILL-PEN-L{$loan->id}",
                        notes: "Backfilled penalty share (50% of last-cycle penalty collected) for loan #{$loan->id}",
                    );
                }
            }
        });

        $wallet  = $partner->getWallet('broker_fee');
        $dp      = $wallet->decimal_places ?? 4;
        $balance = $wallet->balance / (10 ** $dp);

        $this->newLine();
        $this->info("Posted. broker_fee wallet balance: " . number_format($balance, $dp));

        return self::SUCCESS;
    }

    /**
     * Compute actual penalty collected using ONLY the most recent cycle.
     *
     * penalty_collected = MAX(0, sum(non-excluded repayments in last cycle) - full_balance_of_last_cycle)
     */
    private function computePenaltyCollected(Loan $loan, LoanCalculator $calculator): float
    {
        $cycle = LoanCycle::where('loan_id', $loan->id)
            ->orderByDesc('cycle_number')
            ->first();

        if (! $cycle) {
            return 0.0;
        }

        $calc = $calculator->calculateCycleBalance($loan, $cycle);
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