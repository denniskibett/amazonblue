<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('disbursements')) {
            return;
        }

        Schema::create('disbursements', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_id')->index('disbursements_loan_id_foreign');
            $table->unsignedBigInteger('loan_cycle_id')->nullable();
            $table->decimal('amount', 15);
            $table->decimal('processing_fee', 15)->default(0);
            $table->decimal('net_amount', 15)->nullable();
            $table->string('transaction')->nullable();
            $table->string('mode');
            $table->date('disburse_date');
            $table->date('payment_date');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('partner_transaction_id')->nullable()->index('partner_transaction_id');
            $table->enum('funding_source', ['internal', 'partner', 'mixed'])->nullable()->default('internal');
            $table->unsignedBigInteger('investment_id')->nullable()->index('investment_id');
            $table->text('notes')->nullable();

            $table->foreign(['partner_transaction_id'], 'disbursements_ibfk_1')->references(['id'])->on('partner_transactions')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['investment_id'], 'disbursements_ibfk_2')->references(['id'])->on('investments')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['loan_id'])->references(['id'])->on('loans')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disbursements');
    }
};
