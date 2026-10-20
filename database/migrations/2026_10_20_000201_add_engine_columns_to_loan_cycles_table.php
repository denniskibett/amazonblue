<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 Phase 2: a cycle carries its own terms and a cache of its balance.
 *  - principal: what the cycle lends (cycle 1: the amount sent; later cycles: what was carried over).
 *  - penalty_rate, grace_days, default_threshold_days: the product's terms when the cycle was created, so a
 *    product edit never changes a running loan. NULL = read the product (rows from before the snapshot).
 *  - penalty_waived_until: no penalty for days up to this date (payment plans, forbearance); this cycle only.
 *  - closing_balance … penalty_paid, balance_as_of: what App\Services\Loans\CycleBalance computed, stored by
 *    loans:restate and on every change, so lists and reports can read it without recomputing.
 *  - closed_on: the day the cycle was settled or rolled.
 * Same change as database/sql/2026_10_20_loan_cycle_engine_columns.sql.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'principal' => ['decimal', 'interest_rate'],
        'penalty_rate' => ['rate', 'principal'],
        'grace_days' => ['int', 'penalty_rate'],
        'default_threshold_days' => ['int', 'grace_days'],
        'penalty_waived_until' => ['date', 'due_date'],
        'closing_balance' => ['decimal', 'penalty_waived_until'],
        'penalty_accrued' => ['decimal', 'closing_balance'],
        'principal_paid' => ['decimal', 'penalty_accrued'],
        'interest_paid' => ['decimal', 'principal_paid'],
        'penalty_paid' => ['decimal', 'interest_paid'],
        'balance_as_of' => ['date', 'penalty_paid'],
        'closed_on' => ['date', 'balance_as_of'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('loan_cycles')) {
            return;
        }
        foreach (self::COLUMNS as $name => [$type, $after]) {
            if (Schema::hasColumn('loan_cycles', $name)) {
                continue;
            }
            Schema::table('loan_cycles', function (Blueprint $table) use ($name, $type, $after) {
                $col = match ($type) {
                    'decimal' => $table->decimal($name, 15)->nullable(),
                    'rate' => $table->decimal($name, 6, 3)->nullable(),
                    'int' => $table->unsignedInteger($name)->nullable(),
                    'date' => $table->date($name)->nullable(),
                };
                $col->after($after);
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(array_keys(self::COLUMNS)) as $name) {
            if (Schema::hasColumn('loan_cycles', $name)) {
                Schema::table('loan_cycles', fn (Blueprint $table) => $table->dropColumn($name));
            }
        }
    }
};
