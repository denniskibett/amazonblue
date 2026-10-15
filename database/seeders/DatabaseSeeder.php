<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Every seeder here is safe to re-run: it only adds what is missing.
     */
    public function run(): void
    {
        // Roles and permissions (spatie/laravel-permission): super-admin, admin, teller, broker, borrower, partner
        $this->call(RoleSeeder::class);

        // Lookup values used by forms and reports, and the loan products
        $this->call(LookupTablesSeeder::class);

        // House partner accounts that fund loans
        $this->call(HouseAccountsSeeder::class);

        // Demo accounts with a known password: never on production
        if (! app()->environment('production')) {
            $this->call(DemoUsersSeeder::class);
        }
    }
}
