<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;

class HouseAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // House Principal Account
        Partner::firstOrCreate(
            ['name' => 'House Principal Account'],
            [
                'email' => 'house.principal@internal',
                'phone' => 'N/A',
                'type' => 'company',
                'account_type' => 'principal',
                'is_house_account' => true,
                'status' => 'active',
                'current_balance' => 0,
                'total_contribution' => 0,
                'total_withdrawn' => 0,
                'profit_share_rate' => 0,
                'notes' => 'Company own capital used to fund loans. No interest is due on this money.',
            ]
        );

        // House Interest Account
        Partner::firstOrCreate(
            ['name' => 'House Interest Account'],
            [
                'email' => 'house.interest@internal',
                'phone' => 'N/A',
                'type' => 'company',
                'account_type' => 'interest',
                'is_house_account' => true,
                'status' => 'active',
                'current_balance' => 0,
                'total_contribution' => 0,
                'total_withdrawn' => 0,
                'profit_share_rate' => 0,
                'notes' => 'Company retained interest. Available for reinvestment. Segregated from partner principal.',
            ]
        );

        $this->command->info('House Principal and Interest accounts ensured.');
    }
}