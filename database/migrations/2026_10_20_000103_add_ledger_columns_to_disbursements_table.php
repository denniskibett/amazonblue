<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 1: disbursements say what they are (kind, as on repayments) and who entered them (created_by).
 * Same change as database/sql/2026_10_20_money_row_columns.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('disbursements')) {
            return;
        }
        if (! Schema::hasColumn('disbursements', 'kind')) {
            Schema::table('disbursements', function (Blueprint $table) {
                $table->string('kind', 16)->nullable()->after('transaction')->index('idx_disbursements_kind');
            });
        }
        if (! Schema::hasColumn('disbursements', 'created_by')) {
            Schema::table('disbursements', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->after('notes')->index('fk_disbursements_created_by');
                $table->foreign(['created_by'], 'fk_disbursements_created_by')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('disbursements', 'created_by')) {
            Schema::table('disbursements', function (Blueprint $table) {
                $table->dropForeign('fk_disbursements_created_by');
                $table->dropIndex('fk_disbursements_created_by');
                $table->dropColumn('created_by');
            });
        }
        if (Schema::hasColumn('disbursements', 'kind')) {
            Schema::table('disbursements', function (Blueprint $table) {
                $table->dropIndex('idx_disbursements_kind');
                $table->dropColumn('kind');
            });
        }
    }
};
