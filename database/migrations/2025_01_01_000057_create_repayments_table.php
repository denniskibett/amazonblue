<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('repayments')) {
            return;
        }

        Schema::create('repayments', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_id')->index('repayments_loan_id_foreign');
            $table->unsignedBigInteger('loan_cycle_id')->nullable()->index('idx_repayments_loan_cycle_id');
            $table->decimal('amount', 15);
            $table->decimal('processing_fee', 15)->default(0);
            $table->decimal('net_amount', 15)->nullable();
            $table->string('transaction')->nullable();
            $table->string('mode', 100)->nullable();
            $table->date('repayment_date');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('partner_transaction_id')->nullable()->index('partner_transaction_id');
            $table->unsignedBigInteger('investment_id')->nullable()->index('investment_id');
            $table->text('notes')->nullable();

            $table->foreign(['loan_cycle_id'], 'fk_repayments_loan_cycle')->references(['id'])->on('loan_cycles')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['partner_transaction_id'], 'repayments_ibfk_1')->references(['id'])->on('partner_transactions')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['investment_id'], 'repayments_ibfk_2')->references(['id'])->on('investments')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['loan_id'])->references(['id'])->on('loans')->onUpdate('no action')->onDelete('cascade');
        });

        // partner_transactions also references repayments (the two tables point at each other),
        // so that key is added here, once both tables exist.
        Schema::table('partner_transactions', function (Blueprint $table) {
            $table->foreign(['repayment_id'], 'partner_transactions_ibfk_3')->references(['id'])->on('repayments')->onUpdate('no action')->onDelete('no action');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('partner_transactions')) {
            Schema::table('partner_transactions', function (Blueprint $table) {
                $table->dropForeign('partner_transactions_ibfk_3');
            });
        }

        Schema::dropIfExists('repayments');
    }
};
