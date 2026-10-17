<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Models\User;
use App\Services\CustomerWallets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Create every customer's bavix wallets and post borrower cash history into them.
 * Safe to run any time: postings are matched to their source records, so nothing is posted twice.
 */
class WalletsSync extends Command
{
    protected $signature = 'wallets:sync
        {--user= : Only this borrower (user id)}
        {--dry-run : Show what would be posted without writing anything}';

    protected $description = 'Create customer wallets and sync borrower cash (real disbursements and repayments) into them.';

    public function handle(CustomerWallets $wallets): int
    {
        $dry = (bool) $this->option('dry-run');
        // anyone with the borrower role or with loans (some partners and staff borrow too)
        $borrowers = User::withTrashed()
            ->where(fn ($q) => $q->role('borrower')->orWhereIn('id', \App\Models\Loan::withTrashed()->select('user_id')))
            ->when($this->option('user'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('id')->get();

        if (! $this->option('user')) {
            foreach (Partner::orderBy('id')->get() as $partner) {
                $missing = collect(CustomerWallets::PARTNER_WALLETS)->reject(fn ($slug) => $partner->getWallet($slug))->values();
                if ($missing->isNotEmpty()) {
                    $this->line("partner #{$partner->id} {$partner->name}: " . ($dry ? 'would create ' : 'created ') . $missing->implode(', '));
                    if (! $dry) {
                        $wallets->ensurePartnerWallets($partner);
                    }
                }
            }
        }

        $totalPostings = 0;
        $totalMinor = 0;
        $bar = $this->output->createProgressBar($borrowers->count());
        foreach ($borrowers as $user) {
            if ($dry) {
                // run inside a transaction and roll it back, so the numbers are real but nothing is kept
                DB::beginTransaction();
                $result = $wallets->syncBorrower($user);
                DB::rollBack();
            } else {
                $result = $wallets->syncBorrower($user);
            }
            $totalPostings += $result['postings'];
            $totalMinor += $result['minor'];
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            '%s %d posting(s) across %d borrower(s); net KES %s.',
            $dry ? 'Would post' : 'Posted', $totalPostings, $borrowers->count(), number_format($totalMinor / 100, 2)
        ));
        if (! $dry) {
            $this->line('Run `php artisan wallets:check` to reconcile.');
        }

        return self::SUCCESS;
    }
}
