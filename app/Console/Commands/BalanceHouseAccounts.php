<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Partner;
use App\Models\Repayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BalanceHouseAccounts extends Command
{
    protected $signature = 'loans:balance-house-accounts
                            {--apply : Actually save the balances (default is dry-run)}
                            {--verbose-detail : Print per-loan breakdown}';

    protected $description = 'Compute House Principal and House Interest balances from historical loans.';

    /**
     * Transaction codes that should SKIP the entire loan from the sum.
     */
    protected const SKIP_LOAN_CODES = [
        'roll over',
        'rollover',
        'roll-over',
    ];

    /**
     * Transaction codes whose repayment amounts are EXCLUDED from the sum.
     */
    protected const EXCLUDED_REPAYMENT_CODES = [
        'credit discount',
        'bad debt',
    ];

    public function handle(): int
    {
        $verbose = $this->option('verbose-detail');
        $apply = $this->option('apply');

        // ============ 1. LOAD ALL LOANS WITH RELATIONS ============
        $this->info('Loading loans...');
        $loans = Loan::with(['loanType', 'repayments'])
            ->whereNull('deleted_at')
            ->get();

        $this->info("Total loans: {$loans->count()}");

        // ============ 2. CLASSIFY LOANS ============
        $principalTotal = 0;
        $interestTotal = 0;
        $loansSkippedByRollover = 0;
        $loansIncludedPrincipal = 0;
        $loansIncludedInterest = 0;
        $repaymentsExcludedAmount = 0;

        $perLoanRows = [];

        foreach ($loans as $loan) {
            $loanType = $loan->loanType;
            if (!$loanType) {
                continue;
            }

            // ---- Check if any repayment has a rollover code → skip whole loan ----
            $skipLoan = false;
            foreach ($loan->repayments as $r) {
                $code = strtolower(trim($r->transaction ?? ''));
                foreach (self::SKIP_LOAN_CODES as $skipCode) {
                    if (str_contains($code, $skipCode)) {
                        $skipLoan = true;
                        break 2;
                    }
                }
            }

            if ($skipLoan) {
                $loansSkippedByRollover++;
                if ($verbose) {
                    $this->line("  ⏭ Skipping loan #{$loan->id} — has rollover transaction");
                }
                continue;
            }

            // ---- Compute valid repayments (excluding credit discount / bad debt) ----
            $validRepayments = 0;
            foreach ($loan->repayments as $r) {
                $code = strtolower(trim($r->transaction ?? ''));
                $excluded = false;
                foreach (self::EXCLUDED_REPAYMENT_CODES as $excCode) {
                    if (str_contains($code, $excCode)) {
                        $excluded = true;
                        break;
                    }
                }
                if (!$excluded) {
                    $validRepayments += (float) $r->amount;
                } else {
                    $repaymentsExcludedAmount += (float) $r->amount;
                }
            }

            // ---- Principal contribution (always, unless skipped) ----
            $principalContribution = (float) $loan->amount;
            $principalTotal += $principalContribution;
            $loansIncludedPrincipal++;

            // ---- Interest contribution (only for repaid loans) ----
            $interestContribution = 0;
            if ($loan->status === 'repaid') {
                // Interest = amount * rate / 100
                $interestContribution = (float) $loan->amount * ((float) $loanType->interest_rate / 100);
                $interestTotal += $interestContribution;
                $loansIncludedInterest++;
            }

            $perLoanRows[] = [
                'id' => $loan->id,
                'status' => $loan->status,
                'amount' => (float) $loan->amount,
                'rate' => (float) $loanType->interest_rate,
                'principal' => $principalContribution,
                'interest' => $interestContribution,
                'valid_repayments' => $validRepayments,
            ];

            if ($verbose) {
                $this->line(sprintf(
                    "  ✓ Loan #%d [%s] amount=%.2f rate=%.2f%% → principal+=%.2f, interest+=%.2f",
                    $loan->id,
                    $loan->status,
                    $loan->amount,
                    $loanType->interest_rate,
                    $principalContribution,
                    $interestContribution
                ));
            }
        }

        // ============ 3. SUMMARY ============
        $this->newLine();
        $this->info('========== HOUSE ACCOUNT BALANCE SUMMARY ==========');
        $this->line("Loans included (principal):       {$loansIncludedPrincipal}");
        $this->line("Loans skipped (rollover):         {$loansSkippedByRollover}");
        $this->line("Loans contributing interest:      {$loansIncludedInterest} (status = repaid)");
        $this->line("Repayments excluded (discount/bad debt): KES " . number_format($repaymentsExcludedAmount, 2));
        $this->newLine();
        $this->line("🏦 House Principal Account should hold: KES " . number_format($principalTotal, 2));
        $this->line("💹 House Interest Account should hold:  KES " . number_format($interestTotal, 2));
        $this->newLine();

        // ============ 4. COMPARE WITH CURRENT ============
        $housePrincipal = Partner::where('is_house_account', true)
            ->where('account_type', 'principal')
            ->first();

        $houseInterest = Partner::where('is_house_account', true)
            ->where('account_type', 'interest')
            ->first();

        if (!$housePrincipal || !$houseInterest) {
            $this->error('House accounts not found. Run: php artisan db:seed --class=HouseAccountsSeeder');
            return self::FAILURE;
        }

        $currentPrincipal = (float) $housePrincipal->current_balance;
        $currentInterest = (float) $houseInterest->current_balance;

        $this->info('========== COMPARISON ==========');
        $this->line(sprintf(
            "Principal: current=%.2f  computed=%.2f  diff=%+.2f",
            $currentPrincipal,
            $principalTotal,
            $principalTotal - $currentPrincipal
        ));
        $this->line(sprintf(
            "Interest:  current=%.2f  computed=%.2f  diff=%+.2f",
            $currentInterest,
            $interestTotal,
            $interestTotal - $currentInterest
        ));
        $this->newLine();

        // ============ 5. APPLY (OPTIONAL) ============
        if (!$apply) {
            $this->warn('DRY RUN — nothing was saved. Re-run with --apply to persist.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($housePrincipal, $houseInterest, $principalTotal, $interestTotal) {
            $housePrincipal->update([
                'current_balance' => $principalTotal,
                'total_contribution' => $principalTotal,
            ]);

            $houseInterest->update([
                'current_balance' => $interestTotal,
                'total_contribution' => $interestTotal,
            ]);
        });

        $this->info('✅ House account balances updated.');
        $this->line("   House Principal → KES " . number_format($principalTotal, 2));
        $this->line("   House Interest  → KES " . number_format($interestTotal, 2));

        return self::SUCCESS;
    }
}