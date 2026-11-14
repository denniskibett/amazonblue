<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who shares a facility split (owner, 14 Nov 2026): each party's principal, its principal-plus-interest entitlement,
 * its share of the penalty and what it had already received before counted_from (so later payments catch it up).
 * SQL twin: database/sql/2026_11_14_facility_splits.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('facility_split_parties')) {
            return;
        }
        Schema::create('facility_split_parties', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->foreignId('facility_split_id')->constrained('facility_splits')->cascadeOnDelete();
            $t->foreignId('partner_id')->constrained('partners');
            $t->decimal('principal', 15, 2);
            $t->decimal('pi_entitled', 15, 2)->comment('this party\'s principal plus interest');
            $t->decimal('penalty_pct', 5, 2)->comment('this party\'s % of the penalty');
            $t->decimal('received_before', 15, 2)->default(0)->comment('already received before counted_from');
            $t->timestamps();
            $t->unique(['facility_split_id', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_split_parties');
    }
};
