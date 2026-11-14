<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixed-return lines (owner, 14 Nov 2026): the investor is owed a set % for a set number of days (Yvonne: 100,000
 * for 10 days at 8% = 108,000), whatever the loans the money funds earn. investments.interest_rate holds that %,
 * duration_days the days, maturity_date the due day, expected_return the amount. SQL twin:
 * database/sql/2026_11_15_fixed_returns.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('investments', 'return_type')) {
            return;
        }
        $type = DB::selectOne("SELECT COLUMN_TYPE AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'investments' AND COLUMN_NAME = 'return_type'");
        if ($type && ! str_contains((string) $type->t, 'fixed_return')) {
            DB::statement("ALTER TABLE `investments` MODIFY `return_type` ENUM('house','loan_share','fixed_rate','fixed_return') NOT NULL DEFAULT 'house'");
        }
    }

    public function down(): void
    {
        // left as it is: lines may already use fixed_return
    }
};
