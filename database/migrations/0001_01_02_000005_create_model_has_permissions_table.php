<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spatie/laravel-permission table: model_has_permissions
 */
return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('model_has_permissions')) {
            return;
        }

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type']);
            $table->primary(['permission_id', 'model_id', 'model_type']);

            $table->foreign(['permission_id'])->references(['id'])->on('permissions')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_permissions');
    }
};
