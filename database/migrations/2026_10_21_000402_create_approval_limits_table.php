<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 5: the most one person in a role may do alone, per action (decision 14: second sign-off
 * above KES 50,000). Above it, a second person with the same permission must approve.
 * Same change as database/sql/2026_10_21_phase5_tables.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('approval_limits')) {
            return;
        }
        Schema::create('approval_limits', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';
            $table->bigIncrements('id');
            $table->string('role', 60);
            $table->string('action', 40);
            $table->decimal('max_amount', 15, 2);
            $table->timestamps();
            $table->unique(['role', 'action'], 'uq_approval_limits');
        });
        foreach (['super-admin', 'admin', 'manager'] as $role) {
            \Illuminate\Support\Facades\DB::table('approval_limits')->insertOrIgnore(['role' => $role, 'action' => 'approve_loan', 'max_amount' => 50000, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_limits');
    }
};
