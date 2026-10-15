<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('loan_cycles')) {
            return;
        }

        Schema::create('loan_cycles', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_id')->index('idx_loan_cycles_loan_id');
            $table->integer('cycle_number')->default(1)->index('idx_loan_cycles_cycle_number');
            $table->decimal('previous_balance', 15)->default(0);
            $table->decimal('interest_capitalized', 15)->default(0);
            $table->decimal('new_balance', 15)->default(0);
            $table->decimal('interest_rate', 5)->default(0);
            $table->date('start_date');
            $table->date('due_date')->index('idx_loan_cycles_due_date');
            $table->enum('status', ['active', 'completed', 'defaulted', 'repaid'])->default('active')->index('idx_loan_cycles_status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['loan_id', 'status'], 'idx_loan_cycle_active');
            $table->index(['loan_id', 'start_date', 'due_date'], 'idx_loan_cycle_dates');
            $table->unique(['loan_id', 'cycle_number'], 'uk_loan_cycle_number');

            $table->foreign(['loan_id'], 'fk_loan_cycles_loan')->references(['id'])->on('loans')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_cycles');
    }
};
