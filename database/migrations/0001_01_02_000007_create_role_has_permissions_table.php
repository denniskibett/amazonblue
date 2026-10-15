<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spatie/laravel-permission table: role_has_permissions
 */
return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('role_has_permissions')) {
            return;
        }

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id')->index('role_has_permissions_role_id_foreign');

            $table->primary(['permission_id', 'role_id']);

            $table->foreign(['permission_id'])->references(['id'])->on('permissions')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['role_id'])->references(['id'])->on('roles')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
    }
};
