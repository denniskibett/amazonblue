<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tamper evidence for signed loan agreements: the PDF is generated once when the borrower consents
 * and frozen on the private disk; its hash, signing time, signer IP / user agent and template version
 * are kept here. Same change as database/sql/2026_10_18_loan_agreement_fields.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (! Schema::hasColumn('loans', 'agreement_path')) {
                // relative to the private disk (storage/app/private)
                $table->string('agreement_path')->nullable()->after('consent_date');
            }
            if (! Schema::hasColumn('loans', 'agreement_sha256')) {
                $table->char('agreement_sha256', 64)->nullable()->after('agreement_path');
            }
            if (! Schema::hasColumn('loans', 'agreement_signed_at')) {
                $table->timestamp('agreement_signed_at')->nullable()->after('agreement_sha256');
            }
            if (! Schema::hasColumn('loans', 'agreement_signer_ip')) {
                $table->string('agreement_signer_ip', 45)->nullable()->after('agreement_signed_at');
            }
            if (! Schema::hasColumn('loans', 'agreement_user_agent')) {
                $table->string('agreement_user_agent')->nullable()->after('agreement_signer_ip');
            }
            if (! Schema::hasColumn('loans', 'agreement_version')) {
                // LoanAgreementService::TEMPLATE_VERSION, or "legacy" for copies frozen by files:make-private
                $table->string('agreement_version', 30)->nullable()->after('agreement_user_agent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['agreement_path', 'agreement_sha256', 'agreement_signed_at', 'agreement_signer_ip', 'agreement_user_agent', 'agreement_version']);
        });
    }
};
