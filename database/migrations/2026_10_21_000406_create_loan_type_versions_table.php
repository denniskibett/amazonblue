<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 5: every change to a loan product keeps the values it had before (running loans are safe:
 * their cycles carry their own terms since Phase 2).
 * Same change as database/sql/2026_10_21_phase5_tables.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loan_type_versions')) {
            return;
        }
        Schema::create('loan_type_versions', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_type_id')->index('fk_loan_type_versions_type');
            $table->unsignedInteger('version');
            $table->json('values');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->unique(['loan_type_id', 'version'], 'uq_loan_type_version');
            $table->foreign(['loan_type_id'], 'fk_loan_type_versions_type')->references(['id'])->on('loan_types')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_type_versions');
    }
};
