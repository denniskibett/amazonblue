<?php

namespace App\Console\Commands;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\InvestmentFunding;
use App\Models\Partner;
use Illuminate\Console\Command;

class BackfillHouseAccount extends Command
{
    protected $signature = 'loans:backfill-house-account';
    protected $description = 'Backfill existing investments/disbursements with the House Principal account.';

    public function handle(): int
    {
        $housePrincipal = Partner::where('is_house_account', true)
            ->where('account_type', 'principal')
            ->first();

        if (!$housePrincipal) {
            $this->error('House Principal account not found. Run HouseAccountsSeeder first.');
            return self::FAILURE;
        }

        // 1. Investments without fundings
        $investments = Investment::whereDoesntHave('fundings')->get();
        foreach ($investments as $inv) {
            InvestmentFunding::create([
                'investment_id' => $inv->id,
                'partner_id' => $housePrincipal->id,
                'amount_committed' => $inv->committed_amount, // uses new fallback accessor
                'amount_disbursed' => $inv->disbursements()->sum('amount'),
                'amount_returned' => $inv->repayments()->sum('amount'),
                'status' => 'active',
                'notes' => 'Backfilled to House Principal.',
            ]);
            $this->line("Backfilled investment #{$inv->id} ({$inv->name})");
        }

        // 2. Disbursements without investment_id
        $houseInvestment = Investment::firstOrCreate(
            ['name' => 'General Lending Book'],
            [
                'type' => 'other',
                'facility_type' => 'long_term',
                'country' => 'Kenya',
                'initial_amount' => 0,
                'current_value' => 0,
                'purchase_date' => now()->toDateString(),
                'status' => 'active',
            ]
        );

        $orphanDisbursements = Disbursement::whereNull('investment_id')->get();
        foreach ($orphanDisbursements as $d) {
            $d->update([
                'investment_id' => $houseInvestment->id,
                'funding_source' => 'internal',
            ]);
        }
        $this->info("Backfilled {$orphanDisbursements->count()} disbursements to 'General Lending Book'.");

        return self::SUCCESS;
    }
}