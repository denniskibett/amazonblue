<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Borrowers (owner, 23 Oct 2026): how often and on which day they are paid (borrower-entered), and a copy of their
 * repayment behaviour (written nightly by analytics:profiles from borrower_profiles).
 * SQL twin: database/sql/2026_10_23_borrowers_pay_and_behaviour.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrowers', function (Blueprint $t) {
            $t->string('pay_frequency', 20)->nullable()->after('net_salary');   // monthly | fortnightly | weekly | daily | irregular
            $t->unsignedTinyInteger('pay_day')->nullable()->after('pay_frequency'); // day of the month (1-31) for monthly pay
            $t->timestamp('profile_completed_at')->nullable()->after('department');
            $t->unsignedTinyInteger('behaviour_score')->nullable()->after('profile_completed_at');
            $t->decimal('on_time_rate', 5, 1)->nullable()->after('behaviour_score');
            $t->decimal('comfort_amount', 15, 2)->nullable()->after('on_time_rate');
            $t->unsignedInteger('suggested_limit')->nullable()->after('comfort_amount');
            $t->text('behaviour_flags')->nullable()->after('suggested_limit');
            $t->timestamp('behaviour_at')->nullable()->after('behaviour_flags');
        });
    }

    public function down(): void
    {
        Schema::table('borrowers', function (Blueprint $t) {
            $t->dropColumn(['pay_frequency', 'pay_day', 'profile_completed_at', 'behaviour_score', 'on_time_rate', 'comfort_amount', 'suggested_limit', 'behaviour_flags', 'behaviour_at']);
        });
    }
};
