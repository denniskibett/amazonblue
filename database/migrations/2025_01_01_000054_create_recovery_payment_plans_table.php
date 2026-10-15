<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('recovery_payment_plans')) {
            return;
        }

        Schema::create('recovery_payment_plans', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('case_id')->index('idx_case_id');
            $table->decimal('total_amount', 15)->default(0);
            $table->decimal('down_payment', 15)->default(0);
            $table->decimal('installment_amount', 15)->default(0);
            $table->integer('number_of_installments')->default(0);
            $table->enum('installment_frequency', ['weekly', 'bi_weekly', 'monthly', 'quarterly'])->nullable()->default('monthly');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'proposed', 'accepted', 'rejected', 'completed', 'defaulted'])->nullable()->default('draft')->index('idx_status');
            $table->boolean('agreed_by_debtor')->nullable()->default(false);
            $table->date('agreed_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->unsignedBigInteger('approved_by')->nullable()->index('approved_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['case_id'], 'recovery_payment_plans_ibfk_1')->references(['id'])->on('debt_recovery_cases')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['created_by'], 'recovery_payment_plans_ibfk_2')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['approved_by'], 'recovery_payment_plans_ibfk_3')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_payment_plans');
    }
};
