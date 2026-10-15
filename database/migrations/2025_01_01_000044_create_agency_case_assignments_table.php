<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('agency_case_assignments')) {
            return;
        }

        Schema::create('agency_case_assignments', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('case_id')->index('idx_case_id');
            $table->unsignedBigInteger('agency_id')->index('idx_agency_id');
            $table->date('assignment_date');
            $table->decimal('commission_rate', 5)->nullable();
            $table->decimal('recovery_amount', 15)->nullable();
            $table->decimal('agency_fees', 15)->nullable();
            $table->enum('status', ['assigned', 'in_progress', 'recovered', 'returned'])->nullable()->default('assigned');
            $table->date('return_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['case_id'], 'agency_case_assignments_ibfk_1')->references(['id'])->on('debt_recovery_cases')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['agency_id'], 'agency_case_assignments_ibfk_2')->references(['id'])->on('recovery_agencies')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['created_by'], 'agency_case_assignments_ibfk_3')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_case_assignments');
    }
};
