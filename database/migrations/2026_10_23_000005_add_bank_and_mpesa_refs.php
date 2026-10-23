<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two references per payment (owner, 23 Oct 2026): money that crosses between a bank and M-Pesa has a bank
 * Transaction Ref ID and an M-Pesa code. Both are kept so either statement matches and neither is recorded twice.
 * SQL twin: database/sql/2026_10_23_bank_and_mpesa_refs.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['disbursements', 'repayments'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('bank_ref', 100)->nullable()->after('transaction');
                $t->string('mpesa_ref', 40)->nullable()->after('bank_ref');
                $t->index('bank_ref');
                $t->index('mpesa_ref');
            });
        }
    }

    public function down(): void
    {
        foreach (['disbursements', 'repayments'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['bank_ref']);
                $t->dropIndex(['mpesa_ref']);
                $t->dropColumn(['bank_ref', 'mpesa_ref']);
            });
        }
    }
};
