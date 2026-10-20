<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 4: a borrower's "I've paid" (M-Pesa code, amount, phone), stored so its status is the same on
 * every device: checking -> received (matched to a repayment) or rejected (by staff, with a reason).
 * Same change as database/sql/2026_10_21_mpesa_tables.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_claims')) {
            return;
        }
        Schema::create('payment_claims', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('fk_claims_user');
            $table->unsignedBigInteger('loan_id')->index('fk_claims_loan');
            $table->string('code', 32)->index('idx_claims_code');
            $table->decimal('amount', 15, 2);
            $table->string('phone', 32)->nullable();
            $table->enum('status', ['checking', 'received', 'rejected'])->default('checking')->index('idx_claims_status');
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('repayment_id')->nullable()->index('fk_claims_repayment');
            $table->unsignedBigInteger('mpesa_transaction_id')->nullable()->index('fk_claims_mpesa');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamps();
            $table->unique(['loan_id', 'code'], 'uq_claims_loan_code');

            $table->foreign(['user_id'], 'fk_claims_user')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['loan_id'], 'fk_claims_loan')->references(['id'])->on('loans')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['repayment_id'], 'fk_claims_repayment')->references(['id'])->on('repayments')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['mpesa_transaction_id'], 'fk_claims_mpesa')->references(['id'])->on('mpesa_transactions')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_claims');
    }
};
