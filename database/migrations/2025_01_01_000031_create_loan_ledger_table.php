<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('loan_ledger')) {
            return;
        }

        Schema::create('loan_ledger', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_id')->index('idx_loan_id');
            $table->enum('entry_type', ['debit', 'credit', 'interest', 'penalty', 'fee', 'adjustment'])->index('idx_entry_type');
            $table->decimal('amount', 15);
            $table->decimal('balance_after', 15);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->string('description')->nullable();
            $table->date('entry_date')->index('idx_entry_date');
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->timestamp('created_at')->nullable();

            $table->foreign(['loan_id'], 'loan_ledger_ibfk_1')->references(['id'])->on('loans')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['created_by'], 'loan_ledger_ibfk_2')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_ledger');
    }
};
