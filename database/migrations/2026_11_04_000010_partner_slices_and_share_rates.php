<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner money, recorded the way it happens (owner, 3–4 Nov 2026):
 *  - a line carries its own shares: the partner's % of the interest (60 when the money is theirs and the client ours)
 *    and of the penalty (50); empty = the partner's profit-share rate and no penalty, as before;
 *  - a loan_fundings slice can be settled by the house on a date: the house pays the partner their capital and their
 *    share of that slice's interest and owns the slice from then on (a rolled-over facility whose partner was paid);
 *  - a payout already made is recorded with the day it was paid and the bank or M-Pesa reference.
 * SQL twin: database/sql/2026_11_04_partner_slices.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $t) {
            if (! Schema::hasColumn('investments', 'partner_share_rate')) {
                $t->decimal('partner_share_rate', 5, 2)->nullable()->after('return_type');
            }
            if (! Schema::hasColumn('investments', 'partner_penalty_rate')) {
                $t->decimal('partner_penalty_rate', 5, 2)->nullable()->after('partner_share_rate');
            }
        });
        Schema::table('loan_fundings', function (Blueprint $t) {
            if (! Schema::hasColumn('loan_fundings', 'settled_on')) {
                $t->date('settled_on')->nullable()->after('source');
                $t->string('settled_by', 20)->nullable()->after('settled_on');
                $t->decimal('settled_interest', 12, 2)->nullable()->after('settled_by');
                $t->unsignedBigInteger('settled_transaction_id')->nullable()->after('settled_interest');
            }
        });
        Schema::table('partner_requests', function (Blueprint $t) {
            if (! Schema::hasColumn('partner_requests', 'paid_on')) {
                $t->date('paid_on')->nullable()->after('status');
                $t->string('bank_reference', 60)->nullable()->after('paid_on');
            }
        });
    }

    public function down(): void
    {
        Schema::table('partner_requests', fn (Blueprint $t) => $t->dropColumn(['paid_on', 'bank_reference']));
        Schema::table('loan_fundings', fn (Blueprint $t) => $t->dropColumn(['settled_on', 'settled_by', 'settled_interest', 'settled_transaction_id']));
        Schema::table('investments', fn (Blueprint $t) => $t->dropColumn(['partner_share_rate', 'partner_penalty_rate']));
    }
};
