<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('recovery_agencies')) {
            return;
        }

        Schema::create('recovery_agencies', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->string('agency_name')->index('idx_agency_name');
            $table->string('license_number', 100)->nullable();
            $table->decimal('commission_rate', 5)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->nullable()->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_agencies');
    }
};
