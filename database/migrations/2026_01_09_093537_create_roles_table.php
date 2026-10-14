<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Superseded: roles are owned by spatie/laravel-permission
 * (2026_09_04_113052_create_permission_tables). Kept as a no-op so
 * `php artisan migrate` doesn't try to create a second roles table,
 * and so a rollback can't drop the real one.
 */
return new class extends Migration
{
    public function up(): void
    {
    }

    public function down(): void
    {
    }
};
