<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Models\PartnerTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WalletsReconcileFromJournal extends Command
{
    protected $signature = 'wallets:reconcile-from-journal
        {--partner= : Limit to one partner id}
        {--apply : Reset bavix balances to match journal totals}';

    protected $description = 'Compare partner_transactions sums to bavix wallet balances; optionally rebuild.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $only = $this->option('partner');

        $query = PartnerTransaction::query()
            ->select('partner_id', 'wallet_slug',
                     DB::raw('SUM(amount) AS journal_sum'),
                     DB::raw('COUNT(*) AS row_count'))
            ->groupBy('partner_id', 'wallet_slug');

        if ($only) {
            $query->where('partner_id', (int) $only);
        }

        $rows = $query->get();
        $mismatches = 0;

        foreach ($rows as $row) {
            $partner = Partner::find($row->partner_id);
            if (!$partner) {
                $this->warn("partner#{$row->partner_id} missing");
                continue;
            }
            $wallet = $partner->getWallet($row->wallet_slug);
            $walletBalance = $wallet ? (float) $wallet->balance / 100 : 0.0;
            $journalSum = (float) $row->journal_sum;

            $diff = round($walletBalance - $journalSum, 4);

            if (abs($diff) < 0.0001) {
                continue;
            }

            $mismatches++;
            $this->warn(sprintf(
                'partner#%d slug=%s wallet=%.4f journal=%.4f diff=%.4f rows=%d',
                $partner->id, $row->wallet_slug, $walletBalance, $journalSum, $diff, $row->row_count
            ));

            if ($apply && $wallet) {
                DB::transaction(function () use ($wallet, $journalSum, $diff) {
                    // Reset to zero, then deposit exact journal sum.
                    if ($wallet->balance > 0) {
                        $wallet->forceWithdraw((int) $wallet->balance, [
                            'description' => 'Reconcile reset',
                        ]);
                    }
                    $target = (int) round($journalSum * 100);
                    if ($target > 0) {
                        $wallet->deposit($target, [
                            'description' => 'Reconcile rebuild from journal',
                        ]);
                    }
                });
            }
        }

        $this->newLine();
        $this->info($mismatches === 0
            ? 'All wallets match the journal.'
            : "{$mismatches} mismatch(es) found." . ($apply ? ' Rebuilt.' : ' Re-run with --apply to fix.'));

        return $mismatches === 0 ? self::SUCCESS : self::FAILURE;
    }
}