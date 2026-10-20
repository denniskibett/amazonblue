<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Services\CustomerWallets;
use App\Services\Ledger;
use Illuminate\Console\Command;

/**
 * Reconciliation report for customer wallets:
 *  - borrower cash wallets vs real cash in the disbursements and repayments tables
 *  - partner wallets vs the partner journal (partner_transactions)
 *  - every wallet's stored balance vs the sum of its own transactions
 *  - the ledger (gl- wallets): trial balance 0, and cash per borrower vs their borrower wallet
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
        $this->line("  {$p['checked']} partner wallet(s) checked (every wallet, and every journal group)");
        foreach ($p['slug_mismatches'] as $m) {
            $problems++;
            $this->warn(sprintf('  partner #%d journal: %d row(s) (%s) say %s but were posted to %s', $m['partner'], $m['rows'], number_format((float) $m['amount'], 4), $m['journal_says'], $m['posted_to']));
        }
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
            // safe only once the journal itself is right: aligning wallets to a journal whose rows point
            // at the wrong wallet is what double-counted the House Account on 2026-10-15
            if ($p['slug_mismatches']) {
                $this->error('Not repairing: journal rows point at the wrong wallet. Run database/sql/2026_10_16_partner_journal_wallet_slug.sql first.');

                return self::FAILURE;
            }
            $journal = collect($p['issues'])->mapWithKeys(fn ($i) => [$i['partner'] . ':' . $i['wallet'] => $i['journal']]);
            $targets = $journal->keys()->merge(collect($p['integrity'])->map(fn ($i) => $i['holder'] . ':' . $i['slug']))->unique();
            foreach ($targets as $key) {
                [$partnerId, $slug] = explode(':', $key);
                $partner = Partner::find($partnerId);
                if (! $partner) {
                    continue;
                }
                $wallets->ensurePartnerWallets($partner);
                $total = $journal[$key] ?? (float) \App\Models\PartnerTransaction::where('partner_id', $partnerId)->where('wallet_slug', $slug)->sum('amount');
                $posted = $wallets->correctPartnerWallet($partner, $slug, $total, 'wallets:check --repair-partners: wallet differed from the partner journal');
                $this->line(sprintf('  repaired partner #%d %s (correcting entry %s)', $partnerId, $slug, number_format($posted, 4)));
            }
            $this->info('Repaired. Run wallets:check again to confirm.');
        }

        if (Ledger::enabled() || \Bavix\Wallet\Models\Wallet::where('slug', 'like', 'gl-%')->exists()) {
            $this->newLine();
            $this->info('Ledger (gl- wallets)');
            $ledger = app(Ledger::class);
            $tb = $ledger->trialBalance();
            $this->line(sprintf('  %d account(s); trial balance %s (must be 0)', $tb['accounts'], number_format($tb['total'], 4)));
            if (abs($tb['total']) >= 0.0001) {
                $problems++;
                $this->warn('  the ledger does not balance');
            }
            if (\App\Services\LoanCalculator::v2()) {
                // informational: differences come from rows the ledger does not post yet (legacy chains, non_cash)
                $r = $ledger->checkReceivables();
                $this->line(sprintf('  %d loan(s): ledger receivable vs owed; %d differ (see loans:restate CSV)', $r['checked'], count($r['issues'])));
            }
            $c = $ledger->checkBorrowerCash();
            $this->line("  {$c['checked']} borrower(s): ledger cash vs borrower wallet");
            foreach ($c['issues'] as $i) {
                $problems++;
                $this->warn(sprintf('  user #%d: ledger %s, wallet %s', $i['user'], number_format($i['ledger'], 2), $i['wallet'] === null ? 'missing' : number_format($i['wallet'], 2)));
            }
        }

        $this->newLine();
        $problems === 0 ? $this->info('All customer wallets reconcile.') : $this->warn("{$problems} item(s) need attention.");

        return $problems === 0 ? self::SUCCESS : self::FAILURE;
    }
}
