<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('loan_types')) {
            return;
        }

        Schema::create('loan_types', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->string('name');
            $table->integer('period');
            $table->string('unit');
            $table->decimal('interest_rate', 5);
            $table->decimal('penalty_rate', 5);
            $table->integer('grace_period_days')->default(0);
            $table->integer('default_threshold_days')->default(30);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_types');
    }
};
