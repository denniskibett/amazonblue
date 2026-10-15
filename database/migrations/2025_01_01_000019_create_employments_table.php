<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('employments')) {
            return;
        }

        Schema::create('employments', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_user_id');
            $table->string('employer_name')->nullable()->index('idx_employer_name');
            $table->text('employer_address')->nullable();
            $table->string('employer_phone', 50)->nullable();
            $table->string('employer_email')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->string('supervisor_phone', 50)->nullable();
            $table->string('supervisor_email')->nullable();
            $table->string('hr_contact_name')->nullable();
            $table->string('hr_contact_phone', 50)->nullable();
            $table->string('hr_contact_email')->nullable();
            $table->unsignedBigInteger('employment_type_id')->nullable()->index('employment_type_id');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->nullable()->default(true)->index('idx_is_current');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['user_id'], 'employments_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['employment_type_id'], 'employments_ibfk_2')->references(['id'])->on('employment_types')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employments');
    }
};
