<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a recovery action to the Repayment its "amount collected" created, so recovery collections
 * are real repayments (wallet, loan balance) instead of a number on the case.
 * Same change as database/sql/2026_10_18_recovery_action_repayment.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recovery_actions') || Schema::hasColumn('recovery_actions', 'repayment_id')) {
            return;
        }
        Schema::table('recovery_actions', function (Blueprint $table) {
            $table->unsignedBigInteger('repayment_id')->nullable()->after('amount_collected')->index('fk_recovery_action_repayment');
            $table->foreign(['repayment_id'], 'fk_recovery_action_repayment')->references(['id'])->on('repayments')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('recovery_actions', 'repayment_id')) {
            return;
        }
        Schema::table('recovery_actions', function (Blueprint $table) {
            $table->dropForeign('fk_recovery_action_repayment');
            $table->dropColumn('repayment_id');
        });
    }
};
