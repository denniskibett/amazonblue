<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed all lookup tables first
        $this->call(LookupTablesSeeder::class);

        // Create admin user
        \App\Models\User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@amazonblue.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 0,
            'phone' => '+254700000000',
            'gender' => 'male',
            'dob' => '1990-01-01',
            'nationality' => 'KE',
            'marital_status' => 'single',
            'id_type' => 'national_id',
            'id_number' => '12345678',
        ]);

        // Create test borrower
        $borrower = \App\Models\User::factory()->create([
            'name' => 'Test Borrower',
            'email' => 'borrower@amazonblue.com',
            'password' => Hash::make('password'),
            'role' => 'borrower',
            'status' => 0,
            'phone' => '+254711111111',
            'gender' => 'male',
            'dob' => '1995-06-15',
            'nationality' => 'KE',
            'marital_status' => 'married',
            'religion' => 'Christian',
            'education' => "Bachelor's Degree",
            'disability' => 0,
            'kin_name' => 'Jane Doe',
            'kin_email' => 'jane@example.com',
            'kin_phone' => '+254722222222',
            'kin_occupation' => 'Teacher',
            'kin_relation' => 'Spouse',
            'kin_id_type' => 'national_id',
            'kin_id_number' => '87654321',
            'id_type' => 'national_id',
            'id_number' => '123456789',
        ]);

        // Create borrower profile
        \App\Models\UserProfile::create([
            'user_id' => $borrower->id,
            'current_residence' => 'Nairobi, Kenya',
            'current_residence_from' => '2020-01-01',
            'residence_type_id' => \App\Models\ResidenceType::where('slug', 'rented')->first()?->id,
            'general_notes' => 'Test borrower profile',
            'created_by' => 1,
        ]);

        // Create borrower record
        \App\Models\Borrower::create([
            'user_id' => $borrower->id,
            'client_type' => 0,
            'status' => 1,
            'income_type' => 'employed',
            'gross_salary' => 150000,
            'net_salary' => 120000,
            'job_title' => 'Software Engineer',
            'workplace' => 'Tech Company',
            'employer_name' => 'AmazonBlue Capital',
            'employer_email' => 'hr@amazonblue.com',
            'department' => 'Engineering',
        ]);

        // Create test broker
        \App\Models\User::factory()->create([
            'name' => 'Test Broker',
            'email' => 'broker@amazonblue.com',
            'password' => Hash::make('password'),
            'role' => 'broker',
            'status' => 0,
            'phone' => '+254733333333',
            'gender' => 'female',
            'dob' => '1988-03-20',
            'nationality' => 'KE',
            'marital_status' => 'single',
            'id_type' => 'national_id',
            'id_number' => '23456789',
        ]);

        // Create test teller
        \App\Models\User::factory()->create([
            'name' => 'Test Teller',
            'email' => 'teller@amazonblue.com',
            'password' => Hash::make('password'),
            'role' => 'teller',
            'status' => 0,
            'phone' => '+254744444444',
            'gender' => 'male',
            'dob' => '1992-11-10',
            'nationality' => 'KE',
            'marital_status' => 'married',
            'id_type' => 'national_id',
            'id_number' => '34567890',
        ]);

        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->info('👤 Admin: admin@amazonblue.com / password');
        $this->command->info('👤 Borrower: borrower@amazonblue.com / password');
        $this->command->info('👤 Broker: broker@amazonblue.com / password');
        $this->command->info('👤 Teller: teller@amazonblue.com / password');
    }
}