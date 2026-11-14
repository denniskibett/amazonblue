<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A facility's own split (owner, 14 Nov 2026; first for loan 458, the ISIRO facility): from `counted_from`, the cash
 * collected on the loan first settles principal plus interest (`pi_target`) between the parties, then everything up
 * to `total_cap` is penalty, shared by each party's penalty_pct. It replaces the normal split for that loan's
 * payments from that day. The parties are in facility_split_parties. SQL twin: database/sql/2026_11_14_facility_splits.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('facility_splits')) {
            return;
        }
        Schema::create('facility_splits', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->foreignId('loan_id')->unique()->constrained('loans')->cascadeOnDelete();
            $t->decimal('pi_target', 15, 2)->comment('principal plus interest the parties share first');
            $t->decimal('total_cap', 15, 2)->comment('everything collected up to here counts; above pi_target it is penalty');
            $t->date('counted_from')->comment('payments on or after this day are split by this rule');
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_splits');
    }
};
