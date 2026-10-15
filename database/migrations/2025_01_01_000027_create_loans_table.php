<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('loans')) {
            return;
        }

        Schema::create('loans', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('loans_user_id_foreign');
            $table->unsignedBigInteger('loan_type_id')->index('loans_loan_type_id_foreign');
            $table->decimal('amount', 15);
            $table->decimal('processing_fee_rate', 5)->default(0);
            $table->decimal('total_processing_fees', 15)->default(0);
            $table->date('borrow_date');
            $table->tinyInteger('broker_status')->default(0);
            $table->unsignedBigInteger('partner_id')->nullable()->index('loans_partner_id_idx')->comment('Broker/partner of record when broker_status=1; null for pure house loans');
            $table->decimal('broker_rate', 5)->default(40)->comment('Broker share of interest for this loan, percent');
            $table->enum('status', ['pending', 'approved', 'disbursed', 'rejected', 'repaid', 'overdue', 'defaulted', 'recovery', 'forbearance'])->default('pending');
            $table->integer('cycle')->default(1);
            $table->decimal('original_amount', 15)->nullable();
            $table->decimal('capitalized_interest', 15)->default(0);
            $table->integer('grace_period_days')->default(0);
            $table->date('grace_period_end_date')->nullable();
            $table->integer('grace_days_balance')->default(0);
            $table->integer('grace_days_earned')->default(0);
            $table->integer('grace_days_used')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('guarantor_id')->nullable()->index('fk_loans_guarantor');
            $table->string('guarantor_relationship')->nullable();
            $table->unsignedBigInteger('loan_officer_id')->nullable()->index('fk_loans_officer');
            $table->boolean('consent')->nullable()->default(false);
            $table->timestamp('consent_date')->nullable();
            $table->text('reason')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_non_performing')->nullable()->default(false)->comment('Flag for NPL status');
            $table->date('default_date')->nullable()->comment('Date when loan went into default');
            $table->integer('days_in_default')->default(0);
            $table->timestamp('default_triggered_at')->nullable();
            $table->timestamp('recovery_started_at')->nullable();
            $table->timestamp('forbearance_until')->nullable();
            $table->text('recovery_notes')->nullable();
            $table->integer('days_overdue')->nullable()->default(0)->comment('Number of days overdue');
            $table->dateTime('last_overdue_check')->nullable()->comment('Last time overdue status was checked');
            $table->boolean('default_triggered')->nullable()->default(false)->comment('Whether NPL trigger has fired');
            $table->date('calculated_due_date')->nullable()->comment('Calculated due date (derived from loan_types)');
            $table->integer('npl_trigger_threshold')->nullable()->default(0)->comment('Days overdue when NPL triggered');

            $table->foreign(['guarantor_id'], 'fk_loans_guarantor')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['loan_officer_id'], 'fk_loans_officer')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['loan_type_id'])->references(['id'])->on('loan_types')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['partner_id'], 'loans_partner_id_fk')->references(['id'])->on('partners')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
