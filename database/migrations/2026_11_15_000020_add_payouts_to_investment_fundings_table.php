<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settling one investor with another's money (owner, 15 Nov 2026): Buosti's 3,120 and Ruth's 10,400 were paid out of
 * Yvonne's 100,000. amount_paid_out is what a line's money paid to other investors (it is no longer free to lend);
 * paid_from_investment_id, on the line that was paid back, is the line that paid it (null: our own money).
 * SQL twin: database/sql/2026_11_15_line_payouts.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('investment_fundings')) {
            return;
        }
        Schema::table('investment_fundings', function (Blueprint $t) {
            if (! Schema::hasColumn('investment_fundings', 'amount_paid_out')) {
                $t->decimal('amount_paid_out', 15, 2)->default(0)->after('amount_returned')->comment('this line\'s money used to pay other investors back');
            }
            if (! Schema::hasColumn('investment_fundings', 'paid_from_investment_id')) {
                $t->foreignId('paid_from_investment_id')->nullable()->after('amount_paid_out')->constrained('investments')->nullOnDelete()
                    ->comment('the line whose money paid this one back (null: our own money)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('investment_fundings', function (Blueprint $t) {
            if (Schema::hasColumn('investment_fundings', 'paid_from_investment_id')) {
                $t->dropConstrainedForeignId('paid_from_investment_id');
            }
            if (Schema::hasColumn('investment_fundings', 'amount_paid_out')) {
                $t->dropColumn('amount_paid_out');
            }
        });
    }
};
