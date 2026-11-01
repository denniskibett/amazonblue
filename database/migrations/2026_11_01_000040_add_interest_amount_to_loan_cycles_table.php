<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A short payment accepted as the cycle's full amount (owner, 1 Nov 2026): the cycle's interest is set to an exact
 * amount, which a 2-decimal rate cannot always give (19.05% of 21,000 is 4,000.50, not 4,000). NULL = rate × principal.
 * Same change as database/sql/2026_11_01_cycle_interest_amount.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loan_cycles') || Schema::hasColumn('loan_cycles', 'interest_amount')) {
            return;
        }
        Schema::table('loan_cycles', function (Blueprint $table) {
            $table->decimal('interest_amount', 15, 2)->nullable()->after('interest_rate')->comment('Exact interest for the cycle when set; NULL = rate x principal');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('loan_cycles', 'interest_amount')) {
            Schema::table('loan_cycles', fn (Blueprint $table) => $table->dropColumn('interest_amount'));
        }
    }
};
