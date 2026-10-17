<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Automatic 4Cs scoring (App\Services\CreditAssessment): one system assessment per loan, kept up to
 * date from the borrower's repayment history. Same change as database/sql/2026_10_17_risk_scoring.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_risk_assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('loan_risk_assessments', 'source')) {
                // auto = scored by the system from repayment history; manual = entered by staff
                $table->string('source', 20)->default('manual')->after('loan_id');
            }
            if (! Schema::hasColumn('loan_risk_assessments', 'risk_category_id')) {
                $table->unsignedBigInteger('risk_category_id')->nullable()->after('risk_category')->index('fk_risk_category');
                $table->foreign(['risk_category_id'], 'fk_risk_category')->references(['id'])->on('risk_categories')->onUpdate('no action')->onDelete('set null');
            }
            if (! Schema::hasColumn('loan_risk_assessments', 'limit_amount')) {
                // the borrowing limit this assessment supported
                $table->decimal('limit_amount', 15, 2)->nullable()->after('overall_score');
            }
            if (! Schema::hasColumn('loan_risk_assessments', 'factors')) {
                // what the scores were built from (history counts, outcome of the loan, ...)
                $table->json('factors')->nullable()->after('recommendation');
            }
            if (! Schema::hasColumn('loan_risk_assessments', 'assessed_at')) {
                // the date the assessment is "as at" (the loan's borrow date for auto assessments)
                $table->timestamp('assessed_at')->nullable()->after('factors');
            }
            // system assessments have no staff assessor
            $table->unsignedBigInteger('assessed_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('loan_risk_assessments', function (Blueprint $table) {
            $table->dropForeign('fk_risk_category');
            $table->dropColumn(['source', 'risk_category_id', 'limit_amount', 'factors', 'assessed_at']);
        });
    }
};
