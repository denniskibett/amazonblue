<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 5: a borrower's extension, payment-plan or help request, with the staff decision the borrower
 * sees. A consent (e.g. accepting a plan) stores its version, IP, user agent and hash in payload.
 * Same change as database/sql/2026_10_21_phase5_tables.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('borrower_requests')) {
            return;
        }
        Schema::create('borrower_requests', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';
            $table->bigIncrements('id');
            $table->enum('type', ['extension', 'plan', 'help']);
            $table->unsignedBigInteger('user_id')->index('fk_borrower_requests_user');
            $table->unsignedBigInteger('loan_id')->nullable()->index('fk_borrower_requests_loan');
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->enum('status', ['open', 'approved', 'declined', 'done'])->default('open')->index('idx_borrower_requests_status');
            $table->string('decision_note', 500)->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->foreign(['user_id'], 'fk_borrower_requests_user')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['loan_id'], 'fk_borrower_requests_loan')->references(['id'])->on('loans')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrower_requests');
    }
};
