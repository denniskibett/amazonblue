<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 5: a teller's cash-up for a day: expected cash by mode (from the repayments they entered),
 * what they counted, the variance, and the manager who approved it.
 * Same change as database/sql/2026_10_21_phase5_tables.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teller_day_closes')) {
            return;
        }
        Schema::create('teller_day_closes', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('teller_id');
            $table->date('day');
            $table->json('expected');
            $table->json('counted');
            $table->decimal('variance', 15, 2);
            $table->text('note')->nullable();
            $table->enum('status', ['closed', 'approved'])->default('closed');
            $table->unsignedBigInteger('closed_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['teller_id', 'day'], 'uq_teller_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teller_day_closes');
    }
};
