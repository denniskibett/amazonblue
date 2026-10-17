<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('loan_agreement_sections')) {
            return;
        }

        Schema::create('loan_agreement_sections', function (Blueprint $table) {
            $table->collation = 'utf8mb3_general_ci';
            $table->charset = 'utf8mb3';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_id');
            $table->string('section_type');
            $table->string('title');
            $table->json('content')->nullable();
            $table->json('variables')->nullable();
            $table->integer('order')->nullable()->default(0);
            $table->boolean('is_editable')->nullable()->default(true);
            $table->string('status', 50)->nullable()->default('draft');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();

            $table->unique(['loan_id', 'section_type'], 'uq_loan_section');

            $table->foreign(['loan_id'], 'fk_agreement_loan')->references(['id'])->on('loans')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_agreement_sections');
    }
};
