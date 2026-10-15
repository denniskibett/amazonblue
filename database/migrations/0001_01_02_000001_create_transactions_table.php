<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * bavix/laravel-wallet table: transactions
 */
return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('transactions')) {
            return;
        }

        Schema::create('transactions', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');
            $table->unsignedBigInteger('wallet_id')->index('transactions_wallet_id_foreign');
            $table->enum('type', ['deposit', 'withdraw'])->index();
            $table->decimal('amount', 64, 0);
            $table->boolean('confirmed');
            $table->json('meta')->nullable();
            $table->char('uuid', 36)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['payable_type', 'payable_id', 'confirmed'], 'payable_confirmed_ind');
            $table->index(['payable_type', 'payable_id', 'type', 'confirmed'], 'payable_type_confirmed_ind');
            $table->index(['payable_type', 'payable_id', 'type'], 'payable_type_ind');
            $table->index(['payable_type', 'payable_id'], 'payable_type_payable_id_ind');
            $table->index(['payable_type', 'payable_id']);

            $table->foreign(['wallet_id'])->references(['id'])->on('wallets')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
