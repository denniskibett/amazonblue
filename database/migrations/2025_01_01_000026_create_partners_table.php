<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched, only adding
        // account_type (house principal / interest accounts) where an older copy lacks it.
        if (Schema::hasTable('partners')) {
            if (! Schema::hasColumn('partners', 'account_type')) {
                Schema::table('partners', function (Blueprint $table) {
                    $table->enum('account_type', ['principal', 'interest', 'operating', 'reserve'])->default('principal')->after('type');
                });
            }

            return;
        }

        Schema::create('partners', function (Blueprint $table) {
            $table->collation = 'utf8mb3_general_ci';
            $table->charset = 'utf8mb3';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index('user_id');
            $table->string('name');
            $table->string('email')->unique('email');
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->enum('type', ['corporate', 'partner', 'business', 'company', 'individual'])->default('partner');
            $table->enum('account_type', ['principal', 'interest', 'operating', 'reserve'])->default('principal');
            $table->boolean('is_house_account')->default(false);
            $table->enum('status', ['active', 'inactive', 'suspended'])->nullable()->default('active');
            $table->decimal('profit_share_rate', 5)->nullable()->default(0);
            $table->decimal('broker_rate', 5)->default(40)->comment('Default broker fee share of interest, percent. Per-loan override on loans.broker_rate.');
            $table->decimal('max_loan_to_value', 5)->nullable()->default(75);
            $table->enum('risk_tolerance', ['conservative', 'moderate', 'aggressive'])->nullable()->default('moderate');
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('swift_code', 50)->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->decimal('current_balance', 15)->default(0);

            $table->foreign(['user_id'], 'partners_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
