<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('legal_proceedings')) {
            return;
        }

        Schema::create('legal_proceedings', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('case_id')->index('idx_case_id');
            $table->unsignedBigInteger('proceeding_type_id')->index('proceeding_type_id');
            $table->date('filing_date');
            $table->string('court_name')->nullable();
            $table->string('case_number', 100)->nullable();
            $table->string('judge_name')->nullable();
            $table->string('plaintiff')->nullable();
            $table->string('defendant')->nullable();
            $table->decimal('amount_claimed', 15)->nullable();
            $table->decimal('amount_awarded', 15)->nullable();
            $table->date('judgment_date')->nullable();
            $table->enum('status', ['filed', 'pending', 'active', 'resolved', 'appealed', 'dismissed'])->nullable()->default('filed')->index('idx_status');
            $table->date('next_hearing_date')->nullable();
            $table->string('lawyer_name')->nullable();
            $table->string('lawyer_contact')->nullable();
            $table->decimal('lawyer_fees', 15)->nullable();
            $table->decimal('costs', 15)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['case_id'], 'legal_proceedings_ibfk_1')->references(['id'])->on('debt_recovery_cases')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['proceeding_type_id'], 'legal_proceedings_ibfk_2')->references(['id'])->on('legal_proceeding_types')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['created_by'], 'legal_proceedings_ibfk_3')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_proceedings');
    }
};
