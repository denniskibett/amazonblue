<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Utility meters (owner, 3 Nov 2026): KPLC meter or account numbers a borrower gives as proof of address, kept as a
 * JSON list on the user, e.g. [{"provider": "kplc", "kind": "prepaid", "number": "37100000000",
 * "name_on_account": "Peter Kamau", "address": "Kasarani, house 14", "source": "kyc", "added_at": "2026-11-03"}].
 * Captured with the borrower's consent; it records where they live, it is not a way to look them up at KPLC.
 * SQL twin: database/sql/2026_11_03_users_utility_meters.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'utility_meters')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->json('utility_meters')->nullable()->after('kyc_checks');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'utility_meters')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('utility_meters');
            });
        }
    }
};
