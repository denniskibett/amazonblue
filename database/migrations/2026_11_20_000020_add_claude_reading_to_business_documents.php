<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// What Claude read from a business document (Services\Kyc\DocumentReader). SQL twin: database/sql/2026_11_20_claude_document_reading.sql
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('business_documents', 'read_status')) {
                $table->string('read_status', 12)->nullable()->after('size');
                $table->text('extracted')->nullable()->after('read_status');
                $table->string('read_error', 255)->nullable()->after('extracted');
                $table->timestamp('read_at')->nullable()->after('read_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_documents', function (Blueprint $table) {
            $table->dropColumn(['read_status', 'extracted', 'read_error', 'read_at']);
        });
    }
};
