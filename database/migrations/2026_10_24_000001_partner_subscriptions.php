<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner KYC and online subscription (owner, 23 Oct 2026). No new table: a subscription is a partner funding
 * request with the line it asks for and its signed subscription in `payload` (as borrower_requests keeps a signed
 * plan), and the line it opened in `investment_id`. A business partner points at its verified business instead of
 * keeping a second copy of the company details. SQL twin: database/sql/2026_10_24_partner_subscriptions.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        // each step skips what is already there, like the SQL twin
        if (! Schema::hasColumn('partner_requests', 'payload')) {
            Schema::table('partner_requests', fn (Blueprint $t) => $t->json('payload')->nullable()->after('note'));
        }
        if (! Schema::hasColumn('partner_requests', 'investment_id')) {
            Schema::table('partner_requests', function (Blueprint $t) {
                $t->unsignedBigInteger('investment_id')->nullable()->after('partner_transaction_id');
                $t->foreign('investment_id', 'fk_partner_requests_investment')->references('id')->on('investments')->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('partners', 'business_id')) {
            Schema::table('partners', function (Blueprint $t) {
                $t->unsignedBigInteger('business_id')->nullable()->after('user_id');
                $t->foreign('business_id', 'fk_partners_business')->references('id')->on('businesses')->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('partners', 'source_of_funds')) {
            Schema::table('partners', fn (Blueprint $t) => $t->string('source_of_funds', 60)->nullable()->after('tax_id'));
        }
    }

    public function down(): void
    {
        Schema::table('partner_requests', function (Blueprint $t) {
            $t->dropForeign('fk_partner_requests_investment');
            $t->dropColumn(['payload', 'investment_id']);
        });
        Schema::table('partners', function (Blueprint $t) {
            $t->dropForeign('fk_partners_business');
            $t->dropColumn(['business_id', 'source_of_funds']);
        });
    }
};
