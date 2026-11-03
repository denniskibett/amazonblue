<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Outside debt collection agencies (owner, 3 Nov 2026). Staff add an agency and its collectors (role "collector",
 * a login outside the back office), then place late loans with it, forbearance included. A collector sees only the
 * loans placed with their agency, while the placement is open, and logs calls, visits and promises against them.
 * SQL twin: database/sql/2026_11_03_collection_agencies.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('collection_agencies')) {
            Schema::create('collection_agencies', function (Blueprint $t) {
                $t->id();
                $t->string('name', 120);
                $t->string('contact_name', 120)->nullable();
                $t->string('email', 160)->nullable();
                $t->string('phone', 30)->nullable();
                $t->string('agreement_ref', 60)->nullable();          // the signed data sharing agreement
                $t->decimal('commission_rate', 5, 2)->default(0);      // % of what they collect
                $t->string('coverage', 160)->nullable();              // e.g. "Coast and Uganda"
                $t->boolean('cross_border')->default(false);          // works outside Kenya: needs the DPA s.48 clause
                $t->enum('status', ['active', 'paused'])->default('active');
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('collection_agency_users')) {
            Schema::create('collection_agency_users', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('agency_id');
                $t->unsignedBigInteger('user_id');
                $t->timestamps();
                $t->unique('user_id', 'uq_collection_agency_users_user');
                $t->index('agency_id', 'ix_collection_agency_users_agency');
            });
        }
        if (! Schema::hasTable('collection_placements')) {
            Schema::create('collection_placements', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('agency_id');
                $t->unsignedBigInteger('loan_id');
                $t->unsignedBigInteger('placed_by')->nullable();
                $t->dateTime('placed_at');
                $t->date('notice_sent_on')->nullable();               // the borrower was told which agency will call
                $t->date('ends_on');
                $t->enum('status', ['active', 'returned'])->default('active');
                $t->dateTime('returned_at')->nullable();
                $t->unsignedBigInteger('returned_by')->nullable();
                $t->text('note')->nullable();
                $t->timestamps();
                $t->index(['loan_id', 'status'], 'ix_collection_placements_loan');
                $t->index(['agency_id', 'status'], 'ix_collection_placements_agency');
            });
        }
        if (! Schema::hasTable('collection_contacts')) {
            Schema::create('collection_contacts', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('placement_id');
                $t->unsignedBigInteger('loan_id');
                $t->unsignedBigInteger('user_id');
                $t->enum('channel', ['call', 'sms', 'visit', 'whatsapp', 'email']);
                $t->string('outcome', 120);
                $t->decimal('promise_amount', 12, 2)->nullable();
                $t->date('promise_date')->nullable();
                $t->text('note')->nullable();
                $t->timestamps();
                $t->index('loan_id', 'ix_collection_contacts_loan');
                $t->index('placement_id', 'ix_collection_contacts_placement');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_contacts');
        Schema::dropIfExists('collection_placements');
        Schema::dropIfExists('collection_agency_users');
        Schema::dropIfExists('collection_agencies');
    }
};
