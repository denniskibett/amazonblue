<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 1: money rows say what they are and who entered them.
 *  - kind: cash | rollover | discount | bad_debt | non_cash, stored instead of guessed from the reference
 *    text (CustomerWallets::kind is the fallback while a row is NULL; money:backfill-columns fills them).
 *  - created_by: the user who entered the row (backfilled from the audit_logs create rows).
 *  - reversal_of_id: a reversal is a new row pointing at the original; unique, so a row is reversed once.
 * Same change as database/sql/2026_10_20_money_row_columns.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('repayments')) {
            return;
        }
        if (! Schema::hasColumn('repayments', 'kind')) {
            Schema::table('repayments', function (Blueprint $table) {
                $table->string('kind', 16)->nullable()->after('transaction')->index('idx_repayments_kind');
            });
        }
        if (! Schema::hasColumn('repayments', 'created_by')) {
            Schema::table('repayments', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->after('notes')->index('fk_repayments_created_by');
                $table->foreign(['created_by'], 'fk_repayments_created_by')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            });
        }
        if (! Schema::hasColumn('repayments', 'reversal_of_id')) {
            Schema::table('repayments', function (Blueprint $table) {
                $table->unsignedBigInteger('reversal_of_id')->nullable()->after('created_by')->unique('uq_repayments_reversal_of');
                $table->foreign(['reversal_of_id'], 'fk_repayments_reversal_of')->references(['id'])->on('repayments')->onUpdate('no action')->onDelete('no action');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('repayments', 'reversal_of_id')) {
            Schema::table('repayments', function (Blueprint $table) {
                $table->dropForeign('fk_repayments_reversal_of');
                $table->dropUnique('uq_repayments_reversal_of');
                $table->dropColumn('reversal_of_id');
            });
        }
        if (Schema::hasColumn('repayments', 'created_by')) {
            Schema::table('repayments', function (Blueprint $table) {
                $table->dropForeign('fk_repayments_created_by');
                $table->dropIndex('fk_repayments_created_by');
                $table->dropColumn('created_by');
            });
        }
        if (Schema::hasColumn('repayments', 'kind')) {
            Schema::table('repayments', function (Blueprint $table) {
                $table->dropIndex('idx_repayments_kind');
                $table->dropColumn('kind');
            });
        }
    }
};
