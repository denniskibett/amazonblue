<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('recovery_documents')) {
            return;
        }

        Schema::create('recovery_documents', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('case_id')->index('idx_case_id');
            $table->unsignedBigInteger('document_type_id')->index('document_type_id');
            $table->unsignedBigInteger('document_status_id')->nullable()->index('document_status_id');
            $table->string('document_name');
            $table->string('file_path', 500);
            $table->integer('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable()->index('uploaded_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['case_id'], 'recovery_documents_ibfk_1')->references(['id'])->on('debt_recovery_cases')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['document_type_id'], 'recovery_documents_ibfk_2')->references(['id'])->on('document_types')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['document_status_id'], 'recovery_documents_ibfk_3')->references(['id'])->on('document_statuses')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['uploaded_by'], 'recovery_documents_ibfk_4')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_documents');
    }
};
