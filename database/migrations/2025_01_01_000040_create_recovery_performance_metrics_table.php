<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('recovery_performance_metrics')) {
            return;
        }

        Schema::create('recovery_performance_metrics', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('officer_id')->index('idx_officer_id');
            $table->date('metric_date')->index('idx_metric_date');
            $table->integer('cases_assigned')->nullable()->default(0);
            $table->integer('cases_resolved')->nullable()->default(0);
            $table->decimal('recovery_amount', 15)->nullable()->default(0);
            $table->decimal('collection_rate', 5)->nullable()->default(0);
            $table->integer('avg_days_to_resolve')->nullable()->default(0);
            $table->decimal('contact_rate', 5)->nullable()->default(0);
            $table->decimal('promise_to_pay_rate', 5)->nullable()->default(0);
            $table->decimal('performance_score', 5)->nullable()->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign(['officer_id'], 'recovery_performance_metrics_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_performance_metrics');
    }
};
