<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletsBootstrap extends Command
{
    protected $signature = 'wallets:bootstrap
        {--apply : Persist. Without this flag, dry-run only.}
        {--slug=principal : Which partner wallet slug to seed from partners.current_balance}';

    protected $description = 'Create bavix wallets for every partner and borrower, seed from journal.';

    private const PARTNER_SLUGS = ['principal', 'interest', 'broker_fee', 'investment_tracker'];
    private const BORROWER_SLUG = 'borrower_loan';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $seedSlug = (string) $this->option('slug');

        $this->info($apply ? 'APPLY MODE' : 'DRY RUN — nothing will be written');

        $partners = Partner::query()->get();
        $borrowers = User::query()->where('role', 'borrower')->get();

        $partnerRows = 0;
        $partnerErrors = 0;

        foreach ($partners as $partner) {
            foreach (self::PARTNER_SLUGS as $slug) {
                $existing = $partner->getWallet($slug);
                if ($existing) {
                    $this->line("  partner#{$partner->id} wallet '{$slug}' exists (balance="
                        . number_format((float)$existing->balance / 100, 4) . ')');
                    continue;
                }

                if (!$apply) {
                    $this->line("  would create partner#{$partner->id} wallet '{$slug}'");
                    $partnerRows++;
                    continue;
                }

                DB::transaction(function () use ($partner, $slug, $seedSlug) {
                    $wallet = $partner->createWallet([
                        'name' => ucfirst(str_replace('_', ' ', $slug)),
                        'slug' => $slug,
                        'meta' => ['partner_id' => $partner->id],
                    ]);

                    // Only the seed slug gets a journal entry, from current_balance.
                    if ($slug === $seedSlug) {
                        $opening = (float) ($partner->current_balance ?? 0);
                        if ($opening != 0.0) {
                            $reference = 'OPENING-' . $partner->id . '-' . Str::ulid();
                            $wallet->deposit((int) round($opening * 100), [
                                'reference' => $reference,
                                'description' => 'Opening balance migration',
                            ]);

                            PartnerTransaction::create([
                                'partner_id'     => $partner->id,
                                'wallet_slug'    => $slug,
                                'type'           => 'contribution',
                                'amount'         => $opening,
                                'balance_after'  => $opening,
                                'reference'      => $reference,
                                'transaction_date' => now()->toDateString(),
                                'notes'          => 'Opening balance from partners.current_balance',
                            ]);
                        }
                    }
                });

                $partnerRows++;
            }
        }

        $borrowerRows = 0;
        foreach ($borrowers as $borrower) {
            if ($borrower->getWallet(self::BORROWER_SLUG)) {
                continue;
            }
            if (!$apply) {
                $borrowerRows++;
                continue;
            }
            $borrower->createWallet([
                'name' => 'Loan Wallet',
                'slug' => self::BORROWER_SLUG,
                'meta' => ['user_id' => $borrower->id],
            ]);
            $borrowerRows++;
        }

        $this->newLine();
        $this->table(
            ['metric', 'count'],
            [
                ['partners scanned',    $partners->count()],
                ['partner wallet rows', $partnerRows],
                ['borrowers scanned',   $borrowers->count()],
                ['borrower wallet rows',$borrowerRows],
                ['errors',              $partnerErrors],
            ]
        );

        if (!$apply) {
            $this->warn('DRY RUN — re-run with --apply to persist.');
        }

        // Reconcile check (post-apply only, or against simulated values in dry-run)
        $this->newLine();
        $this->info('Reconciliation: sum(principal wallet) vs sum(partners.current_balance)');

        $mismatches = 0;
        foreach ($partners as $partner) {
            $wallet = $partner->getWallet('principal');
            $walletBalance = $wallet ? (float) $wallet->balance / 100 : 0.0;
            $columnBalance = (float) ($partner->current_balance ?? 0);
            if (abs($walletBalance - $columnBalance) > 0.0001) {
                $this->error(sprintf(
                    '  partner#%d mismatch: wallet=%.4f column=%.4f',
                    $partner->id, $walletBalance, $columnBalance
                ));
                $mismatches++;
            }
        }

        return $mismatches === 0 ? self::SUCCESS : self::FAILURE;
    }
}