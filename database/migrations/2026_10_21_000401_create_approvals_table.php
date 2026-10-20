<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 5: maker-checker. One row per money action that needs a second person (an approval above the
 * approver's limit, a write-off, a reversal, a partner withdrawal). The checker can never be the maker: the app
 * refuses it and so does the database (CHECK).
 * Same change as database/sql/2026_10_21_phase5_tables.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('approvals')) {
            return;
        }
        Schema::create('approvals', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';
            $table->bigIncrements('id');
            $table->string('action', 40)->index('idx_approvals_action');
            $table->string('subject_type', 60);
            $table->unsignedBigInteger('subject_id');
            $table->decimal('amount', 15, 2)->nullable();
            $table->json('payload')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->index('idx_approvals_status');
            $table->unsignedBigInteger('requested_by')->index('fk_approvals_requested_by');
            $table->unsignedBigInteger('decided_by')->nullable()->index('fk_approvals_decided_by');
            $table->timestamp('decided_at')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id'], 'idx_approvals_subject');
            $table->foreign(['requested_by'], 'fk_approvals_requested_by')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['decided_by'], 'fk_approvals_decided_by')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE `approvals` ADD CONSTRAINT `chk_approvals_checker` CHECK (`decided_by` IS NULL OR `decided_by` <> `requested_by`)');
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
