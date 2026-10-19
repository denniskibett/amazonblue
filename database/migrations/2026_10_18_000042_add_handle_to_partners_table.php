<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wallet handle (rule 9), e.g. @denniskibet: lowercase letters/digits, unique, set from the name by the
 * model (App\Support\Handle) and backfilled with `php artisan handles:backfill`.
 * Same change as database/sql/2026_10_18_wallet_handles.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('partners', 'handle')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->string('handle', 64)->nullable()->after('name')->unique('partners_handle_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('partners', 'handle')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->dropUnique('partners_handle_unique');
                $table->dropColumn('handle');
            });
        }
    }
};
