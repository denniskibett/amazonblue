<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('contacts')) {
            return;
        }

        Schema::create('contacts', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_user_id');
            $table->unsignedBigInteger('contact_type_id')->index('idx_contact_type_id');
            $table->string('name')->index('idx_name');
            $table->string('phone', 50)->nullable()->index('idx_phone');
            $table->string('phone_2', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('relationship_specific', 100)->nullable()->comment('e.g., Mother, Father, Brother');
            $table->boolean('is_primary_contact')->nullable()->default(false);
            $table->tinyInteger('priority')->nullable()->default(1);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->unsignedBigInteger('updated_by')->nullable()->index('updated_by');
            $table->timestamps();
            $table->softDeletes()->index();

            $table->foreign(['user_id'], 'contacts_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['contact_type_id'], 'contacts_ibfk_2')->references(['id'])->on('contact_types')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['created_by'], 'contacts_ibfk_3')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['updated_by'], 'contacts_ibfk_4')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
