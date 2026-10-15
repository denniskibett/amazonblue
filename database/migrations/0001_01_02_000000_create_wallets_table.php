<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * bavix/laravel-wallet table: wallets
 */
return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('wallets')) {
            return;
        }

        Schema::create('wallets', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->string('holder_type');
            $table->unsignedBigInteger('holder_id');
            $table->string('name');
            $table->string('slug')->index();
            $table->char('uuid', 36)->unique();
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->decimal('balance', 64, 0)->default(0);
            $table->unsignedSmallInteger('decimal_places')->default(2);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['holder_type', 'holder_id']);
            $table->unique(['holder_type', 'holder_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
