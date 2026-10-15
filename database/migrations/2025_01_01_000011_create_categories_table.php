<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('categories')) {
            return;
        }

        Schema::create('categories', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->string('name');
            $table->string('category_type');
            $table->unsignedBigInteger('categoryable_id');
            $table->timestamps();

            $table->index(['category_type', 'categoryable_id'], 'categories_categoryable_type_categoryable_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
