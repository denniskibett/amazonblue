<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('court_hearings')) {
            return;
        }

        Schema::create('court_hearings', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('legal_proceeding_id')->index('idx_legal_proceeding_id');
            $table->date('hearing_date')->index('idx_hearing_date');
            $table->time('hearing_time')->nullable();
            $table->string('court_room', 100)->nullable();
            $table->string('judge_name')->nullable();
            $table->text('result')->nullable();
            $table->date('next_hearing_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['legal_proceeding_id'], 'court_hearings_ibfk_1')->references(['id'])->on('legal_proceedings')->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_hearings');
    }
};
