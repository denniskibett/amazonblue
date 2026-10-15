<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('financial_assessments')) {
            return;
        }

        Schema::create('financial_assessments', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_user_id');
            $table->unsignedBigInteger('case_id')->nullable()->index('idx_case_id');
            $table->date('assessment_date');
            $table->decimal('monthly_income', 15)->nullable();
            $table->decimal('monthly_expenses', 15)->nullable();
            $table->decimal('monthly_surplus', 15)->nullable();
            $table->decimal('other_obligations', 15)->nullable();
            $table->integer('dependents_count')->nullable()->default(0);
            $table->unsignedBigInteger('hardship_reason_id')->nullable()->index('hardship_reason_id');
            $table->text('hardship_description')->nullable();
            $table->text('supporting_documents')->nullable();
            $table->text('recommended_plan')->nullable();
            $table->unsignedBigInteger('assessed_by')->nullable()->index('assessed_by');
            $table->boolean('is_approved')->nullable()->default(false);
            $table->date('approval_date')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable()->index('approved_by');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['user_id'], 'financial_assessments_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['case_id'], 'financial_assessments_ibfk_2')->references(['id'])->on('debt_recovery_cases')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['hardship_reason_id'], 'financial_assessments_ibfk_3')->references(['id'])->on('hardship_reasons')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['assessed_by'], 'financial_assessments_ibfk_4')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['approved_by'], 'financial_assessments_ibfk_5')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_assessments');
    }
};
