<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('brokers')) {
            return;
        }

        Schema::create('brokers', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('brokers_user_id_foreign');
            $table->decimal('interest_client', 5);
            $table->decimal('interest_broker', 5);
            $table->decimal('penalty_client', 5);
            $table->decimal('penalty_broker', 5);
            $table->string('cert_no')->unique();
            $table->timestamps();

            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brokers');
    }
};
