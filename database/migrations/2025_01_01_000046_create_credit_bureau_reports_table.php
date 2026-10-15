<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('credit_bureau_reports')) {
            return;
        }

        Schema::create('credit_bureau_reports', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_user_id');
            $table->unsignedBigInteger('case_id')->nullable()->index('case_id');
            $table->unsignedBigInteger('bureau_name_id')->index('idx_bureau_name_id');
            $table->enum('report_type', ['initial', 'updated', 'dispute', 'clearance'])->nullable()->default('initial');
            $table->date('report_date');
            $table->string('report_reference', 100)->nullable();
            $table->integer('credit_score')->nullable();
            $table->string('credit_rating', 50)->nullable();
            $table->decimal('default_amount', 15)->nullable();
            $table->date('default_date')->nullable();
            $table->date('settlement_date')->nullable();
            $table->decimal('settlement_amount', 15)->nullable();
            $table->boolean('is_disputed')->nullable()->default(false);
            $table->text('dispute_reason')->nullable();
            $table->text('dispute_resolution')->nullable();
            $table->enum('reporting_status', ['reported', 'disputed', 'resolved', 'cleared'])->nullable()->default('reported');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['user_id'], 'credit_bureau_reports_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['case_id'], 'credit_bureau_reports_ibfk_2')->references(['id'])->on('debt_recovery_cases')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['bureau_name_id'], 'credit_bureau_reports_ibfk_3')->references(['id'])->on('bureau_names')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['created_by'], 'credit_bureau_reports_ibfk_4')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_bureau_reports');
    }
};
