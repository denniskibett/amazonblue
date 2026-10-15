<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('bank_accounts')) {
            return;
        }

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->string('bank_name');
            $table->string('name');
            $table->string('number');
            $table->string('branch');
            $table->string('accountable_type');
            $table->unsignedBigInteger('accountable_id');
            $table->timestamps();

            $table->index(['accountable_type', 'accountable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
