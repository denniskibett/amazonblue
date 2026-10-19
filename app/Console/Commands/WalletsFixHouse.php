<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Services\CustomerWallets;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Console\Command;

/**
 * One-off correction for audit finding B1 (House Account #6 double count).
 *
 * The house interest postings were journaled as 'principal' (wallet_slug wasn't saved), and
 * `wallets:check --repair-partners` on 2026-10-15 then deposited KES 4,420,156.64 into the House
 * principal wallet to "match" that wrong journal. Once the journal is fixed
 * (database/sql/2026_10_16_partner_journal_wallet_slug.sql) that deposit has nothing behind it.
 *
 * This posts ONE compensating entry per partner wallet that differs from its journal (withdrawal when
 * the wallet is above it), with meta {source: correction, reason, reference}. Nothing is deleted or
 * edited; the original deposit stays in the log next to its reversal. Safe to re-run: a wallet that
 * already matches gets nothing.
 */
class WalletsFixHouse extends Command
{
    protected $signature = 'wallets:fix-house
        {--partner= : Only this partner id (default: every partner wallet)}
        {--dry-run : Show the corrections without posting them}';

    protected $description = 'Post compensating entries so every partner wallet equals its partner journal (fixes the House Account double count).';

    public function handle(CustomerWallets $wallets): int
    {
        $dry = (bool) $this->option('dry-run');
        $check = $wallets->checkPartners();

        if ($check['slug_mismatches']) {
            foreach ($check['slug_mismatches'] as $m) {
                $this->warn(sprintf('  partner #%d: %d journal row(s) (%s) say %s but were posted to %s', $m['partner'], $m['rows'], number_format((float) $m['amount'], 4), $m['journal_says'], $m['posted_to']));
            }
            $this->error('The journal is wrong first: run database/sql/2026_10_16_partner_journal_wallet_slug.sql, then this command.');

            return self::FAILURE;
        }

        $issues = collect($check['issues'])->when($this->option('partner'), fn ($c, $id) => $c->where('partner', (int) $id));
        if ($issues->isEmpty()) {
            $this->info("All {$check['checked']} partner wallet(s) equal their journal. Nothing to correct.");

            return self::SUCCESS;
        }

        $this->table(['partner', 'wallet', 'journal', 'wallet balance', 'correction'], $issues->map(fn ($i) => [
            '#' . $i['partner'], $i['wallet'], number_format($i['journal'], 4),
            $i['balance'] === null ? 'missing' : number_format($i['balance'], 4),
            $i['balance'] === null ? '-' : number_format($i['journal'] - $i['balance'], 4),
        ])->all());

        foreach ($issues as $i) {
            $partner = Partner::find($i['partner']);
            if (! $partner || $i['balance'] === null) {
                $this->warn("  partner #{$i['partner']} {$i['wallet']}: no wallet (run wallets:sync first)");
                continue;
            }

            // name the unbacked repair deposit(s) this reverses, so the log explains itself
            $wallet = $partner->getWallet($i['wallet']);
            $reconcileIds = Transaction::where('wallet_id', $wallet->getKey())->where('meta->source', 'reconcile')->pluck('id')->all();
            $reason = sprintf('Wallet held %s against a partner journal of %s (audit B1: the 2026-10-15 repair aligned it to journal rows saved under the wrong wallet%s).',
                number_format($i['balance'], 4), number_format($i['journal'], 4),
                $reconcileIds ? '; reverses transaction #' . implode(', #', $reconcileIds) : '');

            $posted = $wallets->correctPartnerWallet($partner, $i['wallet'], $i['journal'], $reason, $dry);
            $this->line(sprintf('  %s partner #%d %s: %s %s', $dry ? 'would post' : 'posted', $partner->id, $i['wallet'],
                $posted < 0 ? 'withdrawal' : 'deposit', number_format(abs($posted), 4)));
        }

        $this->newLine();
        $dry ? $this->info('Dry run: nothing posted.') : $this->info('Done. Run wallets:check to confirm.');

        return self::SUCCESS;
    }
}
