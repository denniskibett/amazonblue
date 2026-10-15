<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('agency_contacts')) {
            return;
        }

        Schema::create('agency_contacts', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('agency_id')->index('idx_agency_id');
            $table->string('contact_person');
            $table->string('title', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('phone_2', 50)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->nullable()->default(false)->index('idx_is_primary');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['agency_id'], 'agency_contacts_ibfk_1')->references(['id'])->on('recovery_agencies')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_contacts');
    }
};
