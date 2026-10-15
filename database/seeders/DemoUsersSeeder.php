<?php

namespace Database\Seeders;

use App\Models\Borrower;
use App\Models\Broker;
use App\Models\Teller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo accounts for local development, one per role (password: "password").
 * DatabaseSeeder only runs this outside production.
 *
 * Each account gets its role the same way real accounts do: the Spatie role
 * (model_has_roles, which drives permissions), the legacy users.role column kept
 * in step, and the matching role record (borrowers / brokers / tellers).
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        // super-admin has every permission, including system settings
        $this->account('super-admin', 'admin', [
            'name' => 'Admin User',
            'email' => 'admin@amazonblue.com',
            'phone' => '+254700000000',
        ]);

        $borrower = $this->account('borrower', 'borrower', [
            'name' => 'Test Borrower',
            'email' => 'borrower@amazonblue.com',
            'phone' => '+254711111111',
            'gender' => 'male',
            'dob' => '1995-06-15',
            'nationality' => 'Kenyan',
            'marital_status' => 'married',
            'kin_name' => 'Jane Doe',
            'kin_phone' => '+254722222222',
            'kin_relation' => 'Spouse',
            'id_type' => 'national_id',
            'id_number' => '12345679',
        ]);
        // individual borrower, as created by the public sign-up
        Borrower::firstOrCreate(['user_id' => $borrower->id], [
            'client_type' => 'individual',
            'status' => 1,
            'income_type' => 'employed',
            'gross_salary' => 150000,
            'net_salary' => 120000,
            'job_title' => 'Software Engineer',
            'workplace' => 'Tech Company',
            'employer_name' => 'Tech Company',
        ]);
        UserProfile::firstOrCreate(['user_id' => $borrower->id], [
            'current_residence' => 'Nairobi, Kenya',
            'current_residence_from' => '2020-01-01',
            'residence_type_id' => DB::table('residence_types')->where('slug', 'rented')->value('id'),
            'general_notes' => 'Demo borrower profile',
        ]);

        $broker = $this->account('broker', 'broker', [
            'name' => 'Test Broker',
            'email' => 'broker@amazonblue.com',
            'phone' => '+254733333333',
        ]);
        Broker::firstOrCreate(['user_id' => $broker->id], [
            'interest_client' => 0,
            'interest_broker' => 0,
            'penalty_client' => 0,
            'penalty_broker' => 0,
            'cert_no' => 'DEMO-001',
        ]);

        $teller = $this->account('teller', 'teller', [
            'name' => 'Test Teller',
            'email' => 'teller@amazonblue.com',
            'phone' => '+254744444444',
        ]);
        Teller::firstOrCreate(['user_id' => $teller->id], ['branch' => 'main']);

        $this->command?->info('Demo accounts (password "password"): admin@, borrower@, broker@, teller@amazonblue.com');
    }

    /**
     * Create the account once (re-running the seeder leaves it as it is) and give it its role.
     */
    private function account(string $role, string $legacyRole, array $attributes): User
    {
        $user = User::firstOrCreate(['email' => $attributes['email']], $attributes + [
            'password' => Hash::make('password'),
            'role' => $legacyRole, // legacy column, kept in step with the Spatie role
            'status' => 0,
        ]);

        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
        $user->syncRoles([$role]);

        return $user;
    }
}
