<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('loan_agreement_templates')) {
            return;
        }

        Schema::create('loan_agreement_templates', function (Blueprint $table) {
            $table->collation = 'utf8mb3_general_ci';
            $table->charset = 'utf8mb3';

            $table->bigIncrements('id');
            $table->string('name');
            $table->string('type');
            $table->longText('content')->collation('utf8mb4_bin');
            $table->longText('variables')->collation('utf8mb4_bin')->nullable();
            $table->string('status', 50)->nullable()->default('active');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_agreement_templates');
    }
};
