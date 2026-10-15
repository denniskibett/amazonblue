<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('payment_methods')) {
            return;
        }

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_user_id');
            $table->unsignedBigInteger('method_type_id')->index('idx_method_type_id');
            $table->string('account_name');
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('swift_code', 50)->nullable();
            $table->string('mobile_network', 50)->nullable();
            $table->string('mobile_number', 50)->nullable();
            $table->string('crypto_currency', 50)->nullable();
            $table->string('wallet_address', 500)->nullable();
            $table->string('wallet_provider')->nullable();
            $table->boolean('is_primary')->nullable()->default(false)->index('idx_is_primary');
            $table->boolean('is_verified')->nullable()->default(false);
            $table->date('verification_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending_verification', 'suspended'])->nullable()->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes()->index();

            $table->foreign(['user_id'], 'payment_methods_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['method_type_id'], 'payment_methods_ibfk_2')->references(['id'])->on('payment_method_types')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
