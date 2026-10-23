<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Borrowers can ask for a top-up (owner, 23 Oct 2026). SQL twin: database/sql/2026_10_23_borrower_requests_topup.sql. */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE borrower_requests MODIFY `type` ENUM('extension','plan','help','topup') COLLATE utf8mb4_unicode_ci NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE borrower_requests MODIFY `type` ENUM('extension','plan','help') COLLATE utf8mb4_unicode_ci NOT NULL");
    }
};
