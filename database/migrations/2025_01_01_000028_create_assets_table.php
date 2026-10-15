<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('assets')) {
            return;
        }

        Schema::create('assets', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_user_id');
            $table->unsignedBigInteger('asset_type_id')->index('idx_asset_type_id');
            $table->string('asset_name');
            $table->text('description')->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->decimal('estimated_value', 15)->nullable();
            $table->string('lien_holder')->nullable();
            $table->decimal('lien_amount', 15)->nullable();
            $table->boolean('is_collateral')->nullable()->default(false)->index('idx_is_collateral');
            $table->unsignedBigInteger('collateral_for_loan_id')->nullable()->index('collateral_for_loan_id');
            $table->date('valuation_date')->nullable();
            $table->string('valuation_by')->nullable();
            $table->text('location')->nullable();
            $table->enum('status', ['owned', 'financed', 'leased', 'repossessed', 'sold'])->nullable()->default('owned');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes()->index();

            $table->foreign(['user_id'], 'assets_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['asset_type_id'], 'assets_ibfk_2')->references(['id'])->on('asset_types')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['collateral_for_loan_id'], 'assets_ibfk_3')->references(['id'])->on('loans')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
