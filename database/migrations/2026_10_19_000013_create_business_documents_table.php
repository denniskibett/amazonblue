<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company documents (certificate, CR12, KRA PIN certificate, bank statement, permit, other). Files are on the
 * private disk (App\Support\PrivateFiles) and served only by files.business. Same change as
 * database/sql/2026_10_19_businesses.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('business_documents')) {
            return;
        }

        Schema::create('business_documents', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('business_documents_business_id_foreign');
            // certificate | cr12 | kra_pin_certificate | bank_statement | business_permit | other
            $table->string('type', 30);
            $table->string('path', 255);
            $table->string('original_name', 255)->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable()->index('business_documents_uploaded_by_foreign');
            $table->timestamps();

            $table->foreign(['business_id'], 'business_documents_business_id_foreign')->references(['id'])->on('businesses')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['uploaded_by'], 'business_documents_uploaded_by_foreign')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_documents');
    }
};
