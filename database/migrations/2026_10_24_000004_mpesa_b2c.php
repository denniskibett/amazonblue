<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M-Pesa B2C payouts (owner, 23 Oct 2026): money we send out is an M-Pesa transaction like money coming in, so it
 * goes on mpesa_transactions (type b2c) with Daraja's conversation id and the disbursement it became. No new table.
 * SQL twin: database/sql/2026_10_24_mpesa_b2c.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE mpesa_transactions MODIFY `type` ENUM('c2b','stk','b2c') COLLATE utf8mb4_unicode_ci NOT NULL");
        if (! Schema::hasColumn('mpesa_transactions', 'conversation_id')) {
            Schema::table('mpesa_transactions', function (Blueprint $t) {
                $t->string('conversation_id', 64)->nullable()->unique('uq_mpesa_transactions_conversation')->after('checkout_request_id');
                $t->unsignedBigInteger('disbursement_id')->nullable()->after('repayment_id');
                $t->foreign('disbursement_id', 'fk_mpesa_transactions_disbursement')->references('id')->on('disbursements')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $t) {
            $t->dropForeign('fk_mpesa_transactions_disbursement');
            $t->dropUnique('uq_mpesa_transactions_conversation');
            $t->dropColumn(['conversation_id', 'disbursement_id']);
        });
        DB::statement("ALTER TABLE mpesa_transactions MODIFY `type` ENUM('c2b','stk') COLLATE utf8mb4_unicode_ci NOT NULL");
    }
};
