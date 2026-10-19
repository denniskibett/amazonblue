<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds 'written_off' to loans.status (Loan::STATUS_WRITTEN_OFF). A recovery write-off now sets this
 * status instead of 'repaid', so written-off debt is no longer reported as repaid.
 * Same change as database/sql/2026_10_18_loan_written_off_status.sql.
 */
return new class extends Migration
{
    private const WITH = "ENUM('pending','approved','disbursed','rejected','repaid','overdue','defaulted','recovery','forbearance','written_off') NOT NULL DEFAULT 'pending'";
    private const WITHOUT = "ENUM('pending','approved','disbursed','rejected','repaid','overdue','defaulted','recovery','forbearance') NOT NULL DEFAULT 'pending'";

    public function up(): void
    {
        if (! Schema::hasColumn('loans', 'status')) {
            return;
        }
        $type = DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'loans')
            ->where('column_name', 'status')
            ->value('column_type');

        if ($type !== null && ! str_contains($type, "'written_off'")) {
            DB::statement('ALTER TABLE `loans` MODIFY `status` ' . self::WITH);
        }
    }

    public function down(): void
    {
        // Written-off loans keep their history; only shrink the enum when none use the value.
        if (DB::table('loans')->where('status', 'written_off')->exists()) {
            return;
        }
        DB::statement('ALTER TABLE `loans` MODIFY `status` ' . self::WITHOUT);
    }
};
