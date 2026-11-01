<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Part funding (owner, 1 Nov 2026): who put in what of a loan's cash. Several rows = the loan is part funded and each
 * repayment's interest and principal split pro rata to `amount`; one row = a single funder (also the frozen funding of
 * past loans, source 'backfill 2026-11-02', written by `php artisan loans:backfill-fundings --apply`). No rows = the
 * split reads the loan's first cash disbursement, as before. SQL twin: database/sql/2026_11_02_part_funding.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loan_fundings')) {
            return;
        }
        Schema::create('loan_fundings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('loan_id');
            $t->unsignedBigInteger('investment_id')->nullable()->comment('The line the money came from; NULL = house with no line');
            $t->unsignedBigInteger('partner_id')->nullable()->comment('The partner behind the line; NULL = the house');
            $t->decimal('amount', 15, 2)->comment('What this funder put in of the amount sent');
            $t->decimal('share', 9, 6)->comment('amount / total, for display; the split uses the amounts');
            $t->unsignedBigInteger('disbursement_id')->nullable();
            $t->string('source', 40)->default('disbursement')->comment("disbursement | backfill 2026-11-02 | partner_links");
            $t->string('notes')->nullable();
            $t->timestamps();
            $t->index('loan_id', 'idx_loan_fundings_loan');
            $t->index('investment_id', 'idx_loan_fundings_line');
            $t->foreign('loan_id', 'fk_loan_fundings_loan')->references('id')->on('loans')->cascadeOnDelete();
            $t->foreign('investment_id', 'fk_loan_fundings_line')->references('id')->on('investments')->nullOnDelete();
            $t->foreign('partner_id', 'fk_loan_fundings_partner')->references('id')->on('partners')->nullOnDelete();
            $t->foreign('disbursement_id', 'fk_loan_fundings_disbursement')->references('id')->on('disbursements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_fundings');
    }
};
