<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * bavix/laravel-wallet table: transfers
 */
return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('transfers')) {
            return;
        }

        Schema::create('transfers', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('from_id')->index();
            $table->unsignedBigInteger('to_id')->index();
            $table->enum('status', ['exchange', 'transfer', 'paid', 'refund', 'gift'])->default('transfer');
            $table->enum('status_last', ['exchange', 'transfer', 'paid', 'refund', 'gift'])->nullable();
            $table->unsignedBigInteger('deposit_id')->index('transfers_deposit_id_foreign');
            $table->unsignedBigInteger('withdraw_id')->index('transfers_withdraw_id_foreign');
            $table->decimal('discount', 64, 0)->default(0);
            $table->decimal('fee', 64, 0)->default(0);
            $table->json('extra')->nullable();
            $table->char('uuid', 36)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['deposit_id'])->references(['id'])->on('transactions')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['withdraw_id'])->references(['id'])->on('transactions')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
