<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ============ PERMISSIONS ============
        // Use firstOrCreate to avoid duplicates
        $permissions = [
            // Loan Permissions
            'view loans',
            'create loans',
            'edit loans',
            'delete loans',
            'approve loans',
            'disburse loans',
            'rollover loans',
            
            // User Permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage roles',
            
            // Report Permissions
            'view reports',
            'export reports',
            
            // Disbursement Permissions
            'view disbursements',
            'create disbursements',
            'edit disbursements',
            'delete disbursements',
            
            // Repayment Permissions
            'view repayments',
            'create repayments',
            'edit repayments',
            'delete repayments',
            
            // Recovery Permissions
            'view recovery cases',
            'create recovery cases',
            'edit recovery cases',
            'delete recovery cases',
            
            // Partner Permissions
            'view partners',
            'create partners',
            'edit partners',
            'delete partners',
            
            // Investment Permissions
            'view investments',
            'create investments',
            'edit investments',
            'delete investments',
            
            // Dashboard Permissions
            'view dashboard',
            
            // Forbearance Permissions
            'grant forbearance',
            'end forbearance',
            
            // System Permissions
            'access system settings',
            'clear cache',
            'view logs',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['guard_name' => 'web']
            );
        }

        // ============ ROLES ============
        
        // Super Admin - Has all permissions
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            ['guard_name' => 'web']
        );
        $superAdmin->syncPermissions(Permission::all());
        
        // Admin - Most permissions except system settings
        $admin = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['guard_name' => 'web']
        );
        $admin->syncPermissions([
            'view loans', 'create loans', 'edit loans', 'delete loans', 'approve loans', 'disburse loans', 'rollover loans',
            'view users', 'create users', 'edit users', 'delete users',
            'view reports', 'export reports',
            'view disbursements', 'create disbursements', 'edit disbursements', 'delete disbursements',
            'view repayments', 'create repayments', 'edit repayments', 'delete repayments',
            'view recovery cases', 'create recovery cases', 'edit recovery cases', 'delete recovery cases',
            'view partners', 'create partners', 'edit partners', 'delete partners',
            'view investments', 'create investments', 'edit investments', 'delete investments',
            'view dashboard',
            'grant forbearance', 'end forbearance',
        ]);
        
        // Teller - Can manage loans, disbursements, repayments
        $teller = Role::firstOrCreate(
            ['name' => 'teller', 'guard_name' => 'web'],
            ['guard_name' => 'web']
        );
        $teller->syncPermissions([
            'view loans', 'create loans', 'edit loans', 'disburse loans',
            'view disbursements', 'create disbursements',
            'view repayments', 'create repayments',
            'view dashboard',
        ]);
        
        // Broker - Can view their clients' loans and create loans for them
        $broker = Role::firstOrCreate(
            ['name' => 'broker', 'guard_name' => 'web'],
            ['guard_name' => 'web']
        );
        $broker->syncPermissions([
            'view loans', 'create loans', 'edit loans',
            'view disbursements', 'view repayments',
            'view dashboard',
            'view users',
        ]);
        
        // Borrower - Can view their own loans and make repayments
        $borrower = Role::firstOrCreate(
            ['name' => 'borrower', 'guard_name' => 'web'],
            ['guard_name' => 'web']
        );
        $borrower->syncPermissions([
            'view loans',
            'view repayments',
            'view dashboard',
        ]);
        
        // Partner - Can view investments and their own data
        $partner = Role::firstOrCreate(
            ['name' => 'partner', 'guard_name' => 'web'],
            ['guard_name' => 'web']
        );
        $partner->syncPermissions([
            'view partners',
            'view investments',
            'view dashboard',
        ]);

        // ============ ASSIGN SUPER ADMIN TO FIRST USER ============
        // Optionally assign super-admin role to the first user (admin@example.com)
        $user = \App\Models\User::where('email', 'admin@example.com')->first();
        if ($user) {
            $user->assignRole('super-admin');
        }
    }
}