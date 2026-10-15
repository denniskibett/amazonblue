<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('recovery_case_notes')) {
            return;
        }

        Schema::create('recovery_case_notes', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('case_id')->index('idx_case_id');
            $table->enum('note_type', ['general', 'action', 'reminder', 'alert', 'legal'])->nullable()->default('general');
            $table->text('note');
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->boolean('is_private')->nullable()->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['case_id'], 'recovery_case_notes_ibfk_1')->references(['id'])->on('debt_recovery_cases')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['created_by'], 'recovery_case_notes_ibfk_2')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_case_notes');
    }
};
