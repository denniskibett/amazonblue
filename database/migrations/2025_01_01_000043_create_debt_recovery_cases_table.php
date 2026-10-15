<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('debt_recovery_cases')) {
            return;
        }

        Schema::create('debt_recovery_cases', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_user_id');
            $table->unsignedBigInteger('loan_id')->nullable()->index('idx_loan_id');
            $table->string('case_number', 50)->unique('unique_case_number');
            $table->decimal('total_debt_amount', 15)->default(0);
            $table->decimal('principal_outstanding', 15)->default(0);
            $table->decimal('interest_outstanding', 15)->default(0);
            $table->decimal('penalty_outstanding', 15)->default(0);
            $table->decimal('fees_outstanding', 15)->default(0);
            $table->date('default_date')->index('idx_default_date');
            $table->integer('days_in_default')->default(0);
            $table->unsignedBigInteger('status_id')->index('idx_status_id');
            $table->unsignedBigInteger('priority_id')->index('idx_priority_id');
            $table->unsignedBigInteger('assigned_to')->nullable()->index('assigned_to');
            $table->date('last_contact_date')->nullable();
            $table->date('next_action_date')->nullable();
            $table->text('recovery_strategy')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->unsignedBigInteger('updated_by')->nullable()->index('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['user_id'], 'debt_recovery_cases_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['loan_id'], 'debt_recovery_cases_ibfk_2')->references(['id'])->on('loans')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['status_id'], 'debt_recovery_cases_ibfk_3')->references(['id'])->on('recovery_statuses')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['priority_id'], 'debt_recovery_cases_ibfk_4')->references(['id'])->on('recovery_priorities')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['assigned_to'], 'debt_recovery_cases_ibfk_5')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['created_by'], 'debt_recovery_cases_ibfk_6')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['updated_by'], 'debt_recovery_cases_ibfk_7')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_recovery_cases');
    }
};
