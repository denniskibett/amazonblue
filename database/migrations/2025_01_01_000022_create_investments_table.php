<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('investments')) {
            return;
        }

        Schema::create('investments', function (Blueprint $table) {
            $table->collation = 'utf8mb3_general_ci';
            $table->charset = 'utf8mb3';

            $table->bigIncrements('id');
            $table->string('name');
            $table->enum('type', ['commodity', 'equity', 'bond', 'real_estate', 'startup', 'infrastructure', 'technology', 'agriculture', 'energy', 'other']);
            $table->enum('facility_type', ['short_term', 'long_term'])->default('long_term');
            $table->integer('duration_days')->nullable();
            $table->decimal('interest_rate', 5)->nullable();
            $table->string('sector', 100)->nullable();
            $table->string('sub_sector', 100)->nullable();
            $table->string('country', 100);
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('company_name')->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->date('incorporation_date')->nullable();
            $table->enum('legal_structure', ['sole_proprietorship', 'partnership', 'llc', 'corporation', 'non_profit'])->nullable();
            $table->decimal('ebitda_pre_investment', 15)->nullable();
            $table->decimal('revenue_pre_investment', 15)->nullable();
            $table->decimal('net_profit_pre_investment', 15)->nullable();
            $table->decimal('total_assets_pre_investment', 15)->nullable();
            $table->decimal('total_liabilities_pre_investment', 15)->nullable();
            $table->decimal('current_value', 15)->default(0);
            $table->decimal('expected_return', 5)->nullable();
            $table->decimal('actual_return', 5)->nullable();
            $table->decimal('revenue_current', 15)->nullable();
            $table->decimal('profit_current', 15)->nullable();
            $table->decimal('valuation_current', 15)->nullable();
            $table->decimal('initial_amount', 15);
            $table->decimal('irr', 5)->nullable();
            $table->integer('payback_period_months')->nullable();
            $table->decimal('break_even_point', 15)->nullable();
            $table->date('purchase_date');
            $table->date('maturity_date')->nullable();
            $table->date('exit_date')->nullable();
            $table->enum('risk_rating', ['A', 'AA', 'AAA', 'BBB', 'BB', 'B', 'C'])->nullable();
            $table->longText('risk_factors')->collation('utf8mb4_bin')->nullable();
            $table->longText('stakeholders')->collation('utf8mb4_bin')->nullable();
            $table->string('pitch_deck_path')->nullable();
            $table->string('financial_model_path')->nullable();
            $table->string('due_diligence_path')->nullable();
            $table->longText('legal_docs')->collation('utf8mb4_bin')->nullable();
            $table->text('market_research')->nullable();
            $table->text('competitive_landscape')->nullable();
            $table->longText('swot_analysis')->collation('utf8mb4_bin')->nullable();
            $table->text('key_assumptions')->nullable();
            $table->longText('notes')->collation('utf8mb4_bin')->nullable();
            $table->enum('status', ['pipeline', 'due_diligence', 'active', 'matured', 'liquidated', 'write_off'])->nullable()->default('pipeline');
            $table->enum('stage', ['ideation', 'seed', 'startup', 'growth', 'expansion', 'mature'])->nullable();
            $table->longText('milestones')->collation('utf8mb4_bin')->nullable();
            $table->decimal('total_funding_raised', 15)->nullable()->default(0);
            $table->longText('funding_partners')->collation('utf8mb4_bin')->nullable();
            $table->longText('updates')->collation('utf8mb4_bin')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->unsignedBigInteger('updated_by')->nullable()->index('updated_by');

            $table->foreign(['created_by'], 'investments_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['updated_by'], 'investments_ibfk_2')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
