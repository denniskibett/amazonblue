<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops loan_ledger (Stage 4 Phase 1, owner OK 20 Oct 2026). It never had a writer and has 0 rows on
 * live; the ledger is now kept in bavix wallets (App\Services\Ledger). Refuses if the table holds rows.
 * Same change as database/sql/2026_10_20_drop_loan_ledger.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loan_ledger')) {
            return;
        }
        if (DB::table('loan_ledger')->exists()) {
            throw new RuntimeException('loan_ledger has rows; not dropping it. Export them and ask the owner first.');
        }
        Schema::drop('loan_ledger');
    }

    public function down(): void
    {
        // Not recreated: 2025_01_01_000031_create_loan_ledger_table creates it on a fresh install, and
        // nothing reads or writes it.
    }
};
