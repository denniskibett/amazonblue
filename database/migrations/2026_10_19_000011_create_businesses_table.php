<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Businesses a verified borrower adds from /account (business KYC). The person who adds it is the owner
 * (owner_user_id); staff review it in Business reviews. Foundation for business loans, trade finance and
 * asset-backed lending (Stage 4). Same change as database/sql/2026_10_19_businesses.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('businesses')) {
            return;
        }

        Schema::create('businesses', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('owner_user_id')->index('businesses_owner_user_id_foreign');
            $table->string('legal_name', 191);
            $table->string('trading_name', 191)->nullable();
            $table->string('registration_number', 50)->nullable()->index('businesses_registration_number_index');
            // sole_proprietorship | partnership | limited_company | other
            $table->string('registration_type', 30)->nullable();
            $table->string('kra_pin', 20)->nullable();
            $table->string('industry', 100)->nullable();
            $table->date('date_registered')->nullable();
            $table->string('address_line_1', 191)->nullable();
            $table->string('address_line_2', 191)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('county', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable()->default('Kenya');
            $table->string('phone', 30)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('website', 191)->nullable();
            $table->decimal('monthly_revenue', 15, 2)->nullable();
            $table->unsignedInteger('employees')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 50)->nullable();
            // draft | submitted | approved | rejected
            $table->string('status', 20)->default('draft')->index('businesses_status_index');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index('businesses_reviewed_by_foreign');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->json('checks')->nullable();
            // wallet handle (App\Support\Handle), unique across users, partners and businesses
            $table->string('handle', 64)->nullable()->unique('businesses_handle_unique');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['owner_user_id'], 'businesses_owner_user_id_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['reviewed_by'], 'businesses_reviewed_by_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
