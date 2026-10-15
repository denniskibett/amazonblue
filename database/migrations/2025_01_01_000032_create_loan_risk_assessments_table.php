<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('loan_risk_assessments')) {
            return;
        }

        Schema::create('loan_risk_assessments', function (Blueprint $table) {
            $table->collation = 'utf8mb3_general_ci';
            $table->charset = 'utf8mb3';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_id')->index('fk_risk_loan');
            $table->integer('character_score')->nullable();
            $table->integer('capacity_score')->nullable();
            $table->integer('capital_score')->nullable();
            $table->integer('conditions_score')->nullable();
            $table->integer('overall_score')->nullable();
            $table->string('risk_category', 100)->nullable();
            $table->unsignedBigInteger('assessed_by')->index('fk_risk_assessor');
            $table->text('assessment_notes')->nullable();
            $table->string('recommendation')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->softDeletes();

            $table->foreign(['assessed_by'], 'fk_risk_assessor')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['loan_id'], 'fk_risk_loan')->references(['id'])->on('loans')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_risk_assessments');
    }
};
