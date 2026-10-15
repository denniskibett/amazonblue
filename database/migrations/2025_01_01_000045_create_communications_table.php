<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('communications')) {
            return;
        }

        Schema::create('communications', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('case_id')->index('idx_case_id');
            $table->unsignedBigInteger('communication_type_id')->index('idx_communication_type_id');
            $table->unsignedBigInteger('communication_status_id')->nullable()->index('idx_communication_status_id');
            $table->enum('direction', ['outbound', 'inbound']);
            $table->string('recipient')->nullable();
            $table->string('recipient_phone', 50)->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->text('provider_response')->nullable();
            $table->integer('delivery_attempts')->nullable()->default(1);
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['case_id'], 'communications_ibfk_1')->references(['id'])->on('debt_recovery_cases')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['communication_type_id'], 'communications_ibfk_2')->references(['id'])->on('communication_types')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['communication_status_id'], 'communications_ibfk_3')->references(['id'])->on('communication_statuses')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['created_by'], 'communications_ibfk_4')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};
