<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('borrowers')) {
            return;
        }

        Schema::create('borrowers', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('broker_id')->nullable()->index('borrowers_broker_id_foreign');
            $table->unsignedBigInteger('user_id')->index('borrowers_user_id_foreign');
            $table->string('client_type');
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->string('income_type', 100)->nullable();
            $table->decimal('gross_salary', 15)->nullable();
            $table->decimal('net_salary', 15)->nullable();
            $table->string('job_title')->nullable();
            $table->string('workplace')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('employer_email')->nullable();
            $table->string('employer_title')->nullable();
            $table->string('department')->nullable();

            $table->foreign(['broker_id'])->references(['id'])->on('brokers')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowers');
    }
};
