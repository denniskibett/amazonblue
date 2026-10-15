<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('addresses')) {
            return;
        }

        Schema::create('addresses', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_user_id');
            $table->unsignedBigInteger('address_type_id')->index('idx_address_type_id');
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->boolean('is_primary')->nullable()->default(false)->index('idx_is_primary');
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes()->index();

            $table->foreign(['user_id'], 'addresses_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['address_type_id'], 'addresses_ibfk_2')->references(['id'])->on('address_types')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
