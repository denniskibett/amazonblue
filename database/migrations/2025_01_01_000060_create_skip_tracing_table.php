<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('skip_tracing')) {
            return;
        }

        Schema::create('skip_tracing', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_user_id');
            $table->unsignedBigInteger('case_id')->nullable()->index('idx_case_id');
            $table->enum('tracing_type', ['phone', 'address', 'employment', 'social_media', 'database_search', 'field_investigation', 'other']);
            $table->date('search_date');
            $table->string('search_provider')->nullable();
            $table->text('search_result')->nullable();
            $table->string('verification_method')->nullable();
            $table->text('verification_result')->nullable();
            $table->boolean('is_verified')->nullable()->default(false);
            $table->string('source_contact')->nullable();
            $table->string('source_phone', 50)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['user_id'], 'skip_tracing_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['case_id'], 'skip_tracing_ibfk_2')->references(['id'])->on('debt_recovery_cases')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['created_by'], 'skip_tracing_ibfk_3')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skip_tracing');
    }
};
