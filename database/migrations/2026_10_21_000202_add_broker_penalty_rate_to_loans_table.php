<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 3: a brokered loan records the broker's share of penalty next to its share of interest
 * (loans.broker_rate), taken from brokers.penalty_broker when the broker is linked. NULL = not set yet.
 * Same change as database/sql/2026_10_21_phase3_columns.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loans') || Schema::hasColumn('loans', 'broker_penalty_rate')) {
            return;
        }
        Schema::table('loans', function (Blueprint $table) {
            $table->decimal('broker_penalty_rate', 5)->nullable()->after('broker_rate')->comment('Broker share of penalty for this loan, percent');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('loans', 'broker_penalty_rate')) {
            Schema::table('loans', fn (Blueprint $table) => $table->dropColumn('broker_penalty_rate'));
        }
    }
};
