<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff verification of borrower KYC: borrowers submit, staff approve or reject after calling
 * the next of kin and guarantor. Same change as database/sql/2026_10_16_kyc_review.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'kyc_status')) {
                // null = not submitted yet; submitted / approved / rejected
                $table->string('kyc_status', 20)->nullable()->after('kyc_submitted_at');
            }
            if (! Schema::hasColumn('users', 'kyc_reviewed_by')) {
                $table->unsignedBigInteger('kyc_reviewed_by')->nullable()->after('kyc_status');
            }
            if (! Schema::hasColumn('users', 'kyc_reviewed_at')) {
                $table->timestamp('kyc_reviewed_at')->nullable()->after('kyc_reviewed_by');
            }
            if (! Schema::hasColumn('users', 'kyc_review_notes')) {
                $table->text('kyc_review_notes')->nullable()->after('kyc_reviewed_at');
            }
            if (! Schema::hasColumn('users', 'kyc_checks')) {
                // which verification calls staff made, e.g. {"next_of_kin": true, "guarantor": true}
                $table->json('kyc_checks')->nullable()->after('kyc_review_notes');
            }
        });

        // anyone who already submitted is waiting for review
        DB::table('users')->whereNotNull('kyc_submitted_at')->whereNull('kyc_status')->update(['kyc_status' => 'submitted']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kyc_status', 'kyc_reviewed_by', 'kyc_reviewed_at', 'kyc_review_notes', 'kyc_checks']);
        });
    }
};
