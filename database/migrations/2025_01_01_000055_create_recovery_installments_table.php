<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('recovery_installments')) {
            return;
        }

        Schema::create('recovery_installments', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_plan_id')->index('idx_payment_plan_id');
            $table->integer('installment_number');
            $table->date('due_date')->index('idx_due_date');
            $table->decimal('amount', 15);
            $table->decimal('paid_amount', 15)->default(0);
            $table->date('payment_date')->nullable();
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'waived'])->nullable()->default('pending')->index('idx_status');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['payment_plan_id'], 'recovery_installments_ibfk_1')->references(['id'])->on('recovery_payment_plans')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_installments');
    }
};
