<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 5: a partner's "I've sent funds" or withdrawal request. Funding is confirmed by staff when the
 * money is in the bank; a withdrawal is prepared by one person and approved by another; both post through the
 * partner journal (PartnerLedger).
 * Same change as database/sql/2026_10_21_phase5_tables.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partner_requests')) {
            return;
        }
        Schema::create('partner_requests', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';
            $table->bigIncrements('id');
            $table->enum('type', ['funding', 'withdrawal']);
            $table->unsignedBigInteger('partner_id')->index('fk_partner_requests_partner');
            $table->decimal('amount', 15, 2);
            $table->string('wallet_slug', 40)->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'prepared', 'done', 'declined'])->default('pending')->index('idx_partner_requests_status');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('prepared_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('partner_transaction_id')->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->timestamps();
            $table->foreign(['partner_id'], 'fk_partner_requests_partner')->references(['id'])->on('partners')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_requests');
    }
};
