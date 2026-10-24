<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Business loans (owner, 23 Oct 2026), on the business KYC tables that already exist:
 *  - a loan can be for a verified business (loans.business_id); the owner signs and stays the borrower of record
 *  - a product says who it is for (loan_types.audience)
 *  - the approver sets the business's limit (businesses.credit_limit)
 *  - each director gets a self-verification link (token hash and expiry on business_members) and their ID pictures
 *    are business documents tied to that member (business_documents.business_member_id)
 *  - changes to an approved business are asked for as a request (borrower_requests type business_change)
 * SQL twin: database/sql/2026_10_24_business_lending.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('loans', 'business_id')) {
            Schema::table('loans', function (Blueprint $t) {
                $t->unsignedBigInteger('business_id')->nullable()->after('user_id');
                $t->foreign('business_id', 'fk_loans_business')->references('id')->on('businesses')->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('loan_types', 'audience')) {
            Schema::table('loan_types', function (Blueprint $t) {
                $t->enum('audience', ['individual', 'business'])->default('individual')->after('name');
            });
        }
        if (! Schema::hasColumn('businesses', 'credit_limit')) {
            Schema::table('businesses', function (Blueprint $t) {
                $t->decimal('credit_limit', 15, 2)->nullable()->after('monthly_revenue');
            });
        }
        if (! Schema::hasColumn('business_members', 'verify_token_hash')) {
            Schema::table('business_members', function (Blueprint $t) {
                $t->char('verify_token_hash', 64)->nullable()->unique('uq_business_members_verify_token')->after('verified_at');
                $t->timestamp('verify_sent_at')->nullable()->after('verify_token_hash');
                $t->timestamp('verify_expires_at')->nullable()->after('verify_sent_at');
            });
        }
        if (! Schema::hasColumn('business_documents', 'business_member_id')) {
            Schema::table('business_documents', function (Blueprint $t) {
                $t->unsignedBigInteger('business_member_id')->nullable()->after('business_id');
                $t->foreign('business_member_id', 'fk_business_documents_member')->references('id')->on('business_members')->cascadeOnDelete();
            });
        }
        if (! Schema::hasColumn('borrower_requests', 'business_id')) {
            Schema::table('borrower_requests', function (Blueprint $t) {
                $t->unsignedBigInteger('business_id')->nullable()->after('loan_id');
                $t->foreign('business_id', 'fk_borrower_requests_business')->references('id')->on('businesses')->nullOnDelete();
            });
        }
        DB::statement("ALTER TABLE borrower_requests MODIFY `type` ENUM('extension','plan','help','topup','business_change') COLLATE utf8mb4_unicode_ci NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE borrower_requests MODIFY `type` ENUM('extension','plan','help','topup') COLLATE utf8mb4_unicode_ci NOT NULL");
        Schema::table('borrower_requests', function (Blueprint $t) {
            $t->dropForeign('fk_borrower_requests_business');
            $t->dropColumn('business_id');
        });
        Schema::table('business_documents', function (Blueprint $t) {
            $t->dropForeign('fk_business_documents_member');
            $t->dropColumn('business_member_id');
        });
        Schema::table('business_members', function (Blueprint $t) {
            $t->dropUnique('uq_business_members_verify_token');
            $t->dropColumn(['verify_token_hash', 'verify_sent_at', 'verify_expires_at']);
        });
        Schema::table('businesses', fn (Blueprint $t) => $t->dropColumn('credit_limit'));
        Schema::table('loan_types', fn (Blueprint $t) => $t->dropColumn('audience'));
        Schema::table('loans', function (Blueprint $t) {
            $t->dropForeign('fk_loans_business');
            $t->dropColumn('business_id');
        });
    }
};
