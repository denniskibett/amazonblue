<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('investment_fundings')) {
            return;
        }

        Schema::create('investment_fundings', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('investment_id')->index('investment_fundings_investment_id_foreign');
            $table->unsignedBigInteger('partner_id')->index('investment_fundings_partner_id_foreign');
            $table->unsignedBigInteger('partner_transaction_id')->nullable()->index('investment_fundings_partner_transaction_id_foreign');
            $table->decimal('amount_committed', 15)->default(0);
            $table->decimal('amount_disbursed', 15)->default(0);
            $table->decimal('amount_returned', 15)->default(0);
            $table->enum('status', ['active', 'settled', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign(['investment_id'])->references(['id'])->on('investments')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['partner_id'])->references(['id'])->on('partners')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['partner_transaction_id'])->references(['id'])->on('partner_transactions')->onUpdate('no action')->onDelete('set null');
        });

        // partner_transactions also references investment_fundings (the two tables point at each other),
        // so that key is added here, once both tables exist.
        Schema::table('partner_transactions', function (Blueprint $table) {
            $table->foreign(['investment_funding_id'], 'pt_investment_funding_fk')->references(['id'])->on('investment_fundings')->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('partner_transactions')) {
            Schema::table('partner_transactions', function (Blueprint $table) {
                $table->dropForeign('pt_investment_funding_fk');
            });
        }

        Schema::dropIfExists('investment_fundings');
    }
};
