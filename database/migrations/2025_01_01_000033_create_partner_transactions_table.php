<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('partner_transactions')) {
            return;
        }

        Schema::create('partner_transactions', function (Blueprint $table) {
            $table->collation = 'utf8mb3_general_ci';
            $table->charset = 'utf8mb3';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('partner_id');
            $table->string('wallet_slug', 32)->default('principal')->comment('principal|interest|broker_fee|investment_tracker|borrower_loan');
            $table->string('type', 32)->comment('contribution|withdrawal|principal_deployed|principal_returned|profit_distribution|broker_fee_earned|penalty_share|bonus|repayment|facility_funded|facility_settled|adjustment');
            $table->decimal('amount', 19, 4)->comment('Signed from partner perspective: credit=+, debit=-');
            $table->decimal('balance_after', 19, 4)->nullable()->comment('Snapshot only; bavix wallet is source of truth');
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('loan_id')->nullable()->index('pt_loan_idx');
            $table->unsignedBigInteger('repayment_id')->nullable()->index('pt_repayment_idx');
            $table->unsignedBigInteger('investment_id')->nullable()->index('pt_investment_idx');
            $table->unsignedBigInteger('investment_funding_id')->nullable()->index('pt_investment_funding_idx');
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->timestamp('posted_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'wallet_slug', 'transaction_date'], 'pt_partner_wallet_date_idx');
            $table->unique(['reference', 'wallet_slug'], 'pt_reference_wallet_unique');

            $table->foreign(['partner_id'], 'partner_transactions_ibfk_1')->references(['id'])->on('partners')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['loan_id'], 'partner_transactions_ibfk_2')->references(['id'])->on('loans')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['investment_id'], 'partner_transactions_ibfk_4')->references(['id'])->on('investments')->onUpdate('no action')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_transactions');
    }
};
