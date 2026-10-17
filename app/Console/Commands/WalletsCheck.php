<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Services\CustomerWallets;
use Illuminate\Console\Command;

/**
 * Reconciliation report for customer wallets:
 *  - borrower cash wallets vs real cash in the disbursements and repayments tables
 *  - partner wallets vs the partner journal (partner_transactions)
 *  - every wallet's stored balance vs the sum of its own transactions
 */
class WalletsCheck extends Command
{
    protected $signature = 'wallets:check
        {--repair-partners : Align partner wallets (balance and transaction log) with the journal}';

    protected $description = 'Reconcile customer wallets against loans (cash), the partner journal and their own transaction logs.';

    public function handle(CustomerWallets $wallets): int
    {
        $problems = 0;

        $this->info('Borrower cash wallets vs loan tables (cash only)');
        $b = $wallets->checkBorrowers();
        $this->line("  {$b['checked']} borrower(s) checked");
        foreach ($b['issues'] as $i) {
            $problems++;
            $this->warn(sprintf('  user #%d %s: expected %s, wallet %s', $i['user'], $i['name'], number_format($i['expected'], 2), $i['wallet'] === null ? 'missing' : number_format($i['wallet'], 2)));
        }
        foreach ($b['integrity'] as $i) {
            $problems++;
            $this->warn(sprintf('  wallet #%d (user #%d %s): balance %s but its transactions add up to %s', $i['wallet_id'], $i['holder'], $i['slug'], number_format($i['balance'], 2), number_format($i['log'], 2)));
        }

        $this->newLine();
        $this->info('Partner wallets vs partner journal');
        $p = $wallets->checkPartners();
        $this->line("  {$p['checked']} partner wallet(s) with journal entries checked");
        foreach ($p['issues'] as $i) {
            $problems++;
            $this->warn(sprintf('  partner #%d %s: journal %s, wallet %s', $i['partner'], $i['wallet'], number_format($i['journal'], 4), $i['balance'] === null ? 'missing' : number_format($i['balance'], 4)));
        }
        foreach ($p['integrity'] as $i) {
            $problems++;
            $this->warn(sprintf('  wallet #%d (partner #%d %s): balance %s but its transactions add up to %s', $i['wallet_id'], $i['holder'], $i['slug'], number_format($i['balance'], 4), number_format($i['log'], 4)));
        }

        if ($this->option('repair-partners') && ($p['issues'] || $p['integrity'])) {
            $this->newLine();
            $journal = \App\Models\PartnerTransaction::selectRaw('partner_id, wallet_slug, sum(amount) as total')->groupBy('partner_id', 'wallet_slug')->get()
                ->keyBy(fn ($r) => $r->partner_id . ':' . $r->wallet_slug);
            $targets = collect($p['issues'])->map(fn ($i) => $i['partner'] . ':' . $i['wallet'])
                ->merge(collect($p['integrity'])->map(fn ($i) => $i['holder'] . ':' . $i['slug']))->unique();
            foreach ($targets as $key) {
                [$partnerId, $slug] = explode(':', $key);
                $partner = Partner::find($partnerId);
                $wallets->ensurePartnerWallets($partner);
                $posted = $wallets->repairPartnerWallet($partner, $slug, (float) ($journal[$key]->total ?? 0));
                $this->line(sprintf('  repaired partner #%d %s (correcting entry %s)', $partnerId, $slug, number_format($posted, 4)));
            }
            $this->info('Repaired. Run wallets:check again to confirm.');
        }

        $this->newLine();
        $problems === 0 ? $this->info('All customer wallets reconcile.') : $this->warn("{$problems} item(s) need attention.");

        return $problems === 0 ? self::SUCCESS : self::FAILURE;
    }
}
