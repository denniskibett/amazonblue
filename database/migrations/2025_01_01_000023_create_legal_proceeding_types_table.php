<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('legal_proceeding_types')) {
            return;
        }

        Schema::create('legal_proceeding_types', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->string('slug', 100)->unique('unique_slug');
            $table->text('description')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->integer('sort_order')->nullable()->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_proceeding_types');
    }
};
