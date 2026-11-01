<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Testimonials (owner, 1 Nov 2026): what real borrowers say, shown on the website only after staff approve it.
 * One per borrower (they can edit it; an edit goes back to pending). SQL twin: database/sql/2026_11_01_testimonials.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('testimonials')) {
            return;
        }
        Schema::create('testimonials', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('loan_id')->nullable();
            $t->unsignedTinyInteger('rating');
            $t->text('body');
            $t->string('display_name', 60);
            $t->string('display_role', 80)->nullable();
            $t->enum('status', ['pending', 'approved', 'hidden'])->default('pending');
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->unique('user_id', 'uq_testimonials_user');
            $t->index(['status', 'approved_at'], 'idx_testimonials_status');
            $t->foreign('user_id', 'fk_testimonials_user')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('loan_id', 'fk_testimonials_loan')->references('id')->on('loans')->nullOnDelete();
            $t->foreign('approved_by', 'fk_testimonials_approver')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
