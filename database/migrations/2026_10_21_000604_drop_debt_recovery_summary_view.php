<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Stage 4 Phase 7: the SQL view `debt_recovery_summary` is dropped. No code reads it; reports read the cycles and the ledger
 * (Services\LedgerReports). Re-run its 2025_01_02 create migration to bring it back.
 * Same change as database/sql/2026_10_21_drop_unused_views.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS `debt_recovery_summary`');
    }

    public function down(): void
    {
        // not recreated here: see the 2025_01_02 migration that creates `debt_recovery_summary`
    }
};
