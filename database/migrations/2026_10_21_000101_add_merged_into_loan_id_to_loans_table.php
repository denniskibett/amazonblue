<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy ROLL OVER chains (owner, 21 Oct 2026): a loan row that only continued an earlier loan is merged into
 * the chain's first loan as more cycles. The continuation row is kept (soft-deleted) and points at the loan it
 * became part of. Same change as database/sql/2026_10_21_loans_merged_into.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loans') || Schema::hasColumn('loans', 'merged_into_loan_id')) {
            return;
        }
        Schema::table('loans', function (Blueprint $table) {
            $table->unsignedBigInteger('merged_into_loan_id')->nullable()->after('id')->index('fk_loans_merged_into');
            $table->foreign(['merged_into_loan_id'], 'fk_loans_merged_into')->references(['id'])->on('loans')->onUpdate('no action')->onDelete('no action');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('loans', 'merged_into_loan_id')) {
            return;
        }
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign('fk_loans_merged_into');
            $table->dropIndex('fk_loans_merged_into');
            $table->dropColumn('merged_into_loan_id');
        });
    }
};
