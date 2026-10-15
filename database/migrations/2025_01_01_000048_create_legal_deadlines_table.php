<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('legal_deadlines')) {
            return;
        }

        Schema::create('legal_deadlines', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('case_id')->index('idx_case_id');
            $table->enum('deadline_type', ['statute_of_limitations', 'court_filing', 'hearing', 'response_due', 'payment_due', 'other']);
            $table->date('deadline_date')->index('idx_deadline_date');
            $table->string('description');
            $table->date('extension_date')->nullable();
            $table->text('extension_reason')->nullable();
            $table->enum('status', ['pending', 'met', 'extended', 'missed', 'waived'])->nullable()->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['case_id'], 'legal_deadlines_ibfk_1')->references(['id'])->on('debt_recovery_cases')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['created_by'], 'legal_deadlines_ibfk_2')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_deadlines');
    }
};
