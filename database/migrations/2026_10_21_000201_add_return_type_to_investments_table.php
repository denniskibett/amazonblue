<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 3: what a funding line pays its partner (owner, 21 Oct 2026).
 *   house       AC's own money; AC keeps what is left after brokers.
 *   loan_share  a partner or broker funds AC's loans and earns a share of their interest
 *               (a broker: 100 - brokers.interest_broker); AC guarantees the principal.
 *   fixed_rate  a long-term partner earns investments.interest_rate % a month, simple, on the committed amount,
 *               from the 1st of the month after funding, whole calendar months only, locked for the term.
 * Same change as database/sql/2026_10_21_phase3_columns.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('investments') || Schema::hasColumn('investments', 'return_type')) {
            return;
        }
        Schema::table('investments', function (Blueprint $table) {
            $table->enum('return_type', ['house', 'loan_share', 'fixed_rate'])->default('house')->after('facility_type');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('investments', 'return_type')) {
            Schema::table('investments', fn (Blueprint $table) => $table->dropColumn('return_type'));
        }
    }
};
