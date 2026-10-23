<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One behaviour profile per borrower, refreshed nightly by analytics:profiles (owner, 23 Oct 2026).
 * SQL twin: database/sql/2026_10_23_borrower_profiles.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrower_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $t->unsignedInteger('loans')->default(0);
            $t->decimal('on_time_rate', 5, 1)->nullable();
            $t->decimal('avg_days_late', 7, 1)->default(0);
            $t->unsignedInteger('max_days_late')->default(0);
            $t->unsignedInteger('bad_loans')->default(0);
            $t->unsignedInteger('rolled')->default(0);
            $t->decimal('comfort_amount', 15, 2)->default(0);
            $t->decimal('largest', 15, 2)->default(0);
            $t->unsignedInteger('weekend_loans')->default(0);
            $t->unsignedInteger('weekend_bad')->default(0);
            $t->unsignedTinyInteger('score')->nullable();
            $t->unsignedInteger('suggested_limit')->default(0);
            $t->text('flags')->nullable();
            $t->string('last_outcome', 20)->nullable();
            $t->timestamp('computed_at')->nullable();
            $t->timestamp('updated_at')->nullable();
            $t->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrower_profiles');
    }
};
