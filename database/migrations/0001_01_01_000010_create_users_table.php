<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel core table: users
 */
return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched, only adding
        // the KYC columns from the self-service onboarding if they are missing.
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'kra_pin')) {
                    $table->string('kra_pin', 20)->nullable()->after('id_number');
                }
                if (! Schema::hasColumn('users', 'kyc_submitted_at')) {
                    $table->timestamp('kyc_submitted_at')->nullable()->after('id_back_path');
                }
            });

            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->string('name');
            $table->string('avatar')->nullable();
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'borrower', 'broker', 'teller', 'partner']);
            $table->tinyInteger('status')->default(0);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('dob')->nullable();
            $table->string('pob')->nullable();
            $table->string('nationality')->nullable();
            $table->string('marital_status', 100)->nullable();
            $table->string('religion', 100)->nullable();
            $table->boolean('disability')->nullable()->default(false);
            $table->string('education')->nullable();
            $table->string('kin_name')->nullable();
            $table->string('kin_email')->nullable();
            $table->string('kin_phone', 50)->nullable();
            $table->string('kin_occupation')->nullable();
            $table->string('kin_relation', 100)->nullable();
            $table->string('kin_id_type', 100)->nullable();
            $table->string('kin_id_number', 100)->nullable();
            $table->text('signature')->nullable();
            $table->string('id_type', 100)->nullable();
            $table->string('id_number', 100)->nullable();
            $table->string('kra_pin', 20)->nullable();
            $table->string('id_front_path')->nullable();
            $table->string('id_back_path')->nullable();
            $table->timestamp('kyc_submitted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
