<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * users.role becomes a plain string so it can mirror any Spatie role (manager, auditor, ...).
 * Spatie roles are the source of truth; this column only mirrors the user's primary role
 * (User::syncLegacyRole). Same change as database/sql/2026_10_18_users_role_string.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }
        $type = DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'users')
            ->where('column_name', 'role')
            ->value('column_type');

        if ($type !== null && str_starts_with(strtolower($type), 'enum')) {
            DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(50) NULL DEFAULT NULL");
        }
    }

    public function down(): void
    {
        // Values outside the old enum (manager, auditor, ...) would be lost, so the column stays a string.
    }
};
