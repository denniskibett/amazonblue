<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 4: every M-Pesa payment Daraja tells us about (C2B confirmation or STK callback), stored raw
 * before anything is done with it. trans_id (the M-Pesa receipt) is unique, so a repeated callback can never
 * record a payment twice. Same change as database/sql/2026_10_21_mpesa_tables.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mpesa_transactions')) {
            return;
        }
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';
            $table->bigIncrements('id');
            $table->enum('type', ['c2b', 'stk']);
            $table->string('trans_id', 32)->nullable()->unique('uq_mpesa_trans_id')->comment('M-Pesa receipt, e.g. UJ7CQ9KQCR');
            $table->string('checkout_request_id', 100)->nullable()->unique('uq_mpesa_checkout');
            $table->string('msisdn', 64)->nullable()->comment('payer phone as sent (may be hashed by Safaricom)');
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('bill_ref', 64)->nullable()->comment('account number the payer typed');
            $table->string('shortcode', 20)->nullable();
            $table->dateTime('trans_time')->nullable();
            $table->unsignedBigInteger('loan_id')->nullable()->index('fk_mpesa_loan');
            $table->unsignedBigInteger('repayment_id')->nullable()->index('fk_mpesa_repayment');
            $table->enum('status', ['pending', 'received', 'matched', 'unmatched', 'duplicate', 'failed', 'reversed'])->default('received')->index('idx_mpesa_status');
            $table->string('note')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();

            $table->foreign(['loan_id'], 'fk_mpesa_loan')->references(['id'])->on('loans')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['repayment_id'], 'fk_mpesa_repayment')->references(['id'])->on('repayments')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};
