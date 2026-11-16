<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The client's bank (owner, 16 Nov 2026): beside our bank (bank_code / bank_name), the borrower's bank on a bank
 * transfer, Pesalink or cheque, e.g. Cynthia's loan went from our I&M to her Stanbic. Same id list
 * (resources/data/banks-kenya.txt) and the full name as it read that day.
 * SQL twin: database/sql/2026_11_16_client_bank_on_payments.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('disbursements', 'client_bank_code')) {
            return;
        }
        Schema::table('disbursements', function (Blueprint $t) {
            $t->string('client_bank_code', 20)->nullable()->after('bank_name');
            $t->string('client_bank_name', 120)->nullable()->after('client_bank_code');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('disbursements', 'client_bank_code')) {
            Schema::table('disbursements', fn (Blueprint $t) => $t->dropColumn(['client_bank_code', 'client_bank_name']));
        }
    }
};
