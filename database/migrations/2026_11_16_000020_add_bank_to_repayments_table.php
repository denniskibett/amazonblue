<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which bank (owner, 16 Nov 2026): money that goes through a bank (bank transfer, bank to M-Pesa, M-Pesa to bank,
 * Pesalink, cheque) records our bank: bank_code is the id from resources/data/banks-kenya.txt (stable, for reports)
 * and bank_name the full name as it read that day (or what staff typed for a bank not on the list).
 * SQL twin: database/sql/2026_11_16_bank_on_payments.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('repayments', 'bank_code')) {
            return;
        }
        Schema::table('repayments', function (Blueprint $t) {
            $t->string('bank_code', 20)->nullable()->after('mode');
            $t->string('bank_name', 120)->nullable()->after('bank_code');
            $t->index('bank_code');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('repayments', 'bank_code')) {
            return;
        }
        Schema::table('repayments', function (Blueprint $t) {
            $t->dropIndex(['bank_code']);
            $t->dropColumn(['bank_code', 'bank_name']);
        });
    }
};
