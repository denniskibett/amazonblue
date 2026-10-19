<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Directors, owners, shareholders and signatories of a business. user_id links a member who is also a
 * verified borrower (the owner always; others when their ID number matches an approved individual KYC).
 * Same change as database/sql/2026_10_19_businesses.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('business_members')) {
            return;
        }

        Schema::create('business_members', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('business_members_business_id_foreign');
            $table->unsignedBigInteger('user_id')->nullable()->index('business_members_user_id_foreign');
            $table->string('full_name', 191);
            // owner | director | shareholder | signatory
            $table->string('role', 20)->default('director');
            $table->string('id_number', 50)->nullable();
            $table->string('kra_pin', 20)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 191)->nullable();
            $table->decimal('ownership_pct', 5, 2)->default(0);
            $table->boolean('is_signatory')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign(['business_id'], 'business_members_business_id_foreign')->references(['id'])->on('businesses')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_id'], 'business_members_user_id_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_members');
    }
};
